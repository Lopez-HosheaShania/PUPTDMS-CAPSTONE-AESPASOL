<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\PatientOdontogram;
use App\Models\ServiceType;
use App\Models\Tooth;
use App\Models\ToothLegend;
use App\Models\ToothSurface;
use App\Models\DentalHistory;
use App\Models\DentalHistoryAnswer;
use App\Models\DentalHistoryCondition;
use App\Models\DentalHistoryConditionDate;
use App\Models\DentalHistoryConcern;
use App\Models\Disease;
use App\Models\MedicalHistory;
use App\Models\MedicalHistoryAnswer;
use App\Models\MedicalHistoryDiseaseAnswer;
use App\Models\MedicalHistoryQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Patient;
use Carbon\Carbon;
use App\Models\ClinicSchedule;
use App\Models\BlockedDate;
use App\Helpers\PhilippineHolidays;
use App\Helpers\BookingQuestions;

class OdontogramController extends Controller
{
    private function isAdminExistingAppointmentContext(): bool
    {
        return request()->routeIs('admin.odontogram.existing-appointment.*');
    }

    private function existingAppointmentRouteName(string $suffix): string
    {
        $prefix = $this->isAdminExistingAppointmentContext()
            ? 'admin.odontogram.existing-appointment.'
            : 'dentist.odontogram.existing-appointment.';

        return $prefix . $suffix;
    }

    private function existingAppointmentLayoutRole(): string
    {
        return $this->isAdminExistingAppointmentContext()
            ? 'admin'
            : 'dentist';
    }

    private function existingAppointmentBackUrl(Patient $patient): string
    {
        if ($this->isAdminExistingAppointmentContext()) {
            return route('admin.existing-record.index');
        }

        return route('dentist.dentist.patient.profile', ['patient' => $patient->id]);
    }

    private function savedVisitEditBackUrl(Patient $patient): string
    {
        return route('dentist.dentist.patient.profile', [
            'patient' => $patient->id,
            'from' => request()->query('from', 'appointments'),
        ]);
    }

    private function procedureEntrySource(?string $source, bool $existingAppointmentMode = false, bool $savedVisitEditMode = false): string
    {
        if ($existingAppointmentMode) {
            return 'existing-appointment';
        }

        if ($savedVisitEditMode) {
            return 'saved-visit';
        }

        $normalized = strtolower(trim((string) $source));

        return match ($normalized) {
            'walk-in', 'appointments', 'patient-profile' => $normalized,
            default => 'appointments',
        };
    }

    private function procedureDiscardReturnUrl(
        string $source,
        ?Appointment $appointment = null,
        ?Patient $patient = null,
        bool $existingAppointmentMode = false,
        bool $savedVisitEditMode = false
    ): string {
        if ($existingAppointmentMode) {
            return route($this->existingAppointmentRouteName('create'), ['patient' => $patient?->id]);
        }

        if ($savedVisitEditMode) {
            return $this->savedVisitEditBackUrl($patient ?? $appointment->patient);
        }

        return match ($source) {
            'walk-in' => route('dentist.walk-in.index'),
            'patient-profile' => route('dentist.dentist.patient.profile', [
                'patient' => $patient?->id ?? $appointment?->patient_id,
                'from' => 'patient-profile',
            ]),
            default => route('dentist.dentist.appointments'),
        };
    }

    private function getSavedOdontogramDataForPatient(Patient $patient): array
    {
        $patientOdontogram = PatientOdontogram::where('patient_id', $patient->id)->first();

        $latestProcedureWithOdontogram = $patientOdontogram
            ? null
            : AppointmentProcedure::whereHas('appointment', function ($query) use ($patient) {
                $query->where('patient_id', $patient->id);
            })
            ->whereHas('odontogram', fn ($query) => $query->where('has_data', true))
            ->latest('updated_at')
            ->latest('id')
            ->first();

        return $patientOdontogram?->odontogram_data
            ?? $latestProcedureWithOdontogram?->odontogram_data
            ?? [];
    }

    private function getProcedureWorkspaceContext(): array
    {
        $appointmentCountsPerDay = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $appointmentCountsPerSlot = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, appointment_time, COUNT(*) as count')
            ->groupBy('appointment_date', 'appointment_time')
            ->get()
            ->groupBy('appointment_date')
            ->map(function ($rows) {
                return $rows->pluck('count', 'appointment_time')->toArray();
            })
            ->toArray();

        $calendarAppointmentDetails = Appointment::with('patient')
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->get()
            ->groupBy(function ($appointment) {
                return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->map(function ($appointment) {
                    return [
                        'name' => $appointment->patient->name ?? 'Unknown',
                        'time' => Carbon::parse($appointment->appointment_time)->format('h:i A'),
                        'service' => $appointment->service_type_name,
                    ];
                })->toArray();
            })
            ->toArray();

        $schedules = ClinicSchedule::active()
            ->orderBy('id')
            ->get()
            ->map(function ($s) {
                $s->days = is_string($s->days) ? json_decode($s->days, true) : $s->days;
                return $s;
            });

        $blockedDates = BlockedDate::pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $philippineHolidays = PhilippineHolidays::recordsRange(
            yearsBefore: 15,
            yearsAfter: 15
        );

        return compact(
            'appointmentCountsPerDay',
            'appointmentCountsPerSlot',
            'calendarAppointmentDetails',
            'schedules',
            'blockedDates',
            'philippineHolidays'
        );
    }

