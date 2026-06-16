<?php

namespace App\Http\Controllers\Dentist;

use App\Helpers\PhilippineHolidays;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DentistClinicScheduleController extends Controller
{
    public function index()
    {
        $schedules = ClinicSchedule::active()->orderBy('id')->get();
        $blockedDates = BlockedDate::orderBy('date')->get();

        $startDate = Carbon::now()->startOfMonth()->subMonth();
        $endDate = Carbon::now()->endOfMonth()->addMonths(3);

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

        $philippineHolidays = PhilippineHolidays::range(0, 1);
        $notifications = [];

        return view('dentist.dentist-clinic-schedule', compact(
            'schedules',
            'blockedDates',
            'appointmentCountsPerDay',
            'weeklyAppointments',
            'philippineHolidays',
            'notifications',
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request);

        ClinicSchedule::create($this->prepareRule($validated));

        return back()->with('success', 'Schedule rule added successfully.');
    }

    public function update(Request $request, ClinicSchedule $clinicSchedule)
    {
        $validated = $this->validateRule($request);

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

        $user = $request->user();

        BlockedDate::create([
            'date'       => $validated['date'],
            'reason'     => $validated['reason'],
            'note'       => $validated['note'] ?? null,
            'created_by' => $user?->getAuthIdentifier(),
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
        $end = $start->copy()->addMonths(3);

        $blocked = BlockedDate::whereBetween('date', [$start, $end])
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $closedDayAbbrs = [];

        foreach (ClinicSchedule::active()->where('status', 'closed')->get() as $schedule) {
            $closedDayAbbrs = array_merge($closedDayAbbrs, $schedule->days ?? []);
        }

        $closedDayAbbrs = array_unique($closedDayAbbrs);
        $unavailable = $blocked;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $abbr = $date->format('D');

            if (in_array($abbr, $closedDayAbbrs, true)) {
                $unavailable[] = $date->toDateString();
            }
        }

        $philippineHolidays = PhilippineHolidays::range(0, 1);

        foreach ($philippineHolidays as $date => $name) {
            if ($date >= $start->toDateString() && $date <= $end->toDateString()) {
                $unavailable[] = $date;
            }
        }

        return response()->json(array_values(array_unique($unavailable)));
    }

    public function slotsForDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $iso = $request->date;
        $carbon = Carbon::parse($iso);
        $abbr = $carbon->format('D');

        if (BlockedDate::where('date', $iso)->exists()) {
            return response()->json([
                'slots' => [],
                'message' => 'This date is blocked and unavailable for booking.',
            ]);
        }

        $philippineHolidays = PhilippineHolidays::range(0, 1);

        if (isset($philippineHolidays[$iso])) {
            return response()->json([
                'slots' => [],
                'message' => 'The clinic is closed on holidays.',
            ]);
        }

        $schedule = ClinicSchedule::active()
            ->get()
            ->first(fn($schedule) => in_array($abbr, $schedule->days ?? [], true));

        if (! $schedule || $schedule->status === 'closed') {
            return response()->json([
                'slots' => [],
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
                'slots' => [],
                'message' => 'All slots for this day are fully booked.',
            ]);
        }

        return response()->json([
            'slots' => $schedule->availableSlots($iso, $bookedSlotCounts),
            'max_slots' => $schedule->max_slots,
            'booked' => $totalBooked,
            'remaining' => max(0, $schedule->max_slots - $totalBooked),
            'open_time' => $schedule->open_time,
            'close_time' => $schedule->close_time,
            'break_time' => $schedule->break_time,
        ]);
    }

    private function validateRule(Request $request): array
    {
        return $request->validate([
            'days' => 'required|array|min:1',
            'days.*' => 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
            'status' => 'required|in:open,closed,limited',
            'open_time' => 'required_unless:status,closed|nullable|date_format:H:i',
            'close_time' => 'required_unless:status,closed|nullable|date_format:H:i|after:open_time',
            'break_time' => 'nullable|string',
            'max_slots' => 'required_unless:status,closed|nullable|integer|min:1|max:50',
            'notes' => 'nullable|string|max:500',
        ]);
    }

    private function prepareRule(array $validated): array
    {
        $days = $validated['days'];

        sort($days);

        $label = ($days === ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'])
            ? 'Mon – Fri'
            : (count($days) === 1 ? $days[0] : implode(', ', $days));

        return [
            'days_label' => $label,
            'days' => $days,
            'status' => $validated['status'],
            'open_time' => $validated['open_time'] ?? null,
            'close_time' => $validated['close_time'] ?? null,
            'break_time' => $validated['break_time'] ?? null,
            'max_slots' => $validated['max_slots'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ];
    }
}
