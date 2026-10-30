<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    // Preserve historical approved aliases; the current UI only approves/rejects.
    public const APPROVED_STATUSES = ['approved', 'ready', 'ready-for-pickup', 'ready-for-release', 'released'];
    public const CLOSED_STATUSES = [...self::APPROVED_STATUSES, 'rejected'];

    public static function normalizedStatus(?string $status): string
    {
        $status = strtolower(str_replace('_', '-', trim($status ?? 'pending')));

        return in_array($status, self::APPROVED_STATUSES, true) ? 'approved'
            : ($status === 'rejected' ? 'rejected' : 'pending');
    }

    public function retentionDecisionDate(): ?\Illuminate\Support\Carbon
    {
        return match (self::normalizedStatus($this->status)) {
            'approved' => $this->approved_at,
            'rejected' => $this->closed_at,
            default => null,
        };
    }

    use HasFactory;
    use \App\Models\Concerns\ScopesClinicalPatients;
    use \App\Models\Concerns\StoresOptionalDetails { save as private saveWithDetails; }

    protected $hidden = ['document_request_id'];

    protected function detailFields(): array
    {
        return ['review' => ['approved_at', 'approved_by', 'rejection_reason'],
            'requestState' => ['request_date', 'request_time', 'status', 'closed_at']];
    }

    public function requestState()
    {
        return $this->hasOne(DocumentRequestState::class);
    }

    public function scopeWithStateColumns($query)
    {
        return $query->leftJoin('document_request_states', 'document_request_states.document_request_id', '=', 'document_requests.id');
    }

    public function save(array $options = [])
    {
        return $this->getConnection()->transaction(function () use ($options) {
            if (! $this->exists) {
                app(\App\Services\ClinicDentistService::class)->recordCoverageCare((int) $this->patient_id, $this->assigned_dentist_id ? (int) $this->assigned_dentist_id : null);
            }
            if (! $this->exists && $this->status === null) {
                $this->status = 'pending';
            }

            $previousStatus = $this->exists ? $this->requestState()->lockForUpdate()->value('status') : null;
            $nextStatus = $this->pendingOptionalDetails['requestState']['status'] ?? $previousStatus;
            $previousDecision = $previousStatus === null ? null : self::normalizedStatus($previousStatus);
            $nextDecision = self::normalizedStatus($nextStatus);
            if ($previousDecision !== $nextDecision) {
                $this->closed_at = match ($nextDecision) {
                    'approved' => $this->approved_at,
                    // Imported historical rows must provide their actual date.
                    'rejected' => $this->exists ? now() : $this->closed_at,
                    default => null,
                };
            }

            return $this->saveWithDetails($options);
        });
    }

    public function review()
    {
        return $this->hasOne(DocumentRequestReview::class);
    }

    public function scopeWithReviewColumns($query)
    {
        return $query->leftJoin('document_request_reviews', 'document_request_reviews.document_request_id', '=', 'document_requests.id')
            ->select('document_requests.*');
    }


    protected $fillable = [
        'patient_id',
        'assigned_dentist_id',
        'reference_number',
        'document_type',
        'purpose',
        'request_date',
        'request_time',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'closed_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignedDentist()
    {
        return $this->belongsTo(User::class, 'assigned_dentist_id');
    }
}
