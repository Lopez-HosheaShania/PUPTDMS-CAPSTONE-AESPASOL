<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $dentistRole = Role::where('slug', 'dentist')->first();

        if (! $dentistRole) {
            return;
        }

        $permission = Permission::firstOrCreate(
            ['slug' => 'view_dental_records'],
            ['name' => 'View Dental Records', 'module' => 'Dental Records']
        );

        $dentistRole->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function down(): void
    {
        $dentistRole = Role::where('slug', 'dentist')->first();
        $permission = Permission::where('slug', 'view_dental_records')->first();

        if ($dentistRole && $permission) {
            $dentistRole->permissions()->detach($permission->id);
        }
    }
};
