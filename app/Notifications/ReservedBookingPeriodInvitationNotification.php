<?php

namespace App\Notifications;

use App\Models\ReservedBookingPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ReservedBookingPeriodInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ReservedBookingPeriod $period)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reserved Dental Appointment',
            'message' => sprintf(
                '%s is scheduled for your patient group on %s, from %s to %s. Complete your appointment booking before the reserved period reaches capacity.',
                $this->period->title,
                Carbon::parse($this->period->reserved_date)->format('M d, Y'),
                Carbon::parse($this->period->start_time)->format('g:i A'),
                Carbon::parse($this->period->end_time)->format('g:i A')
            ),
            'url' => route('book.appointment.reserved', $this->period),
            'icon' => 'fa-calendar-check',
            'reserved_booking_period_id' => $this->period->id,
            'event' => 'reserved-booking-period.invitation',
            'dedupe_key' => 'reserved-booking-period.invitation.'.$this->period->id,
            'recipient_role' => 'patient',
        ];
    }

}
