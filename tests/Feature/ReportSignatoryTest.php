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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class ReportSignatoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(\App\Services\SignatureAiVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->andReturn([
                'accepted' => true, 'valid' => true, 'message' => 'Signature verified and accepted',
            ])->byDefault();
        });
    }

    private function user(string $slug = 'dentist'): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);

        return User::create(['name' => 'Account Name', 'email' => uniqid().'@example.test', 'role_id' => $role->id, 'status' => 'active']);
    }

    private function saveProfile(User $dentist): ReportSignatory
    {
        $this->actingAs($dentist)->from('/dentist/reports')->put(route('reports.signatory.update'), [
            'signatory_name' => 'New Dentist', 'license_number' => '0012345',
            'signature' => UploadedFile::fake()->image('signature.png', 240, 60),
        ])->assertRedirect('/dentist/reports')->assertSessionHasNoErrors();

        return ReportSignatory::where('user_id', $dentist->id)->firstOrFail();
    }

    public function test_name_and_license_validation_preserves_existing_profile_and_leading_zeros(): void
    {
        Storage::fake('local');
        $profile = $this->saveProfile($this->user());
        foreach (['Juan123', 'Maria@Santos', '12345'] as $name) {
            $this->put(route('reports.signatory.update'), ['signatory_name' => $name, 'license_number' => '1234567'])
                ->assertSessionHasErrorsIn('reportSignatory', ['signatory_name']);
        }
        foreach (['', '0', '12345', 'ABC123', '123-456', '12.34', '-123', '1e7', '+123', '12345678', '123456789@', '12345-7890'] as $license) {
            $this->put(route('reports.signatory.update'), ['signatory_name' => 'Juan Dela Cruz', 'license_number' => $license])
                ->assertSessionHasErrorsIn('reportSignatory', ['license_number']);
        }
        $this->assertSame('New Dentist', $profile->fresh()->name);
        foreach (['Juan Dela Cruz', 'Maria Santos', 'Angela Jane Raymundo', 'José Peña'] as $name) {
            $this->put(route('reports.signatory.update'), ['signatory_name' => '  '.$name.'  ', 'license_number' => '0012345'])
                ->assertSessionHasNoErrors();
            $this->assertSame($name, $profile->fresh()->name);
            $this->assertSame('0012345', $profile->fresh()->license_number);
        }
        foreach (['123344', '000123', '123456', '1234567', '0012345'] as $license) {
            $this->put(route('reports.signatory.update'), ['signatory_name' => 'Juan Dela Cruz', 'license_number' => $license])
                ->assertSessionHasNoErrors();
            $this->assertSame($license, $profile->fresh()->license_number);
        }
        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Juan Dela Cruz', 'license_number' => '12345'])
            ->assertSessionHasErrorsIn('reportSignatory', [
                'license_number' => 'License number is incomplete. Please enter at least 6 digits.',
            ]);
        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Juan Dela Cruz', 'license_number' => '12345678'])
            ->assertSessionHasErrorsIn('reportSignatory', [
                'license_number' => 'License number must not exceed 7 digits.',
            ]);
    }

    public function test_ai_rejection_is_enforced_on_preview_and_save_without_replacing_saved_signature(): void
    {
        Storage::fake('local');
        $profile = $this->saveProfile($this->user());
        $this->mock(\App\Services\SignatureAiVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->twice()->andReturn([
                'accepted' => false, 'reason' => 'The image contains a photo, not a signature.',
            ]);
        });
        $this->postJson(route('reports.signatory.validate-signature'), [
            'signature' => UploadedFile::fake()->image('photo.png', 240, 120),
        ])->assertUnprocessable()->assertJsonPath('valid', false);
        $this->put(route('reports.signatory.update'), [
            'signatory_name' => 'Juan Dela Cruz', 'license_number' => '1234567',
            'signature' => UploadedFile::fake()->image('drawn-signature-photo.png', 240, 120),
        ])->assertSessionHasErrorsIn('reportSignatory', ['signature']);
        $this->assertSame('New Dentist', $profile->fresh()->name);
        Storage::disk('local')->assertExists($profile->signature_path);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_drawings_save_and_can_be_retained_without_calling_ai_for_each_signatory_role(): void
    {
        Storage::fake('local');
        $this->mock(\App\Services\SignatureAiVerifier::class, function ($mock) {
            $mock->shouldNotReceive('verify');
        });
        foreach (['dentist', 'dental_aide', 'dental_intern'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->put(route('reports.signatory.update'), [
                'signatory_name' => 'Juan Dela Cruz', 'license_number' => '123344',
                'signature_source' => 'drawn',
                'signature' => UploadedFile::fake()->image('drawn-signature-123.png', 100, 50),
            ])->assertSessionHasNoErrors();
            $profile = ReportSignatory::where('user_id', $user->id)->firstOrFail();
            $this->assertStringStartsWith('report-signatures/'.$user->id.'/drawn/', $profile->signature_path);
            Storage::disk('local')->assertExists($profile->signature_path);

            $this->put(route('reports.signatory.update'), [
                'signatory_name' => 'Updated Name', 'license_number' => '123344',
            ])->assertSessionHasNoErrors();
            $this->assertSame($profile->signature_path, $profile->fresh()->signature_path);
            $this->assertSame('Updated Name', $profile->fresh()->name);
        }
    }

    public function test_drawing_replaces_saved_upload_and_reopened_modal_shows_the_new_signature(): void
    {
        Storage::fake('local');
        $user = $this->user();
        $profile = $this->saveProfile($user);
        $oldPath = $profile->signature_path;
        $oldPreview = $profile->signatureDataUri();
        $this->mock(\App\Services\SignatureAiVerifier::class, function ($mock) {
            $mock->shouldNotReceive('verify');
        });
        $this->from('/dentist/reports')->put(route('reports.signatory.update'), [
            'signatory_name' => 'Juan Dela Cruz', 'license_number' => '123344',
            'signature_source' => 'drawn',
            'signature' => UploadedFile::fake()->image('drawn-signature-123.png', 300, 100),
        ])->assertRedirect('/dentist/reports')->assertSessionHasNoErrors()
            ->assertSessionHas('report_signatory_saved');
        $profile->refresh();
        $this->assertNotSame($oldPath, $profile->signature_path);
        $this->assertStringStartsWith('report-signatures/'.$user->id.'/drawn/', $profile->signature_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($profile->signature_path);
        $html = Blade::render('@include("shared.report-signatory")');
        $this->assertStringContainsString($profile->signatureDataUri(), $html);
        $this->assertStringNotContainsString($oldPreview, $html);
        $this->assertStringContainsString('data-signature-drawn-prefix="drawn-signature"', $html);
    }

    public function test_upload_replacing_a_drawing_still_requires_ai_and_preserves_drawing_when_rejected(): void
    {
        Storage::fake('local');
        $user = $this->user();
        $this->actingAs($user)->put(route('reports.signatory.update'), [
            'signatory_name' => 'Juan Dela Cruz', 'license_number' => '123344',
            'signature_source' => 'drawn',
            'signature' => UploadedFile::fake()->image('drawn-signature-123.png', 240, 120),
        ])->assertSessionHasNoErrors();
        $profile = ReportSignatory::where('user_id', $user->id)->firstOrFail();
        $this->mock(\App\Services\SignatureAiVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn([
                'accepted' => false, 'reason' => 'The image contains a photo, not a signature.',
            ]);
        });
        $this->put(route('reports.signatory.update'), [
            'signatory_name' => 'Juan Dela Cruz', 'license_number' => '123344',
            'signature_source' => 'upload',
            'signature' => UploadedFile::fake()->image('photo.png', 240, 120),
        ])->assertSessionHasErrorsIn('reportSignatory', ['signature']);
        $this->assertSame($profile->signature_path, $profile->fresh()->signature_path);
        Storage::disk('local')->assertExists($profile->signature_path);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_signature_preview_checks_permissions_files_dimensions_and_ai_result(): void
    {
        $url = route('reports.signatory.validate-signature');
        $this->postJson($url)->assertUnauthorized();
        $this->actingAs($this->user('admin'))->postJson($url)->assertForbidden();
        $this->actingAs($this->user());
        foreach ([
            UploadedFile::fake()->create('signature.txt', 1, 'text/plain'),
            UploadedFile::fake()->image('oversize.png', 240, 60)->size(2049),
            UploadedFile::fake()->image('tiny.png', 10, 10),
            UploadedFile::fake()->image('wide.png', 4001, 60),
        ] as $file) {
            $this->postJson($url, ['signature' => $file])->assertUnprocessable()->assertJsonValidationErrors('signature');
        }
        $this->postJson($url, ['signature' => UploadedFile::fake()->image('signature.png', 240, 60)])
            ->assertOk()->assertJsonPath('valid', true)->assertJsonPath('accepted', true);
    }

    private function useRealSignatureVerifier(bool $available = true): void
    {
        $manager = \Mockery::mock(\App\Services\AiServiceManager::class);
        $manager->shouldReceive('shouldUse')->with('signature')->andReturn($available);
        $manager->shouldReceive('recordSuccess', 'recordFailure', 'recordFallback')->zeroOrMoreTimes();
        $this->instance(\App\Services\SignatureAiVerifier::class, new \App\Services\SignatureAiVerifier($manager));
        config(['services.openai.api_key' => 'test-key', 'services.signature_ai.threshold' => 0.8]);
        Http::preventStrayRequests();
    }

    public function test_reports_apply_the_real_booking_ai_conditions_to_preview_and_save(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user());
        $this->useRealSignatureVerifier();
        $classification = [];
        Http::fake(['api.openai.com/*' => function () use (&$classification) {
            return Http::response(['output_text' => json_encode($classification)]);
        }]);
        foreach ([
            ['photo', 0.99, true, false],
            ['screenshot', 0.99, true, false],
            ['typed_text', 0.99, true, false],
            ['signature', 0.79, true, false],
            ['handwritten_signature', 0.98, false, true],
        ] as [$type, $confidence, $aiBoolean, $accepted]) {
            $classification = [
                'detected_type' => $type, 'confidence' => $confidence,
                'is_signature' => $aiBoolean, 'reason' => 'AI classification: '.$type,
            ];
            $this->postJson(route('reports.signatory.validate-signature'), [
                'signature' => UploadedFile::fake()->image('upload.png', 240, 120),
            ])->assertStatus($accepted ? 200 : 422)->assertJsonPath('accepted', $accepted);
            $response = $this->put(route('reports.signatory.update'), [
                'signatory_name' => 'Juan Dela Cruz', 'license_number' => '0012345',
                'signature' => UploadedFile::fake()->image('upload.png', 240, 120),
            ]);
            if ($accepted) {
                $response->assertSessionHasNoErrors();
                $this->assertDatabaseCount('report_signatories', 1);
            } else {
                $response->assertSessionHasErrorsIn('reportSignatory', ['signature']);
                $this->assertDatabaseCount('report_signatories', 0);
                $this->assertCount(0, Storage::disk('local')->allFiles());
            }
        }
    }

    public function test_exhausted_api_credits_cannot_approve_a_report_signature(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user());
        $this->useRealSignatureVerifier();
        Http::fake(['api.openai.com/*' => Http::response([
            'error' => ['code' => 'credit_balance_exhausted', 'type' => 'insufficient_quota'],
        ], 429)]);
        $this->postJson(route('reports.signatory.validate-signature'), [
            'signature' => UploadedFile::fake()->image('poster.png', 240, 120),
        ])->assertUnprocessable()->assertJsonPath('accepted', false)
            ->assertJsonPath('detected_type', 'verification_unavailable');
        $this->put(route('reports.signatory.update'), [
            'signatory_name' => 'Juan Dela Cruz', 'license_number' => '1234567',
            'signature' => UploadedFile::fake()->image('poster.png', 240, 120),
        ])->assertSessionHasErrorsIn('reportSignatory', ['signature']);
        $this->assertDatabaseCount('report_signatories', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_saved_legacy_image_is_rechecked_and_offline_ai_cannot_approve_it(): void
    {
        Storage::fake('local');
        $profile = $this->saveProfile($this->user());
        $this->useRealSignatureVerifier(false);
        $this->put(route('reports.signatory.update'), [
            'signatory_name' => 'Updated Name', 'license_number' => '1234567',
        ])->assertSessionHasErrorsIn('reportSignatory', ['signature']);
        $this->assertSame('New Dentist', $profile->fresh()->name);
        Storage::disk('local')->assertExists($profile->signature_path);
        Http::assertNothingSent();

        $this->useRealSignatureVerifier();
        Http::fake(['api.openai.com/*' => Http::response(['output_text' => json_encode([
            'is_signature' => false, 'confidence' => 0.99,
            'detected_type' => 'typed_text', 'reason' => 'The saved image is a poster.',
        ])])]);
        $this->put(route('reports.signatory.update'), [
            'signatory_name' => 'Updated Name', 'license_number' => '1234567',
        ])->assertSessionHasErrorsIn('reportSignatory', ['signature']);
        $this->assertSame('New Dentist', $profile->fresh()->name);
        Storage::disk('local')->assertExists($profile->signature_path);
        Http::assertSentCount(1);
    }

    public function test_dentist_and_dental_aide_signatories_share_name_and_ai_validation(): void
    {
        Storage::fake('local');
        $this->useRealSignatureVerifier();
        $type = 'signature';
        $status = 200;
        Http::fake(['api.openai.com/*' => function () use (&$type, &$status) {
            return Http::response($status === 200 ? ['output_text' => json_encode([
                'is_signature' => $type === 'signature', 'confidence' => 0.99,
                'detected_type' => $type, 'reason' => 'AI classification: '.$type,
            ])] : ['error' => ['code' => 'credit_balance_exhausted']], $status);
        }]);

        foreach (['dentist', 'dentist_role', 'head_dentist', 'dental_aide', 'dental-aide', 'dental_aide_role', 'dental_intern', 'dental-intern'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user);
            $html = Blade::render('@include("shared.report-signatory")', ['errors' => new \Illuminate\Support\ViewErrorBag]);
            $this->assertStringContainsString('data-global-validation', $html);
            $this->assertStringContainsString('data-report-signature-drawing', $html);
            $this->assertStringContainsString('data-signature-drawn-prefix="drawn-signature"', $html);
            $this->assertStringContainsString('id="reportDrawnSignature" data-signature-input', $html);
            $this->assertStringContainsString('data-signature-undo', $html);
            $this->assertStringNotContainsString('name="patient_signature"', $html);
            $isDentist = \App\Services\AssignedDentistService::isDentist($user);
            if ($isDentist) {
                $this->assertStringContainsString('pattern="[0-9]{6,7}"', $html);
                $this->assertMatchesRegularExpression('/minlength="6"\s+maxlength="7"/', $html);
            } else {
                $this->assertStringNotContainsString('name="license_number"', $html);
            }
            $this->put(route('reports.signatory.update'), [
                'signatory_name' => 'Juan123', 'license_number' => '0012345',
            ])->assertSessionHasErrorsIn('reportSignatory', ['signatory_name']);

            foreach ([['photo', 200, false], ['signature', 429, false], ['signature', 200, true]] as [$type, $status, $accepted]) {
                $this->postJson(route('reports.signatory.validate-signature'), [
                    'signature' => UploadedFile::fake()->image('upload.png', 240, 120),
                ])->assertStatus($accepted ? 200 : 422)->assertJsonPath('accepted', $accepted);
                $response = $this->put(route('reports.signatory.update'), [
                    'signatory_name' => 'Juan Dela Cruz', 'license_number' => '0012345',
                    'signature' => UploadedFile::fake()->image('upload.png', 240, 120),
                ]);
                if ($accepted) {
                    $response->assertSessionHasNoErrors();
                    $profile = ReportSignatory::where('user_id', $user->id)->firstOrFail();
                    $this->assertSame($isDentist ? '0012345' : null, $profile->license_number);
                } else {
                    $response->assertSessionHasErrorsIn('reportSignatory', ['signature']);
                    $this->assertNull(ReportSignatory::where('user_id', $user->id)->first());
                }
            }
        }
    }

    public function test_booking_signature_component_keeps_its_default_upload_and_drawing_contract(): void
    {
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag);
        $html = Blade::render('<x-booking.signature />');
        $this->assertStringContainsString('name="patient_signature"', $html);
        $this->assertStringContainsString('id="patient_signature" data-signature-input', $html);
        $this->assertStringContainsString('name="signature_source"', $html);
        $this->assertStringContainsString('id="signature_result_box"', $html);
        $this->assertStringContainsString(route('book.appointment.validate-signature'), $html);
        $this->assertStringContainsString('data-signature-use-drawn', $html);
        $drawOnly = Blade::render('<x-booking.signature mode="draw-only" />');
        $this->assertStringContainsString('data-signature-drawn-prefix="walk-in-signature"', $drawOnly);
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
        $this->assertStringContainsString('New Dentist', $html);
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
        $this->assertStringContainsString('0012345', $html);

        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Updated Name', 'license_number' => '000099'])
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
            'user_id' => $owner->id, 'signatory_name' => 'Someone Else', 'license_number' => '0000777',
            'signature' => UploadedFile::fake()->image('signature.jpg', 240, 60),
        ])->assertSessionHasNoErrors();
        $this->assertSame('New Dentist', $profile->fresh()->name);
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
        $this->assertStringNotContainsString('New Dentist', $html);
        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Aide Signatory'])
            ->assertSessionHasErrorsIn('reportSignatory', ['signature']);

        $this->put(route('reports.signatory.update'), [
            'user_id' => $dentist->id, 'signatory_name' => 'Aide Signatory',
            'signature' => UploadedFile::fake()->image('aide.png', 180, 60),
            'license_number' => 'Ignore this injected license',
        ])->assertSessionHasNoErrors()->assertSessionHas('report_signatory_saved');
        $profile = ReportSignatory::where('user_id', $aide->id)->firstOrFail();
        $this->assertNull($profile->license_number);
        $this->assertSame('New Dentist', $dentistProfile->fresh()->name);
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
                'signature' => UploadedFile::fake()->image('intern.png', 170, 60),
            ])->assertSessionHasNoErrors();
            $profile = ReportSignatory::where('user_id', $intern->id)->firstOrFail();
            $this->assertNull($profile->license_number);
            $this->assertSame($profile->id, app(SignatoryRenderer::class)->currentPreparerProfile()?->id);
            $this->assertSame($dentistProfile->id, app(SignatoryRenderer::class)->currentProfile()?->id);
            $template = new DocumentTemplate(['document_type' => 'monthly_report', 'content' => '{{prepared_by}}|{{prepared_by_role}}|{{dentist_name}}']);
            $this->assertSame('Intern Report Preparer|Dental Intern|Dr. New Dentist, DMD', app(DocumentTemplateRenderer::class)->render($template));

            $pdf = new Fpdi('P', 'pt', 'A3');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetCompression(false);
            $pdf->AddPage();
            app(SignatoryRenderer::class)->draw($pdf, 'gad_report');
            $content = $pdf->Output('S');
            $this->assertStringContainsString('(Intern Report Preparer)', $content);
            $this->assertStringContainsString('(Dental Intern)', $content);
            $this->assertStringContainsString('(Dr. New Dentist, DMD)', $content);
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
            'signatory_name' => 'Aide Report Author', 'signature' => UploadedFile::fake()->image('aide.png', 180, 60),
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
            $this->assertSame('Dr. New Dentist, DMD', $parts[3]);
            $this->assertSame($dentistProfile->signatureDataUri(), $parts[4]);
            $this->assertSame('0012345', $parts[5]);

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
                $this->assertStringContainsString('(Dr. New Dentist, DMD)', $content, $type);
                $this->assertStringContainsString('0012345', $content, $type);
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
        $this->put(route('reports.signatory.update'), ['signatory_name' => 'Viewer Signatory', 'license_number' => '9999999'])
            ->assertSessionHasNoErrors();
        $template = new DocumentTemplate(['document_type' => 'dental_clearance', 'content' => '{{dentist_name}}|{{dentist_signature}}|{{lic_no}}']);
        foreach ([['dentist' => $author], ['appointment' => $appointment]] as $context) {
            $this->assertSame('Dr. New Dentist, DMD|'.$profile->signatureDataUri().'|0012345', app(DocumentTemplateRenderer::class)->render($template, $context));
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
            'signatory_name' => 'PNG Signatory', 'license_number' => '0000123', 'signature' => $file,
        ])->assertSessionHasNoErrors();
        $pdf = new Fpdi('P', 'pt');
        $pdf->SetCompression(false);
        $pdf->AddPage();
        app(SignatoryRenderer::class)->draw($pdf, 'monthly_report');
        $this->assertStringContainsString('(Dr. PNG Signatory, DMD)', $pdf->Output('S'));
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
            $this->assertStringContainsString('New Dentist', $content, $type);
            $this->assertStringContainsString('0012345', $content, $type);
            $this->assertStringContainsString('/Subtype /Image', $content, $type);
            $this->assertStringNotContainsString('(Historical Provider)', $content, $type);
        }
        $template = new DocumentTemplate(['document_type' => 'monthly_report', 'content' => '{{dentist_name}}|{{lic_no}}|{{dentist_signature}}']);
        $html = app(DocumentTemplateRenderer::class)->render($template);
        $this->assertStringStartsWith('Dr. New Dentist, DMD|0012345|data:image/png;base64,', $html);
    }

    public function test_dentist_report_name_adds_titles_once_without_changing_the_saved_name(): void
    {
        foreach (['Juan Dela Cruz', 'Dr. Juan Dela Cruz', 'DR Juan Dela Cruz, MD', 'Dr. Juan Dela Cruz, DMD.', 'DR. Juan Dela Cruz, MD'] as $name) {
            $this->assertSame('Dr. Juan Dela Cruz, DMD', SignatoryRenderer::dentistNameForReport($name));
        }
        $this->assertSame('', SignatoryRenderer::dentistNameForReport(''));
        Storage::fake('local');
        $profile = $this->saveProfile($this->user());
        $html = app(DocumentTemplateRenderer::class)->render(new DocumentTemplate([
            'document_type' => 'monthly_report', 'content' => '{{dentist_name}}',
        ]));
        $this->assertSame('Dr. New Dentist, DMD', $html);
        $this->assertSame('New Dentist', $profile->fresh()->name);
        $this->assertStringContainsString('value="New Dentist"', Blade::render('@include("shared.report-signatory")'));
    }

    public function test_template_titles_are_added_only_when_the_name_placeholder_does_not_already_have_them(): void
    {
        Storage::fake('local');
        $this->saveProfile($this->user());
        $renderer = app(DocumentTemplateRenderer::class);
        foreach ([
            '{{dentist_name}}' => 'Dr. New Dentist, DMD',
            'Dr. {{dentist_name}}' => 'Dr. New Dentist, DMD',
            '{{dentist_name}}, DMD' => 'Dr. New Dentist, DMD',
            'Dr. {{dentist_name}}, DMD' => 'Dr. New Dentist, DMD',
            '<span>Dr.</span> <strong>{{dentist_name}}</strong> <span>D.M.D.</span>' => '<span>Dr.</span> <strong>New Dentist</strong> <span>D.M.D.</span>',
            '{{dentist_name}}<span>D.M.D.</span>' => 'Dr. New Dentist<span>D.M.D.</span>',
            '{{dentist_name}} | {{dentist_name}} DMD' => 'Dr. New Dentist, DMD | Dr. New Dentist DMD',
            'Dr. {{submitted_by}}, DMD | {{noted_by}}' => 'Dr. New Dentist, DMD | Dr. New Dentist, DMD',
        ] as $content => $expected) {
            $template = new DocumentTemplate(['document_type' => 'monthly_report', 'content' => $content]);
            $this->assertSame($expected, $renderer->render($template));
        }
        foreach (['annual-dental-clearance', 'dental-clearance'] as $file) {
            $template = new DocumentTemplate([
                'document_type' => str_replace('-', '_', $file),
                'content' => file_get_contents(resource_path('views/admin/document-templates-defaults/'.$file.'.blade.php')),
            ]);
            $html = $renderer->render($template);
            $this->assertStringContainsString('>Dr. New Dentist</span>', $html);
            $this->assertStringContainsString('D.M.D.', $html);
            $this->assertStringNotContainsString('Dr. New Dentist, DMD', $html);
        }
    }

    public function test_updated_signatories_keep_original_template_text_positions_and_sign_above_the_name(): void
    {
        Storage::fake('local');
        $dentist = $this->user();
        $dentistProfile = $this->saveProfile($dentist);
        $aide = $this->user('dental_aide');
        $this->actingAs($aide)->put(route('reports.signatory.update'), [
            'signatory_name' => 'Aide Template Signatory',
            'signature' => UploadedFile::fake()->image('aide.png', 240, 60),
        ])->assertSessionHasNoErrors();
        $aideProfile = ReportSignatory::where('user_id', $aide->id)->firstOrFail();
        $positions = [
            'gad_report' => [[128.4, 518.569], [466.65, 518.569]],
            'daily_treatment_record' => [[61.226, 425.967], [430.949, 426.287]],
            'dental_cases' => [[72.024, 666.7], [324.07, 666.7]],
            'monthly_report' => [null, [30.48, 355.56]],
            'dpt_emergency' => [null, [386.684, 578.4]],
            'dpt_non_emergency' => [null, [386.684, 578.4]],
        ];
        foreach (['New Dentist', 'Angela Jane Raymundo Dela Cruz'] as $name) {
            $dentistProfile->update(['name' => $name]);
            foreach ($positions as $type => $slots) {
                $pdf = new class('P', 'pt', 'A3') extends Fpdi {
                    public array $placedTexts = [];
                    public array $placedImages = [];

                    public function Text($x, $y, $txt)
                    {
                        $this->placedTexts[$txt] = [$x, $y, $this->GetStringWidth($txt)];
                        parent::Text($x, $y, $txt);
                    }

                    public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '')
                    {
                        $this->placedImages[$file] = [$x, $y, $w, $h];
                        parent::Image($file, $x, $y, $w, $h, $type, $link);
                    }
                };
                $pdf->AddPage();
                app(SignatoryRenderer::class)->draw($pdf, $type);
                foreach ([$aideProfile, $dentistProfile] as $index => $profile) {
                    if (! $slots[$index]) {
                        continue;
                    }
                    [$x, $y, $textWidth] = $pdf->placedTexts[$index === 1 ? SignatoryRenderer::dentistNameForReport($profile->name) : $profile->name];
                    [$imageX, $imageY, $imageWidth, $imageHeight] = $pdf->placedImages[$profile->signaturePath()];
                    $this->assertEqualsWithDelta($slots[$index][0], $x, 0.01, $type);
                    $this->assertEqualsWithDelta($slots[$index][1], $y, 0.01, $type);
                    $this->assertEqualsWithDelta($x + $textWidth / 2, $imageX + $imageWidth / 2, 0.01, $type);
                    $this->assertLessThan($y - 3, $imageY + $imageHeight, $type);
                }
            }
        }
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
            $this->assertStringContainsString('(Dr. New Dentist)', $content);
            $this->assertStringContainsString('0012345', $content);
            $this->assertStringContainsString('/Subtype /Image', $content);
        }
    }
}
