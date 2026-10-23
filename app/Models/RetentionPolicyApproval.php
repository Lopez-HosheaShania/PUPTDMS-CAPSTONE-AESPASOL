<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionPolicyApproval extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'approved_by' => 'integer', 'approved_at' => 'datetime',
        'activated_by' => 'integer', 'activated_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(RetentionPolicy::class, 'retention_policy_id');
    }
}
