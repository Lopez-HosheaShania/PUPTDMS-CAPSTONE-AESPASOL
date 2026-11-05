<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicScheduleConfiguration extends Model
{
    protected $fillable = [
        'slot_duration_minutes',
    ];

    protected $casts = [
        'slot_duration_minutes' => 'integer',
    ];

    public function clinicSchedule()
    {
        return $this->belongsTo(ClinicSchedule::class);
    }
}
