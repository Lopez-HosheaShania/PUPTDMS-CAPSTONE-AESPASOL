<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('slug', 'admin')->first();
        $permission = Permission::where('slug', 'manage_walk_in_patients')->first();

        if (! $role || ! $permission) {
            return;
        }

        $role->permissions()->detach($permission->id);
    }

    public function down(): void
    {
        $role = Role::where('slug', 'admin')->first();
        $permission = Permission::where('slug', 'manage_walk_in_patients')->first();

        if (! $role || ! $permission) {
            return;
        }

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }
};
