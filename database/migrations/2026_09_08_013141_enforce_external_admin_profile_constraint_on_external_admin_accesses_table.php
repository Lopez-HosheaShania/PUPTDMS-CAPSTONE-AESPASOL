<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->unsignedBigInteger('external_admin_profile_id')
                ->nullable(false)
                ->change();

            $table->unique(
                'external_admin_profile_id',
                'external_admin_accesses_profile_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->dropUnique('external_admin_accesses_profile_unique');

            $table->unsignedBigInteger('external_admin_profile_id')
                ->nullable()
                ->change();
        });
    }
};