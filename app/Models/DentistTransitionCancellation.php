<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentistTransitionCancellation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['cancelled_at' => 'datetime'];
}
