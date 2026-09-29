<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceType;

class Appointment extends Model
{
    use HasFactory;

    private const DETAIL_FIELDS = [
        'reserved_booking_period_id' => 'reservedBookingDetails',
        'reserved_booking_period_slot_id' => 'reservedBookingDetails',
        'is_follow_up' => 'followUpDetails',
        'follow_up_for_appointment_id' => 'followUpDetails',
        'follow_up_reason' => 'followUpDetails',
        'original_dentist_id' => 'transferDetails',
        'transferred_by' => 'transferDetails',
        'transferred_at' => 'transferDetails',
        'transfer_reason' => 'transferDetails',
        'follow_up_reminder_sent_at' => 'reminderState',
        'follow_up_today_reminder_sent_at' => 'reminderState',
        'follow_up_one_day_reminder_sent_at' => 'reminderState',
    ];

    protected $with = ['transferDetails', 'reminderState', 'reservedBookingDetails', 'followUpDetails'];

    protected $hidden = ['transferDetails', 'reminderState', 'reservedBookingDetails', 'followUpDetails'];

    private array $pendingDetails = [];

    public function reservedBookingDetails()
    {
        return $this->hasOne(AppointmentReservedBooking::class);
    }

    public function followUpDetails()
    {
        return $this->hasOne(AppointmentFollowUp::class);
    }

    public function scopeRegularBooking(Builder $query): Builder
    {
        return $query->whereDoesntHave('reservedBookingDetails', fn ($details) => $details->whereNotNull('reserved_booking_period_id'));
    }

    public function scopeForReservedPeriod(Builder $query, int $periodId): Builder
    {
        return $query->whereHas('reservedBookingDetails', fn ($details) => $details->where('reserved_booking_period_id', $periodId));
    }

    public function scopeForReservedSlot(Builder $query, int $slotId): Builder
    {
        return $query->whereHas('reservedBookingDetails', fn ($details) => $details->where('reserved_booking_period_slot_id', $slotId));
    }

    public function scopeFollowUps(Builder $query): Builder
    {
        return $query->whereHas('followUpDetails', fn ($details) => $details->where('is_follow_up', true));
    }

    public function transferDetails()
    {
        return $this->hasOne(AppointmentTransfer::class);
    }

    public function reminderState()
    {
        return $this->hasOne(AppointmentReminder::class);
    }

    public function getAttribute($key)
    {
        if (isset(self::DETAIL_FIELDS[$key])) {
            $relation = self::DETAIL_FIELDS[$key];
            if (array_key_exists($key, $this->pendingDetails[$relation] ?? [])) {
                return $this->{$relation}()->getRelated()->newInstance($this->pendingDetails[$relation])->getAttribute($key);
            }

            $value = $this->getRelationValue($relation)?->getAttribute($key);

            return $key === 'is_follow_up' ? (bool) $value : $value;
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (isset(self::DETAIL_FIELDS[$key])) {
            $relation = self::DETAIL_FIELDS[$key];
            $detail = $this->{$relation}()->getRelated()->newInstance([$key => $value]);
            $this->pendingDetails[$relation][$key] = $detail->getAttributes()[$key];

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        $details = [];
        foreach (self::DETAIL_FIELDS as $key => $relation) {
            $value = $this->getAttribute($key);
            $details[$key] = $value instanceof \DateTimeInterface ? $this->serializeDate($value) : $value;
        }

        return array_merge($attributes, $this->getArrayableItems($details));
    }

    public function save(array $options = [])
    {
        if ($this->pendingDetails === []) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options) {
            if (! parent::save($options)) {
                return false;
            }
            foreach ($this->pendingDetails as $relation => $values) {
                if ($relation === 'reservedBookingDetails') {
                    $values['booking_patient_id'] = $this->patient_id;
                }
                $detail = $this->{$relation}()->updateOrCreate([], $values);
                $this->setRelation($relation, $detail);
            }
            $this->pendingDetails = [];

            return true;
        });
    }

    public const ACTIVE_DUTY_END_STATUSES = [
        'pending',
        'confirmed',
        'upcoming',
        'rescheduled',
    ];

    public const FINALIZED_STATUSES = [
        'completed',
        'cancelled',
        'rejected',
        'no_show',
        'no-show',
    ];

    protected $fillable = [
        'patient_id',
        'service_type_id',
        'reserved_booking_period_id',
        'reserved_booking_period_slot_id',
        'dentist_id',
        'original_dentist_id',
        'service_type',
        'appointment_date',
        'appointment_time',
        'status',
        'cancellation_reason',
        'transferred_by',
        'transferred_at',
        'transfer_reason',
        'is_follow_up',
        'follow_up_for_appointment_id',
        'follow_up_reason',
        'follow_up_reminder_sent_at',
        'follow_up_today_reminder_sent_at',
        'follow_up_one_day_reminder_sent_at',
        'is_walk_in',
    ];

