<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentClosure extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'appointment_id';
    protected $guarded = [];
    protected $casts = ['closed_at' => 'datetime'];
}
