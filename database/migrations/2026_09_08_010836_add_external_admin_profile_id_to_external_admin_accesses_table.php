<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->foreignId('external_admin_profile_id')
                ->nullable()
                ->after('id')
                ->constrained('external_admin_profiles')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->dropForeign(['external_admin_profile_id']);
            $table->dropColumn('external_admin_profile_id');
        });
    }
};