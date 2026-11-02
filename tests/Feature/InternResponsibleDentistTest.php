<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\DentistTransition;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\DptReport;
use App\Services\AssignedDentistService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InternResponsibleDentistTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $slug, string $name): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('_', ' ', $slug))]);
        foreach (['manage_appointments', 'manage_walk_in_patients'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return User::create(['name' => $name, 'email' => uniqid() . '@example.test', 'role_id' => $role->id, 'status' => 'active']);
    }

    private function patient(): Patient
    {
        return Patient::create(['name' => 'Intern Patient', 'email' => uniqid() . '@example.test', 'password' => bcrypt('password'), 'classification' => 'faculty']);
    }

    private function assign(Patient $patient, User $dentist): void
    {
        $previous = $this->user('dentist', 'Previous Dentist');
        $transition = DentistTransition::create([
            'dentist_id' => $previous->id, 'default_successor_dentist_id' => $dentist->id,
            'transition_type' => 'retirement', 'coverage_type' => 'permanent', 'status' => 'completed',
            'last_working_date' => today()->subDay()->toDateString(), 'effective_date' => today()->toDateString(),
            'access_ends_at' => today(), 'initiated_by' => $dentist->id,
        ]);
        DB::table('patient_care_assignments')->insert([
            'patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'dentist_transition_id' => $transition->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_intern_walk_in_and_odontogram_keep_the_appointed_dentist_on_reports(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 09:00:00'));
        $this->user('dentist', 'Other Available Dentist');
        $dentist = $this->user('dentist', 'Appointed Dentist');
        $intern = $this->user('dental_intern', 'Encoding Intern');
        $patient = $this->patient();
        $this->assign($patient, $dentist);
        MedicalHistory::create([
            'patient_id' => $patient->id, 'emergency_person' => 'Emergency Contact',
            'emergency_number' => '09123456789', 'emergency_relation' => 'Parent',
            'patient_signature' => 'signatures/existing.png',
        ]);
        ServiceType::firstOrCreate(['name' => 'Oral Check-up']);
        $this->actingAs($intern)->withSession(['role' => 'dental_intern']);
        $response = $this->postJson(route('dentist.walk-in.start'), [
            'patient_id' => $patient->id, 'service_type' => 'Oral Check-up',
            'is_emergency' => '1',
            'emergency_person' => 'Emergency Contact', 'emergency_number' => '09123456789',
            'emergency_relation' => 'Parent', 'diseases' => [],
        ])->assertOk();
        $appointment = Appointment::findOrFail($response->json('appointment_id'));
        $this->assertSame($dentist->id, $appointment->dentist_id);

        $appointment->update(['dentist_id' => $intern->id]);
        $this->postJson(route('dentist.odontogram.save', $appointment), [
            'odontogram_data' => [], 'oral_examination' => 'Encoded by intern',
            'diagnosis' => 'Check-up', 'has_applied_treatment' => true, 'completion_action' => 'finished',
            'procedure_started_at' => '2026-10-28T09:00:00', 'procedure_completed_at' => '2026-10-28T09:10:00',
        ])->assertSuccessful();
        $this->assertSame($dentist->id, $appointment->fresh()->dentist_id);
        $this->assertSame('Appointed Dentist', $appointment->fresh()->dentist_name);
        $rows = app(DptReport::class)->records(today()->startOfDay(), today()->endOfDay(), true);
        $this->assertSame('Appointed Dentist', $rows->first()->dentist);
        $this->assertNotSame($intern->name, $rows->first()->dentist);
    }

    public function test_old_intern_assignments_print_the_patient_responsible_dentist(): void
    {
        $dentist = $this->user('dentist', 'Responsible Dentist');
        $intern = $this->user('dental_intern', 'Legacy Encoding Intern');
        $patient = $this->patient();
        $this->assign($patient, $dentist);
        $this->actingAs($intern)->withSession(['role' => 'dental_intern']);
        $appointment = Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => $intern->id, 'is_walk_in' => true,
            'status' => 'completed', 'appointment_date' => today()->toDateString(), 'appointment_time' => '09:00:00', 'service_type' => 'Oral Check-up',
        ]);
        AppointmentProcedure::create(['appointment_id' => $appointment->id]);
        $this->assertSame('Responsible Dentist', $appointment->fresh()->dentist_name);
        $this->assertSame('Responsible Dentist', app(DptReport::class)->records(today()->startOfDay(), today()->endOfDay(), true)->first()->dentist);
    }

    public function test_intern_default_uses_current_dentist_until_effective_continuity_handover(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 09:00:00'));
        $current = $this->user('dentist', 'Current Dentist');
        $successor = $this->user('dentist', 'Successor Dentist');
        $intern = $this->user('dental_intern', 'Intern');
        $patient = $this->patient();
        DB::table('clinic_dentist_assignments')->where('slot', 'primary')->update(['dentist_id' => $current->id]);
        DentistTransition::create([
            'dentist_id' => $current->id, 'default_successor_dentist_id' => $successor->id,
            'transition_type' => 'retirement', 'coverage_type' => 'permanent', 'status' => 'scheduled',
            'last_working_date' => '2026-10-28', 'effective_date' => '2026-10-29',
            'access_ends_at' => '2026-10-29 00:00:00', 'initiated_by' => $intern->id,
        ]);
        $dentistService = app(AssignedDentistService::class);
        $this->assertSame($current->id, $dentistService->requireForPatient($patient->id, $intern)->id);
        $this->travelTo(Carbon::parse('2026-10-29 00:00:00'));
        $this->assertNull($dentistService->findForPatient($patient->id, $intern));
        $this->assertSame(1, app(\App\Services\DentistTransitionService::class)->processDueTransitions());
        $this->assertSame($successor->id, $dentistService->requireForPatient($patient->id, $intern)->id);
    }
}
