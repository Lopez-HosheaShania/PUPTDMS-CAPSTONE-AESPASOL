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

        if ($user) {
            $user->access_token = null;
            $user->refresh_token = null;
            $user->save();
        }

        AuditLogger::log(
            'logout',
            'authentication',
            'User logged out of the system'
        );

        Auth::logout();

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Cookie::queue(Cookie::forget('jwt_token', '/'));

        return redirect('/')
            ->with('success', 'You have been logged out successfully.');
    }
}