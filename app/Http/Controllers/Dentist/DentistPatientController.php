<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ReservedBookingPeriod;
use App\Services\DentistDutyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\PhilippineHolidays;
use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\Auth;

class DentistPatientController extends Controller
{
    private function syncDutyEndAppointments(): void
    {
        $dentist = Auth::user();

        if ($dentist) {
            app(DentistDutyService::class)->syncOutDentistAppointments($dentist);
        }
    }

    public function index()
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $this->syncDutyEndAppointments();
        $today = Carbon::today()->toDateString();

        $appointments = Appointment::with('patient')
            ->whereHas('patient')
            ->orderByRaw(
                '
        CASE
            WHEN appointment_date = ? THEN 0
            WHEN appointment_date > ? THEN 1
            ELSE 2
        END
        ',
                [$today, $today]
            )
            ->orderByRaw(
                '
        CASE
            WHEN appointment_date >= ?
            THEN appointment_date
        END ASC
        ',
                [$today]
            )
            ->orderByRaw(
                '
        CASE
            WHEN appointment_date < ?
            THEN appointment_date
        END DESC
        ',
                [$today]
            )
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

        return view('shared.patient-list', [
            'layoutRole' => 'dentist',
            'pageTitle' => 'Patient Directory',
            'pageShellClass' => 'app-page-shell app-page-shell',
            'isDentistView' => true,
            'patientProfileRouteName' => 'dentist.dentist.patient.profile',

            'appointments' => $appointments,
            'upcomingAppointments' => $upcomingAppointments,
            'pastAppointments' => $pastAppointments,
            'todayCount' => $todayCount,
            'upcomingCount' => $upcomingCount,
            'rescheduledCount' => $rescheduledCount,
            'cancelledCount' => $cancelledCount,
            'completedCount' => $completedCount,
            'allCount' => $allCount,
            'notifications' => $notifications,
        ]);
    }

    public function profile(Patient $patient)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $this->syncDutyEndAppointments();

        $patient->loadMissing([
            'user',
            'odontogram',
            'medicalHistory.answers.question',
            'medicalHistory.diseaseAnswers.disease',
            'dentalHistory',
            'dentalHistoryDates',
            'dentalHistoryConcerns',
            'dentalHistoryAnswers.condition',
        ]);

        $today = Carbon::today()->toDateString();

        $futureVisits = Appointment::with([
            'procedure',
            'followUpAppointments',
            'dentist',
            'reservedBookingPeriod',
        ])
            ->where('patient_id', $patient->id)
            ->whereDate('appointment_date', '>=', $today)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $pastVisits = Appointment::with([
            'procedure',
            'followUpAppointments',
            'dentist',
            'reservedBookingPeriod',
        ])
            ->where('patient_id', $patient->id)
            ->whereIn('status', [
                'completed',
                'cancelled',
            ])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        $completedVisits = $pastVisits
            ->where('status', 'completed')
            ->values();

        $totalVisits = $completedVisits->count();

        $lastVisit = $completedVisits->first();

        $nextAppointment = $futureVisits->first();

        $philippineHolidays = PhilippineHolidays::recordsRange(1, 1);

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
