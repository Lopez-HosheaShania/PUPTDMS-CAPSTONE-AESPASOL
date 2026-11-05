<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SystemLogsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
    }

    public static function layouts(): array
    {
        return [['admin'], ['dentist']];
    }

    #[DataProvider('layouts')]
    public function test_page_ajax_and_live_updates_use_the_same_readable_payload(string $role): void
    {
        $this->signIn($role, ['view_system_logs']);
        $original = json_encode(['category' => 'patients', 'previous' => ['duration' => 1, 'unit' => 'years'],
            'updated' => ['duration' => 6, 'unit' => 'months']], JSON_THROW_ON_ERROR);
        $log = AuditLog::create([
            'actor_role' => $role, 'actor_name' => 'Test User', 'action' => 'retention_period_modified',
            'module' => 'data_retention', 'description' => $original,
        ]);
        $filters = ['module' => 'data_retention', 'action_type' => 'retention_period_modified'];
        $url = route($role.'.system_logs', $filters);
        $page = $this->get($url)->assertOk()->assertSee('Previous retention period: 1 year')
            ->assertSee('Saved retention period: 6 months')->assertDontSee(e($original), false);
        foreach ($this->xpath($page->getContent())->query('//script[not(@src)]') as $script) {
            if (! str_contains($script->textContent, 'slRenderRows')) {
                continue;
            }
            $process = new \Symfony\Component\Process\Process(['node', '--check', '--input-type=module']);
            $process->setInput($script->textContent);
            $process->setTimeout(15);
            $process->run();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        }
        $initial = $page->viewData('initialLogsPayload')->first();
        $ajax = $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $latest = $this->getJson(route($role.'.system_logs.fetch', $filters))->assertOk();
        $this->assertSame($initial, $ajax->json('logs.0'));
        $this->assertSame($initial, $latest->json('logs.0'));
        $this->assertSame($log->display_details, $initial['full_description']);
        $this->assertSame($original, $log->fresh()->full_description);
        $this->assertSame($role.'.system_logs.export', $page->viewData('routeNames')['export']);
        $this->getJson(route($role.'.system_logs.check', $filters + ['since_id' => 0]))
            ->assertOk()->assertJsonPath('new_count', 1)->assertJsonPath('new_ids.0', $log->id);
    }

    public static function accessCases(): array
    {
        return [
            'admin viewer' => ['admin', ['view_system_logs'], true, false, false],
            'admin exporter' => ['admin', ['export_system_logs'], true, true, false],
            'admin archiver' => ['admin', ['archive_system_logs'], true, false, true],
            'admin without permissions' => ['admin', [], false, false, false],
            'dentist viewer' => ['dentist', ['view_system_logs'], true, false, false],
            'dentist exporter' => ['dentist', ['export_system_logs'], true, true, false],
            'dentist archiver' => ['dentist', ['archive_system_logs'], true, false, true],
            'dentist without permissions' => ['dentist', [], false, false, false],
            'super administrator' => ['super_admin', [], true, true, true],
        ];
    }

    #[DataProvider('accessCases')]
    public function test_sidebar_page_and_actions_agree_with_route_permissions(
        string $role, array $permissions, bool $canView, bool $canExport, bool $canArchive
    ): void {
        $this->signIn($role, $permissions);
        $layout = $role === 'super_admin' ? 'admin' : $role;
        $response = $this->get(route($layout.'.system_logs'));
        if ($canView) {
            $response->assertOk()
                ->assertSee('const CAN_VIEW_SYSTEM_LOGS = true;', false)
                ->assertSee('const CAN_EXPORT_SYSTEM_LOGS = '.($canExport ? 'true' : 'false').';', false)
                ->assertSee('const CAN_ARCHIVE_SYSTEM_LOGS = '.($canArchive ? 'true' : 'false').';', false);
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(1, $xpath->query('//aside[@id="sidebar"]//a[@href="'.route($layout.'.system_logs').'" and contains(@class,"active")]')->length);
            $this->assertSame(1, $xpath->query('//a[contains(@class,"drawer-link") and @href="'.route($layout.'.system_logs').'" and contains(@class,"active")]')->length);
        } else {
            $response->assertForbidden();
            $sidebar = view('components.global-sidebar', ['role' => $layout])->render();
            $this->assertStringNotContainsString(route($layout.'.system_logs'), $sidebar);
            $this->getJson(route($layout.'.system_logs.fetch'))->assertForbidden();
            $this->getJson(route($layout.'.system_logs.check'))->assertForbidden();
        }
        if (! $canExport) {
            $this->getJson(route($layout.'.system_logs.export'))->assertForbidden();
        }
        if (! $canArchive) {
            $this->postJson(route($layout.'.system_logs.archive'), ['older_than_days' => 90])->assertForbidden();
        }
    }

    #[DataProvider('layouts')]
    public function test_archive_keeps_filters_and_only_archives_old_matching_logs(string $role): void
    {
        $this->signIn($role, ['view_system_logs', 'archive_system_logs']);
        $old = AuditLog::create(['actor_role' => 'admin', 'action' => 'view', 'module' => 'merge_review', 'description' => 'Old matching event']);
        $old->forceFill(['created_at' => now()->subDays(100)])->save();
        $other = AuditLog::create(['actor_role' => 'patient', 'action' => 'view', 'module' => 'merge_review', 'description' => 'Old patient event']);
        $other->forceFill(['created_at' => now()->subDays(100)])->save();
        $recent = AuditLog::create(['actor_role' => 'admin', 'action' => 'view', 'module' => 'merge_review', 'description' => 'Recent matching event']);
        $this->postJson(route($role.'.system_logs.archive'), [
            'older_than_days' => 90, 'role' => 'admin', 'module' => 'merge_review',
        ])->assertOk()->assertJsonPath('archived_count', 1);
        $this->assertTrue($old->fresh()->is_archived);
        $this->assertFalse($other->fresh()->is_archived);
        $this->assertFalse($recent->fresh()->is_archived);
        $this->getJson(route($role.'.system_logs', ['status' => 'archived', 'module' => 'merge_review']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonCount(1, 'logs')->assertJsonPath('logs.0.id', $old->id);
    }

    #[DataProvider('layouts')]
    public function test_export_passes_filtered_related_details_to_pdf(string $role): void
    {
        $this->signIn($role, ['export_system_logs']);
        $log = AuditLog::create(['actor_role' => 'patient', 'actor_name' => 'Exported Patient',
            'action' => 'login', 'module' => 'merge_review', 'description' => 'Patient logged in']);
        AuditLog::create(['actor_role' => 'dentist', 'action' => 'login', 'module' => 'merge_review', 'description' => 'Dentist logged in']);
        Pdf::shouldReceive('loadView')->once()->with('admin.system-logs-pdf', Mockery::on(function ($data) use ($log) {
            return $data['logs']->count() === 1 && $data['logs']->first()->id === $log->id
                && $data['logs']->first()->actor_name === 'Exported Patient';
        }))->andReturnSelf();
        Pdf::shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));
        $this->get(route($role.'.system_logs.export', ['role' => 'patient', 'module' => 'merge_review']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_retention_review_keeps_system_settings_active_in_sidebar_and_drawer(): void
    {
        $this->signIn('admin', ['manage_system_settings']);
        $page = $this->get(route('admin.retention.review', ['category' => 'patients']))->assertOk();
        $xpath = $this->xpath($page->getContent());
        $this->assertSame(1, $xpath->query('//aside[@id="sidebar"]//a[@href="'.route('admin.system_settings').'" and contains(@class,"active")]')->length);
        $this->assertSame(1, $xpath->query('//a[contains(@class,"drawer-link") and @href="'.route('admin.system_settings').'" and contains(@class,"active")]')->length);
    }

    public function test_super_admin_impersonation_does_not_show_unavailable_sidebar_links(): void
    {
        $this->signIn('super_admin', []);
        $dentist = Role::create(['slug' => 'dentist', 'name' => 'Dentist']);
        $permission = Permission::create(['slug' => 'view_system_logs', 'name' => 'View System Logs']);
        $dentist->permissions()->attach($permission);
        $this->withSession(['role' => 'dentist', 'impersonated_role' => 'dentist']);
        $sidebar = view('components.global-sidebar', ['role' => 'dentist'])->render();
        $this->assertStringNotContainsString(route('dentist.system_logs'), $sidebar);
    }

    public function test_same_timestamp_logs_have_stable_pagination_and_live_order(): void
    {
        $this->signIn('admin', ['view_system_logs']);
        $ids = [];
        for ($i = 0; $i < 12; $i++) {
            $log = AuditLog::create(['action' => 'view', 'module' => 'merge_sort', 'description' => 'Same timestamp event']);
            $log->forceFill(['created_at' => now()->startOfDay()])->save();
            $ids[] = $log->id;
        }
        $filters = ['module' => 'merge_sort', 'per_page' => 10];
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];
        $first = $this->getJson(route('admin.system_logs', $filters), $headers)->assertOk();
        $second = $this->getJson(route('admin.system_logs', $filters + ['page' => 2]), $headers)->assertOk();
        $actual = array_merge(array_column($first->json('logs'), 'id'), array_column($second->json('logs'), 'id'));
        $this->assertSame(array_reverse($ids), $actual);
        $latest = $this->getJson(route('admin.system_logs.fetch', ['module' => 'merge_sort']))->assertOk();
        $this->assertSame($actual, array_column($latest->json('logs'), 'id'));
    }

    public function test_session_dashboard_sidebar_link_opens_and_remains_active(): void
    {
        $this->signIn('admin', ['manage_audit_trail']);
        $this->withSession(['admin_logged_in' => true]);
        $page = $this->get(route('admin.session_management.index'))->assertOk()->assertSee('Session Dashboard');
        $xpath = $this->xpath($page->getContent());
        $this->assertSame(1, $xpath->query('//aside[@id="sidebar"]//a[@href="'.route('admin.session_management.index').'" and contains(@class,"active")]')->length);
        $this->assertSame(1, $xpath->query('//a[contains(@class,"drawer-link") and @href="'.route('admin.session_management.index').'" and contains(@class,"active")]')->length);
        $this->getJson(route('admin.session_management.check'))->assertOk();
    }

    private function signIn(string $slug, array $permissions): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        foreach ($permissions as $permissionSlug) {
            $permission = Permission::firstOrCreate(['slug' => $permissionSlug], ['name' => $permissionSlug, 'module' => 'System Logs']);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $user = User::create(['name' => 'Flow Reviewer', 'email' => 'reviewer@example.test', 'role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['role' => $slug]);

        return $user;
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        return new \DOMXPath($document);
    }
}
