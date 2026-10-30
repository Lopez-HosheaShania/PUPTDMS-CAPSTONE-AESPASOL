<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retention_policies', function (Blueprint $table) {
            $table->string('counting_rule', 64)->nullable();
        });
        Schema::table('retention_policy_approvals', function (Blueprint $table) {
            $table->date('effective_from')->nullable();
            $table->unsignedInteger('policy_version')->nullable();
        });
        Schema::table('document_request_states', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable();
        });
        Schema::create('appointment_closures', function (Blueprint $table) {
            $table->foreignId('appointment_id')->primary()->constrained('appointments')->cascadeOnDelete();
            $table->timestamp('closed_at')->nullable();
        });
        Schema::create('patient_record_closures', function (Blueprint $table) {
            $table->foreignId('patient_id')->primary()->constrained('patients')->cascadeOnDelete();
            $table->timestamp('closed_at');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 2000);
        });

        // Only an existing documented approval date is a reliable historical closure.
        // Unknown rejection/cancellation/record-closure dates remain unknown.
        DB::table('document_request_reviews')->whereNotNull('approved_at')->orderBy('id')
            ->chunkById(500, function ($reviews) {
                foreach ($reviews as $review) {
                    DB::table('document_request_states')->where('document_request_id', $review->document_request_id)
                        ->where('status', 'approved')->update(['closed_at' => $review->approved_at]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_record_closures');
        Schema::dropIfExists('appointment_closures');
        Schema::table('document_request_states', fn (Blueprint $table) => $table->dropColumn('closed_at'));
        Schema::table('retention_policy_approvals', fn (Blueprint $table) => $table->dropColumn(['effective_from', 'policy_version']));
        Schema::table('retention_policies', fn (Blueprint $table) => $table->dropColumn('counting_rule'));
    }
};
