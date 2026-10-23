<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\AuditLog;
use App\Models\DentistTransition;
use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\DentistTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentistContinuityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $source;
    private User $replacement;
    private DentistTransitionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-19 08:00:00'));
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $dentistRole = Role::create(['name' => 'Dentist', 'slug' => 'dentist']);
        foreach (['view_dentist_transitions', 'create_dentist_transitions', 'update_dentist_transitions', 'assign_dentist_successors', 'finalize_dentist_transitions', 'cancel_dentist_transitions', 'extend_dentist_access', 'view_patient_list', 'view_dental_records'] as $slug) {
            $permission = Permission::create(['name' => $slug, 'slug' => $slug, 'module' => 'Dentist Continuity']);
            $adminRole->permissions()->attach($permission);
        }
        $this->admin = User::create(['name' => 'Test Clinician', 'email' => uniqid().'@example.test', 'password' => bcrypt('secret'), 'role_id' => $adminRole->id, 'status' => 'active']);
        $this->source = User::create(['name' => 'Test Clinician', 'email' => uniqid().'@example.test', 'password' => bcrypt('secret'), 'role_id' => $dentistRole->id, 'status' => 'active']);
        $this->replacement = User::create(['name' => 'Test Clinician', 'email' => uniqid().'@example.test', 'password' => bcrypt('secret'), 'role_id' => $dentistRole->id, 'status' => 'active']);
        $this->service = app(DentistTransitionService::class);
        $this->asAdmin();
    }

    private function asAdmin(): void
    {
        $this->actingAs($this->admin)->withSession(['role' => 'admin', 'admin_logged_in' => true, 'admin_id' => $this->admin->id]);
    }

    private function patient(): Patient
    {
        return Patient::create(['name' => 'Continuity Patient', 'email' => uniqid().'@example.test', 'password' => bcrypt('secret'), 'birthdate' => '2000-01-01', 'gender' => 'Male']);
    }

    private function appointment(?Patient $patient = null, array $attributes = []): Appointment
    {
        return Appointment::create(array_merge([
            'patient_id' => ($patient ?? $this->patient())->id,
            'dentist_id' => $this->source->id, 'service_type' => 'Cleaning',
            'appointment_date' => today()->addDays(2)->toDateString(),
            'appointment_time' => '09:00:00', 'status' => 'upcoming',
        ], $attributes));
    }

    private function payload(array $attributes = []): array
    {
        return array_merge([
            'dentist_id' => $this->source->id, 'transition_type' => 'resignation',
            'coverage_type' => 'permanent', 'effective_date' => today()->toDateString(),
            'last_working_date' => today()->toDateString(),
            'default_successor_dentist_id' => $this->replacement->id,
        ], $attributes);
    }

    public function test_draft_does_not_transfer_access_appointments_or_disable_the_source(): void
    {
        $visit = $this->appointment();
        $this->post(route('admin.dentist-transitions.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $plan = DentistTransition::firstOrFail();
        $this->assertSame('draft', $plan->status);
        $this->assertSame($this->source->id, $visit->fresh()->dentist_id);
        $this->assertDatabaseCount('patient_care_assignments', 0);
        $this->assertSame('active', $this->source->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'transition_created']);
    }

    public function test_review_page_renders_scope_and_confirmation_without_a_checklist(): void
    {
        $this->appointment();
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->get(route('admin.dentist-transitions.show', $plan))->assertOk()
            ->assertSee('Continuity scope and access review')->assertDontSee('Handover checklist')->assertDontSee('Save Review Checklist')->assertDontSee('id="continuity-confirmed"', false)
            ->assertSee('Ready To Transfer')->assertSee('Critical Unresolved')->assertSee('Approve and Schedule Reassignment');
        $this->get(route('admin.dentist-transitions.create'))->assertOk()->assertSee('Effective date');
        $this->get(route('admin.dentist-transitions.index'))->assertOk()->assertSee('Dentist Continuity Policy');
    }

    public function test_draft_can_be_saved_without_an_available_replacement_but_cannot_be_approved(): void
    {
        $this->appointment();
        $this->post(route('admin.dentist-transitions.store'), $this->payload(['default_successor_dentist_id' => null]))->assertSessionHasNoErrors();
        $plan = DentistTransition::firstOrFail();
        $this->post(route('admin.dentist-transitions.finalize', $plan), ['confirmed' => 1])->assertSessionHasErrors('default_successor_dentist_id');
        $this->assertSame('draft', $plan->fresh()->status);
    }

    public function test_even_a_dentist_with_transition_permissions_cannot_view_or_change_plans(): void
    {
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->source->role->permissions()->sync(Permission::pluck('id'));
        $this->actingAs($this->source)->withSession(['role' => 'dentist', 'dentist_id' => $this->source->id]);
        $this->get(route('dentist.dentist.transitions.show', $plan))->assertForbidden();
        $this->post(route('dentist.dentist.transitions.store'), $this->payload())->assertForbidden();
        $this->put(route('dentist.dentist.transitions.assignments', $plan), ['default_successor_dentist_id' => $this->source->id])->assertForbidden();
        $this->post(route('dentist.dentist.transitions.cancel', $plan), ['cancellation_reason' => 'Unauthorized'])->assertForbidden();
    }

    public function test_invalid_dates_are_rejected_and_approval_does_not_require_a_checkbox(): void
    {
        $this->post(route('admin.dentist-transitions.store'), $this->payload(['coverage_type' => 'temporary', 'end_date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('end_date');
        $this->post(route('admin.dentist-transitions.store'), $this->payload(['effective_date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('effective_date');
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->post(route('admin.dentist-transitions.finalize', $plan))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('completed', $plan->fresh()->status);
    }

    public function test_approval_waits_until_effective_date_then_transfers_once(): void
    {
        $visit = $this->appointment();
        $plan = $this->service->createTransition($this->payload(['effective_date' => today()->addDay()->toDateString()]), $this->admin);
        $plan = $this->service->finalizeTransition($plan, $this->admin);
        $this->assertSame('scheduled', $plan->status);
        $this->assertSame($this->source->id, $visit->fresh()->dentist_id);
        $this->travel(1)->days();
        $this->assertSame(1, $this->service->processDueTransitions());
        $this->assertSame($this->replacement->id, $visit->fresh()->dentist_id);
        $this->assertSame(0, $this->service->processDueTransitions());
        $this->assertSame('inactive', $this->source->fresh()->status);
        $this->assertSame('completed', $plan->fresh()->status);
    }

    public function test_replacement_sees_reassigned_history_but_no_unrelated_patients_or_records(): void
    {
        $patient = $this->patient();
        $upcoming = $this->appointment($patient);
        $history = $this->appointment($patient, ['status' => 'completed', 'appointment_date' => today()->subYear()->toDateString()]);
        $notes = AppointmentProcedure::create(['appointment_id' => $history->id, 'diagnosis' => 'Original clinical note']);
        $unrelated = $this->appointment(null, ['dentist_id' => null]);
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->assertSame($this->source->id, $history->fresh()->dentist_id);
        $this->assertSame('Original clinical note', $notes->fresh()->diagnosis);
        $this->actingAs($this->replacement);
        $this->assertNotNull(Patient::find($patient->id));
        $this->assertNotNull(Appointment::find($history->id));
        $this->assertNotNull(Appointment::find($upcoming->id));
        $this->assertNull(Patient::find($unrelated->patient_id));
        $this->assertNull(Appointment::find($unrelated->id));
        $this->actingAs($this->source->fresh());
        $this->assertNull(Patient::find($patient->id));
        $this->assertNull(Appointment::find($history->id));
    }

    public function test_temporary_coverage_returns_care_and_open_work_without_rewriting_completed_visits(): void
    {
        $patient = $this->patient();
        $visit = $this->appointment($patient, ['appointment_date' => today()->toDateString()]);
        $afterCoverage = $this->appointment($patient, ['appointment_date' => today()->addDays(5)->toDateString()]);
        $document = DocumentRequest::create(['patient_id' => $patient->id, 'assigned_dentist_id' => $this->source->id, 'reference_number' => 'CONT-001', 'purpose' => 'Care continuity', 'request_date' => today()->toDateString(), 'request_time' => '08:00:00', 'document_type' => 'Medical Certificate', 'status' => 'pending']);
        $plan = $this->service->createTransition($this->payload(['coverage_type' => 'temporary', 'transition_type' => 'temporary_leave', 'end_date' => today()->addDay()->toDateString()]), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->assertSame('active', $plan->fresh()->status);
        $this->assertSame('active', $this->source->fresh()->status);
        $this->assertSame($this->source->id, $afterCoverage->fresh()->dentist_id);
        $this->assertSame($this->replacement->id, $document->fresh()->assigned_dentist_id);
        $visit->update(['status' => 'completed']);
        $this->travel(2)->days();
        $this->assertSame(1, $this->service->processDueTransitions());
        $this->assertSame($this->replacement->id, $visit->fresh()->dentist_id);
        $this->assertSame($this->source->id, $document->fresh()->assigned_dentist_id);
        $this->assertDatabaseHas('patient_care_assignments', ['patient_id' => $patient->id, 'dentist_id' => $this->source->id]);
        $this->actingAs($this->replacement);
        $this->assertNull(Patient::find($patient->id));
        $this->actingAs($this->source);
        $this->assertNotNull(Patient::find($patient->id));
    }

    public function test_temporary_return_is_held_when_the_original_dentist_is_inactive(): void
    {
        $visit = $this->appointment();
        $plan = $this->service->createTransition($this->payload(['coverage_type' => 'temporary', 'end_date' => today()->addDays(3)->toDateString()]), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->source->update(['status' => 'inactive']);
        $this->travel(4)->days();
        $this->assertSame(0, $this->service->processDueTransitions());
        $this->assertSame('active', $plan->fresh()->status);
        $this->assertDatabaseHas('patient_care_assignments', ['patient_id' => $visit->patient_id, 'dentist_id' => $this->replacement->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'transition_blocked']);
    }

    public function test_overlapping_appointment_times_block_the_entire_handover(): void
    {
        $visit = $this->appointment();
        $this->appointment(null, ['dentist_id' => $this->replacement->id, 'appointment_time' => '09:30:00']);
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->post(route('admin.dentist-transitions.finalize', $plan), ['confirmed' => 1])->assertSessionHasErrors('appointments');
        $this->assertSame($this->source->id, $visit->fresh()->dentist_id);
        $this->assertDatabaseCount('patient_care_assignments', 0);
    }

    public function test_unavailable_replacement_is_rechecked_when_scheduled_plan_takes_effect(): void
    {
        $visit = $this->appointment();
        $plan = $this->service->createTransition($this->payload(['effective_date' => today()->addDay()->toDateString()]), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->replacement->update(['status' => 'inactive']);
        $this->travel(1)->days();
        $this->assertSame(0, $this->service->processDueTransitions());
        $this->assertSame($this->source->id, $visit->fresh()->dentist_id);
        $this->assertDatabaseCount('patient_care_assignments', 0);
        $this->assertSame('scheduled', $plan->fresh()->status);
    }

    public function test_scheduled_plan_can_be_cancelled_without_reactivating_a_deactivated_account(): void
    {
        $plan = $this->service->createTransition($this->payload(['effective_date' => today()->addDay()->toDateString()]), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->source->update(['status' => 'inactive']);
        $this->post(route('admin.dentist-transitions.cancel', $plan), ['cancellation_reason' => 'Replacement coverage changed'])->assertSessionHasNoErrors();
        $this->assertSame('inactive', $this->source->fresh()->status);
        $this->assertSame('cancelled', $plan->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'transition_cancelled']);
    }

    public function test_effective_plan_cannot_be_cancelled_modified_or_used_to_restore_outgoing_access(): void
    {
        $this->appointment();
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->post(route('admin.dentist-transitions.cancel', $plan), ['cancellation_reason' => 'Undo'])->assertSessionHasErrors('transition');
        $this->put(route('admin.dentist-transitions.assignments', $plan), ['default_successor_dentist_id' => $this->replacement->id])->assertSessionHasErrors('transition');
        $this->post(route('admin.dentist-transitions.extend-access', $plan), ['access_ends_at' => now()->addYear()->toDateTimeString()])->assertSessionHasErrors('access_ends_at');
        $this->assertSame('inactive', $this->source->fresh()->status);
    }

    public function test_successive_transitions_preserve_the_first_original_dentist_and_move_care_forward(): void
    {
        $visit = $this->appointment();
        $third = User::create(['name' => 'Test Clinician', 'email' => uniqid().'@example.test', 'password' => bcrypt('secret'), 'role_id' => $this->replacement->role_id, 'status' => 'active']);
        $first = $this->service->createTransition($this->payload(), $this->admin);
        $this->service->finalizeTransition($first, $this->admin);
        $next = $this->service->createTransition($this->payload(['dentist_id' => $this->replacement->id, 'default_successor_dentist_id' => $third->id]), $this->admin);
        $this->service->finalizeTransition($next, $this->admin);
        $this->assertSame($third->id, $visit->fresh()->dentist_id);
        $this->assertSame($this->source->id, $visit->fresh()->original_dentist_id);
        $this->assertDatabaseHas('patient_care_assignments', ['patient_id' => $visit->patient_id, 'dentist_id' => $third->id]);
    }

    public function test_lifecycle_audits_include_actor_dates_counts_and_no_patient_names(): void
    {
        $visit = $this->appointment();
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        foreach (['transition_created', 'transition_finalized', 'transition_effective', 'transition_access_updated', 'transition_item_transferred', 'transition_deactivated'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action]);
        }
        $audit = AuditLog::where('action', 'transition_effective')->firstOrFail();
        $data = json_decode($audit->full_description, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($this->admin->id, $data['administrator_id']);
        $this->assertSame(1, $data['counts']['active_patients']);
        $this->assertStringNotContainsString($visit->patient->name, $audit->full_description);
    }

    public function test_deactivated_source_can_still_have_care_reassigned_without_being_reactivated(): void
    {
        $visit = $this->appointment();
        $this->source->update(['status' => 'inactive']);
        $plan = $this->service->createTransition($this->payload(['transition_type' => 'suspension']), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->assertSame($this->replacement->id, $visit->fresh()->dentist_id);
        $this->assertSame('inactive', $this->source->fresh()->status);
    }

    public function test_new_appointments_created_after_approval_are_included_at_activation(): void
    {
        $plan = $this->service->createTransition($this->payload(['effective_date' => today()->addDay()->toDateString()]), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $visit = $this->appointment();
        $this->travel(1)->days();
        $this->assertSame(1, $this->service->processDueTransitions());
        $this->assertSame($this->replacement->id, $visit->fresh()->dentist_id);
        $this->assertDatabaseHas('patient_care_assignments', ['patient_id' => $visit->patient_id, 'dentist_id' => $this->replacement->id]);
    }

    public function test_open_ended_temporary_coverage_requires_a_confirmed_manual_return(): void
    {
        $visit = $this->appointment();
        $plan = $this->service->createTransition($this->payload(['coverage_type' => 'temporary']), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->assertNull($plan->fresh()->end_date);
        $this->post(route('admin.dentist-transitions.end-temporary', $plan))->assertSessionHasErrors('confirmed');
        $this->post(route('admin.dentist-transitions.end-temporary', $plan), ['confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertSame('completed', $plan->fresh()->status);
        $this->assertSame($this->source->id, $visit->fresh()->dentist_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'transition_temporary_ended']);
    }

    public function test_temporary_outgoing_account_cannot_use_clinical_routes_during_coverage(): void
    {
        $visit = $this->appointment();
        $plan = $this->service->createTransition($this->payload(['coverage_type' => 'temporary']), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->actingAs($this->source)->withSession(['role' => 'dentist']);
        $this->get(route('dentist.dentist.patient.profile', $visit->patient_id))->assertNotFound();
        $this->get(route('dentist.dentist.dashboard'))->assertForbidden();
    }

    public function test_replacement_cannot_rewrite_the_original_dentists_clinical_notes(): void
    {
        $visit = $this->appointment(null, ['status' => 'completed']);
        $permission = Permission::create(['name' => 'Record procedure', 'slug' => 'create_procedure_records', 'module' => 'Clinical']);
        $this->replacement->role->permissions()->attach($permission);
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->service->finalizeTransition($plan, $this->admin);
        $this->actingAs($this->replacement)->withSession(['role' => 'dentist']);
        $this->postJson(route('dentist.odontogram.saved.update', $visit))->assertForbidden();
        $this->assertSame($this->source->id, $visit->fresh()->dentist_id);
    }

    public function test_unpermitted_admins_and_impersonating_admins_cannot_approve_handover(): void
    {
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->admin->role->permissions()->detach();
        $this->post(route('admin.dentist-transitions.finalize', $plan), ['confirmed' => 1])->assertForbidden();
        $this->admin->role->permissions()->sync(Permission::pluck('id'));
        $this->withSession(['impersonated_role' => 'dentist']);
        $this->post(route('dentist.dentist.transitions.finalize', $plan), ['confirmed' => 1])->assertForbidden();
    }

    public function test_forged_default_successor_and_foreign_transition_items_are_rejected(): void
    {
        $this->appointment();
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $this->put(route('admin.dentist-transitions.assignments', $plan), ['default_successor_dentist_id' => $this->source->id])->assertSessionHasErrors('default_successor_dentist_id');
        $otherPlan = $this->service->createTransition($this->payload(['dentist_id' => $this->replacement->id, 'default_successor_dentist_id' => $this->source->id]), $this->admin);
        $foreign = $otherPlan->items()->create(['item_type' => 'patient', 'record_id' => 999, 'original_dentist_id' => $this->replacement->id]);
        $this->put(route('admin.dentist-transitions.assignments', $plan), ['items' => [$foreign->id => ['successor_dentist_id' => $this->replacement->id]]])->assertSessionHasErrors('items.'.$foreign->id);
    }

    public function test_notification_links_take_replacements_to_their_scoped_patient_directory(): void
    {
        $plan = $this->service->createTransition($this->payload(), $this->admin);
        $notification = new \App\Notifications\DentistTransitionNotification($plan, 'effective', 'dentist');
        $this->assertSame(route('dentist.dentist.patients'), $notification->toArray($this->replacement)['url']);
        \Illuminate\Support\Facades\Notification::fake();
        $this->service->notifyTransitionParticipants($plan, 'effective');
        \Illuminate\Support\Facades\Notification::assertSentTo([$this->admin, $this->replacement], \App\Notifications\DentistTransitionNotification::class);
    }
}
