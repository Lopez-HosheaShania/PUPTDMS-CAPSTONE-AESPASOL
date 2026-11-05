<?php

namespace App\Support;

class DentalCaseTeeth
{
    public static function count(array|string|null $odontogram): int
    {
        if (is_string($odontogram)) {
            $odontogram = json_decode($odontogram, true);
        }
        if (! is_array($odontogram)) {
            return 0;
        }

        $teeth = [];
        foreach ($odontogram ?? [] as $entry) {
            $tooth = (int) data_get($entry, 'tooth', 0);
            if ($tooth > 0 && OdontogramTreatmentDisplay::items([$entry]) !== []) {
                $teeth[$tooth] = true;
            }
        }

        return count($teeth);
    }
}
