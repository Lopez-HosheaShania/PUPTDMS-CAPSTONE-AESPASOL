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
        foreach (['view_cms_integration', 'create_cms_integration', 'view_patient_profiles'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'CMS']);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return User::create(['name' => 'Operator', 'email' => 'operator@example.test', 'role_id' => $role->id, 'status' => 'active']);
    }
}
