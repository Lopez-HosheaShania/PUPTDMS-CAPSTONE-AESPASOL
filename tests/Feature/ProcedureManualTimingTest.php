<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\DentalServicesProcedureTiming;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProcedureManualTimingTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-26 09:00:00'));
    }

    private function actor(string $slug = 'dentist', bool $permitted = true): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        if ($permitted) {
            $permission = Permission::firstOrCreate(['slug' => 'create_procedure_records'], ['name' => 'Record procedures']);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return User::create(['name' => 'Timing Dentist', 'email' => 'dentist'.++$this->sequence.'@example.test',
            'role_id' => $role->id, 'status' => 'active']);
    }

    private function visit(User $dentist, ?Patient $patient = null): Appointment
    {
        $patient ??= Patient::create(['name' => 'Timing Patient', 'email' => 'patient'.++$this->sequence.'@example.test',
            'classification' => 'faculty', 'password' => bcrypt('password')]);
        $service = ServiceType::firstOrCreate(['name' => 'Oral Check-Up']);

        return Appointment::create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id,
            'service_type_id' => $service->id, 'service_type' => $service->name,
            'appointment_date' => '2026-10-23', 'appointment_time' => sprintf('08:%02d:00', ++$this->sequence), 'status' => 'upcoming']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['odontogram_data' => [], 'has_applied_treatment' => false,
            'procedure_started_at' => '09:17:45',
            'procedure_completed_at' => '09:18:46',
            'completion_action' => 'finished'], $overrides);
    }

    public static function visitTypes(): array
    {
        return ['scheduled' => [false], 'walk-in' => [true]];
    }

    #[DataProvider('visitTypes')]
    public function test_entered_times_are_saved_exactly_and_reloaded(bool $walkIn): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $bookedTime = $visit->appointment_time;
        if ($walkIn) $visit->update(['is_walk_in' => true]);
        $this->actingAs($dentist)->withSession(['role' => 'dentist', 'procedure_started_at_'.$visit->id => '2000-01-01 00:00:00']);

        $this->get(route('dentist.odontogram', $visit))->assertOk()
            ->assertSee('type="text" id="procedure_started_at"', false)
            ->assertDontSee('id="procedure_completed_at"', false)
            ->assertSee('name="procedure_duration_hms"', false)
            ->assertSee('placeholder="HH:MM"', false)
            ->assertDontSee('placeholder="HH:MM AM/PM"', false)
            ->assertSee('class="form-input-custom js-flatpickr-time"', false)
            ->assertSee('data-time-minute-increment="1"', false)
            ->assertDontSee('odontogram-time-period-control', false)
            ->assertDontSee('type="time"', false)->assertDontSee('type="datetime-local"', false)->assertDontSee('Time Processed');
        $this->postJson(route('dentist.odontogram.save', $visit), $this->payload([
            'procedure_started_at' => '14:17', 'procedure_completed_at' => null,
            'procedure_duration_hms' => '00:01:00', 'procedure_duration_seconds' => 999999,
        ]))->assertOk();

        $procedure = $visit->fresh()->procedure;
        $this->assertSame('2026-10-23 14:17:00', $procedure->procedure_started_at->toDateTimeString());
        $this->assertSame('2026-10-23 14:18:00', $procedure->procedure_completed_at->toDateTimeString());
        $this->assertSame(60, $procedure->procedure_duration_seconds);
        $this->assertSame('2026-10-23 14:18:00', DentalServicesProcedureTiming::timeProcessed($visit->fresh())->toDateTimeString());
        $this->assertSame($bookedTime, $visit->fresh()->appointment_time);
        $this->get(route('dentist.odontogram', $visit))->assertOk()
            ->assertSee('value="14:17"', false)->assertSee('value="00:01:00"', false);
    }

    public function test_opening_workspace_does_not_capture_current_time_or_change_history(): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->get(route('dentist.odontogram', $visit))->assertOk();
        $this->assertNull(session('procedure_started_at_'.$visit->id));
        $this->assertNull($visit->fresh()->procedure);

        $procedure = AppointmentProcedure::create(['appointment_id' => $visit->id,
            'procedure_started_at' => '2020-01-01 09:00:00', 'procedure_completed_at' => null]);
        $before = $procedure->fresh()->timing->getAttributes();
        $this->get(route('dentist.odontogram', $visit))->assertOk()->assertSee('value="09:00"', false);
        $this->assertSame($before, $procedure->fresh()->timing->getAttributes());
    }

    public static function durationIntervals(): array
    {
        return [
            'ten minutes' => ['12:00', '00:10:00', '2026-10-23 12:10:00', 600],
            'crosses midnight' => ['23:50', '00:30:00', '2026-10-24 00:20:00', 1800],
            'includes seconds' => ['09:17', '00:01:01', '2026-10-23 09:18:01', 61],
            'zero duration' => ['09:17', '00:00:00', '2026-10-23 09:17:00', 0],
        ];
    }

    #[DataProvider('durationIntervals')]
    public function test_end_time_is_calculated_and_retained_for_reports(string $start, string $duration, string $end, int $seconds): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $payload = $this->payload(['procedure_started_at' => $start, 'procedure_duration_hms' => $duration]);
        unset($payload['procedure_completed_at']);
        $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.save', $visit), $payload)->assertOk();

        $saved = $visit->fresh();
        $this->assertSame($end, $saved->procedure->procedure_completed_at->toDateTimeString());
        $this->assertSame($seconds, $saved->procedure->procedure_duration_seconds);
        $this->assertSame($end, DentalServicesProcedureTiming::timeProcessed($saved)->toDateTimeString());
        $this->get(route('dentist.odontogram', $visit))->assertOk()
            ->assertSee('value="'.$duration.'"', false)->assertDontSee('id="procedure_completed_at"', false);
    }

    public function test_invalid_duration_is_rejected_without_saving(): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);
        foreach (['', '-01:00:00', '00:60:00', '00:00:60', 'abc', ['00:30:00']] as $duration) {
            $this->postJson(route('dentist.odontogram.save', $visit), $this->payload([
                'procedure_completed_at' => null, 'procedure_duration_hms' => $duration,
            ]))->assertUnprocessable()->assertJsonValidationErrors('procedure_duration_hms');
        }
        $this->assertDatabaseCount('appointment_procedures', 0);
    }

    public static function invalidTimes(): array
    {
        return [
            'missing start' => [['procedure_started_at' => ''], 'procedure_started_at'],
            'missing finish' => [['procedure_completed_at' => ''], 'procedure_completed_at'],
            'malformed' => [['procedure_started_at' => 'yesterday'], 'procedure_started_at'],
            'invalid calendar date' => [['procedure_started_at' => '2026-02-30T09:00'], 'procedure_started_at'],
            'array injection' => [['procedure_completed_at' => ['2026-10-23T09:20']], 'procedure_completed_at'],
            'invalid hour' => [['procedure_started_at' => '25:00'], 'procedure_started_at'],
            'invalid minute' => [['procedure_completed_at' => '09:60'], 'procedure_completed_at'],
            'earlier finish' => [['procedure_completed_at' => '09:00'], 'procedure_completed_at'],
        ];
    }

    #[DataProvider('invalidTimes')]
    public function test_invalid_times_are_rejected_without_saving(array $overrides, string $field): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.save', $visit), $this->payload($overrides))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('appointment_procedures', 0);
        $this->assertSame('upcoming', $visit->fresh()->status);
    }

    public function test_cross_midnight_and_zero_duration_are_valid(): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.save', $visit), $this->payload([
                'procedure_started_at' => '2026-10-23T23:50:15',
                'procedure_completed_at' => '2026-10-24T00:20:15',
            ]))->assertOk();
        $this->assertSame(1800, $visit->fresh()->procedure->procedure_duration_seconds);

        $other = $this->visit($dentist);
        $this->postJson(route('dentist.odontogram.save', $other), $this->payload([
            'procedure_completed_at' => '09:17:45',
        ]))->assertOk();
        $this->assertSame(0, $other->fresh()->procedure->procedure_duration_seconds);
    }

    public static function sharedResources(): array
    {
        return ['dentist' => [false], 'patient with another dentist' => [true]];
    }

    #[DataProvider('sharedResources')]
    public function test_overlapping_treatment_is_rejected_and_adjacent_treatment_is_allowed(bool $samePatient): void
    {
        $dentist = $this->actor();
        $first = $this->visit($dentist);
        $first->update(['status' => 'completed']);
        AppointmentProcedure::create(['appointment_id' => $first->id,
            'procedure_started_at' => '2026-10-23 23:50:00',
            'procedure_completed_at' => '2026-10-24 00:20:00', 'procedure_duration_seconds' => 1800]);
        $otherDentist = $samePatient ? $this->actor() : $dentist;
        $second = $this->visit($otherDentist, $samePatient ? $first->patient : null);
        $this->actingAs($otherDentist)->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.save', $second), $this->payload([
                'procedure_started_at' => '2026-10-24T00:10', 'procedure_completed_at' => '2026-10-24T00:30',
            ]))->assertUnprocessable()->assertJsonValidationErrors('procedure_started_at');
        $this->assertDatabaseCount('appointment_procedures', 1);

        $this->postJson(route('dentist.odontogram.save', $second), $this->payload([
            'procedure_started_at' => '2026-10-24T00:20', 'procedure_completed_at' => '2026-10-24T00:30',
        ]))->assertOk();
    }

    public function test_wrong_dentist_and_admin_cannot_change_clinical_timing(): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $this->actingAs($this->actor())->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.save', $visit), $this->payload())->assertNotFound();
        $this->actingAs($this->actor('admin', false))->withSession(['role' => 'admin'])
            ->postJson(route('dentist.odontogram.save', $visit), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('appointment_procedures', 0);
    }

    public function test_existing_appointment_entry_uses_manual_times_instead_of_draft_booking_and_duration(): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $patient = $visit->patient;
        $method = new \ReflectionMethod(\App\Http\Controllers\Dentist\OdontogramController::class, 'getPatientHistoryDefaults');
        $draft = array_merge($method->invoke(new \App\Http\Controllers\Dentist\OdontogramController, $patient), [
            'appointment_date' => '2026-10-23', 'appointment_time' => '15:00',
            'service_type' => 'Oral Check-Up', 'procedure_duration_hms' => '99:59:59',
            'emergency_person' => 'Test Contact', 'emergency_number' => '09123456789', 'emergency_relation' => 'Father',
        ]);
        $key = 'existing_appointment_draft_patient_'.$patient->id;
        $this->actingAs($dentist)->withSession(['role' => 'dentist', $key => $draft]);
        $this->get(route('dentist.odontogram.existing-appointment.odontogram', $patient))
            ->assertOk()->assertSee('Time In')->assertSee('Procedure Duration')->assertDontSee('Time Processed');
        $url = route('dentist.odontogram.existing-appointment.store', $patient);
        $this->postJson($url, $this->payload(['procedure_completed_at' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('procedure_completed_at');
        $this->assertNotNull(session($key));
        $this->assertDatabaseCount('appointments', 1);

        $this->postJson($url, $this->payload([
            'procedure_completed_at' => null, 'procedure_duration_hms' => '00:01:01',
        ]))->assertOk();
        $saved = Appointment::latest('id')->firstOrFail();
        $this->assertSame('15:00:00', $saved->appointment_time);
        $this->assertSame('2026-10-23 09:17:45', $saved->procedure->procedure_started_at->toDateTimeString());
        $this->assertSame('2026-10-23 09:18:46', $saved->procedure->procedure_completed_at->toDateTimeString());
        $this->assertSame(61, $saved->procedure->procedure_duration_seconds);
        $this->assertNull(session($key));
    }

    public function test_saved_visit_correction_validates_times_and_does_not_overwrite_them_with_current_time(): void
    {
        $dentist = $this->actor();
        $visit = $this->visit($dentist);
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);
        $this->postJson(route('dentist.odontogram.save', $visit), $this->payload())->assertOk();
        $this->get(route('dentist.odontogram.saved.edit', $visit))->assertOk()
            ->assertSee('value="09:17"', false)->assertSee('value="00:01:01"', false);
        $reason = \App\Models\OdontogramRevisionReasonType::where('code', 'wrong_tooth')->firstOrFail();
        $payload = $this->payload(['odontogram_data' => [['tooth' => 11, 'status' => ['code' => 'F', 'label' => 'Filled']]],
            'has_applied_treatment' => true, 'reason_type_id' => $reason->id]);
        $url = route('dentist.odontogram.saved.update', $visit);
        $this->postJson($url, array_replace($payload, ['procedure_completed_at' => '09:00']))
            ->assertUnprocessable()->assertJsonValidationErrors('procedure_completed_at');
        $this->assertSame('2026-10-23 09:18:46', $visit->fresh()->procedure->procedure_completed_at->toDateTimeString());
        $this->postJson($url, array_replace($payload, [
            'procedure_completed_at' => null, 'procedure_duration_hms' => '00:01:01',
        ]))->assertOk();
        $this->assertSame('2026-10-23 09:17:45', $visit->fresh()->procedure->procedure_started_at->toDateTimeString());
        $this->assertSame('2026-10-23 09:18:46', $visit->fresh()->procedure->procedure_completed_at->toDateTimeString());
    }
}
