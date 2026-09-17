<?php

use App\Helpers\AuditLogger;
use App\Helpers\PhilippineHolidays;
use App\Http\Controllers\Admin\AdminAppointmentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInventoryController;
use App\Http\Controllers\Admin\AdminPatientController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSessionManagementController;
use App\Http\Controllers\Admin\ClinicScheduleController;
use App\Http\Controllers\Admin\DentistTransitionController;
use App\Http\Controllers\Admin\DocumentRequestController as AdminDocumentRequestController;
use App\Http\Controllers\Admin\DocumentTemplateController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\BackupLoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OIDCController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ClinicSchedule\ReservedBookingPeriodController;
use App\Http\Controllers\Dentist\DentistAppointmentController;
use App\Http\Controllers\Dentist\DentistClinicScheduleController;
use App\Http\Controllers\Dentist\DentistDashboardController;
use App\Http\Controllers\Dentist\DentistPatientController;
use App\Http\Controllers\Dentist\InventoryController;
use App\Http\Controllers\Dentist\OdontogramController;
use App\Http\Controllers\Dentist\WalkInController;
use App\Http\Controllers\Shared\AcademicPeriodController;
use App\Http\Controllers\Shared\DentalRecordController;
use App\Http\Controllers\Shared\ExternalAdminController;
use App\Http\Controllers\Shared\FacultyController;
use App\Http\Controllers\Shared\ServiceTypeController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\Security\SessionManagementController;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\StudentApiService;
use App\Services\IdpHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

// kela matt
Route::get('/faculties', [FacultyController::class, 'getFacultyList'])
    ->middleware([
        'auth',
        'permission:view_faculty_integration,create_faculty_integration,update_faculty_integration',
    ])
    ->name('shared.faculties.index');

Route::get('/faculty-integration', function () {
    $user = Auth::user();

    abort_unless($user, 403);

    if ($user->hasAnyRole(['super_admin', 'admin'])) {
        return redirect()->route('admin.faculty.integration');
    }

    if ($user->hasAnyRole(['dentist', 'dentist_role'])) {
        return redirect()->route('dentist.faculty.integration');
    }

    abort(403);
})
    ->middleware('auth')
    ->name('faculty.integration.redirect');

// routes/web.php---

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

Route::middleware(['web'])->group(function () {
    Route::get('/auth/oidc/redirect', [OIDCController::class, 'redirect'])
        ->name('oidc.redirect');
    Route::get('/auth/oidc/callback', [OIDCController::class, 'callback'])
        ->name('oidc.callback');
});

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH PAGES
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect('/login'));

// Patient Login
Route::get('/login', function (IdpHealthService $idpHealthService) {
    return view('auth.login', $idpHealthService->loginViewData());
})->name('login');
Route::get('/backup-login', [BackupLoginController::class, 'show'])->name('backup.login');

Route::get('/dashboard', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    $impersonatedRole = session('impersonated_role');

    if ($impersonatedRole === 'patient') {
        return redirect()->route('patient.dashboard');
    }

    if ($impersonatedRole === 'dentist') {
        return redirect()->route('dentist.dentist.dashboard');
    }

    $role = strtolower((string) optional(Auth::user()->role)->slug);

    return match ($role) {
        'admin', 'super_admin' => redirect()->route('admin.admin.dashboard'),
        'dentist', 'dentist_role' => redirect()->route('dentist.dentist.dashboard'),
        'patient', 'patient_role' => redirect()->route('patient.dashboard'),
        default => redirect()->route('login'),
    };
})->name('dashboard');

// Patient Register
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH ACTIONS
|--------------------------------------------------------------------------
*/

// Patient Registration
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|min:2|max:255',
        'email' => 'required|email|unique:patients,email|unique:users,email',
        'phone' => 'nullable|string|max:20',
        'birthdate' => 'required|date|before:today|after:120 years ago',
        'gender' => 'required|in:Male,Female',
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
        ],
    ], [
        'name.min' => 'Name must be at least 2 characters.',
        'email.unique' => 'This email is already registered.',
        'email.email' => 'Please enter a valid email address.',
        'birthdate.required' => 'Birthdate is required.',
        'birthdate.before' => 'Birthdate cannot be in the future.',
        'birthdate.after' => 'Please enter a valid birthdate.',
        'gender.required' => 'Please select a gender.',
        'gender.in' => 'Gender must be Male or Female.',
        'password.min' => 'Password must be at least 8 characters.',
        'password.confirmed' => 'Passwords do not match.',
        'password.regex' => 'Password must contain at least one letter, one number, and one special character.',
    ]);

    try {
        DB::transaction(function () use ($validated) {
            $patientRole = Role::where('slug', 'patient')->firstOrFail();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $patientRole->id,
                'status' => 'active',
            ]);

            Patient::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? '',
                'birthdate' => $validated['birthdate'],
                'gender' => $validated['gender'],
                'password' => $user->password,
            ]);
        });

        return redirect('/login')
            ->with('success', 'Account created successfully! You can now log in.');
    } catch (\Exception $e) {
        return redirect()->back()
            ->withInput($request->except('password', 'password_confirmation'))
            ->with('error', 'Something went wrong. Please try again.');
    }
});

// Single Login POST — handles patient, dentist, and admin
// ── PATIENT ──
Route::post('/login', [BackupLoginController::class, 'store'])->name('login.store');

// Logout (all roles)
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::post(
    '/session/expire',
    [
        SessionManagementController::class,
        'expire',
    ]
)->name('session.expire');

Route::post(
    '/session/activity',
    [
        SessionManagementController::class,
        'activity',
    ]
)
    ->middleware('auth')
    ->name('session.activity');

Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::post('/{notificationId}/read', [NotificationController::class, 'markAsRead'])
        ->name('mark-read');

    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('mark-all-read');
});

Route::middleware('auth')->prefix('security')->name('security.')->group(function () {
    Route::get('/sessions', [SessionManagementController::class, 'index'])
        ->name('sessions.index');

    Route::delete('/sessions/current', [SessionManagementController::class, 'destroyCurrent'])
        ->name('sessions.destroy-current');

    Route::delete('/sessions/all', [SessionManagementController::class, 'destroyAll'])
        ->name('sessions.destroy-all');

    Route::delete('/sessions/{reference}', [SessionManagementController::class, 'destroy'])
        ->name('sessions.destroy');

    Route::delete('/sessions', [SessionManagementController::class, 'destroyOthers'])
        ->name('sessions.destroy-others');
});

Route::get(
    '/clinical/patients/search',
    [WalkInController::class, 'searchPatient']
)
    ->middleware([
        'auth',
        'permission:view_patient_profiles,manage_existing_records,manage_walk_in_patients',
    ])
    ->name('shared.existing-record.search-patient');

