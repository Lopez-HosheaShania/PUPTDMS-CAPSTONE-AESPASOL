<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateDates = DB::table('reserved_booking_periods')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select('reserved_date')
            ->groupBy('reserved_date')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('reserved_date');

        if ($duplicateDates->isNotEmpty()) {
            throw new \RuntimeException(
                'Cannot enforce one active reserved period per date. Resolve duplicates for: '
                .$duplicateDates->implode(', ')
            );
        }

        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->date('active_reserved_date')
                ->nullable()
                ->after('reserved_date');
        });

        DB::table('reserved_booking_periods')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->update([
                'active_reserved_date' => DB::raw('reserved_date'),
            ]);

        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->unique(
                'active_reserved_date',
                'reserved_period_active_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reserved_booking_periods', function (Blueprint $table) {
            $table->dropUnique('reserved_period_active_date_unique');
            $table->dropColumn('active_reserved_date');
        });
    }
};
