<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservedBookingPeriodServiceOption extends Model
{
    protected $table = 'reserved_booking_period_services';

    public $timestamps = false;

    protected $fillable = ['service_name', 'position'];

    protected $casts = ['position' => 'integer'];

    public function reservedBookingPeriod()
    {
        return $this->belongsTo(ReservedBookingPeriod::class);
    }
}
