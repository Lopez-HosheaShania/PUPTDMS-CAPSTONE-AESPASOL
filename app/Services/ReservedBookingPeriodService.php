<?php

namespace App\Services;

use App\Helpers\PhilippineHolidays;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Models\ReservedBookingPeriod;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservedBookingPeriodService
{
    public function __construct(
        private readonly StudentTargetOptionService $studentTargetOptionService,
        private readonly ReservedBookingInvitationService $invitationService
    ) {
    }

    public function create(array $data, User $actor): ReservedBookingPeriod
    {
        $period = DB::transaction(function () use ($data, $actor) {
            $this->validateScheduleRestrictions($data);

            $period = ReservedBookingPeriod::create([
                ...$this->normalize($data),
                'is_active' => $this->requestedActive($data),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncSlots($period, $data);

            return $period->load('slots');
        });

        // Inactive periods are drafts only. They do not reserve clinic time and
        // they do not create patient notifications until they are activated.
        if ($period->is_active) {
            $this->invitationService->syncPeriod($period, true);
        }

        return $period;
    }

    public function update(
        ReservedBookingPeriod $period,
        array $data,
        User $actor
    ): ReservedBookingPeriod {
        $wasActive = (bool) $period->is_active;

        $updatedPeriod = DB::transaction(function () use ($period, $data, $actor) {
            $lockedPeriod = ReservedBookingPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            $newIsActive = $this->requestedActive($data);

            if ($lockedPeriod->is_active
                && ! $newIsActive
                && $lockedPeriod->activeAppointments()->exists()) {
                $this->fail(
                    'is_active',
                    'This reserved period has active patient bookings and cannot be set to Inactive. Cancel or complete those bookings first.'
                );
            }

            $this->validateScheduleRestrictions($data, $lockedPeriod->id);
            $normalized = $this->normalize($data);

            if ($lockedPeriod->activeAppointments()->exists()) {
                $protectedFields = [
                    'reserved_date',
                    'start_time',
                    'end_time',
                    'booking_mode',
                    'timeslot_duration_minutes',
                    'target_patient_type',
                    'program_code',
                    'year_level',
                    'section',
                    'max_capacity',
                ];

                $scheduleChanged = collect($protectedFields)->contains(
                    fn ($field) => (string) $lockedPeriod->getRawOriginal($field)
                        !== (string) ($normalized[$field] ?? '')
                );

                if ($scheduleChanged) {
                    $this->fail(
                        'reserved_date',
                        'The schedule, target, and capacity cannot be changed after patients have booked this period.'
                    );
                }
            }

            $lockedPeriod->update([
                ...$normalized,
                'is_active' => $newIsActive,
                'updated_by' => $actor->id,
            ]);

            if (! $lockedPeriod->activeAppointments()->exists()) {
                $this->syncSlots($lockedPeriod, $data);
            }

            return $lockedPeriod->refresh()->load('slots');
        });

        if ($updatedPeriod->is_active) {
            // Only a transition from Inactive -> Active should make existing
            // invitations unread again. Normal edits while already active only
            // refresh their notification payload.
            $this->invitationService->syncPeriod($updatedPeriod, ! $wasActive);
        } else {
            // Deactivation immediately removes invitations and releases the date
            // and time range for regular booking logic, which only reads active periods.
            $this->invitationService->removePeriod($updatedPeriod);
        }

        return $updatedPeriod;
    }

    private function validateScheduleRestrictions(
        array $data,
        ?int $ignorePeriodId = null
    ): void {
        $this->validateStudentTarget($data);

        $start = $this->normalizeTime($data['start_time']);
        $end = $this->normalizeTime($data['end_time']);
        $this->validateTimeslots($data, $start, $end);

        // Inactive periods are saved as drafts. Operational restrictions that
        // actually reserve clinic availability are enforced only on activation.
        if (! $this->requestedActive($data)) {
            return;
        }

        $date = Carbon::parse($data['reserved_date']);
        $dateString = $date->toDateString();

        if (BlockedDate::whereDate('date', $dateString)->exists()) {
            $this->fail(
                'reserved_date',
                'The selected date is blocked and cannot have an active reserved booking period.'
            );
        }

        if (array_key_exists($dateString, PhilippineHolidays::recordsRange(0, 2))) {
            $this->fail(
                'reserved_date',
                'Active reserved booking periods cannot be scheduled on a holiday.'
            );
        }

        $day = $date->format('D');
        $clinicSchedule = ClinicSchedule::active()
            ->get()
            ->first(fn (ClinicSchedule $schedule) => in_array($day, $schedule->days ?? [], true));

        if (! $clinicSchedule || $clinicSchedule->status === 'closed') {
            $this->fail(
                'reserved_date',
                'The clinic is closed on the selected date. Keep this period Inactive or choose an open clinic date.'
            );
        }

        if (! $clinicSchedule->open_time || ! $clinicSchedule->close_time) {
            $this->fail(
                'reserved_date',
                'Clinic operating hours are not configured for the selected date.'
            );
        }

        $dateConflictQuery = ReservedBookingPeriod::active()
            ->whereDate('reserved_date', $dateString);

        if ($ignorePeriodId !== null) {
            $dateConflictQuery->whereKeyNot($ignorePeriodId);
        }

        if ($dateConflictQuery->lockForUpdate()->exists()) {
            $this->fail(
                'reserved_date',
                'This date already has an active reserved booking period. Set that period to Inactive first or choose another date.'
            );
        }

        $clinicOpen = $this->normalizeTime($clinicSchedule->open_time);
        $clinicClose = $this->normalizeTime($clinicSchedule->close_time);

        if ($start < $clinicOpen || $end > $clinicClose) {
            $this->fail(
                'start_time',
                sprintf(
                    'The reserved period must stay within clinic hours (%s-%s) before it can be activated.',
                    Carbon::parse($clinicOpen)->format('g:i A'),
                    Carbon::parse($clinicClose)->format('g:i A')
                )
            );
        }

        if ($start === $clinicOpen && $end === $clinicClose) {
            $this->fail(
                'end_time',
                'An active reserved period cannot occupy the clinic\'s entire operating day.'
            );
        }

        $hasExistingAppointments = Appointment::query()
            ->whereDate('appointment_date', $dateString)
            ->whereNull('reserved_booking_period_id')
            ->whereTime('appointment_time', '>=', $start)
            ->whereTime('appointment_time', '<', $end)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->lockForUpdate()
            ->exists();

        if ($hasExistingAppointments) {
            $this->fail(
                'start_time',
                'This time range already contains regular appointments and cannot be activated as a reserved period.'
            );
        }
    }

    private function normalize(array $data): array
    {
        $isStudent = $data['target_patient_type'] === 'student';

        return [
            'title' => trim($data['title']),
            'reserved_date' => Carbon::parse($data['reserved_date'])->toDateString(),
            'start_time' => $this->normalizeTime($data['start_time']),
            'end_time' => $this->normalizeTime($data['end_time']),
            'booking_mode' => $data['booking_mode'],
            'timeslot_duration_minutes' => $data['booking_mode'] === 'timeslot'
                ? (int) $data['timeslot_duration_minutes']
                : null,
            'target_patient_type' => $data['target_patient_type'],
            'program_code' => $isStudent ? strtoupper(trim($data['program_code'])) : null,
            'year_level' => $isStudent ? (int) $data['year_level'] : null,
            'section' => $isStudent ? strtoupper(trim($data['section'])) : null,
            'max_capacity' => $data['booking_mode'] === 'timeslot'
                ? count($data['timeslots'])
                : (int) $data['max_capacity'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function validateStudentTarget(array $data): void
    {
        if ($data['target_patient_type'] !== 'student') {
            return;
        }

        $program = strtoupper(trim((string) $data['program_code']));
        $year = (int) $data['year_level'];
        $section = strtoupper(trim((string) $data['section']));

        $isAvailable = $this->studentTargetOptionService->get()->contains(
            fn ($option) => strtoupper((string) $option['course_code']) === $program
                && (int) $option['year_level'] === $year
                && strtoupper((string) $option['section']) === $section
        );

        if (! $isAvailable) {
            $this->fail(
                'program_code',
                'The selected program, year level, and section is not available in the student information system.'
            );
        }
    }

    private function validateTimeslots(array $data, string $start, string $end): void
    {
        if ($data['booking_mode'] !== 'timeslot') {
            return;
        }

        $times = collect($data['timeslots'])
            ->map(fn ($slot) => $this->normalizeTime($slot['time']));
        $duration = (int) $data['timeslot_duration_minutes'];
        $startMinutes = $this->timeToMinutes($start);
        $endMinutes = $this->timeToMinutes($end);
        $slotMinutes = $times->map(fn ($time) => $this->timeToMinutes($time))->sort()->values();

        if ($times->duplicates()->isNotEmpty()) {
            $this->fail('timeslots', 'Each selectable timeslot must have a unique time.');
        }

        if ($slotMinutes->contains(
            fn ($time) => $time < $startMinutes || ($time + $duration) > $endMinutes
        )) {
            $this->fail(
                'timeslots',
                'Every timeslot, including its duration, must fit within the reserved period.'
            );
        }

        for ($index = 1; $index < $slotMinutes->count(); $index++) {
            if ($slotMinutes[$index] < ($slotMinutes[$index - 1] + $duration)) {
                $this->fail('timeslots', 'Timeslots cannot overlap based on the selected duration.');
            }
        }

        if (count($data['timeslots']) > ReservedBookingPeriod::MAX_CAPACITY) {
            $this->fail(
                'timeslots',
                'A reserved period cannot have more than '.ReservedBookingPeriod::MAX_CAPACITY.' timeslots.'
            );
        }
    }

    private function syncSlots(ReservedBookingPeriod $period, array $data): void
    {
        $period->slots()->delete();

        if ($data['booking_mode'] !== 'timeslot') {
            return;
        }

        $period->slots()->createMany(
            collect($data['timeslots'])
                ->sortBy('time')
                ->map(fn ($slot) => [
                    'slot_time' => $this->normalizeTime($slot['time']),
                    'max_capacity' => 1,
                ])
                ->values()
                ->all()
        );
    }

    private function requestedActive(array $data): bool
    {
        return filter_var($data['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function normalizeTime(?string $time): string
    {
        return Carbon::parse((string) $time)->format('H:i:s');
    }

    private function timeToMinutes(string $time): int
    {
        $parsed = Carbon::parse($time);

        return ($parsed->hour * 60) + $parsed->minute;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ])->errorBag('reservedPeriod');
    }
}
