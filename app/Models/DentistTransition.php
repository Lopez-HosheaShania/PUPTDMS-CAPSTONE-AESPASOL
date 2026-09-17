<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DentistTransition extends Model
{
    use HasFactory;

    use \App\Models\Concerns\StoresOptionalDetails;

    protected function detailFields(): array
    {
        return ['details' => ['handover_notes', 'remarks', 'reviewed_by', 'approved_by', 'completed_at'], 'cancellation' => ['cancelled_at', 'cancellation_reason']];
    }

    public function details()
    {
        return $this->hasOne(DentistTransitionDetail::class, 'dentist_transition_id');
    }

    public function cancellation()
    {
        return $this->hasOne(DentistTransitionCancellation::class, 'dentist_transition_id');
    }


    public const STATUSES = [
        'draft',
        'pending_review',
        'handover_in_progress',
        'scheduled',
        'completed',
        'cancelled',
    ];

    public const TYPES = [
        'retirement',
        'resignation',
        'transfer',
        'long_term_leave',
        'termination',
        'other',
    ];

    protected $fillable = [
        'dentist_id',
        'transition_type',
        'default_successor_dentist_id',
        'last_working_date',
        'access_ends_at',
        'status',
        'handover_notes',
        'remarks',
        'initiated_by',
        'reviewed_by',
        'approved_by',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'last_working_date' => 'date',
        'access_ends_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function defaultSuccessor()
    {
        return $this->belongsTo(User::class, 'default_successor_dentist_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(DentistTransitionItem::class);
    }

    public function checklistItems()
    {
        return $this->hasMany(DentistTransitionChecklistItem::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        $total = $this->checklistItems->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $this->checklistItems->where('is_completed', true)->count();

        return (int) round(($completed / $total) * 100);
    }
}
