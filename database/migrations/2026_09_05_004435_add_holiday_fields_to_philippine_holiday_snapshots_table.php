<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'philippine_holiday_snapshots',
            function (Blueprint $table) {
                $table
                    ->unsignedSmallInteger('year')
                    ->unique()
                    ->after('id');

                $table
                    ->json('holidays')
                    ->after('year');

                $table
                    ->string('source', 50)
                    ->default('ph_holidays_mcp')
                    ->after('holidays');

                $table
                    ->timestamp('fetched_at')
                    ->nullable()
                    ->after('source');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'philippine_holiday_snapshots',
            function (Blueprint $table) {
                $table->dropUnique([
                    'year',
                ]);

                $table->dropColumn([
                    'year',
                    'holidays',
                    'source',
                    'fetched_at',
                ]);
            }
        );
    }
};
