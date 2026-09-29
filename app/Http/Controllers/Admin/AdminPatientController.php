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
        $isBookingMode = request()->routeIs('admin.book_appointments.*');
        $today = Carbon::today()->toDateString();

        Appointment::cancelElapsedScheduledAppointments();

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

        $notifications = [];

        return view('shared.patient-list', [
            'layoutRole' => 'admin',
            'pageTitle' => 'Patient List',
            'pageShellClass' => 'app-page-shell',
            'isDentistView' => false,
            'patientProfileRouteName' => $isBookingMode
                ? 'admin.book_appointments.profile'
                : 'admin.admin.patient.profile',

            'appointments' => $appointments,
            'todayCount' => $todayCount,
            'upcomingCount' => $upcomingCount,
            'rescheduledCount' => $rescheduledCount,
            'cancelledCount' => $cancelledCount,
            'completedCount' => $completedCount,
            'allCount' => $allCount,
            'notifications' => $notifications,
        ]);
    }

    public function show(Patient $patient)
    {
        $isBookingMode = request()->routeIs('admin.book_appointments.*');

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

        Appointment::cancelElapsedScheduledAppointments();

        $appointments = Appointment::with(['procedure', 'followUpAppointments', 'dentist'])
            ->where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        $futureVisits = Appointment::with(['procedure', 'followUpAppointments', 'dentist'])
            ->where('patient_id', $patient->id)
            ->whereDate('appointment_date', '>=', $today)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $pastVisits = Appointment::with(['procedure', 'followUpAppointments', 'dentist'])
            ->where('patient_id', $patient->id)
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
            'bookingMode' => $isBookingMode,
        ]);
    }
}
