<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\ReservedBookingPeriod;
use App\Models\User;
use App\Notifications\ReservedBookingPeriodInvitationNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReservedBookingInvitationService
{
    public function syncPeriod(ReservedBookingPeriod $period, bool $markUnread = true): void
    {
        $period->refresh();

        Patient::query()
            ->with('user')
            ->whereNotNull('user_id')
            ->chunkById(200, function ($patients) use ($period, $markUnread) {
                foreach ($patients as $patient) {
                    $this->syncPatientPeriod($patient, $period, $markUnread);
                }
            });
    }

    public function syncPatient(Patient $patient): Collection
    {
        $periods = $this->availablePeriodsFor($patient);
        $availablePeriodIds = $periods->pluck('id')->map(fn ($id) => (int) $id)->all();
        $user = $patient->user;

        if (! $user) {
            return $periods;
        }

        $user->notifications()
            ->where('type', ReservedBookingPeriodInvitationNotification::class)
            ->get()
            ->reject(fn (DatabaseNotification $notification) => in_array(
                (int) data_get($notification->data, 'reserved_booking_period_id'),
                $availablePeriodIds,
                true
            ))
            ->each->delete();

        $periods->each(fn (ReservedBookingPeriod $period) => $this->syncPatientPeriod($patient, $period));

        return $periods;
    }

    public function availablePeriodsFor(Patient $patient): Collection
    {
        return ReservedBookingPeriod::query()
            ->with(['slots.appointment'])
            ->active()
            ->whereDate('reserved_date', '>=', now()->toDateString())
            ->orderBy('reserved_date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (ReservedBookingPeriod $period) => $this->isAvailableTo($period, $patient))
            ->values();
    }

    public function removePeriod(ReservedBookingPeriod $period): void
    {
        $this->periodNotifications($period)->each->delete();
    }

    private function syncPatientPeriod(
        Patient $patient,
        ReservedBookingPeriod $period,
        bool $markUnread = false
    ): void
    {
        $user = $patient->user;

        if (! $user) {
            return;
        }

        $existing = $this->periodNotificationForUser($user, $period);
        $eligible = $this->isAvailableTo($period, $patient);

        if (! $eligible) {
            $existing?->delete();

            return;
        }

        $notification = new ReservedBookingPeriodInvitationNotification($period);
        $payload = $notification->toArray($user);

        if ($existing) {
            $existing->forceFill([
                'data' => $payload,
                ...($markUnread ? ['read_at' => null] : []),
            ])->save();

            return;
        }

        $user->notify($notification);
    }

    private function isAvailableTo(ReservedBookingPeriod $period, Patient $patient): bool
    {
        if (! $period->is_active || $period->trashed() || ! $period->isEligiblePatient($patient)) {
            return false;
        }

        $reservedDate = Carbon::parse($period->reserved_date)->startOfDay();

        // Temporarily disabled: same-day reserved invitations used to be unavailable.
        // if (! $reservedDate->isAfter(now()->startOfDay())) {
        //     return false;
        // }

        if ($reservedDate->isBefore(now()->startOfDay())) {
            return false;
        }

        if ($reservedDate->isToday()
            && Carbon::parse($period->reserved_date->format('Y-m-d').' '.$period->end_time)->isPast()) {
            return false;
        }

        if ($period->appointments()->where('patient_id', $patient->id)->exists()) {
            return false;
        }

        if ($period->activeAppointments()->count() >= $period->max_capacity) {
            return false;
        }

        if ($period->booking_mode !== 'timeslot') {
            return true;
        }

        $period->loadMissing('slots.appointment');

        return $period->slots->contains(
            fn ($slot) => ! $slot->appointment || $slot->appointment->status === 'cancelled'
        );
    }

    private function periodNotificationForUser(
        User $user,
        ReservedBookingPeriod $period
    ): ?DatabaseNotification {
        return $user->notifications()
            ->where('type', ReservedBookingPeriodInvitationNotification::class)
            ->get()
            ->first(fn ($notification) => (int) data_get(
                $notification->data,
                'reserved_booking_period_id'
            ) === $period->id);
    }

    private function periodNotifications(ReservedBookingPeriod $period)
    {
        return DatabaseNotification::query()
            ->where('type', ReservedBookingPeriodInvitationNotification::class)
            ->get()
            ->filter(fn ($notification) => (int) data_get(
                $notification->data,
                'reserved_booking_period_id'
            ) === $period->id);
    }
}
