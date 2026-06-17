<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentApiService
{
    protected string $baseUrl;
    protected string $tokenUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $studentSearchPath;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ogos.base_url'), '/');
        $this->tokenUrl = (string) config('services.ogos.token_url');
        $this->clientId = (string) config('services.ogos.client_id');
        $this->clientSecret = (string) config('services.ogos.client_secret');

        $this->studentSearchPath = '/' . ltrim(
            (string) (config('services.ogos.student_search_path') ?: '/integrations/students/profiles'),
            '/'
        );
    }

    public function getAccessToken(): string
    {
        return Cache::remember('ogos_student_api_access_token', now()->addMinutes(50), function () {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->post($this->tokenUrl, [
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret,
                ]);

            if (! $response->successful()) {
                Log::error('Student API token request failed', [
                    'url' => $this->tokenUrl,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Failed to get student API access token.');
            }

            $data = $response->json();

            $accessToken = data_get($data, 'data.accessToken')
                ?? data_get($data, 'data.access_token')
                ?? data_get($data, 'accessToken')
                ?? data_get($data, 'access_token');

            if (! $accessToken) {
                Log::error('Student API token missing in response', [
                    'response' => $data,
                ]);

                throw new Exception('Student API access token not found.');
            }

            return $accessToken;
        });
    }

    public function searchStudents(?string $search = null, int $limit = 30): array
    {
        $token = $this->getAccessToken();

        $limit = max(1, min($limit, 80));

        $url = $this->baseUrl . $this->studentSearchPath;

        $query = [
            'limit' => $limit,
            'search' => $search !== null ? trim($search) : '',
        ];

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

        return $this->extractStudentList($payload);
    }

    public function getStudentByEmail(string $email): array
    {
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

        return $response->json() ?? [];
    }

    public function getPersonalInfoByStudentNumber(string $studentNumber): array
    {
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

        return $response->json() ?? [];
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

        if (! $name) {
            $first = data_get($student, 'first_name') ?? data_get($student, 'firstName');
            $middle = data_get($student, 'middle_name') ?? data_get($student, 'middleName');
            $last = data_get($student, 'last_name') ?? data_get($student, 'lastName');

            $name = trim(collect([$first, $middle, $last])->filter()->implode(' '));
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
            'email' => strtolower($email),
            'phone' => $phone,
            'program' => $program,
            'gender' => $gender,
            'raw' => $student,
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
}
