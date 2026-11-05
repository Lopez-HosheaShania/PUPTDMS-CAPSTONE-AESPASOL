<?php

namespace App\Models;

use App\Models\Concerns\StoresOptionalDetails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class ClinicSchedule extends Model
{
    use HasFactory, StoresOptionalDetails;

    protected function detailFields(): array
    {
        return [
            'configuration' => ['slot_duration_minutes'],
        ];
    }

    protected $fillable = [
        'days_label', 'days', 'status',
        'open_time', 'close_time', 'break_time',
        'max_slots', 'notes', 'is_active',
        'slot_duration_minutes',
    ];

    protected $casts = [
        'days'      => 'array',
        'is_active' => 'boolean',
    ];

    public function configuration()
    {
        return $this->hasOne(ClinicScheduleConfiguration::class);
    }

    // ── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───
    public function getHoursRangeAttribute(): string
    {
        if ($this->status === 'closed') return 'Closed';
        if (! $this->open_time || ! $this->close_time) return '—';
        return date('g:i A', strtotime($this->open_time))
             . ' – '
             . date('g:i A', strtotime($this->close_time));
    }

    public function getDaysLabelAttribute(): string
    {
        $map = [
            'Mon' => 'M',
            'Tue' => 'T',
            'Wed' => 'W',
            'Thu' => 'TH',
            'Fri' => 'F',
            'Sat' => 'SAT',
            'Sun' => 'SUN',
        ];

        $order = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return collect($this->days ?? [])
            ->sortBy(fn($day) => array_search($day, $order))
            ->map(fn($day) => $map[$day] ?? $day)
            ->implode(', ');
    }

    /**
     * Returns available time-slot strings for a specific ISO date.
     * Each element: ['time' => '9:00 AM', 'available' => true]
     *
     * @param  array  $bookedSlotCounts  ['9:00 AM' => 1, '10:00 AM' => 0, …]
     */
    
    public function availableSlots(string $isoDate, array $bookedSlotCounts = []): array
    {
        if ($this->status === 'closed' || ! $this->open_time || ! $this->close_time) {
            return [];
        }

        $durationMinutes = max(5, (int) ($this->slot_duration_minutes ?: 60));
        $date = Carbon::parse($isoDate)->toDateString();
        $open = Carbon::parse($date . ' ' . $this->open_time);
        $close = Carbon::parse($date . ' ' . $this->close_time);

        $breakStart = null;
        $breakEnd = null;

        if ($this->break_time && $this->break_time !== 'none') {
            [$bs, $be] = explode('-', $this->break_time);
            $breakStart = Carbon::parse($date . ' ' . trim($bs));
            $breakEnd = Carbon::parse($date . ' ' . trim($be));
        }

        $slots = [];

        for ($slotStart = $open->copy(); $slotStart->copy()->addMinutes($durationMinutes)->lte($close); $slotStart->addMinutes($durationMinutes)) {
            $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

            if ($breakStart && $slotStart->lt($breakEnd) && $slotEnd->gt($breakStart)) {
                continue;
            }

            $mysqlTime = $slotStart->format('H:i:s');
            $booked = $bookedSlotCounts[$mysqlTime] ?? 0;

            $slots[] = [
                'time' => $slotStart->format('g:i A'),
                'mysql_time' => $mysqlTime,
                'available' => $booked < 1,
            ];
        }

        return $slots;
    }

    public static function possibleSlotCount(
        string $openTime,
        string $closeTime,
        ?string $breakTime,
        int $durationMinutes,
        ?string $isoDate = null
    ): int {
        $durationMinutes = max(5, $durationMinutes);
        $date = Carbon::parse($isoDate ?: now()->toDateString())->toDateString();
        $open = Carbon::parse($date . ' ' . $openTime);
        $close = Carbon::parse($date . ' ' . $closeTime);

        if ($open->gte($close)) {
            return 0;
        }

        $breakStart = null;
        $breakEnd = null;

        if ($breakTime && $breakTime !== 'none') {
            [$bs, $be] = explode('-', $breakTime);
            $breakStart = Carbon::parse($date . ' ' . trim($bs));
            $breakEnd = Carbon::parse($date . ' ' . trim($be));
        }

        $count = 0;

        for ($slotStart = $open->copy(); $slotStart->copy()->addMinutes($durationMinutes)->lte($close); $slotStart->addMinutes($durationMinutes)) {
            $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

            if ($breakStart && $slotStart->lt($breakEnd) && $slotEnd->gt($breakStart)) {
                continue;
            }

            $count++;
        }

        return $count;
    }
}
