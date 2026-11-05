<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservedBookingPeriodConfiguration extends Model
{
    protected $table = 'reserved_booking_period_configurations';

    public $timestamps = false;

    protected $fillable = ['booking_mode', 'timeslot_duration_minutes', 'max_capacity', 'restrict_services'];

    protected $casts = ['timeslot_duration_minutes' => 'integer', 'max_capacity' => 'integer', 'restrict_services' => 'boolean'];

    public function reservedBookingPeriod()
    {
        return $this->belongsTo(ReservedBookingPeriod::class);
    }
}
