<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservedBookingPeriodSchedule extends Model
{
    protected $table = 'reserved_booking_period_schedules';

    public $timestamps = false;

    protected $fillable = ['reserved_date', 'active_reserved_date', 'start_time', 'end_time', 'is_active'];

    protected $casts = ['reserved_date' => 'date:Y-m-d', 'active_reserved_date' => 'date:Y-m-d', 'is_active' => 'boolean'];

    public function reservedBookingPeriod()
    {
        return $this->belongsTo(ReservedBookingPeriod::class);
    }
}