/*
|--------------------------------------------------------------------------
| ADMIN / SUPER ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/faculty-integration', function () {
            return view('shared.faculty-integration');
        })
            ->middleware('permission:view_faculty_integration,create_faculty_integration,update_faculty_integration')
            ->name('admin.faculty.integration');

        Route::post('/faculty-integration/store', [FacultyController::class, 'store'])
            ->middleware('permission:create_faculty_integration')
            ->name('admin.faculty.store');

        /*
        |--------------------------------------------------------------------------
        | CMS ACCESS (External Admin Integration)
        |--------------------------------------------------------------------------
        */
        Route::get('/assign-cms-access', [ExternalAdminController::class, 'index'])
            ->middleware('permission:view_cms_integration')
            ->name('admin.assign-cms-access');

        Route::post('/assign-cms-access', [ExternalAdminController::class, 'store'])
            ->middleware('permission:create_cms_integration')
            ->name('admin.assign-cms-access.store');

        Route::get('/external-admins/search', [ExternalAdminController::class, 'search'])
            ->middleware('permission:view_cms_integration')
            ->name('admin.external_admins.search');

        Route::get('/external-admins/{adminId}', [ExternalAdminController::class, 'show'])
            ->middleware('permission:view_cms_integration')
            ->name('admin.external_admins.show');

        /*
        |--------------------------------------------------------------------------
        | ADMIN DASHBOARD
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('permission:access_super_admin_dashboard')
            ->name('admin.admin.dashboard');

        Route::get('/dental-records', [DentalRecordController::class, 'index'])
            ->middleware('permission:view_dental_records')
            ->name('admin.dental-records.index');

        Route::get('/dental-records/{appointment}', [DentalRecordController::class, 'show'])
            ->middleware('permission:view_dental_records')
            ->name('admin.dental-records.show');

        Route::get('/reports-index', function () {
            return redirect()->route('admin.reports');
        })->name('admin.reports.index');

        Route::get('/appointments-alias', function () {
            return redirect()->route('admin.admin.appointments');
        })->name('admin.appointments');

        /*
        |--------------------------------------------------------------------------
        | REPORTS & ANALYTICS
        |--------------------------------------------------------------------------
        */
        Route::get('/report-files', [AdminReportController::class, 'reportFiles'])
            ->middleware('permission:create_report_files')
            ->name('admin.report-files');

        Route::get('/reports', [AdminReportController::class, 'index'])
            ->middleware('permission:view_reports,view_ai_reports,create_ai_generative_reports')
            ->name('admin.reports');

        Route::get('/reports/ai-generated', [AdminReportController::class, 'aiGenerated'])
            ->middleware('permission:view_ai_reports')
            ->name('admin.reports.ai-generated');

        Route::get('/reports/ai-generated/download', [AdminReportController::class, 'downloadAiGenerated'])
            ->middleware('permission:create_ai_generative_reports')
            ->name('admin.reports.ai-generated.download');

        /*
        |--------------------------------------------------------------------------
        | ROLE & PERMISSIONS
        |--------------------------------------------------------------------------
        */
        Route::get('/role-permissions', [RolePermissionController::class, 'index'])
            ->middleware('permission:view_roles_permissions')
            ->name('admin.role_permissions');

        Route::post('/role-permissions/update', [RolePermissionController::class, 'update'])
            ->middleware('permission:update_role_permissions')
            ->name('admin.role_permissions.update');

        Route::post('/role-permissions/reset', [RolePermissionController::class, 'reset'])
            ->middleware('permission:update_role_permissions')
            ->name('admin.role_permissions.reset');

        Route::post('/role-permissions/store-role', [RolePermissionController::class, 'storeRole'])
            ->middleware('permission:create_custom_roles')
            ->name('admin.role_permissions.store_role');

        Route::match(['post', 'delete'], '/role-permissions/{id}/destroy', [RolePermissionController::class, 'destroyRole'])
            ->middleware('permission:delete_custom_roles')
            ->name('admin.role_permissions.destroy_role');

        /*
        |--------------------------------------------------------------------------
        | SYSTEM LOGS
        |--------------------------------------------------------------------------
        */
        Route::get('/system-logs', [SystemLogController::class, 'index'])
            ->middleware('permission:view_system_logs,export_system_logs,archive_system_logs')
            ->name('admin.system_logs');

        Route::get('/session-management', [AdminSessionManagementController::class, 'index'])
            ->middleware('permission:manage_audit_trail')
            ->name('admin.session_management.index');

        Route::delete('/session-management/sessions/{reference}', [AdminSessionManagementController::class, 'destroySession'])
            ->middleware('permission:manage_audit_trail')
            ->name('admin.session_management.destroy_session');

        Route::delete('/session-management/users/{user}', [AdminSessionManagementController::class, 'destroyUserSessions'])
            ->middleware('permission:manage_audit_trail')
            ->name('admin.session_management.destroy_user_sessions');

        Route::get('/system-logs/fetch', [SystemLogController::class, 'fetchLatest'])
            ->middleware('permission:view_system_logs,export_system_logs,archive_system_logs')
            ->name('admin.system_logs.fetch');

        Route::get('/system-logs/check', [SystemLogController::class, 'checkLatest'])
            ->middleware('permission:view_system_logs,export_system_logs,archive_system_logs')
            ->name('admin.system_logs.check');

        Route::get('/system-logs/export', [SystemLogController::class, 'export'])
            ->middleware('permission:export_system_logs')
            ->name('admin.system_logs.export');

        Route::post('/system-logs/archive', [SystemLogController::class, 'archive'])
            ->middleware('permission:archive_system_logs')
            ->name('admin.system_logs.archive');

        /*
        |--------------------------------------------------------------------------
        | PATIENT DIRECTORY
        |--------------------------------------------------------------------------
        */
        Route::get('/patient-directory', [AdminPatientController::class, 'index'])
            ->middleware('permission:view_patient_profiles')
            ->name('admin.patient_directory');

        Route::get('/patients', [AdminPatientController::class, 'index'])
            ->middleware('permission:view_patient_profiles')
            ->name('admin.admin.patients');

        Route::get('/patient/{patient}', [AdminPatientController::class, 'show'])
            ->middleware('permission:view_patient_profiles')
            ->name('admin.admin.patient.profile');

        /*
        |--------------------------------------------------------------------------
        | APPOINTMENTS
        |--------------------------------------------------------------------------
        */
        Route::get('/appointments', [AdminAppointmentController::class, 'index'])
            ->middleware('permission:view_appointments')
            ->name('admin.admin.appointments');

        Route::get('/add-existing-record', [\App\Http\Controllers\Shared\ExistingRecordController::class, 'index'])
            ->middleware('permission:manage_existing_records')
            ->name('admin.existing-record.index');

        Route::get('/patients/{patient}/existing-appointment', [\App\Http\Controllers\Dentist\OdontogramController::class, 'createExistingAppointment'])
            ->middleware('permission:create_procedure_records')
            ->name('admin.odontogram.existing-appointment.create');

        Route::post('/patients/{patient}/existing-appointment', [\App\Http\Controllers\Dentist\OdontogramController::class, 'storeExistingAppointmentIntake'])
            ->middleware('permission:create_medical_records')
            ->name('admin.odontogram.existing-appointment.intake.store');

        Route::patch('/patients/{patient}/existing-appointment/history/autosave', [\App\Http\Controllers\Dentist\OdontogramController::class, 'autosaveExistingAppointmentHistory'])
            ->middleware('permission:create_medical_records')
            ->name('admin.odontogram.existing-appointment.history.autosave');

        Route::get('/existing-appointment/slots', [\App\Http\Controllers\Dentist\OdontogramController::class, 'existingAppointmentSlotsForDate'])
            ->middleware('permission:create_procedure_records')
            ->name('admin.odontogram.existing-appointment.slots');

        Route::get('/patients/{patient}/existing-appointment/odontogram', [\App\Http\Controllers\Dentist\OdontogramController::class, 'showExistingAppointmentOdontogram'])
            ->middleware('permission:create_procedure_records')
            ->name('admin.odontogram.existing-appointment.odontogram');

        Route::post('/patients/{patient}/existing-appointment/save', [\App\Http\Controllers\Dentist\OdontogramController::class, 'storeExistingAppointment'])
            ->middleware('permission:create_procedure_records')
            ->name('admin.odontogram.existing-appointment.store');

        /*
        |--------------------------------------------------------------------------
        | PATIENT LIST (FOR IMPERSONATION)
        |--------------------------------------------------------------------------
        */
        Route::get('/patients/list', function () {
            $user = Auth::user();

            if (! $user || ! $user->hasPermission('view_patient_profiles')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $patients = Patient::query()
                ->with([
                    'information' => function ($query) {
                        $query->select([
                            'id',
                            'patient_id',
                            'phone',
                        ]);
                    },
                ])
                ->select([
                    'id',
                    'name',
                    'email',
                ])
                ->orderBy('name')
                ->get()
                ->map(function (Patient $patient) {
                    return [
                        'id' => $patient->id,
                        'name' => $patient->name,
                        'email' => $patient->email,
                        'phone' => $patient->information?->phone,
                    ];
                })
                ->values();

            return response()->json($patients);
        })->name('admin.patients.list');

        /*
        |--------------------------------------------------------------------------
        | ACADEMIC PERIODS
        |--------------------------------------------------------------------------
        */
        Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])
            ->middleware('permission:view_academic_periods')
            ->name('admin.academic_periods');

        Route::post('/academic-periods', [AcademicPeriodController::class, 'store'])
            ->middleware('permission:create_academic_period')
            ->name('admin.academic_periods.store');

        Route::put('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update'])
            ->middleware('permission:update_academic_period')
            ->name('admin.academic_periods.update');

        Route::delete('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'destroy'])
            ->middleware('permission:delete_academic_period')
            ->name('admin.academic_periods.destroy');

        Route::patch('/academic-periods/{academicPeriod}/set-active', [AcademicPeriodController::class, 'setActive'])
            ->middleware('permission:update_academic_period')
            ->name('admin.academic_periods.set_active');

        Route::post('/academic-periods/sync-flss', [AcademicPeriodController::class, 'syncFromFlss'])
            ->middleware('permission:update_academic_period')
            ->name('admin.academic_periods.sync_flss');

        /*
        |--------------------------------------------------------------------------
        | CLINIC SCHEDULE
        |--------------------------------------------------------------------------
        */
        Route::get('/clinic-schedule', [ClinicScheduleController::class, 'index'])
            ->middleware('permission:view_clinic_schedule')
            ->name('admin.clinic_schedule');

        Route::post('/clinic-schedule', [ClinicScheduleController::class, 'store'])
            ->middleware('permission:create_clinic_schedule')
            ->name('admin.clinic_schedule.store');

        Route::put('/clinic-schedule/rules/{clinicSchedule}', [ClinicScheduleController::class, 'update'])
            ->middleware('permission:update_clinic_schedule')
            ->name('admin.clinic_schedule.update');

        Route::delete('/clinic-schedule/rules/{clinicSchedule}', [ClinicScheduleController::class, 'destroy'])
            ->middleware('permission:delete_clinic_schedule')
            ->name('admin.clinic_schedule.destroy');

        Route::post('/clinic-schedule/reserved-periods', [ReservedBookingPeriodController::class, 'store'])
            ->middleware('permission:create_clinic_schedule')
            ->name('admin.clinic_schedule.reserved_periods.store');

        Route::put('/clinic-schedule/reserved-periods/{reservedBookingPeriod}', [ReservedBookingPeriodController::class, 'update'])
            ->middleware('permission:update_clinic_schedule')
            ->name('admin.clinic_schedule.reserved_periods.update');

        Route::delete('/clinic-schedule/reserved-periods/{reservedBookingPeriod}', [ReservedBookingPeriodController::class, 'destroy'])
            ->middleware('permission:delete_clinic_schedule')
            ->name('admin.clinic_schedule.reserved_periods.destroy');

        Route::post('/clinic-schedule/block-date', [ClinicScheduleController::class, 'blockDate'])
            ->middleware('permission:create_clinic_schedule')
            ->name('admin.clinic_schedule.block');

        Route::delete('/clinic-schedule/block-date/{blockedDate}', [ClinicScheduleController::class, 'unblockDate'])
            ->middleware('permission:delete_clinic_schedule')
            ->name('admin.clinic_schedule.unblock');

        Route::get('/clinic-schedule/unavailable-dates', [ClinicScheduleController::class, 'unavailableDates'])
            ->middleware('permission:view_clinic_schedule')
            ->name('admin.clinic_schedule.unavailable_dates');

        Route::get('/clinic-schedule/slots', [ClinicScheduleController::class, 'slotsForDate'])
            ->middleware('permission:view_clinic_schedule')
            ->name('admin.clinic_schedule.slots');

        // INVENTORY
        Route::get('/inventory', [AdminInventoryController::class, 'index'])
            ->middleware('permission:view_inventory')
            ->name('admin.inventory');

        Route::get('/inventory/data', [AdminInventoryController::class, 'fetch'])
            ->middleware('permission:view_inventory')
            ->name('admin.inventory.data');

        Route::post('/inventory', [AdminInventoryController::class, 'store'])
            ->middleware('permission:add_inventory')
            ->name('admin.inventory.store');

        Route::put('/inventory/{inventory}', [AdminInventoryController::class, 'update'])
            ->middleware('permission:update_inventory')
            ->name('admin.inventory.update');

        Route::delete('/inventory/{inventory}', [AdminInventoryController::class, 'destroy'])
            ->middleware('permission:delete_inventory')
            ->name('admin.inventory.destroy');
    });

