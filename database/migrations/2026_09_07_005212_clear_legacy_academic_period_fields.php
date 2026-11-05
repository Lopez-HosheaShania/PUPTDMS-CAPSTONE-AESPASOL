<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('academic_periods')->update([
            'academic_year' => null,
            'semester' => null,
        ]);
    }

    public function down(): void
    {
        DB::table('academic_periods')
            ->orderBy('id')
            ->get()
            ->each(function ($period) {
                $academicYear = DB::table('academic_years')
                    ->where('id', $period->academic_year_id)
                    ->value('name');

                $academicTerm = DB::table('academic_terms')
                    ->where('id', $period->academic_term_id)
                    ->first();

                $semester = match ($academicTerm?->code) {
                    'first_semester' => 'First Semester',
                    'second_semester' => 'Second Semester',
                    'summer' => 'Summer',
                    default => null,
                };

                DB::table('academic_periods')
                    ->where('id', $period->id)
                    ->update([
                        'academic_year' => $academicYear,
                        'semester' => $semester,
                    ]);
            });
    }
};