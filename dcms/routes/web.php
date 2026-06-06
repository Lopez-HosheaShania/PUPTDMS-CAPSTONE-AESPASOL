<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\Patient;
use App\Helpers\AuditLogger;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Dentist\InventoryController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\Dentist\DentistPatientController;
use App\Http\Controllers\Dentist\DentistAppointmentController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Helpers\PhilippineHolidays;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\AdminPatientController;
use App\Http\Controllers\Admin\ServiceTypeController;
use App\Http\Controllers\Admin\ClinicScheduleController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\DocumentRequestController as AdminDocumentRequestController;
use App\Http\Controllers\Auth\OIDCController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\DataBackupController;
use App\Http\Controllers\Admin\AdminAppointmentController;
use App\Http\Controllers\Dentist\DentistDashboardController;
use App\Http\Controllers\Admin\AdminInventoryController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\ExternalAdminController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\Admin\DocumentTemplateController;
use App\Services\StudentApiService;
use App\Services\FacultyApiService;
use App\Http\Controllers\Admin\DentalRecordController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\Dentist\OdontogramController;

//api nila albert

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
    $email = 'student5@gmail.com'; // palitan mo kung needed
    return response()->json($studentApiService->getStudentByEmail($email));
});



//kela matt
Route::get('/faculties', [FacultyController::class, 'getFacultyList']);

Route::get('/faculty-integration', function () {
    return view('admin.faculty-integration');
})->name('admin.faculty.integration');

Route::post('/faculty-integration/store', [FacultyController::class, 'store'])
    ->name('admin.faculty.store');

// routes/web.php---

