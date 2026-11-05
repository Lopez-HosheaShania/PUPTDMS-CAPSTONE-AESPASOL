<?php

namespace Tests\Feature;

use App\Models\{AuditLog, DocumentRequest, Patient, RetentionPolicy, Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetentionBasisUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private function legacyPolicy(string $category, string $rule): RetentionPolicy
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::create(['name' => 'Policy Administrator', 'email' => uniqid().'@example.test',
            'password' => bcrypt('password'), 'status' => 'active', 'role_id' => $role->id]);
        $policy = RetentionPolicy::where('category', $category)->firstOrFail();
        $policy->update(['counting_rule' => $rule, 'duration' => 5, 'unit' => 'years',
            'status' => 'active', 'version' => 6, 'updated_by' => $admin->id,
            'approved_by' => $admin->id, 'approved_at' => now()->subDay(),
            'approval_reference' => 'Existing documented approval '.$category,
            'effective_from' => now()->subDay()->toDateString(), 'policy_version' => 6,
            'activated_by' => $admin->id, 'activated_at' => now()->subDay()]);

        return $policy->fresh();
    }

    public function test_upgrade_retires_only_changed_bases_and_preserves_records_periods_and_approval_history(): void
    {
        $patientPolicy = $this->legacyPolicy('patients', 'patient_record_closed');
        $documentPolicy = $this->legacyPolicy('document_requests', 'document_request_closed');
        $unaffected = $this->legacyPolicy('users', 'account_deactivated');
        $beforeUnaffected = $unaffected->attributesToArray();
        $references = [$patientPolicy->id => $patientPolicy->approval_reference,
            $documentPolicy->id => $documentPolicy->approval_reference];
        $patient = Patient::create(['name' => 'Existing Patient', 'email' => 'existing@example.test',
            'password' => bcrypt('password'), 'classification' => 'faculty']);
        $request = DocumentRequest::create(['patient_id' => $patient->id, 'reference_number' => 'PRODUCTION-LEGACY',
            'document_type' => 'annual_dental_clearance', 'purpose' => 'Existing request',
            'request_date' => '2020-01-01', 'request_time' => '09:00:00',
            'status' => 'released', 'approved_at' => '2020-01-02 10:00:00']);
        $requestBefore = $request->fresh()->toArray();
        $patientBefore = $patient->fresh()->toArray();
        $migration = require database_path('migrations/2026_10_29_000003_update_retention_counting_bases.php');
        $migration->up();

        foreach ([$patientPolicy->id => 'linked_account_deactivated', $documentPolicy->id => 'document_request_decided'] as $id => $rule) {
            $policy = RetentionPolicy::findOrFail($id);
            $this->assertSame($rule, $policy->counting_rule);
            $this->assertSame(5, $policy->duration);
            $this->assertSame('years', $policy->unit);
            $this->assertSame('provisional', $policy->status);
            $this->assertSame(7, $policy->version);
            $this->assertNull($policy->approval);
            $audit = AuditLog::where('action', 'retention_counting_basis_changed')->get()
                ->first(fn ($row) => json_decode($row->full_description, true)['policy_id'] === $id);
            $this->assertNotNull($audit);
            $this->assertSame($references[$id], json_decode($audit->full_description, true)['previous']['approval']['approval_reference']);
        }
        $this->assertSame($beforeUnaffected, $unaffected->fresh()->attributesToArray());
        $this->assertSame($requestBefore, $request->fresh()->toArray());
        $this->assertSame($patientBefore, $patient->fresh()->toArray());
        $count = AuditLog::count();
        $migration->up();
        $migration->down();
        $this->assertSame($count, AuditLog::count());
        $this->assertSame(7, $patientPolicy->fresh()->version);
    }

    public function test_failed_audit_rolls_back_basis_version_and_approval_together(): void
    {
        $policy = $this->legacyPolicy('patients', 'patient_record_closed');
        $before = $policy->attributesToArray();
        AuditLog::creating(function (AuditLog $log) {
            if ($log->action === 'retention_counting_basis_changed') throw new \RuntimeException('Audit unavailable');
        });
        try {
            $migration = require database_path('migrations/2026_10_29_000003_update_retention_counting_bases.php');
            try {
                $migration->up();
                $this->fail('Expected an audit failure.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Audit unavailable', $exception->getMessage());
            }
            $this->assertSame($before, $policy->fresh()->attributesToArray());
        } finally {
            AuditLog::flushEventListeners();
        }
    }
}
