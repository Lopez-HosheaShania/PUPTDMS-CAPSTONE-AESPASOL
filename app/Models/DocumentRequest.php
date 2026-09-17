<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    use HasFactory;
    use \App\Models\Concerns\StoresOptionalDetails { save as private saveWithDetails; }

    protected $hidden = ['document_request_id'];

    protected function detailFields(): array
    {
        return ['review' => ['approved_at', 'approved_by', 'rejection_reason'],
            'requestState' => ['request_date', 'request_time', 'status']];
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
        if (! $this->exists && $this->status === null) {
            $this->status = 'pending';
        }

        return $this->saveWithDetails($options);
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
