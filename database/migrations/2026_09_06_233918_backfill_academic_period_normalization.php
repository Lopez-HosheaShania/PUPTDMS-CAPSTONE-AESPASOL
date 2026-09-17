<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('academic_periods')
            ->select('academic_year')
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderBy('academic_year')
            ->get()
            ->each(function ($period) {
                DB::table('academic_years')->updateOrInsert(
                    ['name' => $period->academic_year],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });

        $terms = [
            [
                'code' => 'first_semester',
                'name' => 'First Semester',
                'sort_order' => 1,
            ],
            [
                'code' => 'second_semester',
                'name' => 'Second Semester',
                'sort_order' => 2,
            ],
            [
                'code' => 'summer',
                'name' => 'Summer',
                'sort_order' => 3,
            ],
        ];

        foreach ($terms as $term) {
            DB::table('academic_terms')->updateOrInsert(
                ['code' => $term['code']],
                [
                    'name' => $term['name'],
                    'sort_order' => $term['sort_order'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('academic_periods')
            ->orderBy('id')
            ->get()
            ->each(function ($period) {
                $academicYear = DB::table('academic_years')
                    ->where('name', $period->academic_year)
                    ->first();

                $semester = trim((string) $period->semester);

                $termCode = match ($semester) {
                    '1st Semester',
                    'First Semester' => 'first_semester',

                    '2nd Semester',
                    'Second Semester' => 'second_semester',

                    'Summer' => 'summer',

                    default => null,
                };

                $academicTerm = $termCode
                    ? DB::table('academic_terms')
                        ->where('code', $termCode)
                        ->first()
                    : null;

                DB::table('academic_periods')
                    ->where('id', $period->id)
                    ->update([
                        'academic_year_id' => $academicYear?->id,
                        'academic_term_id' => $academicTerm?->id,
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('academic_periods')->update([
            'academic_year_id' => null,
            'academic_term_id' => null,
        ]);

        DB::table('academic_terms')->delete();
        DB::table('academic_years')->delete();
    }
};