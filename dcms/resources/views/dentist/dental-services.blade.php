@extends('layouts.dentist')

@section('title', 'Dental Services Record | PUP Taguig Dental Clinic')

@section('content')
@php
$frontendRecords = collect($records ?? [])->values();
$selectedMonth = $selectedMonth ?? now()->format('Y-m');
@endphp

<main id="mainContent" class="dentist-page-shell dentist-records-page dental-services-page page-enter">
    <div class="w-full">

        <section class="dentist-hero mb-5">
            <div class="dentist-hero-content">
                <div class="dentist-hero-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <div class="min-w-0">
                    <div class="dentist-hero-eyebrow">
                        <i class="fa-solid fa-tooth"></i>
                        Dental Services
                    </div>

                    <h1 class="dentist-hero-title">Dental Services Record</h1>
                </div>
            </div>

            <div class="dentist-hero-actions">
                <button type="button" onclick="openDentalCreateReportModal()" class="btn-primary-global">
                    <i class="fa-solid fa-plus"></i>
                    Create Report
                </button>
            </div>
        </section>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-5 mb-8 kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon bg-red-50">
                    <i class="fa-solid fa-file-lines text-[#8B0000]"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="kpi-value" id="statTotal">0</div>
                    <div class="kpi-label">Total Records</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon bg-amber-50">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="kpi-value text-amber-600" id="statEmergency">0</div>
                    <div class="kpi-label">Emergency</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon bg-green-50">
                    <i class="fa-solid fa-circle-check text-green-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="kpi-value text-green-600" id="statNonEmergency">0</div>
                    <div class="kpi-label">Non-Emergency</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon bg-blue-50">
                    <i class="fa-solid fa-venus text-blue-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="kpi-value text-blue-600" id="statFemale">0</div>
                    <div class="kpi-label">Female Patients</div>
                </div>
            </div>
        </div>

        <section class="card service-records-card">
            <div class="card-header service-card-header">
                <div class="card-header-left">
                    <div class="card-header-icon">
                        <i class="fa-solid fa-table-list"></i>
                    </div>

                    <div>
                        <h2 class="card-title">Patient Records</h2>
                        <p class="card-subtitle">Dental service entries for the selected month</p>
                    </div>
                </div>

                <div class="card-header-right service-toolbar search-filter-row">
                    <div class="service-month-picker service-toolbar-month-picker">
                        <input type="text" id="monthPicker" class="form-input-custom service-period-input"
                            data-month-only-picker placeholder="Select month" readonly>

                        <i class="fa-solid fa-calendar-days service-month-icon"></i>
                    </div>

                    <div class="voice-search-row service-search-row">
                        <div class="search-wrap global-search" data-search-wrapper>
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>

                            <input id="searchInput" type="text" class="search-input" data-search-input
                                placeholder="Search name, program, contact…">

                            <button type="button" class="search-clear" data-search-clear aria-label="Clear search">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <button type="button" class="voice-search-mic external" data-voice-trigger
                            data-voice-target="#searchInput" data-voice-status="#dentalServicesVoiceStatus"
                            aria-label="Use voice search">
                            <i class="fa-solid fa-microphone"></i>
                        </button>

                        <span id="dentalServicesVoiceStatus" class="voice-status hidden" data-voice-status></span>
                    </div>

                    <button id="openFilter" type="button" class="global-filter-btn" aria-pressed="false"
                        onclick="openDentalFilterModal()">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Filter</span>
                        <span id="filterBadge" class="filter-badge"></span>
                    </button>

                    <button id="externalClearFilterBtn" type="button"
                        class="global-filter-reset-btn dental-clear-filter-btn hidden">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>
            </div>

            <div class="card-body service-card-body">
                <div class="sl-page-size-control">
                    <label for="servicePerPageSelect">Show</label>

                    <div class="global-page-size" data-global-page-size data-page-size-input="#servicePerPageSelect">

                        <select id="servicePerPageSelect" class="global-page-size-native" tabindex="-1"
                            aria-hidden="true">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>

                        <button type="button" class="global-page-size-trigger" data-global-page-size-trigger
                            aria-haspopup="listbox" aria-expanded="false">
                            <span data-global-page-size-value>10</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                        <div class="global-page-size-menu" data-global-page-size-menu role="listbox">
                            <button type="button" class="global-page-size-option is-selected" data-value="10"
                                role="option" aria-selected="true">
                                <span>10</span>
                                <i class="fa-solid fa-check"></i>
                            </button>

                            <button type="button" class="global-page-size-option" data-value="20" role="option"
                                aria-selected="false">
                                <span>20</span>
                                <i class="fa-solid fa-check"></i>
                            </button>

                            <button type="button" class="global-page-size-option" data-value="50" role="option"
                                aria-selected="false">
                                <span>50</span>
                                <i class="fa-solid fa-check"></i>
                            </button>

                            <button type="button" class="global-page-size-option" data-value="100" role="option"
                                aria-selected="false">
                                <span>100</span>
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </div>
                    </div>

                    <span>per page</span>
                </div>

                <div class="sl-pagination-wrap service-pagination-wrap"></div>
            </div>

            <div id="dentalServicesListView" class="table-responsive-fix service-table-wrap">
                <table class="data-table service-table service-dental-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Name of Patient</th>
                            <th>Course / Dept</th>
                            <th>Age</th>
                            <th>Male</th>
                            <th>Female</th>
                            <th>Senior</th>
                            <th>PWD</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Time Out</th>
                            <th>Duration</th>
                            <th>Emergency</th>
                            <th>Non-Emerg.</th>
                            <th>Sig.</th>
                        </tr>
                    </thead>

                    <tbody id="dentalServicesTableBody"></tbody>
                </table>
            </div>

            <div id="dentalServicesGridView" class="service-record-grid" hidden></div>

            <div id="dentalServicesEmptyState" class="empty-state-host"></div>

            <div class="sl-pagebar service-pagebar">
                <span class="sl-pagebar-info service-pagebar-info">
                    Showing <strong>0</strong> entries
                </span>

                <div class="sl-pagination-wrap service-pagination-wrap"></div>
            </div>
    </div>
    </section>
</main>

