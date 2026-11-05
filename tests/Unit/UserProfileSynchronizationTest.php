<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserProfileSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_is_created_and_kept_in_sync_with_legacy_user_fields(): void
    {
        $role = Role::create([
            'name' => 'Patient',
            'slug' => 'patient',
        ]);

        $user = User::create([
            'name' => 'Jane M. Doe',
            'first_name' => 'Jane',
            'middle_name' => 'M.',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone' => '09171234567',
            'birthdate' => '2000-01-01',
            'gender' => 'Female',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '09171234567',
        ]);

        $user->update(['phone' => '09981234567']);

        $this->assertSame('09981234567', $user->fresh()->profile->phone);
    }

    public function test_users_table_keeps_only_account_fields_after_profile_data_is_moved(): void
    {
        $this->assertFalse(Schema::hasColumns('users', [
            'first_name',
            'middle_name',
            'last_name',
            'suffix_name',
            'phone',
            'birthdate',
            'gender',
        ]));
    }
}
