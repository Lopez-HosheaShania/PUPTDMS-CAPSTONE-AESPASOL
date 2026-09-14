<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use App\Models\Appointment;
use App\Models\DentalHistory;
use App\Models\DentalHistoryCondition;
use App\Models\DentalHistoryAnswer;
use App\Models\DentalHistoryConditionDate;
use App\Models\DentalHistoryConcern;

use App\Models\MedicalHistory;
use App\Models\MedicalHistoryQuestion;
use App\Models\MedicalHistoryAnswer;
use App\Models\Disease;
use App\Models\MedicalHistoryDiseaseAnswer;

use App\Models\Patient;
use App\Models\User;
use App\Models\BlockedDate;
use App\Models\ClinicSchedule;
use App\Models\ServiceType;
use App\Helpers\PhilippineHolidays;
use App\Helpers\AuditLogger;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentRescheduledNotification;
use App\Notifications\SignatureReuploadRequiredNotification;
use App\Services\SignatureAiVerifier;
use App\Helpers\BookingQuestions;
use App\Models\AppointmentDraft;
use App\Models\PatientOdontogram;
use App\Models\ReservedBookingPeriod;
use App\Models\ReservedBookingPeriodSlot;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Mail\AppointmentConfirmationMail;
use App\Mail\AppointmentRescheduleMail;
use App\Services\StudentApiService;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly StudentApiService $studentApiService
    ) {}

    private function resolveBookableDentist(): ?User
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('role', fn($query) => $query->where('slug', 'dentist'))
            ->orderBy('id')
            ->first();
    }

    public function index()
    {
        $patientId = session('impersonated_patient_id') ?: session('patient_id');

        if (!$patientId) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient = Patient::findOrFail($patientId);

        $this->backfillStudentEmergencyContactIfNeeded($patient);

        $now = now();
        $today = $now->toDateString();
        $nowTime = $now->format('H:i:s');

        $appointments = Appointment::with([
            'dentist.role',
            'originalDentist.role',
            'procedure',
            'followUpAppointments',
            'reservedBookingPeriod',
        ])
            ->where('patient_id', $patientId)
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $futureVisits = Appointment::with([
            'dentist.role',
            'originalDentist.role',
            'procedure',
            'followUpAppointments',
            'reservedBookingPeriod',
        ])
            ->where('patient_id', $patientId)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereDate('appointment_date', '>', $today)
                    ->orWhere(function ($q2) use ($today, $nowTime) {
                        $q2->whereDate('appointment_date', '=', $today)
                            ->where(function ($sameDay) use ($nowTime) {
                                $sameDay->where(function ($regular) use ($nowTime) {
                                    $regular->regularBooking()
                                        ->whereTime('appointment_time', '>=', $nowTime);
                                })->orWhereHas('reservedBookingPeriod', function ($period) use ($nowTime) {
                                    $period->withTrashed()->withScheduleColumns()->whereTime('end_time', '>=', $nowTime);
                                });
                            });
                    });
            })
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $pastVisits = Appointment::with([
            'dentist.role',
            'originalDentist.role',
            'procedure',
            'followUpAppointments',
            'reservedBookingPeriod',
        ])
            ->where('patient_id', $patientId)
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereIn('status', ['completed', 'cancelled'])
                    ->orWhereDate('appointment_date', '<', $today)
                    ->orWhere(function ($q2) use ($today, $nowTime) {
                        $q2->whereDate('appointment_date', '=', $today)
                            ->where(function ($sameDay) use ($nowTime) {
                                $sameDay->where(function ($regular) use ($nowTime) {
                                    $regular->regularBooking()
                                        ->whereTime('appointment_time', '<', $nowTime);
                                })->orWhereHas('reservedBookingPeriod', function ($period) use ($nowTime) {
                                    $period->withTrashed()->withScheduleColumns()->whereTime('end_time', '<', $nowTime);
                                });
                            });
                    });
            })
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        $patient->load([
            'dentalHistory',
            'dentalHistoryDates',
            'dentalHistoryConcerns',
            'dentalHistoryAnswers.condition',
            'medicalHistory',
            'medicalHistory.answers.question',
            'medicalHistory.diseaseAnswers.disease',
        ]);

        $appointmentCountsPerDay = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->regularBooking()
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $unavailableDates = [];

        $philippineHolidays = PhilippineHolidays::recordsRange(1, 3);

        $odontogramTeeth =
            PatientOdontogram::where(
                'patient_id',
                $patient->id
            )
            ->value('odontogram_data')
            ?? [];

        $notifications = [];

        $chartStart = now()->startOfMonth()->subMonths(5)->toDateString();
        $chartEnd = now()->endOfMonth()->toDateString();

        $activityRows = Appointment::query()
            ->where('patient_id', $patientId)
            ->whereBetween('appointment_date', [$chartStart, $chartEnd])
            ->selectRaw("DATE_FORMAT(appointment_date, '%Y-%m') as ym")
            ->selectRaw("SUM(CASE WHEN TRIM(LOWER(COALESCE(status, ''))) = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN TRIM(LOWER(COALESCE(status, ''))) IN ('cancelled', 'declined') THEN 1 ELSE 0 END) as cancelled")
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $appointmentActivityChart = collect(range(5, 0))
            ->map(function ($offset) use ($activityRows) {
                $month = now()->startOfMonth()->subMonths($offset);
                $key = $month->format('Y-m');
                $row = $activityRows->get($key);

                return [
                    'key' => $key,
                    'label' => $month->format('M'),
                    'completed' => (int) ($row->completed ?? 0),
                    'cancelled' => (int) ($row->cancelled ?? 0),
                ];
            })
            ->values()
            ->all();

        AuditLogger::log(
            'view',
            'appointments',
            "Patient viewed appointment page"
        );

        $clinicDentist = \App\Models\User::with('role')
            ->whereHas('role', fn($q) => $q->where('slug', 'dentist'))
            ->first();

        return view('patient.appointment', compact(
            'appointments',
            'clinicDentist',
            'futureVisits',
            'pastVisits',
            'patient',
            'appointmentCountsPerDay',
            'unavailableDates',
            'philippineHolidays',
            'notifications',
            'odontogramTeeth',
            'appointmentActivityChart'
        ));
    }


    public function create(
        Request $request,
        ?ReservedBookingPeriod $reservedBookingPeriod = null
    ) {
        $patientId = session('impersonated_patient_id') ?: session('patient_id');

        if (!$patientId) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient = Patient::findOrFail($patientId);
        $availableReservedSlots = collect();

        if ($reservedBookingPeriod) {
            $reservedBookingPeriod->load(['slots.appointment']);

            if (! $this->reservedPeriodIsBookableBy($reservedBookingPeriod, $patient)) {
                return redirect()->route('homepage')->with(
                    'error',
                    'This reserved booking invitation is unavailable or is not assigned to your patient group.'
                );
            }

            if ($reservedBookingPeriod->appointments()->where('patient_id', $patient->id)->exists()) {
                return redirect()->route('patient.appointment.index')->with(
                    'info',
                    'You already booked an appointment for this reserved period.'
                );
            }

            if ($this->reservedPeriodRemainingCapacity($reservedBookingPeriod) < 1) {
                return redirect()->route('homepage')->with(
                    'error',
                    'This reserved booking period is already full.'
                );
            }

            if ($reservedBookingPeriod->booking_mode === 'timeslot') {
                $availableReservedSlots = $reservedBookingPeriod->slots
                    ->filter(fn(ReservedBookingPeriodSlot $slot) => ! $slot->appointment
                        || $slot->appointment->status === 'cancelled')
                    ->filter(function (ReservedBookingPeriodSlot $slot) use ($reservedBookingPeriod) {
                        if (! $reservedBookingPeriod->reserved_date->isToday()) {
                            return true;
                        }

                        return Carbon::parse(
                            $reservedBookingPeriod->reserved_date->format('Y-m-d') . ' ' . $slot->slot_time
                        )->isFuture();
                    })
                    ->values();

                if ($availableReservedSlots->isEmpty()) {
                    return redirect()->route('homepage')->with(
                        'error',
                        'All selectable timeslots for this reserved period are already booked.'
                    );
                }
            }
        }

        $patient->load([
            'dentalHistory',
            'dentalHistoryDates',
            'dentalHistoryConcerns',
            'dentalHistoryAnswers.condition',
            'medicalHistory.answers.question',
            'medicalHistory.diseaseAnswers.disease',
        ]);

        $dentalDefaults = [
            'last_dental_visit' => $patient->dentalHistory?->last_dental_visit
                ? $patient->dentalHistory->last_dental_visit->format('Y-m-d')
                : '',

            'previous_dentist' => $patient->dentalHistory?->previous_dentist ?? '',

            'additional_concerns' =>
            $patient->dentalHistoryConcerns?->additional_concerns ?? '',

            'extraction_date' => $patient->dentalHistoryDates?->extraction_date
                ? $patient->dentalHistoryDates->extraction_date->format('Y-m-d')
                : '',

            'dentures_date' => $patient->dentalHistoryDates?->dentures_date
                ? $patient->dentalHistoryDates->dentures_date->format('Y-m-d')
                : '',

            'ortho_date' => $patient->dentalHistoryDates?->ortho_date
                ? $patient->dentalHistoryDates->ortho_date->format('Y-m-d')
                : '',
        ];

        foreach ($patient->dentalHistoryAnswers as $answer) {
            $code = $answer->condition?->code;

            if (!$code) {
                continue;
            }

            $dentalDefaults[$code] = $answer->answer ? 'YES' : 'NO';
        }

        $medicalDefaults = [];

        if ($patient->medicalHistory) {
            $medicalDefaults = [
                'emergency_person' =>
                $patient->medicalHistory->emergency_person ?? '',

                'emergency_number' =>
                $patient->medicalHistory->emergency_number ?? '',

                'emergency_relation' =>
                $patient->medicalHistory->emergency_relation ?? '',
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
                        $answer->answer_date->format('Y-m-d');
                }
            }
        }

        $selectedDiseases = $patient->medicalHistory
            ? $patient->medicalHistory
            ->diseaseAnswers
            ->filter(fn($answer) => $answer->has_disease)
            ->map(fn($answer) => $answer->disease?->code)
            ->filter()
            ->values()
            ->all()
            : [];

        $hasExistingDentalHistory =
            $this->hasExistingDentalHistoryRecord($patient);

        $hasExistingMedicalHistory =
            $this->hasExistingMedicalHistoryRecord($patient);

        $hasExistingBookingInformation =
            $hasExistingDentalHistory &&
            $hasExistingMedicalHistory;

        $hasReusableSignature =
            !empty($patient->medicalHistory?->patient_signature) &&
            $patient->medicalHistory?->signature_review_status !==
            'invalid_reupload_required';


        // DO NOT REMOVE
        $hasActiveAppointment = Appointment::where('patient_id', $patientId)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->exists();

        if ($hasActiveAppointment && ! $reservedBookingPeriod) {
            return redirect()->back()->with([
                'activeAppointmentModal' => true,
                'activeAppointmentMsg' =>
                "You already have an active appointment. Please wait until it is completed before booking another one."
            ]);
        }

        $appointmentCountsPerDay = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->regularBooking()
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $appointmentCountsPerSlot = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->regularBooking()
            ->selectRaw('appointment_date, appointment_time, COUNT(*) as count')
            ->groupBy('appointment_date', 'appointment_time')
            ->get()
            ->groupBy('appointment_date')
            ->map(function ($rows) {
                return $rows->pluck('count', 'appointment_time')->toArray();
            })
            ->toArray();

        $schedules = ClinicSchedule::active()->orderBy('id')->get();

        $blockedDates = BlockedDate::pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $philippineHolidays = PhilippineHolidays::recordsRange(0, 1);

        $diseases = Disease::orderBy('sort_order')->get();

        $serviceTypes = ServiceType::where('is_active_for_booking', true)
            ->when(
                $reservedBookingPeriod && $reservedBookingPeriod->allowed_services !== null,
                fn ($query) => $query->whereIn('name', $reservedBookingPeriod->allowed_services)
            )
            ->orderBy('name')
            ->get()
            ->map(function ($service) {
                return [
                    'name' => $service->name,
                    'desc' => $service->description ?: 'No description available.',
                    'img'  => null,
                ];
            });

        $odontogramTeeth = \App\Models\Tooth::with('surfaces.legends')
            ->where('patient_id', $patient->id)
            ->get()
            ->map(function ($tooth) {
                $legends = $tooth->surfaces
                    ->flatMap(fn($surface) => $surface->legends)
                    ->unique('id')
                    ->values();

                return [
                    'tooth' => $tooth->tooth_number,
                    'legends' => $legends->map(fn($legend) => [
                        'code' => $legend->code,
                        'description' => $legend->description,
                        'category' => $legend->category,
                    ])->values(),
                    'surfaces' => $tooth->surfaces->map(fn($surface) => [
                        'surface_number' => $surface->surface_number,
                        'legends' => $surface->legends->map(fn($legend) => [
                            'code' => $legend->code,
                            'description' => $legend->description,
                            'category' => $legend->category,
                        ])->values(),
                    ])->values(),
                ];
            })
            ->values();

        $dentalQuestions = BookingQuestions::dental();
        $medicalQuestions = BookingQuestions::medical();

        AuditLogger::log(
            'view',
            'appointments',
            "Patient opened book appointment page"
        );

        return view('patient.book-appointment', compact(
            'patient',
            'appointmentCountsPerDay',
            'appointmentCountsPerSlot',
            'schedules',
            'blockedDates',
            'philippineHolidays',
            'diseases',
            'serviceTypes',
            'odontogramTeeth',
            'dentalQuestions',
            'medicalQuestions',
            'dentalDefaults',
            'medicalDefaults',
            'selectedDiseases',
            'hasExistingDentalHistory',
            'hasExistingMedicalHistory',
            'hasExistingBookingInformation',
            'hasReusableSignature',
            'reservedBookingPeriod',
            'availableReservedSlots'
        ));
    }

    private function reservedPeriodIsBookableBy(
        ReservedBookingPeriod $period,
        Patient $patient
    ): bool {
        if (! $period->is_active || $period->trashed() || ! $period->isEligiblePatient($patient)) {
            return false;
        }

        // Temporarily disabled: reserved periods previously had to be after today.
        // return Carbon::parse($period->reserved_date)
        //     ->startOfDay()
        //     ->isAfter(now()->startOfDay());

        $reservedDate = Carbon::parse($period->reserved_date)->startOfDay();

        if ($reservedDate->isBefore(now()->startOfDay())) {
            return false;
        }

        return ! $reservedDate->isToday()
            || Carbon::parse($period->reserved_date->format('Y-m-d') . ' ' . $period->end_time)->isFuture();
    }

    private function reservedPeriodRemainingCapacity(ReservedBookingPeriod $period): int
    {
        $booked = $period->appointments()
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->count();

        return max(0, (int) $period->max_capacity - $booked);
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

        // Student profile synchronization may create a medical history row using
        // emergency-contact data alone. That partial row does not mean the patient
        // has completed the medical questionnaire and must not skip its booking step.
        if (filled($patient->medicalHistory?->patient_signature)) {
            return true;
        }

        return false;
    }

    public function getDraft()
    {
        $patientId =
            session('impersonated_patient_id')
            ?: session('patient_id');

        if (! $patientId) {
            return response()->json([
                'has_draft' => false,
                'draft' => null,
            ], 401);
        }

        $draft =
            AppointmentDraft::where(
                'patient_id',
                $patientId
            )->first();

        return response()->json([
            'has_draft' => (bool) $draft,

            'draft' => $draft
                ? [
                    'payload' =>
                    $draft->payload,

                    'current_step' =>
                    $draft->current_step,

                    'last_saved_at' =>
                    optional(
                        $draft->last_saved_at
                    )->toISOString(),
                ]
                : null,
        ]);
    }

    public function saveDraft(Request $request)
    {
        $patientId =
            session('impersonated_patient_id')
            ?: session('patient_id');

        if (! $patientId) {
            return response()->json([
                'message' =>
                'Unauthenticated.',
            ], 401);
        }

        $validated =
            $request->validate([
                'payload' =>
                'required|array',

                'current_step' =>
                'nullable|integer|min:0|max:4',
            ]);

        $draft =
            AppointmentDraft::updateOrCreate(
                [
                    'patient_id' =>
                    $patientId,
                ],
                [
                    'payload' =>
                    $validated['payload'],

                    'current_step' =>
                    $validated['current_step']
                        ?? 0,

                    'last_saved_at' =>
                    now(),
                ]
            );

        return response()->json([
            'saved' => true,

            'last_saved_at' =>
            $draft
                ->last_saved_at
                ?->toISOString(),
        ]);
    }

    public function deleteDraft()
    {
        $patientId =
            session('impersonated_patient_id')
            ?: session('patient_id');

        if (! $patientId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        AppointmentDraft::where(
            'patient_id',
            $patientId
        )->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }

    /* =======================
       STORE APPOINTMENT
    ======================= */
    public function store(
        Request $request,
        SignatureAiVerifier $signatureVerifier
    ) {
        $request->merge([
            'emergency_number' => $this->normalizePhilippineMobile(
                (string) $request->input('emergency_number', '')
            ) ?? (string) $request->input('emergency_number', ''),
        ]);

        $patientId = session('impersonated_patient_id') ?: session('patient_id');

        if (!$patientId) {
            return redirect()->route('login')
                ->with('error', 'Please login first!');
        }

        $patient = Patient::with([
            'dentalHistory',
            'dentalHistoryDates',
            'dentalHistoryConcerns',
            'dentalHistoryAnswers.condition',
            'medicalHistory.answers.question',
            'medicalHistory.diseaseAnswers.disease',
        ])->findOrFail($patientId);

        $reservedBookingPeriod = filled($request->input('reserved_booking_period_id'))
            ? ReservedBookingPeriod::with('slots')->find($request->integer('reserved_booking_period_id'))
            : null;

        if (filled($request->input('reserved_booking_period_id')) && ! $reservedBookingPeriod) {
            return redirect()->back()->withInput()->with('error', 'The reserved booking period no longer exists.');
        }

        if ($reservedBookingPeriod && ! $this->reservedPeriodIsBookableBy($reservedBookingPeriod, $patient)) {
            return redirect()->route('homepage')->with(
                'error',
                'This reserved booking invitation is unavailable or is not assigned to your patient group.'
            );
        }

        $reservedSlot = null;

        if ($reservedBookingPeriod?->booking_mode === 'timeslot') {
            $reservedSlot = $reservedBookingPeriod->slots
                ->firstWhere('id', $request->integer('reserved_booking_period_slot_id'));

            if (! $reservedSlot) {
                return redirect()->back()->withInput()->withErrors([
                    'appointment_time' => 'Select an available timeslot for this reserved period.',
                ]);
            }
        }

        if ($reservedBookingPeriod) {
            $request->merge([
                'appointment_date' => $reservedBookingPeriod->reserved_date->format('Y-m-d'),
                'appointment_time' => Carbon::parse(
                    $reservedSlot?->slot_time ?: $reservedBookingPeriod->start_time
                )->format('g:i A'),
            ]);
        }

        $preserveExistingDentalHistory = (bool) $reservedBookingPeriod
            && $this->hasExistingDentalHistoryRecord($patient)
            && ! $request->boolean('update_dental_history');
        $preserveExistingMedicalHistory = (bool) $reservedBookingPeriod
            && $this->hasExistingMedicalHistoryRecord($patient)
            && ! $request->boolean('update_medical_history');

        $existingMedicalHistory = MedicalHistory::where(
            'patient_id',
            $patientId
        )->first();

        $hasReusableSignature =
            !empty($existingMedicalHistory?->patient_signature) &&
            $existingMedicalHistory?->signature_review_status !== 'invalid_reupload_required';

        $request->validate([
            'appointment_date'     => [
                'required',
                'date',
                $reservedBookingPeriod ? 'after_or_equal:today' : 'after:today',
            ],
            'appointment_time'     => 'required|string', // "1:00 PM"
            'reserved_booking_period_id' => ['nullable', 'integer'],
            'reserved_booking_period_slot_id' => ['nullable', 'integer'],
            'service_type' => 'required|string|max:255',
            'final_confirmation' => ['accepted'],

            'contact_email' => [
                'required',
                'email',
                'max:255',
            ],

            'contact_phone' => [
                'required',
                'regex:/^09\d{9}$/',
            ],

            'contact_address' => [
                'required',
                'string',
                'max:500',
            ],

            'emergency_person' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-zÑñ\s.\'-]+$/u',
            ],
            'emergency_number'     => ['required', 'string', 'size:11', 'regex:/^09\d{9}$/'],
            'emergency_relation' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::in([
                    'Father',
                    'Mother',
                    'Uncle',
                    'Auntie',
                    'Brother',
                    'Sister',
                    'Grandmother',
                    'Grandfather',
                    'Cousin',
                    'Legal Guardian',
                    'Friend',
                    'Other Relative',
                ]),
            ],

            'patient_signature' => [
                $hasReusableSignature ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:25600',
            ],
            'signature_source' => 'nullable|in:drawn,upload',

            'diseases'   => 'array',
            'diseases.*' => 'string|exists:diseases,code',
        ]);


        if (!ServiceType::where('name', $request->service_type)
            ->where('is_active_for_booking', true)
            ->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid service type selected.');
        }

        if ($reservedBookingPeriod && ! $reservedBookingPeriod->allowsService($request->service_type)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'The selected dental service is not available for this reserved booking period.');
        }

        $isFemalePatient = strtolower($patient->gender ?? '') === 'female';

        try {
            $mysqlTime = $this->toMysqlTime($request->appointment_time);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid time format. Please pick a valid time slot.');
        }

        $date = Carbon::parse($request->appointment_date);
        $dayAbbr = $date->format('D');

        if ($date->isToday() && ! $reservedBookingPeriod) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Same-day booking is not allowed. Please select a future date.');
        }

        $mustUseFutureTime = ! $reservedBookingPeriod
            || $reservedBookingPeriod->booking_mode === 'timeslot';

        if (
            $date->isToday()
            && $mustUseFutureTime
            && Carbon::parse($date->toDateString() . ' ' . $mysqlTime)->lessThanOrEqualTo(now())
        ) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'That appointment time has already passed. Please select a later time.');
        }

        if (BlockedDate::whereDate('date', $request->appointment_date)->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This date is blocked and unavailable for booking.');
        }

        if (PhilippineHolidays::isBlockedForBooking($request->appointment_date)) {
            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'The clinic is closed on this Philippine holiday. Please choose another date.'
                );
        }

        $schedule = ClinicSchedule::active()
            ->get()
            ->first(function ($rule) use ($dayAbbr) {
                return in_array($dayAbbr, $rule->days ?? []);
            });

        if (! $schedule || $schedule->status === 'closed') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'The clinic is closed on the selected date.');
        }

        if (! $schedule->open_time || ! $schedule->close_time) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Clinic hours are not configured properly for this date.');
        }

        if ($mysqlTime < $schedule->open_time || $mysqlTime >= $schedule->close_time) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Selected time is outside clinic operating hours.');
        }

        if (!empty($schedule->break_time) && $schedule->break_time !== 'none') {
            [$breakStart, $breakEnd] = explode('-', $schedule->break_time);

            $breakStartTime = Carbon::createFromFormat('H:i', trim($breakStart))->format('H:i:s');
            $breakEndTime   = Carbon::createFromFormat('H:i', trim($breakEnd))->format('H:i:s');

            if ($mysqlTime >= $breakStartTime && $mysqlTime < $breakEndTime) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Selected time falls within the clinic lunch break.');
            }
        }

        if (! $reservedBookingPeriod) {
            $conflictingReservedPeriod = ReservedBookingPeriod::query()
                ->active()
                ->whereDate('reserved_date', $request->appointment_date)
                ->whereTime('start_time', '<=', $mysqlTime)
                ->whereTime('end_time', '>', $mysqlTime)
                ->exists();

            if ($conflictingReservedPeriod) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'That time belongs to a reserved booking period. Please select a later available time.');
            }
        }

        $appointmentCount = Appointment::where('appointment_date', $request->appointment_date)
            ->regularBooking()
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->count();

        if (! $reservedBookingPeriod && $appointmentCount >= $schedule->max_slots) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sorry, this date is fully booked. Please select another date.');
        }

        $timeTaken = Appointment::where('appointment_date', $request->appointment_date)
            ->where('appointment_time', $mysqlTime)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->exists();

        if (! $reservedBookingPeriod && $timeTaken) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sorry, that time slot was already taken. Please choose another time.');
        }

        // The database also prevents a patient from holding two records at one
        // exact date and time. Check it here to return a normal form error.
        $duplicatePatientSchedule = Appointment::query()
            ->where('patient_id', $patientId)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('appointment_time', $mysqlTime)
            ->exists();

        if ($duplicatePatientSchedule) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'appointment_time' => 'You already have an appointment at this date and time.',
                ]);
        }

        if (! $isFemalePatient) {
            $request->merge([
                'pregnant' => 'NO',
                'nursing' => 'NO',
                'birth_control' => 'NO',
            ]);
        }

        $signatureFile = $request->file('patient_signature');

        if (!$signatureFile && $hasReusableSignature) {


            $signaturePath = $existingMedicalHistory->patient_signature;

            $signatureReviewStatus =
                $existingMedicalHistory->signature_review_status ?? 'verified';

            $signatureReviewNotes =
                $existingMedicalHistory->signature_review_notes;

            $aiResult = [
                'accepted' => true,
                'source' => 'existing',
                'reason' => 'Existing patient signature reused.',
                'confidence' => $existingMedicalHistory->signature_ai_confidence,
                'review_required' =>
                $signatureReviewStatus === 'pending_manual_review',
                'review_status' => $signatureReviewStatus,
                'detected_type' => 'existing_signature',
            ];
        } else {

            $isDrawnSignature =
                $this->isDrawnSignatureSubmission($request);

            if (! $isDrawnSignature) {

                $aiResult =
                    $signatureVerifier->verify(
                        $signatureFile
                    );

                \Log::info(
                    'Store Appointment Signature Result',
                    $aiResult
                );

                if (!($aiResult['accepted'] ?? false)) {
                    $reason =
                        $aiResult['reason']
                        ?? 'The uploaded image did not pass signature validation.';

                    $detectedType =
                        $aiResult['detected_type']
                        ?? 'unknown';

                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors([
                            'patient_signature' =>
                            'Signature could not be processed. Please try again. ' .
                                'Reason: ' . $reason .
                                ' Detected: ' . $detectedType,
                        ]);
                }
            } else {

                $aiResult = [
                    'accepted' => true,
                    'source' => 'drawn',
                    'reason' =>
                    'Drawn signature skipped AI validation.',
                    'confidence' => 1,
                    'review_required' => false,
                    'review_status' => 'verified',
                    'detected_type' => 'drawn_signature',
                ];

                \Log::info(
                    'Store Appointment Signature Result',
                    [
                        'accepted' =>
                        $aiResult['accepted'],

                        'source' =>
                        $aiResult['source'],

                        'reason' =>
                        $aiResult['reason'],
                    ]
                );
            }

            $signatureReviewStatus =
                ($aiResult['review_status'] ?? null)
                === 'pending_manual_review'
                ? 'pending_manual_review'
                : 'verified';

            $signatureReviewNotes =
                $aiResult['review_required'] ?? false
                ? (
                    $aiResult['reason']
                    ?? 'Accepted for manual review.'
                )
                : null;

            $signaturePath =
                $signatureFile->store(
                    'signatures',
                    'public'
                );
        }

        $appointment = null;

        DB::transaction(function () use ($request, $signaturePath, $mysqlTime, $patientId, $patient, $reservedBookingPeriod, $reservedSlot, $preserveExistingDentalHistory, $preserveExistingMedicalHistory, $signatureReviewStatus, $signatureReviewNotes, $aiResult, &$appointment) {

            $lockedReservedPeriod = null;

            if ($reservedBookingPeriod) {
                $lockedReservedPeriod = ReservedBookingPeriod::query()
                    ->withTrashed()
                    ->lockForUpdate()
                    ->findOrFail($reservedBookingPeriod->id);

                if (! $this->reservedPeriodIsBookableBy($lockedReservedPeriod, $patient)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'reserved_booking_period_id' => 'This reserved booking period is no longer available to you.',
                    ]);
                }

                $bookedCount = Appointment::query()
                    ->forReservedPeriod($lockedReservedPeriod->id)
                    ->whereIn('status', ['upcoming', 'rescheduled'])
                    ->count();

                if ($bookedCount >= $lockedReservedPeriod->max_capacity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'reserved_booking_period_id' => 'This reserved booking period is already full.',
                    ]);
                }

                if ($lockedReservedPeriod->booking_mode === 'timeslot') {
                    $lockedSlot = ReservedBookingPeriodSlot::query()
                        ->where('reserved_booking_period_id', $lockedReservedPeriod->id)
                        ->lockForUpdate()
                        ->find($reservedSlot?->id);

                    $slotTaken = $lockedSlot && Appointment::query()
                        ->forReservedSlot($lockedSlot->id)
                        ->whereIn('status', ['upcoming', 'rescheduled'])
                        ->exists();

                    if (! $lockedSlot || $slotTaken) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'appointment_time' => 'That reserved timeslot was just taken. Select another available timeslot.',
                        ]);
                    }
                }

                if (Appointment::query()
                    ->where('patient_id', $patientId)
                    ->forReservedPeriod($lockedReservedPeriod->id)
                    ->exists()
                ) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'reserved_booking_period_id' => 'You already booked this reserved period.',
                    ]);
                }
            }

            $assignedDentist = $this->resolveBookableDentist();

            if (! $assignedDentist) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'appointment_date' => 'No active dentist account is available for this appointment.',
                ]);
            }

            $serviceType = ServiceType::where('name', $request->service_type)->firstOrFail();

            // 1) APPOINTMENT
            $appointment = Appointment::create([
                'patient_id'       => $patientId,
                'reserved_booking_period_id' => $lockedReservedPeriod?->id,
                'reserved_booking_period_slot_id' => $reservedSlot?->id,
                'dentist_id'       => $assignedDentist->id,
                'service_type_id'  => $serviceType->id,
                'service_type'     => $serviceType->name,
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $mysqlTime,
                'status'           => 'upcoming',
            ]);

            $patient->fill([
                'email' => $request->contact_email,
                'phone' => $request->contact_phone,
                'address' => $request->contact_address,
            ]);

            $patient->save();

            // Notify the dentist who will handle this appointment.
            $assignedDentist->notify(new AppointmentBookedNotification($appointment, $appointment->patient));

            //  Notify admins
            $admins = User::whereHas('role', function ($q) {
                $q->where('slug', 'admin');
            })->get();

            foreach ($admins as $admin) {
                $admin->notify(new AppointmentBookedNotification($appointment, $appointment->patient));
            }

            // 2) DENTAL HISTORY (patient-based)
            if (! $preserveExistingDentalHistory) {
                DentalHistory::updateOrCreate(
                    ['patient_id' => $patientId],
                    [
                        'last_dental_visit' => $request->last_dental_visit,
                        'previous_dentist'  => $request->previous_dentist,
                    ]
                );

                DentalHistoryConditionDate::updateOrCreate(
                    ['patient_id' => $patientId],
                    [
                        'extraction_date' => $request->extraction_date,
                        'dentures_date'   => $request->dentures_date,
                        'ortho_date'      => $request->ortho_date,
                    ]
                );

                DentalHistoryConcern::updateOrCreate(
                    ['patient_id' => $patientId],
                    [
                        'additional_concerns' => $request->additional_concerns,
                    ]
                );

                // YES/NO answers (dental_history_answers)
                $dentalAnswerMap = [
                    'bleeding_gums'      => $request->bleeding_gums,
                    'sensitive_temp'     => $request->sensitive_temp,
                    'sensitive_taste'    => $request->sensitive_taste,
                    'tooth_pain'         => $request->tooth_pain,
                    'sores'              => $request->sores,
                    'injuries'           => $request->injuries,

                    'clicking'           => $request->clicking,
                    'joint_pain'         => $request->joint_pain,
                    'difficulty_moving'  => $request->difficulty_moving,
                    'difficulty_chewing' => $request->difficulty_chewing,
                    'jaw_headaches'      => $request->jaw_headaches,
                    'clench_grind'       => $request->clench_grind,
                    'biting'             => $request->biting,
                    'teeth_loosening'    => $request->teeth_loosening,
                    'food_teeth'         => $request->food_teeth,
                    'med_reaction'       => $request->med_reaction,

                    'periodontal'          => $request->periodontal,
                    'difficult_extraction' => $request->difficult_extraction,
                    'prolonged_bleeding'   => $request->prolonged_bleeding,
                    'dentures'             => $request->dentures,
                    'ortho_treatment'      => $request->ortho_treatment,
                ];

                $conditionIdsByCode = DentalHistoryCondition::whereIn('code', array_keys($dentalAnswerMap))
                    ->pluck('id', 'code');

                foreach ($dentalAnswerMap as $code => $rawValue) {
                    $conditionId = $conditionIdsByCode[$code] ?? null;
                    if (!$conditionId) continue;

                    DentalHistoryAnswer::updateOrCreate(
                        [
                            'patient_id'   => $patientId,
                            'condition_id' => $conditionId,
                        ],
                        [
                            'answer' => ($this->yesNoValue($rawValue) === 'YES'),
                        ]
                    );
                }
            }

            // 3) MEDICAL HISTORY (patient-based)
            if (! $preserveExistingMedicalHistory) {
                $medicalHistory = MedicalHistory::updateOrCreate(
                    ['patient_id' => $patientId],
                    [
                        'emergency_person'   => $request->emergency_person,
                        'emergency_number'   => $request->emergency_number,
                        'emergency_relation' => $request->emergency_relation,
                        'patient_signature'  => $signaturePath,
                        'signature_review_status' => $signatureReviewStatus,
                        'signature_review_notes' => $signatureReviewNotes,
                        'signature_ai_provider' => 'openai',
                        'signature_ai_confidence' => $aiResult['confidence'] ?? null,
                    ]
                );

                $tobaccoUse = $this->yesNoValue($request->input('tobacco_use'));

                // Medical answers map
                $medicalAnswerMap = [
                    // bool
                    'good_health'       => $request->good_health,
                    'had_medical_exam'  => $request->had_medical_exam,
                    'under_treatment'   => $request->under_treatment,
                    'hospitalized'      => $request->hospitalized,
                    'allergy_medicine'  => $request->allergy_medicine,
                    'allergy_food'      => $request->allergy_food,
                    'medication'        => $request->medication,
                    'pregnant'          => $request->pregnant,
                    'nursing'           => $request->nursing,
                    'birth_control'     => $request->birth_control,
                    'tobacco_use'       => $tobaccoUse,
                    'headaches'         => $request->headaches,
                    'earaches'          => $request->earaches,
                    'neck_aches'        => $request->neck_aches,

                    // text
                    'good_health_details' => $request->good_health_details,
                    'treatment_details'   => $request->treatment_details,
                    'hospital_details'    => $request->hospital_details,
                    'allergy_others'      => $request->allergy_others,
                    'medication_details'  => $request->medication_details,
                    'tobacco_per_day' => $tobaccoUse === 'YES'
                        ? $request->input('tobacco_per_day')
                        : null,

                    'tobacco_per_week' => $tobaccoUse === 'YES'
                        ? $request->input('tobacco_per_week')
                        : null,

                    // date
                    'medical_exam_date'   => $request->medical_exam_date,
                ];

                $questions = MedicalHistoryQuestion::whereIn('code', array_keys($medicalAnswerMap))
                    ->get()
                    ->keyBy('code');

                foreach ($medicalAnswerMap as $code => $rawValue) {
                    $q = $questions->get($code);
                    if (!$q) continue;

                    // Normalize by type
                    if ($q->type === 'bool') {
                        $bool = ($this->yesNoValue($rawValue) === 'YES');

                        MedicalHistoryAnswer::updateOrCreate(
                            [
                                'patient_id'         => $patientId,
                                'medical_history_id' => $medicalHistory->id,
                                'question_id'        => $q->id,
                            ],
                            [
                                'answer_bool' => $bool,
                                'answer_text' => null,
                                'answer_date' => null,
                            ]
                        );

                        continue;
                    }

                    if ($q->type === 'text') {
                        $text = ($rawValue === null) ? '' : trim((string) $rawValue);

                        // remove row if empty text 
                        if ($text === '') {
                            MedicalHistoryAnswer::where([
                                'patient_id'         => $patientId,
                                'medical_history_id' => $medicalHistory->id,
                                'question_id'        => $q->id,
                            ])->delete();
                            continue;
                        }

                        MedicalHistoryAnswer::updateOrCreate(
                            [
                                'patient_id'         => $patientId,
                                'medical_history_id' => $medicalHistory->id,
                                'question_id'        => $q->id,
                            ],
                            [
                                'answer_bool' => null,
                                'answer_text' => $text,
                                'answer_date' => null,
                            ]
                        );

                        continue;
                    }

                    if ($q->type === 'date') {
                        $date = $rawValue ? trim((string) $rawValue) : '';

                        // remove row if empty date
                        if ($date === '') {
                            MedicalHistoryAnswer::where([
                                'patient_id'         => $patientId,
                                'medical_history_id' => $medicalHistory->id,
                                'question_id'        => $q->id,
                            ])->delete();
                            continue;
                        }

                        MedicalHistoryAnswer::updateOrCreate(
                            [
                                'patient_id'         => $patientId,
                                'medical_history_id' => $medicalHistory->id,
                                'question_id'        => $q->id,
                            ],
                            [
                                'answer_bool' => null,
                                'answer_text' => null,
                                'answer_date' => $date,
                            ]
                        );

                        continue;
                    }
                }

                MedicalHistoryAnswer::where('patient_id', $patientId)
                    ->where('medical_history_id', $medicalHistory->id)
                    ->whereNull('answer_bool')
                    ->whereNull('answer_date')
                    ->where(function ($q) {
                        $q->whereNull('answer_text')
                            ->orWhereRaw("TRIM(answer_text) = ''");
                    })
                    ->delete();

                // 4) DISEASES (selected codes from form)
                $selectedDiseaseCodes = $request->input('diseases', []);
                $selectedDiseaseIds = Disease::whereIn('code', $selectedDiseaseCodes)
                    ->pluck('id')
                    ->all();

                MedicalHistoryDiseaseAnswer::where('medical_history_id', $medicalHistory->id)->delete();

                foreach ($selectedDiseaseIds as $diseaseId) {
                    MedicalHistoryDiseaseAnswer::create([
                        'patient_id'         => $patientId,
                        'medical_history_id' => $medicalHistory->id,
                        'disease_id'         => $diseaseId,
                        'has_disease'        => true,
                    ]);
                }
            }
        });

        if ($appointment) {
            if ($reservedBookingPeriod) {
                app(\App\Services\ReservedBookingInvitationService::class)
                    ->syncPatient($patient);
            }

            AppointmentDraft::where(
                'patient_id',
                $patientId
            )->delete();

            AuditLogger::log(
                'create',
                'appointments',
                "Patient booked appointment for {$appointment->appointment_date} at {$appointment->appointment_time}"
            );

            try {
                $appointment->loadMissing('patient');

                $patientEmail = $appointment->patient?->email;

                if ($patientEmail) {
                    Mail::to($patientEmail)
                        ->send(new AppointmentConfirmationMail($appointment));

                    Log::info('Appointment confirmation email sent.', [
                        'appointment_id' => $appointment->id,
                        'patient_id' => $appointment->patient_id,
                        'email' => $patientEmail,
                    ]);
                } else {
                    Log::warning('Appointment confirmation email not sent: patient has no email.', [
                        'appointment_id' => $appointment->id,
                        'patient_id' => $appointment->patient_id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Appointment confirmation email failed.', [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }


        $successMessage =
            $signatureReviewStatus ===
            'pending_manual_review'
            ? 'Appointment booked successfully! The signature was accepted and flagged for manual review.'
            : ($reservedBookingPeriod
                ? 'Reserved appointment booked successfully!'
                : 'Appointment booked successfully!');

        return redirect()
            ->route('homepage')
            ->with(
                'success',
                $successMessage
            )
            ->with(
                'appointment_draft_completed',
                true
            )
            ->with(
                'appointment_confirmation',
                [
                    'date' =>
                    Carbon::parse(
                        $appointment
                            ->appointment_date
                    )->format(
                        'F d, Y'
                    ),

                    'time' =>
                    Carbon::parse(
                        $appointment
                            ->appointment_time
                    )->format(
                        'g:i A'
                    ),

                    'service' =>
                    $appointment
                        ->service_type_name,

                    'status' =>
                    'Confirmed',
                ]
            );
    }

    public function slotsForDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after:today',
        ]);

        $iso = $request->date;
        $date = Carbon::parse($iso);
        $dayAbbr = $date->format('D');

        if ($date->isToday()) {
            return response()->json([
                'slots' => [],
                'message' => 'Same-day booking is not allowed. Please choose a future date.',
            ]);
        }

        if (BlockedDate::whereDate('date', $iso)->exists()) {
            return response()->json([
                'slots' => [],
                'message' => 'This date is blocked and unavailable for booking.',
            ]);
        }

        if (PhilippineHolidays::isBlockedForBooking($iso)) {
            return response()->json([
                'slots' => [],
                'message' =>
                'The clinic is closed on this Philippine holiday.',
            ]);
        }

        $schedule = ClinicSchedule::active()
            ->get()
            ->first(fn($s) => in_array($dayAbbr, $s->days ?? []));

        if (! $schedule || $schedule->status === 'closed') {
            return response()->json([
                'slots' => [],
                'message' => 'The clinic is closed on this day.',
            ]);
        }

        $bookedSlotCounts = Appointment::where('appointment_date', $iso)
            ->regularBooking()
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('appointment_time, COUNT(*) as cnt')
            ->groupBy('appointment_time')
            ->pluck('cnt', 'appointment_time')
            ->toArray();

        $totalBooked = array_sum($bookedSlotCounts);

        if ($totalBooked >= $schedule->max_slots) {
            return response()->json([
                'slots' => [],
                'message' => 'All slots for this day are fully booked.',
                'max_slots' => $schedule->max_slots,
                'booked' => $totalBooked,
                'remaining' => 0,
            ]);
        }

        $slots = collect($schedule->availableSlots($iso, $bookedSlotCounts));

        $reservedPeriod = ReservedBookingPeriod::query()
            ->active()
            ->whereDate('reserved_date', $iso)
            ->first();

        if ($reservedPeriod) {
            $reservedStart = Carbon::parse($reservedPeriod->start_time);
            $reservedEnd = Carbon::parse($reservedPeriod->end_time);

            $slots = $slots->reject(function ($slot) use ($reservedStart, $reservedEnd) {
                $value = is_array($slot) ? ($slot['time'] ?? null) : $slot;

                if (! $value) {
                    return false;
                }

                $time = Carbon::parse($value);

                return $time->greaterThanOrEqualTo($reservedStart) && $time->lessThan($reservedEnd);
            })->values();
        }

        return response()->json([
            'slots'      => $slots,
            'max_slots'  => $schedule->max_slots,
            'booked'     => $totalBooked,
            'remaining'  => max(0, $schedule->max_slots - $totalBooked),
            'open_time'  => $schedule->open_time,
            'close_time' => $schedule->close_time,
            'break_time' => $schedule->break_time,
            'status'     => $schedule->status,
        ]);
    }

    /* =======================
   VALIDATE SIGNATURE AJAX
======================= */
    public function validateSignature(Request $request, SignatureAiVerifier $signatureVerifier)
    {
        $request->validate([
            'patient_signature' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:25600',
            ],
        ]);

        $signatureFile = $request->file('patient_signature');

        if ($this->isDrawnSignatureSubmission($request)) {
            return response()->json([
                'valid' => true,
                'accepted' => true,
                'message' => 'Drawn signature accepted',
                'detected_type' => 'drawn_signature',
                'confidence' => 1,
                'reason' => '',
            ]);
        }

        $aiResult = $signatureVerifier->verify($signatureFile);

        \Log::info('Validate Signature Endpoint Result', $aiResult);

        if (!($aiResult['accepted'] ?? false)) {
            return response()->json([
                'valid' => false,
                'accepted' => false,
                'message' => 'Signature could not be processed. Please try again.',
                'detected_type' => $aiResult['detected_type'] ?? 'unknown',
                'confidence' => $aiResult['confidence'] ?? 0,
                'reason' => $aiResult['reason'] ?? 'The uploaded image did not pass signature validation.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'accepted' => true,
            'message' => $aiResult['review_required'] ?? false
                ? 'Signature accepted for manual review.'
                : 'Signature verified and accepted',
            'detected_type' => $aiResult['detected_type'] ?? 'signature',
            'confidence' => $aiResult['confidence'] ?? 0,
            'reason' => $aiResult['reason'] ?? '',
            'review_required' => (bool) ($aiResult['review_required'] ?? false),
            'review_status' => $aiResult['review_status'] ?? 'verified',
        ]);
    }

    public function showSignatureReview()
    {
        $patient = $this->resolveAuthenticatedPatient();

        if (!$patient) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient->load('medicalHistory');
        $medicalHistory = $patient->medicalHistory;

        if (!$medicalHistory || $medicalHistory->signature_review_status !== 'invalid_reupload_required') {
            return redirect()->route('homepage')->with('info', 'There is no pending signature re-upload request for your account.');
        }

        return view('patient.signature-review', compact('patient', 'medicalHistory'));
    }

    public function updateSignatureReview(Request $request, SignatureAiVerifier $signatureVerifier)
    {
        $patient = $this->resolveAuthenticatedPatient();

        if (!$patient) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient->load('medicalHistory');
        $medicalHistory = $patient->medicalHistory;

        if (!$medicalHistory || $medicalHistory->signature_review_status !== 'invalid_reupload_required') {
            return redirect()->route('homepage')->with('error', 'There is no signature re-upload request to update.');
        }

        $request->validate([
            'patient_signature' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:25600',
            ],
        ]);

        $signatureFile = $request->file('patient_signature');
        $isDrawnSignature = $this->isDrawnSignatureSubmission($request);

        if (! $isDrawnSignature) {
            $aiResult = $signatureVerifier->verify($signatureFile);

            \Log::info('Signature Reupload Verification Result', $aiResult);

            if (!($aiResult['accepted'] ?? false)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'patient_signature' => $aiResult['reason'] ?? 'The uploaded image did not pass signature validation.',
                    ]);
            }
        } else {
            $aiResult = [
                'accepted' => true,
                'reason' => 'Drawn signature accepted.',
                'confidence' => 1,
                'review_required' => false,
                'review_status' => 'verified',
            ];
        }

        $oldSignaturePath = $medicalHistory->patient_signature;
        $newSignaturePath = $signatureFile->store('signatures', 'public');
        $signatureReviewStatus = ($aiResult['review_status'] ?? null) === 'pending_manual_review'
            ? 'pending_manual_review'
            : 'verified';
        $signatureReviewNotes = $aiResult['review_required'] ?? false
            ? ($aiResult['reason'] ?? 'Accepted for manual review.')
            : null;

        $medicalHistory->update([
            'patient_signature' => $newSignaturePath,
            'signature_review_status' => $signatureReviewStatus,
            'signature_review_notes' => $signatureReviewNotes,
            'signature_ai_provider' => 'openai',
            'signature_ai_confidence' => $aiResult['confidence'] ?? null,
        ]);

        if ($oldSignaturePath && $oldSignaturePath !== $newSignaturePath) {
            Storage::disk('public')->delete($oldSignaturePath);
        }

        AuditLogger::log(
            'update',
            'medical_histories',
            "Patient re-uploaded signature for manual review follow-up (patient_id: {$patient->id})"
        );

        $successMessage = $signatureReviewStatus === 'pending_manual_review'
            ? 'Your new signature was uploaded successfully and is pending manual review.'
            : 'Your new signature was uploaded and verified successfully.';

        return redirect()->route('homepage')->with('success', $successMessage);
    }

    public function markSignatureInvalid(Request $request, Patient $patient)
    {
        $patient->load('medicalHistory', 'user');

        $medicalHistory = $patient->medicalHistory;

        if (!$medicalHistory || !$medicalHistory->patient_signature) {
            return redirect()->back()->with('error', 'No uploaded signature was found for this patient.');
        }

        if ($medicalHistory->signature_review_status !== 'pending_manual_review') {
            return redirect()->back()->with('error', 'Only signatures awaiting manual review can be marked invalid.');
        }

        $reason = 'The uploaded image is not a valid patient signature. Please upload a clearer or correct signature image.';

        $medicalHistory->update([
            'signature_review_status' => 'invalid_reupload_required',
            'signature_review_notes' => $reason,
        ]);

        if ($patient->user) {
            $patient->user->notify(new SignatureReuploadRequiredNotification($patient, $reason));
        }

        AuditLogger::log(
            'update',
            'medical_histories',
            "Signature marked invalid during manual review (patient_id: {$patient->id})"
        );

        return redirect()->back()->with('success', 'The signature was marked invalid and the patient was notified to upload a new one.');
    }

    /* =======================
       HELPERS
    ======================= */
    private function toMysqlTime(string $time12h): string
    {
        return Carbon::createFromFormat('g:i A', trim($time12h))->format('H:i:s');
    }

    private function yesNoValue($value): string
    {
        if ($value === null) return 'NO';
        if (is_bool($value)) return $value ? 'YES' : 'NO';

        $v = strtoupper(trim((string) $value));
        if (in_array($v, ['YES', 'Y', 'TRUE', '1', 'ON'], true)) return 'YES';

        return 'NO';
    }

    private function backfillStudentEmergencyContactIfNeeded(Patient $patient): void
    {
        $isStudent = ! empty($patient->student_no) || (! empty($patient->email) && ! empty($patient->course_code));

        if (! $isStudent) {
            return;
        }

        $medicalHistory = $patient->medicalHistory;
        $needsBackfill = blank($medicalHistory?->emergency_person)
            || blank($medicalHistory?->emergency_number)
            || blank($medicalHistory?->emergency_relation);

        if (! $needsBackfill) {
            return;
        }

        try {
            $studentProfile = [];

            if (! empty($patient->email)) {
                $studentProfileResponse = $this->studentApiService->getStudentByEmail($patient->email);
                $studentProfile = is_array($studentProfileResponse['data'] ?? null)
                    ? $studentProfileResponse['data']
                    : [];
            }

            $studentNumber = $patient->student_no
                ?: data_get($studentProfile, 'studentNumber')
                ?: data_get($studentProfile, 'student_number');

            if (empty($studentNumber)) {
                return;
            }

            $personalInfoResponse = $this->studentApiService->getPersonalInfoByStudentNumber($studentNumber);
            $personalInfo = is_array($personalInfoResponse['data'] ?? null)
                ? $personalInfoResponse['data']
                : [];

            if ($personalInfo === []) {
                return;
            }

            $this->syncStudentMedicalHistory($patient, $personalInfo);
            $patient->unsetRelation('medicalHistory');
            $patient->load('medicalHistory');
        } catch (\Throwable $e) {
            Log::warning('Appointment OGOS medical history backfill failed', [
                'patient_id' => $patient->id,
                'student_no' => $patient->student_no,
                'email' => $patient->email,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function syncStudentMedicalHistory(Patient $patient, array $personalInfo): void
    {
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

        $medicalHistory = MedicalHistory::firstOrNew(['patient_id' => $patient->id]);
        $currentRelation = strtolower(trim((string) ($medicalHistory->emergency_relation ?? '')));
        $hasPlaceholderRelation = in_array($currentRelation, ['', 'not specified', '(not specified)', 'n/a', 'na'], true);

        if ($emergencyPerson && blank($medicalHistory->emergency_person)) {
            $medicalHistory->emergency_person = $emergencyPerson;
        }

        if ($emergencyNumber && blank($medicalHistory->emergency_number)) {
            $medicalHistory->emergency_number = $emergencyNumber;
        }

        if ($emergencyRelation && $hasPlaceholderRelation) {
            $medicalHistory->emergency_relation = $emergencyRelation;
        }

        if (! $medicalHistory->exists && empty($medicalHistory->emergency_relation)) {
            $medicalHistory->emergency_relation = 'Not specified';
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

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
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

    private function resolveAuthenticatedPatient(): ?Patient
    {
        $patientId = session('impersonated_patient_id') ?: session('patient_id');

        if ($patientId) {
            return Patient::find($patientId);
        }

        return auth()->user()?->patient;
    }

    public function reschedule($id)
    {
        $appointment = Appointment::with('patient')->findOrFail($id);

        $appointmentCountsPerDay = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('DATE(appointment_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $appointmentCountsPerSlot = Appointment::whereIn('status', ['upcoming', 'rescheduled'])
            ->selectRaw('DATE(appointment_date) as date, appointment_time, COUNT(*) as count')
            ->groupBy('date', 'appointment_time')
            ->get()
            ->groupBy(function ($item) {
                return $item->date;
            })
            ->map(function ($group) {
                return $group->pluck('count', 'appointment_time')->toArray();
            })
            ->toArray();

        $unavailableDates = [];

        $philippineHolidays = PhilippineHolidays::recordsCurrent();

        return view('dentist.dentist-reschedule', compact(
            'appointment',
            'appointmentCountsPerDay',
            'appointmentCountsPerSlot',
            'unavailableDates',
            'philippineHolidays'
        ));
    }

    /**
     * Update the rescheduled appointment
     */
    public function updateReschedule(Request $request, $id)
    {
        $request->validate([
            'new_appointment_date' => 'required|date|after_or_equal:today',
            'new_appointment_time' => 'required',
            'service_type' => 'required|string',
        ]);

        $appointment = Appointment::findOrFail($id);
        $serviceType = ServiceType::where('name', $request->service_type)->firstOrFail();


        try {
            $mysqlTime = $this->toMysqlTime($request->new_appointment_time);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid time format. Please pick a valid time slot.');
        }

        $slotTaken = Appointment::where('appointment_date', $request->new_appointment_date)
            ->where('appointment_time', $mysqlTime)
            ->where('id', '!=', $id)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->exists();

        if ($slotTaken) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sorry, that time slot is already taken. Please choose another time.');
        }

        $oldAppointmentDate = $appointment->appointment_date;
        $oldAppointmentTime = $appointment->appointment_time;

        $appointment->update([
            'appointment_date' => $request->new_appointment_date,
            'appointment_time' => $mysqlTime,
            'service_type_id' => $serviceType->id,
            'service_type' => $serviceType->name,
            'status' => 'rescheduled',
        ]);

        $appointment->load('patient');

        $dentists = User::query()
            ->where('status', 'active')
            ->whereHas('role', function ($query) {
                $query->where('slug', 'dentist');
            })
            ->get();

        if ($dentists->isNotEmpty()) {
            foreach ($dentists as $dentist) {
                $dentist->notify(new AppointmentRescheduledNotification($appointment, 'Patient'));
            }
        }

        try {
            $appointment->loadMissing('patient');

            $patientEmail = $appointment->patient?->email;

            if ($patientEmail) {
                Mail::to($patientEmail)
                    ->send(new AppointmentRescheduleMail(
                        $appointment,
                        $oldAppointmentDate,
                        $oldAppointmentTime,
                        'the clinic'
                    ));

                Log::info('Appointment reschedule email sent.', [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'email' => $patientEmail,
                ]);
            } else {
                Log::warning('Appointment reschedule email not sent: patient has no email.', [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Appointment reschedule email failed.', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    private function isDrawnSignatureSubmission(Request $request): bool
    {
        $signatureFile = $request->file('patient_signature');

        return $request->input('signature_source') === 'drawn'
            && $signatureFile
            && str_starts_with($signatureFile->getClientOriginalName(), 'drawn-signature-');
    }
}
