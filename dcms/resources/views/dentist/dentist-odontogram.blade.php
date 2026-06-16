@extends('layouts.dentist')

@section('title', 'Odontogram Procedure')

@section('content')
    @php
        use Carbon\Carbon;
        $patientName = $patient->name ?? 'Unknown Patient';
        $today = Carbon::now()->format('F d, Y');
    @endphp

    <main id="mainContent" class="odontogram-page pt-[100px] px-3 md:px-6 pb-6">
        <div class="w-full fade-in">
            <div class="odontogram-hero mb-6">
                <div class="odontogram-hero-main">
                    <div class="odontogram-hero-left">
                        <button type="button" id="cancelProcedureBtn" class="hero-danger-btn" data-tooltip="Cancel Procedure"
                            aria-label="Cancel Procedure">
                            <i class="fa-solid fa-xmark"></i>
                            <span>Cancel Procedure</span>
                        </button>

                        <div class="hero-title-card">
                            <div class="hero-title-icon">
                                <i class="fa-solid fa-tooth"></i>
                            </div>
                            <div>
                                <p class="hero-eyebrow">Dental Procedure Workspace</p>
                                <h1 class="hero-title">Dentist Odontogram</h1>
                                <p class="hero-subtitle">2D / 3D Treatment &amp; Condition Mapping</p>
                            </div>
                        </div>
                    </div>

                    <div class="hero-patient-card">
                        <div class="hero-patient-meta">
                            <p class="hero-patient-label">Patient</p>
                            <h2 class="hero-patient-name">{{ $patientName }}</h2>
                        </div>

                        <div class="hero-procedure-meta">
                            <div class="hero-stat">
                                <span class="hero-stat-label">Procedure Time</span>
                                <span id="procedureTimer" class="hero-stat-value">00:00:00</span>
                            </div>
                            <div class="hero-stat">
                                <span class="hero-stat-label">Date</span>
                                <span class="hero-stat-date">{{ $today }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="odontogram-toolbar" id="odontogramToolbar">
                    <div class="toolbar-group toolbar-group-tools">
                        <span class="toolbar-label">Selection Tools</span>
                        <div class="toolbar-actions">
                            <button type="button" id="clearSelectionBtn" class="toolbar-soft-btn"
                                data-tooltip="Clear Selection" title="Clear Selection" disabled>
                                <i class="fa-solid fa-arrow-pointer"></i>
                                <span>Clear Selection</span>
                            </button>

                            <button type="button" id="undoBtn" class="toolbar-soft-btn" data-tooltip="Undo"
                                title="Undo" disabled>
                                <span>Undo</span>
                            </button>

                            <button type="button" id="redoBtn" class="toolbar-soft-btn" data-tooltip="Redo"
                                title="Redo" disabled>
                                <i class="fa-solid fa-rotate-right"></i>
                                <span>Redo</span>
                            </button>
                        </div>
                    </div>

                    <div class="toolbar-group toolbar-group-view">
                        <span class="toolbar-label">View Mode</span>
                        <div class="toolbar-actions">
                            <button type="button" id="view2dBtn" class="view-toggle-btn active" data-tooltip="2D View"
                                title="2D View">
                                <i class="fa-regular fa-image"></i>
                                <span>2D View</span>
                            </button>

                            <button type="button" id="view3dBtn" class="view-toggle-btn" data-tooltip="3D View"
                                title="3D View">
                                <i class="fa-solid fa-cube"></i>
                                <span>3D View</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="odontogramToolbarSentinel" class="odontogram-toolbar-sentinel" aria-hidden="true"></div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 odontogram-layout">
                <div class="xl:col-span-8 glass-panel p-4 odontogram-left-panel">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                Click a tooth surface in 2D or switch to 3D view
                            </p>
                        </div>
                    </div>

                    <div class="soft-card p-4 left-view-shell">
                        <div class="w-full flex flex-col sm:flex-row justify-between sm:items-center gap-3 mb-4 px-2">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                <i class="fa-solid fa-tooth"></i>
                                <span id="viewInstructionText">Click the tooth surfaces or status box to assign a
                                    condition</span>
                            </p>

                            <div
                                class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100 shadow-inner">
                                <i class="fa-solid fa-tooth text-[#8B0000]"></i>
                                <p id="toothHoverLabel" class="text-sm font-extrabold text-[#8B0000]">Select a tooth</p>
                            </div>
                        </div>

                        <div id="odontogram2DPanel" class="mode-panel active">
                            <div class="odontogram2d-shell custom-scrollbar">
                                <div id="odontogram2DBoard" class="odontogram-board"></div>
                            </div>
                        </div>

                        <div id="odontogram3DPanel" class="mode-panel">
                            <div id="canvas-container">
                                <div id="loadingOverlay"
                                    class="absolute inset-0 bg-white flex flex-col gap-3 items-center justify-center z-10 rounded-xl transition-opacity duration-500">
                                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-[#8B0000]"></i>
                                    <p class="text-sm font-semibold text-gray-600">Generating 3D Model...</p>
                                </div>

                                <div id="toothTooltip" class="tooth-tooltip">
                                    <div id="toothTooltipContent"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4 glass-panel p-6 odontogram-right-shell">
                    <div class="odontogram-right-top">
                        <div class="selected-tooth-card rounded-2xl p-4">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                    Selected Target
                                </label>
                                <span id="selectedViewBadge"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-[#8B0000] text-[11px] font-bold border border-red-100">
                                    2D View
                                </span>
                            </div>

                            <div id="selectedToothDisplay"
                                class="w-full bg-white border border-red-100 text-[#8B0000] text-base font-extrabold rounded-2xl px-4 py-3">
                                Click a tooth on the chart
                            </div>

                            <p id="selectedToothName" class="mt-2 text-sm font-bold text-gray-700">
                                No tooth selected
                            </p>

                            <div class="mt-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                                    Current Treatment
                                </p>
                                <div id="selectedToothLegendList" class="flex flex-wrap gap-2">
                                    <span class="text-[11px] text-gray-400 italic">No treatment assigned yet.</span>
                                </div>
                            </div>

                            <div id="legendStatusNote"
                                class="hidden mt-3 rounded-xl px-3 py-2 text-xs font-semibold legend-status-note">
                                Choose a treatment legend below, then click Apply Treatment.
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-3">
                                <button type="button" id="applyTreatmentBtn"
                                    class="primary-action-btn w-full text-sm font-semibold py-2.5 rounded-xl shadow-sm"
                                    disabled>
                                    <i class="fa-solid fa-stethoscope mr-2"></i>
                                    Apply Treatment
                                </button>

                                <button type="button" id="clearCurrentToothBtn"
                                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold py-2.5 rounded-xl transition"
                                    disabled>
                                    <i class="fa-solid fa-eraser mr-2"></i>
                                    Clear Target
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="odontogram-right-scroll">
                        <div class="right-panel-sections">
                            <section class="right-section-card">
                                <div class="right-section-head">
                                    <div>
                                        <p class="right-section-eyebrow">Treatment Legend</p>
                                        <h3 class="right-section-title">Legend Selection</h3>
                                    </div>

                                    <button type="button" id="openLegendDrawerBtn"
                                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-[#8B0000] text-sm font-semibold border border-red-100 transition">
                                        <i class="fa-solid fa-layer-group"></i>
                                        Open Legend
                                    </button>
                                </div>

                                <div class="right-section-body">
                                    <p id="selectedLegendPreview" class="text-sm text-[#8B0000] font-bold">
                                        No legend selected yet.
                                    </p>
                                </div>
                            </section>

                            <section class="right-section-card">
                                <div class="right-section-head">
                                    <div>
                                        <p class="right-section-eyebrow">Clinical Documentation</p>
                                        <h3 class="right-section-title">Oral Examination Notes</h3>
                                    </div>
                                </div>

                                <div class="right-section-body space-y-4">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                            Oral Examination Notes
                                        </label>
                                        <textarea id="oralExaminationNotes" rows="5"
                                            class="w-full bg-gray-50 border border-gray-100 rounded-xl p-4 text-sm focus:ring-2 focus:ring-[#8B0000] focus:border-[#8B0000] resize-none transition"
                                            placeholder="Add examination notes here..."></textarea>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                            Diagnosis
                                        </label>
                                        <textarea id="diagnosisNotes" rows="5"
                                            class="w-full bg-gray-50 border border-gray-100 rounded-xl p-4 text-sm focus:ring-2 focus:ring-[#8B0000] focus:border-[#8B0000] resize-none transition"
                                            placeholder="Add diagnosis here..."></textarea>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                            Prescriptions <span class="normal-case text-gray-400">(Optional)</span>
                                        </label>
                                        <textarea id="prescriptionsNotes" rows="5"
                                            class="w-full bg-gray-50 border border-gray-100 rounded-xl p-4 text-sm focus:ring-2 focus:ring-[#8B0000] focus:border-[#8B0000] resize-none transition"
                                            placeholder="Add prescriptions here..."></textarea>
                                    </div>
                                </div>
                            </section>

                            <input type="hidden" id="odontogramData" name="odontogram_data" value="[]">
                        </div>
                    </div>

                    <div class="odontogram-right-bottom space-y-3">
                        <button type="button" id="followUpBtn"
                            class="w-full flex justify-center items-center gap-2 bg-amber-100 hover:bg-amber-200 text-amber-800 text-sm font-semibold py-2.75 rounded-xl transition shadow-sm border border-amber-200">
                            <i class="fa-solid fa-calendar-plus"></i>
                            Follow-Up Appointment
                        </button>

                        <button type="button" id="finishProcedureBtn"
                            class="w-full flex justify-center items-center gap-2 bg-[#8B0000] hover:bg-[#660000] text-white text-sm font-semibold py-2.75 rounded-xl transition shadow-md">
                            <i class="fa-solid fa-check"></i>
                            Finish Procedure
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="legendDrawerBackdrop" class="fixed inset-0 bg-black/30 z-[60]"></div>

    <div id="legendDrawer"
        class="fixed top-0 right-0 h-full w-full sm:w-[460px] bg-white z-[61] shadow-2xl border-l border-gray-200 transition-transform duration-300 flex flex-col">

        <div class="legend-drawer-header px-5 py-4 border-b border-gray-100 bg-white">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-red-50 text-[#8B0000]">
                            <i class="fa-solid fa-layer-group"></i>
                        </span>
                        <div>
                            <h3 class="text-lg font-extrabold text-[#8B0000] leading-tight">Treatment Legend</h3>
                            <p class="text-xs text-gray-500">Choose a legend and apply it to the selected target.</p>
                        </div>
                    </div>
                </div>

                <button type="button" id="closeLegendDrawerBtn"
                    class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 transition shrink-0">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="mt-4">
                <div class="soft-card px-4 py-3 border border-red-100 bg-red-50/40">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Selected Legend</p>
                    <p id="drawerSelectedLegendPreview" class="text-sm font-semibold text-[#8B0000]">
                        No legend selected yet.
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="legendSearchInput"
                        class="w-full pl-10 pr-10 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-[#8B0000] focus:border-[#8B0000]"
                        placeholder="Search legend code or label...">
                    <button type="button" id="clearLegendSearchBtn"
                        class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Legend Categories</p>
                <p id="legendResultCount" class="text-xs font-semibold text-gray-500">0 results</p>
            </div>
        </div>

        <div class="p-5 overflow-y-auto custom-scrollbar flex-1">
            <div id="legendContainer" class="space-y-5"></div>
            <div id="legendEmptyState" class="hidden soft-card p-5 text-center">
                <div
                    class="w-12 h-12 mx-auto rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <p class="text-sm font-semibold text-gray-700">No matching legend found.</p>
                <p class="text-xs text-gray-500 mt-1">Try a different keyword or clear the search.</p>
            </div>
        </div>
    </div>

    <div id="resetTreatmentModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4">
        <div class="absolute inset-0 modal-backdrop"></div>

        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 p-6">
            <div class="flex items-start gap-4">
                <div
                    class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div class="flex-1">
                    <h3 class="text-lg font-extrabold text-gray-900 mb-2">Reset Tooth Treatment</h3>
                    <p id="resetTreatmentMessage" class="text-sm text-gray-600 leading-relaxed">
                        Are you sure you want to reset the treatment for this tooth?
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <button type="button" id="confirmResetTreatmentBtn"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition">
                    Yes, Reset
                </button>

                <button type="button" id="cancelResetTreatmentBtn"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <div id="cancelProcedureModal" class="fixed inset-0 z-[80] hidden items-center justify-center p-4">
        <div class="absolute inset-0 modal-backdrop"></div>

        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 p-6">
            <div class="flex items-start gap-4">
                <div
                    class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>

                <div class="flex-1">
                    <h3 class="text-lg font-extrabold text-gray-900 mb-2">Cancel Procedure?</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Are you sure you want to cancel this procedure? Any unsaved progress in this session may be lost.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <button type="button" id="confirmCancelProcedureBtn"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition">
                    Yes, Cancel
                </button>

                <button type="button" id="dismissCancelProcedureBtn"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition">
                    No, Stay
                </button>
            </div>
        </div>
    </div>

    <div id="followUpModal"
        class="fixed inset-0 bg-black/50 hidden items-end sm:items-center justify-center backdrop-blur-sm z-[9999] p-0 sm:p-4">

        <div
            class="reschedule-modal-panel bg-white w-full sm:max-w-5xl rounded-t-2xl sm:rounded-2xl overflow-hidden shadow-2xl flex flex-col">

            <div class="relative bg-gradient-to-r from-[#8B0000] via-[#A00000] to-[#C1121F] px-5 sm:px-6 py-4">
                <button type="button" onclick="closeFollowUpModal()"
                    class="absolute top-3.5 right-3.5 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 text-white/90 hover:text-white flex items-center justify-center transition text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="flex items-start gap-3 pr-10">
                    <div
                        class="w-11 h-11 rounded-2xl bg-white/20 border border-white/25 shadow-sm flex items-center justify-center flex-shrink-0 backdrop-blur-sm">
                        <i class="fa-solid fa-calendar-plus text-white text-lg"></i>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-white/75 mb-1">
                            Follow-up Schedule
                        </p>
                        <h2 class="text-white font-bold text-lg leading-tight">
                            Set Follow-Up Appointment
                        </h2>
                        <p class="text-white/85 text-[12px] mt-1 leading-relaxed">
                            Choose the follow-up date, time, and reason before completing this procedure.
                        </p>
                    </div>
                </div>
            </div>

            <div class="reschedule-modal-body px-5 sm:px-6 py-4 sm:py-5 bg-gray-50 overflow-y-auto">
                <div
                    class="bg-white border border-[#f1ece7] rounded-2xl px-4 sm:px-5 py-4 mb-4 shadow-[0_4px_18px_rgba(0,0,0,0.04)]">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">
                                <i class="fa-solid fa-user fa-xs mr-1"></i>Patient
                            </div>
                            <div class="text-[14px] font-bold text-gray-800 truncate">
                                {{ $patientName }}
                            </div>
                        </div>

                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">
                                <i class="fa-regular fa-calendar fa-xs mr-1"></i>Current Date
                            </div>
                            <div class="text-[13px] font-medium text-gray-700">
                                {{ $today }}
                            </div>
                        </div>

                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">
                                <i class="fa-solid fa-tooth fa-xs mr-1"></i>Procedure
                            </div>
                            <div class="text-[13px] font-medium text-gray-700">
                                Odontogram
                            </div>
                        </div>
                    </div>
                </div>

                <form id="followUpForm" method="POST"
                    action="{{ route('dentist.dentist.appointments.follow-up.store', $appointment->id) }}">
                    @csrf

                    <input type="hidden" id="followup_appointment_date" name="followup_appointment_date" required>
                    <input type="hidden" id="followup_appointment_time" name="followup_appointment_time" required>

                    <div class="section-label">
                        <i class="fa-regular fa-calendar fa-xs"></i> Follow-up Date & Time
                    </div>

                    <div id="followUpDateError" class="error-msg" style="display:none;">
                        <i class="fa-solid fa-circle-exclamation"></i> Please select a follow-up date.
                    </div>

                    <div class="two-col mb-2 sm:mb-3">
                        <div class="cal-wrap follow-up-cal-wrap">
                            <div id="followUpCalendarWrap"></div>
                        </div>

                        <div class="slots-wrap follow-up-slots-wrap">
                            <div class="section-label" style="margin-bottom:.6rem;">
                                <i class="fa-regular fa-clock fa-xs"></i> Follow-up Time Slot
                            </div>

                            <div class="slots-date-pill" id="followUpDatePill"></div>

                            <div id="followUpSlotPlaceholder" class="slots-placeholder">
                                <i class="fa-regular fa-calendar-xmark"></i>
                                <span>Select a date to see available slots</span>
                            </div>

                            <div id="followUpSlotContainer" class="hidden">
                                <div id="followUpSlotGrid" class="slots-grid" style="display:none;"></div>
                            </div>

                            <div id="followUpSelectedTimePill"
                                class="hidden mt-4 w-full rounded-2xl border border-red-100 bg-red-50 px-4 py-3 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-[#8B0000] text-white shadow-sm">
                                        <i class="fa-solid fa-circle-check text-sm"></i>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#8B0000]">
                                            Selected Follow-up Time
                                        </p>
                                        <p id="followUpSelectedTimeText"
                                            class="text-[15px] font-bold text-[#8B0000] leading-tight"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="followUpTimeError" class="error-msg" style="display:none;">
                        <i class="fa-solid fa-circle-exclamation"></i> Please select a follow-up time slot.
                    </div>

                    <div class="section-label mt-5 sm:mt-6">
                        <i class="fa-regular fa-message fa-xs"></i>
                        Reason for Follow-up
                        <span style="font-weight:400;text-transform:none;letter-spacing:0;">(required)</span>
                    </div>

                    <div class="reason-wrap w-full">
                        <textarea id="followup_reason" name="followup_reason" rows="3"
                            placeholder="e.g. Check healing progress after extraction..."
                            class="reason-textarea w-full min-h-[92px] resize-none" required></textarea>
                    </div>

                    <div class="btn-row flex flex-col-reverse sm:flex-row gap-3">
                        <button type="button" class="btn btn-cancel" onclick="closeFollowUpModal()">
                            <i class="fa-solid fa-xmark"></i> Cancel
                        </button>

                        <button type="submit" class="btn btn-confirm follow-up-confirm-btn" id="confirmFollowUpBtn">
                            <i class="fa-solid fa-check"></i> Save Procedure & Schedule Follow-up
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    @include('components.appointment-calendar-script', [
        'mode' => 'booking',
        'calendarContainerId' => 'followUpCalendarWrap',
        'calGridId' => 'followUpCalGrid',
        'calMonthLabelId' => 'followUpCalMonthLabel',
        'calYearLabelId' => 'followUpCalYearLabel',
    
        'dateInputId' => 'followup_appointment_date',
        'timeInputId' => 'followup_appointment_time',
    
        'dateBannerId' => null,
        'slotPlaceholderId' => 'followUpSlotPlaceholder',
        'slotContainerId' => 'followUpSlotContainer',
        'slotGridId' => 'followUpSlotGrid',
    
        'selectedSlotDisplayId' => 'followUpSelectedTimePill',
        'selectedSlotTextId' => 'followUpSelectedTimeText',
        'selectedTimePillId' => 'followUpSelectedTimePill',
        'selectedTimeTextId' => 'followUpSelectedTimeText',
    
        'datePillId' => 'followUpDatePill',
        'dateErrorId' => 'followUpDateError',
        'timeErrorId' => 'followUpTimeError',
    
        'calendarWrapSelector' => '.follow-up-cal-wrap',
        'slotsWrapSelector' => '.follow-up-slots-wrap',
    
        'slotEndpoint' => route('dentist.appointment.slots'),
    
        'scheduleRules' => $schedules ?? [],
        'blockedDates' => $blockedDates ?? [],
        'appointmentCountsPerDay' => $appointmentCountsPerDay ?? [],
        'philippineHolidays' => $philippineHolidays ?? [],
        'personalAppointments' => [],
    
        'disallowToday' => true,
        'allowToggleOffDate' => true,
        'useDynamicScheduleRules' => true,
        'renderStyle' => 'patient',
    ])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cancelProcedureBtn = document.getElementById('cancelProcedureBtn');
            const cancelProcedureModal = document.getElementById('cancelProcedureModal');
            const confirmCancelProcedureBtn = document.getElementById('confirmCancelProcedureBtn');
            const dismissCancelProcedureBtn = document.getElementById('dismissCancelProcedureBtn');
            const cancelProcedureRedirectUrl = @json(route('dentist.dentist.patient.profile', $patient->id ?? 1));

            const legendSearchInput = document.getElementById('legendSearchInput');
            const clearLegendSearchBtn = document.getElementById('clearLegendSearchBtn');
            const legendResultCount = document.getElementById('legendResultCount');
            const legendEmptyState = document.getElementById('legendEmptyState');
            const drawerSelectedLegendPreview = document.getElementById('drawerSelectedLegendPreview');

            const procedureTimer = document.getElementById('procedureTimer');
            const container = document.getElementById('canvas-container');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const openLegendDrawerBtn = document.getElementById('openLegendDrawerBtn');
            const closeLegendDrawerBtn = document.getElementById('closeLegendDrawerBtn');
            const legendDrawer = document.getElementById('legendDrawer');
            const legendDrawerBackdrop = document.getElementById('legendDrawerBackdrop');

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
            const clearCurrentToothBtn = document.getElementById('clearCurrentToothBtn');

            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            const undoBtn = document.getElementById('undoBtn');
            const redoBtn = document.getElementById('redoBtn');

            const toothTooltip = document.getElementById('toothTooltip');
            const toothTooltipContent = document.getElementById('toothTooltipContent');

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
            let hoveredMesh = null;
            let pendingResetPayload = null;
            let hasAppliedTreatmentThisSession = false;

            let historyStack = [];
            let redoStack = [];
            const HISTORY_LIMIT = 50;

            let scene = null;
            let camera = null;
            let renderer = null;
            let controls = null;
            let raycaster = null;
            let mouse = null;
            let teethMeshes = [];
            let threeSceneInitialized = false;

            const legends = [{
                    code: 'D',
                    label: 'Decayed'
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
                    label: 'Indicated for Extraction'
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
                    label: 'Inlay'
                },
                {
                    code: 'LC',
                    label: 'Light Cure Composite'
                },
                {
                    code: 'RM',
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
                    code: 'PT',
                    label: 'Present Tooth'
                },
                {
                    code: 'CM',
                    label: 'Congenitally Missing'
                },
                {
                    code: 'SP',
                    label: 'Supernumerary'
                }
            ];

            const legendColors = {
                D: '#ef4444',
                M: '#9ca3af',
                F: '#22c55e',
                I: '#f97316',
                RF: '#7c2d12',
                MO: '#6b7280',
                IM: '#9333ea',
                J: '#2563eb',
                A: '#0f766e',
                AB: '#14b8a6',
                P: '#6366f1',
                IN: '#10b981',
                LC: '#22d3ee',
                RM: '#94a3b8',
                X: '#b91c1c',
                XO: '#7f1d1d',
                PT: '#111111',
                CM: '#a1a1aa',
                SP: '#c084fc'
            };

            const legendIcons = {
                D: 'fa-solid fa-bug',
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
                RM: 'fa-solid fa-teeth',
                X: 'fa-solid fa-xmark',
                XO: 'fa-solid fa-skull-crossbones',
                PT: 'fa-solid fa-check',
                CM: 'fa-solid fa-question',
                SP: 'fa-solid fa-plus'
            };

            const legendCategories = [{
                    key: 'conditions',
                    title: 'Conditions & Findings',
                    icon: 'fa-solid fa-heart-pulse',
                    items: ['D', 'RF', 'IM', 'CM', 'SP', 'PT']
                },
                {
                    key: 'missing',
                    title: 'Missing / Extraction',
                    icon: 'fa-solid fa-ban',
                    items: ['M', 'MO', 'X', 'XO', 'I']
                },
                {
                    key: 'restorations',
                    title: 'Restorations',
                    icon: 'fa-solid fa-screwdriver-wrench',
                    items: ['F', 'A', 'LC', 'IN', 'J']
                },
                {
                    key: 'prosthetics',
                    title: 'Prosthetics & Support',
                    icon: 'fa-solid fa-link',
                    items: ['AB', 'P', 'RM']
                }
            ];

            const rawSavedOdontogramData = @json($savedOdontogramData ?? []);
            const savedOdontogramData = Array.isArray(rawSavedOdontogramData) ?
                rawSavedOdontogramData :
                Object.values(rawSavedOdontogramData || {});

            const odontogramState = {};

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
                    threeD: null
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

                return {
                    ...defaultState,
                    ...entry,
                    tooth: toothNumber,
                    toothName: entry.toothName || entry.tooth_name || defaultState.toothName,
                    status: entry.status || null,
                    threeD: entry.threeD || entry.three_d || null,
                    surfaces: {
                        ...defaultState.surfaces,
                        ...surfaces
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

            function openCancelProcedureModal() {
                cancelProcedureModal.classList.remove('hidden');
                cancelProcedureModal.classList.add('flex');
            }

            function closeCancelProcedureModal() {
                cancelProcedureModal.classList.add('hidden');
                cancelProcedureModal.classList.remove('flex');
            }

            function getSurfaceLabel(surface) {
                const map = {
                    top: 'Top Surface',
                    bottom: 'Bottom Surface',
                    left: 'Left Surface',
                    right: 'Right Surface',
                    center: 'Center Surface',
                    status: 'Status Box',
                    whole: 'Whole Tooth'
                };
                return map[surface] || surface;
            }

            function ensureToothState(toothNumber) {
                const numericToothNumber = Number(toothNumber);

                if (!odontogramState[numericToothNumber]) {
                    odontogramState[numericToothNumber] = createDefaultToothState(numericToothNumber);
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

            function getSelectedRecord() {
                if (!selectedTooth || !selectedTargetType) return null;

                const state = ensureToothState(selectedTooth);

                if (selectedTargetType === 'status') return state.status;
                if (selectedTargetType === 'surface' && selectedSurfaceKey) return state.surfaces[
                    selectedSurfaceKey];
                if (selectedTargetType === '3d') return state.threeD;

                return null;
            }

            function renderSelectedToothLegendList() {
                const selectedRecord = getSelectedRecord();

                if (!selectedRecord) {
                    selectedToothLegendList.innerHTML =
                        '<span class="text-[11px] text-gray-400 italic">No treatment assigned yet.</span>';
                    return;
                }

                selectedToothLegendList.innerHTML = `
                    <span class="treatment-chip inline-flex items-center gap-2">
                        <span class="legend-color-dot" style="background:${selectedRecord.colorHex};"></span>
                        ${selectedRecord.code} - ${selectedRecord.label}
                    </span>
                `;
            }

            function updateActionButtons() {
                const hasSelectedTarget = !!selectedTooth && !!selectedTargetType;
                const hasSelectedLegend = !!selectedLegend;
                const hasAssignedTreatment = !!getSelectedRecord();

                applyTreatmentBtn.disabled = !(hasSelectedTarget && hasSelectedLegend);
                clearCurrentToothBtn.disabled = !hasAssignedTreatment;
                updateHistoryButtons();
            }

            function updateSelectedToothUI() {
                if (!selectedTooth || !selectedTargetType) {
                    drawerSelectedLegendPreview.textContent = selectedLegend ?
                        `${selectedLegend} - ${(getLegendByCode(selectedLegend)?.label || selectedLegend)}` :
                        'No legend selected yet';

                    selectedToothDisplay.textContent = currentView === '2d' ?
                        'Click a tooth surface on the 2D odontogram' :
                        'Click a tooth on the 3D odontogram';

                    selectedToothName.textContent = 'No tooth selected';
                    toothHoverLabel.innerText = 'Select a tooth';
                    legendStatusNote.classList.add('hidden');
                    selectedLegend = null;
                    selectedViewBadge.textContent = currentView === '2d' ? '2D View' : '3D View';

                    updateLegendActiveState();
                    renderSelectedToothLegendList();
                    updateActionButtons();
                    return;
                }

                const surfaceText = selectedTargetType === 'surface' ?
                    getSurfaceLabel(selectedSurfaceKey) :
                    selectedTargetType === 'status' ?
                    getSurfaceLabel('status') :
                    getSurfaceLabel('whole');

                selectedToothDisplay.textContent = `Tooth #${selectedTooth} • ${surfaceText}`;
                selectedToothName.textContent = getToothName(selectedTooth);
                toothHoverLabel.innerText = `Selected: #${selectedTooth}`;
                selectedViewBadge.textContent = currentView === '2d' ? '2D View' : '3D View';
                legendStatusNote.classList.remove('hidden');

                const record = getSelectedRecord();
                selectedLegend = record ? record.code : null;

                updateLegendActiveState();
                renderSelectedToothLegendList();
                updateActionButtons();
            }

            function openLegendDrawer() {
                legendSearchInput.value = '';
                clearLegendSearchBtn.classList.add('hidden');
                renderLegendButtons('');
                legendDrawer.classList.add('open');
                legendDrawerBackdrop.classList.add('show');
                document.body.classList.add('legend-drawer-open');
            }

            function closeLegendDrawer() {
                legendDrawer.classList.remove('open');
                legendDrawerBackdrop.classList.remove('show');
                document.body.classList.remove('legend-drawer-open');
            }

            openLegendDrawerBtn.addEventListener('click', openLegendDrawer);
            closeLegendDrawerBtn.addEventListener('click', closeLegendDrawer);
            legendDrawerBackdrop.addEventListener('click', closeLegendDrawer);

            legendSearchInput.addEventListener('input', function() {
                const value = legendSearchInput.value.trim();
                clearLegendSearchBtn.classList.toggle('hidden', value.length === 0);
                renderLegendButtons(value);
            });

            clearLegendSearchBtn.addEventListener('click', function() {
                legendSearchInput.value = '';
                clearLegendSearchBtn.classList.add('hidden');
                renderLegendButtons('');
                legendSearchInput.focus();
            });

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
                                legend.code.toLowerCase().includes(normalizedSearch) ||
                                legend.label.toLowerCase().includes(normalizedSearch)
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
                                    <p class="text-sm font-bold text-gray-900">${category.title}</p>
                                    <p class="text-[11px] text-gray-500">Choose a legend from this group</p>
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

                        btn.innerHTML = `
                            <span class="legend-color-dot" style="background:${legendColors[legend.code]};"></span>
                            <i class="${legendIcons[legend.code]} text-[#8B0000] w-4 text-center"></i>
                            <span class="legend-meta">
                                <span class="legend-code">${legend.code}</span>
                                <span class="legend-label">${legend.label}</span>
                            </span>
                        `;

                        btn.addEventListener('click', function() {
                            if (!selectedTooth || !selectedTargetType) {
                                alert('Please select a tooth target first.');
                                return;
                            }

                            selectedLegend = legend.code;
                            selectedLegendPreview.textContent =
                                `${legend.code} - ${legend.label}`;
                            drawerSelectedLegendPreview.textContent =
                                `${legend.code} - ${legend.label}`;
                            updateLegendActiveState();
                            updateActionButtons();
                        });

                        body.appendChild(btn);
                    });

                    categoryBlock.appendChild(body);
                    legendContainer.appendChild(categoryBlock);
                });

                legendResultCount.textContent = `${totalResults} result${totalResults === 1 ? '' : 's'}`;

                if (totalResults === 0) {
                    legendEmptyState.classList.remove('hidden');
                } else {
                    legendEmptyState.classList.add('hidden');
                }

                updateLegendActiveState();
            }

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

            function sync3DFrom2D(toothNumber) {
                const state = ensureToothState(toothNumber);

                if (!isAdultTooth(toothNumber)) {
                    state.threeD = null;
                    return;
                }

                state.threeD = getPreferredToothVisual(state);
            }

            function fillAll2DSurfacesFromLegend(state, payload) {
                state.status = payload;
                state.surfaces.top = payload;
                state.surfaces.left = payload;
                state.surfaces.center = payload;
                state.surfaces.right = payload;
                state.surfaces.bottom = payload;
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

                if (threeSceneInitialized) {
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
                if (clearSelectionBtn) clearSelectionBtn.disabled = !(selectedTooth && selectedTargetType);
            }

            function clearCurrentSelection() {
                selectedTooth = null;
                selectedTargetType = null;
                selectedSurfaceKey = null;
                selectedMesh = null;
                selectedLegend = null;

                render2DOdontogram();

                if (threeSceneInitialized) {
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
                pushHistory();

                const state = ensureToothState(selectedTooth);
                const payload = createLegendPayload(code);
                hasAppliedTreatmentThisSession = true;

                if (selectedTargetType === 'status') {
                    state.status = payload;
                    sync3DFrom2D(selectedTooth);
                } else if (selectedTargetType === 'surface' && selectedSurfaceKey) {
                    state.surfaces[selectedSurfaceKey] = payload;
                    sync3DFrom2D(selectedTooth);
                } else if (selectedTargetType === '3d') {
                    state.threeD = payload;
                    fillAll2DSurfacesFromLegend(state, payload);

                    if (selectedMesh) {
                        selectedMesh.material.color.setStyle(payload.colorHex);
                        selectedMesh.userData.legend = code;
                        selectedMesh.userData.originalColor = payload.colorHex;
                        applySelectedMeshState(selectedMesh);
                    }
                }

                updateHiddenInput();
                render2DOdontogram();

                if (threeSceneInitialized) {
                    renderThreeVisuals();
                }

                renderSelectedToothLegendList();
                updateLegendActiveState();
                updateActionButtons();
                selectedLegendPreview.textContent = `${payload.code} - ${payload.label}`;
                drawerSelectedLegendPreview.textContent = `${payload.code} - ${payload.label}`;
                closeLegendDrawer();
            }

            function clearSelectedTargetTreatment() {
                if (!pendingResetPayload) return;
                pushHistory();

                const {
                    tooth,
                    targetType,
                    surfaceKey
                } = pendingResetPayload;
                const state = ensureToothState(tooth);

                if (targetType === 'status') {
                    state.status = null;
                    sync3DFrom2D(tooth);
                } else if (targetType === 'surface' && surfaceKey) {
                    state.surfaces[surfaceKey] = null;
                    sync3DFrom2D(tooth);
                } else if (targetType === '3d') {
                    state.threeD = null;
                    clearAll2DSurfaces(state);

                    if (selectedMesh && Number(selectedMesh.userData.tooth) === Number(tooth)) {
                        selectedMesh.material.color.setStyle('#FFFFF0');
                        selectedMesh.userData.originalColor = '#FFFFF0';
                        delete selectedMesh.userData.legend;
                        applySelectedMeshState(selectedMesh);
                    }
                }

                selectedLegend = null;
                pendingResetPayload = null;

                selectedLegendPreview.textContent = 'No legend selected yet.';
                drawerSelectedLegendPreview.textContent = 'No legend selected yet.';

                updateHiddenInput();
                render2DOdontogram();

                if (threeSceneInitialized) {
                    renderThreeVisuals();
                }

                renderSelectedToothLegendList();
                updateLegendActiveState();
                updateActionButtons();
                closeResetModal();
            }

            function openResetModal() {
                if (!selectedTooth || !selectedTargetType || !getSelectedRecord()) return;

                pendingResetPayload = {
                    tooth: selectedTooth,
                    targetType: selectedTargetType,
                    surfaceKey: selectedSurfaceKey
                };

                const partText = selectedTargetType === 'surface' ?
                    getSurfaceLabel(selectedSurfaceKey) :
                    selectedTargetType === 'status' ?
                    'Status Box' :
                    'Whole Tooth';

                resetTreatmentMessage.textContent =
                    `Are you sure you want to reset the treatment for tooth #${selectedTooth} (${getToothName(selectedTooth)}) - ${partText}?`;

                resetTreatmentModal.classList.remove('hidden');
                resetTreatmentModal.classList.add('flex');
            }

            function closeResetModal() {
                pendingResetPayload = null;
                resetTreatmentModal.classList.add('hidden');
                resetTreatmentModal.classList.remove('flex');
            }

            function createToothUnit(toothNumber, statusPlacement = 'top') {
                const state = ensureToothState(toothNumber);

                const wrap = document.createElement('div');
                wrap.className = 'tooth-unit';

                const statusBox = document.createElement('div');
                statusBox.className = 'status-box';
                statusBox.dataset.tooth = toothNumber;
                statusBox.dataset.target = 'status';

                if (state.status) {
                    statusBox.style.background = state.status.colorHex;
                    statusBox.style.borderColor = state.status.colorHex;
                }
                if (selectedTooth === toothNumber && selectedTargetType === 'status') {
                    statusBox.classList.add('selected-target');
                }

                statusBox.addEventListener('click', function() {
                    currentView = '2d';
                    selectedTooth = toothNumber;
                    selectedTargetType = 'status';
                    selectedSurfaceKey = null;
                    updateSelectedToothUI();
                    render2DOdontogram();
                });

                const toothNumberEl = document.createElement('div');
                toothNumberEl.className = 'tooth-number';
                toothNumberEl.textContent = toothNumber;

                const toothFace = document.createElement('div');
                toothFace.className = 'tooth-2d-wrapper';

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
                    const surfaceRecord = state.surfaces[surface];

                    if (surfaceRecord) {
                        part.style.fill = surfaceRecord.colorHex;
                    }

                    if (selectedTooth === toothNumber && selectedTargetType === 'surface' &&
                        selectedSurfaceKey === surface) {
                        part.classList.add('selected-target');
                    }

                    part.addEventListener('click', function() {
                        currentView = '2d';
                        selectedTooth = toothNumber;
                        selectedTargetType = 'surface';
                        selectedSurfaceKey = surface;
                        updateSelectedToothUI();
                        render2DOdontogram();
                    });

                    part.addEventListener('mouseenter', function() {
                        toothHoverLabel.innerText = `Tooth #${toothNumber}`;
                    });

                    part.addEventListener('mouseleave', function() {
                        toothHoverLabel.innerText = selectedTooth ? `Selected: #${selectedTooth}` :
                            'Select a tooth';
                    });
                });

                if (statusPlacement === 'top') {
                    wrap.appendChild(statusBox);
                    wrap.appendChild(toothNumberEl);
                    wrap.appendChild(toothFace);
                } else {
                    wrap.appendChild(toothFace);
                    wrap.appendChild(toothNumberEl);
                    wrap.appendChild(statusBox);
                }

                return wrap;
            }

            function createRow(leftTeeth, rightTeeth, statusPlacement = 'top', leftLabel = '', rightLabel = '') {
                const row = document.createElement('div');
                row.className = 'odontogram-row';

                const left = document.createElement('div');
                left.className = 'status-label-left';
                left.innerHTML = leftLabel ? leftLabel : '&nbsp;';

                if (statusPlacement === 'top') {
                    left.style.paddingTop = '10px';
                } else {
                    left.style.alignSelf = 'flex-end';
                    left.style.paddingBottom = '10px';
                }
                row.appendChild(left);

                leftTeeth.forEach(tooth => row.appendChild(createToothUnit(tooth, statusPlacement)));

                const spacer = document.createElement('div');
                spacer.className = 'w-3 md:w-5';
                row.appendChild(spacer);

                rightTeeth.forEach(tooth => row.appendChild(createToothUnit(tooth, statusPlacement)));

                const right = document.createElement('div');
                right.className = 'status-label-right';
                right.innerHTML = rightLabel ? rightLabel : '&nbsp;';

                if (statusPlacement === 'top') {
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

                const row1 = createRow(primaryUpperRight, primaryUpperLeft, 'top', 'STATUS<br>RIGHT', 'LEFT');
                row1.classList.add('arch-divider');
                board2d.appendChild(row1);

                const row2 = createRow(adultUpperRight, adultUpperLeft, 'top', '', '');
                row2.style.marginBottom = '24px';
                board2d.appendChild(row2);

                const row3 = createRow(adultLowerRight, adultLowerLeft, 'bottom', '', '');
                row3.classList.add('arch-divider');
                board2d.appendChild(row3);

                const row4 = createRow(primaryLowerRight, primaryLowerLeft, 'bottom', 'STATUS<br>RIGHT', 'LEFT');
                board2d.appendChild(row4);
            }

            function switchView(view) {
                currentView = view;

                if (view === '2d') {
                    selectedMesh = null;
                    panel2d.classList.add('active');
                    panel3d.classList.remove('active');
                    view2dBtn.classList.add('active');
                    view3dBtn.classList.remove('active');
                    viewInstructionText.textContent =
                        'Click the tooth surfaces or status box to assign a condition';
                } else {
                    panel2d.classList.remove('active');
                    panel3d.classList.add('active');
                    view2dBtn.classList.remove('active');
                    view3dBtn.classList.add('active');
                    viewInstructionText.textContent = 'Click a tooth in the 3D model to assign a condition';

                    if (!threeSceneInitialized) {
                        initThreeScene();
                    } else if (renderer && camera) {
                        handleResize();
                        renderThreeVisuals();
                    }
                }

                if (view === '3d' && threeSceneInitialized && selectedTooth) {
                    selectedMesh = teethMeshes.find(mesh => Number(mesh.userData.tooth) === Number(
                        selectedTooth)) || null;
                    renderThreeVisuals();
                }

                updateSelectedToothUI();
            }

            view2dBtn.addEventListener('click', () => switchView('2d'));
            view3dBtn.addEventListener('click', () => switchView('3d'));

            function initThreeScene() {
                const width = container.clientWidth || 700;
                const height = container.clientHeight || 480;

                scene = new THREE.Scene();
                scene.background = new THREE.Color(
                    document.documentElement.getAttribute('data-theme') === 'dark' ||
                    document.documentElement.classList.contains('dark') ||
                    document.body.classList.contains('dark') ?
                    '#0D1117' :
                    '#F1F5F9'
                );

                camera = new THREE.PerspectiveCamera(40, width / height, 0.1, 1000);
                camera.position.set(0, 10, 14);

                renderer = new THREE.WebGLRenderer({
                    antialias: true,
                    alpha: false
                });
                renderer.setPixelRatio(window.devicePixelRatio);
                renderer.setSize(width, height);
                renderer.shadowMap.enabled = true;
                renderer.shadowMap.type = THREE.PCFSoftShadowMap;
                container.appendChild(renderer.domElement);

                controls = new THREE.OrbitControls(camera, renderer.domElement);
                controls.enableDamping = true;
                controls.dampingFactor = 0.07;
                controls.minDistance = 5;
                controls.maxDistance = 30;
                controls.maxPolarAngle = Math.PI / 1.8;

                const ambientLight = new THREE.AmbientLight(0xffffff, 0.75);
                scene.add(ambientLight);

                const keyLight = new THREE.DirectionalLight(0xffffff, 0.8);
                keyLight.position.set(10, 15, 10);
                keyLight.castShadow = true;
                keyLight.shadow.mapSize.width = 1024;
                keyLight.shadow.mapSize.height = 1024;
                scene.add(keyLight);

                const backLight = new THREE.DirectionalLight(0xffffff, 0.45);
                backLight.position.set(-10, 5, -10);
                scene.add(backLight);

                const fillLight = new THREE.DirectionalLight(0xffffff, 0.35);
                fillLight.position.set(0, 8, 12);
                scene.add(fillLight);

                const toothGeometry = new THREE.CylinderGeometry(0.38, 0.22, 1.0, 32, 1);

                const enamelMaterialProps = {
                    color: 0xFFFFF0,
                    metalness: 0.05,
                    roughness: 0.28,
                    emissive: 0x111111,
                    envMapIntensity: 1.0
                };

                function createArch(teethArray, yPosition) {
                    const group = new THREE.Group();

                    teethArray.forEach((toothNum, i) => {
                        const material = new THREE.MeshStandardMaterial(enamelMaterialProps);
                        const mesh = new THREE.Mesh(toothGeometry, material);
                        mesh.castShadow = true;
                        mesh.receiveShadow = true;

                        const angle = Math.PI - (i / (teethArray.length - 1)) * Math.PI;
                        const x = Math.cos(angle) * 4.0;
                        const z = Math.sin(angle) * 3.5;

                        mesh.position.set(x, yPosition, z);
                        mesh.lookAt(0, yPosition, 0);

                        mesh.userData = {
                            tooth: toothNum,
                            originalColor: '#FFFFF0'
                        };

                        group.add(mesh);
                        teethMeshes.push(mesh);
                    });

                    scene.add(group);
                }

                createArch(adultUpper, 0.6);
                createArch(adultLower, -0.6);

                const gumGeometry = new THREE.TorusGeometry(3.9, 0.5, 12, 50, Math.PI);
                const gumMaterial = new THREE.MeshStandardMaterial({
                    color: 0xF5B7B1,
                    roughness: 0.85,
                    metalness: 0.0
                });

                const upperGums = new THREE.Mesh(gumGeometry, gumMaterial);
                upperGums.rotation.x = Math.PI / 2;
                upperGums.position.set(0, 1.1, 0);
                upperGums.receiveShadow = true;
                scene.add(upperGums);

                const lowerGums = new THREE.Mesh(gumGeometry, gumMaterial);
                lowerGums.rotation.x = Math.PI / 2;
                lowerGums.position.set(0, -1.1, 0);
                lowerGums.receiveShadow = true;
                scene.add(lowerGums);

                raycaster = new THREE.Raycaster();
                mouse = new THREE.Vector2();

                renderer.domElement.addEventListener('pointermove', onThreePointerMove);
                renderer.domElement.addEventListener('pointerleave', onThreePointerLeave);
                renderer.domElement.addEventListener('pointerdown', onThreePointerDown);

                function animate() {
                    requestAnimationFrame(animate);
                    controls.update();
                    renderer.render(scene, camera);
                }

                animate();

                setTimeout(() => {
                    loadingOverlay.style.opacity = '0';
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                    }, 500);
                }, 600);

                threeSceneInitialized = true;
                renderThreeVisuals();
            }

            function handleResize() {
                if (!renderer || !camera) return;
                const newWidth = container.clientWidth || 700;
                const newHeight = container.clientHeight || 480;
                camera.aspect = newWidth / newHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(newWidth, newHeight);
            }

            function renderThreeVisuals() {
                if (!teethMeshes.length) return;

                teethMeshes.forEach(mesh => {
                    const toothId = mesh.userData.tooth;
                    const state = ensureToothState(toothId);

                    mesh.scale.set(1, 1, 1);
                    mesh.material.emissive.setHex(0x111111);
                    mesh.material.emissiveIntensity = 0.12;

                    if (state.threeD) {
                        mesh.material.color.setStyle(state.threeD.colorHex);
                        mesh.userData.originalColor = state.threeD.colorHex;
                        mesh.userData.legend = state.threeD.code;
                    } else {
                        mesh.material.color.setStyle('#FFFFF0');
                        mesh.userData.originalColor = '#FFFFF0';
                        delete mesh.userData.legend;
                    }
                });

                if (selectedMesh && selectedTargetType === '3d') {
                    applySelectedMeshState(selectedMesh);
                }
            }

            function applySelectedMeshState(mesh) {
                if (!mesh) return;
                mesh.scale.set(1.18, 1.18, 1.18);
                mesh.material.emissive.setHex(0x8B0000);
                mesh.material.emissiveIntensity = 0.25;
            }

            function updateMousePosition(event) {
                const rect = renderer.domElement.getBoundingClientRect();
                mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
                mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
            }

            function showTooltip(event, mesh) {
                const toothNumber = mesh.userData.tooth;
                const toothName = getToothName(toothNumber);
                const treatment = ensureToothState(toothNumber).threeD;

                toothTooltipContent.innerHTML = `
                    <div class="text-xs font-extrabold tracking-wide text-red-200 mb-1">Tooth #${toothNumber}</div>
                    <div class="text-sm font-bold leading-tight">${toothName}</div>
                    <div class="mt-2 text-xs ${treatment ? 'text-emerald-200' : 'text-gray-300'}">
                        ${treatment ? `${treatment.code} - ${treatment.label}` : 'No treatment assigned'}
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

            function onThreePointerMove(event) {
                updateMousePosition(event);
                raycaster.setFromCamera(mouse, camera);

                const intersects = raycaster.intersectObjects(teethMeshes);

                if (intersects.length > 0) {
                    hoveredMesh = intersects[0].object;
                    const hoveredToothNumber = hoveredMesh.userData.tooth;
                    toothHoverLabel.innerText = selectedTooth && selectedTargetType === '3d' ?
                        `Selected: #${selectedTooth}` :
                        `Tooth #${hoveredToothNumber}`;

                    showTooltip(event, hoveredMesh);
                } else {
                    hoveredMesh = null;
                    toothHoverLabel.innerText = selectedTooth ? `Selected: #${selectedTooth}` : 'Select a tooth';
                    hideTooltip();
                }
            }

            function onThreePointerLeave() {
                hoveredMesh = null;
                toothHoverLabel.innerText = selectedTooth ? `Selected: #${selectedTooth}` : 'Select a tooth';
                hideTooltip();
            }

            function onThreePointerDown(event) {
                updateMousePosition(event);
                raycaster.setFromCamera(mouse, camera);

                const intersects = raycaster.intersectObjects(teethMeshes);

                if (intersects.length > 0) {
                    selectedMesh = intersects[0].object;
                    selectedTooth = selectedMesh.userData.tooth;
                    selectedTargetType = '3d';
                    selectedSurfaceKey = null;

                    const state = ensureToothState(selectedTooth);
                    selectedLegend = state.threeD ? state.threeD.code : null;

                    renderThreeVisuals();
                    updateSelectedToothUI();

                    if (hoveredMesh) {
                        showTooltip(event, hoveredMesh);
                    }
                }
            }

            const procedureStartTimestamp = Date.now();

            function formatElapsedTime(totalSeconds) {
                const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
                const seconds = String(totalSeconds % 60).padStart(2, '0');
                return `${hours}:${minutes}:${seconds}`;
            }

            function updateProcedureTimer() {
                const elapsedSeconds = Math.floor((Date.now() - procedureStartTimestamp) / 1000);
                procedureTimer.textContent = formatElapsedTime(elapsedSeconds);
            }

            applyTreatmentBtn.addEventListener('click', function() {
                if (!selectedTooth || !selectedTargetType) {
                    alert('Please select a tooth target first.');
                    selectedLegendPreview.textContent = selectedLegend ?
                        `${selectedLegend} - ${(getLegendByCode(selectedLegend)?.label || selectedLegend)}` :
                        'No legend selected yet.';
                    return;
                }

                if (!selectedLegend) {
                    alert('Please choose a treatment legend first.');
                    return;
                }

                applyLegendToSelectedTarget(selectedLegend);
            });

            clearCurrentToothBtn.addEventListener('click', function() {
                if (!selectedTooth || !selectedTargetType || !getSelectedRecord()) return;
                openResetModal();
            });

            confirmResetTreatmentBtn.addEventListener('click', clearSelectedTargetTreatment);
            cancelResetTreatmentBtn.addEventListener('click', closeResetModal);

            resetTreatmentModal.addEventListener('click', function(event) {
                if (event.target === resetTreatmentModal || event.target.classList.contains(
                        'modal-backdrop')) {
                    closeResetModal();
                }
            });

            window.addEventListener('resize', handleResize);

            const finishProcedureBtn = document.getElementById('finishProcedureBtn');
            const followUpBtn = document.getElementById('followUpBtn');
            const saveProcedureUrl = @json(route('dentist.odontogram.save', $appointment->id));
            const storeFollowUpUrl = @json(route('dentist.dentist.appointments.follow-up.store', $appointment->id));

            function showProcedureToast(message, type = 'success') {
                const existingToast = document.getElementById('procedureToast');
                if (existingToast) {
                    existingToast.remove();
                }

                const toast = document.createElement('div');
                toast.id = 'procedureToast';

                const isSuccess = type === 'success';

                toast.className = `
        fixed top-24 right-6 z-[100000]
        min-w-[280px] max-w-[420px]
        rounded-2xl px-5 py-4 shadow-2xl border
        flex items-start gap-3
        transition-all duration-300
        translate-x-6 opacity-0
        ${isSuccess
            ? 'bg-white border-green-100 text-gray-800'
            : 'bg-white border-red-100 text-gray-800'}
    `;

                toast.innerHTML = `
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
            ${isSuccess ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
            <i class="fa-solid ${isSuccess ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-extrabold ${isSuccess ? 'text-green-700' : 'text-red-700'}">
                ${isSuccess ? 'Success' : 'Error'}
            </p>
            <p class="text-sm font-semibold text-gray-700 mt-0.5 leading-relaxed">
                ${message}
            </p>
        </div>

        <button type="button" class="text-gray-400 hover:text-gray-700 transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

                toast.querySelector('button')?.addEventListener('click', () => {
                    toast.classList.add('translate-x-6', 'opacity-0');
                    setTimeout(() => toast.remove(), 250);
                });

                document.body.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.remove('translate-x-6', 'opacity-0');
                });

                setTimeout(() => {
                    toast.classList.add('translate-x-6', 'opacity-0');
                    setTimeout(() => toast.remove(), 250);
                }, 3500);
            }

            function resetFollowUpForm() {
                const form = document.getElementById('followUpForm');
                const dateInput = document.getElementById('followup_appointment_date');
                const timeInput = document.getElementById('followup_appointment_time');
                const reasonInput = document.getElementById('followup_reason');

                const datePill = document.getElementById('followUpDatePill');
                const slotPlaceholder = document.getElementById('followUpSlotPlaceholder');
                const slotContainer = document.getElementById('followUpSlotContainer');
                const slotGrid = document.getElementById('followUpSlotGrid');
                const selectedTimePill = document.getElementById('followUpSelectedTimePill');
                const selectedTimeText = document.getElementById('followUpSelectedTimeText');

                if (form) form.reset();
                if (dateInput) dateInput.value = '';
                if (timeInput) timeInput.value = '';
                if (reasonInput) reasonInput.value = '';

                if (typeof selectedDate !== 'undefined') selectedDate = null;
                if (typeof selectedTime !== 'undefined') selectedTime = null;

                document.getElementById('followUpDateError')?.classList.add('hidden');
                document.getElementById('followUpTimeError')?.classList.add('hidden');

                document.querySelector('.follow-up-cal-wrap')?.classList.remove('error');
                document.querySelector('.follow-up-slots-wrap')?.classList.remove('error');

                if (datePill) {
                    datePill.innerHTML = '';
                    datePill.classList.remove('show');
                }

                if (slotPlaceholder) {
                    slotPlaceholder.classList.remove('hidden');
                    slotPlaceholder.style.display = 'flex';
                    slotPlaceholder.innerHTML = `
            <i class="fa-regular fa-calendar-xmark"></i>
            <span>Select a date to see available slots</span>
        `;
                }

                if (slotContainer) {
                    slotContainer.classList.add('hidden');
                    slotContainer.style.display = 'none';
                }

                if (slotGrid) {
                    slotGrid.innerHTML = '';
                    slotGrid.style.display = 'none';
                }

                if (selectedTimePill) {
                    selectedTimePill.classList.add('hidden');
                    selectedTimePill.classList.remove('show');
                    selectedTimePill.style.display = 'none';
                }

                if (selectedTimeText) {
                    selectedTimeText.textContent = '';
                }
            }

            window.openFollowUpModal = function() {
                resetFollowUpForm();

                const modal = document.getElementById('followUpModal');
                if (!modal) return;

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                requestAnimationFrame(() => {
                    if (typeof renderCalendar === 'function') {
                        renderCalendar();
                    } else {
                        console.warn('renderCalendar is not loaded.');
                    }
                });
            };

            window.closeFollowUpModal = function() {
                const modal = document.getElementById('followUpModal');
                if (!modal) return;

                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

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
                        };

                        if (entry.status && entry.status.code) {
                            cleanEntry.status = entry.status;
                        }

                        if (entry.threeD && entry.threeD.code) {
                            cleanEntry.threeD = entry.threeD;
                        }

                        const surfaces = entry.surfaces || {};

                        ['top', 'left', 'center', 'right', 'bottom'].forEach(function(surfaceKey) {
                            if (surfaces[surfaceKey] && surfaces[surfaceKey].code) {
                                cleanEntry.surfaces[surfaceKey] = surfaces[surfaceKey];
                            }
                        });

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

            async function saveProcedure(completionAction, clickedButton, loadingText) {
                const originalButtonHtml = clickedButton.innerHTML;

                updateHiddenInput();

                if (!hasAppliedTreatmentThisSession) {
                    alert(
                        'Please apply at least one treatment to the tooth chart before finishing the procedure.'
                    );
                    return;
                }

                const cleanOdontogramData = getCleanOdontogramDataForSave();

                if (cleanOdontogramData.length === 0) {
                    alert(
                        'Please apply at least one treatment to the tooth chart before finishing the procedure.'
                    );
                    return;
                }

                const payload = {
                    odontogram_data: cleanOdontogramData,
                    oral_examination: document.getElementById('oralExaminationNotes').value,
                    diagnosis: document.getElementById('diagnosisNotes').value,
                    prescriptions: document.getElementById('prescriptionsNotes').value,
                    completion_action: completionAction,
                    has_applied_treatment: hasAppliedTreatmentThisSession,
                };

                finishProcedureBtn.disabled = true;
                followUpBtn.disabled = true;

                clickedButton.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> ${loadingText}`;

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

                    const result = await response.json();

                    if (!response.ok) {
                        console.error(result);
                        showProcedureToast(
                            result.message ||
                            'Failed to save procedure. Please check your inputs and try again.',
                            'error'
                        );
                        return;
                    }

                    showProcedureToast(result.message || 'Procedure completed successfully.', 'success');

                    if (result.redirect_url) {
                        setTimeout(() => {
                            window.location.href = result.redirect_url;
                        }, 900);
                    }

                } catch (error) {
                    console.error(error);
                   showProcedureToast('Something went wrong while saving the procedure.', 'error');
                } finally {
                    finishProcedureBtn.disabled = false;
                    followUpBtn.disabled = false;
                    clickedButton.innerHTML = originalButtonHtml;
                }
            }

            finishProcedureBtn.addEventListener('click', function() {
                saveProcedure('finished', this, 'Saving Procedure...');
            });

            followUpBtn.addEventListener('click', function() {
                openFollowUpModal();
            });

            document.getElementById('followUpForm')?.addEventListener('submit', async function(event) {
                event.preventDefault();

                const form = event.currentTarget;
                const dateInput = document.getElementById('followup_appointment_date');
                const timeInput = document.getElementById('followup_appointment_time');
                const reasonInput = document.getElementById('followup_reason');
                const confirmBtn = document.getElementById('confirmFollowUpBtn');

                let valid = true;

                if (!dateInput?.value) {
                    document.getElementById('followUpDateError')?.classList.remove('hidden');
                    document.querySelector('.follow-up-cal-wrap')?.classList.add('error');
                    valid = false;
                }

                if (!timeInput?.value) {
                    document.getElementById('followUpTimeError')?.classList.remove('hidden');
                    document.querySelector('.follow-up-slots-wrap')?.classList.add('error');
                    valid = false;
                }

                if (!reasonInput?.value.trim()) {
                    reasonInput.focus();
                    valid = false;
                }

                if (!valid) return;

                const originalBtnHtml = confirmBtn.innerHTML;
                confirmBtn.disabled = true;
                confirmBtn.innerHTML =
                    '<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Saving...';

                try {
                    const followUpResponse = await fetch(storeFollowUpUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });

                    const followUpResult = await followUpResponse.json().catch(() => null);

                    if (!followUpResponse.ok) {
                        alert(followUpResult?.message || 'Failed to schedule follow-up appointment.');
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = originalBtnHtml;
                        return;
                    }

                    await saveProcedure('follow_up', confirmBtn, 'Saving Procedure...');

                } catch (error) {
                    console.error(error);
                    alert('Something went wrong while scheduling the follow-up appointment.');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalBtnHtml;
                }
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

            if (dismissCancelProcedureBtn) {
                dismissCancelProcedureBtn.addEventListener('click', closeCancelProcedureModal);
            }

            if (confirmCancelProcedureBtn) {
                confirmCancelProcedureBtn.addEventListener('click', function() {
                    window.location.href = cancelProcedureRedirectUrl;
                });
            }

            if (cancelProcedureModal) {
                cancelProcedureModal.addEventListener('click', function(event) {
                    if (event.target === cancelProcedureModal || event.target.classList.contains(
                            'modal-backdrop')) {
                        closeCancelProcedureModal();
                    }
                });
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeResetModal();
                    closeCancelProcedureModal();
                }
            });

            updateProcedureTimer();
            setInterval(updateProcedureTimer, 1000);

            renderLegendButtons('');
            updateHiddenInput();
            render2DOdontogram();
            updateSelectedToothUI();
            updateHistoryButtons();

            document.addEventListener('keydown', handleHistoryKeyboardShortcuts);
        });
    </script>
@endsection
