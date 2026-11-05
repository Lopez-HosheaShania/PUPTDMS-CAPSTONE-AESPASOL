<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentistTransitionItemResolution extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['transferred_by' => 'integer', 'transferred_at' => 'datetime'];
}
