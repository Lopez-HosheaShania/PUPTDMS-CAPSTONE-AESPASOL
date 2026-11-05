<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\DefaultRolePermissions;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DefaultRolePermissions::all() as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
