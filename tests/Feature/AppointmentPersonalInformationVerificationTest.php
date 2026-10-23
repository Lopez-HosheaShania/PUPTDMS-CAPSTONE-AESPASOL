<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentPersonalInformationVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ogos.base_url' => 'https://ogos.test/api/v1']);
        Cache::put('ogos_student_api_access_token', 'test-token', 3600);
        Http::preventStrayRequests();

        $role = Role::firstOrCreate(['slug' => 'patient'], ['name' => 'Patient']);
        $permission = Permission::firstOrCreate(['slug' => 'book_appointments'], ['name' => 'Book Appointments']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user = User::create([
            'name' => 'Booking Student', 'email' => 'booking@example.test',
            'role_id' => $role->id, 'status' => 'active',
        ]);
        $patient = Patient::create([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'classification' => 'student', 'student_no' => '2026-001', 'password' => bcrypt('password'),
        ]);
        $this->actingAs($user)->withSession(['patient_id' => $patient->id, 'role' => 'patient']);
    }

    private function fakePersonalInfo(array $info, int $status = 200): void
    {
        Http::fake(['https://ogos.test/api/v1/integrations/students/*/personal-info' => Http::response(['data' => $info], $status)]);
    }

    public function test_complete_profile_passes_and_opens_the_existing_form(): void
    {
        $this->fakePersonalInfo(['completionPercentage' => 100]);
        $this->getJson(route('book.appointment.verify-personal-information'))->assertOk()->assertJson(['verified' => true]);
        $this->get(route('patient.book.appointment'))->assertOk()->assertViewIs('patient.book-appointment');
        $this->assertDatabaseCount('appointments', 0);
    }

    private function personalInformationDetails(): array
    {
        return [
            'studentNumber' => '2026-001',
            'gender' => ['id' => 2, 'name' => 'Female'],
            'dateOfBirth' => '2005-10-31T00:00:00Z',
            'placeOfBirth' => 'Manila',
            'heightM' => 1.6,
            'weightKg' => 58,
            'emergencyContactName' => 'Emergency Contact',
            'emergencyContactNumber' => '09123456789',
            'emergencyContactRelationship' => 'Friend',
        ];
    }

    private function completeLocalProfile(): void
    {
        $patient = auth()->user()->patient;
        $patient->update(['phone' => '09987654321', 'birthdate' => '2005-10-31', 'gender' => 'Female']);
        $patient->medicalHistory()->create([
            'emergency_person' => 'Emergency Contact', 'emergency_number' => '09123456789',
            'emergency_relation' => 'Friend',
        ]);
    }

    public function test_complete_actual_guisis_details_without_a_percentage_allow_booking(): void
    {
        $this->completeLocalProfile();
        $this->fakePersonalInfo($this->personalInformationDetails());

        $this->getJson(route('book.appointment.verify-personal-information'))
            ->assertOk()->assertJson(['verified' => true]);
        foreach (['patient.book.appointment', 'book.appointment.create', 'appointment.create'] as $route) {
            $this->get(route($route))->assertOk()->assertViewIs('patient.book-appointment');
        }
        // Creation proceeds to form validation instead of the verification modal.
        foreach (['appointment.store', 'book.appointment.store'] as $route) {
            $response = $this->postJson(route($route));
            $response->assertStatus(422)->assertJsonValidationErrors(['appointment_date']);
            $this->assertArrayNotHasKey('code', $response->json());
        }
        $this->assertDatabaseCount('appointments', 0);
    }

    public static function missingRequiredDetails(): array
    {
        return array_map(fn ($field) => [$field], [
            'name', 'phone', 'email', 'dateOfBirth', 'gender',
            'emergencyContactName', 'emergencyContactNumber',
        ]);
    }

    #[DataProvider('missingRequiredDetails')]
    public function test_missing_required_detail_blocks_booking_even_with_a_previously_complete_profile(string $field): void
    {
        $this->completeLocalProfile();
        $info = $this->personalInformationDetails();
        if (in_array($field, ['name', 'phone', 'email'], true)) {
            auth()->user()->patient->update([$field => '']);
        } else {
            unset($info[$field]);
        }
        $this->fakePersonalInfo($info);

        $this->getJson(route('book.appointment.verify-personal-information'))
            ->assertStatus(422)->assertJson(['code' => 'personal_information_incomplete']);
        $this->get(route('patient.book.appointment'))->assertRedirect(route('homepage'));
        $this->postJson(route('book.appointment.store'))
            ->assertStatus(422)->assertJson(['code' => 'personal_information_incomplete']);
        $this->assertDatabaseCount('appointments', 0);
    }

    public static function deniedProfiles(): array
    {
        return [
            '99 percent' => [['completionPercentage' => 99], 200, 422],
            'zero percent' => [['completionPercentage' => 0], 200, 422],
            'missing' => [[], 200, 503],
            'null' => [['completionPercentage' => null], 200, 503],
            'malformed' => [['completionPercentage' => 'complete'], 200, 503],
            'above 100' => [['completionPercentage' => 101], 200, 503],
            'outage' => [['completionPercentage' => 100], 500, 503],
        ];
    }

    #[DataProvider('deniedProfiles')]
    public function test_click_check_direct_form_and_both_creation_routes_fail_closed(array $info, int $apiStatus, int $expected): void
    {
        $this->fakePersonalInfo($info, $apiStatus);
        // A previously complete cached response must not override a fresh failure.
        Cache::put('ogos_student_personal_info_2026-001', ['data' => ['completionPercentage' => 100]], 3600);
        $this->getJson(route('book.appointment.verify-personal-information'))
            ->assertStatus($expected)->assertJson(['verified' => false]);
        foreach (['patient.book.appointment', 'book.appointment.create', 'appointment.create'] as $route) {
            $this->get(route($route))->assertRedirect(route('homepage'))->assertSessionHas('booking_verification_error');
        }
        foreach (['appointment.store', 'book.appointment.store'] as $route) {
            $this->postJson(route($route), ['completionPercentage' => 100])->assertStatus($expected)->assertJson(['verified' => false]);
            $this->post(route($route))->assertRedirect(route('homepage'))->assertSessionHas('booking_verification_error');
        }
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_creation_rechecks_after_a_successful_frontend_check(): void
    {
        Http::fake(['https://ogos.test/api/v1/integrations/students/*/personal-info' => Http::sequence()
            ->push(['data' => ['completionPercentage' => 100]])
            ->push(['data' => ['completionPercentage' => 99]])]);
        $this->getJson(route('book.appointment.verify-personal-information'))->assertOk();
        $this->postJson(route('book.appointment.store'))->assertStatus(422);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_student_without_a_number_is_resolved_using_existing_email_lookup(): void
    {
        auth()->user()->patient->update(['student_no' => null]);
        Http::fake([
            'https://ogos.test/api/v1/integrations/students/profile*' => Http::response(['data' => ['studentNumber' => '2026-002']]),
            'https://ogos.test/api/v1/integrations/students/2026-002/personal-info' => Http::response(['data' => ['completionPercentage' => '100']]),
        ]);
        $this->getJson(route('book.appointment.verify-personal-information'))->assertOk()->assertJson(['verified' => true]);
        Http::assertSentCount(2);
    }

    public function test_faculty_booking_is_preserved_without_ogos(): void
    {
        auth()->user()->patient->update(['classification' => 'faculty', 'student_no' => null]);
        $this->getJson(route('book.appointment.verify-personal-information'))->assertOk();
        Http::assertNothingSent();
    }

    public function test_default_patient_category_without_identifiers_cannot_bypass_verification(): void
    {
        auth()->user()->patient->update(['classification' => 'dependent_alumni', 'student_no' => null, 'course_code' => null]);
        Http::fake([
            'https://ogos.test/api/v1/integrations/students/profile*' => Http::response(['data' => ['studentNumber' => '2026-002']]),
            'https://ogos.test/api/v1/integrations/students/2026-002/personal-info' => Http::response(['data' => ['completionPercentage' => 29]]),
        ]);
        $this->getJson(route('book.appointment.verify-personal-information'))
            ->assertStatus(422)->assertJson(['title' => 'Complete Your Personal Information']);
        $this->get(route('patient.book.appointment'))->assertRedirect(route('homepage'));
        $this->postJson(route('book.appointment.store'))->assertStatus(422);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_default_patient_with_no_ogos_record_remains_blocked(): void
    {
        auth()->user()->patient->update(['classification' => 'dependent_alumni', 'student_no' => null]);
        Http::fake(['https://ogos.test/api/v1/integrations/students/profile*' => Http::response(['data' => []])]);
        $this->getJson(route('book.appointment.verify-personal-information'))
            ->assertStatus(503)->assertJson(['title' => 'Unable to Verify Information']);
        $this->postJson(route('appointment.store'))->assertStatus(503);
        $this->assertDatabaseCount('appointments', 0);
    }
}
