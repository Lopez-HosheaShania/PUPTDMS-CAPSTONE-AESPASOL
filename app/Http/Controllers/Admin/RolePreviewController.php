<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Helpers\PhilippineHolidays;
use App\Http\Controllers\Controller;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\ServiceType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolePreviewController extends Controller
{
    private const PREVIEW_ROLES = [
        'dentist',
        'patient',
    ];

    public function start(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user &&
                $user->hasPermission(
                    'access_super_admin_dashboard'
                ),
            403,
            'Unauthorized.'
        );

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in(
                    self::PREVIEW_ROLES
                ),
            ],
        ]);

        $role = strtolower(
            trim(
                $validated['role']
            )
        );

        $request->session()->forget([
            'impersonated_role',
            'impersonated_patient_id',
            'impersonator_role',
            'impersonator_admin_id',
            'impersonator_admin_email',
        ]);

        $request->session()->put([
            'role_preview_active' => true,
            'preview_role' => $role,
            'preview_started_at' => now()->toIso8601String(),
        ]);

        AuditLogger::log(
            'view',
            'roles_permissions',
            "Admin started {$role} role preview"
        );

        return response()->json([
            'success' => true,

            'redirect' => $role === 'patient'
                ? route(
                    'admin.role_preview.patient'
                )
                : route(
                    'admin.role_preview.dentist'
                ),
        ]);
    }

    public function patient(
        Request $request
    ) {
        $this->ensurePreview(
            $request,
            'patient'
        );

        $patient = new Patient();

        $patient->forceFill([
            'name' => 'Demo Patient',
            'email' => 'demo.patient@example.test',
            'phone' => '0917 000 0000',
            'birthdate' => '2004-06-18',
            'gender' => 'Female',
            'student_no' => 'DEMO-2026-001',
            'course_code' => 'BSIT',
            'address' => 'Demo Address, Taguig City',
            'profile_image' => null,
        ]);

        $medicalHistory =
            new MedicalHistory();

        $medicalHistory->forceFill([
            'emergency_person' => 'Demo Guardian',
            'emergency_number' => '0917 111 1111',
            'emergency_relation' => 'Legal Guardian',
        ]);

        $patient->setRelation(
            'medicalHistory',
            $medicalHistory
        );

        $patient->setRelation(
            'user',
            null
        );

        $appointmentDate =
            Carbon::today()
            ->addDays(3);

        $previewServiceType =
            ServiceType::activeForBooking()
            ->orderBy('id')
            ->value('name')
            ?? 'Sample Dental Service';

        $appointments = collect([
            (object) [
                'id' =>
                'preview-appointment-1',

                'status' =>
                'upcoming',

                'appointment_date' =>
                $appointmentDate
                    ->toDateString(),

                'appointment_time' =>
                '10:00:00',

                'service_type_name' =>
                $previewServiceType,

                'dentist_name' =>
                'Demo Dentist',

                'is_follow_up' =>
                false,

                'is_walk_in' =>
                false,
            ],
        ]);

        return view(
            'patient.index',
            [
                'patient' =>
                $patient,

                'appointments' =>
                $appointments,

                'upcomingAppointment' =>
                $appointments->first(),

                'appointmentCountsPerDay' => [
                    $appointmentDate
                        ->toDateString() => 1,
                ],

                'unavailableDates' =>
                [],

                'philippineHolidays' =>
                PhilippineHolidays::recordsRange(
                    0,
                    4
                ),

                'records' =>
                collect(),

                'notifications' =>
                collect(),

                'previousOdontogramByAppointment' =>
                [],

                'reservedBookingReminders' =>
                collect(),

                'documentRequests' =>
                collect(),

                'schedules' =>
                [],

                'rolePreviewActive' =>
                true,

                'previewRole' =>
                'patient',
            ]
        );
    }

    public function dentist(
        Request $request
    ) {
        $this->ensurePreview(
            $request,
            'dentist'
        );

        $today =
            Carbon::today();

        $tomorrow =
            $today->copy()
            ->addDay();

        $nextDay =
            $today->copy()
            ->addDays(2);

        $previewServiceTypes =
            ServiceType::activeForBooking()
            ->orderBy('id')
            ->pluck('name')
            ->filter()
            ->values();

        $firstPreviewService =
            $previewServiceTypes->get(0)
            ?? 'Sample Dental Service';

        $secondPreviewService =
            $previewServiceTypes->get(1)
            ?? $firstPreviewService;

        $calendarAppointmentDetails = [
            $tomorrow->toDateString() => [
                [
                    'id' =>
                    'preview-dentist-1',

                    'name' =>
                    'Sample Patient A',

                    'time' =>
                    '09:00 AM',

                    'service' =>
                    $firstPreviewService,

                    'status' =>
                    'upcoming',

                    'date' =>
                    $tomorrow
                        ->toDateString(),

                    'patientProfileUrl' =>
                    '#',

                    'rescheduleUrl' =>
                    '#',

                    'cancelUrl' =>
                    '#',

                    'is_walk_in' =>
                    false,

                    'is_follow_up' =>
                    false,

                    'is_reserved' =>
                    false,

                    'patientPhotoUrl' =>
                    '',
                ],
            ],

            $nextDay->toDateString() => [
                [
                    'id' =>
                    'preview-dentist-2',

                    'name' =>
                    'Sample Patient B',

                    'time' =>
                    '01:30 PM',

                    'service' =>
                    $secondPreviewService,

                    'status' =>
                    'rescheduled',

                    'date' =>
                    $nextDay
                        ->toDateString(),

                    'patientProfileUrl' =>
                    '#',

                    'rescheduleUrl' =>
                    '#',

                    'cancelUrl' =>
                    '#',

                    'is_walk_in' =>
                    false,

                    'is_follow_up' =>
                    true,

                    'is_reserved' =>
                    false,

                    'patientPhotoUrl' =>
                    '',
                ],
            ],
        ];

        $todayAppointments =
            collect([
                [
                    'id' =>
                    'preview-today-1',

                    'name' =>
                    'Sample Patient C',

                    'status' =>
                    'completed',

                    'is_follow_up' =>
                    false,

                    'is_walk_in' =>
                    false,
                ],

                [
                    'id' =>
                    'preview-today-2',

                    'name' =>
                    'Sample Patient D',

                    'status' =>
                    'upcoming',

                    'is_follow_up' =>
                    true,

                    'is_walk_in' =>
                    false,
                ],
            ]);

        $appointmentCountsPerDay =
            collect(
                $calendarAppointmentDetails
            )
            ->map(
                fn($appointments) =>
                count($appointments)
            )
            ->toArray();

        return view(
            'dentist.dentist-dashboard',
            [
                'todayAppointments' =>
                $todayAppointments,

                'appointmentCountsPerDay' =>
                $appointmentCountsPerDay,

                'calendarAppointmentDetails' =>
                $calendarAppointmentDetails,

                'dashboardAppointmentDetails' =>
                $calendarAppointmentDetails,

                'blockedDates' => [],

                'philippineHolidays' =>
                PhilippineHolidays::recordsRange(
                    1,
                    5
                ),

                'schedules' => [],

                'notifications' =>
                collect(),

                'dentalCasesThisMonth' =>
                12,

                'dentalCasesDelta' =>
                8,

                'totalApptsThisMonth' =>
                18,

                'totalApptsDelta' =>
                5,

                'medicalSupplies' =>
                collect([
                    [
                        'name' =>
                        'Dental Gloves',

                        'qty' => 50,
                        'used' => 18,
                    ],

                    [
                        'name' =>
                        'Face Masks',

                        'qty' => 40,
                        'used' => 12,
                    ],

                    [
                        'name' =>
                        'Cotton Rolls',

                        'qty' => 25,
                        'used' => 8,
                    ],
                ]),

                'medicineSupplies' =>
                collect([
                    [
                        'name' =>
                        'Ibuprofen',

                        'form' =>
                        'Tablet',

                        'qty' => 30,
                        'used' => 9,
                    ],

                    [
                        'name' =>
                        'Paracetamol',

                        'form' =>
                        'Tablet',

                        'qty' => 25,
                        'used' => 7,
                    ],

                    [
                        'name' =>
                        'Oral Antiseptic',

                        'form' =>
                        'Bottle',

                        'qty' => 12,
                        'used' => 4,
                    ],
                ]),

                'gadLabels' => [
                    'Student',
                    'Administrative',
                    'Faculty',
                    'Dependent',
                ],

                'gadFemale' => [
                    4,
                    2,
                    1,
                    1,
                ],

                'gadMale' => [
                    3,
                    2,
                    3,
                    2,
                ],

                'clinicStatus' =>
                'in',

                'rolePreviewActive' =>
                true,

                'previewRole' =>
                'dentist',

                'previewDisplayName' =>
                'Demo Dentist',
            ]
        );
    }

    public function stop(
        Request $request
    ) {
        if (
            (bool) $request->session()->get(
                'role_preview_active',
                false
            )
        ) {
            AuditLogger::log(
                'role_preview_stopped',
                'roles_permissions',
                'Admin stopped role preview'
            );
        }

        $request->session()->forget([
            'role_preview_active',
            'preview_role',
            'preview_started_at',

            'impersonated_role',
            'impersonated_patient_id',
            'impersonator_role',
            'impersonator_admin_id',
            'impersonator_admin_email',
        ]);

        if (
            $request->expectsJson() ||
            $request->ajax()
        ) {
            return response()->json([
                'success' => true,

                'redirect' =>
                route(
                    'admin.role_permissions',
                    [
                        'open_view_as' => 1,
                    ]
                ),
            ]);
        }

        return redirect()
            ->route(
                'admin.role_permissions',
                [
                    'open_view_as' => 1,
                ]
            );
    }

    private function ensurePreview(
        Request $request,
        string $role
    ): void {
        $isPreviewActive =
            (bool) $request->session()->get(
                'role_preview_active',
                false
            );

        $previewRole =
            (string) $request->session()->get(
                'preview_role',
                ''
            );

        abort_unless(
            $isPreviewActive &&
                $previewRole === $role,
            403,
            'Role preview is not active.'
        );
    }
}
