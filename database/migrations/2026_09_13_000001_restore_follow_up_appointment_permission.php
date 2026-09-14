<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSION = [
        'name' => 'Create Follow-Up Appointments',
        'slug' => 'create_follow_up_appointments',
        'module' => 'Appointments',
    ];

    private const ROLE_SLUGS = [
        'admin',
        'dentist',
    ];

    public function up(): void
    {
        $permission = Permission::updateOrCreate(
            ['slug' => self::PERMISSION['slug']],
            self::PERMISSION
        );

        Role::whereIn('slug', self::ROLE_SLUGS)
            ->get()
            ->each(function (Role $role) use ($permission): void {
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            });
    }

    public function down(): void
    {
        $permission = Permission::where('slug', self::PERMISSION['slug'])->first();

        if (! $permission) {
            return;
        }

        Role::whereIn('slug', self::ROLE_SLUGS)
            ->get()
            ->each(function (Role $role) use ($permission): void {
                $role->permissions()->detach($permission->id);
            });
    }
};
