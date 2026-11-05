<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Inventory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BottleInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $slug = 'dentist', bool $permitted = true): void
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        if ($permitted) {
            foreach (['view_inventory', 'add_inventory', 'update_inventory'] as $permissionSlug) {
                $permission = Permission::firstOrCreate(['slug' => $permissionSlug], ['name' => $permissionSlug]);
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }
        $user = User::create(['name' => 'Bottle Dentist', 'email' => uniqid('bottle-').'@example.test',
            'role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['role' => $slug]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['category' => 'Medicine', 'date_received' => today()->toDateString(),
            'stock_no' => '26-101', 'name' => 'Mouthwash', 'unit' => 'Bottle', 'qty' => 3, 'used' => 0,
            'volume_per_bottle_ml' => '100.00'], $overrides);
    }

    private function bottle(array $overrides = []): Inventory
    {
        return Inventory::create($this->payload($overrides + ['current_bottle_remaining_ml' => '100.00']));
    }

    private function updatePayload(Inventory $item, array $overrides = []): array
    {
        return array_replace($item->only(['category', 'stock_no', 'name', 'unit', 'qty', 'used', 'volume_per_bottle_ml']), [
            'date_received' => $item->date_received->toDateString(), 'stock_revision' => $item->stock_revision,
        ], $overrides);
    }

    public static function consumptionCases(): array
    {
        return [
            'partial' => [3, '100.00', '0.5', 3, '99.50', '299.50'],
            'depleted active bottle' => [3, '0.50', '0.50', 2, '100.00', '200.00'],
            'crosses boundary' => [3, '20.00', '25', 2, '95.00', '195.00'],
            'multiple bottles' => [3, '50.00', '170', 1, '80.00', '80.00'],
            'all stock consumed' => [3, '50.00', '250', 0, '0.00', '0.00'],
            'two decimal precision' => [3, '100.00', '1.25', 3, '98.75', '298.75'],
        ];
    }

    #[DataProvider('consumptionCases')]
    public function test_volume_consumption_preserves_physical_bottles_until_depleted(int $qty, string $remaining, string $consumed, int $expectedQty, string $expectedRemaining, string $expectedTotal): void
    {
        $this->signIn();
        $item = $this->bottle(['qty' => $qty, 'current_bottle_remaining_ml' => $remaining]);
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['volume_consumed_ml' => $consumed]))->assertOk();
        $item->refresh();
        $this->assertSame($expectedQty, (int) $item->qty);
        $this->assertSame($expectedQty, $item->balance);
        $this->assertSame(0, (int) $item->used);
        $this->assertSame($expectedRemaining, $item->current_bottle_remaining_ml);
        $this->assertSame($expectedTotal, $item->total_available_volume_ml);
        $this->assertDatabaseHas('audit_logs', ['action' => 'update_inventory']);
    }

    public function test_bottle_creation_and_admin_updates_use_the_same_rules(): void
    {
        $this->signIn('admin');
        $this->postJson(route('admin.inventory.store'), $this->payload(['unit' => ' bottle ']))->assertOk();
        $item = Inventory::firstOrFail();
        $this->assertSame('Bottle', $item->unit);
        $this->assertSame('100.00', $item->current_bottle_remaining_ml);
        $this->assertSame('300.00', $item->total_available_volume_ml);
        $this->putJson(route('admin.inventory.update', $item), $this->updatePayload($item, ['volume_consumed_ml' => '0.50']))->assertOk();
        $this->assertSame('99.50', $item->fresh()->current_bottle_remaining_ml);
        $this->getJson(route('admin.inventory.data'))->assertOk()->assertJsonPath('0.balance', 3)
            ->assertJsonPath('0.total_available_volume_ml', '299.50');
    }

    public function test_initial_consumption_can_be_recorded_when_adding_a_bottle_item(): void
    {
        $this->signIn();
        $this->postJson(route('dentist.dentist.inventory.store'), $this->payload(['volume_consumed_ml' => '0.50']))->assertOk();
        $item = Inventory::firstOrFail();
        $this->assertSame(3, (int) $item->qty);
        $this->assertSame('99.50', $item->current_bottle_remaining_ml);
        $this->assertSame('299.50', $item->total_available_volume_ml);
    }

    public function test_overconsumption_leaves_stock_and_audit_unchanged(): void
    {
        $this->signIn();
        $item = $this->bottle(['qty' => 1, 'current_bottle_remaining_ml' => '25.00']);
        $before = $item->fresh()->getAttributes();
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['volume_consumed_ml' => '30']))
            ->assertUnprocessable()->assertJsonValidationErrors('volume_consumed_ml')
            ->assertJsonPath('errors.volume_consumed_ml.0', 'The entered consumption exceeds the remaining available volume.');
        $this->assertSame($before, $item->fresh()->getAttributes());
        $this->assertDatabaseMissing('audit_logs', ['action' => 'update_inventory']);
    }

    public function test_replaying_or_submitting_a_stale_form_cannot_consume_twice_or_reset_stock(): void
    {
        $this->signIn();
        $item = $this->bottle();
        $stale = $this->updatePayload($item, ['volume_consumed_ml' => '0.5']);
        $this->putJson(route('dentist.dentist.inventory.update', $item), $stale)->assertOk();
        $this->putJson(route('dentist.dentist.inventory.update', $item), $stale)->assertStatus(409);
        $this->assertSame('99.50', $item->fresh()->current_bottle_remaining_ml);
        $this->assertSame(1, AuditLog::where('action', 'update_inventory')->count());
    }

    public function test_metadata_edits_restocking_and_empty_stock_do_not_reset_partial_volume(): void
    {
        $this->signIn();
        $item = $this->bottle(['current_bottle_remaining_ml' => '87.50']);
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['name' => 'New name', 'qty' => 4]))->assertOk();
        $this->assertSame('87.50', $item->fresh()->current_bottle_remaining_ml);
        $this->assertSame('387.50', $item->fresh()->total_available_volume_ml);
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item->fresh(), ['volume_per_bottle_ml' => '50']))
            ->assertUnprocessable()->assertJsonValidationErrors('volume_per_bottle_ml');
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item->fresh(), ['qty' => 0]))->assertOk();
        $this->assertSame('0.00', $item->fresh()->current_bottle_remaining_ml);
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item->fresh(), ['qty' => 2]))->assertOk();
        $this->assertSame('100.00', $item->fresh()->current_bottle_remaining_ml);
    }

    public static function invalidVolumes(): array
    {
        return array_map(fn ($value) => [$value], ['0', '-1', 'NaN', 'abc', '0.001', '1e3', '999999999.99']);
    }

    #[DataProvider('invalidVolumes')]
    public function test_invalid_consumption_is_rejected(string $value): void
    {
        $this->signIn();
        $item = $this->bottle();
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['volume_consumed_ml' => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors('volume_consumed_ml');
        $this->assertSame('100.00', $item->fresh()->current_bottle_remaining_ml);
    }

    public function test_missing_capacity_mixed_units_and_client_remaining_values_are_rejected(): void
    {
        $this->signIn();
        $this->postJson(route('dentist.dentist.inventory.store'), $this->payload(['volume_per_bottle_ml' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('volume_per_bottle_ml');
        $item = $this->bottle();
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['used' => 1]))->assertUnprocessable();
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['current_bottle_remaining_ml' => '500']))->assertUnprocessable();
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['unit' => 'Piece']))->assertUnprocessable();
    }

    public function test_non_bottle_inventory_preserves_quantity_minus_used_behavior(): void
    {
        $this->signIn();
        $this->postJson(route('dentist.dentist.inventory.store'), $this->payload(['unit' => 'Piece', 'qty' => 10, 'used' => 2, 'volume_per_bottle_ml' => null]))->assertOk();
        $item = Inventory::firstOrFail();
        $this->assertSame(8, $item->balance);
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['used' => 3]))->assertOk();
        $this->assertSame(7, $item->fresh()->balance);
        $this->assertNull($item->fresh()->total_available_volume_ml);
    }

    public function test_existing_bottles_are_not_assigned_an_invented_capacity(): void
    {
        $this->signIn();
        $item = Inventory::create($this->payload(['qty' => 5, 'used' => 2, 'volume_per_bottle_ml' => null]));
        $this->assertFalse($item->is_volume_bottle);
        $this->assertSame(3, $item->balance);
        $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item, ['qty' => 3, 'used' => 0, 'volume_per_bottle_ml' => '100']))->assertOk();
        $this->assertSame('300.00', $item->fresh()->total_available_volume_ml);
    }

    public function test_repeated_decimal_consumption_does_not_accumulate_float_errors(): void
    {
        $this->signIn();
        $item = $this->bottle(['qty' => 2, 'volume_per_bottle_ml' => '1.00', 'current_bottle_remaining_ml' => '1.00']);
        for ($step = 0; $step < 10; $step++) {
            $this->putJson(route('dentist.dentist.inventory.update', $item), $this->updatePayload($item->fresh(), ['volume_consumed_ml' => '0.10']))->assertOk();
        }
        $this->assertSame(1, (int) $item->fresh()->qty);
        $this->assertSame('1.00', $item->fresh()->current_bottle_remaining_ml);
    }

    public function test_low_stock_reports_use_volume_for_bottles_and_existing_rules_for_other_units(): void
    {
        $this->bottle(['qty' => 1, 'current_bottle_remaining_ml' => '20.00']);
        Inventory::create($this->payload(['stock_no' => '26-102', 'unit' => 'Piece', 'qty' => 10, 'used' => 8, 'volume_per_bottle_ml' => null]));
        $this->assertSame(2, DB::table('inventory_items')->whereRaw(Inventory::lowStockSql())->count());
    }
}
