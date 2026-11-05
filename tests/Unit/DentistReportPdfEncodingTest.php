<?php

namespace Tests\Unit;

use App\Http\Controllers\Dentist\DentistReportController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class DentistReportPdfEncodingTest extends TestCase
{
    #[DataProvider('utf8ReportValuesProvider')]
    public function test_fpdi_report_text_is_encoded_once_at_the_pdf_boundary(string $value): void
    {
        $controller = new DentistReportController();
        $method = new ReflectionMethod($controller, 'preparePdfText');
        $method->setAccessible(true);

        $encoded = $method->invoke($controller, $value);

        $this->assertSame($value, mb_convert_encoding($encoded, 'UTF-8', 'Windows-1252'));

        if (preg_match('/[^\x00-\x7F]/', $value) === 1) {
            $this->assertNotSame($value, $encoded);
            $this->assertStringNotContainsString('Ã', $encoded);
        } else {
            $this->assertSame($value, $encoded);
        }
    }

    public static function utf8ReportValuesProvider(): array
    {
        return [
            'enye' => ['Gecilie C. Almirañez'],
            'capital enye' => ['ÑOÑO DELA CRUZ'],
            'accented name' => ['María José Hernández'],
            'suffix name' => ['José Muñoz Jr.'],
            'ascii name' => ['Juan Dela Cruz'],
            'mixed fields' => ['Office of Niño Support - evaluación'],
        ];
    }
}