<div id="filterModal" class="filter-drawer-wrapper dental-services-filter" aria-hidden="true">
    <div class="filter-drawer-overlay" onclick="closeFilterDrawer('filterModal')"></div>

    <aside class="filter-drawer-panel flex flex-col bg-white">
        <div
            class="filter-drawer-header px-6 py-5 flex items-center justify-between flex-shrink-0 bg-white border-b border-gray-100">
            <div class="filter-drawer-title flex items-center gap-2">
                <i class="fa-solid fa-sliders text-xl"></i>
                <h2 class="text-xl font-extrabold">Filter Records</h2>
            </div>

            <button id="closeFilterModalBtn" type="button" class="text-gray-400 hover:text-gray-700 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="filter-drawer-body px-6 py-5 flex flex-col gap-6 flex-1 overflow-y-auto bg-white">
            <div id="activeFiltersSection" class="hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[13px] font-bold text-gray-800">Active Filters</span>
                    <button id="clearAllChipsBtn" type="button" class="clear-all-chips">
                        Clear All
                    </button>
                </div>

                <div id="activeChipsContainer" class="flex flex-wrap gap-2 pb-4 border-b border-gray-100"></div>
            </div>

            <div>
                <h3 class="filter-section-title">Sort by Name</h3>
                <div class="filter-chip-row">
                    <label class="choice-chip">
                        <input type="radio" name="sort" value="az" class="chip-radio">
                        <span>A → Z</span>
                    </label>

                    <label class="choice-chip">
                        <input type="radio" name="sort" value="za" class="chip-radio">
                        <span>Z → A</span>
                    </label>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Date Order</h3>
                <div class="filter-chip-row">
                    <label class="choice-chip">
                        <input type="radio" name="dateOrder" value="asc" class="chip-radio">
                        <span>Ascending</span>
                    </label>

                    <label class="choice-chip">
                        <input type="radio" name="dateOrder" value="desc" class="chip-radio">
                        <span>Descending</span>
                    </label>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Gender</h3>
                <div class="filter-chip-row">
                    <label class="choice-chip">
                        <input type="radio" name="gender" value="Male" class="chip-radio">
                        <span>Male</span>
                    </label>

                    <label class="choice-chip">
                        <input type="radio" name="gender" value="Female" class="chip-radio">
                        <span>Female</span>
                    </label>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Priority</h3>
                <div class="filter-chip-row">
                    <label class="choice-chip">
                        <input type="checkbox" name="gad" value="PWD" class="chip-radio gadPriority">
                        <span>PWD</span>
                    </label>

                    <label class="choice-chip">
                        <input type="checkbox" name="gad" value="Senior" class="chip-radio gadPriority">
                        <span>Senior</span>
                    </label>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Type</h3>
                <div class="filter-chip-row">
                    <label class="choice-chip">
                        <input type="radio" name="type" value="Emergency" class="chip-radio">
                        <span>Emergency</span>
                    </label>

                    <label class="choice-chip">
                        <input type="radio" name="type" value="Non-Emergency" class="chip-radio">
                        <span>Non-Emergency</span>
                    </label>
                </div>
            </div>

            <div class="pb-6">
                <h3 class="filter-section-title">Department</h3>
                <div class="filter-chip-row">
                    <label class="choice-chip">
                        <input type="radio" name="department" value="Administrative" class="chip-radio departmentRadio">
                        <span>Administrative</span>
                    </label>

                    <label class="choice-chip">
                        <input type="radio" name="department" value="Faculty" class="chip-radio departmentRadio">
                        <span>Faculty</span>
                    </label>

                    <label class="choice-chip">
                        <input type="radio" name="department" value="Dependent" class="chip-radio departmentRadio">
                        <span>Dependent</span>
                    </label>
                </div>
            </div>
        </div>

        <div
            class="filter-drawer-footer px-6 py-5 bg-white flex flex-col sm:flex-row items-center justify-between flex-shrink-0 border-t border-gray-100 gap-4 sm:gap-0 relative z-20">
            <button id="clearFilterBtn" type="button"
                class="filter-clear-btn flex items-center gap-2 transition-colors w-full sm:w-auto justify-center sm:justify-start">
                <i class="fa-regular fa-trash-can text-lg"></i>
                <span class="text-[13px] font-bold leading-none whitespace-nowrap">Clear Filters</span>
            </button>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button id="cancelFilterBtn" type="button"
                    class="filter-cancel-btn flex-1 sm:flex-none px-5 py-2.5 text-sm font-bold rounded-lg transition-colors">
                    Cancel
                </button>

                <button id="applyFiltersBtn" type="button"
                    class="filter-show-results-btn filter-apply-btn flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold rounded-lg transition-colors shadow-sm">
                    <i class="fa-solid fa-check"></i>
                    <span id="showResultsText">Show 0 results</span>
                </button>
            </div>
        </div>
    </aside>
</div>

