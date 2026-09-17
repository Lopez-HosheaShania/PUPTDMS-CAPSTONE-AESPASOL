<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ReservedBookingPeriod;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservedBookingPeriodDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function period(array $values = []): ReservedBookingPeriod
    {
        return ReservedBookingPeriod::create(array_merge([
            'title' => 'Student check-up', 'notes' => 'Keep historical notes',
            'reserved_date' => '2026-10-05', 'start_time' => '09:00:00', 'end_time' => '11:00:00',
            'is_active' => true, 'booking_mode' => 'timeslot', 'timeslot_duration_minutes' => 30,
            'max_capacity' => 2, 'target_patient_type' => 'student', 'program_code' => 'BSIT',
            'year_level' => 1, 'section' => '1', 'allowed_services' => ['Cleaning'],
        ], $values));
    }

    public function test_main_table_contains_only_identity_and_record_metadata(): void
    {
        $period = $this->period();
        $this->assertEqualsCanonicalizing(['id', 'title', 'notes', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'], Schema::getColumnListing('reserved_booking_periods'));
        $this->assertDatabaseHas('reserved_booking_period_schedules', ['reserved_booking_period_id' => $period->id, 'active_reserved_date' => '2026-10-05']);
        $this->assertDatabaseHas('reserved_booking_period_configurations', ['reserved_booking_period_id' => $period->id, 'max_capacity' => 2, 'restrict_services' => true]);
        $this->assertDatabaseHas('reserved_booking_period_targets', ['reserved_booking_period_id' => $period->id, 'program_code' => 'BSIT']);
        $payload = $period->fresh()->toArray();
        foreach (['schedule', 'configuration', 'target', 'restrict_services'] as $internal) {
            $this->assertArrayNotHasKey($internal, $payload);
        }
        $this->assertSame('2026-10-05', $payload['reserved_date']);
        $this->assertSame(1, $payload['year_level']);
        $this->assertSame(30, $payload['timeslot_duration_minutes']);
        $this->assertSame(['Cleaning'], $payload['allowed_services']);
        $period->update(['title' => 'Updated title']);
        $this->assertSame('2026-10-05', $period->fresh()->active_reserved_date->format('Y-m-d'));
    }

    public function test_failed_target_write_rolls_back_schedule_configuration_and_parent(): void
    {
        $period = $this->period();
        DB::statement("CREATE TRIGGER reject_reserved_target BEFORE UPDATE ON reserved_booking_period_targets BEGIN SELECT RAISE(ABORT, 'simulated target failure'); END");
        try {
            $period->update(['title' => 'Must roll back', 'reserved_date' => '2026-10-06', 'max_capacity' => 5, 'program_code' => 'BSCE']);
            $this->fail('Expected detail write failure.');
        } catch (QueryException $exception) {
            $fresh = $period->fresh();
            $this->assertSame('Student check-up', $fresh->title);
            $this->assertSame('2026-10-05', $fresh->reserved_date->format('Y-m-d'));
            $this->assertSame(2, $fresh->max_capacity);
            $this->assertSame('BSIT', $fresh->program_code);
        } finally {
            DB::statement('DROP TRIGGER reject_reserved_target');
        }
    }

    public function test_migration_round_trip_preserves_deleted_periods_appointments_and_slots(): void
    {
        $period = $this->period();
        $deleted = $this->period(['is_active' => false, 'allowed_services' => null]);
        $deleted->delete();
        $slot = $period->slots()->create(['slot_time' => '09:00:00', 'max_capacity' => 1]);
        $patient = Patient::create(['name' => 'Migration test patient', 'email' => 'migration@example.test', 'password' => bcrypt('password')]);
        $appointment = Appointment::create([
            'patient_id' => $patient->id, 'appointment_date' => '2026-10-05', 'appointment_time' => '09:00:00',
            'status' => 'upcoming', 'service_type' => 'Cleaning',
            'reserved_booking_period_id' => $period->id, 'reserved_booking_period_slot_id' => $slot->id,
        ]);
        $before = [$period->fresh()->toArray(), $deleted->fresh()->toArray()];
        $migration = require database_path('migrations/2026_09_14_000002_separate_reserved_booking_period_details.php');
        $migration->down();
        $this->assertDatabaseHas('reserved_booking_periods', ['id' => $period->id, 'program_code' => 'BSIT', 'reserved_date' => '2026-10-05', 'max_capacity' => 2]);
        $migration->up();
        $this->assertSame($before, [$period->fresh()->toArray(), $deleted->fresh()->toArray()]);
        $this->assertSame($period->id, $appointment->fresh()->reservedBookingPeriod->id);
        $this->assertSame($slot->id, $appointment->fresh()->reserved_booking_period_slot_id);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_date_conflicts_and_soft_delete_restore_still_use_unique_schedule_date(): void
    {
        $period = $this->period();
        $draft = $this->period(['is_active' => false]);
        try {
            $draft->update(['is_active' => true]);
            $this->fail('Expected one active period per date constraint.');
        } catch (QueryException $exception) {
            $this->assertFalse($draft->fresh()->is_active);
        }
        $period->delete();
        $draft->refresh()->update(['is_active' => true]);
        $this->assertSame($draft->id, ReservedBookingPeriod::active()->whereDate('reserved_date', '2026-10-05')->firstOrFail()->id);
        try {
            $period->restore();
            $this->fail('Expected restoring to an occupied date to fail.');
        } catch (QueryException $exception) {
            $this->assertTrue($period->fresh()->trashed());
        }
        $draft->delete();
        $period->refresh()->restore();
        $this->assertTrue($period->fresh()->is_active);
        $period->forceDelete();
        foreach (['schedules', 'configurations', 'targets', 'services'] as $suffix) {
            $this->assertDatabaseMissing('reserved_booking_period_'.$suffix, ['reserved_booking_period_id' => $period->id]);
        }
    }
}
