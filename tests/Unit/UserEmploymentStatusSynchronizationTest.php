<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserEmploymentStatusSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_employment_data_is_stored_outside_users_and_remains_accessible(): void
    {
        $role = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);

        $user = User::create([
            'name' => 'Employment Test Dentist',
            'email' => 'employment.test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
            'employment_status' => 'active',
            'account_status' => 'for_transition',
            'last_working_date' => '2026-09-10',
            'access_ends_at' => '2026-09-10 17:00:00',
        ]);

        $this->assertDatabaseHas('user_employment_statuses', [
            'user_id' => $user->id,
            'employment_status' => 'active',
            'account_status' => 'for_transition',
        ]);
        $this->assertSame('for_transition', $user->fresh()->account_status);
        $this->assertFalse(Schema::hasColumns('users', [
            'employment_status',
            'account_status',
            'last_working_date',
            'access_ends_at',
            'deactivated_at',
            'deactivated_by',
            'deactivation_reason',
        ]));
    }

    public function test_deactivation_is_recorded_as_history_without_a_users_table_column(): void
    {
        $role = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        $actor = User::create([
            'name' => 'Admin Actor',
            'email' => 'admin.actor@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Departing Dentist',
            'email' => 'departing.dentist@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
            'employment_status' => 'retired',
            'account_status' => 'retired',
        ]);

        $user->forceFill([
            'status' => 'inactive',
            'deactivated_at' => now(),
            'deactivated_by' => $actor->id,
            'deactivation_reason' => 'retirement',
        ])->save();

        $this->assertDatabaseHas('user_deactivations', [
            'user_id' => $user->id,
            'deactivated_by' => $actor->id,
            'reason' => 'retirement',
        ]);
        $event = $user->deactivationEvents()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('user_deactivation_employments', [
            'user_deactivation_id' => $event->id,
            'employment_status' => 'retired',
        ]);
        $this->assertDatabaseHas('user_deactivation_accesses', [
            'user_deactivation_id' => $event->id,
            'account_status' => 'retired',
        ]);
        $this->assertSame('retirement', $user->fresh()->deactivation_reason);
    }
}
