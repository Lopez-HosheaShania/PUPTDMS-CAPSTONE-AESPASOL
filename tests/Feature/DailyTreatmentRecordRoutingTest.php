<?php

namespace Tests\Feature;

use App\Http\Controllers\Dentist\DentistReportController;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DailyTreatmentRecordRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_treatment_list_groups_alumni_and_dependent_records_without_affecting_existing_classifications(): void
    {
        $dentist = $this->makeDentist();

        $student = $this->createCompletedAppointment('student', 'Student Patient', '2026-09-02', [
            'course_code' => 'BSIT',
            'student_no' => '2026-0001',
        ]);
        $faculty = $this->createCompletedAppointment('faculty', 'Faculty Patient', '2026-09-02', [
            'faculty_code' => 'FAC-001',
        ]);
        $administrative = $this->createCompletedAppointment('administrative', 'Administrative Patient', '2026-09-02', [
            'course_name' => 'Registrar Office',
        ]);
        $alumni = $this->createCompletedAppointment('alumni', 'Alumni Patient', '2026-09-02');
        $dependent = $this->createCompletedAppointment('dependent', 'Dependent Patient', '2026-09-02');

        $response = $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->getJson(route('dentist.dentist.reports.daily-treatment-record.list', [
                'month' => '2026-09',
                'per_page' => 20,
            ]));

        $response->assertOk();

        $recordsByName = collect($response->json('data'))->keyBy('patient_name');

        $this->assertSame('BSIT', $recordsByName[$student->patient->name]['office_display']);
        $this->assertSame('FAC-001', $recordsByName[$faculty->patient->name]['office_display']);
        $this->assertSame('Registrar Office', $recordsByName[$administrative->patient->name]['office_display']);
        $this->assertSame('Alumni', $recordsByName[$alumni->patient->name]['office_display']);
        $this->assertSame('Dependent', $recordsByName[$dependent->patient->name]['office_display']);

        $filteredResponse = $this->actingAs($dentist)
            ->withSession(['role' => 'dentist'])
            ->getJson(route('dentist.dentist.reports.daily-treatment-record.list', [
                'month' => '2026-09',
                'office_type' => 'Dependent',
                'per_page' => 20,
            ]));

        $filteredResponse->assertOk();

        $filteredNames = collect($filteredResponse->json('data'))
            ->pluck('patient_name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            [$alumni->patient->name, $dependent->patient->name],
            $filteredNames
        );
    }

    public function test_daily_treatment_template_audiences_route_each_classification_to_exactly_one_report_group(): void
    {
        $controller = app(DentistReportController::class);
        $matches = new ReflectionMethod($controller, 'matchesDailyTreatmentTemplateAudience');
        $matches->setAccessible(true);

        $student = $this->createCompletedAppointment('student', 'Student Matrix', '2026-09-02');
        $faculty = $this->createCompletedAppointment('faculty', 'Faculty Matrix', '2026-09-02');
        $administrative = $this->createCompletedAppointment('administrative', 'Administrative Matrix', '2026-09-02');
        $alumni = $this->createCompletedAppointment('alumni', 'Alumni Matrix', '2026-09-02');
        $dependent = $this->createCompletedAppointment('dependent', 'Dependent Matrix', '2026-09-02');
        $legacyCombined = $this->createCompletedAppointment('dependent_alumni', 'Legacy Combined Matrix', '2026-09-02');

        $studentTemplate = new DocumentTemplate(['code' => 'DTR-DEFAULT']);
        $facultyTemplate = new DocumentTemplate(['code' => 'DTR-FACULTY']);
        $alumniTemplate = new DocumentTemplate(['code' => 'DTR-ALUMNI']);

        $this->assertTrue($matches->invoke($controller, $student, $studentTemplate));
        $this->assertFalse($matches->invoke($controller, $student, $facultyTemplate));
        $this->assertFalse($matches->invoke($controller, $student, $alumniTemplate));

        $this->assertFalse($matches->invoke($controller, $faculty, $studentTemplate));
        $this->assertTrue($matches->invoke($controller, $faculty, $facultyTemplate));
        $this->assertFalse($matches->invoke($controller, $faculty, $alumniTemplate));

        $this->assertFalse($matches->invoke($controller, $administrative, $studentTemplate));
        $this->assertTrue($matches->invoke($controller, $administrative, $facultyTemplate));
        $this->assertFalse($matches->invoke($controller, $administrative, $alumniTemplate));

        $this->assertFalse($matches->invoke($controller, $alumni, $studentTemplate));
        $this->assertFalse($matches->invoke($controller, $alumni, $facultyTemplate));
        $this->assertTrue($matches->invoke($controller, $alumni, $alumniTemplate));

        $this->assertFalse($matches->invoke($controller, $dependent, $studentTemplate));
        $this->assertFalse($matches->invoke($controller, $dependent, $facultyTemplate));
        $this->assertTrue($matches->invoke($controller, $dependent, $alumniTemplate));

        $this->assertFalse($matches->invoke($controller, $legacyCombined, $studentTemplate));
        $this->assertFalse($matches->invoke($controller, $legacyCombined, $facultyTemplate));
        $this->assertTrue($matches->invoke($controller, $legacyCombined, $alumniTemplate));
    }

    public function test_alumni_dependent_download_respects_date_range_and_handles_missing_optional_patient_fields(): void
    {
        $dentist = $this->makeDentist();

        $template = DocumentTemplate::updateOrCreate(
            ['code' => 'DTR-ALUMNI'],
            [
                'name' => 'Daily Treatment Record - Alumni / Dependent',
                'document_type' => 'daily_treatment_record',
                'category' => 'Record',
                'engine' => 'html',
                'output_format' => 'pdf',
                'content' => 'template',
                'paper_size' => 'Legal',
                'orientation' => 'landscape',
                'status' => 'active',
                'is_default' => true,
                'version' => 1,
            ]
        );

        $this->createCompletedAppointment('alumni', 'Alumni In Range', '2026-09-02', [
            'email' => null,
            'phone' => null,
        ]);
        $this->createCompletedAppointment('dependent', 'Dependent In Range', '2026-09-02');
        $this->createCompletedAppointment('alumni', 'Alumni Out Of Range', '2026-08-30');
        $this->createCompletedAppointment('student', 'Student Same Day', '2026-09-02');

        $response = $this->actingAs($dentist)
            ->post(route('dentist.dentist.report.daily-treatment-record-download'), [
                'report_name' => 'alumni-dependent-sept-02',
                'document_template_id' => $template->id,
                'date_from' => '2026-09-02',
                'date_to' => '2026-09-02',
                'quantity' => 1,
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $emptyRangeResponse = $this->actingAs($dentist)
            ->postJson(route('dentist.dentist.report.daily-treatment-record-download'), [
                'report_name' => 'alumni-dependent-sept-01',
                'document_template_id' => $template->id,
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-01',
                'quantity' => 1,
            ]);

        $emptyRangeResponse->assertStatus(422)
            ->assertJson([
                'message' => 'No completed appointments found for the selected Daily Treatment Record date range.',
            ]);
    }

    public function test_student_daily_treatment_pdf_uses_all_ten_template_rows_before_adding_a_page(): void
    {
        $dentist = $this->makeDentist();

        $template = DocumentTemplate::updateOrCreate(
            ['code' => 'DTR-DEFAULT'],
            [
                'name' => 'Daily Treatment Record - Student',
                'document_type' => 'daily_treatment_record',
                'category' => 'Record',
                'engine' => 'html',
                'output_format' => 'pdf',
                'content' => 'template',
                'paper_size' => 'Legal',
                'orientation' => 'landscape',
                'status' => 'active',
                'is_default' => true,
                'version' => 1,
            ]
        );

        foreach (range(1, 9) as $index) {
            $this->createCompletedAppointment(
                'student',
                sprintf('Student Row %02d', $index),
                '2026-09-02',
                [
                    'course_code' => 'BSIT',
                    'student_no' => sprintf('2026-%04d', $index),
                    'appointment_time' => sprintf('09:%02d:00', $index),
                ]
            );
        }

        $response = $this->actingAs($dentist)
            ->post(route('dentist.dentist.report.daily-treatment-record-download'), [
                'report_name' => 'student-nine-records',
                'document_template_id' => $template->id,
                'date_from' => '2026-09-02',
                'date_to' => '2026-09-02',
                'quantity' => 1,
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $this->assertSame(1, $this->pdfPageCount($response->getContent()));
    }

    private function makeDentist(): User
    {
        $role = Role::create([
            'name' => 'Dentist',
            'slug' => 'dentist',
        ]);

        $permissions = collect([
            [
                'slug' => 'manage_appointments',
                'name' => 'Manage Appointments',
                'module' => 'Dentist',
            ],
            [
                'slug' => 'create_report_files',
                'name' => 'Create Report Files',
                'module' => 'Reports',
            ],
        ])
            ->map(fn(array $permission) => Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'module' => $permission['module'],
                ]
            ));

        $role->permissions()->attach($permissions->pluck('id'));

        return User::create([
            'name' => 'DTR Dentist',
            'email' => 'dtr-dentist@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function createCompletedAppointment(
        string $classification,
        string $patientName,
        string $appointmentDate,
        array $overrides = []
    ): Appointment {
        $email = $overrides['email'] ?? strtolower(str_replace(' ', '.', $patientName)) . '@example.com';
        $phone = $overrides['phone'] ?? '09171234567';
        $appointmentTime = $overrides['appointment_time'] ?? '09:00:00';
        $startedAt = $appointmentDate . ' 09:00:00';
        $completedAt = $appointmentDate . ' 09:45:00';

        $user = User::create([
            'name' => $patientName,
            'email' => $email ?: strtolower(str_replace(' ', '.', $patientName)) . '@fallback.example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'suffix_name' => $overrides['suffix_name'] ?? null,
        ]);

        $patient = Patient::create([
            'user_id' => $user->id,
            'name' => $patientName,
            'email' => $email,
            'phone' => $phone,
            'birthdate' => '2000-01-01',
            'gender' => $overrides['gender'] ?? 'Female',
            'classification' => $classification,
            'student_no' => $overrides['student_no'] ?? null,
            'course_code' => $overrides['course_code'] ?? null,
            'course_name' => $overrides['course_name'] ?? null,
            'faculty_code' => $overrides['faculty_code'] ?? null,
            'password' => bcrypt('password'),
        ]);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $overrides['dentist_id'] ?? null,
            'service_type' => $overrides['service_type'] ?? 'Oral Prophylaxis',
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => 'completed',
        ]);

        AppointmentProcedure::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'procedure_started_at' => $startedAt,
            'procedure_completed_at' => $completedAt,
            'procedure_duration_seconds' => 2700,
        ]);

        return $appointment->load(['patient', 'procedure']);
    }

    private function pdfPageCount(string $pdfContent): int
    {
        preg_match_all('/\/Type\s*\/Page\b/', $pdfContent, $matches);

        return count($matches[0]);
    }
}
