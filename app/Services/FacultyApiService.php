<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AcademicPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\AcademicYear;
use App\Models\AcademicTerm;

class FacultyApiService
{
    private string $baseUrl;
    private string $secret;

    public function __construct()
    {
        /*
         * Use FLSS_API_URL because both endpoints are under /api/v1:
         * - /api/v1/faculty-profiles
         * - /api/v1/academic-year-semester
         */
        $this->baseUrl = rtrim((string) (
            config('services.flss.api_url')
            ?: env('FLSS_API_URL')
            ?: $this->buildApiUrlFromBaseUrl()
        ), '/');

        $this->secret = (string) (
            config('services.flss.hmac_secret')
            ?: config('services.flss.secret')
            ?: env('FLSS_HMAC_SECRET')
        );
    }

    public function getFaculties(): array
    {
        try {
            if (empty($this->baseUrl) || empty($this->secret)) {
                Log::error('Faculty API config missing.', [
                    'FLSS_API_URL' => $this->baseUrl,
                    'FLSS_HMAC_SECRET_exists' => !empty($this->secret),
                ]);

                return [];
            }

            $method = 'GET';
            $url = $this->baseUrl . '/faculty-profiles';
            $body = '';
            $timestamp = (string) time();
            $nonce = '';

            $signature = $this->generateSignature(
                $method,
                $url,
                $body,
                $timestamp,
                $nonce
            );

            $response = Http::withHeaders([
                'X-HMAC-Signature' => $signature,
                'X-HMAC-Timestamp' => $timestamp,
                'X-HMAC-Nonce'     => $nonce,
            ])
                ->acceptJson()
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                $json = $response->json();

                if (! is_array($json)) {
                    return [];
                }

                return collect($this->extractFacultyList($json))
                    ->map(fn($faculty) => is_array($faculty) ? $this->normalizeFaculty($faculty) : null)
                    ->filter()
                    ->values()
                    ->all();
            }

            Log::error('Faculty API request failed.', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'url'    => $url,
            ]);

