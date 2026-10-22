<?php

namespace Tests\Feature;

use App\Models\AiServiceLog;
use App\Models\AuditLog;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\RetentionPolicy;
use App\Models\RetentionRecordReview;
use App\Models\Role;
use App\Models\User;
use App\Services\RetentionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(string $role = 'admin', bool $permitted = true): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role)]);
        if ($permitted) {
            $permission = Permission::firstOrCreate(['slug' => 'manage_system_settings'],
                ['name' => 'Manage System Settings', 'module' => 'System Settings']);
            $roleModel->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return User::create(['name' => 'Retention Administrator', 'email' => uniqid('retention-', true).'@example.test',
            'password' => bcrypt('Password123!'), 'role_id' => $roleModel->id, 'status' => 'active']);
    }

    private function payload(RetentionPolicy $policy, array $overrides = []): array
    {
        return array_replace(['action' => 'update', 'version' => $policy->version, 'confirmed' => '1',
            'duration' => 24, 'unit' => 'months', 'start_event' => 'reviewed_lifecycle_date'], $overrides);
    }

    public function test_defaults_and_administrator_interface(): void
    {
        $this->assertSame(count(config('retention.categories')), RetentionPolicy::count());
        foreach (RetentionPolicy::all() as $policy) {
            $this->assertSame(1, $policy->duration);
            $this->assertSame('years', $policy->unit);
            $this->assertSame('provisional', $policy->status);
            $this->assertFalse($policy->archive_enabled);
            $this->assertFalse($policy->disposal_enabled);
        }
        $this->actingAs($this->administrator())->get(route('admin.retention.index'))
            ->assertRedirect(route('admin.system_settings', ['tab' => 'retention']));
        $this->get(route('admin.system_settings', ['tab' => 'retention']))
            ->assertOk()->assertSee('Data Retention')->assertDontSee('Pending Review')
            ->assertDontSee('Changes require policy review')
            ->assertSee('Save Period')->assertDontSee('Manage Policy Approvals');
    }

    public function test_admin_changes_are_category_specific_and_audited_without_rewriting_history(): void
    {
        $admin = $this->administrator();
        $policy = RetentionPolicy::firstOrFail();
        $other = RetentionPolicy::whereKeyNot($policy->id)->firstOrFail();
        $oldAudit = AuditLog::where('action', 'retention_policy_created')->firstOrFail();
        $oldDescription = $oldAudit->full_description;
        $this->actingAs($admin)->put(route('admin.retention.update', $policy), $this->payload($policy,
            ['category' => $other->category, 'archive_enabled' => true, 'disposal_enabled' => true, 'status' => 'active']))
            ->assertRedirect(route('admin.system_settings', ['tab' => 'retention']));
        $this->assertSame(24, $policy->fresh()->duration);
        $this->assertSame('months', $policy->fresh()->unit);
        $this->assertSame($admin->id, $policy->fresh()->updated_by);
        $this->assertSame('provisional', $policy->fresh()->status);
        $this->assertFalse($policy->fresh()->archive_enabled);
        $this->assertFalse($policy->fresh()->disposal_enabled);
        $this->assertSame(1, $other->fresh()->duration);
        $audit = AuditLog::where('action', 'retention_policy_updated')->firstOrFail();
        $description = json_decode($audit->full_description, true);
        $this->assertSame(1, $description['previous']['duration']);
        $this->assertSame(24, $description['updated']['duration']);
        $this->assertSame('success', $description['outcome']);
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'retention_period_modified']);
        $this->assertSame($oldDescription, $oldAudit->fresh()->full_description);
    }

    public function test_system_settings_includes_retention_period_controls_without_a_separate_sidebar_entry(): void
    {
        $response = $this->actingAs($this->administrator())
            ->get(route('admin.system_settings', ['tab' => 'retention']));
        $response->assertOk()->assertSee('id="tab-retention" class="settings-section active"', false)
            ->assertSee('Save Period')->assertDontSee('Data Retention Policy');
        foreach (RetentionPolicy::all() as $policy) {
            $response->assertSee('form="retention-period-'.$policy->id.'"', false)
                ->assertSee('id="retention-duration-'.$policy->id.'"', false)
                ->assertSee(config('retention.categories.'.$policy->category.'.label'));
        }
    }

    public function test_saving_a_period_from_system_settings_returns_to_the_retention_tab(): void
    {
        $admin = $this->administrator();
        $policy = RetentionPolicy::firstOrFail();
        $other = RetentionPolicy::whereKeyNot($policy->id)->firstOrFail();
        $this->actingAs($admin)->put(route('admin.retention.update', $policy), $this->payload($policy,
            ['return_to' => 'system_settings']))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.system_settings', ['tab' => 'retention']));
        $this->assertSame(24, $policy->fresh()->duration);
        $this->assertSame('months', $policy->fresh()->unit);
        $this->assertSame(1, $other->fresh()->duration);
        $this->assertFalse($policy->fresh()->archive_enabled);
        $this->assertFalse($policy->fresh()->disposal_enabled);
        $this->assertDatabaseHas('audit_logs', ['action' => 'retention_period_modified']);
        $this->get(route('admin.system_settings', ['tab' => 'retention']))->assertOk();
    }

    public function test_log_policy_migration_adds_missing_categories_without_resetting_existing_periods(): void
    {
        RetentionPolicy::whereIn('category', ['audit_logs', 'ai_service_logs'])->delete();
        $patientPolicy = RetentionPolicy::where('category', 'patients')->firstOrFail();
        $patientPolicy->update(['duration' => 5]);
        $migration = require database_path('migrations/2026_10_22_000001_add_log_retention_policies.php');
        $migration->up();
        foreach (['audit_logs', 'ai_service_logs'] as $category) {
            $this->assertDatabaseHas('retention_policies', ['category' => $category,
                'duration' => 1, 'unit' => 'years', 'status' => 'provisional',
                'archive_enabled' => false, 'disposal_enabled' => false]);
        }
        $logPolicy = RetentionPolicy::where('category', 'audit_logs')->firstOrFail();
        $logPolicy->update(['duration' => 7]);
        $auditCount = AuditLog::count();
        $migration->up();
        $this->assertSame($auditCount, AuditLog::count());
        $this->assertSame(7, $logPolicy->fresh()->duration);
        $this->assertSame(5, $patientPolicy->fresh()->duration);
        $this->assertSame(count(config('retention.categories')), RetentionPolicy::count());
    }

    public function test_log_periods_can_be_saved_independently_without_changing_log_records(): void
    {
        $admin = $this->administrator();
        $audit = AuditLog::where('action', 'retention_policy_created')->firstOrFail();
        $aiLog = AiServiceLog::create(['feature' => 'signature_review', 'provider' => 'test',
            'status' => 'success', 'message' => 'Retention test']);
        $beforeAudit = $audit->fresh()->toArray();
        $beforeAi = $aiLog->fresh()->getAttributes();
        $this->actingAs($admin);
        foreach (['audit_logs' => $audit, 'ai_service_logs' => $aiLog] as $category => $record) {
            $policy = RetentionPolicy::where('category', $category)->firstOrFail();
            $other = RetentionPolicy::where('category', $category === 'audit_logs' ? 'ai_service_logs' : 'audit_logs')->firstOrFail();
            $this->put(route('admin.retention.update', $policy), $this->payload($policy))
                ->assertSessionHasNoErrors()->assertRedirect(route('admin.system_settings', ['tab' => 'retention']));
            $this->assertSame(24, $policy->fresh()->duration);
            $this->assertSame($other->duration, $other->fresh()->duration);
            RetentionRecordReview::create(['category' => $category, 'record_id' => $record->id,
                'retention_starts_at' => now()->subYears(5)]);
            $result = app(RetentionLifecycleService::class)->assess($policy->fresh(), $record->id);
            $this->assertNotNull($result['expires_at']);
            $this->assertFalse($result['archive_eligible']);
            $this->assertFalse($result['disposal_eligible']);
        }
        $this->assertSame($beforeAudit, $audit->fresh()->toArray());
        $this->assertSame($beforeAi, $aiLog->fresh()->getAttributes());
    }

    public function test_day_and_week_periods_can_be_saved_and_calculate_expiry(): void
    {
        $admin = $this->administrator();
        $policy = RetentionPolicy::where('category', 'users')->firstOrFail();
        RetentionRecordReview::create(['category' => 'users', 'record_id' => $admin->id,
            'retention_starts_at' => '2024-02-28 00:00:00']);
        $this->actingAs($admin);

        foreach (['days' => '2024-03-01', 'weeks' => '2024-03-13'] as $unit => $expectedDate) {
            $this->put(route('admin.retention.update', $policy),
                $this->payload($policy, ['duration' => 2, 'unit' => $unit]))
                ->assertSessionHasNoErrors()->assertRedirect();
            $policy->refresh();
            $this->assertSame($unit, $policy->unit);
            $this->assertSame(2, $policy->duration);
            $result = app(RetentionLifecycleService::class)->assess($policy, $admin->id);
            $this->assertStringStartsWith($expectedDate, $result['expires_at']);
            $this->get(route('admin.system_settings', ['tab' => 'retention']))
                ->assertOk()->assertSee('<option value="'.$unit.'" selected>', false);
        }
    }

    public function test_dentist_with_settings_permission_cannot_see_retention_controls(): void
    {
        $this->actingAs($this->administrator('dentist'))->get(route('dentist.system_settings', ['tab' => 'retention']))
            ->assertOk()->assertDontSee('id="tab-retention"', false)->assertDontSee('Save Period');
    }

    public function test_unauthorized_roles_and_unpermitted_admin_cannot_read_or_modify(): void
    {
        $policy = RetentionPolicy::firstOrFail();
        foreach (['patient', 'dentist', 'admin'] as $role) {
            $user = $this->administrator($role, $role !== 'admin');
            $this->actingAs($user)->get(route('admin.retention.index'))->assertForbidden();
            $this->put(route('admin.retention.update', $policy), $this->payload($policy))->assertForbidden();
        }
        $this->assertSame(1, $policy->fresh()->duration);
    }

    public function test_guests_and_impersonating_administrators_cannot_change_policies(): void
    {
        $policy = RetentionPolicy::firstOrFail();
        $this->get(route('admin.retention.index'))->assertRedirect('/login');
        $this->actingAs($this->administrator('super_admin'))->withSession(['impersonated_role' => 'admin'])
            ->put(route('admin.retention.update', $policy), $this->payload($policy))->assertForbidden();
    }

    public function test_approval_activation_and_edits_require_explicit_transitions(): void
    {
        $policy = RetentionPolicy::firstOrFail();
        $this->actingAs($this->administrator())->put(route('admin.retention.update', $policy),
            $this->payload($policy, ['action' => 'approve', 'approval_reference' => 'Institutional decision 123']))
            ->assertForbidden();
        $this->actingAs($this->administrator('super_admin'));
        $this->put(route('admin.retention.update', $policy), $this->payload($policy, ['action' => 'activate']))
            ->assertUnprocessable();
        $this->put(route('admin.retention.update', $policy),
            $this->payload($policy, ['action' => 'approve', 'approval_reference' => 'Institutional decision 123']))
            ->assertRedirect();
        $policy->refresh();
        $this->assertSame('approved', $policy->status);
        $this->put(route('admin.retention.update', $policy), $this->payload($policy, ['action' => 'activate']))
            ->assertRedirect();
        $policy->refresh();
        $this->assertSame('active', $policy->status);
        $this->assertFalse($policy->archive_enabled);
        $this->assertFalse($policy->disposal_enabled);
        $this->put(route('admin.retention.update', $policy), $this->payload($policy, ['action' => 'deactivate']))
            ->assertRedirect();
        $policy->refresh();
        $this->assertSame('approved', $policy->status);
        $this->put(route('admin.retention.update', $policy), $this->payload($policy))->assertRedirect();
        $policy->refresh();
        $this->assertSame('provisional', $policy->status);
        $this->assertNull($policy->approved_at);
        $this->assertNull($policy->activated_at);
        foreach (['approved', 'activated', 'deactivated'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => 'retention_policy_'.$action]);
        }
    }

    public function test_validation_and_stale_version_preserve_policy(): void
    {
        $this->actingAs($this->administrator());
        $policy = RetentionPolicy::firstOrFail();
        foreach ([['duration' => 0], ['unit' => 'invalid'], ['start_event' => 'created_at'], ['confirmed' => '0']] as $invalid) {
            $this->put(route('admin.retention.update', $policy), $this->payload($policy, $invalid))->assertSessionHasErrors();
        }
        $this->put(route('admin.retention.update', $policy), $this->payload($policy))->assertRedirect();
        $this->put(route('admin.retention.update', $policy), $this->payload($policy))->assertStatus(409);
        $this->assertSame(2, $policy->fresh()->version);
    }

    public function test_expired_records_remain_available_and_unchanged_even_with_forged_automation_flags(): void
    {
        $user = $this->administrator();
        $policy = RetentionPolicy::where('category', 'users')->firstOrFail();
        $policy->update(['start_event' => 'last_updated', 'archive_enabled' => true, 'disposal_enabled' => true]);
        $user->timestamps = false;
        $user->updated_at = now()->subYears(3);
        $user->save();
        $before = $user->fresh()->getAttributes();
        config(['retention.archiving_enabled' => true, 'retention.disposal_enabled' => true]);
        $assessment = app(RetentionLifecycleService::class)->assess($policy, $user->id);
        $this->assertFalse($assessment['archive_eligible']);
        $this->assertFalse($assessment['disposal_eligible']);
        $this->assertContains('policy_not_approved_and_active', $assessment['blockers']);
        $this->assertContains('legal_hold_or_unknown', $assessment['blockers']);
        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->actingAs($user)->get(route('admin.system_settings', ['tab' => 'retention']))->assertOk();
    }

    public function test_expiry_uses_calendar_periods_and_holds_dependencies_and_stale_reviews_block(): void
    {
        $admin = $this->administrator('super_admin');
        $policy = RetentionPolicy::where('category', 'users')->firstOrFail();
        $review = RetentionRecordReview::create(['category' => 'users', 'record_id' => $admin->id,
            'retention_starts_at' => '2024-02-29 00:00:00', 'reviewed_by' => $admin->id,
            'reviewed_at' => now(), 'review_reference' => 'Review 123', 'policy_version' => 1]);
        $result = app(RetentionLifecycleService::class)->assess($policy, $admin->id);
        $this->assertStringStartsWith('2025-02-28', $result['expires_at']);
        $this->assertContains('legal_hold_or_unknown', $result['blockers']);
        $this->assertContains('preservation_required_or_unknown', $result['blockers']);
        $this->assertContains('dependencies_not_cleared', $result['blockers']);
        $review->update(['policy_version' => 99]);
        $this->assertContains('current_review_required', app(RetentionLifecycleService::class)->assess($policy, $admin->id)['blockers']);
        $this->assertFalse(app(RetentionLifecycleService::class)->assess($policy, PHP_INT_MAX)['archive_eligible']);
    }

    public function test_expired_patient_and_medical_records_are_preserved_with_their_relationships(): void
    {
        $patient = Patient::create(['name' => 'Retention Patient', 'email' => 'retention-patient@example.test',
            'password' => bcrypt('Password123!'), 'phone' => '09123456789', 'birthdate' => '2000-01-01', 'gender' => 'Male']);
        $medical = MedicalHistory::create(['patient_id' => $patient->id, 'emergency_person' => 'Test Contact',
            'emergency_number' => '09123456789', 'emergency_relation' => 'Parent']);
        foreach (['patients' => $patient, 'medical_histories' => $medical] as $category => $record) {
            $policy = RetentionPolicy::where('category', $category)->firstOrFail();
            RetentionRecordReview::create(['category' => $category, 'record_id' => $record->id,
                'retention_starts_at' => now()->subYears(5)]);
            $before = $record->fresh()->getAttributes();
            $assessment = app(RetentionLifecycleService::class)->assess($policy, $record->id);
            $this->assertFalse($assessment['archive_eligible']);
            $this->assertFalse($assessment['disposal_eligible']);
            $this->assertSame($before, $record->fresh()->getAttributes());
        }
        $this->assertSame($patient->id, $medical->fresh()->patient->id);
    }

    public function test_active_policy_and_clear_review_cannot_enable_unimplemented_archiving_or_disposal(): void
    {
        $admin = $this->administrator('super_admin');
        $policy = RetentionPolicy::where('category', 'users')->firstOrFail();
        $policy->update(['status' => 'active', 'approved_at' => now(), 'approved_by' => $admin->id,
            'approval_reference' => 'Reviewed decision 123', 'activated_at' => now(), 'activated_by' => $admin->id,
            'archive_enabled' => true, 'disposal_enabled' => true]);
        RetentionRecordReview::create(['category' => 'users', 'record_id' => $admin->id,
            'retention_starts_at' => now()->subYears(3), 'reviewed_at' => now(), 'reviewed_by' => $admin->id,
            'review_reference' => 'Review 123', 'policy_version' => $policy->version,
            'legal_hold' => false, 'preservation_required' => false, 'dependencies_cleared' => true]);
        config(['retention.archiving_enabled' => true, 'retention.disposal_enabled' => true]);
        $result = app(RetentionLifecycleService::class)->assess($policy, $admin->id);
        $this->assertFalse($result['archive_eligible']);
        $this->assertFalse($result['disposal_eligible']);
        $this->assertContains('archive_adapter_required', $result['blockers']);
        $this->assertContains('separate_authorization_required', $result['disposal_blockers']);
        $this->assertNotNull($admin->fresh());
    }
}
