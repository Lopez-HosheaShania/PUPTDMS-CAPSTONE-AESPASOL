<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilippineHolidaySnapshot extends Model
{
    protected $fillable = [
        'year',
        'holidays',
        'source',
        'fetched_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'holidays' => 'array',
        'fetched_at' => 'datetime',
    ];
}
