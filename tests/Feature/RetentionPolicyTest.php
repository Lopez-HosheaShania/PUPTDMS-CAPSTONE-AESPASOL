<?php

namespace Tests\Feature;

use App\Models\AiServiceLog;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\RetentionPolicy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            'duration' => 24, 'unit' => 'months', 'password' => 'Password123!',
            'counting_rule' => array_key_first($policy->countingRules()), 'effective_from' => now()->toDateString()], $overrides);
    }

    public function test_defaults_and_administrator_interface(): void
    {
        $this->assertFalse(Schema::hasTable('retention_record_reviews'));
        $this->assertFalse(Schema::hasColumn('retention_policies', 'start_event'));
        $this->assertSame(['users', 'patients', 'clinical_records', 'appointments',
            'document_requests', 'audit_logs', 'ai_service_logs'], array_keys(config('retention.categories')));
        $this->assertSame(count(config('retention.categories')), RetentionPolicy::count());
        foreach (RetentionPolicy::all() as $policy) {
            $this->assertSame(1, $policy->duration);
            $this->assertSame('years', $policy->unit);
            $this->assertSame('provisional', $policy->status);
            $this->assertArrayNotHasKey('archive_enabled', $policy->getAttributes());
            $this->assertArrayNotHasKey('disposal_enabled', $policy->getAttributes());
        }
        $this->actingAs($this->administrator())->get(route('admin.retention.index'))
            ->assertRedirect(route('admin.system_settings', ['tab' => 'retention']));
        $this->get(route('admin.system_settings', ['tab' => 'retention']))
            ->assertOk()->assertSee('Data Retention')->assertSee('Pending Review')
            ->assertDontSee('Changes require policy review')
            ->assertSee('Save Period')->assertDontSee('Manage Policy Approvals')
            ->assertSee('id="retentionPasswordDialog"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertDontSee('name="start_event"', false);
    }

    private function legacyClinicalPolicies(): array
    {
        RetentionPolicy::where('category', 'clinical_records')->delete();
        $appointments = RetentionPolicy::where('category', 'appointments')->firstOrFail();
        $appointments->update(['duration' => 5, 'unit' => 'years']);
        $medical = RetentionPolicy::create([
            'category' => 'medical_histories', 'duration' => 60, 'unit' => 'months',
            'status' => 'provisional', 'version' => 3,
        ]);
        $dental = RetentionPolicy::create([
            'category' => 'dental_histories', 'duration' => 5, 'unit' => 'years',
            'status' => 'provisional', 'version' => 2,
        ]);

        return [$appointments, $medical, $dental];
    }

    public function test_clinical_consolidation_preserves_legacy_periods_and_approvals_without_reapproving_them(): void
    {
        [$appointments, $medical, $dental] = $this->legacyClinicalPolicies();
        $admin = $this->administrator('super_admin');
        $medical->update(['status' => 'active', 'approved_by' => $admin->id,
            'approved_at' => now(), 'approval_reference' => 'Existing medical history policy approval',
            'activated_by' => $admin->id, 'activated_at' => now()]);
        $before = $medical->fresh()->getAttributes();
        $approval = $medical->fresh()->approval->getAttributes();
        $dentalBefore = $dental->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_10_29_000001_consolidate_clinical_retention_policy.php');
        $migration->up();

        $clinical = RetentionPolicy::where('category', 'clinical_records')->firstOrFail();
        $this->assertSame(5, $clinical->duration);
        $this->assertSame('years', $clinical->unit);
        $this->assertSame('provisional', $clinical->status);
        $this->assertNull($clinical->approval);
        $this->assertSame($before, $medical->fresh()->getAttributes());
        $this->assertSame($approval, $medical->fresh()->approval->getAttributes());
        $this->assertSame($dentalBefore, $dental->fresh()->getAttributes());
        $this->assertSame(7, RetentionPolicy::current()->count());
        $this->assertDatabaseCount('retention_policies', 9);
        $auditCount = AuditLog::count();
        $clinical->update(['duration' => 8]);
        $migration->up();
        $migration->down();
        $this->assertSame(8, $clinical->fresh()->duration);
        $this->assertSame($auditCount, AuditLog::count());

        $response = $this->actingAs($admin)->get(route('admin.system_settings', ['tab' => 'retention']))->assertOk();
        foreach (['Clinical records', 'Appointment records', 'Document requests and issued documents',
            'Audit and security logs', 'Technical and AI service logs'] as $label) {
            $response->assertSee($label);
        }
        foreach ([$medical, $dental] as $legacy) {
            $response->assertDontSee('id="retention-duration-'.$legacy->id.'"', false)
                ->assertDontSee('id="retention-period-'.$legacy->id.'"', false);
            $this->put(route('admin.retention.update', $legacy), $this->payload($legacy))->assertNotFound();
        }
        $this->put(route('admin.retention.update', $clinical), $this->payload($clinical))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(24, $clinical->fresh()->duration);
        $this->assertSame('months', $clinical->fresh()->unit);
        $this->assertSame(5, $appointments->fresh()->duration);
        $this->assertSame($before, $medical->fresh()->getAttributes());
        $this->assertSame($approval, $medical->fresh()->approval->getAttributes());
    }

    public function test_conflicting_clinical_periods_block_consolidation_without_changing_policies(): void
    {
        [, $medical] = $this->legacyClinicalPolicies();
        $medical->update(['duration' => 2, 'unit' => 'years']);
        $before = RetentionPolicy::orderBy('id')->get()->map->getAttributes()->all();
        $auditCount = AuditLog::count();
        $migration = require database_path('migrations/2026_10_29_000001_consolidate_clinical_retention_policy.php');
        try {
            $migration->up();
            $this->fail('A conflicting period must require a clinic decision.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Clinical retention periods differ', $exception->getMessage());
        }
        $this->assertSame($before, RetentionPolicy::orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame($auditCount, AuditLog::count());
        $this->assertDatabaseMissing('retention_policies', ['category' => 'clinical_records']);
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
        $this->assertArrayNotHasKey('archive_enabled', $policy->fresh()->getAttributes());
        $this->assertArrayNotHasKey('disposal_enabled', $policy->fresh()->getAttributes());
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
        $this->assertArrayNotHasKey('archive_enabled', $policy->fresh()->getAttributes());
        $this->assertArrayNotHasKey('disposal_enabled', $policy->fresh()->getAttributes());
        $this->assertDatabaseHas('audit_logs', ['action' => 'retention_period_modified']);
        $this->get(route('admin.system_settings', ['tab' => 'retention']))->assertOk();
    }

    public function test_log_policy_migration_adds_missing_categories_without_resetting_existing_periods(): void
    {
        $startEventCleanup = require database_path('migrations/2026_10_23_000004_remove_unused_retention_start_event.php');
        $startEventCleanup->down();
        $cleanup = require database_path('migrations/2026_10_23_000001_remove_unused_retention_policy_flags.php');
        $cleanup->down();
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
        $cleanup->up();
        $startEventCleanup->up();
    }

    public function test_flag_cleanup_preserves_policy_data_and_supports_rollback(): void
    {
        $admin = $this->administrator('super_admin');
        $policy = RetentionPolicy::firstOrFail();
        $policy->update(['duration' => 7, 'unit' => 'months', 'version' => 4,
            'updated_by' => $admin->id, 'status' => 'active',
            'approved_by' => $admin->id, 'approved_at' => now(),
            'approval_reference' => 'Existing approval reference',
            'activated_by' => $admin->id, 'activated_at' => now()]);
        $before = $policy->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_10_23_000001_remove_unused_retention_policy_flags.php');

        $migration->down();
        $this->assertTrue(Schema::hasColumns('retention_policies', ['archive_enabled', 'disposal_enabled']));
        $this->assertDatabaseHas('retention_policies', ['id' => $policy->id,
            'archive_enabled' => false, 'disposal_enabled' => false]);
        $migration->up();

        $this->assertFalse(Schema::hasColumn('retention_policies', 'archive_enabled'));
        $this->assertFalse(Schema::hasColumn('retention_policies', 'disposal_enabled'));
        $this->assertSame($before, $policy->fresh()->getAttributes());
    }

    public function test_approval_split_preserves_existing_details_and_rolls_back(): void
    {
        $admin = $this->administrator('super_admin');
        $policy = RetentionPolicy::firstOrFail();
        $emptyPolicy = RetentionPolicy::whereKeyNot($policy->id)->firstOrFail();
        $this->assertDatabaseCount('retention_policy_approvals', 0);
        $policy->update(['status' => 'active', 'approved_by' => $admin->id,
            'approved_at' => now(), 'approval_reference' => 'Existing institutional approval',
            'activated_by' => $admin->id, 'activated_at' => now()]);
        $before = $policy->fresh()->attributesToArray();
        $migration = require database_path('migrations/2026_10_23_000002_separate_retention_policy_approvals.php');

        $migration->down();
        $this->assertFalse(Schema::hasTable('retention_policy_approvals'));
        $this->assertDatabaseHas('retention_policies', ['id' => $policy->id,
            'approved_by' => $admin->id, 'approval_reference' => 'Existing institutional approval']);
        $migration->up();

        $this->assertFalse(Schema::hasColumn('retention_policies', 'approved_by'));
        $this->assertFalse(Schema::hasColumn('retention_policies', 'activated_at'));
        $this->assertSame($before, $policy->fresh()->attributesToArray());
        $this->assertDatabaseCount('retention_policy_approvals', 1);
        $this->assertNull($emptyPolicy->fresh()->approval);
        $this->assertSame($policy->id, $policy->fresh()->approval->policy->id);

        DB::table('retention_policies')->where('id', $policy->id)->delete();
        $this->assertDatabaseCount('retention_policy_approvals', 0);
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
        }
        $this->assertSame($beforeAudit, $audit->fresh()->toArray());
        $this->assertSame($beforeAi, $aiLog->fresh()->getAttributes());
    }

    public function test_day_and_week_periods_can_be_saved(): void
    {
        $admin = $this->administrator();
        $policy = RetentionPolicy::where('category', 'users')->firstOrFail();
        $this->actingAs($admin);

        foreach (['days', 'weeks'] as $unit) {
            $this->put(route('admin.retention.update', $policy),
                $this->payload($policy, ['duration' => 2, 'unit' => $unit]))
                ->assertSessionHasNoErrors()->assertRedirect();
            $policy->refresh();
            $this->assertSame($unit, $policy->unit);
            $this->assertSame(2, $policy->duration);
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
            foreach (['update', 'approve', 'activate'] as $action) {
                $this->put(route('admin.retention.update', $policy), $this->payload($policy, [
                    'action' => $action, 'approval_reference' => 'Documented approval 2026',
                ]))->assertForbidden();
            }
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
        $policy->update(['counting_rule' => array_key_first($policy->countingRules())]);
        $this->actingAs($this->administrator());
        $this->put(route('admin.retention.update', $policy), $this->payload($policy, ['action' => 'activate']))
            ->assertUnprocessable();
        $this->put(route('admin.retention.update', $policy),
            $this->payload($policy, ['action' => 'approve', 'approval_reference' => 'Institutional decision 123']))
            ->assertRedirect();
        $policy->refresh();
        $this->assertSame('approved', $policy->status);
        $this->assertDatabaseHas('retention_policy_approvals', ['retention_policy_id' => $policy->id,
            'approved_by' => auth()->id(), 'approval_reference' => 'Institutional decision 123']);
        $approvalAudit = AuditLog::where('action', 'retention_policy_approved')->firstOrFail();
        $approvalDescription = json_decode($approvalAudit->full_description, true);
        $this->assertNull($approvalDescription['previous']['approved_by']);
        $this->assertSame(auth()->id(), $approvalDescription['updated']['approved_by']);
        $this->put(route('admin.retention.update', $policy), $this->payload($policy, ['action' => 'activate']))
            ->assertRedirect();
        $policy->refresh();
        $this->assertSame('active', $policy->status);
        $this->assertSame(auth()->id(), $policy->approval->activated_by);
        $this->assertArrayNotHasKey('archive_enabled', $policy->getAttributes());
        $this->assertArrayNotHasKey('disposal_enabled', $policy->getAttributes());
        $this->put(route('admin.retention.update', $policy), $this->payload($policy, ['action' => 'deactivate']))
            ->assertRedirect();
        $policy->refresh();
        $this->assertSame('approved', $policy->status);
        $this->put(route('admin.retention.update', $policy), $this->payload($policy))->assertRedirect();
        $policy->refresh();
        $this->assertSame('provisional', $policy->status);
        $this->assertNull($policy->approved_at);
        $this->assertNull($policy->activated_at);
        $this->assertDatabaseMissing('retention_policy_approvals', ['retention_policy_id' => $policy->id]);
        foreach (['approved', 'activated', 'deactivated'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => 'retention_policy_'.$action]);
        }
    }

    public function test_validation_and_stale_version_preserve_policy(): void
    {
        $this->actingAs($this->administrator());
        $policy = RetentionPolicy::firstOrFail();
        foreach ([['duration' => 0], ['unit' => 'invalid'], ['confirmed' => '0']] as $invalid) {
            $this->put(route('admin.retention.update', $policy), $this->payload($policy, $invalid))->assertSessionHasErrors();
        }
        $this->put(route('admin.retention.update', $policy), $this->payload($policy))->assertRedirect();
        $this->put(route('admin.retention.update', $policy), $this->payload($policy))->assertStatus(409);
        $this->assertSame(2, $policy->fresh()->version);
    }

    public function test_missing_and_incorrect_passwords_cannot_change_a_policy_or_flash_secrets(): void
    {
        $this->actingAs($this->administrator());
        $policy = RetentionPolicy::firstOrFail();
        $before = $policy->fresh()->attributesToArray();

        foreach ([null, '', 'IncorrectSecret123!'] as $password) {
            $this->from(route('admin.system_settings', ['tab' => 'retention']))
                ->put(route('admin.retention.update', $policy), $this->payload($policy, [
                    'password' => $password, 'retention_policy_id' => $policy->id,
                ]))
                ->assertRedirect(route('admin.system_settings', ['tab' => 'retention']))
                ->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password');
            $this->assertSame($before, $policy->fresh()->attributesToArray());
        }

        $this->assertDatabaseMissing('audit_logs', ['action' => 'retention_policy_updated']);
        $failure = AuditLog::where('action', 'retention_password_verification_failed')->firstOrFail();
        $this->assertStringNotContainsString('IncorrectSecret123!', $failure->full_description);
        $this->get(route('admin.system_settings', ['tab' => 'retention']))->assertOk()
            ->assertDontSee('IncorrectSecret123!');
    }

    public function test_password_is_required_again_after_every_successful_save(): void
    {
        $admin = $this->administrator();
        $policy = RetentionPolicy::firstOrFail();
        $this->actingAs($admin)->put(route('admin.retention.update', $policy), $this->payload($policy))
            ->assertSessionHasNoErrors()->assertRedirect();
        $policy->refresh();

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->putJson(route('admin.retention.update', $policy), $this->payload($policy, ['password' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame(2, $policy->fresh()->version);

        $this->put(route('admin.retention.update', $policy), $this->payload($policy, ['duration' => 6]))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(6, $policy->fresh()->duration);
        $this->assertSame(3, $policy->fresh()->version);
        $audit = AuditLog::where('action', 'retention_policy_updated')->latest('id')->firstOrFail();
        $this->assertStringNotContainsString('Password123!', $audit->full_description);
        $this->assertArrayNotHasKey('password', json_decode($audit->full_description, true)['updated']);
    }

    public function test_only_the_signed_in_administrators_password_is_accepted(): void
    {
        $admin = $this->administrator();
        $other = $this->administrator('super_admin');
        $other->update(['password' => bcrypt('OtherAdminPassword123!')]);
        $policy = RetentionPolicy::firstOrFail();
        $this->actingAs($admin)->putJson(route('admin.retention.update', $policy),
            $this->payload($policy, ['password' => 'OtherAdminPassword123!']))
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame(1, $policy->fresh()->version);
    }

    public function test_password_attempt_limit_is_shared_across_categories_and_expires(): void
    {
        $this->actingAs($this->administrator());
        $policy = RetentionPolicy::firstOrFail();
        $other = RetentionPolicy::whereKeyNot($policy->id)->firstOrFail();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->putJson(route('admin.retention.update', $policy),
                $this->payload($policy, ['password' => 'WrongPassword123!']))
                ->assertUnprocessable()->assertJsonValidationErrors('password');
        }
        $this->putJson(route('admin.retention.update', $other), $this->payload($other))
            ->assertUnprocessable()->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', fn ($message) => str_contains($message, 'Too many incorrect password attempts'));
        $this->assertSame(1, $other->fresh()->version);

        $this->travel(61)->seconds();
        $this->put(route('admin.retention.update', $other), $this->payload($other))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, $other->fresh()->version);
        $this->travelBack();
    }

    public function test_passwordless_accounts_cannot_bypass_retention_confirmation(): void
    {
        $admin = $this->administrator();
        $admin->update(['password' => null]);
        $policy = RetentionPolicy::firstOrFail();
        $this->actingAs($admin)->putJson(route('admin.retention.update', $policy), $this->payload($policy))
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame(1, $policy->fresh()->version);
    }

    public function test_all_policy_transitions_require_password_verification(): void
    {
        $this->actingAs($this->administrator('super_admin'));
        $policy = RetentionPolicy::firstOrFail();
        $policy->update(['counting_rule' => array_key_first($policy->countingRules())]);
        foreach (['approve', 'activate', 'deactivate'] as $action) {
            $payload = $this->payload($policy, [
                'action' => $action, 'approval_reference' => 'Institutional decision 123',
            ]);
            $this->putJson(route('admin.retention.update', $policy), array_replace($payload, ['password' => null]))
                ->assertUnprocessable()->assertJsonValidationErrors('password');
            $this->assertSame($policy->version, $policy->fresh()->version);
            $this->put(route('admin.retention.update', $policy), $payload)
                ->assertSessionHasNoErrors()->assertRedirect();
            $policy->refresh();
        }
    }

    public function test_audit_failure_rolls_back_the_period_approval_and_version_together(): void
    {
        $admin = $this->administrator('super_admin');
        $policy = RetentionPolicy::firstOrFail();
        $policy->update(['status' => 'active', 'approved_by' => $admin->id,
            'approved_at' => now(), 'approval_reference' => 'Approved retention decision',
            'activated_by' => $admin->id, 'activated_at' => now()]);
        $before = $policy->fresh()->attributesToArray();
        AuditLog::creating(function (AuditLog $log) {
            if ($log->action === 'retention_period_modified') {
                throw new \RuntimeException('Simulated audit storage failure');
            }
        });

        try {
            $this->withoutExceptionHandling()->actingAs($admin)
                ->put(route('admin.retention.update', $policy), $this->payload($policy));
            $this->fail('The audit failure should abort the save.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated audit storage failure', $exception->getMessage());
        } finally {
            AuditLog::flushEventListeners();
        }

        $this->assertSame($before, $policy->fresh()->attributesToArray());
        $this->assertDatabaseMissing('audit_logs', ['action' => 'retention_policy_updated']);
    }
}
