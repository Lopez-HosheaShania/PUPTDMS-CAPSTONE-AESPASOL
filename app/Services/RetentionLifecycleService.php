<?php

namespace App\Services;

use App\Models\RetentionPolicy;
use App\Models\RetentionRecordReview;
use Carbon\CarbonImmutable;

class RetentionLifecycleService
{
    public function assess(RetentionPolicy $policy, int $recordId): array
    {
        $blockers = [];
        $model = config("retention.categories.{$policy->category}.model");
        $record = $model ? $model::find($recordId) : null;
        $review = RetentionRecordReview::where('category', $policy->category)
            ->where('record_id', $recordId)->first();
        $start = $policy->start_event === 'last_updated'
            ? ($record?->updated_at ? CarbonImmutable::parse($record->updated_at) : null)
            : $review?->retention_starts_at;
        $expires = $start ? match ($policy->unit) {
            'days' => $start->addDays($policy->duration),
            'weeks' => $start->addWeeks($policy->duration),
            'months' => $start->addMonthsNoOverflow($policy->duration),
            'years' => $start->addYearsNoOverflow($policy->duration),
            default => null,
        } : null;
        if (! $record) {
            $blockers[] = 'record_missing';
        }
        if ($policy->status !== 'active' || ! $policy->approved_at || ! $policy->approved_by
            || ! $policy->activated_at || ! $policy->activated_by || ! $policy->approval_reference) {
            $blockers[] = 'policy_not_approved_and_active';
        }
        if (! $expires || $expires->isFuture()) {
            $blockers[] = 'retention_not_expired';
        }
        if (! $review || ! $review->reviewed_at || ! $review->reviewed_by || ! $review->review_reference
            || $review->reviewed_at->isFuture() || $review->policy_version !== $policy->version) {
            $blockers[] = 'current_review_required';
        }
        if (! $review || $review->legal_hold) {
            $blockers[] = 'legal_hold_or_unknown';
        }
        if (! $review || $review->preservation_required) {
            $blockers[] = 'preservation_required_or_unknown';
        }
        if (! $review?->dependencies_cleared) {
            $blockers[] = 'dependencies_not_cleared';
        }
        if ($record && $review?->reviewed_at && $record->updated_at?->gt($review->reviewed_at)) {
            $blockers[] = 'record_changed_since_review';
        }
        $disposalBlockers = $blockers;
        if (! $review?->archived_at) {
            $disposalBlockers[] = 'archive_required_before_disposal';
        }
        if ($review?->archived_at) {
            $blockers[] = 'already_archived';
        }
        if (! $policy->archive_enabled || ! config('retention.archiving_enabled')) {
            $blockers[] = 'archiving_disabled';
        }
        $blockers[] = 'archive_adapter_required';

        return ['expires_at' => $expires?->toIso8601String(), 'archive_eligible' => false,
            'disposal_eligible' => false, 'blockers' => array_values(array_unique($blockers)),
            'disposal_blockers' => array_values(array_unique(array_merge($disposalBlockers,
                ['disposal_disabled', 'separate_authorization_required',
                    'backup_and_related_record_disposal_plan_required', 'secure_disposal_adapter_required'])))];
    }
}
