<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->unsignedSmallInteger('timeslot_duration_minutes')
                ->nullable()
                ->after('booking_mode');
        });

        DB::table('reserved_booking_periods')
            ->where('booking_mode', 'timeslot')
            ->update(['timeslot_duration_minutes' => 30]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE reserved_booking_periods
                 ADD CONSTRAINT reserved_period_duration_chk
                 CHECK (
                    (booking_mode = 'timeslot'
                        AND timeslot_duration_minutes BETWEEN 5 AND 240
                        AND MOD(timeslot_duration_minutes, 5) = 0)
                    OR
                    (booking_mode = 'date_only' AND timeslot_duration_minutes IS NULL)
                 )"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $version = strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version);
            $dropClause = str_contains($version, 'mariadb') ? 'DROP CONSTRAINT' : 'DROP CHECK';

            DB::statement(
                "ALTER TABLE reserved_booking_periods
                 {$dropClause} reserved_period_duration_chk"
            );
        }

        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->dropColumn('timeslot_duration_minutes');
        });
    }
};