    protected $casts = [
        'is_follow_up' => 'boolean',
        'follow_up_reminder_sent_at' => 'datetime',
        'follow_up_today_reminder_sent_at' => 'datetime',
        'follow_up_one_day_reminder_sent_at' => 'datetime',
        'is_walk_in' => 'boolean',
        'transferred_at' => 'datetime',
    ];

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function dentalHistory()
    {
        return $this->hasOne(DentalHistory::class);
    }

    public function scopeForDentist(Builder $query, int $dentistId): Builder
    {
        return $query->where('dentist_id', $dentistId);
    }

    public function scopeScheduledOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeActiveForDutyEnd(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_DUTY_END_STATUSES);
    }

    public function scopeActiveAndNotElapsed(Builder $query, ?Carbon $now = null): Builder
    {
        $now ??= now();
        $today = $now->toDateString();
        $nowTime = $now->format('H:i:s');

        return $query
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->where(function (Builder $query) use ($today, $nowTime) {
                $query->whereDate('appointment_date', '>', $today)
                    ->orWhere(function (Builder $sameDate) use ($today, $nowTime) {
                        $sameDate->whereDate('appointment_date', $today)
                            ->where(function (Builder $sameDay) use ($nowTime) {
                                $sameDay->where(function (Builder $regular) use ($nowTime) {
                                    $regular->regularBooking()
                                        ->whereTime('appointment_time', '>=', $nowTime);
                                })->orWhereHas('reservedBookingPeriod', function (Builder $period) use ($nowTime) {
                                    $period->withTrashed()
                                        ->withScheduleColumns()
                                        ->whereTime('end_time', '>=', $nowTime);
                                });
                            });
                    });
            });
    }

    public static function cancelElapsedScheduledAppointments(?Carbon $now = null): int
    {
        $today = ($now ?? now())->toDateString();

        return DB::transaction(function () use ($today) {
            $appointmentIds = static::query()
                ->whereIn('status', self::ACTIVE_DUTY_END_STATUSES)
                ->whereDate('appointment_date', '<', $today)
                ->pluck('id');

            if ($appointmentIds->isEmpty()) {
                return 0;
            }

            $timestamp = now();

            $cancelled = static::query()
                ->whereKey($appointmentIds)
                ->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => DB::raw(
                        "COALESCE(cancellation_reason, 'Automatically cancelled because the scheduled appointment date elapsed.')"
                    ),
                    'updated_at' => $timestamp,
                ]);

            AppointmentReservedBooking::query()
                ->whereIn('appointment_id', $appointmentIds)
                ->update([
                    'reserved_booking_period_slot_id' => null,
                    'updated_at' => $timestamp,
                ]);

            return $cancelled;
        });
    }

    public function medicalHistory()
    {
        return $this->hasOne(MedicalHistory::class);
    }
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function reservedBookingPeriod()
    {
        return $this->hasOneThrough(ReservedBookingPeriod::class, AppointmentReservedBooking::class,
            'appointment_id', 'id', 'id', 'reserved_booking_period_id')->withTrashed();
    }

    public function reservedBookingPeriodSlot()
    {
        return $this->hasOneThrough(ReservedBookingPeriodSlot::class, AppointmentReservedBooking::class,
            'appointment_id', 'id', 'id', 'reserved_booking_period_slot_id');
    }

    public function reservedProcedureWindowIsOpen(?Carbon $now = null): bool
    {
        if (! $this->reservedBookingPeriod) {
            return Carbon::parse($this->appointment_date)->isToday();
        }

        $now ??= now();
        $date = Carbon::parse($this->reservedBookingPeriod->reserved_date)->toDateString();
        $start = Carbon::parse($date . ' ' . $this->reservedBookingPeriod->start_time);
        $end = Carbon::parse($date . ' ' . $this->reservedBookingPeriod->end_time);

        return $now->betweenIncluded($start, $end);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function originalDentist()
    {
        return $this->belongsTo(User::class, 'original_dentist_id');
    }

    public function transferActor()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function originalAppointment()
    {
        return $this->hasOneThrough(Appointment::class, AppointmentFollowUp::class,
            'appointment_id', 'id', 'id', 'follow_up_for_appointment_id');
    }

    public function followUpAppointments()
    {
        return $this->hasManyThrough(Appointment::class, AppointmentFollowUp::class,
            'follow_up_for_appointment_id', 'id', 'id', 'appointment_id');
    }

    public function procedure()
    {
        return $this->hasOne(AppointmentProcedure::class);
    }

    public function getServiceTypeNameAttribute(): ?string
    {
        return $this->serviceType?->name ?? $this->service_type;
    }
}
