<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        if (($user?->status ?? 'inactive') !== 'active') {
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

        if (in_array($user->role?->slug, ['dentist', 'dentist_role'], true)) {
            $unavailable = \App\Models\DentistTransition::where('dentist_id', $user->id)
                ->whereNotNull('effective_date')
                ->where(function ($q) {
                    $q->where('status', 'active')->orWhere(function ($q) {
                        $q->where('status', 'scheduled')->whereDate('effective_date', '<=', today());
                    });
                })->exists();
            abort_if($unavailable, 403, 'Your care responsibilities are covered by an effective continuity plan. Contact an administrator.');
        }

        $userRole = session('impersonated_role') ?: optional($user->role)->slug;

        if ($userRole === 'super_admin' && in_array('admin', $roles, true)) {
            return $next($request);
        }

        $hasRoleMatch = $userRole && in_array($userRole, $roles, true);
        $hasPermissionAreaAccess = false;

        foreach ($roles as $role) {
            if ($role === 'admin' && $user->canAccessAdminArea($userRole)) {
                $hasPermissionAreaAccess = true;
                break;
            }

            if ($role === 'dentist' && $user->canAccessClinicalArea($userRole)) {
                $hasPermissionAreaAccess = true;
                break;
            }

            if ($role === 'patient' && $user->canAccessPatientArea($userRole)) {
                $hasPermissionAreaAccess = true;
                break;
            }
        }

        if (!$hasRoleMatch && !$hasPermissionAreaAccess) {
            abort(403, 'Unauthorized access.');
        }

        
        if (in_array('patient', $roles, true)) {
            $patientId = session('impersonated_patient_id')
                ?: session('patient_id')
                ?: $user?->patient?->id;

            if ($patientId && !session()->has('patient_id') && !session()->has('impersonated_patient_id')) {
                session(['patient_id' => $patientId]);
            }

            if (!$patientId) {
                if (session()->has('impersonator_role')) {
                    return redirect()->route('admin.admin.dashboard')
                        ->with('error', 'No patient selected for impersonation.');
                }

                Auth::logout();
                session()->forget(['role', 'patient_id']);
                return redirect('/login');
            }

            $patient = Patient::find($patientId);

            if (!$patient) {
                if (session()->has('impersonator_role')) {
                    return redirect()->route('admin.admin.dashboard')
                        ->with('error', 'Impersonated patient was not found.');
                }

                Auth::logout();
                session()->forget(['role', 'patient_id']);
                return redirect('/login');
            }

            view()->share('patient', $patient);
        }

        return $next($request);
    }
}
