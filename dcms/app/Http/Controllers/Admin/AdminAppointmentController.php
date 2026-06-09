<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentCompletedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminAppointmentController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $appointments = Appointment::with(['patient'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $todayCount = $appointments->filter(function ($appt) use ($today) {
            $status = strtolower($appt->status ?? '');
            return $appt->appointment_date === $today
                && !in_array($status, ['cancelled', 'completed'], true);
        })->count();

        $upcomingCount = $appointments->filter(function ($appt) use ($today) {
            $status = strtolower($appt->status ?? '');
            return $appt->appointment_date > $today
                && in_array($status, ['upcoming', 'pending', 'confirmed', 'rescheduled'], true);
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

        $upcomingAppointments = $appointments->filter(function ($appt) use ($today) {
            $status = strtolower($appt->status ?? '');
            return $appt->appointment_date >= $today
                && !in_array($status, ['cancelled', 'completed'], true);
        });

        $pastAppointments = $appointments->filter(function ($appt) use ($today) {
            $status = strtolower($appt->status ?? '');
            return $appt->appointment_date < $today
                || in_array($status, ['completed', 'cancelled'], true);
        });

        return view('admin.admin-appointments', compact(
            'appointments',
            'upcomingAppointments',
            'pastAppointments',
            'todayCount',
            'upcomingCount',
            'rescheduledCount',
            'cancelledCount',
            'completedCount',
            'allCount'
        ));
    }

    public function show($id)
    {
        $appointment = Appointment::with(['patient'])->findOrFail($id);

        return view('admin.admin-appointment-show', compact('appointment'));
    }

    public function reschedule($id)
    {
        $appointment = Appointment::with(['patient'])->findOrFail($id);

        return view('admin.admin-appointment-reschedule', compact('appointment'));
    }

    public function start($id)
    {
        $appointment = Appointment::with('patient.user')->findOrFail($id);

        $appointment->status = 'completed';
        $appointment->save();

        $patientUser = optional($appointment->patient)->user;

        if (!$patientUser && !empty(optional($appointment->patient)->email)) {
            $patientUser = User::where('email', $appointment->patient->email)->first();
        }

        if ($patientUser) {
            $patientUser->notify(new AppointmentCompletedNotification($appointment));
        }

        return redirect()
            ->route('admin.admin.appointments')
            ->with('success', 'Appointment marked as completed.');
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $appointment = Appointment::findOrFail($id);
        $appointment->status = 'cancelled';

        if (isset($appointment->cancellation_reason)) {
            $appointment->cancellation_reason = $request->reason;
        }

        if (isset($appointment->cancelled_at)) {
            $appointment->cancelled_at = now();
        }

        $appointment->save();

        return response()->json([
            'success' => true,
            'message' => 'Appointment cancelled successfully.',
        ]);
    }
}