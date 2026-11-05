<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ExternalAdminAccess;
use App\Models\ExternalAdminProfile;
use App\Models\Patient;
use App\Models\PatientFacultyInformation;
use App\Models\Role;
use App\Models\User;
use App\Services\CmsApiService;
use App\Services\FacultyApiService;
use App\Services\StudentApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SyncPatientClassificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->mock(CmsApiService::class)->shouldReceive('getAdmins')->andReturn([
            ['admin_id' => 'unrelated-cms', 'email' => 'unrelated-cms@example.test'],
        ]);
    }

    public function test_faculty_repair_previews_then_restores_a_legacy_patient_without_changing_account_or_visits(): void
    {
        $patient = $this->patient();
        $other = $this->patient(['email' => 'other@example.test']);
        $userBefore = $patient->user->getRawOriginal();
        $appointment = Appointment::create([
            'patient_id' => $patient->id, 'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:00:00', 'status' => 'completed', 'service_type' => 'Cleaning',
        ]);
        $faculty = $this->mock(FacultyApiService::class);
        $faculty->shouldReceive('getFaculties')->times(3)->andReturn([
            ['email' => ' FACULTY@EXAMPLE.TEST ', 'faculty_code' => 'FAC-030'],
        ]);
        $this->mock(StudentApiService::class)->shouldNotReceive('getAllStudents');
        $options = ['--patient' => [$patient->id], '--source' => 'faculty'];

        $this->artisan('patients:reconcile-classifications', $options)
            ->expectsOutputToContain('PREVIEW ONLY')
            ->expectsOutputToContain('Proposed repairs: 1')->assertSuccessful();
        $this->assertNull($patient->fresh()->classification);
        $this->assertDatabaseCount('patient_faculty_information', 0);

        $this->artisan('patients:reconcile-classifications', $options + ['--apply' => true])
            ->expectsOutputToContain('Repaired: 1')->assertSuccessful();
        $this->assertSame('faculty', $patient->fresh()->classification);
        $this->assertSame('FAC-030', $patient->fresh()->faculty_code);
        $this->assertNull($other->fresh()->classification);
        $this->assertSame($userBefore, $patient->user->fresh()->getRawOriginal());
        $this->assertSame($patient->id, $appointment->fresh()->patient_id);

        $updatedAt = $patient->fresh()->getRawOriginal('updated_at');
        $this->travel(1)->hour();
        $this->artisan('patients:reconcile-classifications', $options + ['--apply' => true])
            ->expectsOutputToContain('Repaired: 0')->assertSuccessful();
        $this->assertSame($updatedAt, $patient->fresh()->getRawOriginal('updated_at'));
        $this->assertDatabaseCount('patient_faculty_information', 1);
    }

    public function test_student_repair_restores_missing_academic_fields_and_preserves_existing_information(): void
    {
        $patient = $this->patient([
            'classification' => 'dependent_alumni', 'course_name' => 'Existing Program', 'phone' => '09123456789',
        ]);
        $this->mock(FacultyApiService::class)->shouldNotReceive('getFaculties');
        $this->mock(StudentApiService::class)->shouldReceive('getAllStudents')->once()->andReturn([
            ['emailAddress' => $patient->email, 'studentNumber' => '2026-030',
                'program' => ['code' => 'BSIT', 'name' => 'New Program'], 'yearLevel' => 3, 'section' => '2'],
        ]);

        $this->artisan('patients:reconcile-classifications', ['--source' => 'student', '--apply' => true])
            ->expectsOutputToContain('Repaired: 1')->assertSuccessful();

        $patient->refresh();
        $this->assertSame('student', $patient->classification);
        $this->assertSame('2026-030', $patient->student_no);
        $this->assertSame('BSIT', $patient->course_code);
        $this->assertSame('Existing Program', $patient->course_name);
        $this->assertEquals(3, $patient->year_level);
        $this->assertSame('2', $patient->section);
        $this->assertSame('09123456789', $patient->phone);
    }

    public function test_default_preview_and_apply_can_repair_saved_identifiers_without_network_access(): void
    {
        $faculty = $this->patient(['faculty_code' => 'FAC-001']);
        $student = $this->patient(['email' => 'student@example.test', 'student_no' => '2026-001', 'classification' => 'dependent']);
        $unknown = $this->patient(['email' => 'unknown@example.test']);

        $this->artisan('patients:reconcile-classifications')->expectsOutputToContain('Proposed repairs: 2')->assertSuccessful();
        $this->assertNull($faculty->fresh()->classification);
        $this->assertSame('dependent', $student->fresh()->classification);
        $this->artisan('patients:reconcile-classifications', ['--apply' => true])
            ->expectsOutputToContain('Repaired: 2')->assertSuccessful();
        $this->assertSame('faculty', $faculty->fresh()->classification);
        $this->assertSame('student', $student->fresh()->classification);
        $this->assertNull($unknown->fresh()->classification);
        Http::assertNothingSent();
    }

    public static function conflicts(): array
    {
        return [
            'both directories' => [[], [['email' => 'faculty@example.test']], [['email' => 'faculty@example.test']], 'conflicting student and faculty evidence'],
            'saved student identity' => [['student_no' => '2026-030'], [['email' => 'faculty@example.test']], [['email' => 'unrelated@example.test']], 'conflicting student and faculty evidence'],
            'explicit student category' => [['classification' => 'student'], [['email' => 'faculty@example.test']], [['email' => 'unrelated@example.test']], 'conflicting student and faculty evidence'],
            'duplicate faculty matches' => [[], [['email' => 'faculty@example.test'], ['email' => 'faculty@example.test']], [['email' => 'unrelated@example.test']], 'multiple directory matches'],
            'different faculty code' => [['faculty_code' => 'ORIGINAL'], [['email' => 'faculty@example.test', 'faculty_code' => 'DIFFERENT']], [['email' => 'unrelated@example.test']], 'directory identifier differs'],
            'explicit alumni' => [['classification' => 'alumni'], [['email' => 'faculty@example.test']], [['email' => 'unrelated@example.test']], 'explicit category requires manual review'],
            'no identity match' => [[], [['email' => 'unrelated@example.test', 'name' => 'Faculty Patient']], [['email' => 'unrelated@example.test']], 'no student, faculty, or CMS evidence'],
        ];
    }

    #[DataProvider('conflicts')]
    public function test_ambiguous_and_explicit_records_are_skipped(array $attributes, array $faculty, array $students, string $reason): void
    {
        $patient = $this->patient($attributes);
        $before = $patient->fresh()->getRawOriginal();
        $this->mock(FacultyApiService::class)->shouldReceive('getFaculties')->once()->andReturn($faculty);
        $this->mock(StudentApiService::class)->shouldReceive('getAllStudents')->once()->andReturn($students);

        $this->artisan('patients:reconcile-classifications', ['--source' => 'all', '--apply' => true])
            ->expectsOutputToContain($reason)->expectsOutputToContain('Repaired: 0')->assertSuccessful();
        $this->assertSame($before, $patient->fresh()->getRawOriginal());
    }

    public function test_identifiers_already_owned_by_another_patient_are_not_copied(): void
    {
        $patient = $this->patient();
        $owner = $this->patient(['email' => 'owner@example.test', 'faculty_code' => 'FAC-SHARED']);
        $this->mock(FacultyApiService::class)->shouldReceive('getFaculties')->once()->andReturn([
            ['email' => $patient->email, 'faculty_code' => 'FAC-SHARED'],
        ]);

        $this->artisan('patients:reconcile-classifications', [
            '--source' => 'faculty', '--patient' => [$patient->id], '--apply' => true,
        ])->expectsOutputToContain('institutional identifier belongs to another patient')->assertSuccessful();
        $this->assertNull($patient->fresh()->classification);
        $this->assertNull($patient->fresh()->faculty_code);
        $this->assertSame('FAC-SHARED', $owner->fresh()->faculty_code);
    }

    public function test_duplicate_patient_email_is_not_automatically_linked(): void
    {
        $patient = $this->patient();
        $other = $this->patient(['email' => 'other@example.test']);
        // Historical imports can have duplicate identities even if a current unique index prevents new ones.
        // Use the normalized equality (case/space insensitive) without violating the SQLite unique index.
        DB::table('patients')->where('id', $other->id)->update(['email' => ' FACULTY@EXAMPLE.TEST ']);
        $this->mock(FacultyApiService::class)->shouldReceive('getFaculties')->once()->andReturn([
            ['email' => $patient->email, 'faculty_code' => 'FAC-030'],
        ]);

        $this->artisan('patients:reconcile-classifications', ['--source' => 'faculty', '--apply' => true])
            ->expectsOutputToContain('email belongs to multiple patient records')->assertSuccessful();
        $this->assertNull($patient->fresh()->classification);
        $this->assertNull($other->fresh()->classification);
        $this->assertDatabaseCount('patient_faculty_information', 0);
    }

    public function test_sso_identity_can_match_faculty_without_matching_email_or_code(): void
    {
        $patient = $this->patient();
        $patient->user->update(['sso_user_id' => 'idp-030']);
        $this->mock(FacultyApiService::class)->shouldReceive('getFaculties')->once()->andReturn([
            ['email' => 'different@example.test', 'idp_user_id' => 'idp-030'],
        ]);

        $this->artisan('patients:reconcile-classifications', ['--source' => 'faculty', '--apply' => true])
            ->expectsOutputToContain('Repaired: 1')->assertSuccessful();
        $this->assertSame('faculty', $patient->fresh()->classification);
        $this->assertNull($patient->fresh()->faculty_code);
    }

    public function test_unavailable_directory_does_not_downgrade_or_modify_any_patient(): void
    {
        $patient = $this->patient(['faculty_code' => 'FAC-030']);
        $this->mock(FacultyApiService::class)->shouldReceive('getFaculties')->once()->andReturn([]);

        $this->artisan('patients:reconcile-classifications', ['--source' => 'faculty', '--apply' => true])
            ->expectsOutputToContain('empty or unavailable')->assertFailed();
        $this->assertNull($patient->fresh()->classification);
        $this->assertSame('FAC-030', $patient->fresh()->faculty_code);
    }

    public function test_directory_exception_stops_before_writes(): void
    {
        $patient = $this->patient();
        $this->mock(StudentApiService::class)->shouldReceive('getAllStudents')->once()->andThrow(new \RuntimeException('API unavailable'));

        $this->artisan('patients:reconcile-classifications', ['--source' => 'student', '--apply' => true])
            ->expectsOutputToContain('Directory retrieval failed')->assertFailed();
        $this->assertNull($patient->fresh()->classification);
        $this->assertDatabaseCount('patient_student_information', 0);
    }

    public function test_related_information_failure_rolls_back_classification(): void
    {
        $patient = $this->patient();
        $this->mock(FacultyApiService::class)->shouldReceive('getFaculties')->once()->andReturn([
            ['email' => $patient->email, 'faculty_code' => 'FAC-030'],
        ]);
        PatientFacultyInformation::saving(function () {
            throw new \RuntimeException('Simulated related write failure');
        });

        $this->artisan('patients:reconcile-classifications', ['--source' => 'faculty', '--apply' => true])
            ->expectsOutputToContain('transaction was rolled back')->assertFailed();
        $this->assertNull($patient->fresh()->classification);
        $this->assertDatabaseCount('patient_faculty_information', 0);
    }

    public function test_invalid_patient_filter_fails_instead_of_repairing_everyone(): void
    {
        $patient = $this->patient(['faculty_code' => 'FAC-030']);
        $this->artisan('patients:reconcile-classifications', ['--patient' => ['30,31'], '--apply' => true])
            ->expectsOutputToContain('positive integers')->assertExitCode(2);
        $this->assertNull($patient->fresh()->classification);
        Http::assertNothingSent();
    }

    public function test_cms_repair_previews_and_updates_legacy_profile_id_without_changing_access(): void
    {
        $patient = $this->patient(['classification' => 'dependent_alumni']);
        $profile = ExternalAdminProfile::create(['external_admin_id' => $patient->email, 'email' => $patient->email]);
        $access = ExternalAdminAccess::create([
            'external_admin_profile_id' => $profile->id, 'has_cms_access' => true, 'cms_role' => 'patient', 'cms_status' => 'active',
        ]);
        $accessBefore = $access->fresh()->getRawOriginal();
        $roleId = $patient->user->role_id;
        $this->mock(CmsApiService::class)->shouldReceive('getAdmins')->times(3)->andReturn([
            ['admin_id' => 'cms-030', 'email' => $patient->email, 'access_level' => 'superadmin'],
        ]);

        $this->artisan('patients:reconcile-classifications', ['--source' => 'cms'])
            ->expectsOutputToContain('Proposed repairs: 1')->expectsOutputToContain('external_admin_id')->assertSuccessful();
        $this->assertSame('dependent_alumni', $patient->fresh()->classification);
        $this->assertSame($patient->email, $profile->fresh()->external_admin_id);

        $this->artisan('patients:reconcile-classifications', ['--source' => 'cms', '--apply' => true])
            ->expectsOutputToContain('Repaired: 1')->assertSuccessful();
        $this->assertSame('administrative', $patient->fresh()->classification);
        $this->assertSame('cms-030', $profile->fresh()->external_admin_id);
        $this->assertSame($roleId, $patient->user->fresh()->role_id);
        $this->assertSame($accessBefore, $access->fresh()->getRawOriginal());
        $this->artisan('patients:reconcile-classifications', ['--source' => 'cms', '--apply' => true])
            ->expectsOutputToContain('Repaired: 0')->assertSuccessful();
    }

    public function test_cms_repair_without_existing_profile_does_not_create_access_assignment(): void
    {
        $patient = $this->patient();
        $this->mock(CmsApiService::class)->shouldReceive('getAdmins')->once()->andReturn([
            ['admin_id' => 'cms-030', 'email' => $patient->email],
        ]);
        $this->artisan('patients:reconcile-classifications', ['--source' => 'cms', '--apply' => true])
            ->expectsOutputToContain('Repaired: 1')->assertSuccessful();
        $this->assertSame('administrative', $patient->fresh()->classification);
        $this->assertDatabaseCount('external_admin_profiles', 0);
        $this->assertDatabaseCount('external_admin_accesses', 0);
    }

    public function test_cms_and_saved_student_conflict_is_skipped(): void
    {
        $patient = $this->patient(['student_no' => '2026-030']);
        $this->mock(CmsApiService::class)->shouldReceive('getAdmins')->once()->andReturn([
            ['admin_id' => 'cms-030', 'email' => $patient->email],
        ]);
        $this->artisan('patients:reconcile-classifications', ['--source' => 'cms', '--apply' => true])
            ->expectsOutputToContain('conflicting CMS and student/faculty evidence')->assertSuccessful();
        $this->assertNull($patient->fresh()->classification);
        $this->assertSame('2026-030', $patient->fresh()->student_no);
    }

    public function test_conflicting_local_cms_profiles_are_skipped(): void
    {
        $patient = $this->patient();
        ExternalAdminProfile::create(['external_admin_id' => $patient->email, 'email' => $patient->email]);
        ExternalAdminProfile::create(['external_admin_id' => 'cms-030', 'email' => 'other@example.test']);
        $this->mock(CmsApiService::class)->shouldReceive('getAdmins')->once()->andReturn([
            ['admin_id' => 'cms-030', 'email' => $patient->email],
        ]);
        $this->artisan('patients:reconcile-classifications', ['--source' => 'cms', '--apply' => true])
            ->expectsOutputToContain('multiple local CMS profiles')->assertSuccessful();
        $this->assertNull($patient->fresh()->classification);
    }

    public function test_failed_cms_profile_update_also_rolls_back_patient_classification(): void
    {
        $patient = $this->patient();
        $profile = ExternalAdminProfile::create(['external_admin_id' => $patient->email, 'email' => $patient->email]);
        $this->mock(CmsApiService::class)->shouldReceive('getAdmins')->once()->andReturn([
            ['admin_id' => 'cms-030', 'email' => $patient->email],
        ]);
        ExternalAdminProfile::saving(function () {
            throw new \RuntimeException('Simulated CMS profile write failure');
        });
        $this->artisan('patients:reconcile-classifications', ['--source' => 'cms', '--apply' => true])
            ->expectsOutputToContain('transaction was rolled back')->assertFailed();
        $this->assertNull($patient->fresh()->classification);
        $this->assertSame($patient->email, $profile->fresh()->external_admin_id);
    }

    private function patient(array $attributes = []): Patient
    {
        $role = Role::firstOrCreate(['slug' => 'patient'], ['name' => 'Patient']);
        $email = $attributes['email'] ?? 'faculty@example.test';
        $user = User::create(['name' => 'Faculty Patient', 'email' => $email, 'role_id' => $role->id, 'status' => 'active']);

        return Patient::create(array_merge([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $email,
            'password' => bcrypt('password'), 'classification' => null,
        ], $attributes));
    }
}
