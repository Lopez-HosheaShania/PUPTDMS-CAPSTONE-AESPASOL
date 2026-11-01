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
        return [
            'normalized service, emergency' => [false, true],
            'normalized service, non-emergency' => [false, false],
            'legacy service, emergency' => [true, true],
            'legacy service, non-emergency' => [true, false],
        ];
    }

    #[DataProvider('schemas')]
    public function test_start_procedure_creates_walk_in_and_opens_odontogram(bool $legacyName, bool $emergency): void
    {
        if (! $legacyName) {
            Schema::table('appointments', fn (Blueprint $table) => $table->dropColumn('service_type'));
        }
        [$patient, $history, $service] = $this->prepareIntake();

        $response = $this->postJson(route('dentist.walk-in.start'), [
            'patient_id' => $patient->id, 'service_type' => $service->name,
            'is_emergency' => $emergency ? '1' : '0',
            'emergency_person' => 'Test Contact', 'emergency_number' => '09123456789',
            'emergency_relation' => 'Parent', 'diseases' => [],
        ])->assertOk()->assertJsonPath('success', true);

        $appointment = Appointment::findOrFail($response->json('appointment_id'));
        $this->assertTrue($appointment->is_walk_in);
        $this->assertSame($emergency, $appointment->is_emergency);
        $this->assertSame($service->id, $appointment->service_type_id);
        $this->assertSame('Follow-up', $appointment->service_type_name);
        $this->assertSame('upcoming', $appointment->status);
        $this->assertSame('signatures/existing.png', $history->fresh()->patient_signature);
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseHas('appointment_case_classifications', [
            'appointment_id' => $appointment->id, 'is_emergency' => $emergency,
        ]);
        $this->assertFalse(Schema::hasColumn('appointments', 'is_emergency'));
        if ($legacyName) {
            $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'service_type' => $service->name]);
        }
        $this->get($response->json('start_url'))->assertOk();
    }

    public function test_case_choice_is_required_and_invalid_choices_cannot_create_an_appointment(): void
    {
        [$patient, , $service] = $this->prepareIntake();
        $payload = [
            'patient_id' => $patient->id, 'service_type' => $service->name,
            'emergency_person' => 'Test Contact', 'emergency_number' => '09123456789',
            'emergency_relation' => 'Parent',
        ];

        $this->postJson(route('dentist.walk-in.start'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('is_emergency');
        foreach ([null, '', 'Emergency', 'Non-Emergency', '2', []] as $invalid) {
            $this->postJson(route('dentist.walk-in.start'), [...$payload, 'is_emergency' => $invalid])
                ->assertUnprocessable()->assertJsonValidationErrors('is_emergency');
        }
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('appointment_case_classifications', 0);

        $html = $this->get(route('dentist.walk-in.index'))->assertOk()->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $choices = $xpath->query('//input[@name="is_emergency"]');
        $this->assertCount(2, $choices);
        $this->assertSame(['1', '0'], array_map(fn ($input) => $input->getAttribute('value'), iterator_to_array($choices)));
        $this->assertSame(0, $xpath->query('//input[@name="is_emergency"][@checked]')->length);
        $this->assertSame(2, $xpath->query('//input[@name="is_emergency"][@required][@class="global-radio-input"][ancestor::div[@data-global-field]]')->length);
    }

    private function prepareIntake(): array
    {
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

        $this->actingAs($dentist)->withSession(['role' => 'dentist']);

        return [$patient, $history, $service];
    }
}