Route::get('/debug-session', function () {
    return response()->json(session()->all());
});

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
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

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
        'name'      => 'required|string|min:2|max:255',
        'email'     => 'required|email|unique:patients,email|unique:users,email',
        'phone'     => 'nullable|string|max:20',
        'birthdate' => 'required|date|before:today|after:120 years ago',
        'gender'    => 'required|in:Male,Female',
        'password'  => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
        ],
    ], [
        'name.min'           => 'Name must be at least 2 characters.',
        'email.unique'       => 'This email is already registered.',
        'email.email'        => 'Please enter a valid email address.',
        'birthdate.required' => 'Birthdate is required.',
        'birthdate.before'   => 'Birthdate cannot be in the future.',
        'birthdate.after'    => 'Please enter a valid birthdate.',
        'gender.required'    => 'Please select a gender.',
        'gender.in'          => 'Gender must be Male or Female.',
        'password.min'       => 'Password must be at least 8 characters.',
        'password.confirmed' => 'Passwords do not match.',
        'password.regex'     => 'Password must contain at least one letter, one number, and one special character.',
    ]);

    try {
        DB::transaction(function () use ($validated) {
            $patientRole = Role::where('slug', 'patient')->firstOrFail();

            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id'  => $patientRole->id,
                'status'   => 'active',
            ]);

            Patient::create([
                'user_id'   => $user->id,
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'phone'     => $validated['phone'] ?? '',
                'birthdate' => $validated['birthdate'],
                'gender'    => $validated['gender'],
                'password'  => $user->password,
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
Route::post('/login', function (Request $request) {
    $patient = Patient::where('email', $request->email)->first();

    if (!$patient) {
        return back()->with('error', 'No account associated with this email.');
    }

    if (!Hash::check($request->password, $patient->password)) {
        return back()->with('error', 'Incorrect password. Please try again.');
    }

    $user = User::where('email', $patient->email)->first();

    if (!$user) {
        return back()->with('error', 'No linked user account found for this patient.');
    }

    if (!$user->hasRole('patient')) {
        return back()->with('error', 'This account is not allowed to log in as patient.');
    }

    Auth::login($user);

    session([
        'patient_id' => $patient->id,
        'email' => $patient->email,
    ]);

    session()->save();

    AuditLogger::log('login', 'authentication', 'Patient logged into the system');

    return redirect()->route('patient.dashboard')
        ->with('login_as', $patient->name)
        ->with('show_terms_modal', true);
});


// Logout (all roles)
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::post('/{notificationId}/read', [NotificationController::class, 'markAsRead'])
        ->name('mark-read');

    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('mark-all-read');
});

/*
|--------------------------------------------------------------------------
| ADMIN / SUPER ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | CMS ACCESS (External Admin Integration)
        |--------------------------------------------------------------------------
        */
        Route::get('/assign-cms-access', [ExternalAdminController::class, 'index'])
            ->name('admin.assign-cms-access');

        Route::post('/assign-cms-access', [ExternalAdminController::class, 'store'])
            ->name('admin.assign-cms-access.store');

        Route::get('/external-admins/search', [ExternalAdminController::class, 'search'])
            ->name('admin.external_admins.search');

        Route::get('/external-admins/{adminId}', [ExternalAdminController::class, 'show'])
            ->name('admin.external_admins.show');


        /*
        |--------------------------------------------------------------------------
        | ADMIN DASHBOARD
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('admin.admin.dashboard');

        Route::get('/dental-records', [DentalRecordController::class, 'index'])
            ->name('admin.dental-records.index');

        // Safe stub routes used by blades to avoid missing-route exceptions.
        // These redirect to the index until full handlers are implemented.

        Route::get('/dental-records/{id}', function ($id) {
            return redirect()->route('admin.dental-records.index');
        })->name('admin.dental-records.show');

        // Route aliases for blade views
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
        Route::get('/reports', [AdminReportController::class, 'index'])
            ->name('admin.reports');

        /*
        |--------------------------------------------------------------------------
        | ROLE & PERMISSIONS
        |--------------------------------------------------------------------------
        */
        Route::get('/role-permissions', [RolePermissionController::class, 'index'])
            ->name('admin.role_permissions');

        Route::post('/role-permissions/update', [RolePermissionController::class, 'update'])
            ->name('admin.role_permissions.update');

        Route::post('/role-permissions/reset', [RolePermissionController::class, 'reset'])
            ->name('admin.role_permissions.reset');

        Route::post('/role-permissions/store-role', [RolePermissionController::class, 'storeRole'])
            ->name('admin.role_permissions.store_role');

        Route::delete('/role-permissions/{id}/destroy', [RolePermissionController::class, 'destroyRole'])
            ->name('admin.role_permissions.destroy_role');


        /*
        |--------------------------------------------------------------------------
        | SYSTEM LOGS
        |--------------------------------------------------------------------------
        */
        Route::get('/system-logs', [SystemLogController::class, 'index'])
            ->name('admin.system_logs');

        Route::get('/system-logs/fetch', [SystemLogController::class, 'fetchLatest'])
            ->name('admin.system_logs.fetch');

        Route::get('/system-logs/check', [SystemLogController::class, 'checkLatest'])
            ->name('admin.system_logs.check');

        Route::get('/system-logs/export', [SystemLogController::class, 'export'])
            ->name('admin.system_logs.export');


        /*
        |--------------------------------------------------------------------------
        | PATIENT DIRECTORY
        |--------------------------------------------------------------------------
        */
        Route::get('/patient-directory', [AdminPatientController::class, 'index'])
            ->name('admin.patient_directory');

        Route::get('/patients', [AdminPatientController::class, 'index'])
            ->name('admin.admin.patients');

        Route::get('/patient/{patient}', [AdminPatientController::class, 'show'])
            ->name('admin.admin.patient.profile');


        /*
        |--------------------------------------------------------------------------
        | APPOINTMENTS
        |--------------------------------------------------------------------------
        */
        Route::get('/appointments', [AdminAppointmentController::class, 'index'])
            ->name('admin.admin.appointments');

        Route::get('/appointments/{id}', [AdminAppointmentController::class, 'show'])
            ->name('admin.admin.appointments.show');

        Route::get('/appointments/{id}/reschedule', [AdminAppointmentController::class, 'reschedule'])
            ->name('admin.admin.appointments.reschedule');

        Route::get('/appointments/{id}/start', [AdminAppointmentController::class, 'start'])
            ->name('admin.admin.appointments.start');

        Route::post('/appointments/{id}/cancel', [AdminAppointmentController::class, 'cancel'])
            ->name('admin.admin.appointments.cancel');


        /*
        |--------------------------------------------------------------------------
        | PATIENT LIST (FOR IMPERSONATION)
        |--------------------------------------------------------------------------
        */
        Route::get('/patients/list', function () {
            $user = Auth::user();

            if (!$user || !in_array(optional($user->role)->slug, ['super_admin', 'admin'])) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $patients = Patient::select('id', 'name', 'email', 'phone')
                ->orderBy('name')
                ->get();

            return response()->json($patients);
        })->name('admin.patients.list');


        /*
        |--------------------------------------------------------------------------
        | ACADEMIC PERIODS
        |--------------------------------------------------------------------------
        */
        Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])
            ->name('admin.academic_periods');

        Route::post('/academic-periods', [AcademicPeriodController::class, 'store'])
            ->name('admin.academic_periods.store');

        Route::put('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update'])
            ->name('admin.academic_periods.update');

        Route::delete('/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'destroy'])
            ->name('admin.academic_periods.destroy');

        Route::patch('/academic-periods/{academicPeriod}/set-active', [AcademicPeriodController::class, 'setActive'])
            ->name('admin.academic_periods.set_active');

        Route::post('/admin/academic-periods/sync-flss', [AcademicPeriodController::class, 'syncFromFlss'])
            ->name('admin.academic_periods.sync_flss');


        /*
        |--------------------------------------------------------------------------
        | CLINIC SCHEDULE
        |--------------------------------------------------------------------------
        */
        Route::get('/clinic-schedule', [ClinicScheduleController::class, 'index'])
            ->name('admin.clinic_schedule');

        Route::post('/clinic-schedule', [ClinicScheduleController::class, 'store'])
            ->name('admin.clinic_schedule.store');

        Route::put('/clinic-schedule/rules/{clinicSchedule}', [ClinicScheduleController::class, 'update'])
            ->name('admin.clinic_schedule.update');

        Route::delete('/clinic-schedule/rules/{clinicSchedule}', [ClinicScheduleController::class, 'destroy'])
            ->name('admin.clinic_schedule.destroy');

        Route::post('/clinic-schedule/block-date', [ClinicScheduleController::class, 'blockDate'])
            ->name('admin.clinic_schedule.block');

        Route::delete('/clinic-schedule/block-date/{blockedDate}', [ClinicScheduleController::class, 'unblockDate'])
            ->name('admin.clinic_schedule.unblock');

        Route::get('/clinic-schedule/unavailable-dates', [ClinicScheduleController::class, 'unavailableDates'])
            ->name('admin.clinic_schedule.unavailable_dates');

        Route::get('/clinic-schedule/slots', [ClinicScheduleController::class, 'slotsForDate'])
            ->name('admin.clinic_schedule.slots');

        // INVENTORY
        Route::get('/inventory', [AdminInventoryController::class, 'index'])
            ->name('admin.inventory');

        Route::get('/inventory/data', [AdminInventoryController::class, 'fetch'])
            ->name('admin.inventory.data');

        Route::post('/inventory', [AdminInventoryController::class, 'store'])
            ->name('admin.inventory.store');

        Route::put('/inventory/{inventory}', [AdminInventoryController::class, 'update'])
            ->name('admin.inventory.update');

        Route::delete('/inventory/{inventory}', [AdminInventoryController::class, 'destroy'])
            ->name('admin.inventory.destroy');
    });

