<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentistTransitionDetail extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['reviewed_by' => 'integer', 'approved_by' => 'integer', 'completed_at' => 'datetime'];
}
