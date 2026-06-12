<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminPatientController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $appointments = Appointment::with('patient')
            ->whereHas('patient')
            ->orderByRaw("
                CASE
                    WHEN appointment_date = ? THEN 0
                    WHEN appointment_date > ? THEN 1
                    ELSE 2
                END
            ", [$today, $today])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $todayCount = $appointments->filter(function ($appt) use ($today) {
            $status = strtolower($appt->status ?? '');
            return $appt->appointment_date === $today
                && $status !== 'cancelled'
                && $status !== 'completed';
        })->count();

        $upcomingCount = $appointments->filter(function ($appt) use ($today) {
            $status = strtolower($appt->status ?? '');
            return $appt->appointment_date > $today
                && in_array($status, ['upcoming', 'rescheduled', 'pending', 'confirmed'], true);
        })->count();

        $rescheduledCount = $appointments->filter(function ($appt) {
            return strtolower($appt->status ?? '') === 'rescheduled';
        })->count();

        $cancelledCount = $appointments->filter(function ($appt) {
            return strtolower($appt->status ?? '') === 'cancelled';
        })->count();

        $completedCount = $appointments->filter(function ($appt) {
            return strtolower($appt->status ?? '') === 'completed';
        })->count();

        $allCount = $appointments->count();

        $notifications = []; // palitan later if meron kang notifications query

        return view('admin.admin-patient', compact(
            'appointments',
            'todayCount',
            'upcomingCount',
            'rescheduledCount',
            'cancelledCount',
            'completedCount',
            'allCount',
            'notifications'
        ));
    }

    public function show(Patient $patient)
    {
        $today = Carbon::today()->toDateString();

        $appointments = Appointment::where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        $futureVisits = Appointment::where('patient_id', $patient->id)
            ->whereDate('appointment_date', '>=', $today)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $pastVisits = Appointment::where('patient_id', $patient->id)
            ->where(function ($query) use ($today) {
                $query->whereDate('appointment_date', '<', $today)
                    ->orWhereIn('status', ['completed', 'cancelled']);
            })
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        $totalVisits = $appointments->count();
        $lastVisit = $pastVisits->first();
        $nextAppointment = $futureVisits->first();
        $notifications = collect([]);

        return view('patient.shared-profile', [
            'patient' => $patient,
            'appointments' => $appointments,
            'futureVisits' => $futureVisits,
            'pastVisits' => $pastVisits,
            'totalVisits' => $totalVisits,
            'lastVisit' => $lastVisit,
            'nextAppointment' => $nextAppointment,
            'notifications' => $notifications,
            'profileLayout' => 'layouts.admin',
            'profileMode' => 'admin',
        ]);
    }
}
