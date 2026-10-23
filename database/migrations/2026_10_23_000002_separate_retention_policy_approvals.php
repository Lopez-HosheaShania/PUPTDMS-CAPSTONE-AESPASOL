<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = ['approved_by', 'approved_at', 'approval_reference', 'activated_by', 'activated_at'];

    private function approvalFields(Blueprint $table): void
    {
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('approved_at')->nullable();
        $table->text('approval_reference')->nullable();
        $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('activated_at')->nullable();
    }

    public function up(): void
    {
        Schema::create('retention_policy_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retention_policy_id')->unique()->constrained('retention_policies')->cascadeOnDelete();
            $this->approvalFields($table);
        });

        DB::transaction(function () {
            DB::table('retention_policies')->orderBy('id')->chunkById(500, function ($policies) {
                foreach ($policies as $policy) {
                    $values = array_intersect_key((array) $policy, array_flip(self::FIELDS));
                    if (count(array_filter($values, fn ($value) => $value !== null)) === 0) {
                        continue;
                    }
                    DB::table('retention_policy_approvals')->insert(array_merge(
                        ['retention_policy_id' => $policy->id], $values));
                    $saved = DB::table('retention_policy_approvals')->where('retention_policy_id', $policy->id)->first();
                    foreach ($values as $field => $value) {
                        if ($saved->{$field} !== $value) {
                            throw new RuntimeException('Approval copy verification failed; original columns retained.');
                        }
                    }
                }
            });
        });

        // SQLite rebuilds the parent for foreign-key changes, which can cascade
        // to child rows. Keep the verified copy available across that rebuild.
        $approvals = DB::table('retention_policy_approvals')->get();
        Schema::table('retention_policies', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['activated_by']);
            $table->dropColumn(self::FIELDS);
        });
        foreach ($approvals as $approval) {
            DB::table('retention_policy_approvals')->updateOrInsert(
                ['retention_policy_id' => $approval->retention_policy_id], (array) $approval);
        }
    }

    public function down(): void
    {
        $approvals = DB::table('retention_policy_approvals')->get();
        Schema::table('retention_policies', fn (Blueprint $table) => $this->approvalFields($table));
        foreach ($approvals as $approval) {
            $values = array_intersect_key((array) $approval, array_flip(self::FIELDS));
            DB::table('retention_policies')->where('id', $approval->retention_policy_id)->update($values);
        }
        Schema::drop('retention_policy_approvals');
    }
};
