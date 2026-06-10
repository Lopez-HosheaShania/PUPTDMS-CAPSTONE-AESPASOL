<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\SystemSetting;

class AppointmentBookedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment,
        private readonly Patient $patient
    ) {}

    public function via(object $notifiable): array
    {
        return SystemSetting::notificationVia('notif_new_appointment');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Appointment Booked',
            'message' => sprintf(
                '%s booked an appointment on %s at %s.',
                $this->patient->name ?? 'A patient',
                optional($this->appointment->appointment_date)->format('M d, Y') ?? (string) $this->appointment->appointment_date,
                $this->formatTime($this->appointment->appointment_time)
            ),
            'url' => route('dentist.dentist.appointments'),
            'icon' => 'fa-calendar-check',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => $this->formatDateForPayload(),
            'appointment_month' => $this->formatMonthForPayload(),
            'event' => 'appointment.booked',
            'dedupe_key' => 'appointment.booked.' . $this->appointment->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'New Appointment Booked',
            'message' => sprintf(
                '%s booked an appointment on %s at %s.',
                $this->patient->name ?? 'A patient',
                optional($this->appointment->appointment_date)->format('M d, Y') ?? (string) $this->appointment->appointment_date,
                $this->formatTime($this->appointment->appointment_time)
            ),
            'url' => route('dentist.dentist.appointments'),
            'icon' => 'fa-calendar-check',
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => $this->formatDateForPayload(),
            'appointment_month' => $this->formatMonthForPayload(),
            'event' => 'appointment.booked',
            'created_at_label' => 'Just now',
            'state' => 'unread',
            'dedupe_key' => 'appointment.booked.' . $this->appointment->id,
        ]);
    }

    private function formatDateForPayload(): ?string
    {
        if (empty($this->appointment->appointment_date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($this->appointment->appointment_date)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatMonthForPayload(): ?string
    {
        if (empty($this->appointment->appointment_date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($this->appointment->appointment_date)->format('Y-m');
        } catch (\Throwable) {
            return null;
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
