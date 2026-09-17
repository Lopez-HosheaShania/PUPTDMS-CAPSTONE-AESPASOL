<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ConcurrentSessionService;
use App\Services\IdpHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BackupLoginController extends Controller
{
    public function __construct(
        private readonly ConcurrentSessionService $concurrentSessionService,
        private readonly IdpHealthService $idpHealthService
    ) {}

    public function show()
    {
        if ($this->idpHealthService->isAvailable()) {
            return redirect()->route('login')
                ->with('error', 'Local admin login is only available while the primary SSO service is unavailable.');
        }

        return view('auth.backup-login', $this->idpHealthService->loginViewData());
    }

    public function store(Request $request): RedirectResponse|Response
    {
        if ($this->idpHealthService->isAvailable()) {
            return redirect()->route('login')
                ->with('error', 'Local admin login is only available while the primary SSO service is unavailable.');
        }

        $this->concurrentSessionService->rememberBrowserHint($request);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($this->hasTooManyAttempts($request)) {
            return $this->sendRateLimitedResponse($request, $credentials['email']);
        }

        $user = User::with('role')
            ->where('email', $credentials['email'])
            ->first();

        if (!$user) {
            $this->hitRateLimiter($request);
            $this->logFailedAttempt($credentials['email'], 'Backup login failed for unknown email address.');

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'No local account associated with this email.');
        }

        if ($this->isAccountLocked($user)) {
            $this->logFailedAttempt($user->email, 'Backup login blocked because the account is temporarily locked.', $user);

            return back()
                ->withInput($request->only('email'))
                ->with('error', $this->lockoutMessage($user));
        }

        if (($user->status ?? 'inactive') !== 'active') {
            $this->hitRateLimiter($request);
            $this->logFailedAttempt($user->email, 'Backup login blocked because the local admin account is inactive.', $user);

            return $this->renderInactiveAccessPage();
        }

        $roleSlug = optional($user->role)->slug;

        if ($roleSlug !== 'admin') {
            $this->hitRateLimiter($request);
            $this->logFailedAttempt($user->email, 'Backup login blocked because the account does not have the admin role.', $user);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Local login is restricted to admin accounts only. Patients and dentists must sign in through the IdP.');
        }

        if (!filled($user->password)) {
            $this->hitRateLimiter($request);
            $this->logFailedAttempt($user->email, 'Backup login blocked because no local backup password is configured.', $user);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'No local backup password is set for this admin account yet.');
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            $this->hitRateLimiter($request);
            $this->recordUserFailure($user);

            if ($this->isAccountLocked($user)) {
                $this->logFailedAttempt($user->email, 'Backup login temporarily locked after repeated failed password attempts.', $user);

                return back()
                    ->withInput($request->only('email'))
                    ->with('error', $this->lockoutMessage($user));
            }

            $remaining = max(
                0,
                (int) config('security.login.max_attempts', 5) - (int) $user->failed_login_attempts
            );

            $this->logFailedAttempt(
                $user->email,
                'Backup login failed because an invalid password was provided.',
                $user
            );

            return back()
                ->withInput($request->only('email'))
                ->with('error', $remaining > 0
                    ? "Invalid email or password. {$remaining} attempt(s) remaining before temporary lockout."
                    : 'Invalid email or password.');
        }

        Auth::guard('web')->login($user);

        $request->session()->regenerate();
        $this->clearLoginSecurityState($request, $user);
        $request->session()->put('role', $roleSlug);
        $request->session()->forget(['patient_id', 'dentist_id', 'dentist_email']);
        $request->session()->put('admin_id', $user->id);
        $request->session()->put('admin_email', $user->email);
        $this->concurrentSessionService->syncCurrentSessionMetadata($request);

        $sessionResult = $this->concurrentSessionService->enforceLimitForCurrentSession(
            $user,
            $request->session()->getId()
        );

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        AuditLogger::log(
            'login',
            'authentication',
            'Admin logged in via backup login'
        );

        $redirect = redirect()->route('admin.admin.dashboard')
            ->with('login_as', $user->name ?: $user->email);

        if (($sessionResult['terminated_sessions'] ?? 0) > 0) {
            $redirect->with(
                'success',
                'Logged in successfully. Older active session(s) were closed for your account.'
            );
        }

        return $redirect;
    }

    private function hasTooManyAttempts(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->throttleKey($request),
            (int) config('security.login.max_attempts', 5)
        );
    }

    private function hitRateLimiter(Request $request): void
    {
        RateLimiter::hit(
            $this->throttleKey($request),
            (int) config('security.login.decay_seconds', 60)
        );
    }

    private function throttleKey(Request $request): string
    {
        return Str::lower((string) $request->input('email')) . '|' . $request->ip();
    }

    private function isAccountLocked(User $user): bool
    {
        if (!$this->supportsLoginSecurityColumns()) {
            return false;
        }

        return $user->locked_until !== null && $user->locked_until->isFuture();
    }

    private function recordUserFailure(User $user): void
    {
        if (!$this->supportsLoginSecurityColumns()) {
            return;
        }

        $attempts = (int) $user->failed_login_attempts + 1;
        $maxAttempts = (int) config('security.login.max_attempts', 5);
        $lockoutSeconds = (int) config('security.login.lockout_seconds', 900);

        $user->forceFill([
            'failed_login_attempts' => $attempts,
            'last_failed_login_at' => now(),
            'locked_until' => $attempts >= $maxAttempts ? now()->addSeconds($lockoutSeconds) : null,
        ])->save();
    }

    private function clearLoginSecurityState(Request $request, User $user): void
    {
        RateLimiter::clear($this->throttleKey($request));

        if (!$this->supportsLoginSecurityColumns()) {
            return;
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
            'locked_until' => null,
        ])->save();
    }

    private function sendRateLimitedResponse(Request $request, string $email): RedirectResponse
    {
        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        $this->logFailedAttempt(
            $email,
            "Backup login rate-limited after repeated attempts. Retry after {$seconds} second(s)."
        );

        return back()
            ->withInput($request->only('email'))
            ->with('error', "Too many login attempts. Please wait {$seconds} second(s) before trying again.");
    }

    private function lockoutMessage(User $user): string
    {
        $seconds = max(1, now()->diffInSeconds($user->locked_until, false));

        return "Your account is temporarily locked due to repeated failed login attempts. Try again in {$seconds} second(s).";
    }

    private function logFailedAttempt(string $email, string $description, ?User $user = null): void
    {
        AuditLogger::log(
            'login_failed',
            'authentication',
            trim($description . ' [email: ' . Str::lower($email) . ']' . ($user ? ' [user_id: ' . $user->id . ']' : ''))
        );
    }

    private function supportsLoginSecurityColumns(): bool
    {
        static $supportsColumns;

        if ($supportsColumns === null) {
            $supportsColumns = Schema::hasColumns('user_auth_security', [
                'failed_login_attempts',
                'last_failed_login_at',
                'locked_until',
            ]);
        }

        return $supportsColumns;
    }

    private function renderInactiveAccessPage(): Response
    {
        return response()->view('errors.403', [
            'exception' => new AccessDeniedHttpException('Your account is inactive. Please contact the administrator.'),
        ], 403);
    }
}
