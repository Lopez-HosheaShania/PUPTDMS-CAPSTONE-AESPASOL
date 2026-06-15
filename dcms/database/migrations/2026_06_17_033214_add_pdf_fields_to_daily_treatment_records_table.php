<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_treatment_records', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_treatment_records', 'time_in')) {
                $table->time('time_in')->nullable()->after('treatment_date');
            }

            if (!Schema::hasColumn('daily_treatment_records', 'time_out')) {
                $table->time('time_out')->nullable()->after('time_in');
            }

            if (!Schema::hasColumn('daily_treatment_records', 'age')) {
                $table->unsignedTinyInteger('age')->nullable()->after('program_code');
            }

            if (!Schema::hasColumn('daily_treatment_records', 'is_senior')) {
                $table->boolean('is_senior')->default(false)->after('gender');
            }

            if (!Schema::hasColumn('daily_treatment_records', 'is_pwd')) {
                $table->boolean('is_pwd')->default(false)->after('is_senior');
            }

            if (!Schema::hasColumn('daily_treatment_records', 'case_type')) {
                $table->enum('case_type', ['Emergency', 'Non-Emergency'])
                    ->default('Non-Emergency')
                    ->after('is_pwd');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_treatment_records', function (Blueprint $table) {
            if (Schema::hasColumn('daily_treatment_records', 'case_type')) {
                $table->dropColumn('case_type');
            }

            if (Schema::hasColumn('daily_treatment_records', 'is_pwd')) {
                $table->dropColumn('is_pwd');
            }

            if (Schema::hasColumn('daily_treatment_records', 'is_senior')) {
                $table->dropColumn('is_senior');
            }

            if (Schema::hasColumn('daily_treatment_records', 'age')) {
                $table->dropColumn('age');
            }

            if (Schema::hasColumn('daily_treatment_records', 'time_out')) {
                $table->dropColumn('time_out');
            }

            if (Schema::hasColumn('daily_treatment_records', 'time_in')) {
                $table->dropColumn('time_in');
            }
        });
    }
};