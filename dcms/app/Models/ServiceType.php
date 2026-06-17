<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active_for_booking',
        'is_default',
    ];

    protected $casts = [
        'is_active_for_booking' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function scopeActiveForBooking(Builder $query): Builder
    {
        return $query->where('is_active_for_booking', true);
    }
}