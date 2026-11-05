<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AcademicTerm;

class AcademicPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $periods = [
            [
                'academic_year' => '2025-2026',
                'semester' => '1st Semester',
                'start_date' => '2025-09-01',
                'end_date' => '2026-01-31',
                'description' => 'Default academic period for AY 2025-2026.',
                'is_active' => false,
            ],
            [
                'academic_year' => '2025-2026',
                'semester' => '2nd Semester',
                'start_date' => '2026-02-01',
                'end_date' => '2026-06-21',
                'description' => 'Default academic period for AY 2025-2026.',
                'is_active' => true,
            ],
            [
                'academic_year' => '2025-2026',
                'semester' => 'Summer',
                'start_date' => '2026-06-29',
                'end_date' => '2026-08-08',
                'description' => 'Default summer term for AY 2025-2026.',
                'is_active' => false,
            ],
        ];

        foreach ($periods as $period) {
            $academicYear = AcademicYear::firstOrCreate([
                'name' => $period['academic_year'],
            ]);

            $termCode = match ($period['semester']) {
                '1st Semester' => 'first_semester',
                '2nd Semester' => 'second_semester',
                'Summer' => 'summer',
            };

            $academicTerm = AcademicTerm::where('code', $termCode)->firstOrFail();

            AcademicPeriod::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'academic_term_id' => $academicTerm->id,
                ],
                [
                    'start_date' => $period['start_date'],
                    'end_date' => $period['end_date'],
                    'description' => $period['description'],
                    'is_active' => $period['is_active'],
                ]
            );
        }
    }
}
