<?php

namespace App\Http\Controllers\Dentist;

use App\Helpers\PhilippineHolidays;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DentistDutyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DentistDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        $todayAppointments = Appointment::with('patient')
            ->whereDate('appointment_date', $today)
            ->whereIn('status', [
                'upcoming',
                'rescheduled',
                'pending',
                'confirmed',
                'completed',
                'cancelled',
            ])
            ->orderBy('appointment_time', 'asc')
            ->get();

        $calendarEndDate =
            Carbon::today()
            ->addDays(90)
            ->toDateString();

        $calendarAppointments =
            Appointment::with('patient')
            ->whereDate(
                'appointment_date',
                '>=',
                $today
            )
            ->whereDate(
                'appointment_date',
                '<=',
                $calendarEndDate
            )
            ->whereIn('status', [
                'upcoming',
                'rescheduled',
            ])
            ->orderBy(
                'appointment_date',
                'asc'
            )
            ->orderBy(
                'appointment_time',
                'asc'
            )
            ->get();

        $appointmentCountsPerDay = $calendarAppointments
            ->groupBy(function ($appointment) {
                return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->count();
            })
            ->toArray();

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

                    $serviceTypeName = $appointment->service_type_name;

                    $service = $serviceTypeName === 'others'
                        ? ($appointment->other_services ?? 'Other Service')
                        : ($serviceTypeName ?? 'General Service');

                    return [
                        'id' => $appointment->id,
                        'name' => $name,
                        'time' => $time,
                        'service' => ucwords($service),
                        'status' => $appointment->status ?? 'upcoming',
                        'date' => Carbon::parse($appointment->appointment_date)->format('Y-m-d'),

                        'patientProfileUrl' => $appointment->patient_id
                            ? route('dentist.dentist.patient.profile', $appointment->patient_id)
                            : '#',

                        'rescheduleUrl' => route(
                            'dentist.dentist.appointments.reschedule.update',
                            $appointment->id
                        ),

                        'cancelUrl' => route(
                            'dentist.dentist.appointments.cancel',
                            $appointment->id
                        ),
                        'is_walk_in' =>
                        (bool) ($appointment->is_walk_in ?? false),

                        'is_follow_up' =>
                        (bool) ($appointment->is_follow_up ?? false),
                    ];
                })->values()->toArray();
            })
            ->toArray();

        $dashboardAppointmentWindow = Appointment::with([
            'patient',
            'reservedBookingPeriod',
            'serviceType',
        ])

            ->whereBetween('appointment_date', [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays(90)->toDateString(),
            ])
            ->whereIn('status', [
                'upcoming',
                'rescheduled',
            ])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $dashboardAppointmentDetails = $dashboardAppointmentWindow
            ->groupBy(function ($appointment) {
                return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->map(function ($appointment) {
                    $name = $appointment->patient->name ?? 'Unknown Patient';

                    $time = !empty($appointment->appointment_time)
                        ? Carbon::parse($appointment->appointment_time)->format('h:i A')
                        : '—';

                    $serviceTypeName = $appointment->service_type_name;

                    $service = $serviceTypeName === 'others'
                        ? ($appointment->other_services ?? 'Other Service')
                        : ($serviceTypeName ?? 'General Service');

                    return [
                        'id' => $appointment->id,
                        'name' => $name,
                        'time' => $time,
                        'service' => ucwords($service),
                        'status' => $appointment->status ?? 'upcoming',
                        'date' => Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
                        'is_walk_in' => (bool) ($appointment->is_walk_in ?? false),
                        'is_follow_up' => (bool) ($appointment->is_follow_up ?? false),
                        'is_reserved' => filled($appointment->reserved_booking_period_id),
                        'reserved_title' => $appointment->reservedBookingPeriod?->title,

                        'patientPhotoUrl' =>
                        optional($appointment->patient)->profile_photo_url
                            ?? optional($appointment->patient)->profile_picture_url
                            ?? optional($appointment->patient)->avatar_url
                            ?? optional($appointment->patient)->photo_url
                            ?? '',

                        'patientProfileUrl' => $appointment->patient_id
                            ? route('dentist.dentist.patient.profile', $appointment->patient_id)
                            : '#',

                        'rescheduleUrl' => route(
                            'dentist.dentist.appointments.reschedule.update',
                            $appointment->id
                        ),

                        'cancelUrl' => route(
                            'dentist.dentist.appointments.cancel',
                            $appointment->id
                        ),
                    ];
                })->values()->toArray();
            })
            ->toArray();

        $dentalCasesThisMonth = Appointment::whereYear('appointment_date', $now->year)
            ->whereMonth('appointment_date', $now->month)
            ->where('status', 'completed')
            ->count();

        $lastMonth = $now->copy()->subMonth();

        $dentalCasesLastMonth = Appointment::whereYear(
            'appointment_date',
            $lastMonth->year
        )
            ->whereMonth('appointment_date', $lastMonth->month)
            ->where('status', 'completed')
            ->count();

        $dentalCasesDelta = $dentalCasesLastMonth > 0
            ? round(
                (($dentalCasesThisMonth - $dentalCasesLastMonth)
                    / $dentalCasesLastMonth) * 100
            )
            : null;

        $totalApptsThisMonth = Appointment::whereYear(
            'appointment_date',
            $now->year
        )
            ->whereMonth('appointment_date', $now->month)
            ->whereIn('status', [
                'upcoming',
                'rescheduled',
                'completed',
                'cancelled',
                'pending',
                'confirmed',
            ])
            ->count();

        $totalApptsLastMonth = Appointment::whereYear(
            'appointment_date',
            $lastMonth->year
        )
            ->whereMonth('appointment_date', $lastMonth->month)
            ->whereIn('status', [
                'pending',
                'confirmed',
                'upcoming',
                'rescheduled',
                'completed',
                'cancelled',
            ])
            ->count();

        $totalApptsDelta = $totalApptsLastMonth > 0
            ? round(
                (($totalApptsThisMonth - $totalApptsLastMonth)
                    / $totalApptsLastMonth) * 100
            )
            : null;

        $medicalSupplies = DB::table('inventory_items')
            ->where('category', 'Supplies')
            ->orderByRaw('(qty - used) ASC')
            ->limit(3)
            ->get();

        $medicineSupplies = DB::table('inventory_items')
            ->where('category', 'Medicine')
            ->orderByRaw('(qty - used) ASC')
            ->limit(3)
            ->get();

        $gadRaw = app(\App\Services\AppointmentReportRecords::class)
            ->between($now->copy()->startOfMonth(), $now->copy()->endOfMonth())
            ->groupBy(fn ($row) => json_encode([$row->office_type, $row->gender]))
            ->map(fn ($rows) => (object) ['office_type' => $rows->first()->office_type,
                'gender' => $rows->first()->gender, 'total' => $rows->count()])->values();

        $gadLabels = [
            'Student',
            'Administrative',
            'Faculty',
            'Dependent',
        ];

        $gadFemale = [];
        $gadMale = [];

        foreach ($gadLabels as $label) {
            $gadFemale[] = (int) $gadRaw
                ->filter(fn($row) => $this->normalizeDashboardOfficeType($row->office_type) === strtolower($label))
                ->where('gender', 'Female')
                ->sum('total');

            $gadMale[] = (int) $gadRaw
                ->filter(fn($row) => $this->normalizeDashboardOfficeType($row->office_type) === strtolower($label))
                ->where('gender', 'Male')
                ->sum('total');
        }

        $blockedDates = BlockedDate::pluck('date')
            ->map(
                fn($date) =>
                Carbon::parse($date)->toDateString()
            )
            ->toArray();

        $philippineHolidays = PhilippineHolidays::recordsRange(
            yearsBefore: 1,
            yearsAfter: 5
        );

        $schedules = ClinicSchedule::active()
            ->orderBy('id')
            ->get()
            ->map(function ($schedule) {
                $schedule->days = is_string($schedule->days)
                    ? json_decode($schedule->days, true)
                    : $schedule->days;

                return $schedule;
            })
            ->toArray();

        $clinicStatus = app(DentistDutyService::class)->currentStatusFor(auth()->user());

        $notifications = collect([]);

        return view('dentist.dentist-dashboard', compact(
            'todayAppointments',
            'appointmentCountsPerDay',
            'calendarAppointmentDetails',
            'dashboardAppointmentDetails',
            'blockedDates',
            'philippineHolidays',
            'schedules',
            'notifications',

            'dentalCasesThisMonth',
            'dentalCasesDelta',
            'totalApptsThisMonth',
            'totalApptsDelta',

            'medicalSupplies',
            'medicineSupplies',

            'gadLabels',
            'gadFemale',
            'gadMale',

            'clinicStatus'
        ));
    }
    public function updateClinicStatus(Request $request)
    {
        $request->validate([
            'status' => ['required', 'in:in,out'],
        ]);

        $dutyService = app(DentistDutyService::class);
        $oldStatus = $dutyService->currentStatusFor($request->user());
        $newStatus = strtolower($request->status);

        SystemSetting::setSetting(
            'clinic_status',
            $newStatus,
            'clinic'
        );

        if ($oldStatus !== 'out' && $newStatus === 'out') {
            SystemSetting::setSetting(
                'clinic_status_out_at',
                Carbon::now()->toDateTimeString(),
                'clinic'
            );
        }

        if ($oldStatus === 'out' && $newStatus === 'in') {
            SystemSetting::setSetting(
                'clinic_status_in_at',
                Carbon::now()->toDateTimeString(),
                'clinic'
            );
        }

        if ($newStatus === 'out') {
            $summary = $dutyService->clockOut($request->user(), Carbon::now(), false);
        } else {
            $dutyService->clockIn($request->user(), Carbon::now());
            $summary = [
                'appointments_cancelled' => 0,
                'cancelled_appointment_ids' => [],
                'notifications_created' => 0,
            ];
        }

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus === 'out'
                ? 'Clinic marked as closed.'
                : 'Clinic marked as open.',
            'appointments_cancelled' => $summary['appointments_cancelled'],
            'cancelled_appointment_ids' => $summary['cancelled_appointment_ids'],
            'notifications_created' => $summary['notifications_created'],
        ]);
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

    private function normalizeDashboardOfficeType(?string $officeType): string
    {
        $officeType = strtolower(trim((string) $officeType));

        if (
            $officeType === '' ||
            str_contains($officeType, 'student')
        ) {
            return 'student';
        }

        if (str_contains($officeType, 'faculty')) {
            return 'faculty';
        }

        if (
            str_contains($officeType, 'admin') ||
            str_contains($officeType, 'administrative') ||
            str_contains($officeType, 'personnel')
        ) {
            return 'administrative';
        }

        if (
            str_contains($officeType, 'dependent') ||
            str_contains($officeType, 'alumni') ||
            str_contains($officeType, 'guest')
        ) {
            return 'dependent';
        }

        return 'student';
    }
}
