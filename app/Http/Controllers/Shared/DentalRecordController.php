<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class DentalRecordController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', 'all'));
        $status = in_array($status, ['all', 'scheduled', 'completed'], true) ? $status : 'all';
        $classification = trim((string) $request->input('classification', 'all'));
        $sort = trim((string) $request->input('sort', 'newest'));
        $datePreset = trim((string) $request->input('date_preset', 'all'));
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));

        $perPageInput = (int) $request->input('per_page', 10);
        $perPage = in_array($perPageInput, [10, 20, 50, 100], true) ? $perPageInput : 10;

        $statsCollection = $this->loadRecordAppointments(
            $this->applyRecordFilters(
                $this->buildRecordAppointmentsQuery(),
                '',
                'all',
                'all',
                'newest',
                'all',
                '',
                ''
            )
        );

        $totalRecords = $statsCollection->count();
        $recordsToday = $statsCollection->filter(fn($record) => $record->date_iso === Carbon::today()->toDateString())->count();
        $scheduledCount = $statsCollection->where('status', 'scheduled')->count();
        $completedCount = $statsCollection->where('status', 'completed')->count();
        $topProcedure = $statsCollection
            ->pluck('procedure')
            ->filter(fn($procedure) => filled($procedure) && $procedure !== 'Dental Record')
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first() ?? 'No data yet';

        $completedThisWeek = $statsCollection
            ->filter(function ($record) {
                if (
                    $record->status !== 'completed' ||
                    empty($record->completed_date_iso)
                ) {
                    return false;
                }

                $date = Carbon::parse(
                    $record->completed_date_iso
                );

                return $date->betweenIncluded(
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                );
            })
            ->count();
        $patientsForFollowUp = $statsCollection->filter(fn($record) => $record->has_follow_up)->count();

        $recordsPaginator = $this->applyRecordFilters(
            $this->buildRecordAppointmentsQuery(),
            $search,
            $status,
            $classification,
            $sort,
            $datePreset,
            $dateFrom,
            $dateTo
        )->paginate($perPage)->withQueryString();

        $recordItems = $this->summarizeAppointments(
            $recordsPaginator->getCollection()
        );
        $recordsPaginator->setCollection($recordItems);
        $records = $recordsPaginator;

        $viewData = compact(
            'totalRecords',
            'recordsToday',
            'scheduledCount',
            'completedCount',
            'records',
            'topProcedure',
            'completedThisWeek',
            'patientsForFollowUp'
        );

        $isPaginator = $records instanceof AbstractPaginator;

        $pagination = [
            'current_page' => $isPaginator ? $records->currentPage() : 1,
            'last_page' => $isPaginator ? $records->lastPage() : 1,
            'total' => $isPaginator ? $records->total() : $records->count(),
            'from' => $isPaginator ? ($records->firstItem() ?? 0) : 0,
            'to' => $isPaginator ? ($records->lastItem() ?? 0) : $records->count(),
            'per_page' => $isPaginator ? $records->perPage() : $perPage,
        ];

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'html' => view('shared.dental-records', $viewData + [
                    'layoutRole' => $this->resolveLayoutRole(),
                ])->render(),
                'pagination' => $pagination,
                'counts' => [
                    'all' => $totalRecords,
                    'today' => $recordsToday,
                    'scheduled' => $scheduledCount,
                    'completed' => $completedCount,
                ],
            ]);
        }

        return view('shared.dental-records', $viewData + [
            'layoutRole' => $this->resolveLayoutRole(),
        ]);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if (! in_array(
            strtolower(trim((string) $appointment->status)),
            ['upcoming', 'rescheduled', 'completed'],
            true
        )) {
            abort(404);
        }

        $appointment->load([
            'serviceType',
            'dentist:id,name',
            'procedure',

            'followUpAppointments' => function ($query) {
                $query
                    ->whereNotIn('status', [
                        'cancelled',
                        'canceled',
                    ])
                    ->orderBy('appointment_date', 'asc')
                    ->orderBy('appointment_time', 'asc');
            },

            'patient.information',
            'patient.studentInformation',
            'patient.facultyInformation',
            'patient.medicalHistory.answers.question',
            'patient.medicalHistory.diseaseAnswers.disease',
            'patient.dentalHistory',
            'patient.dentalHistoryDates',
            'patient.dentalHistoryConcerns',
            'patient.dentalHistoryAnswers.condition',
            'patient.odontogram',
        ]);

        if (! $appointment->patient) {
            abort(404);
        }

        $record = $this->buildAppointmentRecordSummary(
            $appointment
        );

        if (
            $request->ajax() ||
            $request->expectsJson()
        ) {
            return response()->json(
                $record
            );
        }

        $profileRouteName =
            $this->resolveLayoutRole() === 'dentist'
            ? 'dentist.dentist.patient.profile'
            : 'admin.admin.patient.profile';

        return redirect()->route(
            $profileRouteName,
            [
                'patient' =>
                $appointment->patient_id,

                'from' =>
                'patients',
            ]
        );
    }

    private function buildRecordAppointmentsQuery(): Builder
    {
        return Appointment::query()
            ->whereIn('status', [
                'upcoming',
                'rescheduled',
                'completed',
            ])
            ->whereHas('patient')
            ->with([
                'serviceType',
                'dentist:id,name',
                'procedure',

                'followUpAppointments' => function ($query) {
                    $query
                        ->whereNotIn('status', [
                            'cancelled',
                            'canceled',
                        ])
                        ->orderBy('appointment_date', 'asc')
                        ->orderBy('appointment_time', 'asc');
                },

                'patient.information',
                'patient.studentInformation',
                'patient.facultyInformation',
                'patient.medicalHistory.answers.question',
                'patient.medicalHistory.diseaseAnswers.disease',
                'patient.dentalHistory',
                'patient.dentalHistoryDates',
                'patient.dentalHistoryConcerns',
                'patient.dentalHistoryAnswers.condition',
                'patient.odontogram',
            ]);
    }

    private function applyRecordFilters(
        Builder $query,
        string $search,
        string $status,
        string $classification,
        string $sort,
        string $datePreset,
        string $dateFrom,
        string $dateTo
    ): Builder {
        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery
                    ->whereHas('serviceType', function (Builder $serviceTypeQuery) use ($search) {
                        $serviceTypeQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('patient', function (Builder $patientQuery) use ($search) {
                        $patientQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('information', function (Builder $informationQuery) use ($search) {
                                $informationQuery->where(
                                    'phone',
                                    'like',
                                    "%{$search}%"
                                );
                            });
                    })
                    ->orWhereHas('dentist', function (Builder $dentistQuery) use ($search) {
                        $dentistQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($classification !== 'all') {
            $query->whereHas('patient', function (Builder $patientQuery) use ($classification) {
                $patientQuery->where(
                    'classification',
                    $classification
                );
            });
        }

        if ($status !== 'all') {
            if ($status === 'scheduled') {
                $query->whereIn('status', [
                    'upcoming',
                    'rescheduled',
                ]);
            } elseif ($status === 'completed') {
                $query->where(
                    'status',
                    'completed'
                );
            }
        }

        if ($dateFrom !== '') {
            $query->whereDate(
                'appointment_date',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo !== '') {
            $query->whereDate(
                'appointment_date',
                '<=',
                $dateTo
            );
        }

        if (
            $dateFrom === '' &&
            $dateTo === '' &&
            $datePreset !== 'all'
        ) {
            $startDate = match ($datePreset) {
                'today' => Carbon::today(),

                '7' =>
                Carbon::today()
                    ->subDays(6),

                '30' =>
                Carbon::today()
                    ->subDays(29),

                '90' =>
                Carbon::today()
                    ->subDays(89),

                '180' =>
                Carbon::today()
                    ->subDays(179),

                '365' =>
                Carbon::today()
                    ->subDays(364),

                default => null,
            };

            if ($startDate) {
                $query
                    ->whereDate(
                        'appointment_date',
                        '>=',
                        $startDate
                    )
                    ->whereDate(
                        'appointment_date',
                        '<=',
                        Carbon::today()
                    );
            }
        }

        if ($sort === 'name_asc') {
            $query
                ->orderBy(
                    Patient::select('name')
                        ->whereColumn(
                            'patients.id',
                            'appointments.patient_id'
                        ),
                    'asc'
                )
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time');
        } elseif ($sort === 'name_desc') {
            $query
                ->orderBy(
                    Patient::select('name')
                        ->whereColumn(
                            'patients.id',
                            'appointments.patient_id'
                        ),
                    'desc'
                )
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time');
        } elseif ($sort === 'oldest') {
            $query
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc')
                ->orderBy('id', 'asc');
        } else {
            $query
                ->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc')
                ->orderBy('id', 'desc');
        }

        return $query;
    }

    private function loadRecordAppointments(Builder $query): Collection
    {
        return $this->summarizeAppointments(
            $query->get()
        );
    }

    private function summarizeAppointments(
        Collection $appointments
    ): Collection {
        return $appointments
            ->map(
                fn(Appointment $appointment) =>
                $this->buildAppointmentRecordSummary(
                    $appointment
                )
            )
            ->values();
    }

    private function buildAppointmentRecordSummary(
        Appointment $appointment
    ): object {
        $patient = $appointment->patient;
        $procedure = $appointment->procedure;

        $followUpAppointment = $appointment
            ->followUpAppointments
            ->sortBy(function ($followUp) {
                return trim(
                    (string) $followUp->appointment_date
                        . ' '
                        . (string) $followUp->appointment_time
                );
            })
            ->first();

        $appointmentDateTime = null;

        if ($appointment->appointment_date) {
            $appointmentDateTime = Carbon::parse(
                trim(
                    (string) $appointment->appointment_date
                        . ' '
                        . (string) (
                            $appointment->appointment_time
                            ?: '00:00:00'
                        )
                )
            );
        }

        $appointmentStatus = strtolower(
            trim((string) $appointment->status)
        );

        $status = $appointmentStatus === 'completed'
            ? 'completed'
            : 'scheduled';

        return (object) [
            'id' => $appointment->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->name ?: 'Unknown Patient',

            'procedure' => filled($appointment->service_type_name)
                ? $appointment->service_type_name
                : 'No service recorded',

            'dentist_name' => $appointment->dentist?->name ?: '—',
            'status' => $status,

            'date' => $appointmentDateTime
                ? $appointmentDateTime->format('M d, Y')
                : '—',

            'date_iso' => $appointmentDateTime
                ? $appointmentDateTime->toDateString()
                : '',

            'date_sort' => $appointmentDateTime
                ? $appointmentDateTime->format('Y-m-d H:i:s')
                : '',

            'created_date_iso' => $appointment->created_at
                ? $appointment->created_at->toDateString()
                : '',

            'completed_date_iso' => $procedure?->procedure_completed_at
                ? $procedure->procedure_completed_at->toDateString()
                : '',

            'time' => $appointment->appointment_time
                ? Carbon::parse($appointment->appointment_time)->format('g:i A')
                : '',

            'notes' => $this->buildTreatmentSummary($procedure),
            'visit_count' => 1,
            'services_count' => filled($appointment->service_type_name) ? 1 : 0,
            'profile_fields' => $this->buildPatientProfileFields($patient),

            'emergency_contact' => [
                'name' => $patient->medicalHistory?->emergency_person ?: 'N/A',
                'number' => $patient->medicalHistory?->emergency_number ?: 'N/A',
                'relation' => $patient->medicalHistory?->emergency_relation ?: 'N/A',
            ],

            'dental_history_summary' => $this->buildDentalHistorySummary($patient),
            'medical_history_summary' => $this->buildMedicalHistorySummary($patient),
            'dental_history_fields' => $this->buildDentalHistoryFields($patient),
            'dental_symptoms' => $this->buildDentalSymptoms($patient),
            'medical_history_fields' => $this->buildMedicalHistoryFields($patient),
            'medical_conditions' => $this->buildMedicalConditions($patient),
            'record_sections' => $this->buildRecordSections(
                $patient,
                $appointment,
                $procedure,
                $followUpAppointment
            ),
            'follow_up_summary' => $this->buildFollowUpSummary($followUpAppointment),
            'follow_up' => $followUpAppointment ? [
                'date' => $followUpAppointment->appointment_date
                    ? Carbon::parse($followUpAppointment->appointment_date)->format('d M Y')
                    : null,
                'time' => $followUpAppointment->appointment_time
                    ? Carbon::parse($followUpAppointment->appointment_time)->format('g:i A')
                    : null,
                'service' => $followUpAppointment->service_type_name ?: 'Follow-up',
                'status' => $followUpAppointment->status ?: 'upcoming',
                'reason' => $followUpAppointment->follow_up_reason,
            ] : null,
            'has_follow_up' => (bool) $followUpAppointment,
            'oral' => $procedure?->oral_examination ?: 'No oral examination record yet.',
            'diagnosis' => $procedure?->diagnosis ?: 'No diagnosis record yet.',
            'prescription' => $procedure?->prescriptions ?: 'No prescription recorded.',
            'odontogram_data' => $procedure?->odontogram_data ?: [],
            'full_record_url' => route(
                $this->resolveLayoutRole() === 'dentist'
                    ? 'dentist.dentist.patient.profile'
                    : 'admin.admin.patient.profile',
                [
                    'patient' => $patient->id,
                    'from' => 'patients',
                ]
            ),
        ];
    }

    private function normalizeRecordStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'completed' => 'completed',

            'upcoming',
            'rescheduled' => 'scheduled',

            'cancelled',
            'canceled' => 'cancelled',

            default => 'scheduled',
        };
    }

    private function buildTreatmentSummary($latestProcedure): string
    {
        if (! $latestProcedure) {
            return 'No treatment recorded.';
        }

        $odontogramData = $latestProcedure->odontogram_data ?? [];

        if (empty($odontogramData) || ! is_array($odontogramData)) {
            return 'No treatment recorded.';
        }

        $treatments = collect($odontogramData)
            ->flatMap(function ($entry) {
                $labels = collect([
                    data_get($entry, 'status.label'),
                    data_get($entry, 'threeD.label'),
                ]);

                foreach ((array) data_get($entry, 'surfaces', []) as $surface) {
                    $labels->push(
                        data_get($surface, 'label')
                    );
                }

                return $labels;
            })
            ->filter(fn($label) => filled($label))
            ->unique()
            ->values();

        return $treatments->isNotEmpty()
            ? $treatments->implode(', ')
            : 'No treatment recorded.';
    }

    private function buildDentalHistorySummary(Patient $patient): string
    {
        $parts = collect($this->buildDentalHistoryFields($patient))
            ->map(fn($item) => ($item['label'] ?? 'Item') . ': ' . ($item['value'] ?? 'N/A'))
            ->values();

        $symptoms = collect($this->buildDentalSymptoms($patient));

        if ($symptoms->isNotEmpty()) {
            $parts->push('Reported: ' . $symptoms->implode(', '));
        }

        return $parts->filter()->implode("\n") ?: 'No dental history recorded yet.';
    }

    private function buildMedicalHistorySummary(Patient $patient): string
    {
        $parts = collect($this->buildMedicalHistoryFields($patient))
            ->map(fn($item) => ($item['label'] ?? 'Item') . ': ' . ($item['value'] ?? 'N/A'))
            ->values();

        $conditions = collect($this->buildMedicalConditions($patient));

        if ($conditions->isNotEmpty()) {
            $parts->push('Conditions: ' . $conditions->implode(', '));
        }

        return $parts->implode("\n") ?: 'No medical history recorded yet.';
    }

    private function buildPatientProfileFields(Patient $patient): array
    {
        $program = trim((string) ($patient->course_code ?? ''));

        if ($program === '') {
            $program = filled($patient->classification)
                ? $this->formatHistoryLabel($patient->classification)
                : 'No program';
        }

        $yearSection = collect([
            filled($patient->year_level) ? $patient->year_level : null,
            filled($patient->section) ? $patient->section : null,
        ])->filter()->implode('-');

        $programYear = trim(
            $program . ($yearSection !== '' ? ' ' . $yearSection : '')
        );

        $identity = filled($patient->student_no)
            ? $patient->student_no
            : (
                filled($patient->faculty_code)
                ? 'Faculty: ' . $patient->faculty_code
                : 'No identity number'
            );

        $emergencyContact = collect([
            $patient->medicalHistory?->emergency_person,
            $patient->medicalHistory?->emergency_number,
            $patient->medicalHistory?->emergency_relation,
        ])->filter()->implode(' • ');

        $profileFields = [
            [
                'label' => 'Name',
                'value' => $patient->name ?: 'Unknown Patient',
                'icon' => 'fa-regular fa-user',
            ],

            [
                'label' => 'Program / Year',
                'value' => $programYear !== '' ? $programYear : 'No program',
                'icon' => 'fa-solid fa-graduation-cap',
            ],

            [
                'label' => 'Student No.',
                'value' => $identity,
                'icon' => 'fa-regular fa-id-badge',
            ],

            [
                'label' => 'Email',
                'value' => $patient->email ?: 'No email',
                'icon' => 'fa-regular fa-envelope',
            ],

            [
                'label' => 'Emergency Contact',
                'value' => $emergencyContact !== ''
                    ? $emergencyContact
                    : 'No emergency contact',
                'icon' => 'fa-solid fa-phone',
            ],
        ];

        if ($patient->is_pwd) {
            $profileFields[] = [
                'label' => 'PWD',
                'value' => '',
                'icon' => 'fa-solid fa-wheelchair',
            ];
        }

        return $profileFields;
    }

    private function buildDentalHistoryFields(Patient $patient): array
    {
        $fields = [];

        if ($patient->dentalHistory?->last_dental_visit) {
            $fields[] = [
                'label' => 'Last dental visit',
                'value' => Carbon::parse($patient->dentalHistory->last_dental_visit)->format('M d, Y'),
                'icon' => 'fa-regular fa-calendar-check',
            ];
        }

        if (filled($patient->dentalHistory?->previous_dentist)) {
            $fields[] = [
                'label' => 'Previous dentist',
                'value' => $patient->dentalHistory->previous_dentist,
                'icon' => 'fa-solid fa-user-doctor',
            ];
        }

        if ($patient->dentalHistoryDates?->extraction_date) {
            $fields[] = [
                'label' => 'Extraction date',
                'value' => Carbon::parse($patient->dentalHistoryDates->extraction_date)->format('M d, Y'),
                'icon' => 'fa-solid fa-tooth',
            ];
        }

        if ($patient->dentalHistoryDates?->dentures_date) {
            $fields[] = [
                'label' => 'Dentures date',
                'value' => Carbon::parse($patient->dentalHistoryDates->dentures_date)->format('M d, Y'),
                'icon' => 'fa-solid fa-calendar-days',
            ];
        }

        if ($patient->dentalHistoryDates?->ortho_date) {
            $fields[] = [
                'label' => 'Orthodontic treatment date',
                'value' => Carbon::parse($patient->dentalHistoryDates->ortho_date)->format('M d, Y'),
                'icon' => 'fa-regular fa-calendar',
            ];
        }

        if (filled($patient->dentalHistoryConcerns?->additional_concerns)) {
            $fields[] = [
                'label' => 'Additional concern',
                'value' => $patient->dentalHistoryConcerns->additional_concerns,
                'icon' => 'fa-regular fa-comment-dots',
            ];
        }

        return $fields;
    }

    private function buildDentalSymptoms(Patient $patient): array
    {
        return collect($patient->dentalHistoryAnswers ?? [])
            ->filter(fn($answer) => (bool) $answer->answer)
            ->map(fn($answer) => $this->formatHistoryLabel($answer->condition?->label ?: $answer->condition?->code))
            ->filter()
            ->values()
            ->all();
    }

    private function buildMedicalHistoryFields(Patient $patient): array
    {
        return collect($patient->medicalHistory?->answers ?? [])
            ->filter(function ($answer) {
                return $answer->answer_bool === true || filled($answer->answer_text) || filled($answer->answer_date);
            })
            ->map(function ($answer) {
                $label = $this->formatHistoryLabel(
                    $answer->question?->label ?: $answer->question?->code ?: 'Medical item'
                );

                $value = collect([
                    $answer->answer_bool === true ? 'Yes' : null,
                    $answer->answer_text,
                    $answer->answer_date ? Carbon::parse($answer->answer_date)->format('M d, Y') : null,
                ])->filter()->implode(' ');

                return [
                    'label' => $label,
                    'value' => $value !== '' ? $value : 'N/A',
                    'icon' => 'fa-solid fa-stethoscope',
                ];
            })
            ->values()
            ->all();
    }

    private function buildMedicalConditions(Patient $patient): array
    {
        return collect($patient->medicalHistory?->diseaseAnswers ?? [])
            ->map(
                fn($answer) => $this->formatHistoryLabel(
                    $answer->disease?->label
                )
            )
            ->filter()
            ->values()
            ->all();
    }

    private function buildRecordSections(Patient $patient, $latestAppointment, $latestProcedure, $followUpAppointment): array
    {
        $sections = [];

        $appointmentRows = [
            ['label' => 'Service', 'value' => filled($latestAppointment?->service_type_name)
                ? $latestAppointment->service_type_name
                : 'No service recorded'],
            ['label' => 'Date', 'value' => $latestAppointment?->appointment_date ? Carbon::parse($latestAppointment->appointment_date)->format('F d, Y') : 'N/A'],
            ['label' => 'Time', 'value' => $latestAppointment?->appointment_time ? Carbon::parse($latestAppointment->appointment_time)->format('g:i A') : 'N/A'],
            ['label' => 'Duration', 'value' => $this->formatProcedureDurationForDisplay($latestProcedure?->procedure_duration_seconds)],
            ['label' => 'Status', 'value' => ucfirst($this->normalizeRecordStatus($latestAppointment?->status))],
            ['label' => 'Follow-up', 'value' => $this->buildFollowUpSummary($followUpAppointment),],
        ];

        $sections[] = [
            'title' => 'Appointment Details',
            'icon' => 'fa-calendar-check',
            'rows' => $appointmentRows,
        ];

        $dentalGroups = [
            [
                'title' => 'Basic Info',
                'rows' => array_values(array_filter([
                    [
                        'label' => 'Last Dental Visit',
                        'value' => $patient->dentalHistory?->last_dental_visit
                            ? Carbon::parse(
                                $patient->dentalHistory->last_dental_visit
                            )->format('F d, Y')
                            : 'N/A',
                    ],
                    [
                        'label' => 'Previous Dentist',
                        'value' => $patient->dentalHistory?->previous_dentist
                            ?: 'N/A',
                    ],
                ], fn($row) => true)),
            ],

            [
                'title' => 'Dental Symptoms & Procedures',
                'rows' => collect($patient->dentalHistoryAnswers ?? [])
                    ->map(function ($answer) {
                        return [
                            'label' => $this->formatHistoryLabel($answer->condition?->label ?: $answer->condition?->code ?: 'Dental item'),
                            'value' => $this->formatBooleanAnswer($answer->answer),
                        ];
                    })
                    ->values()
                    ->all(),
            ],
            [
                'title' => 'Procedure Dates',
                'rows' => [
                    ['label' => 'Extraction Date', 'value' => $patient->dentalHistoryDates?->extraction_date ? Carbon::parse($patient->dentalHistoryDates->extraction_date)->format('F d, Y') : 'N/A'],
                    ['label' => 'Dentures Date', 'value' => $patient->dentalHistoryDates?->dentures_date ? Carbon::parse($patient->dentalHistoryDates->dentures_date)->format('F d, Y') : 'N/A'],
                    ['label' => 'Orthodontic Treatment Date', 'value' => $patient->dentalHistoryDates?->ortho_date ? Carbon::parse($patient->dentalHistoryDates->ortho_date)->format('F d, Y') : 'N/A'],
                ],
            ],
            [
                'title' => 'Additional Dental Concerns',
                'rows' => [
                    ['label' => 'Concern', 'value' => $patient->dentalHistoryConcerns?->additional_concerns ?: 'N/A'],
                ],
            ],
        ];

        $sections[] = [
            'title' => 'Dental History',
            'icon' => 'fa-tooth',
            'groups' => $dentalGroups,
        ];

        $medicalAnswers = collect($patient->medicalHistory?->answers ?? []);
        $tobaccoUseAnswer = $medicalAnswers->first(fn($answer) => $answer->question?->code === 'tobacco_use');

        $tobaccoUse = $tobaccoUseAnswer?->answer_bool === true;
        $medicalAnswerRows = $medicalAnswers->reject(function ($answer) use ($tobaccoUse) {
            $code = $answer->question?->code;

            return ! $tobaccoUse
                && in_array($code, [
                    'tobacco_per_day',
                    'tobacco_per_week',
                ], true);
        })
            ->map(function ($answer) {
                $code = $answer->question?->code;

                if (in_array($code, [
                    'tobacco_per_day',
                    'tobacco_per_week',
                ], true)) {
                    $value = filled($answer->answer_text)
                        ? $answer->answer_text
                        : 'N/A';
                } else {
                    $value = collect([
                        $answer->answer_bool === true
                            ? 'Yes'
                            : ($answer->answer_bool === false ? 'No' : null),

                        filled($answer->answer_text)
                            ? $answer->answer_text
                            : null,

                        filled($answer->answer_date)
                            ? Carbon::parse($answer->answer_date)->format('F d, Y')
                            : null,
                    ])->filter()->implode(' | ');
                }

                return [
                    'label' => $this->formatHistoryLabel(
                        $answer->question?->label
                            ?: $answer->question?->code
                            ?: 'Medical item'
                    ),
                    'value' => $value !== '' ? $value : 'N/A',
                ];
            })
            ->values()
            ->all();

        $medicalConditionRows = collect($patient->medicalHistory?->diseaseAnswers ?? [])
            ->map(function ($answer) {
                return [
                    'label' => $this->formatHistoryLabel($answer->disease?->label ?: 'Condition'),
                    'value' => $this->formatBooleanAnswer($answer->has_disease),
                ];
            })
            ->values()
            ->all();

        $sections[] = [
            'title' => 'Medical History',
            'icon' => 'fa-heart-pulse',
            'groups' => [
                ['title' => 'Medical Answers', 'rows' => $medicalAnswerRows],
                ['title' => 'Medical Conditions', 'rows' => $medicalConditionRows],
            ],
        ];

        $sections[] = [
            'title' => 'Clinical Notes',
            'icon' => 'fa-file-medical',
            'rows' => [
                ['label' => 'Treatment', 'value' => $this->buildTreatmentSummary($latestProcedure)],
                ['label' => 'Oral Examination', 'value' => $latestProcedure?->oral_examination ?: 'N/A'],
                ['label' => 'Diagnosis', 'value' => $latestProcedure?->diagnosis ?: 'N/A'],
                ['label' => 'Prescription', 'value' => $latestProcedure?->prescriptions ?: 'N/A'],
            ],
        ];

        return $sections;
    }

    private function formatBooleanAnswer($value): string
    {
        if ($value === true || $value === 1 || $value === '1') {
            return 'Yes';
        }

        if ($value === false || $value === 0 || $value === '0') {
            return 'No';
        }

        return filled($value) ? (string) $value : 'N/A';
    }

    private function formatProcedureDurationForDisplay($seconds): string
    {
        $seconds = (int) $seconds;

        if ($seconds <= 0) {
            return 'N/A';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' hr' . ($hours === 1 ? '' : 's');
        }

        if ($minutes > 0) {
            $parts[] = $minutes . ' min' . ($minutes === 1 ? '' : 's');
        }

        if (empty($parts)) {
            $parts[] = $seconds . ' sec' . ($seconds === 1 ? '' : 's');
        }

        return implode(' ', $parts);
    }

    private function formatHistoryLabel(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?: $value;

        return ucfirst(strtolower($value));
    }

    private function buildFollowUpSummary($followUpAppointment): string
    {
        if (! $followUpAppointment) {
            return 'No follow-up appointment scheduled.';
        }

        $date = $followUpAppointment->appointment_date
            ? Carbon::parse($followUpAppointment->appointment_date)->format('F j, Y')
            : null;

        $time = $followUpAppointment->appointment_time
            ? Carbon::parse($followUpAppointment->appointment_time)->format('g:i A')
            : null;

        if ($date && $time) {
            return "{$date} at {$time}";
        }

        if ($date) {
            return $date;
        }

        if ($time) {
            return $time;
        }

        return 'Follow-up appointment scheduled.';
    }

    private function resolveLayoutRole(): string
    {
        return request()->routeIs('dentist.dental-records*') ? 'dentist' : 'admin';
    }
}
