<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('slug', 'dentist')->first();
        $permission = Permission::where('slug', 'manage_existing_records')->first();

        if (! $role || ! $permission) {
            return;
        }

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function down(): void
    {
        $role = Role::where('slug', 'dentist')->first();
        $permission = Permission::where('slug', 'manage_existing_records')->first();

        if (! $role || ! $permission) {
            return;
        }

        $role->permissions()->detach($permission->id);
    }
};
