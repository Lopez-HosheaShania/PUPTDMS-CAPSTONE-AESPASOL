<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::updateOrCreate(
            ['slug' => 'manage_clinic_schedule'],
            [
                'name' => 'Manage Clinic Schedule',
                'module' => 'Appointments',
            ]
        );

        Role::whereIn('slug', ['admin', 'dentist'])
            ->get()
            ->each(function (Role $role) use ($permission): void {
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            });
    }

    public function down(): void
    {
        $permission = Permission::where('slug', 'manage_clinic_schedule')->first();

        if (! $permission) {
            return;
        }

        $permission->roles()->detach();
        $permission->delete();
    }
};
