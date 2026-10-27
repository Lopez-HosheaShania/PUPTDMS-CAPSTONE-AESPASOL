<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PatientBookingConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public static function backgroundRequestCounts(): array
    {
        return [
            'direct redirect' => [0, 'homepage'],
            'background requests before homepage' => [2, 'homepage'],
            'alternate dashboard route' => [2, 'patient.dashboard'],
        ];
    }

    #[DataProvider('backgroundRequestCounts')]
    public function test_booking_confirmation_survives_until_homepage_and_appears_once(int $backgroundRequests, string $dashboardRoute): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-26 08:00:00'));
        Http::fake(['*' => Http::response(['data' => []])]);
        Mail::fake();
        Notification::fake();
        $role = Role::create(['name' => 'Patient', 'slug' => 'patient']);
        foreach (['access_patient_dashboard', 'book_appointments'] as $slug) {
            $permission = Permission::create(['name' => $slug, 'slug' => $slug]);
            $role->permissions()->attach($permission);
        }
        $user = User::create([
            'name' => 'Booking Patient', 'email' => 'confirmation@example.test',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 'active',
        ]);
        $patient = Patient::create([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'password' => bcrypt('password'), 'classification' => 'faculty',
        ]);
        $patient->medicalHistory()->create([
            'patient_signature' => 'signatures/existing.png', 'signature_review_status' => 'verified',
            'emergency_person' => 'Emergency Contact', 'emergency_number' => '09987654321',
            'emergency_relation' => 'Friend',
        ]);
        $dentistRole = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        User::create([
            'name' => 'Booking Dentist', 'email' => 'dentist@example.test',
            'role_id' => $dentistRole->id, 'status' => 'active',
        ]);
        ClinicSchedule::create([
            'days' => ['Tue'], 'days_label' => 'T', 'status' => 'open', 'is_active' => true,
            'open_time' => '09:00:00', 'close_time' => '12:00:00', 'max_slots' => 3,
        ]);
        ServiceType::create(['name' => 'Cleaning', 'is_active_for_booking' => true]);

        $this->actingAs($user)->withSession(['role' => 'patient', 'patient_id' => $patient->id])
            ->post(route('book.appointment.store'), [
                'appointment_date' => '2026-10-27', 'appointment_time' => '9:00 AM',
                'service_type' => 'Cleaning', 'final_confirmation' => '1',
                'contact_email' => $patient->email, 'contact_phone' => '09123456789',
                'contact_address' => 'Taguig City', 'emergency_person' => 'Emergency Contact',
                'emergency_number' => '09987654321', 'emergency_relation' => 'Friend',
            ])->assertSessionHasNoErrors()->assertRedirect(route('homepage'))
            ->assertSessionHas('appointment_confirmation');
        $this->assertDatabaseCount('appointments', 1);
        for ($i = 0; $i < $backgroundRequests; $i++) {
            $this->getJson(route('notifications.poll'))->assertOk();
        }
        $response = $this->get(route($dashboardRoute));
        $response->assertOk();
        $this->assertTrue(str_contains($response->getContent(), 'id="appointmentConfirmedModal"'),
            'The homepage must include the booking confirmation after background requests.');
        $response->assertSee('October 27, 2026')->assertSee('9:00 AM')
            ->assertSee('const openAppointmentConfirmedModal', false)
            ->assertSee('"appointmentDraft:v1"', false)
            ->assertSessionMissing('appointment_confirmation')
            ->assertSessionMissing('appointment_draft_completed');
        $this->get(route($dashboardRoute))->assertOk()->assertDontSee('id="appointmentConfirmedModal"', false);
        $this->assertSame('upcoming', Appointment::firstOrFail()->status);
    }
}
