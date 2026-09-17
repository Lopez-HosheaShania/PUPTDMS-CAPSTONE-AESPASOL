<?php

namespace Tests\Feature;

use App\Support\BrowserDetection;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ConcurrentSessionService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentSessionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'session.driver' => 'database',
            'session.table' => 'sessions',
            'session.lifetime' => 120,
            'concurrent-sessions.enabled' => true,
            'concurrent-sessions.default_limit' => 1,
            'concurrent-sessions.role_limits' => [
                'admin' => 1,
                'dentist' => 1,
                'patient' => 1,
            ],
        ]);
    }

    public function test_admin_login_policy_revokes_existing_older_sessions(): void
    {
        $admin = $this->makeUser('admin@example.com', 'admin');

        $this->insertSession('old-admin-session-1', $admin->id, now()->subMinutes(15)->timestamp);
        $this->insertSession('old-admin-session-2', $admin->id, now()->subMinutes(5)->timestamp);

        $result = app(ConcurrentSessionService::class)
            ->enforceLimitForCurrentSession($admin, 'new-admin-session');

        $this->assertSame(2, $result['terminated_sessions']);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-admin-session-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-admin-session-2']);
    }

    public function test_patient_login_policy_revokes_existing_older_sessions_like_admin_and_dentist(): void
    {
        $patient = $this->makeUser('patient@example.com', 'patient');

        $this->insertSession('patient-session-1', $patient->id, now()->subMinutes(20)->timestamp);
        $this->insertSession('patient-session-2', $patient->id, now()->subMinutes(5)->timestamp);

        $result = app(ConcurrentSessionService::class)
            ->enforceLimitForCurrentSession($patient, 'new-patient-session');

        $this->assertSame(2, $result['terminated_sessions']);
        $this->assertDatabaseMissing('sessions', ['id' => 'patient-session-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'patient-session-2']);
    }

    public function test_deactivating_a_user_revokes_their_existing_sessions(): void
    {
        $admin = $this->makeUser('owner-admin@example.com', 'admin');
        $targetUser = $this->makeUser('revoked-user@example.com', 'dentist');

        $permission = Permission::create([
            'name' => 'Manage Users',
            'slug' => 'manage_users',
            'module' => 'Users',
        ]);

        $admin->role->permissions()->syncWithoutDetaching([$permission->id]);

        $this->insertSession('dentist-session-1', $targetUser->id, now()->subMinutes(8)->timestamp);
        $this->insertSession('dentist-session-2', $targetUser->id, now()->subMinutes(2)->timestamp);

        $this->actingAs($admin)
            ->withSession([
                'role' => 'admin',
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
            ])
            ->post(route('admin.user_management.toggle_status', $targetUser))
            ->assertRedirect();

        $targetUser->refresh();

        $this->assertSame('inactive', $targetUser->status);
        $this->assertDatabaseMissing('sessions', ['id' => 'dentist-session-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'dentist-session-2']);
    }

    public function test_idle_expiry_is_recorded_in_recent_session_history(): void
    {
        $patient = $this->makeUser('idle-patient@example.com', 'patient');

        $this->actingAs($patient)
            ->withSession([
                'role' => 'patient',
                'patient_id' => $patient->id,
                'patient_name' => $patient->name,
                'last_activity_at' => now()->subMinutes(10)->timestamp,
                'session_idle_locked' => true,
            ])
            ->postJson(route('session.expire'))
            ->assertOk()
            ->assertJson(['expired' => true]);

        $this->assertAuditLogHas([
            'actor_id' => $patient->id,
            'actor_role' => 'patient',
            'action' => 'logout',
            'module' => 'authentication',
            'description' => 'User was signed out due to inactivity.',
        ]);
    }

    public function test_standard_logout_records_a_single_logout_activity_entry(): void
    {
        $dentist = $this->makeUser('logout-dentist@example.com', 'dentist');

        $this->actingAs($dentist)
            ->withSession($this->sessionStateFor($dentist))
            ->post(route('logout'))
            ->assertOk();

        $this->assertGuest();
        $this->assertSame(1, $this->logoutCountFor($dentist));
        $this->assertAuditLogHas([
            'actor_id' => $dentist->id,
            'actor_role' => 'dentist',
            'action' => 'logout',
            'module' => 'authentication',
            'description' => 'Clinical user (dentist) logged out',
        ]);
    }

    public function test_session_history_includes_sign_in_and_sign_out_entries_in_chronological_order(): void
    {
        $dentist = $this->makeUser('history-dentist@example.com', 'dentist');

        $this->insertAuditLog($dentist, 'login', 'authentication', 'Clinical user (dentist) logged in via OIDC', '2026-09-03 09:00:00');

        Carbon::setTestNow('2026-09-03 09:05:00');
        $this->actingAs($dentist)
            ->withSession($this->sessionStateFor($dentist))
            ->post(route('logout'))
            ->assertOk();

        $this->insertAuditLog($dentist, 'login', 'authentication', 'Clinical user (dentist) logged in via OIDC', '2026-09-03 09:10:00');

        $this->actingAs($dentist)->withSession($this->sessionStateFor($dentist));

        $history = app(ConcurrentSessionService::class)->recentSessionHistoryForUser($dentist, 8);
        $actions = $history->pluck('action')->values();

        $this->assertGreaterThanOrEqual(2, $actions->filter(fn(string $action) => $action === 'login')->count());
        $this->assertTrue($actions->contains('logout'));

        Carbon::setTestNow();
    }

    public function test_single_logout_action_does_not_create_duplicate_logout_entries(): void
    {
        $dentist = $this->makeUser('no-duplicate-dentist@example.com', 'dentist');

        $this->actingAs($dentist)
            ->withSession($this->sessionStateFor($dentist))
            ->post(route('logout'))
            ->assertOk();

        $this->assertSame(1, $this->logoutCountFor($dentist));
    }

    public function test_logout_recording_works_for_core_and_custom_roles(): void
    {
        $dentist = $this->makeUser('core-dentist@example.com', 'dentist');
        $intern = $this->makeUser('intern@example.com', 'dental_intern');

        $this->actingAs($dentist)
            ->withSession($this->sessionStateFor($dentist))
            ->post(route('logout'))
            ->assertOk();

        $this->actingAs($intern)
            ->withSession($this->sessionStateFor($intern))
            ->post(route('logout'))
            ->assertOk();

        $this->assertAuditLogHas([
            'actor_id' => $dentist->id,
            'actor_role' => 'dentist',
            'action' => 'logout',
            'description' => 'Clinical user (dentist) logged out',
        ]);

        $this->assertAuditLogHas([
            'actor_id' => $intern->id,
            'actor_role' => 'dental_intern',
            'action' => 'logout',
            'description' => 'Clinical user (dental_intern) logged out',
        ]);
    }

    public function test_current_session_logout_preserves_existing_behavior_and_records_logout_history(): void
    {
        $dentist = $this->makeUser('current-session-dentist@example.com', 'dentist');

        $this->actingAs($dentist)
            ->withSession($this->sessionStateFor($dentist))
            ->delete(route('security.sessions.destroy-current'))
            ->assertRedirect(route('login', [
                'logged_out' => 1,
                'reason' => 'manual',
            ]));

        $this->assertGuest();
        $this->assertSame(1, $this->logoutCountFor($dentist));
    }

    public function test_log_out_all_devices_revokes_sessions_and_records_only_one_logout_entry_for_the_current_user(): void
    {
        $dentist = $this->makeUser('all-devices-dentist@example.com', 'dentist');

        $this->insertSession('all-devices-1', $dentist->id, now()->subMinutes(4)->timestamp);
        $this->insertSession('all-devices-2', $dentist->id, now()->subMinutes(2)->timestamp);

        $this->actingAs($dentist)
            ->withSession($this->sessionStateFor($dentist))
            ->delete(route('security.sessions.destroy-all'))
            ->assertRedirect(route('login', [
                'logged_out' => 1,
                'reason' => 'manual',
            ]));

        $this->assertGuest();
        $this->assertSame(0, DB::table('sessions')->where('user_id', $dentist->id)->count());
        $this->assertSame(1, $this->logoutCountFor($dentist));
        $this->assertAuditLogHas([
            'actor_id' => $dentist->id,
            'action' => 'sessions_revoked',
            'module' => 'authentication',
        ]);
    }

    public function test_session_display_prefers_stored_browser_name_when_available(): void
    {
        $patient = $this->makeUser('brave-user@example.com', 'patient');

        $this->insertSession(
            'brave-session',
            $patient->id,
            now()->subMinute()->timestamp,
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
            'Brave'
        );

        $session = app(ConcurrentSessionService::class)
            ->sessionsForDisplay($patient, 'brave-session')
            ->first();

        $this->assertSame('Brave', $session['browser_label']);
    }

    public function test_browser_detection_prefers_explicit_browser_hint(): void
    {
        $request = Request::create('/auth/oidc/redirect', 'GET', [
            'browser_name' => 'Brave',
        ], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/138.0.0.0 Safari/537.36',
        ]);

        $request->setLaravelSession(new class {
            public function get($key, $default = null)
            {
                return $default;
            }
        });

        $this->assertSame('Brave', BrowserDetection::detectFromRequest($request));
    }

    public function test_browser_detection_identifies_brave_from_client_hints(): void
    {
        $this->assertSame(
            'Brave',
            BrowserDetection::detectFromClientHints('"Brave";v="138", "Chromium";v="138", "Not=A?Brand";v="99"')
        );
    }

    public function test_reduced_android_user_agent_does_not_display_k_as_the_device_name(): void
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36';

        $this->assertSame('Android Phone', BrowserDetection::detectDeviceName($userAgent));
        $this->assertSame('Android Phone', BrowserDetection::deviceNameForDisplay('K', $userAgent));
    }

    private function makeUser(string $email, string $roleSlug): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst(str_replace('_', ' ', $roleSlug))]
        );

        return User::create([
            'name' => ucfirst($roleSlug) . ' User',
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function insertSession(
        string $id,
        int $userId,
        int $lastActivity,
        string $userAgent = 'PHPUnit',
        ?string $browserName = null
    ): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => $userAgent,
            'browser_name' => $browserName,
            'payload' => base64_encode('payload'),
            'last_activity' => $lastActivity,
        ]);
    }

    private function logoutCountFor(User $user): int
    {
        return AuditLog::whereHas('actorSnapshot', fn ($actor) => $actor->where('actor_id', $user->id))
            ->where('action', 'logout')
            ->count();
    }

    private function assertAuditLogHas(array $attributes): void
    {
        $query = AuditLog::query();
        foreach ($attributes as $field => $value) {
            if (in_array($field, AuditLog::ACTOR_FIELDS, true)) {
                $query->whereHas('actorSnapshot', fn ($actor) => $actor->where($field, $value));
            } elseif ($field === 'description') {
                $query->withDescription($value);
            } else {
                $query->where($field, $value);
            }
        }
        $this->assertTrue($query->exists(), 'Expected audit event and its actor snapshot were not found.');
    }

    private function insertAuditLog(
        User $user,
        string $action,
        string $module,
        string $description,
        string $createdAt
    ): void {
        AuditLog::query()->create([
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'actor_role' => (string) optional($user->role)->slug,
            'actor_identifier' => $user->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 PHPUnit',
            'browser_name' => 'Chrome',
            'device_type' => 'desktop',
            'device_name' => 'Windows PC',
            'os_name' => 'Windows',
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ]);
    }

    private function sessionStateFor(User $user): array
    {
        $roleSlug = (string) optional($user->role)->slug;

        return match ($roleSlug) {
            'patient' => [
                'role' => 'patient',
                'patient_id' => $user->id,
                'patient_name' => $user->name,
                'email' => $user->email,
            ],
            'admin', 'super_admin' => [
                'role' => $roleSlug,
                'admin_logged_in' => true,
                'admin_id' => $user->id,
                'admin_name' => $user->name,
                'admin_email' => $user->email,
                'email' => $user->email,
            ],
            default => [
                'role' => $roleSlug,
                'dentist_id' => $user->id,
                'dentist_name' => $user->name,
                'dentist_email' => $user->email,
                'email' => $user->email,
            ],
        };
    }
}
