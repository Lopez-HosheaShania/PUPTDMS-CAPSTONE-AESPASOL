<?php

namespace Tests\Feature;

use App\Http\Controllers\Dentist\DentalServicesRecordController;
use App\Http\Controllers\Dentist\DentistReportController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Services\AppointmentReportRecords;
use App\Services\DocumentTemplateRenderer;
use App\Services\DptReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class AppointmentCaseClassificationTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(bool $walkIn, ?bool $emergency = null): Appointment
    {
        $patient = Patient::create([
            'name' => 'Case Patient', 'email' => uniqid('case').'@example.test',
            'password' => bcrypt('password'), 'classification' => 'student',
        ]);
        $appointment = Appointment::create([
            'patient_id' => $patient->id, 'appointment_date' => '2026-11-02',
            'appointment_time' => '09:00:00', 'status' => 'completed', 'is_walk_in' => $walkIn,
            'service_type' => 'Oral Check-up',
            ...($emergency !== null ? ['is_emergency' => $emergency] : []),
        ]);

        return $appointment->fresh();
    }

    public static function classifications(): array
    {
        return [
            'emergency walk-in' => [true, true],
            'non-emergency walk-in' => [true, false],
            'scheduled non-emergency' => [false, false],
            'explicit emergency takes precedence over booking source' => [false, true],
        ];
    }

    #[DataProvider('classifications')]
    public function test_lists_templates_and_pdf_marks_use_the_saved_classification(bool $walkIn, bool $emergency): void
    {
        $this->travelTo(Carbon::parse('2026-11-02'));
        $appointment = $this->appointment($walkIn, $emergency);

        $record = app(AppointmentReportRecords::class)->between(today(), today())->sole();
        $this->assertSame($emergency, $record->is_emergency);
        $this->assertSame($walkIn, $record->is_walk_in);
        $this->assertSame($emergency ? 'Emergency' : 'Non-Emergency', $record->visit_type);

        $controller = app(DentalServicesRecordController::class);
        $row = (new ReflectionMethod($controller, 'toFrontendRow'))->invoke($controller, $appointment);
        $this->assertSame($record->visit_type, $row['type']);

        $renderer = app(DocumentTemplateRenderer::class);
        $context = (new ReflectionMethod($renderer, 'buildDentalServicesContext'))->invoke($renderer);
        $this->assertSame($emergency ? '✓' : '—', $context['emergency_case_1']);
        $this->assertSame($emergency ? '—' : '✓', $context['non_emergency_case_1']);

        $report = app(DptReport::class);
        $this->assertSame([$appointment->id], $report->records(today(), today(), $emergency)->pluck('id')->all());
        $this->assertCount(0, $report->records(today(), today(), ! $emergency));

        // Inspect the actual PDF drawing commands at the two case columns.
        $pdf = new class('L', 'pt') extends Fpdi
        {
            public array $caseMarks = [];

            public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
            {
                if (in_array(round($this->GetX() + $w / 2, 2), [653.35, 689.25], true)) {
                    $this->caseMarks[] = $txt;
                }

                parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
            }
        };
        $pdf->AddPage();
        $pdfController = app(DentistReportController::class);
        (new ReflectionMethod($pdfController, 'drawDentalServicesRows'))->invoke($pdfController, $pdf, collect([$appointment]));
        $this->assertSame($emergency ? ['X', ''] : ['', 'X'], $pdf->caseMarks);
    }

    public function test_dpt_filters_do_not_mix_explicit_choices_with_legacy_records_or_pending_visits(): void
    {
        $legacyEmergency = $this->appointment(true);
        $legacyScheduled = $this->appointment(false);
        $nonEmergency = $this->appointment(true, false);
        $emergency = $this->appointment(true, true);
        $this->appointment(true, false)->update(['status' => 'upcoming']);
        $this->appointment(true, true)->update(['appointment_date' => '2026-10-01']);

        $report = app(DptReport::class);
        $this->assertSame([$legacyEmergency->id, $emergency->id], $report->records(Carbon::parse('2026-11-02'), Carbon::parse('2026-11-02'), true)->pluck('id')->all());
        $this->assertSame([$legacyScheduled->id, $nonEmergency->id], $report->records(Carbon::parse('2026-11-02'), Carbon::parse('2026-11-02'), false)->pluck('id')->all());
    }

    public function test_migration_backfills_legacy_cases_and_keeps_one_cascading_record_per_appointment(): void
    {
        $walkIn = $this->appointment(true);
        $scheduled = $this->appointment(false);
        $migration = require database_path('migrations/2026_11_02_000000_create_appointment_case_classifications_table.php');
        $migration->down();
        $migration->up();

        $this->assertFalse(Schema::hasColumn('appointments', 'is_emergency'));
        $this->assertDatabaseCount('appointment_case_classifications', 2);
        $this->assertTrue($walkIn->fresh()->is_emergency);
        $this->assertFalse($scheduled->fresh()->is_emergency);
        $this->assertSame(0, DB::table('appointment_case_classifications')->insertOrIgnore([
            'appointment_id' => $walkIn->id, 'is_emergency' => false,
        ]));

        $walkIn->update(['is_walk_in' => false]);
        $this->assertTrue($walkIn->fresh()->is_emergency);
        $walkIn->update(['is_emergency' => false]);
        $this->assertFalse($walkIn->fresh()->is_emergency);
        $this->assertFalse($walkIn->fresh()->toArray()['is_emergency']);
        $this->assertArrayNotHasKey('case_classification', $walkIn->fresh()->toArray());
        $this->assertDatabaseCount('appointment_case_classifications', 2);
        $walkIn->delete();
        $this->assertDatabaseMissing('appointment_case_classifications', ['appointment_id' => $walkIn->id]);
        $this->assertDatabaseCount('appointment_case_classifications', 1);
    }
}
