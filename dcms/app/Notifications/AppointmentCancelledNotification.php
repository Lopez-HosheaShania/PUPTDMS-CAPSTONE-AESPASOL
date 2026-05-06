<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class AppointmentCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment,
        private readonly string $cancelledBy = 'the dentist',
        private readonly ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->notificationData();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage(array_merge(
            $this->notificationData(),
            [
                'created_at_label' => 'Just now',
                'state' => 'unread',
            ]
        ));
    }

    private function notificationData(): array
    {
        $message = sprintf(
            'Your appointment on %s at %s was cancelled by %s.',
            $this->formatDate($this->appointment->appointment_date),
            $this->formatTime($this->appointment->appointment_time),
            $this->cancelledBy
        );

        if (!empty($this->reason)) {
            $message .= ' Reason: ' . $this->reason;
        }

        return [
            'title' => 'Appointment Cancelled',
            'message' => $message,
            'url' => route('patient.appointment.cancelled.view', $this->appointment->id),
            'icon' => 'fa-calendar-xmark',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'status' => $this->appointment->status,
            'reason' => $this->reason,
            'event' => 'appointment.cancelled',
        ];
    }

    private function formatDate($date): string
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

    private function formatTime($time): string
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
