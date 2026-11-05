<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservedBookingPeriodTarget extends Model
{
    protected $table = 'reserved_booking_period_targets';

    public $timestamps = false;

    protected $fillable = ['target_patient_type', 'program_code', 'year_level', 'section'];

    protected $casts = ['year_level' => 'integer'];

    public function reservedBookingPeriod()
    {
        return $this->belongsTo(ReservedBookingPeriod::class);
    }
}
