<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retention_policies', function (Blueprint $table) {
            $table->dropColumn('start_event');
        });
    }

    public function down(): void
    {
        Schema::table('retention_policies', function (Blueprint $table) {
            $table->string('start_event')->default('reviewed_lifecycle_date');
        });
    }
};
