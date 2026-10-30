@extends('layouts.app')

@section('layout-role', $layoutRole ?? 'dentist')

@section('hide-sidebar')
@endsection

@section('hide-footer')
@endsection

@section('title', 'Patient Odontogram')

@section('styles')
    @vite('resources/css/pages/dentist/odontogram.css')
@endsection

@section('content')

    @php
        use Carbon\Carbon;

        $existingAppointmentMode = (bool) ($existingAppointmentMode ?? false);
        $savedVisitEditMode = (bool) ($savedVisitEditMode ?? false);
        $patientName = $patient->name ?? 'Unknown Patient';
        $patientAvatarUrl = !empty($patient?->profile_image)
            ? asset('storage/' . $patient->profile_image)
            : (!empty($patient?->user?->profile_image)
                ? asset('storage/' . $patient->user->profile_image)
                : '');

        $appointmentDateValue = $existingAppointmentMode
            ? data_get($existingAppointmentDraft ?? [], 'appointment_date')
            : $appointment?->appointment_date;

        $appointmentTimeValue = $existingAppointmentMode
            ? data_get($existingAppointmentDraft ?? [], 'appointment_time')
            : $appointment?->appointment_time;

        $formattedAppointmentDate = filled($appointmentDateValue)
            ? Carbon::parse($appointmentDateValue)->format('M d, Y')
            : '—';

        $formattedAppointmentTime = filled($appointmentTimeValue)
            ? Carbon::parse($appointmentTimeValue)->format('h:i A')
            : '—';

        $currentServiceType = $existingAppointmentMode
            ? data_get($existingAppointmentDraft ?? [], 'service_type', '')
            : $appointment?->service_type_name ?? '';
        $serviceTypeLabel = $currentServiceType ?: 'No service type';

        $serviceTypeLower = strtolower(trim((string) $serviceTypeLabel));

        $serviceBadgeClass = 'service-badge-default';

        if (str_contains($serviceTypeLower, 'surgery')) {
            $serviceBadgeClass = 'service-badge-surgery';
        } elseif (str_contains($serviceTypeLower, 'check')) {
            $serviceBadgeClass = 'service-badge-checkup';
        } elseif (str_contains($serviceTypeLower, 'whiten')) {
            $serviceBadgeClass = 'service-badge-whitening';
        } elseif (str_contains($serviceTypeLower, 'extrac')) {
            $serviceBadgeClass = 'service-badge-extraction';
        }

        $allowsProcedureCompletionWithoutOdontogramChanges =
            strcasecmp(trim((string) $currentServiceType), 'Oral Prophylaxis') === 0 ||
            strcasecmp(trim((string) $currentServiceType), 'Oral Check-Up') === 0;

        $entrySource = request()->query('from');
        $isWalkInMode = $entrySource === 'walk-in';

        $entryContextLabel = $existingAppointmentMode
            ? 'Existing Appointment'
            : ($savedVisitEditMode
                ? 'Saved Visit Record'
                : ($isWalkInMode
                    ? 'Walk-in Visit'
                    : null));

        $entryContextMessage = $existingAppointmentMode
            ? 'This odontogram is for an existing appointment. Saving it will store the visit as completed.'
            : ($savedVisitEditMode
                ? 'You are editing a previously saved visit. Any changes here will update the saved odontogram and clinical notes.'
                : ($isWalkInMode
                    ? 'This odontogram is being created for a walk-in visit.'
                    : null));

        $pageEyebrow = $savedVisitEditMode ? 'Saved Dental Record Editor' : 'Dental Procedure Workspace';
        $pageTitle = $savedVisitEditMode ? 'Edit Saved Odontogram' : 'Patient Odontogram';
        $pageSubtitle = '2D / 3D Treatment &amp; Condition Mapping';

        $manualTimeIn = old('procedure_started_at', $procedure?->procedure_started_at?->format('H:i') ?? '');
        $savedDurationSeconds = $procedure?->procedure_duration_seconds;
        $manualDuration = old(
            'procedure_duration_hms',
            $savedDurationSeconds !== null
                ? sprintf(
                    '%02d:%02d:%02d',
                    intdiv($savedDurationSeconds, 3600),
                    intdiv($savedDurationSeconds % 3600, 60),
                    $savedDurationSeconds % 60,
                )
                : '',
        );
        $discardProcedureTitle = $existingAppointmentMode
            ? 'Discard appointment entry?'
            : ($savedVisitEditMode
                ? 'Discard saved visit changes?'
                : 'Discard procedure?');

        $discardProcedureSubtitle = $existingAppointmentMode
            ? 'The current existing appointment entry has not been saved.'
            : ($savedVisitEditMode
                ? 'The current saved visit changes have not been saved.'
                : 'The current procedure has not been completed.');

        $discardProcedureMessage = $existingAppointmentMode
            ? 'Going back will discard this existing appointment entry and return you to the Add Existing Appointment form.'
            : ($savedVisitEditMode
                ? 'Going back will discard the current saved visit changes and return you to the patient profile.'
                : 'Going back will discard the current procedure progress and return you to the previous page.');
    @endphp

    <main id="mainContent" class="odontogram-page">

        <div id="odontogramDockLayout" class="odontogram-dock-layout">

            <section class="odontogram-dock-main">
                <div class="page-enter">

                    <div class="odontogram-hero">
                        <div class="odontogram-hero-main">

                            @if ($entryContextLabel)
                                <div class="global-info-profile">

                                    <span class="global-info-profile-icon">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </span>

                                    <div class="global-info-profile-copy">

                                        <div class="global-info-group">

                                            <strong class="global-info-profile-name">
                                                {{ $entryContextLabel }}
                                            </strong>

                                            @if ($savedVisitEditMode)
                                                <span class="global-info-pill">
                                                    <i class="fa-solid fa-floppy-disk"></i>
                                                    Saved Record
                                                </span>
                                            @endif

                                        </div>

                                        <span class="global-info-subvalue">
                                            {{ $entryContextMessage }}
                                        </span>

                                    </div>

                                </div>
                            @endif

                            <div class="card hero-title-card">

                                <div class="hero-title-icon">
                                    <i class="fa-solid fa-tooth"></i>
                                </div>

                                <div class="odontogram-hero-copy">
                                    <p class="hero-eyebrow">{{ $pageEyebrow }}</p>

                                    <h1 class="hero-title">
                                        {{ $pageTitle }}
                                    </h1>

                                    <p class="hero-subtitle">
                                        {!! $pageSubtitle !!}
                                    </p>

                                </div>

                                <button type="button" id="cancelProcedureBtn"
                                    class="ui-btn {{ $savedVisitEditMode ? 'ui-btn-primary' : 'ui-btn-danger' }}">

                                    <i class="fa-solid fa-arrow-left"></i>

                                    <span>
                                        Cancel Odontogram
                                    </span>
                                </button>

                            </div>

                            <div class="card odontogram-patient-card">
                                <div class="odontogram-patient-main">
                                    <span class="patient-avatar patient-avatar-lg" data-patient-avatar
                                        data-patient-name="{{ $patientName }}" data-patient-url="{{ $patientAvatarUrl }}"
                                        aria-label="{{ $patientName }}">
                                    </span>

                                    <div class="odontogram-patient-copy">
                                        <span class="odontogram-patient-eyebrow">
                                            Patient
                                        </span>

                                        <h2 class="odontogram-patient-name" data-patient-name>
                                            {{ $patientName }}
                                        </h2>

                                        <span class="service-badge {{ $serviceBadgeClass }} odontogram-service-badge">
                                            <i class="fa-solid fa-tooth"></i>
                                            {{ $serviceTypeLabel }}
                                        </span>
                                    </div>
                                </div>

                                <div class="odontogram-appointment-meta">
                                    <div class="odontogram-appointment-meta-item">
                                        <span class="odontogram-appointment-meta-icon">
                                            <i class="fa-regular fa-calendar"></i>
                                        </span>

                                        <div>
                                            <span class="odontogram-appointment-meta-label">
                                                Appointment Date
                                            </span>

                                            <strong class="odontogram-appointment-meta-value">
                                                {{ $formattedAppointmentDate }}
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="odontogram-appointment-meta-item">
                                        <span class="odontogram-appointment-meta-icon">
                                            <i class="fa-regular fa-clock"></i>
                                        </span>

                                        <div>
                                            <span class="odontogram-appointment-meta-label">
                                                Appointment Time
                                            </span>

                                            <strong class="odontogram-appointment-meta-value">
                                                {{ $formattedAppointmentTime }}
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="odontogram-appointment-meta-item odontogram-procedure-timing-item">
                                        @foreach (['procedure_started_at' => ['Time In', $manualTimeIn]] as $field => [$label, $value])
                                            <div class="odontogram-procedure-duration-copy" data-global-field>
                                                <label for="{{ $field }}" class="odontogram-appointment-meta-label">
                                                    {{ $label }} <span class="required-mark">*</span>
                                                </label>
                                                <input type="text" id="{{ $field }}" name="{{ $field }}"
                                                    class="form-input-custom js-flatpickr-time" value="{{ $value }}"
                                                    required readonly placeholder="HH:MM" autocomplete="off"
                                                    data-time-minute-increment="1" data-field-label="{{ $label }}"
                                                    data-required-message="Please enter {{ $label }}."
                                                    aria-describedby="{{ $field }}_error">
                                                <div id="{{ $field }}_error" class="global-field-error"
                                                    data-error-for="{{ $field }}" aria-hidden="true"></div>
                                            </div>
                                        @endforeach
                                        <div class="odontogram-procedure-duration-copy" data-global-field>
                                            <label for="procedureDurationInput"
                                                class="odontogram-appointment-meta-label">Procedure Duration <span
                                                    class="required-mark">*</span></label>
                                            <input type="text" id="procedureDurationInput" name="procedure_duration_hms"
                                                class="form-input-custom" value="{{ $manualDuration }}"
                                                placeholder="HH:MM:SS" required inputmode="numeric" autocomplete="off"
                                                pattern="[0-9]{2}:[0-5][0-9]:[0-5][0-9]"
                                                data-field-label="Procedure Duration"
                                                data-required-message="Please enter Procedure Duration."
                                                data-pattern-message="Enter a valid duration in HH:MM:SS format, e.g. 00:30:00."
                                                aria-describedby="procedure_duration_hms_error">
                                            <div id="procedure_duration_hms_error" class="global-field-error"
                                                data-error-for="procedure_duration_hms" aria-hidden="true"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form id="procedureDiscardForm" class="hidden" data-discard-form
                            data-discard-handler="discardProcedureBeforeLeave"
                            data-discard-title="{{ $discardProcedureTitle }}"
                            data-discard-subtitle="{{ $discardProcedureSubtitle }}"
                            data-discard-message="{{ $discardProcedureMessage }}">
                            <input type="hidden" name="context" value="{{ $discardProcedureContext ?? 'appointments' }}">
                            <input type="hidden" name="appointment_id" value="{{ $appointment->id ?? '' }}">
                            <input type="hidden" name="patient_id" value="{{ $patient->id ?? '' }}">
                            <input type="hidden" id="procedureDiscardDirtyFlag" name="dirty_flag" value="0">
                        </form>

                        <div class="card odontogram-toolbar" id="odontogramToolbar">
                            <div class="toolbar-group toolbar-group-tools">
                                <span class="toolbar-label">Selection Tools</span>
                                <div class="toolbar-actions">
                                    <button type="button" id="clearSelectionBtn" class="ui-icon-btn neutral"
                                        data-tooltip="Clear Selection" data-tooltip-tone="neutral"
                                        aria-label="Clear Selection" disabled>
                                        <i class="fa-solid fa-arrow-pointer"></i>
                                    </button>

                                    <button type="button" id="undoBtn" class="ui-icon-btn neutral"
                                        data-tooltip="Undo" data-tooltip-tone="neutral" aria-label="Undo" disabled>
                                        <i class="fa-solid fa-undo"></i>
                                    </button>

                                    <button type="button" id="redoBtn" class="ui-icon-btn neutral"
                                        data-tooltip="Redo" data-tooltip-tone="neutral" aria-label="Redo" disabled>
                                        <i class="fa-solid fa-rotate-right"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="toolbar-group toolbar-group-view">
                                <span class="toolbar-label">View Mode</span>
                                <div class="toolbar-actions">
                                    <button type="button" id="view2dBtn" class="ui-icon-btn neutral active"
                                        data-tooltip="2D View" data-tooltip-tone="neutral" aria-label="2D View">
                                        <i class="fa-regular fa-image"></i>
                                    </button>

                                    <button type="button" id="view3dBtn" class="ui-icon-btn neutral"
                                        data-tooltip="3D View" data-tooltip-tone="neutral" aria-label="3D View">
                                        <i class="fa-solid fa-cube"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="odontogramToolbarSentinel" class="odontogram-toolbar-sentinel" aria-hidden="true">
                        </div>
                    </div>

                    <div class="odontogram-workspace-grid">
                        <div class="card odontogram-left-panel">

                            <div class="card-body left-view-shell">
                                <div class="odontogram-guide-row">
                                    <div class="global-info-group">
                                        <span class="global-info-icon status-all">
                                            <i class="fa-solid fa-tooth"></i>
                                        </span>

                                        <div class="global-info-copy">
                                            <span class="global-info-label">
                                                Tooth Selection
                                            </span>

                                            <span id="viewInstructionText" class="global-info-subvalue">
                                                Select a surface to begin treatment
                                            </span>
                                        </div>
                                    </div>

                                    <div class="global-info-pill">
                                        <i class="fa-solid fa-tooth"></i>

                                        <span id="toothHoverLabel">
                                            Select a tooth
                                        </span>
                                    </div>
                                </div>

                                <div id="odontogram2DPanel" class="mode-panel active">

                                    <div class="odontogram2d-shell custom-scrollbar">
                                        <div id="odontogram2DBoard" class="odontogram-board"></div>
                                    </div>
                                </div>

                                <div id="odontogram3DPanel" class="mode-panel">
                                    <div class="three-mouse-guide" aria-label="3D model mouse controls">
                                        <div class="three-mouse-guide-item">
                                            <span class="mouse-button-key">L</span>
                                            <span><strong>Left mouse:</strong> Click a surface; drag to rotate</span>
                                        </div>
                                        <div class="three-mouse-guide-item">
                                            <span class="mouse-button-key">R</span>
                                            <span><strong>Right mouse:</strong> Move the model</span>
                                        </div>
                                        <div class="three-mouse-guide-item">
                                            <span class="mouse-button-key mouse-wheel-key"><i
                                                    class="fa-solid fa-arrows-up-down"></i></span>
                                            <span><strong>Scroll:</strong> Zoom in/out</span>
                                        </div>
                                    </div>
                                    <div id="canvas-container">
                                        <div id="loadingOverlay" class="odontogram-loading" role="status"
                                            aria-live="polite" aria-label="Generating 3D odontogram model.">

                                            <div aria-hidden="true">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </div>

                                            <p>
                                                Generating 3D model...
                                            </p>

                                        </div>

                                        <div id="toothTooltip" class="tooth-tooltip">
                                            <div id="toothTooltipContent"></div>
                                        </div>

                                        <div id="surfacePicker3D" class="hidden odontogram-surface-picker">

                                            <button type="button" id="surfacePickerDragHandle"
                                                class="ui-icon-btn neutral surface-picker-drag-handle"
                                                title="Drag to move surface picker" aria-label="Move surface picker">
                                                <i class="fa-solid fa-up-down-left-right"></i>
                                            </button>

                                            <div class="odontogram-surface-picker-head">
                                                <div class="odontogram-surface-picker-copy">
                                                    <p class="global-form-label">
                                                        3D Surface Picker
                                                    </p>
                                                    <h4 id="surfacePickerToothLabel"
                                                        class="odontogram-surface-picker-title">No
                                                        tooth
                                                        selected</h4>
                                                    <p id="surfacePickerHelperText" class="ui-muted-text">
                                                        Click a surface on the model, or use the buttons below.
                                                    </p>
                                                </div>
                                                <div class="odontogram-surface-picker-actions">
                                                    <span class="global-info-icon crimson">
                                                        <i class="fa-solid fa-cube"></i>
                                                    </span>
                                                    <button type="button" id="close3DSurfacePickerBtn"
                                                        class="ui-icon-btn neutral" title="Hide surface picker"
                                                        aria-label="Hide surface picker">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="section-block odontogram-surface-grid-shell">
                                                <div class="odontogram-surface-grid">
                                                    <div></div>
                                                    <button type="button" data-surface="top" class="surface-picker-btn">
                                                        Top
                                                    </button>
                                                    <div></div>

                                                    <button type="button" data-surface="left"
                                                        class="surface-picker-btn">
                                                        Left
                                                    </button>
                                                    <button type="button" data-surface="center"
                                                        class="surface-picker-btn surface-picker-btn-center">
                                                        Center
                                                    </button>
                                                    <button type="button" data-surface="right"
                                                        class="surface-picker-btn">
                                                        Right
                                                    </button>

                                                    <div></div>
                                                    <button type="button" data-surface="bottom"
                                                        class="surface-picker-btn">
                                                        Bottom
                                                    </button>
                                                    <div></div>
                                                </div>
                                            </div>

                                            <div class="odontogram-surface-picker-footer">
                                                <p class="ui-muted-text">
                                                    Choose a surface, select a treatment, then apply.
                                                </p>
                                                <button type="button" id="reset3DViewBtn"
                                                    class="ui-btn ui-btn-secondary ui-btn-sm">
                                                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                                                    Full View
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="odontogram-clinical-section">
                                <div class="odontogram-clinical-header">
                                    <div>
                                        <p class="right-section-eyebrow">
                                            Clinical Documentation
                                        </p>

                                        <h3 class="right-section-title">
                                            Procedure Notes
                                        </h3>
                                    </div>
                                </div>

                                <div class="odontogram-clinical-grid">
                                    <div class="global-form-group">
                                        <label for="oralExaminationNotes" class="global-form-label">
                                            Oral Examination
                                            <span class="field-optional">
                                                (Optional) </span>
                                        </label>

                                        <textarea id="oralExaminationNotes" rows="4" class="form-input-custom global-form-textarea"
                                            placeholder="Record oral examination findings...">{{ old('oral_examination', $procedure?->oral_examination) }}</textarea>
                                    </div>

                                    @php
                                        $savedDiagnosis = trim((string) old('diagnosis', $procedure?->diagnosis));
                                        $diagnosisOptions = [
                                            'Dental Caries',
                                            'Gingivitis',
                                            'Periapical Abscess',
                                            'Ulceration',
                                        ];
                                        $selectedDiagnosis = in_array($savedDiagnosis, $diagnosisOptions, true)
                                            ? $savedDiagnosis
                                            : ($savedDiagnosis !== ''
                                                ? 'Others'
                                                : '');
                                        $customDiagnosis = $selectedDiagnosis === 'Others' ? $savedDiagnosis : '';
                                    @endphp
                                    <div class="global-form-group">
                                        <label for="diagnosisNotes" class="global-form-label">
                                            Diagnosis
                                            <span class="field-optional">
                                                (Optional) </span>
                                        </label>

                                        <select id="diagnosisNotes" class="form-select-custom js-custom-select"
                                            data-placeholder="Select diagnosis">
                                            <option value="" {{ $selectedDiagnosis === '' ? 'selected' : '' }}>
                                                Select
                                                diagnosis</option>
                                            @foreach ($diagnosisOptions as $diagnosisOption)
                                                <option value="{{ $diagnosisOption }}"
                                                    {{ $selectedDiagnosis === $diagnosisOption ? 'selected' : '' }}>
                                                    {{ $diagnosisOption }}
                                                </option>
                                            @endforeach
                                            <option value="Others"
                                                {{ $selectedDiagnosis === 'Others' ? 'selected' : '' }}>
                                                Others
                                            </option>
                                        </select>

                                        <input type="text" id="diagnosisCustomNotes" class="form-input-custom mt-3"
                                            placeholder="Enter custom diagnosis..." value="{{ $customDiagnosis }}"
                                            @if ($selectedDiagnosis !== 'Others') hidden @endif>
                                    </div>

                                    <div class="global-form-group">
                                        <label for="prescriptionsNotes" class="global-form-label">
                                            Prescription
                                            <span class="field-optional">
                                                (Optional)
                                            </span>
                                        </label>

                                        <textarea id="prescriptionsNotes" rows="3" class="form-input-custom global-form-textarea"
                                            placeholder="Add medication or aftercare instructions...">{{ old('prescriptions', $procedure?->prescriptions) }}</textarea>
                                    </div>

                                    <div class="global-form-group">
                                        <label for="inventoryNotes" class="global-form-label">
                                            Inventory Notes
                                            <span class="field-optional">
                                                (Optional)
                                            </span>
                                        </label>
                                        <textarea id="inventoryNotes" name="inventory_notes" rows="3" maxlength="10000"
                                            class="form-input-custom global-form-textarea"
                                            placeholder="List medicines, materials, or supplies used during this procedure...">{{ old('inventory_notes', $procedure?->inventory_notes) }}</textarea>
                                    </div>

                                    <div class="odontogram-procedure-actions">
                                        @unless ($existingAppointmentMode || $savedVisitEditMode)
                                            <button type="button" id="followUpBtn" class="ui-btn ui-btn-warning">
                                                <span>Set Follow-Up Appointment</span>
                                            </button>
                                        @endunless

                                        <button type="button" id="finishProcedureBtn" class="ui-btn ui-btn-primary">
                                            <span>
                                                {{ $existingAppointmentMode
                                                    ? 'Save Existing Appointment'
                                                    : ($savedVisitEditMode
                                                        ? 'Save Changes'
                                                        : 'Finish Procedure') }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="odontogramData" name="odontogram_data" value="[]">
                    </div>
            </section>

            <div id="legendResizeHandle" class="odontogram-legend-resizer" role="separator" aria-orientation="vertical"
                aria-label="Resize Treatment Legend panel"></div>

            <aside id="legendDrawer" class="odontogram-legend-panel">
                <div class="legend-drawer-header">
                    <div class="legend-drawer-heading">
                        <span class="legend-drawer-title-icon">
                            <i class="fa-solid fa-layer-group"></i>
                        </span>
                        <div>
                            <h3 class="legend-drawer-title">Treatment Legend</h3>
                            <p class="legend-drawer-subtitle">
                                Choose a whole tooth or a specific surface, then select a treatment.
                            </p>
                        </div>
                    </div>

                    <div class="section-block odontogram-legend-target">
                        <div class="odontogram-target-row">
                            <span class="global-form-label">
                                Target
                            </span>

                            <span id="selectedViewBadge" class="badge-pill status-all">
                                2D
                            </span>
                        </div>

                        <div class="odontogram-target-scope" role="group" aria-label="Treatment target scope">
                            <button type="button" id="wholeToothScopeBtn" class="ui-btn ui-btn-secondary"
                                aria-pressed="false">
                                <i class="fa-solid fa-tooth"></i>
                                Whole Tooth
                            </button>
                            <button type="button" id="toothSurfaceScopeBtn" class="ui-btn ui-btn-secondary active"
                                aria-pressed="true">
                                <i class="fa-solid fa-border-all"></i>
                                Tooth Surface
                            </button>
                        </div>

                        <div id="selectedToothDisplay" class="odontogram-selected-display">
                            Select a tooth surface
                        </div>

                        <p id="selectedToothName" class="odontogram-selected-name"></p>

                        <div class="odontogram-treatment-row">
                            <span class="global-form-label">
                                Treatment
                            </span>

                            <div id="selectedToothLegendList">
                                <span class="ui-muted-text">
                                    None selected
                                </span>
                            </div>
                        </div>

                        <div id="legendStatusNote" class="hidden legend-status-note odontogram-legend-status-note">
                            Select a treatment, then apply.
                        </div>

                        <div class="odontogram-target-actions">
                            <button type="button" id="applyTreatmentBtn" class="ui-btn ui-btn-primary w-full" disabled>
                                Apply
                            </button>

                            <button type="button" id="clearCurrentToothBtn" class="ui-btn ui-btn-secondary w-full"
                                disabled>
                                Clear
                            </button>
                        </div>
                    </div>

                    <span id="selectedLegendPreview" hidden>None selected</span>
                    <span id="drawerSelectedLegendPreview" hidden>None selected</span>

                    <div class="odontogram-legend-search-wrap">
                        <x-search-bar id="legendSearchInput" class="search-compact" placeholder="Search treatments…"
                            clear-label="Clear treatment search" callback="filterOdontogramLegends" :debounce="150" />
                    </div>

                    <div class="odontogram-legend-options-head">
                        <p class="global-form-label">
                            Treatment Options
                        </p>

                        <p id="legendResultCount" class="ui-muted-text">
                            0
                        </p>
                    </div>
                </div>

                <div class="legend-drawer-body custom-scrollbar">
                    <div id="legendContainer" class="odontogram-legend-list"></div>
                    <div id="legendEmptyState" class="empty-state-host"></div>
                </div>
            </aside>
        </div>
    </main>

    @unless ($existingAppointmentMode || $savedVisitEditMode)
        <x-follow-up-modal :patient-name="$patientName" :patient-avatar-url="$patientAvatarUrl" :service-type="$currentServiceType ?: '—'" :appointment-date="$formattedAppointmentDate" :appointment-time="$formattedAppointmentTime"
            :store-url="route('dentist.dentist.appointments.follow-up.store', $appointment->id)" />
    @endunless

    @unless ($existingAppointmentMode || $savedVisitEditMode)
        <x-booking.confirmed-modal id="followUpSuccessModal" eyebrow="Follow-Up Appointment" title="Follow-Up Scheduled"
            subtitle="The procedure was completed and the follow-up appointment was scheduled successfully."
            header-icon="fa-calendar-check" section-icon="fa-calendar-check" section-eyebrow="Appointment Status"
            section-title="Follow-up appointment created" section-message="The patient now has a new follow-up appointment."
            detail-label="Status" result-title="Upcoming" message-title="Scheduled appointment information"
            :is-static="true">
            <div class="global-info-grid global-info-grid-2">

                <div class="global-info-item">
                    <span class="global-info-icon status-upcoming">
                        <i class="fa-regular fa-calendar"></i>
                    </span>

                    <div class="global-info-copy">
                        <span class="global-info-label">
                            Appointment Date
                        </span>

                        <strong id="followUpSuccessDate" class="global-info-value">
                            —
                        </strong>
                    </div>
                </div>

                <div class="global-info-item">
                    <span class="global-info-icon status-upcoming">
                        <i class="fa-regular fa-clock"></i>
                    </span>

                    <div class="global-info-copy">
                        <span class="global-info-label">
                            Appointment Time
                        </span>

                        <strong id="followUpSuccessTime" class="global-info-value">
                            —
                        </strong>
                    </div>
                </div>

                <div class="global-info-item">
                    <span class="global-info-icon status-all">
                        <i class="fa-solid fa-user"></i>
                    </span>

                    <div class="global-info-copy">
                        <span class="global-info-label">
                            Patient
                        </span>

                        <strong id="followUpSuccessPatient" class="global-info-value">
                            —
                        </strong>
                    </div>
                </div>

                <div class="global-info-item">
                    <span class="global-info-icon status-all">
                        <i class="fa-solid fa-tooth"></i>
                    </span>

                    <div class="global-info-copy">
                        <span class="global-info-label">
                            Service Type
                        </span>

                        <strong id="followUpSuccessService" class="global-info-value">
                            Follow-up
                        </strong>
                    </div>
                </div>

                <div class="global-info-item global-info-item-wide">
                    <span class="global-info-icon status-all">
                        <i class="fa-regular fa-message"></i>
                    </span>

                    <div class="global-info-copy">
                        <span class="global-info-label">
                            Reason for Follow-Up
                        </span>

                        <strong id="followUpSuccessReason" class="global-info-value">
                            No reason provided.
                        </strong>
                    </div>
                </div>

            </div>

            <x-slot:footer>
                <button type="button" id="followUpSuccessBackBtn" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Appointments</span>
                </button>
            </x-slot:footer>
        </x-booking.confirmed-modal>
    @endunless

    <div id="resetTreatmentModal" class="ui-modal modal-theme-danger" aria-hidden="true" role="dialog"
        aria-modal="true" aria-labelledby="resetTreatmentModalTitle">
        <div class="ui-modal-card modal-md">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <div class="modal-copy">
                        <h2 id="resetTreatmentModalTitle" class="modal-title">
                            Reset Tooth Treatment
                        </h2>
                        <p class="modal-subtitle">
                            Remove the treatment currently assigned to this target.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div class="global-confirm-alert">
                    <i class="fa-solid fa-eraser"></i>

                    <div>
                        <strong>Reset selected treatment?</strong>
                        <span id="resetTreatmentMessage">
                            Are you sure you want to reset the treatment for this tooth?
                        </span>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" id="cancelResetTreatmentBtn" class="ui-btn ui-btn-secondary">
                    <span>Keep Treatment</span>
                </button>

                <button type="button" id="confirmResetTreatmentBtn" class="ui-btn ui-btn-danger">
                    <span>Reset Treatment</span>
                </button>
            </div>
        </div>
    </div>

    <div id="cancelProcedureModal" class="ui-modal modal-theme-danger" aria-hidden="true" role="dialog"
        aria-modal="true" aria-labelledby="cancelProcedureModalTitle">
        <div class="ui-modal-card modal-md">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>

                    <div class="modal-copy">
                        <h2 id="cancelProcedureModalTitle" class="modal-title">
                            {{ $savedVisitEditMode ? 'Cancel Edit?' : 'Cancel Procedure?' }}
                        </h2>
                        <p class="modal-subtitle">
                            {{ $savedVisitEditMode
                                ? 'Unsaved changes in this saved visit editor may be lost.'
                                : 'Unsaved changes in this procedure session may be lost.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div class="global-confirm-alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>

                    <div>
                        <strong>{{ $savedVisitEditMode ? 'Leave this edit?' : 'Leave this procedure?' }}</strong>
                        <span>
                            {{ $savedVisitEditMode
                                ? 'Are you sure you want to cancel this edit? Any unsaved changes in this saved visit may be
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    lost.'
                                : 'Are you sure you want to cancel this procedure? Any unsaved progress in this session may be
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    lost.' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" id="dismissCancelProcedureBtn" class="ui-btn ui-btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>{{ $savedVisitEditMode ? 'Continue Editing' : 'Continue Procedure' }}</span>
                </button>

                <button type="button" id="confirmCancelProcedureBtn"
                    class="ui-btn {{ $savedVisitEditMode ? 'ui-btn-primary' : 'ui-btn-danger' }}">
                    <i class="fa-solid fa-ban"></i>
                    <span>{{ $savedVisitEditMode ? 'Cancel Edit' : 'Cancel Procedure' }}</span>
                </button>
            </div>
        </div>
    </div>

    @if ($savedVisitEditMode)
        <div id="correctionReasonModal" class="ui-modal modal-theme-warning" aria-hidden="true" role="dialog"
            aria-modal="true" aria-labelledby="correctionReasonModalTitle">

            <div class="ui-modal-card modal-md">

                <div class="modal-hd">
                    <div class="modal-heading">

                        <div class="modal-icon">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>

                        <div class="modal-copy">
                            <h2 id="correctionReasonModalTitle" class="modal-title">
                                Reason for Correction
                            </h2>

                            <p class="modal-subtitle">
                                Document why this previously saved odontogram is being corrected.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="modal-bd">

                    <div class="global-confirm-alert">
                        <i class="fa-solid fa-triangle-exclamation"></i>

                        <div>
                            <strong>Correction will be recorded</strong>
                            <span>
                                The original version will remain in the revision history together with your identity, role,
                                date, and reason for this change.
                            </span>
                        </div>
                    </div>

                    <div class="global-form-group mt-4" data-global-field>
                        <label for="correctionReasonCategory" class="global-form-label">
                            Correction Reason
                            <span class="required-mark">*</span>
                        </label>

                        <select id="correctionReasonCategory" class="form-select-custom js-custom-select"
                            data-placeholder="Select a reason" data-field-label="Correction Reason">
                            <option value="">
                                Select a reason
                            </option>

                            @foreach ($revisionReasonTypes ?? [] as $reasonType)
                                <option value="{{ $reasonType->id }}" data-code="{{ $reasonType->code }}"
                                    data-requires-detail="{{ $reasonType->requires_detail ? '1' : '0' }}">
                                    {{ $reasonType->label }}
                                </option>
                            @endforeach
                        </select>

                        <div class="global-field-error" data-error-for="correctionReasonCategory" aria-hidden="true">
                        </div>
                    </div>

                    <div id="correctionReasonOtherField" class="global-form-group mt-4" data-global-field hidden>

                        <label for="correctionReasonOther" class="global-form-label">
                            Specify Reason
                            <span class="required-mark">*</span>
                        </label>

                        <input type="text" id="correctionReasonOther" class="form-input-custom" maxlength="255"
                            data-field-label="Specify Reason" placeholder="Specify the correction reason...">

                        <div class="global-field-error" data-error-for="correctionReasonOther" aria-hidden="true">
                        </div>

                    </div>

                    <div class="global-form-group mt-4" data-global-field>
                        <div class="global-label-row">
                            <label for="correctionReasonNotes" class="global-form-label">
                                Correction Notes
                                <span class="field-optional">(Optional)</span>
                            </label>

                            <span id="correctionReasonNotesCounter" class="char-counter">
                                0 / 2000 characters
                            </span>
                        </div>

                        <textarea id="correctionReasonNotes" rows="4" maxlength="2000" data-char-limit="2000"
                            data-char-counter="#correctionReasonNotesCounter" class="form-input-custom global-form-textarea"
                            placeholder="Add additional details about this correction..."></textarea>
                    </div>

                </div>

                <div class="modal-ft">

                    <button type="button" id="dismissCorrectionReasonBtn" class="ui-btn ui-btn-secondary">
                        <span>Continue Editing</span>
                    </button>

                    <button type="button" id="confirmCorrectionReasonBtn" class="ui-btn ui-btn-warning">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Correction</span>
                    </button>

                </div>

            </div>
        </div>
    @endif

    <div id="finishProcedureModal" class="ui-modal modal-theme-success" aria-hidden="true" role="dialog"
        aria-modal="true" aria-labelledby="finishProcedureModalTitle">
        <div class="ui-modal-card modal-md">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i id="finishProcedureModalIcon" class="fa-solid fa-clipboard-check"></i>
                    </div>

                    <div class="modal-copy">
                        <h2 id="finishProcedureModalTitle" class="modal-title">
                            Procedure Completed!
                        </h2>
                        <p id="finishProcedureModalMessage" class="modal-subtitle"></p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div class="global-confirm-alert">
                    <i class="fa-solid fa-circle-check"></i>

                    <div>
                        <strong id="finishProcedureModalAlertTitle">
                            Procedure status
                        </strong>
                        <span id="finishProcedureModalAlertMessage">
                            Review the message above before continuing.
                        </span>
                    </div>
                </div>
            </div>

            <div id="finishProcedureConfirmActions" class="modal-ft hidden">
                <button type="button" id="dismissFinishProcedureBtn" class="ui-btn ui-btn-secondary">
                    <span>Back to Review</span>
                </button>

                <button type="button" id="confirmFinishProcedureBtn" class="ui-btn ui-btn-warning">
                    <span>Finish Procedure</span>
                </button>
            </div>

            <div id="finishProcedureResultActions" class="modal-ft">
                <button type="button" id="finishProcedureModalActionBtn" class="ui-btn ui-btn-primary">
                    <span>Back to Appointments</span>
                </button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @unless ($existingAppointmentMode || $savedVisitEditMode)
        @include('components.appointment-calendar-script', [
            'mode' => 'booking',
        
            'calendarContainerId' => 'followUpCalendarGrid',
            'calGridId' => 'followUpCalGrid',
            'calMonthLabelId' => 'followUpCalMonthLabel',
            'calYearLabelId' => 'followUpCalYearLabel',
        
            'dateInputId' => 'followup_appointment_date',
            'timeInputId' => 'followup_appointment_time',
        
            'dateBannerId' => 'followUpDateBanner',
        
            'slotPlaceholderId' => 'followUpSlotPlaceholder',
            'slotContainerId' => 'followUpSlotContainer',
            'slotGridId' => 'followUpSlotGrid',
        
            'selectedSlotDisplayId' => 'followUpSelectedSlotDisplay',
            'selectedSlotTextId' => 'followUpSelectedSlotText',
        
            'clearSlotButtonId' => 'followUpClearTimeBtn',
        
            'datePillId' => null,
        
            'dateErrorId' => 'followUpDateError',
            'timeErrorId' => 'followUpTimeError',
        
            'calendarWrapSelector' => '#followUpCalendarWrap',
            'slotsWrapSelector' => '#followUpTimeWrap',
        
            'slotEndpoint' => route('dentist.appointment.slots'),
        
            'scheduleRules' => $schedules ?? [],
            'blockedDates' => $blockedDates ?? [],
            'appointmentCountsPerDay' => $appointmentCountsPerDay ?? [],
            'philippineHolidays' => $philippineHolidays ?? [],
            'personalAppointments' => [],
        
            'disallowToday' => true,
            'allowToggleOffDate' => true,
            'useDynamicScheduleRules' => true,
            'renderStyle' => 'dentist',
        ])
    @endunless
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', function() {
            const cancelProcedureBtn = document.getElementById('cancelProcedureBtn');
            const diagnosisSelect = document.getElementById('diagnosisNotes');
            const diagnosisCustomInput = document.getElementById('diagnosisCustomNotes');
            const cancelProcedureModal = document.getElementById('cancelProcedureModal');
            const confirmCancelProcedureBtn = document.getElementById('confirmCancelProcedureBtn');
            const dismissCancelProcedureBtn = document.getElementById('dismissCancelProcedureBtn');
            const correctionReasonModal = document.getElementById('correctionReasonModal');

            const correctionReasonCategory = document.getElementById('correctionReasonCategory');
            const correctionReasonNotes = document.getElementById('correctionReasonNotes');
            const correctionReasonOtherField = document.getElementById('correctionReasonOtherField');
            const correctionReasonOther = document.getElementById('correctionReasonOther');
            const dismissCorrectionReasonBtn = document.getElementById('dismissCorrectionReasonBtn');
            const confirmCorrectionReasonBtn = document.getElementById('confirmCorrectionReasonBtn');

            const finishProcedureModal = document.getElementById('finishProcedureModal');
            const finishProcedureModalTitle = document.getElementById('finishProcedureModalTitle');
            const finishProcedureModalMessage = document.getElementById('finishProcedureModalMessage');
            const finishProcedureModalIcon = document.getElementById('finishProcedureModalIcon');
            const finishProcedureConfirmActions = document.getElementById('finishProcedureConfirmActions');
            const confirmFinishProcedureBtn = document.getElementById('confirmFinishProcedureBtn');
            const dismissFinishProcedureBtn = document.getElementById('dismissFinishProcedureBtn');
            const finishProcedureModalActionBtn = document.getElementById('finishProcedureModalActionBtn');
            const finishProcedureResultActions = document.getElementById('finishProcedureResultActions');
            const finishProcedureModalAlertTitle = document.getElementById('finishProcedureModalAlertTitle');
            const finishProcedureModalAlertMessage = document.getElementById('finishProcedureModalAlertMessage');
            const existingAppointmentMode = @json($existingAppointmentMode);
            const savedVisitEditMode = @json($savedVisitEditMode);
            const allowsProcedureCompletionWithoutOdontogramChanges = @json($allowsProcedureCompletionWithoutOdontogramChanges);
            const procedureDiscardForm = document.getElementById('procedureDiscardForm');
            const procedureDiscardDirtyFlag = document.getElementById('procedureDiscardDirtyFlag');
            const discardProcedureUrl = @json($discardProcedureUrl ?? null);
            const discardProcedureReturnUrl = @json(
                $discardProcedureReturnUrl ??
                    ($cancelProcedureRedirectUrl ?? route('dentist.dentist.patient.profile', $patient->id ?? 1)));
            const cancelProcedureRedirectUrl = @json($cancelProcedureRedirectUrl ?? route('dentist.dentist.patient.profile', $patient->id ?? 1));
            let procedureDiscardPending = false;
            let finishProcedureModalRedirectUrl = null;

            const legendResultCount = document.getElementById('legendResultCount');
            const legendEmptyState = document.getElementById('legendEmptyState');
            const drawerSelectedLegendPreview = document.getElementById('drawerSelectedLegendPreview');

            const procedureDurationInput = document.getElementById('procedureDurationInput');
            const procedureTimeInInput = document.getElementById('procedure_started_at');
            const container = document.getElementById('canvas-container');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const legendDrawer = document.getElementById('legendDrawer');

            const odontogramDockLayout = document.getElementById('odontogramDockLayout');
            const legendResizeHandle = document.getElementById('legendResizeHandle');

            const view2dBtn = document.getElementById('view2dBtn');
            const view3dBtn = document.getElementById('view3dBtn');
            const panel2d = document.getElementById('odontogram2DPanel');
            const panel3d = document.getElementById('odontogram3DPanel');
            const board2d = document.getElementById('odontogram2DBoard');
            const viewInstructionText = document.getElementById('viewInstructionText');
            const selectedViewBadge = document.getElementById('selectedViewBadge');

            const selectedToothDisplay = document.getElementById('selectedToothDisplay');
            const selectedToothName = document.getElementById('selectedToothName');
            const toothHoverLabel = document.getElementById('toothHoverLabel');
            const legendContainer = document.getElementById('legendContainer');
            const odontogramDataInput = document.getElementById('odontogramData');
            const selectedLegendPreview = document.getElementById('selectedLegendPreview');
            const selectedToothLegendList = document.getElementById('selectedToothLegendList');
            const legendStatusNote = document.getElementById('legendStatusNote');
            const applyTreatmentBtn = document.getElementById('applyTreatmentBtn');
            const wholeToothScopeBtn = document.getElementById('wholeToothScopeBtn');
            const toothSurfaceScopeBtn = document.getElementById('toothSurfaceScopeBtn');
            const clearCurrentToothBtn = document.getElementById('clearCurrentToothBtn');

            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            const undoBtn = document.getElementById('undoBtn');
            const redoBtn = document.getElementById('redoBtn');

            const toothTooltip = document.getElementById('toothTooltip');
            const toothTooltipContent = document.getElementById('toothTooltipContent');
            const surfacePicker3D = document.getElementById('surfacePicker3D');
            const surfacePickerDragHandle = document.getElementById('surfacePickerDragHandle');
            const surfacePickerToothLabel = document.getElementById('surfacePickerToothLabel');
            const surfacePickerHelperText = document.getElementById('surfacePickerHelperText');
            const surfacePickerButtons = Array.from(document.querySelectorAll('.surface-picker-btn'));
            const reset3DViewBtn = document.getElementById('reset3DViewBtn');
            const close3DSurfacePickerBtn = document.getElementById('close3DSurfacePickerBtn');

            const resetTreatmentModal = document.getElementById('resetTreatmentModal');
            const resetTreatmentMessage = document.getElementById('resetTreatmentMessage');
            const confirmResetTreatmentBtn = document.getElementById('confirmResetTreatmentBtn');
            const cancelResetTreatmentBtn = document.getElementById('cancelResetTreatmentBtn');

            let currentView = '2d';
            let selectedTooth = null;
            let selectedLegend = null;
            let selectedTargetType = null;
            let selectedSurfaceKey = null;
            let selectedMesh = null;
            let selectedScope = 'surface';
            const selectedTargets = new Map();
            let multiSelectTipToast = null;
            let pendingResetPayload = null;
            let hasAppliedTreatmentThisSession = false;

            let historyStack = [];
            let redoStack = [];
            const HISTORY_LIMIT = 50;

            let odontogramThreeState = null;

            let odontogramThreeLoadPromise =
                null;

            function waitForThreeSceneIdle() {
                return new Promise(
                    resolve => {

                        if (
                            typeof window.requestIdleCallback ===
                            'function'
                        ) {
                            window.requestIdleCallback(
                                () => {
                                    resolve();
                                }
                            );

                            return;
                        }

                        window.setTimeout(
                            resolve,
                            180
                        );
                    }
                );
            }

            const legends = [{
                    code: 'D',
                    label: 'Decayed (Caries indicated for Filling)'
                },
                {
                    code: 'RC',
                    label: 'Recurrent Caries'
                },
                {
                    code: 'M',
                    label: 'Missing due to Caries'
                },
                {
                    code: 'F',
                    label: 'Filled'
                },
                {
                    code: 'I',
                    label: 'Caries Indicated for Extraction'
                },
                {
                    code: 'RF',
                    label: 'Root Fragment'
                },
                {
                    code: 'MO',
                    label: 'Missing due to Other Causes'
                },
                {
                    code: 'IM',
                    displayCode: 'Im',
                    label: 'Impacted Tooth'
                },
                {
                    code: 'J',
                    label: 'Jacket Crown'
                },
                {
                    code: 'A',
                    label: 'Amalgam Filling'
                },
                {
                    code: 'AB',
                    label: 'Abutment'
                },
                {
                    code: 'P',
                    label: 'Pontic'
                },
                {
                    code: 'IN',
                    displayCode: 'In',
                    label: 'Inlay'
                },
                {
                    code: 'LC',
                    label: 'Light Cure Composite'
                },
                {
                    code: 'TP',
                    label: 'Temporary Pasta'
                },
                {
                    code: 'RM',
                    displayCode: 'Rm',
                    label: 'Removable Denture'
                },
                {
                    code: 'X',
                    label: 'Extraction due to Caries'
                },
                {
                    code: 'XO',
                    label: 'Extraction due to Other Causes'
                },
                {
                    code: '✓',
                    label: 'Present Teeth'
                },
                {
                    code: 'CM',
                    displayCode: 'Cm',
                    label: 'Congenitally Missing'
                },
                {
                    code: 'SP',
                    displayCode: 'Sp',
                    label: 'Supernumerary'
                }
            ];

            const legendColors = {
                D: '#ef4444',
                RC: '#ef4444',
                M: '#111827',
                F: '#2563eb',
                I: '#ef4444',
                RF: '#ef4444',
                MO: '#111827',
                IM: '#111827',
                J: '#2563eb',
                A: '#2563eb',
                AB: '#2563eb',
                P: '#2563eb',
                IN: '#2563eb',
                LC: '#2563eb',
                TP: '#2563eb',
                RM: '#2563eb',
                X: '#2563eb',
                XO: '#2563eb',
                '✓': '#111827',
                CM: '#111827',
                SP: '#111827'
            };

            const legendIcons = {
                D: 'fa-solid fa-bug',
                RC: 'fa-solid fa-bug',
                M: 'fa-solid fa-ban',
                F: 'fa-solid fa-square-check',
                I: 'fa-solid fa-syringe',
                RF: 'fa-solid fa-teeth-open',
                MO: 'fa-solid fa-circle-xmark',
                IM: 'fa-solid fa-triangle-exclamation',
                J: 'fa-solid fa-crown',
                A: 'fa-solid fa-fill-drip',
                AB: 'fa-solid fa-anchor',
                P: 'fa-solid fa-link',
                IN: 'fa-solid fa-puzzle-piece',
                LC: 'fa-solid fa-wand-magic-sparkles',
                TP: 'fa-solid fa-clock',
                RM: 'fa-solid fa-teeth',
                X: 'fa-solid fa-xmark',
                XO: 'fa-solid fa-skull-crossbones',
                '✓': 'fa-solid fa-check',
                CM: 'fa-solid fa-question',
                SP: 'fa-solid fa-plus'
            };

            const legendCategories = [{
                    key: 'conditions',
                    title: 'Conditions',
                    icon: 'fa-solid fa-heart-pulse',
                    items: ['D', 'RC', 'M', 'F', 'I', 'RF', 'MO', 'IM']
                },
                {
                    key: 'restoration-prosthetics',
                    title: 'Restorations & Prosthetics',
                    icon: 'fa-solid fa-screwdriver-wrench',
                    items: ['J', 'A', 'AB', 'P', 'IN', 'LC', 'TP', 'RM']
                },
                {
                    key: 'surgery',
                    title: 'Surgery',
                    icon: 'fa-solid fa-user-doctor',
                    items: ['X', 'XO', '✓', 'CM', 'SP']
                }
            ];

            const rawSavedOdontogramData = @json($savedOdontogramData ?? []);
            const savedOdontogramData = Array.isArray(rawSavedOdontogramData) ?
                rawSavedOdontogramData :
                Object.values(rawSavedOdontogramData || {});

            const odontogramState = {};

            async function initThreeScene() {

                if (odontogramThreeState) {
                    return odontogramThreeState;
                }


                if (odontogramThreeLoadPromise) {
                    return odontogramThreeLoadPromise;
                }

                odontogramThreeLoadPromise =
                    (async () => {

                        await waitForThreeSceneIdle();

                        if (currentView !== '3d') {
                            return null;
                        }

                        if (!window.Odontogram3D) {
                            try {
                                await window.loadOdontogramThreeModule?.();
                            } catch (error) {
                                console.error(
                                    'Unable to load odontogram 3D module.',
                                    error
                                );

                                showProcedureToast(
                                    'Unable to load the 3D odontogram.',
                                    'error',
                                    '3D View Unavailable'
                                );

                                return;
                            }
                        }
                        await waitForThreeSceneIdle();

                        if (currentView !== '3d') {
                            return null;
                        }
                        if (!window.Odontogram3D) {
                            showProcedureToast(
                                'The 3D odontogram module is unavailable.',
                                'error',
                                '3D View Unavailable'
                            );

                            return;
                        }

                        container.style.pointerEvents =
                            'none';

                        odontogramThreeState =
                            window.Odontogram3D.create({
                                container,

                                data: Object.values(
                                    odontogramState
                                ),

                                mode: 'editor',

                                onToothHover: (
                                    toothNumber,
                                    mesh,
                                    event,
                                    surfaceKey
                                ) => {

                                    if (!toothNumber) {
                                        toothHoverLabel
                                            .innerText =
                                            selectedTooth ?
                                            `Selected: #${selectedTooth}` :
                                            'Select a tooth';

                                        hideTooltip();

                                        return;
                                    }

                                    toothHoverLabel
                                        .innerText =
                                        `Tooth #${toothNumber} · ${getSurfaceLabel(surfaceKey, toothNumber)}`;

                                    showTooltip(event, mesh, surfaceKey);
                                },

                                onToothClick: (toothNumber, mesh, event, surfaceKey) => {
                                    if (!toothNumber || !mesh) {
                                        if (!event?.shiftKey) clear3DSurfacePickerSelection(
                                            false);
                                        hideTooltip();
                                        return;
                                    }

                                    const previousTooth = selectedTooth;
                                    const target = {
                                        tooth: toothNumber,
                                        targetType: selectedScope === 'whole' ? 'whole' :
                                            'surface',
                                        surfaceKey: selectedScope === 'whole' ? null :
                                            surfaceKey
                                    };
                                    select2DTarget(target, Boolean(event?.shiftKey));
                                    if (selectedTargetType === 'surface' &&
                                        selectedSurfaceKey) {
                                        ensureToothState(selectedTooth).lastSelectedSurface =
                                            selectedSurfaceKey;
                                    }
                                    selectedMesh = window.Odontogram3D.getToothMesh(
                                        odontogramThreeState,
                                        selectedTooth);
                                    if (selectedMesh && previousTooth !== selectedTooth && !
                                        event?.shiftKey) {
                                        window.Odontogram3D.focusTooth(odontogramThreeState,
                                            selectedMesh);
                                    }
                                    renderThreeVisuals();
                                    updateScopeButtons();
                                    updateSelectedToothUI();
                                },

                                onReady: () => {

                                    container.style.removeProperty(
                                        'pointer-events'
                                    );

                                    loadingOverlay.style.opacity =
                                        '0';

                                    setTimeout(() => {

                                        loadingOverlay.style.display =
                                            'none';

                                    }, 500);
                                }
                            });

                        return odontogramThreeState;
                    })();

                try {

                    return await odontogramThreeLoadPromise;

                } finally {

                    odontogramThreeLoadPromise =
                        null;
                }
            }

            function renderThreeVisuals() {
                if (
                    !odontogramThreeState
                ) {
                    return;
                }

                window.Odontogram3D.update(
                    odontogramThreeState,

                    Object.values(
                        odontogramState
                    ),

                    {
                        selectedTooth: selectedTooth,
                        selectedSurfaceKey: selectedSurfaceKey,
                        selectedTargetType: selectedTargetType,
                        selectedTargets: Array.from(selectedTargets.values()),

                        dimUnselected: currentView === '3d' &&
                            Boolean(
                                selectedTooth
                            )
                    }
                );
            }

            function resetCameraToFull3DView() {
                if (
                    !odontogramThreeState
                ) {
                    return;
                }

                window.Odontogram3D
                    .resetCamera(
                        odontogramThreeState
                    );
            }

            function handleResize() {
                if (
                    !odontogramThreeState
                ) {
                    return;
                }

                window.Odontogram3D
                    .resize(
                        odontogramThreeState
                    );
            }

            function createDefaultToothState(toothNumber) {
                return {
                    tooth: Number(toothNumber),
                    toothName: getToothName(Number(toothNumber)),
                    status: null,
                    surfaces: {
                        top: null,
                        left: null,
                        center: null,
                        right: null,
                        bottom: null
                    },
                    threeD: null,
                    lastSelectedSurface: null
                };
            }

            function normalizeOdontogramEntry(entry) {
                if (!entry) return null;

                const toothNumber = Number(entry.tooth || entry.tooth_number || 0);

                if (!toothNumber) return null;

                const defaultState = createDefaultToothState(toothNumber);

                const surfaces =
                    entry.surfaces &&
                    typeof entry.surfaces === 'object' &&
                    !Array.isArray(entry.surfaces) ?
                    entry.surfaces : {};

                const normalizeLegendRecord = function(record) {
                    if (!record || !record.code) return null;

                    const rawCode = String(record.code).trim();
                    const normalizedCode = ['PT', '+'].includes(rawCode.toUpperCase()) ?
                        '✓' :
                        rawCode.toUpperCase();
                    const currentLegend = getLegendByCode(normalizedCode);

                    return {
                        ...record,
                        code: normalizedCode,
                        label: currentLegend?.label || record.label || normalizedCode,
                        colorHex: legendColors[normalizedCode] || record.colorHex || '#111827'
                    };
                };

                return {
                    ...defaultState,
                    ...entry,
                    tooth: toothNumber,
                    toothName: entry.toothName || entry.tooth_name || defaultState.toothName,
                    status: normalizeLegendRecord(entry.status),
                    threeD: normalizeLegendRecord(entry.threeD || entry.three_d),
                    lastSelectedSurface: ['top', 'left', 'center', 'right', 'bottom'].includes(
                            entry.lastSelectedSurface || entry.last_selected_surface
                        ) ?
                        (entry.lastSelectedSurface || entry.last_selected_surface) : null,
                    surfaces: {
                        top: normalizeLegendRecord(surfaces.top),
                        left: normalizeLegendRecord(surfaces.left),
                        center: normalizeLegendRecord(surfaces.center),
                        right: normalizeLegendRecord(surfaces.right),
                        bottom: normalizeLegendRecord(surfaces.bottom)
                    }
                };
            }

            savedOdontogramData.forEach(function(entry) {
                const normalizedEntry = normalizeOdontogramEntry(entry);

                if (normalizedEntry) {
                    odontogramState[normalizedEntry.tooth] = normalizedEntry;
                }
            });

            const primaryUpperRight = [55, 54, 53, 52, 51];
            const primaryUpperLeft = [61, 62, 63, 64, 65];

            const adultUpperRight = [18, 17, 16, 15, 14, 13, 12, 11];
            const adultUpperLeft = [21, 22, 23, 24, 25, 26, 27, 28];

            const adultLowerRight = [48, 47, 46, 45, 44, 43, 42, 41];
            const adultLowerLeft = [31, 32, 33, 34, 35, 36, 37, 38];

            const primaryLowerRight = [85, 84, 83, 82, 81];
            const primaryLowerLeft = [71, 72, 73, 74, 75];

            const adultUpper = [...adultUpperRight, ...adultUpperLeft];
            const adultLower = [...adultLowerRight, ...adultLowerLeft];

            function getLegendByCode(code) {
                return legends.find(item => item.code === code) || null;
            }

            function getLegendDisplayCode(code) {
                const legend = getLegendByCode(code);
                return legend?.displayCode || legend?.code || code;
            }

            function getToothName(toothNumber) {
                const names = {
                    18: 'Upper Right Third Molar',
                    17: 'Upper Right Second Molar',
                    16: 'Upper Right First Molar',
                    15: 'Upper Right Second Premolar',
                    14: 'Upper Right First Premolar',
                    13: 'Upper Right Canine',
                    12: 'Upper Right Lateral Incisor',
                    11: 'Upper Right Central Incisor',
                    21: 'Upper Left Central Incisor',
                    22: 'Upper Left Lateral Incisor',
                    23: 'Upper Left Canine',
                    24: 'Upper Left First Premolar',
                    25: 'Upper Left Second Premolar',
                    26: 'Upper Left First Molar',
                    27: 'Upper Left Second Molar',
                    28: 'Upper Left Third Molar',
                    48: 'Lower Right Third Molar',
                    47: 'Lower Right Second Molar',
                    46: 'Lower Right First Molar',
                    45: 'Lower Right Second Premolar',
                    44: 'Lower Right First Premolar',
                    43: 'Lower Right Canine',
                    42: 'Lower Right Lateral Incisor',
                    41: 'Lower Right Central Incisor',
                    31: 'Lower Left Central Incisor',
                    32: 'Lower Left Lateral Incisor',
                    33: 'Lower Left Canine',
                    34: 'Lower Left First Premolar',
                    35: 'Lower Left Second Premolar',
                    36: 'Lower Left First Molar',
                    37: 'Lower Left Second Molar',
                    38: 'Lower Left Third Molar',

                    55: 'Upper Right Second Molar (Primary)',
                    54: 'Upper Right First Molar (Primary)',
                    53: 'Upper Right Canine (Primary)',
                    52: 'Upper Right Lateral Incisor (Primary)',
                    51: 'Upper Right Central Incisor (Primary)',
                    61: 'Upper Left Central Incisor (Primary)',
                    62: 'Upper Left Lateral Incisor (Primary)',
                    63: 'Upper Left Canine (Primary)',
                    64: 'Upper Left First Molar (Primary)',
                    65: 'Upper Left Second Molar (Primary)',

                    85: 'Lower Right Second Molar (Primary)',
                    84: 'Lower Right First Molar (Primary)',
                    83: 'Lower Right Canine (Primary)',
                    82: 'Lower Right Lateral Incisor (Primary)',
                    81: 'Lower Right Central Incisor (Primary)',
                    71: 'Lower Left Central Incisor (Primary)',
                    72: 'Lower Left Lateral Incisor (Primary)',
                    73: 'Lower Left Canine (Primary)',
                    74: 'Lower Left First Molar (Primary)',
                    75: 'Lower Left Second Molar (Primary)',
                };

                return names[toothNumber] || `Tooth #${toothNumber}`;
            }

            function openUiModal(modal) {
                if (!modal?.id) return;

                window.openModal?.(modal.id);
            }

            function closeUiModal(modal) {
                if (!modal?.id) return;

                window.closeModal?.(modal.id);
            }

            function setCorrectionOtherFieldVisible(
                visible
            ) {
                if (!correctionReasonOtherField) {
                    return;
                }

                if (
                    correctionReasonOtherField
                    .__hideTimer
                ) {
                    window.clearTimeout(
                        correctionReasonOtherField
                        .__hideTimer
                    );

                    correctionReasonOtherField
                        .__hideTimer = null;
                }

                correctionReasonOtherField
                    .classList
                    .remove(
                        'slide-down-fade-in',
                        'content-fade-out'
                    );

                if (visible) {

                    if (
                        !correctionReasonOtherField.hidden
                    ) {
                        return;
                    }

                    correctionReasonOtherField.hidden =
                        false;

                    void correctionReasonOtherField
                        .offsetWidth;

                    correctionReasonOtherField
                        .classList
                        .add(
                            'slide-down-fade-in'
                        );

                    return;
                }

                if (
                    correctionReasonOtherField.hidden
                ) {
                    return;
                }

                void correctionReasonOtherField
                    .offsetWidth;

                correctionReasonOtherField
                    .classList
                    .add(
                        'content-fade-out'
                    );

                correctionReasonOtherField
                    .__hideTimer =
                    window.setTimeout(
                        () => {

                            correctionReasonOtherField
                                .classList
                                .remove(
                                    'content-fade-out'
                                );

                            correctionReasonOtherField
                                .hidden =
                                true;

                            correctionReasonOtherField
                                .__hideTimer =
                                null;

                        },
                        180
                    );
            }

            function openCorrectionReasonModal() {
                if (!savedVisitEditMode || !correctionReasonModal) {
                    return;
                }

                window.initCustomSelects?.(
                    correctionReasonModal
                );

                openUiModal(
                    correctionReasonModal
                );

                window.setTimeout(() => {
                    correctionReasonCategory
                        ?.closest('.custom-select')
                        ?.querySelector('.custom-select-button')
                        ?.focus();
                }, 50);
            }

            function closeCorrectionReasonModal() {
                closeUiModal(correctionReasonModal);
            }

            function markProcedureDiscardDirty() {
                if (procedureDiscardDirtyFlag) {
                    procedureDiscardDirtyFlag.value = '1';
                }
            }

            async function discardProcedureBeforeLeave(form) {
                if (procedureDiscardPending) {
                    return false;
                }

                if (!discardProcedureUrl) {
                    window.DiscardChanges?.closeDiscardModal?.();
                    window.location.href = discardProcedureReturnUrl ||
                        cancelProcedureRedirectUrl;
                    return false;
                }

                procedureDiscardPending = true;

                const discardModal = document.getElementById('globalDiscardModal');
                const confirmButton = discardModal?.querySelector('[data-discard-confirm]');
                const keepButtons = discardModal?.querySelectorAll('[data-discard-keep]') ?? [];
                const originalConfirmHtml = confirmButton?.innerHTML;

                if (confirmButton) {
                    confirmButton.disabled = true;
                    confirmButton.innerHTML =
                        '<i class="fa-solid fa-circle-notch fa-spin"></i> Discarding...';
                }

                keepButtons.forEach(button => {
                    button.disabled = true;
                });

                try {
                    const payload = {
                        context: form?.querySelector('[name="context"]')?.value ||
                            'appointments',
                        appointment_id: form?.querySelector('[name="appointment_id"]')
                            ?.value || null,
                        patient_id: form?.querySelector('[name="patient_id"]')?.value ||
                            null,
                    };

                    const response = await fetch(discardProcedureUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        console.error(result);

                        showProcedureToast(
                            result.message ||
                            'Failed to save procedure. Please check your inputs and try again.',
                            'error'
                        );

                        return null;
                    }

                    window.DiscardChanges?.closeDiscardModal?.();
                    window.location.href = result.redirect_url || discardProcedureReturnUrl ||
                        cancelProcedureRedirectUrl;
                    return false;
                } catch (error) {
                    console.error(error);
                    showProcedureToast(
                        'Something went wrong while discarding the current procedure.',
                        'error');
                    return false;
                } finally {
                    procedureDiscardPending = false;

                    if (confirmButton) {
                        confirmButton.disabled = false;
                        confirmButton.innerHTML = originalConfirmHtml;
                    }

                    keepButtons.forEach(button => {
                        button.disabled = false;
                    });
                }
            }

            window.discardProcedureBeforeLeave = discardProcedureBeforeLeave;

            function openCancelProcedureModal() {
                markProcedureDiscardDirty();

                if (window.DiscardChanges?.confirmClose && procedureDiscardForm) {
                    window.DiscardChanges.confirmClose(procedureDiscardForm, () => {});
                    return;
                }

                void discardProcedureBeforeLeave(procedureDiscardForm);
            }

            function closeCancelProcedureModal() {
                closeUiModal(cancelProcedureModal);
            }

            function openFinishProcedureModal({
                title,
                message,
                icon = 'fa-clipboard-check',
                buttonText = 'Back to Appointments',
                redirectUrl = null,
                confirmation = false,
            }) {
                finishProcedureModalTitle.textContent = title;
                finishProcedureModalMessage.textContent =
                    confirmation ?
                    'Confirm before completing this dental procedure.' :
                    (
                        redirectUrl ?
                        'The procedure was completed successfully.' :
                        title === 'Treatment Required' ?
                        'A treatment entry is required before this procedure can be completed.' :
                        message
                    );
                finishProcedureModalIcon.className = `fa-solid ${icon}`;
                finishProcedureModalRedirectUrl = redirectUrl;

                const actionButton = finishProcedureModalActionBtn;

                if (actionButton) {
                    actionButton.classList.remove(
                        'ui-btn-primary',
                        'ui-btn-success',
                        'ui-btn-warning',
                        'ui-btn-danger'
                    );

                    actionButton.classList.add(
                        redirectUrl ?
                        'ui-btn-success' :
                        confirmation ?
                        'ui-btn-warning' :
                        'ui-btn-primary'
                    );
                }

                finishProcedureModal.classList.remove(
                    'modal-theme-success',
                    'modal-theme-warning',
                    'modal-theme-danger'
                );

                finishProcedureModal.classList.add(
                    confirmation ?
                    'modal-theme-warning' :
                    (redirectUrl ? 'modal-theme-success' : 'modal-theme-danger')
                );

                finishProcedureModalAlertTitle.textContent =
                    confirmation ?
                    'This action will complete the appointment' :
                    (
                        redirectUrl ?
                        'Appointment completed' :
                        title === 'Treatment Required' ?
                        'Add a treatment first' :
                        'Action required'
                    );

                finishProcedureModalAlertMessage.textContent = message;

                finishProcedureConfirmActions.classList.toggle('hidden', !confirmation);
                finishProcedureResultActions.classList.toggle('hidden', confirmation);

                const actionLabel = finishProcedureModalActionBtn?.querySelector('span');
                if (actionLabel) {
                    actionLabel.textContent = buttonText;
                }

                openUiModal(finishProcedureModal);

                requestAnimationFrame(() => {
                    (confirmation ? confirmFinishProcedureBtn :
                        finishProcedureModalActionBtn)?.focus();
                });
            }

            function closeFinishProcedureModal() {
                const redirectUrl = finishProcedureModalRedirectUrl;
                finishProcedureModalRedirectUrl = null;

                closeUiModal(finishProcedureModal);

                if (redirectUrl) {
                    window.setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 180);
                    return;
                }

                window.setTimeout(() => {
                    finishProcedureBtn?.focus({
                        preventScroll: true
                    });
                }, 180);
            }

            function getSurfaceLabel(surface, toothNumber = selectedTooth) {
                const numericTooth = Number(toothNumber);
                const quadrant = Math.floor(numericTooth / 10);
                const toothPosition = numericTooth % 10;
                const isUpperTooth = [1, 2, 5, 6].includes(quadrant);
                const isLowerTooth = [3, 4, 7, 8].includes(quadrant);
                const centerSurfaceLabel = toothPosition >= 1 && toothPosition <= 3 ?
                    'Incisal Surface' :
                    (toothPosition >= 4 && toothPosition <= 8 ?
                        'Occlusal Surface' :
                        'Center Surface');
                const isPatientRightSide =
                    (numericTooth >= 11 && numericTooth <= 18) ||
                    (numericTooth >= 41 && numericTooth <= 48) ||
                    (numericTooth >= 51 && numericTooth <= 55) ||
                    (numericTooth >= 81 && numericTooth <= 85);
                const isPatientLeftSide =
                    (numericTooth >= 21 && numericTooth <= 28) ||
                    (numericTooth >= 31 && numericTooth <= 38) ||
                    (numericTooth >= 61 && numericTooth <= 65) ||
                    (numericTooth >= 71 && numericTooth <= 75);

                const map = {
                    top: isUpperTooth ?
                        'Buccal Surface' : (isLowerTooth ? 'Lingual Surface' : 'Top Surface'),
                    bottom: isUpperTooth ?
                        'Palatal Surface' : (isLowerTooth ? 'Buccal Surface' :
                            'Bottom Surface'),
                    left: isPatientRightSide ?
                        'Distal Surface' : (isPatientLeftSide ? 'Mesial Surface' :
                            'Left Surface'),
                    right: isPatientRightSide ?
                        'Mesial Surface' : (isPatientLeftSide ? 'Distal Surface' :
                            'Right Surface'),
                    center: centerSurfaceLabel,
                    status: 'Status Box',
                    whole: 'Whole Tooth'
                };
                return map[surface] || surface;
            }

            function update3DSurfacePicker() {
                if (!surfacePicker3D) return;

                const showPicker = currentView === '3d' && !!selectedTooth && selectedScope ===
                    'surface';
                surfacePicker3D.classList.toggle('hidden', !showPicker);

                if (!showPicker) {
                    return;
                }

                if (selectedTooth) {
                    surfacePickerToothLabel.textContent =
                        `Tooth #${selectedTooth} - ${getToothName(selectedTooth)}`;
                    surfacePickerHelperText.textContent = selectedTargetType === 'surface' ?
                        `${getSurfaceLabel(selectedSurfaceKey, selectedTooth)} selected.` :
                        'Click another surface on the tooth, or choose a button below.';
                } else {
                    surfacePickerToothLabel.textContent = 'No tooth selected';
                    surfacePickerHelperText.textContent =
                        'Click a surface on the model, or use the buttons below.';
                }

                surfacePickerButtons.forEach(btn => {
                    const surfaceKey = btn.dataset.surface;
                    const disabled = !selectedTooth;
                    const isActive = isTargetSelected(
                        selectedTooth,
                        'surface',
                        surfaceKey
                    );

                    const toothState =
                        selectedTooth ?
                        ensureToothState(selectedTooth) :
                        null;

                    const treatment =
                        toothState?.surfaces[surfaceKey] ||
                        toothState?.status;

                    const surfaceLabel =
                        getSurfaceLabel(
                            surfaceKey,
                            selectedTooth
                        );

                    btn.disabled = disabled;

                    btn.textContent =
                        surfaceLabel.replace(' Surface', '');

                    btn.classList.toggle(
                        'active',
                        isActive
                    );

                    btn.classList.toggle(
                        'has-treatment',
                        Boolean(treatment)
                    );

                    btn.setAttribute(
                        'aria-pressed',
                        String(isActive)
                    );

                    btn.removeAttribute('title');

                    btn.setAttribute(
                        'aria-label',
                        treatment ?
                        `${surfaceLabel}: ${getLegendDisplayCode(treatment.code)} - ${treatment.label}` :
                        `${surfaceLabel}: No treatment assigned`
                    );

                    if (treatment?.colorHex) {
                        btn.style.setProperty(
                            '--surface-treatment-color',
                            treatment.colorHex
                        );

                        const treatmentDot =
                            document.createElement('span');

                        treatmentDot.className =
                            'surface-picker-treatment-dot';

                        treatmentDot.setAttribute(
                            'data-tooltip',
                            `${getLegendDisplayCode(treatment.code)} - ${treatment.label}`
                        );

                        treatmentDot.setAttribute(
                            'data-tooltip-tone',
                            'neutral'
                        );

                        treatmentDot.setAttribute(
                            'data-tooltip-color',
                            treatment.colorHex
                        );
                        treatmentDot.setAttribute(
                            'aria-hidden',
                            'true'
                        );

                        btn.appendChild(
                            treatmentDot
                        );
                    } else {
                        btn.style.removeProperty(
                            '--surface-treatment-color'
                        );
                    }
                });
            }

            function initSurfacePickerDrag() {
                if (
                    !surfacePicker3D ||
                    !surfacePickerDragHandle ||
                    !container
                ) {
                    return;
                }

                let dragging = false;
                let activePointerId = null;
                let pointerOffsetX = 0;
                let pointerOffsetY = 0;

                function clampPickerPosition(left, top) {
                    const containerRect =
                        container.getBoundingClientRect();

                    const pickerRect =
                        surfacePicker3D.getBoundingClientRect();

                    const padding = 8;

                    const maxLeft = Math.max(
                        padding,
                        containerRect.width -
                        pickerRect.width -
                        padding
                    );

                    const maxTop = Math.max(
                        padding,
                        containerRect.height -
                        pickerRect.height -
                        padding
                    );

                    return {
                        left: Math.min(
                            maxLeft,
                            Math.max(padding, left)
                        ),

                        top: Math.min(
                            maxTop,
                            Math.max(padding, top)
                        )
                    };
                }

                function movePicker(clientX, clientY) {
                    const containerRect =
                        container.getBoundingClientRect();

                    const nextPosition =
                        clampPickerPosition(
                            clientX -
                            containerRect.left -
                            pointerOffsetX,

                            clientY -
                            containerRect.top -
                            pointerOffsetY
                        );

                    surfacePicker3D.style.left =
                        `${nextPosition.left}px`;

                    surfacePicker3D.style.top =
                        `${nextPosition.top}px`;

                    surfacePicker3D.style.right = 'auto';
                    surfacePicker3D.style.bottom = 'auto';
                    surfacePicker3D.style.transform = 'none';
                }

                function stopDragging(event) {
                    if (
                        !dragging ||
                        (
                            activePointerId !== null &&
                            event.pointerId !== activePointerId
                        )
                    ) {
                        return;
                    }

                    dragging = false;

                    surfacePickerDragHandle.classList.remove(
                        'is-dragging'
                    );

                    if (
                        activePointerId !== null &&
                        surfacePickerDragHandle.hasPointerCapture?.(
                            activePointerId
                        )
                    ) {
                        surfacePickerDragHandle.releasePointerCapture(
                            activePointerId
                        );
                    }

                    activePointerId = null;
                }

                surfacePickerDragHandle.addEventListener(
                    'pointerdown',
                    function(event) {
                        if (
                            event.pointerType === 'mouse' &&
                            event.button !== 0
                        ) {
                            return;
                        }

                        const containerRect =
                            container.getBoundingClientRect();

                        const pickerRect =
                            surfacePicker3D.getBoundingClientRect();

                        dragging = true;
                        activePointerId = event.pointerId;

                        pointerOffsetX =
                            event.clientX - pickerRect.left;

                        pointerOffsetY =
                            event.clientY - pickerRect.top;

                        surfacePicker3D.style.left =
                            `${pickerRect.left - containerRect.left}px`;

                        surfacePicker3D.style.top =
                            `${pickerRect.top - containerRect.top}px`;

                        surfacePicker3D.style.right = 'auto';
                        surfacePicker3D.style.bottom = 'auto';
                        surfacePicker3D.style.transform = 'none';

                        surfacePickerDragHandle.classList.add(
                            'is-dragging'
                        );

                        surfacePickerDragHandle.setPointerCapture?.(
                            event.pointerId
                        );

                        event.preventDefault();
                        event.stopPropagation();
                    }
                );

                surfacePickerDragHandle.addEventListener(
                    'pointermove',
                    function(event) {
                        if (
                            !dragging ||
                            event.pointerId !== activePointerId
                        ) {
                            return;
                        }

                        movePicker(
                            event.clientX,
                            event.clientY
                        );

                        event.preventDefault();
                        event.stopPropagation();
                    }
                );

                surfacePickerDragHandle.addEventListener(
                    'pointerup',
                    stopDragging
                );

                surfacePickerDragHandle.addEventListener(
                    'pointercancel',
                    stopDragging
                );

                window.addEventListener(
                    'resize',
                    function() {
                        if (
                            !surfacePicker3D.style.left ||
                            surfacePicker3D.classList.contains('hidden')
                        ) {
                            return;
                        }

                        const currentLeft =
                            parseFloat(
                                surfacePicker3D.style.left
                            ) || 0;

                        const currentTop =
                            parseFloat(
                                surfacePicker3D.style.top
                            ) || 0;

                        const position =
                            clampPickerPosition(
                                currentLeft,
                                currentTop
                            );

                        surfacePicker3D.style.left =
                            `${position.left}px`;

                        surfacePicker3D.style.top =
                            `${position.top}px`;
                    }
                );
            }

            function selectSurfaceFrom3DPicker(surfaceKey, useMultiSelect = false) {
                if (!selectedTooth) {
                    showProcedureToast(
                        'Select a tooth in the 3D model before choosing a surface.',
                        'warning',
                        'Select a Tooth'
                    );
                    return;
                }

                const tooth = selectedTooth;
                select2DTarget({
                    tooth,
                    targetType: 'surface',
                    surfaceKey
                }, useMultiSelect);
                ensureToothState(tooth).lastSelectedSurface = surfaceKey;
                selectedMesh = window.Odontogram3D.getToothMesh(odontogramThreeState,
                    selectedTooth);
                if (!useMultiSelect && selectedMesh) {
                    window.Odontogram3D.focusTooth(odontogramThreeState, selectedMesh, surfaceKey);
                }
                updateSelectedToothUI();
                renderThreeVisuals();
            }

            function clear3DSurfacePickerSelection(shouldResetCamera = false) {
                selectedTargets.clear();
                selectedTooth = null;
                selectedTargetType = null;
                selectedSurfaceKey = null;
                selectedMesh = null;
                selectedLegend = null;

                if (odontogramThreeState) {
                    renderThreeVisuals();

                    if (shouldResetCamera) {
                        resetCameraToFull3DView();
                    }
                }

                updateSelectedToothUI();
                updateLegendActiveState();
                updateActionButtons();
                updateHistoryButtons();
                update3DSurfacePicker();
            }

            function ensureToothState(toothNumber) {
                const numericToothNumber = Number(toothNumber);

                if (!odontogramState[numericToothNumber]) {
                    odontogramState[numericToothNumber] = createDefaultToothState(
                        numericToothNumber);
                } else {
                    odontogramState[numericToothNumber] = normalizeOdontogramEntry(odontogramState[
                            numericToothNumber]) ||
                        createDefaultToothState(numericToothNumber);
                }

                return odontogramState[numericToothNumber];
            }

            function updateHiddenInput() {
                odontogramDataInput.value = JSON.stringify(Object.values(odontogramState));
            }

            function updateLegendActiveState() {
                document.querySelectorAll('.legend-btn').forEach(btn => {
                    btn.classList.remove('active');
                    if (selectedLegend && btn.dataset.code === selectedLegend) {
                        btn.classList.add('active');
                    }
                });
            }

            function getTargetSelectionKey(tooth, targetType, surfaceKey = null) {
                return `${Number(tooth)}:${targetType}:${surfaceKey || ''}`;
            }

            function isTargetSelected(tooth, targetType, surfaceKey = null) {
                return selectedTargets.has(
                    getTargetSelectionKey(tooth, targetType, surfaceKey)
                );
            }

            function getLastSelectedTarget() {
                const targets = Array.from(selectedTargets.values());
                return targets.length ? targets[targets.length - 1] : null;
            }

            function setPrimaryTarget(target, preserveLegend = false) {
                if (!target) {
                    selectedTooth = null;
                    selectedTargetType = null;
                    selectedSurfaceKey = null;
                    selectedLegend = null;
                    return;
                }

                const previousLegend = selectedLegend;

                selectedTooth = Number(target.tooth);
                selectedTargetType = target.targetType;
                selectedSurfaceKey = target.surfaceKey || null;
                selectedScope = target.targetType === 'whole' ? 'whole' : 'surface';

                const state = ensureToothState(selectedTooth);
                const record = target.targetType === 'whole' ?
                    state.status :
                    state.surfaces[selectedSurfaceKey];

                selectedLegend = preserveLegend ?
                    previousLegend :
                    (record?.code || null);
            }

            function removeConflictingTargetsForTooth(tooth, targetType) {
                const numericTooth = Number(tooth);

                selectedTargets.forEach((target, key) => {
                    if (Number(target.tooth) !== numericTooth) return;

                    const conflicts =
                        targetType === 'whole' ||
                        target.targetType === 'whole';

                    if (conflicts) {
                        selectedTargets.delete(key);
                    }
                });
            }

            function select2DTarget(target, useMultiSelect = false) {
                const normalizedTarget = {
                    tooth: Number(target.tooth),
                    targetType: target.targetType,
                    surfaceKey: target.surfaceKey || null
                };
                const key = getTargetSelectionKey(
                    normalizedTarget.tooth,
                    normalizedTarget.targetType,
                    normalizedTarget.surfaceKey
                );

                if (!useMultiSelect) {
                    selectedTargets.clear();
                    selectedTargets.set(key, normalizedTarget);
                    setPrimaryTarget(normalizedTarget, false);
                    return;
                }

                window.dismissToast?.(multiSelectTipToast);
                multiSelectTipToast = null;

                const preserveLegend = selectedTargets.size > 0 && !!selectedLegend;

                if (selectedTargets.has(key)) {
                    selectedTargets.delete(key);
                    setPrimaryTarget(getLastSelectedTarget(), preserveLegend);
                    return;
                }

                removeConflictingTargetsForTooth(
                    normalizedTarget.tooth,
                    normalizedTarget.targetType
                );
                selectedTargets.set(key, normalizedTarget);
                setPrimaryTarget(normalizedTarget, preserveLegend);
            }

            function getSelectedRecord() {
                if (!selectedTooth || !selectedTargetType) return null;

                const state = ensureToothState(selectedTooth);

                if (selectedTargetType === 'status') return state.status;
                if (selectedTargetType === 'whole') return state.status;
                if (selectedTargetType === 'surface' && selectedSurfaceKey) return state.surfaces[
                    selectedSurfaceKey];
                if (selectedTargetType === '3d') return state.threeD;

                return null;
            }

            function renderSelectedToothLegendList() {
                if (selectedTargets.size > 1) {
                    const legend = selectedLegend ? getLegendByCode(selectedLegend) : null;

                    if (!legend) {
                        selectedToothLegendList.innerHTML =
                            `<span class="ui-muted-text">${selectedTargets.size} targets selected</span>`;
                        return;
                    }

                    selectedToothLegendList.innerHTML = `
                    <span class="odontogram-preview-marking">
                        <span
                            class="odontogram-preview-marking-swatch"
                            data-ui-style="background:${window.uiAttribute(legendColors[legend.code])};"
                        ></span>
                        <span>
                            ${getLegendDisplayCode(legend.code)} - ${legend.label}
                        </span>
                    </span>
                `;
                    return;
                }

                const selectedRecord = getSelectedRecord();

                if (!selectedRecord) {
                    selectedToothLegendList.innerHTML =
                        '<span class="ui-muted-text">None selected</span>';

                    return;
                }

                selectedToothLegendList.innerHTML = `
        <span class="odontogram-preview-marking">
            <span
                class="odontogram-preview-marking-swatch"
                data-ui-style="background:${window.uiAttribute(selectedRecord.colorHex)};"
            ></span>

            <span>
                ${getLegendDisplayCode(selectedRecord.code)} - ${selectedRecord.label}
            </span>
        </span>
    `;
            }

            function updateActionButtons() {
                const hasSelectedTarget =
                    selectedTargets.size > 0 ||
                    (!!selectedTooth && !!selectedTargetType);

                const hasSelectedLegend = !!selectedLegend;

                const hasAssignedTreatment =
                    selectedTargets.size > 0 ?
                    Array.from(
                        selectedTargets.values()
                    ).some(target => {
                        const state =
                            ensureToothState(
                                target.tooth
                            );

                        if (
                            target.targetType === 'surface' &&
                            target.surfaceKey
                        ) {
                            return !!state
                                .surfaces[
                                    target.surfaceKey
                                ];
                        }

                        if (
                            [
                                'whole',
                                'status',
                                '3d'
                            ].includes(
                                target.targetType
                            )
                        ) {
                            return !!state.status;
                        }

                        return false;
                    }) :
                    !!getSelectedRecord();

                applyTreatmentBtn.disabled = !(
                    hasSelectedTarget &&
                    hasSelectedLegend
                );

                clearCurrentToothBtn.disabled = !hasAssignedTreatment;

                updateHistoryButtons();
            }

            function updateSelectedToothUI() {
                if (!selectedTooth || !selectedTargetType) {
                    drawerSelectedLegendPreview.textContent =
                        selectedLegend ?
                        `${getLegendDisplayCode(selectedLegend)} - ${getLegendByCode(selectedLegend)?.label || selectedLegend
                        }` :
                        'None selected';

                    selectedToothDisplay.textContent =
                        currentView === '2d' ?
                        (selectedScope === 'whole' ? 'Select a tooth.' :
                            'Select a tooth surface.') :
                        'Select a tooth.';

                    selectedToothName.textContent = '';
                    toothHoverLabel.innerText = 'Select a tooth';
                    legendStatusNote.classList.add('hidden');
                    selectedLegend = null;
                    selectedViewBadge.textContent = currentView === '2d' ? '2D View' : '3D View';

                    updateLegendActiveState();
                    renderSelectedToothLegendList();
                    updateActionButtons();
                    update3DSurfacePicker();
                    return;
                }

                if (selectedTargets.size > 1) {
                    const targetCount = selectedTargets.size;

                    selectedToothDisplay.textContent = `${targetCount} targets selected`;
                    selectedToothName.textContent =
                        'Shift + Click to add or remove teeth and surfaces';
                    toothHoverLabel.innerText = `${targetCount} targets selected`;
                    selectedViewBadge.textContent = currentView === '2d' ? '2D View' : '3D View';
                    legendStatusNote.classList.remove('hidden');
                    legendStatusNote.textContent =
                        `Select a treatment, then apply it to all ${targetCount} selected targets.`;

                    updateLegendActiveState();
                    renderSelectedToothLegendList();
                    updateActionButtons();
                    update3DSurfacePicker();
                    return;
                }

                if (currentView === '3d' && !selectedTargetType) {
                    selectedToothDisplay.textContent =
                        `Tooth #${selectedTooth} - Choose a surface below`;
                    selectedToothName.textContent = getToothName(selectedTooth);
                    toothHoverLabel.innerText = `Selected: #${selectedTooth}`;
                    selectedViewBadge.textContent = '3D View';
                    legendStatusNote.classList.remove('hidden');
                    legendStatusNote.textContent =
                        'Choose a surface, select a treatment, then apply.';
                    selectedLegend = null;

                    updateLegendActiveState();
                    renderSelectedToothLegendList();
                    updateActionButtons();
                    update3DSurfacePicker();
                    return;
                }

                const surfaceText = selectedTargetType === 'surface' ?
                    getSurfaceLabel(selectedSurfaceKey, selectedTooth) :
                    selectedTargetType === 'status' ?
                    getSurfaceLabel('status') :
                    getSurfaceLabel('whole');

                selectedToothDisplay.textContent = `Tooth #${selectedTooth} - ${surfaceText}`;
                selectedToothName.textContent = getToothName(selectedTooth);
                toothHoverLabel.innerText = `Selected: #${selectedTooth}`;
                selectedViewBadge.textContent = currentView === '2d' ? '2D View' : '3D View';
                legendStatusNote.classList.remove('hidden');
                legendStatusNote.textContent = 'Select a treatment, then apply.';

                const record = getSelectedRecord();
                selectedLegend = record ? record.code : null;

                updateLegendActiveState();
                renderSelectedToothLegendList();
                updateActionButtons();
                update3DSurfacePicker();
            }

            function renderLegendButtons(searchTerm = '') {
                legendContainer.innerHTML = '';

                const normalizedSearch = searchTerm.trim().toLowerCase();
                let totalResults = 0;

                legendCategories.forEach(category => {
                    const categoryLegends = category.items
                        .map(code => legends.find(item => item.code === code))
                        .filter(Boolean)
                        .filter(legend => {
                            if (!normalizedSearch) return true;
                            return (
                                legend.code.toLowerCase().includes(
                                    normalizedSearch) ||
                                legend.label.toLowerCase().includes(
                                    normalizedSearch)
                            );
                        });

                    if (!categoryLegends.length) return;

                    totalResults += categoryLegends.length;

                    const categoryBlock = document.createElement('div');
                    categoryBlock.className = 'legend-category-block';

                    categoryBlock.innerHTML = `
                        <div class="legend-category-head">
                            <div class="legend-category-title">
                                <span class="legend-category-icon">
                                    <i class="${category.icon}"></i>
                                </span>
                                <div>
                                    <p class="odontogram-legend-category-name">
                                        ${category.title}
                                    </p>
                                </div>
                            </div>
                            <span class="legend-category-count">${categoryLegends.length}</span>
                        </div>
                    `;

                    const body = document.createElement('div');
                    body.className = 'legend-category-body';

                    categoryLegends.forEach(legend => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.dataset.code = legend.code;
                        btn.className = 'legend-btn';
                        btn.title =
                            `${legend.displayCode || legend.code} - ${legend.label}`;

                        btn.innerHTML = `
                            <span class="legend-color-dot" data-ui-style="background:${window.uiAttribute(legendColors[legend.code])};"></span>
                            <i class="${legendIcons[legend.code]}"></i>
                            <span class="legend-meta">
                                <span class="legend-code">${legend.displayCode || legend.code}</span>
                                <span class="legend-label">${legend.label}</span>
                            </span>
                        `;

                        btn.addEventListener('click', function() {
                            if (!selectedTooth || !selectedTargetType) {
                                showProcedureToast(
                                    'Select a tooth surface first, then choose a treatment legend.',
                                    'warning',
                                    'Select a Tooth'
                                );
                                return;
                            }

                            selectedLegend = legend.code;
                            selectedLegendPreview.textContent =
                                `${legend.displayCode || legend.code} - ${legend.label}`;
                            drawerSelectedLegendPreview.textContent =
                                `${legend.displayCode || legend.code} - ${legend.label}`;
                            updateLegendActiveState();
                            updateActionButtons();
                        });

                        body.appendChild(btn);
                    });

                    categoryBlock.appendChild(body);
                    legendContainer.appendChild(categoryBlock);
                });

                legendResultCount.textContent =
                    String(totalResults);

                if (totalResults === 0) {
                    window.renderGlobalSearchEmptyState?.({
                        host: legendEmptyState,
                        input: '#legendSearchInput',
                        title: 'No treatments found',
                        message: 'Try another name or code.',
                        icon: 'fa-tooth',
                        className: 'empty-state-compact',
                    });
                } else {
                    window.hideGlobalSearchEmptyState?.(
                        legendEmptyState
                    );
                }

                updateLegendActiveState();
            }

            window.filterOdontogramLegends = function(query = '') {
                renderLegendButtons(query);
            };

            function createLegendPayload(code) {
                const legendObj = getLegendByCode(code);
                return {
                    code: code,
                    label: legendObj ? legendObj.label : code,
                    colorHex: legendColors[code] || '#ef4444'
                };
            }

            function isAdultTooth(toothNumber) {
                return adultUpper.includes(toothNumber) || adultLower.includes(toothNumber);
            }

            function getPreferredToothVisual(state) {
                if (!state) return null;

                if (state.status) return state.status;

                const surfacePriority = ['center', 'top', 'right', 'bottom', 'left'];
                for (const key of surfacePriority) {
                    if (state.surfaces[key]) return state.surfaces[key];
                }

                return null;
            }

            function getFirstTreatedSurfaceKey(state) {
                if (!state) return null;
                if (!state.surfaces) return null;

                const surfacePriority = ['top', 'left', 'center', 'right', 'bottom'];

                for (const key of surfacePriority) {
                    if (state.surfaces[key] && state.surfaces[key].code) {
                        return key;
                    }
                }

                return null;
            }

            function getPreferredSurfaceKey(state) {
                if (!state || !state.surfaces) return null;

                const lastSurface = state.lastSelectedSurface;

                if (
                    lastSurface &&
                    state.surfaces[lastSurface] &&
                    state.surfaces[lastSurface].code
                ) {
                    return lastSurface;
                }

                return getFirstTreatedSurfaceKey(state);
            }

            function getStatusBoxDisplayRecord(state) {
                if (!state) return null;

                if (state.status?.code) return state.status;

                if (!state.surfaces) return null;

                const preferredSurfaceKey = getPreferredSurfaceKey(state);

                if (
                    preferredSurfaceKey &&
                    state.surfaces[preferredSurfaceKey] &&
                    state.surfaces[preferredSurfaceKey].code
                ) {
                    return state.surfaces[preferredSurfaceKey];
                }

                return null;
            }

            function applyDividedStatusBoxVisual(statusBox, record) {
                const colorPart = document.createElement('span');
                const codePart = document.createElement('span');
                const colorHex = record ? record.colorHex : '#ffffff';
                const code = record ? record.code : '';

                statusBox.classList.add('status-box-divided');
                statusBox.innerHTML = '';

                statusBox.style.padding = '0';
                statusBox.style.display = 'flex';
                statusBox.style.flexDirection = 'column';
                statusBox.style.alignItems = 'stretch';
                statusBox.style.justifyContent = 'stretch';
                statusBox.style.overflow = 'hidden';
                statusBox.style.boxSizing = 'border-box';
                statusBox.style.background = '#ffffff';
                statusBox.style.borderColor = record ? colorHex : '';

                colorPart.className = 'status-box-color-part';
                colorPart.style.display = 'flex';
                colorPart.style.alignItems = 'center';
                colorPart.style.justifyContent = 'center';
                colorPart.style.flex = '1 1 0';
                colorPart.style.minHeight = '0';
                colorPart.style.width = '100%';
                colorPart.style.background = record ? colorHex : '#ffffff';

                codePart.className = 'status-box-code-part';
                codePart.textContent = code;
                codePart.style.display = 'flex';
                codePart.style.alignItems = 'center';
                codePart.style.justifyContent = 'center';
                codePart.style.flex = '1 1 0';
                codePart.style.minHeight = '0';
                codePart.style.width = '100%';
                codePart.style.background = '#ffffff';
                codePart.style.borderTop = '1px solid #8B0000';
                codePart.style.color = record ? colorHex : 'transparent';
                codePart.style.fontSize = code.length > 1 ? '8.5px' : '10px';
                codePart.style.fontWeight = '900';
                codePart.style.lineHeight = '1';
                codePart.style.letterSpacing = '-0.03em';
                codePart.style.whiteSpace = 'nowrap';

                statusBox.appendChild(colorPart);
                statusBox.appendChild(codePart);
            }


            function restoreSavedSurfaceSelectionForTooth(toothNumber) {
                const state = ensureToothState(toothNumber);
                const savedSurfaceKey = getPreferredSurfaceKey(state);

                if (!savedSurfaceKey) {
                    selectedTargetType = null;
                    selectedSurfaceKey = null;
                    selectedLegend = null;
                    return;
                }

                selectedTargetType = 'surface';
                selectedSurfaceKey = savedSurfaceKey;
                selectedLegend = state.surfaces[savedSurfaceKey].code;
                state.lastSelectedSurface = savedSurfaceKey;
            }

            function sync3DFrom2D(toothNumber) {
                const state = ensureToothState(toothNumber);

                if (!isAdultTooth(toothNumber)) {
                    state.threeD = null;
                    return;
                }

                state.threeD = state.status || null;
            }

            function fillAll2DSurfacesFromLegend(state, payload) {
                state.status = payload;
                state.surfaces.top = null;
                state.surfaces.left = null;
                state.surfaces.center = null;
                state.surfaces.right = null;
                state.surfaces.bottom = null;
            }

            function clearAll2DSurfaces(state) {
                state.status = null;
                state.surfaces.top = null;
                state.surfaces.left = null;
                state.surfaces.center = null;
                state.surfaces.right = null;
                state.surfaces.bottom = null;
            }

            function snapshotState() {
                return JSON.stringify(odontogramState);
            }

            function restoreStateFromSnapshot(snapshot) {
                const parsed = JSON.parse(snapshot);

                Object.keys(odontogramState).forEach(key => delete odontogramState[key]);
                Object.keys(parsed).forEach(key => {
                    odontogramState[key] = parsed[key];
                });

                updateHiddenInput();
                render2DOdontogram();

                if (odontogramThreeState) {
                    renderThreeVisuals();
                }

                updateSelectedToothUI();
                updateLegendActiveState();
                renderSelectedToothLegendList();
                updateActionButtons();
                updateHistoryButtons();
            }

            function pushHistory() {
                historyStack.push(snapshotState());
                if (historyStack.length > HISTORY_LIMIT) {
                    historyStack.shift();
                }
                redoStack = [];
                updateHistoryButtons();
            }

            function updateHistoryButtons() {
                if (undoBtn) undoBtn.disabled = historyStack.length === 0;
                if (redoBtn) redoBtn.disabled = redoStack.length === 0;
                if (clearSelectionBtn) {
                    clearSelectionBtn.disabled = !(
                        selectedTargets.size > 0 ||
                        (selectedTooth && selectedTargetType)
                    );
                }
            }

            function clearCurrentSelection() {
                if (currentView === '3d') {
                    clear3DSurfacePickerSelection(true);
                    return;
                }

                selectedTargets.clear();
                selectedTooth = null;
                selectedTargetType = null;
                selectedSurfaceKey = null;
                selectedMesh = null;
                selectedLegend = null;

                render2DOdontogram();

                if (odontogramThreeState) {
                    renderThreeVisuals();
                }

                updateSelectedToothUI();
                updateLegendActiveState();
                updateActionButtons();
                updateHistoryButtons();
            }

            function undoLastAction() {
                if (!historyStack.length) return;

                redoStack.push(snapshotState());
                const previous = historyStack.pop();
                restoreStateFromSnapshot(previous);
            }

            function redoLastAction() {
                if (!redoStack.length) return;

                historyStack.push(snapshotState());
                const next = redoStack.pop();
                restoreStateFromSnapshot(next);
            }

            function applyLegendToSelectedTarget(code) {
                if (!selectedTooth || !selectedTargetType) return;

                const targets =
                    selectedTargets.size > 0 ?
                    Array.from(selectedTargets.values()) : [{
                        tooth: selectedTooth,
                        targetType: selectedTargetType,
                        surfaceKey: selectedSurfaceKey
                    }];

                pushHistory();

                const payload = createLegendPayload(code);
                let replacedWholeTooth = false;
                let replacedSurfaces = false;

                targets.forEach(target => {
                    const state = ensureToothState(target.tooth);

                    if (
                        target.targetType === 'surface' &&
                        target.surfaceKey
                    ) {
                        replacedWholeTooth = replacedWholeTooth || !!state.status;
                        state.status = null;
                        state.threeD = null;
                        state.surfaces[target.surfaceKey] = {
                            ...payload
                        };
                        state.lastSelectedSurface = target.surfaceKey;

                        sync3DFrom2D(target.tooth);
                        return;
                    }

                    if (['whole', '3d', 'status'].includes(target.targetType)) {
                        replacedSurfaces = replacedSurfaces ||
                            Object.values(state.surfaces || {}).some(Boolean);
                        state.threeD = {
                            ...payload
                        };

                        fillAll2DSurfacesFromLegend(
                            state, {
                                ...payload
                            }
                        );
                    }
                });

                hasAppliedTreatmentThisSession = true;

                if (targets.length === 1) {
                    if (replacedWholeTooth) {
                        showProcedureToast(
                            'The whole tooth treatment was replaced by this surface treatment.',
                            'info',
                            'Target Updated'
                        );
                    } else if (replacedSurfaces) {
                        showProcedureToast(
                            'Existing surface treatments were replaced by the whole tooth treatment.',
                            'info',
                            'Target Updated'
                        );
                    }
                } else {
                    showProcedureToast(
                        `${getLegendDisplayCode(payload.code)} - ${payload.label} was applied to ${targets.length} selected targets.`,
                        'success',
                        'Multiple Targets Updated'
                    );
                }

                updateHiddenInput();

                if (currentView === '2d') {
                    clearCurrentSelection();

                    selectedLegendPreview.textContent =
                        'None selected';

                    drawerSelectedLegendPreview.textContent =
                        'None selected';

                    return;
                }

                render2DOdontogram();

                if (odontogramThreeState) {
                    renderThreeVisuals();
                }

                renderSelectedToothLegendList();
                updateLegendActiveState();
                updateActionButtons();

                selectedLegendPreview.textContent =
                    `${getLegendDisplayCode(payload.code)} - ${payload.label}`;

                drawerSelectedLegendPreview.textContent =
                    `${getLegendDisplayCode(payload.code)} - ${payload.label}`;

                if (currentView === '3d') {
                    clear3DSurfacePickerSelection(false);
                }
            }

            function clearSelectedTargetTreatment() {
                if (
                    !Array.isArray(
                        pendingResetPayload
                    ) ||
                    !pendingResetPayload.length
                ) {
                    return;
                }

                const targetsToClear = [...pendingResetPayload];

                pushHistory();

                targetsToClear.forEach(
                    target => {
                        const {
                            tooth,
                            targetType,
                            surfaceKey
                        } = target;

                        const state =
                            ensureToothState(
                                tooth
                            );

                        if (
                            targetType === 'surface' &&
                            surfaceKey
                        ) {
                            state.surfaces[
                                surfaceKey
                            ] = null;

                            state.lastSelectedSurface =
                                getFirstTreatedSurfaceKey(
                                    state
                                );

                            sync3DFrom2D(
                                tooth
                            );

                            return;
                        }

                        if (
                            [
                                'whole',
                                '3d',
                                'status'
                            ].includes(
                                targetType
                            )
                        ) {
                            state.threeD = null;
                            state.lastSelectedSurface = null;

                            clearAll2DSurfaces(
                                state
                            );
                        }
                    }
                );

                const clearedCount =
                    targetsToClear.length;

                selectedLegend = null;
                pendingResetPayload = null;

                selectedLegendPreview.textContent =
                    'None selected';

                drawerSelectedLegendPreview.textContent =
                    'None selected';

                updateHiddenInput();

                closeResetModal();

                if (
                    clearedCount > 1
                ) {
                    showProcedureToast(
                        `Treatments were cleared from ${clearedCount} selected targets.`,
                        'success',
                        'Multiple Treatments Cleared'
                    );
                }

                clearCurrentSelection();
            }

            function openResetModal() {
                if (
                    !selectedTooth ||
                    !selectedTargetType
                ) {
                    return;
                }

                const targets =
                    selectedTargets.size > 0 ?
                    Array.from(
                        selectedTargets.values()
                    ) : [{
                        tooth: selectedTooth,

                        targetType: selectedTargetType,

                        surfaceKey: selectedSurfaceKey
                    }];

                const treatedTargets =
                    targets.filter(
                        target => {
                            const state =
                                ensureToothState(
                                    target.tooth
                                );

                            if (
                                target.targetType === 'surface' &&
                                target.surfaceKey
                            ) {
                                return !!state
                                    .surfaces[
                                        target.surfaceKey
                                    ];
                            }

                            if (
                                [
                                    'whole',
                                    'status',
                                    '3d'
                                ].includes(
                                    target.targetType
                                )
                            ) {
                                return !!state.status;
                            }

                            return false;
                        }
                    );

                if (!treatedTargets.length) {
                    return;
                }

                pendingResetPayload =
                    treatedTargets;

                if (
                    treatedTargets.length > 1
                ) {
                    resetTreatmentMessage.textContent =
                        `Are you sure you want to clear the treatments from ${treatedTargets.length} selected targets?`;

                    openUiModal(
                        resetTreatmentModal
                    );

                    return;
                }

                const target =
                    treatedTargets[0];

                const partText =
                    target.targetType === 'surface' ?
                    getSurfaceLabel(
                        target.surfaceKey,
                        target.tooth
                    ) :
                    target.targetType === 'status' ?
                    'Status Box' :
                    'Whole Tooth';

                resetTreatmentMessage.textContent =
                    `Are you sure you want to reset the treatment for tooth #${target.tooth} (${getToothName(target.tooth)}) - ${partText}?`;

                openUiModal(
                    resetTreatmentModal
                );
            }

            function closeResetModal() {
                pendingResetPayload = null;
                closeUiModal(resetTreatmentModal);
            }

            function createToothUnit(toothNumber, statusPlacement = 'top') {
                const state = ensureToothState(toothNumber);
                const endpointSurfaceIndicators = {
                    18: {
                        side: 'left',
                        label: 'Distal'
                    },
                    11: {
                        side: 'right',
                        label: 'Mesial'
                    },
                    21: {
                        side: 'left',
                        label: 'Mesial'
                    },
                    28: {
                        side: 'right',
                        label: 'Distal'
                    },
                    48: {
                        side: 'left',
                        label: 'Distal'
                    },
                    41: {
                        side: 'right',
                        label: 'Mesial'
                    },
                    31: {
                        side: 'left',
                        label: 'Mesial'
                    },
                    38: {
                        side: 'right',
                        label: 'Distal'
                    }
                };

                const wrap = document.createElement('div');
                wrap.className = 'tooth-unit';

                const statusBox = document.createElement('div');
                statusBox.className = 'status-box';
                statusBox.dataset.tooth = toothNumber;
                statusBox.setAttribute('aria-label', `Select whole tooth #${toothNumber}`);
                statusBox.title = 'Select this whole tooth.';
                statusBox.style.cursor = 'pointer';

                const statusDisplayRecord = getStatusBoxDisplayRecord(state);

                applyDividedStatusBoxVisual(
                    statusBox,
                    statusDisplayRecord
                );

                if (statusDisplayRecord) {
                    statusBox.classList.add(
                        'has-treatment'
                    );
                }

                if (isTargetSelected(toothNumber, 'whole')) {
                    statusBox.classList.add('selected-target');

                    if (selectedTargets.size > 1) {
                        statusBox.classList.add('multi-selected-target');
                    }
                }

                statusBox.addEventListener('click', function(event) {
                    currentView = '2d';
                    select2DTarget({
                        tooth: toothNumber,
                        targetType: 'whole',
                        surfaceKey: null
                    }, event.shiftKey);
                    updateScopeButtons();
                    updateSelectedToothUI();
                    render2DOdontogram();
                });

                const toothNumberEl = document.createElement('div');
                toothNumberEl.className = 'tooth-number';
                toothNumberEl.textContent = toothNumber;

                const toothFace = document.createElement('div');
                toothFace.className = 'tooth-2d-wrapper';

                const toothFaceFrame = document.createElement('div');
                toothFaceFrame.className = 'tooth-2d-frame';
                toothFaceFrame.appendChild(toothFace);

                toothFace.innerHTML = `
                    <svg viewBox="0 0 100 100" class="tooth-svg" preserveAspectRatio="xMidYMid meet">
                        <path class="surface-part surface-top" data-surface="top" d="M 0 0 L 100 0 L 50 50 Z" />
                        <path class="surface-part surface-right" data-surface="right" d="M 100 0 L 100 100 L 50 50 Z" />
                        <path class="surface-part surface-bottom" data-surface="bottom" d="M 100 100 L 0 100 L 50 50 Z" />
                        <path class="surface-part surface-left" data-surface="left" d="M 0 100 L 0 0 L 50 50 Z" />
                        <circle class="surface-part surface-center" data-surface="center" cx="50" cy="50" r="22" />
                    </svg>
                `;

                const surfaces = toothFace.querySelectorAll('.surface-part');
                surfaces.forEach(part => {
                    const surface = part.dataset.surface;
                    const surfaceRecord = state.surfaces[surface] || state.status;

                    if (surfaceRecord) {
                        part.style.fill =
                            surfaceRecord.colorHex;

                        part.classList.add(
                            'has-treatment'
                        );
                    }

                    if (isTargetSelected(toothNumber, 'surface', surface)) {
                        part.classList.add('selected-target');

                        if (selectedTargets.size > 1) {
                            part.classList.add('multi-selected-target');
                        }
                    }

                    part.addEventListener('click', function(event) {
                        currentView = '2d';
                        select2DTarget({
                            tooth: toothNumber,
                            targetType: 'surface',
                            surfaceKey: surface
                        }, event.shiftKey);
                        updateScopeButtons();
                        updateSelectedToothUI();
                        render2DOdontogram();
                    });

                    part.addEventListener('mouseenter', function() {
                        toothHoverLabel.innerText = `Tooth #${toothNumber}`;
                    });

                    part.addEventListener('mouseleave', function() {
                        toothHoverLabel.innerText = selectedTooth ?
                            `Selected: #${selectedTooth}` :
                            'Select a tooth';
                    });
                });

                const endpointIndicator = endpointSurfaceIndicators[toothNumber];
                if (endpointIndicator) {
                    const indicator = document.createElement('span');
                    indicator.className = [
                        'tooth-endpoint-surface-label',
                        `tooth-endpoint-surface-label-${endpointIndicator.side}`,
                        `tooth-endpoint-surface-label-${statusPlacement}`
                    ].join(' ');
                    indicator.textContent = endpointIndicator.label;
                    indicator.setAttribute('aria-hidden', 'true');
                    toothFaceFrame.appendChild(indicator);
                }

                if (statusPlacement === 'top') {
                    wrap.appendChild(statusBox);
                    wrap.appendChild(toothNumberEl);
                    wrap.appendChild(toothFaceFrame);
                } else {
                    wrap.appendChild(toothFaceFrame);
                    wrap.appendChild(toothNumberEl);
                    wrap.appendChild(statusBox);
                }

                return wrap;
            }

            function createRow(leftTeeth, rightTeeth, statusPlacement = 'top', leftLabel = '',
                rightLabel = '',
                surfaceOrientation = null) {
                const row = document.createElement('div');
                row.className = 'odontogram-row';

                if (surfaceOrientation) {
                    row.classList.add('odontogram-surface-orientation-row');
                    row.classList.add(`odontogram-surface-orientation-row-${statusPlacement}`);
                }

                const orientationLabels = surfaceOrientation ?
                    `<span class="surface-side-label surface-side-label-${surfaceOrientation.top.toLowerCase()}">${surfaceOrientation.top}</span>` +
                    `<span class="surface-side-label surface-side-label-${surfaceOrientation.bottom.toLowerCase()}">${surfaceOrientation.bottom}</span>` :
                    '';

                const left = document.createElement('div');
                left.className = 'status-label-left';
                left.innerHTML = surfaceOrientation ?
                    orientationLabels :
                    (leftLabel ? leftLabel : '&nbsp;');

                if (surfaceOrientation) {
                    left.classList.add('surface-orientation-side');
                } else if (statusPlacement === 'top') {
                    left.style.paddingTop = '10px';
                } else {
                    left.style.alignSelf = 'flex-end';
                    left.style.paddingBottom = '10px';
                }
                row.appendChild(left);

                leftTeeth.forEach(tooth => row.appendChild(createToothUnit(tooth,
                    statusPlacement)));

                const spacer = document.createElement('div');
                spacer.className = 'odontogram-arch-spacer';
                row.appendChild(spacer);

                rightTeeth.forEach(tooth => row.appendChild(createToothUnit(tooth,
                    statusPlacement)));

                const right = document.createElement('div');
                right.className = 'status-label-right';
                right.innerHTML = surfaceOrientation ?
                    orientationLabels :
                    (rightLabel ? rightLabel : '&nbsp;');

                if (surfaceOrientation) {
                    right.classList.add('surface-orientation-side');
                } else if (statusPlacement === 'top') {
                    right.style.paddingTop = '10px';
                } else {
                    right.style.alignSelf = 'flex-end';
                    right.style.paddingBottom = '10px';
                }
                row.appendChild(right);

                return row;
            }

            function render2DOdontogram() {
                board2d.innerHTML = '';

                const row1 = createRow(primaryUpperRight, primaryUpperLeft, 'top',
                    'STATUS<br>RIGHT', 'LEFT');
                row1.classList.add('arch-divider');
                board2d.appendChild(row1);

                const row2 = createRow(adultUpperRight, adultUpperLeft, 'top', '', '', {
                    top: 'Buccal',
                    bottom: 'Palatal'
                });
                row2.style.marginBottom = '24px';
                board2d.appendChild(row2);

                const row3 = createRow(adultLowerRight, adultLowerLeft, 'bottom', '', '', {
                    top: 'Lingual',
                    bottom: 'Buccal'
                });
                row3.classList.add('arch-divider');
                board2d.appendChild(row3);

                const row4 = createRow(primaryLowerRight, primaryLowerLeft, 'bottom',
                    'STATUS<br>RIGHT', 'LEFT');
                board2d.appendChild(row4);
            }

            async function switchView(view) {
                const previousView = currentView;
                currentView = view;

                if (view === '2d') {
                    selectedMesh = null;
                    panel2d.classList.add('active');
                    panel3d.classList.remove('active');
                    view2dBtn.classList.add('active');
                    view3dBtn.classList.remove('active');
                    viewInstructionText.textContent =
                        'Select a surface to begin treatment';
                } else {
                    panel2d.classList.remove('active');
                    panel3d.classList.add('active');
                    view2dBtn.classList.remove('active');
                    view3dBtn.classList.add('active');
                    viewInstructionText.textContent =
                        'Click a surface; Shift + Click to select multiple';

                    if (previousView !== '3d') {
                        selectedTargets.clear();
                        selectedTooth = null;
                        selectedTargetType = null;
                        selectedSurfaceKey = null;
                        selectedMesh = null;
                        selectedLegend = null;
                    }

                    if (!odontogramThreeState) {
                        await initThreeScene();
                    } else {
                        handleResize();
                        renderThreeVisuals();
                    }
                }

                if (
                    view === '3d' &&
                    odontogramThreeState &&
                    selectedTooth
                ) {
                    selectedMesh =
                        window.Odontogram3D
                        .getToothMesh(
                            odontogramThreeState,
                            selectedTooth
                        );

                    renderThreeVisuals();
                }

                updateSelectedToothUI();
                update3DSurfacePicker();
                if (view === '2d') render2DOdontogram();
            }

            view2dBtn.addEventListener('click', () => switchView('2d'));
            view3dBtn.addEventListener(
                'click', () => switchView('3d'));

            function updateScopeButtons() {
                const isWhole = selectedScope === 'whole';
                wholeToothScopeBtn?.classList.toggle('active', isWhole);
                toothSurfaceScopeBtn?.classList.toggle('active', !isWhole);
                wholeToothScopeBtn?.setAttribute('aria-pressed', String(isWhole));
                toothSurfaceScopeBtn?.setAttribute('aria-pressed', String(!isWhole));
                update3DSurfacePicker();
            }

            function selectTargetScope(scope) {
                selectedScope = scope;
                selectedSurfaceKey = null;
                selectedTargets.clear();

                if (selectedTooth) {
                    const state = ensureToothState(selectedTooth);
                    selectedTargetType = scope === 'whole' ? 'whole' : null;
                    selectedLegend = scope === 'whole' ? state.status?.code || null : null;

                    if (scope === 'whole') {
                        const target = {
                            tooth: Number(selectedTooth),
                            targetType: 'whole',
                            surfaceKey: null
                        };
                        selectedTargets.set(
                            getTargetSelectionKey(target.tooth, target.targetType),
                            target
                        );
                    }
                }

                updateScopeButtons();
                updateSelectedToothUI();
                render2DOdontogram();
                renderThreeVisuals();
            }

            wholeToothScopeBtn?.addEventListener('click', () => selectTargetScope(
                'whole'));
            toothSurfaceScopeBtn?.addEventListener('click', () => selectTargetScope(
                'surface'));

            function showTooltip(event, mesh, surfaceKey = null) {
                const toothNumber = mesh.userData.tooth;
                const toothName = getToothName(toothNumber);
                const state = ensureToothState(toothNumber);
                const hasSurfaces = Object.values(state.surfaces || {}).some(item => item?.code);
                const wholeTreatment = state.status || (!hasSurfaces ? state.threeD : null);
                const treatment = surfaceKey ? state.surfaces[surfaceKey] || wholeTreatment :
                    wholeTreatment;

                toothTooltipContent.innerHTML = `
    <div class="tooth-tooltip-number">
        Tooth #${toothNumber}
    </div>

    <div class="tooth-tooltip-name">
        ${toothName}
    </div>

    <div class="tooth-tooltip-help">
        ${surfaceKey ? getSurfaceLabel(surfaceKey, toothNumber) : 'Whole Tooth'} · Click to select. Shift + Click to add.
    </div>

    <div class="tooth-tooltip-treatment ${treatment ? 'has-treatment' : ''}">
        ${treatment
                    ? `Treatment: ${getLegendDisplayCode(treatment.code)} - ${treatment.label}`
                    : 'No treatment assigned'
                }
    </div>
`;

                const rect = container.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;

                toothTooltip.style.left = `${x}px`;
                toothTooltip.style.top = `${y}px`;
                toothTooltip.classList.add('show');
            }

            function hideTooltip() {
                toothTooltip.classList.remove('show');
            }

            function getProcedureTime24Hour(field) {
                // The shared Flatpickr shows h:i K and keeps H:i in the original input.
                return /^([01]\d|2[0-3]):[0-5]\d$/.test(field.value) ? field.value : null;
            }

            function parseProcedureDurationSeconds() {
                if (!/^\d{2,3}:[0-5]\d:[0-5]\d$/.test(procedureDurationInput.value)) return null;
                const [hours, minutes, seconds] = procedureDurationInput.value.split(':').map(Number);
                return hours * 3600 + minutes * 60 + seconds;
            }

            function formatProcedureDurationInput(event) {
                const input = procedureDurationInput;
                const digits = input.value.replace(/\D/g, '').slice(-6);
                const padded = digits.padStart(6, '0');
                const clearedByDeletion = event?.inputType?.startsWith('delete') && !/[1-9]/.test(digits);
                input.value = digits && !clearedByDeletion ?
                    `${padded.slice(0, 2)}:${padded.slice(2, 4)}:${padded.slice(4, 6)}` : '';
                input.setSelectionRange?.(input.value.length, input.value.length);
            }

            function validateProcedureTimingFields({
                focus = true
            } = {}) {
                let firstInvalid = null;
                [procedureTimeInInput, procedureDurationInput].forEach(field => {
                    const valid = window.validateFormInputField?.(field) ?? field.checkValidity();
                    if (!valid && !firstInvalid) firstInvalid = field;
                });
                if (!firstInvalid && getProcedureTime24Hour(procedureTimeInInput) === null) {
                    firstInvalid = procedureTimeInInput;
                    window.showFormInputValidationMessage?.(firstInvalid, 'Please select a valid Time In.');
                }
                if (!firstInvalid && parseProcedureDurationSeconds() === null) {
                    firstInvalid = procedureDurationInput;
                    window.showFormInputValidationMessage?.(firstInvalid,
                        'Enter a valid duration in HH:MM:SS format, e.g. 00:30:00.');
                }
                [procedureTimeInInput, procedureDurationInput].forEach(field => {
                    field._flatpickr?.altInput?.classList.toggle('is-invalid', field === firstInvalid);
                });
                if (firstInvalid && focus) window.focusGlobalInvalidField?.(firstInvalid._flatpickr?.altInput ||
                    firstInvalid);
                return !firstInvalid;
            }

            [procedureTimeInInput, procedureDurationInput].forEach(field => {
                const onTimeChange = event => {
                    if (field === procedureDurationInput) formatProcedureDurationInput(event);
                    window.showFormInputValidationMessage?.(field, '');
                    field._flatpickr?.altInput?.classList.remove('is-invalid');
                    markProcedureDiscardDirty();
                };
                field.addEventListener('input', onTimeChange);
                field.addEventListener('change', onTimeChange);
            });

            applyTreatmentBtn.addEventListener('click', function() {
                if (!selectedTooth || !selectedTargetType) {
                    showProcedureToast(
                        'Please select a tooth surface before choosing a treatment legend.',
                        'warning',
                        'Select a Tooth'
                    );
                    selectedLegendPreview.textContent = selectedLegend ?
                        `${selectedLegend} - ${(getLegendByCode(selectedLegend)?.label || selectedLegend)}` :
                        'None selected';
                    return;
                }

                if (!selectedLegend) {
                    showProcedureToast(
                        'Choose a treatment legend before applying treatment.',
                        'warning',
                        'Select a Legend'
                    );
                    return;
                }

                applyLegendToSelectedTarget(selectedLegend);
            });

            clearCurrentToothBtn.addEventListener('click', function() {
                if (
                    !selectedTooth ||
                    !selectedTargetType
                ) {
                    return;
                }
                openResetModal();
            });

            confirmResetTreatmentBtn.addEventListener('click',
                clearSelectedTargetTreatment);
            cancelResetTreatmentBtn.addEventListener('click',
                closeResetModal);

            resetTreatmentModal.addEventListener('click', function(event) {
                if (event.target === resetTreatmentModal) {
                    closeResetModal();
                }
            });

            window.addEventListener('resize', handleResize);

            const finishProcedureBtn = document.getElementById('finishProcedureBtn');
            const followUpBtn = document.getElementById('followUpBtn');
            const closeFollowUpModalBtn = document.getElementById('closeFollowUpModalBtn');
            const cancelFollowUpModalBtn = document.getElementById('cancelFollowUpModalBtn');
            const followUpSuccessModal = document.getElementById('followUpSuccessModal');
            const followUpSuccessDate = document.getElementById('followUpSuccessDate');
            const followUpSuccessTime = document.getElementById('followUpSuccessTime');
            const followUpSuccessPatient = document.getElementById('followUpSuccessPatient');
            const followUpSuccessService = document.getElementById('followUpSuccessService');
            const followUpSuccessReason = document.getElementById('followUpSuccessReason');
            const followUpSuccessBackBtn = document.getElementById('followUpSuccessBackBtn');

            let followUpSuccessRedirectUrl = null;
            const saveProcedureUrl = @json($saveProcedureUrl ?? null);
            const storeFollowUpUrl = @json(
                !$existingAppointmentMode && $appointment
                    ? route('dentist.dentist.appointments.follow-up.store', $appointment->id)
                    : null);

            function showProcedureToast(message, type = 'success', title = null) {
                window.showToast?.({
                    type,
                    title: title ||
                        (
                            type === 'error' ?
                            'Action Required' :
                            type === 'warning' ?
                            'Attention' :
                            'Success'
                        ),
                    message,
                    duration: 4000,
                });
            }

            function resetFollowUpForm() {
                const form = document.getElementById('followUpForm');
                const dateInput = document.getElementById('followup_appointment_date');
                const timeInput = document.getElementById('followup_appointment_time');
                const reasonInput = document.getElementById('followup_reason');
                const confirmBtn =
                    document.getElementById(
                        'confirmFollowUpBtn'
                    );

                const dateBanner =
                    document.getElementById(
                        'followUpDateBanner'
                    );

                const slotPlaceholder =
                    document.getElementById(
                        'followUpSlotPlaceholder'
                    );

                const slotContainer =
                    document.getElementById(
                        'followUpSlotContainer'
                    );

                const slotGrid =
                    document.getElementById(
                        'followUpSlotGrid'
                    );

                const selectedSlotDisplay =
                    document.getElementById(
                        'followUpSelectedSlotDisplay'
                    );

                const selectedSlotText =
                    document.getElementById(
                        'followUpSelectedSlotText'
                    );

                const clearTimeBtn =
                    document.getElementById(
                        'followUpClearTimeBtn'
                    );

                const dateGroup =
                    document.getElementById(
                        'followUpCalendarWrap'
                    );

                const timeGroup =
                    document.getElementById(
                        'followUpTimeWrap'
                    );

                form?.reset();

                if (confirmBtn) {
                    confirmBtn.disabled = false;

                    confirmBtn.innerHTML = `
                <span>
                    Schedule Follow-Up
                </span>
            `;
                }

                window.DiscardChanges?.markNotSubmitting(form);

                if (dateInput) {
                    dateInput.value = '';
                }

                if (timeInput) {
                    timeInput.value = '';
                }

                if (reasonInput) {
                    reasonInput.value = '';
                }

                if (typeof selectedDate !== 'undefined') selectedDate = null;
                if (typeof selectedTime !== 'undefined') selectedTime = null;

                window.clearGlobalGroupError?.(
                    dateGroup,
                    'followup_appointment_date'
                );

                window.clearGlobalGroupError?.(
                    timeGroup,
                    'followup_appointment_time'
                );

                if (dateBanner) {
                    dateBanner.replaceChildren();
                    dateBanner.classList.add('hidden');
                    dateBanner.style.removeProperty('display');
                }

                if (slotPlaceholder) {
                    slotPlaceholder.classList.remove('hidden');
                    slotPlaceholder.style.removeProperty('display');
                }

                if (slotContainer) {
                    slotContainer.classList.add('hidden');
                    slotContainer.style.removeProperty('display');
                }

                if (slotGrid) {
                    slotGrid.replaceChildren();
                    slotGrid.style.removeProperty('display');
                }

                selectedSlotDisplay?.classList.add(
                    'hidden'
                );

                if (selectedSlotText) {
                    selectedSlotText.textContent = '';
                }

                clearTimeBtn?.classList.add(
                    'hidden'
                );
            }

            const followUpClearTimeBtn =
                document.getElementById(
                    'followUpClearTimeBtn'
                );

            followUpClearTimeBtn?.addEventListener(
                'click',
                function() {

                    const slotGrid =
                        document.getElementById(
                            'followUpSlotGrid'
                        );

                    const timeInput =
                        document.getElementById(
                            'followup_appointment_time'
                        );

                    const selectedDisplay =
                        document.getElementById(
                            'followUpSelectedSlotDisplay'
                        );

                    const selectedText =
                        document.getElementById(
                            'followUpSelectedSlotText'
                        );

                    if (
                        typeof selectedTime !==
                        'undefined'
                    ) {
                        selectedTime = null;
                    }

                    if (timeInput) {
                        timeInput.value = '';

                        timeInput.dispatchEvent(
                            new Event(
                                'change', {
                                    bubbles: true
                                }
                            )
                        );
                    }

                    slotGrid
                        ?.querySelectorAll(
                            '.slot-chip'
                        )
                        .forEach(chip => {

                            chip.classList.remove(
                                'selected'
                            );

                            chip.setAttribute(
                                'aria-pressed',
                                'false'
                            );
                        });

                    selectedDisplay?.classList.add(
                        'hidden'
                    );

                    if (selectedText) {
                        selectedText.textContent = '';
                    }

                    followUpClearTimeBtn.classList.add(
                        'hidden'
                    );
                }
            );

            function openFollowUpModal() {
                const modal =
                    document.getElementById(
                        'followUpModal'
                    );

                if (!modal) return;

                resetFollowUpForm();

                window.PatientUI
                    ?.initAvatars?.(modal);

                window.openModal?.(
                    'followUpModal'
                );
            }

            function openFollowUpSuccessModal(appointment, redirectUrl) {
                if (
                    !followUpSuccessModal ||
                    !appointment
                ) {
                    return;
                }

                followUpSuccessRedirectUrl =
                    redirectUrl ||
                    @json(route('dentist.dentist.appointments'));

                if (followUpSuccessDate) {
                    followUpSuccessDate.textContent =
                        appointment.appointment_date ||
                        '—';
                }

                if (followUpSuccessTime) {
                    followUpSuccessTime.textContent =
                        appointment.appointment_time ||
                        '—';
                }

                if (followUpSuccessPatient) {
                    followUpSuccessPatient.textContent =
                        appointment.patient_name ||
                        @json($patientName);
                }

                if (followUpSuccessService) {
                    followUpSuccessService.textContent =
                        appointment.service_type ||
                        'Follow-up';
                }

                if (followUpSuccessReason) {
                    followUpSuccessReason.textContent =
                        appointment.follow_up_reason ||
                        'No reason provided.';
                }

                window.openModal?.('followUpSuccessModal');

                requestAnimationFrame(
                    () => {
                        followUpSuccessBackBtn
                            ?.focus();
                    }
                );
            }

            function getCleanOdontogramDataForSave() {
                let rawData = [];

                try {
                    rawData = JSON.parse(odontogramDataInput.value || '[]');
                } catch (error) {
                    rawData = [];
                }

                if (!Array.isArray(rawData)) {
                    rawData = Object.values(rawData || {});
                }

                return rawData
                    .map(function(entry) {
                        const toothNumber = Number(entry?.tooth || 0);

                        if (!toothNumber) {
                            return null;
                        }

                        const cleanEntry = {
                            tooth: toothNumber,
                            toothName: entry.toothName || getToothName(toothNumber),
                            status: null,
                            surfaces: {
                                top: null,
                                left: null,
                                center: null,
                                right: null,
                                bottom: null,
                            },
                            threeD: null,
                            lastSelectedSurface: null,
                        };

                        if (entry.status && entry.status.code) {
                            cleanEntry.status = entry.status;
                        }

                        if (entry.threeD && entry.threeD.code) {
                            cleanEntry.threeD = entry.threeD;
                        }

                        const surfaces = entry.surfaces || {};

                        ['top', 'left', 'center', 'right', 'bottom'].forEach(function(
                            surfaceKey) {
                            if (surfaces[surfaceKey] && surfaces[surfaceKey].code) {
                                cleanEntry.surfaces[surfaceKey] = surfaces[surfaceKey];
                            }
                        });

                        const savedSurfaceKey = ['top', 'left', 'center', 'right', 'bottom']
                            .includes(entry
                                .lastSelectedSurface) ?
                            entry.lastSelectedSurface :
                            null;

                        cleanEntry.lastSelectedSurface =
                            savedSurfaceKey && cleanEntry.surfaces[savedSurfaceKey] ?
                            savedSurfaceKey :
                            getFirstTreatedSurfaceKey(cleanEntry);

                        if (!cleanEntry.threeD) {
                            cleanEntry.threeD = getPreferredToothVisual(cleanEntry);
                        }

                        const hasTreatment =
                            (cleanEntry.status && cleanEntry.status.code) ||
                            (cleanEntry.threeD && cleanEntry.threeD.code) ||
                            Object.values(cleanEntry.surfaces).some(function(surface) {
                                return surface && surface.code;
                            });

                        return hasTreatment ? cleanEntry : null;
                    })
                    .filter(Boolean);
            }

            function syncDiagnosisField() {
                if (!diagnosisSelect || !diagnosisCustomInput) {
                    return '';
                }

                const selectedValue = diagnosisSelect.value.trim();
                const useCustomDiagnosis = selectedValue === 'Others';

                diagnosisCustomInput.hidden = !useCustomDiagnosis;
                diagnosisCustomInput.required = useCustomDiagnosis;

                if (!useCustomDiagnosis) {
                    return selectedValue;
                }

                return diagnosisCustomInput.value.trim();
            }

            diagnosisSelect?.addEventListener('change', syncDiagnosisField);
            syncDiagnosisField();

            function validateFollowUpTreatmentRequirement() {
                if (
                    allowsProcedureCompletionWithoutOdontogramChanges
                ) {
                    return true;
                }

                updateHiddenInput();

                const cleanOdontogramData =
                    getCleanOdontogramDataForSave();

                const hasAnySavedTreatment =
                    cleanOdontogramData.length > 0;

                const hasRequiredTreatment =
                    hasAnySavedTreatment &&
                    (
                        hasAppliedTreatmentThisSession ||
                        (
                            savedVisitEditMode &&
                            hasAnySavedTreatment
                        )
                    );

                if (
                    hasRequiredTreatment
                ) {
                    return true;
                }

                showProcedureToast(
                    'Apply at least one treatment to the tooth chart before scheduling a follow-up appointment.',
                    'warning',
                    'Treatment Required'
                );

                const odontogramWorkspace =
                    document.querySelector(
                        '.odontogram-left-panel'
                    );

                if (odontogramWorkspace) {
                    window.setTimeout(() => {
                        odontogramWorkspace.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 80);
                }

                return false;
            }

            function validateProcedurePrerequisites() {
                updateHiddenInput();

                const cleanOdontogramData =
                    getCleanOdontogramDataForSave();

                const hasAnySavedTreatment =
                    cleanOdontogramData.length > 0;

                const diagnosisValue =
                    syncDiagnosisField();

                if (
                    diagnosisSelect?.value === 'Others' &&
                    !diagnosisValue
                ) {
                    diagnosisCustomInput?.focus();

                    showProcedureToast(
                        'Please enter a custom diagnosis for Others.',
                        'warning',
                        'Diagnosis Required'
                    );

                    return false;
                }

                if (
                    !validateProcedureTimingFields({
                        focus: true
                    })
                ) {
                    return false;
                }

                if (
                    !allowsProcedureCompletionWithoutOdontogramChanges &&
                    !hasAppliedTreatmentThisSession &&
                    !(
                        savedVisitEditMode &&
                        hasAnySavedTreatment
                    )
                ) {
                    showProcedureToast(
                        'Apply at least one treatment to the tooth chart before finishing the procedure.',
                        'warning',
                        'Treatment Required'
                    );

                    return false;
                }

                if (
                    !allowsProcedureCompletionWithoutOdontogramChanges &&
                    cleanOdontogramData.length === 0
                ) {
                    showProcedureToast(
                        'Apply at least one treatment to the tooth chart before finishing the procedure.',
                        'warning',
                        'Treatment Required'
                    );

                    return false;
                }

                return true;
            }

            async function saveProcedure(completionAction, clickedButton, loadingText) {
                const originalButtonHtml = clickedButton.innerHTML;

                updateHiddenInput();
                const cleanOdontogramData = getCleanOdontogramDataForSave();
                const hasAnySavedTreatment = cleanOdontogramData.length > 0;
                const diagnosisValue = syncDiagnosisField();

                if (diagnosisSelect?.value === 'Others' && !diagnosisValue) {
                    diagnosisCustomInput?.focus();
                    showProcedureToast('Please enter a custom diagnosis for Others.', 'warning',
                        'Diagnosis Required');
                    return;
                }

                if (
                    !validateProcedureTimingFields({
                        focus: true
                    })
                ) {
                    return;
                }


                if (!allowsProcedureCompletionWithoutOdontogramChanges && !
                    hasAppliedTreatmentThisSession && !(
                        savedVisitEditMode && hasAnySavedTreatment)) {
                    showProcedureToast(
                        'Apply at least one treatment to the tooth chart before finishing the procedure.',
                        'warning',
                        'Treatment Required'
                    );

                    return;
                }

                if (!allowsProcedureCompletionWithoutOdontogramChanges && cleanOdontogramData
                    .length === 0) {
                    showProcedureToast(
                        'Apply at least one treatment to the tooth chart before finishing the procedure.',
                        'warning',
                        'Treatment Required'
                    );

                    return;
                }

                const payload = {
                    odontogram_data: cleanOdontogramData,
                    oral_examination: document.getElementById('oralExaminationNotes').value,
                    diagnosis: diagnosisValue,
                    prescriptions: document.getElementById('prescriptionsNotes').value,
                    inventory_notes: document.getElementById('inventoryNotes').value,
                    completion_action: completionAction,
                    has_applied_treatment: allowsProcedureCompletionWithoutOdontogramChanges ||
                        hasAppliedTreatmentThisSession ||
                        (savedVisitEditMode && hasAnySavedTreatment),
                    procedure_started_at: getProcedureTime24Hour(procedureTimeInInput),
                    procedure_duration_hms: procedureDurationInput.value,
                };

                if (savedVisitEditMode) {
                    const reasonTypeId =
                        correctionReasonCategory
                        ?.value || '';

                    const selectedReasonOption =
                        correctionReasonCategory
                        ?.selectedOptions?.[0];

                    const requiresReasonDetail =
                        selectedReasonOption
                        ?.dataset
                        ?.requiresDetail === '1';

                    const correctionNotes =
                        correctionReasonNotes
                        ?.value
                        .trim() || '';

                    const reasonDetail =
                        correctionReasonOther
                        ?.value
                        .trim() || '';

                    payload.reason_type_id =
                        reasonTypeId ?
                        Number(reasonTypeId) :
                        null;

                    payload.reason_detail =
                        requiresReasonDetail ?
                        reasonDetail :
                        null;

                    payload.reason_notes =
                        correctionNotes;
                }

                finishProcedureBtn.disabled = true;
                if (followUpBtn) {
                    followUpBtn.disabled = true;
                }

                clickedButton.innerHTML =
                    `<i class="fa-solid fa-circle-notch fa-spin"></i> ${loadingText}`;

                try {
                    const response = await fetch(saveProcedureUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const responseText =
                        await response.text();

                    let result = null;

                    try {
                        result =
                            JSON.parse(
                                responseText
                            );
                    } catch (_) {
                        result = null;
                    }

                    if (!response.ok) {
                        Object.entries(result?.errors || {}).forEach(([name, messages]) => {
                            const field = document.getElementById(name);
                            if (field) window.showFormInputValidationMessage?.(field, messages[0]);
                        });

                        showProcedureToast(
                            result?.message ||
                            'Failed to save procedure. Please check your inputs and try again.',
                            'error'
                        );

                        return null;
                    }

                    if (completionAction === 'finished') {
                        openFinishProcedureModal({
                            title: existingAppointmentMode ?
                                'Existing Appointment Saved!' : (
                                    savedVisitEditMode ?
                                    'Odontogram Updated!' :
                                    'Procedure Completed!'
                                ),

                            message: existingAppointmentMode ?
                                'The existing appointment, notes, duration, and odontogram were saved successfully.' :
                                (
                                    savedVisitEditMode ?
                                    'The saved 2D/3D odontogram and clinical notes were updated successfully.' :
                                    'The odontogram and procedure notes were saved successfully. This appointment has been marked as completed.'
                                ),

                            icon: 'fa-clipboard-check',

                            buttonText: (
                                    existingAppointmentMode ||
                                    savedVisitEditMode
                                ) ?
                                'Back to Patient Profile' : 'Back to Appointments',

                            redirectUrl: result.redirect_url ||
                                null,
                        });
                    }

                    return result;

                } catch (error) {
                    console.error(error);

                    showProcedureToast(
                        'Something went wrong while saving the procedure.',
                        'error'
                    );

                    return null;
                } finally {
                    finishProcedureBtn.disabled = false;
                    if (followUpBtn) {
                        followUpBtn.disabled = false;
                    }
                    clickedButton.innerHTML = originalButtonHtml;
                }
            }

            finishProcedureBtn.addEventListener('click', function() {
                if (!validateProcedureTimingFields()) {
                    return;
                }

                updateHiddenInput();

                const cleanOdontogramData =
                    getCleanOdontogramDataForSave();

                const hasAnySavedTreatment =
                    cleanOdontogramData.length > 0;

                if (
                    !allowsProcedureCompletionWithoutOdontogramChanges &&
                    (
                        (!hasAppliedTreatmentThisSession &&
                            !(savedVisitEditMode && hasAnySavedTreatment)) ||
                        cleanOdontogramData.length === 0
                    )
                ) {
                    openFinishProcedureModal({
                        title: 'Treatment Required',
                        message: 'Select a tooth surface and apply at least one treatment before completing this procedure.',
                        icon: 'fa-tooth',
                        buttonText: 'Back to Odontogram',
                    });

                    return;
                }

                if (savedVisitEditMode) {
                    openCorrectionReasonModal();
                    return;
                }

                openFinishProcedureModal({
                    title: existingAppointmentMode ?
                        'Save Existing Appointment?' : 'Finish Procedure?',

                    message: existingAppointmentMode ?
                        'Are you sure you want to save this existing appointment? The old visit details, odontogram, and notes will be stored as a completed appointment.' :
                        'Are you sure you want to finish this procedure? The odontogram and procedure notes will be saved, and this appointment will be marked as completed.',

                    icon: 'fa-circle-question',
                    confirmation: true,
                });
            });

            dismissCorrectionReasonBtn?.addEventListener(
                'click',
                closeCorrectionReasonModal
            );

            confirmCorrectionReasonBtn
                ?.addEventListener(
                    'click',
                    function() {

                        const reasonTypeId =
                            correctionReasonCategory
                            ?.value
                            .trim() || '';

                        if (!reasonTypeId) {
                            window
                                .showFormInputValidationMessage?.(
                                    correctionReasonCategory,
                                    'Please select a reason for the correction.'
                                );

                            correctionReasonCategory
                                ?.closest('.custom-select')
                                ?.querySelector(
                                    '.custom-select-button'
                                )
                                ?.focus();

                            return;
                        }

                        const selectedReasonOption =
                            correctionReasonCategory
                            ?.selectedOptions?.[0];

                        const requiresReasonDetail =
                            selectedReasonOption
                            ?.dataset
                            ?.requiresDetail === '1';

                        const reasonDetail =
                            correctionReasonOther
                            ?.value
                            .trim() || '';

                        if (
                            requiresReasonDetail &&
                            !reasonDetail
                        ) {
                            window
                                .showFormInputValidationMessage?.(
                                    correctionReasonOther,
                                    'Please specify the correction reason.'
                                );

                            correctionReasonOther?.focus();

                            return;
                        }

                        window
                            .clearFormInputValidation?.(
                                correctionReasonOther
                            );

                        window
                            .clearFormInputValidation?.(
                                correctionReasonCategory
                            );

                        closeCorrectionReasonModal();

                        saveProcedure(
                            'finished',
                            finishProcedureBtn,
                            'Saving Correction...'
                        );
                    }
                );

            correctionReasonOther
                ?.addEventListener(
                    'input',
                    function() {

                        if (
                            correctionReasonOther
                            .value
                            .trim()
                        ) {
                            window
                                .clearFormInputValidation?.(
                                    correctionReasonOther
                                );
                        }
                    }
                );

            correctionReasonCategory
                ?.addEventListener(
                    'change',
                    function() {

                        const reasonTypeId =
                            correctionReasonCategory
                            ?.value
                            .trim() || '';

                        if (reasonTypeId) {
                            window
                                .clearFormInputValidation?.(
                                    correctionReasonCategory
                                );
                        }

                        const selectedReasonOption =
                            correctionReasonCategory
                            ?.selectedOptions?.[0];

                        const requiresReasonDetail =
                            selectedReasonOption
                            ?.dataset
                            ?.requiresDetail === '1';

                        setCorrectionOtherFieldVisible(
                            requiresReasonDetail
                        );

                        if (!requiresReasonDetail) {

                            if (correctionReasonOther) {
                                correctionReasonOther
                                    .value = '';
                            }

                            window
                                .clearFormInputValidation?.(
                                    correctionReasonOther
                                );
                        }
                    }
                );

            correctionReasonModal?.addEventListener(
                'click',
                function(event) {
                    if (event.target === correctionReasonModal) {
                        closeCorrectionReasonModal();
                    }
                }
            );

            confirmFinishProcedureBtn.addEventListener('click', function() {
                closeFinishProcedureModal();
                saveProcedure('finished', finishProcedureBtn, 'Saving Procedure...');
            });

            dismissFinishProcedureBtn.addEventListener('click',
                closeFinishProcedureModal);
            finishProcedureModalActionBtn.addEventListener('click',
                closeFinishProcedureModal);

            finishProcedureModal.addEventListener('click', function(event) {
                if (event.target === finishProcedureModal) {
                    closeFinishProcedureModal();
                }
            });

            followUpBtn?.addEventListener('click', function() {

                if (
                    !validateProcedureTimingFields({
                        focus: true
                    })
                ) {
                    return;
                }

                if (
                    !validateFollowUpTreatmentRequirement()
                ) {
                    return;
                }

                openFollowUpModal();
            });

            closeFollowUpModalBtn?.addEventListener(
                'click',
                closeFollowUpModal
            );

            cancelFollowUpModalBtn?.addEventListener(
                'click',
                closeFollowUpModal
            );

            document.getElementById('followUpModal')?.addEventListener('click', function(event) {
                if (event.target === event.currentTarget) {
                    closeFollowUpModal();
                }
            });

            document.getElementById('followUpForm')?.addEventListener('submit', async function(
                event) {
                event.preventDefault();

                const form =
                    event.currentTarget;

                const confirmBtn =
                    document.getElementById(
                        'confirmFollowUpBtn'
                    );

                const validation =
                    window.validateGlobalForm?.(
                        form, {
                            focus: false
                        }
                    );

                if (
                    validation &&
                    !validation.valid
                ) {
                    window.DiscardChanges
                        ?.markNotSubmitting(
                            form
                        );

                    return;
                }

                if (
                    !validateProcedurePrerequisites()
                ) {
                    window.DiscardChanges
                        ?.markNotSubmitting(
                            form
                        );

                    return;
                }

                const originalBtnHtml =
                    confirmBtn.innerHTML;

                confirmBtn.disabled = true;

                confirmBtn.innerHTML =
                    '<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Saving...';

                try {
                    const followUpResponse =
                        await fetch(
                            storeFollowUpUrl, {
                                method: 'POST',

                                headers: {
                                    'X-CSRF-TOKEN': @json(csrf_token()),

                                    'Accept': 'application/json',

                                    'X-Requested-With': 'XMLHttpRequest',
                                },

                                body: new FormData(
                                    form
                                ),
                            }
                        );

                    const followUpResponseText =
                        await followUpResponse.text();

                    let followUpResult = null;

                    try {
                        followUpResult =
                            JSON.parse(
                                followUpResponseText
                            );
                    } catch (_) {
                        followUpResult = null;
                    }

                    if (!followUpResponse.ok) {
                        console.error(
                            'Follow-up request failed:',
                            followUpResponse.status,
                            followUpResponseText
                        );

                        window.DiscardChanges
                            ?.markNotSubmitting(
                                form
                            );

                        showProcedureToast(
                            followUpResult?.message ||
                            'Failed to schedule follow-up appointment.',
                            'error'
                        );

                        return;
                    }

                    const procedureResult =
                        await saveProcedure(
                            'follow_up',
                            confirmBtn,
                            'Saving Procedure...'
                        );

                    if (!procedureResult) {
                        window.DiscardChanges
                            ?.markNotSubmitting(
                                form
                            );

                        return;
                    }

                    const createdFollowUp =
                        followUpResult
                        ?.appointment;

                    if (!createdFollowUp) {
                        showProcedureToast(
                            'The follow-up was saved, but its confirmation details could not be loaded.',
                            'warning',
                            'Confirmation Unavailable'
                        );

                        return;
                    }

                    window.DiscardChanges?.markNotSubmitting(form);
                    window.closeModal?.('followUpModal');

                    window.setTimeout(
                        () => {
                            openFollowUpSuccessModal(
                                createdFollowUp,
                                procedureResult
                                .redirect_url
                            );
                        },
                        190
                    );

                } catch (error) {
                    window.DiscardChanges
                        ?.markNotSubmitting(
                            form
                        );

                    console.error(
                        error
                    );

                    showProcedureToast(
                        'Something went wrong while scheduling the follow-up appointment.',
                        'error'
                    );

                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalBtnHtml;
                }
            });

            followUpSuccessBackBtn?.addEventListener('click', function() {
                if (!followUpSuccessRedirectUrl) {
                    return;
                }

                this.disabled = true;

                this.innerHTML = `
            <i class="fa-solid fa-circle-notch fa-spin"></i>
            <span>Opening Appointments...</span>
                `;

                window.location.href = followUpSuccessRedirectUrl;
            });

            if (clearSelectionBtn) {
                clearSelectionBtn.addEventListener('click', clearCurrentSelection);
            }

            if (undoBtn) {
                undoBtn.addEventListener('click', undoLastAction);
            }

            if (redoBtn) {
                redoBtn.addEventListener('click', redoLastAction);
            }

            function handleHistoryKeyboardShortcuts(event) {
                const isTyping =
                    event.target &&
                    (
                        event.target.tagName === 'INPUT' ||
                        event.target.tagName === 'TEXTAREA' ||
                        event.target.isContentEditable
                    );

                if (isTyping) return;

                const key = event.key.toLowerCase();
                const isCtrlOrCmd = event.ctrlKey || event.metaKey;

                if (!isCtrlOrCmd) return;

                if (key === 'z' && !event.shiftKey) {
                    event.preventDefault();
                    undoLastAction();
                    return;
                }

                if (key === 'y' || (key === 'z' && event.shiftKey)) {
                    event.preventDefault();
                    redoLastAction();
                }
            }

            if (cancelProcedureBtn) {
                cancelProcedureBtn.addEventListener('click', openCancelProcedureModal);
            }

            if (procedureDiscardForm) {
                window.DiscardChanges?.captureForm?.(procedureDiscardForm);
                markProcedureDiscardDirty();
            }

            function initLegendPanelResize() {
                if (!odontogramDockLayout || !legendResizeHandle || !legendDrawer) {
                    return;
                }

                const MIN_WIDTH = 320;
                const MAX_WIDTH = 560;
                const MIN_MAIN_WIDTH = 680;

                let resizing = false;

                function stopResize() {
                    if (!resizing) return;

                    resizing = false;

                    legendResizeHandle.classList.remove('is-resizing');
                    document.body.classList.remove('odontogram-resizing');

                    document.removeEventListener('pointermove', handleResizeMove);
                    document.removeEventListener('pointerup', stopResize);
                    document.removeEventListener('pointercancel', stopResize);

                    handleResize();
                }

                function handleResizeMove(event) {
                    if (!resizing) return;

                    const layoutRect = odontogramDockLayout.getBoundingClientRect();

                    let nextWidth = layoutRect.right - event.clientX;

                    const availableMaxWidth = Math.max(
                        MIN_WIDTH,
                        layoutRect.width - MIN_MAIN_WIDTH
                    );

                    nextWidth = Math.min(
                        nextWidth,
                        MAX_WIDTH,
                        availableMaxWidth
                    );

                    nextWidth = Math.max(
                        MIN_WIDTH,
                        nextWidth
                    );

                    odontogramDockLayout.style.setProperty(
                        '--legend-panel-width',
                        `${nextWidth}px`
                    );

                    localStorage.setItem(
                        'odontogramLegendPanelWidth',
                        String(nextWidth)
                    );

                    handleResize();
                }

                const savedWidth = Number(
                    localStorage.getItem('odontogramLegendPanelWidth')
                );

                if (Number.isFinite(savedWidth)) {
                    const safeSavedWidth = Math.min(
                        MAX_WIDTH,
                        Math.max(MIN_WIDTH, savedWidth)
                    );

                    odontogramDockLayout.style.setProperty(
                        '--legend-panel-width',
                        `${safeSavedWidth}px`
                    );
                }

                legendResizeHandle.addEventListener('pointerdown', function(event) {
                    if (window.innerWidth <= 900) {
                        return;
                    }

                    event.preventDefault();

                    resizing = true;

                    legendResizeHandle.classList.add('is-resizing');
                    document.body.classList.add('odontogram-resizing');

                    legendResizeHandle.setPointerCapture?.(event.pointerId);

                    document.addEventListener('pointermove', handleResizeMove);
                    document.addEventListener('pointerup', stopResize);
                    document.addEventListener('pointercancel', stopResize);
                });
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeResetModal();
                    closeCancelProcedureModal();
                    closeCorrectionReasonModal();
                    closeFinishProcedureModal();
                    closeFollowUpModal();

                    if (currentView === '3d' && selectedTooth) {
                        clear3DSurfacePickerSelection(false);
                    }
                }
            });

            surfacePickerButtons.forEach(function(button) {
                button.addEventListener('click', function(event) {
                    selectSurfaceFrom3DPicker(button.dataset.surface, event.shiftKey);
                });
            });

            if (reset3DViewBtn) {
                reset3DViewBtn.addEventListener('click', function() {
                    clear3DSurfacePickerSelection(true);
                });
            }

            if (close3DSurfacePickerBtn) {
                close3DSurfacePickerBtn.addEventListener('click', function() {
                    clear3DSurfacePickerSelection(false);
                });
            }

            const coarsePointer =
                window.matchMedia?.(
                    '(hover: none), (pointer: coarse)'
                )?.matches;

            if (!coarsePointer) {
                multiSelectTipToast =
                    window.showToast?.({
                        type: 'info',
                        title: 'Tip',
                        message: 'Hold Shift + Click to select multiple teeth and surfaces.',
                        duration: 9000,
                    });

                multiSelectTipToast
                    ?.classList
                    .add(
                        'odontogram-tip-toast'
                    );

                multiSelectTipToast
                    ?.querySelector(
                        '.toast-icon-wrap i'
                    )
                    ?.setAttribute(
                        'class',
                        'fa-solid fa-lightbulb'
                    );
            }

            initSurfacePickerDrag();
            initLegendPanelResize();

            renderLegendButtons(
                ''
            );
            updateHiddenInput();
            render2DOdontogram();
            updateSelectedToothUI();
            updateHistoryButtons();

            document.addEventListener('keydown', handleHistoryKeyboardShortcuts);
        });
    </script>
@endsection
