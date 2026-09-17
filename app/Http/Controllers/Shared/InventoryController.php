<?php

namespace App\Http\Controllers\Shared;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Services\InventoryExpirationNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

abstract class InventoryController extends Controller
{
    abstract protected function actorLabel(): string;

    abstract protected function layoutRole(): string;

    abstract protected function isDentistView(): bool;

    abstract protected function inventoryRouteNames(): array;

    abstract protected function inventoryWatcherKey(): string;


    public function index()
    {
        AuditLogger::log(
            'view',
            'inventory',
            $this->actorLabel() . ' viewed inventory page'
        );

        return view('shared.inventory', [
            'notifications' => collect([]),

            'layoutRole' => $this->layoutRole(),

            'pageShellClass' => 'app-page-shell',

            'isDentistView' => $this->isDentistView(),

            'inventoryRouteNames' =>
            $this->inventoryRouteNames(),

            'inventoryWatcherKey' =>
            $this->inventoryWatcherKey(),
        ]);
    }


    public function fetch()
    {
        return Inventory::query()
            ->orderByDesc('date_received')
            ->get();
    }


    public function store(Request $request)
    {
        $data = $request->validate(
            $this->validationRules()
        );

        $data['unit'] =
            $this->normalizeUnit(
                $data['unit']
            );

        $inventory =
            Inventory::create($data);

        app(
            InventoryExpirationNotifier::class
        )->notify($inventory);

        AuditLogger::log(
            'create_inventory',
            'inventory',
            $this->actorLabel()
                . ' added inventory item: '
                . $inventory->name
        );

        return response()->json([
            'success' => true,
        ]);
    }


    public function update(
        Request $request,
        Inventory $inventory
    ) {
        $data = $request->validate(
            $this->validationRules(
                $inventory
            )
        );

        $data['unit'] =
            $this->normalizeUnit(
                $data['unit']
            );

        $inventory->update($data);

        app(
            InventoryExpirationNotifier::class
        )->notify(
            $inventory->fresh()
        );

        AuditLogger::log(
            'update_inventory',
            'inventory',
            $this->actorLabel()
                . ' updated inventory item ID '
                . $inventory->id
        );

        return response()->json([
            'success' => true,
        ]);
    }


    public function destroy(
        Inventory $inventory
    ) {
        $inventoryId =
            $inventory->id;

        $inventory->delete();

        AuditLogger::log(
            'delete_inventory',
            'inventory',
            $this->actorLabel()
                . ' deleted inventory item ID '
                . $inventoryId
        );

        return response()->json([
            'success' => true,
        ]);
    }


    protected function validationRules(
        ?Inventory $inventory = null
    ): array {
        $stockNumberRule =
            Rule::unique(
                'inventory_items',
                'stock_no'
            );

        if ($inventory) {
            $stockNumberRule->ignore(
                $inventory->id
            );
        }

        return [
            'category' => [
                'required',
                Rule::in([
                    'Medicine',
                    'Supplies',
                ]),
            ],

            'date_received' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'expiration_date' => [
                'nullable',
                'date',
            ],

            'stock_no' => [
                'required',
                'regex:/^\d{2}-\d{3}$/',
                $stockNumberRule,
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'qty' => [
                'required',
                'integer',
                'min:1',
                'max:99999',
            ],

            'used' => [
                'required',
                'integer',
                'min:0',
                'max:99999',
                'lte:qty',
            ],
        ];
    }


    protected function normalizeUnit(
        string $unit
    ): string {
        return ucwords(
            strtolower(
                trim($unit)
            )
        );
    }
}
