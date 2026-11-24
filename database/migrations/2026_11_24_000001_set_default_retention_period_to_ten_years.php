<?php

use App\Helpers\AuditLogger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categories = array_keys(config('retention.categories', []));

        DB::transaction(function () use ($categories) {
            DB::table('retention_policies')
                ->whereIn('category', $categories)
                ->where('duration', 1)
                ->where('unit', 'years')
                ->where('status', 'provisional')
                ->whereNull('updated_by')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->each(function ($policy) {
                    $updated = [
                        'duration' => 10,
                        'version' => $policy->version + 1,
                        'updated_at' => now(),
                    ];

                    DB::table('retention_policies')->where('id', $policy->id)->update($updated);

                    AuditLogger::log('retention_period_modified', 'data_retention', json_encode([
                        'policy_id' => $policy->id,
                        'category' => $policy->category,
                        'previous' => (array) $policy,
                        'updated' => array_merge((array) $policy, $updated),
                        'outcome' => 'success',
                        'source' => 'default_retention_period_update',
                    ], JSON_THROW_ON_ERROR));
                });
        });
    }

    public function down(): void
    {
        // Keep the policy changes and audit history if this migration is rolled back.
    }
};
