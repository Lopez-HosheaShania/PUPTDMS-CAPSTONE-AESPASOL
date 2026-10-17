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
        $minutes = self::minutesProcessed($appointment);

        return $start && $minutes !== null ? $start->addMinutes($minutes) : null;
    }
}
