<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            if (session('admin_id')) {
                $user = User::find(session('admin_id'));
            } elseif (session('email')) {
                $user = User::where('email', session('email'))->first();
            }
        }

        $idpLogoutUrl = config('services.oidc.logout_url');
        $clientId = config('services.oidc.client_id');
        $postLogoutRedirect = route('login');

        if ($user) {
            $user->access_token = null;
            $user->refresh_token = null;
            $user->save();
        }

        Cookie::queue(Cookie::forget('jwt_token', '/'));

        AuditLogger::log(
            'logout',
            'authentication',
            'User logged out of the system (global logout)'
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($idpLogoutUrl && $clientId) {
            return view('auth.oidc-logout', [
                'logoutUrl' => $idpLogoutUrl,
                'clientId' => $clientId,
                'redirectUrl' => $postLogoutRedirect,
            ]);
        }

        return redirect()->route('login');
    }
}