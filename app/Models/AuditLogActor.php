<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogActor extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];
}
