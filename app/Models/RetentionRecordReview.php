<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionRecordReview extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'retention_starts_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime',
        'archived_at' => 'immutable_datetime', 'legal_hold' => 'boolean',
        'preservation_required' => 'boolean', 'dependencies_cleared' => 'boolean',
        'policy_version' => 'integer',
    ];
}
