<?php

namespace App\Support;

use App\Models\Appointment;
use Carbon\Carbon;

class DentalServicesProcedureTiming
{
    public static function requestedTimeIn(Appointment $appointment): ?Carbon
    {
        if ($appointment->procedure?->procedure_started_at) {
            return $appointment->procedure->procedure_started_at->copy();
        }

        // Older records may only have the booked time available.
        return $appointment->appointment_date && $appointment->appointment_time
            ? Carbon::parse(Carbon::parse($appointment->appointment_date)->toDateString() . ' ' . $appointment->appointment_time)
            : null;
    }

    public static function minutesProcessed(Appointment $appointment): ?int
    {
        $seconds = $appointment->procedure?->procedure_duration_seconds;

        return $seconds !== null ? (int) ceil(max(0, $seconds) / 60) : null;
    }

    public static function timeProcessed(Appointment $appointment): ?Carbon
    {
        $start = self::requestedTimeIn($appointment);
        $end = $appointment->procedure?->procedure_completed_at;
        $seconds = $appointment->procedure?->procedure_duration_seconds;

        if ($start && $end && $seconds !== null && $start->diffInSeconds($end, false) === (float) $seconds) {
            return $end->copy();
        }

        $minutes = self::minutesProcessed($appointment);

        return $start && $minutes !== null ? $start->addMinutes($minutes) : null;
    }
}
