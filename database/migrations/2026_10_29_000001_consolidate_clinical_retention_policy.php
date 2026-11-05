<?php

use App\Helpers\AuditLogger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Fresh installations seed the current categories in the original migration.
            if (DB::table('retention_policies')->where('category', 'clinical_records')->exists()) {
                return;
            }

            $sources = DB::table('retention_policies')
                ->whereIn('category', ['appointments', 'medical_histories', 'dental_histories'])
                ->lockForUpdate()->get()->keyBy('category');

            if ($sources->count() !== 3) {
                throw new RuntimeException('Clinical retention cannot be consolidated: the three existing policies must be present. No saved policies were changed.');
            }

            $periods = $sources->map(function ($policy) {
                // Calendar months/years cannot be equated to a fixed number of days.
                return match ($policy->unit) {
                    'years' => 'months:'.((int) $policy->duration * 12),
                    'months' => 'months:'.(int) $policy->duration,
                    'weeks' => 'days:'.((int) $policy->duration * 7),
                    'days' => 'days:'.(int) $policy->duration,
                    default => throw new RuntimeException('Unsupported retention unit; no saved policies were changed.'),
                };
            });

            if ($periods->unique()->count() !== 1) {
                throw new RuntimeException('Clinical retention periods differ. Have the clinic select and align the appointments, medical_histories, and dental_histories periods before rerunning this migration. No saved policies were changed.');
            }

            $source = $sources->get('appointments');
            $values = [
                'category' => 'clinical_records', 'duration' => $source->duration,
                'unit' => $source->unit, 'status' => 'provisional', 'version' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ];
            $id = DB::table('retention_policies')->insertGetId($values);
            AuditLogger::log('retention_policy_created', 'data_retention', json_encode([
                'policy_id' => $id, 'category' => 'clinical_records', 'previous' => null,
                'updated' => $values, 'source_policy_ids' => $sources->pluck('id')->all(),
                'outcome' => 'success', 'source' => 'clinical_retention_consolidation',
            ], JSON_THROW_ON_ERROR));
            // Preserve legacy policies and their normalized approval records as history.
            // They are excluded from current controls and cannot be updated through the API.
        });
    }

    public function down(): void
    {
        // This data-only migration keeps saved policies on rollback. Removing a
        // category from configuration retires its controls without deleting history.
    }
};
