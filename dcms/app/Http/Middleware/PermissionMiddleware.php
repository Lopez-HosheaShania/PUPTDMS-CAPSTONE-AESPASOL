<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!session()->has('role')) {
            return redirect('/login');
        }

        $originalRoleSlug = session('role');
        $activeRoleSlug = session('impersonated_role') ?: $originalRoleSlug;

        if ($originalRoleSlug === 'super_admin') {
            return $next($request);
        }

        $role = Role::with('permissions')->where('slug', $activeRoleSlug)->first();

        if (!$role) {
            abort(403, 'No valid role assigned.');
        }

        $hasPermission = $role->permissions->contains('slug', $permission);

        if (!$hasPermission) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}