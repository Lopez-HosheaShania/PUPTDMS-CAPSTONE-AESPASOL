<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ConcurrentSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class SessionManagementController extends Controller
{
    public function __construct(
        private readonly ConcurrentSessionService $concurrentSessionService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $currentSessionId = $request->session()->getId();
        $sessions = $this->concurrentSessionService->sessionsForDisplay($user, $currentSessionId);
        $history = $this->concurrentSessionService->recentSessionHistoryForUser($user);
        $role = $this->normalizeRole((string) (session('impersonated_role') ?: optional($user->role)->slug ?: session('role')));

        return view('shared.sessions', [
            'role' => $role,
            'sessions' => $sessions,
            'history' => $history,
            'currentSessionCount' => $sessions->where('is_current', true)->count(),
            'otherSessionsCount' => $sessions->where('is_current', false)->count(),
            'sessionLimit' => $this->concurrentSessionService->getSessionLimitForUser($user),
            'notifications' => collect(),
        ]);
    }

    public function activity(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'active' => false,
            ], 401);
        }

        $activeRoleSlug = (string) (
            $request->session()->get('impersonated_role')
            ?: $request->session()->get('role')
            ?: optional($user->role)->slug
        );

        $timeoutSeconds =
            $user->idleTimeoutSeconds(
                $activeRoleSlug
            );

        if ($timeoutSeconds <= 0) {
            return response()->json([
                'active' => true,
                'exempt' => true,
            ]);
        }

        if (
            (bool) $request->session()->get(
                'session_idle_locked',
                false
            )
        ) {
            return response()->json([
                'expired' => true,
                'reason' => 'idle',
            ], 401);
        }

        $serverNow =
            now()->getTimestamp();

        $lastActivityAt =
            (int) $request->session()->get(
                'last_activity_at',
                $serverNow
            );

        $expiresAt =
            $lastActivityAt +
            $timeoutSeconds;

        if ($serverNow >= $expiresAt) {
            $request->session()->put(
                'session_idle_locked',
                true
            );

            return response()->json([
                'expired' => true,
                'reason' => 'idle',
                'server_now' => $serverNow,
                'expires_at' => $expiresAt,
                'remaining_seconds' => 0,
                'timeout_seconds' => $timeoutSeconds,
            ], 401);
        }

        if ($request->boolean('touch')) {
            $lastActivityAt =
                $serverNow;

            $request->session()->put(
                'last_activity_at',
                $lastActivityAt
            );

            $expiresAt =
                $lastActivityAt +
                $timeoutSeconds;
        }

        return response()->json([
            'active' => true,
            'server_now' => $serverNow,
            'last_activity_at' => $lastActivityAt,
            'expires_at' => $expiresAt,
            'remaining_seconds' => max(
                0,
                $expiresAt - $serverNow
            ),
            'timeout_seconds' => $timeoutSeconds,
        ]);
    }

    public function expire(
        Request $request
    ): JsonResponse {
        if ($this->isExemptFromIdleTimeout($request)) {
            $request->session()->forget('session_idle_locked');

            return response()->json([
                'expired' => false,
                'active' => true,
                'server_now' => now()->getTimestamp(),
            ]);
        }

        $this->logoutCurrentSession(
            $request,
            $request->user(),
            false,
            'idle'
        );

        return response()->json([
            'expired' => true,
            'active' => false,
            'reason' => 'idle',
            'server_now' => now()->getTimestamp(),
        ]);
    }

    public function destroy(Request $request, string $reference): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $revoked = $this->concurrentSessionService->revokeSessionByReference(
            $user,
            $reference,
            $request->session()->getId()
        );

        return back()->with(
            $revoked ? 'success' : 'error',
            $revoked
                ? 'The selected session has been logged out.'
                : 'That session could not be removed. It may already be inactive or belong to another device state.'
        );
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $revoked = $this->concurrentSessionService->revokeOtherSessions(
            $user,
            $request->session()->getId(),
            'user_requested_other_session_logout'
        );

        return back()->with(
            $revoked > 0 ? 'success' : 'error',
            $revoked > 0
                ? 'All other active sessions were logged out.'
                : 'No other active sessions were found.'
        );
    }

    public function destroyCurrent(Request $request): RedirectResponse
    {
        $this->logoutCurrentSession($request, $request->user(), false, 'manual');

        return redirect()->route('login', [
            'logged_out' => 1,
            'reason' => 'manual',
        ])->with('success', 'This session has been logged out.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $this->concurrentSessionService->revokeAllSessions($user, null, 'user_requested_global_logout');
        $this->logoutCurrentSession($request, $user, true, 'manual');

        return redirect()->route('login', [
            'logged_out' => 1,
            'reason' => 'manual',
        ])->with('success', 'All devices have been logged out.');
    }

    private function idleExpiredResponse(
        Request $request,
        int $serverNow,
        int $timeoutSeconds,
        ?int $lastActivityAt = null
    ): JsonResponse {
        $lastActivityAt ??= (int) $request->session()->get(
            'last_activity_at',
            $serverNow - $timeoutSeconds
        );

        return response()->json([
            'active' => false,
            'expired' => true,
            'reason' => 'idle',
            'server_now' => $serverNow,
            'timeout_seconds' => $timeoutSeconds,
            'last_activity_at' => $lastActivityAt,
            'expires_at' => $lastActivityAt + $timeoutSeconds,
            'remaining_seconds' => 0,
        ], 401);
    }

    private function idleTimeoutSeconds(
        Request $request
    ): int {
        $user = $request->user();

        if (!$user) {
            return 0;
        }

        $role = (string) (
            $request->session()->get('impersonated_role')
            ?: $request->session()->get('role')
            ?: optional($user->role)->slug
        );

        return max(
            0,
            (int) (
                $user->idleTimeoutSeconds($role) ??
                config('session.idle_timeout_seconds', 600)
            )
        );
    }

    private function normalizeRole(string $role): string
    {
        return $role === 'super_admin' ? 'admin' : ($role !== '' ? $role : 'patient');
    }

    private function isExemptFromIdleTimeout(Request $request): bool
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        return $user->isExemptFromIdleTimeout((string) (
            $request->session()->get('impersonated_role')
            ?: $request->session()->get('role')
            ?: optional($user->role)->slug
        ));
    }

    private function logoutCurrentSession(
        Request $request,
        ?User $user,
        bool $alreadyRevokedAll,
        string $reason
    ): void {
        if ($user && !$alreadyRevokedAll) {
            $this->concurrentSessionService->revokeAllSessions(
                $user,
                $request->session()->getId(),
                'user_requested_current_session_logout'
            );
        }

        if ($user) {
            $this->concurrentSessionService->recordLogoutActivity($user, $reason);
        }

        Cookie::queue(Cookie::forget('jwt_token', '/'));

        Auth::guard('patient')->logout();
        Auth::guard('web')->logout();
        Auth::logout();

        $request->session()->forget([
            'oidc_id_token',
            'oidc_state',
            'role',
            'patient_id',
            'patient_name',
            'email',
            'admin_logged_in',
            'admin_id',
            'admin_name',
            'admin_email',
            'dentist_id',
            'dentist_name',
            'dentist_email',
            'impersonated_role',
            'impersonated_patient_id',
            'impersonator_role',
            'impersonator_admin_id',
            'impersonator_admin_email',
            'last_activity_at',
            'session_idle_locked',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
