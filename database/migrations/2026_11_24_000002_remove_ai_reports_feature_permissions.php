<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', ['view_ai_reports', 'create_ai_generative_reports'])
                ->pluck('id');

            if ($permissionIds->isNotEmpty()) {
                DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            }

            DB::table('system_settings')->where('key', 'ai_reports_enabled')->delete();
        });
    }

    public function down(): void
    {
        // Removed report permissions and settings are intentionally not restored.
    }
};
