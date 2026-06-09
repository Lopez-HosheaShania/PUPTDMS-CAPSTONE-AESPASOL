<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DentistEmergencyOutNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment
    ) {}

    public function via(object $notifiable): array
    {
        return SystemSetting::notificationVia('notif_dentist_emergency_out');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'dentist_emergency_out',
            'title' => 'Clinic Temporarily Closed',
            'message' => 'The dentist is currently OUT due to an emergency. Your appointment today may be delayed or rescheduled. Please wait for further updates.',
            'url' => route('patient.appointment.index'),
            'icon' => 'fa-triangle-exclamation',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'date' => optional($this->appointment->appointment_date)->format('M d, Y') ?? $this->appointment->appointment_date,
            'time' => $this->appointment->appointment_time,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}