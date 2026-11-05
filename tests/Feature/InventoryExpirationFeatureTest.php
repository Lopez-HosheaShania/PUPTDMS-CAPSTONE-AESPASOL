<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryExpirationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_expiration_dates_are_optional_and_have_date_based_states(): void
    {
        Carbon::setTestNow('2026-09-03 08:00:00');

        $inventory = $this->makeInventory();

        $this->assertNull($inventory->expiration_date);
        $this->assertSame('none', $inventory->expiration_status);
        $this->assertNull($inventory->expiration_label);

        $inventory->update(['expiration_date' => '2026-10-04']);
        $this->assertSame('normal', $inventory->fresh()->expiration_status);

        $inventory->update(['expiration_date' => '2026-09-08']);
        $this->assertSame('near', $inventory->fresh()->expiration_status);

        $inventory->update(['expiration_date' => '2026-09-03']);
        $this->assertSame('today', $inventory->fresh()->expiration_status);

        $inventory->update(['expiration_date' => '2026-09-02']);
        $this->assertSame('expired', $inventory->fresh()->expiration_status);

        $inventory->update(['expiration_date' => null]);
        $this->assertNull($inventory->fresh()->expiration_date);
        $this->assertSame('none', $inventory->fresh()->expiration_status);
    }

    public function test_daily_command_notifies_inventory_users_once_for_near_today_and_expired_items(): void
    {
        Carbon::setTestNow('2026-09-03 08:00:00');

        $recipient = $this->makeInventoryRecipient();
        $near = $this->makeInventory(['expiration_date' => '2026-09-10']);
        $expired = $this->makeInventory(['stock_no' => '00-002', 'expiration_date' => '2026-09-02']);
        $today = $this->makeInventory(['stock_no' => '00-003', 'expiration_date' => '2026-09-03']);
        $this->makeInventory(['stock_no' => '00-004', 'expiration_date' => '2026-10-04']);

        $this->artisan('inventory:send-expiration-alerts')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertSame(3, $recipient->fresh()->notifications()->count());

        $this->assertDatabaseHas('notifications', [
            'data' => json_encode([
                'title' => 'Inventory Item Expiring Soon',
                'message' => "{$near->name} expires on September 10, 2026.",
                'url' => route('dentist.dentist.inventory'),
                'icon' => 'fa-clock',
                'inventory_id' => $near->id,
                'expiration_date' => '2026-09-10',
                'event' => 'inventory.expiration.near',
                'dedupe_key' => "inventory.expiration.near.{$near->id}.2026-09-10",
                'recipient_role' => 'dentist',
            ]),
        ]);

        $this->artisan('inventory:send-expiration-alerts')
            ->assertSuccessful();

        $this->assertSame(3, $recipient->fresh()->notifications()->count());
        $this->assertNotNull($expired->fresh()->expiration_date);
        $this->assertSame('today', $today->fresh()->expiration_status);
    }

    private function makeInventory(array $attributes = []): Inventory
    {
        return Inventory::create(array_merge([
            'category' => 'Medicine',
            'date_received' => '2026-09-01',
            'stock_no' => '00-001',
            'name' => 'Test Item',
            'unit' => 'Box',
            'qty' => 10,
            'used' => 0,
            'expiration_date' => null,
        ], $attributes));
    }

    private function makeInventoryRecipient(): User
    {
        $role = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        $permission = Permission::create([
            'name' => 'View Inventory',
            'slug' => 'view_inventory',
            'module' => 'Inventory',
        ]);
        $role->permissions()->attach($permission);

        return User::create([
            'name' => 'Dr. Inventory',
            'email' => 'inventory@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }
}
