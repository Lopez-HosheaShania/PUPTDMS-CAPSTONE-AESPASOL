<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\DefaultRolePermissions;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('slug', 'admin')->firstOrFail();
        $dentist = Role::where('slug', 'dentist')->firstOrFail();
        $patient = Role::where('slug', 'patient')->firstOrFail();

        $roleMappings = [
            ['role' => $admin, 'permissions' => DefaultRolePermissions::defaultsForRole('admin')],
            ['role' => $dentist, 'permissions' => DefaultRolePermissions::defaultsForRole('dentist')],
            ['role' => $patient, 'permissions' => DefaultRolePermissions::defaultsForRole('patient')],
        ];

        foreach ($roleMappings as $roleMapping) {
            $permissionIds = Permission::whereIn('slug', $roleMapping['permissions'])->pluck('id')->all();

            $roleMapping['role']->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
