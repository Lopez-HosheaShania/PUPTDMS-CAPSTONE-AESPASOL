<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserved_booking_periods', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->date('reserved_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('booking_mode', ['timeslot', 'date_only']);
            $table->enum('target_patient_type', [
                'student',
                'faculty',
                'administrative',
                'guest',
            ]);
            $table->string('program_code', 50)->nullable();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->string('section', 50)->nullable();
            $table->unsignedSmallInteger('max_capacity');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['reserved_date', 'is_active', 'start_time', 'end_time'],
                'reserved_period_date_active_times_idx'
            );
            $table->index(
                ['target_patient_type', 'program_code', 'year_level', 'section'],
                'reserved_period_target_idx'
            );
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE reserved_booking_periods
                 ADD CONSTRAINT reserved_period_valid_time_chk
                 CHECK (start_time < end_time)'
            );

            DB::statement(
                'ALTER TABLE reserved_booking_periods
                 ADD CONSTRAINT reserved_period_capacity_chk
                 CHECK (max_capacity BETWEEN 1 AND 500)'
            );

            DB::statement(
                "ALTER TABLE reserved_booking_periods
                 ADD CONSTRAINT reserved_period_target_fields_chk
                 CHECK (
                    (target_patient_type = 'student'
                        AND program_code IS NOT NULL
                        AND year_level IS NOT NULL
                        AND section IS NOT NULL)
                    OR
                    (target_patient_type <> 'student'
                        AND program_code IS NULL
                        AND year_level IS NULL
                        AND section IS NULL)
                 )"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reserved_booking_periods');
    }
};
