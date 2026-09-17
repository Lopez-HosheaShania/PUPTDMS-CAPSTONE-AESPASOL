<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Shared\InventoryController as SharedInventoryController;

class AdminInventoryController extends SharedInventoryController
{
    protected function actorLabel(): string
    {
        return 'Admin';
    }


    protected function layoutRole(): string
    {
        return 'admin';
    }


    protected function isDentistView(): bool
    {
        return false;
    }


    protected function inventoryRouteNames(): array
    {
        return [
            'data' =>
            'admin.inventory.data',

            'store' =>
            'admin.inventory.store',

            'update' =>
            'admin.inventory.update',

            'destroy' =>
            'admin.inventory.destroy',
        ];
    }


    protected function inventoryWatcherKey(): string
    {
        return 'admin-inventory';
    }
}
