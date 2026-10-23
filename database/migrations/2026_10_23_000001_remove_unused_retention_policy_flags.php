<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retention_policies', function (Blueprint $table) {
            $table->dropColumn(['archive_enabled', 'disposal_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('retention_policies', function (Blueprint $table) {
            $table->boolean('archive_enabled')->default(false);
            $table->boolean('disposal_enabled')->default(false);
        });
    }
};
