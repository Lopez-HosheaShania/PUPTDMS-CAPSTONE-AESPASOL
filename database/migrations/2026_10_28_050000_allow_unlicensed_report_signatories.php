<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_signatories', function (Blueprint $table) {
            $table->string('license_number', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('report_signatories')->whereNull('license_number')->update(['license_number' => '']);
        Schema::table('report_signatories', function (Blueprint $table) {
            $table->string('license_number', 60)->nullable(false)->change();
        });
    }
};
