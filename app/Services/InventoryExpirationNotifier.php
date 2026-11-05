<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\User;
use App\Notifications\InventoryExpirationNotification;
use Illuminate\Support\Collection;

class InventoryExpirationNotifier
{
    public function notify(Inventory $inventory): int
    {
        $state = $inventory->expiration_status;

        if (! in_array($state, ['near', 'today', 'expired'], true)) {
            return 0;
        }

        $dedupeKey = implode('.', [
            'inventory.expiration',
            $state,
            $inventory->id,
            $inventory->expiration_date?->toDateString(),
        ]);
        $sent = 0;

        foreach ($this->recipients() as $recipient) {
            if ($recipient->notifications()->where('data->dedupe_key', $dedupeKey)->exists()) {
                continue;
            }

            $recipient->notify(new InventoryExpirationNotification($inventory, $state));
            $sent++;
        }

        return $sent;
    }

    private function recipients(): Collection
    {
        return User::query()
            ->with('role')
            ->where('status', 'active')
            ->whereHas('role', function ($query) {
                $query->whereIn('slug', ['admin', 'super_admin'])
                    ->orWhereHas('permissions', function ($permissions) {
                        $permissions->whereIn('slug', [
                            'manage_inventory',
                            'manage_inventory_items',
                            'view_inventory',
                        ]);
                    });
            })
            ->get();
    }
}
