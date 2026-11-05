<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::updateOrCreate(
            ['slug' => 'manage_walk_in_patients'],
            [
                'name' => 'Manage Walk-in Patients',
                'module' => 'Appointments',
            ]
        );

        Role::query()
            ->whereIn('slug', ['admin', 'dentist'])
            ->each(function (Role $role) use ($permission): void {
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            });
    }

    public function down(): void
    {
        $permission = Permission::where('slug', 'manage_walk_in_patients')->first();

        if (! $permission) {
            return;
        }

        $permission->roles()->detach();
        $permission->delete();
    }
};