// DOCUMENT REQUEST 
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/document-requests', [AdminDocumentRequestController::class, 'index'])
            ->name('admin.document-requests.index');

        Route::get('/document-requests/export', [AdminDocumentRequestController::class, 'export'])
            ->name('admin.document-requests.export');

        Route::get('/document-requests/print-queue', [AdminDocumentRequestController::class, 'printQueue'])
            ->name('admin.document-requests.print-queue');

        Route::get('/document-requests/{documentRequest}', [AdminDocumentRequestController::class, 'show'])
            ->name('admin.document-requests.show');

        Route::patch('/document-requests/{documentRequest}/approve', [AdminDocumentRequestController::class, 'approve'])
            ->name('admin.document-requests.approve');

        Route::patch('/document-requests/{documentRequest}/release', [AdminDocumentRequestController::class, 'release'])
            ->name('admin.document-requests.release');

        Route::patch('/document-requests/{documentRequest}/reject', [AdminDocumentRequestController::class, 'reject'])
            ->name('admin.document-requests.reject');
    });

// DOCUMENT TEMPLATES (SIMPLIFIED)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        // LIST PAGE
        Route::get('/document-template', [DocumentTemplateController::class, 'index'])
            ->name('document-template');

        // PREVIEW (AJAX)
        Route::get('/document-template/{id}', [DocumentTemplateController::class, 'show'])
            ->name('document-template.show');

        // ARCHIVE
        Route::patch('/document-template/{id}/archive', [DocumentTemplateController::class, 'archive'])
            ->name('document-template.archive');

        // ACTIVATE
        Route::patch('/document-template/{id}/activate', [DocumentTemplateController::class, 'activate'])
            ->name('document-template.activate');

        // SET DEFAULT
        Route::patch('/document-template/{id}/default', [DocumentTemplateController::class, 'setDefault'])
            ->name('document-template.default');
    });

