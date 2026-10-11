<?php

namespace App\Support;

use App\Models\Patient;

class DentalServicesPatientName
{
    public static function surnameFirst(?Patient $patient): string
    {
        $user = $patient?->user;
        $surname = trim((string) ($user?->last_name ?? ''));
        $givenNames = array_values(array_filter([
            trim((string) ($user?->first_name ?? '')),
            trim((string) ($user?->middle_name ?? '')),
        ], fn($value) => $value !== ''));
        $suffix = trim((string) ($user?->suffix_name ?? ''));

        if ($surname !== '' && $givenNames !== []) {
            // The profile is authoritative; never split its surname on spaces.
            return $surname . ', ' . implode(' ', array_filter([
                implode(' ', $givenNames),
                $suffix,
            ], fn($value) => $value !== ''));
        }

        $fullName = preg_replace('/\s+/u', ' ', trim((string) ($patient?->name ?? ''))) ?? '';

        if ($fullName === '') {
            return $surname !== '' ? $surname : 'Unknown Patient';
        }

        // Legacy names already in surname-first order must not be reversed again.
        if (str_contains($fullName, ',')) {
            return $fullName;
        }

        $parts = explode(' ', $fullName);

        if (count($parts) > 1 && preg_match('/^(?:jr|sr|ii|iii|iv|v|vi|vii|viii|ix|x)\.?$/iu', end($parts))) {
            $suffix = array_pop($parts);
        }

        if ($surname !== '') {
            $nameWithoutSuffix = implode(' ', $parts);
            $surnamePattern = preg_quote(preg_replace('/\s+/u', ' ', $surname), '/');

            if (preg_match('/^(.*?)\s+' . $surnamePattern . '$/iu', $nameWithoutSuffix, $matches)) {
                return $surname . ', ' . trim($matches[1] . ' ' . $suffix);
            }

            // Incomplete, inconsistent profile data is not enough to guess a new name.
            return $fullName;
        }

        if (count($parts) === 1) {
            return trim($parts[0] . ' ' . $suffix);
        }

        // Only full-name-only legacy records need this surname-particle heuristic.
        $surnameParts = [array_pop($parts)];
        while (count($parts) > 1 && in_array(mb_strtolower(end($parts)), [
            'de', 'del', 'dela', 'la', 'las', 'los', 'san', 'santa', 'santo',
            'da', 'das', 'do', 'dos', 'van', 'von', 'der', 'den',
        ], true)) {
            array_unshift($surnameParts, array_pop($parts));
        }

        return implode(' ', $surnameParts) . ', ' . trim(implode(' ', $parts) . ' ' . $suffix);
    }
}
