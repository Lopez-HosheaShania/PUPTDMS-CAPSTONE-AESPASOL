<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiServiceLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'feature',
        'provider',
        'status',
        'mode',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
