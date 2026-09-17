<?php

namespace Tests\Feature;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Admin\SystemLogController;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_logger_writes_one_event_without_recursive_auditing_and_preserves_actor_snapshot(): void
    {
        $user = $this->signIn();
        $count = AuditLog::count();
        AuditLogger::log('test_action', 'test_module', 'Test event');
        $this->assertSame($count + 1, AuditLog::count());
        $log = AuditLog::where('action', 'test_action')->sole();
        $this->assertSame($user->id, $log->actor_id);
        $this->assertSame('Audit Admin', $log->actor_name);
        $this->assertSame('admin', $log->actor_role);
        $this->assertArrayNotHasKey('actor_snapshot', $log->toArray());
        $this->assertDatabaseHas('audit_log_requests', ['audit_log_id' => $log->id]);
        $user->update(['name' => 'New Name']);
        $this->assertSame('Audit Admin', $log->fresh()->actor_name);
        $this->assertSame('New Name', $log->fresh()->user->name);
        $user->delete();
        $this->assertSame('Audit Admin', $log->fresh()->actor_name);
        $this->assertSame($user->id, $log->fresh()->actor_id);
    }

    public function test_filters_export_and_archive_use_related_actor_details(): void
    {
        $this->signIn();
        $log = AuditLog::create(['actor_name' => 'Historical Dentist', 'actor_role' => 'dentist',
            'actor_identifier' => 'old-identifier', 'action' => 'login', 'module' => 'authentication',
            'description' => 'Historical login', 'browser_name' => 'Brave', 'device_name' => 'Laptop']);
        DB::table('audit_logs')->where('id', $log->id)->update(['created_at' => now()->subDays(100)]);
        $controller = app(SystemLogController::class);
        $request = Request::create('/system-logs', 'GET', ['role' => 'dentist', 'search' => 'Historical Dentist']);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = $controller->index($request)->getData(true);
        $this->assertSame(1, $response['pagination']['total']);
        $this->assertSame('Historical Dentist', $response['logs'][0]['actor_name']);
        $this->assertSame(1, $response['counts']['dentist']);

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('PDF test response'));
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()
            ->with('admin.system-logs-pdf', \Mockery::on(function ($data) {
                return $data['logs']->count() === 1 && $data['logs']->first()->actor_name === 'Historical Dentist';
            }))->andReturn($pdf);
        $controller->export($request);
        $archive = $controller->archive(Request::create('/system-logs/archive', 'POST', [
            'role' => 'dentist', 'search' => 'Historical Dentist', 'older_than_days' => 30,
        ]))->getData(true);
        $this->assertSame(1, $archive['archived_count']);
        $this->assertTrue($log->fresh()->is_archived);
        $this->assertSame('Brave', $log->fresh()->browser_name);
    }

    public function test_migration_round_trip_preserves_guest_actor_device_and_archive_values(): void
    {
        $log = AuditLog::create(['actor_id' => null, 'actor_name' => 'Unknown User', 'actor_role' => 'guest',
            'actor_identifier' => null, 'action' => 'error', 'module' => 'system', 'description' => '419 | CSRF token mismatch.',
            'ip_address' => '::1', 'user_agent' => str_repeat('Agent ', 100), 'browser_name' => 'Firefox',
            'device_type' => 'desktop', 'device_name' => 'Test PC', 'os_name' => 'Windows',
            'is_archived' => true, 'archived_at' => '2026-09-07 09:00:00']);
        $before = $log->fresh()->toArray();
        $migration = require database_path('migrations/2026_09_07_090000_separate_audit_actor_and_request_details.php');
        $migration->down();
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id, 'browser_name' => 'Firefox', 'actor_role' => 'guest']);
        $migration->up();
        $this->assertSame($before, $log->fresh()->toArray());
        $this->assertFalse(Schema::hasColumn('audit_logs', 'user_agent'));
        $log->delete();
        $this->assertDatabaseCount('audit_log_actors', 0);
        $this->assertDatabaseCount('audit_log_requests', 0);
    }

    public function test_detail_failure_does_not_leave_a_partial_audit_event(): void
    {
        $count = AuditLog::count();
        DB::unprepared("CREATE TRIGGER reject_audit_request BEFORE INSERT ON audit_log_requests BEGIN SELECT RAISE(ABORT, 'Simulated request failure'); END");
        try {
            AuditLog::create(['action' => 'test', 'module' => 'test', 'actor_role' => 'guest', 'ip_address' => '127.0.0.1']);
            $this->fail('Expected request failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('Simulated request failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER reject_audit_request');
        }
        $this->assertSame($count, AuditLog::count());
        $this->assertDatabaseCount('audit_log_actors', 0);
    }

    public function test_short_descriptions_preserve_full_text_and_searchable_changed_fields(): void
    {
        $this->signIn();
        $original = 'Academic Period #4 updated fields: academic_year_id, academic_term_id.';
        $log = AuditLog::create(['actor_role' => 'admin', 'action' => 'update', 'module' => 'Academic Periods', 'description' => $original]);
        $this->assertSame('Updated Academic Period #4', $log->fresh()->description);
        $this->assertSame($original, $log->fresh()->full_description);
        $this->assertSame(1, AuditLog::withDescription($original)->count());
        $request = Request::create('/system-logs', 'GET', ['search' => 'academic_term_id']);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = app(SystemLogController::class)->index($request)->getData(true);
        $this->assertSame($original, $response['logs'][0]['full_description']);
        $log->update(['description' => 'Short replacement']);
        $this->assertSame('Short replacement', $log->fresh()->full_description);
        $log->update(['description' => null]);
        $this->assertNull($log->fresh()->full_description);
    }

    public function test_description_migration_restores_original_text_and_preserves_timestamps(): void
    {
        $log = AuditLog::create(['action' => 'view', 'module' => 'admin_dashboard', 'description' => 'Admin viewed the dashboard']);
        $migration = require database_path('migrations/2026_09_07_100000_shorten_audit_descriptions.php');
        $migration->down();
        $before = DB::table('audit_logs')->where('id', $log->id)->first();
        $this->assertSame('Admin viewed the dashboard', $before->description);
        $migration->up();
        $this->assertSame('Viewed the dashboard', $log->fresh()->description);
        $this->assertSame($before->description, $log->fresh()->full_description);
        $migration->down();
        $this->assertEquals($before, DB::table('audit_logs')->where('id', $log->id)->first());
        $migration->up();
    }

    public function test_long_errors_are_summarized_without_losing_original_text(): void
    {
        $text = '500 | '.str_repeat('Detailed error context. ', 25);
        $log = AuditLog::create(['action' => 'error', 'module' => 'system', 'description' => $text]);
        $this->assertSame('500 | Error recorded', $log->fresh()->description);
        $this->assertSame($text, $log->fresh()->full_description);
        $this->assertSame('419 | CSRF token mismatch.', \App\Support\AuditDescription::summarize('419 | CSRF token mismatch.'));
    }

    private function signIn(): User
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $user = User::create(['name' => 'Audit Admin', 'email' => 'audit-admin@example.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['role' => 'admin']);
        app('request')->setLaravelSession(app('session.store'));

        return $user;
    }
}
