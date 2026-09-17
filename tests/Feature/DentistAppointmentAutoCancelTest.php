<?php

namespace Tests\Feature;

use App\Mail\AppointmentCancelledMail;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AppointmentCancelledNotification;
use App\Services\DentistDutyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DentistAppointmentAutoCancelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_manual_dentist_out_cancels_same_day_eligible_appointments_and_notifies_patient(): void
    {
        Carbon::setTestNow('2026-09-04 15:30:00');
        Notification::fake();
        Mail::fake();

        $dentist = $this->makeDentist('manual-out-dentist@example.com');
        [$patient, $patientUser] = $this->makePatientWithUser('manual-out-patient@example.com');

        $completedAppointment = $this->makeAppointment($dentist, $patient, [
            'appointment_time' => '09:00:00',
            'status' => 'completed',
        ]);

        $upcomingAppointment = $this->makeAppointment($dentist, $patient, [
            'appointment_time' => '16:00:00',
        ]);

        $response = $this->actingAs($dentist)
            ->withSession(['role' => 'dentist', 'dentist_id' => $dentist->id, 'dentist_name' => $dentist->name])
            ->postJson(route('dentist.clinic-status.update'), [
                'status' => 'out',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'out')
            ->assertJsonPath('appointments_cancelled', 1);

        $this->assertSame('completed', $completedAppointment->fresh()->status);
        $this->assertSame('cancelled', $upcomingAppointment->fresh()->status);
        $this->assertSame('Dentist clinic duty ended', $upcomingAppointment->fresh()->cancellation_reason);

        $this->assertCount(1, Notification::sent($patientUser, AppointmentCancelledNotification::class));
        Mail::assertSent(AppointmentCancelledMail::class, 1);
        $this->assertTrue(AuditLog::where('module', 'dentist_duty')->forActorRole('dentist')->exists());
    }

    public function test_duty_end_cancellation_remains_persisted_and_displayed_after_sync_and_page_reload(): void
    {
        Carbon::setTestNow('2026-09-04 15:00:00');
        Notification::fake();

        $dentist = $this->makeDentist('cancelled-status-dentist@example.com');
        [$patient, $patientUser] = $this->makePatientWithUser('cancelled-status-patient@example.com');
        $appointment = $this->makeAppointment($dentist, $patient);

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])
            ->assertOk()
            ->assertJsonPath('cancelled_appointment_ids.0', $appointment->id);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Dentist clinic duty ended',
        ]);

        app(DentistDutyService::class)->syncOutDentistAppointments($dentist);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertCount(1, Notification::sent($patientUser, AppointmentCancelledNotification::class));

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->get(route('dentist.dentist.patients'))
            ->assertOk()
            ->assertSee('Cancelled');
    }

    public function test_completed_appointment_remains_completed_when_dentist_goes_out(): void
    {
        Carbon::setTestNow('2026-09-04 15:30:00');
        Notification::fake();

        $dentist = $this->makeDentist('completed-protection-dentist@example.com');
        [$patient] = $this->makePatientWithUser('completed-protection-patient@example.com');

        $appointment = $this->makeAppointment($dentist, $patient, [
            'status' => 'completed',
        ]);

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])
            ->assertOk();

        $this->assertSame('completed', $appointment->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_future_appointment_remains_unchanged_when_dentist_goes_out_today(): void
    {
        Carbon::setTestNow('2026-09-04 15:30:00');
        Notification::fake();

        $dentist = $this->makeDentist('future-protection-dentist@example.com');
        [$patient] = $this->makePatientWithUser('future-protection-patient@example.com');

        $appointment = $this->makeAppointment($dentist, $patient, [
            'appointment_date' => '2026-09-05',
        ]);

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])
            ->assertOk();

        $this->assertSame('upcoming', $appointment->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_already_cancelled_appointment_remains_unchanged_without_duplicate_notification(): void
    {
        Carbon::setTestNow('2026-09-04 15:30:00');
        Notification::fake();

        $dentist = $this->makeDentist('already-cancelled-dentist@example.com');
        [$patient, $patientUser] = $this->makePatientWithUser('already-cancelled-patient@example.com');

        $appointment = $this->makeAppointment($dentist, $patient, [
            'status' => 'cancelled',
            'cancellation_reason' => 'Patient requested cancellation.',
        ]);

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])
            ->assertOk();

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('Patient requested cancellation.', $appointment->fresh()->cancellation_reason);
        Notification::assertNothingSent();
        $this->assertCount(0, Notification::sent($patientUser, AppointmentCancelledNotification::class));
    }

    public function test_only_the_selected_dentists_same_day_appointments_are_cancelled(): void
    {
        Carbon::setTestNow('2026-09-04 15:30:00');
        Notification::fake();

        $dentistA = $this->makeDentist('dentist-a@example.com');
        $dentistB = $this->makeDentist('dentist-b@example.com');
        [$patientA, $patientUserA] = $this->makePatientWithUser('other-dentist-a@example.com');
        [$patientB, $patientUserB] = $this->makePatientWithUser('other-dentist-b@example.com');

        $appointmentA = $this->makeAppointment($dentistA, $patientA);
        $appointmentB = $this->makeAppointment($dentistB, $patientB);

        $this->actingAs($dentistA)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])
            ->assertOk();

        $this->assertSame('cancelled', $appointmentA->fresh()->status);
        $this->assertSame('upcoming', $appointmentB->fresh()->status);
        $this->assertCount(1, Notification::sent($patientUserA, AppointmentCancelledNotification::class));
        $this->assertCount(0, Notification::sent($patientUserB, AppointmentCancelledNotification::class));
    }

    public function test_automatic_eight_pm_clock_out_cancels_remaining_same_day_appointments(): void
    {
        Carbon::setTestNow('2026-09-04 20:00:00');
        Notification::fake();
        Mail::fake();

        $dentist = $this->makeDentist('auto-clockout-dentist@example.com');
        [$patient, $patientUser] = $this->makePatientWithUser('auto-clockout-patient@example.com');

        $appointment = $this->makeAppointment($dentist, $patient);

        $this->artisan('appointments:auto-end-dentist-duty')
            ->expectsOutput('Dentists automatically clocked out: 1')
            ->expectsOutput('Appointments automatically cancelled: 1')
            ->expectsOutput('Patient notifications created: 1')
            ->assertSuccessful();

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertCount(1, Notification::sent($patientUser, AppointmentCancelledNotification::class));
        Mail::assertSent(AppointmentCancelledMail::class, 1);
        $this->assertTrue(AuditLog::where('module', 'dentist_duty')->forActorRole('system')->exists());
    }

    public function test_automatic_process_does_not_run_before_eight_pm(): void
    {
        Carbon::setTestNow('2026-09-04 19:59:00');
        Notification::fake();

        $dentist = $this->makeDentist('before-eight-dentist@example.com');
        [$patient] = $this->makePatientWithUser('before-eight-patient@example.com');

        $appointment = $this->makeAppointment($dentist, $patient);

        $this->artisan('appointments:auto-end-dentist-duty')
            ->expectsOutput('Skipped automatic dentist duty end before 8:00 PM Asia/Manila.')
            ->assertSuccessful();

        $this->assertSame('upcoming', $appointment->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_end_duty_process_is_idempotent_across_repeated_runs(): void
    {
        Carbon::setTestNow('2026-09-04 15:30:00');
        Notification::fake();

        $dentist = $this->makeDentist('idempotent-dentist@example.com');
        [$patient, $patientUser] = $this->makePatientWithUser('idempotent-patient@example.com');

        $appointment = $this->makeAppointment($dentist, $patient);

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])
            ->assertOk();

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])
            ->assertOk();

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertCount(1, Notification::sent($patientUser, AppointmentCancelledNotification::class));
        $this->assertSame(1, AuditLog::query()->where('module', 'dentist_duty')->count());
    }

    public function test_completed_procedure_record_prevents_auto_cancellation_even_if_status_is_not_synced(): void
    {
        Carbon::setTestNow('2026-09-04 15:30:00');
        Notification::fake();

        $dentist = $this->makeDentist('procedure-protection-dentist@example.com');
        [$patient] = $this->makePatientWithUser('procedure-protection-patient@example.com');

        $appointment = $this->makeAppointment($dentist, $patient);

        AppointmentProcedure::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'completion_action' => 'finished',
            'procedure_started_at' => Carbon::parse('2026-09-04 14:00:00'),
            'procedure_completed_at' => Carbon::parse('2026-09-04 14:30:00'),
            'procedure_duration_seconds' => 1800,
        ]);

        $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])
            ->assertOk();

        $this->assertSame('upcoming', $appointment->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_unfinished_and_missing_timing_records_remain_eligible_for_auto_cancellation(): void
    {
        Carbon::setTestNow('2026-09-04 15:30:00');
        Notification::fake();
        $dentist = $this->makeDentist('unfinished-timing-dentist@example.com');
        $appointments = [];
        foreach ([false, true] as $missing) {
            [$patient] = $this->makePatientWithUser('unfinished-timing-'.(int) $missing.'@example.com');
            $appointment = $this->makeAppointment($dentist, $patient, ['appointment_time' => $missing ? '14:30:00' : '14:00:00']);
            $procedure = AppointmentProcedure::create([
                'appointment_id' => $appointment->id,
                'procedure_started_at' => Carbon::parse('2026-09-04 14:00:00'),
                'procedure_completed_at' => null,
                'procedure_duration_seconds' => 0,
            ]);
            if ($missing) {
                $procedure->timing()->delete();
            }
            $appointments[] = $appointment;
        }
        $this->actingAs($dentist)->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.clinic-status.update'), ['status' => 'out'])->assertOk();
        foreach ($appointments as $appointment) {
            $this->assertSame('cancelled', $appointment->fresh()->status);
        }
    }

    private function makeDentist(string $email): User
    {
        $dentistRole = Role::firstOrCreate(
            ['slug' => 'dentist'],
            ['name' => 'Dentist']
        );

        $permissionIds = collect([
            ['slug' => 'update_clinic_schedule', 'name' => 'Update Clinic Schedule'],
            ['slug' => 'manage_patient_profiles', 'name' => 'Manage Patient Profiles'],
        ])->map(function (array $permission) {
            return Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'module' => 'Dentist',
                ]
            )->id;
        })->all();

        $dentistRole->permissions()->syncWithoutDetaching($permissionIds);

        return User::create([
            'name' => 'Duty Dentist',
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => $dentistRole->id,
            'status' => 'active',
        ]);
    }

    private function makePatientWithUser(string $email): array
    {
        $patientRole = Role::firstOrCreate(
            ['slug' => 'patient'],
            ['name' => 'Patient']
        );

        $patientUser = User::create([
            'name' => 'Patient User',
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => $patientRole->id,
            'status' => 'active',
        ]);

        $patient = Patient::create([
            'user_id' => $patientUser->id,
            'name' => 'Patient User',
            'email' => $email,
            'phone' => '09171234567',
            'birthdate' => '2004-01-01',
            'gender' => 'Female',
            'password' => bcrypt('password'),
        ]);

        return [$patient, $patientUser];
    }

    private function makeAppointment(User $dentist, Patient $patient, array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'service_type' => 'Oral Check-up',
            'appointment_date' => '2026-09-04',
            'appointment_time' => '16:00:00',
            'status' => 'upcoming',
            'cancellation_reason' => null,
        ], $overrides));
    }
}
