<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DocumentTemplate;
use App\Support\DentalServicesPatientName;
use App\Support\DentalServicesProcedureTiming;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DentalServicesRecordController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $this->resolveSelectedMonth($request->input('month'));

        $records = $this->recordsForMonth($selectedMonth)
            ->map(fn(Appointment $appointment) => $this->toFrontendRow($appointment))
            ->values();

        $dentalServiceTemplates = DocumentTemplate::query()
            ->active()
            ->where('document_type', 'dental_services')
            ->orderBy('name')
            ->get();

        $notifications = [];

        return view('dentist.dental-services', [
            'records' => $records,
            'selectedMonth' => $selectedMonth,
            'notifications' => $notifications,
            'dentalServiceTemplates' => $dentalServiceTemplates,
        ]);
    }

    public function data(Request $request)
    {
        $selectedMonth = $this->resolveSelectedMonth($request->input('month'));

        $records = $this->recordsForMonth($selectedMonth)
            ->map(fn(Appointment $appointment) => $this->toFrontendRow($appointment))
            ->values();

        return response()->json([
            'records' => $records,
            'selectedMonth' => $selectedMonth,
        ]);
    }

    private function resolveSelectedMonth(?string $month): string
    {
        if ($month && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $month;
        }

        return now()->format('Y-m');
    }

    private function recordsForMonth(string $selectedMonth)
    {
        $start = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return Appointment::query()
            ->with([
                'patient.medicalHistory',
                'patient.user.profile',
                'procedure',
            ])
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<=', $end->toDateString())
            ->whereHas('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();
    }

    private function toFrontendRow(Appointment $appointment): array
    {
        $patient = $appointment->patient;
        $procedure = $appointment->procedure;
        $gender = strtolower(trim((string) ($patient->gender ?? $patient->user?->gender ?? '')));
        $isMale = str_starts_with($gender, 'm');
        $isFemale = str_starts_with($gender, 'f');

        $programDisplay = trim((string) ($patient->course_code ?? ''));

        if ($programDisplay === '') {
            $programDisplay = trim((string) ($patient->course_name ?? ''));
        }

        if ($programDisplay !== '' && !empty($patient->year_level)) {
            $programDisplay .= ' - Y' . $patient->year_level;
        }

        if ($programDisplay !== '' && !empty($patient->section)) {
            $programDisplay .= ' / ' . $patient->section;
        }

        if ($programDisplay === '') {
            $programDisplay = trim((string) ($patient->faculty_code ?? ''));
        }

        $programDisplay = $programDisplay !== '' ? $programDisplay : '—';

        $priority = array_values(array_filter([
            ($patient->is_pwd ?? false) ? 'PWD' : null,
            ($patient->is_senior ?? false) ? 'Senior' : null,
        ]));

        $timeIn = DentalServicesProcedureTiming::requestedTimeIn($appointment)?->format('h:i A') ?? '';
        $timeOut = DentalServicesProcedureTiming::timeProcessed($appointment)?->format('h:i A') ?? '';
        $durationMinutes = DentalServicesProcedureTiming::minutesProcessed($appointment);
        $duration = $durationMinutes !== null ? $durationMinutes . ' mins' : '';

        return [
            'id' => $appointment->id,
            'date' => $appointment->appointment_date
                ? Carbon::parse($appointment->appointment_date)->format('m/d/y')
                : '',
            'dateKey' => $appointment->appointment_date
                ? Carbon::parse($appointment->appointment_date)->format('Y-m-d')
                : '',
            'monthKey' => $appointment->appointment_date
                ? Carbon::parse($appointment->appointment_date)->format('Y-m')
                : '',
            'timeIn' => $timeIn,
            'name' => DentalServicesPatientName::surnameFirst($patient),
            'program' => $programDisplay,
            'age' => $this->patientAge($patient?->birthdate ?? $patient?->user?->birthdate),
            'gad' => [
                'gender' => $isMale ? 'Male' : ($isFemale ? 'Female' : trim((string) ($patient->gender ?? ''))),
                'priority' => $priority,
            ],
            'email' => trim((string) ($patient->email ?? '')),
            'contact' => trim((string) ($patient->phone ?? '')),
            'timeOut' => $timeOut,
            'duration' => $duration,
            'type' => $appointment->is_emergency ? 'Emergency' : 'Non-Emergency',
            'department' => $this->departmentLabel($patient),
            'has_signature' => filled($patient?->medicalHistory?->patient_signature),
            'signature_url' => filled($patient?->medicalHistory?->patient_signature)
                ? asset('storage/' . ltrim((string) $patient->medicalHistory->patient_signature, '/'))
                : null,
        ];
    }

    private function patientAge($birthdate): string
    {
        if (empty($birthdate)) {
            return '';
        }

        try {
            return (string) Carbon::parse($birthdate)->age;
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function departmentLabel($patient): string
    {
        if (!$patient) {
            return '';
        }

        $patientType = strtolower(trim((string) (
            $patient->classification
            ?? $patient->patient_type
            ?? $patient->type
            ?? ''
        )));

        if (
            str_contains($patientType, 'dependent') ||
            str_contains($patientType, 'alumni') ||
            str_contains($patientType, 'guest')
        ) {
            return 'Dependent';
        }

        if ($patientType === 'faculty' || filled($patient->faculty_code)) {
            return 'Faculty';
        }

        if ($patientType === 'student' || filled($patient->student_no) || filled($patient->course_code) || filled($patient->course_name)) {
            return 'Student';
        }

        return 'Administrative';
    }
}
