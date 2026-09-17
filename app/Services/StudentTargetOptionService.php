<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StudentTargetOptionService
{
    private const CACHE_KEY = 'clinic_schedule_student_target_options_v2';

    public function __construct(private readonly StudentApiService $studentApiService) {}

    public function get(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(15), function () {
            $options = collect();

            try {
                $options = collect($this->studentApiService->getAllStudents())
                    ->map(fn($student) => is_array($student) ? $this->fromExternalStudent($student) : null)
                    ->filter();
            } catch (\Throwable $exception) {
                Log::warning('Student targeting options are using the local fallback.', [
                    'message' => $exception->getMessage(),
                ]);
            }
            $localOptions = Patient::query()
                ->with([
                    'studentInformation' => function ($query) {
                        $query->select([
                            'id',
                            'patient_id',
                            'course_code',
                            'course_name',
                            'year_level',
                            'section',
                        ]);
                    },
                ])
                ->where('classification', 'student')
                ->whereHas('studentInformation', function ($query) {
                    $query->whereNotNull('course_code');
                })
                ->select([
                    'id',
                    'classification',
                ])
                ->get()
                ->map(function (Patient $patient) {
                    $studentInformation = $patient->studentInformation;

                    return [
                        'course_code' => trim(
                            (string) $studentInformation?->course_code
                        ),

                        'course_name' => trim(
                            (string) (
                                $studentInformation?->course_name
                                ?: $studentInformation?->course_code
                            )
                        ),

                        'year_level' => (int) (
                            $studentInformation?->year_level ?? 0
                        ),

                        'section' => trim(
                            (string) $studentInformation?->section
                        ),
                    ];
                });

            return $options
                ->concat($localOptions)
                ->filter(fn($option) => filled($option['course_code'])
                    && filled($option['year_level'])
                    && filled($option['section']))
                ->unique(fn($option) => strtolower(implode('|', [
                    $option['course_code'],
                    $option['year_level'],
                    $option['section'],
                ])))
                ->sortBy(fn($option) => sprintf(
                    '%s|%02d|%s',
                    strtolower($option['course_code']),
                    $option['year_level'],
                    strtolower($option['section'])
                ))
                ->values();
        });
    }

    private function fromExternalStudent(array $student): ?array
    {
        $normalized = $this->studentApiService->normalizeStudent($student);
        $raw = $normalized['raw'] ?? $student;

        $courseCode = data_get($raw, 'program.code')
            ?? data_get($raw, 'program_code')
            ?? data_get($raw, 'programCode')
            ?? data_get($raw, 'course.code')
            ?? data_get($raw, 'course_code')
            ?? data_get($raw, 'courseCode')
            ?? data_get($normalized, 'program');
        $courseName = data_get($raw, 'program.name')
            ?? data_get($raw, 'program_name')
            ?? data_get($raw, 'programName')
            ?? data_get($raw, 'course.name')
            ?? data_get($raw, 'course_name')
            ?? data_get($raw, 'courseName')
            ?? $courseCode;
        $yearLevel = data_get($normalized, 'year_level')
            ?? data_get($raw, 'year_level')
            ?? data_get($raw, 'yearLevel');
        $section = data_get($normalized, 'section')
            ?? data_get($raw, 'section')
            ?? data_get($raw, 'section_name')
            ?? data_get($raw, 'sectionName');

        if (blank($courseCode) || blank($yearLevel) || blank($section)) {
            return null;
        }

        return [
            'course_code' => strtoupper(trim((string) $courseCode)),
            'course_name' => trim((string) $courseName),
            'year_level' => (int) $yearLevel,
            'section' => strtoupper(trim((string) $section)),
        ];
    }
}
