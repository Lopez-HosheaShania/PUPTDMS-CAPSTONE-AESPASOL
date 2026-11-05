<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatientHomepageVisitHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_homepage_renders_completed_and_cancelled_visits_after_login(): void
    {
        Http::fake(['*' => Http::response(['data' => []])]);
        $role = Role::create(['name' => 'Patient', 'slug' => 'patient']);
        $permission = Permission::create([
            'name' => 'Patient Dashboard', 'slug' => 'access_patient_dashboard', 'module' => 'Patient',
        ]);
        $role->permissions()->attach($permission);
        $user = User::create([
            'name' => 'History Patient', 'email' => 'history@example.test',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 'active',
        ]);
        $patient = Patient::create([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'password' => bcrypt('password'), 'classification' => 'faculty',
        ]);
        foreach (['completed' => 'Cleaning', 'cancelled' => 'Restoration'] as $status => $service) {
            $appointment = Appointment::create([
                'patient_id' => $patient->id, 'service_type' => $service,
                'appointment_date' => now()->subDays(2)->toDateString(),
                'appointment_time' => $status === 'completed' ? '09:00:00' : '10:00:00', 'status' => $status,
            ]);
            if ($status === 'completed') {
                AppointmentProcedure::create([
                    'appointment_id' => $appointment->id,
                    'oral_examination' => 'Normal', 'diagnosis' => 'Routine visit',
                    'odontogram_data' => [],
                ]);
            }
        }

        $response = $this->actingAs($user)->withSession([
            'role' => 'patient', 'patient_id' => $patient->id,
        ])->get(route('homepage'));

        $response->assertOk()->assertSee('id="dashboardRecordCardsTemplate"', false)
            ->assertSee('Cleaning')->assertSee('Restoration');
        $this->assertAuthenticatedAs($user);
    }
}
