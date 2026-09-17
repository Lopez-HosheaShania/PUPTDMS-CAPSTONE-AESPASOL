<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['academic_term_id']);
        });

        Schema::table('academic_periods', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')
                ->nullable(false)
                ->change();

            $table->unsignedBigInteger('academic_term_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('academic_periods', function (Blueprint $table) {
            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->restrictOnDelete();

            $table->foreign('academic_term_id')
                ->references('id')
                ->on('academic_terms')
                ->restrictOnDelete();

            $table->unique(
                ['academic_year_id', 'academic_term_id'],
                'academic_period_year_term_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->dropUnique('academic_period_year_term_unique');

            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['academic_term_id']);
        });

        Schema::table('academic_periods', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')
                ->nullable()
                ->change();

            $table->unsignedBigInteger('academic_term_id')
                ->nullable()
                ->change();
        });

        Schema::table('academic_periods', function (Blueprint $table) {
            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->nullOnDelete();

            $table->foreign('academic_term_id')
                ->references('id')
                ->on('academic_terms')
                ->nullOnDelete();
        });
    }
};