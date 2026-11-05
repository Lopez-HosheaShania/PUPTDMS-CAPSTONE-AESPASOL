<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reserved_booking_period_slots')->update(['max_capacity' => 1]);

        DB::table('reserved_booking_period_slots')
            ->select('reserved_booking_period_id', DB::raw('COUNT(*) AS slot_count'))
            ->groupBy('reserved_booking_period_id')
            ->get()
            ->each(function ($period): void {
                DB::table('reserved_booking_periods')
                    ->where('id', $period->reserved_booking_period_id)
                    ->update(['max_capacity' => $period->slot_count]);
            });

        if (DB::getDriverName() === 'mysql') {
            $this->dropCapacityConstraint();
            DB::statement(
                'ALTER TABLE reserved_booking_period_slots
                 ADD CONSTRAINT reserved_period_slot_capacity_chk
                 CHECK (max_capacity = 1)'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->dropCapacityConstraint();
            DB::statement(
                'ALTER TABLE reserved_booking_period_slots
                 ADD CONSTRAINT reserved_period_slot_capacity_chk
                 CHECK (max_capacity BETWEEN 1 AND 500)'
            );
        }
    }

    private function dropCapacityConstraint(): void
    {
        $version = strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version);
        $dropClause = str_contains($version, 'mariadb')
            ? 'DROP CONSTRAINT'
            : 'DROP CHECK';

        DB::statement(
            "ALTER TABLE reserved_booking_period_slots
             {$dropClause} reserved_period_slot_capacity_chk"
        );
    }
};
