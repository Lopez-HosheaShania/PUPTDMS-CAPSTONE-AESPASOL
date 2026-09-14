<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Notifications\FollowUpAppointmentReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppointmentDetailsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_fields_relationships_serialization_and_partial_updates_are_preserved(): void
    {
        $appointment = $this->appointment();
        $appointment->update([
            'original_dentist_id' => $appointment->dentist_id,
            'transferred_by' => $appointment->dentist_id,
            'transferred_at' => '2026-09-07 09:00:00', 'transfer_reason' => 'retirement',
        ]);
        $fresh = Appointment::with(['originalDentist', 'transferActor'])->findOrFail($appointment->id);
        $this->assertSame($appointment->dentist_id, $fresh->originalDentist->id);
        $this->assertSame($appointment->dentist_id, $fresh->transferActor->id);
        $this->assertSame('09:00:00', $fresh->transferred_at->format('H:i:s'));
        $this->assertSame($fresh->transferred_at->toJSON(), $fresh->toArray()['transferred_at']);
        $this->assertArrayNotHasKey('transfer_details', $fresh->toArray());

        $fresh->update(['status' => 'rescheduled', 'transfer_reason' => null]);
        $this->assertNull($fresh->fresh()->transfer_reason);
        $this->assertSame($appointment->dentist_id, $fresh->fresh()->original_dentist_id);
        $this->assertDatabaseCount('appointment_transfers', 1);
        $this->assertFalse(Schema::hasColumn('appointments', 'transferred_at'));
    }

    public function test_reminders_send_once_and_can_be_reset_after_rescheduling(): void
    {
        Notification::fake();
        $appointment = $this->appointment();
        $user = $appointment->patient->user;
        $appointment->update(['is_follow_up' => true, 'appointment_date' => now()->addDays(2)->toDateString()]);
        $this->artisan('appointments:send-follow-up-reminders')->assertSuccessful();
        $this->artisan('appointments:send-follow-up-reminders')->assertSuccessful();
        Notification::assertSentToTimes($user, FollowUpAppointmentReminderNotification::class, 1);
        $this->assertNotNull($appointment->fresh()->follow_up_reminder_sent_at);

        $appointment->update([
            'status' => 'rescheduled', 'appointment_date' => now()->addDay()->toDateString(),
            'follow_up_reminder_sent_at' => null,
            'follow_up_today_reminder_sent_at' => null,
            'follow_up_one_day_reminder_sent_at' => null,
        ]);
        $this->assertNull($appointment->fresh()->follow_up_reminder_sent_at);
        $this->artisan('appointments:send-follow-up-reminders')->assertSuccessful();
        $this->artisan('appointments:send-follow-up-reminders')->assertSuccessful();
        Notification::assertSentToTimes($user, FollowUpAppointmentReminderNotification::class, 2);
        $this->assertNotNull($appointment->fresh()->follow_up_one_day_reminder_sent_at);
        $this->assertDatabaseCount('appointment_reminders', 1);
    }

    public function test_migration_round_trip_preserves_both_populated_and_null_details(): void
    {
        // Match the migration runner: SQLite table rebuilds cannot run inside RefreshDatabase's transaction.
        DB::commit();
        try {
            $appointment = $this->appointment();
            $procedure = $appointment->procedure()->create(['diagnosis' => 'Preserved', 'odontogram_data' => [['tooth' => 11]]]);
            $migration = require database_path('migrations/2026_09_07_060000_separate_appointment_transfer_and_reminder_details.php');
            foreach ([null, '2026-09-07 09:00:00'] as $date) {
                $appointment->update([
                    'original_dentist_id' => $appointment->dentist_id, 'transferred_by' => $appointment->dentist_id,
                    'transfer_reason' => 'retirement', 'transferred_at' => $date,
                    'follow_up_reminder_sent_at' => $date, 'follow_up_today_reminder_sent_at' => $date,
                    'follow_up_one_day_reminder_sent_at' => $date,
                ]);
                $before = $appointment->fresh()->toArray();
                $migration->down();
                $this->assertDatabaseHas('appointments', [
                    'id' => $appointment->id, 'transferred_at' => $date, 'follow_up_reminder_sent_at' => $date,
                ]);
                $migration->up();
                $this->assertSame($before, $appointment->fresh()->toArray());
                $this->assertSame('Preserved', $procedure->fresh()->diagnosis);
                $this->assertSame([['tooth' => 11]], $procedure->fresh()->odontogram_data);
            }
            $migration->down();
            Schema::table('appointments', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn('follow_up_today_reminder_sent_at');
            });
            $migration->up();
            $this->assertNull($appointment->fresh()->follow_up_today_reminder_sent_at);
            $this->assertNotNull($appointment->fresh()->follow_up_reminder_sent_at);
            $this->assertSame([['tooth' => 11]], $procedure->fresh()->odontogram_data);
            $appointment->delete();
            $this->assertDatabaseCount('appointment_transfers', 0);
            $this->assertDatabaseCount('appointment_reminders', 0);
        } finally {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
            DB::beginTransaction();
        }
    }

    public function test_failed_detail_write_rolls_back_appointment_changes(): void
    {
        $appointment = $this->appointment();
        $before = $appointment->fresh()->toArray();
        try {
            $appointment->update(['status' => 'rescheduled', 'original_dentist_id' => 999999]);
            $this->fail('Expected a foreign key failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('FOREIGN KEY', $exception->getMessage());
        }
        $this->assertSame($before, $appointment->fresh()->toArray());
    }

    public function test_reserved_booking_links_constraints_and_slot_release_are_preserved(): void
    {
        $appointment = $this->appointment();
        $period = \App\Models\ReservedBookingPeriod::create([
            'title' => 'Test period', 'reserved_date' => now()->toDateString(),
            'start_time' => '09:00:00', 'end_time' => '12:00:00', 'booking_mode' => 'timeslot',
            'target_patient_type' => 'guest', 'max_capacity' => 3, 'is_active' => true,
            'created_by' => $appointment->dentist_id, 'updated_by' => $appointment->dentist_id,
        ]);
        $slot = $period->slots()->create(['slot_time' => '09:00:00', 'max_capacity' => 1]);
        $appointment->update(['reserved_booking_period_id' => $period->id, 'reserved_booking_period_slot_id' => $slot->id]);
        $this->assertSame($appointment->id, $slot->fresh()->appointment->id);
        $this->assertSame(1, $period->activeAppointments()->count());
        $this->assertSame(0, Appointment::regularBooking()->count());
        $this->assertSame(1, Appointment::forReservedSlot($slot->id)->count());
        $this->assertSame(1, Appointment::whereHas('reservedBookingPeriod', fn ($query) => $query->withScheduleColumns()->whereTime('end_time', '>=', '10:00:00'))->count());
        $this->assertSame($slot->id, $appointment->fresh()->reservedBookingPeriodSlot->id);

        $other = Appointment::create(['patient_id' => $appointment->patient_id, 'dentist_id' => $appointment->dentist_id,
            'service_type' => 'Oral Checkup', 'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '11:00:00', 'status' => 'upcoming']);
        foreach ([['reserved_booking_period_id' => $period->id], ['reserved_booking_period_slot_id' => $slot->id]] as $values) {
            try {
                $other->fresh()->update($values);
                $this->fail('Duplicate booking must fail.');
            } catch (\Illuminate\Database\QueryException $exception) {
                $this->assertStringContainsString('UNIQUE', $exception->getMessage());
            }
        }
        $appointment->update(['status' => 'cancelled', 'reserved_booking_period_slot_id' => null]);
        $this->assertNull($slot->fresh()->appointment);
        $this->assertSame(0, $period->activeAppointments()->count());
        $this->assertSame($period->id, $appointment->fresh()->reservedBookingPeriod->id);
    }

    public function test_follow_up_relationships_and_booking_migration_round_trip(): void
    {
        DB::commit();
        try {
            $original = $this->appointment();
            $period = \App\Models\ReservedBookingPeriod::create([
                'title' => 'Migration period', 'reserved_date' => now()->toDateString(),
                'start_time' => '09:00:00', 'end_time' => '12:00:00', 'booking_mode' => 'timeslot',
                'target_patient_type' => 'guest', 'max_capacity' => 1, 'is_active' => true,
                'created_by' => $original->dentist_id, 'updated_by' => $original->dentist_id,
            ]);
            $slot = $period->slots()->create(['slot_time' => '09:00:00', 'max_capacity' => 1]);
            $original->update(['reserved_booking_period_id' => $period->id, 'reserved_booking_period_slot_id' => $slot->id]);
            $procedure = $original->procedure()->create(['diagnosis' => 'Preserved', 'odontogram_data' => [['tooth' => 11]]]);
            $followUp = Appointment::create(['patient_id' => $original->patient_id, 'dentist_id' => $original->dentist_id,
                'service_type' => 'Oral Checkup', 'appointment_date' => now()->addDay()->toDateString(),
                'appointment_time' => '11:00:00', 'status' => 'upcoming', 'is_follow_up' => true,
                'follow_up_for_appointment_id' => $original->id, 'follow_up_reason' => 'Review healing']);
            $this->assertSame($followUp->id, $original->fresh()->followUpAppointments->sole()->id);
            $this->assertSame($original->id, $followUp->fresh()->originalAppointment->id);
            $this->assertSame(1, Appointment::followUps()->count());
            $before = $followUp->fresh()->toArray();
            $migration = require database_path('migrations/2026_09_07_070000_separate_reserved_bookings_and_follow_ups.php');
            $migration->down();
            $this->assertDatabaseHas('appointments', ['id' => $followUp->id, 'follow_up_reason' => 'Review healing']);
            $migration->up();
            $this->assertSame($before, $followUp->fresh()->toArray());
            $this->assertSame($slot->id, $original->fresh()->reserved_booking_period_slot_id);
            $this->assertSame($period->id, $original->fresh()->reserved_booking_period_id);
            $this->assertSame([['tooth' => 11]], $procedure->fresh()->odontogram_data);
            $this->assertFalse(Schema::hasColumn('appointments', 'follow_up_reason'));
            $this->assertFalse(Schema::hasColumn('appointments', 'reserved_booking_period_id'));
            $original->delete();
            $this->assertNull($followUp->fresh()->follow_up_for_appointment_id);
            $this->assertTrue($followUp->fresh()->is_follow_up);
            $followUp->delete();
            $this->assertDatabaseCount('appointment_follow_ups', 0);
            $this->assertDatabaseCount('appointment_reserved_bookings', 0);
        } finally {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
            DB::beginTransaction();
        }
    }

    private function appointment(): Appointment
    {
        $role = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        $dentist = User::create(['name' => 'Dentist', 'email' => 'details-dentist@example.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 'active']);
        $patientRole = Role::create(['name' => 'Patient', 'slug' => 'patient']);
        $user = User::create(['name' => 'Patient', 'email' => 'details-patient@example.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'status' => 'active']);
        $patient = Patient::create(['user_id' => $user->id, 'name' => 'Patient', 'email' => $user->email,
            'password' => bcrypt('password')]);

        return Appointment::create(['patient_id' => $patient->id, 'dentist_id' => $dentist->id,
            'service_type' => 'Oral Checkup', 'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:00:00', 'status' => 'upcoming']);
    }
}
