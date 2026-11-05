<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\DefaultRolePermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $adminRole = Role::query()->where('slug', 'admin')->first();

        if (! $adminRole) {
            return;
        }

        foreach (DefaultRolePermissions::requiredAdminPermissionDefinitions() as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $permissionIds = Permission::query()
            ->whereIn('slug', DefaultRolePermissions::defaultsForRole('admin'))
            ->pluck('id')
            ->all();

        $adminRole->permissions()->syncWithoutDetaching($permissionIds);
    }

    public function down(): void
    {
        // Intentionally left as a no-op because this migration codifies
        // the current Admin default permission matrix.
    }
};
