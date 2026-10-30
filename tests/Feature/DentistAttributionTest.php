<?php

namespace Tests\Feature;

use App\Http\Controllers\Dentist\DentistReportController;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\DocumentRequest;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\DocumentTemplateRenderer;
use App\Services\AppointmentReportRecords;
use App\Services\ReportSignatory;
use App\Services\DentistTransitionService;
use App\Support\DentistName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Notification;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class DentistAttributionTest extends TestCase
{
    use RefreshDatabase;

    private function dentist(string $name): User
    {
        $role = Role::firstOrCreate(['slug' => 'dentist'], ['name' => 'Dentist']);
        $permission = Permission::firstOrCreate(['slug' => 'manage_appointments'], ['name' => 'Manage Appointments']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        return User::create([
            'name' => $name, 'email' => str_replace(' ', '', $name) . '@example.test',
            'role_id' => $role->id, 'status' => 'active',
        ]);
    }

    private function appointment(User $dentist, string $status = 'completed'): Appointment
    {
        $patient = Patient::create([
            'name' => 'Attribution Patient', 'email' => uniqid() . '@example.test', 'password' => bcrypt('password'),
        ]);

        return Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => $dentist->id,
            'appointment_date' => today()->toDateString(), 'appointment_time' => '09:00:00',
            'status' => $status, 'service_type' => 'Oral Check-up',
        ]);
    }

    public function test_treatment_save_records_the_authenticated_dentist_and_later_edits_preserve_authorship(): void
    {
        $performer = $this->dentist('Original Dentist');
        $viewer = $this->dentist('Other Dentist');
        $appointment = $this->appointment($performer, 'upcoming');
        $this->actingAs($performer)->withSession(['role' => 'dentist'])
            ->postJson(route('dentist.odontogram.save', $appointment), [
                'odontogram_data' => [], 'diagnosis' => 'Original examination',
                'oral_examination' => 'Initial findings', 'completion_action' => 'finished',
                'has_applied_treatment' => true,
                'procedure_started_at' => today()->toDateString() . 'T09:00:00',
                'procedure_completed_at' => today()->toDateString() . 'T09:30:00',
                'recorded_by' => $viewer->id,
            ])->assertSuccessful();
        $procedure = AppointmentProcedure::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame($performer->id, $procedure->recorded_by);

        $this->actingAs($viewer);
        $procedure->update(['diagnosis' => 'Corrected findings']);
        $appointment->update(['dentist_id' => $viewer->id]);
        $appointment->refresh();
        $this->assertSame($performer->id, $procedure->fresh()->recorded_by);
        $this->assertSame('Original Dentist', $appointment->dentist_name);
        $html = Blade::render('<x-appointment-record-card :appointment="$appointment" />', compact('appointment'));
        $this->assertStringContainsString('Original Dentist', $html);
        $this->assertStringNotContainsString('Other Dentist', $html);
    }

    public function test_legacy_records_use_their_stored_dentist_and_never_the_current_viewer(): void
    {
        $performer = $this->dentist('Legacy Dentist');
        $appointment = $this->appointment($performer);
        AppointmentProcedure::create(['appointment_id' => $appointment->id]);
        $this->actingAs($this->dentist('Current Viewer'));

        $this->assertSame('Legacy Dentist', $appointment->dentist_name);
        $this->assertFalse($appointment->relationLoaded('procedure'));
    }

    public function test_template_signatories_follow_record_context_instead_of_the_current_user(): void
    {
        $first = $this->dentist('First Dentist');
        $second = $this->dentist('Second Dentist');
        $template = new DocumentTemplate([
            'document_type' => 'dental_clearance',
            'content' => '{{dentist_name}} | {{submitted_by}} | {{noted_by}} | {{prepared_by}} | {{dentist_name}} | {{dentist_signature}}',
        ]);
        $renderer = app(DocumentTemplateRenderer::class);
        foreach ([$first, $second] as $actor) {
            $this->actingAs($actor);
            $html = $renderer->render($template, ['appointment' => $this->appointment($actor)]);
            $this->assertStringContainsString($actor->name, $html);
            $this->assertStringNotContainsString('Nelson', $html);
            $this->assertStringNotContainsString('dr.angeles-sign', $html);
        }
        $appointment = $this->appointment($first);
        $this->assertStringStartsWith('Dr. First Dentist, DMD | Dr. First Dentist, DMD', $renderer->render($template, ['appointment' => $appointment]));
    }

    public function test_clearance_reprints_use_the_recorded_approver_in_both_pdf_forms(): void
    {
        $approver = $this->dentist('Approving Dentist');
        $downloader = $this->dentist('Downloading Dentist');
        $appointment = $this->appointment($downloader);
        $request = DocumentRequest::create([
            'patient_id' => $appointment->patient_id, 'assigned_dentist_id' => $downloader->id,
            'reference_number' => 'DOC-AUTHOR', 'document_type' => 'dental_clearance', 'purpose' => 'School',
            'status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now(),
            'request_date' => today()->toDateString(), 'request_time' => '09:00:00',
        ]);
        $this->actingAs($downloader);
        $this->assertSame('Approving Dentist', DentistName::forDocumentRequest($request));
        foreach (['drawAnnualDentalClearancePage', 'drawDentalClearancePage'] as $method) {
            $pdf = new Fpdi('P', 'pt', 'A4');
            $pdf->SetCompression(false);
            $pdf->AddPage();
            (new ReflectionMethod(DentistReportController::class, $method))
                ->invoke(app(DentistReportController::class), $pdf, $request);
            $content = $pdf->Output('S');
            $this->assertStringContainsString('(Dr. Approving Dentist)', $content);
            $this->assertStringNotContainsString('(Dr. Downloading Dentist)', $content);
        }
    }

    public function test_pdf_report_signatories_follow_treating_dentists_regardless_of_who_generates_them(): void
    {
        $first = $this->dentist('First Report Author');
        $second = $this->dentist('Second Report Author');
        $records = [$this->appointment($first), $this->appointment($second)];
        foreach ([$first, $second] as $actor) {
            $this->actingAs($actor);
            foreach (['gad_report', 'daily_treatment_record', 'dental_cases', 'monthly_report', 'dpt_emergency', 'dpt_non_emergency'] as $type) {
                $pdf = new Fpdi('P', 'pt', 'A3');
                $pdf->SetCompression(false);
                $pdf->AddPage();
                app(ReportSignatory::class)->draw($pdf, $type, $records);
                $content = $pdf->Output('S');
                $this->assertStringContainsString('First Report Author', $content);
                $this->assertStringContainsString('Second Report Author', $content);
                $this->assertStringNotContainsString('Nelson', $content);
            }
        }
    }

    public function test_custom_support_roles_are_preparers_and_never_replace_the_treating_dentists(): void
    {
        $first = $this->dentist('Treating Dentist One');
        $second = $this->dentist('Treating Dentist Two');
        $this->actingAs($first);
        $firstVisit = $this->appointment($first);
        AppointmentProcedure::create(['appointment_id' => $firstVisit->id]);
        $this->actingAs($second);
        $secondVisit = $this->appointment($second);
        AppointmentProcedure::create(['appointment_id' => $secondVisit->id]);

        foreach (['dental_aide', 'dental_assistant'] as $slug) {
            $role = Role::create(['name' => ucwords(str_replace('_', ' ', $slug)), 'slug' => $slug]);
            $staff = User::create(['name' => $role->name . ' Reporter', 'email' => $slug . '@example.test', 'role_id' => $role->id, 'status' => 'active']);
            $this->actingAs($staff)->withSession(['role' => $slug]);
            $records = app(AppointmentReportRecords::class)->between(now()->startOfMonth(), now()->endOfMonth());
            $this->assertEqualsCanonicalizing(['Treating Dentist One', 'Treating Dentist Two'], DentistName::forRecords($records));
            foreach (['gad_report', 'daily_treatment_record', 'dental_cases', 'monthly_report', 'dpt_emergency', 'dpt_non_emergency'] as $type) {
                $pdf = new Fpdi('P', 'pt', 'A3');
                $pdf->SetCompression(false);
                $pdf->AddPage();
                app(ReportSignatory::class)->draw($pdf, $type, $records);
                $content = $pdf->Output('S');
                $this->assertStringContainsString('Treating Dentist One', $content);
                $this->assertStringContainsString('Treating Dentist Two', $content);
                $this->assertStringNotContainsString($staff->name, $content);
            }

            foreach (['dental_cases', 'gad_report', 'daily_treatment_record'] as $type) {
                $template = new DocumentTemplate(['document_type' => $type, 'content' => '{{dentist_name}} | {{noted_by}} | {{prepared_by}}']);
                $parts = explode(' | ', app(DocumentTemplateRenderer::class)->render($template));
                $this->assertStringContainsString('Treating Dentist One', $parts[0]);
                $this->assertStringContainsString('Treating Dentist Two', $parts[0]);
                $this->assertStringNotContainsString($staff->name, $parts[0]);
                $this->assertSame($staff->name, $parts[2]);
            }
        }
    }

    public function test_missing_provider_never_falls_back_to_the_report_generator(): void
    {
        $this->actingAs($this->dentist('Unrelated Generator'));
        $pdf = new Fpdi('P', 'pt');
        $pdf->SetCompression(false);
        $pdf->AddPage();
        app(ReportSignatory::class)->draw($pdf, 'monthly_report');
        $this->assertStringNotContainsString('Unrelated Generator', $pdf->Output('S'));
        $template = new DocumentTemplate(['document_type' => 'dental_clearance', 'content' => '{{dentist_name}}']);
        $this->assertSame('', app(DocumentTemplateRenderer::class)->render($template));
    }

    public function test_assistant_downloads_keep_the_procedure_dentists_in_generated_report_pdfs(): void
    {
        $first = $this->dentist('Procedure Dentist One');
        $second = $this->dentist('Procedure Dentist Two');
        foreach ([$first, $second] as $dentist) {
            $this->actingAs($dentist);
            $visit = $this->appointment($dentist);
            $visit->patient->update(['classification' => 'student', 'gender' => 'Female']);
            AppointmentProcedure::create(['appointment_id' => $visit->id, 'diagnosis' => 'Check-up']);
        }
        $role = Role::create(['name' => 'Dental Assistant', 'slug' => 'dental_assistant']);
        $permission = Permission::firstOrCreate(['slug' => 'create_report_files'], ['name' => 'Create Report Files']);
        $role->permissions()->attach($permission);
        $assistant = User::create(['name' => 'Assistant Reporter', 'email' => 'assistant-download@example.test', 'role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($assistant)->withSession(['role' => $role->slug]);

        foreach ([
            'gad_report' => 'gad-download',
            'daily_treatment_record' => 'daily-treatment-record-download',
            'dental_cases' => 'dental-cases-download',
            'monthly_report' => 'monthly-report-download',
        ] as $type => $route) {
            $template = DocumentTemplate::create([
                'name' => $type, 'code' => $type === 'daily_treatment_record' ? 'DTR-DEFAULT' : strtoupper($type),
                'document_type' => $type, 'status' => 'active', 'content' => '<p>Report</p>',
            ]);
            $response = $this->postJson(route('dentist.dentist.report.' . $route), [
                'report_name' => 'Provider attribution', 'document_template_id' => $template->id,
                'date_from' => today()->toDateString(), 'date_to' => today()->toDateString(), 'quantity' => 1,
            ])->assertOk();
            $pdf = $response->getContent();
            preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
            $content = '';
            foreach ($streams[1] as $stream) {
                // Inspect text streams without expanding large embedded page images.
                if (strlen($stream) < 200000) {
                    $decoded = @gzuncompress($stream, 200000) ?: '';
                    preg_match_all('/\(((?:\\\\.|[^\\\\)])*)\)\s*Tj/', $decoded, $texts);
                    $content .= implode(' ', $texts[1]) . ' ';
                }
            }
            $this->assertStringContainsString('Procedure Dentist One', $content, $type);
            $this->assertStringContainsString('Procedure Dentist Two', $content, $type);
            $this->assertStringNotContainsString('Assistant Reporter', $content, $type);
        }
    }

    public function test_scheduled_continuity_keeps_current_dentist_on_reports_until_access_ends(): void
    {
        Notification::fake();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-28 08:00:00'));
        $current = $this->dentist('Current Continuity Dentist');
        $successor = $this->dentist('Successor Continuity Dentist');
        $this->actingAs($current)->withSession(['role' => 'dentist']);
        $historical = $this->appointment($current);
        AppointmentProcedure::create(['appointment_id' => $historical->id]);
        $upcoming = $this->appointment($current, 'upcoming');
        $upcoming->update(['appointment_date' => '2026-10-30']);

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::create([
            'name' => 'Continuity Administrator', 'email' => 'continuity-reports@example.test',
            'role_id' => $adminRole->id, 'status' => 'active',
        ]);
        $this->actingAs($admin)->withSession(['role' => 'admin']);
        \App\Models\ClinicDentistAssignment::whereKey('primary')->update(['dentist_id' => $current->id]);
        $service = app(DentistTransitionService::class);
        $transition = $service->createTransition([
            'dentist_id' => $current->id, 'transition_type' => 'retirement', 'coverage_type' => 'permanent',
            'default_successor_dentist_id' => $successor->id,
            'last_working_date' => '2026-10-28', 'effective_date' => '2026-10-29',
        ], $admin);
        $transition = $service->finalizeTransition($transition, $admin);
        $this->assertSame('scheduled', $transition->status);

        $this->travelTo($transition->access_ends_at->copy()->subSecond());
        $this->assertSame(0, $service->processDueTransitions());
        $this->assertSame('active', $current->fresh()->status);
        $this->assertSame($current->id, $upcoming->fresh()->dentist_id);
        $records = app(AppointmentReportRecords::class)->between(now()->startOfDay(), now()->endOfDay());
        $this->assertSame(['Current Continuity Dentist'], DentistName::forRecords($records));
        foreach (['gad_report', 'daily_treatment_record', 'dental_cases', 'monthly_report', 'dpt_emergency', 'dpt_non_emergency'] as $type) {
            $pdf = new Fpdi('P', 'pt', 'A3');
            $pdf->SetCompression(false);
            $pdf->AddPage();
            app(ReportSignatory::class)->draw($pdf, $type, $records);
            $content = $pdf->Output('S');
            $this->assertStringContainsString('Current Continuity Dentist', $content);
            $this->assertStringNotContainsString('Successor Continuity Dentist', $content);
        }

        $this->travelTo($transition->access_ends_at->copy());
        $this->assertSame(1, $service->processDueTransitions());
        $this->assertSame($successor->id, $upcoming->fresh()->dentist_id);
        $this->assertSame('inactive', $current->fresh()->status);
        $this->assertSame('Current Continuity Dentist', $historical->fresh()->dentist_name);
        $this->actingAs($successor)->withSession(['role' => 'dentist']);
        $upcoming = $upcoming->fresh();
        $upcoming->update(['status' => 'completed']);
        AppointmentProcedure::create(['appointment_id' => $upcoming->id]);
        $this->assertSame('Successor Continuity Dentist', $upcoming->fresh()->dentist_name);
    }
}
