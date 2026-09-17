<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AppointmentReportRecords
{
    /** Read report rows from the same completed appointments used by the PDF downloads. */
    public function between(Carbon $from, Carbon $to): Collection
    {
        return Appointment::with(['patient.user', 'patient.information', 'patient.medicalHistory', 'procedure'])
            ->where('status', 'completed')->whereHas('patient')
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->orderByDesc('appointment_date')->orderByDesc('appointment_time')->orderByDesc('id')
            ->get()->map(function (Appointment $appointment) {
                $patient = $appointment->patient;
                $procedure = $appointment->procedure;
                $classification = strtolower(trim((string) $patient->classification));
                $department = match ($classification) {
                    'alumni', 'dependent_alumni', 'dependent' => 'Dependent',
                    'administrative', 'administrative personnel' => 'Administrative',
                    default => ucfirst($classification),
                };
                $birthdate = $patient->birthdate ?? $patient->user?->birthdate;
                try {
                    $age = $birthdate ? (int) Carbon::parse($birthdate)->age : null;
                } catch (\Throwable) {
                    $age = null;
                }
                $date = Carbon::parse($appointment->appointment_date);
                $signature = $patient->medicalHistory?->patient_signature;

                return (object) [
                    'id' => $appointment->id,
                    'treatment_date' => $date,
                    'time_in' => $appointment->appointment_time ? Carbon::parse($date->toDateString().' '.$appointment->appointment_time) : null,
                    'time_out' => $procedure?->procedure_completed_at ? Carbon::parse($procedure->procedure_completed_at) : null,
                    'updated_at' => $procedure?->procedure_completed_at ? Carbon::parse($procedure->procedure_completed_at) : $appointment->updated_at,
                    'patient_name' => $patient->name,
                    'patient_email' => $patient->email, 'email' => $patient->email,
                    'patient_phone' => $patient->phone, 'contact' => $patient->phone,
                    'office_type' => $department, 'department' => $department,
                    'program_code' => $patient->course_code,
                    'year_level' => $patient->year_level, 'section' => $patient->section,
                    'age' => $age, 'gender' => ucfirst(strtolower((string) ($patient->gender ?? $patient->user?->gender))),
                    'is_senior' => (bool) $patient->is_senior, 'is_pwd' => (bool) $patient->is_pwd,
                    'treatment_done' => $appointment->service_type ?? 'Dental Service',
                    'minutes_processed' => round(($procedure?->procedure_duration_seconds ?? 0) / 60, 2),
                    'has_signature' => filled($signature), 'signature_path' => $signature,
                    'is_walk_in' => (bool) $appointment->is_walk_in,
                    'visit_type' => $appointment->is_walk_in ? 'Emergency' : 'Non-Emergency',
                ];
            });
    }
}
