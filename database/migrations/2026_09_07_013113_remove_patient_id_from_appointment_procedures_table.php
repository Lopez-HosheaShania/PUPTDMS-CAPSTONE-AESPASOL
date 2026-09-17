<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_procedures', function (Blueprint $table) {
            $table->foreignId('patient_id')
                ->nullable()
                ->after('appointment_id')
                ->constrained('patients')
                ->cascadeOnDelete();
        });
    }
};