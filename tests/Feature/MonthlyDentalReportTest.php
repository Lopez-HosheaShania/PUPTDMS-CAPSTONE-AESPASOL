<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\MonthlyDentalReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class MonthlyDentalReportTest extends TestCase
{
    use RefreshDatabase;

    private function actor(bool $permitted = true): User
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-02 09:00:00'));
        $role = Role::firstOrCreate(['slug' => 'dentist'], ['name' => 'Dentist']);
        if ($permitted) {
            $permission = Permission::firstOrCreate(['slug' => 'create_report_files'], ['name' => 'Create Report Files']);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $user = User::create(['name' => 'Monthly Dentist', 'email' => 'monthly@example.test', 'role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['role' => 'dentist']);

        return $user;
    }

    private function visit(string $service = 'Oral Check-Up', string $classification = 'student', ?Patient $patient = null): Appointment
    {
        $patient ??= Patient::create(['name' => 'Monthly Patient', 'email' => uniqid('monthly').'@example.test',
            'password' => bcrypt('password'), 'classification' => $classification]);
        $service = ServiceType::firstOrCreate(['name' => $service]);

        return Appointment::create(['patient_id' => $patient->id, 'service_type_id' => $service->id,
            'service_type' => $service->name, 'appointment_date' => '2026-10-15',
            'appointment_time' => sprintf('09:%02d:00', Appointment::where('patient_id', $patient->id)->count()),
            'dentist_id' => auth()->id(), 'status' => 'completed']);
    }

    private function period(): array
    {
        return ['document_template_id' => DocumentTemplate::where('code', 'MREP-DEFAULT')->firstOrFail()->id,
            'date_from' => '2026-10-01', 'date_to' => '2026-10-31'];
    }

    private function counts(): array
    {
        return array_fill_keys(array_keys(MonthlyDentalReport::ROWS), array_fill_keys(array_keys(MonthlyDentalReport::COLUMNS), 0));
    }

    private function download(array $overrides = []): array
    {
        return array_replace($this->period(), ['report_name' => 'October Dental Report', 'quantity' => 1,
            'report_counts' => $this->counts(), 'counts_period' => '2026-10-01|2026-10-31', 'counts_confirmed' => '1'], $overrides);
    }

    public function test_prefill_counts_completed_visits_and_unique_patients_without_guessing_unknown_services(): void
    {
        $this->actor();
        $visit = $this->visit();
        $visit->update(['is_walk_in' => true, 'is_emergency' => false]);
        $visit->procedure()->create(['prescriptions' => 'Recorded prescription']);
        $this->visit('Oral Prophylaxis', patient: $visit->patient);
        $this->visit('Fluoride Treatment', 'faculty');
        $this->visit('Unmapped Custom Service', 'faculty');
        $this->visit(classification: 'guest');
        $this->visit()->update(['status' => 'upcoming']);
        $this->visit()->update(['appointment_date' => '2026-09-30']);

        $this->postJson(route('dentist.dentist.report.monthly-report-counts'), $this->period())->assertOk()
            ->assertJsonPath('completed_visits', 5)->assertJsonPath('unclassified_visits', 1)
            ->assertJsonPath('counts.student.actual_patient', 1)->assertJsonPath('counts.faculty.actual_patient', 2)
            ->assertJsonPath('counts.student.inquiry', 0)->assertJsonPath('counts.student.rx', 1)
            ->assertJsonPath('counts.student.prophylaxis', 1)->assertJsonPath('counts.faculty.inquiry', 0)
            ->assertJsonPath('counts.student.med_rx', null)->assertJsonPath('counts.student.extraction', null)
            ->assertJsonPath('counts.student.clearance', null)->assertJsonPath('counts_period', '2026-10-01|2026-10-31');
    }

    public function test_tooth_suggestions_deduplicate_surfaces_but_count_each_tooth_and_visit(): void
    {
        $this->actor();
        $visit = $this->visit('Dental Surgery');
        $visit->procedure()->create(['odontogram_data' => [
            ['tooth' => 11, 'status' => ['code' => 'X'], 'threeD' => ['code' => 'X'], 'surfaces' => ['top' => ['code' => 'X']]],
            ['tooth' => 12, 'status' => ['code' => 'XO']],
            ['tooth' => 13, 'surfaces' => ['top' => ['code' => 'TP'], 'left' => ['code' => 'TP']]],
            ['tooth' => 14, 'status' => ['code' => 'LC'], 'surfaces' => ['top' => ['code' => 'A']]],
        ]]);
        $next = $this->visit('Restoration & Prosthesis', patient: $visit->patient);
        $next->procedure()->create(['odontogram_data' => [['tooth' => 11, 'status' => ['code' => 'TP']]]]);

        $this->postJson(route('dentist.dentist.report.monthly-report-counts'), $this->period())->assertOk()
            ->assertJsonPath('counts.student.actual_patient', 1)->assertJsonPath('counts.student.extraction', 2)
            ->assertJsonPath('counts.student.temporary', 2)->assertJsonPath('counts.student.permanent', 1)
            ->assertJsonPath('counts.student.inquiry', 0);
    }

    public function test_reviewed_counts_replace_suggestions_and_generate_a4_copies_without_changing_patient_records(): void
    {
        $this->actor();
        $visit = $this->visit();
        $before = $visit->fresh()->toArray();
        $counts = $this->counts();
        $counts['student']['actual_patient'] = 2;
        $counts['faculty']['actual_patient'] = 1;
        $counts['student']['extraction'] = 7;
        $counts['faculty']['extraction'] = 3;
        $counts['student']['temporary'] = 2;
        $totals = app(MonthlyDentalReport::class)->totals($counts);
        $this->assertSame(12, $totals['services']);
        $this->assertSame(3, $totals['patients']);
        $this->assertSame(10, $totals['columns']['extraction']);
        $this->assertSame(9, $totals['rows']['student']);

        $response = $this->postJson(route('dentist.dentist.report.monthly-report-download'), $this->download([
            'report_counts' => $counts, 'quantity' => 2,
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $reader = new Fpdi('L', 'pt');
        $this->assertSame(2, $reader->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($response->getContent())));
        $size = $reader->getTemplateSize($reader->importPage(1));
        $this->assertEqualsWithDelta(841.92, $size['width'], 0.01);
        $this->assertEqualsWithDelta(595.32, $size['height'], 0.01);
        $this->assertSame($before, $visit->fresh()->toArray());
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'download', 'module' => 'dentist_reports']);
    }

    public function test_pdf_places_service_grand_total_separately_from_patient_count(): void
    {
        $counts = $this->counts();
        $counts['student']['actual_patient'] = 1;
        $counts['student']['extraction'] = 4;
        $pdf = new class('L', 'pt', 'A4') extends Fpdi
        {
            public array $cells = [];

            public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
            {
                $this->cells[] = [round($this->GetX(), 2), round($this->GetY(), 2), $txt];
                parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
            }
        };
        $pdf->AddPage();
        app(MonthlyDentalReport::class)->draw($pdf, $counts, \Carbon\Carbon::parse('2026-10-01'), \Carbon\Carbon::parse('2026-10-31'));
        $this->assertContains([31.0, 315.88, '4'], $pdf->cells);
        $this->assertContains([92.0, 181.42, '1'], $pdf->cells);
    }

    public function test_blank_invalid_unreviewed_and_stale_counts_are_rejected(): void
    {
        $this->actor();
        $url = route('dentist.dentist.report.monthly-report-download');
        foreach ([-1, '1.5', 100000, '', null] as $invalid) {
            $counts = $this->counts();
            $counts['student']['extraction'] = $invalid;
            $this->postJson($url, $this->download(['report_counts' => $counts]))
                ->assertUnprocessable()->assertJsonValidationErrors('report_counts.student.extraction');
        }
        $counts = $this->counts();
        $counts['student']['invented_category'] = 5;
        $this->postJson($url, $this->download(['report_counts' => $counts]))->assertUnprocessable()->assertJsonValidationErrors('report_counts.student');
        $this->postJson($url, $this->download(['counts_confirmed' => '0']))->assertUnprocessable()->assertJsonValidationErrors('counts_confirmed');
        $this->postJson($url, $this->download(['counts_period' => '2026-09-01|2026-09-30']))->assertUnprocessable()->assertJsonValidationErrors('counts_period');
        $this->postJson($url, $this->download(['report_counts' => []]))->assertUnprocessable()->assertJsonValidationErrors('report_counts');
        $this->postJson($url, $this->download(['date_to' => '2026-09-01']))->assertUnprocessable()->assertJsonValidationErrors('date_to');
    }

    public function test_manual_only_report_is_supported_and_template_registration_is_idempotent(): void
    {
        $this->actor();
        $template = DocumentTemplate::where('code', 'MREP-DEFAULT')->firstOrFail();
        $template->update(['name' => 'Customized Monthly Report']);
        (new \Database\Seeders\MonthlyDentalReportTemplateSeeder)->run();
        $this->assertSame(1, DocumentTemplate::where('code', 'MREP-DEFAULT')->count());
        $this->assertSame('Customized Monthly Report', $template->fresh()->name);
        $this->postJson(route('dentist.dentist.report.monthly-report-download'), $this->download())->assertOk();
        $template->update(['status' => 'archived']);
        $this->postJson(route('dentist.dentist.report.monthly-report-counts'), $this->period())
            ->assertUnprocessable()->assertJsonValidationErrors('document_template_id');
    }

    public function test_counts_endpoint_requires_report_permission(): void
    {
        $this->actor(false);
        $this->postJson(route('dentist.dentist.report.monthly-report-counts'), $this->period())->assertForbidden();
    }

    public function test_report_page_offers_the_template_and_shared_count_controls(): void
    {
        $this->actor();
        $html = $this->get(route('dentist.dentist.report'))->assertOk()->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//select[@id="reportType"]/option[@data-document-type="monthly_report"]')->length);
        $this->assertSame(76, $xpath->query('//input[@data-monthly-count][@data-number-stepper-input][@disabled]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="counts_confirmed"][@disabled]')->length);
    }
}
