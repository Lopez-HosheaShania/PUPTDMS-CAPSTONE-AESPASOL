<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentCaseClassification extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'appointment_id';

    protected $guarded = [];

    protected $casts = ['is_emergency' => 'boolean'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