// DOCUMENT REQUEST
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/document-requests', [AdminDocumentRequestController::class, 'index'])
            ->middleware('permission:view_document_requests')
            ->name('admin.document-requests.index');

        Route::get('/document-requests/data', [AdminDocumentRequestController::class, 'data'])
            ->middleware('permission:view_document_requests')
            ->name('admin.document-requests.data');

        Route::get('/document-requests/export', [AdminDocumentRequestController::class, 'export'])
            ->middleware('permission:view_document_requests')
            ->name('admin.document-requests.export');

        Route::get('/document-requests/print-queue', [AdminDocumentRequestController::class, 'printQueue'])
            ->middleware('permission:view_document_requests')
            ->name('admin.document-requests.print-queue');

        Route::get('/document-requests/{id}', [AdminDocumentRequestController::class, 'show'])
            ->middleware('permission:view_document_requests')
            ->name('admin.document-requests.show');

        Route::patch('/document-requests/{id}/approve', [AdminDocumentRequestController::class, 'approve'])
            ->middleware('permission:view_document_requests')
            ->middleware('permission:approve_document_requests')
            ->name('admin.document-requests.approve');

        Route::patch('/document-requests/{id}/reject', [AdminDocumentRequestController::class, 'reject'])
            ->middleware('permission:view_document_requests')
            ->middleware('permission:reject_document_requests')
            ->name('admin.document-requests.reject');
    });

