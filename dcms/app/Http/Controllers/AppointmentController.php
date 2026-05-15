<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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


class AppointmentController extends Controller
{

    public function index()
    {
        $patientId = session('impersonated_patient_id') ?: session('patient_id');

        if (!$patientId) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient = Patient::findOrFail($patientId);

        $now = now();
        $today = $now->toDateString();
        $nowTime = $now->format('H:i:s');

        $appointments = Appointment::with(['dentist.role'])
            ->where('patient_id', $patientId)
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $futureVisits = Appointment::with(['dentist.role'])
            ->where('patient_id', $patientId)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereDate('appointment_date', '>', $today)
                    ->orWhere(function ($q2) use ($today, $nowTime) {
                        $q2->whereDate('appointment_date', '=', $today)
                            ->whereTime('appointment_time', '>=', $nowTime);
                    });
            })
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $pastVisits = Appointment::with(['dentist.role'])
            ->where('patient_id', $patientId)
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereIn('status', ['completed', 'cancelled'])
                    ->orWhereDate('appointment_date', '<', $today)
                    ->orWhere(function ($q2) use ($today, $nowTime) {
                        $q2->whereDate('appointment_date', '=', $today)
                            ->whereTime('appointment_time', '<', $nowTime);
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
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->toArray();

        $unavailableDates = [];

        $philippineHolidays = PhilippineHolidays::range(1, 3);

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

    public function create()
    {
        $patientId = session('impersonated_patient_id') ?: session('patient_id');

        if (!$patientId) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient = Patient::findOrFail($patientId);

        // DO NOT REMOVE
        // $hasActiveAppointment = Appointment::where('patient_id', $patientId)
        //     ->whereIn('status', ['upcoming', 'rescheduled'])
        //     ->exists();

        // if ($hasActiveAppointment) {
        //     return redirect()->back()->with([
        //         'activeAppointmentModal' => true,
        //         'activeAppointmentMsg' =>
        //         "You already have an active appointment. Please wait until it is completed before booking another one."
        //     ]);
        // }

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

        $schedules = ClinicSchedule::active()->orderBy('id')->get();

        $blockedDates = BlockedDate::pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $philippineHolidays = PhilippineHolidays::range(0, 1);

        $diseases = Disease::orderBy('sort_order')->get();

        $serviceTypes = ServiceType::where('is_active_for_booking', true)
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
            'odontogramTeeth'
        ));
    }

    /* =======================
       STORE APPOINTMENT
    ======================= */
    public function store(Request $request)
    {
        $request->validate([
            'appointment_date'     => 'required|date|after:today',
            'appointment_time'     => 'required|string', // "1:00 PM"
            'service_type' => 'required|string|max:255',

            'emergency_person'     => 'required|string|max:50',
            'emergency_number'     => 'required|string|max:15',
            'emergency_relation' => [
                                        'required',
                                        'string',
                                        \Illuminate\Validation\Rule::in([
                                            'Mother',
                                            'Father',
                                            'Sibling',
                                            'Guardian',
                                            'Spouse',
                                            'Grandparent',
                                            'Aunt',
                                            'Uncle',
                                            'Cousin',
                                            'Child',
                                        ]),
                                    ],

            'patient_signature'    => 'required|mimes:jpg,jpeg,png,pdf|max:5120',

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

        $patientId = session('patient_id');
        if (!$patientId) {
            return redirect()->route('login')->with('error', 'Please login first!');
        }

        $patient = Patient::findOrFail($patientId);
        $isFemalePatient = strtolower($patient->gender ?? '') === 'female';

        // Convert UI "1:00 PM" -> DB TIME "13:00:00"
        try {
            $mysqlTime = $this->toMysqlTime($request->appointment_time);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid time format. Please pick a valid time slot.');
        }

        $date = Carbon::parse($request->appointment_date);
        $dayAbbr = $date->format('D');

        if ($date->isToday()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Same-day booking is not allowed. Please select a future date.');
        }

        if (BlockedDate::whereDate('date', $request->appointment_date)->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This date is blocked and unavailable for booking.');
        }

        $philippineHolidays = PhilippineHolidays::range(0, 1);
        if (isset($philippineHolidays[$request->appointment_date])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'The clinic is closed on holidays. Please choose another date.');
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

        $appointmentCount = Appointment::where('appointment_date', $request->appointment_date)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->count();

        if ($appointmentCount >= $schedule->max_slots) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sorry, this date is fully booked. Please select another date.');
        }

        $timeTaken = Appointment::where('appointment_date', $request->appointment_date)
            ->where('appointment_time', $mysqlTime)
            ->whereIn('status', ['upcoming', 'rescheduled'])
            ->exists();

        if ($timeTaken) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sorry, that time slot was already taken. Please choose another time.');
        }

        if (! $isFemalePatient) {
            $request->merge([
                'pregnant' => 'NO',
                'nursing' => 'NO',
                'birth_control' => 'NO',
            ]);
        }

        $signaturePath = $request->file('patient_signature')->store('signatures', 'public');
        $appointment = null;

        DB::transaction(function () use ($request, $signaturePath, $mysqlTime, $patientId, &$appointment) {

            // 1) APPOINTMENT
            $appointment = Appointment::create([
                'patient_id'       => $patientId,
                'service_type'     => $request->service_type,
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $mysqlTime,
                'status'           => 'upcoming',
            ]);

            //  Notify dentists
            $dentists = User::whereHas('role', function ($q) {
                $q->where('slug', 'dentist');
            })->get();

            foreach ($dentists as $dentist) {
                $dentist->notify(new AppointmentBookedNotification($appointment, $appointment->patient));
            }

            //  Notify admins
            $admins = User::whereHas('role', function ($q) {
                $q->where('slug', 'admin');
            })->get();

            foreach ($admins as $admin) {
                $admin->notify(new AppointmentBookedNotification($appointment, $appointment->patient));
            }

            // 2) DENTAL HISTORY (patient-based)
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

            // 3) MEDICAL HISTORY (patient-based)
            $medicalHistory = MedicalHistory::updateOrCreate(
                ['patient_id' => $patientId],
                [
                    'emergency_person'   => $request->emergency_person,
                    'emergency_number'   => $request->emergency_number,
                    'emergency_relation' => $request->emergency_relation,
                    'patient_signature'  => $signaturePath,
                ]
            );

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
                'tobacco_use'       => $request->tobacco_use,
                'headaches'         => $request->headaches,
                'earaches'          => $request->earaches,
                'neck_aches'        => $request->neck_aches,

                // text
                'good_health_details' => $request->good_health_details,
                'treatment_details'   => $request->treatment_details,
                'hospital_details'    => $request->hospital_details,
                'allergy_others'      => $request->allergy_others,
                'medication_details'  => $request->medication_details,
                'tobacco_per_day'     => $request->tobacco_per_day,
                'tobacco_per_week'    => $request->tobacco_per_week,

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
        });

        if ($appointment) {
            AuditLogger::log(
                'create',
                'appointments',
                "Patient booked appointment for {$appointment->appointment_date} at {$appointment->appointment_time}"
            );

            $dentists = User::query()
                ->where('status', 'active')
                ->whereHas('role', function ($query) {
                    $query->where('slug', 'dentist');
                })
                ->get();

            if ($dentists->isNotEmpty()) {
                foreach ($dentists as $dentist) {
                    $dentist->notify(new AppointmentBookedNotification($appointment, $patient));
                }
            }
        }

        return redirect()->route('homepage')->with('success', 'Appointment booked successfully!');
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

        $philippineHolidays = PhilippineHolidays::range(0, 1);
        if (isset($philippineHolidays[$iso])) {
            return response()->json([
                'slots' => [],
                'message' => 'The clinic is closed on holidays.',
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

        return response()->json([
            'slots'      => $schedule->availableSlots($iso, $bookedSlotCounts),
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

        $philippineHolidays = PhilippineHolidays::current();

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

        $appointment->update([
            'appointment_date' => $request->new_appointment_date,
            'appointment_time' => $mysqlTime,
            'service_type' => $request->service_type,
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

        return response()->json(['success' => true]);
    }
}
