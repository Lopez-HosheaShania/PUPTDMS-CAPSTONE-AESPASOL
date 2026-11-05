<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('reserved_booking_period_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('reserved_booking_periods')
                ->restrictOnDelete();
            $table->foreignId('reserved_booking_period_slot_id')
                ->nullable()
                ->after('reserved_booking_period_id')
                ->constrained('reserved_booking_period_slots')
                ->restrictOnDelete();

            $table->unique(
                ['patient_id', 'reserved_booking_period_id'],
                'appointment_patient_reserved_period_unique'
            );
            $table->unique(
                'reserved_booking_period_slot_id',
                'appointment_reserved_period_slot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointment_patient_reserved_period_unique');
            $table->dropUnique('appointment_reserved_period_slot_unique');
            $table->dropConstrainedForeignId('reserved_booking_period_slot_id');
            $table->dropConstrainedForeignId('reserved_booking_period_id');
        });
    }
};
