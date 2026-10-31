<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions
    ): Response {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (
            $user &&
            ($user->status ?? 'inactive')
            !== 'active'
        ) {
            Auth::guard('patient')
                ->logout();

            Auth::guard('web')
                ->logout();

            Auth::logout();

            $request->session()
                ->forget([
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

                    'role_preview_active',
                    'preview_role',
                    'preview_started_at',

                    'impersonated_role',
                    'impersonated_patient_id',
                    'impersonator_role',
                    'impersonator_admin_id',
                    'impersonator_admin_email',

                    'last_activity_at',
                    'session_idle_locked',
                ]);

            $request->session()
                ->invalidate();

            $request->session()
                ->regenerateToken();

            abort(
                403,
                'You no longer have access.'
            );
        }

        if (!$user) {
            return redirect(
                '/login'
            );
        }

        $requiredPermissions =
            collect(
                $permissions
            )
            ->flatMap(
                fn(
                    string $group
                ) =>
                explode(
                    ',',
                    $group
                )
            )
            ->map(
                fn(
                    string $slug
                ) =>
                trim($slug)
            )
            ->filter()
            ->values();

        $hasPermission =
            $requiredPermissions
            ->contains(
                fn(
                    string $slug
                ) =>
                $user
                    ->hasPermission(
                        $slug
                    )
            );

        if (!$hasPermission) {
            abort(
                403,
                'Unauthorized.'
            );
        }

        return $next($request);
    }
}