    private function getExistingAppointmentCalendarContext(): array
    {
        $appointmentCountsPerDay = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $appointmentCountsPerSlot = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_date, appointment_time, COUNT(*) as count')
            ->groupBy('appointment_date', 'appointment_time')
            ->get()
            ->groupBy('appointment_date')
            ->map(function ($rows) {
                return $rows->pluck('count', 'appointment_time')->toArray();
            })
            ->toArray();

        $schedules = ClinicSchedule::active()->orderBy('id')->get()
            ->map(function ($schedule) {
                $schedule->days = is_string($schedule->days) ? json_decode($schedule->days, true) : $schedule->days;
                return $schedule;
            });

        $blockedDates = BlockedDate::pluck('date')
            ->map(fn($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $philippineHolidays =
            PhilippineHolidays::recordsRange(
                yearsBefore: 15,
                yearsAfter: 15
            );

        return compact(
            'appointmentCountsPerDay',
            'appointmentCountsPerSlot',
            'schedules',
            'blockedDates',
            'philippineHolidays'
        );
    }

    private function normalizeProcedureTime(?string $timeValue): string
    {
        $trimmed = trim((string) $timeValue);

        if ($trimmed === '') {
            return '00:00:00';
        }

        foreach (['H:i:s', 'H:i', 'g:i A'] as $format) {
            try {
                return Carbon::createFromFormat($format, $trimmed)->format('H:i:s');
            } catch (\Throwable $e) {
                continue;
            }
        }

        return Carbon::parse($trimmed)->format('H:i:s');
    }

    private function parseDurationToSeconds(?string $duration): int
    {
        $normalized = trim((string) $duration);

        if ($normalized === '') {
            return 0;
        }

        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $normalized)) {
            return 0;
        }

        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $normalized));

        if ($minutes > 59 || $seconds > 59) {
            return 0;
        }

        return max(0, ($hours * 3600) + ($minutes * 60) + $seconds);
    }

    private function procedureDurationSeconds(array $validated): int
    {
        $durationHms = trim((string) ($validated['procedure_duration_hms'] ?? ''));

        if ($durationHms !== '') {
            return $this->parseDurationToSeconds($durationHms);
        }

        return max(0, (int) ($validated['procedure_duration_seconds'] ?? 0));
    }

    private function existingAppointmentDraftSessionKey(Patient $patient): string
    {
        return 'existing_appointment_draft_patient_' . $patient->id;
    }

    private function yesNoValue($value): string
    {
        $normalized = strtoupper(trim((string) $value));
        return $normalized === 'YES' ? 'YES' : 'NO';
    }
    private function getPatientHistoryDefaults(Patient $patient): array
    {
        $patient->loadMissing([
            'dentalHistory',
            'dentalHistoryDates',
            'dentalHistoryConcerns',
            'dentalHistoryAnswers.condition',
            'medicalHistory.answers.question',
            'medicalHistory.diseaseAnswers.disease',
        ]);

        $dentalAnswers = $patient->dentalHistoryAnswers
            ->filter(fn($answer) => $answer->condition)
            ->mapWithKeys(fn($answer) => [
                $answer->condition->code => $answer->answer ? 'YES' : 'NO',
            ])
            ->all();

        $medicalAnswers = optional($patient->medicalHistory)->answers
            ? $patient->medicalHistory->answers->mapWithKeys(function ($answer) {
                $code = $answer->question->code ?? null;
                if (!$code) {
                    return [];
                }

                $value = $answer->answer_text;
                if (!is_null($answer->answer_bool)) {
                    $value = $answer->answer_bool ? 'YES' : 'NO';
                } elseif (!is_null($answer->answer_date)) {
                    $value = Carbon::parse($answer->answer_date)->toDateString();
                }

                return [$code => $value];
            })->all()
            : [];

        return [
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:00',
            'service_type' => '',
            'procedure_duration_hms' => '00:30:00',
            'last_dental_visit' => optional($patient->dentalHistory?->last_dental_visit)->toDateString(),
            'previous_dentist' => $patient->dentalHistory?->previous_dentist,
            'extraction_date' => optional($patient->dentalHistoryDates?->extraction_date)->toDateString(),
            'dentures_date' => optional($patient->dentalHistoryDates?->dentures_date)->toDateString(),
            'ortho_date' => optional($patient->dentalHistoryDates?->ortho_date)->toDateString(),
            'additional_concerns' => $patient->dentalHistoryConcerns?->additional_concerns,
            'emergency_person' => $patient->medicalHistory?->emergency_person,
            'emergency_number' => $patient->medicalHistory?->emergency_number,
            'emergency_relation' => $patient->medicalHistory?->emergency_relation,
            'dental_answers' => $dentalAnswers,
            'medical_answers' => $medicalAnswers,
            'diseases' => optional($patient->medicalHistory)->diseaseAnswers
                ? $patient->medicalHistory->diseaseAnswers->filter(fn($row) => $row->has_disease && $row->disease)
                ->pluck('disease.code')->values()->all()
                : [],
        ];
    }

    private function backfillStudentEmergencyContactIfNeeded(Patient $patient): void
    {
        $isStudent = filled($patient->student_no)
            || (filled($patient->email) && filled($patient->course_code))
            || strtolower(trim((string) $patient->classification)) === 'student';

        if (! $isStudent) {
            return;
        }

        $medicalHistory = $patient->medicalHistory;

        if (
            filled($medicalHistory?->emergency_person)
            && filled($medicalHistory?->emergency_number)
            && filled($medicalHistory?->emergency_relation)
        ) {
            return;
        }

        try {
            $studentService = app(\App\Services\StudentApiService::class);
            $studentProfile = [];

            if (filled($patient->email)) {
                $studentProfile = data_get(
                    $studentService->getStudentByEmail($patient->email),
                    'data',
                    []
                );
            }

            $studentNumber = $patient->student_no
                ?: data_get($studentProfile, 'studentNumber')
                ?: data_get($studentProfile, 'student_number');

            if (blank($studentNumber)) {
                return;
            }

            $personalInfo = data_get(
                $studentService->getPersonalInfoByStudentNumber($studentNumber),
                'data',
                []
            );

            if (! is_array($personalInfo) || $personalInfo === []) {
                return;
            }

            $emergencyPerson = $this->cleanEmergencyContactValue(
                $personalInfo['emergencyContactName']
                    ?? $personalInfo['emergency_contact_name']
                    ?? data_get($personalInfo, 'emergencyContact.name')
                    ?? data_get($personalInfo, 'emergency_contact.name')
                    ?? data_get($personalInfo, 'emergency_contact.contact_name')
                    ?? data_get($personalInfo, 'emergencyContact.contactName')
                    ?? null
            );

            $emergencyNumber = $this->normalizeEmergencyContactNumber(
                $this->cleanEmergencyContactValue(
                    $personalInfo['emergencyContactNumber']
                        ?? $personalInfo['emergency_contact_number']
                        ?? data_get($personalInfo, 'emergencyContact.number')
                        ?? data_get($personalInfo, 'emergency_contact.number')
                        ?? data_get($personalInfo, 'emergencyContact.contactNumber')
                        ?? data_get($personalInfo, 'emergency_contact.contact_number')
                        ?? null
                )
            );

            $emergencyRelation = $this->normalizeEmergencyContactRelation(
                $this->cleanEmergencyContactValue(
                    $personalInfo['emergencyContactRelationship']
                        ?? $personalInfo['emergency_contact_relationship']
                        ?? $personalInfo['emergencyContactRelation']
                        ?? $personalInfo['emergency_contact_relation']
                        ?? data_get($personalInfo, 'emergencyContact.relationship')
                        ?? data_get($personalInfo, 'emergency_contact.relationship')
                        ?? data_get($personalInfo, 'emergencyContact.relation')
                        ?? data_get($personalInfo, 'emergency_contact.relation')
                        ?? data_get($personalInfo, 'emergency_contact.relationship_name')
                        ?? data_get($personalInfo, 'emergencyContact.relationshipName')
                        ?? data_get($personalInfo, 'emergencyContactRelationship.name')
                        ?? data_get($personalInfo, 'emergency_contact_relationship.name')
                        ?? null
                )
            );

            if (! $emergencyPerson && ! $emergencyNumber && ! $emergencyRelation) {
                return;
            }

            $medicalHistory = MedicalHistory::firstOrNew([
                'patient_id' => $patient->id,
            ]);

            if ($emergencyPerson && blank($medicalHistory->emergency_person)) {
                $medicalHistory->emergency_person = $emergencyPerson;
            }

            if ($emergencyNumber && blank($medicalHistory->emergency_number)) {
                $medicalHistory->emergency_number = $emergencyNumber;
            }

            if ($emergencyRelation && blank($medicalHistory->emergency_relation)) {
                $medicalHistory->emergency_relation = $emergencyRelation;
            }

            if ($medicalHistory->isDirty()) {
                $medicalHistory->save();
            }

            $patient->unsetRelation('medicalHistory');
            $patient->load('medicalHistory');
        } catch (\Throwable $exception) {
            logger()->warning('Existing appointment emergency contact backfill failed.', [
                'patient_id' => $patient->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function cleanEmergencyContactValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeEmergencyContactNumber(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }

    private function normalizeEmergencyContactRelation(?string $value): ?string
    {
        return match (strtolower(trim((string) $value))) {
            'father', 'dad', 'papa', 'tatay' => 'Father',
            'mother', 'mom', 'mama', 'nanay' => 'Mother',
            'uncle', 'tiyo', 'tito' => 'Uncle',
            'aunt', 'auntie', 'tiya', 'tita' => 'Auntie',
            'brother', 'kuya' => 'Brother',
            'sister', 'ate' => 'Sister',
            'grandmother', 'lola' => 'Grandmother',
            'grandfather', 'lolo' => 'Grandfather',
            'cousin', 'pinsan' => 'Cousin',
            'guardian', 'legal guardian' => 'Legal Guardian',
            'friend', 'kaibigan' => 'Friend',
            'other relative', 'relative', 'kamag-anak', 'kamaganak' => 'Other Relative',
            default => null,
        };
    }

    private function validateExistingAppointmentIntake(Request $request): array
    {
        return $request->validate([
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|date_format:H:i',
            'service_type' => 'required|string|max:255',
            'procedure_duration_hms' => ['required', 'regex:/^\d{2}:[0-5]\d:[0-5]\d$/'],
            'last_dental_visit' => 'nullable|date|before_or_equal:today',
            'previous_dentist' => 'nullable|string|max:50',
            'extraction_date' => 'nullable|date|before_or_equal:today',
            'dentures_date' => 'nullable|date|before_or_equal:today',
            'ortho_date' => 'nullable|date|before_or_equal:today',
            'additional_concerns' => 'nullable|string|max:2000',
            'emergency_person' => ['required', 'string', 'max:50', 'regex:/^[A-Za-zÑñ\s.\'-]+$/u'],
            'emergency_number' => ['required', 'string', 'size:11', 'regex:/^09\d{9}$/'],
            'emergency_relation' => 'required|string|max:50',
            'diseases' => 'nullable|array',
            'diseases.*' => 'string|exists:diseases,code',
            'dental_answers' => 'required|array',
            'medical_answers' => 'required|array',
        ]);
    }

    private function persistExistingAppointmentPatientHistory(Patient $patient, array $draft): void
    {
        DentalHistory::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'last_dental_visit' => $draft['last_dental_visit'] ?: null,
                'previous_dentist' => $draft['previous_dentist'] ?: null,
            ]
        );

        DentalHistoryConditionDate::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'extraction_date' => $draft['extraction_date'] ?: null,
                'dentures_date' => $draft['dentures_date'] ?: null,
                'ortho_date' => $draft['ortho_date'] ?: null,
            ]
        );

        DentalHistoryConcern::updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'additional_concerns' => $draft['additional_concerns'] ?: null,
            ]
        );

        $conditionIdsByCode = DentalHistoryCondition::whereIn('code', array_keys($draft['dental_answers'] ?? []))
            ->pluck('id', 'code');

        foreach (($draft['dental_answers'] ?? []) as $code => $rawValue) {
            $conditionId = $conditionIdsByCode[$code] ?? null;
            if (!$conditionId) {
                continue;
            }

            DentalHistoryAnswer::updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'condition_id' => $conditionId,
                ],
                [
                    'answer' => $this->yesNoValue($rawValue) === 'YES',
                ]
            );
        }

        $medicalHistory =
            MedicalHistory::updateOrCreate(
                [
                    'patient_id' =>
                    $patient->id,
                ],
                [
                    'emergency_person' =>
                    $draft['emergency_person']
                        ?? null,

                    'emergency_number' =>
                    $draft['emergency_number']
                        ?? null,

                    'emergency_relation' =>
                    $draft['emergency_relation']
                        ?? null,
                ]
            );

        $questions = MedicalHistoryQuestion::whereIn('code', array_keys($draft['medical_answers'] ?? []))
            ->get()
            ->keyBy('code');

        foreach (($draft['medical_answers'] ?? []) as $code => $rawValue) {
            $question = $questions->get($code);
            if (!$question) {
                continue;
            }

            $criteria = [
                'patient_id' => $patient->id,
                'medical_history_id' => $medicalHistory->id,
                'question_id' => $question->id,
            ];

            if ($question->type === 'bool') {
                MedicalHistoryAnswer::updateOrCreate($criteria, [
                    'answer_bool' => $this->yesNoValue($rawValue) === 'YES',
                    'answer_text' => null,
                    'answer_date' => null,
                ]);
                continue;
            }

            if ($question->type === 'date') {
                $dateValue = trim((string) $rawValue);
                if ($dateValue === '') {
                    MedicalHistoryAnswer::where($criteria)->delete();
                } else {
                    MedicalHistoryAnswer::updateOrCreate($criteria, [
                        'answer_bool' => null,
                        'answer_text' => null,
                        'answer_date' => $dateValue,
                    ]);
                }
                continue;
            }

            $textValue = trim((string) $rawValue);
            if ($textValue === '') {
                MedicalHistoryAnswer::where($criteria)->delete();
            } else {
                MedicalHistoryAnswer::updateOrCreate($criteria, [
                    'answer_bool' => null,
                    'answer_text' => $textValue,
                    'answer_date' => null,
                ]);
            }
        }

        MedicalHistoryDiseaseAnswer::where('medical_history_id', $medicalHistory->id)->delete();

        $diseaseIds = Disease::whereIn('code', $draft['diseases'] ?? [])
            ->pluck('id')
            ->all();

        foreach ($diseaseIds as $diseaseId) {
            MedicalHistoryDiseaseAnswer::create([
                'patient_id' => $patient->id,
                'medical_history_id' => $medicalHistory->id,
                'disease_id' => $diseaseId,
                'has_disease' => true,
            ]);
        }
    }

    private function shouldUpdatePatientOdontogram(Patient $patient, Appointment $appointment): bool
    {
        $patientOdontogram = PatientOdontogram::where('patient_id', $patient->id)->first();
        $lastAppointmentId = $patientOdontogram?->last_appointment_id;

        if (!$lastAppointmentId) {
            return true;
        }

        $lastAppointment = Appointment::find($lastAppointmentId);

        if (!$lastAppointment) {
            return true;
        }

        $currentDateTime = Carbon::parse(
            trim((string) $appointment->appointment_date . ' ' . ($appointment->appointment_time ?: '00:00:00'))
        );

        $lastDateTime = Carbon::parse(
            trim((string) $lastAppointment->appointment_date . ' ' . ($lastAppointment->appointment_time ?: '00:00:00'))
        );

        if ($currentDateTime->greaterThan($lastDateTime)) {
            return true;
        }

        if ($currentDateTime->equalTo($lastDateTime)) {
            return (int) $appointment->id >= (int) $lastAppointment->id;
        }

        return false;
    }

    private function persistProcedureSnapshot(
        Appointment $appointment,
        array $validated,
        int $procedureDurationSeconds,
        ?Carbon $procedureCompletedAt = null
    ): array {
        $appointment->loadMissing('patient');
        $patient = $appointment->patient;
        $completionAction = $validated['completion_action'] ?? 'finished';
        $procedureCompletedAt = $procedureCompletedAt ?: now();
        $procedureStartedAt = $procedureDurationSeconds > 0
            ? $procedureCompletedAt->copy()->subSeconds($procedureDurationSeconds)
            : null;
        $rawOdontogramData = $validated['odontogram_data'] ?? [];

        $cleanOdontogramData = collect($rawOdontogramData)
            ->map(function ($entry) {
                $toothNumber = (int) data_get($entry, 'tooth', 0);

                if ($toothNumber <= 0) {
                    return null;
                }

                $cleanEntry = [
                    'tooth' => $toothNumber,
                    'toothName' => data_get($entry, 'toothName'),
                    'status' => null,
                    'surfaces' => [
                        'top' => null,
                        'left' => null,
                        'center' => null,
                        'right' => null,
                        'bottom' => null,
                    ],
                    'threeD' => null,
                ];

                $statusCode = trim((string) data_get($entry, 'status.code', ''));
                if ($statusCode !== '') {
                    $cleanEntry['status'] = [
                        'code' => $statusCode,
                        'label' => data_get($entry, 'status.label', $statusCode),
                        'colorHex' => data_get($entry, 'status.colorHex'),
                    ];
                }

                $threeDCode = trim((string) data_get($entry, 'threeD.code', ''));
                if ($threeDCode !== '') {
                    $cleanEntry['threeD'] = [
                        'code' => $threeDCode,
                        'label' => data_get($entry, 'threeD.label', $threeDCode),
                        'colorHex' => data_get($entry, 'threeD.colorHex'),
                    ];
                }

                foreach (['top', 'left', 'center', 'right', 'bottom'] as $surfaceKey) {
                    $surfaceData = data_get($entry, "surfaces.$surfaceKey");
                    $surfaceCode = trim((string) data_get($surfaceData, 'code', ''));

                    if ($surfaceCode !== '') {
                        $cleanEntry['surfaces'][$surfaceKey] = [
                            'code' => $surfaceCode,
                            'label' => data_get($surfaceData, 'label', $surfaceCode),
                            'colorHex' => data_get($surfaceData, 'colorHex'),
                        ];
                    }
                }

                $hasAppliedTreatment =
                    filled(data_get($cleanEntry, 'status.code')) ||
                    filled(data_get($cleanEntry, 'threeD.code')) ||
                    collect($cleanEntry['surfaces'])->contains(function ($surface) {
                        return filled(data_get($surface, 'code'));
                    });

                return $hasAppliedTreatment ? $cleanEntry : null;
            })
            ->filter()
            ->values()
            ->all();

        $allowsProcedureCompletionWithoutOdontogramChanges = $this->allowsProcedureCompletionWithoutOdontogramChanges(
            $appointment->service_type_name
        );

        $hasOdontogramChanges = count($cleanOdontogramData) > 0;
        if (
            !$allowsProcedureCompletionWithoutOdontogramChanges &&
            !$hasOdontogramChanges
        ) {
            throw new HttpResponseException(
                response()->json([
                    'message' =>
                    'Please apply at least one treatment to the tooth chart before finishing the procedure.',
                ], 422)
            );
        }

        $legendCache = [];

        $resolveLegendId = function (?string $code, ?string $label = null) use (&$legendCache) {
            if (!$code) {
                return null;
            }

            $normalizedCode = strtoupper(trim($code));

            if ($normalizedCode === '') {
                return null;
            }

            if (isset($legendCache[$normalizedCode])) {
                return $legendCache[$normalizedCode];
            }

            $legend = ToothLegend::whereRaw('UPPER(code) = ?', [$normalizedCode])->first();

            if (!$legend) {
                $legend = ToothLegend::create([
                    'code' => $normalizedCode,
                    'description' => $label ?: $normalizedCode,
                    'category' => 'Odontogram',
                ]);
            }

            $legendCache[$normalizedCode] = $legend->id;

            return $legend->id;
        };

        $surfaceMap = [
            'top' => 1,
            'right' => 2,
            'bottom' => 3,
            'left' => 4,
            'center' => 5,
        ];

        $savedTeeth = 0;
        $shouldUpdatePatientOdontogram = $hasOdontogramChanges &&
            $this->shouldUpdatePatientOdontogram(
                $patient,
                $appointment
            );

        DB::transaction(function () use (
            $appointment,
            $patient,
            $validated,
            $cleanOdontogramData,
            $completionAction,
            $procedureStartedAt,
            $procedureCompletedAt,
            $procedureDurationSeconds,
            $resolveLegendId,
            $surfaceMap,
            $shouldUpdatePatientOdontogram,
            $hasOdontogramChanges,
            &$savedTeeth
        ) {

            if ($hasOdontogramChanges) {
                $submittedToothNumbers = collect($cleanOdontogramData)
                    ->pluck('tooth')
                    ->map(fn($toothNumber) => (int) $toothNumber)
                    ->all();

                Tooth::where('patient_id', $patient->id)
                    ->whereNotIn('tooth_number', $submittedToothNumbers)
                    ->delete();

                foreach ($cleanOdontogramData as $entry) {
                    $toothNumber = (int) $entry['tooth'];

                    $tooth = Tooth::firstOrCreate([
                        'patient_id' => $patient->id,
                        'tooth_number' => $toothNumber,
                    ]);

                    $savedTeeth++;

                    $toothLegendCode =
                        data_get($entry, 'threeD.code')
                        ?: data_get($entry, 'status.code');

                    $toothLegendLabel =
                        data_get($entry, 'threeD.label')
                        ?: data_get($entry, 'status.label');

                    $toothLegendId =
                        $resolveLegendId(
                            $toothLegendCode,
                            $toothLegendLabel
                        );

                    $tooth->legends()->sync(
                        $toothLegendId
                            ? [$toothLegendId]
                            : []
                    );

                    foreach ($surfaceMap as $surfaceKey => $surfaceNumber) {

                        $surface = ToothSurface::firstOrCreate([
                            'tooth_id' => $tooth->id,
                            'surface_number' => $surfaceNumber,
                        ]);

                        $surfaceCode =
                            data_get(
                                $entry,
                                "surfaces.$surfaceKey.code"
                            );

                        $surfaceLabel =
                            data_get(
                                $entry,
                                "surfaces.$surfaceKey.label"
                            );

                        $surfaceLegendId =
                            $resolveLegendId(
                                $surfaceCode,
                                $surfaceLabel
                            );

                        $surface->legends()->sync(
                            $surfaceLegendId
                                ? [$surfaceLegendId]
                                : []
                        );
                    }
                }
            }

            if ($shouldUpdatePatientOdontogram) {
                PatientOdontogram::updateOrCreate(
                    [
                        'patient_id' => $patient->id,
                    ],
                    [
                        'odontogram_data' => $cleanOdontogramData,
                        'last_appointment_id' => $appointment->id,
                        'last_updated_by' => auth()->id(),
                    ]
                );
            }

            $procedurePayload = [
                'odontogram_data' => $cleanOdontogramData,
                'oral_examination' => $validated['oral_examination'] ?? null,
                'diagnosis' => $validated['diagnosis'] ?? null,
                'prescriptions' => $validated['prescriptions'] ?? null,
                'completion_action' => $completionAction,
                'procedure_started_at' => $procedureStartedAt,
                'procedure_completed_at' => $procedureCompletedAt,
                'procedure_duration_seconds' => max(0, $procedureDurationSeconds),
            ];

            AppointmentProcedure::updateOrCreate(
                [
                    'appointment_id' => $appointment->id,
                ],
                $procedurePayload
            );

            $appointmentUpdate = [
                'status' => 'completed',
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('appointments', 'completed_at')) {
                $appointmentUpdate['completed_at'] = $procedureCompletedAt;
            }

            DB::table('appointments')
                ->where('id', $appointment->id)
                ->update($appointmentUpdate);
        });

        $appointment->refresh();

        return [
            'appointment' => $appointment,
            'saved_teeth' => $savedTeeth,
            'patient_odontogram_updated' => $shouldUpdatePatientOdontogram,
        ];
    }

    public function startForPatient(Patient $patient)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $appointment = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->first();

        if (!$appointment) {
            return redirect()
                ->route('dentist.dentist.patient.profile', ['patient' => $patient->id])
                ->with('error', 'No upcoming appointment found for this patient.');
        }

        return redirect()->route('dentist.odontogram', [
            'appointment' => $appointment->id,
        ]);
    }
    public function show(Request $request, Appointment $appointment)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $appointment->load(['patient', 'reservedBookingPeriod']);

        if (!$appointment->patient) {
            abort(404, 'Patient not found for this appointment.');
        }

        if (
            $request->boolean('start_procedure')
            && $appointment->reserved_booking_period_id
            && ! $appointment->reservedProcedureWindowIsOpen()
        ) {
            return redirect()
                ->route('dentist.dentist.appointments')
                ->with('error', 'This reserved appointment can only be started during its reserved period.');
        }

        $patient = $appointment->patient;
        $entrySource = $this->procedureEntrySource($request->query('from'));

        $procedure = AppointmentProcedure::where('appointment_id', $appointment->id)->first();
        $savedOdontogramData = $this->getSavedOdontogramDataForPatient($patient);

        return view('dentist.dentist-odontogram', array_merge(compact(
            'patient',
            'appointment',
            'procedure',
            'savedOdontogramData',
        ), $this->getProcedureWorkspaceContext(), [
            'existingAppointmentMode' => false,
            'discardProcedureContext' => $entrySource,
            'discardProcedureReturnUrl' => $this->procedureDiscardReturnUrl($entrySource, $appointment, $patient),
            'discardProcedureUrl' => route('dentist.odontogram.discard'),
            'serviceTypes' => ServiceType::activeForBooking()->orderBy('name')->get(['name']),
            'saveProcedureUrl' => route('dentist.odontogram.save', $appointment->id),
        ]));
    }

    public function createExistingAppointment(Patient $patient)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        session()->forget($this->existingAppointmentDraftSessionKey($patient));

        $patient->loadMissing(
            'medicalHistory'
        );

        $this->backfillStudentEmergencyContactIfNeeded($patient);

        $hasReusableSignature =
            !empty($patient
                ->medicalHistory
                ?->patient_signature) &&
            $patient
            ->medicalHistory
            ?->signature_review_status !==
            'invalid_reupload_required';

        $existingSignatureUrl = null;

        if (
            $hasReusableSignature &&
            filled(
                $patient
                    ->medicalHistory
                    ?->patient_signature
            )
        ) {
            $signaturePath = ltrim(
                str_replace(
                    'storage/',
                    '',
                    $patient
                        ->medicalHistory
                        ->patient_signature
                ),
                '/'
            );

            if (
                Storage::disk('public')
                ->exists($signaturePath)
            ) {
                $existingSignatureUrl =
                    Storage::disk('public')
                    ->url($signaturePath);
            }
        }

        $defaults = $this->getPatientHistoryDefaults($patient);

        $defaults['appointment_date'] = '';
        $defaults['appointment_time'] = '';
        $defaults['service_type'] = '';
        $defaults['procedure_duration_hms'] = '';

        return view(
            'dentist.add-existing-appointment',
            [
                'patient' => $patient,
                'layoutRole' => $this->existingAppointmentLayoutRole(),
                'backUrl' => $this->existingAppointmentBackUrl($patient),
                'storeIntakeUrl' => route($this->existingAppointmentRouteName('intake.store'), ['patient' => $patient->id]),
                'historyAutosaveUrl' => route($this->existingAppointmentRouteName('history.autosave'), ['patient' => $patient->id]),
                'slotEndpoint' => route($this->existingAppointmentRouteName('slots')),

                'serviceTypes' =>
                ServiceType::activeForBooking()
                    ->orderBy('name')
                    ->get(),

                'diseases' =>
                Disease::orderBy('sort_order')
                    ->get(),

                'defaults' => $defaults,

                'dentalQuestions' =>
                BookingQuestions::dental(),

                'medicalQuestions' =>
                BookingQuestions::medical(),

                'hasReusableSignature' =>
                $hasReusableSignature,

                'existingSignatureUrl' =>
                $existingSignatureUrl,

            ] + $this->getExistingAppointmentCalendarContext()
        );
    }

    public function storeExistingAppointmentIntake(Request $request, Patient $patient)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return redirect('/login');
        }

        $rawAppointmentTime =
            trim(
                (string)
                $request->input(
                    'appointment_time',
                    ''
                )
            );

        if ($rawAppointmentTime !== '') {
            try {
                $normalizedAppointmentTime =
                    $this->normalizeProcedureTime(
                        $rawAppointmentTime
                    );

                $request->merge([
                    'appointment_time' =>
                    substr(
                        $normalizedAppointmentTime,
                        0,
                        5
                    ),
                ]);
            } catch (\Throwable $error) {
            }
        }

        $validated = $this->validateExistingAppointmentIntake($request);

        $serviceExists = ServiceType::where('name', $validated['service_type'])
            ->where('is_active_for_booking', true)
            ->exists();

        if (!$serviceExists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' =>
                    'Please select a valid service type.',

                    'errors' => [
                        'service_type' => [
                            'Please select a valid service type.',
                        ],
                    ],
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'service_type' =>
                    'Please select a valid service type.',
                ]);
        }

        $validated['dental_answers'] = collect($validated['dental_answers'] ?? [])
            ->map(fn($value) => $this->yesNoValue($value))
            ->all();

        $validated['medical_answers'] = collect($validated['medical_answers'] ?? [])
            ->map(
                function ($value, $code) {
                    if (
                        in_array(
                            $code,
                            BookingQuestions::medicalCodesByType(
                                'bool'
                            ),
                            true
                        )
                    ) {
                        return $this
                            ->yesNoValue(
                                $value
                            );
                    }

                    return is_string(
                        $value
                    )
                        ? trim($value)
                        : $value;
                }
            )
            ->all();

        $appointmentDate =
            Carbon::parse(
                $validated['appointment_date']
            );

        if (
            $appointmentDate->isSaturday() ||
            $appointmentDate->isSunday()
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' =>
                    'The clinic is closed on Saturdays and Sundays.',

                    'errors' => [
                        'appointment_date' => [
                            'The clinic is closed on Saturdays and Sundays.',
                        ],
                    ],
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'appointment_date' =>
                    'The clinic is closed on Saturdays and Sundays.',
                ]);
        }

        $appointmentDateIso =
            $appointmentDate
            ->toDateString();

        if (
            PhilippineHolidays::isBlockedForBooking(
                $appointmentDateIso
            )
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' =>
                    'The clinic is closed on this Philippine holiday.',

                    'errors' => [
                        'appointment_date' => [
                            'The clinic is closed on this Philippine holiday.',
                        ],
                    ],
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'appointment_date' =>
                    'The clinic is closed on this Philippine holiday.',
                ]);
        }

        session([
            $this->existingAppointmentDraftSessionKey($patient) => $validated,
        ]);

        $odontogramUrl =
            route(
                $this->existingAppointmentRouteName('odontogram'),
                [
                    'patient' =>
                    $patient->id,
                ]
            );

        if (
            $request->expectsJson()
        ) {
            return response()->json([
                'success' => true,

                'message' =>
                'The existing appointment details were recorded successfully.',

                'odontogram_url' =>
                $odontogramUrl,

                'appointment' => [
                    'patient' =>
                    $patient->name,

                    'service' =>
                    $validated['service_type'],

                    'date' =>
                    Carbon::parse(
                        $validated['appointment_date']
                    )->format(
                        'F j, Y'
                    ),

                    'time' =>
                    Carbon::createFromFormat(
                        'H:i',
                        $validated['appointment_time']
                    )->format(
                        'g:i A'
                    ),

                    'duration' =>
                    $validated['procedure_duration_hms'],
                ],
            ]);
        }

        return redirect()
            ->to(
                $odontogramUrl
            );
    }

    public function autosaveExistingAppointmentHistory(
        Request $request,
        Patient $patient
    ) {
        $activeRole =
            session('impersonated_role')
            ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'last_dental_visit' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'previous_dentist' => [
                'nullable',
                'string',
                'max:50',
            ],

            'extraction_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'dentures_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'ortho_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'additional_concerns' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'emergency_person' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-zÑñ\s.\'-]+$/u',
            ],

            'emergency_number' => [
                'nullable',
                'string',
                'max:15',
            ],

            'emergency_relation' => [
                'nullable',
                'string',
                'max:50',
            ],

            'dental_answers' => [
                'nullable',
                'array',
            ],

            'medical_answers' => [
                'nullable',
                'array',
            ],

            'diseases' => [
                'nullable',
                'array',
            ],

            'diseases_present' => [
                'nullable',
                'boolean',
            ],

            'diseases.*' => [
                'string',
                'exists:diseases,code',
            ],
        ]);

        $current =
            $this->getPatientHistoryDefaults(
                $patient
            );

        $draft = array_merge(
            $current,
            $validated
        );

        $draft['dental_answers'] =
            array_merge(
                $current['dental_answers'] ?? [],
                $validated['dental_answers'] ?? []
            );

        $draft['medical_answers'] =
            array_merge(
                $current['medical_answers'] ?? [],
                $validated['medical_answers'] ?? []
            );

        if (
            array_key_exists(
                'diseases_present',
                $validated
            )
        ) {
            $draft['diseases'] =
                $validated['diseases']
                ?? [];
        } else {
            $draft['diseases'] =
                $current['diseases']
                ?? [];
        }

        $draft['dental_answers'] =
            collect(
                $draft['dental_answers'] ?? []
            )
            ->map(
                fn($value) =>
                $this->yesNoValue($value)
            )
            ->all();

        $draft['medical_answers'] =
            collect(
                $draft['medical_answers'] ?? []
            )
            ->map(
                function (
                    $value,
                    $code
                ) {
                    if (
                        in_array(
                            $code,
                            BookingQuestions::medicalCodesByType(
                                'bool'
                            ),
                            true
                        )
                    ) {
                        return $this
                            ->yesNoValue(
                                $value
                            );
                    }

                    return is_string($value)
                        ? trim($value)
                        : $value;
                }
            )
            ->all();

        DB::transaction(function () use (
            $patient,
            $draft
        ) {
            $this
                ->persistExistingAppointmentPatientHistory(
                    $patient,
                    $draft
                );
        });

        return response()->json([
            'success' => true,
            'message' => 'Patient history saved.',
        ]);
    }

    public function showExistingAppointmentOdontogram(Request $request, Patient $patient)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (
            !optional(Auth::user())
                ->canAccessClinicalArea(
                    $activeRole
                )
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' =>
                    'Your session is no longer authorized.',
                ], 403);
            }

            return redirect('/login');
        }

        $draft = session($this->existingAppointmentDraftSessionKey($patient));

        if (!$draft) {
            return redirect()
                ->route($this->existingAppointmentRouteName('create'), ['patient' => $patient->id])
                ->with('error', 'Please complete the existing appointment details first.');
        }

        $patient->loadMissing('user');

        return view('dentist.dentist-odontogram', [
            'patient' => $patient,
            'appointment' => null,
            'procedure' => null,
            'layoutRole' => $this->existingAppointmentLayoutRole(),
            'cancelProcedureRedirectUrl' => $this->existingAppointmentBackUrl($patient),
            'discardProcedureContext' => $this->procedureEntrySource(null, true),
            'discardProcedureReturnUrl' => $this->procedureDiscardReturnUrl(
                $this->procedureEntrySource(null, true),
                null,
                $patient,
                true
            ),
            'discardProcedureUrl' => route('dentist.odontogram.discard'),
            'savedOdontogramData' => $this->getSavedOdontogramDataForPatient($patient),
            'existingAppointmentMode' => true,
            'existingAppointmentDraft' => $draft,
            'isExistingAppointment' => true,
            'saveProcedureUrl' => route($this->existingAppointmentRouteName('store'), $patient->id),
            'appointmentCountsPerDay' => [],
            'appointmentCountsPerSlot' => [],
            'calendarAppointmentDetails' => [],
            'schedules' => collect(),
            'blockedDates' => [],
            'philippineHolidays' => [],
        ]);
    }

    public function existingAppointmentSlotsForDate(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json([
                'slots' => [],
                'message' => 'Unauthorized.',
            ], 403);
        }

        $request->validate([
            'date' => 'required|date',
        ]);

        $iso = $request->date;
        $date = Carbon::parse($iso);
        $dayAbbr = $date->format('D');

        if (PhilippineHolidays::isBlockedForBooking($iso)) {
            return response()->json([
                'slots' => [],
                'message' =>
                'The clinic is closed on this Philippine holiday.',
            ]);
        }

        $schedule = ClinicSchedule::active()
            ->get()
            ->first(fn($rule) => in_array($dayAbbr, $rule->days ?? []));

        if (! $schedule || $schedule->status === 'closed') {
            $schedule = ClinicSchedule::active()
                ->where('status', '!=', 'closed')
                ->orderBy('id')
                ->first();
        }

        if (! $schedule) {
            return response()->json([
                'slots' => collect(range(8, 16))->map(function ($hour) {
                    $mysqlTime = sprintf('%02d:00:00', $hour);
                    return [
                        'time' => date('g:i A', strtotime($mysqlTime)),
                        'mysql_time' => $mysqlTime,
                        'available' => true,
                    ];
                })->values()->all(),
                'max_slots' => 9,
                'booked' => 0,
                'remaining' => 9,
                'open_time' => '08:00:00',
                'close_time' => '17:00:00',
                'break_time' => 'none',
                'status' => 'open',
            ]);
        }

        return response()->json([
            'slots' => $schedule->availableSlots($iso, []),
            'max_slots' => $schedule->max_slots,
            'booked' => 0,
            'remaining' => $schedule->max_slots,
            'open_time' => $schedule->open_time,
            'close_time' => $schedule->close_time,
            'break_time' => $schedule->break_time,
            'status' => $schedule->status,
        ]);
    }

    public function save(Request $request, Appointment $appointment)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $appointment->load('patient.user');
        $patient = $appointment->patient;

        if (!$appointment->patient) {
            return response()->json([
                'message' => 'Patient not found for this appointment.',
            ], 404);
        }

        $validated = $request->validate([
            'odontogram_data' => 'present|array',
            'odontogram_data.*.tooth' => 'nullable|integer|min:1',

            'odontogram_data.*.status.code' => 'nullable|string|max:20',
            'odontogram_data.*.status.label' => 'nullable|string|max:255',
            'odontogram_data.*.status.colorHex' => 'nullable|string|max:30',

            'odontogram_data.*.threeD.code' => 'nullable|string|max:20',
            'odontogram_data.*.threeD.label' => 'nullable|string|max:255',
            'odontogram_data.*.threeD.colorHex' => 'nullable|string|max:30',

            'odontogram_data.*.surfaces' => 'nullable|array',
            'odontogram_data.*.surfaces.*.code' => 'nullable|string|max:20',
            'odontogram_data.*.surfaces.*.label' => 'nullable|string|max:255',
            'odontogram_data.*.surfaces.*.colorHex' => 'nullable|string|max:30',

            'oral_examination' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'prescriptions' => 'nullable|string',
            'completion_action' => 'nullable|in:finished,follow_up',
            'has_applied_treatment' => 'required|boolean',
            'procedure_duration_hms' => ['required', 'regex:/^\d{2}:[0-5]\d:[0-5]\d$/'],
            'procedure_duration_seconds' => 'nullable|integer|min:0',
        ]);

        $allowsProcedureCompletionWithoutOdontogramChanges = $this->allowsProcedureCompletionWithoutOdontogramChanges(
            $appointment->service_type_name
        );

        if (
            !$allowsProcedureCompletionWithoutOdontogramChanges &&
            !$request->boolean('has_applied_treatment')
        ) {
            return response()->json([
                'message' =>
                'Please apply at least one treatment to the tooth chart before finishing the procedure.',
            ], 422);
        }

        $result = $this->persistProcedureSnapshot(
            $appointment,
            $validated,
            $this->procedureDurationSeconds($validated)
        );
        $appointment = $result['appointment'];
        $completionAction = $validated['completion_action'] ?? 'finished';

        if ($appointment->status !== 'completed') {
            return response()->json([
                'message' => 'Procedure was saved, but appointment status was not updated to completed.',
                'current_status' => $appointment->status,
            ], 500);
        }

        return response()->json([
            'message' => $completionAction === 'follow_up'
                ? 'Procedure completed. You may now create a follow-up appointment.'
                : 'Procedure completed successfully.',
            'saved_teeth' => $result['saved_teeth'],
            'status' => 'completed',
            'redirect_url' => route('dentist.dentist.appointments') . '?refresh=' . now()->timestamp,
        ]);
    }

    public function editSavedVisit(Request $request, Appointment $appointment)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized.',
                ], 403);
            }

            return redirect('/login');
        }

        $appointment->load([
            'patient.user',
            'procedure',
        ]);

        $patient = $appointment->patient;
        $procedure = $appointment->procedure;

        if (!$patient) {
            abort(404, 'Patient not found for this appointment.');
        }

        return view('dentist.dentist-odontogram', array_merge(
            [
                'patient' => $patient,
                'appointment' => $appointment,
                'procedure' => $procedure,
                'layoutRole' => 'dentist',
                'cancelProcedureRedirectUrl' => $this->savedVisitEditBackUrl($patient),
                'discardProcedureContext' => $this->procedureEntrySource($request->query('from'), false, true),
                'discardProcedureReturnUrl' => $this->procedureDiscardReturnUrl(
                    $this->procedureEntrySource($request->query('from'), false, true),
                    $appointment,
                    $patient,
                    false,
                    true
                ),
                'discardProcedureUrl' => route('dentist.odontogram.discard'),
                // A completed visit may not have an odontogram record yet; start its editor blank.
                'savedOdontogramData' => $procedure?->odontogram_data ?? [],
                'existingAppointmentMode' => false,
                'savedVisitEditMode' => true,
                'saveProcedureUrl' => route('dentist.odontogram.saved.update', [
                    'appointment' => $appointment->id,
                    'from' => $request->query('from', 'appointments'),
                ]),
                'serviceTypes' => ServiceType::activeForBooking()->orderBy('name')->get(['name']),
            ],
            $this->getProcedureWorkspaceContext()
        ));
    }

    public function updateSavedVisit(Request $request, Appointment $appointment)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $appointment->load([
            'patient.user',
            'procedure',
        ]);

        $patient = $appointment->patient;

        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found for this appointment.',
            ], 404);
        }

        $validated = $request->validate([
            'odontogram_data' => 'present|array',
            'odontogram_data.*.tooth' => 'nullable|integer|min:1',
            'odontogram_data.*.status.code' => 'nullable|string|max:20',
            'odontogram_data.*.status.label' => 'nullable|string|max:255',
            'odontogram_data.*.status.colorHex' => 'nullable|string|max:30',
            'odontogram_data.*.threeD.code' => 'nullable|string|max:20',
            'odontogram_data.*.threeD.label' => 'nullable|string|max:255',
            'odontogram_data.*.threeD.colorHex' => 'nullable|string|max:30',
            'odontogram_data.*.surfaces' => 'nullable|array',
            'odontogram_data.*.surfaces.*.code' => 'nullable|string|max:20',
            'odontogram_data.*.surfaces.*.label' => 'nullable|string|max:255',
            'odontogram_data.*.surfaces.*.colorHex' => 'nullable|string|max:30',
            'oral_examination' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'prescriptions' => 'nullable|string',
            'completion_action' => 'nullable|in:finished',
            'has_applied_treatment' => 'required|boolean',
            'procedure_duration_hms' => ['required', 'regex:/^\d{2}:\d{2}:\d{2}$/'],
            'procedure_duration_seconds' => 'nullable|integer|min:0',
        ]);

        $allowsProcedureCompletionWithoutOdontogramChanges = $this->allowsProcedureCompletionWithoutOdontogramChanges(
            $appointment->service_type_name
        );

        if (
            !$allowsProcedureCompletionWithoutOdontogramChanges &&
            !$request->boolean('has_applied_treatment')
        ) {
            return response()->json([
                'message' => 'Please apply at least one treatment to the tooth chart before saving the odontogram.',
            ], 422);
        }

        $procedureDurationSeconds = $this->procedureDurationSeconds($validated);

        $result = $this->persistProcedureSnapshot(
            $appointment,
            array_merge($validated, [
                'completion_action' => 'finished',
            ]),
            max(0, $procedureDurationSeconds)
        );

        return response()->json([
            'message' => 'Saved visit odontogram updated successfully.',
            'saved_teeth' => $result['saved_teeth'],
            'status' => 'completed',
            'redirect_url' => $this->savedVisitEditBackUrl($patient) . '&refresh=' . now()->timestamp,
        ]);
    }

    public function discard(Request $request)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'context' => 'required|string|in:walk-in,appointments,patient-profile,existing-appointment,saved-visit',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'patient_id' => 'nullable|integer|exists:patients,id',
        ]);

        $context = $validated['context'];
        $appointment = null;
        $patient = null;

        if (!empty($validated['appointment_id'])) {
            $appointment = Appointment::with(['patient', 'procedure'])->findOrFail($validated['appointment_id']);
            $patient = $appointment->patient;
        }

        if (!empty($validated['patient_id'])) {
            $patient = $patient ?: Patient::findOrFail($validated['patient_id']);
        }

        if ($context === 'existing-appointment') {
            abort_unless($patient, 404, 'Patient not found.');

            session()->forget($this->existingAppointmentDraftSessionKey($patient));

            return response()->json([
                'redirect_url' => $this->procedureDiscardReturnUrl($context, null, $patient, true),
            ]);
        }

        abort_unless($appointment && $patient, 404, 'Appointment not found.');

        if ($context === 'walk-in') {
            abort_if(!($appointment->is_walk_in ?? false), 422, 'Only temporary walk-in procedures can be discarded from this flow.');

            if ($appointment->procedure()->exists() || $appointment->status === 'completed') {
                return response()->json([
                    'redirect_url' => $this->procedureDiscardReturnUrl($context, $appointment, $patient),
                ]);
            }

            DB::transaction(function () use ($appointment) {
                $appointment->delete();
            });
        }

        return response()->json([
            'redirect_url' => $this->procedureDiscardReturnUrl(
                $context,
                $appointment,
                $patient,
                false,
                $context === 'saved-visit'
            ),
        ]);
    }

    public function storeExistingAppointment(Request $request, Patient $patient)
    {
        $activeRole = session('impersonated_role') ?: session('role');

        if (!optional(Auth::user())->canAccessClinicalArea($activeRole)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $draft = session($this->existingAppointmentDraftSessionKey($patient));

        if (!$draft) {
            return response()->json([
                'message' => 'Existing appointment draft not found. Please complete the intake form first.',
            ], 422);
        }

        $validated = $request->validate([
            'odontogram_data' => 'present|array',
            'odontogram_data.*.tooth' => 'nullable|integer|min:1',
            'odontogram_data.*.status.code' => 'nullable|string|max:20',
            'odontogram_data.*.status.label' => 'nullable|string|max:255',
            'odontogram_data.*.status.colorHex' => 'nullable|string|max:30',
            'odontogram_data.*.threeD.code' => 'nullable|string|max:20',
            'odontogram_data.*.threeD.label' => 'nullable|string|max:255',
            'odontogram_data.*.threeD.colorHex' => 'nullable|string|max:30',
            'odontogram_data.*.surfaces' => 'nullable|array',
            'odontogram_data.*.surfaces.*.code' => 'nullable|string|max:20',
            'odontogram_data.*.surfaces.*.label' => 'nullable|string|max:255',
            'odontogram_data.*.surfaces.*.colorHex' => 'nullable|string|max:30',
            'oral_examination' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'prescriptions' => 'nullable|string',
            'completion_action' => 'nullable|in:finished',
            'has_applied_treatment' => 'required|boolean',
        ]);

        $allowsProcedureCompletionWithoutOdontogramChanges = $this->allowsProcedureCompletionWithoutOdontogramChanges(
            $draft['service_type'] ?? ''
        );

        if (
            !$allowsProcedureCompletionWithoutOdontogramChanges && !$request->boolean(
                'has_applied_treatment'
            )
        ) {
            return response()->json([
                'message' =>
                'Please apply at least one treatment to the tooth chart before saving this appointment.',
            ], 422);
        }

        $appointmentDate = Carbon::parse($draft['appointment_date'])->toDateString();
        $appointmentTime = $this->normalizeProcedureTime($draft['appointment_time']);
        $procedureDurationSeconds = $this->parseDurationToSeconds($draft['procedure_duration_hms'] ?? '00:00:00');
        $procedureStartedAt =
            Carbon::parse(
                trim(
                    $appointmentDate . ' ' .
                        $appointmentTime
                )
            );

        $procedureCompletedAt =
            $procedureStartedAt
            ->copy()
            ->addSeconds(
                $procedureDurationSeconds
            );

        $serviceType = ServiceType::where(
            'name',
            $draft['service_type']
        )->firstOrFail();

        $appointmentPayload = [
            'patient_id' => $patient->id,
            'dentist_id' => auth()->id(),
            'service_type_id' => $serviceType->id,
            'service_type' => $serviceType->name,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => 'completed',
        ];

        if (Schema::hasColumn('appointments', 'completed_at')) {
            $appointmentPayload['completed_at'] = $procedureCompletedAt;
        }

        $result = null;

        DB::transaction(function () use (
            $patient,
            $draft,
            $appointmentPayload,
            $validated,
            $procedureDurationSeconds,
            $procedureCompletedAt,
            &$result
        ) {
            $this->persistExistingAppointmentPatientHistory($patient, $draft);

            $appointment = Appointment::create($appointmentPayload);

            $result = $this->persistProcedureSnapshot(
                $appointment,
                array_merge($validated, [
                    'completion_action' => 'finished',
                ]),
                $procedureDurationSeconds,
                $procedureCompletedAt
            );
        });

        session()->forget($this->existingAppointmentDraftSessionKey($patient));

        return response()->json([
            'message' => 'Existing appointment saved successfully.',
            'saved_teeth' => $result['saved_teeth'],
            'status' => 'completed',
            'redirect_url' => $this->existingAppointmentBackUrl($patient) . '?refresh=' . now()->timestamp,
        ]);
    }

    private function allowsProcedureCompletionWithoutOdontogramChanges(?string $serviceType): bool
    {
        $normalizedServiceType = trim((string) $serviceType);

        return strcasecmp($normalizedServiceType, 'Oral Prophylaxis') === 0 ||
            strcasecmp($normalizedServiceType, 'Oral Check-Up') === 0;
    }
}
