<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

class OdontogramPolicy
{
    public function edit(User $user, Appointment $appointment): Response
    {
        $activeRole = session('impersonated_role')
            ?: session('role')
            ?: optional($user->role)->slug;

        if (in_array(
            $activeRole,
            config('odontogram.admin_roles', ['admin', 'super_admin']),
            true
        )) {
            return Response::allow();
        }

        if ($this->isLocked($appointment)) {
            return Response::deny(
                'This odontogram can no longer be edited because the correction period has expired.'
            );
        }

        if (in_array(
            $activeRole,
            config('odontogram.head_dentist_roles', ['head_dentist']),
            true
        )) {
            return Response::allow();
        }

        if (
            $activeRole === 'dentist'
            && (int) $appointment->dentist_id === (int) $user->id
        ) {
            return Response::allow();
        }

        return Response::deny(
            'You are not authorized to edit this appointment odontogram.'
        );
    }

    private function isLocked(Appointment $appointment): bool
    {
        $lockDays = max(
            0,
            (int) config('odontogram.edit_lock_days', 7)
        );

        $lockDate = Carbon::parse($appointment->appointment_date)
            ->addDays($lockDays)
            ->endOfDay();

        return now()->greaterThan($lockDate);
    }
}