<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogDescription extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];
}
