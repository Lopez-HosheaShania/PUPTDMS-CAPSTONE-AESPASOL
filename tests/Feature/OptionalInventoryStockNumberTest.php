<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OptionalInventoryStockNumberTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return ['dentist' => ['dentist', 'dentist.dentist.inventory'], 'admin' => ['admin', 'admin.inventory']];
    }

    private function signIn(string $slug): void
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        foreach (['view_inventory', 'add_inventory', 'update_inventory'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $user = User::create(['name' => 'Inventory User', 'email' => 'inventory-optional@example.test',
            'role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['role' => $role->slug]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'category' => 'Supplies', 'date_received' => today()->toDateString(),
            'name' => 'Gauze', 'unit' => 'Piece', 'qty' => 10, 'used' => 0,
        ], $overrides);
    }

    #[DataProvider('roles')]
    public function test_multiple_items_can_be_created_without_stock_numbers(string $role, string $route): void
    {
        $this->signIn($role);
        foreach ([[], ['stock_no' => null], ['stock_no' => ''], ['stock_no' => '   ']] as $stock) {
            $this->postJson(route($route.'.store'), $this->payload($stock))->assertOk()->assertJsonPath('success', true);
        }
        $this->assertSame(4, Inventory::whereNull('stock_no')->count());
        $this->getJson(route($route.'.data'))->assertOk()->assertJsonCount(4)->assertJsonPath('0.stock_no', null);
    }

    #[DataProvider('roles')]
    public function test_edit_can_clear_a_stock_number_and_keep_it_blank(string $role, string $route): void
    {
        $this->signIn($role);
        $item = Inventory::create($this->payload(['stock_no' => '26-001']));
        $this->putJson(route($route.'.update', $item), $this->payload(['stock_no' => '']))->assertOk();
        $this->assertNull($item->fresh()->stock_no);
        $this->putJson(route($route.'.update', $item), $this->payload(['stock_no' => null]))->assertOk();
        $this->assertNull($item->fresh()->stock_no);
        $this->putJson(route($route.'.update', $item), $this->payload(['stock_no' => '26-002']))->assertOk();
        $this->putJson(route($route.'.update', $item), $this->payload(['name' => 'Updated Gauze']))->assertOk();
        $this->assertSame('26-002', $item->fresh()->stock_no);
    }

    #[DataProvider('roles')]
    public function test_provided_numbers_still_require_the_format_and_uniqueness(string $role, string $route): void
    {
        $this->signIn($role);
        $item = Inventory::create($this->payload(['stock_no' => '26-001']));
        foreach (['invalid', '26001', '26-01', '26-001'] as $invalid) {
            $this->postJson(route($route.'.store'), $this->payload(['stock_no' => $invalid]))
                ->assertUnprocessable()->assertJsonValidationErrors('stock_no');
        }
        $this->putJson(route($route.'.update', $item), $this->payload(['stock_no' => '26-001']))->assertOk();
        $other = Inventory::create($this->payload());
        $this->putJson(route($route.'.update', $other), $this->payload(['stock_no' => '26-001']))
            ->assertUnprocessable()->assertJsonValidationErrors('stock_no');
        $this->assertNull($other->fresh()->stock_no);
        $this->assertSame(0, DB::table('inventory_items')->insertOrIgnore($this->payload(['stock_no' => '26-001'])));
    }
}
