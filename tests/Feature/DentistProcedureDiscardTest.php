<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DentalHistory;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentistProcedureDiscardTest extends TestCase
{
    use RefreshDatabase;

    public function test_booked_appointment_discard_keeps_the_original_appointment_upcoming(): void
    {
        $dentist = $this->makeDentist();
        $patient = $this->makePatient('booked-patient@example.com');
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'service_type' => 'Oral Check-up',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:00:00',
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.discard'), [
                'context' => 'appointments',
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'redirect_url' => route('dentist.dentist.appointments'),
            ]);

        $this->assertSame('upcoming', $appointment->fresh()->status);
    }

    public function test_walk_in_discard_removes_only_the_unsaved_walk_in_appointment(): void
    {
        $dentist = $this->makeDentist();
        $patient = $this->makePatient('walk-in-patient@example.com');
        $walkInAppointment = Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'service_type' => 'Cleaning',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'upcoming',
            'is_walk_in' => true,
        ]);

        $otherAppointment = Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'service_type' => 'Follow-up',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '11:00:00',
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.discard'), [
                'context' => 'walk-in',
                'appointment_id' => $walkInAppointment->id,
                'patient_id' => $patient->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'redirect_url' => route('dentist.walk-in.index'),
            ]);

        $this->assertDatabaseMissing('appointments', [
            'id' => $walkInAppointment->id,
        ]);
        $this->assertSame('upcoming', $otherAppointment->fresh()->status);
    }

    public function test_existing_appointment_discard_clears_the_session_draft_without_touching_saved_history(): void
    {
        $dentist = $this->makeDentist();
        $patient = $this->makePatient('existing-patient@example.com');

        DentalHistory::create([
            'patient_id' => $patient->id,
            'last_dental_visit' => '2026-08-15',
            'previous_dentist' => 'Dr. Existing',
        ]);

        $draftSessionKey = 'existing_appointment_draft_patient_' . $patient->id;

        $response = $this->actingAs($dentist)
            ->withSession([
                'role' => 'dentist',
                $draftSessionKey => [
                    'appointment_date' => now()->toDateString(),
                    'appointment_time' => '09:00',
                    'service_type' => 'Extraction',
                ],
            ])
            ->postJson(route('dentist.odontogram.discard'), [
                'context' => 'existing-appointment',
                'patient_id' => $patient->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'redirect_url' => route('dentist.dentist.patient.profile', ['patient' => $patient->id]),
            ]);

        $this->assertNull(session($draftSessionKey));
        $this->assertDatabaseHas('dental_histories', [
            'patient_id' => $patient->id,
            'previous_dentist' => 'Dr. Existing',
        ]);
    }

    public function test_existing_appointment_discard_returns_to_profile_without_any_appointments(): void
    {
        $this->withoutVite();
        $this->withoutExceptionHandling();
        \Illuminate\Support\Facades\Http::fake();

        $dentist = $this->makeDentist();
        $permission = Permission::firstOrCreate(
            ['slug' => 'view_patient_profiles'],
            ['name' => 'View Patient Profiles', 'module' => 'Patients']
        );
        $dentist->role->permissions()->attach($permission);
        $patient = $this->makePatient('no-visits@example.com');
        $profileUrl = route('dentist.dentist.patient.profile', ['patient' => $patient->id]);

        $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->get($profileUrl)->assertOk();

        $draftSessionKey = 'existing_appointment_draft_patient_' . $patient->id;
        $response = $this->withSession([
            $draftSessionKey => ['service_type' => 'Cleaning'],
        ])->postJson(route('dentist.odontogram.discard'), [
            'context' => 'existing-appointment',
            'patient_id' => $patient->id,
        ]);

        $response->assertOk()->assertJson(['redirect_url' => $profileUrl]);
        $response->assertSessionMissing($draftSessionKey);
        $this->get($response->json('redirect_url'))->assertOk();
        $this->assertDatabaseMissing('appointments', ['patient_id' => $patient->id]);
    }

    private function makeDentist(): User
    {
        $role = Role::create([
            'name' => 'Dentist',
            'slug' => 'dentist',
        ]);

        $permission = Permission::firstOrCreate(
            ['slug' => 'create_procedure_records'],
            [
                'name' => 'Create Procedure Records',
                'module' => 'Dentist',
            ]
        );

        $role->permissions()->attach($permission);

        return User::create([
            'name' => 'Procedure Dentist',
            'email' => 'procedure-dentist@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function makePatient(string $email): Patient
    {
        return Patient::create([
            'name' => 'Procedure Patient',
            'email' => $email,
            'phone' => '09171234567',
            'birthdate' => '2004-01-01',
            'gender' => 'Female',
            'password' => bcrypt('password'),
        ]);
    }
}
