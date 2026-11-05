<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->string('external_admin_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('external_admin_accesses')
            ->whereNull('external_admin_id')
            ->orderBy('id')
            ->chunkById(100, function ($accesses) {
                foreach ($accesses as $access) {
                    $externalAdminId = DB::table('external_admin_profiles')
                        ->where('id', $access->external_admin_profile_id)
                        ->value('external_admin_id');

                    if ($externalAdminId !== null) {
                        DB::table('external_admin_accesses')
                            ->where('id', $access->id)
                            ->update([
                                'external_admin_id' => $externalAdminId,
                            ]);
                    }
                }
            });

        Schema::table('external_admin_accesses', function (Blueprint $table) {
            $table->string('external_admin_id')
                ->nullable(false)
                ->change();
        });
    }
};