// DATA BACKUP
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/data-backup', [DataBackupController::class, 'index'])->name('admin.data_backup');
        Route::post('/data-backup/store', [DataBackupController::class, 'store'])->name('admin.data_backup.store');
        Route::get('/data-backup/download/{id}', [DataBackupController::class, 'download'])->name('admin.data_backup.download');
        Route::post('/data-backup/toggle-auto', [DataBackupController::class, 'toggleAuto'])->name('admin.data_backup.toggle_auto');
        Route::post('/data-backup/restore/{id}', [DataBackupController::class, 'restore'])->name('admin.data_backup.restore');
        Route::delete('/data-backup/delete/{id}', [DataBackupController::class, 'destroy'])->name('admin.data_backup.delete');
        Route::post('/data-backup/update-schedule', [DataBackupController::class, 'updateSchedule'])->name('admin.data_backup.update_schedule');
    });

// SYSTEM SETTINGS
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/system-settings', [SystemSettingsController::class, 'index'])
            ->name('admin.system_settings');

        Route::post('/system-settings', [SystemSettingsController::class, 'update'])
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
            ->name('admin.user_management');

        Route::post('/user-management', [UserManagementController::class, 'store'])
            ->name('admin.user_management.store');

        Route::put('/user-management/{user}', [UserManagementController::class, 'update'])
            ->name('admin.user_management.update');

        Route::post('/user-management/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
            ->name('admin.user_management.reset_password');

        Route::post('/user-management/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
            ->name('admin.user_management.toggle_status');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        Route::get('/service-types', [ServiceTypeController::class, 'index'])->name('service-types');
        Route::post('/service-types', [ServiceTypeController::class, 'store'])->name('service-types.store');
        Route::put('/service-types/{id}', [ServiceTypeController::class, 'update'])->name('service-types.update');
        Route::delete('/service-types/{id}', [ServiceTypeController::class, 'destroy'])->name('service-types.destroy');
    });

/*
|--------------------------------------------------------------------------
| ADMIN IMPERSONATION ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/impersonate', function (Request $request) {

    /** @var \App\Models\User $user */
    $user = Auth::user();

    if (!$user || !$user->hasAnyRole(['super_admin', 'admin'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $request->validate([
        'role' => 'required|string',
    ]);

    $targetRole = strtolower(trim($request->role));

    if (!session()->has('impersonator_role')) {
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
            'Super Admin started impersonating Dentist dashboard'
        );

        return response()->json([
            'redirect' => route('dentist.dentist.dashboard')
        ]);
    }

    if (in_array($targetRole, ['patient', 'patient_role'], true)) {

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
        ]);

        $patient = Patient::find($request->patient_id);

        if (!$patient) {
            return response()->json([
                'message' => 'Selected patient not found.'
            ], 422);
        }

        session([
            'impersonated_role' => 'patient',
            'impersonated_patient_id' => $patient->id,
        ]);

        AuditLogger::log(
            'impersonation_started',
            'authentication',
            'Super Admin started impersonating Patient ID ' . $patient->id
        );

        return response()->json([
            'redirect' => route('patient.dashboard')
        ]);
    }

    return response()->json([
        'message' => 'Unsupported role: ' . $targetRole
    ], 422);
})->name('admin.impersonate');


