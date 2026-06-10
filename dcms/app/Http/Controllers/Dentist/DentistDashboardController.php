<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Helpers\PhilippineHolidays;
use Carbon\Carbon;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\DentistEmergencyOutNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DentistDashboardController extends Controller
{
    public function index()
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $calendarAppointments = Appointment::with('patient')
            ->whereDate('appointment_date', '>=', Carbon::today())
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        // COUNT PER DAY
        $appointmentCountsPerDay = $calendarAppointments
            ->groupBy(function ($appointment) {
                return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->count();
            })
            ->toArray();

        // FULL DETAILS PER DAY
        $calendarAppointmentDetails = $calendarAppointments
            ->groupBy(function ($appointment) {
                return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->map(function ($appointment) {
                    $name = $appointment->patient->name ?? 'Unknown Patient';

                    $time = !empty($appointment->appointment_time)
                        ? Carbon::parse($appointment->appointment_time)->format('h:i A')
                        : '—';

                    $service = $appointment->service_type === 'others'
                        ? ($appointment->other_services ?? 'Other Service')
                        : ($appointment->service_type ?? 'General Service');

                    return [
                        'id' => $appointment->id,
                        'name' => $name,
                        'time' => $time,
                        'service' => ucwords($service),
                        'status' => $appointment->status ?? 'pending',
                        'date' => Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
                    ];
                })->values()->toArray();
            })
            ->toArray();

        $blockedDates = BlockedDate::pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $philippineHolidays = PhilippineHolidays::range(0, 1);

        $schedules = ClinicSchedule::active()->orderBy('id')->get()
            ->map(function ($s) {
                $s->days = is_string($s->days) ? json_decode($s->days, true) : $s->days;
                return $s;
            })->toArray();

        return view('dentist.dentist-dashboard', compact(
            'appointmentCountsPerDay',
            'blockedDates',
            'philippineHolidays',
            'schedules',
            'calendarAppointmentDetails'
        ));
    }

    public function updateClinicStatus(Request $request)
        {
            $request->validate([
                'status' => ['required', 'in:in,out'],
            ]);

            $oldStatus = SystemSetting::getSetting('clinic_status', 'in');
            $newStatus = strtolower($request->status);

            SystemSetting::setSetting('clinic_status', $newStatus, 'clinic');

            if ($oldStatus !== 'out' && $newStatus === 'out') {
                $this->notifyPatientsWithAppointmentsToday();
            }

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus === 'out'
                    ? 'Clinic marked as closed.'
                    : 'Clinic marked as open.',
            ]);
        }

        private function notifyPatientsWithAppointmentsToday(): void
        {
            $appointments = Appointment::query()
                ->with('patient')
                ->whereDate('appointment_date', Carbon::today())
                ->whereIn('status', ['upcoming', 'rescheduled'])
                ->get();

            $notifiedUserIds = [];

            foreach ($appointments as $appointment) {
                $patient = $appointment->patient;

                if (!$patient) {
                    Log::warning('Emergency OUT notification skipped because patient was not found.', [
                        'appointment_id' => $appointment->id,
                        'patient_id' => $appointment->patient_id,
                    ]);

                    continue;
                }

                $patientUser = $this->resolvePatientUser($patient);

                if (!$patientUser) {
                    Log::warning('Emergency OUT notification skipped because patient user was not found.', [
                        'appointment_id' => $appointment->id,
                        'patient_id' => $patient->id,
                    ]);

                    continue;
                }

                // Prevent duplicate notification in the same request
                if (in_array($patientUser->id, $notifiedUserIds, true)) {
                    continue;
                }

                // Prevent duplicate notification already saved today
                $alreadyNotifiedToday = DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $patientUser->id)
                    ->whereDate('created_at', Carbon::today())
                    ->where('data->type', 'dentist_emergency_out')
                    ->exists();

                if ($alreadyNotifiedToday) {
                    continue;
                }

                $patientUser->notify(new DentistEmergencyOutNotification($appointment));

                $notifiedUserIds[] = $patientUser->id;
            }
        }

        private function resolvePatientUser($patient): ?User
        {
            if (isset($patient->user) && $patient->user instanceof User) {
                return $patient->user;
            }

            if (!empty($patient->user_id)) {
                return User::find($patient->user_id);
            }

            if (!empty($patient->email)) {
                return User::where('email', $patient->email)->first();
            }

            return User::where('patient_id', $patient->id)->first();
        }
}
