<?php

use App\Helpers\AuditLogger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('category')->unique();
            $table->unsignedInteger('duration')->default(1);
            $table->string('unit')->default('years');
            $table->string('start_event')->default('reviewed_lifecycle_date');
            $table->string('status')->default('provisional');
            $table->boolean('archive_enabled')->default(false);
            $table->boolean('disposal_enabled')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_reference')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        Schema::create('retention_record_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->unsignedBigInteger('record_id');
            $table->timestamp('retention_starts_at')->nullable();
            $table->boolean('legal_hold')->default(true);
            $table->boolean('preservation_required')->default(true);
            $table->boolean('dependencies_cleared')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_reference')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('policy_version')->nullable();
            $table->timestamps();
            $table->unique(['category', 'record_id']);
        });
        foreach (array_keys(config('retention.categories')) as $category) {
            DB::transaction(function () use ($category) {
                $id = DB::table('retention_policies')->insertGetId([
                    'category' => $category, 'duration' => 1, 'unit' => 'years',
                    'start_event' => 'reviewed_lifecycle_date', 'status' => 'provisional',
                    'archive_enabled' => false, 'disposal_enabled' => false,
                    'version' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
                AuditLogger::log('retention_policy_created', 'data_retention', json_encode([
                    'policy_id' => $id, 'category' => $category, 'previous' => null,
                    'updated' => ['duration' => 1, 'unit' => 'years', 'status' => 'provisional', 'version' => 1],
                    'outcome' => 'success', 'source' => 'initial_configuration',
                ], JSON_THROW_ON_ERROR));
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_record_reviews');
        Schema::dropIfExists('retention_policies');
    }
};
