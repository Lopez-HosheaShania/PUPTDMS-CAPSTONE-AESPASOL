<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory_items'; 

    protected $fillable = [
        'category',
        'date_received',
        'expiration_date',
        'stock_no',
        'name',
        'unit',
        'qty',
        'used',
        'volume_per_bottle_ml',
        'current_bottle_remaining_ml',
    ];

    protected $appends = [
        'balance',
        'formatted_date',
        'formatted_expiration_date',
        'expiration_days_remaining',
        'expiration_status',
        'expiration_label',
        'is_volume_bottle',
        'total_available_volume_ml',
        'stock_revision',
    ];

    public function getBalanceAttribute()
    {
        return $this->is_volume_bottle ? (int) $this->qty : $this->qty - $this->used;
    }

    public function getIsVolumeBottleAttribute(): bool
    {
        return strtolower(trim((string) $this->unit)) === 'bottle' && $this->volume_per_bottle_ml > 0;
    }

    public static function volumeHundredths(string|int|float $value): int
    {
        $parts = explode('.', (string) $value);

        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public static function volumeDecimal(int $hundredths): string
    {
        return intdiv($hundredths, 100).'.'.str_pad((string) ($hundredths % 100), 2, '0', STR_PAD_LEFT);
    }

    public function getTotalAvailableVolumeMlAttribute(): ?string
    {
        if (!$this->is_volume_bottle) {
            return null;
        }
        $total = $this->qty > 0
            ? self::volumeHundredths($this->current_bottle_remaining_ml ?? 0)
                + (((int) $this->qty - 1) * self::volumeHundredths($this->volume_per_bottle_ml))
            : 0;

        return self::volumeDecimal($total);
    }

    public function getStockRevisionAttribute(): string
    {
        return hash('sha256', json_encode([$this->id, $this->unit, (int) $this->qty, (int) $this->used,
            $this->volume_per_bottle_ml, $this->current_bottle_remaining_ml, (string) $this->updated_at]));
    }

    public static function lowStockSql(): string
    {
        return "CASE WHEN LOWER(TRIM(unit)) = 'bottle' AND volume_per_bottle_ml > 0 THEN "
            ."(CASE WHEN qty > 0 THEN current_bottle_remaining_ml + ((qty - 1) * volume_per_bottle_ml) ELSE 0 END) <= volume_per_bottle_ml * 0.30 "
            ."ELSE (qty - used) <= (qty * 0.30) END";
    }

    public function getFormattedDateAttribute()
    {
        return $this->date_received->format('m/d/y');
    }

    public function getFormattedExpirationDateAttribute(): ?string
    {
        return $this->expiration_date?->format('m/d/y');
    }

    public function getExpirationDaysRemainingAttribute(): ?int
    {
        return $this->expiration_date
            ? today()->diffInDays($this->expiration_date, false)
            : null;
    }

    public function getExpirationStatusAttribute(): string
    {
        if (! $this->expiration_date) {
            return 'none';
        }

        $today = today();
        $expirationDate = $this->expiration_date->copy()->startOfDay();

        if ($expirationDate->lt($today)) {
            return 'expired';
        }

        if ($expirationDate->equalTo($today)) {
            return 'today';
        }

        return $expirationDate->lte($today->copy()->addDays(30))
            ? 'near'
            : 'normal';
    }

    public function getExpirationLabelAttribute(): ?string
    {
        if (! $this->expiration_date) {
            return null;
        }

        return match ($this->expiration_status) {
            'expired' => 'Expired ' . today()->diffInDays($this->expiration_date) . ' day(s) ago',
            'today' => 'Expires Today',
            'near' => 'Expiring Soon (' . today()->diffInDays($this->expiration_date) . ' day(s))',
            default => 'Expires ' . $this->expiration_date->format('M d, Y'),
        };
    }

    protected $casts = [
        'date_received' => 'date',
        'expiration_date' => 'date',
        'volume_per_bottle_ml' => 'decimal:2',
        'current_bottle_remaining_ml' => 'decimal:2',
    ];
}
