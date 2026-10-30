<div id="tab-retention" class="settings-section {{ $defaultSettingsTab === 'retention' ? 'active' : '' }}">
    <div class="section-card">
        <div class="section-card-hdr retention-header">
            <div class="section-card-hdr-left">
                <i class="fa-solid fa-shield-halved text-[#8B0000]" aria-hidden="true"></i>
                <h2 class="font-bold text-gray-800 text-sm">Data Retention</h2>
            </div>
            <a href="{{ route('admin.retention.review') }}" class="ui-btn ui-btn-secondary" aria-label="View Records for Retention Review">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i> Review Records
            </a>
        </div>
        <div class="section-card-body">
            <p class="setting-row-desc retention-intro">Set the period and counting rule for each category. Changes require your admin password and return the policy to Pending Review.</p>
            <p class="setting-row-desc retention-intro">Clinical records share one period. Only approved, active policies are used for review eligibility. Saving or activating a policy does not archive or delete records.</p>
            <p class="setting-row-desc retention-intro">The policy effective date controls when the rule can be used. Each record's starting date comes from the event selected under Count from.</p>
            @if ($errors->any())
                <div role="alert" class="text-sm text-red-700">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="retention-policies">
            @foreach ($retentionPolicies as $policy)
                @php
                    $hasRetentionErrors = (string) old('retention_policy_id') === (string) $policy->id;
                    $statusClass = match ($policy->status) {
                        'active' => 'status-active',
                        'approved' => 'status-approved',
                        default => 'status-pending',
                    };
                @endphp
                <section aria-labelledby="retention-category-{{ $policy->id }}" class="setting-row retention-setting-row">
                    <div class="setting-row-info">
                        <h3 id="retention-category-{{ $policy->id }}" class="setting-row-label">{{ config('retention.categories.'.$policy->category.'.label', $policy->category) }}</h3>
                        <p id="retention-description-{{ $policy->id }}" class="setting-row-desc">{{ config('retention.categories.'.$policy->category.'.description') }}</p>
                        <div class="retention-status">
                            <span class="status-pill {{ $statusClass }}">{{ $policy->status_label }}</span>
                            <span class="global-info-subvalue">Last updated: {{ $policy->updated_at?->format('M d, Y') ?? 'Not recorded' }}</span>
                        </div>
                        @if ($policy->approved_at)
                            <p class="setting-row-desc retention-approval-summary">Approval reference: {{ $policy->approval_reference }}<br>
                                Policy effective from: {{ $policy->effective_from?->format('M d, Y') ?? 'Not configured' }}<br>
                                Approved: {{ $policy->approved_at->format('M d, Y') }} · Approver account #{{ $policy->approved_by }}</p>
                        @endif
                    </div>
                    <div class="retention-period-controls">
                        <div class="global-form-group retention-counting-field" data-global-field>
                            <label class="global-form-label" for="retention-rule-{{ $policy->id }}">Count from <span class="required-mark">*</span></label>
                            <select id="retention-rule-{{ $policy->id }}" name="counting_rule" form="retention-period-{{ $policy->id }}" class="form-select-custom js-custom-select" data-field-label="Counting rule" required>
                                <option value="">Select a counting rule</option>
                                @foreach ($policy->countingRules() as $rule => $label)
                                    <option value="{{ $rule }}" @selected(($hasRetentionErrors ? old('counting_rule', $policy->counting_rule) : $policy->counting_rule) === $rule)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if (config('retention.categories.'.$policy->category.'.counting_note'))
                                <p class="setting-note">{{ config('retention.categories.'.$policy->category.'.counting_note') }}</p>
                            @endif
                            <div class="global-field-error" data-error-for="retention-rule-{{ $policy->id }}" aria-live="polite" aria-hidden="true"></div>
                        </div>
                        <div class="global-form-group" data-global-field>
                            <label class="global-form-label" for="retention-duration-{{ $policy->id }}">Period <span class="required-mark">*</span></label>
                            <input id="retention-duration-{{ $policy->id }}" form="retention-period-{{ $policy->id }}" type="number"
                                name="duration" min="1" max="1200" step="1" required class="form-input-custom"
                                aria-describedby="retention-description-{{ $policy->id }}"
                                value="{{ $hasRetentionErrors ? old('duration', $policy->duration) : $policy->duration }}">
                        </div>
                        <div class="global-form-group" data-global-field>
                            <label class="global-form-label" for="retention-unit-{{ $policy->id }}">Unit <span class="required-mark">*</span></label>
                            <select id="retention-unit-{{ $policy->id }}" form="retention-period-{{ $policy->id }}" name="unit" class="form-select-custom js-custom-select" aria-describedby="retention-description-{{ $policy->id }}" required>
                                <option value="days" @selected(($hasRetentionErrors ? old('unit', $policy->unit) : $policy->unit) === 'days')>Days</option>
                                <option value="weeks" @selected(($hasRetentionErrors ? old('unit', $policy->unit) : $policy->unit) === 'weeks')>Weeks</option>
                                <option value="months" @selected(($hasRetentionErrors ? old('unit', $policy->unit) : $policy->unit) === 'months')>Months</option>
                                <option value="years" @selected(($hasRetentionErrors ? old('unit', $policy->unit) : $policy->unit) === 'years')>Years</option>
                            </select>
                        </div>
                        <button type="submit" form="retention-period-{{ $policy->id }}" class="ui-btn ui-btn-secondary retention-save-btn"
                            aria-label="Save period for {{ config('retention.categories.'.$policy->category.'.label', $policy->category) }}">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            Save Period
                        </button>
                    </div>
                    @if ($canManageRetention && $policy->status === 'provisional' && $policy->hasConfiguredRule())
                        <div class="retention-approval-panel" aria-labelledby="retention-approval-title-{{ $policy->id }}">
                            <h4 class="setting-row-label" id="retention-approval-title-{{ $policy->id }}">Policy approval</h4>
                            <p class="setting-row-desc">Record the clinic's documented approval for the saved policy settings.</p>
                            <div class="retention-approval-controls">
                                <div class="global-form-group" data-global-field>
                                    <label class="global-form-label" for="retention-reference-{{ $policy->id }}">Documented approval reference <span class="required-mark">*</span></label>
                                    <div class="global-form-textarea-wrap">
                                        <textarea id="retention-reference-{{ $policy->id }}" form="retention-approve-{{ $policy->id }}" name="approval_reference" class="form-input-custom global-form-textarea" rows="3" minlength="10" maxlength="2000" data-char-limit="2000" placeholder="Reference number or title of the approved clinic policy" required>{{ $hasRetentionErrors ? old('approval_reference') : '' }}</textarea>
                                    </div>
                                    <div class="global-field-error" data-error-for="retention-reference-{{ $policy->id }}" aria-live="polite" aria-hidden="true"></div>
                                </div>
                                <div class="global-form-group" data-global-field>
                                    <label class="global-form-label" for="retention-effective-{{ $policy->id }}">Policy effective from <span class="required-mark">*</span></label>
                                    <div class="fp-date-input-wrap">
                                        <input id="retention-effective-{{ $policy->id }}" form="retention-approve-{{ $policy->id }}" name="effective_from" type="text" class="form-input-custom fp-date-input js-flatpickr-date" value="{{ $hasRetentionErrors ? old('effective_from') : '' }}" data-flatpickr-append-to-body data-field-label="Effective date" data-validation-rule="strictIsoDate" placeholder="Select effective date" required readonly>
                                        <i class="fa-regular fa-calendar fp-date-icon" aria-hidden="true"></i>
                                    </div>
                                    <div class="global-field-error" data-error-for="retention-effective-{{ $policy->id }}" aria-live="polite" aria-hidden="true"></div>
                                </div>
                            </div>
                            <div class="retention-actions">
                                <button type="submit" form="retention-approve-{{ $policy->id }}" class="ui-btn ui-btn-primary"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i> Record Approval</button>
                            </div>
                        </div>
                    @elseif ($canManageRetention && $policy->status === 'approved')
                        <div class="retention-actions">
                            <button type="submit" form="retention-activate-{{ $policy->id }}" class="ui-btn ui-btn-primary">Activate for Review</button>
                        </div>
                    @endif
                    @if ($policy->status === 'active')
                        <div class="retention-actions">
                            <button type="submit" form="retention-deactivate-{{ $policy->id }}" class="ui-btn ui-btn-secondary">Deactivate Policy</button>
                        </div>
                    @endif
                </section>
            @endforeach
            </div>
        </div>
    </div>
</div>
