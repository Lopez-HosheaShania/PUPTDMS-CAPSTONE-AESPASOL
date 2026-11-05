<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentFollowUp extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_follow_up' => 'boolean'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
