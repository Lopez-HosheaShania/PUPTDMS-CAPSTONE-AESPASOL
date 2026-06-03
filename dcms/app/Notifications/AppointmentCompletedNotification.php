<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\SystemSetting;

class AppointmentCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment
    ) {}

    public function via(object $notifiable): array
    {
        return SystemSetting::notificationVia('notif_appointment_completed');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Appointment Completed',
            'message' => sprintf(
                'Your appointment on %s at %s has been marked as completed.',
                $this->formatDate($this->appointment->appointment_date),
                $this->formatTime($this->appointment->appointment_time)
            ),
            'url' => route('patient.record'),
            'icon' => 'fa-circle-check',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'event' => 'appointment.completed',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Appointment Completed',
            'message' => sprintf(
                'Your appointment on %s at %s has been marked as completed.',
                $this->formatDate($this->appointment->appointment_date),
                $this->formatTime($this->appointment->appointment_time)
            ),
            'url' => route('patient.record'),
            'icon' => 'fa-circle-check',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'event' => 'appointment.completed',
            'created_at_label' => 'Just now',
            'state' => 'unread',
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
}
