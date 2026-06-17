<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\ExternalAdminAccess;
use App\Models\Faculty;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\FacultyApiService;
use App\Services\StudentApiService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class OIDCController extends Controller
{
    protected StudentApiService $studentApiService;
    protected FacultyApiService $facultyApiService;

    public function __construct(
        FacultyApiService $facultyApiService,
        StudentApiService $studentApiService
    ) {
        $this->facultyApiService = $facultyApiService;
        $this->studentApiService = $studentApiService;
    }

   public function redirect(Request $request)
{
    $loginUrl     = config('services.idp.login_url');
    $authorizeUrl = config('services.oidc.authorize_url');
    $clientId     = config('services.oidc.client_id');
    $redirectUri  = config('services.oidc.redirect');
    $scope        = config('services.oidc.scope', 'openid profile email');

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

        // Force IDP to ask credentials again instead of silent login
        'prompt'        => 'login',
        'max_age'       => 0,
        'fresh'         => now()->timestamp,
    ]);

    $baseUrl = $loginUrl ?: $authorizeUrl;

    if (!$baseUrl) {
        return redirect()->route('login')
            ->with('error', 'OIDC login URL is not configured.');
    }

    $separator = str_contains($baseUrl, '?') ? '&' : '?';
    $fullUrl = $baseUrl . $separator . $query;

    Log::info('OIDC redirect URL', [
        'url' => $fullUrl,
    ]);

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

        $studentData = [];

        try {
            $studentProfile = $this->studentApiService->getStudentByEmail($email);
            $studentData = is_array($studentProfile['data'] ?? null)
                ? $studentProfile['data']
                : [];

            Log::info('Student API profile fetched', [
                'email' => $email,
                'student_profile' => $studentProfile,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Student API fetch failed', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }

        $incomingRoles = $profile['roles'] ?? [];

        if (is_string($incomingRoles)) {
            $incomingRoles = $incomingRoles ? [$incomingRoles] : [];
        }

        $roleSlug = null;

        foreach ($incomingRoles as $incomingRole) {
            $incomingRole = strtolower((string) $incomingRole);

            if (str_contains($incomingRole, 'dentist')) {
                $roleSlug = 'dentist';
                break;
            }

            if (str_contains($incomingRole, 'admin')) {
                $roleSlug = 'admin';
            }
        }

        if (!$roleSlug) {
            $roleSlug = 'patient';
        }

        $assignedAccess = ExternalAdminAccess::where('email', $email)
            ->orWhere('external_admin_id', (string) $ssoUserId)
            ->first();

        $facultyAccess = Faculty::with(['user.role'])
            ->whereHas('user', function ($query) use ($email, $ssoUserId) {
                $query->where('email', $email);

                if ($ssoUserId) {
                    $query->orWhere('sso_user_id', $ssoUserId);
                }
            })
            ->first();

        if ($assignedAccess) {
            if (($assignedAccess->cms_status ?? 'inactive') !== 'active') {
                return redirect()->route('login')
                    ->with('error', 'Your CMS access is inactive. Contact administrator.');
            }

            if (!empty($assignedAccess->cms_role)) {
                $roleSlug = $assignedAccess->cms_role;
            }
        } elseif ($facultyAccess && $facultyAccess->user) {
            if (($facultyAccess->user->status ?? 'inactive') !== 'active') {
                return redirect()->route('login')
                    ->with('error', 'Your CMS access is inactive. Contact administrator.');
            }

            if (!empty($facultyAccess->user->role?->slug)) {
                $roleSlug = $facultyAccess->user->role->slug;
                $roleId = Role::where('slug', $roleSlug)->value('id');
            }
        }

        $roleId = Role::where('slug', $roleSlug)->value('id');

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

        $user->name          = $name ?: $user->name ?: $email;
        $user->first_name    = $firstName !== '' ? $firstName : $user->first_name;
        $user->middle_name   = $middleName !== '' ? $middleName : $user->middle_name;
        $user->last_name     = $lastName !== '' ? $lastName : $user->last_name;
        $user->suffix_name   = $suffixName !== '' ? $suffixName : $user->suffix_name;
        $user->email         = $email;
        if ($user->wasRecentlyCreated) {
            $user->role_id = $roleId;
        }
        $user->sso_user_id   = $ssoUserId ?: $user->sso_user_id;
        $user->access_token  = $accessToken;
        $user->refresh_token = $refreshToken;
        $user->last_login_at = now();
        $user->status        = 'active';
        $user->save();
        // I-reload para makuha yung actual role na naka-set sa DB
        $user->refresh();
        $actualRoleSlug = optional($user->role)->slug ?? $roleSlug;
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
        session()->save();

        if ($actualRoleSlug === 'patient') {
            session([
                'role'         => 'patient',
                'patient_id'   => $patient?->id,
                'patient_name' => $patient?->name,
                'email'        => $patient?->email,
            ]);

            session()->save();

            AuditLogger::log('login', 'authentication', 'Patient logged in via OIDC');

            return redirect()->route('homepage')
                ->with('login_as', $patient?->name)
                ->with('show_terms_modal', true);
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

            return redirect()->route('admin.admin.dashboard')
                ->with('login_as', $user->name ?: $name ?: $email)
                ->with('show_terms_modal', true);
        }

        if ($actualRoleSlug === 'dentist') {
            session([
                'role'          => 'dentist',
                'dentist_id'    => $user->id,
                'dentist_name'  => $user->name ?: $name ?: $email,
                'dentist_email' => $user->email,
            ]);

            session()->save();

            AuditLogger::log('login', 'authentication', 'Dentist logged in via OIDC');

            return redirect()->route('dentist.dentist.dashboard')
                ->with('login_as', $user->name ?: $name ?: $email)
                ->with('show_terms_modal', true);
        }

        Auth::logout();

        return redirect()->route('login')
            ->with('error', 'Your account role is not allowed to log in.');
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
        $phone = $studentData['mobileNumber'] ?? '';
        $facultyCode = null;
        $studentNo = $studentData['studentNumber'] ?? null;
        $courseCode = $studentData['course']['code'] ?? null;
        $courseName = $studentData['course']['name'] ?? null;
        $yearLevel = $studentData['yearLevel'] ?? null;
        $section = $studentData['section'] ?? null;

        $personalInfo = [];
        $birthdate = null;
        $gender = null;

        try {
            if (!empty($studentNo)) {
                $personalResponse = $this->studentApiService->getPersonalInfoByStudentNumber($studentNo);
                $personalInfo = is_array($personalResponse['data'] ?? null)
                    ? $personalResponse['data']
                    : [];
            }
        } catch (\Throwable $e) {
            Log::warning('Student personal info fetch failed', [
                'student_no' => $studentNo,
                'message' => $e->getMessage(),
            ]);
        }

        $birthdate = $this->normalizeDate($personalInfo['dateOfBirth'] ?? null);
        $gender = $personalInfo['gender']['name'] ?? null;

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
                    $gender = $admin['gender'] ?? $gender;
                    $phone = $admin['emergency_contact_no'] ?? $phone;
                } else {
                    $gender = $assignedAccess->gender ?? $gender;
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
                $faculties = $this->facultyApiService->getFaculties();

                $matchedFaculty = collect($faculties)->first(function ($faculty) use ($email, $ssoUserId) {
                    $facultyEmail = strtolower((string) ($faculty['email'] ?? ''));
                    $userEmail = strtolower((string) $email);

                    $facultyId = (string) ($faculty['faculty_id'] ?? '');
                    $currentSsoUserId = (string) ($ssoUserId ?? '');

                    return ($facultyEmail !== '' && $facultyEmail === $userEmail)
                        || ($facultyId !== '' && $currentSsoUserId !== '' && $facultyId === $currentSsoUserId);
                });

                if ($matchedFaculty) {
                    $birthdate = $this->normalizeDate($matchedFaculty['profile']['birthday'] ?? $birthdate);
                    $gender = $matchedFaculty['profile']['gender'] ?? $gender;
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

        Log::info('Resolved patient data before save', [
            'email' => $email,
            'phone' => $phone,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'faculty_code' => $facultyCode,
            'student_no' => $studentNo,
            'course_code' => $courseCode,
            'course_name' => $courseName,
            'year_level' => $yearLevel,
            'section' => $section,
        ]);

        if ($patient) {
            $patient->user_id = $patient->user_id ?: $user->id;
            $patient->name = $user->name ?: $name ?: $email;
            $patient->email = $user->email;
            $patient->phone = $phone ?: $patient->phone;
            $patient->birthdate = $birthdate ?: $patient->birthdate;
            $patient->gender = $gender ?: $patient->gender;
            $patient->faculty_code = $facultyCode ?: $patient->faculty_code;
            $patient->student_no = $studentNo ?: $patient->student_no;
            $patient->course_code = $courseCode ?: $patient->course_code;
            $patient->course_name = $courseName ?: $patient->course_name;
            $patient->year_level = $yearLevel ?: $patient->year_level;
            $patient->section = $section ?: $patient->section;
            $patient->is_pwd = $patient->is_pwd ?? false;
            $patient->is_senior = $patient->is_senior ?? false;
            $patient->address = $patient->address ?? null;

            if (empty($patient->password)) {
                $patient->password = Hash::make(Str::random(16));
            }

            $patient->save();

            return $patient;
        }

        return Patient::create([
            'user_id' => $user->id,
            'name' => $user->name ?: $name ?: $email,
            'email' => $user->email,
            'phone' => $phone,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'password' => Hash::make(Str::random(16)),
            'faculty_code' => $facultyCode,
            'student_no' => $studentNo,
            'course_code' => $courseCode,
            'course_name' => $courseName,
            'year_level' => $yearLevel,
            'section' => $section,
            'is_pwd' => false,
            'is_senior' => false,
            'address' => null,
        ]);
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
}
