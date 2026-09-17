<?php

namespace App\Http\Controllers\Dentist;

use App\Helpers\PhilippineHolidays;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Models\Disease;
use App\Models\MedicalHistory;
use App\Models\DentalHistory;
use App\Models\DentalHistoryCondition;
use App\Models\DentalHistoryAnswer;
use App\Models\DentalHistoryConditionDate;
use App\Models\DentalHistoryConcern;

use App\Models\MedicalHistoryQuestion;
use App\Models\MedicalHistoryAnswer;
use App\Models\MedicalHistoryDiseaseAnswer;
use App\Models\Patient;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\FacultyApiService;
use App\Services\StudentApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Helpers\BookingQuestions;

class WalkInController extends Controller
{
    public function index()
    {
        $serviceTypes = ServiceType::query()
            ->orderBy('name')
            ->get()
            ->map(function ($service) {
                $image = trim((string) ($service->image ?? ''));

                return [
                    'name' => $service->name,
                    'desc' => $service->description ?? 'Dental service available for walk-in appointment.',
                    'img' => $image !== '' ? $image : null,
                ];
            });

        $diseases = Disease::query()
            ->orderBy('sort_order')
            ->get();

        $schedules = ClinicSchedule::active()
            ->orderBy('id')
            ->get();

        $blockedDates = BlockedDate::orderBy('date')
            ->get();

        $startDate = now()->startOfMonth()->subMonth();
        $endDate = now()->endOfMonth()->addMonths(3);

        $appointmentCountsPerDay = Appointment::whereBetween('appointment_date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('appointment_date, COUNT(*) as total')
            ->groupBy('appointment_date')
            ->pluck('total', 'appointment_date');

        $philippineHolidays = PhilippineHolidays::recordsRange(yearsBefore: 1, yearsAfter: 5);

        $dentalQuestions = BookingQuestions::dental();
        $medicalQuestions = BookingQuestions::medical();

        return view(
            'dentist.dentist-walk-in',
            compact(
                'serviceTypes',
                'diseases',
                'schedules',
                'blockedDates',
                'appointmentCountsPerDay',
                'philippineHolidays',
                'dentalQuestions',
                'medicalQuestions'
            )
        );
    }

    public function searchPatient(
        Request $request,
        FacultyApiService $facultyApiService,
        StudentApiService $studentApiService
    ) {
        $search = trim((string) $request->query('q', ''));

        $showAll =
            $request->boolean(
                'show_all'
            );

        $role = strtolower(
            trim(
                (string) $request->query(
                    'role',
                    ''
                )
            )
        );

        if (
            !in_array(
                $role,
                [
                    'patient',
                    'faculty',
                    'admin',
                ],
                true
            )
        ) {
            $role = '';
        }

        $page = max(
            1,
            (int) $request->query('page', 1)
        );

        $perPageInput = (int) $request->query(
            'per_page',
            10
        );

        $perPage = in_array(
            $perPageInput,
            [10, 20, 50, 100],
            true
        )
            ? $perPageInput
            : 10;

        $sourceLimit = max(
            20,
            min(
                $perPage,
                50
            )
        );

        try {
            $hasMeaningfulSearch =
                mb_strlen($search) >= 2;

            $shouldLoadConnectedSources =
                $hasMeaningfulSearch ||
                $showAll;

            $loadStudents =
                $shouldLoadConnectedSources &&
                in_array(
                    $role,
                    ['', 'patient'],
                    true
                );

            $loadFaculty =
                $shouldLoadConnectedSources &&
                in_array(
                    $role,
                    ['', 'faculty'],
                    true
                );

            $loadAdministrative =
                $shouldLoadConnectedSources &&
                in_array(
                    $role,
                    ['', 'admin'],
                    true
                );

            $localClassifications =
                match ($role) {
                    'patient' => [
                        'student',
                        'alumni',
                        'dependent',
                        'dependent_alumni',
                    ],

                    'faculty' => [
                        'faculty',
                    ],

                    'admin' => [
                        'administrative',
                    ],

                    default => null,
                };

            $patients = collect(
                $this->searchLocalPatients(
                    $search,
                    $sourceLimit,
                    $localClassifications
                )
            )
                ->merge(
                    $loadStudents
                        ? $this->searchOgosPatients(
                            $search,
                            $sourceLimit,
                            $studentApiService
                        )
                        : []
                )
                ->merge(
                    $loadFaculty
                        ? $this->searchFacultyPatients(
                            $search,
                            $sourceLimit,
                            $facultyApiService
                        )
                        : []
                )
                ->merge(
                    $loadAdministrative
                        ? $this->searchExternalAdminPatients(
                            $search,
                            $sourceLimit
                        )
                        : []
                )
                ->filter()
                ->unique(
                    fn(array $patient) =>
                    $this->getPatientIdentityKey(
                        $patient
                    )
                )
                ->map(function (array $patient) use ($search) {
                    $patient['_score'] =
                        $this->scorePatientSearchMatch(
                            $patient,
                            $search
                        );

                    return $patient;
                })
                ->filter(
                    fn(array $patient) =>
                    $search === '' ||
                        (($patient['_score'] ?? -1) >= 0)
                )
                ->sort(function (
                    array $a,
                    array $b
                ) use ($search) {

                    if ($search === '') {
                        return strcasecmp(
                            (string) ($a['name'] ?? ''),
                            (string) ($b['name'] ?? '')
                        );
                    }

                    $scoreCompare =
                        ($b['_score'] ?? -1)
                        <=>
                        ($a['_score'] ?? -1);

                    if ($scoreCompare !== 0) {
                        return $scoreCompare;
                    }

                    return strcasecmp(
                        (string) ($a['name'] ?? ''),
                        (string) ($b['name'] ?? '')
                    );
                })
                ->map(function (array $patient) {
                    unset($patient['_score']);

                    return $patient;
                })
                ->values();

            $total = $patients->count();

            $lastPage = max(
                1,
                (int) ceil($total / $perPage)
            );

            $page = min($page, $lastPage);

            $offset = ($page - 1) * $perPage;

            $pageRecords = $patients
                ->slice($offset, $perPage)
                ->values();

            $from = $total > 0
                ? $offset + 1
                : null;

            $to = $total > 0
                ? min($offset + $perPage, $total)
                : null;

            return response()->json([
                'data' => $pageRecords,
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $from,
                'to' => $to,
            ]);
        } catch (\Throwable $e) {
            Log::error('Walk-in unified patient search failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Unable to load patient records from connected systems.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function patientBookingInformation(
        Patient $patient,
        StudentApiService $studentApiService
    ) {
        $patient = $this->resolveWalkInSourcePatient($patient);

        $personalInfo = $this->backfillConnectedPatientMedicalHistory(
            $patient,
            $studentApiService
        );

        $patient->refresh();
        $patient->load([
            'user',
            'information',
            'dentalHistory',
            'dentalHistoryDates',
            'dentalHistoryConcerns',
            'dentalHistoryAnswers.condition',
            'medicalHistory.answers.question',
            'medicalHistory.diseaseAnswers.disease',
        ]);

        $ogosEmergencyDefaults = $this->resolveConnectedPatientEmergencyDefaults($personalInfo);

        /*
    |--------------------------------------------------------------------------
    | DENTAL HISTORY
    |--------------------------------------------------------------------------
    */

        $dentalDefaults = [
            'last_dental_visit' =>
            $patient->dentalHistory?->last_dental_visit
                ? Carbon::parse(
                    $patient->dentalHistory->last_dental_visit
                )->format('Y-m-d')
                : '',

            'previous_dentist' =>
            $patient->dentalHistory?->previous_dentist ?? '',

            'additional_concerns' =>
            $patient->dentalHistoryConcerns?->additional_concerns ?? '',

            'extraction_date' =>
            $patient->dentalHistoryDates?->extraction_date
                ? Carbon::parse(
                    $patient->dentalHistoryDates->extraction_date
                )->format('Y-m-d')
                : '',

            'dentures_date' =>
            $patient->dentalHistoryDates?->dentures_date
                ? Carbon::parse(
                    $patient->dentalHistoryDates->dentures_date
                )->format('Y-m-d')
                : '',

            'ortho_date' =>
            $patient->dentalHistoryDates?->ortho_date
                ? Carbon::parse(
                    $patient->dentalHistoryDates->ortho_date
                )->format('Y-m-d')
                : '',
        ];

        foreach ($patient->dentalHistoryAnswers as $answer) {
            $code = $answer->condition?->code;

            if (!$code) {
                continue;
            }

            $dentalDefaults[$code] =
                $answer->answer ? 'YES' : 'NO';
        }

        $medicalDefaults = [];

        if ($patient->medicalHistory) {
            $medicalDefaults = [
                'emergency_person' =>
                $patient->medicalHistory->emergency_person
                    ?: ($ogosEmergencyDefaults['emergency_person'] ?? ''),

                'emergency_number' =>
                $patient->medicalHistory->emergency_number
                    ?: ($ogosEmergencyDefaults['emergency_number'] ?? ''),

                'emergency_relation' =>
                $patient->medicalHistory->emergency_relation
                    ?: ($ogosEmergencyDefaults['emergency_relation'] ?? ''),
            ];

            foreach ($patient->medicalHistory->answers as $answer) {
                $code = $answer->question?->code;

                if (!$code) {
                    continue;
                }

                if ($answer->answer_bool !== null) {
                    $medicalDefaults[$code] =
                        $answer->answer_bool ? 'YES' : 'NO';

                    continue;
                }

                if ($answer->answer_text !== null) {
                    $medicalDefaults[$code] =
                        $answer->answer_text;

                    continue;
                }

                if ($answer->answer_date !== null) {
                    $medicalDefaults[$code] =
                        Carbon::parse(
                            $answer->answer_date
                        )->format('Y-m-d');
                }
            }
        } elseif ($ogosEmergencyDefaults !== []) {
            $medicalDefaults = [
                'emergency_person' => $ogosEmergencyDefaults['emergency_person'] ?? '',
                'emergency_number' => $ogosEmergencyDefaults['emergency_number'] ?? '',
                'emergency_relation' => $ogosEmergencyDefaults['emergency_relation'] ?? '',
            ];
        }

        $selectedDiseases =
            $patient->medicalHistory
            ? $patient->medicalHistory
            ->diseaseAnswers
            ->filter(
                fn($answer) =>
                $answer->has_disease
            )
            ->map(
                fn($answer) =>
                $answer->disease?->code
            )
            ->filter()
            ->values()
            ->all()
            : [];
        $hasExistingDentalHistory =
            $this->hasExistingDentalHistoryRecord($patient);

        $hasExistingMedicalHistory =
            $this->hasExistingMedicalHistoryRecord($patient);

        $latestAppointment =
            Appointment::where(
                'patient_id',
                $patient->id
            )
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->first();

        $hasExistingAppointment =
            $latestAppointment !== null;

        $hasExistingBookingInformation =
            $hasExistingDentalHistory &&
            $hasExistingMedicalHistory;

        $hasAutofillData =
            $hasExistingAppointment ||
            $hasExistingDentalHistory ||
            $hasExistingMedicalHistory;

        $hasReusableSignature =
            !empty($patient->medicalHistory?->patient_signature) &&
            $patient->medicalHistory?->signature_review_status !==
            'invalid_reupload_required';

        $existingSignatureUrl = null;

        if (
            $hasReusableSignature &&
            filled($patient->medicalHistory?->patient_signature)
        ) {
            $signaturePath = ltrim(
                str_replace(
                    'storage/',
                    '',
                    $patient->medicalHistory->patient_signature
                ),
                '/'
            );

            if (
                Storage::disk('public')->exists(
                    $signaturePath
                )
            ) {
                $existingSignatureUrl =
                    Storage::disk('public')->url(
                        $signaturePath
                    );
            }
        }

        return response()->json([
            'success' => true,

            'has_existing_dental_history' =>
            $hasExistingDentalHistory,

            'has_existing_medical_history' =>
            $hasExistingMedicalHistory,

            'has_existing_appointment' =>
            $hasExistingAppointment,

            'has_existing_booking_information' =>
            $hasExistingBookingInformation,

            'has_autofill_data' =>
            $hasAutofillData,

            'last_service_type' =>
            $latestAppointment?->service_type_name,

            'has_reusable_signature' =>
            $hasReusableSignature,

            'existing_signature_url' =>
            $existingSignatureUrl,

            'dental' =>
            $dentalDefaults,

            'medical' =>
            $medicalDefaults,

            'diseases' =>
            $selectedDiseases,

            'contact' => [
                'email' =>
                $patient->email
                    ?? $patient->user?->email
                    ?? '',

                'phone' =>
                $patient->information?->phone
                    ?? '',

                'address' =>
                $patient->information?->address
                    ?? '',
            ],
        ]);
    }

    private function hasExistingDentalHistoryRecord(Patient $patient): bool
    {
        if ($patient->dentalHistoryAnswers->isNotEmpty()) {
            return true;
        }

        if (filled($patient->dentalHistoryConcerns?->additional_concerns)) {
            return true;
        }

        if (
            filled($patient->dentalHistory?->last_dental_visit) ||
            filled($patient->dentalHistory?->previous_dentist) ||
            filled($patient->dentalHistoryDates?->extraction_date) ||
            filled($patient->dentalHistoryDates?->dentures_date) ||
            filled($patient->dentalHistoryDates?->ortho_date)
        ) {
            return true;
        }

        return false;
    }

    private function hasExistingMedicalHistoryRecord(Patient $patient): bool
    {
        if ($patient->medicalHistory?->answers->isNotEmpty()) {
            return true;
        }

        if ($patient->medicalHistory?->diseaseAnswers->isNotEmpty()) {
            return true;
        }

        if (
            filled($patient->medicalHistory?->emergency_person) ||
            filled($patient->medicalHistory?->emergency_number) ||
            filled($patient->medicalHistory?->emergency_relation) ||
            filled($patient->medicalHistory?->patient_signature)
        ) {
            return true;
        }

        return false;
    }

    private function resolveConnectedPatientEmergencyDefaults(array $personalInfo): array
    {
        if ($personalInfo === []) {
            return [];
        }

        return array_filter([
            'emergency_person' =>
            $this->cleanStringValue(
                $personalInfo['emergencyContactName']
                    ?? $personalInfo['emergency_contact_name']
                    ?? data_get(
                        $personalInfo,
                        'emergencyContact.name'
                    )
                    ?? data_get(
                        $personalInfo,
                        'emergency_contact.name'
                    )
                    ?? data_get(
                        $personalInfo,
                        'emergency_contact.contact_name'
                    )
                    ?? data_get(
                        $personalInfo,
                        'emergencyContact.contactName'
                    )
                    ?? null
            ),

            'emergency_number' =>
            $this->normalizePhilippineMobile(
                $this->cleanStringValue(
                    $personalInfo['emergencyContactNumber']
                        ?? $personalInfo['emergency_contact_number']
                        ?? data_get(
                            $personalInfo,
                            'emergencyContact.number'
                        )
                        ?? data_get(
                            $personalInfo,
                            'emergencyContact.contactNumber'
                        )
                        ?? data_get(
                            $personalInfo,
                            'emergency_contact.number'
                        )
                        ?? data_get(
                            $personalInfo,
                            'emergency_contact.contact_number'
                        )
                        ?? null
                )
            ),

            'emergency_relation' =>
            $this->normalizeEmergencyRelation(
                $this->cleanStringValue(
                    $personalInfo['emergencyContactRelationship']
                        ?? $personalInfo['emergency_contact_relationship']
                        ?? $personalInfo['emergencyContactRelation']
                        ?? $personalInfo['emergency_contact_relation']
                        ?? data_get(
                            $personalInfo,
                            'emergencyContact.relationship'
                        )
                        ?? data_get(
                            $personalInfo,
                            'emergencyContact.relation'
                        )
                        ?? data_get(
                            $personalInfo,
                            'emergency_contact.relationship'
                        )
                        ?? data_get(
                            $personalInfo,
                            'emergency_contact.relation'
                        )
                        ?? null
                )
            ),
        ], fn($value) => filled($value));
    }

    private function resolveWalkInSourcePatient(Patient $patient): Patient
    {
        $candidateRelations = [
            'information',
            'dentalHistory',
            'dentalHistoryDates',
            'dentalHistoryConcerns',
            'dentalHistoryAnswers',
            'medicalHistory.answers',
            'medicalHistory.diseaseAnswers',
        ];

        $patient->loadMissing($candidateRelations);

        $candidates = collect([$patient]);


        if ($patient->user_id) {
            $candidates = $candidates->merge(
                Patient::with($candidateRelations)
                    ->where('id', '!=', $patient->id)
                    ->where('user_id', $patient->user_id)
                    ->get()
            );
        }

        $email = strtolower(
            trim((string) ($patient->email ?? ''))
        );

        if ($email !== '') {
            $candidates = $candidates->merge(
                Patient::with($candidateRelations)
                    ->where('id', '!=', $patient->id)
                    ->whereRaw(
                        'LOWER(email) = ?',
                        [$email]
                    )
                    ->get()
            );
        }

        $studentNumber = trim(
            (string) (
                $patient->studentInformation?->student_no
                ?? $patient->student_no
                ?? ''
            )
        );

        if ($studentNumber !== '') {
            $candidates = $candidates->merge(
                Patient::with($candidateRelations)
                    ->where('id', '!=', $patient->id)
                    ->whereHas(
                        'studentInformation',
                        function ($query) use ($studentNumber) {
                            $query->where(
                                'student_no',
                                $studentNumber
                            );
                        }
                    )
                    ->get()
            );
        }

        $facultyCode = trim(
            (string) (
                $patient->facultyInformation?->faculty_code
                ?? $patient->faculty_code
                ?? ''
            )
        );

        if ($facultyCode !== '') {
            $candidates = $candidates->merge(
                Patient::with($candidateRelations)
                    ->where('id', '!=', $patient->id)
                    ->whereHas(
                        'facultyInformation',
                        function ($query) use ($facultyCode) {
                            $query->where(
                                'faculty_code',
                                $facultyCode
                            );
                        }
                    )
                    ->get()
            );
        }

        $bestMatch = $candidates
            ->unique('id')
            ->sortByDesc(function (Patient $candidate) {
                $score = 0;

                if ($this->hasExistingDentalHistoryRecord($candidate)) {
                    $score += 8;
                }

                if ($this->hasExistingMedicalHistoryRecord($candidate)) {
                    $score += 8;
                }

                if (
                    Appointment::where(
                        'patient_id',
                        $candidate->id
                    )->exists()
                ) {
                    $score += 10;
                }

                if (
                    filled(
                        $candidate
                            ->medicalHistory
                            ?->emergency_person
                    )
                ) {
                    $score += 4;
                }

                if (
                    filled(
                        $candidate
                            ->medicalHistory
                            ?->emergency_number
                    )
                ) {
                    $score += 4;
                }

                if (
                    filled(
                        $candidate
                            ->medicalHistory
                            ?->emergency_relation
                    )
                ) {
                    $score += 4;
                }

                if ($candidate->medicalHistory) {
                    $score += 2;
                }

                if (
                    filled(
                        $candidate
                            ->medicalHistory
                            ?->patient_signature
                    ) &&
                    $candidate
                    ->medicalHistory
                    ?->signature_review_status !==
                    'invalid_reupload_required'
                ) {
                    $score += 6;
                }

                return $score;
            })
            ->first();

        return $bestMatch instanceof Patient
            ? $bestMatch
            : $patient;
    }

    private function backfillConnectedPatientMedicalHistory(
        Patient $patient,
        StudentApiService $studentApiService
    ): array {
        $patient->loadMissing([
            'information',
            'medicalHistory',
        ]);

        $information =
            $patient->information;

        $classification = strtolower(
            trim(
                (string) (
                    $patient->classification
                    ?? ''
                )
            )
        );

        $isStudent =
            filled(
                $patient->student_no
            ) ||
            (
                filled($patient->email) &&
                filled(
                    $patient->course_code
                )
            ) ||
            $classification === 'student';

        if (! $isStudent) {
            return [];
        }

        $medicalHistory =
            $patient->medicalHistory;

        $needsBackfill =
            blank($information?->birthdate) ||
            blank($information?->gender) ||
            blank($information?->address) ||
            blank(
                $medicalHistory
                    ?->emergency_person
            ) ||
            blank(
                $medicalHistory
                    ?->emergency_number
            ) ||
            blank(
                $medicalHistory
                    ?->emergency_relation
            );

        if (! $needsBackfill) {
            return [];
        }

        $studentNumber = trim(
            (string) (
                $patient->student_no
                ?? ''
            )
        );

        $studentProfile = [];
        $personalInfo = [];
        $addresses = [];

        try {
            if (filled($patient->email) && ($studentNumber === '' || blank($information?->gender))) {
                $studentProfileResponse =
                    $studentApiService
                    ->getStudentByEmail(
                        (string) $patient->email
                    );

                $studentProfile =
                    is_array(
                        $studentProfileResponse['data'] ?? null
                    )
                    ? $studentProfileResponse['data']
                    : [];
            }

            $studentNumber =
                $studentNumber
                ?: data_get(
                    $studentProfile,
                    'studentNumber'
                )
                ?: data_get(
                    $studentProfile,
                    'student_number'
                )
                ?: '';

            if (
                $studentNumber !== '' &&
                blank(
                    $patient->student_no
                )
            ) {
                $patient->student_no =
                    $studentNumber;
            }

            if ($studentNumber !== '') {
                $personalInfoResponse =
                    $studentApiService
                    ->getPersonalInfoByStudentNumber(
                        $studentNumber
                    );

                $personalInfo =
                    is_array(
                        $personalInfoResponse['data'] ?? null
                    )
                    ? $personalInfoResponse['data']
                    : [];

                if (blank($information?->address)) {
                    $addressResponse =
                        $studentApiService
                        ->getAddressesByStudentNumber(
                            $studentNumber
                        );

                    $addresses =
                        is_array(
                            $addressResponse['data'] ?? null
                        )
                        ? $addressResponse['data']
                        : [];
                }
            }

            $birthdate =
                $this->normalizeDate(
                    $personalInfo['dateOfBirth']
                        ?? $personalInfo['birthdate']
                        ?? null
                );

            $gender =
                $this->normalizeGenderLabel(
                    $personalInfo['gender']['name']
                        ?? $personalInfo['gender']
                        ?? data_get(
                            $studentProfile,
                            'gender.name'
                        )
                        ?? data_get(
                            $studentProfile,
                            'gender'
                        )
                        ?? null
                );

            $address =
                $this->formatStudentAddress(
                    $addresses
                );

            if (
                blank(
                    $information?->birthdate
                ) &&
                filled($birthdate)
            ) {
                $patient->birthdate =
                    $birthdate;
            }

            if (
                blank(
                    $information?->gender
                ) &&
                filled($gender)
            ) {
                $patient->gender =
                    $gender;
            }

            if (
                blank(
                    $information?->address
                ) &&
                filled($address)
            ) {
                $patient->address =
                    $address;
            }

            if ($patient->isDirty()) {
                $patient->save();
            }

            if ($personalInfo !== []) {
                $this->syncStudentMedicalHistory(
                    $patient,
                    $personalInfo
                );
            }
        } catch (\Throwable $e) {
            Log::warning(
                'Walk-in OGOS medical history backfill failed',
                [
                    'patient_id' =>
                    $patient->id,

                    'student_no' =>
                    $studentNumber,

                    'email' =>
                    $patient->email,

                    'message' =>
                    $e->getMessage(),
                ]
            );
        }
        // Reuse even a partial result; do not repeat a failed external request.
        return $personalInfo;
    }
    private function syncStudentMedicalHistory(
        Patient $patient,
        array $personalInfo
    ): void {
        $emergencyPerson = $this->cleanStringValue(
            $personalInfo['emergencyContactName']
                ?? $personalInfo['emergency_contact_name']
                ?? data_get($personalInfo, 'emergencyContact.name')
                ?? data_get($personalInfo, 'emergency_contact.name')
                ?? data_get($personalInfo, 'emergency_contact.contact_name')
                ?? data_get($personalInfo, 'emergencyContact.contactName')
                ?? null
        );

        $emergencyNumber = $this->normalizePhilippineMobile(
            $this->cleanStringValue(
                $personalInfo['emergencyContactNumber']
                    ?? $personalInfo['emergency_contact_number']
                    ?? data_get($personalInfo, 'emergencyContact.number')
                    ?? data_get($personalInfo, 'emergencyContact.contactNumber')
                    ?? data_get($personalInfo, 'emergency_contact.number')
                    ?? data_get($personalInfo, 'emergency_contact.contact_number')
                    ?? null
            )
        );

        $emergencyRelation = $this->normalizeEmergencyRelation(
            $this->cleanStringValue(
                $personalInfo['emergencyContactRelationship']
                    ?? $personalInfo['emergency_contact_relationship']
                    ?? $personalInfo['emergencyContactRelation']
                    ?? $personalInfo['emergency_contact_relation']
                    ?? data_get($personalInfo, 'emergencyContact.relationship')
                    ?? data_get($personalInfo, 'emergencyContact.relation')
                    ?? data_get($personalInfo, 'emergency_contact.relationship')
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
    }

    private function cleanStringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $cleaned = trim((string) $value);

        return $cleaned !== '' ? $cleaned : null;
    }

    private function extractStudentNumber(array $studentData): ?string
    {
        return data_get($studentData, 'studentNumber')
            ?? data_get($studentData, 'student_number')
            ?? data_get($studentData, 'studentNo')
            ?? data_get($studentData, 'student_no')
            ?? null;
    }

    private function normalizeDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return $value;
        }
    }

    private function normalizeGenderLabel(?string $value): ?string
    {
        $gender = strtolower(trim((string) $value));

        if ($gender === '') {
            return null;
        }

        if (str_starts_with($gender, 'm')) {
            return 'Male';
        }

        if (str_starts_with($gender, 'f')) {
            return 'Female';
        }

        return $value;
    }

    private function formatStudentAddress(array $addresses): ?string
    {
        if ($addresses === []) {
            return null;
        }

        $preferredAddress = collect($addresses)->first(function ($address) {
            $type = strtolower(trim((string) data_get($address, 'addressType')));

            return in_array($type, ['current', 'present', 'home', 'permanent'], true);
        }) ?? $addresses[0];

        $parts = array_filter([
            $this->cleanStringValue(data_get($preferredAddress, 'streetDetail.string'))
                ?: $this->cleanStringValue(data_get($preferredAddress, 'streetDetail')),
            $this->cleanStringValue(data_get($preferredAddress, 'barangay.name')),
            $this->cleanStringValue(data_get($preferredAddress, 'city.name')),
            $this->cleanStringValue(data_get($preferredAddress, 'province.name.string'))
                ?: $this->cleanStringValue(data_get($preferredAddress, 'province.name')),
            $this->cleanStringValue(data_get($preferredAddress, 'region.name')),
        ]);

        if ($parts === []) {
            return null;
        }

        return implode(', ', array_values($parts));
    }

    private function normalizePhilippineMobile(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        if (! preg_match('/^09\d{9}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    private function normalizeEmergencyRelation(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
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

    private function makeExternalPatientResult(
        string $source,
        array $data
    ): array {
        $identity = strtolower(trim((string) (
            $data['email']
            ?? $data['student_number']
            ?? $data['faculty_code']
            ?? $data['external_id']
            ?? $data['name']
            ?? Str::uuid()->toString()
        )));

        $selectionToken = Crypt::encryptString(
            json_encode(
                [
                    'source' => $source,
                    'data' => $data,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return [
            'id' => 'external:' . $source . ':' . sha1($identity),

            'name' => $data['name'] ?? 'Patient',
            'gender' => $data['gender'] ?? null,
            'birthdate' => $data['birthdate'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,

            'type' => $data['type'] ?? ucfirst($source),

            'student_number' =>
            $data['student_number'] ?? null,

            'program' =>
            $data['program'] ?? null,

            'department' =>
            $data['department'] ?? null,

            'faculty_code' =>
            $data['faculty_code'] ?? null,

            'faculty_type' =>
            $data['faculty_type'] ?? null,

            'year_level' =>
            $data['year_level'] ?? null,

            'section' =>
            $data['section'] ?? null,

            'is_pwd' =>
            $data['is_pwd'] ?? false,

            'is_local' => false,
            'source' => $source,
            'selection_token' => $selectionToken,

            'record_url' => null,
            'avatar_url' => null,
        ];
    }

    private function searchOgosPatients(
        string $search,
        int $limit,
        StudentApiService $studentApiService
    ): array {
        try {
            $students =
                $studentApiService->searchStudents(
                    search: $search !== ''
                        ? $search
                        : null,

                    limit: $limit
                );

            return collect($students)
                ->map(
                    fn($student) =>
                    $studentApiService->normalizeStudent(
                        (array) $student
                    )
                )
                ->filter()
                ->map(function (array $student) {
                    $name = trim(
                        (string) (
                            $student['name']
                            ?? 'Student'
                        )
                    );

                    $email = strtolower(
                        trim(
                            (string) (
                                $student['email']
                                ?? ''
                            )
                        )
                    );

                    $studentNumber = trim(
                        (string) (
                            $student['student_number']
                            ?? ''
                        )
                    );

                    if ($email === '') {
                        $identity =
                            $studentNumber !== ''
                            ? $studentNumber
                            : $name;

                        $email =
                            'student_' .
                            sha1(strtolower($identity)) .
                            '@walkin.local';
                    }

                    $data = [
                        'name' => $name,

                        'first_name' =>
                        $student['first_name']
                            ?? null,

                        'middle_name' =>
                        $student['middle_name']
                            ?? null,

                        'last_name' =>
                        $student['last_name']
                            ?? null,

                        'suffix_name' =>
                        $student['suffix_name']
                            ?? null,

                        'email' => $email,

                        'phone' =>
                        $student['phone']
                            ?? null,

                        'gender' =>
                        $student['gender']
                            ?? null,

                        'birthdate' =>
                        $student['birthdate']
                            ?? null,

                        'student_number' =>
                        $studentNumber !== ''
                            ? $studentNumber
                            : null,

                        'program' =>
                        $student['program']
                            ?? null,

                        'year_level' =>
                        $student['year_level']
                            ?? null,

                        'section' =>
                        $student['section']
                            ?? null,

                        'is_pwd' =>
                        $student['is_pwd']
                            ?? false,

                        'faculty_code' => null,

                        'classification' =>
                        'student',

                        'type' =>
                        'Student',
                    ];

                    return $this
                        ->makeExternalPatientResult(
                            'student',
                            $data
                        );
                })
                ->all();
        } catch (\Throwable $e) {
            Log::warning(
                'Walk-in OGOS search failed',
                [
                    'message' =>
                    $e->getMessage(),
                ]
            );

            return [];
        }
    }

    private function scorePatientSearchMatch(array $patient, string $search): int
    {
        $normalizedQuery = $this->normalizeSearchText($search);
        if ($normalizedQuery === '') {
            return 0;
        }

        $tokens = array_values(array_filter(explode(' ', $normalizedQuery)));
        $values = [
            $patient['name'] ?? '',
            $patient['email'] ?? '',
            $patient['student_number'] ?? '',
            $patient['program'] ?? '',
            $patient['type'] ?? '',
        ];

        $best = -1;

        foreach ($values as $index => $value) {
            $score = $this->scoreSearchValue((string) $value, $normalizedQuery, $tokens);
            if ($score >= 0) {
                $best = max($best, $score - ($index * 10));
            }
        }

        return $best;
    }

    private function scoreSearchValue(string $haystack, string $query, array $tokens): int
    {
        $normalizedHaystack = $this->normalizeSearchText($haystack);
        if ($normalizedHaystack === '') {
            return -1;
        }

        if ($normalizedHaystack === $query) {
            return 1000;
        }

        if (str_starts_with($normalizedHaystack, $query)) {
            return 800;
        }

        if ($tokens !== [] && collect($tokens)->every(fn(string $token) => str_contains($normalizedHaystack, $token))) {
            return 500 - strpos($normalizedHaystack, $tokens[0]);
        }

        if (str_contains($normalizedHaystack, $query)) {
            return 250 - strpos($normalizedHaystack, $query);
        }

        return -1;
    }

    private function normalizeSearchText(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9@\s._-]/', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return trim($normalized);
    }

    private function getPatientIdentityKey(array $patient): string
    {
        $email = strtolower(trim((string) ($patient['email'] ?? '')));

        if ($email !== '') {
            return 'email:' . $email;
        }

        $studentNumber = strtolower(trim((string) ($patient['student_number'] ?? '')));

        if ($studentNumber !== '') {
            return 'student:' . $studentNumber;
        }

        $facultyCode = strtolower(trim((string) ($patient['faculty_code'] ?? '')));

        if ($facultyCode !== '') {
            return 'faculty:' . $facultyCode;
        }

        return 'id:' . strtolower(trim((string) ($patient['id'] ?? ($patient['name'] ?? Str::uuid()->toString()))));
    }

    private function searchLocalPatients(
        string $search,
        int $limit,
        ?array $classifications = null
    ): array {
        $userColumns = [
            'id',
            'name',
            'email',
        ];

        if (Schema::hasColumn('users', 'profile_image')) {
            $userColumns[] = 'profile_image';
        }

        $patientColumns = [
            'id',
            'user_id',
            'name',
            'email',
            'classification',
        ];

        if (Schema::hasColumn('patients', 'profile_image')) {
            $patientColumns[] = 'profile_image';
        }

        $query = Patient::query()
            ->with([
                'user' => function ($query) use ($userColumns) {
                    $query->select($userColumns);
                },

                'user.profile' => function ($query) {
                    $query->select([
                        'id',
                        'user_id',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'suffix_name',
                    ]);
                },

                'information' => function ($query) {
                    $query->select([
                        'id',
                        'patient_id',
                        'phone',
                        'gender',
                        'birthdate',
                        'is_pwd',
                    ]);
                },

                'studentInformation' => function ($query) {
                    $query->select([
                        'id',
                        'patient_id',
                        'student_no',
                        'course_code',
                        'course_name',
                        'year_level',
                        'section',
                    ]);
                },

                'facultyInformation' => function ($query) {
                    $query->select([
                        'id',
                        'patient_id',
                        'faculty_code',
                    ]);
                },
            ])
            ->select($patientColumns)
            ->orderBy('name');

        if ($classifications !== null) {
            $query->whereIn(
                'classification',
                $classifications
            );
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {

                $builder
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhereHas(
                        'studentInformation',
                        function ($studentQuery) use ($search) {
                            $studentQuery
                                ->where(
                                    'student_no',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'course_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'course_code',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'facultyInformation',
                        function ($facultyQuery) use ($search) {
                            $facultyQuery->where(
                                'faculty_code',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(function (Patient $patient) {
                $user = $patient->user;
                $userProfile = $user?->profile;
                $information = $patient->information;
                $studentInformation = $patient->studentInformation;
                $facultyInformation = $patient->facultyInformation;

                $suffixName = $this->normalizeNameSuffix(
                    $userProfile?->suffix_name
                );

                $patientName = trim(
                    collect([
                        $userProfile?->first_name,
                        $userProfile?->middle_name,
                        $userProfile?->last_name,
                        $suffixName,
                    ])
                        ->filter(fn($value) => filled($value))
                        ->implode(' ')
                );

                if ($patientName === '') {
                    $patientName = trim(
                        (string) ($user?->name ?? '')
                    );
                }

                if ($patientName === '') {
                    $patientName = trim(
                        (string) ($patient->name ?? '')
                    );
                }

                $patientType = match ($patient->classification) {
                    'student' => 'Student',
                    'faculty' => 'Faculty',
                    'administrative' => 'Administrative Personnel',
                    'alumni' => 'Alumni',
                    'dependent' => 'Dependent',
                    'dependent_alumni' => 'Dependent & Alumni',
                    default => 'Dependent & Alumni',
                };

                return [
                    'id' =>
                    $patient->id,

                    'is_local' =>
                    true,

                    'source' =>
                    'local',

                    'selection_token' =>
                    null,

                    'name' =>
                    $patientName !== ''
                        ? $patientName
                        : 'Patient',

                    'gender' =>
                    $information?->gender,

                    'birthdate' =>
                    $information?->birthdate,

                    'email' =>
                    $patient->email
                        ?? $user?->email,

                    'phone' =>
                    $information?->phone,

                    'type' =>
                    $patientType,

                    'student_number' =>
                    $studentInformation?->student_no,

                    'program' =>
                    $studentInformation?->course_name
                        ?? $studentInformation?->course_code,

                    'faculty_code' =>
                    $facultyInformation?->faculty_code,

                    'year_level' =>
                    $studentInformation?->year_level,

                    'section' =>
                    $studentInformation?->section,

                    'is_pwd' =>
                    $information?->is_pwd,

                    'avatar_url' =>
                    $this->getPatientAvatarUrl(
                        $patient,
                        $patientName
                    ),

                    'record_url' =>
                    route(
                        'dentist.odontogram.existing-appointment.create',
                        [
                            'patient' => $patient->id,
                        ]
                    ),
                ];
            })
            ->all();
    }

    private function searchFacultyPatients(
        string $search,
        int $limit,
        FacultyApiService $facultyApiService
    ): array {
        $faculties = collect(
            $facultyApiService->getFaculties()
        )
            ->filter(function ($faculty) use ($search) {
                if ($search === '') {
                    return true;
                }

                $haystack = strtolower(
                    implode(
                        ' ',
                        array_filter([
                            (string) (
                                $faculty['name']
                                ?? ''
                            ),

                            (string) (
                                $faculty['first_name']
                                ?? ''
                            ),

                            (string) (
                                $faculty['middle_name']
                                ?? ''
                            ),

                            (string) (
                                $faculty['last_name']
                                ?? ''
                            ),

                            (string) (
                                $faculty['suffix_name']
                                ?? ''
                            ),

                            (string) (
                                $faculty['email']
                                ?? ''
                            ),

                            (string) (
                                $faculty['faculty_code']
                                ?? ''
                            ),

                            (string) (
                                $faculty['department']
                                ?? ''
                            ),
                        ])
                    )
                );

                return str_contains(
                    $haystack,
                    strtolower($search)
                );
            })
            ->take($limit)
            ->map(function (array $faculty) {
                $firstName = trim(
                    (string) (
                        $faculty['first_name']
                        ?? ''
                    )
                );

                $middleName = trim(
                    (string) (
                        $faculty['middle_name']
                        ?? ''
                    )
                );

                $lastName = trim(
                    (string) (
                        $faculty['last_name']
                        ?? ''
                    )
                );

                $suffixName = trim(
                    (string) (
                        $faculty['suffix_name']
                        ?? ''
                    )
                );

                $name = trim(
                    (string) (
                        $faculty['name']
                        ?? ''
                    )
                );

                if ($name === '') {
                    $name = trim(
                        collect([
                            $firstName,
                            $middleName,
                            $lastName,
                            $suffixName,
                        ])
                            ->filter()
                            ->implode(' ')
                    );
                }

                if ($name === '') {
                    $name = 'Faculty Member';
                }

                $facultyCode = trim(
                    (string) (
                        $faculty['faculty_code']
                        ?? ''
                    )
                );

                $email = strtolower(
                    trim(
                        (string) (
                            $faculty['email']
                            ?? ''
                        )
                    )
                );

                if ($email === '') {
                    $identity =
                        $facultyCode !== ''
                        ? $facultyCode
                        : (
                            $faculty['faculty_id']
                            ?? $name
                        );

                    $email =
                        'faculty_' .
                        sha1(
                            strtolower(
                                (string) $identity
                            )
                        ) .
                        '@walkin.local';
                }

                $department =
                    $faculty['department']
                    ?? data_get(
                        $faculty,
                        'profile.department'
                    );

                $data = [
                    'name' => $name,

                    'first_name' =>
                    $firstName !== ''
                        ? $firstName
                        : null,

                    'middle_name' =>
                    $middleName !== ''
                        ? $middleName
                        : null,

                    'last_name' =>
                    $lastName !== ''
                        ? $lastName
                        : null,

                    'suffix_name' =>
                    $suffixName !== ''
                        ? $suffixName
                        : null,

                    'email' => $email,

                    'phone' =>
                    $faculty['contact_number'] ?? null,

                    'gender' =>
                    data_get(
                        $faculty,
                        'profile.gender'
                    ),

                    'birthdate' =>
                    $faculty['birthdate']
                        ?? $faculty['birthday']
                        ?? data_get(
                            $faculty,
                            'profile.birthdate'
                        ),

                    'student_number' => null,

                    'program' =>
                    $department,

                    'department' =>
                    $department,

                    'faculty_code' =>
                    $facultyCode !== ''
                        ? $facultyCode
                        : null,

                    'faculty_type' =>
                    $faculty['faculty_type'] ?? null,

                    'classification' =>
                    'faculty',

                    'type' =>
                    'Faculty',
                ];

                return $this
                    ->makeExternalPatientResult(
                        'faculty',
                        $data
                    );
            });

        return $faculties->all();
    }

    private function searchExternalAdminPatients(
        string $search,
        int $limit
    ): array {
        $baseUrl = rtrim(
            (string) env(
                'OCMS_EXTERNAL_API_URL'
            ),
            '/'
        );

        $apiKey = (string) env(
            'OCMS_EXTERNAL_API_KEY'
        );

        if (
            $baseUrl === '' ||
            $apiKey === ''
        ) {
            return [];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withHeaders([
                    'X-External-Api-Key' =>
                    $apiKey,
                ])
                ->get(
                    $baseUrl .
                        '/external/admins',

                    array_filter([
                        'search' =>
                        $search !== ''
                            ? $search
                            : null,
                    ])
                );

            if ($response->failed()) {
                Log::error(
                    'OCMS external admin search failed during walk-in',
                    [
                        'status' =>
                        $response->status(),

                        'body' =>
                        $response->body(),
                    ]
                );

                return [];
            }

            return collect(
                is_array(
                    $response->json('data')
                )
                    ? $response->json('data')
                    : []
            )
                ->take($limit)
                ->map(function (array $admin) {
                    $name = trim(
                        (string) (
                            ($admin['name'] ?? '')
                            ?: trim(
                                ((string) (
                                    $admin['first_name']
                                    ?? ''
                                )) .
                                    ' ' .
                                    ((string) (
                                        $admin['last_name']
                                        ?? ''
                                    ))
                            )
                        )
                    );

                    if ($name === '') {
                        $name =
                            'Administrative Patient';
                    }

                    $externalId =
                        $admin['external_admin_id']
                        ?? $admin['id']
                        ?? null;

                    $email = strtolower(
                        trim(
                            (string) (
                                $admin['email']
                                ?? ''
                            )
                        )
                    );

                    if ($email === '') {
                        $identity =
                            $externalId
                            ?? $name;

                        $email =
                            'admin_' .
                            sha1(
                                strtolower(
                                    (string) $identity
                                )
                            ) .
                            '@walkin.local';
                    }

                    $office = trim(
                        (string) (
                            $admin['office']
                            ?? ''
                        )
                    );

                    $data = [
                        'name' => $name,

                        'first_name' =>
                        $admin['first_name'] ?? null,

                        'middle_name' =>
                        $admin['middle_name'] ?? null,

                        'last_name' =>
                        $admin['last_name'] ?? null,

                        'suffix_name' =>
                        $admin['suffix_name'] ?? null,

                        'email' => $email,

                        'phone' =>
                        $admin['emergency_contact_no'] ?? null,

                        'gender' =>
                        $admin['gender'] ?? null,

                        'birthdate' =>
                        $admin['birthday']
                            ?? $admin['birthdate']
                            ?? null,

                        'student_number' =>
                        null,

                        'faculty_code' =>
                        null,

                        'program' =>
                        $office !== ''
                            ? $office
                            : null,

                        'department' =>
                        $office !== ''
                            ? $office
                            : null,

                        'external_id' =>
                        $externalId,

                        'classification' =>
                        'administrative',

                        'type' =>
                        'Administrative Personnel',
                    ];

                    return $this
                        ->makeExternalPatientResult(
                            'administrative',
                            $data
                        );
                })
                ->all();
        } catch (\Throwable $e) {
            Log::warning(
                'OCMS external admin search unavailable during walk-in',
                [
                    'message' =>
                    $e->getMessage(),

                    'base_url' =>
                    $baseUrl,
                ]
            );

            return [];
        }
    }

    public function resolveExternalPatient(Request $request)
    {
        $validated = $request->validate([
            'selection_token' => [
                'required',
                'string',
            ],
        ]);

        try {
            $decrypted = Crypt::decryptString(
                $validated['selection_token']
            );

            $payload = json_decode(
                $decrypted,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $e) {
            Log::warning(
                'Invalid walk-in external patient selection token',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                'The selected external patient could not be verified. Please search for the patient again.',
            ], 422);
        }

        if (
            ! is_array($payload) ||
            ! isset($payload['source']) ||
            ! isset($payload['data']) ||
            ! is_array($payload['data'])
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                'The selected external patient information is invalid.',
            ], 422);
        }

        $source = strtolower(
            trim(
                (string) $payload['source']
            )
        );

        if (
            ! in_array(
                $source,
                [
                    'student',
                    'faculty',
                    'administrative',
                ],
                true
            )
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                'Unsupported external patient source.',
            ], 422);
        }

        $data = $payload['data'];

        $type = match ($source) {
            'student' => 'Student',
            'faculty' => 'Faculty',
            'administrative' => 'Administrative',
        };

        $data['classification'] = match ($source) {
            'student' => 'student',
            'faculty' => 'faculty',
            'administrative' => 'administrative',
        };

        try {
            $patient = DB::transaction(
                function () use (
                    $data,
                    $type
                ) {


                    $user =
                        $this->syncWalkInUser(
                            $data
                        );

                    return $this->syncWalkInPatient(
                        $user,
                        $data,
                        $type
                    );
                }
            );

            $patient->loadMissing([
                'user.profile',
                'information',
                'studentInformation',
                'facultyInformation',
            ]);

            $information = $patient->information;
            $studentInformation = $patient->studentInformation;
            $facultyInformation = $patient->facultyInformation;

            return response()->json([
                'success' => true,

                'patient' => [
                    'id' =>
                    $patient->id,

                    'name' =>
                    $patient->name
                        ?: ($data['name'] ?? 'Patient'),

                    'email' =>
                    $patient->email
                        ?: ($data['email'] ?? null),

                    'phone' =>
                    $information?->phone
                        ?: ($data['phone'] ?? null),

                    'gender' =>
                    $information?->gender
                        ?: ($data['gender'] ?? null),

                    'birthdate' =>
                    $information?->birthdate
                        ? $information
                        ->birthdate
                        ->format('Y-m-d')
                        : ($data['birthdate'] ?? null),

                    'student_number' =>
                    $studentInformation?->student_no
                        ?: ($data['student_number'] ?? null),

                    'faculty_code' =>
                    $facultyInformation?->faculty_code
                        ?: ($data['faculty_code'] ?? null),

                    'program' =>
                    $studentInformation?->course_name
                        ?: $studentInformation?->course_code
                        ?: ($data['program'] ?? null),

                    'year_level' =>
                    $studentInformation?->year_level
                        ?? ($data['year_level'] ?? null),

                    'section' =>
                    $studentInformation?->section
                        ?: ($data['section'] ?? null),

                    'is_pwd' =>
                    $information?->is_pwd
                        ?? ($data['is_pwd'] ?? false),

                    'type' =>
                    match ($patient->classification) {
                        'student' =>
                        'Student',

                        'faculty' =>
                        'Faculty',

                        'administrative' =>
                        'Administrative Personnel',

                        'alumni' =>
                        'Alumni',

                        'dependent' =>
                        'Dependent',

                        'dependent_alumni' =>
                        'Dependent & Alumni',

                        default =>
                        $type,
                    },


                    'is_local' => true,
                    'source' => 'local',

                    'avatar_url' =>
                    $this->getPatientAvatarUrl(
                        $patient,
                        $patient->name
                    ),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'Walk-in external patient resolution failed',
                [
                    'source' => $source,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                config('app.debug')
                    ? $e->getMessage()
                    : 'Unable to prepare the selected patient for walk-in.',
            ], 500);
        }
    }

    public function storeGuest(Request $request)
    {
        $validated = $request->validate([
            'guest_first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'guest_middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'guest_last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'guest_suffix' => [
                'nullable',
                'string',
                'max:20',
            ],

            'guest_email' => [
                'required',
                'email',
                'max:255',
            ],

            'guest_phone' => [
                'required',
                'string',
                'regex:/^09\d{9}$/',
            ],

            'guest_patient_type' => [
                'required',
                'in:student,faculty,alumni,dependent,administrative',
            ],

            'guest_gender' => [
                'required',
                'in:Male,Female',
            ],

            'guest_birthdate' => [
                'required',
                'date',
                'before:today',
            ],

            'guest_program' => [
                'nullable',
                'string',
                'max:255',
            ],

            'guest_student_number' => [
                'nullable',
                'string',
                'max:100',
                'required_if:guest_patient_type,student',
            ],

            'guest_faculty_code' => [
                'nullable',
                'string',
                'max:100',
                'required_if:guest_patient_type,faculty',
            ],

            'guest_year_level' => [
                'nullable',
                'string',
                'max:50',
            ],

            'guest_section' => [
                'nullable',
                'string',
                'max:50',
            ],

            'guest_is_pwd' => [
                'required',
                'boolean',
            ],
        ]);

        $selectedPatientType = $this->normalizeGuestPatientType(
            $validated['guest_patient_type']
        );

        try {
            $patient = DB::transaction(function () use ($validated) {
                $email =
                    $validated['guest_email'];

                $guestPatientType =
                    $validated['guest_patient_type'];

                $patientType =
                    $this->normalizeGuestPatientType(
                        $guestPatientType
                    );

                $classification =
                    $this->guestClassification(
                        $guestPatientType
                    );

                $guestSuffix = $this->normalizeNameSuffix(
                    $validated['guest_suffix'] ?? null
                );

                $fullName = trim(
                    collect([
                        $validated['guest_first_name'],
                        $validated['guest_middle_name'] ?? null,
                        $validated['guest_last_name'],
                        $guestSuffix,
                    ])
                        ->filter(fn($value) => filled($value))
                        ->implode(' ')
                );
                $studentLikeData = [
                    'name' => $fullName,

                    'first_name' =>
                    $validated['guest_first_name'],

                    'middle_name' =>
                    $validated['guest_middle_name'] ?? null,

                    'last_name' =>
                    $validated['guest_last_name'],

                    'suffix_name' =>
                    $guestSuffix,

                    'email' =>
                    strtolower($email),

                    'phone' =>
                    $validated['guest_phone'] ?? null,

                    'gender' =>
                    $validated['guest_gender'],

                    'birthdate' =>
                    $validated['guest_birthdate'],

                    'program' =>
                    $validated['guest_program'] ?? null,

                    'faculty_code' =>
                    $guestPatientType === 'faculty'
                        ? trim((string) ($validated['guest_faculty_code'] ?? ''))
                        : null,

                    'year_level' =>
                    $validated['guest_year_level'] ?? null,

                    'section' =>
                    $validated['guest_section'] ?? null,

                    'is_pwd' =>
                    (bool) $validated['guest_is_pwd'],

                    'student_number' =>
                    $guestPatientType === 'student'
                        ? trim(
                            (string) (
                                $validated['guest_student_number']
                                ?? ''
                            )
                        )
                        : null,

                    'patient_type' =>
                    $patientType,

                    'classification' =>
                    $classification,
                ];

                $user = $this->syncWalkInUser($studentLikeData);
                return $this->syncWalkInPatient($user, $studentLikeData, $patientType);
            });

            return response()->json([
                'success' => true,
                'patient' => [
                    'id' =>
                    $patient->id,

                    'name' =>
                    $patient->name
                        ?? optional(
                            $patient->user
                        )->name,

                    'first_name' =>
                    optional(
                        $patient->user
                    )->first_name,

                    'middle_name' =>
                    optional(
                        $patient->user
                    )->middle_name,

                    'last_name' =>
                    optional(
                        $patient->user
                    )->last_name,

                    'suffix_name' =>
                    optional(
                        $patient->user
                    )->suffix_name,

                    'gender' =>
                    $patient->gender,


                    'email' =>
                    $patient->email
                        ?? optional($patient->user)->email,

                    'phone' => $patient->phone,

                    'birthdate' => $patient->birthdate,

                    'program' => $patient->course_name,

                    'student_number' =>
                    $patient
                        ->information
                        ?->student_no,
                    'faculty_code' => $patient->faculty_code,

                    'year_level' => $patient->year_level,

                    'section' => $patient->section,

                    'is_pwd' => (bool) $patient->is_pwd,

                    'avatar_url' =>
                    $this->getPatientAvatarUrl(
                        $patient,
                        $patient->name
                            ?? optional($patient->user)->name
                    ),

                    'patient_type' => match ($patient->classification) {
                        'student' => 'Student',
                        'faculty' => 'Faculty',
                        'administrative' => 'Administrative Personnel',
                        'alumni' => 'Alumni',
                        'dependent' => 'Dependent',
                        'dependent_alumni' => 'Dependent & Alumni',
                        default => $selectedPatientType,
                    },

                    'type' => match ($patient->classification) {
                        'student' => 'Student',
                        'faculty' => 'Faculty',
                        'administrative' => 'Administrative Personnel',
                        'alumni' => 'Alumni',
                        'dependent' => 'Dependent',
                        'dependent_alumni' => 'Dependent & Alumni',
                        default => $selectedPatientType,
                    },
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Walk-in guest creation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create guest patient.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function startWalkIn(Request $request)
    {
        $request->merge([
            'emergency_number' => $this->normalizePhilippineMobile(
                (string) $request->input('emergency_number', '')
            ) ?? (string) $request->input('emergency_number', ''),
        ]);

        $existingMedicalHistory = null;
        $hasReusableSignature = false;

        if ($request->filled('patient_id')) {
            $existingMedicalHistory =
                MedicalHistory::where(
                    'patient_id',
                    $request->input('patient_id')
                )->first();

            $hasReusableSignature =
                !empty($existingMedicalHistory?->patient_signature) &&
                $existingMedicalHistory?->signature_review_status !==
                'invalid_reupload_required';
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'service_type' => ['required', 'string', 'max:255'],
            'concern' => ['nullable', 'string', 'max:1000'],

            'emergency_person' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-zÑñ\s.\'-]+$/u',
            ],

            'emergency_number' => ['required', 'string', 'size:11', 'regex:/^09\d{9}$/'],
            'emergency_relation' => ['required', 'string', 'max:50'],

            'patient_signature' => [
                $hasReusableSignature
                    ? 'nullable'
                    : 'required',

                'file',
                'mimes:png,jpg,jpeg',
                'max:25600',
            ],

            'signature_source' => [
                'nullable',
                'in:drawn,upload',
            ],

            'diseases' => [
                'nullable',
                'array',
            ],

            'diseases.*' => [
                'string',
                'exists:diseases,code',
            ],
        ]);

        $patient = Patient::findOrFail(
            $validated['patient_id']
        );

        $now = Carbon::now();

        $serviceType = ServiceType::where(
            'name',
            $validated['service_type']
        )->firstOrFail();


        if ($request->hasFile('patient_signature')) {
            $signaturePath =
                $request
                ->file('patient_signature')
                ->store(
                    'signatures',
                    'public'
                );
        } elseif ($hasReusableSignature) {
            $signaturePath =
                $existingMedicalHistory
                ->patient_signature;
        } else {
            return response()->json([
                'success' => false,
                'message' =>
                'Patient signature is required.',
            ], 422);
        }

        $appointmentData = [
            'patient_id' => $patient->id,
            'dentist_id' => Auth::id(),
            'service_type_id' => $serviceType->id,
            'appointment_date' => $now->toDateString(),
            'appointment_time' => $now->format('H:i:s'),
            'status' => 'upcoming',
        ];

        if (Schema::hasColumn('appointments', 'service_type')) {
            $appointmentData['service_type'] = $serviceType->name;
        }

        if (Schema::hasColumn('appointments', 'is_walk_in')) {
            $appointmentData['is_walk_in'] = true;
        }

        if (
            Schema::hasColumn(
                'appointments',
                'concern'
            )
        ) {
            $appointmentData['concern'] =
                $validated['concern'] ?? null;
        }

        $appointment = null;

        try {
            DB::transaction(
                function () use (
                    $request,
                    $patient,
                    $validated,
                    $appointmentData,
                    $signaturePath,
                    &$appointment
                ) {

                    $appointment =
                        Appointment::create(
                            $appointmentData
                        );

                    DentalHistory::updateOrCreate(
                        [
                            'patient_id' =>
                            $patient->id,
                        ],
                        [
                            'last_dental_visit' =>
                            $request->input(
                                'last_dental_visit'
                            ),

                            'previous_dentist' =>
                            $request->input(
                                'previous_dentist'
                            ),
                        ]
                    );

                    DentalHistoryConditionDate::updateOrCreate(
                        [
                            'patient_id' =>
                            $patient->id,
                        ],
                        [
                            'extraction_date' =>
                            $request->input(
                                'extraction_date'
                            ),

                            'dentures_date' =>
                            $request->input(
                                'dentures_date'
                            ),

                            'ortho_date' =>
                            $request->input(
                                'ortho_date'
                            ),
                        ]
                    );

                    DentalHistoryConcern::updateOrCreate(
                        [
                            'patient_id' =>
                            $patient->id,
                        ],
                        [
                            'additional_concerns' =>
                            $request->input(
                                'additional_concerns'
                            ),
                        ]
                    );

                    $dentalAnswerMap = [
                        'bleeding_gums' =>
                        $request->input(
                            'bleeding_gums'
                        ),

                        'sensitive_temp' =>
                        $request->input(
                            'sensitive_temp'
                        ),

                        'sensitive_taste' =>
                        $request->input(
                            'sensitive_taste'
                        ),

                        'tooth_pain' =>
                        $request->input(
                            'tooth_pain'
                        ),

                        'sores' =>
                        $request->input(
                            'sores'
                        ),

                        'injuries' =>
                        $request->input(
                            'injuries'
                        ),

                        'clicking' =>
                        $request->input(
                            'clicking'
                        ),

                        'joint_pain' =>
                        $request->input(
                            'joint_pain'
                        ),

                        'difficulty_moving' =>
                        $request->input(
                            'difficulty_moving'
                        ),

                        'difficulty_chewing' =>
                        $request->input(
                            'difficulty_chewing'
                        ),

                        'jaw_headaches' =>
                        $request->input(
                            'jaw_headaches'
                        ),

                        'clench_grind' =>
                        $request->input(
                            'clench_grind'
                        ),

                        'biting' =>
                        $request->input(
                            'biting'
                        ),

                        'teeth_loosening' =>
                        $request->input(
                            'teeth_loosening'
                        ),

                        'food_teeth' =>
                        $request->input(
                            'food_teeth'
                        ),

                        'med_reaction' =>
                        $request->input(
                            'med_reaction'
                        ),

                        'periodontal' =>
                        $request->input(
                            'periodontal'
                        ),

                        'difficult_extraction' =>
                        $request->input(
                            'difficult_extraction'
                        ),

                        'prolonged_bleeding' =>
                        $request->input(
                            'prolonged_bleeding'
                        ),

                        'dentures' =>
                        $request->input(
                            'dentures'
                        ),

                        'ortho_treatment' =>
                        $request->input(
                            'ortho_treatment'
                        ),
                    ];

                    $conditionIdsByCode =
                        DentalHistoryCondition::whereIn(
                            'code',
                            array_keys(
                                $dentalAnswerMap
                            )
                        )
                        ->pluck(
                            'id',
                            'code'
                        );

                    foreach (
                        $dentalAnswerMap
                        as $code => $rawValue
                    ) {
                        $conditionId =
                            $conditionIdsByCode[$code] ?? null;

                        if (!$conditionId) {
                            continue;
                        }

                        DentalHistoryAnswer::updateOrCreate(
                            [
                                'patient_id' =>
                                $patient->id,

                                'condition_id' =>
                                $conditionId,
                            ],
                            [
                                'answer' =>
                                $this->yesNoValue(
                                    $rawValue
                                ) === 'YES',
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
                                $validated['emergency_person'],

                                'emergency_number' =>
                                $validated['emergency_number'],

                                'emergency_relation' =>
                                $validated['emergency_relation'],

                                'patient_signature' =>
                                $signaturePath,
                            ]
                        );

                    $tobaccoUse = $this->yesNoValue($request->input('tobacco_use'));

                    $medicalAnswerMap = [
                        'good_health' =>
                        $request->input(
                            'good_health'
                        ),

                        'had_medical_exam' =>
                        $request->input(
                            'had_medical_exam'
                        ),

                        'under_treatment' =>
                        $request->input(
                            'under_treatment'
                        ),

                        'hospitalized' =>
                        $request->input(
                            'hospitalized'
                        ),

                        'allergy_medicine' =>
                        $request->input(
                            'allergy_medicine'
                        ),

                        'allergy_food' =>
                        $request->input(
                            'allergy_food'
                        ),

                        'medication' =>
                        $request->input(
                            'medication'
                        ),

                        'pregnant' =>
                        $request->input(
                            'pregnant'
                        ),

                        'nursing' =>
                        $request->input(
                            'nursing'
                        ),

                        'birth_control' =>
                        $request->input(
                            'birth_control'
                        ),

                        'tobacco_use' =>
                        $tobaccoUse,

                        'headaches' =>
                        $request->input(
                            'headaches'
                        ),

                        'earaches' =>
                        $request->input(
                            'earaches'
                        ),

                        'neck_aches' =>
                        $request->input(
                            'neck_aches'
                        ),

                        'good_health_details' =>
                        $request->input(
                            'good_health_details'
                        ),

                        'treatment_details' =>
                        $request->input(
                            'treatment_details'
                        ),

                        'hospital_details' =>
                        $request->input(
                            'hospital_details'
                        ),

                        'allergy_others' =>
                        $request->input(
                            'allergy_others'
                        ),

                        'medication_details' =>
                        $request->input(
                            'medication_details'
                        ),

                        'tobacco_per_day' =>
                        $tobaccoUse === 'YES'
                            ? $request->input(
                                'tobacco_per_day'
                            )
                            : null,

                        'tobacco_per_week' =>
                        $tobaccoUse === 'YES'
                            ? $request->input(
                                'tobacco_per_week'
                            )
                            : null,

                        'medical_exam_date' =>
                        $request->input(
                            'medical_exam_date'
                        ),
                    ];

                    $questions =
                        MedicalHistoryQuestion::whereIn(
                            'code',
                            array_keys(
                                $medicalAnswerMap
                            )
                        )
                        ->get()
                        ->keyBy(
                            'code'
                        );

                    foreach (
                        $medicalAnswerMap
                        as $code => $rawValue
                    ) {
                        $question =
                            $questions->get(
                                $code
                            );

                        if (!$question) {
                            continue;
                        }

                        $criteria = [
                            'patient_id' =>
                            $patient->id,

                            'medical_history_id' =>
                            $medicalHistory->id,

                            'question_id' =>
                            $question->id,
                        ];

                        if (
                            $question->type ===
                            'bool'
                        ) {
                            MedicalHistoryAnswer::updateOrCreate(
                                $criteria,
                                [
                                    'answer_bool' =>
                                    $this->yesNoValue(
                                        $rawValue
                                    ) === 'YES',

                                    'answer_text' =>
                                    null,

                                    'answer_date' =>
                                    null,
                                ]
                            );

                            continue;
                        }

                        if (
                            $question->type ===
                            'date'
                        ) {
                            $dateValue =
                                trim(
                                    (string) $rawValue
                                );

                            if (
                                $dateValue === ''
                            ) {
                                MedicalHistoryAnswer::where(
                                    $criteria
                                )->delete();

                                continue;
                            }

                            MedicalHistoryAnswer::updateOrCreate(
                                $criteria,
                                [
                                    'answer_bool' =>
                                    null,

                                    'answer_text' =>
                                    null,

                                    'answer_date' =>
                                    $dateValue,
                                ]
                            );

                            continue;
                        }

                        $textValue =
                            trim(
                                (string) $rawValue
                            );

                        if (
                            $textValue === ''
                        ) {
                            MedicalHistoryAnswer::where(
                                $criteria
                            )->delete();

                            continue;
                        }

                        MedicalHistoryAnswer::updateOrCreate(
                            $criteria,
                            [
                                'answer_bool' =>
                                null,

                                'answer_text' =>
                                $textValue,

                                'answer_date' =>
                                null,
                            ]
                        );
                    }

                    $selectedDiseaseCodes =
                        $request->input(
                            'diseases',
                            []
                        );

                    $selectedDiseaseIds =
                        Disease::whereIn(
                            'code',
                            $selectedDiseaseCodes
                        )
                        ->pluck(
                            'id'
                        )
                        ->all();

                    MedicalHistoryDiseaseAnswer::where(
                        'medical_history_id',
                        $medicalHistory->id
                    )->delete();

                    foreach (
                        $selectedDiseaseIds
                        as $diseaseId
                    ) {
                        MedicalHistoryDiseaseAnswer::create([
                            'patient_id' =>
                            $patient->id,

                            'medical_history_id' =>
                            $medicalHistory->id,

                            'disease_id' =>
                            $diseaseId,

                            'has_disease' =>
                            true,
                        ]);
                    }
                }
            );
        } catch (\Throwable $error) {
            Log::error(
                'Walk-in appointment creation failed',
                [
                    'message' =>
                    $error->getMessage(),

                    'file' =>
                    $error->getFile(),

                    'line' =>
                    $error->getLine(),

                    'patient_id' =>
                    $patient->id,
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,

                    'message' =>
                    config('app.debug')
                        ? $error->getMessage()
                        : 'Unable to create the walk-in appointment.',
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Unable to create the walk-in appointment.'
                );
        }

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' =>
                'The walk-in appointment could not be created.',
            ], 500);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,

                'message' =>
                'The walk-in appointment was recorded successfully.',

                'appointment_id' =>
                $appointment->id,

                'start_url' =>
                route(
                    'dentist.odontogram',
                    [
                        'appointment' =>
                        $appointment->id,
                    ]
                ) .
                    '?from=walk-in&start_procedure=1',
            ]);
        }

        return redirect()
            ->route(
                'dentist.odontogram',
                [
                    'appointment' =>
                    $appointment->id,
                ]
            )
            ->with(
                'success',
                'The walk-in appointment was recorded successfully.'
            );
    }

    private function syncWalkInUser(array $data): User
    {
        $email = strtolower(
            trim(
                (string) ($data['email'] ?? '')
            )
        );

        $user = User::where(
            'email',
            $email
        )->first();

        $userData = [];

        if (
            Schema::hasColumn('users', 'name') &&
            filled($data['name'] ?? null)
        ) {
            $userData['name'] =
                trim((string) $data['name']);
        }

        if (
            Schema::hasColumn('users', 'first_name') &&
            array_key_exists('first_name', $data)
        ) {
            $userData['first_name'] =
                filled($data['first_name'])
                ? trim((string) $data['first_name'])
                : null;
        }

        if (
            Schema::hasColumn('users', 'middle_name') &&
            array_key_exists('middle_name', $data)
        ) {
            $userData['middle_name'] =
                filled($data['middle_name'])
                ? trim((string) $data['middle_name'])
                : null;
        }

        if (
            Schema::hasColumn('users', 'last_name') &&
            array_key_exists('last_name', $data)
        ) {
            $userData['last_name'] =
                filled($data['last_name'])
                ? trim((string) $data['last_name'])
                : null;
        }

        if (
            Schema::hasColumn('users', 'suffix_name') &&
            array_key_exists('suffix_name', $data)
        ) {
            $userData['suffix_name'] =
                filled($data['suffix_name'])
                ? trim((string) $data['suffix_name'])
                : null;
        }

        if (Schema::hasColumn('users', 'role_id')) {
            $patientRoleId = Role::query()
                ->where('slug', 'patient')
                ->value('id');

            if ($patientRoleId) {
                $userData['role_id'] = $patientRoleId;
            }
        }

        if (!$user) {
            $user = new User();

            $userData['email'] =
                $email;

            $userData['password'] =
                bcrypt(
                    Str::random(32)
                );

            if (
                Schema::hasColumn(
                    'users',
                    'role'
                )
            ) {
                $userData['role'] =
                    'patient';
            }

            if (
                Schema::hasColumn(
                    'users',
                    'user_type'
                )
            ) {
                $userData['user_type'] =
                    'patient';
            }

            if (
                Schema::hasColumn(
                    'users',
                    'status'
                )
            ) {
                $userData['status'] =
                    'active';
            }

            $user->forceFill(
                $userData
            );

            $user->save();

            return $user;
        }

        if (!empty($userData)) {
            $user->forceFill(
                $userData
            );

            $user->save();
        }

        return $user;
    }

    private function syncWalkInPatient(
        User $user,
        array $data,
        string $type
    ): Patient {
        $patient = Patient::with([
            'medicalHistory',
            'information',
        ])
            ->where('user_id', $user->id)
            ->first();

        $normalizedEmail = strtolower(
            trim((string) ($data['email'] ?? ''))
        );

        $studentNumber = trim(
            (string) ($data['student_number'] ?? '')
        );

        $facultyCode = trim(
            (string) ($data['faculty_code'] ?? '')
        );



        if (
            ! $patient &&
            $normalizedEmail !== ''
        ) {
            $patient = Patient::with([
                'medicalHistory',
                'information',
            ])
                ->whereRaw(
                    'LOWER(email) = ?',
                    [$normalizedEmail]
                )
                ->first();
        }

        if (
            ! $patient &&
            $studentNumber !== ''
        ) {
            $patient = Patient::with([
                'medicalHistory',
                'information',
                'studentInformation',
                'facultyInformation',
            ])
                ->whereHas(
                    'studentInformation',
                    function ($query) use ($studentNumber) {
                        $query->where(
                            'student_no',
                            $studentNumber
                        );
                    }
                )
                ->first();
        }

        if (
            ! $patient &&
            $facultyCode !== ''
        ) {
            $patient = Patient::with([
                'medicalHistory',
                'information',
                'studentInformation',
                'facultyInformation',
            ])
                ->whereHas(
                    'facultyInformation',
                    function ($query) use ($facultyCode) {
                        $query->where(
                            'faculty_code',
                            $facultyCode
                        );
                    }
                )
                ->first();
        }

        if ($patient) {
            $patient =
                $this->resolveWalkInSourcePatient(
                    $patient
                );
        }


        $patientData = [
            'user_id' => $user->id,
        ];

        if (
            array_key_exists('name', $data) &&
            filled($data['name'])
        ) {
            $patientData['name'] =
                trim((string) $data['name']);
        }

        if (
            array_key_exists('email', $data) &&
            filled($data['email'])
        ) {
            $patientData['email'] =
                strtolower(
                    trim((string) $data['email'])
                );
        }



        if (
            array_key_exists('phone', $data) &&
            filled($data['phone'])
        ) {
            $patientData['phone'] =
                $data['phone'];
        }

        if (
            array_key_exists('gender', $data) &&
            filled($data['gender'])
        ) {
            $patientData['gender'] =
                $data['gender'];
        }

        if (
            array_key_exists('birthdate', $data) &&
            filled($data['birthdate'])
        ) {
            $patientData['birthdate'] =
                $data['birthdate'];
        }

        if (
            array_key_exists('year_level', $data) &&
            filled($data['year_level'])
        ) {
            $patientData['year_level'] =
                $data['year_level'];
        }

        if (
            array_key_exists('section', $data) &&
            filled($data['section'])
        ) {
            $patientData['section'] =
                $data['section'];
        }

        if (
            array_key_exists('is_pwd', $data) &&
            $data['is_pwd'] !== null
        ) {
            $patientData['is_pwd'] =
                (bool) $data['is_pwd'];
        }

        if (
            array_key_exists('program', $data) &&
            filled($data['program'])
        ) {

            $patientData['course_code'] =
                $data['program'];

            $patientData['course_name'] =
                $data['program'];
        }

        if (
            array_key_exists(
                'faculty_code',
                $data
            )
        ) {
            $value = trim(
                (string) (
                    $data['faculty_code']
                    ?? ''
                )
            );

            $patientData['faculty_code'] =
                $value !== ''
                ? $value
                : null;
        }

        if (
            array_key_exists(
                'student_number',
                $data
            )
        ) {
            $value = trim(
                (string) (
                    $data['student_number']
                    ?? ''
                )
            );

            $patientData['student_no'] =
                $value !== ''
                ? $value
                : null;
        }

        $incomingClassification = trim(
            (string) (
                $data['classification']
                ?? ''
            )
        );

        if ($studentNumber !== '') {
            $patientData['classification'] =
                'student';
        } elseif ($facultyCode !== '') {
            $patientData['classification'] =
                'faculty';
        } elseif (
            in_array(
                $incomingClassification,
                [
                    'student',
                    'faculty',
                    'administrative',
                    'alumni',
                    'dependent',
                    'dependent_alumni',
                ],
                true
            )
        ) {
            $patientData['classification'] =
                $incomingClassification;
        } else {
            $patientData['classification'] =
                $this->normalizePatientClassification(
                    $type
                );
        }

        if (
            Schema::hasColumn(
                'patients',
                'status'
            )
        ) {
            $patientData['status'] =
                'active';
        }

        if (
            Schema::hasColumn(
                'patients',
                'role'
            )
        ) {
            $patientData['role'] =
                'patient';
        }

        /*
    |--------------------------------------------------------------------------
    | Create patient
    |--------------------------------------------------------------------------
    */

        if (! $patient) {
            if (
                Schema::hasColumn(
                    'patients',
                    'password'
                )
            ) {
                $patientData['password'] =
                    bcrypt(
                        Str::random(32)
                    );
            }

            $patient = new Patient();

            $patient->forceFill(
                $patientData
            );

            $patient->save();

            return $patient->loadMissing(
                'information'
            );
        }


        if (empty($patient->user_id)) {
            $patientData['user_id'] =
                $user->id;
        }

        if (
            Schema::hasColumn(
                'patients',
                'password'
            ) &&
            empty($patient->password)
        ) {
            $patientData['password'] =
                bcrypt(
                    Str::random(32)
                );
        }

        $patient->forceFill(
            $patientData
        );

        $patient->save();

        $patient->unsetRelation(
            'information'
        );

        return $patient->loadMissing(
            'information'
        );
    }

    private function getPatientAvatarUrl(
        Patient $patient,
        ?string $displayName = null
    ): ?string {
        $patientAvatar = '';

        if (
            Schema::hasColumn(
                $patient->getTable(),
                'profile_image'
            )
        ) {
            $patientAvatar = trim(
                (string) (
                    $patient->getAttribute(
                        'profile_image'
                    ) ?? ''
                )
            );
        }

        $userAvatar = '';

        if (
            $patient->user &&
            Schema::hasColumn(
                $patient->user->getTable(),
                'profile_image'
            )
        ) {
            $userAvatar = trim(
                (string) (
                    $patient->user->getAttribute(
                        'profile_image'
                    ) ?? ''
                )
            );
        }

        foreach (
            [
                $patientAvatar,
                $userAvatar,
            ] as $avatar
        ) {
            if ($avatar === '') {
                continue;
            }

            $path = ltrim(
                str_replace(
                    'storage/',
                    '',
                    $avatar
                ),
                '/'
            );

            if (
                Storage::disk('public')->exists(
                    $path
                )
            ) {
                return Storage::disk('public')->url(
                    $path
                );
            }
        }

        return null;
    }

    private function yesNoValue($value): string
    {
        $normalized =
            strtoupper(
                trim(
                    (string) $value
                )
            );

        return $normalized === 'YES'
            ? 'YES'
            : 'NO';
    }

    private function normalizeGuestPatientType(string $guestPatientType): string
    {
        return match (strtolower(trim($guestPatientType))) {
            'student' => 'Student',
            'faculty' => 'Faculty',
            'alumni' => 'Alumni',
            'dependent' => 'Dependent',
            'administrative' => 'Administrative Personnel',
            default => 'Guest',
        };
    }

    private function normalizeNameSuffix(?string $suffix): ?string
    {
        $suffix = trim((string) $suffix);

        if ($suffix === '') {
            return null;
        }

        return preg_match(
            '/^(ii|iii|iv|v|vi|vii|viii|ix|x)\.?$/i',
            $suffix
        ) ? strtoupper($suffix) : $suffix;
    }

    private function guestClassification(
        string $guestPatientType
    ): string {
        return match (strtolower(trim($guestPatientType))) {
            'student' =>
            'student',

            'faculty' =>
            'faculty',

            'administrative' =>
            'administrative',

            'dependent' =>
            'dependent',

            'alumni' =>
            'alumni',

            default =>
            'dependent',
        };
    }

    private function normalizePatientClassification(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return match (true) {
            str_contains($type, 'student') =>
            'student',

            str_contains($type, 'faculty') =>
            'faculty',

            str_contains($type, 'administrative'),
            str_contains($type, 'admin'),
            str_contains($type, 'personnel') =>
            'administrative',

            str_contains($type, 'dependent') =>
            'dependent',

            str_contains($type, 'alumni') =>
            'alumni',

            str_contains($type, 'guest') =>
            'dependent',

            default =>
            'dependent',
        };
    }
}
