<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Support\DefaultRolePermissions;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDefaultPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbac_seeders_create_and_attach_required_admin_permissions_idempotently(): void
    {
        $this->seedRbac();

        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $requiredPermissions = collect(DefaultRolePermissions::requiredAdminPermissionDefinitions())->keyBy('slug');

        $this->assertSame(['admin', 'dentist', 'patient'], Role::query()->orderBy('id')->pluck('slug')->all());

        foreach ($requiredPermissions as $slug => $permission) {
            $this->assertDatabaseHas('permissions', $permission);
            $this->assertTrue(
                $adminRole->permissions()->where('slug', $slug)->exists(),
                "Expected admin role to contain [{$slug}] permission."
            );
        }

        $unrelatedAdminPermission = Permission::updateOrCreate(
            ['slug' => 'unrelated_admin_permission'],
            ['name' => 'Unrelated Admin Permission', 'module' => 'Testing']
        );

        $customRolePermission = Permission::updateOrCreate(
            ['slug' => 'custom_role_permission'],
            ['name' => 'Custom Role Permission', 'module' => 'Testing']
        );

        $customRole = Role::create([
            'name' => 'Clinic Staff',
            'slug' => 'clinic_staff',
        ]);

        $adminRole->permissions()->syncWithoutDetaching([$unrelatedAdminPermission->id]);
        $customRole->permissions()->syncWithoutDetaching([$customRolePermission->id]);

        Permission::where('slug', 'view_system_logs')->update([
            'name' => 'Stale Log Permission',
            'module' => 'Legacy Module',
        ]);

        $this->seedRbac();

        $adminRole->refresh();
        $customRole->refresh();

        $this->assertDatabaseHas('permissions', [
            'slug' => 'view_system_logs',
            'name' => 'View System Logs',
            'module' => 'System Logs',
        ]);

        foreach ($requiredPermissions->keys() as $slug) {
            $permissionId = Permission::where('slug', $slug)->value('id');

            $this->assertNotNull($permissionId);
            $this->assertSame(1, DB::table('role_permissions')
                ->where('role_id', $adminRole->id)
                ->where('permission_id', $permissionId)
                ->count());
        }

        $this->assertSame(1, Permission::where('slug', 'view_system_logs')->count());
        $this->assertSame(1, Permission::where('slug', 'view_roles_permissions')->count());
        $this->assertSame(1, Permission::where('slug', 'view_cms_integration')->count());
        $this->assertSame(1, Permission::where('slug', 'view_faculty_integration')->count());
        $this->assertSame(1, Permission::where('slug', 'view_ai_reports')->count());
        $this->assertSame(1, Permission::where('slug', 'create_ai_generative_reports')->count());

        $this->assertTrue(
            $adminRole->permissions()->where('slug', 'unrelated_admin_permission')->exists(),
            'Expected unrelated admin permissions to remain attached after reseeding.'
        );

        $this->assertTrue(
            $customRole->permissions()->where('slug', 'custom_role_permission')->exists(),
            'Expected custom role permissions to remain untouched after reseeding.'
        );
    }

    private function seedRbac(): void
    {
        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
