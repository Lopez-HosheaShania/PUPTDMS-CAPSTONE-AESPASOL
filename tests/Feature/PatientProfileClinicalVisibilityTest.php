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
            'signature_review_status' => 'pending_manual_review',
            'patient_signature' => 'signatures/legacy-pending.png',
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

        foreach ([$admin, $this->staff('super_admin')] as $administrator) {
            $this->actingAs($administrator)->withSession([
                'role' => $administrator->role->slug,
                'impersonated_role' => 'dentist',
                'impersonator_role' => $administrator->role->slug,
            ]);

            foreach ([
                route('dentist.dentist.patient.profile', $patient),
                route('dentist.dentist.appointments.patientProfile', $appointment),
            ] as $url) {
                $response = $this->get($url)->assertOk();
                $response->assertSee('Emergency Contact')->assertSee('Past Visits');
                $response->assertViewHas('profileMode', 'dentist');
                foreach (['Health & Lifestyle Information', 'PRIVATE_SIGNATURE_NOTE', 'PRIVATE_DENTAL_HISTORY',
                    'PRIVATE_EXAMINATION', 'PRIVATE_DIAGNOSIS', 'PRIVATE_PRESCRIPTION', 'PRIVATE_INVENTORY_NOTES', 'data-record='] as $text) {
                    $response->assertDontSee($text, false);
                }
                $this->assertFalse($response->viewData('patient')->relationLoaded('dentalHistory'));
                $this->assertFalse($response->viewData('patient')->relationLoaded('odontogram'));
                $this->assertFalse($response->viewData('patient')->medicalHistory->relationLoaded('answers'));
                $this->assertFalse($response->viewData('pastVisits')->first()->relationLoaded('procedure'));
            }
        }

        session()->forget(['impersonated_role', 'impersonator_role']);
        $response = $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->get(route('dentist.dentist.patient.profile', $patient))->assertOk();
        $response->assertDontSee('Pending Manual Review')->assertDontSee('Signature Review Required')
            ->assertDontSee('signature/invalid')->assertDontSee('PRIVATE_SIGNATURE_NOTE');
        foreach (['Health & Lifestyle Information', 'PRIVATE_DENTAL_HISTORY', 'PRIVATE_EXAMINATION',
            'PRIVATE_DIAGNOSIS', 'PRIVATE_PRESCRIPTION', 'data-record='] as $text) {
            $this->assertTrue(str_contains($response->getContent(), $text), 'Dentist profile missing: ' . $text);
        }
    }

    public function test_impersonation_cannot_restore_removed_record_permissions(): void
    {
        $admin = $this->staff('admin');
        $dentist = $this->staff('dentist');
        $admin->role->permissions()->detach(Permission::whereIn('slug', [
            'view_dental_records', 'create_medical_records',
        ])->pluck('id'));
        $dashboardPermission = Permission::create([
            'slug' => 'access_dentist_dashboard', 'name' => 'Dentist dashboard', 'module' => 'Dashboard',
        ]);
        $dentist->role->permissions()->attach($dashboardPermission);

        $this->actingAs($admin)->withSession([
            'role' => 'admin', 'impersonated_role' => 'dentist', 'impersonator_role' => 'admin',
        ]);

        $this->assertTrue($admin->hasPermission('access_dentist_dashboard'));
        $this->assertTrue($admin->hasPermission('view_patient_profiles'));
        $this->assertFalse($admin->hasPermission('view_dental_records'));
        $this->assertFalse($admin->hasPermission('create_medical_records'));
        $this->get(route('dentist.dental-records.index'))->assertForbidden();
        $patient = Patient::create([
            'name' => 'Restricted Patient', 'email' => 'restricted@example.test',
            'password' => bcrypt('password'), 'birthdate' => '2000-01-01',
        ]);
        $this->post('/dentist/patients/'.$patient->id.'/signature/invalid')->assertNotFound();

        session()->forget(['impersonated_role', 'impersonator_role']);
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);
        $this->assertTrue($dentist->hasPermission('view_dental_records'));
        $this->assertTrue($dentist->hasPermission('create_medical_records'));
    }

    private function staff(string $slug): User
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug]);
        foreach (['view_patient_profiles', 'view_appointments', 'view_dental_records', 'create_medical_records'] as $permissionSlug) {
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
