<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDeactivationEmployment extends Model
{
    protected $table = 'user_deactivation_employments';

    public $timestamps = false;

    protected $fillable = ['employment_status', 'last_working_date'];

    protected $casts = ['last_working_date' => 'date'];

    public function userDeactivation()
    {
        return $this->belongsTo(UserDeactivation::class);
    }
}