// DOCUMENT TEMPLATES (SIMPLIFIED)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        // LIST PAGE
        Route::get('/document-template', [DocumentTemplateController::class, 'index'])
            ->middleware('permission:manage_document_templates')
            ->name('document-template');

        // PREVIEW (AJAX)
        Route::get('/document-template/{id}', [DocumentTemplateController::class, 'show'])
            ->middleware('permission:manage_document_templates')
            ->name('document-template.show');

        // ARCHIVE
        Route::patch('/document-template/{id}/archive', [DocumentTemplateController::class, 'archive'])
            ->middleware('permission:manage_document_templates')
            ->name('document-template.archive');

        // ACTIVATE
        Route::patch('/document-template/{id}/activate', [DocumentTemplateController::class, 'activate'])
            ->middleware('permission:manage_document_templates')
            ->name('document-template.activate');

        // SET DEFAULT
        Route::patch('/document-template/{id}/default', [DocumentTemplateController::class, 'setDefault'])
            ->middleware('permission:manage_document_templates')
            ->name('document-template.default');
    });

// SYSTEM SETTINGS
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/system-settings', [SystemSettingsController::class, 'index'])
            ->middleware('permission:manage_system_settings,set_notification_rules')
            ->name('admin.system_settings');

        Route::post('/system-settings', [SystemSettingsController::class, 'update'])
            ->middleware('permission:manage_system_settings,set_notification_rules')
            ->name('admin.system_settings.update');
    });
/*
|--------------------------------------------------------------------------
| ADMIN USER MANAGEMENT ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/user-management', [UserManagementController::class, 'index'])
            ->middleware('permission:view_account_details,create_users,disable_users,update_user_role,update_user_password')
            ->name('admin.user_management');

        Route::post('/user-management', [UserManagementController::class, 'store'])
            ->middleware('permission:create_users')
            ->name('admin.user_management.store');

        Route::put('/user-management/{user}', [UserManagementController::class, 'update'])
            ->middleware('permission:update_user_role')
            ->name('admin.user_management.update');

        Route::post('/user-management/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
            ->middleware('permission:update_user_password')
            ->name('admin.user_management.reset_password');

        Route::post('/user-management/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
            ->middleware('permission:disable_users')
            ->name('admin.user_management.toggle_status');

        /*
        |--------------------------------------------------------------------------
        | DENTIST CONTINUITY MANAGEMENT
        |--------------------------------------------------------------------------
        */
        Route::get('/dentist-transitions', [DentistTransitionController::class, 'index'])
            ->name('admin.dentist-transitions.index');

        Route::get('/dentist-transitions/create', [DentistTransitionController::class, 'create'])
            ->name('admin.dentist-transitions.create');

        Route::post('/dentist-transitions', [DentistTransitionController::class, 'store'])
            ->name('admin.dentist-transitions.store');

        Route::get('/dentist-transitions/{transition}', [DentistTransitionController::class, 'show'])
            ->name('admin.dentist-transitions.show');

        Route::get('/dentist-transitions/{transition}/edit', [DentistTransitionController::class, 'edit'])
            ->name('admin.dentist-transitions.edit');

        Route::put('/dentist-transitions/{transition}', [DentistTransitionController::class, 'update'])
            ->name('admin.dentist-transitions.update');

        Route::post('/dentist-transitions/{transition}/generate-items', [DentistTransitionController::class, 'generateItems'])
            ->name('admin.dentist-transitions.generate-items');

        Route::put('/dentist-transitions/{transition}/assignments', [DentistTransitionController::class, 'assignments'])
            ->name('admin.dentist-transitions.assignments');

        Route::put('/dentist-transitions/{transition}/checklist', [DentistTransitionController::class, 'checklist'])
            ->name('admin.dentist-transitions.checklist');

        Route::post('/dentist-transitions/{transition}/finalize', [DentistTransitionController::class, 'finalize'])
            ->name('admin.dentist-transitions.finalize');

        Route::post('/dentist-transitions/{transition}/cancel', [DentistTransitionController::class, 'cancel'])
            ->name('admin.dentist-transitions.cancel');

        Route::post('/dentist-transitions/{transition}/extend-access', [DentistTransitionController::class, 'extendAccess'])
            ->name('admin.dentist-transitions.extend-access');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/service-types', [ServiceTypeController::class, 'index'])
            ->middleware('permission:view_service_type,create_service_type,delete_service_type,update_default_service_type')
            ->name('service-types');
        Route::post('/service-types', [ServiceTypeController::class, 'store'])
            ->middleware('permission:create_service_type')
            ->name('service-types.store');
        Route::put('/service-types/{id}', [ServiceTypeController::class, 'update'])
            ->middleware('permission:update_default_service_type')
            ->name('service-types.update');
        Route::delete('/service-types/{id}', [ServiceTypeController::class, 'destroy'])
            ->middleware('permission:delete_service_type')
            ->name('service-types.destroy');
    });

/*
|--------------------------------------------------------------------------
| ADMIN IMPERSONATION ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/impersonate', function (Request $request) {

    /** @var \App\Models\User $user */
    $user = Auth::user();

    if (! $user || ! $user->hasPermission('access_super_admin_dashboard')) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $request->validate([
        'role' => 'required|string',
    ]);

    $targetRole = strtolower(trim($request->role));

    if (! session()->has('impersonator_role')) {
        session([
            'impersonator_role' => optional(Auth::user()->role)->slug,
            'impersonator_admin_id' => Auth::id(),
            'impersonator_admin_email' => Auth::user()->email,
        ]);
    }

    if (in_array($targetRole, ['dentist', 'dentist_role'], true)) {

        session([
            'impersonated_role' => 'dentist',
        ]);

        session()->forget(['impersonated_patient_id']);

        AuditLogger::log(
            'impersonation_started',
            'authentication',
            'Admin started impersonating Dentist dashboard'
        );

        return response()->json([
            'redirect' => route('dentist.dentist.dashboard'),
        ]);
    }

    if (in_array($targetRole, ['patient', 'patient_role'], true)) {

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
        ]);

        $patient = Patient::find($request->patient_id);

        if (! $patient) {
            return response()->json([
                'message' => 'Selected patient not found.',
            ], 422);
        }

        session([
            'impersonated_role' => 'patient',
            'impersonated_patient_id' => $patient->id,
        ]);

        AuditLogger::log(
            'impersonation_started',
            'authentication',
            'Admin started impersonating Patient ID ' . $patient->id
        );

        return response()->json([
            'redirect' => route('patient.dashboard'),
        ]);
    }

    return response()->json([
        'message' => 'Unsupported role: ' . $targetRole,
    ], 422);
})->name('admin.impersonate');

