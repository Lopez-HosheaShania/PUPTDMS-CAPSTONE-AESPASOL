<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Models\Appointment;
use App\Models\ReservedBookingPeriod;
use App\Services\StudentTargetOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Helpers\PhilippineHolidays;

class ClinicScheduleController extends Controller
{
    private const ALL_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    public function index(StudentTargetOptionService $studentTargetOptionService)
    {
        $schedules    = ClinicSchedule::query()->orderByDesc('is_active')->orderBy('id')->get();
        $blockedDates = BlockedDate::orderBy('date')->get();
        $reservedBookingPeriods = ReservedBookingPeriod::query()
            ->with(['creator:id,name', 'slots'])
            ->orderBy('reserved_date')
            ->orderBy('start_time')
            ->get();
        $studentTargetOptions = $studentTargetOptionService->get();

        $startDate = Carbon::now()->startOfMonth()->subMonth();
        $endDate   = Carbon::now()->endOfMonth()->addMonths(3);

        $appointmentCountsPerDay = Appointment::whereBetween('appointment_date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, COUNT(*) as cnt')
            ->groupBy('appointment_date')
            ->pluck('cnt', 'appointment_date')
            ->toArray();

        $weeklyAppointments = Appointment::with('patient')
            ->whereBetween('appointment_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'appointment_date' => Carbon::parse($appointment->appointment_date)->toDateString(),
                    'appointment_time' => Carbon::parse($appointment->appointment_time)->format('H:i'),
                    'display_time' => Carbon::parse($appointment->appointment_time)->format('g:i A'),
                    'service_type' => $appointment->service_type,
                    'other_services' => $appointment->other_services,
                    'status' => $appointment->status,
                    'patient_name' => optional($appointment->patient)->name ?? 'Unknown Patient',
                ];
            })
            ->values();

        $philippineHolidays = PhilippineHolidays::recordsRange(0, 1);
        $notifications      = [];

        return view('shared.clinic-schedule', [
            'layoutRole' => 'admin',
            'pageTitle' => 'Clinic Schedule',
            'pageShellClass' => 'app-page-shell',
            'isDentistView' => false,

            'schedules' => $schedules,
            'blockedDates' => $blockedDates,
            'reservedBookingPeriods' => $reservedBookingPeriods,
            'studentTargetOptions' => $studentTargetOptions,
            'appointmentCountsPerDay' => $appointmentCountsPerDay,
            'weeklyAppointments' => $weeklyAppointments,
            'philippineHolidays' => $philippineHolidays,
            'notifications' => $notifications,

            'clinicScheduleRouteNames' => [
                'store' => 'admin.clinic_schedule.store',
                'update' => 'admin.clinic_schedule.update',
                'destroy' => 'admin.clinic_schedule.destroy',
                'block' => 'admin.clinic_schedule.block',
                'unblock' => 'admin.clinic_schedule.unblock',
                'reserved_store' => 'admin.clinic_schedule.reserved_periods.store',
                'reserved_update' => 'admin.clinic_schedule.reserved_periods.update',
                'reserved_destroy' => 'admin.clinic_schedule.reserved_periods.destroy',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request);

        if ((bool) $validated['is_active']) {
            $this->ensureDaysAreAvailable($request, $validated['days']);
        }

        ClinicSchedule::create($this->prepareRule($validated));
        return back()->with('success', 'Schedule rule added successfully.');
    }

    public function update(Request $request, ClinicSchedule $clinicSchedule)
    {
        $validated = $this->validateRule($request);

        if ((bool) $validated['is_active']) {
            $this->ensureDaysAreAvailable($request, $validated['days'], $clinicSchedule->id);
        }

        $clinicSchedule->update($this->prepareRule($validated));
        return back()->with('success', 'Schedule rule updated.');
    }

    public function destroy(ClinicSchedule $clinicSchedule)
    {
        $clinicSchedule->delete();
        return back()->with('success', 'Schedule rule deleted.');
    }

    public function blockDate(Request $request)
    {
        $validated = $request->validate([
            'date'   => 'required|date|after_or_equal:today|unique:blocked_dates,date',
            'reason' => 'required|string|max:100',
            'note'   => 'nullable|string|max:300',
        ]);

        BlockedDate::create([
            'date'       => $validated['date'],
            'reason'     => $validated['reason'],
            'note'       => $validated['note'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Date blocked successfully.');
    }

    public function unblockDate(BlockedDate $blockedDate)
    {
        $blockedDate->delete();
        return back()->with('success', 'Date unblocked.');
    }

    public function unavailableDates()
    {
        $start = Carbon::today();
        $end   = $start->copy()->addMonths(3);

        // Explicitly blocked dates
        $blocked = BlockedDate::whereBetween('date', [$start, $end])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Days of week marked closed
        $closedDayAbbrs = [];
        foreach (ClinicSchedule::active()->where('status', 'closed')->get() as $s) {
            $closedDayAbbrs = array_merge($closedDayAbbrs, $s->days ?? []);
        }
        $closedDayAbbrs = array_unique($closedDayAbbrs);

        $unavailable = $blocked;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $abbr = $d->format('D');
            if (in_array($abbr, $closedDayAbbrs)) {
                $unavailable[] = $d->toDateString();
            }
        }

        $philippineHolidays = PhilippineHolidays::recordsRange(0, 1);

        foreach ($philippineHolidays as $date => $holiday) {
            if (
                $date >= $start->toDateString() &&
                $date <= $end->toDateString() &&
                ($holiday['is_blocked_for_booking'] ?? false)
            ) {
                $unavailable[] = $date;
            }
        }

        return response()->json(array_values(array_unique($unavailable)));
    }

    public function slotsForDate(Request $request)
    {
        $request->validate(['date' => 'required|date|after_or_equal:today']);

        $iso    = $request->date;
        $carbon = Carbon::parse($iso);
        $abbr   = $carbon->format('D');

        if (BlockedDate::where('date', $iso)->exists()) {
            return response()->json([
                'slots'   => [],
                'message' => 'This date is blocked and unavailable for booking.',
            ]);
        }

        if (PhilippineHolidays::isBlockedForBooking($iso)) {
            return response()->json([
                'slots' => [],
                'message' =>
                'The clinic is closed on this Philippine holiday.',
            ]);
        }

        $schedule = ClinicSchedule::active()
            ->get()
            ->first(fn($s) => in_array($abbr, $s->days ?? []));

        if (! $schedule || $schedule->status === 'closed') {
            return response()->json([
                'slots'   => [],
                'message' => 'The clinic is closed on this day.',
            ]);
        }

        $bookedSlotCounts = Appointment::where('appointment_date', $iso)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_time, COUNT(*) as cnt')
            ->groupBy('appointment_time')
            ->pluck('cnt', 'appointment_time')
            ->toArray();

        $totalBooked = array_sum($bookedSlotCounts);

        if ($totalBooked >= $schedule->max_slots) {
            return response()->json([
                'slots'   => [],
                'message' => 'All slots for this day are fully booked.',
            ]);
        }

        $remaining =
            max(
                0,
                (int) $schedule->max_slots - $totalBooked
            );

        $slots =
            collect(
                $schedule->availableSlots(
                    $iso,
                    $bookedSlotCounts
                )
            )
            ->filter(
                fn($slot) => ($slot['available'] ?? false) === true
            )
            ->take($remaining)
            ->values()
            ->all();

        return response()->json([
            'slots'      => $slots,
            'max_slots'  => (int) $schedule->max_slots,
            'booked'     => $totalBooked,
            'remaining'  => $remaining,
            'open_time'  => $schedule->open_time,
            'close_time' => $schedule->close_time,
            'break_time' => $schedule->break_time,
        ]);
    }

    private function validateRule(Request $request): array
    {
        return $request->validate([
            'days'       => 'required|array|min:1',
            'days.*'     => 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
            'is_active'  => 'required|boolean',
            'status'     => 'required|in:open,closed,limited',
            'open_time'  => 'required_unless:status,closed|nullable|date_format:H:i',
            'close_time' => 'required_unless:status,closed|nullable|date_format:H:i|after:open_time',
            'break_time' => 'nullable|string',
            'max_slots'  => 'required_unless:status,closed|nullable|integer|min:1|max:30',
            'notes'      => 'nullable|string|max:500',
        ]);
    }

    private function ensureDaysAreAvailable(Request $request, array $days, ?int $ignoreId = null): void
    {
        $conflictingDays = $this->findConflictingScheduleDays($days, $ignoreId);

        if (empty($conflictingDays)) {
            return;
        }

        $request->validate([
            'days' => [
                function ($attribute, $value, $fail) use ($conflictingDays) {
                    $fail('An active schedule already exists for ' . $this->formatDays($conflictingDays) . '. Set the current active schedule to Inactive before activating this rule.');
                },
            ],
        ]);
    }

    private function findConflictingScheduleDays(array $days, ?int $ignoreId = null): array
    {
        $query = ClinicSchedule::active();

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $conflictingDays = [];

        foreach ($query->get() as $schedule) {
            $conflictingDays = array_merge(
                $conflictingDays,
                array_intersect($days, $schedule->days ?? [])
            );
        }

        return $this->sortDays(array_values(array_unique($conflictingDays)));
    }

    private function sortDays(array $days): array
    {
        $order = array_flip(self::ALL_DAYS);

        usort($days, fn($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

        return $days;
    }

    private function formatDays(array $days): string
    {
        return implode(', ', $this->sortDays($days));
    }

    private function prepareRule(array $v): array
    {
        $days = $v['days'];
        sort($days);
        $label = ($days === ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'])
            ? 'Mon – Fri'
            : (count($days) === 1 ? $days[0] : implode(', ', $days));

        return [
            'days_label' => $label,
            'days'       => $days,
            'status'     => $v['status'],
            'open_time'  => $v['open_time']  ?? null,
            'close_time' => $v['close_time'] ?? null,
            'break_time' => $v['break_time'] ?? null,
            'max_slots'  => $v['max_slots']  ?? 0,
            'notes'      => $v['notes']      ?? null,
            'is_active'  => (bool) $v['is_active'],
        ];
    }
}
