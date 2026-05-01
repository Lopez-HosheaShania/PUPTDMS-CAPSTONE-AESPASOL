<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = [
        'name', 
        'description',
        'is_active_for_booking',
        'is_default',
    ];
}