<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Shared\InventoryController as SharedInventoryController;

class InventoryController extends SharedInventoryController
{
    protected function actorLabel(): string
    {
        return 'Dentist';
    }


    protected function layoutRole(): string
    {
        return 'dentist';
    }


    protected function isDentistView(): bool
    {
        return true;
    }


    protected function inventoryRouteNames(): array
    {
        return [
            'data' =>
            'dentist.dentist.inventory.data',

            'store' =>
            'dentist.dentist.inventory.store',

            'update' =>
            'dentist.dentist.inventory.update',

            'destroy' =>
            'dentist.dentist.inventory.destroy',
        ];
    }


    protected function inventoryWatcherKey(): string
    {
        return 'dentist-inventory';
    }
}
