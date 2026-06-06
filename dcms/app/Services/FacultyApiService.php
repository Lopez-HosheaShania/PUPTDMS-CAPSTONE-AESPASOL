<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AcademicPeriod;
use Illuminate\Support\Facades\DB;

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

                return is_array($json) ? ($json['faculties'] ?? []) : [];
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

            return AcademicPeriod::updateOrCreate(
                [
                    'academic_year' => $data['academic_year'],
                    'semester' => $data['semester'],
                ],
                [
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'description' => 'Synced from FLSS active academic year endpoint.',
                    'is_active' => true,
                ]
            );
        });
    }
}
