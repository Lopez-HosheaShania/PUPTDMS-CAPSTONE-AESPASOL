<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class OdontogramRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'patient_odontogram_id',
        'appointment_id',
        'version_number',
        'odontogram_data',
        'changes',
        'reason_type_id',
        'reason_detail',
        'reason_notes',
        'changed_by',
        'changed_by_role',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'odontogram_data' => 'array',
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(
                'Odontogram revisions are immutable and cannot be updated.'
            );
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Odontogram revisions are immutable and cannot be deleted.'
            );
        });
    }

    public function reasonType(): BelongsTo
    {
        return $this->belongsTo(
            OdontogramRevisionReasonType::class,
            'reason_type_id'
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function patientOdontogram(): BelongsTo
    {
        return $this->belongsTo(PatientOdontogram::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
