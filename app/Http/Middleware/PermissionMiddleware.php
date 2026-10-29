<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && ($user->status ?? 'inactive') !== 'active') {
            Auth::guard('patient')->logout();
            Auth::guard('web')->logout();
            Auth::logout();

            $request->session()->forget([
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

            abort(403, 'You no longer have access.');
        }

        if (!$user) {
            return redirect('/login');
        }

        $actualRoleSlug = optional($user->role)->slug;
        $sessionRoleSlug = session('role') ?: $actualRoleSlug;
        $activeRoleSlug = session('impersonated_role') ?: $sessionRoleSlug;

        if ($actualRoleSlug === 'super_admin' && $activeRoleSlug === 'super_admin') {
            return $next($request);
        }

        $requiredPermissions = collect($permissions)
            ->flatMap(fn (string $group) => explode(',', $group))
            ->map(fn (string $slug) => trim($slug))
            ->filter()
            ->values();

        $hasPermission = $requiredPermissions->contains(function (string $slug) use ($user, $activeRoleSlug) {
            if ($user->hasPermission($slug)) {
                return true;
            }

            if (!$activeRoleSlug || session()->has('impersonated_role')) {
                return false;
            }

            $activeRole = Role::with('permissions')->where('slug', $activeRoleSlug)->first();

            return $activeRole?->permissions->contains('slug', $slug) ?? false;
        });

        if (!$hasPermission) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
