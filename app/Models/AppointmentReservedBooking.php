<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentReservedBooking extends Model
{
    protected $guarded = ['id'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
