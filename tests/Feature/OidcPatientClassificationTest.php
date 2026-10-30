<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\OIDCController;
use App\Http\Controllers\Dentist\DentalServicesRecordController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\AppointmentReportRecords;
use App\Services\ConcurrentSessionService;
use App\Services\FacultyApiService;
use App\Services\StudentApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OidcPatientClassificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ocms.base_url' => null, 'services.ocms.api_key' => null]);
        Http::preventStrayRequests();
    }

    public static function categories(): array
    {
        return [
            'saved faculty identifier without API' => [['faculty_code' => 'FAC-001'], [], [], [], 'faculty'],
            'saved student identifier without API' => [['student_no' => '2026-001', 'course_code' => 'BSIT', 'course_name' => 'Information Technology', 'year_level' => 2, 'section' => '1'], [], [], [], 'student'],
            'student API profile' => [[], ['studentNumber' => '2026-002'], [], [], 'student'],
            'student API profile without number' => [[], ['email' => 'category@example.test'], [], [], 'student'],
            'OIDC student without student API' => [[], [], ['Student'], [], 'student'],
            'OIDC faculty without faculty API' => [[], [], ['Faculty'], [], 'faculty'],
            'faculty API match without code' => [[], [], [], [['email' => 'category@example.test']], 'faculty'],
            'saved student category without identifiers' => [['classification' => 'student'], [], [], [], 'student'],
            'saved faculty category without identifiers' => [['classification' => 'faculty'], [], [], [], 'faculty'],
            'saved administrative category without API' => [['classification' => 'administrative'], [], [], [], 'administrative'],
            'saved alumni' => [['classification' => 'alumni'], [], [], [], 'alumni'],
            'saved dependent' => [['classification' => 'dependent'], [], [], [], 'dependent'],
            'saved guest' => [['classification' => 'guest'], [], [], [], 'guest'],
            'existing fallback' => [['classification' => 'dependent_alumni'], [], [], [], 'dependent_alumni'],
            'no category evidence' => [[], [], [], [], 'dependent_alumni'],
        ];
    }

    #[DataProvider('categories')]
    public function test_category_and_saved_identifiers_survive_sync_and_reach_appointments(
        array $saved, array $studentData, array $incomingRoles, array $faculties, string $expected
    ): void {
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name' => 'Patient']);
        $user = User::create(['name' => 'Category Patient', 'email' => 'category@example.test', 'role_id' => $role->id, 'status' => 'active']);
        $patient = Patient::create(array_merge([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'password' => bcrypt('password'), 'classification' => 'dependent_alumni',
        ], $saved));

        $students = Mockery::mock(StudentApiService::class);
        $students->shouldReceive('getPersonalInfoByStudentNumber')->andReturn(['data' => []]);
        $students->shouldReceive('getAddressesByStudentNumber')->andReturn(['data' => []]);
        $faculty = Mockery::mock(FacultyApiService::class);
        $faculty->shouldReceive('getFaculties')->andReturn($faculties);
        $controller = new OIDCController($faculty, $students, app(ConcurrentSessionService::class));

        $synced = (new \ReflectionMethod($controller, 'syncPatientRecord'))->invoke(
            $controller, $user, $user->name, $user->email, 'sso-1', $studentData, $patient, null, null, $incomingRoles
        )->fresh();

        $this->assertSame($expected, $synced->classification);
        foreach (array_diff_key($saved, ['classification' => true]) as $field => $value) {
            $this->assertEquals($value, $synced->{$field}, $field . ' must not be erased');
        }
        $this->assertSame($role->id, $user->fresh()->role_id);
        $appointment = Appointment::create([
            'patient_id' => $synced->id, 'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:00:00', 'status' => 'completed', 'service_type' => 'Cleaning',
        ]);
        $this->assertSame($expected, $appointment->fresh()->patient->classification);
        $row = app(AppointmentReportRecords::class)->between(now()->startOfDay(), now()->endOfDay())->first();
        $this->assertSame(in_array($expected, ['alumni', 'dependent', 'dependent_alumni'], true) ? 'Dependent' : ucfirst($expected), $row->office_type);
        $response = (new DentalServicesRecordController)->data(Request::create('/report/dental-services/data', 'GET', ['month' => now()->format('Y-m')]))->getData(true);
        $this->assertSame(in_array($expected, ['alumni', 'dependent', 'dependent_alumni', 'guest'], true) ? 'Dependent' : ucfirst($expected), $response['records'][0]['department']);
    }

    public static function loginRoles(): array
    {
        return [
            'new student without GUISIS' => [null, ['Student'], 'patient', 'student', 'homepage'],
            'new faculty without faculty API' => [null, 'Faculty', 'patient', 'faculty', 'homepage'],
            'new patient without category evidence' => [null, [], 'patient', 'dependent_alumni', 'homepage'],
            'CMS membership without local access assignment' => [null, [], 'patient', 'administrative', 'homepage'],
            'dentist unchanged' => ['dentist', ['Dentist'], 'dentist', null, 'dentist.dentist.dashboard'],
            'local admin unchanged' => ['admin', ['Faculty'], 'admin', null, 'admin.admin.dashboard'],
            'custom role unchanged' => ['receptionist', ['Student'], 'receptionist', null, 'login'],
        ];
    }

    #[DataProvider('loginRoles')]
    public function test_login_keeps_access_roles_separate_from_patient_categories(
        ?string $localRole, array|string $incomingRoles, string $expectedRole, ?string $category, string $redirect
    ): void {
        $this->seed([
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);
        config(['services.oidc.token_url' => 'https://idp.example.test/token', 'services.oidc.me_url' => 'https://idp.example.test/me']);
        Http::preventStrayRequests();
        Http::fake([
            'https://idp.example.test/token' => Http::response(['access_token' => 'test-token']),
            'https://idp.example.test/me' => Http::response([
                'id' => 'sso-1', 'email' => 'login@example.test', 'name' => 'Login Patient', 'roles' => $incomingRoles,
            ]),
        ]);
        if ($category === 'administrative') {
            config(['services.ocms.base_url' => 'https://cms.example.test/api', 'services.ocms.api_key' => 'test-cms-key']);
            Http::fake(['https://cms.example.test/api/external/admins' => Http::response([
                'success' => true, 'data' => [['admin_uuid' => 'cms-001', 'email' => 'login@example.test', 'access_level' => 'superadmin']],
            ])]);
        }
        if ($localRole) {
            $role = Role::firstOrCreate(['slug' => $localRole], ['name' => ucfirst($localRole)]);
            User::create(['name' => 'Login Patient', 'email' => 'login@example.test', 'role_id' => $role->id, 'status' => 'active']);
        }
        $this->mock(StudentApiService::class, function ($mock) {
            $mock->shouldReceive('getStudentByEmail')->once()->andThrow(new \RuntimeException('GUISIS unavailable'));
            $mock->shouldReceive('searchStudents')->once()->andReturn([]);
        });
        $this->mock(FacultyApiService::class, function ($mock) {
            $mock->shouldReceive('getFaculties')->andThrow(new \RuntimeException('Faculty API unavailable'));
        });
        JWTAuth::shouldReceive('fromUser')->once()->andReturn('test-jwt');

        $this->withSession(['oidc_state' => 'test-state'])
            ->get(route('oidc.callback', ['state' => 'test-state', 'code' => 'test-code']))
            ->assertRedirect(route($redirect));

        $user = User::where('email', 'login@example.test')->firstOrFail();
        $this->assertSame($expectedRole, $user->role->slug);
        $this->assertSame($category, $user->patient?->classification);
        if ($category !== null) {
            $this->assertAuthenticatedAs($user);
            $this->assertSame($user->patient->id, session('patient_id'));
            $homepage = $this->get(route('homepage'))->assertOk();
            if (in_array($category, ['student', 'faculty', 'administrative'], true)) {
                $label = $category === 'administrative' ? 'Administrative Personnel' : ucfirst($category);
                $homepage->assertSee('roleLabel: "' . $label . '"', false);
            }
            $this->assertDatabaseCount('external_admin_accesses', 0);
            $this->assertDatabaseCount('external_admin_profiles', 0);
            $this->get('/admin/dashboard')->assertForbidden();
            $this->get('/dentist/dashboard')->assertForbidden();
        } elseif ($redirect !== 'login') {
            $this->assertAuthenticatedAs($user);
            $this->get(route($redirect))->assertOk();
        }
    }
}
