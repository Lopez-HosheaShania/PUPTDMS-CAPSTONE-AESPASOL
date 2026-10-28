<?php

namespace Tests\Feature;

use App\Http\Controllers\Dentist\DentistReportController;
use App\Models\DocumentRequest;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\ReportSignatory;
use App\Models\Role;
use App\Models\User;
use App\Services\DocumentTemplateRenderer;
use App\Services\ReportSignatory as SignatoryRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class ReportSignatoryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $slug = 'dentist'): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);

        return User::create(['name' => 'Account Name', 'email' => uniqid().'@example.test', 'role_id' => $role->id, 'status' => 'active']);
    }

    private function saveProfile(User $dentist): ReportSignatory
    {
        $this->actingAs($dentist)->from('/dentist/reports')->put(route('reports.signatory.update'), [
            'signatory_name' => 'Dr. New Dentist', 'license_number' => 'PRC-123456',
            'signature' => UploadedFile::fake()->image('signature.png', 240, 60),
        ])->assertRedirect('/dentist/reports')->assertSessionHasNoErrors();

        return ReportSignatory::where('user_id', $dentist->id)->firstOrFail();
    }

    public function test_legacy_template_migration_replaces_embedded_signatories_and_preserves_custom_content(): void
    {
        $template = DocumentTemplate::create([
            'name' => 'Legacy Report', 'code' => 'LEGACY-SIGNATORY', 'document_type' => 'monthly_report',
            'header_content' => '<h1>Custom clinic header</h1>',
            'content' => '<p>Nelson P. Angeles, DMD</p><img src="/images/dr.angeles-sign.png"><p>Custom report text</p>',
            'footer_content' => '<p>Ronilo I. Lim</p><img src="/images/sir.lim-sign.png">',
        ]);
        $migration = require database_path('migrations/2026_10_28_040000_normalize_legacy_template_signatories.php');
        $migration->up();
        $template->refresh();
        $this->assertSame('<h1>Custom clinic header</h1>', $template->header_content);
        $this->assertSame('<p>{{dentist_name}}</p><img src="{{dentist_signature}}"><p>Custom report text</p>', $template->content);
        $this->assertSame('<p>{{prepared_by}}</p><img src="{{prepared_by_signature}}">', $template->footer_content);
        $content = $template->content;
        $migration->up();
        $this->assertSame($content, $template->fresh()->content);

        Storage::fake('local');
        $this->saveProfile($this->user());
        $html = app(DocumentTemplateRenderer::class)->render($template);
        $this->assertStringContainsString('Dr. New Dentist', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringNotContainsString('Nelson', $html);
    }

    public function test_dentist_can_save_preview_retain_and_replace_their_signature(): void
    {
        Storage::fake('local');
        $dentist = $this->user();
        $profile = $this->saveProfile($dentist);
        Storage::disk('local')->assertExists($profile->signature_path);
        $this->assertSame('Account Name', $dentist->fresh()->name);
        $html = Blade::render('@include("shared.report-signatory")');
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('PRC-123456', $html);

        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Updated Name', 'license_number' => '0099'])
            ->assertSessionHasNoErrors();
        $this->assertSame($profile->signature_path, $profile->fresh()->signature_path);
        $this->saveProfile($dentist);
        Storage::disk('local')->assertMissing($profile->signature_path);
    }

    public function test_only_active_dentists_can_save_and_cannot_change_another_dentists_profile(): void
    {
        Storage::fake('local');
        $owner = $this->user();
        $profile = $this->saveProfile($owner);
        $other = $this->user();
        $this->actingAs($other)->put(route('reports.signatory.update'), [
            'user_id' => $owner->id, 'signatory_name' => 'Someone Else', 'license_number' => '777',
            'signature' => UploadedFile::fake()->image('signature.jpg'),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Dr. New Dentist', $profile->fresh()->name);
        $this->assertDatabaseHas('report_signatories', ['user_id' => $other->id, 'name' => 'Someone Else']);
        $other->update(['status' => 'inactive']);
        $this->put(route('reports.signatory.update'), [])->assertForbidden();
        $this->actingAs($this->user('admin'))->put(route('reports.signatory.update'), [])->assertForbidden();
    }

    public function test_dental_aide_gets_their_own_modal_and_can_save_without_a_license(): void
    {
        Storage::fake('local');
        $dentist = $this->user();
        $dentistProfile = $this->saveProfile($dentist);
        $aide = $this->user('dental_aide');
        $this->actingAs($aide);
        $html = Blade::render('@include("shared.report-signatory")');
        $this->assertStringContainsString('Dental Aide name', $html);
        $this->assertStringNotContainsString('name="license_number"', $html);
        $this->assertStringNotContainsString('Dr. New Dentist', $html);
        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Aide Signatory'])
            ->assertSessionHasErrorsIn('reportSignatory', ['signature']);

        $this->put(route('reports.signatory.update'), [
            'user_id' => $dentist->id, 'signatory_name' => 'Aide Signatory',
            'signature' => UploadedFile::fake()->image('aide.png', 180, 50),
            'license_number' => 'Ignore this injected license',
        ])->assertSessionHasNoErrors()->assertSessionHas('report_signatory_saved');
        $profile = ReportSignatory::where('user_id', $aide->id)->firstOrFail();
        $this->assertNull($profile->license_number);
        $this->assertSame('Dr. New Dentist', $dentistProfile->fresh()->name);
        $this->assertSame($profile->id, app(SignatoryRenderer::class)->ownProfile()?->id);
        $this->assertSame($dentistProfile->id, app(SignatoryRenderer::class)->currentProfile()?->id);
        $this->assertStringContainsString('Aide Signatory', Blade::render('@include("shared.report-signatory")'));
        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Updated Aide'])
            ->assertSessionHasNoErrors();
        $this->assertSame($profile->signature_path, $profile->fresh()->signature_path);
        $aide->update(['status' => 'inactive']);
        $this->put(route('reports.signatory.update'), [])->assertForbidden();
    }

    public function test_intern_report_page_shows_the_button_and_saves_their_own_preparer_profile(): void
    {
        Storage::fake('local');
        $this->withoutVite();
        $dentist = $this->user();
        $dentistProfile = $this->saveProfile($dentist);
        foreach (['dental_intern', 'dental-intern'] as $slug) {
            $intern = $this->user($slug);
            $permission = \App\Models\Permission::firstOrCreate(['slug' => 'create_report_files'], ['name' => 'Create Report Files']);
            $intern->role->permissions()->syncWithoutDetaching([$permission->id]);
            $this->actingAs($intern)->withSession(['role' => $slug]);
            $this->get(route('dentist.dentist.report'))->assertOk()
                ->assertSee('data-open-modal="reportSignatoryModal"', false)
                ->assertSee('Dental Intern signatory')
                ->assertDontSee('name="license_number"', false);

            $this->put(route('reports.signatory.update'), [
                'signatory_name' => 'Intern Report Preparer',
                'signature' => UploadedFile::fake()->image('intern.png', 170, 45),
            ])->assertSessionHasNoErrors();
            $profile = ReportSignatory::where('user_id', $intern->id)->firstOrFail();
            $this->assertNull($profile->license_number);
            $this->assertSame($profile->id, app(SignatoryRenderer::class)->currentPreparerProfile()?->id);
            $this->assertSame($dentistProfile->id, app(SignatoryRenderer::class)->currentProfile()?->id);
            $template = new DocumentTemplate(['document_type' => 'monthly_report', 'content' => '{{prepared_by}}|{{prepared_by_role}}|{{dentist_name}}']);
            $this->assertSame('Intern Report Preparer|Dental Intern|Dr. New Dentist', app(DocumentTemplateRenderer::class)->render($template));

            $pdf = new Fpdi('P', 'pt', 'A3');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetCompression(false);
            $pdf->AddPage();
            app(SignatoryRenderer::class)->draw($pdf, 'gad_report');
            $content = $pdf->Output('S');
            $this->assertStringContainsString('(Intern Report Preparer)', $content);
            $this->assertStringContainsString('(Dental Intern)', $content);
            $this->assertStringContainsString('(Dr. New Dentist)', $content);
            $this->assertStringNotContainsString('(Dental Aide)', $content);
        }
    }

    public function test_aide_details_print_in_prepared_by_and_keep_the_dentist_signatory_separate(): void
    {
        Storage::fake('local');
        $dentist = $this->user();
        $dentistProfile = $this->saveProfile($dentist);
        $aide = $this->user('dental_aide');
        $this->actingAs($aide)->put(route('reports.signatory.update'), [
            'signatory_name' => 'Aide Report Author', 'signature' => UploadedFile::fake()->image('aide.png', 180, 50),
        ])->assertSessionHasNoErrors();
        $aideProfile = ReportSignatory::where('user_id', $aide->id)->firstOrFail();
        $template = new DocumentTemplate([
            'document_type' => 'monthly_report',
            'content' => '{{prepared_by}}|{{prepared_by_role}}|{{prepared_by_signature}}|{{dentist_name}}|{{dentist_signature}}|{{lic_no}}',
        ]);
        foreach ([$aide, $dentist] as $actor) {
            $this->actingAs($actor);
            $parts = explode('|', app(DocumentTemplateRenderer::class)->render($template));
            $this->assertSame('Aide Report Author', $parts[0]);
            $this->assertSame('Dental Aide', $parts[1]);
            $this->assertSame($aideProfile->signatureDataUri(), $parts[2]);
            $this->assertSame('Dr. New Dentist', $parts[3]);
            $this->assertSame($dentistProfile->signatureDataUri(), $parts[4]);
            $this->assertSame('PRC-123456', $parts[5]);

            foreach ([
                'gad_report' => 'gad-accomplishment-template.pdf',
                'daily_treatment_record' => 'daily-treatment-record-template.pdf',
                'dental_cases' => 'dental-cases-template.pdf',
            ] as $type => $file) {
                $pdf = new Fpdi('P', 'pt');
                $pdf->SetAutoPageBreak(false);
                $pdf->SetCompression(false);
                $pdf->setSourceFile(storage_path('app/report-templates/'.$file));
                $page = $pdf->importPage(1);
                $size = $pdf->getTemplateSize($page);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($page);
                app(SignatoryRenderer::class)->draw($pdf, $type);
                $content = $pdf->Output('S');
                $this->assertStringContainsString('(Aide Report Author)', $content, $type);
                $this->assertStringContainsString('(Dental Aide)', $content, $type);
                $this->assertStringContainsString('(Dr. New Dentist)', $content, $type);
                $this->assertStringContainsString('PRC-123456', $content, $type);
                $this->assertGreaterThanOrEqual(2, substr_count($content, '/Subtype /Image'), $type);
            }
        }
        $aide->update(['status' => 'inactive']);
        $this->assertNull(app(SignatoryRenderer::class)->currentPreparerProfile());
    }

    public function test_explicit_dentist_and_appointment_contexts_use_the_record_signatory_instead_of_the_viewer(): void
    {
        Storage::fake('local');
        $author = $this->user();
        $profile = $this->saveProfile($author);
        $patient = Patient::create(['name' => 'Context Patient', 'email' => 'context@example.test', 'password' => bcrypt('password')]);
        $appointment = \App\Models\Appointment::create([
            'patient_id' => $patient->id, 'dentist_id' => $author->id,
            'appointment_date' => today(), 'appointment_time' => '09:00:00', 'status' => 'completed', 'service_type' => 'Check-up',
        ]);
        $viewer = $this->user();
        $this->saveProfile($viewer);
        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Viewer Signatory', 'license_number' => 'OTHER'])
            ->assertSessionHasNoErrors();
        $template = new DocumentTemplate(['document_type' => 'dental_clearance', 'content' => '{{dentist_name}}|{{dentist_signature}}|{{lic_no}}']);
        foreach ([['dentist' => $author], ['appointment' => $appointment]] as $context) {
            $this->assertSame('Dr. New Dentist|'.$profile->signatureDataUri().'|PRC-123456', app(DocumentTemplateRenderer::class)->render($template, $context));
        }
    }

    public function test_interlaced_png_upload_is_safe_to_use_in_report_pdfs(): void
    {
        Storage::fake('local');
        $dentist = $this->user();
        $file = UploadedFile::fake()->image('interlaced.png', 240, 60);
        $image = imagecreatefrompng($file->getRealPath());
        imageinterlace($image, true);
        imagepng($image, $file->getRealPath());
        imagedestroy($image);
        $this->actingAs($dentist)->put(route('reports.signatory.update'), [
            'signatory_name' => 'PNG Signatory', 'license_number' => '123', 'signature' => $file,
        ])->assertSessionHasNoErrors();
        $pdf = new Fpdi('P', 'pt');
        $pdf->SetCompression(false);
        $pdf->AddPage();
        app(SignatoryRenderer::class)->draw($pdf, 'monthly_report');
        $this->assertStringContainsString('(PNG Signatory)', $pdf->Output('S'));
    }

    public function test_upload_requires_complete_details_and_rejects_non_images(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user())->put(route('reports.signatory.update'), [
            'signature' => UploadedFile::fake()->create('signature.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrorsIn('reportSignatory', ['signatory_name', 'license_number', 'signature']);
        $this->assertDatabaseCount('report_signatories', 0);
    }

    public function test_staff_reports_use_the_sole_active_signatory_and_exclude_inactive_dentists(): void
    {
        Storage::fake('local');
        $dentist = $this->user();
        $profile = $this->saveProfile($dentist);
        $this->actingAs($this->user('admin'));
        $this->assertSame($profile->id, app(SignatoryRenderer::class)->currentProfile()?->id);
        $dentist->update(['status' => 'inactive']);
        $this->assertNull(app(SignatoryRenderer::class)->currentProfile());
    }

    public function test_saved_current_dentist_replaces_embedded_signatory_in_every_supported_report(): void
    {
        Storage::fake('local');
        $this->saveProfile($this->user());
        $templates = [
            'gad_report' => 'gad-accomplishment-template.pdf',
            'daily_treatment_record' => 'daily-treatment-record-template.pdf',
            'dental_cases' => 'dental-cases-template.pdf',
            'monthly_report' => 'monthly-report-template.pdf',
            'dpt_emergency' => 'dpt-emergency-template.pdf',
            'dpt_non_emergency' => 'dpt-non-emergency-template.pdf',
        ];
        foreach ($templates as $type => $file) {
            $pdf = new Fpdi('P', 'pt');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetCompression(false);
            $pdf->setSourceFile(storage_path('app/report-templates/'.$file));
            $page = $pdf->importPage(1);
            $size = $pdf->getTemplateSize($page);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($page);
            app(SignatoryRenderer::class)->draw($pdf, $type, [(object) ['dentist_name' => 'Historical Provider']]);
            $content = $pdf->Output('S');
            $this->assertStringContainsString('Dr. New Dentist', $content, $type);
            $this->assertStringContainsString('PRC-123456', $content, $type);
            $this->assertStringContainsString('/Subtype /Image', $content, $type);
            $this->assertStringNotContainsString('(Historical Provider)', $content, $type);
        }
        $template = new DocumentTemplate(['document_type' => 'monthly_report', 'content' => '{{dentist_name}}|{{lic_no}}|{{dentist_signature}}']);
        $html = app(DocumentTemplateRenderer::class)->render($template);
        $this->assertStringStartsWith('Dr. New Dentist|PRC-123456|data:image/png;base64,', $html);
    }

    public function test_clearance_reprint_uses_approvers_saved_signature_and_license(): void
    {
        Storage::fake('local');
        $approver = $this->user();
        $this->saveProfile($approver);
        $patient = Patient::create(['name' => 'Patient', 'email' => 'patient@example.test', 'password' => bcrypt('password')]);
        $request = DocumentRequest::create([
            'patient_id' => $patient->id, 'reference_number' => 'SIG-001', 'document_type' => 'dental_clearance',
            'purpose' => 'School', 'status' => 'approved', 'approved_by' => $approver->id,
            'approved_at' => now(), 'request_date' => today(), 'request_time' => '09:00:00',
        ]);
        $this->actingAs($this->user());
        foreach (['drawAnnualDentalClearancePage', 'drawDentalClearancePage'] as $method) {
            $pdf = new Fpdi('P', 'pt');
            $pdf->SetCompression(false);
            $pdf->AddPage();
            (new ReflectionMethod(DentistReportController::class, $method))->invoke(app(DentistReportController::class), $pdf, $request);
            $content = $pdf->Output('S');
            $this->assertStringContainsString('Dr. New Dentist', $content);
            $this->assertStringContainsString('PRC-123456', $content);
            $this->assertStringContainsString('/Subtype /Image', $content);
        }
    }
}
