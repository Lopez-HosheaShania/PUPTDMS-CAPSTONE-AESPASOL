<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ProcedureTimingService
{
    public static function rules(): array
    {
        return [
            'procedure_started_at' => ['required', 'date_format:H:i,H:i:s,Y-m-d\TH:i,Y-m-d\TH:i:s'],
            'procedure_completed_at' => ['required', 'date_format:H:i,H:i:s,Y-m-d\TH:i,Y-m-d\TH:i:s', 'after_or_equal:procedure_started_at'],
        ];
    }

    public static function attributes(): array
    {
        return ['procedure_started_at' => 'Time In', 'procedure_completed_at' => 'Time Processed'];
    }

    public function forAppointment(Appointment $appointment, array $validated): array
    {
        // Time-only inputs belong to the visit date, not today's date.
        $date = Carbon::parse($appointment->appointment_date)->toDateString();
        $parseTime = fn (string $value) => Carbon::parse(
            str_contains($value, 'T') ? $value : $date.' '.$value,
            config('app.timezone')
        );
        $start = $parseTime($validated['procedure_started_at']);
        $end = $parseTime($validated['procedure_completed_at']);
        $seconds = (int) $start->diffInSeconds($end, false);

        if ($seconds < 0 || $seconds > 4294967295) {
            throw ValidationException::withMessages([
                'procedure_completed_at' => 'Time Processed must be on or after Time In and within the supported duration.',
            ]);
        }

        User::whereKey($appointment->dentist_id)->lockForUpdate()->first();
        Patient::whereKey($appointment->patient_id)->lockForUpdate()->firstOrFail();
        Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

        if ($seconds > 0 && Appointment::withoutGlobalScope('clinical_patients')
            ->whereKeyNot($appointment->id)
            ->where(fn ($query) => $query->where('dentist_id', $appointment->dentist_id)
                ->orWhere('patient_id', $appointment->patient_id))
            ->whereHas('procedure.timing', fn ($query) => $query
                ->whereColumn('completed_at', '>', 'started_at')
                ->where('started_at', '<', $end)
                ->where('completed_at', '>', $start))
            ->exists()) {
            throw ValidationException::withMessages([
                'procedure_started_at' => 'These treatment times overlap another saved procedure for this dentist or patient.',
            ]);
        }

        return [$start, $end, $seconds];
    }
}
