<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservedBookingPeriodSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'reserved_booking_period_id',
        'slot_time',
        'max_capacity',
    ];

    protected $casts = [
        'max_capacity' => 'integer',
    ];

    public function reservedBookingPeriod()
    {
        return $this->belongsTo(ReservedBookingPeriod::class);
    }

    public function appointment()
    {
        return $this->hasOneThrough(Appointment::class, AppointmentReservedBooking::class,
            'reserved_booking_period_slot_id', 'id', 'id', 'appointment_id');
    }
}
