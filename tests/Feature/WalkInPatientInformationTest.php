<?php

namespace Tests\Feature;

use App\Http\Controllers\Dentist\WalkInController;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Services\StudentApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WalkInPatientInformationTest extends TestCase
{
    use RefreshDatabase;

    private function student(): Patient
    {
        return Patient::create([
            'name' => 'History Test', 'email' => 'history@example.test',
            'password' => bcrypt('test-password'), 'classification' => 'student', 'student_no' => '2026-00001',
            'birthdate' => '2000-01-01', 'gender' => 'Female', 'address' => 'Taguig',
        ]);
    }

    public function test_complete_saved_information_does_not_wait_for_external_requests(): void
    {
        $patient = $this->student();
        MedicalHistory::create(['patient_id' => $patient->id,
            'emergency_person' => 'Saved Contact', 'emergency_number' => '09123456789',
            'emergency_relation' => 'Parent']);
        $api = Mockery::mock(StudentApiService::class);
        $api->shouldNotReceive('getStudentByEmail');
        $api->shouldNotReceive('getPersonalInfoByStudentNumber');
        $api->shouldNotReceive('getAddressesByStudentNumber');

        $response = (new WalkInController)->patientBookingInformation($patient, $api)->getData(true);

        $this->assertTrue($response['success']);
        $this->assertSame('Saved Contact', $response['medical']['emergency_person']);
        $this->assertSame('Taguig', $response['contact']['address']);
    }

    public function test_missing_emergency_information_is_fetched_only_once(): void
    {
        $patient = $this->student();
        $api = Mockery::mock(StudentApiService::class);
        $api->shouldNotReceive('getStudentByEmail');
        $api->shouldNotReceive('getAddressesByStudentNumber');
        $api->shouldReceive('getPersonalInfoByStudentNumber')->once()->with('2026-00001')
            ->andReturn(['data' => ['emergencyContactName' => 'External Contact',
                'emergencyContactNumber' => '09123456789', 'emergencyContactRelationship' => 'Parent']]);

        $response = (new WalkInController)->patientBookingInformation($patient, $api)->getData(true);

        $this->assertTrue($response['success']);
        $this->assertSame('External Contact', $response['medical']['emergency_person']);
        $this->assertSame('09123456789', $response['medical']['emergency_number']);
    }

    public function test_missing_student_number_still_uses_email_lookup(): void
    {
        $patient = $this->student();
        $patient->student_no = '';
        $patient->save();
        $api = Mockery::mock(StudentApiService::class);
        $api->shouldReceive('getStudentByEmail')->once()->with('history@example.test')
            ->andReturn(['data' => ['studentNumber' => '2026-00001']]);
        $api->shouldNotReceive('getAddressesByStudentNumber');
        $api->shouldReceive('getPersonalInfoByStudentNumber')->once()->with('2026-00001')
            ->andReturn(['data' => ['emergencyContactName' => 'External Contact',
                'emergencyContactNumber' => '09123456789', 'emergencyContactRelationship' => 'Parent']]);

        $response = (new WalkInController)->patientBookingInformation($patient, $api)->getData(true);

        $this->assertTrue($response['success']);
        $this->assertSame('External Contact', $response['medical']['emergency_person']);
    }

    public function test_failed_external_request_is_not_repeated_and_local_data_still_loads(): void
    {
        $patient = $this->student();
        MedicalHistory::create(['patient_id' => $patient->id, 'emergency_person' => 'Saved Contact', 'emergency_number' => '', 'emergency_relation' => '']);
        $api = Mockery::mock(StudentApiService::class);
        $api->shouldNotReceive('getStudentByEmail');
        $api->shouldNotReceive('getAddressesByStudentNumber');
        $api->shouldReceive('getPersonalInfoByStudentNumber')->once()->andThrow(new \RuntimeException('Unavailable'));

        $response = (new WalkInController)->patientBookingInformation($patient, $api)->getData(true);

        $this->assertTrue($response['success']);
        $this->assertSame('Saved Contact', $response['medical']['emergency_person']);
        $this->assertSame('Taguig', $response['contact']['address']);
    }
}
