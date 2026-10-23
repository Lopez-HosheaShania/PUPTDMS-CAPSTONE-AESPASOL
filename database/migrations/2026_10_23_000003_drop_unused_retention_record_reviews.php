<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('retention_record_reviews');
    }

    public function down(): void
    {
        // Rollback restores the retired schema, not previously deleted data.
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
    }
};
