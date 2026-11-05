<?php

namespace Tests\Unit;

use App\Services\StudentApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('ogos_student_api_access_token');
        Cache::forget('ogos_student_complete_directory_v1');
        Cache::forget('ogos_student_directory_cache');

        config([
            'services.ogos.base_url' => 'https://ogos.test/api/v1',
            'services.ogos.token_url' => 'https://ogos.test/api/v1/auth/m2m/token',
            'services.ogos.client_id' => 'test-client',
            'services.ogos.client_secret' => 'test-secret',
            'services.ogos.student_search_path' => '/integrations/students/profiles',
        ]);
    }

    public function test_it_fetches_every_student_directory_page_including_domt(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/auth/m2m/token')) {
                return Http::response(['accessToken' => 'test-token']);
            }

            $page = (int) $request['page'];

            return match ($page) {
                1 => Http::response(['data' => [
                    $this->student('2026-0001', 'BSIT', 'Bachelor of Science in Information Technology'),
                    $this->student('2026-0002', 'BSOA', 'Bachelor of Science in Office Administration'),
                ]]),
                2 => Http::response(['data' => [
                    $this->student('2026-0003', 'DOMT', 'Diploma in Office Management Technology'),
                ]]),
                default => Http::response(['data' => []]),
            };
        });

        $students = app(StudentApiService::class)->getAllStudents(pageSize: 2, maxPages: 10);

        $this->assertCount(3, $students);
        $this->assertTrue(collect($students)->contains(
            fn ($student) => data_get($student, 'program.code') === 'DOMT'
        ));

        Http::assertSentCount(3);
    }

    private function student(string $number, string $programCode, string $programName): array
    {
        return [
            'studentNumber' => $number,
            'name' => 'Test Student '.$number,
            'email' => strtolower($number).'@example.test',
            'program' => [
                'code' => $programCode,
                'name' => $programName,
            ],
            'yearLevel' => 1,
            'section' => '1',
        ];
    }
}
