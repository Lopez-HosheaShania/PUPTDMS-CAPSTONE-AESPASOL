<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\OIDCController;
use App\Http\Controllers\Dentist\WalkInController;
use App\Models\ExternalAdminAccess;
use App\Models\ExternalAdminProfile;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CmsApiService;
use App\Services\ConcurrentSessionService;
use App\Services\FacultyApiService;
use App\Services\StudentApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        config(['services.ocms.base_url' => 'https://cms.example.test/api', 'services.ocms.api_key' => 'test-cms-key']);
        foreach (['admin', 'dentist', 'patient'] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        }
    }

    public function test_assign_cms_list_reads_config_and_returns_uuid_and_documented_field_aliases(): void
    {
        $this->actingAs($this->operator());
        Http::fake(['https://cms.example.test/api/external/admins*' => Http::response([
            'success' => true, 'data' => [$this->admin()],
        ])]);

        $this->getJson(route('admin.external_admins.search', ['search' => 'cms']))->assertOk()
            ->assertJsonPath('success', true)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.admin_id', 'cms-030')
            ->assertJsonPath('data.0.address', 'Taguig')
            ->assertJsonPath('data.0.contact_number', '09123456789');
        Http::assertSent(fn (Request $request) => $request->hasHeader('X-External-Api-Key', 'test-cms-key')
            && $request['search'] === 'cms');
    }

    public function test_missing_configuration_is_an_error_and_sends_no_unauthenticated_request(): void
    {
        $this->actingAs($this->operator());
        config(['services.ocms.api_key' => null]);
        $this->getJson(route('admin.external_admins.search'))->assertStatus(500)->assertJsonPath('success', false);
        Http::assertNothingSent();
    }

    public static function directoryFailures(): array
    {
        return [
            'unauthorized' => [['message' => 'Invalid key'], 401],
            'server error' => [['message' => 'Unavailable'], 503],
            'invalid JSON' => ['Not JSON', 200],
            'failure in successful HTTP response' => [['success' => false, 'data' => []], 200],
            'missing directory data' => [['success' => true], 200],
            'profile without canonical identifier' => [['data' => [['email' => 'cms@example.test']]], 200],
        ];
    }

    #[DataProvider('directoryFailures')]
    public function test_failed_directory_is_not_reported_as_successful_empty_list(array|string $body, int $status): void
    {
        $this->actingAs($this->operator());
        Http::fake(['https://cms.example.test/api/external/admins' => Http::response($body, $status)]);
        $this->getJson(route('admin.external_admins.search'))->assertStatus(500)->assertJsonPath('success', false);
    }

    public function test_empty_directory_is_a_successful_empty_list(): void
    {
        $this->actingAs($this->operator());
        Http::fake(['https://cms.example.test/api/external/admins' => Http::response(['success' => true, 'data' => []])]);
        $this->getJson(route('admin.external_admins.search'))->assertOk()->assertJsonPath('success', true)->assertJsonCount(0, 'data');
    }

    public function test_legacy_email_lookup_resolves_via_directory_instead_of_requesting_email_detail_url(): void
    {
        Http::fake(['https://cms.example.test/api/external/admins' => Http::response(['data' => [$this->admin()]])]);
        $admin = app(CmsApiService::class)->getAdmin('cms@example.test');
        $this->assertSame('cms-030', $admin['admin_id']);
        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'cms%40example.test'));
    }

    public function test_profile_details_use_canonical_uuid(): void
    {
        $this->actingAs($this->operator());
        Http::fake(['https://cms.example.test/api/external/admins/cms-030' => Http::response(['data' => $this->admin()])]);
        $this->getJson(route('admin.external_admins.show', 'cms-030'))->assertOk()->assertJsonPath('data.admin_id', 'cms-030');
    }

    public function test_assignment_reuses_legacy_profile_and_verifies_profile_data_with_cms(): void
    {
        $this->actingAs($this->operator());
        $profile = ExternalAdminProfile::create(['external_admin_id' => 'cms@example.test', 'email' => 'cms@example.test']);
        $access = ExternalAdminAccess::create([
            'external_admin_profile_id' => $profile->id, 'has_cms_access' => true, 'cms_role' => 'patient', 'cms_status' => 'active',
        ]);
        Http::fake(['https://cms.example.test/api/external/admins/cms-030' => Http::response(['data' => $this->admin()])]);

        $this->post(route('admin.assign-cms-access.store'), [
            'external_admin_id' => 'cms-030', 'email' => 'untrusted-form@example.test',
            'cms_role' => 'patient', 'cms_status' => 'active',
        ])->assertRedirect(route('admin.assign-cms-access'))->assertSessionHas('success');
        $this->assertSame('cms-030', $profile->fresh()->external_admin_id);
        $this->assertSame('cms@example.test', $profile->fresh()->email);
        $this->assertSame('patient', $access->fresh()->cms_role);
        $this->assertDatabaseCount('external_admin_profiles', 1);
        $this->assertDatabaseCount('external_admin_accesses', 1);
    }

    public static function assignedAccounts(): array
    {
        $cases = [];
        foreach (['admin', 'dentist', 'patient'] as $role) {
            foreach (['active', 'inactive'] as $status) {
                $cases[$role.' '.$status] = [$role, $status];
            }
        }

        return $cases;
    }

    #[DataProvider('assignedAccounts')]
    public function test_assignment_immediately_lists_local_account_without_waiting_for_sso(string $role, string $status): void
    {
        $this->actingAs($this->operator());
        Http::fake(['https://cms.example.test/api/external/admins/cms-030' => Http::response(['data' => $this->admin()])]);

        $this->post(route('admin.assign-cms-access.store'), [
            'external_admin_id' => 'cms-030', 'cms_role' => $role, 'cms_status' => $status,
        ])->assertSessionHas('success');

        $user = User::where('email', 'cms@example.test')->first();
        $this->assertNotNull($user, 'A verified CMS assignment should immediately create a local user.');
        $this->assertSame($role, $user->role->slug);
        $this->assertSame($status, $user->status);
        $this->assertSame('CMS Patient', $user->name);
        $this->assertNull($user->password);
        $this->assertNull($user->sso_user_id, 'The CMS profile UUID must not be assumed to be the SSO subject.');
        $this->getJson(route('admin.user_management', ['search' => 'cms@example.test']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.id', $user->id)
            ->assertJsonPath('users.0.email', 'cms@example.test')
            ->assertJsonPath('users.0.status', $status);
    }

    public function test_reassignment_reuses_existing_account_and_preserves_credentials(): void
    {
        $this->actingAs($this->operator());
        $user = User::create([
            'name' => 'Existing CMS Account', 'email' => 'CMS@example.test',
            'role_id' => Role::where('slug', 'patient')->value('id'), 'status' => 'active',
            'password' => bcrypt('existing-password'), 'sso_user_id' => 'real-idp-subject',
            'access_token' => 'existing-access-token', 'refresh_token' => 'existing-refresh-token',
        ]);
        $password = $user->password;
        Http::fake(['https://cms.example.test/api/external/admins/cms-030' => Http::response(['data' => $this->admin()])]);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post(route('admin.assign-cms-access.store'), [
                'external_admin_id' => 'cms-030', 'cms_role' => 'patient', 'cms_status' => 'active',
            ])->assertSessionHas('success');
        }

        $this->assertSame($user->id, User::where('email', 'cms@example.test')->sole()->id);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame('real-idp-subject', $user->fresh()->sso_user_id);
        $this->assertSame('existing-access-token', $user->fresh()->access_token);
        $this->assertSame('existing-refresh-token', $user->fresh()->refresh_token);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('external_admin_profiles', 1);
        $this->assertDatabaseCount('external_admin_accesses', 1);
    }

    public function test_account_creation_failure_rolls_back_profile_and_assignment(): void
    {
        $this->actingAs($this->operator());
        User::creating(function (User $user) {
            if ($user->email === 'cms@example.test') {
                throw new \RuntimeException('Simulated account creation failure');
            }
        });
        Http::fake(['https://cms.example.test/api/external/admins/cms-030' => Http::response(['data' => $this->admin()])]);
        $this->post(route('admin.assign-cms-access.store'), [
            'external_admin_id' => 'cms-030', 'cms_role' => 'patient', 'cms_status' => 'active',
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('external_admin_profiles', 0);
        $this->assertDatabaseCount('external_admin_accesses', 0);
        $this->assertDatabaseMissing('users', ['email' => 'cms@example.test']);
    }

    public function test_cms_profile_without_valid_email_cannot_leave_an_unlisted_assignment(): void
    {
        $this->actingAs($this->operator());
        Http::fake(['https://cms.example.test/api/external/admins/cms-030' => Http::response([
            'data' => array_replace($this->admin(), ['email' => '']),
        ])]);
        $this->post(route('admin.assign-cms-access.store'), [
            'external_admin_id' => 'cms-030', 'cms_role' => 'patient', 'cms_status' => 'active',
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('external_admin_profiles', 0);
        $this->assertDatabaseCount('external_admin_accesses', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public static function sessionChanges(): array
    {
        return [
            'role changed' => ['dentist', 'active'],
            'account deactivated' => ['patient', 'inactive'],
        ];
    }

    #[DataProvider('sessionChanges')]
    public function test_assignment_revokes_existing_sessions_when_role_or_status_changes(string $role, string $status): void
    {
        config(['session.driver' => 'database', 'session.table' => 'sessions', 'concurrent-sessions.enabled' => true]);
        $operator = $this->operator();
        $this->actingAs($operator);
        $user = User::create([
            'name' => 'CMS Patient', 'email' => 'cms@example.test',
            'role_id' => Role::where('slug', 'patient')->value('id'), 'status' => 'active',
        ]);
        DB::table('sessions')->insert([
            'id' => 'cms-existing-session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'Test browser', 'payload' => base64_encode(serialize([])), 'last_activity' => now()->timestamp,
        ]);
        Http::fake(['https://cms.example.test/api/external/admins/cms-030' => Http::response(['data' => $this->admin()])]);
        $this->post(route('admin.assign-cms-access.store'), [
            'external_admin_id' => 'cms-030', 'cms_role' => $role, 'cms_status' => $status,
        ])->assertSessionHas('success');
        $this->assertSame($role, $user->fresh()->role->slug);
        $this->assertSame($status, $user->fresh()->status);
        $this->assertDatabaseMissing('sessions', ['id' => 'cms-existing-session']);
        $this->assertAuthenticatedAs($operator);
    }

    public function test_first_sso_login_reuses_the_account_created_by_assignment(): void
    {
        $this->actingAs($this->operator());
        config(['services.oidc.token_url' => 'https://idp.example.test/token', 'services.oidc.me_url' => 'https://idp.example.test/me']);
        Http::fake([
            'https://cms.example.test/api/external/admins/cms-030' => Http::response(['data' => $this->admin()]),
            'https://cms.example.test/api/external/admins' => Http::response(['data' => [$this->admin()]]),
            'https://idp.example.test/token' => Http::response(['access_token' => 'new-access-token']),
            'https://idp.example.test/me' => Http::response([
                'id' => 'actual-idp-subject', 'email' => 'cms@example.test', 'name' => 'CMS Patient', 'roles' => [],
            ]),
        ]);
        $this->post(route('admin.assign-cms-access.store'), [
            'external_admin_id' => 'cms-030', 'cms_role' => 'patient', 'cms_status' => 'active',
        ])->assertSessionHas('success');
        $accountId = User::where('email', 'cms@example.test')->sole()->id;
        auth()->logout();
        $this->mock(StudentApiService::class, function ($mock) {
            $mock->shouldReceive('getStudentByEmail')->andReturn(['data' => []]);
            $mock->shouldReceive('searchStudents')->andReturn([]);
        });
        $this->mock(FacultyApiService::class)->shouldReceive('getFaculties')->andReturn([]);
        \Tymon\JWTAuth\Facades\JWTAuth::shouldReceive('fromUser')->once()->andReturn('test-jwt');

        $this->withSession(['oidc_state' => 'cms-state'])->get(route('oidc.callback', [
            'state' => 'cms-state', 'code' => 'test-code',
        ]))->assertRedirect(route('homepage'));
        $user = User::where('email', 'cms@example.test')->sole();
        $this->assertSame($accountId, $user->id);
        $this->assertSame('actual-idp-subject', $user->sso_user_id);
        $this->assertSame('administrative', $user->patient->classification);
        $this->assertDatabaseCount('users', 2);
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_verification_does_not_create_access(): void
    {
        $this->actingAs($this->operator());
        Http::fake(['https://cms.example.test/api/external/admins/cms-030' => Http::response([], 404)]);
        $this->from(route('admin.assign-cms-access'))->post(route('admin.assign-cms-access.store'), [
            'external_admin_id' => 'cms-030', 'cms_role' => 'admin', 'cms_status' => 'active',
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('external_admin_profiles', 0);
        $this->assertDatabaseCount('external_admin_accesses', 0);
    }

    public function test_walk_in_directory_result_contains_cms_uuid(): void
    {
        Http::fake(['https://cms.example.test/api/external/admins*' => Http::response(['data' => [$this->admin()]])]);
        $controller = app(WalkInController::class);
        $results = (new \ReflectionMethod($controller, 'searchExternalAdminPatients'))->invoke($controller, 'cms', 10);
        $this->assertCount(1, $results);
        $selection = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($results[0]['selection_token']), true);
        $this->assertSame('cms-030', $selection['data']['external_id']);
        $this->assertSame('Administrative Personnel', $results[0]['type']);
    }

    public function test_login_sync_repairs_existing_assignment_id_and_preserves_access(): void
    {
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name' => 'Patient']);
        $user = User::create(['name' => 'CMS Patient', 'email' => 'cms@example.test', 'role_id' => $role->id, 'status' => 'active']);
        $patient = Patient::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'password' => bcrypt('test'), 'classification' => 'dependent_alumni']);
        $profile = ExternalAdminProfile::create(['external_admin_id' => $user->email, 'email' => $user->email]);
        $access = ExternalAdminAccess::create([
            'external_admin_profile_id' => $profile->id, 'has_cms_access' => true, 'cms_role' => 'patient', 'cms_status' => 'active',
        ]);
        $before = $access->fresh()->getRawOriginal();
        Http::fake(['https://cms.example.test/api/external/admins' => Http::response(['data' => [$this->admin()]])]);
        $this->sync($patient, $access);
        $this->assertSame('administrative', $patient->fresh()->classification);
        $this->assertSame('cms-030', $profile->fresh()->external_admin_id);
        $this->assertSame($before, $access->fresh()->getRawOriginal());
        $this->assertSame($role->id, $user->fresh()->role_id);
    }

    public function test_cms_outage_preserves_existing_administrative_classification(): void
    {
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name' => 'Patient']);
        $user = User::create(['name' => 'CMS Patient', 'email' => 'cms@example.test', 'role_id' => $role->id, 'status' => 'active']);
        $patient = Patient::create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'password' => bcrypt('test'), 'classification' => 'administrative']);
        Http::fake(['https://cms.example.test/api/external/admins' => Http::response([], 503)]);
        $this->sync($patient);
        $this->assertSame('administrative', $patient->fresh()->classification);
        $this->assertDatabaseCount('external_admin_accesses', 0);
    }

    public function test_duplicate_email_directory_profiles_are_not_used_for_classification(): void
    {
        Http::fake(['https://cms.example.test/api/external/admins' => Http::response(['data' => [
            $this->admin(), array_replace($this->admin(), ['admin_uuid' => 'another-cms']),
        ]])]);
        $this->expectException(\RuntimeException::class);
        app(CmsApiService::class)->findByEmail('cms@example.test');
    }

    public function test_shared_profile_displays_administrative_classification(): void
    {
        $this->actingAs($this->operator());
        $patient = Patient::create([
            'name' => 'CMS Patient', 'email' => 'cms@example.test', 'password' => bcrypt('test'), 'classification' => 'administrative',
        ]);
        $this->get(route('admin.admin.patient.profile', $patient))->assertOk()->assertSee('Administrative Personnel');
    }

    public static function institutionalCategories(): array
    {
        return [['student'], ['faculty']];
    }

    #[DataProvider('institutionalCategories')]
    public function test_cms_membership_preserves_explicit_student_or_faculty_without_identifiers(string $category): void
    {
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name' => 'Patient']);
        $user = User::create(['name' => 'CMS Patient', 'email' => 'cms@example.test', 'role_id' => $role->id, 'status' => 'active']);
        $patient = Patient::create([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'password' => bcrypt('test'), 'classification' => $category,
        ]);
        Http::fake(['https://cms.example.test/api/external/admins' => Http::response(['data' => [$this->admin()]])]);
        $this->sync($patient);
        $this->assertSame($category, $patient->fresh()->classification);
        $this->assertDatabaseCount('external_admin_accesses', 0);
    }

    private function sync(Patient $patient, ?ExternalAdminAccess $access = null): void
    {
        $faculty = \Mockery::mock(FacultyApiService::class);
        $faculty->shouldReceive('getFaculties')->andReturn([]);
        $students = \Mockery::mock(StudentApiService::class);
        $controller = new OIDCController($faculty, $students, app(ConcurrentSessionService::class));
        (new \ReflectionMethod($controller, 'syncPatientRecord'))->invoke(
            $controller, $patient->user, $patient->name, $patient->email, 'sso-cms', [], $patient, $access, null, []
        );
    }

    private function admin(): array
    {
        return ['admin_uuid' => 'cms-030', 'first_name' => 'CMS', 'last_name' => 'Patient',
            'name' => 'CMS Patient', 'email' => 'cms@example.test', 'home_address' => 'Taguig',
            'contact_no' => '09123456789', 'status' => 'active', 'access_level' => 'superadmin'];
    }

    private function operator(): User
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        foreach (['view_cms_integration', 'create_cms_integration', 'view_patient_profiles', 'view_account_details', 'update_user_role'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'CMS']);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return User::create(['name' => 'Operator', 'email' => 'operator@example.test', 'role_id' => $role->id, 'status' => 'active']);
    }
}
