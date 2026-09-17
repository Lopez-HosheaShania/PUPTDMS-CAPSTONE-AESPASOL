@extends('layouts.app')

@section('layout-role', 'dentist')

@section('title', 'Daily Treatment Records')

@section('styles')
    @vite('resources/css/pages/dentist/daily-treatment.css')
@endsection

@section('content')

    <main id="mainContent" class="app-page-shell dentist-records-page daily-treatment-page page-enter">
        <div class="w-full">

            <section class="dentist-hero mb-5">
                <div class="dentist-hero-content">
                    <div class="dentist-hero-icon">
                        <i class="fa-solid fa-notes-medical"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="dentist-hero-eyebrow">
                            <i class="fa-solid fa-tooth"></i>
                            Daily Treatment
                        </div>

                        <h1 class="dentist-hero-title">Daily Treatment Record</h1>
                    </div>
                </div>

                <div class="dentist-hero-actions">
                    <button type="button" class="ui-btn ui-btn-primary" onclick="openDailyCreateReportModal()">
                        <i class="fa-solid fa-plus"></i>
                        <span>Create Report</span>
                    </button>
                </div>
            </section>

            <section class="card service-records-card dtr-card">
                <div class="card-header dtr-card-header">
                    <div class="card-header-left">
                        <div class="card-header-icon">
                            <i class="fa-solid fa-table-list"></i>
                        </div>

                        <div>
                            <h2 class="card-title">Treatment Records</h2>
                            <p class="card-subtitle">Daily patient treatment entries</p>
                        </div>
                    </div>

                    <div class="card-header-right dtr-toolbar search-filter-row">
                        <div class="dtr-month-picker">
                            <input type="text" id="monthPicker" class="form-input-custom" data-month-only-picker
                                data-month-max-today placeholder="Select month" readonly>
                        </div>

                        <div class="voice-search-row">
                            <x-search-bar id="searchInput" placeholder="Search patient, program, treatment…"
                                callback="handleDailyTreatmentSearch" :debounce="350" clear-label="Clear search" />

                            <x-voice-input target="#searchInput" status-id="dailySearchVoiceStatus" label="Use voice search"
                                title="Voice search" />
                        </div>

                        <div class="dtr-filter-actions">
                            <button id="openFilter" type="button" class="global-filter-btn" aria-pressed="false"
                                onclick="openDailyFilterPanel()">
                                <i class="fa-solid fa-sliders"></i>
                                <span>Filter</span>
                                <span id="filterBadge" class="filter-badge"></span>
                            </button>

                            <button id="externalClearFilterBtn" type="button" class="global-filter-reset-btn hidden"
                                title="Reset filters" aria-label="Reset filters" onclick="clearDailyFilters()">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <x-pagination-bar id="dailyPagebarTop" info-id="dailyPageInfoTop" pagination-id="dailyPaginationTop"
                    position="top" :show-entries="true" page-size-id="dtPerPageSelect" page-size-callback="selectDailyPerPage"
                    :page-size-value="10" label="entries" hidden />

                <div class="card-body dtr-card-body">
                    <div id="dailyListView" class="table-size service-table-wrap dtr-table-wrap">
                        <table class="data-table service-table dtr-table">
                            <thead>
                                <tr>
                                    <th>Date/Time Requested</th>
                                    <th>Patient Name</th>
                                    <th>Email / Contact Number</th>
                                    <th>Office / Program</th>
                                    <th>Gender</th>
                                    <th>Treatment Done</th>
                                    <th>Date/Time Processed</th>
                                    <th>Minutes Processed</th>
                                    <th>Signature</th>
                                </tr>
                            </thead>

                            <tbody id="dailyTableBody"></tbody>
                        </table>
                    </div>

                    <div id="dailyGridView" class="service-record-grid dtr-grid" hidden></div>

                    <div id="dailyEmptyState" class="empty-state-host"></div>
                </div>

                <x-pagination-bar id="dailyPagebarBottom" info-id="dailyPageInfoBottom"
                    pagination-id="dailyPaginationBottom" position="bottom" label="entries" hidden />
            </section>
        </div>
    </main>

    <x-filter-drawer id="filterModal" title="Filter Records" close-id="dailyCloseFilterBtn"
        close-callback="closeDailyFilterPanel()" clear-id="filterResetBtn" clear-callback="clearDailyFilterPanelDraft()"
        clear-label="Clear Filters" cancel-id="filterCloseBtn" cancel-callback="closeDailyFilterPanel()"
        cancel-label="Cancel" apply-id="filterApplyBtn" apply-callback="applyDailyFilters()" apply-label="Show Results"
        results-id="dailyShowResultsText">

        <div id="dailyActiveFiltersSection" class="filter-active-section hidden">
            <div class="filter-active-header">
                <span class="filter-active-title">
                    Active Filters
                </span>

                <button id="dailyClearAllChipsBtn" type="button" class="filter-clear-all ui-btn ui-btn-secondary ui-btn-sm"
                    onclick="clearDailyFilterPanelDraft()">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Clear All</span>
                </button>
            </div>

            <div id="dailyActiveChipsContainer" class="active-filters-container"></div>
        </div>


        <x-filter-group title="Sort by Name">
            <div class="filter-chip-row" id="dailyNameSortGroup">
                <label class="choice-chip">
                    <input type="radio" name="daily_sort_name" value="az" class="chip-radio"
                        data-daily-filter-key="sort_name">
                    <span>A to Z</span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="daily_sort_name" value="za" class="chip-radio"
                        data-daily-filter-key="sort_name">
                    <span>Z to A</span>
                </label>
            </div>
        </x-filter-group>


        <x-filter-group title="Date Order">
            <div class="filter-chip-row" id="dailyDateSortGroup">
                <label class="choice-chip">
                    <input type="radio" name="daily_sort_date" value="desc" class="chip-radio"
                        data-daily-filter-key="sort_date">
                    <span>Newest First</span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="daily_sort_date" value="asc" class="chip-radio"
                        data-daily-filter-key="sort_date">
                    <span>Oldest First</span>
                </label>
            </div>
        </x-filter-group>


        <x-filter-group title="Office">
            <div class="filter-chip-row" id="dailyOfficeGroup">
                @foreach (['Administrative', 'Faculty', 'Dependent'] as $office)
                    <label class="choice-chip">
                        <input type="radio" name="daily_office_type" value="{{ $office }}" class="chip-radio"
                            data-daily-filter-key="office_type">
                        <span>{{ $office }}</span>
                    </label>
                @endforeach
            </div>
        </x-filter-group>


        <x-filter-group title="Course" class="filter-group-last">
            <div class="filter-chip-grid" id="dailyProgramGroup">
                @foreach (['BSIT', 'BSECE', 'BSBA - HRM', 'BSED - ENG', 'BSOA', 'BSPSYCH', 'DIT', 'BSME', 'BSBA - MM', 'BSED - MATH', 'DOMT'] as $course)
                    <label class="choice-chip">
                        <input type="radio" name="daily_program_code" value="{{ $course }}" class="chip-radio"
                            data-daily-filter-key="program_code">
                        <span>{{ $course }}</span>
                    </label>
                @endforeach
            </div>
        </x-filter-group>

    </x-filter-drawer>

    <div id="dailyRecordModal" class="ui-modal modal-overlay" aria-hidden="true"
        onclick="closeModalOnBackdrop(event, 'dailyRecordModal')">
        <div class="modal-box-inner um-user-modal report-create-modal max-w-5xl" onclick="event.stopPropagation()">
            <div
                class="um-user-modal-header px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#8B0000] via-[#a40000] to-[#6B0000] flex items-center justify-center shadow-lg shadow-red-900/20 flex-shrink-0">
                        <i class="fa-solid fa-user-pen text-white text-sm"></i>
                    </div>

                    <div class="min-w-0">
                        <h3 class="font-extrabold text-gray-800 text-lg leading-tight">Add Daily Treatment Record</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Cleaner entry form with live procedure timer and signature
                            upload.</p>
                    </div>
                </div>

                <button type="button" onclick="closeDailyRecordModal()" class="um-modal-x"
                    aria-label="Close add record modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="dailyRecordForm" class="flex-1 flex flex-col min-h-0" data-global-validation novalidate>
                <div class="um-user-modal-body">
                    <div class="um-user-main-card space-y-6">
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.9fr)]">
                            <section class="rounded-2xl border border-[#ead9d9] bg-[#fffafa] p-4 sm:p-5">
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div>
                                        <h4 class="text-base font-extrabold text-gray-800 leading-tight">Patient and
                                            Treatment Details</h4>
                                        <p class="text-xs text-gray-500 mt-1">Fields are spaced out para mas madaling
                                            basahin at encode-an.
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex items-center rounded-full bg-white px-3 py-1 text-[11px] font-bold text-[#8B0000] border border-[#f1d7d7]">
                                        Record Entry
                                    </span>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="recordTreatmentDate"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Treatment Date <span class="text-red-500">*</span>
                                        </label>
                                        <input id="recordTreatmentDate" name="treatment_date" type="text"
                                            class="form-input-custom js-flatpickr-date-max-today"
                                            placeholder="Select treatment date" data-field-label="Treatment Date"
                                            data-validation-rule="notFutureDate"
                                            data-required-message="Please select the treatment date." readonly required>
                                    </div>

                                    <div>
                                        <label for="recordGender"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Gender
                                        </label>
                                        <select id="recordGender" name="gender"
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white">
                                            <option value="">Select gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="recordPatientName"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Patient Name <span class="text-red-500">*</span>
                                        </label>
                                        <input id="recordPatientName" name="patient_name" type="text" maxlength="150"
                                            placeholder="Enter patient full name"
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white"
                                            data-field-label="Patient Name" data-validation-rule="personName"
                                            data-required-message="Please enter the patient's full name."
                                            data-pattern-message="Only letters, spaces, apostrophe, period, and hyphen are allowed."
                                            required>
                                    </div>

                                    <div>
                                        <label for="recordPatientEmail"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Email Address
                                        </label>
                                        <input id="recordPatientEmail" name="patient_email" type="email"
                                            maxlength="190" placeholder="name@example.com"
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white">
                                    </div>

                                    <div>
                                        <label for="recordPatientPhone"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Contact Number
                                        </label>
                                        <input id="recordPatientPhone" name="patient_phone" type="tel"
                                            inputmode="numeric" maxlength="13" placeholder="09XX XXX XXXX"
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white"
                                            data-field-label="Contact Number" data-validation-rule="philippineMobile"
                                            data-pattern-message="Contact number must start with 09 and contain exactly 11 digits.">
                                    </div>

                                    <div>
                                        <label for="recordOfficeType"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Office
                                        </label>
                                        <select id="recordOfficeType" name="office_type"
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white">
                                            <option value="">Select office</option>
                                            <option value="Administrative">Administrative</option>
                                            <option value="Faculty">Faculty</option>
                                            <option value="Alumni">Alumni</option>
                                            <option value="Dependent">Dependent</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="recordProgramCode"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Program / Course
                                        </label>
                                        <input id="recordProgramCode" name="program_code" type="text" maxlength="50"
                                            placeholder="e.g. BSIT"
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="recordTreatmentDone"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Treatment Done <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="recordTreatmentDone" name="treatment_done" rows="4" maxlength="150"
                                            placeholder="Describe the treatment performed"
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white resize-none"
                                            data-field-label="Treatment Done" data-required-message="Please describe the treatment performed." required></textarea>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-2xl border border-[#ead9d9] bg-white p-4 sm:p-5">
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div>
                                        <h4 class="text-base font-extrabold text-gray-800 leading-tight">Procedure Timer
                                            and Signature</h4>
                                        <p class="text-xs text-gray-500 mt-1">Mag-start ito once pinindot ang procedure
                                            start.</p>
                                    </div>
                                    <i class="fa-solid fa-clock text-[#8B0000] text-lg"></i>
                                </div>

                                <div
                                    class="rounded-2xl bg-gradient-to-br from-[#8B0000] via-[#a10000] to-[#6B0000] text-white p-5 shadow-lg">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-white/70 font-bold mb-2">Live
                                        Procedure Time</p>
                                    <div id="dailyTimerDisplay"
                                        class="text-3xl sm:text-[2rem] font-black tracking-[0.18em]">00:00:00</div>
                                    <p id="dailyTimerStatus" class="text-xs text-white/80 mt-3">Timer has not started yet.
                                    </p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 mt-4">
                                    <button type="button" id="dailyTimerStartBtn"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#8B0000] px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#6B0000]">
                                        <i class="fa-solid fa-play"></i>
                                        Start Procedure
                                    </button>

                                    <button type="button" id="dailyTimerStopBtn"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 transition hover:border-[#8B0000] hover:text-[#8B0000]">
                                        <i class="fa-solid fa-stop"></i>
                                        Stop Procedure
                                    </button>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2 mt-4">
                                    <div>
                                        <label for="recordTimeInDisplay"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Started At
                                        </label>
                                        <input id="recordTimeInDisplay" type="text" readonly
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-gray-50 text-gray-700">
                                    </div>

                                    <div>
                                        <label for="recordTimeOutDisplay"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Stopped At
                                        </label>
                                        <input id="recordTimeOutDisplay" type="text" readonly
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-gray-50 text-gray-700">
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="recordMinutesProcessed"
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                            Number of Minutes Processed
                                        </label>
                                        <input id="recordMinutesProcessed" name="minutes_processed" type="number"
                                            min="0" max="1440" readonly
                                            class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-gray-50 text-gray-700">
                                    </div>
                                </div>

                                <input id="recordTimeIn" name="time_in" type="hidden">
                                <input id="recordTimeOut" name="time_out" type="hidden">

                                <div class="mt-4">
                                    <label for="recordPatientSignature"
                                        class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Patient Signature
                                    </label>
                                    <input id="recordPatientSignature" name="patient_signature" type="file"
                                        accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                                        class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white file:mr-3 file:rounded-lg file:border-0 file:bg-[#fff0f0] file:px-3 file:py-2 file:text-xs file:font-bold file:text-[#8B0000]">
                                    <p class="text-[11px] text-gray-400 mt-2">Optional. Accepted formats: PNG, JPG, JPEG up
                                        to 5 MB.</p>
                                </div>

                                <div id="dailyRecordErrorBanner" class="report-modal-error hidden mt-4">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>Please complete the required fields before saving.</span>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <div class="modal-ft um-user-modal-footer">
                    <button type="button" onclick="closeDailyRecordModal()" class="modal-btn-ghost">
                        Cancel
                    </button>

                    <button type="button" id="saveDailyRecordBtn" class="modal-btn-confirm-reject um-save-user-btn">
                        <span class="btn-confirm-icon">
                            <i class="fa-solid fa-floppy-disk"></i>
                        </span>
                        <span>Save Record</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="createReportModal" class="ui-modal" aria-hidden="true">
        <div class="ui-modal-card modal-lg" role="dialog" aria-modal="true" aria-labelledby="dailyCreateReportTitle">

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 id="dailyCreateReportTitle" class="modal-title">
                            Create Daily Treatment Report
                        </h3>

                        <p class="modal-subtitle">
                            Fields marked <span class="text-yellow-500 font-bold">*</span> are required.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="createReportModal"
                    aria-label="Close create report modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="reportForm" class="modal-card-form" data-global-validation
                data-form-validation-rule="dailyTreatmentReport" data-discard-form
                data-discard-title="Discard report changes?"
                data-discard-subtitle="You have unsaved changes in this report."
                data-discard-message="Closing this modal will remove the report details you entered. Do you want to discard your changes?"
                novalidate>
                <div class="modal-bd">
                    <div class="modal-form-section">
                        <div class="modal-section-heading">
                            <div class="modal-section-icon">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>

                            <div>
                                <h4>Report Details</h4>
                                <p>Choose the report type, date range, and quantity.</p>
                            </div>
                        </div>

                        <div class="modal-form-grid">
                            <div class="modal-field" data-global-field>
                                <div class="global-label-row">
                                    <label for="reportName" class="global-form-label">
                                        Report Name
                                        <span class="required-mark">*</span>
                                    </label>

                                    <span id="reportNameCounter" class="char-counter">
                                        0 / 100
                                    </span>
                                </div>

                                <div class="global-voice-row" data-voice-field>
                                    <div class="global-voice-control">
                                        <input id="reportName" name="report_name" type="text" minlength="3"
                                            maxlength="100" class="form-input-custom"
                                            placeholder="e.g. Daily Treatment Report — Dec 2026"
                                            data-field-label="Report Name"
                                            data-required-message="Please enter a report name." data-char-limit="100"
                                            data-char-counter="#reportNameCounter" required>
                                    </div>

                                    <x-voice-input target="#reportName" status-id="reportNameVoiceStatus"
                                        label="Voice input for report name" title="Voice input" />
                                </div>

                                <div id="reportNameErr" class="global-field-error" data-error-for="reportName"
                                    aria-live="polite" aria-hidden="true"></div>
                            </div>

                            <div class="modal-field" data-global-field>
                                <label for="reportType" class="global-form-label">
                                    Report Type
                                    <span class="required-mark">*</span>
                                </label>

                                <select id="reportType" name="document_template_id" class="js-custom-select"
                                    data-placeholder="Select a report type" data-field-label="Report Type"
                                    data-required-message="Please select a report type." required>
                                    <option value="">
                                        Select a report type...
                                    </option>

                                    @foreach ($dailyTreatmentTemplates ?? collect() as $template)
                                        <option value="{{ $template->id }}"
                                            data-document-type="{{ $template->document_type }}">
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <div id="reportTypeErr" class="global-field-error" data-error-for="reportType"
                                    aria-live="polite" aria-hidden="true"></div>
                            </div>

                            <div class="modal-form-grid-2">

                                <div class="modal-field" data-global-field>
                                    <label for="dateFrom" class="global-form-label">
                                        From
                                        <span class="required-mark">*</span>
                                    </label>

                                    <div class="fp-date-input-wrap">
                                        <input id="dateFrom" name="date_from" type="text"
                                            class="form-input-custom js-flatpickr-date-max-today"
                                            placeholder="Select start date" data-field-label="From Date"
                                            data-required-message="Please select a start date."
                                            data-validation-rule="notFutureDate" readonly required>
                                    </div>

                                    <div id="dateFromErr" class="global-field-error" data-error-for="dateFrom"
                                        aria-live="polite" aria-hidden="true"></div>
                                </div>

                                <div class="modal-field" data-global-field>
                                    <label for="dateTo" class="global-form-label">
                                        To
                                        <span class="field-optional">
                                            (optional)
                                        </span>
                                    </label>

                                    <div class="fp-date-input-wrap">
                                        <input id="dateTo" name="date_to" type="text"
                                            class="form-input-custom js-flatpickr-date-max-today"
                                            placeholder="Select end date" data-field-label="To Date"
                                            data-validation-rule="notFutureDate" readonly>
                                    </div>

                                    <div id="dateToErr" class="global-field-error" data-error-for="dateTo"
                                        aria-live="polite" aria-hidden="true"></div>
                                </div>

                            </div>

                            <div>
                                <p class="modal-helper-text">
                                    <i class="fa-solid fa-circle-info mr-1"></i>
                                    Leave "To" empty to report on a single date.
                                </p>

                            </div>

                            <div class="modal-field modal-field-full" data-global-field>
                                <label class="global-form-label" for="reportQty">
                                    Quantity
                                    <span class="required-mark">*</span>
                                </label>

                                <div class="modal-inline-control">
                                    <div class="modal-inline-main">

                                        <div class="global-number-stepper" data-global-number-stepper>
                                            <button type="button" class="global-number-stepper-btn"
                                                data-number-step="-1" aria-label="Decrease quantity">
                                                <i class="fa-solid fa-minus"></i>
                                            </button>

                                            <input id="reportQty" name="quantity" type="number" value="1"
                                                min="1" max="100" step="1"
                                                class="global-number-stepper-input" data-number-stepper-input
                                                data-field-label="Quantity"
                                                data-required-message="Please enter a quantity."
                                                data-validation-rule="wholeNumber" required>

                                            <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                                aria-label="Increase quantity">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>

                                    </div>

                                    <span class="modal-helper-text">
                                        Whole numbers only
                                    </span>
                                </div>

                                <div id="reportQtyErr" class="global-field-error" data-error-for="reportQty"
                                    aria-live="polite" aria-hidden="true"></div>
                            </div>

                            <div>
                                <div id="formErrorBanner" class="modal-error-banner hidden">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>Please complete all required fields before downloading.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-ft">
                    <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="createReportModal">
                        Cancel
                    </button>

                    <button type="button" id="downloadReportBtn" class="ui-btn ui-btn-primary">
                        <i class="fa-solid fa-download"></i>
                        <span>Download</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="downloadCompleteModal" class="ui-modal" onclick="closeModalOnBackdrop(event, 'downloadCompleteModal')">
        <div class="ui-modal-card modal-box p-0 rounded-2xl overflow-hidden bg-white shadow-2xl max-w-sm">
            <div class="h-1.5 bg-gradient-to-r from-[#8B0000] to-[#FFD700] w-full"></div>
            <div class="px-8 py-10 text-center">
                <div
                    class="w-16 h-16 bg-green-50 border-2 border-green-200 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fa-solid fa-check text-green-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#8B0000] mb-2">Download Complete!</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-7">Your report has been successfully generated and
                    downloaded.
                </p>
                <button type="button" onclick="closeDownloadModal()"
                    class="px-8 py-2.5 rounded-xl bg-[#8B0000] hover:bg-[#6b0000] text-white font-bold text-sm shadow-sm transition-all w-full">Done</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const DTR_LIST_URL = "{{ route('dentist.dentist.reports.daily-treatment-record.list') }}";
        const DTR_STORE_URL = "{{ route('dentist.dentist.reports.daily-treatment-record.store') }}";
        const DTR_DOWNLOAD_URL = "{{ route('dentist.dentist.report.daily-treatment-record-download') }}";
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            "{{ csrf_token() }}";

        const dtrState = {
            search: '',
            month: '',
            office_type: '',
            program_code: '',
            sort_name: '',
            sort_date: '',
            page: 1,
            perPage: 10,
            total: 0,
        };

        let dtrDraft = {
            ...dtrState
        };
        let dtrListController = null;
        let dtrDraftCountController = null;
        let dtrDraftCountTimer = null;
        let dailyTimerInterval = null;
        let dailyTimerStartedAt = null;
        let dailyTimerStoppedAt = null;

        function escapeDtrHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatDateToMMDDYY(dateStr) {
            if (!dateStr) return '';

            const d = new Date(dateStr);
            if (Number.isNaN(d.getTime())) return escapeDtrHtml(dateStr);

            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            const yy = String(d.getFullYear()).slice(-2);

            return `${mm}/${dd}/${yy}`;
        }

        function formatDtrMonthLabel(value) {
            if (!/^\d{4}-\d{2}$/.test(value || '')) return '';

            const [year, month] = value.split('-');
            const date = new Date(Number(year), Number(month) - 1, 1);

            return date.toLocaleDateString('en-US', {
                month: 'long',
                year: 'numeric',
            });
        }

        function formatDtrClock(value) {
            if (!value) return '—';
            return escapeDtrHtml(value);
        }

        function formatDtrDateTimeDisplay(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';

            return date.toLocaleString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
            });
        }

        function toLocalDateInputValue(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';

            const pad = (value) => String(value).padStart(2, '0');
            const year = date.getFullYear();
            const month = pad(date.getMonth() + 1);
            const day = pad(date.getDate());
            const hour = pad(date.getHours());
            const minute = pad(date.getMinutes());
            const second = pad(date.getSeconds());

            return `${year}-${month}-${day}T${hour}:${minute}:${second}`;
        }

        function buildDtrParams(source = dtrState, options = {}) {
            const params = new URLSearchParams();

            if (source.month) params.set('month', source.month);
            if (source.search) params.set('search', source.search);
            if (source.office_type) params.set('office_type', source.office_type);
            if (source.program_code) params.set('program_code', source.program_code);
            if (source.sort_name) params.set('sort_name', source.sort_name);
            if (source.sort_date) params.set('sort_date', source.sort_date);

            params.set('per_page', options.perPage || source.perPage || 10);
            params.set('page', options.page || source.page || 1);

            return params;
        }

        function dtrFilterCount(source = dtrState) {
            return [source.office_type, source.program_code, source.sort_name, source.sort_date].filter(Boolean).length;
        }

        function updateFilterButtonState() {
            window.setGlobalFilterButtonState?.({
                buttonId: 'openFilter',
                badgeId: 'filterBadge',
                resetId: 'externalClearFilterBtn',
                count: dtrFilterCount(),
            });
        }

        function renderDailyRecords(records) {
            const tbody = document.getElementById('dailyTableBody');
            const grid = document.getElementById('dailyGridView');
            const listView = document.getElementById('dailyListView');
            const emptyState = document.getElementById('dailyEmptyState');

            if (tbody) tbody.innerHTML = '';
            if (grid) grid.innerHTML = '';

            const hasData = Array.isArray(records) && records.length > 0;

            if (!hasData) {
                if (listView) listView.hidden = true;
                if (grid) grid.hidden = true;
                renderDailyTreatmentEmptyState();
                return;
            }

            if (emptyState) {
                window.hideGlobalEmptyState?.(emptyState);
            }

            if (listView) listView.hidden = false;

            records.forEach(record => {
                const contact = [record.patient_email, record.patient_phone].filter(Boolean).join(' / ') || '—';
                const officeOrProgram = record.office_display || record.office_type || record.program_code || '—';
                const signature = record.has_signature ?
                    `
            <a href="${escapeDtrHtml(record.signature_url || '#')}" class="dtr-signature-preview" target="_blank" rel="noopener noreferrer" aria-label="View patient signature">
              <img src="${escapeDtrHtml(record.signature_url || '')}" alt="Patient signature" class="dtr-signature-image">
            </a>
          ` :
                    '<span class="dtr-signature no">No signature</span>';

                if (tbody) {
                    tbody.insertAdjacentHTML('beforeend', `
            <tr>
              <td class="whitespace-nowrap min-w-[150px]">
                <div class="text-gray-800">${escapeDtrHtml(record.requested_date_time || formatDateToMMDDYY(record.treatment_date) || '—')}</div>
              </td>
              <td class="min-w-[220px]">
                <div class="text-gray-800" data-patient-name>
                    ${escapeDtrHtml(record.patient_name || '—')}
                </div>
              </td>
            <td class="min-w-[240px] text-[12px] leading-5">${escapeDtrHtml(contact)}</td>
            <td class="min-w-[140px]">
              <div class="text-gray-800">${escapeDtrHtml(officeOrProgram)}</div>
              </td>
              <td class="text-center">${escapeDtrHtml(record.gender || '—')}</td>
              <td class="min-w-[220px] text-[12px] leading-5">${escapeDtrHtml(record.treatment_done || '—')}</td>
              <td class="whitespace-nowrap min-w-[150px] text-[12px] leading-5">
                <div class="text-gray-700">${escapeDtrHtml(record.processed_date_time || '—')}</div>
              </td>
              <td class="text-center whitespace-nowrap">
                <span class="inline-flex min-w-[56px] items-center justify-center rounded-full bg-[#fff5f5] px-3 py-1 text-xs font-bold text-[#8B0000]">
                  ${escapeDtrHtml(record.minutes_processed || '—')}${record.minutes_processed ? ' mins' : ''}
                </span>
              </td>
              <td class="text-center">${signature}</td>
            </tr>
          `);
                }
            });
        }

        function renderDailyTreatmentEmptyState() {
            const host =
                document.getElementById(
                    'dailyEmptyState'
                );

            if (!host) {
                return;
            }

            const hasSearch =
                Boolean(
                    String(
                        dtrState.search || ''
                    ).trim()
                );

            const hasFilters =
                Boolean(
                    dtrState.sort_name ||
                    dtrState.sort_date ||
                    dtrState.office_type ||
                    dtrState.program_code
                );

            if (hasSearch) {
                window.EmptyState?.renderSearch({
                    host,
                    input: '#searchInput',
                    query: dtrState.search,
                    title: `No results for “${dtrState.search}”`,
                    message: 'Try another patient name, program, treatment, email, or contact number.',
                });

                return;
            }

            if (hasFilters) {
                window.EmptyState?.render({
                    host,
                    icon: 'fa-sliders',
                    title: 'No matches for your filters',
                    message: 'Try removing or adjusting your filter criteria.',
                    actionHtml: `
                <button
                    type="button"
                    class="empty-state-btn"
                    onclick="clearDailyFilters()"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Clear filters
                </button>
            `,
                });

                return;
            }

            window.EmptyState?.render({
                host,
                icon: 'fa-folder-open',
                title: 'No daily treatment records found',
                message: 'There are no treatment records for the selected month.',
            });
        }

        async function fetchDailyRecords(options = {}) {
            if (dtrListController) dtrListController.abort();
            dtrListController = new AbortController();

            const params = buildDtrParams(dtrState, {
                page: options.page || dtrState.page,
                perPage: dtrState.perPage,
            });

            try {
                const res = await fetch(`${DTR_LIST_URL}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: dtrListController.signal,
                });

                if (!res.ok) throw new Error('Failed to load records');

                const json = await res.json();
                const meta = json.meta || {};

                dtrState.total = Number(meta.total || 0);
                dtrState.page = Number(meta.current_page || 1);
                dtrState.perPage = Number(meta.per_page || dtrState.perPage || 10);

                renderDailyRecords(json.data || []);
                renderDtrPagebar(meta);
                updateFilterButtonState();
                updateDailyDraftCountText(dtrState.total);
            } catch (err) {
                if (err.name === 'AbortError') return;

                console.error(err);
                dtrState.total = 0;
                renderDailyRecords([]);
                renderDtrPagebar({
                    total: 0,
                    from: 0,
                    to: 0,
                    current_page: 1,
                    last_page: 1,
                    per_page: dtrState.perPage
                });
                updateFilterButtonState();
            }
        }

        function renderDtrPagebar(meta = {}) {
            const currentPage =
                Number(
                    meta.current_page || 1
                );

            const lastPage =
                Number(
                    meta.last_page || 1
                );

            const total =
                Number(
                    meta.total || 0
                );

            const from =
                total ?
                Number(meta.from || 0) :
                0;

            const to =
                total ?
                Number(meta.to || 0) :
                0;

            window.renderGlobalPagination?.({
                currentPage,
                lastPage,
                total,
                from,
                to,

                containers: [
                    document.getElementById(
                        'dailyPaginationTop'
                    ),
                    document.getElementById(
                        'dailyPaginationBottom'
                    ),
                ],

                bars: [
                    document.getElementById(
                        'dailyPagebarTop'
                    ),
                    document.getElementById(
                        'dailyPagebarBottom'
                    ),
                ],

                infoElements: [
                    document.getElementById(
                        'dailyPageInfoTop'
                    ),
                    document.getElementById(
                        'dailyPageInfoBottom'
                    ),
                ],

                itemLabel: 'entries',

                onPageChange(page) {
                    dtrState.page =
                        Number(page) || 1;

                    fetchDailyRecords();
                },
            });

            setDailyPageSizeUI(
                meta.per_page ||
                dtrState.perPage ||
                10
            );
        }

        function handleDailyTreatmentSearch(value) {
            dtrState.search = String(value || '').trim();
            dtrState.page = 1;

            fetchDailyRecords();
        }

        window.handleDailyTreatmentSearch = handleDailyTreatmentSearch;

        function openDailyFilterPanel() {
            dtrDraft = {
                ...dtrState
            };

            renderDailyFilterDraft();
            updateDailyDraftCount();

            window.openFilterDrawer?.(
                'filterModal'
            );
        }

        function closeDailyFilterPanel() {
            window.closeFilterDrawer?.(
                'filterModal'
            );
        }

        window.openDailyFilterPanel =
            openDailyFilterPanel;

        window.closeDailyFilterPanel =
            closeDailyFilterPanel;

        function setDailyDraftFilter(key, value) {
            if (key === 'office_type') {
                dtrDraft.office_type = dtrDraft.office_type === value ? '' : value;
                if (dtrDraft.office_type) dtrDraft.program_code = '';
            } else if (key === 'program_code') {
                dtrDraft.program_code = dtrDraft.program_code === value ? '' : value;
                if (dtrDraft.program_code) dtrDraft.office_type = '';
            } else {
                dtrDraft[key] = dtrDraft[key] === value ? '' : value;
            }

            renderDailyFilterDraft();
            updateDailyDraftCount();
        }

        window.setDailyDraftFilter = setDailyDraftFilter;

        function syncDailyChoiceGroup(name, value) {
            document.querySelectorAll(`input[name="${name}"]`).forEach(input => {
                const active = input.value === value;

                input.checked = active;
                input.closest('.choice-chip')?.classList.toggle('active', active);
            });
        }

        function renderDailyFilterDraft() {
            syncDailyChoiceGroup('daily_sort_name', dtrDraft.sort_name);
            syncDailyChoiceGroup('daily_sort_date', dtrDraft.sort_date);
            syncDailyChoiceGroup('daily_office_type', dtrDraft.office_type);
            syncDailyChoiceGroup('daily_program_code', dtrDraft.program_code);

            renderDailyActiveChips();
        }

        function bindDailyChoiceChipFilters() {
            document.querySelectorAll('[data-daily-filter-key]').forEach(input => {
                if (input.dataset.dailyChoiceReady === 'true') return;

                input.dataset.dailyChoiceReady = 'true';

                input.addEventListener('click', event => {
                    const key = input.dataset.dailyFilterKey;
                    const value = input.value;

                    if (dtrDraft[key] === value) {
                        event.preventDefault();

                        dtrDraft[key] = '';

                        renderDailyFilterDraft();
                        updateDailyDraftCount();
                    }
                });

                input.addEventListener('change', event => {
                    if (!event.target.checked) return;

                    setDailyDraftFilter(input.dataset.dailyFilterKey, input.value);
                });
            });
        }

        function renderDailyActiveChips() {
            const section = document.getElementById('dailyActiveFiltersSection');
            const container = document.getElementById('dailyActiveChipsContainer');
            if (!section || !container) return;

            const chips = [];

            if (dtrDraft.sort_name) chips.push({
                label: `Name: ${dtrDraft.sort_name === 'za' ? 'Z to A' : 'A to Z'}`,
                key: 'sort_name'
            });
            if (dtrDraft.sort_date) chips.push({
                label: `Date: ${dtrDraft.sort_date === 'asc' ? 'Oldest First' : 'Newest First'}`,
                key: 'sort_date'
            });
            if (dtrDraft.office_type) chips.push({
                label: `Office: ${dtrDraft.office_type}`,
                key: 'office_type'
            });
            if (dtrDraft.program_code) chips.push({
                label: `Course: ${dtrDraft.program_code}`,
                key: 'program_code'
            });

            section.classList.toggle('hidden', chips.length === 0);

            container.innerHTML = chips.map(chip => `
      <span class="filter-chip">
        <span>${escapeDtrHtml(chip.label)}</span>

        <button type="button" class="filter-chip-remove" onclick="removeDailyDraftChip('${chip.key}')"
            aria-label="Remove ${escapeDtrHtml(chip.label)} filter">

            <i class="fa-solid fa-xmark"></i>
          </button>
        </span>
      `).join('');
        }

        function removeDailyDraftChip(key) {
            dtrDraft[key] = '';
            renderDailyFilterDraft();
            updateDailyDraftCount();
        }

        window.removeDailyDraftChip = removeDailyDraftChip;

        function clearDailyFilterPanelDraft() {
            dtrDraft.office_type = '';
            dtrDraft.program_code = '';
            dtrDraft.sort_name = '';
            dtrDraft.sort_date = '';

            renderDailyFilterDraft();
            updateDailyDraftCount();
        }

        window.clearDailyFilterPanelDraft = clearDailyFilterPanelDraft;

        function clearDailyFilters() {
            dtrState.office_type = '';
            dtrState.program_code = '';
            dtrState.sort_name = '';
            dtrState.sort_date = '';
            dtrState.page = 1;

            dtrDraft = {
                ...dtrState
            };
            renderDailyFilterDraft();
            fetchDailyRecords();
        }

        window.clearDailyFilters = clearDailyFilters;

        function applyDailyFilters() {
            dtrState.office_type = dtrDraft.office_type || '';
            dtrState.program_code = dtrDraft.program_code || '';
            dtrState.sort_name = dtrDraft.sort_name || '';
            dtrState.sort_date = dtrDraft.sort_date || '';
            dtrState.page = 1;

            fetchDailyRecords();
            closeDailyFilterPanel();
        }

        window.applyDailyFilters = applyDailyFilters;

        function updateDailyDraftCountText(total) {
            const text = document.getElementById('dailyShowResultsText');
            if (!text) return;

            const value = Number(total || 0);
            text.textContent = `Show ${value} ${value === 1 ? 'result' : 'results'}`;
        }

        function updateDailyDraftCount() {
            clearTimeout(dtrDraftCountTimer);

            dtrDraftCountTimer = setTimeout(async () => {
                if (dtrDraftCountController) dtrDraftCountController.abort();
                dtrDraftCountController = new AbortController();

                const params = buildDtrParams({
                    ...dtrDraft,
                    page: 1,
                    perPage: 1
                }, {
                    page: 1,
                    perPage: 1
                });

                try {
                    const res = await fetch(`${DTR_LIST_URL}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: dtrDraftCountController.signal,
                    });

                    if (!res.ok) throw new Error('Unable to count records');

                    const json = await res.json();
                    updateDailyDraftCountText(json.meta?.total || 0);
                } catch (err) {
                    if (err.name !== 'AbortError') updateDailyDraftCountText(0);
                }
            }, 180);
        }

        function setDailyPageSizeUI(value) {
            const normalized = String(value || 10);
            const hidden = document.getElementById('dtPerPageSelect');

            if (hidden) {
                hidden.value = normalized;
                window.syncGlobalPageSizeSelect?.(hidden, normalized);
            }
        }

        function selectDailyPerPage(value) {
            const selectedValue =
                Number(value) || 10;

            dtrState.perPage =
                selectedValue;

            dtrState.page = 1;

            setDailyPageSizeUI(
                selectedValue
            );

            fetchDailyRecords();
        }

        window.selectDailyPerPage =
            selectDailyPerPage;

        function openDailyCreateReportModal() {
            const modal =
                document.getElementById(
                    'createReportModal'
                );

            if (!modal) {
                return;
            }

            resetDailyReportForm();

            window.initCustomSelects?.(
                modal
            );

            modal
                .querySelectorAll(
                    '.custom-select'
                )
                .forEach(wrapper => {
                    window.syncCustomSelect?.(
                        wrapper
                    );
                });

            window.openModal?.(
                'createReportModal'
            );

            window.initGlobalVoiceInputs?.(
                modal
            );

            window.initGlobalNumberSteppers?.(
                modal
            );

            window.DiscardChanges
                ?.captureForm(
                    document.getElementById(
                        'reportForm'
                    )
                );
        }

        function closeDailyCreateReportModal() {
            window.closeModal?.(
                'createReportModal'
            );

            window.setTimeout(
                resetDailyReportForm,
                180
            );
        }

        function closeDownloadModal() {
            window.closeModal?.(
                'downloadCompleteModal'
            );
        }

        window.openDailyCreateReportModal =
            openDailyCreateReportModal;

        window.closeDailyCreateReportModal =
            closeDailyCreateReportModal;

        window.closeDownloadModal =
            closeDownloadModal;

        function formatDailyElapsedTime(totalSeconds) {
            const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            return `${hours}:${minutes}:${seconds}`;
        }

        function getDailyTimerElements() {
            return {
                display: document.getElementById('dailyTimerDisplay'),
                status: document.getElementById('dailyTimerStatus'),
                timeIn: document.getElementById('recordTimeIn'),
                timeOut: document.getElementById('recordTimeOut'),
                timeInDisplay: document.getElementById('recordTimeInDisplay'),
                timeOutDisplay: document.getElementById('recordTimeOutDisplay'),
                minutes: document.getElementById('recordMinutesProcessed'),
                startBtn: document.getElementById('dailyTimerStartBtn'),
                stopBtn: document.getElementById('dailyTimerStopBtn'),
            };
        }

        function syncDailyTimerButtons(isRunning) {
            const {
                startBtn,
                stopBtn
            } = getDailyTimerElements();
            startBtn?.classList.toggle('opacity-60', isRunning);
            startBtn?.classList.toggle('cursor-not-allowed', isRunning);
            stopBtn?.classList.toggle('opacity-60', !isRunning && !dailyTimerStartedAt);
            stopBtn?.classList.toggle('cursor-not-allowed', !isRunning && !dailyTimerStartedAt);

            if (startBtn) startBtn.disabled = isRunning;
            if (stopBtn) stopBtn.disabled = !isRunning;
        }

        function updateDailyTimerDisplay() {
            const elements = getDailyTimerElements();
            if (!elements.display || !elements.minutes) return;

            if (!dailyTimerStartedAt) {
                elements.display.textContent = '00:00:00';
                elements.minutes.value = 0;
                elements.status.textContent = 'Timer has not started yet.';
                syncDailyTimerButtons(false);
                return;
            }

            const endDate = dailyTimerStoppedAt || new Date();
            const elapsedSeconds = Math.max(0, Math.floor((endDate.getTime() - dailyTimerStartedAt.getTime()) / 1000));
            elements.display.textContent = formatDailyElapsedTime(elapsedSeconds);
            elements.minutes.value = Math.floor(elapsedSeconds / 60);
            elements.status.textContent = dailyTimerStoppedAt ?
                'Procedure timer stopped and ready to save.' :
                'Procedure is running live.';
            syncDailyTimerButtons(!dailyTimerStoppedAt);
        }

        function startDailyProcedureTimer() {
            if (dailyTimerInterval) {
                clearInterval(dailyTimerInterval);
                dailyTimerInterval = null;
            }

            dailyTimerStartedAt = new Date();
            dailyTimerStoppedAt = null;

            const elements = getDailyTimerElements();
            if (elements.timeIn) elements.timeIn.value = toLocalDateInputValue(dailyTimerStartedAt);
            if (elements.timeOut) elements.timeOut.value = '';
            if (elements.timeInDisplay) elements.timeInDisplay.value = formatDtrDateTimeDisplay(dailyTimerStartedAt);
            if (elements.timeOutDisplay) elements.timeOutDisplay.value = '';

            updateDailyTimerDisplay();
            dailyTimerInterval = setInterval(updateDailyTimerDisplay, 1000);
        }

        function stopDailyProcedureTimer() {
            if (!dailyTimerStartedAt) return;

            dailyTimerStoppedAt = new Date();

            const elements = getDailyTimerElements();
            if (elements.timeOut) elements.timeOut.value = toLocalDateInputValue(dailyTimerStoppedAt);
            if (elements.timeOutDisplay) elements.timeOutDisplay.value = formatDtrDateTimeDisplay(dailyTimerStoppedAt);

            if (dailyTimerInterval) {
                clearInterval(dailyTimerInterval);
                dailyTimerInterval = null;
            }

            updateDailyTimerDisplay();
        }

        function resetDailyRecordForm() {
            const form =
                document.getElementById(
                    'dailyRecordForm'
                );

            const today =
                new Date();

            form?.reset();

            dailyTimerStartedAt =
                null;

            dailyTimerStoppedAt =
                null;

            if (dailyTimerInterval) {
                clearInterval(
                    dailyTimerInterval
                );

                dailyTimerInterval =
                    null;
            }

            const elements =
                getDailyTimerElements();

            if (elements.timeIn) {
                elements.timeIn.value = '';
            }

            if (elements.timeOut) {
                elements.timeOut.value = '';
            }

            if (elements.timeInDisplay) {
                elements.timeInDisplay.value = '';
            }

            if (elements.timeOutDisplay) {
                elements.timeOutDisplay.value = '';
            }

            const dateInput =
                document.getElementById(
                    'recordTreatmentDate'
                );

            if (dateInput) {
                dateInput.value =
                    today
                    .toISOString()
                    .split('T')[0];
            }

            const banner =
                document.getElementById(
                    'dailyRecordErrorBanner'
                );

            banner?.classList.add(
                'hidden'
            );

            banner?.classList.remove(
                'flex'
            );

            [
                'recordTreatmentDate',
                'recordPatientName',
                'recordTreatmentDone',
            ].forEach(id => {
                const input =
                    document.getElementById(id);

                input?.classList.remove(
                    'border-red-400'
                );

                input?.classList.add(
                    'border-gray-300'
                );
            });

            updateDailyTimerDisplay();
        }

        function resetDailyReportForm() {
            const form =
                document.getElementById(
                    'reportForm'
                );

            if (!form) {
                return;
            }

            form.reset();

            window.initCustomSelects?.(
                form
            );

            const quantityInput =
                document.getElementById(
                    'reportQty'
                );

            if (quantityInput) {
                quantityInput.value = '1';

                quantityInput.dispatchEvent(
                    new Event(
                        'input', {
                            bubbles: true
                        }
                    )
                );
            }

            ['dateFrom', 'dateTo']
            .forEach(id => {
                document
                    .getElementById(id)
                    ?._flatpickr
                    ?.clear(false);
            });

            form
                .querySelectorAll(
                    'input, textarea, select'
                )
                .forEach(field => {
                    field.classList.remove(
                        'is-invalid',
                        'is-valid'
                    );

                    field.removeAttribute(
                        'aria-invalid'
                    );

                    field.removeAttribute(
                        'aria-describedby'
                    );

                    field.setCustomValidity('');
                });

            form
                .querySelectorAll(
                    '.custom-select'
                )
                .forEach(wrapper => {
                    wrapper.classList.remove(
                        'is-invalid',
                        'is-valid'
                    );

                    window.syncCustomSelect?.(
                        wrapper
                    );
                });

            form
                .querySelectorAll(
                    '.global-field-error'
                )
                .forEach(error => {
                    error.classList.remove(
                        'show',
                        'is-success'
                    );

                    error.innerHTML = '';

                    error.setAttribute(
                        'aria-hidden',
                        'true'
                    );
                });

            document
                .getElementById(
                    'formErrorBanner'
                )
                ?.classList
                .add('hidden');

            const counter =
                document.getElementById(
                    'reportNameCounter'
                );

            if (counter) {
                counter.textContent =
                    '0 / 100';

                counter.className =
                    'char-counter';
            }

            window.initGlobalNumberSteppers?.(
                form
            );
        }

        function openDailyRecordModal() {
            const modal = document.getElementById('dailyRecordModal');
            if (!modal) return;

            resetDailyRecordForm();
            modal.classList.remove('closing');
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.documentElement.classList.add('modal-lock');
            document.body.classList.add('modal-lock');
        }

        function closeDailyRecordModal() {
            if (typeof window.closeModal === 'function') {
                window.closeModal('dailyRecordModal');
            } else {
                forceCloseDailyModal('dailyRecordModal');
            }

            resetDailyRecordForm();
        }

        window.openDailyRecordModal = openDailyRecordModal;
        window.closeDailyRecordModal = closeDailyRecordModal;

        async function saveDailyRecord() {
            const saveBtn = document.getElementById('saveDailyRecordBtn');
            const banner = document.getElementById('dailyRecordErrorBanner');
            const treatmentDate = document.getElementById('recordTreatmentDate');
            const patientName = document.getElementById('recordPatientName');
            const treatmentDone = document.getElementById('recordTreatmentDone');

            const form =
                document.getElementById(
                    'dailyRecordForm'
                );

            const validation =
                window.validateGlobalForm?.(
                    form
                );

            if (
                validation &&
                !validation.valid
            ) {
                return;
            }

            banner.classList.add('hidden');
            banner.classList.remove('flex');

            const formData = new FormData(form);
            formData.append('_token', CSRF_TOKEN);

            const originalHtml = saveBtn?.innerHTML || '';
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.classList.add('opacity-70', 'cursor-not-allowed');
                saveBtn.innerHTML =
                    '<span class="btn-confirm-icon"><i class="fa-solid fa-spinner fa-spin"></i></span><span>Saving...</span>';
            }

            try {
                const response = await fetch(DTR_STORE_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const payload = await response.json();

                if (!response.ok) {
                    let message = payload.message || 'Unable to save the daily treatment record.';
                    if (payload.errors) {
                        const firstError = Object.values(payload.errors)[0];
                        if (Array.isArray(firstError) && firstError.length > 0) {
                            message = firstError[0];
                        }
                    }
                    throw new Error(message);
                }

                closeDailyRecordModal();
                dtrState.page = 1;
                await fetchDailyRecords();
            } catch (error) {
                banner.innerHTML =
                    `<i class="fa-solid fa-triangle-exclamation"></i><span>${escapeDtrHtml(error.message || 'Unable to save the daily treatment record.')}</span>`;
                banner.classList.remove('hidden');
                banner.classList.add('flex');
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                    saveBtn.innerHTML = originalHtml;
                }
            }
        }

        function registerDailyTreatmentReportValidation() {
            if (
                typeof window
                .registerGlobalFormValidationRule !==
                'function'
            ) {
                return;
            }

            window.registerGlobalFormValidationRule(
                'dailyTreatmentReport',
                form => {
                    const fromField =
                        form.querySelector(
                            '#dateFrom'
                        );

                    const toField =
                        form.querySelector(
                            '#dateTo'
                        );

                    const quantityField =
                        form.querySelector(
                            '#reportQty'
                        );

                    let valid = true;
                    let firstInvalid = null;

                    if (
                        fromField?.value &&
                        toField?.value &&
                        toField.value < fromField.value
                    ) {
                        window
                            .showFormInputValidationMessage?.(
                                toField,
                                'End date must be the same as or later than the start date.'
                            );

                        valid = false;
                        firstInvalid = toField;
                    }

                    if (
                        quantityField?.value !== ''
                    ) {
                        const quantity =
                            Number(
                                quantityField.value
                            );

                        if (
                            !Number.isInteger(quantity) ||
                            quantity < 1 ||
                            quantity > 100
                        ) {
                            window
                                .showFormInputValidationMessage?.(
                                    quantityField,
                                    'Quantity must be a whole number between 1 and 100.'
                                );

                            valid = false;

                            firstInvalid ||=
                                quantityField;
                        }
                    }

                    return {
                        valid,
                        firstInvalid,
                    };
                }
            );
        }

        window.addEventListener(
            'global-validation-ready',
            registerDailyTreatmentReportValidation
        );

        document.addEventListener(
            'DOMContentLoaded',
            registerDailyTreatmentReportValidation
        );

        async function downloadDailyReport() {
            const form =
                document.getElementById(
                    'reportForm'
                );

            const btn =
                document.getElementById(
                    'downloadReportBtn'
                );

            const banner =
                document.getElementById(
                    'formErrorBanner'
                );

            if (!form || !btn) {
                return;
            }

            function showBanner(
                message =
                'Please complete all required fields before downloading.'
            ) {
                if (!banner) {
                    return;
                }

                banner.innerHTML = `
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span>
        ${escapeDtrHtml(message)}
      </span>
    `;

                banner.classList.remove(
                    'hidden'
                );
            }

            function hideBanner() {
                banner?.classList.add(
                    'hidden'
                );
            }

            hideBanner();

            const validationResult =
                window.validateGlobalForm?.(
                    form
                );

            if (
                validationResult === false ||
                validationResult?.valid === false
            ) {
                showBanner();

                return;
            }

            const name =
                document.getElementById(
                    'reportName'
                )?.value
                ?.trim() || '';

            const templateId =
                document.getElementById(
                    'reportType'
                )?.value || '';

            const from =
                document.getElementById(
                    'dateFrom'
                )?.value || '';

            const to =
                document.getElementById(
                    'dateTo'
                )?.value || '';

            const qty =
                Number(
                    document.getElementById(
                        'reportQty'
                    )?.value || 0
                );

            if (
                !name ||
                !templateId ||
                !from ||
                !Number.isInteger(qty) ||
                qty < 1 ||
                qty > 100
            ) {
                showBanner();
                return;
            }

            window.DiscardChanges
                ?.markSubmitting(form);

            const originalBtnHtml =
                btn.innerHTML;

            btn.disabled = true;

            btn.setAttribute(
                'aria-busy',
                'true'
            );

            btn.innerHTML = `
    <i class="fa-solid fa-spinner fa-spin"></i>
    <span>Generating...</span>
  `;

            try {
                const formData =
                    new FormData();

                formData.append(
                    '_token',
                    CSRF_TOKEN
                );

                formData.append(
                    'report_name',
                    name
                );

                formData.append(
                    'document_template_id',
                    templateId
                );

                formData.append(
                    'date_from',
                    from
                );

                formData.append(
                    'quantity',
                    String(qty)
                );

                if (to) {
                    formData.append(
                        'date_to',
                        to
                    );
                }

                const response =
                    await fetch(
                        DTR_DOWNLOAD_URL, {
                            method: 'POST',

                            headers: {
                                'X-CSRF-TOKEN': CSRF_TOKEN,

                                'X-Requested-With': 'XMLHttpRequest',

                                'Accept': 'application/pdf, application/json',
                            },

                            body: formData,

                            credentials: 'same-origin',
                        }
                    );

                if (!response.ok) {
                    let message =
                        `Unable to generate the report. Server returned ${response.status}.`;

                    const contentType =
                        response.headers.get(
                            'content-type'
                        ) || '';

                    if (
                        contentType.includes(
                            'application/json'
                        )
                    ) {
                        const errorData =
                            await response.json();

                        message =
                            errorData.message ||
                            message;

                        if (
                            errorData.errors
                        ) {
                            const firstError =
                                Object.values(
                                    errorData.errors
                                )[0];

                            if (
                                Array.isArray(
                                    firstError
                                ) &&
                                firstError.length > 0
                            ) {
                                message =
                                    firstError[0];
                            }
                        }
                    }

                    throw new Error(
                        message
                    );
                }

                const blob =
                    await response.blob();

                const downloadUrl =
                    window.URL
                    .createObjectURL(blob);

                let fileName =
                    `${name.replace(
          /[^A-Za-z0-9_-]/g,
          '_'
        )}.pdf`;

                const disposition =
                    response.headers.get(
                        'Content-Disposition'
                    ) ||
                    response.headers.get(
                        'content-disposition'
                    ) ||
                    '';

                const fileNameMatch =
                    disposition.match(
                        /filename="?([^"]+)"?/i
                    );

                if (
                    fileNameMatch?.[1]
                ) {
                    fileName =
                        fileNameMatch[1];
                }

                const link =
                    document.createElement(
                        'a'
                    );

                link.href =
                    downloadUrl;

                link.download =
                    fileName;

                document.body.appendChild(
                    link
                );

                link.click();
                link.remove();

                window.URL
                    .revokeObjectURL(
                        downloadUrl
                    );

                window.DiscardChanges
                    ?.captureForm(form);

                closeDailyCreateReportModal();

                window.openModal?.(
                    'downloadCompleteModal'
                );

            } catch (error) {
                window.DiscardChanges
                    ?.markNotSubmitting(form);

                showBanner(
                    error.message ||
                    'Unable to generate the report. Please try again.'
                );

            } finally {
                btn.disabled = false;

                btn.removeAttribute(
                    'aria-busy'
                );

                btn.innerHTML =
                    originalBtnHtml;
            }
        }

        document.addEventListener(
            'DOMContentLoaded',
            async () => {
                resetDailyRecordForm();

                const monthPicker =
                    document.getElementById(
                        'monthPicker'
                    );

                const now =
                    new Date();

                const month =
                    String(
                        now.getMonth() + 1
                    ).padStart(
                        2,
                        '0'
                    );

                const year =
                    now.getFullYear();

                const currentMonthValue =
                    `${year}-${month}`;

                const datePickerModule =
                    await window.loadDatePickerModule?.();

                await datePickerModule
                    ?.initGlobalDatePickers(
                        document
                    );

                if (
                    monthPicker?._flatpickr &&
                    typeof window.setMonthOnlyPickerValue ===
                    'function'
                ) {
                    window.setMonthOnlyPickerValue(
                        monthPicker,
                        currentMonthValue,
                        false
                    );
                }

                dtrState.month =
                    currentMonthValue;

                monthPicker
                    ?.addEventListener(
                        'change',
                        event => {
                            dtrState.month =
                                event.target.value || '';

                            dtrState.page = 1;

                            fetchDailyRecords();
                        }
                    );

                document
                    .getElementById(
                        'dailyTimerStartBtn'
                    )
                    ?.addEventListener(
                        'click',
                        startDailyProcedureTimer
                    );

                document
                    .getElementById(
                        'dailyTimerStopBtn'
                    )
                    ?.addEventListener(
                        'click',
                        stopDailyProcedureTimer
                    );

                document
                    .getElementById(
                        'saveDailyRecordBtn'
                    )
                    ?.addEventListener(
                        'click',
                        saveDailyRecord
                    );

                document
                    .getElementById(
                        'downloadReportBtn'
                    )
                    ?.addEventListener(
                        'click',
                        downloadDailyReport
                    );

                bindDailyChoiceChipFilters();

                fetchDailyRecords();
            }
        );
    </script>
@endsection
