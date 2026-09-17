<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['daily_treatment_signatures', 'daily_treatment_patients', 'daily_treatment_records', 'dental_service_records'];

    public function up(): void
    {
        // Check every table before dropping any: manual records must never be silently lost.
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Cannot remove {$table}: it contains records that must be preserved first.");
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        foreach ([
            '2026_02_17_160327_create_daily_treatment_records_table.php',
            '2026_06_17_033214_add_pdf_fields_to_daily_treatment_records_table.php',
            '2026_09_07_110000_separate_daily_treatment_details.php',
            '2026_02_17_164438_create_dental_service_records_table.php',
        ] as $migration) {
            (require __DIR__.'/'.$migration)->up();
        }
    }
};
