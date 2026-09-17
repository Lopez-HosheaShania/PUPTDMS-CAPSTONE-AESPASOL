<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAuthSecurity extends Model
{
    use HasFactory;

    protected $table = 'user_auth_security';

    protected $fillable = [
        'user_id',
        'last_login_at',
        'failed_login_attempts',
        'last_failed_login_at',
        'locked_until',
        'access_token',
        'refresh_token',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'last_failed_login_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
