<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservedBookingPeriod extends Model
{
    use \App\Models\Concerns\StoresOptionalDetails { save as private saveWithDetails; }
    use HasFactory, SoftDeletes;

    protected function detailFields(): array
    {
        return [
            'schedule' => ['reserved_date', 'active_reserved_date', 'start_time', 'end_time', 'is_active'],
            'configuration' => ['booking_mode', 'timeslot_duration_minutes', 'max_capacity', 'restrict_services'],
            'target' => ['target_patient_type', 'program_code', 'year_level', 'section'],
        ];
    }

    public function schedule()
    {
        return $this->hasOne(ReservedBookingPeriodSchedule::class);
    }

    public function configuration()
    {
        return $this->hasOne(ReservedBookingPeriodConfiguration::class);
    }

    public function target()
    {
        return $this->hasOne(ReservedBookingPeriodTarget::class);
    }

    public function scopeWithScheduleColumns($query)
    {
        return $query->join('reserved_booking_period_schedules',
            'reserved_booking_period_schedules.reserved_booking_period_id', '=', 'reserved_booking_periods.id')
            ->select('reserved_booking_periods.*');
    }

    public function getRawOriginal($key = null, $default = null)
    {
        if ($key && ($relation = $this->optionalDetailRelation($key))) {
            return $this->getRelationValue($relation)?->getRawOriginal($key, $default) ?? $default;
        }

        return parent::getRawOriginal($key, $default);
    }

    public const MAX_CAPACITY = 30;

    public const BOOKING_MODES = [
        'timeslot',
        'date_only',
    ];

    public const PATIENT_TYPES = [
        'student',
        'faculty',
        'administrative',
        'guest',
    ];

    protected $fillable = [
        'title',
        'reserved_date',
        'active_reserved_date',
        'start_time',
        'end_time',
        'booking_mode',
        'timeslot_duration_minutes',
        'target_patient_type',
        'allowed_services',
        'program_code',
        'year_level',
        'section',
        'max_capacity',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reserved_date' => 'date:Y-m-d',
        'active_reserved_date' => 'date:Y-m-d',
        'year_level' => 'integer',
        'max_capacity' => 'integer',
        'timeslot_duration_minutes' => 'integer',
        'restrict_services' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'target_label',
        'allowed_services',
    ];

    protected $with = ['serviceOptions'];

    protected $hidden = ['serviceOptions', 'restrict_services'];

    private bool $hasPendingServices = false;

    private ?array $pendingServices = null;

    public function serviceOptions()
    {
        return $this->hasMany(ReservedBookingPeriodServiceOption::class)->orderBy('position');
    }

    public function getAllowedServicesAttribute(): ?array
    {
        if ($this->hasPendingServices) {
            return $this->pendingServices;
        }

        return $this->restrict_services
            ? $this->serviceOptions->pluck('service_name')->all()
            : null;
    }

    public function setAllowedServicesAttribute(?array $services): void
    {
        if ($services !== null && count(array_filter($services, 'is_string')) !== count($services)) {
            throw new \InvalidArgumentException('Allowed services must contain only service names.');
        }

        $this->pendingServices = $services === null ? null : array_values($services);
        $this->hasPendingServices = true;
        $this->restrict_services = $services !== null;
    }

    public function save(array $options = [])
    {
        if (! $this->exists) {
            $this->is_active ??= true;
            $this->restrict_services ??= false;
        }

        return $this->getConnection()->transaction(function () use ($options) {
            // Serialize replacing the list even when saving directly through the model.
            if ($this->exists) {
                $this->newModelQuery()->whereKey($this->getKey())->lockForUpdate()->first();
            }
            $this->active_reserved_date = $this->is_active
                ? optional($this->reserved_date)->format('Y-m-d')
                : null;
            if (! $this->saveWithDetails($options)) {
                return false;
            }

            if (! $this->hasPendingServices) {
                return true;
            }

            $this->serviceOptions()->delete();
            $services = $this->serviceOptions()->createMany(
                collect($this->pendingServices ?? [])->map(fn ($name, $position) => [
                    'service_name' => $name,
                    'position' => $position,
                ])->all()
            );
            $this->setRelation('serviceOptions', $services);
            $this->hasPendingServices = false;
            $this->pendingServices = null;

            return true;
        });
    }

    public function refresh()
    {
        parent::refresh();
        if ($this->exists) {
            $this->pendingOptionalDetails = [];
            $this->hasPendingServices = false;
            $this->pendingServices = null;
        }

        return $this;
    }

    protected static function booted(): void
    {
        static::deleting(function (ReservedBookingPeriod $period) {
            if (! $period->isForceDeleting()) {
                $period->forceFill([
                    'is_active' => false,
                    'active_reserved_date' => null,
                ])->saveQuietly();
            }
        });

        static::restoring(function (ReservedBookingPeriod $period) {
            $period->forceFill([
                'is_active' => true,
                'active_reserved_date' => optional($period->reserved_date)->format('Y-m-d'),
            ]);
        });
    }

    public function scopeActive($query)
    {
        return $query->withScheduleColumns()->where('reserved_booking_period_schedules.is_active', true);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function slots()
    {
        return $this->hasMany(ReservedBookingPeriodSlot::class)
            ->orderBy('slot_time');
    }

    public function appointments()
    {
        return $this->hasManyThrough(Appointment::class, AppointmentReservedBooking::class,
            'reserved_booking_period_id', 'id', 'id', 'appointment_id');
    }

    public function activeAppointments()
    {
        return $this->appointments()->whereIn('status', ['upcoming', 'rescheduled']);
    }

    public function isEligiblePatient(Patient $patient): bool
    {
        $classification = strtolower(trim((string) $patient->classification));

        $matchesType = $this->target_patient_type === 'guest'
            ? in_array($classification, ['guest', 'dependent_alumni'], true)
            : $classification === $this->target_patient_type;

        if (! $matchesType) {
            return false;
        }

        if ($this->target_patient_type !== 'student') {
            return true;
        }

        return strtoupper(trim((string) $patient->course_code)) === strtoupper(trim((string) $this->program_code))
            && (int) $patient->year_level === (int) $this->year_level
            && strtoupper(trim((string) $patient->section)) === strtoupper(trim((string) $this->section));
    }

    public function allowsService(?string $service): bool
    {
        if ($this->allowed_services === null) {
            return true;
        }

        return collect($this->allowed_services)->contains(
            fn ($allowedService) => strcasecmp(trim((string) $allowedService), trim((string) $service)) === 0
        );
    }

    public function getTargetLabelAttribute(): string
    {
        $type = match ($this->target_patient_type) {
            'student' => 'Student',
            'faculty' => 'Faculty',
            'administrative' => 'Administrative',
            default => 'Guest',
        };

        if ($this->target_patient_type !== 'student') {
            return $type;
        }

        return implode(' · ', array_filter([
            $type,
            $this->program_code,
            $this->year_level ? 'Year '.$this->year_level : null,
            $this->section ? 'Section '.$this->section : null,
        ]));
    }
}
