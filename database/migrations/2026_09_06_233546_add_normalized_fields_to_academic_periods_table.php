<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('id')
                ->constrained('academic_years')
                ->nullOnDelete();

            $table->foreignId('academic_term_id')
                ->nullable()
                ->after('academic_year_id')
                ->constrained('academic_terms')
                ->nullOnDelete();
        });
    }
    
    public function down(): void
    {
        Schema::table('academic_periods', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['academic_term_id']);

            $table->dropColumn([
                'academic_year_id',
                'academic_term_id',
            ]);
        });
    }
};
