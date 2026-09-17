<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentRescheduleMail;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;


class DentistAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $today = Carbon::today()->toDateString();

        $upcomingAppointments = Appointment::with(['patient', 'procedure', 'followUpAppointments', 'reservedBookingPeriod'])
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->whereDate('appointment_date', '>=', $today)
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $appointments = $upcomingAppointments;

        $pastAppointments = Appointment::with(['patient', 'procedure', 'followUpAppointments', 'reservedBookingPeriod'])
            ->whereIn('status', ['completed', 'cancelled',])
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
                        'service' => $appointment->service_type_name,
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

        $philippineHolidays = PhilippineHolidays::recordsRange(0, 1);

        $defaultServiceTypes = ServiceType::where('is_default', true)
            ->where('is_active_for_booking', true)
            ->orderBy('name')
            ->get();

        AuditLogger::log(
            'view',
            'dentist_appointments',
            "Dentist viewed appointments page"
        );

        if ($request->expectsJson()) {
            $appointments = $upcomingAppointments
                ->merge($pastAppointments)
                ->map(fn($appointment) => [
                    'id' => $appointment->id,
                    'updated_at' => optional($appointment->updated_at)->toISOString(),
                ])
                ->values();

            return response()->json([
                'appointments' => $appointments,
            ]);
        }

        return view('shared.appointments', [
            'layoutRole' => 'dentist',
            'pageTitle' => 'Appointments',
            'pageShellClass' => 'app-page-shell',

            'isDentistView' => true,

            'canStartProcedure' => $user?->hasPermission('create_procedure_records') ?? false,
            'canRescheduleAppointment' => $user?->hasPermission('reschedule_appointments') ?? false,
            'canCancelAppointment' => $user?->hasPermission('cancel_appointments') ?? false,
            'canViewTreatmentRecord' => $user?->hasAnyPermission([
                'view_appointments',
                'reschedule_appointments',
                'cancel_appointments',
                'create_follow_up_appointments',
                'create_procedure_records',
            ]) ?? false,
            'canScheduleFollowUp' => false,

            'patientProfileRouteName' => 'dentist.dentist.patient.profile',

            'appointments' => $appointments,
            'upcomingAppointments' => $upcomingAppointments,
            'pastAppointments' => $pastAppointments,

            'today' => $today,

            'appointmentCountsPerDay' => $appointmentCountsPerDay,
            'appointmentCountsPerSlot' => $appointmentCountsPerSlot,
            'calendarAppointmentDetails' => $calendarAppointmentDetails,
            'schedules' => $schedules,
            'blockedDates' => $blockedDates,
            'philippineHolidays' => $philippineHolidays,
            'defaultServiceTypes' => $defaultServiceTypes,
            'notifications' => $notifications,
        ]);
    }

    public function patientProfile(Appointment $appointment)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $appointment->load('patient');
        $patient = $appointment->patient;

        if (!$patient) {
            return redirect()->route('dentist.dentist.appointments')
                ->with('error', 'Patient not found for this appointment.');
        }

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

        AuditLogger::log(
            'view',
            'dentist_patients',
            "Dentist viewed patient profile"
        );

        $today = Carbon::today()->toDateString();

        $futureVisits = Appointment::with([
            'procedure',
            'followUpAppointments',
            'dentist',
        ])
            ->where('patient_id', $patient->id)
            ->whereDate('appointment_date', '>=', $today)
            ->whereIn('status', [
                'upcoming',
                'rescheduled',
            ])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $pastVisits = Appointment::with([
            'procedure',
            'followUpAppointments',
            'dentist',
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

        $lastVisit = $completedVisits->first();

        $nextAppointment = $futureVisits->first();

        $totalVisits = $completedVisits->count();

        $notifications = collect([]);

        return view('patient.shared-profile', [
            'patient' => $patient,
            'appointment' => $appointment,
            'futureVisits' => $futureVisits,
            'pastVisits' => $pastVisits,
            'totalVisits' => $totalVisits,
            'lastVisit' => $lastVisit,
            'nextAppointment' => $nextAppointment,
            'notifications' => $notifications,
            'profileLayout' => 'layouts.dentist',
            'profileMode' => 'dentist',
        ]);
    }

    public function start(Request $request, $id)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $appointment = Appointment::with(['patient', 'reservedBookingPeriod'])->findOrFail($id);

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

        if (
            empty($appointment->appointment_date) ||
            !Carbon::parse($appointment->appointment_date)->isToday()
        ) {
            return redirect()
                ->route('dentist.dentist.appointments')
                ->with(
                    'error',
                    'The procedure can only be started on the scheduled appointment date.'
                );
        }

        if ($appointment->reserved_booking_period_id && ! $appointment->reservedProcedureWindowIsOpen()) {
            return redirect()
                ->route('dentist.dentist.appointments')
                ->with('error', 'Reserved appointments can be started anytime from the beginning until the end of their reserved period.');
        }

        AuditLogger::log(
            'view',
            'dentist_appointments',
            'Dentist started an appointment procedure'
        );

        return redirect()->route('dentist.odontogram', [
            'appointment' => $appointment->id,
            'from' => $request->query('from', 'appointments'),
            'start_procedure' => $request->query('start_procedure'),
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $appointment = Appointment::with('patient.user')->findOrFail($id);

        if (! in_array($appointment->status, ['upcoming', 'rescheduled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only upcoming or rescheduled appointments can be cancelled.',
            ], 422);
        }

        $cancelledBy = Auth::user()?->name ?? 'the dentist';

        $updates = [
            'status' => 'cancelled',
            'cancellation_reason' => trim((string) $request->reason),
        ];

        // Release a reserved slot only when this appointment actually owns one.
        // Appointment::setAttribute() persists this normalized detail field through
        // appointment_reserved_bookings.
        if ($appointment->reserved_booking_period_slot_id) {
            $updates['reserved_booking_period_slot_id'] = null;
        }

        $appointment->update($updates);

        $patientUser = optional($appointment->patient)->user;

        if (!$patientUser && !empty(optional($appointment->patient)->email)) {
            $patientUser = User::where('email', $appointment->patient->email)->first();
        }

        if ($patientUser) {
            try {
                $patientUser->notify(
                    new AppointmentCancelledNotification(
                        $appointment,
                        $cancelledBy,
                        $request->reason
                    )
                );
            } catch (\Throwable $e) {
                \Log::error('Appointment cancellation notification failed.', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $patientEmail = optional($appointment->patient)->email;

            if ($patientEmail) {
                Mail::to($patientEmail)
                    ->send(new AppointmentCancelledMail(
                        $appointment,
                        $cancelledBy,
                        $request->reason
                    ));
            }
        } catch (\Throwable $e) {
            \Log::error('Appointment cancellation email failed.', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function updateReschedule(Request $request, $id)
    {
        $request->validate([
            'new_appointment_date' => 'required|date|after:today',
            'new_appointment_time' => 'required',
        ]);

        if (Carbon::parse($request->new_appointment_date)->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Same-day rescheduling is not allowed. Please choose a future date.',
            ], 422);
        }

        if (PhilippineHolidays::isBlockedForBooking($request->new_appointment_date)) {
            return response()->json([
                'success' => false,
                'message' =>
                'The clinic is closed on this Philippine holiday. Please choose another date.',
            ], 422);
        }

        $appointment = Appointment::with('patient.user')->findOrFail($id);

        if (! in_array($appointment->status, ['upcoming', 'rescheduled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only upcoming or rescheduled appointments can be rescheduled.',
            ], 422);
        }

        if ($appointment->reserved_booking_period_id) {
            return response()->json([
                'success' => false,
                'message' => 'Reserved appointments must stay within their assigned period and cannot be rescheduled individually.',
            ], 422);
        }

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

        $oldAppointmentDate = $appointment->appointment_date;
        $oldAppointmentTime = $appointment->appointment_time;

        $appointment->update([
            'appointment_date' => $request->new_appointment_date,
            'appointment_time' => $mysqlTime,
            // Rescheduling changes only the schedule. Keep the appointment's
            // existing normalized service_type_id/service type unchanged.
            'status' => 'rescheduled',
        ]);

        $patientUser = optional($appointment->patient)->user;

        if (!$patientUser && !empty(optional($appointment->patient)->email)) {
            $patientUser = User::where('email', $appointment->patient->email)->first();
        }

        $rescheduledBy = Auth::user()?->name ?? 'the dentist';

        if ($patientUser) {
            try {
                $patientUser->notify(
                    new AppointmentRescheduledNotification(
                        $appointment,
                        $rescheduledBy
                    )
                );
            } catch (\Throwable $e) {
                \Log::error('Appointment reschedule notification failed.', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $patientEmail = optional($appointment->patient)->email;

            if ($patientEmail) {
                Mail::to($patientEmail)
                    ->send(new AppointmentRescheduleMail(
                        $appointment,
                        $oldAppointmentDate,
                        $oldAppointmentTime,
                        $rescheduledBy
                    ));
            }
        } catch (\Throwable $e) {
            \Log::error('Appointment reschedule email failed.', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
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

    public function storeFollowUp(Request $request, $id)
    {
        $request->validate([
            'followup_appointment_date' => 'required|date|after:today',
            'followup_appointment_time' => 'required',
            'followup_reason' => 'nullable|string|max:1000',
        ]);

        if (Carbon::parse($request->followup_appointment_date)->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Same-day follow-up scheduling is not allowed. Please choose a future date.',
            ], 422);
        }

        if (PhilippineHolidays::isBlockedForBooking($request->followup_appointment_date)) {
            return response()->json([
                'success' => false,
                'message' =>
                'The clinic is closed on this Philippine holiday. Please choose another date.',
            ], 422);
        }

        $originalAppointment = Appointment::with('patient')->findOrFail($id);

        $mysqlTime = Carbon::createFromFormat('g:i A', trim($request->followup_appointment_time))->format('H:i:s');

        $slotTaken = Appointment::where('appointment_date', $request->followup_appointment_date)
            ->where('appointment_time', $mysqlTime)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->exists();

        if ($slotTaken) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, that time slot is already taken. Please choose another time.',
            ], 422);
        }

        $followUpServiceType = ServiceType::where(
            'name',
            'Follow-up'
        )->firstOrFail();

        $followUpAppointment = Appointment::create([
            'patient_id' => $originalAppointment->patient_id,
            'dentist_id' => auth()->id(),
            'service_type_id' => $followUpServiceType->id,
            'service_type' => $followUpServiceType->name,
            'appointment_date' => $request->followup_appointment_date,
            'appointment_time' => $mysqlTime,
            'status' => 'upcoming',
            'is_follow_up' => true,
            'follow_up_for_appointment_id' => $originalAppointment->id,
            'follow_up_reason' => $request->filled('followup_reason') ? trim($request->followup_reason) : null,
            'follow_up_reminder_sent_at' => null,
            'follow_up_today_reminder_sent_at' => null,
            'follow_up_one_day_reminder_sent_at' => null,
        ]);

        AuditLogger::log(
            'create',
            'dentist_appointments',
            'Dentist scheduled a follow-up appointment'
        );

        return response()->json([
            'success' => true,

            'message' =>
            'Follow-up appointment scheduled successfully.',

            'appointment' => [
                'id' => $followUpAppointment->id,

                'patient_name' =>
                $originalAppointment->patient?->name
                    ?? 'Unknown Patient',

                'service_type' => $followUpAppointment->service_type_name,

                'appointment_date' =>
                Carbon::parse(
                    $request->followup_appointment_date
                )->format('F j, Y'),

                'appointment_time' =>
                Carbon::createFromFormat(
                    'H:i:s',
                    $mysqlTime
                )->format('g:i A'),

                'status' =>
                ucfirst(
                    $followUpAppointment->status
                ),

                'follow_up_reason' => $followUpAppointment->follow_up_reason,
            ],
        ]);
    }
}
