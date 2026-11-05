<?php

namespace Tests\Unit;

use App\Http\Controllers\Dentist\DentalServicesRecordController;
use App\Http\Controllers\Dentist\DentistReportController;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Patient;
use App\Support\DentalServicesProcedureTiming;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class DentalServicesProcedureTimingTest extends TestCase
{
    #[DataProvider('timingProvider')]
    public function test_reports_use_the_start_plus_processing_minutes(?string $start, ?int $seconds, string $expectedStart, ?string $expectedEnd, ?int $minutes): void
    {
        $appointment = new Appointment(['appointment_date' => '2026-09-07', 'appointment_time' => '09:00:00']);
        $procedure = new AppointmentProcedure(['procedure_started_at' => $start, 'procedure_duration_seconds' => $seconds]);
        $appointment->setRelation('procedure', $procedure);

        $this->assertSame($expectedStart, DentalServicesProcedureTiming::requestedTimeIn($appointment)?->toDateTimeString());
        $this->assertSame($expectedEnd, DentalServicesProcedureTiming::timeProcessed($appointment)?->toDateTimeString());
        $this->assertSame($minutes, DentalServicesProcedureTiming::minutesProcessed($appointment));
        $this->assertSame($start, $procedure->procedure_started_at?->toDateTimeString());
    }

    public static function timingProvider(): array
    {
        return [
            'actual start differs from booking' => ['2026-09-07 09:17:45', 1800, '2026-09-07 09:17:45', '2026-09-07 09:47:45', 30],
            'crosses midnight' => ['2026-09-07 23:50:15', 1800, '2026-09-07 23:50:15', '2026-09-08 00:20:15', 30],
            'zero minutes' => ['2026-09-07 09:17:45', 0, '2026-09-07 09:17:45', '2026-09-07 09:17:45', 0],
            'whole minute report convention' => ['2026-09-07 09:17:45', 61, '2026-09-07 09:17:45', '2026-09-07 09:19:45', 2],
            'legacy booked time fallback' => [null, 1800, '2026-09-07 09:00:00', '2026-09-07 09:30:00', 30],
            'missing duration' => [null, null, '2026-09-07 09:00:00', null, null],
        ];
    }

    public function test_the_dsr_table_and_pdf_use_matching_times(): void
    {
        $patient = new Patient(['name' => 'Mary Joy Dela Cruz']);
        $patient->setRelation('user', null);
        $patient->setRelation('medicalHistory', null);
        $appointment = new Appointment(['appointment_date' => '2026-09-07', 'appointment_time' => '09:00:00']);
        $appointment->setRelation('patient', $patient);
        $appointment->setRelation('procedure', new AppointmentProcedure([
            'procedure_started_at' => '2026-09-07 09:17:45',
            'procedure_completed_at' => '2026-09-07 11:00:00',
            'procedure_duration_seconds' => 1800,
        ]));

        $row = (new ReflectionMethod(DentalServicesRecordController::class, 'toFrontendRow'))->invoke(new DentalServicesRecordController(), $appointment);
        $this->assertSame('09:17 AM', $row['timeIn']);
        $this->assertSame('09:47 AM', $row['timeOut']);
        $this->assertSame('30 mins', $row['duration']);

        $pdf = new class('L', 'pt') extends Fpdi {
            public array $cellTexts = [];

            public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
            {
                $this->cellTexts[] = $txt;
                parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
            }
        };
        $pdf->AddPage();
        (new ReflectionMethod(DentistReportController::class, 'drawDentalServicesRows'))->invoke(new DentistReportController(), $pdf, collect([$appointment]));
        $this->assertContains('09:17 AM', $pdf->cellTexts);
        $this->assertContains('09:47 AM', $pdf->cellTexts);
        $this->assertContains('30 mins', $pdf->cellTexts);
        $this->assertNotContains('11:00 AM', $pdf->cellTexts);
    }
}
