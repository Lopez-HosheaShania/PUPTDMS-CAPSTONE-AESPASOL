<?php

namespace App\Support;

use App\Models\Patient;

class PatientClassification
{
    public static function label(?Patient $patient): string
    {
        $category = strtolower(trim((string) $patient?->classification));
        if ($category === 'faculty' || filled($patient?->faculty_code)) {
            return 'Faculty';
        }
        if ($category === 'student' || filled($patient?->student_no)) {
            return 'Student';
        }

        return in_array($category, ['administrative', 'administrative personnel'], true)
            ? 'Administrative Personnel' : 'Patient';
    }
}
