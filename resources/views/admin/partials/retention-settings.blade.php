<div id="tab-retention" class="settings-section {{ $defaultSettingsTab === 'retention' ? 'active' : '' }}">
    <div class="section-card">
        <div class="section-card-hdr">
            <div class="section-card-hdr-left">
                <i class="fa-solid fa-shield-halved text-[#8B0000]" aria-hidden="true"></i>
                <h2 class="font-bold text-gray-800 text-sm">Data Retention</h2>
            </div>
        </div>
        <div class="section-card-body">
            <p class="setting-row-desc retention-intro">Set how long each type of record should be kept. Saving a period does not automatically archive or delete records.</p>
            @if ($errors->any())
                <div role="alert" class="text-sm text-red-700">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @foreach ($retentionPolicies as $policy)
                @php($hasRetentionErrors = (string) old('retention_policy_id') === (string) $policy->id)
                <section aria-labelledby="retention-category-{{ $policy->id }}" class="setting-row retention-setting-row">
                    <div class="setting-row-info">
                        <h3 id="retention-category-{{ $policy->id }}" class="setting-row-label">{{ config('retention.categories.'.$policy->category.'.label', $policy->category) }}</h3>
                        <p id="retention-description-{{ $policy->id }}" class="setting-row-desc">{{ config('retention.categories.'.$policy->category.'.description') }}</p>
                    </div>
                    <div class="retention-period-controls">
                        <div class="retention-period-field">
                            <label class="form-label" for="retention-duration-{{ $policy->id }}">Retention period</label>
                            <input id="retention-duration-{{ $policy->id }}" form="retention-period-{{ $policy->id }}" type="number"
                                name="duration" min="1" max="1200" step="1" required class="form-ctrl"
                                aria-describedby="retention-description-{{ $policy->id }}"
                                value="{{ $hasRetentionErrors ? old('duration', $policy->duration) : $policy->duration }}">
                        </div>
                        <div class="retention-unit-field">
                            <label class="form-label" for="retention-unit-{{ $policy->id }}">Unit</label>
                            <select id="retention-unit-{{ $policy->id }}" form="retention-period-{{ $policy->id }}" name="unit" class="form-ctrl form-sel" aria-describedby="retention-description-{{ $policy->id }}" required>
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
                </section>
            @endforeach
        </div>
    </div>
</div>
