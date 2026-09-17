<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentProcedure extends Model
{
    use HasFactory;

    public function scopeForPatient($query, $patientId)
    {
        return $query->whereHas('appointment', fn ($appointment) => $appointment->where('appointments.patient_id', $patientId));
    }

    protected $fillable = [
        'appointment_id',
        'odontogram_data',
        'oral_examination',
        'diagnosis',
        'prescriptions',
        'completion_action',
        'procedure_started_at',
        'procedure_completed_at',
        'procedure_duration_seconds',
    ];

    protected $casts = [
        'procedure_started_at' => 'datetime',
        'procedure_completed_at' => 'datetime',
        'procedure_duration_seconds' => 'integer',
    ];

    protected $with = ['odontogram', 'timing'];

    protected $hidden = ['odontogram', 'timing'];

    protected $appends = ['odontogram_data', 'procedure_started_at', 'procedure_completed_at', 'procedure_duration_seconds'];

    private array $pendingTiming = [];

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        foreach (['procedure_started_at', 'procedure_completed_at'] as $key) {
            if (isset($attributes[$key]) && $attributes[$key] instanceof \DateTimeInterface) {
                $attributes[$key] = $this->serializeDate($attributes[$key]);
            }
        }

        return $attributes;
    }

    public function timing()
    {
        return $this->hasOne(AppointmentProcedureTiming::class);
    }

    private function timingValue(string $column)
    {
        return array_key_exists($column, $this->pendingTiming)
            ? (new AppointmentProcedureTiming($this->pendingTiming))->getAttribute($column)
            : $this->timing?->getAttribute($column);
    }

    private function setTimingValue(string $column, $value): void
    {
        $timing = new AppointmentProcedureTiming([$column => $value]);
        $this->pendingTiming[$column] = $timing->getAttributes()[$column];
    }

    public function getProcedureStartedAtAttribute()
    {
        return $this->timingValue('started_at');
    }

    public function setProcedureStartedAtAttribute($value): void
    {
        $this->setTimingValue('started_at', $value);
    }

    public function getProcedureCompletedAtAttribute()
    {
        return $this->timingValue('completed_at');
    }

    public function setProcedureCompletedAtAttribute($value): void
    {
        $this->setTimingValue('completed_at', $value);
    }

    public function getProcedureDurationSecondsAttribute()
    {
        return $this->timingValue('duration_seconds');
    }

    public function setProcedureDurationSecondsAttribute($value): void
    {
        $this->setTimingValue('duration_seconds', $value);
    }

    private bool $hasPendingOdontogram = false;

    private ?array $pendingOdontogram = null;

    public function odontogram()
    {
        return $this->hasOne(AppointmentOdontogram::class);
    }

    // Keep the existing attribute contract for forms, records, and reports.
    public function getOdontogramDataAttribute(): ?array
    {
        return $this->hasPendingOdontogram
            ? $this->pendingOdontogram
            : $this->odontogram?->odontogram_data;
    }

    public function setOdontogramDataAttribute(?array $value): void
    {
        $this->pendingOdontogram = $value;
        $this->hasPendingOdontogram = true;
    }

    public function save(array $options = [])
    {
        if (! $this->hasPendingOdontogram && $this->pendingTiming === [] && $this->exists) {
            return parent::save($options);
        }

        // Clinical data, odontogram, and timing must succeed or roll back together.
        return $this->getConnection()->transaction(function () use ($options) {
            if (! parent::save($options)) {
                return false;
            }

            if ($this->hasPendingOdontogram) {
                $snapshot = $this->odontogram()->updateOrCreate(
                    [],
                    ['odontogram_data' => $this->pendingOdontogram]
                );
                $this->setRelation('odontogram', $snapshot);
            }

            if ($this->pendingTiming !== [] || $this->wasRecentlyCreated) {
                $timing = $this->timing()->updateOrCreate([], $this->pendingTiming);
                $this->setRelation('timing', $timing);
            }

            $this->pendingTiming = [];
            $this->hasPendingOdontogram = false;
            $this->pendingOdontogram = null;

            return true;
        });
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
