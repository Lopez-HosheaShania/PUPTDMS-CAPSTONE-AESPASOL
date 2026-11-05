<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDeactivationAccess extends Model
{
    protected $table = 'user_deactivation_accesses';

    public $timestamps = false;

    protected $fillable = ['account_status', 'access_ends_at'];

    protected $casts = ['access_ends_at' => 'datetime'];

    public function userDeactivation()
    {
        return $this->belongsTo(UserDeactivation::class);
    }
}
