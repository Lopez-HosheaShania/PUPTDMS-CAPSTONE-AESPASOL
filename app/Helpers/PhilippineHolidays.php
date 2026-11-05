<?php

namespace App\Helpers;

/**
 * Philippine Public Holidays Helper
 *
 * Generates the full set of Philippine holidays for any given year.
 * Fixed-date holidays are computed directly.
 * Moveable holidays (Easter-based) are computed from the Easter algorithm.
 *
 * Usage:
 *   $holidays = \App\Helpers\PhilippineHolidays::forYear(2026);
 *   // Returns: ['2026-01-01' => "New Year's Day", '2026-04-02' => 'Maundy Thursday', ...]
 */
class PhilippineHolidays
{
    /**
     * Return all Philippine holidays for the given year as:
     *   ['YYYY-MM-DD' => 'Holiday Name', ...]
     */
    public static function forYear(int $year): array
    {
        $holidays = [];

        // Fixed-date Regular Holidays
        $fixed = [
            '01-01' => "New Year's Day",
            '04-09' => 'Araw ng Kagitingan (Day of Valor)',
            '05-01' => 'Labor Day',
            '06-12' => 'Independence Day',
            '08-26' => 'National Heroes Day',
            '11-30' => 'Bonifacio Day',
            '12-25' => 'Christmas Day',
            '12-30' => 'Rizal Day',
        ];

        foreach ($fixed as $mmdd => $name) {
            $holidays["{$year}-{$mmdd}"] = $name;
        }

        // National Heroes Day = last Monday of August
        $lastMonday = self::lastWeekdayOfMonth($year, 8, 1); // 1 = Monday
        $holidays[$lastMonday] = 'National Heroes Day';

        // Easter-based Moveable Holidays
        $easter     = self::easterDate($year);
        $maundy     = (clone $easter)->modify('-3 days');
        $goodFriday = (clone $easter)->modify('-2 days');
        $blackSat   = (clone $easter)->modify('-1 day');

        $holidays[$maundy->format('Y-m-d')]     = 'Maundy Thursday';
        $holidays[$goodFriday->format('Y-m-d')]  = 'Good Friday';
        $holidays[$blackSat->format('Y-m-d')]    = 'Black Saturday';

        // Special (Non-Working) Holidays
        $special = [
            '02-25' => 'EDSA People Power Revolution Anniversary',
            '08-21' => 'Ninoy Aquino Day',
            '11-01' => "All Saints' Day",
            '11-02' => "All Souls' Day",
            '12-08' => 'Feast of the Immaculate Conception of Mary',
            '12-24' => 'Christmas Eve',
            '12-31' => "New Year's Eve",
        ];

        foreach ($special as $mmdd => $name) {
            $holidays["{$year}-{$mmdd}"] = $name;
        }

        // Sort by date
        ksort($holidays);

        return $holidays;
    }

    /**
     * Return holidays for the CURRENT calendar year.
     */
    public static function current(): array
    {
        return self::forYear((int) date('Y'));
    }

    /**
     * Return holidays spanning the current and adjacent years
     * (useful for calendars that can navigate months forward/back).
     *
     * @param int $yearsBefore  How many years before current to include
     * @param int $yearsAfter   How many years after current to include
     */
    public static function range(int $yearsBefore = 1, int $yearsAfter = 1): array
    {
        $current = (int) date('Y');
        $merged  = [];

        for ($y = $current - $yearsBefore; $y <= $current + $yearsAfter; $y++) {
            $merged = array_merge($merged, self::forYear($y));
        }

        ksort($merged);
        return $merged;
    }

    /**
     * Return normalized holiday records from the maintained
     * Philippine holiday service.
     */
    public static function recordsForYear(
        int $year
    ): array {
        return app(
            \App\Services\PhilippineHolidayService::class
        )->forYear(
            $year
        );
    }

    /**
     * Return normalized holiday records for the current year.
     */
    public static function recordsCurrent(): array
    {
        return self::recordsForYear(
            (int) date('Y')
        );
    }

    /**
     * Return normalized holiday records across adjacent years.
     */
    public static function recordsRange(
        int $yearsBefore = 1,
        int $yearsAfter = 1
    ): array {
        return app(
            \App\Services\PhilippineHolidayService::class
        )->range(
            $yearsBefore,
            $yearsAfter
        );
    }

    public static function isBlockedForBooking(
        string $date
    ): bool {
        return app(
            \App\Services\PhilippineHolidayService::class
        )->isBlockedForBooking(
            $date
        );
    }
    
    // Private Helpers
    private static function easterDate(int $year): \DateTime
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }

    /**
     * Get the last occurrence of a given weekday in a month.
     *
     * @param int $year
     * @param int $month   1–12
     * @param int $weekday 0=Sunday … 6=Saturday (PHP date('w') convention)
     * @return string  'YYYY-MM-DD'
     */
    private static function lastWeekdayOfMonth(int $year, int $month, int $weekday): string
    {
        $lastDay = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $lastDay->modify('last day of this month');

        while ((int) $lastDay->format('w') !== $weekday) {
            $lastDay->modify('-1 day');
        }

        return $lastDay->format('Y-m-d');
    }
}
