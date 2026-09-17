<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\User;
use App\Services\DptReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class DptReportTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(bool $walkIn, string $classification = 'student', string $status = 'completed', string $date = '2026-09-02'): Appointment
    {
        $user = User::create(['name' => 'Maria Dela Cruz', 'last_name' => 'Dela Cruz', 'email' => uniqid('dpt').'@example.test', 'password' => bcrypt('password'), 'status' => 'active']);
        $patient = Patient::create([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'phone' => '09000000000', 'birthdate' => '1990-01-01', 'gender' => 'Female',
            'classification' => $classification, 'password' => bcrypt('password'),
        ]);
        $appointment = Appointment::create([
            'patient_id' => $patient->id, 'appointment_date' => $date,
            'appointment_time' => '09:00:00', 'status' => $status, 'service_type' => 'Cleaning',
            'is_walk_in' => $walkIn, 'dentist_id' => $user->id,
        ]);
        AppointmentProcedure::create([
            'appointment_id' => $appointment->id, 'procedure_started_at' => $date.' 09:00:00',
            'procedure_completed_at' => $date.' 09:30:00', 'procedure_duration_seconds' => 1800,
        ]);

        return $appointment;
    }

    public function test_reports_read_persisted_completed_appointments_and_normalized_timers(): void
    {
        $emergency = $this->appointment(true, 'administrative');
        $scheduled = $this->appointment(false, 'faculty');
        $this->appointment(true, 'student', 'pending');
        $this->appointment(false, 'student', 'completed', '2026-08-01');
        $report = app(DptReport::class);
        $from = Carbon::parse('2026-09-01');
        $to = Carbon::parse('2026-09-03');
        $emergencyRows = $report->records($from, $to, true);
        $scheduledRows = $report->records($from, $to, false);
        $this->assertSame([$emergency->id], $emergencyRows->pluck('id')->all());
        $this->assertSame([$scheduled->id], $scheduledRows->pluck('id')->all());
        $this->assertEquals(30, $emergencyRows->first()->minutes);
        $this->assertSame('Dela Cruz', $emergencyRows->first()->surname);
        $this->assertSame('Maria Dela Cruz', $emergencyRows->first()->dentist);
        $this->assertDatabaseHas('appointment_procedure_timings', ['duration_seconds' => 1800]);
    }

    public function test_both_templates_download_and_paginate_all_rows_and_copies(): void
    {
        $this->travelTo(Carbon::parse('2026-09-15'));
        foreach ([true, false] as $walkIn) {
            for ($i = 0; $i < 8; $i++) {
                $this->appointment($walkIn);
            }
            $this->appointment($walkIn, 'faculty');
        }
        $this->actingAs(User::first());
        $this->withoutMiddleware();
        foreach (array_keys(DptReport::TEMPLATES) as $type) {
            $response = $this->postJson(route('dentist.dentist.report.dpt-download'), [
                'report_name' => 'DPT September',
                'document_template_id' => DocumentTemplate::where('document_type', $type)->firstOrFail()->id,
                'date_from' => '2026-09-01', 'date_to' => '2026-09-03', 'quantity' => 2,
            ]);
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $reader = new Fpdi();
            $stream = \setasign\Fpdi\PdfParser\StreamReader::createByString($response->getContent());
            $this->assertSame(4, $reader->setSourceFile($stream));
        }
    }

    public function test_empty_ranges_invalid_dates_and_inactive_templates_are_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-09-15'));
        $this->appointment(true);
        $this->actingAs(User::first())->withoutMiddleware();
        $template = DocumentTemplate::where('document_type', 'dpt_non_emergency')->firstOrFail();
        $payload = ['report_name' => 'DPT', 'document_template_id' => $template->id, 'date_from' => '2026-09-01', 'quantity' => 1];
        $url = route('dentist.dentist.report.dpt-download');
        $this->postJson($url, $payload)->assertUnprocessable();
        $this->postJson($url, array_merge($payload, ['date_to' => '2026-08-01']))->assertUnprocessable()->assertJsonValidationErrors('date_to');
        $this->postJson($url, array_merge($payload, ['quantity' => 0]))->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $template->update(['status' => 'archived']);
        $this->postJson($url, $payload)->assertNotFound();
    }
}
