<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentProcedureTiming extends Model
{
    protected $fillable = ['appointment_procedure_id', 'started_at', 'completed_at', 'duration_seconds'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function procedure()
    {
        return $this->belongsTo(AppointmentProcedure::class, 'appointment_procedure_id');
    }
}
