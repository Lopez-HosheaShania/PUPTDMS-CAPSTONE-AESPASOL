<?php

namespace Tests\Feature;

use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentRequestNormalizationTest extends TestCase
{
    use RefreshDatabase;

    private function requestRecord(): DocumentRequest
    {
        $user = User::create(['name' => 'Review Test', 'email' => uniqid().'@example.test', 'password' => bcrypt('password'), 'status' => 'active']);
        $patient = Patient::create(['user_id' => $user->id, 'name' => 'Review Test', 'email' => $user->email, 'password' => bcrypt('password')]);

        return DocumentRequest::create(['patient_id' => $patient->id, 'assigned_dentist_id' => $user->id,
            'reference_number' => uniqid('DOC-'), 'document_type' => 'annual_dental_clearance', 'purpose' => 'School',
            'request_date' => '2026-09-07', 'request_time' => '09:00:00', 'status' => 'pending']);
    }

    public function test_approval_rejection_and_release_preserve_compatibility(): void
    {
        $record = $this->requestRecord();
        $this->assertDatabaseCount('document_request_reviews', 0);
        $this->assertFalse(Schema::hasColumn('document_requests', 'approved_at'));
        $record->update(['status' => 'approved', 'approved_by' => $record->assigned_dentist_id, 'approved_at' => '2026-09-07 10:00:00']);
        $this->assertSame($record->assigned_dentist_id, $record->fresh(['approvedBy'])->approvedBy->id);
        $this->assertSame('2026-09-07 10:00:00', $record->fresh()->approved_at->format('Y-m-d H:i:s'));
        $record->update(['status' => 'released']);
        $this->assertNotNull($record->fresh()->approved_at);
        $record->update(['status' => 'rejected', 'approved_at' => null, 'approved_by' => null, 'rejection_reason' => 'Missing requirements']);
        $data = $record->fresh()->toArray();
        $this->assertSame('Missing requirements', $data['rejection_reason']);
        $this->assertNull($data['approved_at']);
        $this->assertArrayNotHasKey('review', $data);
        $record->delete();
        $this->assertDatabaseCount('document_request_reviews', 0);
    }

    public function test_certificate_query_retains_approval_date_and_legacy_fallback(): void
    {
        $approved = $this->requestRecord();
        $approved->update(['status' => 'approved', 'approved_at' => '2026-09-07 10:00:00']);
        $legacy = $this->requestRecord();
        $legacy->update(['status' => 'approved']);
        DB::table('document_requests')->where('id', $legacy->id)->update(['updated_at' => '2026-09-07 11:00:00']);
        $this->requestRecord();
        $rows = DocumentRequest::withStateColumns()->withReviewColumns()->with('approvedBy')->where('status', 'approved')
            ->where(fn ($q) => $q->whereBetween('approved_at', ['2026-09-07', '2026-09-08'])
                ->orWhere(fn ($q) => $q->whereNull('approved_at')->whereBetween('updated_at', ['2026-09-07', '2026-09-08'])))
            ->orderBy('approved_at')->orderBy('updated_at')->get();
        $this->assertEqualsCanonicalizing([$approved->id, $legacy->id], $rows->modelKeys());
    }

    public function test_failed_review_write_rolls_back_status(): void
    {
        $record = $this->requestRecord();
        try {
            $record->update(['status' => 'approved', 'approved_by' => 99999999]);
            $this->fail('Expected a foreign key failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertSame('pending', $record->fresh()->status);
        }
    }

    public function test_state_filters_aggregates_and_updates_from_joined_rows(): void
    {
        $record = $this->requestRecord();
        $this->assertFalse(Schema::hasColumn('document_requests', 'status'));
        $this->assertDatabaseHas('document_request_states', ['document_request_id' => $record->id, 'status' => 'pending', 'request_time' => '09:00:00']);
        $query = DocumentRequest::withStateColumns()->where('patient_id', $record->patient_id)->whereDate('request_date', '2026-09-07');
        $this->assertSame(1, (clone $query)->where('status', 'pending')->count());
        $counts = (clone $query)->selectRaw('LOWER(status) as status_key, COUNT(*) as total')->groupBy('status_key')->pluck('total', 'status_key');
        $this->assertEquals(1, $counts['pending']);
        $joined = (clone $query)->orderBy('request_date')->orderBy('request_time')->firstOrFail();
        $joined->update(['status' => 'approved', 'approved_by' => $record->assigned_dentist_id, 'approved_at' => now()]);
        $this->assertSame('approved', $record->fresh()->status);
        $this->assertSame('2026-09-07', $record->fresh()->request_date->format('Y-m-d'));
        $this->assertArrayNotHasKey('document_request_id', $joined->toArray());
        $this->assertSame(0, (clone $query)->where('status', 'pending')->count());
        $record->delete();
        $this->assertDatabaseCount('document_request_states', 0);
    }

    public function test_state_migration_preserves_populated_requests_and_reviews(): void
    {
        $record = $this->requestRecord();
        $record->update(['status' => 'rejected', 'rejection_reason' => 'Preserve review']);
        $before = $record->fresh()->toArray();
        $migration = require database_path('migrations/2026_09_07_150000_separate_document_request_state.php');
        $migration->down();
        $this->assertDatabaseHas('document_requests', ['id' => $record->id, 'status' => 'rejected']);
        $this->assertSame(1, DB::table('document_requests')->where('id', $record->id)->whereDate('request_date', '2026-09-07')->count());
        $migration->up();
        $this->assertSame($before, $record->fresh()->toArray());
    }

    public function test_all_dental_records_approval_generates_pdf_without_procedure_patient_column(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();
        if (Schema::hasColumn('appointment_procedures', 'patient_id')) {
            Schema::table('appointment_procedures', fn ($table) => $table->dropColumn('patient_id'));
        }
        $record = $this->requestRecord();
        $record->update(['document_type' => 'All Dental Records']);
        $role = \App\Models\Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        $permission = \App\Models\Permission::firstOrCreate(['slug' => 'manage_appointments'], ['name' => 'Manage appointments', 'module' => 'Appointments']);
        $role->permissions()->attach($permission);
        $dentist = User::findOrFail($record->assigned_dentist_id);
        $dentist->update(['role_id' => $role->id]);
        $this->actingAs($dentist)->withSession(['role' => 'dentist']);
        $appointment = \App\Models\Appointment::create([
            'patient_id' => $record->patient_id, 'dentist_id' => $dentist->id,
            'appointment_date' => '2026-09-07', 'appointment_time' => '09:00:00',
            'status' => 'completed', 'service_type' => 'Cleaning',
        ]);
        $procedure = \App\Models\AppointmentProcedure::create(['appointment_id' => $appointment->id, 'diagnosis' => 'Test diagnosis']);
        $this->assertSame($procedure->id, \App\Models\AppointmentProcedure::forPatient($record->patient_id)->latest('id')->first()->id);
        $this->assertNull(\App\Models\AppointmentProcedure::forPatient(99999999)->latest('id')->first());
        $response = app(\App\Http\Controllers\DocumentRequestController::class)->approve(\Illuminate\Http\Request::create('/', 'POST'), $record->id);
        $this->assertSame(200, $response->getStatusCode(), $response->getStatusCode() === 200 ? '' : $response->getContent());
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame('approved', $record->fresh()->status);
        $this->assertSame($dentist->id, $record->fresh()->approved_by);
    }

    public function test_migration_preserves_existing_requests_in_both_directions(): void
    {
        DB::commit();
        try {
            $record = $this->requestRecord();
            $record->update(['approved_at' => now(), 'approved_by' => $record->assigned_dentist_id, 'rejection_reason' => str_repeat('Historical note ', 50)]);
            $pending = $this->requestRecord();
            $before = $record->fresh()->toArray();
            $migration = require database_path('migrations/2026_09_07_140000_separate_document_request_reviews.php');
            $migration->down();
            $this->assertDatabaseHas('document_requests', ['id' => $record->id, 'approved_by' => $record->assigned_dentist_id]);
            $migration->up();
            $this->assertSame($before, $record->fresh()->toArray());
            $this->assertNotNull($pending->fresh());
            $this->assertDatabaseCount('document_request_reviews', 1);
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } finally {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
            DB::beginTransaction();
        }
    }
}
