<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('document_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('document_requests', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('approved_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('document_requests', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            if (Schema::hasColumn('document_requests', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            if (Schema::hasColumn('document_requests', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('document_requests', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};