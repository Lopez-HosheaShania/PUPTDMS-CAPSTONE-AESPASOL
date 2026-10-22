<?php

use App\Helpers\AuditLogger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['audit_logs', 'ai_service_logs'] as $category) {
            DB::transaction(function () use ($category) {
                // Fresh installations may already seed these from the category map.
                if (DB::table('retention_policies')->where('category', $category)->exists()) {
                    return;
                }

                $defaults = ['duration' => 1, 'unit' => 'years',
                    'start_event' => 'reviewed_lifecycle_date', 'status' => 'provisional',
                    'archive_enabled' => false, 'disposal_enabled' => false, 'version' => 1];
                $id = DB::table('retention_policies')->insertGetId(array_merge($defaults, [
                    'category' => $category, 'created_at' => now(), 'updated_at' => now(),
                ]));
                AuditLogger::log('retention_policy_created', 'data_retention', json_encode([
                    'policy_id' => $id, 'category' => $category, 'previous' => null,
                    'updated' => $defaults, 'outcome' => 'success', 'source' => 'log_retention_configuration',
                ], JSON_THROW_ON_ERROR));
            });
        }
    }

    public function down(): void
    {
        DB::table('retention_policies')->whereIn('category', ['audit_logs', 'ai_service_logs'])->delete();
    }
};
