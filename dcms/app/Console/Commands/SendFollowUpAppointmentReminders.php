<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\FollowUpAppointmentReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendFollowUpAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-follow-up-reminders';

    protected $description = 'Send reminders to patients 2 days before and 1 day before their follow-up appointments.';

    public function handle(): int
    {
        $twoDaysFromNow = Carbon::today()->addDays(2)->toDateString();
        $oneDayFromNow = Carbon::today()->addDay()->toDateString();

        $twoDaySent = $this->sendTwoDayReminders($twoDaysFromNow);
        $oneDaySent = $this->sendOneDayReminders($oneDayFromNow);

        $this->info("2-day follow-up reminders sent: {$twoDaySent}");
        $this->info("1-day follow-up reminders sent: {$oneDaySent}");

        return self::SUCCESS;
    }

    private function sendTwoDayReminders(string $targetDate): int
    {
        $appointments = Appointment::with('patient.user')
            ->where('is_follow_up', true)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->whereDate('appointment_date', $targetDate)
            ->whereNull('follow_up_reminder_sent_at')
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            $patientUser = $this->resolvePatientUser($appointment);

            if (!$patientUser) {
                $this->warn("No user account found for 2-day reminder appointment ID: {$appointment->id}");
                continue;
            }

            $patientUser->notify(
                new FollowUpAppointmentReminderNotification($appointment, 'two_days')
            );

            $appointment->update([
                'follow_up_reminder_sent_at' => now(),
            ]);

            $sent++;
        }

        return $sent;
    }

    private function sendOneDayReminders(string $targetDate): int
    {
        $appointments = Appointment::with('patient.user')
            ->where('is_follow_up', true)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->whereDate('appointment_date', $targetDate)
            ->whereNull('follow_up_one_day_reminder_sent_at')
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            $patientUser = $this->resolvePatientUser($appointment);

            if (!$patientUser) {
                $this->warn("No user account found for 1-day reminder appointment ID: {$appointment->id}");
                continue;
            }

            $patientUser->notify(
                new FollowUpAppointmentReminderNotification($appointment, 'one_day')
            );

            $appointment->update([
                'follow_up_one_day_reminder_sent_at' => now(),
            ]);

            $sent++;
        }

        return $sent;
    }

    private function resolvePatientUser(Appointment $appointment): ?User
    {
        $patientUser = optional($appointment->patient)->user;

        if ($patientUser) {
            return $patientUser;
        }

        $patientEmail = optional($appointment->patient)->email;

        if (!$patientEmail) {
            return null;
        }

        return User::where('email', $patientEmail)->first();
    }
}