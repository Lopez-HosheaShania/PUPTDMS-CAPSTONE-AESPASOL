<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentReportRecords;
use App\Services\DocumentTemplateRenderer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

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
        $this->assertSame('Report Test Patient', $services['patient_name_1']);
        $this->assertSame('30', $services['processing_time_1']);
        $this->assertSame('1', $cases['faculty_cases_1']);
        $this->assertSame('1', $gad['header_faculty']);
        $this->assertFalse(Route::has('dentist.dentist.reports.daily-treatment-record.store'));
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
