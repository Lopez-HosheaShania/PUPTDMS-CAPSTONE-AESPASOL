<?php

namespace Tests\Unit;

use App\Http\Controllers\Dentist\DentistReportController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class DentalServicesCourseYearSectionDisplayTest extends TestCase
{
    #[DataProvider('patientDisplayProvider')]
    public function test_it_formats_the_dental_services_course_year_section_column(object $patient, string $expected): void
    {
        $method = new ReflectionMethod(DentistReportController::class, 'dentalServicesCourseYearSectionDisplay');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke(new DentistReportController(), $patient));
    }

    public static function patientDisplayProvider(): array
    {
        return [
            'faculty uses its classification label' => [
                (object) [
                    'classification' => 'faculty',
                    'faculty_code' => 'FAC-001',
                    'course_code' => null,
                    'course_name' => null,
                    'year_level' => null,
                    'section' => null,
                ],
                'Faculty',
            ],
            'student retains course year and section' => [
                (object) [
                    'classification' => 'student',
                    'course_code' => 'BSIT',
                    'course_name' => 'Bachelor of Science in Information Technology',
                    'year_level' => 3,
                    'section' => '3-1',
                ],
                'BSIT - Y3 / 3-1',
            ],
        ];
    }
}
