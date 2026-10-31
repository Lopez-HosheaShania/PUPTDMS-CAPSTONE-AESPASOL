<section class="table-card mb-6" aria-labelledby="continuity-current-dentist-title">
    <div class="card-header">
        <div class="card-header-left">
            <span class="card-header-icon" aria-hidden="true"><i class="fa-solid fa-user-doctor"></i></span>
            <div class="global-info-copy">
                <h2 id="continuity-current-dentist-title" class="card-title">Current Clinic Dentist</h2>
                <p class="card-subtitle">Changes require an approved handover.</p>
            </div>
        </div>
    </div>
    <div class="card-body dt-current-dentist-body">
        @if ($clinicAssignment->dentist)
            <div class="global-info-grid global-info-grid-2 dt-current-dentist-summary">
                <div class="global-info-item">
                    <span class="patient-avatar" data-patient-avatar
                        data-patient-name="{{ $clinicAssignment->dentist->name }}"
                        aria-hidden="true">{{ mb_substr($clinicAssignment->dentist->name, 0, 1) }}</span>
                    <div class="global-info-copy">
                        <span
                            class="global-info-label">{{ $currentPlan?->status === 'active' ? 'Temporary covering dentist' : 'Current dentist' }}</span>
                        <strong class="global-info-value"
                            data-current-clinic-dentist>{{ $clinicAssignment->dentist->name }}</strong>
                        <span class="global-info-subvalue">{{ $clinicAssignment->dentist->email }}</span>
                    </div>
                </div>
                <div class="global-info-item">
                    <div class="global-info-copy">
                        <span class="global-info-label">Clinic access</span>
                        <div class="global-info-value">
                            <span
                                class="status-pill {{ $currentDentistAvailable ? 'status-active' : 'status-pending' }}">{{ $currentDentistAvailable ? 'Active' : 'Unavailable' }}</span>
                        </div>
                        <span
                            class="global-info-subvalue">{{ !$currentDentistAvailable ? 'Review dentist access and the handover plan.' : ($currentPlan ? 'Handover plan in progress.' : 'No open handover plan.') }}</span>
                    </div>
                </div>
            </div>
            <div class="dt-current-dentist-actions">
                @if ($currentPlan)
                    <a class="ui-btn ui-btn-secondary" href="{{ route($routeNames['show'], $currentPlan) }}">View
                        Handover Plan</a>
                @elseif (auth()->user()->hasPermission('create_dentist_transitions') ||
                        auth()->user()->hasPermission('manage_dentist_accounts'))
                    <x-button :href="route($routeNames['create'])" variant="primary" icon="fa-solid fa-arrows-rotate">
                        Create Transition
                    </x-button>
                @endif
                <p class="global-info-subvalue">Previous dentist accounts and completed records are preserved.</p>
            </div>
        @else
            <div class="global-confirm-alert" role="status">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <div>
                    <p>Set the current clinic dentist</p><span>Choose the clinic's current dentist. Existing records
                        keep their original dentist.</span>
                </div>
            </div>
            @if (auth()->user()->hasPermission('update_dentist_transitions') ||
                    auth()->user()->hasPermission('manage_dentist_accounts'))
                <form method="POST" action="{{ route('admin.dentist-transitions.assign-current') }}"
                    class="dt-current-dentist-setup" data-global-selects data-global-validation>
                    @csrf
                    @method('PUT')
                    <div class="global-form-group" data-global-field>
                        <label for="current-clinic-dentist" class="global-form-label">Current dentist <span
                                class="required-mark">*</span></label>
                        <select id="current-clinic-dentist" name="dentist_id"
                            class="form-select-custom js-custom-select" data-custom-select-floating required>
                            <option value="">Select the current dentist</option>
                            @foreach ($availableDentists as $dentist)
                                <option value="{{ $dentist->id }}" @selected(old('dentist_id') == $dentist->id)>{{ $dentist->name }}
                                    — {{ $dentist->email }}</option>
                            @endforeach
                        </select>
                        @error('dentist_id')
                            <p class="global-info-subvalue">{{ $message }}</p>
                        @enderror
                        @if ($availableDentists->isEmpty())
                            <p class="global-info-subvalue">No available dentist. Check User Management.</p>
                        @endif
                    </div>
                    <x-button type="submit" variant="primary" :disabled="$availableDentists->isEmpty()">
                        Set Current Dentist
                    </x-button>
                </form>
            @endif
        @endif
    </div>
</section>
