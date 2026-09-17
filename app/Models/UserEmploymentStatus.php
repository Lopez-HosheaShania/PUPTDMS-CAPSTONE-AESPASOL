<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEmploymentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employment_status',
        'account_status',
        'last_working_date',
        'access_ends_at',
    ];

    protected $casts = [
        'last_working_date' => 'date',
        'access_ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
