<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Appointment;
use App\Models\DentistTransition;
use App\Models\DentistTransitionItem;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Notifications\DentistTransitionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DentistTransitionService
{
    public function __construct(
        private readonly ConcurrentSessionService $concurrentSessionService
    ) {}

    private const ACTIVE_APPOINTMENT_STATUSES = ['pending', 'confirmed', 'upcoming', 'rescheduled'];

    public function createTransition(array $payload, User $actor): DentistTransition
    {
        $this->authorizeAdministrator($actor);
        return DB::transaction(function () use ($payload, $actor) {
            User::whereKey($payload['dentist_id'])->lockForUpdate()->firstOrFail();
            if (DentistTransition::where('dentist_id', $payload['dentist_id'])
                ->whereIn('status', ['draft', 'pending_review', 'handover_in_progress', 'scheduled', 'active'])->exists()) {
                $this->invalid('dentist_id', 'This dentist already has an open continuity process.');
            }
            $effective = Carbon::parse($payload['effective_date'] ?? Carbon::parse($payload['last_working_date'])->addDay())->startOfDay();
            $transition = DentistTransition::create([
                'dentist_id' => $payload['dentist_id'],
                'transition_type' => $payload['transition_type'],
                'coverage_type' => $payload['coverage_type'] ?? 'permanent',
                'effective_date' => $effective,
                'end_date' => $payload['end_date'] ?? null,
                'default_successor_dentist_id' => $payload['default_successor_dentist_id'] ?? null,
                'last_working_date' => $payload['last_working_date'],
                'access_ends_at' => $effective,
                'status' => 'draft',
                'handover_notes' => $payload['handover_notes'] ?? null,
                'initiated_by' => $actor->id,
            ]);
            $transition = $this->generateTransitionItems($transition);
            $this->auditContinuity('transition_created', $transition, $actor);
            DB::afterCommit(fn () => $this->notifyTransitionParticipants($transition, 'created'));
            return $transition;
        });
    }

    public function updateTransition(DentistTransition $transition, array $payload, User $actor): DentistTransition
    {
        $this->authorizeAdministrator($actor);
        return DB::transaction(function () use ($transition, $payload, $actor) {
            $transition = $this->editableTransition($transition);
            $effective = Carbon::parse($payload['effective_date'] ?? Carbon::parse($payload['last_working_date'])->addDay())->startOfDay();
            $transition->fill(collect($payload)->only(['transition_type', 'coverage_type', 'end_date', 'default_successor_dentist_id', 'last_working_date', 'handover_notes'])->all());
            $transition->effective_date = $effective;
            $transition->access_ends_at = $effective;
            $transition->save();
            $transition = $this->generateTransitionItems($transition);
            $this->auditContinuity('transition_updated', $transition, $actor);
            return $transition;
        });
    }

    public function generateImpactSummary(DentistTransition $transition): array
    {
        $items = $transition->items;

        return [
            'active_patients' => $items->where('item_type', 'patient')->count(),
            'pending_documents' => $items->where('item_type', 'document_request')->count(),
            'future_appointments' => $items->where('item_type', 'appointment')->count(),
            'ready_to_transfer' => $items->where('transfer_status', 'ready')->count(),
            'excluded_items' => $items->where('transfer_status', 'excluded')->count(),
            'transferred_items' => $items->where('transfer_status', 'transferred')->count(),
            'critical_unresolved_items' => $items->filter(function (DentistTransitionItem $item) {
                return $item->is_critical && ! in_array($item->transfer_status, ['transferred', 'excluded', 'manually_resolved'], true);
            })->count(),
        ];
    }

    public function generateTransitionItems(DentistTransition $transition, bool $atActivation = false): DentistTransition
    {
        return DB::transaction(function () use ($transition, $atActivation) {
            if (! $atActivation && ! in_array($transition->status, ['draft', 'pending_review', 'handover_in_progress'], true)) {
                return $transition->fresh(['items.patient', 'items.successorDentist', 'items.documentRequest']);
            }
            $appointments = $this->eligibleAppointmentsQuery($transition)
                ->with(['patient', 'dentist'])
                ->get();

            $documentRequests = DocumentRequest::withStateColumns()
                ->where('document_request_states.status', 'pending')
                ->where(
                    'document_requests.assigned_dentist_id',
                    $transition->dentist_id
                )
                ->select('document_requests.*')
                ->with('patient')
                ->get();

            $patientIds = DB::table('appointments')->where('dentist_id', $transition->dentist_id)->pluck('patient_id')
                ->merge($documentRequests->pluck('patient_id'))
                ->merge(DB::table('patient_care_assignments')->where('dentist_id', $transition->dentist_id)->pluck('patient_id'))
                ->unique()->filter();
            $patientIds = $patientIds->diff(DB::table('patient_care_assignments')
                ->where('dentist_id', '!=', $transition->dentist_id)->pluck('patient_id'));
            foreach ($patientIds as $patientId) {
                $transition->items()->firstOrCreate(['item_type' => 'patient', 'record_id' => $patientId], [
                    'patient_id' => $patientId, 'original_dentist_id' => $transition->dentist_id,
                    'successor_dentist_id' => $transition->default_successor_dentist_id,
                    'transfer_status' => $transition->default_successor_dentist_id ? 'ready' : 'pending',
                    'is_critical' => true,
                ]);
            }

            $existingKeys = $transition->items()
                ->get()
                ->keyBy(fn(DentistTransitionItem $item) => $item->item_type . ':' . $item->record_id);

            foreach ($appointments as $appointment) {
                $key = 'appointment:' . $appointment->id;
                $item = $existingKeys->get($key) ?? new DentistTransitionItem([
                    'dentist_transition_id' => $transition->id,
                    'item_type' => 'appointment',
                    'record_id' => $appointment->id,
                ]);



                if (in_array($item->transfer_status, ['transferred', 'excluded', 'manually_resolved'], true)) {
                    continue;
                }
                $item->fill([
                    'patient_id' => $appointment->patient_id,
                    'original_dentist_id' => $appointment->original_dentist_id ?: $transition->dentist_id,
                    'successor_dentist_id' => $item->successor_dentist_id ?: $transition->default_successor_dentist_id,
                    'transfer_status' => $item->successor_dentist_id || $transition->default_successor_dentist_id ? 'ready' : 'pending',
                    'is_critical' => true,
                    'remarks' => $item->remarks,
                ]);
                $item->save();
            }


            foreach ($documentRequests as $documentRequest) {
                $originalDentistId = $transition->dentist_id;

                $key = 'document_request:' . $documentRequest->id;
                $item = $existingKeys->get($key) ?? new DentistTransitionItem([
                    'dentist_transition_id' => $transition->id,
                    'item_type' => 'document_request',
                    'record_id' => $documentRequest->id,
                ]);

                $isResolved = in_array(
                    $item->transfer_status,
                    ['transferred', 'excluded', 'manually_resolved'],
                    true
                );

                $successorDentistId = $isResolved
                    ? $item->successor_dentist_id
                    : ($item->successor_dentist_id ?: $transition->default_successor_dentist_id);

                $transferStatus = $isResolved
                    ? $item->transfer_status
                    : ($successorDentistId ? 'ready' : 'pending');

                $item->fill([
                    'patient_id' => $documentRequest->patient_id,
                    'original_dentist_id' => $originalDentistId,
                    'successor_dentist_id' => $successorDentistId,
                    'transfer_status' => $transferStatus,
                    'is_critical' => true,
                    'remarks' => $item->remarks,
                ]);

                $item->save();
            }

            $existingKeys->each(function (DentistTransitionItem $item) use ($appointments, $documentRequests) {
                if ($item->item_type === 'appointment') {
                    $stillExists = $appointments->contains('id', $item->record_id);

                    if (! $stillExists && $item->transfer_status !== 'transferred') {
                        $item->transfer_status = 'manually_resolved';
                        $item->resolution_type = $item->resolution_type ?: 'no_longer_active';
                        $item->save();
                    }
                } elseif ($item->item_type === 'document_request') {
                    $stillExists = $documentRequests->contains('id', $item->record_id);

                    if (! $stillExists && $item->transfer_status !== 'transferred') {
                        $item->transfer_status = 'manually_resolved';
                        $item->resolution_type = $item->resolution_type ?: 'no_longer_active';
                        $item->save();
                    }
                }
            });

            return $transition->fresh(['items.patient', 'items.successorDentist', 'items.documentRequest']);
        });
    }

    public function updateSuccessorAssignments(DentistTransition $transition, array $payload, User $actor): DentistTransition
    {
        return DB::transaction(function () use ($transition, $payload, $actor) {
            $this->authorizeAdministrator($actor);
            $transition = $this->editableTransition($transition);
            if (array_key_exists('default_successor_dentist_id', $payload)) {
                $transition->default_successor_dentist_id = $payload['default_successor_dentist_id'] ?: null;
                $transition->status = in_array($transition->status, ['draft', 'pending_review'], true)
                    ? 'handover_in_progress'
                    : $transition->status;
                $transition->save();
                foreach ($transition->items as $item) {
                    if (! in_array($item->transfer_status, ['excluded', 'manually_resolved'], true)) {
                        $item->update(['successor_dentist_id' => $transition->default_successor_dentist_id, 'transfer_status' => $transition->default_successor_dentist_id ? 'ready' : 'pending']);
                    }
                }
            }

            foreach (($payload['items'] ?? []) as $itemId => $itemPayload) {
                /** @var DentistTransitionItem $item */
                $item = $transition->items()->findOrFail($itemId);

                $successorId = $itemPayload['successor_dentist_id'] ?? $transition->default_successor_dentist_id;
                $transferStatus = $itemPayload['transfer_status'] ?? null;

                $item->successor_dentist_id = $successorId ?: null;
                $item->remarks = $itemPayload['remarks'] ?? $item->remarks;
                $item->resolution_type = $itemPayload['resolution_type'] ?? $item->resolution_type;

                if ($transferStatus === 'excluded') {
                    $item->transfer_status = 'excluded';
                } elseif ($transferStatus === 'manually_resolved') {
                    $item->transfer_status = 'manually_resolved';
                } else {
                    $item->transfer_status = $item->successor_dentist_id ? 'ready' : 'pending';
                }

                $item->save();
            }

            $this->auditContinuity('transition_assignments_updated', $transition, $actor);

            return $transition->fresh(['items.patient', 'items.successorDentist', 'items.documentRequest']);
        });
    }

    public function validateTransitionReadiness(DentistTransition $transition): array
    {
        $transition->loadMissing('items');

        $unresolvedCriticalItems = $transition->items
            ->filter(function (DentistTransitionItem $item) {
                return $item->is_critical
                    && ! in_array(
                        $item->transfer_status,
                        ['transferred', 'excluded', 'manually_resolved'],
                        true
                    )
                    && ! $item->successor_dentist_id;
            })
            ->values();

        return [
            'ready' => $unresolvedCriticalItems->isEmpty(),
            'unresolved_critical_items' => $unresolvedCriticalItems,
        ];
    }

    public function finalizeTransition(DentistTransition $transition, User $actor): DentistTransition
    {
        $this->authorizeAdministrator($actor);
        return DB::transaction(function () use ($transition, $actor) {
            $transition = $this->editableTransition($transition);
            if (! $transition->effective_date) {
                $this->invalid('effective_date', 'Review this legacy draft and set an explicit effective date before approval.');
            }
            $transition = $this->generateTransitionItems($transition);
            $this->validateSuccessorsAndConflicts($transition);
            if (! $this->validateTransitionReadiness($transition)['ready']) {
                $this->invalid('transition', 'Assign every open responsibility to an available replacement before approval.');
            }
            $transition->update(['status' => 'scheduled', 'reviewed_by' => $actor->id, 'approved_by' => $actor->id]);
            $this->auditContinuity('transition_finalized', $transition, $actor);
            if ($transition->effective_date && $transition->effective_date->lte(today())) {
                $this->activateTransition($transition, $actor);
            }
            DB::afterCommit(fn () => $this->notifyTransitionParticipants($transition->fresh(), 'finalized'));
            return $transition->fresh(['items.patient', 'items.successorDentist']);
        });
    }

    public function cancelTransition(DentistTransition $transition, string $reason, User $actor): DentistTransition
    {
        $this->authorizeAdministrator($actor);
        return DB::transaction(function () use ($transition, $reason, $actor) {
            $transition = DentistTransition::lockForUpdate()->findOrFail($transition->id);
            if ($transition->activated_at || $transition->items->contains('transfer_status', 'transferred') || ! in_array($transition->status, ['draft', 'pending_review', 'handover_in_progress', 'scheduled'], true)) {
                $this->invalid('transition', 'An effective handover cannot be cancelled. Create a new transition to transfer care.');
            }
            $transition->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => $reason, 'reviewed_by' => $actor->id]);
            $this->auditContinuity('transition_cancelled', $transition, $actor);
            return $transition->fresh();
        });
    }

    public function extendAccess(DentistTransition $transition, Carbon $newAccessEndsAt, User $actor): DentistTransition
    {
        $this->authorizeAdministrator($actor);
        $this->invalid('access_ends_at', 'Access cannot be extended independently of care responsibility. Cancel a scheduled plan and create a reviewed plan with the new dates.');
    }

    public function deactivateExpiredDentists(): int
    {
        // Legacy handovers retain their original expiration behavior.
        $count = 0;
        foreach (DentistTransition::whereNull('effective_date')->whereIn('status', ['scheduled', 'completed'])->where('access_ends_at', '<=', now())->get() as $transition) {
            if ($this->deactivateDentistAccount($transition->dentist, $transition)) {
                $transition->update(['status' => 'completed', 'completed_at' => now()]);
                $count++;
            }
        }
        return $count + $this->processDueTransitions();
    }

    public function processDueTransitions(): int
    {
        $count = 0;
        $ids = DentistTransition::whereNotNull('effective_date')->where(function ($q) {
            $q->where('status', 'scheduled')->whereDate('effective_date', '<=', today());
        })->orWhere(function ($q) {
            $q->where('status', 'active')->where('coverage_type', 'temporary')->whereDate('end_date', '<', today());
        })->pluck('id');
        foreach ($ids as $id) {
            try {
                DB::transaction(function () use ($id, &$count) {
                    $transition = DentistTransition::lockForUpdate()->findOrFail($id);
                    $actor = User::findOrFail($transition->approved_by ?? $transition->initiated_by);
                    if ($transition->status === 'scheduled') {
                        $this->activateTransition($transition, $actor);
                    } elseif ($transition->status === 'active') {
                        $this->endTemporaryTransition($transition, $actor);
                    }
                    $count++;
                });
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->writeAuditEntry('transition_blocked', "Transition #{$id}: " . $exception->getMessage());
                if (\Illuminate\Support\Facades\Cache::add("continuity:{$id}:blocked-notification", true, now()->addHour())) {
                    $this->notifyTransitionParticipants(DentistTransition::findOrFail($id), 'blocked');
                }
            }
        }
        return $count;
    }

    private function activateTransition(DentistTransition $transition, User $actor): void
    {
        if ($transition->activated_at) {
            return;
        }
        if ($transition->coverage_type === 'temporary' && $transition->end_date?->lt(today())) {
            $this->invalid('end_date', 'The coverage period has elapsed. Review and replace this scheduled plan.');
        }
        $transition = $this->generateTransitionItems($transition, true);
        $this->validateSuccessorsAndConflicts($transition);
        if (! $this->validateTransitionReadiness($transition)['ready']) {
            $this->invalid('transition', 'New responsibilities require review before this plan can take effect.');
        }
        $this->transferResponsibilities($transition, $actor);
        $transition->update(['activated_at' => now(), 'status' => $transition->coverage_type === 'temporary' ? 'active' : 'completed', 'completed_at' => $transition->coverage_type === 'temporary' ? null : now()]);
        if ($transition->coverage_type === 'permanent') {
            $this->deactivateDentistAccount($transition->dentist, $transition, $actor->id, false);
        } else {
            $this->revokeDentistSessions($transition->dentist);
        }
        $this->auditContinuity('transition_effective', $transition, $actor);
        DB::afterCommit(fn () => $this->notifyTransitionParticipants($transition->fresh(), 'effective'));
    }

    public function endTemporaryCoverage(DentistTransition $transition, User $actor): DentistTransition
    {
        $this->authorizeAdministrator($actor);
        return DB::transaction(function () use ($transition, $actor) {
            $transition = DentistTransition::lockForUpdate()->findOrFail($transition->id);
            if ($transition->status !== 'active' || $transition->coverage_type !== 'temporary') {
                $this->invalid('transition', 'Only active temporary coverage can be ended.');
            }
            $this->endTemporaryTransition($transition, $actor);
            return $transition->fresh();
        });
    }

    private function endTemporaryTransition(DentistTransition $transition, User $actor): void
    {
        $source = User::lockForUpdate()->findOrFail($transition->dentist_id);
        if ($source->status !== 'active' || ! in_array($source->role?->slug, ['dentist', 'dentist_role'], true) || DentistTransition::where('dentist_id', $source->id)->where('id', '!=', $transition->id)->whereIn('status', ['scheduled', 'active'])->exists()) {
            $this->invalid('transition', 'The returning dentist is unavailable. Arrange onward coverage before ending this plan.');
        }
        foreach ($transition->items()->where('item_type', 'appointment')->get() as $item) {
            $appointment = Appointment::lockForUpdate()->find($item->record_id);
            if ($appointment && in_array($appointment->status, self::ACTIVE_APPOINTMENT_STATUSES, true) && $appointment->procedure()->exists()) {
                $this->invalid('appointments', 'Resolve unfinished clinical documentation before returning care responsibility.');
            }
            if ($appointment && in_array($appointment->status, self::ACTIVE_APPOINTMENT_STATUSES, true)
                && (int) $appointment->dentist_id === (int) $item->successor_dentist_id && ! $appointment->procedure()->exists()) {
                $duration = max(5, (int) (\App\Models\ClinicSchedule::active()->first()?->slot_duration_minutes ?: 60));
                $start = Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time);
                foreach (Appointment::where('dentist_id', $source->id)->whereDate('appointment_date', $appointment->appointment_date)->whereIn('status', self::ACTIVE_APPOINTMENT_STATUSES)->get() as $other) {
                    $otherStart = Carbon::parse($other->appointment_date . ' ' . $other->appointment_time);
                    if ($start->lt($otherStart->copy()->addMinutes($duration)) && $start->copy()->addMinutes($duration)->gt($otherStart)) {
                        $this->invalid('appointments', 'Returning appointments conflict with the original dentist schedule. Review coverage before ending it.');
                    }
                }
                $appointment->update(['dentist_id' => $source->id, 'transferred_by' => $actor->id, 'transferred_at' => now(), 'transfer_reason' => 'temporary_coverage_ended']);
            }
        }
        // Finished visits retain the treating dentist; unresolved documents return.
        foreach ($transition->items()->where('item_type', 'document_request')->get() as $item) {
            $document = DocumentRequest::lockForUpdate()->find($item->record_id);
            if ($document && $document->status === 'pending' && (int) $document->assigned_dentist_id === (int) $item->successor_dentist_id) {
                $document->update(['assigned_dentist_id' => $source->id]);
            }
        }
        DB::table('patient_care_assignments')->where('dentist_transition_id', $transition->id)->update(['dentist_id' => $source->id, 'updated_at' => now()]);
        $transition->update(['status' => 'completed', 'completed_at' => now()]);
        $this->auditContinuity('transition_temporary_ended', $transition, $actor);
        DB::afterCommit(fn () => $this->notifyTransitionParticipants($transition->fresh(), 'completed'));
    }

    private function editableTransition(DentistTransition $transition): DentistTransition
    {
        $transition = DentistTransition::lockForUpdate()->findOrFail($transition->id);
        if (! in_array($transition->status, ['draft', 'pending_review', 'handover_in_progress'], true)) {
            $this->invalid('transition', 'Only an unapproved continuity plan can be modified.');
        }
        return $transition;
    }

    private function authorizeAdministrator(User $actor): void
    {
        abort_unless($actor->status === 'active' && $actor->hasAnyRole(['admin', 'super_admin']) && ! session('impersonated_role'), 403);
    }

    private function invalid(string $field, string $message): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([$field => $message]);
    }

    private function auditContinuity(string $event, DentistTransition $transition, User $actor): void
    {
        $summary = $this->generateImpactSummary($transition->fresh('items'));
        $this->writeAuditEntry($event, json_encode([
            'transition_id' => $transition->id, 'administrator_id' => $actor->id,
            'dentist_id' => $transition->dentist_id, 'replacement_id' => $transition->default_successor_dentist_id,
            'coverage_type' => $transition->coverage_type, 'reason' => $transition->transition_type,
            'effective_date' => $transition->effective_date?->toDateString(), 'end_date' => $transition->end_date?->toDateString(),
            'status' => $transition->status, 'counts' => $summary, 'outcome' => 'success',
        ], JSON_THROW_ON_ERROR));
    }

    private function validateSuccessorsAndConflicts(DentistTransition $transition): void
    {
        $transition->load('items');
        if (! $transition->default_successor_dentist_id) {
            $this->invalid('default_successor_dentist_id', 'Choose a default replacement before approving coverage.');
        }
        foreach ($transition->items as $item) {
            if (in_array($item->transfer_status, ['excluded', 'manually_resolved'], true) && ! trim((string) $item->remarks) && ! $item->resolution_type) {
                $this->invalid('items', 'Document a reason for every excluded or manually resolved responsibility.');
            }
            if (in_array($item->transfer_status, ['excluded', 'manually_resolved'], true)) {
                $stillOwned = match ($item->item_type) {
                    'appointment' => Appointment::whereKey($item->record_id)->where('dentist_id', $transition->dentist_id)
                        ->whereIn('status', self::ACTIVE_APPOINTMENT_STATUSES)->whereDate('appointment_date', '>=', today())->exists(),
                    'document_request' => DocumentRequest::whereKey($item->record_id)->where('assigned_dentist_id', $transition->dentist_id)
                        ->whereHas('requestState', fn ($q) => $q->where('status', 'pending'))->exists(),
                    default => false,
                };
                if ($stillOwned) {
                    $this->invalid('items', 'Excluded open work must first be reassigned, cancelled, or completed; it cannot remain with an unavailable dentist.');
                }
                if ($item->item_type === 'patient' && $transition->items->where('patient_id', $item->patient_id)
                    ->whereIn('item_type', ['appointment', 'document_request'])->where('transfer_status', 'ready')->isNotEmpty()) {
                    $this->invalid('items', 'Patient care responsibility must accompany the work being transferred.');
                }
            }
        }
        foreach ($transition->items->whereNotIn('transfer_status', ['excluded', 'manually_resolved'])->groupBy('patient_id') as $patientItems) {
            if ($patientItems->pluck('successor_dentist_id')->filter()->unique()->count() > 1) {
                $this->invalid('items', 'Use one replacement per patient so responsibility and relevant record access stay aligned.');
            }
        }
        $successorIds = $transition->items->whereNotIn('transfer_status', ['excluded', 'manually_resolved'])->pluck('successor_dentist_id')->push($transition->default_successor_dentist_id)->filter()->unique()->sort();
        foreach ($successorIds as $id) {
            $successor = User::with('role')->lockForUpdate()->find($id);
            if (! $successor || $id == $transition->dentist_id || $successor->status !== 'active' || ! in_array($successor->role?->slug, ['dentist', 'dentist_role'], true)) {
                $this->invalid('default_successor_dentist_id', 'Every replacement must be an available active dentist.');
            }
            if (DentistTransition::where('dentist_id', $id)->whereIn('status', ['scheduled', 'active'])
                ->whereDate('effective_date', '<=', $transition->end_date ?? '9999-12-31')
                ->where(function ($q) use ($transition) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $transition->effective_date);
                })->exists()) {
                $this->invalid('default_successor_dentist_id', 'A replacement has overlapping unavailability.');
            }
        }
        foreach ($transition->items->where('item_type', 'appointment')->where('transfer_status', 'ready') as $item) {
            $appointment = Appointment::find($item->record_id);
            if (! $appointment || ! in_array($appointment->status, self::ACTIVE_APPOINTMENT_STATUSES, true)) {
                continue;
            }
            if ($appointment->procedure()->exists()) {
                $this->invalid('appointments', 'An affected visit has clinical work already started. Resolve or exclude it with documented handover instructions before approval.');
            }
            $duration = max(5, (int) (\App\Models\ClinicSchedule::active()->first()?->slot_duration_minutes ?: 60));
            $start = Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time);
            $end = $start->copy()->addMinutes($duration);
            $otherAppointments = Appointment::where('dentist_id', $item->successor_dentist_id)->whereDate('appointment_date', $appointment->appointment_date)
                ->whereIn('status', self::ACTIVE_APPOINTMENT_STATUSES)->whereKeyNot($appointment->id)->get();
            // Also check collisions between two appointments in this same handover.
            foreach ($transition->items->where('item_type', 'appointment')->where('transfer_status', 'ready')->where('successor_dentist_id', $item->successor_dentist_id) as $otherItem) {
                if ($otherItem->record_id != $appointment->id) {
                    $other = Appointment::find($otherItem->record_id);
                    if ($other && $other->appointment_date === $appointment->appointment_date) {
                        $otherAppointments->push($other);
                    }
                }
            }
            foreach ($otherAppointments as $other) {
                $otherStart = Carbon::parse($other->appointment_date . ' ' . $other->appointment_time);
                if ($start->lt($otherStart->copy()->addMinutes($duration)) && $end->gt($otherStart)) {
                    $this->invalid('appointments', 'The replacement has overlapping appointments. Reschedule or assign another replacement.');
                }
            }
        }
    }

    public function revokeDentistSessions(User $dentist): int
    {
        return $this->concurrentSessionService->revokeAllSessions($dentist, null, 'dentist_deactivated');
    }

    private function transferResponsibilities(
        DentistTransition $transition,
        User $actor
    ): void {
        foreach ($transition->items as $item) {
            if (
                $item->transfer_status !== 'ready'
                || ! $item->successor_dentist_id
            ) {
                continue;
            }

            if ($item->item_type === 'patient') {
                $existing = DB::table('patient_care_assignments')->where('patient_id', $item->patient_id)->lockForUpdate()->first();
                if ($existing && (int) $existing->dentist_id !== (int) $transition->dentist_id) {
                    $this->invalid('patients', 'Patient care responsibility changed after review. Refresh and review the plan.');
                }
                DB::table('patient_care_assignments')->updateOrInsert(['patient_id' => $item->patient_id], [
                    'dentist_id' => $item->successor_dentist_id, 'dentist_transition_id' => $transition->id,
                    'created_at' => $existing?->created_at ?? now(), 'updated_at' => now(),
                ]);
                $item->update(['transfer_status' => 'transferred', 'transferred_by' => $actor->id, 'transferred_at' => now()]);
                $this->writeAuditEntry('transition_access_updated', "Transition #{$transition->id}: care responsibility and relevant record access transferred from dentist #{$transition->dentist_id} to dentist #{$item->successor_dentist_id}.");
                continue;
            }

            if ($item->item_type === 'appointment') {
                $appointment = Appointment::query()
                    ->lockForUpdate()
                    ->find($item->record_id);

                if (! $appointment) {
                    $item->transfer_status = 'failed';
                    $item->resolution_type = 'missing_record';
                    $item->remarks = trim(
                        ($item->remarks ? $item->remarks . ' ' : '')
                            . 'Appointment not found during transfer.'
                    );
                    $item->save();

                    throw new \RuntimeException(
                        "Appointment {$item->record_id} could not be transferred because it no longer exists."
                    );
                }

                if ((int) $appointment->dentist_id !== (int) $transition->dentist_id) {
                    $this->invalid('appointments', 'An appointment changed dentist after review. Refresh the plan.');
                }
                $currentStatus = strtolower((string) $appointment->status);

                if (
                    ! in_array(
                        $currentStatus,
                        self::ACTIVE_APPOINTMENT_STATUSES,
                        true
                    )
                ) {
                    $item->transfer_status = 'manually_resolved';
                    $item->resolution_type = 'no_longer_active';
                    $item->save();

                    continue;
                }

                $appointment->original_dentist_id =
                    $appointment->original_dentist_id
                    ?: $transition->dentist_id;

                $appointment->dentist_id =
                    $item->successor_dentist_id;

                $appointment->transferred_by = $actor->id;
                $appointment->transferred_at = now();
                $appointment->transfer_reason =
                    $transition->transition_type;

                $appointment->save();

                $item->transfer_status = 'transferred';
                $item->transferred_by = $actor->id;
                $item->transferred_at = now();
                $item->save();

                $this->writeAuditEntry(
                    'transition_item_transferred',
                    "Transferred appointment #{$appointment->id} from dentist #{$transition->dentist_id} to dentist #{$item->successor_dentist_id}."
                );

                continue;
            }

            if ($item->item_type === 'document_request') {
                $documentRequest = DocumentRequest::query()
                    ->lockForUpdate()
                    ->find($item->record_id);

                if (! $documentRequest) {
                    $item->transfer_status = 'failed';
                    $item->resolution_type = 'missing_record';
                    $item->save();

                    throw new \RuntimeException(
                        "Document request {$item->record_id} could not be transferred because it no longer exists."
                    );
                }

                if (
                    (int) $documentRequest->assigned_dentist_id
                    !== (int) $transition->dentist_id
                ) {
                    $item->transfer_status = 'manually_resolved';
                    $item->resolution_type = 'no_longer_assigned';
                    $item->save();

                    continue;
                }

                $documentRequest->assigned_dentist_id =
                    $item->successor_dentist_id;

                $documentRequest->save();

                $item->transfer_status = 'transferred';
                $item->transferred_by = $actor->id;
                $item->transferred_at = now();
                $item->save();

                $this->writeAuditEntry(
                    'transition_item_transferred',
                    "Transferred document request #{$documentRequest->id} from dentist #{$transition->dentist_id} to dentist #{$item->successor_dentist_id}."
                );
            }
        }
    }
    private function eligibleAppointmentsQuery(DentistTransition $transition)
    {
        return Appointment::query()
            ->when(
                Schema::hasColumn('appointments', 'dentist_id'),
                function ($query) use ($transition) {
                    $query->where('dentist_id', $transition->dentist_id);
                }
            )
            ->whereIn('status', self::ACTIVE_APPOINTMENT_STATUSES)
            ->whereDate(
                'appointment_date',
                '>=',
                ($transition->effective_date ?? $transition->last_working_date->copy()->addDay())->toDateString()
            )
            ->when($transition->coverage_type === 'temporary' && $transition->end_date, fn ($q) => $q->whereDate('appointment_date', '<=', $transition->end_date));
    }

    private function mapEmploymentStatus(string $transitionType): string
    {
        return match ($transitionType) {
            'retirement' => 'retired',
            'resignation' => 'resigned',
            'transfer' => 'transferred',
            'long_term_leave' => 'on_leave',
            'termination' => 'terminated',
            default => 'inactive',
        };
    }

    private function deactivateDentistAccount(?User $dentist, DentistTransition $transition, ?int $actorId = null, bool $automated = true): bool
    {
        if (! $dentist) {
            return false;
        }

        $targetStatus = $this->mapEmploymentStatus($transition->transition_type);

        if ($dentist->status === 'inactive' && $dentist->account_status === $targetStatus && $dentist->deactivated_at) {
            return false;
        }

        $this->revokeDentistSessions($dentist);

        $dentist->status = 'inactive';
        $dentist->employment_status = $targetStatus;
        $dentist->account_status = $targetStatus;
        $dentist->deactivated_at = now();
        $dentist->deactivated_by = $actorId;
        $dentist->deactivation_reason = $transition->transition_type;
        $dentist->save();

        $this->writeAuditEntry(
            $automated ? 'transition_auto_deactivated' : 'transition_deactivated',
            "Deactivated dentist #{$dentist->id} for transition #{$transition->id}."
        );

        return true;
    }

    public function notifyTransitionParticipants(DentistTransition $transition, string $event): void
    {
        $recipients = User::query()
            ->where(function ($q) use ($transition) {
                $q->whereIn('id', [$transition->dentist_id, $transition->default_successor_dentist_id])
                    ->orWhereHas('role', fn ($q) => $q->whereIn('slug', ['admin', 'super_admin']));
            })
            ->where('status', 'active')
            ->whereNotNull('id')
            ->get()
            ->unique('id');

        foreach ($recipients as $recipient) {
            $role = optional($recipient->role)->slug;
            $recipient->notify(new DentistTransitionNotification($transition, $event, $role));
        }
    }

    private function writeAuditEntry(string $action, string $description): void
    {
        AuditLogger::log($action, 'dentist_transition', $description);
    }
}
