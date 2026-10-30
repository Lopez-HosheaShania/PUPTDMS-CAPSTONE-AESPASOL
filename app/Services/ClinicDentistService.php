<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\ClinicDentistAssignment;
use App\Models\DentistTransition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicDentistService
{
    public function assignment(): ClinicDentistAssignment
    {
        return ClinicDentistAssignment::with('dentist.role')->findOrFail(ClinicDentistAssignment::SLOT);
    }

    public function initialCandidates(): Builder
    {
        return User::where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('slug', AssignedDentistService::DENTIST_ROLES))
            ->whereDoesntHave('employmentStatus', fn ($query) => $query->whereNotNull('access_ends_at')->where('access_ends_at', '<=', now()))
            ->whereDoesntHave('dentistTransitions', fn ($query) => $query->where('status', 'active'));
    }

    public function initialize(int $dentistId, User $actor): void
    {
        abort_unless($actor->status === 'active' && $actor->hasAnyRole(['admin', 'super_admin']) && ! session('impersonated_role'), 403);
        abort_unless($actor->hasPermission('update_dentist_transitions') || $actor->hasPermission('manage_dentist_accounts'), 403);

        DB::transaction(function () use ($dentistId, $actor) {
            $assignment = $this->lockAssignment();
            if ($assignment->dentist_id) {
                throw ValidationException::withMessages(['dentist_id' => 'The current dentist is already assigned. Use a reviewed continuity plan to change dentists.']);
            }
            $dentist = $this->initialCandidates()->lockForUpdate()->find($dentistId);
            if (! $dentist) {
                throw ValidationException::withMessages(['dentist_id' => 'Choose an available active dentist.']);
            }
            $coverage = DentistTransition::where('default_successor_dentist_id', $dentistId)
                ->where('status', 'active')->where('coverage_type', 'temporary')->latest('id')->first();
            $assignment->update(['dentist_id' => $dentist->id, 'dentist_transition_id' => $coverage?->id, 'assigned_by' => $actor->id]);
            AuditLogger::log('clinic_dentist_assigned', 'Dentist Continuity', "Clinic dentist set to account #{$dentist->id} by administrator #{$actor->id}.");
        });
    }

    public function assertSource(int $dentistId): void
    {
        $assignment = $this->lockAssignment();
        if (! $assignment->dentist_id) {
            throw ValidationException::withMessages(['dentist_id' => 'Set the current clinic dentist on the Dentist Continuity page before creating or approving a handover.']);
        }
        if ($assignment->dentist_id !== $dentistId) {
            throw ValidationException::withMessages(['dentist_id' => 'This plan is not for the current clinic dentist. Review the current assignment before proceeding.']);
        }
    }

    public function activate(DentistTransition $transition, User $actor): void
    {
        $this->assertSource((int) $transition->dentist_id);
        $this->lockAssignment()->update([
            'dentist_id' => $transition->default_successor_dentist_id,
            'dentist_transition_id' => $transition->id,
            'assigned_by' => $actor->id,
        ]);
    }

    public function returnFromCoverage(DentistTransition $transition, User $actor): void
    {
        $assignment = $this->lockAssignment();
        if ($assignment->dentist_id !== (int) $transition->default_successor_dentist_id || $assignment->dentist_transition_id !== $transition->id) {
            throw ValidationException::withMessages(['transition' => 'The current clinic dentist changed during coverage. Review onward handover before returning care.']);
        }
        $assignment->update(['dentist_id' => $transition->dentist_id, 'assigned_by' => $actor->id]);
    }

    /** Keep new patients in the temporary handover's care relationship. */
    public function recordCoverageCare(int $patientId, ?int $dentistId): void
    {
        $assignment = $this->lockAssignment();
        if (! $dentistId || $assignment->dentist_id !== $dentistId
            || $assignment->transition?->status !== 'active' || $assignment->transition->coverage_type !== 'temporary') {
            return;
        }
        $existing = DB::table('patient_care_assignments')->where('patient_id', $patientId)->lockForUpdate()->first();
        if ($existing && (int) $existing->dentist_id !== $dentistId) {
            throw ValidationException::withMessages(['dentist_id' => 'Review the existing patient care assignment before recording work for the covering dentist.']);
        }
        DB::table('patient_care_assignments')->updateOrInsert(['patient_id' => $patientId], [
            'dentist_id' => $dentistId, 'dentist_transition_id' => $assignment->dentist_transition_id,
            'created_at' => $existing?->created_at ?? now(), 'updated_at' => now(),
        ]);
    }

    public function lockAssignment(): ClinicDentistAssignment
    {
        return ClinicDentistAssignment::lockForUpdate()->findOrFail(ClinicDentistAssignment::SLOT);
    }
}
