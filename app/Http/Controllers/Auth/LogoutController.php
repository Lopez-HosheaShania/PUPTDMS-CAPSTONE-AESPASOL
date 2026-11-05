<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ConcurrentSessionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutController extends Controller
{
    public function __construct(
        private readonly ConcurrentSessionService $concurrentSessionService
    ) {}

    public function logout(Request $request)
    {
        $user = Auth::user();
        $reason = $request->input('reason') === 'idle' ? 'idle' : 'manual';

        if (!$user) {
            if (session('admin_id')) {
                $user = User::find(session('admin_id'));
            } elseif (session('email')) {
                $user = User::where('email', session('email'))->first();
            }
        }

        $accessToken = $request->session()->get('oidc_access_token') ?: $user?->access_token;
        $jwt = $request->session()->get('auth_jwt_token') ?: $request->cookie('jwt_token');
        $postLogoutRedirect = route('login', [
            'logged_out' => 1,
            'reason' => $reason,
        ]);

        $providerLoggedOut = $this->revokeProviderSession($accessToken);

        if (is_string($jwt) && $jwt !== '') {
            try {
                JWTAuth::manager()->setRefreshFlow(true);
                JWTAuth::setToken($jwt)->invalidate();
            } catch (JWTException $exception) {
                Log::notice('Logout JWT invalidation could not be completed', [
                    'exception_type' => $exception::class,
                ]);
            } finally {
                JWTAuth::manager()->setRefreshFlow(false);
            }
        }

        if ($user) {
            $user->access_token = null;
            $user->refresh_token = null;
            $user->save();
        }

        Cookie::queue(Cookie::forget('jwt_token', '/'));

        if ($user) {
            $this->concurrentSessionService->recordLogoutActivity($user, $reason);
        } else {
            AuditLogger::log(
                'logout',
                'authentication',
                $reason === 'idle'
                    ? 'User was signed out due to inactivity.'
                    : 'User logged out of the system (global logout)'
            );
        }

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
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (!$providerLoggedOut) {
            return redirect()->to($postLogoutRedirect)->with('error',
                'You are signed out of the dental clinic, but the identity provider could not confirm sign-out. Please sign out of the identity provider before signing in again.');
        }

        return redirect()->to($postLogoutRedirect);
    }

    private function revokeProviderSession(?string $accessToken): bool
    {
        if (!$accessToken) {
            return true;
        }

        $url = config('services.oidc.logout_url');
        $clientId = config('services.oidc.client_id');
        if (!$url || !$clientId) {
            Log::warning('IDP logout is not configured');
            return false;
        }

        try {
            $response = Http::withToken($accessToken)->acceptJson()
                ->connectTimeout(3)->timeout(5)->withoutRedirecting()
                ->post($url, ['client_id' => $clientId]);

            if ($response->successful() || $response->status() === 302) {
                return true;
            }

            Log::warning('IDP logout was rejected', ['status' => $response->status()]);
        } catch (ConnectionException) {
            Log::warning('IDP logout could not connect');
        }

        return false;
    }
}
