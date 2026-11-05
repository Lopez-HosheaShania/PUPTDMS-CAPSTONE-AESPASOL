<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\SystemSetting;

class AppointmentRescheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment,
        private readonly string $rescheduledBy = 'Dentist'
    ) {}

    public function via(object $notifiable): array
    {
        return SystemSetting::notificationVia('notif_rescheduled');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Appointment Rescheduled',
            'message' => sprintf(
                'Your appointment was rescheduled by %s to %s at %s.',
                $this->rescheduledBy,
                $this->formatDate($this->appointment->appointment_date),
                $this->formatTime($this->appointment->appointment_time)
            ),
            'url' => $this->resolveTargetUrl($notifiable),
            'icon' => 'fa-calendar-days',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'event' => 'appointment.rescheduled',
            'recipient_role' => optional($notifiable->role)->slug,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Appointment Rescheduled',
            'message' => sprintf(
                'Your appointment was rescheduled by %s to %s at %s.',
                $this->rescheduledBy,
                $this->formatDate($this->appointment->appointment_date),
                $this->formatTime($this->appointment->appointment_time)
            ),
            'url' => $this->resolveTargetUrl($notifiable),
            'icon' => 'fa-calendar-days',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'event' => 'appointment.rescheduled',
            'created_at_label' => 'Just now',
            'state' => 'unread',
            'recipient_role' => optional($notifiable->role)->slug,
        ]);
    }

    private function formatDate(mixed $date): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('M d, Y');
        } catch (\Throwable) {
            return (string) $date;
        }
    }

    private function formatTime(?string $time): string
    {
        if (empty($time)) {
            return 'N/A';
        }

        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $time)->format('g:i A');
        } catch (\Throwable) {
            return $time;
        }
    }

    private function resolveTargetUrl(object $notifiable): string
    {
        $role = optional($notifiable->role)->slug;

        return match ($role) {
            'admin', 'super_admin' => route('admin.admin.appointments'),
            'dentist' => route('dentist.dentist.appointments'),
            'patient' => route('patient.appointment.index'),
            default => route('patient.appointment.index'),
        };
    }
}
