<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\BrowserDetection;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConcurrentSessionService
{
    public function rememberBrowserHint(Request $request): ?string
    {
        $browserName = BrowserDetection::detectFromRequest($request);

        if ($browserName !== 'Browser') {
            $request->session()->put('browser_name_hint', $browserName);
        }

        return $browserName !== 'Browser' ? $browserName : null;
    }

    public function syncCurrentSessionMetadata(Request $request): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $sessionId = (string) $request->session()->getId();

        if ($sessionId === '') {
            return;
        }

        $request->session()->save();

        $deviceDetails = BrowserDetection::deviceDetailsFromRequest($request);

        $attributes = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now()->getTimestamp(),
        ];

        if ($this->supportsSessionBrowserNameColumn()) {
            $attributes['browser_name'] = $deviceDetails['browser_name'];
        }

        if ($this->supportsSessionDeviceColumns()) {
            $attributes['device_type'] = $deviceDetails['device_type'];
            $attributes['device_name'] = $deviceDetails['device_name'];
            $attributes['os_name'] = $deviceDetails['os_name'];
        }

        DB::table($this->sessionTable())
            ->where('id', $sessionId)
            ->update($attributes);
    }

    public function isEnabled(): bool
    {
        if (!config('concurrent-sessions.enabled', true)) {
            return false;
        }

        return config('session.driver') === 'database'
            && Schema::hasTable($this->sessionTable());
    }

    public function getSessionLimitForUser(User $user): int
    {
        $roleSlug = (string) optional($user->role)->slug;
        $limits = config('concurrent-sessions.role_limits', []);
        $defaultLimit = (int) config('concurrent-sessions.default_limit', 1);

        $limit = $limits[$roleSlug] ?? $defaultLimit;

        return max(1, (int) $limit);
    }

    public function enforceLimitForCurrentSession(User $user, ?string $currentSessionId = null): array
    {
        if (!$this->isEnabled()) {
            return $this->result(0, $this->getSessionLimitForUser($user), false);
        }

        $sessionTable = $this->sessionTable();
        $currentSessionId ??= session()->getId();
        $limit = $this->getSessionLimitForUser($user);

        if ($limit <= 1) {
            $terminatedSessions = $this->revokeOtherSessions(
                $user,
                $currentSessionId,
                'new_login_single_session_policy'
            );

            return $this->result($terminatedSessions, $limit, true);
        }

        return DB::transaction(function () use ($user, $currentSessionId, $limit, $sessionTable) {
            $lockedUser = User::query()
                ->with('role')
                ->lockForUpdate()
                ->findOrFail($user->id);

            $this->pruneExpiredSessionsForUser($lockedUser);

            $activeSessions = $this->activeSessionsQuery($lockedUser->id)
                ->orderBy('last_activity')
                ->get();

            $currentSessionExists = $activeSessions->contains('id', $currentSessionId);
            $projectedCount = $activeSessions->count() + ($currentSessionExists ? 0 : 1);
            $excessCount = max(0, $projectedCount - $limit);

            if ($excessCount === 0) {
                return $this->result(0, $limit, true);
            }

            $sessionsToRevoke = $activeSessions
                ->filter(fn(object $session) => $session->id !== $currentSessionId)
                ->take($excessCount)
                ->pluck('id')
                ->values();

            if ($sessionsToRevoke->isEmpty()) {
                return $this->result(0, $limit, true);
            }

            DB::table($sessionTable)
                ->where('user_id', $lockedUser->id)
                ->whereIn('id', $sessionsToRevoke->all())
                ->delete();

            AuditLogger::log(
                'session_limit_enforced',
                'authentication',
                sprintf(
                    'Concurrent session policy enforced for user #%d. %d older session(s) were revoked.',
                    $lockedUser->id,
                    $sessionsToRevoke->count()
                )
            );

            return $this->result($sessionsToRevoke->count(), $limit, true);
        });
    }

    public function revokeAllSessions(User $user, ?string $exceptSessionId = null, string $reason = 'security_event'): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        $query = DB::table($this->sessionTable())->where('user_id', $user->id);

        if ($exceptSessionId) {
            $query->where('id', '!=', $exceptSessionId);
        }

        $deleted = $query->delete();

        if ($deleted > 0) {
            AuditLogger::log(
                'sessions_revoked',
                'authentication',
                sprintf(
                    'Revoked %d active session(s) for user #%d due to %s.',
                    $deleted,
                    $user->id,
                    $reason
                )
            );
        }

        return $deleted;
    }

    public function revokeOtherSessions(User $user, string $currentSessionId, string $reason = 'security_event'): int
    {
        return $this->revokeAllSessions($user, $currentSessionId, $reason);
    }

    public function pruneExpiredSessionsForUser(User $user): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        return DB::table($this->sessionTable())
            ->where('user_id', $user->id)
            ->where('last_activity', '<', $this->activeCutoffTimestamp())
            ->delete();
    }

    public function activeSessionsForUser(User $user): Collection
    {
        if (!$this->isEnabled()) {
            return collect();
        }

        return $this->activeSessionsQuery($user->id)
            ->orderByDesc('last_activity')
            ->get();
    }

    public function sessionsForDisplay(User $user, string $currentSessionId): Collection
    {
        return $this->activeSessionsForUser($user)
            ->map(function (object $session) use ($currentSessionId) {
                return [
                    'reference' => $this->makePublicReference($session->id),
                    'is_current' => hash_equals((string) $session->id, $currentSessionId),
                    'ip_address' => $session->ip_address ?: 'Unknown',
                    'user_agent' => $session->user_agent ?: 'Unknown device',
                    'device_type' => $session->device_type
                        ?? BrowserDetection::detectDeviceType($session->user_agent),

                    'device_label' => BrowserDetection::deviceNameForDisplay(
                        $session->device_name ?? null,
                        $session->user_agent
                    ),

                    'os_label' => $session->os_name
                        ?? BrowserDetection::detectOperatingSystem($session->user_agent),

                    'browser_label' => $this->browserLabel(
                        $session->browser_name ?? null,
                        $session->user_agent
                    ),
                    'last_activity_at' => now()->setTimestamp((int) $session->last_activity),
                    'last_activity_label' => now()->setTimestamp((int) $session->last_activity)->diffForHumans(),
                ];
            })
            ->sortByDesc(fn(array $session) => $session['last_activity_at']->getTimestamp())
            ->values();
    }

    public function recentSessionHistoryForUser(User $user, int $limit = 8): Collection
    {
        $limit = max(1, min($limit, 20));
        $roleSlug = (string) optional($user->role)->slug;
        $identifiers = array_values(array_unique(array_filter([
            $user->id,
            $roleSlug === 'patient' ? $user->patient?->id : null,
            $roleSlug === 'admin' || $roleSlug === 'super_admin' ? session('admin_id') : null,
            $roleSlug === 'dentist' ? session('dentist_id') : null,
            $roleSlug === 'patient' ? session('patient_id') : null,
        ])));

        return AuditLog::query()
            ->whereHas('actorSnapshot', function ($query) use ($user, $identifiers, $roleSlug) {
                $query->where(function ($strictQuery) use ($user, $roleSlug) {
                    $strictQuery->where('actor_id', $user->id);

                    if ($roleSlug !== '') {
                        $strictQuery->where('actor_role', $roleSlug);
                    }
                });

                if (!empty($identifiers)) {
                    $query->orWhere(function ($legacyQuery) use ($identifiers, $roleSlug) {
                        $legacyQuery->whereNull('actor_id')
                            ->whereIn('actor_identifier', $identifiers);

                        if ($roleSlug !== '') {
                            $legacyQuery->where('actor_role', $roleSlug);
                        }
                    });
                }
            })
            ->whereIn('action', [
                'login',
                'logout',
                'session_limit_enforced',
                'sessions_revoked',
                'session_revoked',
            ])
            ->whereIn('module', ['authentication', 'patient_auth'])
            ->latest()
            ->take($limit)
            ->get()
            ->map(function (AuditLog $entry) {
                $userAgent = (string) ($entry->user_agent ?: 'Unknown device');
                $action = (string) $entry->action;

                return [
                    'action' => $action,
                    'action_label' => $this->formatHistoryAction($action),
                    'action_tone' => $this->historyTone($action),
                    'device_label' => $this->deviceLabel($entry->browser_name ?? null, $userAgent),
                    'browser_label' => $this->browserLabel($entry->browser_name ?? null, $userAgent),
                    'ip_address' => $entry->ip_address ?: 'Unknown',
                    'user_agent' => $userAgent,
                    'description' => $entry->description ?: 'Session activity recorded.',
                    'occurred_at' => $entry->created_at,
                    'occurred_at_label' => optional($entry->created_at)?->diffForHumans(),
                ];
            });
    }

    public function recordLogoutActivity(User $user, string $reason = 'manual', string $module = 'authentication'): void
    {
        AuditLogger::log(
            'logout',
            $module,
            $this->logoutDescription($user, $reason)
        );
    }

    public function revokeSessionByReference(User $user, string $reference, ?string $currentSessionId = null): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $session = $this->findOwnedSessionByReference($user, $reference);

        if (!$session) {
            return false;
        }

        if ($currentSessionId && hash_equals((string) $session->id, $currentSessionId)) {
            return false;
        }

        $deleted = DB::table($this->sessionTable())
            ->where('user_id', $user->id)
            ->where('id', $session->id)
            ->delete();

        if ($deleted > 0) {
            AuditLogger::log(
                'session_revoked',
                'authentication',
                sprintf('User #%d revoked one of their active sessions.', $user->id)
            );
        }

        return $deleted > 0;
    }

    public function paginateActiveSessionsForAdmin(
        int $perPage = 15,
        array $filters = [],
        ?string $currentSessionId = null
    ): LengthAwarePaginatorContract {
        $perPage = in_array($perPage, [10, 15, 20, 50, 100], true) ? $perPage : 15;
        $currentSessionId ??= session()->getId();

        $query = DB::table($this->sessionTable() . ' as sessions')
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
                'users.status as user_status',
                'roles.slug as role_slug',
            ])
            ->whereNotNull('sessions.user_id')
            ->where('sessions.last_activity', '>=', $this->activeCutoffTimestamp());

        if ($this->supportsSessionBrowserNameColumn()) {
            $query->addSelect('sessions.browser_name');
        }

        if ($this->supportsSessionDeviceColumns()) {
            $query->addSelect([
                'sessions.device_type',
                'sessions.device_name',
                'sessions.os_name',
            ]);
        }

        $sort = $this->normalizeSessionSort($filters['sort'] ?? 'recent');
        $role = trim((string) ($filters['role'] ?? ''));
        $device = $this->normalizeSessionDeviceType($filters['device'] ?? 'all');
        $scope = $this->normalizeSessionScope($filters['scope'] ?? 'all');
        $search = trim((string) ($filters['search'] ?? ''));

        if ($role !== '' && $role !== 'all') {
            $query->where('roles.slug', $role);
        }

        if ($scope === 'current') {
            $query->where('sessions.id', $currentSessionId);
        } elseif ($scope === 'others') {
            $query->where('sessions.id', '!=', $currentSessionId);
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('sessions.ip_address', 'like', "%{$search}%")
                    ->orWhere('sessions.user_agent', 'like', "%{$search}%");
            });
        }

        $items = $query
            ->get()
            ->map(function (object $session) use ($currentSessionId) {
                $role = (string) ($session->role_slug ?: 'unknown');
                $deviceType = $this->normalizeSessionDeviceType(
                    $session->device_type ?? null,
                    $session->user_agent
                );
                $lastActivityAt = now()->setTimestamp((int) $session->last_activity);

                return (object) [
                    'reference' => $this->makePublicReference((string) $session->id),
                    'user_id' => $session->user_id,
                    'user_name' => $session->user_name ?: 'Unknown User',
                    'user_email' => $session->user_email ?: 'No email',
                    'user_status' => $session->user_status ?: 'unknown',
                    'role_slug' => $role,
                    'role_label' => $this->formatRoleLabel($role),
                    'ip_address' => $session->ip_address ?: 'Unknown',
                    'user_agent' => $session->user_agent ?: 'Unknown device',
                    'device_type' => $deviceType,

                    'device_label' => BrowserDetection::deviceNameForDisplay(
                        $session->device_name ?? null,
                        $session->user_agent
                    ),

                    'os_label' => $session->os_name
                        ?? BrowserDetection::detectOperatingSystem($session->user_agent),

                    'browser_label' => $this->browserLabel(
                        $session->browser_name ?? null,
                        $session->user_agent
                    ),
                    'last_activity_at' => $lastActivityAt,
                    'last_activity_label' => $lastActivityAt->diffForHumans(),
                    'is_current' => hash_equals((string) $session->id, $currentSessionId),
                ];
            });

        if ($device !== 'all') {
            $items = $items
                ->filter(fn(object $session) => $session->device_type === $device)
                ->values();
        }

        $items = $sort === 'oldest'
            ? $items->sortBy(fn(object $session) => $session->last_activity_at->getTimestamp())->values()
            : $items->sortByDesc(fn(object $session) => $session->last_activity_at->getTimestamp())->values();

        $currentPage = max(1, (int) Paginator::resolveCurrentPage());
        $total = $items->count();
        $results = $items
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $paginator = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        return $paginator;
    }

    public function getAdminSessionStats(): array
    {
        $baseQuery = DB::table($this->sessionTable() . ' as sessions')
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->whereNotNull('sessions.user_id')
            ->where('sessions.last_activity', '>=', $this->activeCutoffTimestamp());

        return [
            'total_sessions' => (clone $baseQuery)->count(),
            'active_users' => (clone $baseQuery)->distinct('sessions.user_id')->count('sessions.user_id'),
            'admin_sessions' => (clone $baseQuery)->whereIn('roles.slug', ['admin', 'super_admin'])->count(),
            'dentist_sessions' => (clone $baseQuery)->where('roles.slug', 'dentist')->count(),
            'patient_sessions' => (clone $baseQuery)->where('roles.slug', 'patient')->count(),
        ];
    }

    public function revokeSessionByReferenceAsAdmin(User $actor, string $reference, ?string $currentSessionId = null): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $session = $this->findSessionByReference($reference);

        if (!$session) {
            return false;
        }

        if ($currentSessionId && hash_equals((string) $session->id, $currentSessionId)) {
            return false;
        }

        $deleted = DB::table($this->sessionTable())
            ->where('id', $session->id)
            ->delete();

        if ($deleted > 0) {
            AuditLogger::log(
                'admin_session_revoked',
                'authentication',
                sprintf('Admin #%d revoked an active session for user #%d.', $actor->id, (int) $session->user_id)
            );
        }

        return $deleted > 0;
    }

    public function revokeAllSessionsForUserAsAdmin(User $actor, User $targetUser, ?string $exceptSessionId = null): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        $query = DB::table($this->sessionTable())->where('user_id', $targetUser->id);

        if ($exceptSessionId) {
            $query->where('id', '!=', $exceptSessionId);
        }

        $deleted = $query->delete();

        if ($deleted > 0) {
            AuditLogger::log(
                'admin_user_sessions_revoked',
                'authentication',
                sprintf(
                    'Admin #%d revoked %d active session(s) for user #%d.',
                    $actor->id,
                    $deleted,
                    $targetUser->id
                )
            );
        }

        return $deleted;
    }

    protected function activeSessionsQuery(int $userId)
    {
        return DB::table($this->sessionTable())
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $this->activeCutoffTimestamp());
    }

    protected function activeSessionsBaseQuery(User $user): Builder
    {
        return DB::table($this->sessionTable())
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $this->activeCutoffTimestamp());
    }

    protected function activeCutoffTimestamp(): int
    {
        $sessionLifetimeSeconds = max(60, (int) config('session.lifetime', 120) * 60);
        $idleTimeoutSeconds = (int) config('session.idle_timeout_seconds', 0);

        if ($idleTimeoutSeconds > 0) {
            $sessionLifetimeSeconds = min($sessionLifetimeSeconds, $idleTimeoutSeconds);
        }

        return now()->subSeconds($sessionLifetimeSeconds)->getTimestamp();
    }

    protected function sessionTable(): string
    {
        return (string) config('session.table', 'sessions');
    }

    protected function makePublicReference(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }

    protected function findOwnedSessionByReference(User $user, string $reference): ?object
    {
        return $this->activeSessionsBaseQuery($user)
            ->get()
            ->first(function (object $session) use ($reference) {
                return hash_equals($this->makePublicReference((string) $session->id), $reference);
            });
    }

    protected function findSessionByReference(string $reference): ?object
    {
        return DB::table($this->sessionTable())
            ->where('last_activity', '>=', $this->activeCutoffTimestamp())
            ->get()
            ->first(function (object $session) use ($reference) {
                return hash_equals($this->makePublicReference((string) $session->id), $reference);
            });
    }

    protected function formatDeviceLabel(?string $userAgent): string
    {
        return $this->deviceLabel(null, $userAgent);
    }

    protected function deviceLabel(?string $browserName, ?string $userAgent): string
    {
        if (blank($userAgent)) {
            return 'Unknown device';
        }

        $platform = match (true) {
            Str::contains($userAgent, 'Windows', true) => 'Windows',
            Str::contains($userAgent, 'Macintosh', true) => 'macOS',
            Str::contains($userAgent, 'Android', true) => 'Android',
            Str::contains($userAgent, ['iPhone', 'iPad', 'iOS'], true) => 'iOS',
            Str::contains($userAgent, 'Linux', true) => 'Linux',
            default => 'Device',
        };

        $browser = $this->browserLabel($browserName, $userAgent);

        return trim($platform . ' - ' . $browser);
    }

    protected function detectBrowser(?string $userAgent): string
    {
        return BrowserDetection::detectFromUserAgent($userAgent);
    }

    protected function browserLabel(?string $browserName, ?string $userAgent): string
    {
        return BrowserDetection::normalizeBrowserName($browserName)
            ?? $this->detectBrowser($userAgent);
    }

    protected function supportsSessionBrowserNameColumn(): bool
    {
        static $supportsColumn;

        if ($supportsColumn === null) {
            $supportsColumn = Schema::hasColumn(
                $this->sessionTable(),
                'browser_name'
            );
        }

        return $supportsColumn;
    }

    protected function supportsSessionDeviceColumns(): bool
    {
        static $supportsColumns;

        if ($supportsColumns === null) {
            $supportsColumns = Schema::hasColumns($this->sessionTable(), [
                'device_type',
                'device_name',
                'os_name',
            ]);
        }

        return $supportsColumns;
    }

    protected function normalizeSessionSort(mixed $sort): string
    {
        return strtolower(trim((string) $sort)) === 'oldest'
            ? 'oldest'
            : 'recent';
    }

    protected function normalizeSessionScope(mixed $scope): string
    {
        $normalized = strtolower(trim((string) $scope));

        return in_array($normalized, ['current', 'others'], true)
            ? $normalized
            : 'all';
    }

    protected function normalizeSessionDeviceType(mixed $deviceType, ?string $userAgent = null): string
    {
        $normalized = strtolower(trim((string) $deviceType));

        return match (true) {
            in_array($normalized, ['phone', 'mobile', 'smartphone'], true) => 'mobile',
            in_array($normalized, ['tablet', 'ipad'], true) => 'tablet',
            in_array($normalized, ['desktop', 'laptop', 'computer', 'pc'], true) => 'desktop',
            $normalized === 'all' => 'all',
            $normalized !== '' => $normalized,
            Str::contains((string) $userAgent, ['iPad', 'Tablet'], true) => 'tablet',
            Str::contains((string) $userAgent, ['iPhone', 'Android', 'Mobile'], true) => 'mobile',
            Str::contains((string) $userAgent, ['Windows', 'Macintosh', 'Linux', 'CrOS'], true) => 'desktop',
            default => 'unknown',
        };
    }

    protected function formatRoleLabel(string $role): string
    {
        return match ($role) {
            'super_admin', 'admin' => 'Admin',
            'dentist' => 'Dentist',
            'patient' => 'Patient',
            default => Str::headline($role !== '' ? $role : 'Unknown'),
        };
    }

    protected function formatHistoryAction(string $action): string
    {
        return match ($action) {
            'login' => 'Signed In',
            'logout' => 'Signed Out',
            'session_limit_enforced' => 'Older Session Replaced',
            'sessions_revoked', 'session_revoked' => 'Session Revoked',
            default => Str::headline(str_replace('_', ' ', $action)),
        };
    }

    protected function historyTone(string $action): string
    {
        return match ($action) {
            'login' => 'success',
            'logout' => 'neutral',
            'session_limit_enforced', 'sessions_revoked', 'session_revoked' => 'warning',
            default => 'neutral',
        };
    }

    protected function logoutDescription(User $user, string $reason): string
    {
        if ($reason === 'idle') {
            return 'User was signed out due to inactivity.';
        }

        $roleSlug = (string) optional($user->role)->slug;

        return match ($roleSlug) {
            'patient' => 'Patient logged out',
            'admin', 'super_admin' => 'Admin logged out',
            default => sprintf(
                'Clinical user (%s) logged out',
                $roleSlug !== '' ? $roleSlug : 'unknown'
            ),
        };
    }

    protected function result(int $terminatedSessions, int $limit, bool $supported): array
    {
        return [
            'supported' => $supported,
            'terminated_sessions' => $terminatedSessions,
            'limit' => $limit,
        ];
    }
}
