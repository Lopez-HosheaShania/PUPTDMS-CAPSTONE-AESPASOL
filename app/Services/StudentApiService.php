<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentApiService
{
    private const DIRECTORY_CACHE_KEY = 'ogos_student_directory_cache';
    private const FULL_DIRECTORY_CACHE_KEY = 'ogos_student_complete_directory_v1';

    protected string $baseUrl;
    protected string $tokenUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $studentSearchPath;

    public function __construct()
    {
        $this->baseUrl = rtrim(trim((string) config('services.ogos.base_url')), '/');
        $this->tokenUrl = trim((string) config('services.ogos.token_url'));
        $this->clientId = trim((string) config('services.ogos.client_id'));
        $this->clientSecret = trim((string) config('services.ogos.client_secret'));

        $this->studentSearchPath = '/' . ltrim(
            (string) (config('services.ogos.student_search_path') ?: '/integrations/students/profiles'),
            '/'
        );
    }

    public function getAccessToken(): string
    {
        return Cache::remember('ogos_student_api_access_token', now()->addMinutes(50), function () {
            $attempts = [
                [
                    'label' => 'json-camel',
                    'request' => fn() => Http::acceptJson()
                        ->asJson()
                        ->timeout(15)
                        ->post($this->tokenUrl, [
                            'clientId' => $this->clientId,
                            'clientSecret' => $this->clientSecret,
                        ]),
                ],
                [
                    'label' => 'json-snake',
                    'request' => fn() => Http::acceptJson()
                        ->asJson()
                        ->timeout(15)
                        ->post($this->tokenUrl, [
                            'client_id' => $this->clientId,
                            'client_secret' => $this->clientSecret,
                        ]),
                ],
                [
                    'label' => 'form-snake',
                    'request' => fn() => Http::acceptJson()
                        ->asForm()
                        ->timeout(15)
                        ->post($this->tokenUrl, [
                            'client_id' => $this->clientId,
                            'client_secret' => $this->clientSecret,
                        ]),
                ],
            ];

            $lastResponse = null;

            foreach ($attempts as $attempt) {
                $response = $attempt['request']();
                $lastResponse = $response;

                if ($response->successful()) {
                    Log::info('Student API token request succeeded', [
                        'url' => $this->tokenUrl,
                        'attempt' => $attempt['label'],
                    ]);

                    $data = $response->json();

                    $accessToken = data_get($data, 'data.accessToken')
                        ?? data_get($data, 'data.access_token')
                        ?? data_get($data, 'accessToken')
                        ?? data_get($data, 'access_token');

                    if (! $accessToken) {
                        Log::error('Student API token missing in response', [
                            'attempt' => $attempt['label'],
                            'response' => $data,
                        ]);

                        throw new Exception('Student API access token not found.');
                    }

                    return $accessToken;
                }

                Log::warning('Student API token attempt failed', [
                    'url' => $this->tokenUrl,
                    'attempt' => $attempt['label'],
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            if ($lastResponse) {
                Log::error('Student API token request failed', [
                    'url' => $this->tokenUrl,
                    'status' => $lastResponse->status(),
                    'body' => $lastResponse->body(),
                ]);
            }

            throw new Exception('Failed to get student API access token.');
        });
    }

    public function searchStudents(?string $search = null, int $limit = 30): array
    {
        $limit = max(1, min($limit, 80));
        try {
            $token = $this->getAccessToken();

            $url = $this->baseUrl . $this->studentSearchPath;

            $query = [
                'page' => 1,
                'page_size' => $limit,
            ];

            $trimmedSearch = $search !== null ? trim($search) : '';
            if ($trimmedSearch !== '') {
                $query['search'] = $trimmedSearch;
            }

            Log::info('Student API search request', [
                'url' => $url,
                'query' => $query,
            ]);

            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(15)
                ->get($url, $query);

            Log::info('Student API search response', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                Log::error('Student API search failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Failed to search students from Student API.');
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                Log::error('Student API returned non-JSON or empty response', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $students = $this->extractStudentList($payload);
            $this->cacheStudentDirectory($students);

            return $students;
        } catch (\Throwable $e) {
            $fallbackStudents = $this->fallbackStudentDirectory($search, $limit);

            if (! empty($fallbackStudents)) {
                Log::warning('Student API search falling back to cached or logged directory', [
                    'search' => $search,
                    'count' => count($fallbackStudents),
                    'message' => $e->getMessage(),
                ]);

                return $fallbackStudents;
            }

            throw $e;
        }
    }

    public function getAllStudents(int $pageSize = 80, int $maxPages = 100): array
    {
        $pageSize = max(1, min($pageSize, 80));
        $maxPages = max(1, min($maxPages, 100));
        $cachedStudents = Cache::get(self::FULL_DIRECTORY_CACHE_KEY);

        if (is_array($cachedStudents) && $cachedStudents !== []) {
            return $cachedStudents;
        }

        try {
            $token = $this->getAccessToken();
            $url = $this->baseUrl.$this->studentSearchPath;
            $students = [];
            $lastPageWasFull = false;

            for ($page = 1; $page <= $maxPages; $page++) {
                $response = Http::acceptJson()
                    ->withToken($token)
                    ->timeout(20)
                    ->get($url, [
                        'page' => $page,
                        'page_size' => $pageSize,
                    ]);

                if (! $response->successful()) {
                    throw new Exception(sprintf(
                        'Student directory page %d failed with status %d.',
                        $page,
                        $response->status()
                    ));
                }

                $payload = $response->json();
                if (! is_array($payload)) {
                    throw new Exception("Student directory page {$page} returned an invalid payload.");
                }

                $pageStudents = $this->extractStudentList($payload);

                Log::info('Student API directory page loaded', [
                    'page' => $page,
                    'count' => count($pageStudents),
                ]);

                if ($pageStudents === []) {
                    $lastPageWasFull = false;
                    break;
                }

                array_push($students, ...$pageStudents);
                $lastPageWasFull = count($pageStudents) >= $pageSize;

                if (! $lastPageWasFull) {
                    break;
                }
            }

            if ($lastPageWasFull) {
                throw new Exception('Student directory pagination exceeded its safety limit.');
            }

            if ($students === []) {
                throw new Exception('Student API returned an empty directory.');
            }

            $this->cacheStudentDirectory($students);
            Cache::put(self::FULL_DIRECTORY_CACHE_KEY, $students, now()->addHour());

            return $students;
        } catch (\Throwable $exception) {
            $fallbackStudents = $this->fallbackStudentDirectory(null, PHP_INT_MAX);

            if ($fallbackStudents !== []) {
                Log::warning('Complete student directory is using the cached fallback.', [
                    'count' => count($fallbackStudents),
                    'message' => $exception->getMessage(),
                ]);

                return $fallbackStudents;
            }

            throw $exception;
        }
    }

    public function getStudentByEmail(string $email, bool $allowCachedFallback = true): array
    {
        try {
            $token = $this->getAccessToken();

            $url = $this->baseUrl . '/integrations/students/profile';

            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(15)
                ->get($url, [
                    'email' => $email,
                ]);

            if (! $response->successful()) {
                Log::error('Student fetch by email failed', [
                    'email' => $email,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Failed to fetch student by email.');
            }

            $payload = $response->json() ?? [];
            $studentData = is_array(data_get($payload, 'data')) ? data_get($payload, 'data') : [];

            if (! empty($studentData)) {
                $this->cacheStudentDirectory([$studentData]);
                Cache::put($this->personalInfoCacheKey((string) $this->extractStudentNumber($studentData)), $payload, now()->addDays(7));
            }

            return $payload;
        } catch (\Throwable $e) {
            if (! $allowCachedFallback) {
                throw $e;
            }

            $student = $this->findStudentByEmailFromFallbacks($email);

            if ($student !== null) {
                Log::warning('Student fetch by email falling back to cached or logged directory', [
                    'email' => $email,
                    'message' => $e->getMessage(),
                ]);

                return ['data' => $student];
            }

            throw $e;
        }
    }

    public function getPersonalInfoByStudentNumber(string $studentNumber, bool $allowCachedFallback = true): array
    {
        try {
            $token = $this->getAccessToken();

            $url = $this->baseUrl . '/integrations/students/' . urlencode($studentNumber) . '/personal-info';

            Log::info('DEBUG Personal Info Request', [
                'student_number' => $studentNumber,
                'url' => $url,
            ]);

            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(15)
                ->get($url);

            Log::info('DEBUG Personal Info Response', [
                'student_number' => $studentNumber,
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                throw new Exception('Failed to fetch student personal info.');
            }

            $payload = $response->json() ?? [];
            Cache::put($this->personalInfoCacheKey($studentNumber), $payload, now()->addDays(7));

            return $payload;
        } catch (\Throwable $e) {
            if (! $allowCachedFallback) {
                throw $e;
            }

            $cachedPayload = Cache::get($this->personalInfoCacheKey($studentNumber));

            if (is_array($cachedPayload)) {
                Log::warning('Student personal info falling back to cached payload', [
                    'student_number' => $studentNumber,
                    'message' => $e->getMessage(),
                ]);

                return $cachedPayload;
            }

            throw $e;
        }
    }

    public function getPersonalInformationCompletion(string $studentNumber, array $profile = []): ?float
    {
        $payload = $this->getPersonalInfoByStudentNumber($studentNumber, false);
        $info = $payload['data'] ?? null;

        if (! is_array($info) || $info === []) {
            return null;
        }

        foreach (['personalInformationCompletion', 'personal_information_completion', 'completionPercentage', 'completion_percentage', 'profileCompletion', 'profile_completion'] as $field) {
            if (array_key_exists($field, $info)) {
                $value = $info[$field];

                return is_numeric($value) && (float) $value >= 0 && (float) $value <= 100
                    ? (float) $value : null;
            }
        }

        foreach (['isProfileComplete', 'is_profile_complete'] as $field) {
            if (array_key_exists($field, $info)) {
                return is_bool($info[$field]) ? ($info[$field] ? 100.0 : 0.0) : null;
            }
        }

        // GUISIS personal-info normally returns details, without a completion flag.
        // Name, phone and email come from the student profile synced at login;
        // use fresh personal-info for the remaining dashboard completion fields.
        $required = [
            $profile['name'] ?? null,
            $info['dateOfBirth'] ?? $info['birthdate'] ?? null,
            data_get($info, 'gender.name') ?? $info['gender'] ?? null,
            $profile['phone'] ?? null,
            $profile['email'] ?? null,
            $info['emergencyContactName'] ?? $info['emergency_contact_name']
                ?? data_get($info, 'emergencyContact.name') ?? data_get($info, 'emergency_contact.name'),
            $info['emergencyContactNumber'] ?? $info['emergency_contact_number']
                ?? data_get($info, 'emergencyContact.number') ?? data_get($info, 'emergencyContact.contactNumber')
                ?? data_get($info, 'emergency_contact.number') ?? data_get($info, 'emergency_contact.contact_number'),
        ];

        $completed = collect($required)->filter(fn ($value) => is_scalar($value) && filled($value))->count();

        return round(($completed / count($required)) * 100, 2);
    }

    public function getAddressesByStudentNumber(string $studentNumber): array
    {
        try {
            $token = $this->getAccessToken();

            $url = $this->baseUrl . '/integrations/students/' . urlencode($studentNumber) . '/addresses';

            Log::info('DEBUG Student Addresses Request', [
                'student_number' => $studentNumber,
                'url' => $url,
            ]);

            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(15)
                ->get($url);

            Log::info('DEBUG Student Addresses Response', [
                'student_number' => $studentNumber,
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                throw new Exception('Failed to fetch student addresses.');
            }

            $payload = $response->json() ?? [];
            Cache::put($this->addressCacheKey($studentNumber), $payload, now()->addDays(7));

            return $payload;
        } catch (\Throwable $e) {
            $cachedPayload = Cache::get($this->addressCacheKey($studentNumber));

            if (is_array($cachedPayload)) {
                Log::warning('Student addresses falling back to cached payload', [
                    'student_number' => $studentNumber,
                    'message' => $e->getMessage(),
                ]);

                return $cachedPayload;
            }

            throw $e;
        }
    }

    public function normalizeStudent(array $student): ?array
    {
        $studentNumber = data_get($student, 'student_number')
            ?? data_get($student, 'student_no')
            ?? data_get($student, 'studentNumber')
            ?? data_get($student, 'studentNo')
            ?? data_get($student, 'student_id')
            ?? data_get($student, 'studentId')
            ?? data_get($student, 'id_number')
            ?? data_get($student, 'id');

        $name = data_get($student, 'name')
            ?? data_get($student, 'full_name')
            ?? data_get($student, 'fullName');

        $first = data_get($student, 'first_name') ?? data_get($student, 'firstName');
        $middle = data_get($student, 'middle_name') ?? data_get($student, 'middleName');
        $last = data_get($student, 'last_name') ?? data_get($student, 'lastName');
        $suffix = data_get($student, 'suffix_name')
            ?? data_get($student, 'suffixName')
            ?? data_get($student, 'name_suffix')
            ?? data_get($student, 'nameSuffix')
            ?? data_get($student, 'suffix')
            ?? data_get($student, 'profile.suffix_name')
            ?? data_get($student, 'profile.suffixName')
            ?? data_get($student, 'profile.name_suffix')
            ?? data_get($student, 'profile.nameSuffix');

        $suffix = trim((string) $suffix);

        if (preg_match('/^(ii|iii|iv|v|vi|vii|viii|ix|x)\\.?$/i', $suffix)) {
            $suffix = strtoupper($suffix);
        }

        if (! $name) {
            $name = trim(collect([$first, $middle, $last, $suffix])->filter()->implode(' '));
        } elseif ($suffix !== '' && !preg_match(
            '/(?:^|\\s)' . preg_quote($suffix, '/') . '\\.?$/iu',
            $name
        )) {
            $name = trim($name . ' ' . $suffix);
        }

        $email = data_get($student, 'email')
            ?? data_get($student, 'email_address')
            ?? data_get($student, 'emailAddress')
            ?? data_get($student, 'institutional_email')
            ?? data_get($student, 'institutionalEmail');

        $phone = data_get($student, 'phone')
            ?? data_get($student, 'contact_number')
            ?? data_get($student, 'contactNumber')
            ?? data_get($student, 'mobile')
            ?? data_get($student, 'mobile_number')
            ?? data_get($student, 'mobileNumber');

        $programId = data_get($student, 'programId')
            ?? data_get($student, 'program_id')
            ?? data_get($student, 'program.id')
            ?? data_get($student, 'courseId')
            ?? data_get($student, 'course_id')
            ?? data_get($student, 'course.id');

        $program = data_get($student, 'program.code')
            ?? data_get($student, 'program_code')
            ?? data_get($student, 'programCode')
            ?? data_get($student, 'course.code')
            ?? data_get($student, 'course_code')
            ?? data_get($student, 'courseCode')
            ?? data_get($student, 'program.name')
            ?? data_get($student, 'course.name')
            ?? data_get($student, 'program')
            ?? data_get($student, 'course');

        if (is_array($program)) {
            $program = data_get($program, 'code') ?? data_get($program, 'name') ?? null;
        }

        $gender = data_get($student, 'gender')
            ?? data_get($student, 'sex');


        $birthdate = data_get($student, 'birthdate')
            ?? data_get($student, 'birth_date')
            ?? data_get($student, 'birthday')
            ?? data_get($student, 'date_of_birth')
            ?? data_get($student, 'dateOfBirth')
            ?? data_get($student, 'profile.birthdate')
            ?? data_get($student, 'profile.birth_date')
            ?? data_get($student, 'profile.birthday')
            ?? data_get($student, 'profile.date_of_birth')
            ?? data_get($student, 'profile.dateOfBirth');

        $yearLevel = data_get($student, 'year_level')
            ?? data_get($student, 'yearLevel')
            ?? data_get($student, 'year')
            ?? data_get($student, 'academic.year_level')
            ?? data_get($student, 'academic.yearLevel');

        $section = data_get($student, 'section')
            ?? data_get($student, 'section_name')
            ?? data_get($student, 'sectionName')
            ?? data_get($student, 'academic.section')
            ?? data_get($student, 'academic.section_name')
            ?? data_get($student, 'academic.sectionName');

        $isPwdRaw = data_get($student, 'is_pwd')
            ?? data_get($student, 'isPwd')
            ?? data_get($student, 'pwd')
            ?? data_get($student, 'profile.is_pwd')
            ?? data_get($student, 'profile.isPwd')
            ?? data_get($student, 'profile.pwd');

        $isPwd = match (true) {
            is_bool($isPwdRaw) =>
            $isPwdRaw,

            is_numeric($isPwdRaw) =>
            (int) $isPwdRaw === 1,

            is_string($isPwdRaw) =>
            in_array(
                strtolower(trim($isPwdRaw)),
                ['1', 'yes', 'true', 'y'],
                true
            ),

            default =>
            null,
        };

        if (! $name) {
            return null;
        }

        if (! $email) {
            $safeId = $studentNumber ?: Str::uuid()->toString();
            $email = 'student_' . Str::slug((string) $safeId, '_') . '@ogos.local';
        }

        return [
            'student_number' => $studentNumber,

            'name' => $name,

            'first_name' => $first,

            'middle_name' => $middle,

            'last_name' => $last,

            'suffix_name' => $suffix !== '' ? $suffix : null,

            'email' =>
            strtolower($email),

            'phone' =>
            $phone,

            'programId' =>
            $programId,

            'program' =>
            $program,

            'gender' =>
            $gender,

            'birthdate' =>
            $birthdate,

            'year_level' =>
            $yearLevel,

            'section' =>
            $section,

            'is_pwd' =>
            $isPwd,

            'raw' =>
            $student,
        ];
    }

    private function extractStudentList(array $payload): array
    {
        $possibleLists = [
            $payload,
            data_get($payload, 'data'),
            data_get($payload, 'data.data'),
            data_get($payload, 'data.students'),
            data_get($payload, 'data.records'),
            data_get($payload, 'students'),
            data_get($payload, 'records'),
            data_get($payload, 'results'),
        ];

        foreach ($possibleLists as $list) {
            if (is_array($list) && array_is_list($list)) {
                return $list;
            }
        }

        return [];
    }

    private function cacheStudentDirectory(array $students): void
    {
        if (empty($students)) {
            return;
        }

        $existing = Cache::get(self::DIRECTORY_CACHE_KEY, []);
        $indexed = [];

        foreach (array_merge($existing, $students) as $student) {
            if (! is_array($student)) {
                continue;
            }

            $email = strtolower((string) (
                data_get($student, 'email')
                ?? data_get($student, 'emailAddress')
                ?? data_get($student, 'email_address')
                ?? data_get($student, 'institutional_email')
                ?? data_get($student, 'institutionalEmail')
            ));

            if ($email === '') {
                continue;
            }

            $indexed[$email] = $student;
        }

        Cache::put(self::DIRECTORY_CACHE_KEY, array_values($indexed), now()->addDays(7));
    }

    private function fallbackStudentDirectory(?string $search, int $limit): array
    {
        $students = Cache::get(self::DIRECTORY_CACHE_KEY, []);

        if (empty($students)) {
            $students = $this->readStudentDirectoryFromLogs();
        }

        if (! is_array($students)) {
            return [];
        }

        $trimmedSearch = strtolower(trim((string) $search));

        $filtered = collect($students)
            ->filter(function ($student) use ($trimmedSearch) {
                if (! is_array($student)) {
                    return false;
                }

                if ($trimmedSearch === '') {
                    return true;
                }

                $haystacks = [
                    strtolower((string) (data_get($student, 'email') ?? '')),
                    strtolower((string) (data_get($student, 'studentNumber') ?? data_get($student, 'student_number') ?? '')),
                    strtolower((string) (data_get($student, 'firstName') ?? data_get($student, 'first_name') ?? '')),
                    strtolower((string) (data_get($student, 'middleName') ?? data_get($student, 'middle_name') ?? '')),
                    strtolower((string) (data_get($student, 'lastName') ?? data_get($student, 'last_name') ?? '')),
                ];

                foreach ($haystacks as $value) {
                    if ($value !== '' && str_contains($value, $trimmedSearch)) {
                        return true;
                    }
                }

                return false;
            })
            ->take($limit)
            ->values()
            ->all();

        return $filtered;
    }

    private function findStudentByEmailFromFallbacks(string $email): ?array
    {
        $normalizedEmail = strtolower(trim($email));

        foreach ($this->fallbackStudentDirectory($normalizedEmail, 80) as $student) {
            $studentEmail = strtolower((string) (
                data_get($student, 'email')
                ?? data_get($student, 'emailAddress')
                ?? data_get($student, 'email_address')
                ?? data_get($student, 'institutional_email')
                ?? data_get($student, 'institutionalEmail')
            ));

            if ($studentEmail === $normalizedEmail) {
                return $student;
            }
        }

        return null;
    }

    private function readStudentDirectoryFromLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (! is_file($logPath) || ! is_readable($logPath)) {
            return [];
        }

        $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! is_array($lines)) {
            return [];
        }

        for ($index = count($lines) - 1; $index >= 0; $index--) {
            $line = $lines[$index];

            if (! str_contains($line, 'Student API search response')) {
                continue;
            }

            $jsonStart = strpos($line, '{');

            if ($jsonStart === false) {
                continue;
            }

            $context = json_decode(substr($line, $jsonStart), true);
            $body = is_array($context) ? ($context['body'] ?? null) : null;

            if (! is_string($body) || $body === '') {
                continue;
            }

            $payload = json_decode($body, true);
            $students = is_array($payload) ? $this->extractStudentList($payload) : [];

            if (! empty($students)) {
                $this->cacheStudentDirectory($students);
                return $students;
            }
        }

        return [];
    }

    private function extractStudentNumber(array $student): ?string
    {
        return data_get($student, 'studentNumber')
            ?? data_get($student, 'student_number')
            ?? data_get($student, 'studentNo')
            ?? data_get($student, 'student_no');
    }

    private function personalInfoCacheKey(string $studentNumber): string
    {
        return 'ogos_student_personal_info_' . Str::lower(trim($studentNumber));
    }

    private function addressCacheKey(string $studentNumber): string
    {
        return 'ogos_student_addresses_' . Str::lower(trim($studentNumber));
    }
}
