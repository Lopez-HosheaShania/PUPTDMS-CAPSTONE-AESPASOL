<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->json('allowed_services')->nullable()->after('target_patient_type');
        });
    }

    public function down(): void
    {
        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->dropColumn('allowed_services');
        });
    }
};
