<?php

namespace App\Services;

use App\Models\{AiServiceLog, Appointment, AuditLog, DocumentRequest, Patient, RetentionPolicy, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RetentionReviewService
{
    public function records(RetentionPolicy $policy): Builder
    {
        return match ($policy->category) {
            'users' => User::query()->with('patient'),
            'patients', 'clinical_records' => Patient::query()->with([
                'user.profile', 'appointments.procedure', 'appointments.closure',
                'documentRequests.requestState', 'documentRequests.review', 'medicalHistory', 'dentalHistory',
            ]),
            'appointments' => Appointment::query()->with(['closure', 'procedure', 'patient.user.profile']),
            'document_requests' => DocumentRequest::query()->with(['requestState', 'review', 'patient.user.profile']),
            'audit_logs' => AuditLog::query()->without(['actorSnapshot', 'requestDetails', 'descriptionDetails']),
            'ai_service_logs' => AiServiceLog::query(),
            default => throw new \InvalidArgumentException('Unsupported retention category.'),
        };
    }

    public function policyNotice(RetentionPolicy $policy): ?string
    {
        if (! $policy->isCurrent() || ! $policy->hasConfiguredRule()) {
            return 'Choose and save a supported counting rule, then record approval for the saved policy settings.';
        }
        if ($policy->status === 'provisional') return 'This policy is Pending Review. Dates shown are a preview until approval and activation.';
        if ($policy->status !== 'active') return 'This policy is not activated. Dates shown are a preview until activation.';
        if (! $this->validPeriod($policy) || ! $policy->approved_by || ! $policy->approved_at
            || ! $policy->approval_reference || ! $policy->activated_by || ! $policy->activated_at
            || $policy->policy_version !== $policy->version || ! $policy->effective_from) {
            return 'Complete the approval details for the saved policy settings before reviewing record eligibility.';
        }
        if ($policy->effective_from->copy()->startOfDay()->gt(now())) {
            return 'This policy becomes effective on '.$policy->effective_from->format('M d, Y').'. Dates shown are a preview until then.';
        }

        return null;
    }

    private function validPeriod(RetentionPolicy $policy): bool
    {
        return $policy->duration >= 1 && in_array($policy->unit, ['days', 'weeks', 'months', 'years'], true);
    }

    private function dueDate(RetentionPolicy $policy, Carbon $start): Carbon
    {
        return match ($policy->unit) {
            'days' => $start->copy()->addDays($policy->duration),
            'weeks' => $start->copy()->addWeeks($policy->duration),
            'months' => $start->copy()->addMonthsNoOverflow($policy->duration),
            'years' => $start->copy()->addYearsNoOverflow($policy->duration),
        };
    }

    public function assess(RetentionPolicy $policy, Model $record): array
    {
        if (! $policy->hasConfiguredRule()) {
            return $this->result('Policy not active', $this->policyNotice($policy), null, null, 'Choose a counting rule', false);
        }

        try {
            return $this->evaluate($policy, $this->facts($policy, $record));
        } catch (\Carbon\Exceptions\InvalidFormatException) {
            return $this->result('Needs review', 'A source date cannot be read reliably. Verify the original record date.');
        }
    }

    private function evaluate(RetentionPolicy $policy, array $facts): array
    {
        $start = $facts['start'] ? Carbon::parse($facts['start']) : null;
        if ($start && $start->year < 1) {
            return $this->result('Needs review', 'The original record contains an invalid calendar date.');
        }
        $future = $start?->gt(now());
        $due = $start && ! $future && $this->validPeriod($policy) ? $this->dueDate($policy, $start) : null;
        $notice = $this->policyNotice($policy);
        $emptyDate = $facts['waiting'] ? 'Not started' : 'Date needs review';
        if ($notice) {
            return $this->result('Policy not active', $facts['keep'] ?? $facts['issue'] ?? $notice, $start, $due, $emptyDate, false);
        }
        if ($future) {
            return $this->result('Needs review', 'The recorded starting date is in the future. Verify the source date.', $start, null);
        }
        if ($facts['keep']) {
            return $this->result('Keep record', $facts['keep'], $start, $due, $emptyDate);
        }
        if ($facts['issue']) {
            return $this->result('Needs review', $facts['issue'], $start, $due, $emptyDate);
        }
        if (! $start) {
            return $this->result($facts['waiting'] ? 'Not started' : 'Needs review',
                $facts['waiting'] ?? 'A verified historical starting date is missing. No date is inferred from updated_at.',
                null, null, $emptyDate);
        }

        return $due->lte(now())
            ? $this->result('Eligible for review', 'The period has elapsed. Review preservation requirements and related records before any separate disposal decision.', $start, $due)
            : $this->result('Not yet due', 'The retention period has not elapsed.', $start, $due);
    }

    private function result(string $status, string $reason, ?Carbon $start = null, ?Carbon $due = null,
        string $emptyDate = 'Date needs review', bool $policyReady = true): array
    {
        $tone = match ($status) {
            'Eligible for review' => 'status-approved',
            'Keep record', 'Needs review' => 'status-pending',
            default => 'status-inactive',
        };

        return [
            'status' => $status, 'reason' => $reason, 'status_tone' => $tone,
            'start' => $start?->toDateTimeString(), 'due' => $due?->toDateTimeString(),
            'start_display' => $start?->format('M d, Y') ?? $emptyDate,
            'due_display' => $due?->format('M d, Y') ?? ($emptyDate === 'Not started' ? 'Awaiting starting event' : 'Awaiting verified date'),
            'due_note' => $due && ! $policyReady ? 'Preview — policy not active' : null,
        ];
    }

    private function facts(RetentionPolicy $policy, Model $record): array
    {
        $facts = ['start' => null, 'keep' => null, 'issue' => null, 'waiting' => null];
        switch ($policy->counting_rule) {
            case 'account_deactivated':
                if (! $record instanceof User) throw new \InvalidArgumentException('Expected an account.');
                if ($record->status === 'active') {
                    $facts['keep'] = 'The account is still active. Retention starts after account deactivation.';
                    $facts['waiting'] = 'Awaiting account deactivation.';
                } else {
                    $facts['start'] = $record->deactivated_at;
                    if ($record->patient || Appointment::where('dentist_id', $record->id)->exists()) {
                        $facts['keep'] = 'Related patient or treatment records still depend on this account.';
                    }
                }
                break;
            case 'linked_account_deactivated':
            case 'last_completed_treatment':
                if (! $record instanceof Patient) throw new \InvalidArgumentException('Expected a patient.');
                $record->loadMissing(['user', 'appointments.procedure', 'appointments.closure',
                    'documentRequests.requestState', 'documentRequests.review', 'medicalHistory', 'dentalHistory']);
                $open = $record->appointments->contains(fn ($appointment) => ! in_array($appointment->status, Appointment::FINALIZED_STATUSES, true))
                    || $record->documentRequests->contains(fn ($request) => DocumentRequest::normalizedStatus($request->status) === 'pending');
                if ($open) $facts['keep'] = 'There are open appointments or document requests. The profile is still needed.';
                if ($policy->counting_rule === 'linked_account_deactivated') {
                    if (! $record->user) {
                        $facts['issue'] = 'No linked account exists. A deactivation date cannot be inferred for this patient profile.';
                    } elseif ($record->user->status === 'active') {
                        $facts['keep'] ??= 'The linked account is still active. This keeps the record; it does not block patient access.';
                        $facts['waiting'] = 'Awaiting linked account deactivation.';
                    } else {
                        $facts['start'] = $record->user->deactivated_at;
                        if (! $facts['keep']) $facts['keep'] = $this->patientDependencyReason($record);
                    }
                } else {
                    $completed = $record->appointments->where('status', 'completed');
                    if ($completed->contains(fn ($appointment) => ! $appointment->procedure?->procedure_completed_at)) {
                        $facts['issue'] = 'A completed treatment has no documented completion date; the last-treatment date needs review.';
                    } else {
                        $facts['start'] = $completed->map(fn ($appointment) => $appointment->procedure->procedure_completed_at)->sort()->last();
                        if ($completed->isEmpty() && ! $record->medicalHistory && ! $record->dentalHistory) {
                            $facts['waiting'] = 'Awaiting a completed treatment.';
                        }
                        foreach ([$record->medicalHistory, $record->dentalHistory, ...$completed->pluck('procedure')] as $clinicalRecord) {
                            if ($facts['start'] && $clinicalRecord?->updated_at?->gt($facts['start'])) {
                                $facts['issue'] = 'Clinical details changed after the last completed treatment. Review the activity before a preservation decision.';
                                break;
                            }
                        }
                    }
                }
                break;
            case 'appointment_closed':
                if (! $record instanceof Appointment) throw new \InvalidArgumentException('Expected an appointment.');
                if (! in_array($record->status, Appointment::FINALIZED_STATUSES, true)) {
                    $facts['keep'] = 'This appointment is still open.';
                    $facts['waiting'] = 'Awaiting appointment finalization.';
                } else {
                    // Existing production treatments can predate the closure table.
                    $facts['start'] = $record->closure?->closed_at
                        ?? ($record->status === 'completed' ? $record->procedure?->procedure_completed_at : null);
                    if ($record->procedure) $facts['keep'] = 'Treatment details depend on this appointment; preserve them with the clinical record.';
                }
                break;
            case 'document_request_decided':
                if (! $record instanceof DocumentRequest) throw new \InvalidArgumentException('Expected a document request.');
                if (DocumentRequest::normalizedStatus($record->status) === 'pending') {
                    $facts['keep'] = 'The request is pending. Retention starts when it is approved or rejected.';
                    $facts['waiting'] = 'Awaiting approval or rejection.';
                } else {
                    $facts['start'] = $record->retentionDecisionDate();
                }
                break;
            case 'record_created':
                if (($policy->category === 'audit_logs' && ! $record instanceof AuditLog)
                    || ($policy->category === 'ai_service_logs' && ! $record instanceof AiServiceLog)) {
                    throw new \InvalidArgumentException('Expected a log record.');
                }
                $facts['start'] = $record->created_at;
                break;
            default:
                $facts['issue'] = 'The saved counting rule is no longer supported. Save the current rule and record a new approval.';
        }

        return $facts;
    }

    private function patientDependencyReason(Patient $patient): ?string
    {
        $policies = RetentionPolicy::current()->with('approval')
            ->whereIn('category', ['clinical_records', 'appointments', 'document_requests'])->get()->keyBy('category');
        $hasClinical = $patient->medicalHistory || $patient->dentalHistory || $patient->appointments->contains(fn ($appointment) => (bool) $appointment->procedure);
        if ($hasClinical) {
            $clinicalPolicy = $policies->get('clinical_records');
            if (! $clinicalPolicy || $this->assess($clinicalPolicy, $patient)['status'] !== 'Eligible for review') {
                return 'Clinical records still need retention or a verified date under their own policy.';
            }
        }
        foreach (['appointments' => $patient->appointments, 'document_requests' => $patient->documentRequests] as $category => $records) {
            $dependencyPolicy = $policies->get($category);
            if ($records->isNotEmpty() && ! $dependencyPolicy) return 'A related record category has no retention policy.';
            foreach ($records as $record) {
                $facts = $this->facts($dependencyPolicy, $record);
                // The patient-level clinical check above already preserves that dependency.
                if ($category === 'appointments' && $record->procedure && $hasClinical) $facts['keep'] = null;
                if ($this->evaluate($dependencyPolicy, $facts)['status'] !== 'Eligible for review') {
                    return 'Related '.str_replace('_', ' ', $category).' still need retention or a verified date under their own policy.';
                }
            }
        }

        return null;
    }
}
