<?php

namespace Tests\Unit;

use App\Http\Controllers\Dentist\DentalServicesRecordController;
use App\Http\Controllers\Dentist\DentistReportController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\DentalServicesPatientName;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class DentalServicesPatientNameTest extends TestCase
{
    #[DataProvider('nameProvider')]
    public function test_it_preserves_patient_name_parts(?string $name, array $profile, string $expected): void
    {
        $patient = $this->patient($name, $profile);
        $originalName = $patient->getRawOriginal('name');

        $this->assertSame($expected, DentalServicesPatientName::surnameFirst($patient));
        $this->assertSame($originalName, $patient->getRawOriginal('name'));
        $this->assertFalse($patient->isDirty());
        $this->assertSame($profile['last_name'] ?? null, $patient->user->last_name);
    }

    public static function nameProvider(): array
    {
        $cases = [];
        foreach (['Dela Cruz', 'De la Cruz', 'Del Rosario', 'De Guzman', 'De Leon', 'San Juan', 'San Pedro', 'Santa Maria', 'Santos', 'Garcia Lopez'] as $surname) {
            $cases['profile ' . $surname] = [
                'Mary Joy ' . $surname,
                ['first_name' => 'Mary Joy', 'last_name' => $surname],
                $surname . ', Mary Joy',
            ];
        }

        return $cases + [
            'profile takes priority over stale full name' => ['Mary Joy Cruz', ['first_name' => 'Mary Joy', 'last_name' => 'Dela Cruz'], 'Dela Cruz, Mary Joy'],
            'middle initial and suffix' => ['Juan Santos Jr.', ['first_name' => 'Juan', 'middle_name' => 'P.', 'last_name' => 'Dela Cruz', 'suffix_name' => 'Jr.'], 'Dela Cruz, Juan P. Jr.'],
            'profile surname spacing is preserved' => [null, ['first_name' => 'Mary Joy', 'last_name' => 'De  la Cruz'], 'De  la Cruz, Mary Joy'],
            'surname only in profile' => ['Mary Joy Dela Cruz', ['last_name' => 'Dela Cruz'], 'Dela Cruz, Mary Joy'],
            'inconsistent incomplete profile' => ['Mary Joy Santos', ['last_name' => 'Dela Cruz'], 'Mary Joy Santos'],
            'legacy Mary Joy' => ['Mary Joy Dela Cruz', [], 'Dela Cruz, Mary Joy'],
            'legacy Juan' => ['Juan Dela Cruz', [], 'Dela Cruz, Juan'],
            'legacy simple surname' => ['Pedro Santos', [], 'Santos, Pedro'],
            'legacy De Guzman' => ['Maria De Guzman', [], 'De Guzman, Maria'],
            'legacy Del Rosario' => ['Anna Marie Del Rosario', [], 'Del Rosario, Anna Marie'],
            'legacy De la Cruz' => ['Mary Joy De la Cruz', [], 'De La Cruz, Mary Joy'],
            'legacy De Leon' => ['Maria De Leon', [], 'De Leon, Maria'],
            'legacy San Juan' => ['Juan San Juan', [], 'San Juan, Juan'],
            'legacy San Pedro' => ['Juan San Pedro', [], 'San Pedro, Juan'],
            'legacy Santa Maria' => ['Juan Santa Maria', [], 'Santa Maria, Juan'],
            'legacy suffix and middle initial' => ['Juan P. Dela Cruz III', [], 'Dela Cruz, Juan P. III'],
            'legacy extra spaces' => ["  Mary   Joy\tDela   Cruz  ", [], 'Dela Cruz, Mary Joy'],
            'already surname first' => ['Dela Cruz, Mary Joy', [], 'Dela Cruz, Mary Joy'],
            'single name' => ['Mary', [], 'Mary'],
            'null name' => [null, [], 'Unknown Patient'],
            'blank name' => ['   ', [], 'Unknown Patient'],
        ];
    }

    public function test_the_table_api_and_pdf_use_the_complete_profile_surname(): void
    {
        $patient = $this->patient('Mary Joy Cruz', ['first_name' => 'Mary Joy', 'last_name' => 'Dela Cruz']);
        $appointment = new Appointment();
        $appointment->setRelation('patient', $patient);
        $appointment->setRelation('procedure', null);
        $patient->setRelation('medicalHistory', null);

        $row = (new ReflectionMethod(DentalServicesRecordController::class, 'toFrontendRow'))
            ->invoke(new DentalServicesRecordController(), $appointment);
        $this->assertSame('Dela Cruz, Mary Joy', $row['name']);

        $pdf = new class('L', 'pt') extends Fpdi {
            public array $cellTexts = [];

            public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
            {
                $this->cellTexts[] = $txt;
                parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
            }
        };
        $pdf->AddPage();
        (new ReflectionMethod(DentistReportController::class, 'drawDentalServicesRows'))
            ->invoke(new DentistReportController(), $pdf, collect([$appointment]));
        $this->assertContains('Dela Cruz, Mary Joy', $pdf->cellTexts);
    }

    public function test_it_handles_a_missing_patient(): void
    {
        $this->assertSame('Unknown Patient', DentalServicesPatientName::surnameFirst(null));
    }

    private function patient(?string $name, array $profile): Patient
    {
        $user = new User();
        $user->exists = true;
        $user->setRelation('profile', new UserProfile($profile));

        $patient = new Patient(['name' => $name]);
        $patient->setRelation('user', $user);
        $patient->syncOriginal();

        return $patient;
    }
}
