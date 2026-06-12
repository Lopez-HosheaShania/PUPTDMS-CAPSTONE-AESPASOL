<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\PhilippineHolidays;
use App\Helpers\AuditLogger;

class DentistPatientController extends Controller
{
    public function index()
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $today = Carbon::today()->toDateString();

        $appointments = Appointment::with('patient')
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $upcomingAppointments = $appointments->filter(function ($a) use ($today) {
            return in_array($a->status, ['upcoming', 'rescheduled'], true)
                && $a->appointment_date >= $today;
        });

        $pastAppointments = $appointments->filter(function ($a) use ($today) {
            return in_array($a->status, ['completed', 'cancelled'], true)
                || $a->appointment_date < $today;
        });

        $todayCount      = $appointments->where('appointment_date', $today)
            ->whereIn('status', ['upcoming', 'rescheduled'])->count();
        $upcomingCount   = $upcomingAppointments->where('appointment_date', '>', $today)->count();
        $rescheduledCount = $appointments->where('status', 'rescheduled')->count();
        $cancelledCount  = $appointments->where('status', 'cancelled')->count();
        $completedCount  = $appointments->where('status', 'completed')->count();
        $allCount        = $appointments->count();

        $notifications = [];

        AuditLogger::log(
            'view',
            'dentist_patients',
            "Dentist viewed patient list"
        );

        return view('dentist.dentist-patient', compact(
            'appointments',
            'upcomingAppointments',
            'pastAppointments',
            'todayCount',
            'upcomingCount',
            'rescheduledCount',
            'cancelledCount',
            'completedCount',
            'allCount',
            'notifications'
        ));
    }

    public function profile(Patient $patient)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $today = Carbon::today()->toDateString();

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

        $totalVisits = $pastVisits->count();
        $lastVisit = $pastVisits->first();
        $nextAppointment = $futureVisits->first();

        $philippineHolidays = PhilippineHolidays::range(1, 1);

        $appointmentCountsPerDay = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $unavailableDates = [];

        $notifications = collect([]);

        AuditLogger::log(
            'view',
            'dentist_patients',
            "Dentist viewed patient details"
        );

        return view('patient.shared-profile', [
            'patient' => $patient,
            'futureVisits' => $futureVisits,
            'pastVisits' => $pastVisits,
            'totalVisits' => $totalVisits,
            'lastVisit' => $lastVisit,
            'nextAppointment' => $nextAppointment,
            'notifications' => $notifications,
            'philippineHolidays' => $philippineHolidays,
            'appointmentCountsPerDay' => $appointmentCountsPerDay,
            'unavailableDates' => $unavailableDates,
            'profileLayout' => 'layouts.dentist',
            'profileMode' => 'dentist',
        ]);
    }
}
