<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_auto_generated_password(): void
    {
        $admin = $this->createAdminUser();
        $original = $admin->fresh()->getAttributes();
        $count = User::count();

        foreach (['admin', 'dentist'] as $area) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has("{$area}.user_management.store"));
            $this->assertFalse(\Illuminate\Support\Facades\Route::has("{$area}.user_management.reset_password"));
            $this->actingAs($admin)->postJson("/{$area}/user-management", [
                'name' => 'Unwanted User', 'email' => 'unwanted@example.com',
                'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!',
            ])->assertStatus(405);
            $this->actingAs($admin)->postJson("/{$area}/user-management/{$admin->id}/reset-password", [
                'password' => 'ChangedPassword123!', 'password_confirmation' => 'ChangedPassword123!',
            ])->assertNotFound();
        }

        $this->assertSame($count, User::count());
        $this->assertSame($original, $admin->fresh()->getAttributes());
        $this->assertAuthenticatedAs($admin);
    }

    public function test_user_management_page_keeps_applicable_actions(): void
    {
        $this->withoutVite();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->withSession($this->adminSession($admin))
            ->get(route('admin.user_management'))->assertOk()
            ->assertSee('Edit User')->assertSee('Account Details')
            ->assertDontSee('addUserForm')->assertDontSee('resetForm')
            ->assertDontSee('Add New User')->assertDontSee('Reset Password');
    }

    public function test_existing_account_can_be_edited_reassigned_and_disabled_without_changing_password(): void
    {
        $admin = $this->createAdminUser();
        $dentistRole = $this->createRole('Dentist', 'dentist');
        $staffRole = $this->createRole('Clinic Staff', 'clinic_staff');
        $target = User::create([
            'name' => 'Existing Dentist', 'email' => 'existing@example.com',
            'password' => bcrypt('ExistingPassword123!'),
            'role_id' => $dentistRole->id, 'status' => 'active',
        ]);
        $password = $target->password;

        $this->actingAs($admin)->withSession($this->adminSession($admin))
            ->putJson(route('admin.user_management.update', $target), [
                'name' => 'Updated Dentist', 'email' => $target->email,
                'role_id' => $staffRole->id, 'status' => 'active',
                'admin_current_password' => 'Password123!',
            ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame($staffRole->id, $target->fresh()->role_id);
        $this->assertSame('Updated Dentist', $target->fresh()->name);
        $this->assertSame($password, $target->fresh()->password);

        $this->postJson(route('admin.user_management.toggle_status', $target))
            ->assertOk()->assertJsonPath('status', 'inactive');
        $this->assertSame($password, $target->fresh()->password);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_ajax_user_management_index_returns_rich_user_details_payload(): void
    {
        $admin = $this->createAdminUser();
        $patientRole = $this->createRole('Patient', 'patient');

        $user = User::create([
            'name' => 'Detail Target',
            'email' => 'detail-target@example.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $patientRole->id,
            'status' => 'active',
            'last_login_at' => '2026-07-18 09:30:00',
        ]);

        Patient::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '09998887777',
            'birthdate' => '2001-02-03',
            'gender' => 'Female',
            'password' => $user->password,
        ]);

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->get(route('admin.user_management', ['search' => 'detail-target@example.com']), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonPath('users.0.email', 'detail-target@example.com');
        $response->assertJsonPath('users.0.role_name', 'Patient');
        $response->assertJsonPath('users.0.details.id', $user->id);
        $response->assertJsonPath('users.0.details.phone', '09998887777');
        $response->assertJsonPath('users.0.details.gender', 'Female');
        $response->assertJsonPath('users.0.details.patient_profile', 'Linked');
        $response->assertJsonPath('users.0.details.status', 'Active');
        $response->assertJsonPath('users.0.details.role', 'Patient');
        $response->assertJsonPath('users.0.details.last_login_at', 'Jul 18, 2026 09:30 AM');
    }

    public function test_deleting_admin_like_custom_role_reassigns_users_to_admin_role(): void
    {
        $admin = $this->createAdminUser();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $customRole = $this->createRole('Clinic Staff', 'clinic_staff');

        $affectedUser = User::create([
            'name' => 'Clinic Staff User',
            'email' => 'clinic-staff@example.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $customRole->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->delete(route('admin.role_permissions.destroy_role', $customRole->id), [], [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('affected_users', 1);
        $response->assertJsonPath('fallback_role.slug', 'admin');

        $this->assertDatabaseMissing('roles', [
            'id' => $customRole->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $affectedUser->id,
            'role_id' => $adminRole->id,
        ]);
    }

    public function test_deleting_dentist_like_custom_role_reassigns_users_to_dentist_role(): void
    {
        $admin = $this->createAdminUser();
        $dentistRole = $this->createRole('Dentist', 'dentist');
        $customRole = $this->createRole('Senior Dentist', 'senior_dentist');

        $affectedUser = User::create([
            'name' => 'Senior Dentist User',
            'email' => 'senior-dentist@example.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $customRole->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->delete(route('admin.role_permissions.destroy_role', $customRole->id), [], [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonPath('fallback_role.slug', 'dentist');

        $this->assertDatabaseHas('users', [
            'id' => $affectedUser->id,
            'role_id' => $dentistRole->id,
        ]);
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $admin = $this->createAdminUser();
        $adminRole = $this->createRole('Super Admin', 'super_admin');

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession($admin))
            ->delete(route('admin.role_permissions.destroy_role', $adminRole->id));

        $response->assertRedirect(route('admin.role_permissions'));
        $response->assertSessionHas('error', 'Cannot delete the Super Admin role.');

        $this->assertDatabaseHas('roles', [
            'id' => $adminRole->id,
            'slug' => 'super_admin',
        ]);
    }

    private function createAdminUser(): User
    {
        $this->createRole('Dentist', 'dentist');
        $this->createRole('Patient', 'patient');

        $adminRole = $this->createRole('Admin', 'admin');

        return User::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
    }

    private function createRole(string $name, string $slug): Role
    {
        return Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );
    }

    private function adminSession(User $admin): array
    {
        return [
            'role' => 'admin',
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
        ];
    }
}
