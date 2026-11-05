<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentReminder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'follow_up_reminder_sent_at' => 'datetime',
        'follow_up_today_reminder_sent_at' => 'datetime',
        'follow_up_one_day_reminder_sent_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
