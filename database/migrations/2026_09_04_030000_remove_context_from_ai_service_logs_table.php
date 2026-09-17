<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_service_logs', function (Blueprint $table) {
            $table->dropColumn('context');
            $table->renameColumn('happened_at', 'created_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_service_logs', function (Blueprint $table) {
            $table->renameColumn('created_at', 'happened_at');
            $table->json('context')->nullable()->after('message');
        });
    }
};
