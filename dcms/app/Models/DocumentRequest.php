<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
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
}