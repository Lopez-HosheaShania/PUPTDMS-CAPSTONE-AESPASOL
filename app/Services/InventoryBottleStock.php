<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Validation\ValidationException;

class InventoryBottleStock
{
    public function prepare(array $data, ?Inventory $existing = null): array
    {
        $bottle = strtolower(trim($data['unit'])) === 'bottle';
        $consumption = $data['volume_consumed_ml'] ?? null;
        $revision = $data['stock_revision'] ?? null;
        unset($data['volume_consumed_ml'], $data['stock_revision']);

        if ($existing && ($existing->is_volume_bottle || $bottle)) {
            abort_unless(is_string($revision) && hash_equals($existing->stock_revision, $revision), 409,
                'This inventory item changed. Close and reopen the edit form before saving again.');
        }
        if ($existing?->is_volume_bottle && $existing->qty > 0) {
            if (!$bottle) {
                throw ValidationException::withMessages(['unit' => 'The unit cannot be changed while bottle stock remains.']);
            }
            if (Inventory::volumeHundredths($data['volume_per_bottle_ml']) !== Inventory::volumeHundredths($existing->volume_per_bottle_ml)) {
                throw ValidationException::withMessages(['volume_per_bottle_ml' => 'Bottle capacity cannot be changed while stock remains.']);
            }
        }

        if (!$bottle) {
            $data['volume_per_bottle_ml'] = null;
            $data['current_bottle_remaining_ml'] = null;

            return $data;
        }

        $capacity = Inventory::volumeHundredths($data['volume_per_bottle_ml']);
        $quantity = (int) $data['qty'];
        $remaining = $quantity > 0
            ? ($existing?->is_volume_bottle && $existing->qty > 0
                ? Inventory::volumeHundredths($existing->current_bottle_remaining_ml) : $capacity)
            : 0;
        $available = $quantity > 0 ? $remaining + (($quantity - 1) * $capacity) : 0;

        if ($consumption !== null) {
            $consumed = Inventory::volumeHundredths($consumption);
            if ($consumed > $available) {
                throw ValidationException::withMessages(['volume_consumed_ml' => 'The entered consumption exceeds the remaining available volume.']);
            }
            $available -= $consumed;
            $quantity = $available > 0 ? intdiv($available + $capacity - 1, $capacity) : 0;
            $remaining = $quantity > 0 ? $available - (($quantity - 1) * $capacity) : 0;
        }

        $data['qty'] = $quantity;
        $data['used'] = 0;
        $data['volume_per_bottle_ml'] = Inventory::volumeDecimal($capacity);
        $data['current_bottle_remaining_ml'] = Inventory::volumeDecimal($remaining);

        return $data;
    }
}
