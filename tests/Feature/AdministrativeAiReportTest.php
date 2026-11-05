<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdministrativeAiReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 10, 23)->startOfDay());
        config(['services.openai.api_key' => 'test-report-key']);
        SystemSetting::setSetting('ai_mode', 'normal');
        SystemSetting::setSetting('ai_reports_enabled', '1');
        Http::preventStrayRequests();
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        foreach (['view_ai_reports', 'create_ai_generative_reports'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $user = User::create([
            'name' => 'Report Administrator', 'email' => 'report-admin@example.test',
            'role_id' => $role->id, 'status' => 'active',
        ]);
        $patient = Patient::create([
            'name' => 'Source Patient', 'email' => 'source@example.test',
            'classification' => 'faculty', 'password' => bcrypt('password'),
        ]);
        foreach (['completed', 'cancelled'] as $status) {
            Appointment::create([
                'patient_id' => $patient->id, 'appointment_date' => '2026-10-15',
                'appointment_time' => $status === 'completed' ? '09:00:00' : '10:00:00',
                'service_type' => 'Oral Checkup', 'status' => $status,
            ]);
        }
        $this->actingAs($user)->withSession(['role' => 'admin']);
    }

    private function narrative(): array
    {
        return [
            'executive_summary' => 'AI advisory summary for administrative review.',
            'key_findings' => ['AI advisory finding.'],
            'treatment_analysis' => ['AI advisory treatment interpretation.'],
            'inventory_analysis' => ['AI advisory inventory interpretation.'],
            'recommendations' => ['AI advisory recommendation.'],
        ];
    }

    private function assertSourceMetrics(array $report): void
    {
        $this->assertSame(1, $report['print_metrics']['total_patients']);
        $this->assertSame(1, $report['print_metrics']['new_patients']);
        $this->assertSame(2, $report['print_metrics']['total_appointments']);
        $this->assertSame(1, $report['print_metrics']['cancelled_appointments']);
        $this->assertEquals(50, $report['print_metrics']['completion_rate']);
        $this->assertEquals(50, $report['print_metrics']['cancellation_rate']);
        $this->assertSame($report['source_data']['appointments']['total'], $report['print_metrics']['total_appointments']);
    }

    public function test_ai_is_advisory_and_cannot_overwrite_source_values_in_any_output(): void
    {
        $narrative = $this->narrative() + [
            'print_metrics' => ['total_patients' => 999999],
            'risk_level' => 'Invented risk', 'period' => 'Invented period',
        ];
        Http::fake(['api.openai.com/*' => Http::response(['status' => 'completed', 'output_text' => json_encode($narrative)])]);
        $response = $this->get(route('admin.reports.ai-generated'))->assertOk()
            ->assertSee('Administrative AI Generated Overall Report')
            ->assertSee('AI-Generated &bull; Advisory Only', false)
            ->assertSee('Please verify all figures and findings against the underlying clinic records')
            ->assertDontSee('Source clinic statistics')->assertSee($narrative['executive_summary'])
            ->assertDontSee('999999')->assertDontSee('Invented risk')->assertDontSee('Invented period');
        $report = $response->viewData('aiReport');
        $this->assertSourceMetrics($report);
        $this->assertSame('ai', $report['generation_source']);
        Http::assertSent(function ($request) use ($report) {
            $input = $request['input'][0]['content'][0]['text'];
            $data = json_decode(substr($input, strpos($input, "\n") + 1), true);

            return $data == $report['source_data'];
        });
        // The shared notice appears on screen, the browser print document, and PDF preview.
        $this->assertSame(3, substr_count($response->getContent(), 'This report contains AI-generated summaries'));
        $html = view('admin.reports-ai-generated-pdf', ['aiReport' => $report])->render();
        $this->assertStringContainsString('AI-Generated &bull; Advisory Only', $html);
        $this->assertStringContainsString('Please verify all figures and findings', $html);
        $this->assertStringNotContainsString('999999', $html);
        $this->get(route('admin.reports.ai-generated.download'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        Http::assertSentCount(1);
    }

    public static function failedResponses(): array
    {
        return [
            'server failure' => [null, 500, 'completed'],
            'empty output' => ['', 200, 'completed'],
            'malformed JSON' => ['{broken', 200, 'completed'],
            'missing sections' => ['{"executive_summary":"Partial analysis"}', 200, 'completed'],
            'wrong section type' => ['{"executive_summary":"Partial","key_findings":"wrong"}', 200, 'completed'],
            'empty sections' => [json_encode(['executive_summary' => 'Partial', 'key_findings' => [], 'treatment_analysis' => [], 'inventory_analysis' => [], 'recommendations' => []]), 200, 'completed'],
            'incomplete provider output' => [null, 200, 'incomplete'],
        ];
    }

    #[DataProvider('failedResponses')]
    public function test_failed_or_invalid_ai_preserves_statistics_without_replacement_analysis(?string $text, int $status, string $providerStatus): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'status' => $providerStatus, 'output_text' => $text ?? json_encode($this->narrative()),
        ], $status)]);
        $response = $this->get(route('admin.reports.ai-generated'))->assertOk()
            ->assertSee('AI-generated analysis is currently unavailable.')
            ->assertDontSee('Source clinic statistics')->assertDontSee('Partial analysis');
        $report = $response->viewData('aiReport');
        $this->assertSourceMetrics($report);
        $this->assertSame('unavailable', $report['generation_source']);
        foreach (['key_findings', 'treatment_analysis', 'inventory_analysis', 'recommendations'] as $field) {
            $this->assertSame([], $report[$field]);
        }
        $html = view('admin.reports-ai-generated-pdf', ['aiReport' => $report])->render();
        $this->assertStringContainsString('AI-generated analysis is currently unavailable.', $html);
        $this->assertStringContainsString('Total Patients', $html);
        $this->assertDatabaseHas('ai_service_logs', ['feature' => 'reports', 'status' => 'failure']);
    }

    public function test_offline_report_keeps_source_data_and_can_still_be_exported(): void
    {
        SystemSetting::setSetting('ai_mode', 'offline');
        $response = $this->get(route('admin.reports.ai-generated'))->assertOk()
            ->assertSee('AI-generated analysis is currently unavailable.');
        $this->assertSourceMetrics($response->viewData('aiReport'));
        $this->get(route('admin.reports.ai-generated.download'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        Http::assertNothingSent();
    }
}