            return [];
        } catch (\Throwable $e) {
            Log::error('Faculty API error.', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getActiveAcademicYearSemester(): array
    {
        if (empty($this->baseUrl) || empty($this->secret)) {
            throw new \Exception('FLSS API config missing. Please check FLSS_API_URL and FLSS_HMAC_SECRET in .env');
        }

        $method = 'GET';
        $url = $this->baseUrl . '/academic-year-semester';
        $body = '';
        $timestamp = (string) time();

        /*
         * Keep nonce empty because your working faculty-profiles request
         * also uses an empty nonce. This keeps the same HMAC behavior.
         */
        $nonce = '';

        $signature = $this->generateSignature(
            $method,
            $url,
            $body,
            $timestamp,
            $nonce
        );

        $response = Http::withHeaders([
            'X-HMAC-Signature' => $signature,
            'X-HMAC-Timestamp' => $timestamp,
            'X-HMAC-Nonce'     => $nonce,
        ])
            ->acceptJson()
            ->timeout(15)
            ->get($url);

        if ($response->failed()) {
            Log::error('FLSS academic year request failed.', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'url'    => $url,
            ]);

            throw new \Exception(
                'Failed to retrieve active academic year from FLSS. Status: '
                    . $response->status()
                    . ' Response: '
                    . $response->body()
            );
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function generateSignature(
        string $method,
        string $url,
        string $body,
        string $timestamp,
        string $nonce
    ): string {
        $message = $this->buildMessage($method, $url, $body, $timestamp, $nonce);

        return hash_hmac('sha256', $message, $this->secret);
    }

    private function buildMessage(
        string $method,
        string $url,
        string $body,
        string $timestamp,
        string $nonce
    ): string {
        return $method . '|' . $url . '|' . $body . '|' . $timestamp . '|' . $nonce;
    }

    private function buildApiUrlFromBaseUrl(): string
    {
        $baseUrl = rtrim((string) env('FLSS_BASE_URL', ''), '/');

        if (empty($baseUrl)) {
            return '';
        }

        if (str_ends_with($baseUrl, '/api/v1')) {
            return $baseUrl;
        }

        return $baseUrl . '/api/v1';
    }

    private function extractFacultyList(array $payload): array
    {
        $possibleLists = [
            data_get($payload, 'faculties'),
            data_get($payload, 'data.faculties'),
            data_get($payload, 'data'),
            data_get($payload, 'results'),
            $payload,
        ];

        foreach ($possibleLists as $list) {
            if (is_array($list) && array_is_list($list)) {
                return $list;
            }
        }

        return [];
    }

    private function normalizeFaculty(array $faculty): ?array
    {
        $facultyId = data_get($faculty, 'faculty_id')
            ?? data_get($faculty, 'id')
            ?? data_get($faculty, 'facultyId');

        $idpUserId = data_get($faculty, 'idp_user_id')
            ?? data_get($faculty, 'idpUserId')
            ?? data_get($faculty, 'user_id')
            ?? data_get($faculty, 'userId');

        $firstName = $this->cleanValue(
            data_get($faculty, 'first_name')
                ?? data_get($faculty, 'firstName')
                ?? data_get($faculty, 'fname')
                ?? data_get($faculty, 'profile.first_name')
                ?? data_get($faculty, 'profile.firstName')
        );

        $middleName = $this->cleanValue(
            data_get($faculty, 'middle_name')
                ?? data_get($faculty, 'middleName')
                ?? data_get($faculty, 'mname')
                ?? data_get($faculty, 'profile.middle_name')
                ?? data_get($faculty, 'profile.middleName')
        );

        $lastName = $this->cleanValue(
            data_get($faculty, 'last_name')
                ?? data_get($faculty, 'lastName')
                ?? data_get($faculty, 'lname')
                ?? data_get($faculty, 'profile.last_name')
                ?? data_get($faculty, 'profile.lastName')
        );

        $suffixName = $this->cleanValue(
            data_get($faculty, 'suffix_name')
                ?? data_get($faculty, 'suffixName')
                ?? data_get($faculty, 'name_suffix')
                ?? data_get($faculty, 'profile.suffix_name')
                ?? data_get($faculty, 'profile.suffixName')
        );

        $composedName = trim(collect([$firstName, $middleName, $lastName, $suffixName])->filter()->implode(' '));

        $name = $this->cleanValue(
            data_get($faculty, 'name')
                ?? data_get($faculty, 'full_name')
                ?? data_get($faculty, 'fullName')
                ?? ($composedName !== '' ? $composedName : null)
        );

        $department = $this->cleanValue(
            data_get($faculty, 'department')
                ?? data_get($faculty, 'department_name')
                ?? data_get($faculty, 'departmentName')
                ?? data_get($faculty, 'profile.department')
                ?? data_get($faculty, 'profile.department_name')
                ?? data_get($faculty, 'profile.departmentName')
        );

        $facultyCode = $this->cleanValue(
            data_get($faculty, 'faculty_code')
                ?? data_get($faculty, 'facultyCode')
                ?? data_get($faculty, 'code')
        );

        $email = $this->cleanValue(
            data_get($faculty, 'email')
                ?? data_get($faculty, 'email_address')
                ?? data_get($faculty, 'emailAddress')
                ?? data_get($faculty, 'institutional_email')
                ?? data_get($faculty, 'institutionalEmail')
        );

        $contactNumber = $this->cleanValue(
            data_get($faculty, 'contact_number')
                ?? data_get($faculty, 'contactNumber')
                ?? data_get($faculty, 'phone')
                ?? data_get($faculty, 'mobile_number')
                ?? data_get($faculty, 'mobileNumber')
                ?? data_get($faculty, 'profile.contact_number')
                ?? data_get($faculty, 'profile.contactNumber')
        );

        $facultyType = $this->cleanValue(
            data_get($faculty, 'faculty_type')
                ?? data_get($faculty, 'facultyType')
                ?? data_get($faculty, 'employment_type')
                ?? data_get($faculty, 'employmentType')
        );

        $status = $this->cleanValue(data_get($faculty, 'status'));
        $birthday = $this->normalizeDateValue(
            data_get($faculty, 'profile.birthday')
                ?? data_get($faculty, 'profile.birthdate')
                ?? data_get($faculty, 'profile.date_of_birth')
                ?? data_get($faculty, 'profile.dateOfBirth')
                ?? data_get($faculty, 'birthday')
                ?? data_get($faculty, 'birthdate')
                ?? data_get($faculty, 'date_of_birth')
                ?? data_get($faculty, 'dateOfBirth')
                ?? data_get($faculty, 'birth_date')
        );
        $age = $birthday !== null ? $this->calculateAgeFromBirthday($birthday) : null;

        if ($name === '' && $email === '' && $facultyCode === '' && $facultyId === null) {
            return null;
        }

        return array_replace_recursive($faculty, [
            'faculty_id' => $facultyId,
            'idp_user_id' => $idpUserId,
            'name' => $name !== '' ? $name : null,
            'first_name' => $firstName !== '' ? $firstName : null,
            'middle_name' => $middleName !== '' ? $middleName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'suffix_name' => $suffixName !== '' ? $suffixName : null,
            'faculty_code' => $facultyCode !== '' ? $facultyCode : null,
            'faculty_type' => $facultyType !== '' ? $facultyType : null,
            'department' => $department !== '' ? $department : null,
            'email' => $email !== '' ? strtolower($email) : null,
            'contact_number' => $contactNumber !== '' ? $contactNumber : null,
            'status' => $status !== '' ? $status : null,
            'birthday' => $birthday,
            'birthdate' => $birthday,
            'date_of_birth' => $birthday,
            'age' => $age,
            'profile' => [
                'birthday' => $birthday,
                'birthdate' => $birthday,
                'date_of_birth' => $birthday,
                'age' => $age,
                'gender' => $this->cleanValue(
                    data_get($faculty, 'profile.gender')
                        ?? data_get($faculty, 'gender')
                ),
                'department' => $department !== '' ? $department : null,
                'address' => is_array(data_get($faculty, 'profile.address'))
                    ? data_get($faculty, 'profile.address')
                    : [],
            ],
        ]);
    }

    private function cleanValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        if (is_string($value) || is_numeric($value)) {
            $trimmed = trim((string) $value);

            if ($trimmed === '') {
                return null;
            }

            try {
                return Carbon::parse($trimmed)->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function calculateAgeFromBirthday(string $birthday): ?int
    {
        try {
            $birthDate = Carbon::parse($birthday)->startOfDay();
            $today = Carbon::today();

            if ($birthDate->greaterThan($today)) {
                return null;
            }

            return $birthDate->diffInYears($today);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function syncActiveAcademicYearSemester(): AcademicPeriod
    {
        $data = $this->getActiveAcademicYearSemester();

        $requiredFields = [
            'academic_year',
            'semester',
            'start_date',
            'end_date',
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Missing required field from FLSS response: {$field}");
            }
        }

        return DB::transaction(function () use ($data) {
            /*
         * Deactivate all existing active academic periods first.
         * This makes sure only the FLSS active academic year/semester
         * becomes active in your local system.
         */
            AcademicPeriod::where('is_active', true)->update([
                'is_active' => false,
            ]);

            $academicYear = AcademicYear::firstOrCreate([
                'name' => trim($data['academic_year']),
            ]);

            $termCode = match (strtolower(trim($data['semester']))) {
                '1st semester',
                'first semester' => 'first_semester',

                '2nd semester',
                'second semester' => 'second_semester',

                'summer' => 'summer',

                default => null,
            };

            if ($termCode === null) {
                throw new \Exception(
                    "Unsupported semester from FLSS: {$data['semester']}"
                );
            }

            $academicTerm = AcademicTerm::where('code', $termCode)->first();

            if ($academicTerm === null) {
                throw new \Exception(
                    "Academic term configuration not found: {$termCode}"
                );
            }

            $academicPeriod = AcademicPeriod::firstOrNew([
                'academic_year_id' => $academicYear->id,
                'academic_term_id' => $academicTerm->id,
            ]);

            $academicPeriod->fill([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'description' => 'Synced from FLSS active academic year endpoint.',
                'is_active' => true,
            ]);

            $academicPeriod->save();

            return $academicPeriod;
        });
    }
}
