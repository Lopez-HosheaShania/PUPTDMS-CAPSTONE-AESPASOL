<?php

namespace App\Http\Controllers\Shared;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Services\InventoryExpirationNotifier;
use App\Services\InventoryBottleStock;
use Illuminate\Support\Facades\DB;
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

        $inventory = DB::transaction(function () use ($data) {
            $inventory = Inventory::create(app(InventoryBottleStock::class)->prepare($data));
            AuditLogger::log('create_inventory', 'inventory', $this->actorLabel().' added inventory item: '.$inventory->name
                .($inventory->is_volume_bottle ? ' | '.json_encode(['volume_consumed_ml' => $data['volume_consumed_ml'] ?? null, 'bottles' => $inventory->qty, 'current_bottle_remaining_ml' => $inventory->current_bottle_remaining_ml], JSON_THROW_ON_ERROR) : ''));

            return $inventory;
        });

        app(
            InventoryExpirationNotifier::class
        )->notify($inventory);

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

        $inventory = DB::transaction(function () use ($inventory, $data) {
            $locked = Inventory::lockForUpdate()->findOrFail($inventory->id);
            $before = $locked->only(['qty', 'used', 'volume_per_bottle_ml', 'current_bottle_remaining_ml']);
            $before['total_available_volume_ml'] = $locked->total_available_volume_ml;
            $locked->update(app(InventoryBottleStock::class)->prepare($data, $locked));
            AuditLogger::log('update_inventory', 'inventory', json_encode([
                'item_id' => $locked->id, 'volume_consumed_ml' => $data['volume_consumed_ml'] ?? null,
                'previous' => $before,
                'updated' => $locked->only(['qty', 'used', 'volume_per_bottle_ml', 'current_bottle_remaining_ml']),
                'total_available_volume_ml' => $locked->total_available_volume_ml,
            ], JSON_THROW_ON_ERROR));

            return $locked;
        });

        app(
            InventoryExpirationNotifier::class
        )->notify(
            $inventory->fresh()
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

        $bottle = strtolower(trim((string) request('unit'))) === 'bottle';
        $volumeRules = ['nullable', 'numeric', 'regex:/^\d+(?:\.\d{1,2})?$/', 'min:0.01', 'max:99999999.99'];

        return [
            'volume_per_bottle_ml' => array_merge([Rule::requiredIf($bottle)], $volumeRules),
            'volume_consumed_ml' => array_merge([Rule::prohibitedIf(!$bottle)], $volumeRules),
            'stock_revision' => [Rule::requiredIf($inventory && ($inventory->is_volume_bottle || $bottle)), 'nullable', 'string', 'size:64'],
            'current_bottle_remaining_ml' => ['prohibited'],
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
                $inventory && $bottle ? 'min:0' : 'min:1',
                'max:99999',
            ],

            'used' => [
                'required',
                'integer',
                'min:0',
                'max:99999',
                'lte:qty',
                Rule::when($bottle, ['max:0']),
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
