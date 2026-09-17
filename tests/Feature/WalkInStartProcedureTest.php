<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WalkInStartProcedureTest extends TestCase
{
    use RefreshDatabase;

    public static function schemas(): array
    {
        return ['normalized service ID only' => [false], 'legacy service name retained' => [true]];
    }

    #[DataProvider('schemas')]
    public function test_start_procedure_creates_walk_in_and_opens_odontogram(bool $legacyName): void
    {
        if (! $legacyName) {
            Schema::table('appointments', fn (Blueprint $table) => $table->dropColumn('service_type'));
        }
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $role = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        foreach (['manage_walk_in_patients', 'manage_appointments'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Appointments']);
            $role->permissions()->attach($permission);
        }
        $dentist = User::create(['name' => 'Walk-in Dentist', 'email' => 'walkin-dentist@example.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 'active']);
        $patient = Patient::create(['name' => 'Walk-in Patient', 'email' => 'walkin-patient@example.com',
            'password' => bcrypt('password'), 'classification' => 'guest']);
        $history = MedicalHistory::create(['patient_id' => $patient->id,
            'emergency_person' => 'Test Contact', 'emergency_number' => '09123456789',
            'emergency_relation' => 'Parent', 'patient_signature' => 'signatures/existing.png']);
        $service = ServiceType::firstOrCreate(['name' => 'Follow-up']);

        $response = $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.walk-in.start'), [
                'patient_id' => $patient->id, 'service_type' => $service->name,
                'emergency_person' => 'Test Contact', 'emergency_number' => '09123456789',
                'emergency_relation' => 'Parent', 'diseases' => [],
            ])->assertOk()->assertJsonPath('success', true);

        $appointment = Appointment::findOrFail($response->json('appointment_id'));
        $this->assertTrue($appointment->is_walk_in);
        $this->assertSame($service->id, $appointment->service_type_id);
        $this->assertSame('Follow-up', $appointment->service_type_name);
        $this->assertSame('upcoming', $appointment->status);
        $this->assertSame('signatures/existing.png', $history->fresh()->patient_signature);
        $this->assertDatabaseCount('appointments', 1);
        if ($legacyName) {
            $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'service_type' => $service->name]);
        }
        $this->get($response->json('start_url'))->assertOk();
    }
}
