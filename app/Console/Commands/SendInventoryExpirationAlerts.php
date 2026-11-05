<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Services\InventoryExpirationNotifier;
use Illuminate\Console\Command;

class SendInventoryExpirationAlerts extends Command
{
    private const NEAR_EXPIRATION_DAYS = 30;

    protected $signature = 'inventory:send-expiration-alerts';

    protected $description = 'Send one-time alerts for inventory items nearing expiration or already expired.';

    public function handle(InventoryExpirationNotifier $notifier): int
    {
        $today = today();

        $nearExpiration = Inventory::query()
            ->whereBetween('expiration_date', [
                $today->copy()->addDay()->toDateString(),
                $today->copy()->addDays(self::NEAR_EXPIRATION_DAYS)->toDateString(),
            ])
            ->get();

        $expired = Inventory::query()
            ->whereDate('expiration_date', '<', $today->toDateString())
            ->get();

        $expiresToday = Inventory::query()
            ->whereDate('expiration_date', $today->toDateString())
            ->get();

        $sent = 0;

        foreach ($nearExpiration as $inventory) {
            $sent += $notifier->notify($inventory);
        }

        foreach ($expired as $inventory) {
            $sent += $notifier->notify($inventory);
        }

        foreach ($expiresToday as $inventory) {
            $sent += $notifier->notify($inventory);
        }

        $this->info("Inventory expiration alerts sent: {$sent}");

        return self::SUCCESS;
    }
}