Route::post('/stop-impersonation', function () {

    /** @var \App\Models\User $user */
    $user = Auth::user();

    if (! $user || ! $user->hasAnyRole(['super_admin', 'admin'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    if (session()->has('impersonator_role')) {
        AuditLogger::log(
            'impersonation_stopped',
            'authentication',
            'Admin stopped impersonation'
        );
    }

    session()->forget([
        'impersonated_role',
        'impersonated_patient_id',
        'impersonator_role',
        'impersonator_admin_id',
        'impersonator_admin_email',
    ]);

    return redirect()->route('admin.admin.dashboard');
})->name('admin.stop_impersonation');

/*
|--------------------------------------------------------------------------
| PATIENT-PROTECTED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['role:patient'])->group(function () {

    Route::get('/homepage', [HomepageController::class, 'index'])
        ->middleware('permission:access_patient_dashboard')
        ->name('homepage');

    Route::get('/book-appointment', [AppointmentController::class, 'create'])
        ->middleware('permission:book_appointments')
        ->name('patient.book.appointment');

    Route::get('/book-appointment/reserved/{reservedBookingPeriod}', [AppointmentController::class, 'create'])
        ->middleware('permission:book_appointments')
        ->name('book.appointment.reserved');

    Route::get('/available-slots', [AppointmentController::class, 'availableSlots'])
        ->middleware('permission:book_appointments')
        ->name('patient.appointments.available-slots');

    Route::post('/document-requests', [DocumentRequestController::class, 'store'])
        ->middleware('permission:request_documents')
        ->name('patient.document.requests.store');

    Route::get('/document-requests', [DocumentRequestController::class, 'index'])
        ->middleware('permission:request_documents')
        ->name('patient.document.requests.index');

    Route::get('/document-requests/{id}/approved', function ($id) {
        return redirect()->route('patient.document.requests.index', [
            'status' => 'approved',
            'highlight' => $id,
        ]);
    })
        ->middleware('permission:request_documents')
        ->name('patient.document.approved');

    Route::post('/document-requests/{id}/status', [DocumentRequestController::class, 'updateStatus'])
        ->middleware('permission:request_documents')
        ->name('patient.document.requests.updateStatus');

    Route::get('/record', [RecordController::class, 'index'])
        ->middleware('permission:view_own_records')
        ->name('patient.record');

    Route::get('/about-us', fn() => view('patient.about-us'))
        ->name('patient.about.us');

    // Clinic Schedule
    Route::get('/book-appointment/slots', [AppointmentController::class, 'slotsForDate'])
        ->middleware('permission:book_appointments')
        ->name('book.appointment.slots');
});

/*
|--------------------------------------------------------------------------
| PATIENT ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('patient')->middleware(['role:patient'])->group(function () {
    Route::get('/dashboard', function () {

        if (session()->has('impersonated_patient_id')) {
            $patient = Patient::find(session('impersonated_patient_id'));
        } else {
            $user = Auth::user();
            $patient = $user?->patient;

            if ($patient && $user?->email && $patient->email !== $user->email) {
                $patient->forceFill(['email' => $user->email])->save();
            }
        }

        return view('patient.index', compact('patient'));
    })->middleware('permission:access_patient_dashboard')->name('patient.dashboard');

    Route::get('/appointment', [AppointmentController::class, 'index'])
        ->middleware('permission:view_own_appointments,book_appointments')
        ->name('patient.appointment.index');

    Route::get('/appointment/create', [AppointmentController::class, 'create'])
        ->middleware('permission:book_appointments')
        ->name('appointment.create');

    Route::post('/appointment', [AppointmentController::class, 'store'])
        ->middleware('permission:book_appointments')
        ->name('appointment.store');

    Route::get('/book-appointment', [AppointmentController::class, 'create'])
        ->middleware('permission:book_appointments')
        ->name('book.appointment.create');

    Route::post('/book-appointment', [AppointmentController::class, 'store'])
        ->middleware('permission:book_appointments')
        ->name('book.appointment.store');

    Route::get('/book-appointment/draft', [AppointmentController::class, 'getDraft'])
        ->middleware('permission:book_appointments')
        ->name('book.appointment.draft.show');

    Route::put('/book-appointment/draft', [AppointmentController::class, 'saveDraft'])
        ->middleware('permission:book_appointments')
        ->name('book.appointment.draft.save');

    Route::delete('/book-appointment/draft', [AppointmentController::class, 'deleteDraft'])
        ->middleware('permission:book_appointments')
        ->name('book.appointment.draft.delete');

    Route::post('/book-appointment/validate-signature', [AppointmentController::class, 'validateSignature'])
        ->middleware('permission:book_appointments')
        ->name('book.appointment.validate-signature');

    Route::get('/signature-review', [AppointmentController::class, 'showSignatureReview'])
        ->middleware('permission:book_appointments')
        ->name('patient.signature-review.show');

    Route::post('/signature-review', [AppointmentController::class, 'updateSignatureReview'])
        ->middleware('permission:book_appointments')
        ->name('patient.signature-review.update');

    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->middleware('permission:view_own_appointments,book_appointments')
        ->name('book.appointment.index');

    Route::get('/appointments/cancelled', function () {
        return view('patient.cancelled');
    })->name('patient.appointment.cancelled.view');
});

Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/patients/{patient}/signature/invalid', [AppointmentController::class, 'markSignatureInvalid'])
        ->name('admin.patient.signature.invalid');
});

/*
|--------------------------------------------------------------------------
| DENTIST ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('dentist')->middleware(['auth'])->group(function () {
    Route::post('/patients/{patient}/signature/invalid', [AppointmentController::class, 'markSignatureInvalid'])
        ->middleware('permission:create_medical_records')
        ->name('dentist.patient.signature.invalid');
    Route::get('/dashboard', [DentistDashboardController::class, 'index'])
        ->middleware('permission:access_dentist_dashboard')
        ->name('dentist.dentist.dashboard');

    // Appointments
    Route::get('/appointments', [DentistAppointmentController::class, 'index'])
        ->middleware('permission:view_appointments,reschedule_appointments,cancel_appointments,create_follow_up_appointments,create_procedure_records')
        ->name('dentist.dentist.appointments');

    Route::get('/appointments/{appointment}/patient-profile', [DentistAppointmentController::class, 'patientProfile'])
        ->middleware('permission:view_appointments,reschedule_appointments,cancel_appointments,create_follow_up_appointments,create_procedure_records')
        ->name('dentist.dentist.appointments.patientProfile');

    Route::put('/appointments/{id}/reschedule', [DentistAppointmentController::class, 'updateReschedule'])
        ->middleware('permission:reschedule_appointments')
        ->name('dentist.dentist.appointments.reschedule.update');

    Route::post('/appointments/{id}/follow-up', [DentistAppointmentController::class, 'storeFollowUp'])
        ->middleware('permission:create_follow_up_appointments')
        ->name('dentist.dentist.appointments.follow-up.store');

    Route::get('/appointment-slots', [AppointmentController::class, 'slotsForDate'])
        ->middleware('permission:reschedule_appointments')
        ->name('dentist.appointment.slots');

    Route::post('/appointments/{id}/cancel', [DentistAppointmentController::class, 'cancel'])
        ->middleware('permission:cancel_appointments')
        ->name('dentist.dentist.appointments.cancel');

    Route::get('/appointments/{id}/start', [DentistAppointmentController::class, 'start'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.dentist.appointments.start');

    /*
|--------------------------------------------------------------------------
| DENTIST CLINIC SCHEDULE
|--------------------------------------------------------------------------
*/
    Route::get('/clinic-schedule', [DentistClinicScheduleController::class, 'index'])
        ->middleware('permission:view_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule');

    Route::post('/clinic-schedule', [DentistClinicScheduleController::class, 'store'])
        ->middleware('permission:create_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.store');

    Route::put('/clinic-schedule/rules/{clinicSchedule}', [DentistClinicScheduleController::class, 'update'])
        ->middleware('permission:update_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.update');

    Route::delete('/clinic-schedule/rules/{clinicSchedule}', [DentistClinicScheduleController::class, 'destroy'])
        ->middleware('permission:delete_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.destroy');

    Route::post('/clinic-schedule/reserved-periods', [ReservedBookingPeriodController::class, 'store'])
        ->middleware('permission:create_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.reserved_periods.store');

    Route::put('/clinic-schedule/reserved-periods/{reservedBookingPeriod}', [ReservedBookingPeriodController::class, 'update'])
        ->middleware('permission:update_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.reserved_periods.update');

    Route::delete('/clinic-schedule/reserved-periods/{reservedBookingPeriod}', [ReservedBookingPeriodController::class, 'destroy'])
        ->middleware('permission:delete_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.reserved_periods.destroy');

    Route::post('/clinic-schedule/block-date', [DentistClinicScheduleController::class, 'blockDate'])
        ->middleware('permission:create_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.block');

    Route::delete('/clinic-schedule/block-date/{blockedDate}', [DentistClinicScheduleController::class, 'unblockDate'])
        ->middleware('permission:delete_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.unblock');

    Route::get('/clinic-schedule/unavailable-dates', [DentistClinicScheduleController::class, 'unavailableDates'])
        ->middleware('permission:view_clinic_schedule,update_clinic_schedule,create_clinic_schedule,delete_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.unavailable_dates');

    Route::get('/clinic-schedule/slots', [DentistClinicScheduleController::class, 'slotsForDate'])
        ->middleware('permission:view_clinic_schedule,update_clinic_schedule,create_clinic_schedule,delete_clinic_schedule')
        ->name('dentist.dentist.clinic_schedule.slots');

    // Patients
    Route::get('/patients', [DentistPatientController::class, 'index'])
        ->middleware('permission:view_patient_profiles')
        ->name('dentist.dentist.patients');

    Route::get('/patients/{patient}/profile', [DentistPatientController::class, 'profile'])
        ->middleware('permission:view_patient_profiles,view_dental_records')
        ->name('dentist.dentist.patient.profile');

    // Report Page
    Route::get('/report', [\App\Http\Controllers\Dentist\DentistReportController::class, 'index'])
        ->middleware('permission:view_reports,create_report_files')
        ->name('dentist.dentist.report');

    Route::get('/report/gad-data', [\App\Http\Controllers\Dentist\DentistReportController::class, 'gadData'])
        ->middleware('permission:view_reports,create_report_files')
        ->name('dentist.dentist.report.gad-data');

    Route::post('/report/gad-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadGadReport'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.gad-download');

    Route::post('/report/annual-dental-clearance-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadAnnualDentalClearance'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.annual-clearance-download');

    Route::post('/report/dental-clearance-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadDentalClearance'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.dental-clearance-download');

    Route::post('/report/dental-services-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadDentalServicesReport'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.dental-services-download');

    Route::post('/report/medicine-inventory-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadMedicineInventoryReport'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.medicine-inventory-download');

    Route::post('/report/dpt-download', [\App\Http\Controllers\Dentist\DptReportController::class, 'download'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.dpt-download');

    Route::post('/report/daily-treatment-record-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadDailyTreatmentRecordReport'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.daily-treatment-record-download');

    Route::post('/report/dental-health-record-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadDentalHealthRecord'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.dental-health-record-download');

    Route::post('/report/dental-supplies-inventory-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadDentalSuppliesInventoryReport'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.dental-supplies-inventory-download');

    Route::post('/report/dental-cases-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadDentalCasesReport'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.dental-cases-download');

    Route::post('/report/monthly-report-download', [\App\Http\Controllers\Dentist\DentistReportController::class, 'downloadMonthlyReport'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.monthly-report-download');

    Route::get('/report/weekly-data', [\App\Http\Controllers\Dentist\DentistReportController::class, 'weeklyData'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.weekly-data');

    Route::get('/report/daily-treatment-record', [\App\Http\Controllers\Dentist\DentistReportController::class, 'dailyTreatmentRecord'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.daily-treatment');

    Route::get('/report/dental-services', [\App\Http\Controllers\Dentist\DentalServicesRecordController::class, 'index'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.dental-services');

    Route::get('/report/dental-services/data', [\App\Http\Controllers\Dentist\DentalServicesRecordController::class, 'data'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.dental-services.data');

    Route::get('/report/daily-treatment-record/list', [\App\Http\Controllers\Dentist\DentistReportController::class, 'dailyTreatmentRecordList'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.reports.daily-treatment-record.list');



    Route::get('/report/templates/{template}/print', [\App\Http\Controllers\Dentist\DentistReportController::class, 'printTemplate'])
        ->middleware('permission:create_report_files')
        ->name('dentist.dentist.report.templates.print');

    // Walk-in Patients
    Route::get('/walk-in', [WalkInController::class, 'index'])
        ->middleware('permission:manage_walk_in_patients')
        ->name('dentist.walk-in.index');

    Route::get('/walk-in/search-patient', [WalkInController::class, 'searchPatient'])
        ->middleware('permission:view_patient_profiles')
        ->name('dentist.walk-in.search-patient');

    Route::get(
        '/walk-in/patients/{patient}/booking-information',
        [WalkInController::class, 'patientBookingInformation']
    )
        ->middleware('permission:manage_walk_in_patients')
        ->name('dentist.walk-in.patient-booking-information');

    Route::post('/walk-in/guest', [WalkInController::class, 'storeGuest'])
        ->middleware('permission:manage_walk_in_patients')
        ->name('dentist.walk-in.guest.store');
    Route::post('/walk-in/start', [WalkInController::class, 'startWalkIn'])
        ->middleware('permission:manage_walk_in_patients')
        ->name('dentist.walk-in.start');

    Route::post(
        '/walk-in/resolve-external-patient',
        [WalkInController::class, 'resolveExternalPatient']
    )
        ->middleware('permission:manage_walk_in_patients')
        ->name('dentist.walk-in.external.resolve');

    Route::get('/add-existing-record', [\App\Http\Controllers\Shared\ExistingRecordController::class, 'index'])
        ->middleware('permission:manage_existing_records')
        ->name('dentist.existing-record.index');

    // Document Requests
    //     if (session('role') !== 'dentist') {
    //         return redirect('/login');
    //     }
    //     return view('dentist-report');
    // })->name('dentist.report');

    // Document Requests – list page
    Route::get('/document-requests', [DocumentRequestController::class, 'dentistIndex'])
        ->middleware('permission:view_document_requests,approve_document_requests,reject_document_requests')
        ->name('dentist.dentist.documentrequests');

    // Approve (AJAX POST)
    Route::post('/document-requests/{id}/approve', [DocumentRequestController::class, 'approve'])
        ->middleware('permission:view_document_requests')
        ->middleware('permission:approve_document_requests')
        ->name('dentist.dentist.documentrequests.approve');

    // Reject (AJAX POST)
    Route::post('/document-requests/{id}/reject', [DocumentRequestController::class, 'reject'])
        ->middleware('permission:view_document_requests')
        ->middleware('permission:reject_document_requests')
        ->name('dentist.dentist.documentrequests.reject');

    Route::get('/document-requests/data', [DocumentRequestController::class, 'dentistData'])
        ->middleware('permission:view_document_requests,approve_document_requests,reject_document_requests')
        ->name('dentist.dentist.documentrequests.data');

    // Generate (AJAX POST)
    Route::post('/document-requests/generate', [DocumentRequestController::class, 'generate'])
        ->middleware('permission:view_document_requests,approve_document_requests,reject_document_requests')
        ->name('dentist.dentist.documentrequests.generate');

    // Odontogram
    Route::get('/odontogram/patient/{patient}/start', [OdontogramController::class, 'startForPatient'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.start');

    Route::get('/odontogram/{appointment}', [OdontogramController::class, 'show'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram');

    Route::get('/odontogram/{appointment}/saved/edit', [OdontogramController::class, 'editSavedVisit'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.saved.edit');

    Route::post('/odontogram/{appointment}/save', [OdontogramController::class, 'save'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.save');

    Route::post('/odontogram/discard', [OdontogramController::class, 'discard'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.discard');

    Route::post('/odontogram/{appointment}/saved/update', [OdontogramController::class, 'updateSavedVisit'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.saved.update');

    // Add Existing Appointment - Odontogram
    Route::get('/odontogram/patient/{patient}/existing-appointment', [OdontogramController::class, 'createExistingAppointment'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.existing-appointment.create');

    Route::post('/odontogram/patient/{patient}/existing-appointment', [OdontogramController::class, 'storeExistingAppointmentIntake'])
        ->middleware('permission:create_medical_records')
        ->name('dentist.odontogram.existing-appointment.intake.store');

    Route::patch(
        '/odontogram/patient/{patient}/existing-appointment/history/autosave',
        [
            OdontogramController::class,
            'autosaveExistingAppointmentHistory',
        ]
    )
        ->middleware(
            'permission:create_medical_records'
        )
        ->name(
            'dentist.odontogram.existing-appointment.history.autosave'
        );

    Route::get('/odontogram/existing-appointment/slots', [OdontogramController::class, 'existingAppointmentSlotsForDate'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.existing-appointment.slots');

    Route::get('/odontogram/patient/{patient}/existing-appointment/odontogram', [OdontogramController::class, 'showExistingAppointmentOdontogram'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.existing-appointment.odontogram');

    Route::post('/odontogram/patient/{patient}/existing-appointment/save', [OdontogramController::class, 'storeExistingAppointment'])
        ->middleware('permission:create_procedure_records')
        ->name('dentist.odontogram.existing-appointment.store');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])
        ->middleware('permission:view_inventory')
        ->name('dentist.dentist.inventory');

    Route::get('/inventory/data', [InventoryController::class, 'fetch'])
        ->middleware('permission:view_inventory')
        ->name('dentist.dentist.inventory.data');

    Route::post('/inventory', [InventoryController::class, 'store'])
        ->middleware('permission:add_inventory')
        ->name('dentist.dentist.inventory.store');

    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])
        ->middleware('permission:update_inventory')
        ->name('dentist.dentist.inventory.update');

    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])
        ->middleware('permission:delete_inventory')
        ->name('dentist.dentist.inventory.destroy');

    // clinic status
    Route::post('/clinic-status', [DentistDashboardController::class, 'updateClinicStatus'])
        ->middleware('permission:update_clinic_schedule')
        ->name('dentist.clinic-status.update');

    Route::get('/system-settings', [SystemSettingsController::class, 'index'])
        ->middleware('permission:manage_system_settings,set_notification_rules')
        ->name('dentist.system_settings');

    Route::post('/system-settings', [SystemSettingsController::class, 'update'])
        ->middleware('permission:manage_system_settings,set_notification_rules')
        ->name('dentist.system_settings.update');

    Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])
        ->middleware('permission:view_academic_periods,update_academic_period,create_academic_period,delete_academic_period')
        ->name('dentist.academic_periods');

    Route::post('/academic-periods', [AcademicPeriodController::class, 'store'])
        ->middleware('permission:create_academic_period')
        ->name('dentist.academic_periods.store');

    Route::put('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update'])
        ->middleware('permission:update_academic_period')
        ->name('dentist.academic_periods.update');

    Route::delete('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'destroy'])
        ->middleware('permission:delete_academic_period')
        ->name('dentist.academic_periods.destroy');

    Route::patch('/academic-periods/{academicPeriod}/set-active', [AcademicPeriodController::class, 'setActive'])
        ->middleware('permission:update_academic_period')
        ->name('dentist.academic_periods.set_active');

    Route::post('/academic-periods/sync-flss', [AcademicPeriodController::class, 'syncFromFlss'])
        ->middleware('permission:update_academic_period')
        ->name('dentist.academic_periods.sync_flss');

    Route::get('/report-files', [AdminReportController::class, 'reportFiles'])
        ->middleware('permission:create_report_files')
        ->name('dentist.report-files');
    Route::get('/reports', [AdminReportController::class, 'index'])
        ->middleware('permission:view_reports,view_ai_reports,create_ai_generative_reports')
        ->name('dentist.reports');

    Route::get('/reports/ai-generated', [AdminReportController::class, 'aiGenerated'])
        ->middleware('permission:view_ai_reports')
        ->name('dentist.reports.ai-generated');

    Route::get('/reports/ai-generated/download', [AdminReportController::class, 'downloadAiGenerated'])
        ->middleware('permission:create_ai_generative_reports')
        ->name('dentist.reports.ai-generated.download');

    Route::get('/role-permissions', [RolePermissionController::class, 'index'])
        ->middleware('permission:view_roles_permissions,create_custom_roles,update_role_permissions,delete_custom_roles')
        ->name('dentist.role_permissions');

    Route::post('/role-permissions/update', [RolePermissionController::class, 'update'])
        ->middleware('permission:update_role_permissions')
        ->name('dentist.role_permissions.update');

    Route::post('/role-permissions/reset', [RolePermissionController::class, 'reset'])
        ->middleware('permission:update_role_permissions')
        ->name('dentist.role_permissions.reset');

    Route::post('/role-permissions/store-role', [RolePermissionController::class, 'storeRole'])
        ->middleware('permission:create_custom_roles')
        ->name('dentist.role_permissions.store_role');

    Route::match(['post', 'delete'], '/role-permissions/{id}/destroy', [RolePermissionController::class, 'destroyRole'])
        ->middleware('permission:delete_custom_roles')
        ->name('dentist.role_permissions.destroy_role');

    Route::get('/system-logs', [SystemLogController::class, 'index'])
        ->middleware('permission:view_system_logs,export_system_logs,archive_system_logs')
        ->name('dentist.system_logs');

    Route::get('/system-logs/fetch', [SystemLogController::class, 'fetchLatest'])
        ->middleware('permission:view_system_logs,export_system_logs,archive_system_logs')
        ->name('dentist.system_logs.fetch');

    Route::get('/system-logs/check', [SystemLogController::class, 'checkLatest'])
        ->middleware('permission:view_system_logs,export_system_logs,archive_system_logs')
        ->name('dentist.system_logs.check');

    Route::get('/system-logs/export', [SystemLogController::class, 'export'])
        ->middleware('permission:export_system_logs')
        ->name('dentist.system_logs.export');

    Route::post('/system-logs/archive', [SystemLogController::class, 'archive'])
        ->middleware('permission:archive_system_logs')
        ->name('dentist.system_logs.archive');

    Route::get('/service-types', [ServiceTypeController::class, 'index'])
        ->middleware('permission:view_service_type,create_service_type,delete_service_type,update_default_service_type')
        ->name('dentist.service-types');

    Route::post('/service-types', [ServiceTypeController::class, 'store'])
        ->middleware('permission:create_service_type')
        ->name('dentist.service-types.store');

    Route::put('/service-types/{id}', [ServiceTypeController::class, 'update'])
        ->middleware('permission:update_default_service_type')
        ->name('dentist.service-types.update');

    Route::delete('/service-types/{id}', [ServiceTypeController::class, 'destroy'])
        ->middleware('permission:delete_service_type')
        ->name('dentist.service-types.destroy');

    Route::get('/assign-cms-access', [ExternalAdminController::class, 'index'])
        ->middleware('permission:view_cms_integration,create_cms_integration')
        ->name('dentist.assign-cms-access');

    Route::post('/assign-cms-access', [ExternalAdminController::class, 'store'])
        ->middleware('permission:create_cms_integration')
        ->name('dentist.assign-cms-access.store');

    Route::get('/external-admins/search', [ExternalAdminController::class, 'search'])
        ->middleware('permission:view_cms_integration,create_cms_integration')
        ->name('dentist.external-admins.search');

    Route::get('/external-admins/{adminId}', [ExternalAdminController::class, 'show'])
        ->middleware('permission:view_cms_integration,create_cms_integration')
        ->name('dentist.external-admins.show');

    Route::get('/faculty-integration', function () {
        return view('shared.faculty-integration');
    })
        ->middleware('permission:view_faculty_integration,create_faculty_integration,update_faculty_integration')
        ->name('dentist.faculty.integration');

    Route::post('/faculty-integration/store', [FacultyController::class, 'store'])
        ->middleware('permission:create_faculty_integration')
        ->name('dentist.faculty.store');

    Route::get('/user-management', [UserManagementController::class, 'index'])
        ->middleware('permission:view_account_details,create_users,disable_users,update_user_role,update_user_password')
        ->name('dentist.user_management');

    Route::post('/user-management', [UserManagementController::class, 'store'])
        ->middleware('permission:create_users')
        ->name('dentist.user_management.store');

    Route::put('/user-management/{user}', [UserManagementController::class, 'update'])
        ->middleware('permission:update_user_role')
        ->name('dentist.user_management.update');

    Route::post('/user-management/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
        ->middleware('permission:update_user_password')
        ->name('dentist.user_management.reset_password');

    Route::post('/user-management/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
        ->middleware('permission:disable_users')
        ->name('dentist.user_management.toggle_status');

    Route::get('/dental-records', [DentalRecordController::class, 'index'])
        ->middleware('permission:view_dental_records,manage_dental_records')
        ->name('dentist.dental-records.index');

    Route::get('/dental-records/{appointment}', [DentalRecordController::class, 'show'])
        ->middleware('permission:view_dental_records,manage_dental_records')
        ->name('dentist.dental-records.show');

    Route::get('/document-template', [DocumentTemplateController::class, 'index'])
        ->middleware('permission:manage_document_templates')
        ->name('dentist.document-template');

    Route::get('/document-template/{id}', [DocumentTemplateController::class, 'show'])
        ->middleware('permission:manage_document_templates')
        ->name('dentist.document-template.show');

    Route::patch('/document-template/{id}/archive', [DocumentTemplateController::class, 'archive'])
        ->middleware('permission:manage_document_templates')
        ->name('dentist.document-template.archive');

    Route::patch('/document-template/{id}/activate', [DocumentTemplateController::class, 'activate'])
        ->middleware('permission:manage_document_templates')
        ->name('dentist.document-template.activate');

    Route::patch('/document-template/{id}/default', [DocumentTemplateController::class, 'setDefault'])
        ->middleware('permission:manage_document_templates')
        ->name('dentist.document-template.default');

    // Dentist Continuity Transitions
    Route::get('/transitions', [DentistTransitionController::class, 'index'])
        ->middleware('permission:view_dentist_transitions,create_dentist_transitions,update_dentist_transitions,assign_dentist_successors,finalize_dentist_transitions,cancel_dentist_transitions,extend_dentist_access')
        ->name('dentist.dentist.transitions.index');

    Route::get('/transitions/create', [DentistTransitionController::class, 'create'])
        ->middleware('permission:create_dentist_transitions')
        ->name('dentist.dentist.transitions.create');

    Route::post('/transitions', [DentistTransitionController::class, 'store'])
        ->middleware('permission:create_dentist_transitions')
        ->name('dentist.dentist.transitions.store');

    Route::get('/transitions/{transition}', [DentistTransitionController::class, 'show'])
        ->middleware('permission:view_dentist_transitions')
        ->name('dentist.dentist.transitions.show');

    Route::get('/transitions/{transition}/edit', [DentistTransitionController::class, 'edit'])
        ->middleware('permission:update_dentist_transitions')
        ->name('dentist.dentist.transitions.edit');

    Route::put('/transitions/{transition}', [DentistTransitionController::class, 'update'])
        ->middleware('permission:update_dentist_transitions')
        ->name('dentist.dentist.transitions.update');

    Route::post('/transitions/{transition}/generate-items', [DentistTransitionController::class, 'generateItems'])
        ->middleware('permission:update_dentist_transitions')
        ->name('dentist.dentist.transitions.generate-items');

    Route::put('/transitions/{transition}/assignments', [DentistTransitionController::class, 'assignments'])
        ->middleware('permission:assign_dentist_successors')
        ->name('dentist.dentist.transitions.assignments');

    Route::put('/transitions/{transition}/checklist', [DentistTransitionController::class, 'checklist'])
        ->middleware('permission:update_dentist_transitions')
        ->name('dentist.dentist.transitions.checklist');

    Route::post('/transitions/{transition}/finalize', [DentistTransitionController::class, 'finalize'])
        ->middleware('permission:finalize_dentist_transitions')
        ->name('dentist.dentist.transitions.finalize');

    Route::post('/transitions/{transition}/cancel', [DentistTransitionController::class, 'cancel'])
        ->middleware('permission:cancel_dentist_transitions')
        ->name('dentist.dentist.transitions.cancel');

    Route::post('/transitions/{transition}/extend-access', [DentistTransitionController::class, 'extendAccess'])
        ->middleware('permission:extend_dentist_access')
        ->name('dentist.dentist.transitions.extend-access');
});

/*
|--------------------------------------------------------------------------
| REPORT ROUTES (LEGACY DIRECT ACCESS)
|--------------------------------------------------------------------------
*/

/*Route::prefix('report')->middleware(['role:dentist', 'permission:manage_reports'])->group(function () {

    Route::get('/', [\App\Http\Controllers\Dentist\DentistReportController::class, 'index'])
        ->name('dentist.report.legacy');

    Route::get('/daily-treatment-record', function () {
        return view('dentist.daily-treatment');
    })->name('dentist.dentist.report.daily-treatment');

    Route::get('/dental-services', [\App\Http\Controllers\Dentist\DentalServicesRecordController::class, 'index'])
        ->name('dentist.dentist.report.dental-services');
});*/

Route::post('/chat/send', [ChatbotController::class, 'chat']);

if (app()->environment('local')) {

    // api nila albert
    Route::get('/debug-ogos-config', function () {
        return response()->json([
            'base_url' => config('services.ogos.base_url'),
            'token_url' => config('services.ogos.token_url'),
            'client_id' => config('services.ogos.client_id'),
            'client_secret_exists' => filled(config('services.ogos.client_secret')),
            'client_secret_length' => strlen((string) config('services.ogos.client_secret')),
        ]);
    });

    Route::get('/test-student-api', function (StudentApiService $studentApiService) {
        $email = 'student5@gmail.com';

        return response()->json($studentApiService->getStudentByEmail($email));
    });

    Route::get('/dev/error-pages/{code}', function (string $code) {
        $allowedCodes = [
            '401',
            '402',
            '403',
            '404',
            '419',
            '429',
            '500',
            '503',
        ];

        abort_unless(
            in_array($code, $allowedCodes, true),
            404
        );

        return response()->view(
            "errors.{$code}",
            [],
            (int) $code
        );
    })->where('code', '[0-9]+');
}
