<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserved_booking_period_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserved_booking_period_id')
                ->constrained('reserved_booking_periods')
                ->cascadeOnDelete();
            $table->time('slot_time');
            $table->unsignedSmallInteger('max_capacity')->default(1);
            $table->timestamps();

            $table->unique(
                ['reserved_booking_period_id', 'slot_time'],
                'reserved_period_slot_unique_time'
            );
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE reserved_booking_period_slots
                 ADD CONSTRAINT reserved_period_slot_capacity_chk
                 CHECK (max_capacity BETWEEN 1 AND 500)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reserved_booking_period_slots');
    }
};
