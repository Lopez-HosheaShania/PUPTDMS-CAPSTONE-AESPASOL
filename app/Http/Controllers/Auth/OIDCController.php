<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\ExternalAdminAccess;
use App\Models\Faculty;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\FacultyApiService;
use App\Services\ConcurrentSessionService;
use App\Services\StudentApiService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tymon\JWTAuth\Facades\JWTAuth;

class OIDCController extends Controller
{
    protected StudentApiService $studentApiService;
    protected FacultyApiService $facultyApiService;

    public function __construct(
        FacultyApiService $facultyApiService,
        StudentApiService $studentApiService,
        private readonly ConcurrentSessionService $concurrentSessionService
    ) {
        $this->facultyApiService = $facultyApiService;
        $this->studentApiService = $studentApiService;
    }

    public function redirect(Request $request)
    {
        $this->concurrentSessionService->rememberBrowserHint($request);

        $loginUrl     = config('services.idp.login_url');
        $authorizeUrl = config('services.oidc.authorize_url');
        $clientId     = config('services.oidc.client_id');
        $redirectUri  = config('services.oidc.redirect');
        $scope        = config('services.oidc.scope', 'openid profile email');
        $forceReauth  = $request->boolean('reauth');

        if (!$clientId || !$redirectUri) {
            return redirect()->route('login')
                ->with('error', 'OIDC provider is not configured properly.');
        }

        $state = Str::random(40);

        session([
            'oidc_state' => $state,
        ]);
        session()->save();

        $query = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $state,
            'prompt'        => 'login',
            'max_age'       => $forceReauth ? 0 : null,
        ]);

        if ($loginUrl) {
            $separator = str_contains($loginUrl, '?') ? '&' : '?';
            $fullUrl = $loginUrl . $separator . $query;
        } else {
            $fullUrl = $authorizeUrl . '?' . $query;
        }

        Log::info('OIDC redirect URL', ['url' => $fullUrl]);

        return redirect()->away($fullUrl);
    }

    public function callback(Request $request)
    {
        Log::info('OIDC Callback Debug', [
            'incoming_state' => $request->get('state'),
            'session_state'  => session('oidc_state'),
            'session_id'     => session()->getId(),
            'full_url'       => $request->fullUrl(),
            'all_params'     => $request->all(),
        ]);

        if ($request->has('error')) {
            return redirect()->route('login')->with(
                'error',
                'SSO failed: ' . $request->get('error') . ' - ' . $request->get('error_description', 'Authorization failed.')
            );
        }

        $savedState = session('oidc_state');
        $incomingState = $request->get('state');

        if (!$savedState) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please try again.');
        }

        if ($incomingState && !hash_equals($savedState, $incomingState)) {
            session()->forget('oidc_state');

            return redirect()->route('login')
                ->with('error', 'Invalid OIDC state. Possible CSRF attack.');
        }

        session()->forget('oidc_state');

        $rawQuery = $request->server('QUERY_STRING');
        parse_str($rawQuery, $rawParams);
        $code = $rawParams['code'] ?? $request->get('code');

        Log::info('OIDC code comparison', [
            'raw_code'     => $rawParams['code'] ?? 'not found',
            'request_code' => $request->get('code'),
            'match'        => ($rawParams['code'] ?? '') === $request->get('code'),
        ]);

        if (!$code) {
            return redirect()->route('login')
                ->with('error', 'Authorization code missing.');
        }

        /** @var Response $tokenResponse */
        $tokenResponse = Http::acceptJson()
            ->contentType('application/json')
            ->post(config('services.oidc.token_url'), [
                'client_id'     => config('services.oidc.client_id'),
                'client_secret' => config('services.oidc.client_secret'),
                'code'          => $code,
            ]);

        Log::info('OIDC token request', [
            'token_url' => config('services.oidc.token_url'),
            'payload'   => [
                'client_id'         => config('services.oidc.client_id'),
                'code'              => $code,
                'has_client_secret' => !empty(config('services.oidc.client_secret')),
            ],
            'response_status' => $tokenResponse->status(),
            'response_body'   => $tokenResponse->body(),
        ]);

        if (!$tokenResponse->successful()) {
            $tokenError = $tokenResponse->json();

            return redirect()->route('login')->with(
                'error',
                'Token exchange failed: ' . (
                    $tokenError['error_description']
                    ?? $tokenError['error']
                    ?? $tokenResponse->body()
                    ?? 'Unknown error'
                )
            );
        }

        $tokenData = $tokenResponse->json();

        $accessToken  = $tokenData['access_token'] ?? null;
        $idToken      = $tokenData['id_token'] ?? null;
        $refreshToken = $tokenData['refresh_token'] ?? null;

        if (!$accessToken) {
            return redirect()->route('login')
                ->with('error', 'Access token missing from token response.');
        }

        /** @var Response $profileResponse */
        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get(config('services.oidc.me_url'));

        Log::info('OIDC profile response', [
            'status' => $profileResponse->status(),
            'body'   => $profileResponse->body(),
        ]);

        if (!$profileResponse->successful()) {
            $profileError = $profileResponse->json();

            return redirect()->route('login')->with(
                'error',
                'Failed to fetch user profile: ' . (
                    $profileError['error_description']
                    ?? $profileError['error']
                    ?? $profileResponse->body()
                    ?? 'Unknown error'
                )
            );
        }

        $profile = $profileResponse->json();

        $ssoUserId  = $profile['id'] ?? $profile['sub'] ?? null;
        $email      = $profile['email'] ?? null;
        $firstName  = trim((string) ($profile['first_name'] ?? ''));
        $middleName = trim((string) ($profile['middle_name'] ?? ''));
        $lastName   = trim((string) ($profile['last_name'] ?? ''));
        $suffixName = trim((string) ($profile['name_suffix'] ?? ''));

        $nameParts = array_filter([$firstName, $middleName, $lastName, $suffixName], fn($value) => $value !== '');
        $fullName  = trim(implode(' ', $nameParts));
        $name      = $profile['name'] ?? ($fullName !== '' ? $fullName : $email);

        Log::info('OIDC PROFILE DEBUG', [
            'profile'      => $profile,
            'ssoUserId'    => $ssoUserId,
            'email'        => $email,
            'name'         => $name,
            'first_name'   => $firstName,
            'middle_name'  => $middleName,
            'last_name'    => $lastName,
            'suffix_name'  => $suffixName,
        ]);

        if (!$email) {
            return redirect()->route('login')
                ->with('error', 'Email not returned by identity provider.');
        }

        $studentData = $this->resolveStudentDataForSync($email);

        $incomingRoles = $profile['roles'] ?? [];

        if (is_string($incomingRoles)) {
            $incomingRoles = $incomingRoles ? [$incomingRoles] : [];
        }

        $roleSlug = null;

        foreach ($incomingRoles as $incomingRole) {
            if (str_contains(strtolower((string) $incomingRole), 'dentist')) {
                $roleSlug = 'dentist';
                break;
            }
        }

        $assignedAccess = ExternalAdminAccess::forIdentity($email, (string) $ssoUserId)
            ->first();

        $facultyAccess = null;

        if ($assignedAccess) {
            if (($assignedAccess->cms_status ?? 'inactive') !== 'active') {
                return $this->renderInactiveAccessPage();
            }

            if (!empty($assignedAccess->cms_role)) {
                $roleSlug = $assignedAccess->cms_role;
            } else {
                $roleSlug = 'admin';
            }
        } else {
            $hasOidcAdminRole = false;
            foreach ($incomingRoles as $incomingRole) {
                if (str_contains(strtolower((string) $incomingRole), 'admin')) {
                    $hasOidcAdminRole = true;
                    break;
                }
            }

            if ($hasOidcAdminRole) {
                Log::warning('OIDC admin role denied - not in external_admin_accesses', [
                    'email' => $email,
                    'sso_user_id' => $ssoUserId,
                    'incoming_roles' => $incomingRoles,
                ]);
            }

            $facultyAccess = Faculty::with(['user.role', 'profile'])
                ->whereHas('user', function ($query) use ($email, $ssoUserId) {
                    $query->where('email', $email);

                    if ($ssoUserId) {
                        $query->orWhere('sso_user_id', $ssoUserId);
                    }
                })
                ->first();

            if ($facultyAccess && $facultyAccess->user) {
                if (($facultyAccess->user->status ?? 'inactive') !== 'active') {
                    return $this->renderInactiveAccessPage();
                }

                if (!empty($facultyAccess->user->role?->slug)) {
                    $roleSlug = $facultyAccess->user->role->slug;
                }
            }
        }

        if (!$roleSlug) {
            $roleSlug = 'patient';
        }

        $roleId = $this->resolveLocalRoleId($roleSlug);

        Log::info('ROLE MAPPING DEBUG', [
            'incoming_roles'      => $incomingRoles,
            'mapped_role'         => $roleSlug,
            'mapped_role_id'      => $roleId,
            'assigned_access_id'  => $assignedAccess?->id,
            'assigned_cms_role'   => $assignedAccess?->cms_role,
            'assigned_cms_status' => $assignedAccess?->cms_status,
            'faculty_access_id'   => $facultyAccess?->id,
            'faculty_user_id'     => $facultyAccess?->user?->id,
            'faculty_user_role'   => $facultyAccess?->user?->role?->slug,
            'faculty_user_status' => $facultyAccess?->user?->status,
        ]);

        if (!$roleId) {
            return redirect()->route('login')
                ->with('error', 'No matching local role found for this SSO account.');
        }

        $user = User::where('email', $email)
            ->when($ssoUserId, function ($query) use ($ssoUserId) {
                $query->orWhere('sso_user_id', $ssoUserId);
            })
            ->first();

        if ($user && ($user->status ?? 'inactive') !== 'active') {
            return $this->renderInactiveAccessPage();
        }

        if (!$user) {
            $user = User::create([
                'name'          => $name ?: $email,
                'first_name'    => $firstName !== '' ? $firstName : null,
                'middle_name'   => $middleName !== '' ? $middleName : null,
                'last_name'     => $lastName !== '' ? $lastName : null,
                'suffix_name'   => $suffixName !== '' ? $suffixName : null,
                'email'         => $email,
                'role_id'       => $roleId,
                'status'        => 'active',
                'sso_user_id'   => $ssoUserId,
                'last_login_at' => now(),
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
            ]);



            Log::info('OIDC user created', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'role_id' => $user->role_id,
            ]);
        }

        $previousRoleSlug = optional($user->role)->slug;
        $shouldPreserveLocalRole = $user->exists
            && !$assignedAccess
            && !($facultyAccess && $facultyAccess->user)
            && !empty($user->role_id);

        if ($shouldPreserveLocalRole) {
            $roleId = (int) $user->role_id;
            $roleSlug = $previousRoleSlug ?: $roleSlug;
        }

        $user->name          = $name ?: $user->name ?: $email;
        $user->first_name    = $firstName !== '' ? $firstName : $user->first_name;
        $user->middle_name   = $middleName !== '' ? $middleName : $user->middle_name;
        $user->last_name     = $lastName !== '' ? $lastName : $user->last_name;
        $user->suffix_name   = $suffixName !== '' ? $suffixName : $user->suffix_name;
        $user->email         = $email;
        $user->role_id       = $roleId;
        $user->sso_user_id   = $ssoUserId ?: $user->sso_user_id;
        $user->access_token  = $accessToken;
        $user->refresh_token = $refreshToken;
        $user->last_login_at = now();
        $user->save();
        $user->refresh();
        $actualRoleSlug = optional($user->role)->slug ?? $roleSlug;

        Log::info('OIDC user role synced', [
            'user_id' => $user->id,
            'email' => $user->email,
            'previous_role' => $previousRoleSlug,
            'resolved_role' => $roleSlug,
            'saved_role' => $actualRoleSlug,
            'preserved_local_role' => $shouldPreserveLocalRole,
        ]);
        $patient = Patient::where('email', $email)->first();

        if ($patient && !$patient->user_id) {
            $patient->user_id = $user->id;
            $patient->save();
        }

        if ($actualRoleSlug === 'patient') {
            $patient = $this->syncPatientRecord(
                $user,
                $name,
                $email,
                $ssoUserId,
                $studentData,
                $patient,
                $assignedAccess,
                $facultyAccess
            );
        }

        $jwt = JWTAuth::fromUser($user);
        Cookie::queue(
            Cookie::make(
                'jwt_token',
                $jwt,
                60,
                '/',
                null,
                request()->isSecure(),
                true,
                false,
                'Lax'
            )
        );

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('oidc_id_token', $idToken);
        session()->save();
        $this->concurrentSessionService->syncCurrentSessionMetadata($request);

        $sessionResult = $this->concurrentSessionService->enforceLimitForCurrentSession(
            $user,
            $request->session()->getId()
        );

        if ($actualRoleSlug === 'patient') {
            session([
                'role'         => 'patient',
                'patient_id'   => $patient?->id,
                'patient_name' => $patient?->name,
                'email'        => $patient?->email,
            ]);

            session()->save();

            AuditLogger::log('login', 'authentication', 'Patient logged in via OIDC');

            $redirect = redirect()->route('homepage')
                ->with('login_as', $patient?->name)
                ->with('show_terms_modal', true);

            if (($sessionResult['terminated_sessions'] ?? 0) > 0) {
                $redirect->with(
                    'success',
                    'Logged in successfully. Older active session(s) were closed for your account.'
                );
            }

            return $redirect;
        }

        if (in_array($actualRoleSlug, ['admin', 'super_admin'], true)) {
            session([
                'admin_logged_in' => true,
                'role'            => $actualRoleSlug,
                'admin_id'        => $user->id,
                'admin_name'      => $user->name ?: $name ?: $email,
                'admin_email'     => $user->email,
            ]);

            session()->save();

            AuditLogger::log('login', 'authentication', 'Admin logged in via OIDC');

            $redirect = redirect()->route('admin.admin.dashboard')
                ->with('login_as', $user->name ?: $name ?: $email)
                ->with('show_terms_modal', true);

            if (($sessionResult['terminated_sessions'] ?? 0) > 0) {
                $redirect->with(
                    'success',
                    'Logged in successfully. Older active session(s) were closed for your account.'
                );
            }

            return $redirect;
        }

        if ($clinicalLandingRoute = $this->clinicalLandingRoute($user)) {
            session([
                'role'          => $actualRoleSlug,
                'dentist_id'    => $user->id,
                'dentist_name'  => $user->name ?: $name ?: $email,
                'dentist_email' => $user->email,
            ]);

            session()->save();

            AuditLogger::log('login', 'authentication', "Clinical user ({$actualRoleSlug}) logged in via OIDC");

            $redirect = redirect()->route($clinicalLandingRoute)
                ->with('login_as', $user->name ?: $name ?: $email)
                ->with('show_terms_modal', true);

            if (($sessionResult['terminated_sessions'] ?? 0) > 0) {
                $redirect->with(
                    'success',
                    'Logged in successfully. Older active session(s) were closed for your account.'
                );
            }

            return $redirect;
        }

        Auth::logout();

        return redirect()->route('login')
            ->with('error', 'Your account role is not allowed to log in.');
    }

    private function clinicalLandingRoute(User $user): ?string
    {
        foreach (
            [
                'access_dentist_dashboard' => 'dentist.dentist.dashboard',
                'view_appointments' => 'dentist.dentist.appointments',
                'manage_walk_in_patients' => 'dentist.walk-in.index',
                'manage_existing_records' => 'dentist.existing-record.index',
                'view_clinic_schedule' => 'dentist.dentist.clinic_schedule',
                'view_patient_profiles' => 'dentist.dentist.patients',
                'view_document_requests' => 'dentist.dentist.documentrequests',
                'view_inventory' => 'dentist.dentist.inventory',
                'add_inventory' => 'dentist.dentist.inventory',
                'update_inventory' => 'dentist.dentist.inventory',
                'delete_inventory' => 'dentist.dentist.inventory',
                'view_reports' => 'dentist.dentist.report',
                'create_report_files' => 'dentist.dentist.report',
            ] as $permission => $route
        ) {
            if ($user->hasPermission($permission)) {
                return $route;
            }
        }

        return null;
    }

    protected function syncPatientRecord(
        User $user,
        ?string $name,
        string $email,
        $ssoUserId,
        array $studentData,
        ?Patient $patient,
        ?ExternalAdminAccess $assignedAccess,
        ?Faculty $facultyAccess
    ): Patient {
        $patient?->loadMissing('information');

        $information = $patient?->information;

        $phone =
            $this->extractStudentPhone($studentData)
            ?: (string) ($information?->phone ?? '');

        $facultyCode =
            $information?->faculty_code;

        $studentNo =
            $this->extractStudentNumber($studentData)
            ?: $information?->student_no;

        $programCode =
            $this->extractStudentProgramCode($studentData)
            ?: $information?->course_code;

        $programName =
            $this->extractStudentProgramName($studentData)
            ?: $information?->course_name;

        $yearLevel =
            $studentData['yearLevel']
            ?? $studentData['year_level']
            ?? $information?->year_level;

        $section =
            $studentData['section']
            ?? $information?->section;


        $personalInfo = [];
        $studentAddresses = [];

        $birthdate =
            $information?->birthdate
            ? $information->birthdate->format('Y-m-d')
            : null;

        $gender =
            $information?->gender;

        $placeOfBirth =
            $information?->place_of_birth;

        $heightM =
            $information?->height_m !== null
            ? (float) $information->height_m
            : null;

        $weightKg =
            $information?->weight_kg !== null
            ? (float) $information->weight_kg
            : null;


        $address =
            $information?->address
            ?? $patient?->getRawOriginal('address');

        try {
            if (!empty($studentNo)) {
                $personalResponse = $this->studentApiService->getPersonalInfoByStudentNumber($studentNo);
                $personalInfo = is_array($personalResponse['data'] ?? null)
                    ? $personalResponse['data']
                    : [];

                $addressResponse = $this->studentApiService->getAddressesByStudentNumber($studentNo);
                $studentAddresses = is_array($addressResponse['data'] ?? null)
                    ? $addressResponse['data']
                    : [];
            }
        } catch (\Throwable $e) {
            Log::warning('Student personal info or address fetch failed', [
                'student_no' => $studentNo,
                'message' => $e->getMessage(),
            ]);
        }

 

        $resolvedStudentAddress =
            $this->formatStudentAddress(
                $studentAddresses
            );

        if (filled($resolvedStudentAddress)) {
            $address = $resolvedStudentAddress;
        }

        $birthdate = $this->normalizeDate(
            $personalInfo['dateOfBirth']
                ?? $personalInfo['birthdate']
                ?? $studentData['birthdate']
                ?? $studentData['dateOfBirth']
                ?? $birthdate
        );
        $gender = $this->normalizeGenderLabel(
            $personalInfo['gender']['name']
                ?? $personalInfo['gender']
                ?? $studentData['gender']
                ?? $gender
        );
        $placeOfBirth = $this->cleanStringValue(
            $personalInfo['placeOfBirth']
                ?? $personalInfo['place_of_birth']
                ?? $placeOfBirth
        );
        $heightM = $this->normalizeNullableFloat(
            $personalInfo['heightM']
                ?? $personalInfo['height_m']
                ?? $heightM
        );

        $weightKg = $this->normalizeNullableFloat(
            $personalInfo['weightKg']
                ?? $personalInfo['weight_kg']
                ?? $weightKg
        );

        if ($assignedAccess) {
            try {
                $baseUrl = rtrim((string) env('OCMS_EXTERNAL_API_URL'), '/');
                $apiKey = (string) env('OCMS_EXTERNAL_API_KEY');

                $response = Http::timeout(15)
                    ->acceptJson()
                    ->withHeaders([
                        'X-External-Api-Key' => $apiKey,
                    ])
                    ->get($baseUrl . '/external/admins/' . urlencode((string) $assignedAccess->external_admin_id));

                if ($response->successful()) {
                    $payload = $response->json();
                    $admin = is_array($payload['data'] ?? null) ? $payload['data'] : [];

                    $birthdate = $this->normalizeDate($admin['birthday'] ?? $birthdate);
                    $gender = $this->normalizeGenderLabel($admin['gender'] ?? $gender);
                    $phone = $admin['emergency_contact_no'] ?? $phone;
                } else {
                    $gender = $this->normalizeGenderLabel($assignedAccess->gender ?? $gender);
                    $phone = $assignedAccess->contact_number ?? $phone;
                }
            } catch (\Throwable $e) {
                Log::error('Admin API fetch failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($facultyAccess && $facultyAccess->user) {
            try {
                $localFacultyBirthdate = $facultyAccess->profile?->birthday
                    ?? $facultyAccess->user?->birthdate;

                if ($localFacultyBirthdate) {
                    $birthdate = $this->normalizeDate((string) $localFacultyBirthdate) ?? $birthdate;
                }

                $gender = $this->normalizeGenderLabel(
                    $facultyAccess->profile?->gender
                        ?? $facultyAccess->user?->gender
                        ?? $gender
                );

                $faculties = $this->facultyApiService->getFaculties();

                $matchedFaculty = collect($faculties)->first(function ($faculty) use ($email, $ssoUserId) {
                    $facultyEmail = strtolower((string) ($faculty['email'] ?? ''));
                    $userEmail = strtolower((string) $email);

                    $facultyId = (string) ($faculty['faculty_id'] ?? '');
                    $idpUserId = (string) ($faculty['idp_user_id'] ?? '');
                    $currentSsoUserId = (string) ($ssoUserId ?? '');

                    return ($facultyEmail !== '' && $facultyEmail === $userEmail)
                        || ($idpUserId !== '' && $currentSsoUserId !== '' && $idpUserId === $currentSsoUserId)
                        || ($facultyId !== '' && $currentSsoUserId !== '' && $facultyId === $currentSsoUserId);
                });

                if ($matchedFaculty) {
                    $birthdate = $this->normalizeDate(
                        $matchedFaculty['birthday']
                            ?? $matchedFaculty['birthdate']
                            ?? data_get($matchedFaculty, 'profile.birthday')
                            ?? data_get($matchedFaculty, 'profile.birthdate')
                            ?? data_get($matchedFaculty, 'profile.date_of_birth')
                            ?? data_get($matchedFaculty, 'profile.dateOfBirth')
                            ?? $birthdate
                    );
                    $gender = $this->normalizeGenderLabel($matchedFaculty['profile']['gender'] ?? $gender);
                    $phone = $matchedFaculty['contact_number'] ?? $phone;
                    $facultyCode = $matchedFaculty['faculty_code'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::error('Faculty API fetch failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($birthdate === null || $facultyCode === null) {
            try {
                $faculties = $this->facultyApiService->getFaculties();

                $matchedFaculty = collect($faculties)->first(function ($faculty) use ($email, $ssoUserId) {
                    $facultyEmail = strtolower((string) ($faculty['email'] ?? ''));
                    $userEmail = strtolower((string) $email);
                    $facultyId = (string) ($faculty['faculty_id'] ?? '');
                    $idpUserId = (string) ($faculty['idp_user_id'] ?? '');
                    $currentSsoUserId = (string) ($ssoUserId ?? '');

                    return ($facultyEmail !== '' && $facultyEmail === $userEmail)
                        || ($idpUserId !== '' && $currentSsoUserId !== '' && $idpUserId === $currentSsoUserId)
                        || ($facultyId !== '' && $currentSsoUserId !== '' && $facultyId === $currentSsoUserId);
                });

                if ($matchedFaculty) {
                    $birthdate = $this->normalizeDate(
                        $matchedFaculty['birthday']
                            ?? $matchedFaculty['birthdate']
                            ?? data_get($matchedFaculty, 'profile.birthday')
                            ?? data_get($matchedFaculty, 'profile.birthdate')
                            ?? data_get($matchedFaculty, 'profile.date_of_birth')
                            ?? data_get($matchedFaculty, 'profile.dateOfBirth')
                            ?? $birthdate
                    );
                    $facultyCode = $matchedFaculty['faculty_code'] ?? $facultyCode;
                    $gender = $this->normalizeGenderLabel($matchedFaculty['profile']['gender'] ?? $gender);
                    $phone = $matchedFaculty['contact_number'] ?? $phone;
                }
            } catch (\Throwable $e) {
                Log::warning('Faculty API fallback by email failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Resolved patient data before save', [
            'email' => $email,
            'phone' => $phone,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'faculty_code' => $facultyCode,
            'student_no' => $studentNo,
            'program_code' => $programCode,
            'program_name' => $programName,
            'year_level' => $yearLevel,
            'section' => $section,
            'place_of_birth' => $placeOfBirth,
            'height_m' => $heightM,
            'weight_kg' => $weightKg,
            'address' => $address,
        ]);

        $existingClassification = strtolower(trim((string) (
            $patient?->classification ?? ''
        )));

        $classification = 'dependent_alumni';

        if (
            $facultyAccess ||
            filled($facultyCode)
        ) {
            $classification = 'faculty';
        } elseif (filled($studentNo)) {
            $classification = 'student';
        } elseif ($assignedAccess) {
            $classification = 'administrative';
        } elseif (
            in_array(
                $existingClassification,
                [
                    'student',
                    'faculty',
                    'administrative',
                    'dependent_alumni',
                ],
                true
            )
        ) {
            $classification = $existingClassification;
        }

        $user->phone = $phone ?: $user->phone;
        $user->birthdate = $birthdate ?: $user->birthdate;
        $user->gender = $gender ?: $user->gender;
        $user->save();

        if ($patient) {
            $patient->user_id = $patient->user_id ?: $user->id;
            $patient->name = $user->name ?: $name ?: $email;
            $patient->email = $user->email;
            $patient->phone =
                $phone
                ?: $information?->phone;

            $patient->birthdate =
                $birthdate
                ?: $information?->birthdate;

            $patient->gender =
                $gender
                ?: $information?->gender;

            $patient->faculty_code =
                $facultyCode
                ?: $information?->faculty_code;

            $patient->student_no =
                $studentNo
                ?: $information?->student_no;

            $patient->course_code =
                $programCode
                ?: $information?->course_code;

            $patient->course_name =
                $programName
                ?: $information?->course_name;

            $patient->year_level =
                $yearLevel
                ?? $information?->year_level;

            $patient->section =
                $section
                ?: $information?->section;

            $patient->is_pwd =
                $information?->is_pwd
                ?? false;

            $patient->is_senior =
                $information?->is_senior
                ?? false;

            if (filled($address)) {
                $patient->address = $address;
            }

            if (empty($patient->password)) {
                $patient->password = Hash::make(Str::random(16));
            }

            $patient->save();
            $this->syncStudentMedicalHistory($patient, $personalInfo);

            return $patient;
        }

        $patientPayload = [
            'user_id' => $user->id,
            'name' => $user->name ?: $name ?: $email,
            'email' => $user->email,
            'phone' => $phone,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'password' => Hash::make(Str::random(16)),
            'faculty_code' => $facultyCode,
            'classification' => $classification,
            'student_no' => $studentNo,
            'course_code' => $programCode,
            'course_name' => $programName,
            'year_level' => $yearLevel,
            'section' => $section,
            'is_pwd' => false,
            'is_senior' => false,
            'address' => $address,

            'place_of_birth' => $placeOfBirth,
            'height_m' => $heightM,
            'weight_kg' => $weightKg,
        ];

        $patient = Patient::create($patientPayload);

        $this->syncStudentMedicalHistory($patient, $personalInfo);

        return $patient;
    }

    protected function normalizeDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return $value;
        }
    }

    protected function resolveStudentDataForSync(string $email): array
    {
        try {
            $studentProfile = $this->studentApiService->getStudentByEmail($email);
            $studentData = is_array($studentProfile['data'] ?? null)
                ? $studentProfile['data']
                : [];

            Log::info('Student API profile fetched', [
                'email' => $email,
                'student_profile' => $studentProfile,
            ]);

            if (!empty($studentData)) {
                return $studentData;
            }
        } catch (\Throwable $e) {
            Log::warning('Student API fetch by email failed; trying search fallback', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $students = $this->studentApiService->searchStudents($email, 80);
            $matchedStudent = collect($students)->first(function ($student) use ($email) {
                return strtolower((string) $this->extractStudentEmail((array) $student)) === strtolower($email);
            });

            if (is_array($matchedStudent)) {
                Log::info('Student API search fallback matched profile', [
                    'email' => $email,
                    'student_number' => $this->extractStudentNumber($matchedStudent),
                ]);

                return $matchedStudent;
            }
        } catch (\Throwable $e) {
            Log::warning('Student API search fallback failed', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    protected function extractStudentEmail(array $studentData): ?string
    {
        return $studentData['email']
            ?? $studentData['emailAddress']
            ?? $studentData['email_address']
            ?? $studentData['institutional_email']
            ?? $studentData['institutionalEmail']
            ?? null;
    }

    protected function extractStudentPhone(array $studentData): string
    {
        return (string) (
            $studentData['mobileNumber']
            ?? $studentData['mobile_number']
            ?? $studentData['contactNumber']
            ?? $studentData['contact_number']
            ?? $studentData['phone']
            ?? ''
        );
    }

    protected function extractStudentNumber(array $studentData): ?string
    {
        return $studentData['studentNumber']
            ?? $studentData['student_number']
            ?? $studentData['studentNo']
            ?? $studentData['student_no']
            ?? null;
    }

    protected function extractStudentProgramCode(array $studentData): ?string
    {
        return $studentData['program']['code']
            ?? $studentData['programCode']
            ?? $studentData['program_code']
            ?? $studentData['course']['code']
            ?? $studentData['courseCode']
            ?? $studentData['course_code']
            ?? null;
    }

    protected function extractStudentProgramName(array $studentData): ?string
    {
        $programName = $studentData['program']['name']
            ?? $studentData['program']
            ?? $studentData['course']['name']
            ?? $studentData['course']
            ?? null;

        return is_string($programName) ? $programName : null;
    }

    protected function normalizeGenderLabel(?string $value): ?string
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

    protected function normalizeNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function cleanStringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $cleaned = trim((string) $value);

        return $cleaned !== '' ? $cleaned : null;
    }

    protected function formatStudentAddress(array $addresses): ?string
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

    protected function syncStudentMedicalHistory(Patient $patient, array $personalInfo): void
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
        $emergencyNumber = $this->cleanStringValue(
            $personalInfo['emergencyContactNumber']
                ?? $personalInfo['emergency_contact_number']
                ?? data_get($personalInfo, 'emergencyContact.number')
                ?? data_get($personalInfo, 'emergencyContact.contactNumber')
                ?? data_get($personalInfo, 'emergency_contact.number')
                ?? data_get($personalInfo, 'emergency_contact.contact_number')
                ?? null
        );
        $emergencyRelation = $this->cleanStringValue(
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
        );

        if (! $emergencyPerson && ! $emergencyNumber && ! $emergencyRelation) {
            return;
        }

        $medicalHistory = MedicalHistory::firstOrNew(['patient_id' => $patient->id]);
        $currentRelation = strtolower(trim((string) ($medicalHistory->emergency_relation ?? '')));
        $hasPlaceholderRelation = in_array($currentRelation, ['', 'not specified', '(not specified)', 'n/a', 'na'], true);

        if ($emergencyPerson && empty($medicalHistory->emergency_person)) {
            $medicalHistory->emergency_person = $emergencyPerson;
        }

        if ($emergencyNumber && empty($medicalHistory->emergency_number)) {
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

    protected function resolveLocalRoleId(?string $roleSlug): ?int
    {
        $normalizedSlug = strtolower(trim((string) $roleSlug));

        if ($normalizedSlug === '') {
            return null;
        }

        $roleId = Role::where('slug', $normalizedSlug)->value('id');

        if ($roleId) {
            return (int) $roleId;
        }

        $coreRoleNames = [
            'admin' => 'Admin',
            'dentist' => 'Dentist',
            'patient' => 'Patient',
        ];

        if (!isset($coreRoleNames[$normalizedSlug])) {
            return null;
        }

        $role = Role::updateOrCreate(
            ['slug' => $normalizedSlug],
            ['name' => $coreRoleNames[$normalizedSlug]]
        );

        Log::warning('OIDC auto-restored missing core role', [
            'role_slug' => $normalizedSlug,
            'role_id' => $role->id,
        ]);

        return (int) $role->id;
    }

    protected function renderInactiveAccessPage(): HttpResponse
    {
        return response()->view('errors.403', [
            'exception' => new AccessDeniedHttpException('Your account is inactive. Please contact the administrator.'),
        ], 403);
    }
}
