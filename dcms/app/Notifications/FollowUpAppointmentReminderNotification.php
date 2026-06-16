<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FollowUpAppointmentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment,
        private readonly string $reminderType = 'two_days'
    ) {}

    public function via(object $notifiable): array
    {
        return SystemSetting::notificationVia(
            $this->reminderType === 'today'
                ? 'notif_follow_up_today_reminder'
                : 'notif_follow_up_reminder'
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->reminderTitle(),
            'message' => $this->reminderMessage(),
            'url' => route('patient.appointment.index'),
            'icon' => 'fa-bell',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'event' => $this->reminderType === 'today'
                ? 'appointment.follow_up.today_reminder'
                : 'appointment.follow_up.reminder',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->reminderTitle(),
            'message' => $this->reminderMessage(),
            'url' => route('patient.appointment.index'),
            'icon' => 'fa-bell',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'event' => $this->reminderType === 'today'
                ? 'appointment.follow_up.today_reminder'
                : 'appointment.follow_up.reminder',
            'created_at_label' => 'Just now',
            'state' => 'unread',
        ]);
    }

    private function reminderTitle(): string
    {
        return match ($this->reminderType) {
            'one_day' => 'Follow-up Appointment Tomorrow',
            'two_days' => 'Follow-up Appointment Reminder',
            default => 'Follow-up Appointment Reminder',
        };
    }

    private function reminderMessage(): string
    {
        if ($this->reminderType === 'one_day') {
            return sprintf(
                'Reminder: You have a follow-up appointment tomorrow, %s at %s. Reason: %s',
                $this->formatDate($this->appointment->appointment_date),
                $this->formatTime($this->appointment->appointment_time),
                $this->appointment->follow_up_reason ?: 'No reason provided'
            );
        }

        if ($this->reminderType === 'two_days') {
            return sprintf(
                'Reminder: You have a follow-up appointment in 2 days, on %s at %s. Reason: %s',
                $this->formatDate($this->appointment->appointment_date),
                $this->formatTime($this->appointment->appointment_time),
                $this->appointment->follow_up_reason ?: 'No reason provided'
            );
        }

        return sprintf(
            'Reminder: You have a follow-up appointment on %s at %s. Reason: %s',
            $this->formatDate($this->appointment->appointment_date),
            $this->formatTime($this->appointment->appointment_time),
            $this->appointment->follow_up_reason ?: 'No reason provided'
        );
    }

    private function formatDate(mixed $date): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        try {
            return Carbon::parse($date)->format('M d, Y');
        } catch (\Throwable) {
            return (string) $date;
        }
    }

    private function formatTime(mixed $time): string
    {
        if (empty($time)) {
            return 'N/A';
        }

        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable) {
            return (string) $time;
        }
    }
}
