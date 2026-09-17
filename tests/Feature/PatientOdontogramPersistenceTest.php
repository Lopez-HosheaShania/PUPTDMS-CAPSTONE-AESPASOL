<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Patient;
use App\Models\PatientOdontogram;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PatientOdontogramPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private int $appointmentSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_future_appointments_update_one_persistent_patient_odontogram(): void
    {
        $dentistRole = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        $permission = Permission::create([
            'name' => 'Manage Appointments',
            'slug' => 'manage_appointments',
            'module' => 'Appointments',
        ]);
        $dentistRole->permissions()->attach($permission);

        $dentist = User::create([
            'name' => 'Test Dentist',
            'email' => 'dentist@example.com',
            'password' => bcrypt('password'),
            'role_id' => $dentistRole->id,
            'status' => 'active',
        ]);

        $patient = Patient::create([
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'phone' => '09123456789',
            'birthdate' => '2000-01-01',
            'gender' => 'Male',
            'password' => bcrypt('password'),
        ]);

        $firstAppointment = $this->makeAppointment($patient, $dentist);

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.save', $firstAppointment), [
                'odontogram_data' => [
                    $this->toothEntry(11, 'D', ['top' => 'F']),
                ],
                'oral_examination' => 'Initial examination',
                'diagnosis' => 'Initial diagnosis',
                'procedure_duration_hms' => '00:30:00',
                'completion_action' => 'finished',
                'has_applied_treatment' => true,
            ])
            ->assertSuccessful();

        $secondAppointment = $this->makeAppointment($patient, $dentist);

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.save', $secondAppointment), [
                'odontogram_data' => [
                    $this->toothEntry(11, 'F', ['top' => 'F']),
                    $this->toothEntry(12, 'D'),
                ],
                'oral_examination' => 'Follow-up examination',
                'diagnosis' => 'Updated diagnosis',
                'procedure_duration_hms' => '00:30:00',
                'completion_action' => 'finished',
                'has_applied_treatment' => true,
            ])
            ->assertSuccessful();

        $this->assertDatabaseCount('patient_odontograms', 1);
        $this->assertDatabaseCount('appointment_procedures', 2);
        $this->assertDatabaseCount('appointment_procedure_timings', 2);
        $savedProcedure = $firstAppointment->fresh()->procedure;
        $this->assertNotNull($savedProcedure->procedure_started_at);
        $this->assertNotNull($savedProcedure->procedure_completed_at);
        $this->assertSame(1800, $savedProcedure->procedure_duration_seconds);
        $this->assertDatabaseCount('appointment_odontograms', 2);
        $firstSnapshot = collect($firstAppointment->fresh()->procedure->odontogram_data)->keyBy('tooth');
        $this->assertSame('D', data_get($firstSnapshot->get(11), 'status.code'));
        $this->assertCount(1, $firstSnapshot);

        $odontogram = PatientOdontogram::where('patient_id', $patient->id)->firstOrFail();
        $teeth = collect($odontogram->odontogram_data)->keyBy('tooth');

        $this->assertSame([11, 12], $teeth->keys()->sort()->values()->all());
        $this->assertSame('F', data_get($teeth->get(11), 'status.code'));
        $this->assertSame('F', data_get($teeth->get(11), 'surfaces.top.code'));
        $this->assertSame('D', data_get($teeth->get(12), 'status.code'));
        $this->assertSame($secondAppointment->id, $odontogram->last_appointment_id);
        $this->assertSame($dentist->id, $odontogram->last_updated_by);
    }

    public function test_snapshot_migration_round_trip_preserves_json_and_procedure_fields(): void
    {
        $procedure = $this->makeProcedure();
        $migration = require database_path('migrations/2026_09_07_030000_move_appointment_odontograms_to_separate_table.php');
        $normalization = require database_path('migrations/2026_09_07_040000_normalize_appointment_odontogram_data.php');

        $normalization->down();
        $migration->down();
        $before = DB::table('appointment_procedures')->where('id', $procedure->id)->first();
        $this->assertSame([['tooth' => 11, 'status' => null]], json_decode($before->odontogram_data, true));

        $migration->up();
        $this->assertFalse(Schema::hasColumn('appointment_procedures', 'odontogram_data'));
        $snapshot = DB::table('appointment_odontograms')->where('appointment_procedure_id', $procedure->id)->first();
        $this->assertSame($before->odontogram_data, $snapshot->odontogram_data);
        $this->assertSame($before->created_at, $snapshot->created_at);
        $this->assertSame($before->updated_at, $snapshot->updated_at);

        $migration->down();
        $after = DB::table('appointment_procedures')->where('id', $procedure->id)->first();
        $this->assertEquals($before, $after);
        $migration->up();
        $normalization->up();
    }

    public function test_normalized_rows_preserve_every_marking_and_survive_migration_round_trip(): void
    {
        $procedure = $this->makeProcedure();
        $data = [
            [
                'tooth' => 27, 'toothName' => 'Upper molar',
                'status' => ['code' => 'D', 'label' => 'Original diagnosis', 'colorHex' => '#ef4444'],
                'surfaces' => [
                    'top' => ['code' => 'F', 'label' => 'Original filling', 'colorHex' => '#2563eb'],
                    'left' => null, 'center' => [],
                    'right' => ['code' => 'X'], 'bottom' => ['code' => null, 'label' => ''],
                ],
                'threeD' => ['code' => 'LC', 'label' => 'Light Cure Composite', 'colorHex' => null],
            ],
            ['tooth' => 11, 'surfaces' => null, 'threeD' => null],
            ['tooth' => 12, 'surfaces' => []],
        ];
        $procedure->update(['odontogram_data' => $data]);
        $this->assertFalse(Schema::hasColumn('appointment_odontograms', 'odontogram_data'));
        $this->assertDatabaseCount('appointment_odontogram_teeth', 3);
        $this->assertDatabaseHas('appointment_odontogram_markings', [
            'kind' => 'threeD', 'surface' => '', 'code' => 'LC', 'label' => 'Light Cure Composite',
        ]);
        $this->assertSame($data, $procedure->fresh()->odontogram_data);

        $migration = require database_path('migrations/2026_09_07_040000_normalize_appointment_odontogram_data.php');
        $migration->down();
        $stored = DB::table('appointment_odontograms')->where('appointment_procedure_id', $procedure->id)->first();
        $this->assertSame($data, json_decode($stored->odontogram_data, true));
        $migration->up();
        $this->assertSame($data, $procedure->fresh()->odontogram_data);

        $procedure->update(['odontogram_data' => []]);
        $this->assertDatabaseCount('appointment_odontogram_teeth', 0);
        $this->assertDatabaseCount('appointment_odontogram_markings', 0);
        $this->assertSame([], $procedure->fresh()->odontogram_data);

        $procedure->update(['odontogram_data' => $data]);
        $procedure->appointment->delete();
        $this->assertDatabaseCount('appointment_odontogram_teeth', 0);
        $this->assertDatabaseCount('appointment_odontogram_markings', 0);
    }

    public function test_normalization_migration_preserves_null_and_empty_snapshots(): void
    {
        $procedure = $this->makeProcedure();
        $migration = require database_path('migrations/2026_09_07_040000_normalize_appointment_odontogram_data.php');
        foreach ([null, []] as $data) {
            $procedure->update(['odontogram_data' => $data]);
            $migration->down();
            $migration->up();
            $this->assertSame($data, $procedure->fresh()->odontogram_data);
        }
    }

    public function test_unsupported_fields_are_rejected_without_losing_the_previous_snapshot(): void
    {
        $procedure = $this->makeProcedure();
        try {
            $procedure->update(['diagnosis' => 'Unsaved', 'odontogram_data' => [['tooth' => 11, 'unknown' => 'value']]]);
            $this->fail('Unsupported fields must not be silently discarded.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame('Unsupported odontogram tooth structure.', $exception->getMessage());
        }
        $this->assertSame('Initial diagnosis', $procedure->fresh()->diagnosis);
        $this->assertSame([['tooth' => 11, 'status' => null]], $procedure->fresh()->odontogram_data);
    }

    public function test_existing_attribute_supports_updates_null_empty_serialization_and_cascade_delete(): void
    {
        $procedure = $this->makeProcedure();
        $this->assertSame([['tooth' => 11, 'status' => null]], $procedure->fresh()->toArray()['odontogram_data']);
        $this->assertArrayNotHasKey('odontogram', $procedure->fresh()->toArray());

        $procedure->update(['diagnosis' => 'Updated']);
        $this->assertSame([['tooth' => 11, 'status' => null]], $procedure->fresh()->odontogram_data);

        foreach ([[], null, [['tooth' => 12]]] as $data) {
            AppointmentProcedure::updateOrCreate(['appointment_id' => $procedure->appointment_id], ['odontogram_data' => $data]);
            $this->assertSame($data, $procedure->fresh()->odontogram_data);
            $this->assertDatabaseCount('appointment_odontograms', 1);
        }

        $procedure->appointment->delete();
        $this->assertDatabaseCount('appointment_procedures', 0);
        $this->assertDatabaseCount('appointment_odontograms', 0);
    }

    public function test_patient_chart_falls_back_to_appointment_snapshot(): void
    {
        $procedure = $this->makeProcedure();
        $patient = $procedure->appointment->patient;
        $method = new \ReflectionMethod(\App\Http\Controllers\Dentist\OdontogramController::class, 'getSavedOdontogramDataForPatient');
        $controller = app(\App\Http\Controllers\Dentist\OdontogramController::class);

        $this->assertSame($procedure->odontogram_data, $method->invoke($controller, $patient));

        $procedure->update(['odontogram_data' => null]);
        $this->assertSame([], $method->invoke($controller, $patient));
    }

    public function test_outer_transaction_rolls_back_procedure_and_snapshot_together(): void
    {
        $procedure = $this->makeProcedure();
        try {
            DB::transaction(function () use ($procedure) {
                $procedure->update(['diagnosis' => 'Unsaved', 'odontogram_data' => [['tooth' => 12]]]);
                throw new \RuntimeException('Simulated later failure');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated later failure', $exception->getMessage());
        }

        $this->assertSame('Initial diagnosis', $procedure->fresh()->diagnosis);
        $this->assertSame([['tooth' => 11, 'status' => null]], $procedure->fresh()->odontogram_data);
    }

    public function test_timing_attributes_preserve_casts_serialization_and_partial_updates(): void
    {
        $procedure = $this->makeProcedure()->fresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $procedure->procedure_started_at);
        $this->assertSame('09:00:00', $procedure->procedure_started_at->format('H:i:s'));
        $this->assertSame($procedure->procedure_started_at->toJSON(), $procedure->toArray()['procedure_started_at']);
        $this->assertSame(1800, $procedure->toArray()['procedure_duration_seconds']);
        $this->assertArrayNotHasKey('timing', $procedure->toArray());

        foreach ([0, null, 123] as $duration) {
            $procedure->update(['procedure_duration_seconds' => $duration]);
            $fresh = $procedure->fresh();
            $this->assertSame($duration, $fresh->procedure_duration_seconds);
            $this->assertSame('09:00:00', $fresh->procedure_started_at->format('H:i:s'));
            $this->assertSame('09:30:00', $fresh->procedure_completed_at->format('H:i:s'));
            $this->assertDatabaseCount('appointment_procedure_timings', 1);
        }
        $procedure->update(['diagnosis' => 'Updated', 'procedure_completed_at' => null]);
        $this->assertNull($procedure->fresh()->procedure_completed_at);
        $this->assertSame(123, $procedure->fresh()->procedure_duration_seconds);
        $procedure->appointment->delete();
        $this->assertDatabaseCount('appointment_procedure_timings', 0);
    }

    public function test_timing_migration_round_trip_preserves_recorded_duration_and_nulls(): void
    {
        $procedure = $this->makeProcedure();
        $migration = require database_path('migrations/2026_09_07_050000_move_procedure_timings_to_separate_table.php');
        foreach ([123, 0, null] as $duration) {
            $procedure->update(['procedure_duration_seconds' => $duration]);
            $before = $procedure->fresh()->toArray();
            $migration->down();
            $this->assertDatabaseHas('appointment_procedures', [
                'id' => $procedure->id, 'procedure_duration_seconds' => $duration,
                'procedure_started_at' => '2026-09-07 09:00:00',
                'procedure_completed_at' => '2026-09-07 09:30:00',
            ]);
            $migration->up();
            $this->assertSame($before, $procedure->fresh()->toArray());
            $this->assertFalse(Schema::hasColumn('appointment_procedures', 'procedure_duration_seconds'));
        }
    }

    public function test_timing_failure_rolls_back_clinical_and_odontogram_changes(): void
    {
        $procedure = $this->makeProcedure();
        $before = $procedure->fresh()->toArray();
        // A real database constraint failure after the clinical and chart writes.
        DB::unprepared("CREATE TRIGGER reject_test_timing BEFORE UPDATE ON appointment_procedure_timings BEGIN SELECT RAISE(ABORT, 'Simulated timing failure'); END");
        try {
            $procedure->update([
                'diagnosis' => 'Unsaved', 'odontogram_data' => [['tooth' => 12]],
                'procedure_duration_seconds' => 777,
            ]);
            $this->fail('The invalid timing write should fail.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('Simulated timing failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER reject_test_timing');
        }
        $this->assertSame($before, $procedure->fresh()->toArray());
    }

    public function test_only_populated_markings_are_stored_while_blank_values_round_trip(): void
    {
        $procedure = $this->makeProcedure();
        $data = [['tooth' => 27, 'status' => null,
            'threeD' => ['code' => 'LC', 'label' => 'Composite', 'colorHex' => '#2563eb'],
            'surfaces' => ['top' => null, 'left' => [], 'right' => ['label' => '', 'code' => null],
                'bottom' => null, 'center' => ['code' => 'LC']]]];
        $procedure->update(['odontogram_data' => $data]);
        $this->assertDatabaseCount('appointment_odontogram_markings', 2);
        $this->assertSame($data, $procedure->fresh()->odontogram_data);

        $migration = require database_path('migrations/2026_09_07_080000_store_only_populated_odontogram_markings.php');
        $migration->down();
        $this->assertDatabaseCount('appointment_odontogram_markings', 7);
        $this->assertSame($data, $procedure->fresh()->odontogram_data);
        $migration->up();
        $this->assertDatabaseCount('appointment_odontogram_markings', 2);
        $this->assertSame($data, $procedure->fresh()->odontogram_data);

        $blank = [['tooth' => 27, 'status' => null, 'surfaces' => ['top' => null], 'threeD' => []]];
        $procedure->update(['odontogram_data' => $blank]);
        $this->assertDatabaseCount('appointment_odontogram_markings', 0);
        $this->assertSame($blank, $procedure->fresh()->odontogram_data);
    }

    private function makeProcedure(): AppointmentProcedure
    {
        $patient = Patient::create([
            'name' => 'Snapshot Patient', 'email' => 'snapshot@example.com',
            'phone' => '09123456789', 'birthdate' => '2000-01-01',
            'gender' => 'Male', 'password' => bcrypt('password'),
        ]);
        $role = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        $dentist = User::create([
            'name' => 'Snapshot Dentist', 'email' => 'snapshot-dentist@example.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 'active',
        ]);
        $appointment = $this->makeAppointment($patient, $dentist);

        return AppointmentProcedure::create([
            'appointment_id' => $appointment->id,
            'odontogram_data' => [['tooth' => 11, 'status' => null]],
            'oral_examination' => 'Initial examination', 'diagnosis' => 'Initial diagnosis',
            'prescriptions' => 'None', 'completion_action' => 'follow_up',
            'procedure_started_at' => '2026-09-07 09:00:00',
            'procedure_completed_at' => '2026-09-07 09:30:00',
            'procedure_duration_seconds' => 1800,
        ]);
    }

    private function makeAppointment(Patient $patient, User $dentist): Appointment
    {
        $appointmentTime = now()->startOfHour()->addMinutes($this->appointmentSequence * 30);
        $this->appointmentSequence++;

        return Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'service_type' => 'Oral Checkup',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => $appointmentTime->format('H:i:s'),
            'status' => 'upcoming',
        ]);
    }

    private function toothEntry(int $tooth, string $statusCode, array $surfaces = []): array
    {
        $surfaceData = [];

        foreach ($surfaces as $surface => $code) {
            $surfaceData[$surface] = [
                'code' => $code,
                'label' => $code,
                'colorHex' => '#2563eb',
            ];
        }

        return [
            'tooth' => $tooth,
            'status' => [
                'code' => $statusCode,
                'label' => $statusCode,
                'colorHex' => '#ef4444',
            ],
            'surfaces' => $surfaceData,
        ];
    }
}
