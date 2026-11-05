<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentOdontogramTooth extends Model
{
    public $timestamps = false;

    protected $with = ['markings'];

    protected $casts = ['tooth_number' => 'integer', 'surfaces_null' => 'boolean'];

    public function markings()
    {
        return $this->hasMany(AppointmentOdontogramMarking::class, 'tooth_id')->orderBy('position');
    }
}