<div id="createReportModal" class="ui-modal modal-overlay" aria-hidden="true"
    onclick="closeModalOnBackdrop(event, 'createReportModal')">
    <div class="modal-box-inner um-user-modal um-user-modal-md report-create-modal" onclick="event.stopPropagation()">
        <div
            class="um-user-modal-header px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3 min-w-0">
                <div
                    class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#8B0000] via-[#a40000] to-[#6B0000] flex items-center justify-center shadow-lg shadow-red-900/20 flex-shrink-0">
                    <i class="fa-solid fa-file-circle-plus text-white text-sm"></i>
                </div>

                <div class="min-w-0">
                    <h3 class="font-extrabold text-gray-800 text-lg leading-tight">Create Dental Services Report</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Fields marked <span class="text-yellow-500 font-bold">*</span> are required.
                    </p>
                </div>
            </div>

            <button type="button" onclick="closeCreateModal()" data-close-modal="createReportModal" class="um-modal-x"
                aria-label="Close create report modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="reportForm" class="flex-1 flex flex-col min-h-0" novalidate>
            <div class="um-user-modal-body">
                <div class="um-user-main-card">
                    <div class="um-section-title">
                        <div class="um-section-icon bg-red-50 text-[#8B0000]">
                            <i class="fa-solid fa-file-lines text-sm"></i>
                        </div>

                        <div>
                            <h4 class="text-base font-extrabold text-gray-800 leading-tight">Report Details</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Choose the report type, date range, and quantity.
                            </p>
                        </div>
                    </div>

                    <div class="um-field-grid">
                        <div class="um-field-full">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide">
                                    Report Name <span class="text-red-500">*</span>
                                </label>

                                <span id="reportNameCounter" class="text-[11px] font-semibold text-gray-400">0 /
                                    100</span>
                            </div>

                            <div class="voice-search-row" data-voice-field>
                                <input id="reportName" name="report_name" type="text" maxlength="100"
                                    placeholder="e.g. Dental Services Report — Dec 2026"
                                    class="field-input flex-1 min-w-0 border border-gray-200 px-3.5 py-3 text-sm bg-white">

                                <div class="voice-input-toggle">
                                    <button type="button" id="reportNameMicBtn" class="voice-search-mic external"
                                        data-voice-trigger data-voice-target="#reportName"
                                        data-voice-status="#reportNameVoiceStatus"
                                        aria-label="Voice input for report name">
                                        <i class="fa-solid fa-microphone"></i>
                                    </button>

                                    <span id="reportNameVoiceStatus" class="voice-status hidden" data-voice-status
                                        aria-live="polite"></span>
                                </div>
                            </div>

                            <p id="reportNameErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Report name is required.
                            </p>
                        </div>

                        <div class="um-field-full">
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                Report Type <span class="text-red-500">*</span>
                            </label>

                            <div class="report-custom-select report-template-select" data-report-select>
                                <select id="reportType" name="document_type" class="report-native-select"
                                    data-report-select-native>
                                    <option value="dental_services_report" data-document-type="dental_services_report"
                                        selected>Dental Services Report</option>
                                </select>

                                <button type="button" class="report-select-trigger" data-report-select-trigger
                                    data-placeholder="Select a report type..." aria-expanded="false">
                                    <span class="report-select-main">
                                        <span class="report-select-icon">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </span>
                                        <span data-report-select-label>Dental Services Report</span>
                                    </span>
                                    <i class="fa-solid fa-chevron-down report-select-chevron"></i>
                                </button>

                                <div class="report-select-menu" data-report-select-menu>
                                    <button type="button" class="report-select-option is-active"
                                        data-report-select-option data-value="dental_services_report"
                                        data-document-type="dental_services_report">
                                        <span>Dental Services Report</span>
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </div>
                            </div>

                            <p id="reportTypeErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Please select a report type.
                            </p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                From <span class="text-red-500">*</span>
                            </label>

                            <div class="fp-date-input-wrap">
                                <input id="dateFrom" name="date_from" type="text"
                                    class="field-input w-full border border-gray-200 px-3.5 py-3 pr-10 text-sm bg-white js-flatpickr-date-max-today"
                                    placeholder="Select start date" readonly>
                                <i class="fa-regular fa-calendar fp-date-icon"></i>
                            </div>

                            <p id="dateFromErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Start date is required.
                            </p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                To <span class="text-gray-400 normal-case font-normal">(optional)</span>
                            </label>

                            <div class="fp-date-input-wrap">
                                <input id="dateTo" name="date_to" type="text"
                                    class="field-input w-full border border-gray-200 px-3.5 py-3 pr-10 text-sm bg-white js-flatpickr-date-max-today"
                                    placeholder="Select end date" readonly>
                                <i class="fa-regular fa-calendar fp-date-icon"></i>
                            </div>
                        </div>

                        <div class="um-field-full">
                            <p class="text-[11px] text-gray-400 -mt-2">
                                <i class="fa-solid fa-circle-info mr-1"></i>
                                Leave "To" empty to report on a single date.
                            </p>

                            <p id="dateFutureErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Dates cannot be in the future.
                            </p>

                            <p id="dateRangeErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                End date must be on or after start date.
                            </p>
                        </div>

                        <div class="um-field-full">
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                Quantity <span class="text-red-500">*</span>
                            </label>

                            <div class="report-qty-row">
                                <div class="report-qty-control">
                                    <button type="button" class="report-qty-btn" data-qty-minus
                                        aria-label="Decrease quantity">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>

                                    <input id="reportQty" name="quantity" type="number" min="1" max="100" step="1"
                                        placeholder="1 – 100"
                                        class="field-input report-qty-input border border-gray-200 px-3.5 py-3 text-sm bg-white">

                                    <button type="button" class="report-qty-btn" data-qty-plus
                                        aria-label="Increase quantity">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>

                                <span class="report-qty-helper">Whole numbers only</span>
                            </div>

                            <p id="reportQtyErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span id="reportQtyErrMsg">Quantity must be between 1 and 100.</span>
                            </p>
                        </div>

                        <div class="um-field-full">
                            <div id="formErrorBanner" class="report-modal-error hidden">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Please complete all required fields before downloading.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-ft um-user-modal-footer">
                <button type="button" onclick="closeCreateModal()" class="modal-btn-ghost">
                    Cancel
                </button>

                <button type="button" id="downloadReportBtn" class="modal-btn-confirm-reject um-save-user-btn">
                    <span class="btn-confirm-icon">
                        <i class="fa-solid fa-download"></i>
                    </span>
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
                downloaded.</p>
            <button type="button" onclick="closeDownloadModal()"
                class="px-8 py-2.5 rounded-xl bg-[#8B0000] hover:bg-[#6b0000] text-white font-bold text-sm shadow-sm transition-all w-full">Done</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const records = @json($frontendRecords);
    const initialSelectedMonth = @json($selectedMonth);

    let searchKeyword = '';
    let nameSort = null;
    let dateSort = null;
    let selectedMonth = initialSelectedMonth || '';
    let serviceCurrentPage = 1;
    let servicePerPage = 10;
    let selectedGender = null;
    let selectedPriority = [];
    let selectedType = null;
    let selectedDepartment = null;


    function forceCloseDentalModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.remove('open', 'closing');
        modal.setAttribute('aria-hidden', 'true');

        if (!document.querySelector('.ui-modal.open, .modal-overlay.open, dialog[open]')) {
            document.documentElement.classList.remove('modal-lock');
            document.body.classList.remove('modal-lock');
        }
    }

    function closeModalOnBackdrop(event, id) {
        if (event.target?.id !== id) return;

        if (id === 'createReportModal') {
            closeCreateModal();
            return;
        }

        closeDownloadModal();
    }

    function resetDentalReportForm() {
        const form = document.getElementById('reportForm');
        if (form) form.reset();

        syncDentalReportCustomSelects(document.getElementById('createReportModal'));

        const counter = document.getElementById('reportNameCounter');
        if (counter) {
            counter.textContent = '0 / 100';
            counter.classList.remove('text-red-500');
            counter.classList.add('text-gray-400');
        }

        ['reportNameErr', 'reportTypeErr', 'dateFromErr', 'dateFutureErr', 'dateRangeErr', 'reportQtyErr', 'formErrorBanner'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            el.classList.add('hidden');
            el.classList.remove('flex');
        });

        ['reportName', 'reportType', 'dateFrom', 'dateTo', 'reportQty'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            el.classList.remove('border-red-400');
            el.classList.add('border-gray-300');
        });
    }

    function ensureDentalReportFlatpickrs() {
        if (!window.flatpickr) return;

        const todayStr = new Date().toISOString().split('T')[0];

        ['dateFrom', 'dateTo'].forEach(id => {
            const input = document.getElementById(id);
            if (!input) return;

            input.setAttribute('max', todayStr);

            if (input._flatpickr) {
                input._flatpickr.set('maxDate', 'today');
                return;
            }

            window.flatpickr(input, {
                dateFormat: 'Y-m-d',
                maxDate: 'today',
                allowInput: false,
                clickOpens: true,
                disableMobile: true,
                appendTo: document.body,
                positionElement: input,
                onOpen: (_dates, _str, instance) => {
                    instance.calendarContainer.style.zIndex = '1000000';
                },
            });
        });
    }

    function openDentalCreateReportModal() {
        const modal = document.getElementById('createReportModal');
        if (!modal) return;

        modal.classList.remove('closing');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');

        document.documentElement.classList.add('modal-lock');
        document.body.classList.add('modal-lock');

        initDentalReportCustomSelects(modal);
        syncDentalReportCustomSelects(modal);
        ensureDentalReportFlatpickrs();
        initDentalReportQtyButtons();

        window.initGlobalVoiceInputs?.(modal);
        document.dispatchEvent(new CustomEvent('voice:refresh', { detail: { root: modal } }));
    }

    function closeCreateModal() {
        if (typeof window.closeModal === 'function') {
            window.closeModal('createReportModal');
        } else {
            forceCloseDentalModal('createReportModal');
        }

        resetDentalReportForm();
    }

    function closeDownloadModal() {
        if (typeof window.closeModal === 'function') {
            window.closeModal('downloadCompleteModal');
        } else {
            forceCloseDentalModal('downloadCompleteModal');
        }
    }

    window.openDentalCreateReportModal = openDentalCreateReportModal;
    window.closeCreateModal = closeCreateModal;
    window.closeDownloadModal = closeDownloadModal;
    window.closeModalOnBackdrop = closeModalOnBackdrop;

    function closeDentalReportSelects(except = null) {
        document.querySelectorAll('.report-custom-select.open').forEach(select => {
            if (select === except) return;

            select.classList.remove('open');
            select.querySelector('[data-report-select-trigger]')?.setAttribute('aria-expanded', 'false');
        });
    }

    function syncDentalReportSelectUI(nativeSelect) {
        if (!nativeSelect) return;

        const wrap = nativeSelect.closest('[data-report-select]');
        if (!wrap) return;

        const label = wrap.querySelector('[data-report-select-label]');
        const trigger = wrap.querySelector('[data-report-select-trigger]');
        const selectedOption = nativeSelect.selectedOptions?.[0];
        const placeholder = trigger?.dataset.placeholder || 'Select option';
        const selectedText = nativeSelect.value ? selectedOption?.textContent?.trim() : placeholder;

        if (label) label.textContent = selectedText || placeholder;

        wrap.querySelectorAll('[data-report-select-option]').forEach(option => {
            const isActive = option.dataset.value === nativeSelect.value;
            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function syncDentalReportCustomSelects(root = document) {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
        scope.querySelectorAll('[data-report-select-native]').forEach(syncDentalReportSelectUI);
    }

    function initDentalReportCustomSelects(root = document) {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

        scope.querySelectorAll('[data-report-select]').forEach(wrap => {
            if (wrap.dataset.reportSelectInitialized === 'true') {
                syncDentalReportSelectUI(wrap.querySelector('[data-report-select-native]'));
                return;
            }

            wrap.dataset.reportSelectInitialized = 'true';

            const nativeSelect = wrap.querySelector('[data-report-select-native]');
            const trigger = wrap.querySelector('[data-report-select-trigger]');

            trigger?.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                const willOpen = !wrap.classList.contains('open');
                closeDentalReportSelects(wrap);
                wrap.classList.toggle('open', willOpen);
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            wrap.querySelectorAll('[data-report-select-option]').forEach(option => {
                option.addEventListener('click', event => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (!nativeSelect) return;

                    nativeSelect.value = option.dataset.value || '';
                    syncDentalReportSelectUI(nativeSelect);
                    nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    wrap.classList.remove('open');
                    trigger?.setAttribute('aria-expanded', 'false');
                });
            });

            nativeSelect?.addEventListener('change', () => syncDentalReportSelectUI(nativeSelect));
            syncDentalReportSelectUI(nativeSelect);
        });
    }

    function initDentalReportQtyButtons() {
        const input = document.getElementById('reportQty');
        const minus = document.querySelector('[data-qty-minus]');
        const plus = document.querySelector('[data-qty-plus]');
        if (!input || input.dataset.qtyReady === 'true') return;

        input.dataset.qtyReady = 'true';

        function normalize(value) {
            const parsed = parseInt(value, 10);
            if (Number.isNaN(parsed)) return '';
            return Math.min(100, Math.max(1, parsed));
        }

        function updateButtons() {
            const value = parseInt(input.value, 10);
            minus?.classList.toggle('is-disabled', Number.isNaN(value) || value <= 1);
            plus?.classList.toggle('is-disabled', !Number.isNaN(value) && value >= 100);
        }

        minus?.addEventListener('click', () => {
            const value = normalize(input.value || 1) || 1;
            input.value = Math.max(1, value - 1);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            updateButtons();
        });

        plus?.addEventListener('click', () => {
            const value = normalize(input.value || 0) || 0;
            input.value = Math.min(100, value + 1);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            updateButtons();
        });

        input.addEventListener('input', updateButtons);
        updateButtons();
    }

    function setDentalModalError(inputId, errId, show) {
        const input = document.getElementById(inputId);
        const err = document.getElementById(errId);
        if (!input || !err) return;

        const selectWrap = input.closest('[data-report-select]');
        if (selectWrap) selectWrap.classList.toggle('is-invalid', show);

        err.classList.toggle('hidden', !show);
        err.classList.toggle('flex', show);
        input.classList.toggle('border-red-400', show);
        input.classList.toggle('border-gray-300', !show);
    }

    function downloadDentalServicesReport() {
        const btn = document.getElementById('downloadReportBtn');
        const name = document.getElementById('reportName')?.value.trim() || '';
        const type = document.getElementById('reportType')?.value || '';
        const from = document.getElementById('dateFrom')?.value || '';
        const to = document.getElementById('dateTo')?.value || '';
        const qty = parseInt(document.getElementById('reportQty')?.value, 10);
        const banner = document.getElementById('formErrorBanner');
        const todayStr = new Date().toISOString().split('T')[0];

        let valid = true;

        function showBanner(message = 'Please complete all required fields before downloading.') {
            if (!banner) return;

            banner.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-red-500 flex-shrink-0"></i><span>${escapeDentalText(message)}</span>`;
            banner.classList.remove('hidden');
            banner.classList.add('flex');
        }

        function hideBanner() {
            banner?.classList.add('hidden');
            banner?.classList.remove('flex');
        }

        setDentalModalError('reportName', 'reportNameErr', !name);
        if (!name) valid = false;

        setDentalModalError('reportType', 'reportTypeErr', !type);
        if (!type) valid = false;

        ['dateFromErr', 'dateFutureErr', 'dateRangeErr'].forEach(id => {
            const el = document.getElementById(id);
            el?.classList.add('hidden');
            el?.classList.remove('flex');
        });

        ['dateFrom', 'dateTo'].forEach(id => {
            const el = document.getElementById(id);
            el?.classList.remove('border-red-400');
            el?.classList.add('border-gray-300');
        });

        if (!from) {
            document.getElementById('dateFromErr')?.classList.remove('hidden');
            document.getElementById('dateFromErr')?.classList.add('flex');
            document.getElementById('dateFrom')?.classList.add('border-red-400');
            document.getElementById('dateFrom')?.classList.remove('border-gray-300');
            valid = false;
        } else if (from > todayStr || (to && to > todayStr)) {
            document.getElementById('dateFutureErr')?.classList.remove('hidden');
            document.getElementById('dateFutureErr')?.classList.add('flex');
            valid = false;
        } else if (to && new Date(to) < new Date(from)) {
            document.getElementById('dateRangeErr')?.classList.remove('hidden');
            document.getElementById('dateRangeErr')?.classList.add('flex');
            document.getElementById('dateTo')?.classList.add('border-red-400');
            document.getElementById('dateTo')?.classList.remove('border-gray-300');
            valid = false;
        }

        const qtyInvalid = Number.isNaN(qty) || qty < 1 || qty > 100;
        document.getElementById('reportQtyErrMsg').textContent = Number.isNaN(qty) || qty < 1
            ? 'Quantity must be between 1 and 100.'
            : 'Quantity cannot exceed 100.';
        setDentalModalError('reportQty', 'reportQtyErr', qtyInvalid);
        if (qtyInvalid) valid = false;

        if (!valid) {
            showBanner();
            btn?.classList.add('animate-bounce');
            setTimeout(() => btn?.classList.remove('animate-bounce'), 600);
            return;
        }

        hideBanner();
        closeCreateModal();

        if (typeof window.openModal === 'function') {
            window.openModal('downloadCompleteModal');
        } else {
            const completeModal = document.getElementById('downloadCompleteModal');
            completeModal?.classList.add('open');
            completeModal?.setAttribute('aria-hidden', 'false');
            document.documentElement.classList.add('modal-lock');
            document.body.classList.add('modal-lock');
        }
    }

    document.addEventListener('click', event => {
        if (!event.target.closest('[data-report-select]')) closeDentalReportSelects();
    });

    function escapeDentalText(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function getSelectedMonthLabel() {
        if (!selectedMonth) return 'selected month';

        const [year, month] = selectedMonth.split('-');

        if (!year || !month) return 'selected month';

        return new Date(Number(year), Number(month) - 1, 1).toLocaleDateString('en-US', {
            month: 'long',
            year: 'numeric'
        });
    }

    function getDentalServicesEmptyStateHTML() {
        const isSearching = searchKeyword.trim().length > 0;

        const hasFilters = !!(
            selectedGender ||
            selectedType ||
            selectedDepartment ||
            nameSort ||
            dateSort ||
            selectedPriority.length
        );

        let icon = 'fa-folder-open';
        let title = `No record found for "${getSelectedMonthLabel()}"`;
        let sub = 'There are no dental service records for this month.';
        let action = '';

        if (isSearching) {
            icon = 'fa-magnifying-glass';
            title = `No results for "${searchKeyword}"`;
            sub = 'Try searching another patient name, program, contact, or email.';
            action = `
            <button type="button" class="empty-state-btn" data-empty-clear-search>
                <i class="fa-solid fa-xmark"></i>
                Clear search
            </button>
        `;
        } else if (hasFilters) {
            icon = 'fa-sliders';
            title = 'No matches for your filters';
            sub = 'Try removing or adjusting your filter criteria.';
            action = `
            <button type="button" class="empty-state-btn" data-empty-clear-filters>
                <i class="fa-solid fa-rotate-left"></i>
                Clear filters
            </button>
        `;
        }

        return `
        <div class="empty-state service-empty-state">
            <div class="empty-state-icon">
                <i class="fa-solid ${icon}"></i>
            </div>

            <h3 class="empty-state-title">${title}</h3>
            <p class="empty-state-sub">${sub}</p>

            ${action}
        </div>
    `;
    }

    function bindDentalServicesEmptyActions() {
        document.querySelectorAll('[data-empty-clear-search]').forEach((button) => {
            button.addEventListener('click', () => {
                const searchInput = document.getElementById('searchInput');

                if (!searchInput) return;

                if (typeof window.clearSearchInput === 'function') {
                    window.clearSearchInput(searchInput);
                } else {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    searchInput.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                    searchInput.focus();
                }
            });
        });

        document.querySelectorAll('[data-empty-clear-filters]').forEach((button) => {
            button.addEventListener('click', () => {
                resetDentalFilters({
                    closePanel: false
                });
            });
        });
    }

    function getRecordMonthKey(record) {
        if (record.monthKey) return record.monthKey;
        if (record.dateKey) return String(record.dateKey).slice(0, 7);

        const [month, , year] = String(record.date || '').split('/');

        if (!month || !year) return '';

        return `20${year}-${String(month).padStart(2, '0')}`;
    }

    function getDentalPagination(data) {
        const total = data.length;
        const lastPage = Math.max(1, Math.ceil(total / servicePerPage));

        serviceCurrentPage = Math.min(Math.max(1, serviceCurrentPage), lastPage);

        const from = total ? ((serviceCurrentPage - 1) * servicePerPage) + 1 : 0;
        const to = Math.min(serviceCurrentPage * servicePerPage, total);

        return {
            total,
            from,
            to,
            current_page: serviceCurrentPage,
            last_page: lastPage,
            per_page: servicePerPage,
            rows: data.slice(from ? from - 1 : 0, to)
        };
    }

    function renderDentalPagebar(pagination) {
        const infoHtml = pagination.total > 0
            ? `Showing <strong>${pagination.from}–${pagination.to}</strong> of <strong>${pagination.total}</strong> entries`
            : 'Showing <strong>0</strong> entries';

        document.querySelectorAll('.service-pagebar-info').forEach(el => {
            el.innerHTML = infoHtml;
        });

        document.querySelectorAll('.service-pagination-wrap').forEach(el => {
            el.innerHTML = buildDentalPagination(pagination);
        });

        const perPageSelect = document.getElementById('servicePerPageSelect');

        if (perPageSelect) {
            perPageSelect.value = String(servicePerPage);
            window.syncGlobalPageSizeSelect?.(perPageSelect, servicePerPage);
        }
    }

    function buildDentalPagination(p) {
        if (!p || Number(p.last_page || 1) <= 1) return '';

        const current = Number(p.current_page || 1);
        const last = Number(p.last_page || 1);
        const winSize = 5;
        const half = Math.floor(winSize / 2);

        let start = Math.max(1, current - half);
        let end = Math.min(last, start + winSize - 1);

        if (end - start + 1 < winSize) {
            start = Math.max(1, end - winSize + 1);
        }

        let html = '<nav class="sl-pagination" aria-label="Dental services pagination">';

        html += current <= 1
            ? '<button type="button" disabled class="sl-page-disabled" aria-label="Previous page"><i class="fa-solid fa-chevron-left sl-page-icon"></i></button>'
            : `<button type="button" onclick="dentalServiceGoPage(${current - 1})" class="sl-page-btn" aria-label="Previous page"><i class="fa-solid fa-chevron-left sl-page-icon"></i></button>`;

        if (start > 1) {
            html += '<button type="button" onclick="dentalServiceGoPage(1)" class="sl-page-btn">1</button>';
            if (start > 2) html += '<span class="sl-page-ellipsis" aria-hidden="true">&hellip;</span>';
        }

        for (let page = start; page <= end; page++) {
            html += page === current
                ? `<span class="sl-page-current" aria-current="page">${page}</span>`
                : `<button type="button" onclick="dentalServiceGoPage(${page})" class="sl-page-btn">${page}</button>`;
        }

        if (end < last) {
            if (end < last - 1) html += '<span class="sl-page-ellipsis" aria-hidden="true">&hellip;</span>';
            html += `<button type="button" onclick="dentalServiceGoPage(${last})" class="sl-page-btn">${last}</button>`;
        }

        html += current >= last
            ? '<button type="button" disabled class="sl-page-disabled" aria-label="Next page"><i class="fa-solid fa-chevron-right sl-page-icon"></i></button>'
            : `<button type="button" onclick="dentalServiceGoPage(${current + 1})" class="sl-page-btn" aria-label="Next page"><i class="fa-solid fa-chevron-right sl-page-icon"></i></button>`;

        html += '</nav>';

        return html;
    }

    function dentalServiceGoPage(page) {
        serviceCurrentPage = Number(page) || 1;
        applyFilters();
    }

    function renderRecords(data) {
        const tbody = document.getElementById('dentalServicesTableBody');
        const listView = document.getElementById('dentalServicesListView');
        const grid = document.getElementById('dentalServicesGridView');
        const emptyState = document.getElementById('dentalServicesEmptyState');

        tbody.innerHTML = '';
        if (grid) grid.innerHTML = '';

        document.getElementById('statTotal').textContent = data.length;
        document.getElementById('statEmergency').textContent = data.filter(r => r.type === 'Emergency').length;
        document.getElementById('statNonEmergency').textContent = data.filter(r => r.type === 'Non-Emergency').length;
        document.getElementById('statFemale').textContent = data.filter(r => r.gad?.gender === 'Female').length;

        const pagination = getDentalPagination(data);
        renderDentalPagebar(pagination);

        if (!data.length) {
            const emptyHTML = getDentalServicesEmptyStateHTML();

            if (listView) {
                listView.hidden = true;
                listView.setAttribute('aria-hidden', 'true');
            }

            if (grid) {
                grid.hidden = true;
                grid.setAttribute('aria-hidden', 'true');
            }

            if (emptyState) {
                emptyState.className = 'empty-state-host show';
                emptyState.innerHTML = emptyHTML;
            }

            bindDentalServicesEmptyActions();
            return;
        }

        if (emptyState) {
            emptyState.className = 'empty-state-host';
            emptyState.innerHTML = '';
        }

        if (listView) {
            listView.hidden = false;
            listView.removeAttribute('aria-hidden');
        }

        if (grid) {
            grid.hidden = true;
            grid.setAttribute('aria-hidden', 'true');
        }
        pagination.rows.forEach((r) => {
            const safeName = escapeDentalText(r.name);
            const safeProgram = escapeDentalText(r.program);
            const safeEmail = escapeDentalText(r.email);
            const safeContact = escapeDentalText(r.contact);
            const safeDate = escapeDentalText(r.date);
            const safeTimeIn = escapeDentalText(r.timeIn);
            const safeTimeOut = escapeDentalText(r.timeOut);
            const safeDuration = escapeDentalText(r.duration);
            const safeType = escapeDentalText(r.type);
            const safeGender = escapeDentalText(r.gad?.gender);
            const safeDepartment = escapeDentalText(r.department);

            const emergencyMark = r.type === 'Emergency'
                ? `<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>`
                : '';

            const nonEmergencyMark = r.type === 'Non-Emergency'
                ? `<span class="check-mark"><i class="fa-solid fa-check"></i></span>`
                : '';

            tbody.innerHTML += `
            <tr>
                <td class="muted-cell whitespace-nowrap">${safeDate}</td>
                <td class="whitespace-nowrap text-[11px]">${safeTimeIn}</td>
                <td class="name-cell">${safeName}</td>
                <td>${safeProgram}</td>
                <td>${escapeDentalText(r.age)}</td>
                <td>${r.gad?.gender === 'Male' ? '<span class="check-mark"><i class="fa-solid fa-check"></i></span>' : ''}</td>
                <td>${r.gad?.gender === 'Female' ? '<span class="check-mark"><i class="fa-solid fa-check"></i></span>' : ''}</td>
                <td>${(r.gad?.priority || []).includes('Senior') ? '<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>' : ''}</td>
                <td>${(r.gad?.priority || []).includes('PWD') ? '<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>' : ''}</td>
                <td class="muted-cell">${safeEmail}</td>
                <td class="text-[11px]">${safeContact}</td>
                <td class="whitespace-nowrap text-[11px]">${safeTimeOut}</td>
                <td class="text-[11px]">${safeDuration}</td>
                <td>${emergencyMark}</td>
                <td>${nonEmergencyMark}</td>
                <td>${r.has_signature ? '<span class="check-mark"><i class="fa-solid fa-check"></i></span>' : ''}</td>
            </tr>
        `;

            if (grid) {
                grid.innerHTML += `
                <article class="service-record-card">
                    <div class="service-record-card-head">
                        <div>
                            <h3>${safeName}</h3>
                            <p>${safeDate} • ${safeTimeIn} - ${safeTimeOut}</p>
                        </div>
                        <span class="service-record-chip">${safeType}</span>
                    </div>

                    <div class="service-record-meta">
                        <span><i class="fa-solid fa-graduation-cap"></i>${safeProgram}</span>
                        <span><i class="fa-solid fa-envelope"></i>${safeEmail}</span>
                        <span><i class="fa-solid fa-phone"></i>${safeContact}</span>
                        <span><i class="fa-solid fa-clock"></i>${safeDuration}</span>
                    </div>

                    <div class="service-record-footer">
                        <span>${safeGender}</span>
                        <span>${safeDepartment}</span>
                    </div>
                </article>
            `;
            }
        });
    }

    function parseRecordDate(dateString) {
        const [month, day, year] = dateString.split('/');
        return new Date(`20${year}-${month}-${day}`);
    }

    function applyFilters(resetPage = false) {
        if (resetPage) {
            serviceCurrentPage = 1;
        }

        renderRecords(getFilteredDentalRecords());
    }

    function getFilteredDentalRecords() {
        let data = [...records];

        if (searchKeyword) {
            data = data.filter(r => {
                const haystack = [
                    r.name,
                    r.program,
                    r.type,
                    r.contact,
                    r.email,
                    r.department,
                    r.gad?.gender,
                    ...(r.gad?.priority || [])
                ].join(' ').toLowerCase();

                return haystack.includes(searchKeyword);
            });
        }

        if (selectedGender) {
            data = data.filter(r => r.gad.gender === selectedGender);
        }

        if (selectedPriority.length) {
            data = data.filter(r =>
                selectedPriority.every(p => r.gad.priority.includes(p))
            );
        }

        if (selectedType) {
            data = data.filter(r => r.type === selectedType);
        }

        if (selectedDepartment) {
            data = data.filter(r => r.department === selectedDepartment);
        }

        if (selectedMonth) {
            data = data.filter(r => getRecordMonthKey(r) === selectedMonth);
        }

        if (dateSort === 'asc') {
            data.sort((a, b) => parseRecordDate(a.date) - parseRecordDate(b.date));
        }

        if (dateSort === 'desc') {
            data.sort((a, b) => parseRecordDate(b.date) - parseRecordDate(a.date));
        }

        if (nameSort === 'az') {
            data.sort((a, b) => a.name.localeCompare(b.name));
        }

        if (nameSort === 'za') {
            data.sort((a, b) => b.name.localeCompare(a.name));
        }

        return data;
    }

    function updateShowResultsButton() {
        const count = getFilteredDentalRecords().length;
        window.updateShowResultsText?.(count, 'showResultsText');

        const fallback = document.getElementById('showResultsText');
        if (fallback && typeof window.updateShowResultsText !== 'function') {
            fallback.textContent = `Show ${count} ${count === 1 ? 'result' : 'results'}`;
        }
    }

    function renderDentalFilterChips() {
        const section = document.getElementById('activeFiltersSection');
        const container = document.getElementById('activeChipsContainer');

        if (!section || !container) return;

        container.innerHTML = '';

        const chips = [];

        if (nameSort) chips.push({
            label: `Name: ${nameSort === 'az' ? 'A → Z' : 'Z → A'}`,
            clear: () => nameSort = null
        });
        if (dateSort) chips.push({
            label: `Date: ${dateSort === 'asc' ? 'Ascending' : 'Descending'}`,
            clear: () => dateSort = null
        });
        if (selectedGender) chips.push({
            label: selectedGender,
            clear: () => selectedGender = null
        });
        if (selectedType) chips.push({
            label: selectedType,
            clear: () => selectedType = null
        });
        if (selectedDepartment) chips.push({
            label: selectedDepartment,
            clear: () => selectedDepartment = null
        });

        selectedPriority.forEach(priority => {
            chips.push({
                label: priority,
                clear: () => {
                    selectedPriority = selectedPriority.filter(item => item !== priority);
                }
            });
        });

        chips.forEach(chipData => {
            const chip = document.createElement('div');
            chip.className = 'filter-chip';
            chip.innerHTML = `
            <span>${chipData.label}</span>
            <span class="filter-chip-remove">
                <i class="fa-solid fa-xmark"></i>
            </span>
        `;

            chip.querySelector('.filter-chip-remove').addEventListener('click', () => {
                chipData.clear();
                syncDentalFilterInputs();
                applyFilters();
                updateFilterButtonState();
                updateShowResultsButton();
                renderDentalFilterChips();
            });

            container.appendChild(chip);
        });

        section.classList.toggle('hidden', chips.length === 0);
    }

    function syncDentalFilterInputs() {
        document.querySelectorAll('#filterModal input').forEach(input => {
            if (input.name === 'sort') input.checked = input.value === nameSort;
            if (input.name === 'dateOrder') input.checked = input.value === dateSort;
            if (input.name === 'gender') input.checked = input.value === selectedGender;
            if (input.name === 'type') input.checked = input.value === selectedType;
            if (input.name === 'department') input.checked = input.value === selectedDepartment;
            if (input.name === 'gad') input.checked = selectedPriority.includes(input.value);
        });
    }

    function resetDentalFilters({
        closePanel = false
    } = {}) {
        selectedGender = null;
        selectedPriority = [];
        selectedType = null;
        selectedDepartment = null;
        nameSort = null;
        dateSort = null;
        document.querySelectorAll('#filterModal input').forEach(input => {
            input.checked = false;
        });

        syncDentalFilterInputs();
        applyFilters();
        updateFilterButtonState();
        updateShowResultsButton();
        renderDentalFilterChips();

        if (closePanel) {
            closeFilterDrawer('filterModal');
        }
    }

    function openDentalFilterModal() {
        renderDentalFilterChips();
        updateShowResultsButton();
        openFilterDrawer('filterModal');
    }

    function updateFilterButtonState() {
        const activeCount = [
            selectedGender,
            selectedType,
            selectedDepartment,
            nameSort,
            dateSort,
            ...selectedPriority
        ].filter(Boolean).length;

        window.setGlobalFilterButtonState({
            buttonId: 'openFilter',
            badgeId: 'filterBadge',
            resetId: 'externalClearFilterBtn',
            count: activeCount
        });
    }

    document.getElementById('externalClearFilterBtn')?.addEventListener('click', () => {
        document.getElementById('clearFilterBtn').click();
    });

    document.addEventListener('DOMContentLoaded', function () {

        document.getElementById('cancelFilterBtn')?.addEventListener('click', () => {
            closeFilterDrawer('filterModal');
        });

        document.getElementById('closeFilterModalBtn')?.addEventListener('click', () => {
            closeFilterDrawer('filterModal');
        });

        document.getElementById('clearAllChipsBtn')?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            resetDentalFilters({
                closePanel: false
            });
        });

        const clearFilterBtn = document.getElementById('clearFilterBtn');
        const clearBtn = document.getElementById('clearBtn');
        const searchInput = document.getElementById('searchInput');
        const monthPicker = document.getElementById('monthPicker');
        window.initMonthOnlyFlatpickr?.(document);
        const downloadReportBtn = document.getElementById('downloadReportBtn');
        const reportNameInput = document.getElementById('reportName');
        const reportNameCounter = document.getElementById('reportNameCounter');

        reportNameInput?.addEventListener('input', () => {
            const count = reportNameInput.value.length;
            if (reportNameCounter) {
                reportNameCounter.textContent = `${count} / 100`;
                reportNameCounter.classList.toggle('text-red-500', count >= 100);
                reportNameCounter.classList.toggle('text-gray-400', count < 100);
            }
        });

        document.getElementById('applyFiltersBtn').addEventListener('click', () => {
            applyFilters();
            closeFilterDrawer('filterModal');
        });

        clearFilterBtn.addEventListener('click', () => {
            resetDentalFilters({
                closePanel: false
            });
        });

        searchInput.addEventListener('input', (e) => {
            searchKeyword = e.target.value.trim().toLowerCase();
            applyFilters();
        });

        document.querySelectorAll("input[name='sort']").forEach(radio => {
            radio.addEventListener('change', () => {
                nameSort = radio.value;
                applyFilters();
                updateFilterButtonState();
                updateShowResultsButton();
                renderDentalFilterChips();
            });
        });

        document.querySelectorAll("input[name='dateOrder']").forEach(radio => {
            radio.addEventListener('change', () => {
                dateSort = radio.value;
                applyFilters();
                updateFilterButtonState();
                updateShowResultsButton();
                renderDentalFilterChips();
            });
        });

        document.querySelectorAll("input[name='gender']").forEach(radio => {
            radio.addEventListener('change', () => {
                selectedGender = radio.value;
                applyFilters();
                updateFilterButtonState();
                updateShowResultsButton();
                renderDentalFilterChips();
            });
        });

        document.querySelectorAll('.gadPriority').forEach(cb => {
            cb.addEventListener('change', () => {
                selectedPriority = [...document.querySelectorAll('.gadPriority:checked')]
                    .map(i => i.value);
                applyFilters();
                updateFilterButtonState();
                updateShowResultsButton();
                renderDentalFilterChips();
            });
        });

        document.querySelectorAll("input[name='type']").forEach(radio => {
            radio.addEventListener('change', () => {
                selectedType = radio.value;
                applyFilters();
                updateFilterButtonState();
                updateShowResultsButton();
                renderDentalFilterChips();
            });
        });

        document.querySelectorAll(".departmentRadio").forEach(radio => {
            radio.addEventListener('change', () => {
                selectedDepartment = radio.value;
                applyFilters();
                updateFilterButtonState();
                updateShowResultsButton();
                renderDentalFilterChips();
            });
        });

        monthPicker.addEventListener('change', (e) => {
            selectedMonth = e.target.value || '';

            const url = new URL(window.location.href);

            if (selectedMonth) {
                url.searchParams.set('month', selectedMonth);
            } else {
                url.searchParams.delete('month');
            }

            history.replaceState(null, '', url.toString());

            applyFilters(true);
            updateShowResultsButton();
            renderDentalFilterChips();
        });

        downloadReportBtn?.addEventListener('click', downloadDentalServicesReport);

        const currentMonthValue = initialSelectedMonth || new Date().toISOString().slice(0, 7);

        selectedMonth = currentMonthValue;

        requestAnimationFrame(() => {
            window.setMonthOnlyPickerValue?.(monthPicker, currentMonthValue, false);
        });

        document.getElementById('servicePerPageSelect')?.addEventListener('change', (event) => {
            servicePerPage = Number(event.target.value) || 10;
            applyFilters(true);
        });

        applyFilters(true);
        updateFilterButtonState();
        updateShowResultsButton();
    });
</script>
@endsection