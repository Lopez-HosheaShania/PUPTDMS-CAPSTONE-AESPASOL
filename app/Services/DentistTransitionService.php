<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Appointment;
use App\Models\DentistTransition;
use App\Models\DentistTransitionChecklistItem;
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

    private const CHECKLIST_ITEMS = [
        'successor_assigned' => 'Successor dentist assigned',
        'future_appointments_reviewed' => 'Future appointments reviewed',
        'active_treatment_plans_reviewed' => 'Active treatment plans reviewed',
        'pending_procedures_reviewed' => 'Pending procedures reviewed',
        'incomplete_records_resolved' => 'Draft or incomplete records resolved',
        'patient_documents_endorsed' => 'Patient-related documents endorsed',
        'schedule_reviewed' => 'Clinic schedule responsibilities reviewed',
        'handover_notes_submitted' => 'Handover notes submitted',
        'deactivation_confirmed' => 'Account deactivation date confirmed',
        'reviewed_by_admin' => 'Transition reviewed by an administrator',
    ];

    public function createTransition(array $payload, User $actor): DentistTransition
    {
        return DB::transaction(function () use ($payload, $actor) {
            $accessEndsAt = Carbon::parse($payload['last_working_date'])->endOfDay();

            $transition = DentistTransition::create([
                'dentist_id' => $payload['dentist_id'],
                'transition_type' => $payload['transition_type'],
                'default_successor_dentist_id' => $payload['default_successor_dentist_id'],
                'last_working_date' => $payload['last_working_date'],
                'access_ends_at' => $accessEndsAt,
                'status' => $accessEndsAt->isFuture() ? 'scheduled' : 'completed',
                'handover_notes' => $payload['handover_notes'] ?? null,
                'remarks' => null,
                'initiated_by' => $actor->id,
                'reviewed_by' => $actor->id,
                'approved_by' => $actor->id,
                'completed_at' => $accessEndsAt->isFuture() ? null : now(),
            ]);

            $transition = $this->generateTransitionItems($transition);

            $this->transferResponsibilities($transition, $actor);

            if ($accessEndsAt->isFuture()) {
                $this->syncDentistAccountMarkers(
                    $transition,
                    'for_transition',
                    'active'
                );
            } else {
                $this->deactivateDentistAccount(
                    $transition->dentist,
                    $transition,
                    $actor->id,
                    false
                );
            }

            $this->notifyTransitionParticipants($transition, 'finalized');

            $this->writeAuditEntry(
                'transition_finalized',
                sprintf(
                    'Confirmed dentist handover #%d from dentist #%d to dentist #%d.',
                    $transition->id,
                    $transition->dentist_id,
                    $transition->default_successor_dentist_id
                )
            );

            return $transition->fresh([
                'dentist.role',
                'defaultSuccessor',
                'items.patient',
                'items.successorDentist',
                'items.documentRequest',
            ]);
        });
    }

    public function updateTransition(
        DentistTransition $transition,
        array $payload,
        User $actor
    ): DentistTransition {
        return DB::transaction(function () use ($transition, $payload, $actor) {
            $accessEndsAt = Carbon::parse($payload['last_working_date'])->endOfDay();

            $transition->fill([
                'transition_type' => $payload['transition_type'],
                'default_successor_dentist_id' => $payload['default_successor_dentist_id'],
                'last_working_date' => $payload['last_working_date'],
                'access_ends_at' => $accessEndsAt,
                'handover_notes' => $payload['handover_notes'] ?? null,
            ]);

            $transition->status = $accessEndsAt->isFuture()
                ? 'scheduled'
                : 'completed';

            $transition->reviewed_by = $actor->id;
            $transition->approved_by = $actor->id;
            $transition->completed_at = $accessEndsAt->isFuture()
                ? null
                : now();

            $transition->save();

            $transition = $this->generateTransitionItems($transition->fresh());

            $this->transferResponsibilities($transition, $actor);

            if ($accessEndsAt->isFuture()) {
                $this->syncDentistAccountMarkers(
                    $transition,
                    'for_transition',
                    'active'
                );
            } else {
                $this->deactivateDentistAccount(
                    $transition->dentist,
                    $transition,
                    $actor->id,
                    false
                );
            }

            $this->notifyTransitionParticipants($transition, 'finalized');

            $this->writeAuditEntry(
                'transition_updated',
                "Updated and confirmed dentist transition #{$transition->id}."
            );

            return $transition->fresh([
                'dentist.role',
                'defaultSuccessor',
                'items.patient',
                'items.successorDentist',
                'items.documentRequest',
            ]);
        });
    }

    public function generateImpactSummary(DentistTransition $transition): array
    {
        $items = $transition->items;

        return [
            'future_appointments' => $items->where('item_type', 'appointment')->count(),
            'ready_to_transfer' => $items->where('transfer_status', 'ready')->count(),
            'excluded_items' => $items->where('transfer_status', 'excluded')->count(),
            'transferred_items' => $items->where('transfer_status', 'transferred')->count(),
            'critical_unresolved_items' => $items->filter(function (DentistTransitionItem $item) {
                return $item->is_critical && ! in_array($item->transfer_status, ['transferred', 'excluded', 'manually_resolved'], true);
            })->count(),
        ];
    }

    public function generateTransitionItems(DentistTransition $transition): DentistTransition
    {
        return DB::transaction(function () use ($transition) {
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
                    : $transition->default_successor_dentist_id;

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

            return $transition->fresh(['items.patient', 'items.successorDentist', 'items.documentRequest', 'checklistItems']);
        });
    }

    public function updateSuccessorAssignments(DentistTransition $transition, array $payload, User $actor): DentistTransition
    {
        return DB::transaction(function () use ($transition, $payload) {
            if (array_key_exists('default_successor_dentist_id', $payload)) {
                $transition->default_successor_dentist_id = $payload['default_successor_dentist_id'] ?: null;
                $transition->status = in_array($transition->status, ['draft', 'pending_review'], true)
                    ? 'handover_in_progress'
                    : $transition->status;
                $transition->save();
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

            $this->writeAuditEntry('transition_assignments_updated', "Updated successor assignments for transition #{$transition->id}.");

            return $transition->fresh(['items.patient', 'items.successorDentist', 'items.documentRequest', 'checklistItems']);
        });
    }

    public function updateChecklist(DentistTransition $transition, array $payload, User $actor): DentistTransition
    {
        return DB::transaction(function () use ($transition, $payload, $actor) {
            foreach ($transition->checklistItems as $item) {
                $itemPayload = $payload['checklist'][$item->id] ?? null;
                $isCompleted = (bool) ($itemPayload['is_completed'] ?? false);

                $item->is_completed = $isCompleted;
                $item->remarks = $itemPayload['remarks'] ?? null;
                $item->completed_by = $isCompleted ? $actor->id : null;
                $item->completed_at = $isCompleted ? now() : null;
                $item->save();
            }

            if ($transition->status === 'draft') {
                $transition->status = 'handover_in_progress';
                $transition->reviewed_by = $actor->id;
                $transition->save();
            }

            $this->writeAuditEntry('transition_checklist_updated', "Updated checklist for transition #{$transition->id}.");

            return $transition->fresh(['items.patient', 'items.successorDentist', 'items.documentRequest', 'checklistItems']);
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
            'missing_checklist' => collect(),
            'unresolved_critical_items' => $unresolvedCriticalItems,
        ];
    }

    public function finalizeTransition(DentistTransition $transition, User $actor): DentistTransition
    {
        $readiness = $this->validateTransitionReadiness($transition);

        if (! $readiness['ready']) {
            throw new \RuntimeException(
                'This transition cannot be finalized until all affected responsibilities have a valid successor dentist.'
            );
        }

        return DB::transaction(function () use ($transition, $actor) {
            /** @var DentistTransition $lockedTransition */
            $lockedTransition = DentistTransition::query()
                ->with(['items', 'checklistItems'])
                ->lockForUpdate()
                ->findOrFail($transition->id);

            if (in_array($lockedTransition->status, ['scheduled', 'completed'], true)) {
                return $lockedTransition->fresh(['dentist', 'defaultSuccessor', 'items.patient', 'items.successorDentist', 'items.documentRequest', 'checklistItems']);
            }

            $this->transferResponsibilities($lockedTransition, $actor);

            $lockedTransition->approved_by = $actor->id;
            $lockedTransition->reviewed_by = $actor->id;
            $lockedTransition->status = $lockedTransition->access_ends_at->isFuture() ? 'scheduled' : 'completed';
            $lockedTransition->completed_at = now();
            $lockedTransition->save();

            $employmentStatus = $this->mapEmploymentStatus($lockedTransition->transition_type);
            $accountStatus = $lockedTransition->access_ends_at->isFuture() ? 'for_transition' : $employmentStatus;
            $this->syncDentistAccountMarkers($lockedTransition, $employmentStatus, $accountStatus);

            if (! $lockedTransition->access_ends_at->isFuture()) {
                $this->deactivateDentistAccount($lockedTransition->dentist, $lockedTransition, null, false);
            }

            $this->notifyTransitionParticipants($lockedTransition, 'finalized');
            $this->writeAuditEntry('transition_finalized', "Finalized dentist transition #{$lockedTransition->id}.");

            return $lockedTransition->fresh(['dentist', 'defaultSuccessor', 'items.patient', 'items.successorDentist', 'items.documentRequest', 'checklistItems']);
        });
    }

    public function cancelTransition(DentistTransition $transition, string $reason, User $actor): DentistTransition
    {
        return DB::transaction(function () use ($transition, $reason, $actor) {
            $transition->status = 'cancelled';
            $transition->cancelled_at = now();
            $transition->cancellation_reason = $reason;
            $transition->reviewed_by = $actor->id;
            $transition->save();

            $dentist = $transition->dentist;
            $dentist->employment_status = $dentist->employment_status === 'for_transition' ? 'active' : $dentist->employment_status;
            $dentist->account_status = 'active';
            $dentist->save();

            $this->writeAuditEntry('transition_cancelled', "Cancelled dentist transition #{$transition->id}. Reason: {$reason}");

            return $transition->fresh(['dentist', 'defaultSuccessor', 'items.patient', 'items.successorDentist', 'items.documentRequest', 'checklistItems']);
        });
    }

    public function extendAccess(DentistTransition $transition, Carbon $newAccessEndsAt, User $actor): DentistTransition
    {
        return DB::transaction(function () use ($transition, $newAccessEndsAt) {
            $transition->access_ends_at = $newAccessEndsAt;
            $transition->status = $transition->status === 'completed' ? 'scheduled' : $transition->status;
            $transition->save();

            $dentist = $transition->dentist;
            $dentist->access_ends_at = $newAccessEndsAt;
            $dentist->status = 'active';
            $dentist->account_status = 'for_transition';
            $dentist->deactivated_at = null;
            $dentist->deactivated_by = null;
            $dentist->deactivation_reason = null;
            $dentist->save();

            $this->writeAuditEntry('transition_access_extended', "Extended dentist access for transition #{$transition->id} until {$newAccessEndsAt->toDateTimeString()}.");

            return $transition->fresh(['dentist', 'defaultSuccessor', 'items.patient', 'items.successorDentist', 'items.documentRequest', 'checklistItems']);
        });
    }

    public function deactivateExpiredDentists(): int
    {
        $now = now();

        $transitions = DentistTransition::query()
            ->with('dentist')
            ->whereIn('status', ['scheduled', 'completed'])
            ->where('access_ends_at', '<=', $now)
            ->get();

        $count = 0;

        foreach ($transitions as $transition) {
            $changed = $this->deactivateDentistAccount($transition->dentist, $transition, null, true);

            if ($changed) {
                $transition->status = 'completed';
                $transition->completed_at = $transition->completed_at ?: $now;
                $transition->save();
                $count++;
            }
        }

        return $count;
    }

    public function revokeDentistSessions(User $dentist): int
    {
        return $this->concurrentSessionService->revokeAllSessions($dentist, null, 'dentist_deactivated');
    }

    private function createChecklistItems(DentistTransition $transition): void
    {
        foreach (self::CHECKLIST_ITEMS as $key => $label) {
            DentistTransitionChecklistItem::firstOrCreate(
                [
                    'dentist_transition_id' => $transition->id,
                    'checklist_key' => $key,
                ],
                [
                    'label' => $label,
                    'is_required' => true,
                    'is_completed' => false,
                ]
            );
        }
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
                '>',
                $transition->last_working_date->toDateString()
            );
    }

    private function syncDentistAccountMarkers(DentistTransition $transition, string $employmentStatus, string $accountStatus): void
    {
        $dentist = $transition->dentist()->firstOrFail();
        $dentist->employment_status = $employmentStatus;
        $dentist->account_status = $accountStatus;
        $dentist->last_working_date = $transition->last_working_date;
        $dentist->access_ends_at = $transition->access_ends_at;
        $dentist->save();
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
            ->whereIn('id', [$transition->dentist_id, $transition->default_successor_dentist_id])
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
