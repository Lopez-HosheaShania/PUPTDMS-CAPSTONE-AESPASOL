<?php

use App\Helpers\AuditLogger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $changes = [
                'patients' => ['patient_record_closed', 'linked_account_deactivated'],
                'document_requests' => ['document_request_closed', 'document_request_decided'],
            ];
            foreach ($changes as $category => [$oldRule, $newRule]) {
                $policy = DB::table('retention_policies')->where('category', $category)
                    ->where('counting_rule', $oldRule)->lockForUpdate()->first();
                if (! $policy) continue;

                $approval = DB::table('retention_policy_approvals')->where('retention_policy_id', $policy->id)->first();
                $updated = ['counting_rule' => $newRule, 'status' => 'provisional',
                    'version' => $policy->version + 1, 'updated_by' => null, 'updated_at' => now()];
                DB::table('retention_policies')->where('id', $policy->id)->update($updated);
                DB::table('retention_policy_approvals')->where('retention_policy_id', $policy->id)->delete();
                AuditLogger::log('retention_counting_basis_changed', 'data_retention', json_encode([
                    'policy_id' => $policy->id, 'category' => $category,
                    'previous' => ['policy' => (array) $policy, 'approval' => $approval ? (array) $approval : null],
                    'updated' => $updated, 'outcome' => 'success',
                    'source' => 'existing_workflow_retention_basis',
                ], JSON_THROW_ON_ERROR));
            }
        });
        // Historical request states, review dates, and patient closure evidence
        // are retained. There is no schema change or inferred historical date.
    }

    public function down(): void
    {
        // Restoring old code must not reactivate superseded approvals or rewrite
        // saved policy history. This data migration intentionally keeps its audit.
    }
};
