<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentCancellationAnalytics
{
    public function currentMonth(?Carbon $now = null): array
    {
        $now ??= now();

        return $this->forMonth($now->year, $now->month, $now);
    }

    public function forMonth(int $year, int $month, ?Carbon $now = null): array
    {
        $now ??= now();
        $period = $now->copy()->startOfMonth()->setDate($year, $month, 1);

        try {
            $appointments = Appointment::query()
                ->whereYear('appointment_date', $year)
                ->whereMonth('appointment_date', $month)
                ->get(['status', 'cancellation_reason', 'appointment_date', 'appointment_time']);
            $total = $appointments->count();
            $cancelled = $appointments->where('status', 'cancelled');
            $cancelledCount = $cancelled->count();

            $normalizeReason = fn ($reason) => preg_replace('/\s+/u', ' ', trim((string) $reason));
            $reasons = $cancelled
                ->groupBy(fn ($appointment) => mb_strtolower($normalizeReason($appointment->cancellation_reason)))
                ->map(function ($items) use ($normalizeReason, $cancelledCount) {
                    return [
                        'reason' => $normalizeReason($items->first()->cancellation_reason) ?: 'Reason not recorded',
                        'count' => $items->count(),
                        'percentage' => round($items->count() / $cancelledCount * 100, 1),
                    ];
                });

            foreach (Appointment::CANCELLATION_REASONS as $reason) {
                $key = mb_strtolower($reason);
                $entry = $reasons->get($key, ['count' => 0, 'percentage' => 0]);
                $entry['reason'] = $reason;
                $reasons->put($key, $entry);
            }
            $reasons = $reasons->sortByDesc('count')->values()->all();

            $legacyNoShows = 0;
            $noShows = $appointments->filter(function ($appointment) use ($now, $normalizeReason, &$legacyNoShows) {
                $explicitStatus = in_array($appointment->status, ['no_show', 'no-show'], true);
                $explicitReason = $appointment->status === 'cancelled'
                    && mb_strtolower($normalizeReason($appointment->cancellation_reason)) === 'patient no-show';

                if (!$explicitStatus && !$explicitReason) {
                    return false;
                }

                if (!$appointment->appointment_date || !$appointment->appointment_time) {
                    return false;
                }
                $schedule = Carbon::parse(Carbon::parse($appointment->appointment_date)->toDateString().' '.$appointment->getRawOriginal('appointment_time'), $now->timezone);
                if (!$schedule->lt($now)) {
                    return false;
                }
                if ($explicitReason) {
                    $legacyNoShows++;
                }

                return true;
            })->count();

            return [
                'available' => true,
                'period' => $period->format('F Y'),
                'month' => $month,
                'year' => $year,
                'years' => range($now->year, min($now->year - 5, (int) substr(Appointment::query()->min('appointment_date') ?? $now->toDateString(), 0, 4))),
                'total_scheduled' => $total,
                'total_cancelled' => $cancelledCount,
                'cancellation_rate' => $total > 0 ? round($cancelledCount / $total * 100) : 0,
                'total_no_shows' => $noShows,
                'no_show_rate' => $total > 0 ? round($noShows / $total * 100, 1) : 0,
                'legacy_no_shows' => $legacyNoShows,
                'reasons' => $reasons,
            ];
        } catch (Throwable $exception) {
            Log::warning('Appointment cancellation analytics could not be loaded.', ['message' => $exception->getMessage()]);

            return ['available' => false, 'period' => $period->format('F Y'), 'month' => $month, 'year' => $year, 'years' => range($now->year, $now->year - 5)];
        }
    }
}