Route::post('/stop-impersonation', function () {

    /** @var \App\Models\User $user */
    $user = Auth::user();

    if (!$user || !$user->hasAnyRole(['super_admin', 'admin'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    if (session()->has('impersonator_role')) {
        AuditLogger::log(
            'impersonation_stopped',
            'authentication',
            'Super Admin stopped impersonation'
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

    Route::get('/available-slots', [AppointmentController::class, 'availableSlots'])
        ->middleware('permission:book_appointments')
        ->name('patient.appointments.available-slots');

    Route::post('/document-requests', [DocumentRequestController::class, 'store'])
        ->middleware('permission:request_documents')
        ->name('patient.document.requests.store');

    Route::get('/document-requests', [DocumentRequestController::class, 'index'])
        ->middleware('permission:request_documents')
        ->name('patient.document.requests.index');

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
            $patient = Auth::user()->patient;
        }

        return view('patient.index', compact('patient'));
    })->middleware('permission:access_patient_dashboard')->name('patient.dashboard');

    Route::get('/appointment', [AppointmentController::class, 'index'])
        ->middleware('permission:view_own_appointments')
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

    Route::post('/book-appointment/validate-signature', [AppointmentController::class, 'validateSignature'])
        ->name('book.appointment.validate-signature');

    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->middleware('permission:view_own_appointments')
        ->name('book.appointment.index');

    Route::get('/patient/appointments/cancelled', function () {
        return view('patient.cancelled'); // <-- include 'patient.'
    })->name('patient.appointment.cancelled.view');
});

/*
|--------------------------------------------------------------------------
| DENTIST ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('dentist')->middleware(['role:dentist'])->group(function () {

    Route::get('/dashboard', function () {
        $now   = \Carbon\Carbon::now();
        $today = $now->toDateString();

        $todayAppointments = \App\Models\Appointment::with('patient')
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['upcoming', 'rescheduled', 'pending', 'confirmed'])
            ->orderBy('appointment_time', 'asc')
            ->get();

        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $calendarAppointments = \App\Models\Appointment::with('patient')
            ->whereBetween('appointment_date', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['pending', 'confirmed', 'upcoming', 'rescheduled', 'completed'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $appointmentCountsPerDay = $calendarAppointments
            ->groupBy(function ($appointment) {
                return \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->count();
            })
            ->toArray();

        $calendarAppointmentDetails = $calendarAppointments
            ->groupBy(function ($appointment) {
                return \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->map(function ($items) {
                return $items->map(function ($appointment) {
                    $name = $appointment->patient->name ?? 'Unknown Patient';

                    $time = !empty($appointment->appointment_time)
                        ? \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A')
                        : '—';

                    $service = $appointment->service_type === 'others'
                        ? ($appointment->other_services ?? 'Other Service')
                        : ($appointment->service_type ?? 'General Service');

                    return [
                        'id' => $appointment->id,
                        'name' => $name,
                        'time' => $time,
                        'service' => ucwords($service),
                        'status' => $appointment->status ?? 'pending',
                        'date' => \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
                        'patientProfileUrl' => $appointment->patient_id
                            ? route('dentist.dentist.patient.profile', $appointment->patient_id)
                            : '#',
                        'rescheduleUrl' => route('dentist.dentist.appointments.reschedule', $appointment->id),
                        'cancelUrl' => route('dentist.dentist.appointments.cancel', $appointment->id),
                    ];
                })->values()->toArray();
            })
            ->toArray();

        $dentalCasesThisMonth = \App\Models\Appointment::whereYear('appointment_date', $now->year)
            ->whereMonth('appointment_date', $now->month)
            ->where('status', 'completed')
            ->count();

        $lastMonth = $now->copy()->subMonth();

        $dentalCasesLastMonth = \App\Models\Appointment::whereYear('appointment_date', $lastMonth->year)
            ->whereMonth('appointment_date', $lastMonth->month)
            ->where('status', 'completed')
            ->count();

        $dentalCasesDelta = $dentalCasesLastMonth > 0
            ? round((($dentalCasesThisMonth - $dentalCasesLastMonth) / $dentalCasesLastMonth) * 100)
            : null;

        $totalApptsThisMonth = \App\Models\Appointment::whereYear('appointment_date', $now->year)
            ->whereMonth('appointment_date', $now->month)
            ->whereIn('status', ['upcoming', 'rescheduled', 'completed', 'cancelled', 'pending', 'confirmed'])
            ->count();

        $totalApptsLastMonth = \App\Models\Appointment::whereYear('appointment_date', $lastMonth->year)
            ->whereMonth('appointment_date', $lastMonth->month)
            ->whereIn('status', ['pending', 'confirmed', 'upcoming', 'rescheduled', 'completed', 'cancelled'])
            ->count();

        $totalApptsDelta = $totalApptsLastMonth > 0
            ? round((($totalApptsThisMonth - $totalApptsLastMonth) / $totalApptsLastMonth) * 100)
            : null;

        $medicalSupplies = \Illuminate\Support\Facades\DB::table('inventory_items')
            ->where('category', 'Supplies')
            ->orderByRaw('(qty - used) ASC')
            ->limit(3)
            ->get();

        $medicineSupplies = \Illuminate\Support\Facades\DB::table('inventory_items')
            ->where('category', 'Medicine')
            ->orderByRaw('(qty - used) ASC')
            ->limit(3)
            ->get();

        $gadRaw = \Illuminate\Support\Facades\DB::table('daily_treatment_records')
            ->whereYear('treatment_date', $now->year)
            ->whereMonth('treatment_date', $now->month)
            ->select('office_type', 'gender', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('office_type', 'gender')
            ->get();

        $gadLabels = ['Student', 'Administrative', 'Faculty', 'Dependent'];
        $gadFemale = [];
        $gadMale   = [];

        foreach ($gadLabels as $label) {
            $key = $label === 'Student' ? null : $label;
            $gadFemale[] = (int) $gadRaw->where('office_type', $key)->where('gender', 'Female')->sum('total');
            $gadMale[]   = (int) $gadRaw->where('office_type', $key)->where('gender', 'Male')->sum('total');
        }

        $philippineHolidays = PhilippineHolidays::range(yearsBefore: 1, yearsAfter: 5);
        $notifications = collect([]);
        $unavailableDates = [];

        return view('dentist.dentist-dashboard', compact(
            'todayAppointments',
            'appointmentCountsPerDay',
            'calendarAppointmentDetails',
            'philippineHolidays',
            'unavailableDates',
            'notifications',
            'dentalCasesThisMonth',
            'dentalCasesDelta',
            'totalApptsThisMonth',
            'totalApptsDelta',
            'medicalSupplies',
            'medicineSupplies',
            'gadLabels',
            'gadFemale',
            'gadMale'
        ));
    })->middleware('permission:access_dentist_dashboard')->name('dentist.dentist.dashboard');

    // Appointments
    Route::get('/appointments', [DentistAppointmentController::class, 'index'])
        ->middleware('permission:manage_appointments')
        ->name('dentist.dentist.appointments');

    Route::get('/appointments/{appointment}/patient-profile', [DentistAppointmentController::class, 'patientProfile'])
        ->middleware('permission:manage_patient_profiles')
        ->name('dentist.dentist.appointments.patientProfile');

    Route::get('/appointments/{id}/reschedule', [DentistAppointmentController::class, 'reschedule'])
        ->middleware('permission:manage_appointments')
        ->name('dentist.dentist.appointments.reschedule');

    Route::put('/appointments/{id}/reschedule', [DentistAppointmentController::class, 'updateReschedule'])
        ->middleware('permission:manage_appointments')
        ->name('dentist.dentist.appointments.reschedule.update');

    Route::get('/dentist/appointment-slots', [AppointmentController::class, 'slotsForDate'])
        ->middleware(['role:dentist'])
        ->name('dentist.appointment.slots');

    Route::post('/appointments/{id}/cancel', [DentistAppointmentController::class, 'cancel'])
        ->middleware('permission:manage_appointments')
        ->name('dentist.dentist.appointments.cancel');

    Route::get('/appointments/{id}/start', [DentistAppointmentController::class, 'start'])
        ->middleware('permission:manage_appointments')
        ->name('dentist.dentist.appointments.start');

    // Patients
    Route::get('/patients', [DentistPatientController::class, 'index'])
        ->middleware('permission:manage_patient_profiles')
        ->name('dentist.dentist.patients');

    Route::get('/patients/{patient}/profile', [DentistPatientController::class, 'profile'])
        ->middleware('permission:manage_patient_profiles')
        ->name('dentist.dentist.patient.profile');

    // Report Page
    Route::get('/report', [\App\Http\Controllers\Dentist\DentistReportController::class, 'index'])
        ->middleware('permission:manage_reports')
        ->name('dentist.dentist.report');

    Route::get('/report/gad-data', [\App\Http\Controllers\Dentist\DentistReportController::class, 'gadData'])
        ->middleware('permission:manage_reports')
        ->name('dentist.dentist.report.gad-data');

    Route::get('/report/weekly-data', [\App\Http\Controllers\Dentist\DentistReportController::class, 'weeklyData'])
        ->middleware('permission:manage_reports')
        ->name('dentist.dentist.report.weekly-data');

    // Document Requests
    //     if (session('role') !== 'dentist') {
    //         return redirect('/login');
    //     }
    //     return view('dentist-report');
    // })->name('dentist.report');

    // Document Requests – list page
    Route::get('/document-requests', [DocumentRequestController::class, 'dentistIndex'])
        ->middleware('permission:manage_document_requests')
        ->name('dentist.dentist.documentrequests');

    // Approve (AJAX POST)
    Route::post('/document-requests/{id}/approve', [DocumentRequestController::class, 'approve'])
        ->name('dentist.dentist.documentrequests.approve');

    // Reject (AJAX POST)
    Route::post('/document-requests/{id}/reject', [DocumentRequestController::class, 'reject'])
        ->name('dentist.dentist.documentrequests.reject');

    Route::get('/document-requests/data', [DocumentRequestController::class, 'dentistData'])
        ->name('dentist.dentist.documentrequests.data');

    // Generate (AJAX POST)
    Route::post('/document-requests/generate', [DocumentRequestController::class, 'generate'])
        ->name('dentist.dentist.documentrequests.generate');


    // Odontogram
    Route::get('/odontogram/appointment/{appointment}', [OdontogramController::class, 'show'])
        ->middleware('permission:manage_appointments')
        ->name('dentist.odontogram');

    Route::post('/odontogram/appointment/{appointment}/save', [OdontogramController::class, 'save'])
        ->middleware('permission:manage_appointments')
        ->name('dentist.odontogram.save');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])
        ->middleware('permission:manage_inventory')
        ->name('dentist.dentist.inventory');

    Route::get('/inventory/data', [InventoryController::class, 'fetch'])
        ->middleware('permission:manage_inventory')
        ->name('dentist.dentist.inventory.data');

    Route::post('/inventory', [InventoryController::class, 'store'])
        ->middleware('permission:manage_inventory')
        ->name('dentist.dentist.inventory.store');

    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])
        ->middleware('permission:manage_inventory')
        ->name('dentist.dentist.inventory.update');

    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])
        ->middleware('permission:manage_inventory')
        ->name('dentist.dentist.inventory.destroy');
});


/*
|--------------------------------------------------------------------------
| REPORT ROUTES (LEGACY DIRECT ACCESS)
|--------------------------------------------------------------------------
*/

Route::prefix('report')->middleware(['role:dentist', 'permission:manage_reports'])->group(function () {

    Route::get('/', [\App\Http\Controllers\Dentist\DentistReportController::class, 'index'])
        ->name('dentist.report.legacy');

    Route::get('/daily-treatment-record', function () {
        return view('dentist.daily-treatment');
    })->name('dentist.dentist.report.daily-treatment');

    Route::get('/dental-services', function () {
        return view('dentist.dental-services');
    })->name('dentist.dentist.report.dental-services');
});

Route::post('/chat/send', [ChatbotController::class, 'chat']);


