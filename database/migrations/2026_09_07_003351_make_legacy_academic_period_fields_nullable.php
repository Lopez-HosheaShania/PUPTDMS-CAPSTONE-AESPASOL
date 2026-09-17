<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->string('academic_year')->nullable()->change();
            $table->string('semester', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->string('academic_year')->nullable(false)->change();
            $table->string('semester', 50)->nullable(false)->change();
        });
    }
};