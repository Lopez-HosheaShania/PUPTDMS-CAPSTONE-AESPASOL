<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentTransfer extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['transferred_at' => 'datetime'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
