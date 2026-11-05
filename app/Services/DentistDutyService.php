<?php

namespace App\Services;

use App\Mail\AppointmentCancelledMail;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\AppointmentCancelledNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DentistDutyService
{
    public const STATUS_IN = 'in';

    public const STATUS_OUT = 'out';

    public const AUTO_CANCELLATION_REASON = 'Dentist clinic duty ended';

    public const AUTO_CANCELLATION_MESSAGE = 'Your dentist\'s clinic duty has ended for today.';

    public const AUTO_CANCELLED_BY = 'the system';

    public function currentStatusFor(User $dentist): string
    {
        $status = strtolower(trim((string) SystemSetting::getSetting(
            $this->dentistDutyStatusKey($dentist),
            SystemSetting::getSetting('clinic_status', self::STATUS_IN)
        )));

        return $status === self::STATUS_OUT ? self::STATUS_OUT : self::STATUS_IN;
    }

    public function clockIn(User $dentist, CarbonInterface|string|null $at = null): void
    {
        $at = $this->normalizeTimestamp($at);

        SystemSetting::setSetting($this->dentistDutyStatusKey($dentist), self::STATUS_IN, 'clinic');
        SystemSetting::setSetting($this->dentistDutyInAtKey($dentist), $at->toDateTimeString(), 'clinic');
    }

    public function clockOut(User $dentist, CarbonInterface|string|null $at = null, bool $automatic = false): array
    {
        $at = $this->normalizeTimestamp($at);
        $today = $at->toDateString();
        $wasAlreadyOut = $this->currentStatusFor($dentist) === self::STATUS_OUT;

        $result = DB::transaction(function () use ($dentist, $at, $today, $automatic, $wasAlreadyOut) {
            SystemSetting::setSetting($this->dentistDutyStatusKey($dentist), self::STATUS_OUT, 'clinic');
            SystemSetting::setSetting($this->dentistDutyOutAtKey($dentist), $at->toDateTimeString(), 'clinic');

            $appointments = Appointment::query()
                ->with(['patient.user'])
                ->forDentist($dentist->id)
                ->scheduledOnDate($today)
                ->activeForDutyEnd()
                ->where(function ($query) {
                    $query->whereDoesntHave('procedure')
                        ->orWhereHas('procedure', function ($procedureQuery) {
                            $procedureQuery->whereDoesntHave('timing', function ($timingQuery) {
                                $timingQuery->whereNotNull('completed_at');
                            });
                        });
                })
                ->lockForUpdate()
                ->get();

            $cancelledAppointments = 0;
            $notificationsCreated = 0;
            $cancelledAppointmentIds = [];

            foreach ($appointments as $appointment) {
                if (! $this->appointmentIsStillCancellable($appointment, $today)) {
                    continue;
                }

                $appointment->forceFill([
                    'status' => 'cancelled',
                    'cancellation_reason' => self::AUTO_CANCELLATION_REASON,
                    'reserved_booking_period_slot_id' => null,
                ])->save();

                $cancelledAppointments++;
                $cancelledAppointmentIds[] = $appointment->id;
                $notificationsCreated += $this->notifyPatientAboutAutoCancellation($appointment);
            }

            if (! $wasAlreadyOut || $cancelledAppointments > 0) {
                $this->writeAuditLog($dentist, $cancelledAppointments, $automatic, $at);
            }

            return [
                'dentist_clocked_out' => 1,
                'appointments_cancelled' => $cancelledAppointments,
                'cancelled_appointment_ids' => $cancelledAppointmentIds,
                'notifications_created' => $notificationsCreated,
            ];
        });

        if (! $wasAlreadyOut || $result['appointments_cancelled'] > 0) {
            Log::info('Dentist duty ended.', [
                'dentist_id' => $dentist->id,
                'automatic' => $automatic,
                'effective_at' => $at->toDateTimeString(),
                'appointments_cancelled' => $result['appointments_cancelled'],
                'notifications_created' => $result['notifications_created'],
            ]);
        }

        return $result;
    }

    public function syncOutDentistAppointments(User $dentist, CarbonInterface|string|null $at = null): array
    {
        if ($this->currentStatusFor($dentist) !== self::STATUS_OUT) {
            return [
                'dentist_clocked_out' => 0,
                'appointments_cancelled' => 0,
                'cancelled_appointment_ids' => [],
                'notifications_created' => 0,
            ];
        }

        $at = $this->normalizeTimestamp($at ?? $this->resolveOutTimestamp($dentist) ?? $this->now());

        return $this->clockOut($dentist, $at, false);
    }

    public function autoClockOutActiveDentists(CarbonInterface|string|null $at = null): array
    {
        $at = $this->normalizeTimestamp($at);

        if ($at->copy()->setTime(20, 0)->greaterThan($at)) {
            return [
                'ran' => false,
                'dentists_clocked_out' => 0,
                'appointments_cancelled' => 0,
                'notifications_created' => 0,
            ];
        }

        $summary = [
            'ran' => true,
            'dentists_clocked_out' => 0,
            'appointments_cancelled' => 0,
            'notifications_created' => 0,
        ];

        $dentists = User::query()
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('slug', 'dentist'))
            ->get();

        foreach ($dentists as $dentist) {
            if ($this->currentStatusFor($dentist) === self::STATUS_OUT) {
                continue;
            }

            $result = $this->clockOut($dentist, $at, true);

            $summary['dentists_clocked_out'] += $result['dentist_clocked_out'];
            $summary['appointments_cancelled'] += $result['appointments_cancelled'];
            $summary['notifications_created'] += $result['notifications_created'];
        }

        Log::info('Automatic dentist duty end processed.', [
            'effective_at' => $at->toDateTimeString(),
            'dentists_clocked_out' => $summary['dentists_clocked_out'],
            'appointments_cancelled' => $summary['appointments_cancelled'],
            'notifications_created' => $summary['notifications_created'],
        ]);

        return $summary;
    }

    private function appointmentIsStillCancellable(Appointment $appointment, string $today): bool
    {
        $appointment->refresh();

        if ((int) $appointment->dentist_id <= 0 || (string) $appointment->appointment_date !== $today) {
            return false;
        }

        if (! in_array($appointment->status, Appointment::ACTIVE_DUTY_END_STATUSES, true)) {
            return false;
        }

        if ($appointment->procedure && $appointment->procedure->procedure_completed_at) {
            return false;
        }

        return true;
    }

    private function notifyPatientAboutAutoCancellation(Appointment $appointment): int
    {
        $patientUser = $this->resolvePatientUser($appointment);

        if ($patientUser) {
            try {
                $patientUser->notify(new AppointmentCancelledNotification(
                    $appointment,
                    self::AUTO_CANCELLED_BY,
                    self::AUTO_CANCELLATION_MESSAGE
                ));
            } catch (\Throwable $error) {
                Log::error('Automatic appointment cancellation notification failed.', [
                    'appointment_id' => $appointment->id,
                    'dentist_id' => $appointment->dentist_id,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        $patientEmail = (string) optional($appointment->patient)->email;

        if ($patientEmail !== '') {
            try {
                Mail::to($patientEmail)->send(new AppointmentCancelledMail(
                    $appointment,
                    self::AUTO_CANCELLED_BY,
                    self::AUTO_CANCELLATION_MESSAGE
                ));
            } catch (\Throwable $error) {
                Log::error('Automatic appointment cancellation email failed.', [
                    'appointment_id' => $appointment->id,
                    'dentist_id' => $appointment->dentist_id,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        return $patientUser ? 1 : 0;
    }

    private function resolvePatientUser(Appointment $appointment): ?User
    {
        $patientUser = optional($appointment->patient)->user;

        if ($patientUser) {
            return $patientUser;
        }

        $patientEmail = (string) optional($appointment->patient)->email;

        if ($patientEmail === '') {
            return null;
        }

        return User::query()->where('email', $patientEmail)->first();
    }

    private function writeAuditLog(User $dentist, int $cancelledAppointments, bool $automatic, Carbon $at): void
    {
        $existingDescription = sprintf(
            'Dentist duty ended for dentist #%d at %s. Cancelled %d same-day appointment(s).',
            $dentist->id,
            $at->toDateTimeString(),
            $cancelledAppointments
        );

        if (AuditLog::query()->where('module', 'dentist_duty')->withDescription($existingDescription)->exists()) {
            return;
        }

        AuditLog::query()->create([
            'actor_id' => $automatic ? null : $dentist->id,
            'actor_name' => $automatic ? 'System' : $dentist->name,
            'actor_role' => $automatic ? 'system' : 'dentist',
            'actor_identifier' => $automatic ? 'scheduler' : (string) $dentist->id,
            'action' => 'update',
            'module' => 'dentist_duty',
            'description' => $existingDescription,
        ]);
    }

    private function now(): Carbon
    {
        return now(config('app.timezone', 'Asia/Manila'));
    }

    private function normalizeTimestamp(CarbonInterface|string|null $at): Carbon
    {
        if ($at instanceof Carbon) {
            return $at->copy()->setTimezone(config('app.timezone', 'Asia/Manila'));
        }

        if ($at instanceof CarbonInterface) {
            return Carbon::instance($at)->setTimezone(config('app.timezone', 'Asia/Manila'));
        }

        if (is_string($at) && trim($at) !== '') {
            return Carbon::parse($at, config('app.timezone', 'Asia/Manila'));
        }

        return $this->now();
    }

    private function resolveOutTimestamp(User $dentist): ?Carbon
    {
        $value = SystemSetting::getSetting($this->dentistDutyOutAtKey($dentist));

        if (! filled($value)) {
            return null;
        }

        return Carbon::parse($value, config('app.timezone', 'Asia/Manila'));
    }

    private function dentistDutyStatusKey(User $dentist): string
    {
        return 'dentist_duty_status_' . $dentist->id;
    }

    private function dentistDutyOutAtKey(User $dentist): string
    {
        return 'dentist_duty_out_at_' . $dentist->id;
    }

    private function dentistDutyInAtKey(User $dentist): string
    {
        return 'dentist_duty_in_at_' . $dentist->id;
    }
}
