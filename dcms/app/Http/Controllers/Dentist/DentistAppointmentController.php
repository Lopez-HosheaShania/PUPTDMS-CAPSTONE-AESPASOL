<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\AuditLogger;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Helpers\PhilippineHolidays;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentRescheduledNotification;
use App\Models\ServiceType;

class DentistAppointmentController extends Controller
{
    public function index()
    {

        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $today = Carbon::today()->toDateString();

        $upcomingAppointments = Appointment::with('patient')
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->whereDate('appointment_date', '>=', $today)
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $appointments = $upcomingAppointments;

        $pastAppointments = Appointment::with('patient')
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['completed', 'cancelled'])
                    ->orWhere(function ($sub) use ($today) {
                        $sub->whereDate('appointment_date', '<', $today)
                            ->whereIn('status', ['upcoming', 'rescheduled']);
                    });
            })
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        $notifications = collect($notifications ?? []);

        $appointmentCountsPerDay = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $appointmentCountsPerSlot = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, appointment_time, COUNT(*) as count')
            ->groupBy('appointment_date', 'appointment_time')
            ->get()
            ->groupBy('appointment_date')
            ->map(function ($rows) {
                return $rows->pluck('count', 'appointment_time')->toArray();
            })
            ->toArray();

        $calendarAppointmentDetails = Appointment::with('patient')
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->get()
            ->groupBy(function ($appointment) {
                return \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->map(function ($appointment) {
                    return [
                        'name' => $appointment->patient->name ?? 'Unknown',
                        'time' => \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A'),
                        'service' => $appointment->service_type,
                    ];
                })->toArray();
            })
            ->toArray();

        $schedules = ClinicSchedule::active()->orderBy('id')->get()
            ->map(function ($s) {
                $s->days = is_string($s->days) ? json_decode($s->days, true) : $s->days;
                return $s;
            });

        $blockedDates = BlockedDate::pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $philippineHolidays = PhilippineHolidays::range(0, 1);

        $defaultServiceTypes = ServiceType::where('is_default', true)
            ->where('is_active_for_booking', true)
            ->orderBy('name')
            ->get();

        AuditLogger::log(
            'view',
            'dentist_appointments',
            "Dentist viewed appointments page"
        );

        return view('dentist.dentist-appointments', compact(
            'appointments',
            'upcomingAppointments',
            'pastAppointments',
            'today',
            'appointmentCountsPerDay',
            'appointmentCountsPerSlot',
            'calendarAppointmentDetails',
            'schedules',
            'blockedDates',
            'philippineHolidays',
            'notifications',
            'defaultServiceTypes'
        ));
    }

    public function patientProfile(Appointment $appointment)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $appointment->load('patient');
        $patient = $appointment->patient;

        $patient = $appointment->patient;

        if (!$patient) {
            return redirect()->route('dentist.dentist.appointments')
                ->with('error', 'Patient not found for this appointment.');
        }

        AuditLogger::log(
            'view',
            'dentist_patients',
            "Dentist viewed patient profile"
        );

        $today = Carbon::today()->toDateString();

        $futureVisits = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $pastVisits = Appointment::where('patient_id', $patient->id)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['completed', 'cancelled'])
                    ->orWhereDate('appointment_date', '<', $today);
            })
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        $lastVisit = $pastVisits->first();
        $nextAppointment = $futureVisits->first();
        $totalVisits = $pastVisits->count() + $futureVisits->count();

        $notifications = collect([]);


        return view('dentist.dentist-patientprofile', compact(
            'patient',
            'appointment',
            'futureVisits',
            'pastVisits',
            'lastVisit',
            'nextAppointment',
            'totalVisits',
            'notifications'
        ));
    }

    public function start($id)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $appointment = Appointment::with('patient')->findOrFail($id);

        if (!$appointment->patient) {
            return redirect()
                ->route('dentist.dentist.appointments')
                ->with('error', 'Patient not found for this appointment.');
        }

        if (!in_array($appointment->status, ['upcoming', 'rescheduled'], true)) {
            return redirect()
                ->route('dentist.dentist.appointments')
                ->with('error', 'Only upcoming or rescheduled appointments can be started.');
        }

        AuditLogger::log(
            'view',
            'dentist_appointments',
            'Dentist started an appointment procedure'
        );

        return redirect()->route('dentist.odontogram', $appointment->id);
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $appointment = Appointment::with('patient.user')->findOrFail($id);

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
        ]);

        $patientUser = optional($appointment->patient)->user;

        // fallback kung walang relationship
        if (!$patientUser && !empty(optional($appointment->patient)->email)) {
            $patientUser = User::where('email', $appointment->patient->email)->first();
        }

        if ($patientUser) {
            $patientUser->notify(
                new AppointmentCancelledNotification(
                    $appointment,
                    auth()->user()->name ?? 'the dentist',
                    $request->reason
                )
            );
        }
        return response()->json(['success' => true]);
    }

    public function reschedule($id)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if ($activeRole !== 'dentist') {
            return redirect('/login');
        }

        $appointment = Appointment::with('patient')->findOrFail($id);

        $appointmentCountsPerDay = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $appointmentCountsPerSlot = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, appointment_time, COUNT(*) as count')
            ->groupBy('appointment_date', 'appointment_time')
            ->get()
            ->groupBy('appointment_date')
            ->map(function ($rows) {
                return $rows->pluck('count', 'appointment_time')->toArray();
            })
            ->toArray();

        $calendarAppointmentDetails = Appointment::with('patient')
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->get()
            ->groupBy(function ($appointment) {
                return \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->map(function ($appointment) {
                    return [
                        'name' => $appointment->patient->name ?? 'Unknown',
                        'time' => \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A'),
                        'service' => $appointment->service_type,
                    ];
                })->toArray();
            })
            ->toArray();

        $schedules = ClinicSchedule::active()->orderBy('id')->get()
            ->map(function ($s) {
                $s->days = is_string($s->days) ? json_decode($s->days, true) : $s->days;
                return $s;
            });

        $blockedDates = BlockedDate::pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $philippineHolidays = PhilippineHolidays::range(0, 1);

        $notifications = collect([]);

        AuditLogger::log(
            'view',
            'dentist_appointments',
            "Dentist opened reschedule appointment page"
        );

        return view('dentist.dentist-appointments', compact(
            'appointment',
            'appointmentCountsPerDay',
            'appointmentCountsPerSlot',
            'calendarAppointmentDetails',
            'schedules',
            'blockedDates',
            'philippineHolidays',
            'notifications'
        ));
    }

    public function updateReschedule(Request $request, $id)
    {
        $request->validate([
            'new_appointment_date' => 'required|date|after:today',
            'new_appointment_time' => 'required',
            'service_type' => 'required|string',
        ]);

        if (Carbon::parse($request->new_appointment_date)->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Same-day rescheduling is not allowed. Please choose a future date.',
            ], 422);
        }

        $appointment = Appointment::with('patient.user')->findOrFail($id);

        $mysqlTime = Carbon::createFromFormat('g:i A', trim($request->new_appointment_time))->format('H:i:s');

        $slotTaken = Appointment::where('appointment_date', $request->new_appointment_date)
            ->where('appointment_time', $mysqlTime)
            ->where('id', '!=', $appointment->id)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->exists();

        if ($slotTaken) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, that time slot is already taken. Please choose another time.',
            ], 422);
        }

        $appointment->update([
            'appointment_date' => $request->new_appointment_date,
            'appointment_time' => $mysqlTime,
            'service_type' => $request->service_type,
            'status' => 'rescheduled',
        ]);

        $patientUser = optional($appointment->patient)->user;

        if (!$patientUser && !empty(optional($appointment->patient)->email)) {
            $patientUser = User::where('email', $appointment->patient->email)->first();
        }

        if ($patientUser) {
            $patientUser->notify(
                new AppointmentRescheduledNotification(
                    $appointment,
                    auth()->user()->name ?? 'the dentist'
                )
            );
        }

        AuditLogger::log(
            'update',
            'dentist_appointments',
            'Dentist rescheduled an appointment'
        );

        return response()->json([
            'success' => true,
            'message' => 'Appointment rescheduled successfully.'
        ]);
    }
}
