<?php

namespace App\Helpers;

use App\Models\ClinicSchedule;
use Illuminate\Database\QueryException;

class ClinicHours
{
    public static function get(): array
    {
        $weekDays = [
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
            'Sun',
        ];

        try {
            $weeklyHours = array_fill_keys($weekDays, null);

            $schedules = ClinicSchedule::active()
                ->get(['days', 'status', 'open_time', 'close_time']);

            foreach ($schedules as $schedule) {
                foreach ($schedule->days ?? [] as $day) {
                    if (!array_key_exists($day, $weeklyHours)) {
                        continue;
                    }

                    $weeklyHours[$day] = (
                        in_array($schedule->status, ['open', 'limited'], true)
                        && $schedule->open_time
                        && $schedule->close_time
                    )
                        ? $schedule->hours_range
                        . ($schedule->status === 'limited' ? ' (Limited)' : '')
                        : null;
                }
            }

            $groupedHours = [];

            foreach ($weeklyHours as $day => $hours) {
                if ($hours !== null) {
                    $groupedHours[$hours][] = $day;
                }
            }

            $clinicHours = [];

            foreach ($groupedHours as $hours => $days) {
                $firstIndex = array_search($days[0], $weekDays, true);
                $lastDay = $days[count($days) - 1];
                $lastIndex = array_search($lastDay, $weekDays, true);

                $dayLabel = count($days) > 1
                    && $lastIndex - $firstIndex + 1 === count($days)
                    ? "{$days[0]}–{$lastDay}"
                    : implode(', ', $days);

                $clinicHours[] = "{$dayLabel} · {$hours}";
            }

            return $clinicHours ?: ['No regular clinic hours posted'];
        } catch (QueryException $exception) {
            report($exception);

            return ['Clinic hours temporarily unavailable'];
        }
    }
}
