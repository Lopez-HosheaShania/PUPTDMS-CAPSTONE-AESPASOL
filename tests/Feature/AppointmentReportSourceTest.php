<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentReportRecords;
use App\Services\DocumentTemplateRenderer;
use App\Http\Controllers\Dentist\DentistReportController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use setasign\Fpdi\Fpdi;

class AppointmentReportSourceTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(string $status = 'completed', string $date = '2026-09-02'): Appointment
    {
        $user = User::create(['name' => 'Report User', 'email' => uniqid('report').'@example.test', 'password' => bcrypt('password'), 'status' => 'active']);
        $patient = Patient::create([
            'user_id' => $user->id, 'name' => 'Report Test Patient', 'email' => $user->email,
            'phone' => '09000000000', 'birthdate' => '1990-01-01',
            'gender' => 'Female', 'classification' => 'faculty', 'password' => bcrypt('password'),
        ]);
        $appointment = Appointment::create([
            'patient_id' => $patient->id, 'appointment_date' => $date,
            'appointment_time' => '09:00:00', 'status' => $status, 'service_type' => 'Cleaning',
        ]);
        AppointmentProcedure::create([
            'appointment_id' => $appointment->id, 'procedure_started_at' => $date.' 09:00:00',
            'procedure_completed_at' => $date.' 09:30:00', 'procedure_duration_seconds' => 1800,
            'diagnosis' => 'Dental Caries',
            'odontogram_data' => [['tooth' => 11, 'status' => ['code' => 'D', 'label' => 'Decayed']]],
        ]);

        return $appointment;
    }

    public function test_all_template_reports_use_completed_appointments_without_legacy_tables(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07'));
        $this->appointment();
        $this->appointment('upcoming');
        $this->appointment('completed', '2026-08-01');
        foreach (['daily_treatment_records', 'daily_treatment_patients', 'daily_treatment_signatures', 'dental_service_records'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $records = app(AppointmentReportRecords::class)->between(now()->startOfMonth(), now()->endOfMonth());
        $this->assertCount(1, $records);
        $this->assertEquals(30, $records->first()->minutes_processed);
        $renderer = app(DocumentTemplateRenderer::class);
        $invoke = fn ($method, ...$args) => (new \ReflectionMethod($renderer, $method))->invoke($renderer, ...$args);
        $daily = $invoke('buildDailyTreatmentRecordContext');
        $services = $invoke('buildDentalServicesContext');
        $cases = $invoke('buildDentalCasesContext');
        $gad = $invoke('buildGadReportContext', []);
        $this->assertSame('Report Test Patient', $daily['row_1_patient_name']);
        $this->assertSame('Faculty', $daily['row_1_office']);
        $this->assertSame('Patient, Report Test', $services['patient_name_1']);
        $this->assertSame('30', $services['processing_time_1']);
        $this->assertSame('1', $cases['faculty_cases_1']);
        $this->assertSame('1', $gad['header_faculty']);
        $this->assertFalse(Route::has('dentist.dentist.reports.daily-treatment-record.store'));
        $this->travelBack();
    }

    public function test_dental_services_templates_preserve_compound_surnames_without_changing_daily_treatment_names(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07'));
        $legacy = $this->appointment();
        $legacy->patient->update(['name' => 'Mary Joy Dela Cruz']);

        $profile = $this->appointment();
        $profile->patient->update(['name' => 'Mary Joy Cruz']);
        $profile->patient->user->update(['first_name' => 'Mary Joy', 'last_name' => 'Dela Cruz']);

        $records = app(AppointmentReportRecords::class)->between(now()->startOfMonth(), now()->endOfMonth());
        $this->assertSame(['Dela Cruz, Mary Joy', 'Dela Cruz, Mary Joy'], $records->pluck('dental_services_patient_name')->all());

        $renderer = app(DocumentTemplateRenderer::class);
        $services = (new \ReflectionMethod($renderer, 'buildDentalServicesContext'))->invoke($renderer);
        $daily = (new \ReflectionMethod($renderer, 'buildDailyTreatmentRecordContext'))->invoke($renderer);

        $this->assertSame('Dela Cruz, Mary Joy', $services['patient_name_1']);
        $this->assertSame('Dela Cruz, Mary Joy', $services['patient_name_2']);
        $this->assertSame('Mary Joy Cruz', $daily['row_1_patient_name']);
        $this->assertSame('Mary Joy Dela Cruz', $daily['row_2_patient_name']);
        $this->assertDatabaseHas('patients', ['id' => $legacy->patient_id, 'name' => 'Mary Joy Dela Cruz']);
        $this->assertDatabaseHas('patients', ['id' => $profile->patient_id, 'name' => 'Mary Joy Cruz']);
        $this->travelBack();
    }

    public function test_dsr_template_times_use_the_actual_start_and_processing_minutes(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07'));
        $appointment = $this->appointment();
        $appointment->procedure->update([
            'procedure_started_at' => '2026-09-02 09:17:45',
            'procedure_completed_at' => '2026-09-02 11:00:00',
        ]);
        $renderer = app(DocumentTemplateRenderer::class);
        $services = (new \ReflectionMethod($renderer, 'buildDentalServicesContext'))->invoke($renderer);
        $this->assertSame('09:17 AM', $services['time_in_1']);
        $this->assertSame('09:47 AM', $services['time_processed_1']);
        $this->assertSame('30', $services['processing_time_1']);
        $daily = (new \ReflectionMethod($renderer, 'buildDailyTreatmentRecordContext'))->invoke($renderer);
        $this->assertSame('09/02/26 9:17 AM', $daily['row_1_date']);
        $this->assertSame('09/02/26 9:47 AM', $daily['row_1_processed']);
        $this->travelBack();
    }

    public function test_daily_report_uses_manual_timing(): void
    {
        $this->travelTo(Carbon::parse('2026-09-07'));
        foreach (['12:00:00' => '2026-09-02 12:10:00', '23:55:00' => '2026-09-03 00:05:00'] as $timeIn => $expectedEnd) {
            $appointment = $this->appointment();
            $appointment->procedure->update([
                'procedure_started_at' => '2026-09-02 '.$timeIn,
                'procedure_completed_at' => '2026-09-03 18:00:00',
                'procedure_duration_seconds' => 600,
            ]);
            $start = Carbon::parse('2026-09-02 '.$timeIn);
            $end = Carbon::parse($expectedEnd);
            $records = app(AppointmentReportRecords::class)->between(now()->startOfMonth(), now()->endOfMonth());
            $row = $records->firstWhere('id', $appointment->id);
            $this->assertSame($start->toDateTimeString(), $row->time_in->toDateTimeString());
            $this->assertSame($expectedEnd, $row->time_out->toDateTimeString());

            $renderer = app(DocumentTemplateRenderer::class);
            $context = (new \ReflectionMethod($renderer, 'buildDailyTreatmentRecordContext'))->invoke($renderer);
            $this->assertSame($start->format('m/d/y g:i A'), $context['row_1_date']);
            $this->assertSame($end->format('m/d/y g:i A'), $context['row_1_processed']);

            foreach (['DTR-DEFAULT', 'DTR-FACULTY', 'DTR-ALUMNI'] as $code) {
                $pdf = new class('L', 'pt') extends Fpdi {
                    public array $texts = [];

                    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
                    {
                        $this->texts[] = $txt;
                        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
                    }
                };
                $pdf->AddPage();
                (new \ReflectionMethod(DentistReportController::class, 'drawDailyTreatmentRecordRows'))
                    ->invoke(new DentistReportController(), $pdf, collect([$appointment->fresh()]), $code);
                $this->assertContains($start->format('m/d/y  |  h:i A'), $pdf->texts);
                $this->assertContains($end->format('m/d/y  |  h:i A'), $pdf->texts);
                $this->assertNotContains('09/02/26  |  09:00 AM', $pdf->texts);
                $this->assertNotContains('09/03/26  |  06:00 PM', $pdf->texts);
            }
        }
        $this->travelBack();
    }

    public function test_drop_migration_can_restore_schema_and_refuses_to_remove_populated_tables(): void
    {
        $migration = require database_path('migrations/2026_09_07_120000_drop_unused_treatment_record_tables.php');
        $migration->down();
        $this->assertTrue(Schema::hasTable('daily_treatment_patients'));
        $id = DB::table('daily_treatment_records')->insertGetId([
            'treatment_date' => '2026-09-01', 'treatment_done' => 'Legacy manual entry',
        ]);
        try {
            $migration->up();
            $this->fail('A populated table must block the drop.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('contains records', $exception->getMessage());
            $this->assertTrue(Schema::hasTable('daily_treatment_signatures'));
            $this->assertDatabaseHas('daily_treatment_records', ['id' => $id]);
        }
        DB::table('daily_treatment_records')->where('id', $id)->delete();
        $migration->up();
        $this->assertFalse(Schema::hasTable('daily_treatment_records'));
    }
}
