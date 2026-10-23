<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\DentalHistory;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatientProfileClinicalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_omits_clinical_data_while_dentist_profile_keeps_it(): void
    {
        $this->withoutVite();
        $this->withoutExceptionHandling();
        Http::fake();
        $admin = $this->staff('admin');
        $dentist = $this->staff('dentist');
        $patient = Patient::create([
            'name' => 'Profile Visibility Patient', 'email' => 'profile@example.test',
            'password' => bcrypt('password'), 'birthdate' => '2000-01-01',
        ]);
        MedicalHistory::create([
            'patient_id' => $patient->id, 'emergency_person' => 'Emergency Contact',
            'emergency_number' => '09123456789', 'emergency_relation' => 'Parent',
            'signature_review_notes' => 'PRIVATE_SIGNATURE_NOTE',
        ]);
        DentalHistory::create([
            'patient_id' => $patient->id, 'previous_dentist' => 'PRIVATE_DENTAL_HISTORY',
        ]);
        $appointment = Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => $dentist->id,
            'service_type' => 'Oral Check-up', 'appointment_date' => now()->subDay()->toDateString(),
            'appointment_time' => '09:00:00', 'status' => 'completed',
        ]);
        AppointmentProcedure::create([
            'appointment_id' => $appointment->id,
            'oral_examination' => 'PRIVATE_EXAMINATION',
            'diagnosis' => 'PRIVATE_DIAGNOSIS', 'prescriptions' => 'PRIVATE_PRESCRIPTION',
            'inventory_notes' => 'PRIVATE_INVENTORY_NOTES',
        ]);

        $response = $this->actingAs($admin)->withSession(['role' => 'admin'])
            ->get(route('admin.admin.patient.profile', $patient));

        $response->assertOk();
        foreach (['Profile Visibility Patient', 'Emergency Contact', 'Past Visits', 'Oral Check-up'] as $text) {
            $this->assertTrue(str_contains($response->getContent(), $text), 'Admin profile missing: ' . $text);
        }
        foreach (['Health & Lifestyle Information', 'PRIVATE_SIGNATURE_NOTE', 'PRIVATE_DENTAL_HISTORY',
            'PRIVATE_EXAMINATION', 'PRIVATE_DIAGNOSIS', 'PRIVATE_PRESCRIPTION', 'PRIVATE_INVENTORY_NOTES', 'data-record='] as $text) {
            $this->assertFalse(str_contains($response->getContent(), $text), 'Admin profile exposed: ' . $text);
        }
        $this->assertFalse($response->viewData('patient')->relationLoaded('odontogram'));
        $this->assertFalse($response->viewData('patient')->relationLoaded('dentalHistory'));
        $this->assertFalse($response->viewData('patient')->medicalHistory->relationLoaded('answers'));
        $this->assertFalse($response->viewData('pastVisits')->first()->relationLoaded('procedure'));

        $response = $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->get(route('dentist.dentist.patient.profile', $patient))->assertOk();
        foreach (['Health & Lifestyle Information', 'PRIVATE_DENTAL_HISTORY', 'PRIVATE_EXAMINATION',
            'PRIVATE_DIAGNOSIS', 'PRIVATE_PRESCRIPTION', 'data-record='] as $text) {
            $this->assertTrue(str_contains($response->getContent(), $text), 'Dentist profile missing: ' . $text);
        }
    }

    private function staff(string $slug): User
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug]);
        foreach (['view_patient_profiles', 'view_dental_records', 'create_medical_records'] as $permissionSlug) {
            $permission = Permission::firstOrCreate(['slug' => $permissionSlug], [
                'name' => $permissionSlug, 'module' => 'Patients',
            ]);
            $role->permissions()->attach($permission);
        }

        return User::create([
            'name' => ucfirst($slug), 'email' => $slug . '@example.test',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 'active',
        ]);
    }
}
