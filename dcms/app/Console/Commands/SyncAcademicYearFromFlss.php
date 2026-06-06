<?php

namespace App\Console\Commands;

use App\Services\FacultyApiService;
use Illuminate\Console\Command;

class SyncAcademicYearFromFlss extends Command
{
    protected $signature = 'flss:sync-academic-year';

    protected $description = 'Sync active academic year and semester from FLSS';

    public function handle(FacultyApiService $facultyApiService): int
    {
        try {
            $academicPeriod = $facultyApiService->syncActiveAcademicYearSemester();

            $this->info('Academic year synced successfully.');
            $this->line('Academic Year: ' . $academicPeriod->academic_year);
            $this->line('Semester: ' . $academicPeriod->semester);
            $this->line('Start Date: ' . $academicPeriod->start_date?->format('Y-m-d'));
            $this->line('End Date: ' . $academicPeriod->end_date?->format('Y-m-d'));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to sync academic year from FLSS.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}