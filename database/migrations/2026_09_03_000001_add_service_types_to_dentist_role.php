<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SERVICE_TYPE_PERMISSION_SLUGS = [
        'view_service_type',
        'create_service_type',
        'delete_service_type',
        'update_default_service_type',
    ];

    public function up(): void
    {
        $dentistRole = Role::where('slug', 'dentist')->first();

        if (! $dentistRole) {
            return;
        }

        $permissionIds = Permission::whereIn('slug', self::SERVICE_TYPE_PERMISSION_SLUGS)->pluck('id');

        $dentistRole->permissions()->syncWithoutDetaching($permissionIds);
    }

    public function down(): void
    {
        $dentistRole = Role::where('slug', 'dentist')->first();

        if (! $dentistRole) {
            return;
        }

        $permissionIds = Permission::whereIn('slug', self::SERVICE_TYPE_PERMISSION_SLUGS)->pluck('id');

        $dentistRole->permissions()->detach($permissionIds);
    }
};
