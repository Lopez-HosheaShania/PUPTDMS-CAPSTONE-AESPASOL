@extends('layouts.app')

@section('layout-role', 'dentist')

@section('title', 'Dental Services Records')

@section('styles')
    @vite('resources/css/pages/dentist/dental-services.css')
@endsection

@php
    $dentalServiceTemplate = ($dentalServiceTemplates ?? collect())->first();
@endphp

@section('content')

    @php
        $frontendRecords = collect($records ?? [])->values();
        $selectedMonth = $selectedMonth ?? now()->format('Y-m');
    @endphp

    <main id="mainContent" class="app-page-shell dentist-records-page dental-services-page page-enter">
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
                    <button type="button" class="ui-btn ui-btn-primary" onclick="openDentalCreateReportModal()">
                        <i class="fa-solid fa-plus"></i>
                        Create Report
                    </button>
                </div>
            </section>

            <section id="statCards" class="stat-grid" aria-label="Dental service summary">
                <article class="stat-card s-all">
                    <div class="stat-card-info">
                        <div class="stat-num" id="statTotal">0</div>
                        <div class="stat-label">Total Records</div>
                    </div>

                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                </article>


                <article class="stat-card s-amber">
                    <div class="stat-card-info">
                        <div class="stat-num" id="statEmergency">
                            0
                        </div>

                        <div class="stat-label">
                            Emergency
                        </div>
                    </div>

                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </article>


                <article class="stat-card s-green">
                    <div class="stat-card-info">
                        <div class="stat-num" id="statNonEmergency">
                            0
                        </div>

                        <div class="stat-label">
                            Non-Emergency
                        </div>
                    </div>

                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                </article>


                <article class="stat-card s-blue">
                    <div class="stat-card-info">
                        <div class="stat-num" id="statFemale">
                            0
                        </div>

                        <div class="stat-label">
                            Female Patients
                        </div>
                    </div>

                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-venus"></i>
                    </div>
                </article>
            </section>

            <section class="card">
                <div class="card-header">
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
                            <input type="text" id="monthPicker" class="form-input-custom" data-month-only-picker
                                data-month-max-today placeholder="Select month" readonly>
                        </div>

                        <div class="voice-search-row service-search-row">
                            <x-search-bar id="searchInput" placeholder="Search name, program, contact…"
                                callback="handleDentalServicesSearch" :debounce="350" clear-label="Clear search" />

                            <x-voice-input target="#searchInput" status-id="dentalServicesVoiceStatus"
                                label="Use voice search" title="Voice search" />
                        </div>

                        <button id="openFilter" type="button" class="global-filter-btn" aria-pressed="false"
                            onclick="openDentalFilterModal()">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Filter</span>
                            <span id="filterBadge" class="filter-badge"></span>
                        </button>

                        <button id="externalClearFilterBtn" type="button" class="global-filter-reset-btn hidden">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>

                <x-pagination-bar id="dentalServicesPagebarTop" info-id="dentalServicesPageInfoTop"
                    pagination-id="dentalServicesPaginationTop" position="top" :show-entries="true"
                    page-size-id="servicePerPageSelect" page-size-callback="selectDentalServicesPerPage" :page-size-value="10"
                    label="entries" hidden />

                <div id="dentalServicesListView" class="table-size">
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
                                <th>Non-Emergency</th>
                                <th>Sig.</th>
                            </tr>
                        </thead>

                        <tbody id="dentalServicesTableBody"></tbody>
                    </table>
                </div>

                <div id="dentalServicesGridView" class="service-record-grid" hidden></div>

                <div id="dentalServicesEmptyState" class="empty-state-host"></div>

                <x-pagination-bar id="dentalServicesPagebarBottom" info-id="dentalServicesPageInfoBottom"
                    pagination-id="dentalServicesPaginationBottom" position="bottom" label="entries" hidden />
            </section>
        </div>
    </main>

    <x-filter-drawer id="filterModal" title="Filter Records" close-id="closeFilterModalBtn"
        close-callback="closeDentalFilterModal()" clear-id="clearFilterBtn" clear-label="Clear Filters"
        cancel-id="cancelFilterBtn" cancel-callback="closeDentalFilterModal()" cancel-label="Cancel"
        apply-id="applyFiltersBtn" apply-callback="applyDentalFiltersFromDrawer()" apply-label="Show Results"
        results-id="showResultsText">

        <div id="activeFiltersSection" class="filter-active-section hidden">
            <div class="filter-active-header">
                <span class="filter-active-title">
                    Active Filters
                </span>

                <button id="clearAllChipsBtn" type="button" class="filter-clear-all ui-btn ui-btn-secondary ui-btn-sm">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Clear All</span>
                </button>
            </div>

            <div id="activeChipsContainer" class="active-filters-container"></div>
        </div>


        <x-filter-group title="Sort by Name">
            <div class="filter-chip-row">

                <label class="choice-chip">
                    <input type="radio" name="sort" value="az" class="filter-input radio-red chip-radio">
                    <span>A → Z</span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="sort" value="za" class="filter-input radio-red chip-radio">
                    <span>Z → A</span>
                </label>

            </div>
        </x-filter-group>


        <x-filter-group title="Date Order">
            <div class="filter-chip-row">

                <label class="choice-chip">
                    <input type="radio" name="dateOrder" value="asc" class="filter-input radio-red chip-radio">
                    <span>Ascending</span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="dateOrder" value="desc" class="filter-input radio-red chip-radio">
                    <span>Descending</span>
                </label>

            </div>
        </x-filter-group>


        <x-filter-group title="Gender">
            <div class="filter-chip-row">

                <label class="choice-chip">
                    <input type="radio" name="gender" value="Male" class="filter-input radio-red chip-radio">
                    <span>Male</span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="gender" value="Female" class="filter-input radio-red chip-radio">
                    <span>Female</span>
                </label>

            </div>
        </x-filter-group>


        <x-filter-group title="Priority">
            <div class="filter-chip-row">

                <label class="choice-chip">
                    <input type="checkbox" name="gad" value="PWD"
                        class="filter-input checkbox-red choice-input gadPriority">
                    <span>PWD</span>
                </label>

                <label class="choice-chip">
                    <input type="checkbox" name="gad" value="Senior"
                        class="filter-input checkbox-red choice-input gadPriority">
                    <span>Senior</span>
                </label>

            </div>
        </x-filter-group>


        <x-filter-group title="Type">
            <div class="filter-chip-row">

                <label class="choice-chip">
                    <input type="radio" name="type" value="Emergency" class="filter-input radio-red chip-radio">
                    <span>Emergency</span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="type" value="Non-Emergency"
                        class="filter-input radio-red chip-radio">
                    <span>Non-Emergency</span>
                </label>

            </div>
        </x-filter-group>


        <x-filter-group title="Department" class="filter-group-last">
            <div class="filter-chip-row">

                <label class="choice-chip">
                    <input type="radio" name="department" value="Student"
                        class="filter-input radio-red chip-radio departmentRadio">
                    <span>Student</span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="department" value="Faculty"
                        class="filter-input radio-red chip-radio departmentRadio">
                    <span>Faculty</span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="department" value="Administrative"
                        class="filter-input radio-red chip-radio departmentRadio">
                    <span>Administrative</span>
                </label>

            </div>
        </x-filter-group>

    </x-filter-drawer>

    <div id="createReportModal" class="ui-modal" aria-hidden="true">
        <div class="ui-modal-card modal-lg" role="dialog" aria-modal="true" aria-labelledby="createReportTitle">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 id="createReportTitle" class="modal-title">
                            Create Dental Services Report
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
                data-form-validation-rule="dentalServicesReport" data-discard-form
                data-discard-title="Discard dental services report?"
                data-discard-subtitle="You have unsaved report details."
                data-discard-message="Closing this modal will remove the report name, selected report type, date range, and quantity you entered. Do you want to discard these changes?"
                novalidate>
                <div class="modal-bd">
                    <div class="modal-form-section">
                        <div class="modal-section-heading">
                            <div class="modal-section-icon">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>

                            <div>
                                <h4>Report Details</h4>
                                <p>Choose the date range and quantity for the report.</p>
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
                                            placeholder="e.g. Dental Services Report — Dec 2026"
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

                            <div class="modal-field">
                                <label class="global-form-label">
                                    Report Type
                                </label>

                                <input id="reportType" name="document_template_id" type="hidden"
                                    value="{{ $dentalServiceTemplate?->id ?? '' }}">

                                <div class="global-readonly-field">
                                    <i class="fa-solid fa-file-lines"></i>

                                    <span>
                                        {{ $dentalServiceTemplate?->name ?? 'Dental Services Record' }}
                                    </span>
                                </div>
                            </div>

                            <div class="modal-form-grid-2">
                                <div class="modal-field" data-global-field>
                                    <label for="dateFrom" class="global-form-label">
                                        From
                                        <span class="required-mark">*</span>
                                    </label>

                                    <input id="dateFrom" name="date_from" type="text"
                                        class="form-input-custom js-flatpickr-date-max-today"
                                        placeholder="Select start date" data-field-label="From Date"
                                        data-required-message="Please select a start date."
                                        data-validation-rule="notFutureDate" readonly required>

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

                                    <input id="dateTo" name="date_to" type="text"
                                        class="form-input-custom js-flatpickr-date-max-today"
                                        placeholder="Select end date" data-field-label="To Date"
                                        data-validation-rule="notFutureDate" readonly>

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

                            <div>
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

                                                <button type="button" class="global-number-stepper-btn"
                                                    data-number-step="1" aria-label="Increase quantity">
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
                            </div>

                            <div id="formErrorBanner" class="modal-error-banner hidden">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Please complete all required fields before downloading.</span>
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

    <div id="downloadCompleteModal" class="ui-modal modal-theme-success" aria-hidden="true">
        <div class="ui-modal-card modal-sm" role="dialog" aria-modal="true" aria-labelledby="downloadCompleteTitle">
            <div class="modal-hd">

                <div class="modal-heading">

                    <span class="modal-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>

                    <div class="modal-copy">
                        <h2 id="downloadCompleteTitle" class="modal-title">
                            Download Complete
                        </h2>

                        <p class="modal-subtitle">
                            The dental services report was generated successfully.
                        </p>
                    </div>

                </div>

                <button type="button" class="modal-x" onclick="closeDownloadModal()"
                    aria-label="Close download complete modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>


            <div class="modal-bd">

                <div class="global-confirm-alert">

                    <i class="fa-solid fa-file-circle-check"></i>

                    <div>
                        <p>
                            Your report is ready.
                        </p>

                        <span>
                            The generated file has been downloaded to your device.
                        </span>
                    </div>

                </div>

            </div>


            <div class="modal-ft">

                <button type="button" class="ui-btn ui-btn-success" onclick="closeDownloadModal()">
                    <i class="fa-solid fa-check"></i>
                    <span>Done</span>
                </button>

            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        const DENTAL_DOWNLOAD_URL = "{{ route('dentist.dentist.report.dental-services-download') }}";
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            "{{ csrf_token() }}";
        let records = @json($frontendRecords);

        const DENTAL_SERVICES_DATA_URL =
            "{{ route('dentist.dentist.report.dental-services.data') }}";

        let dentalServicesRequestController = null;
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

        function resetDentalReportForm() {
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
                const input =
                    document.getElementById(
                        id
                    );

                input?._flatpickr?.clear(
                    false
                );
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

            const banner =
                document.getElementById(
                    'formErrorBanner'
                );

            banner?.classList.add(
                'hidden'
            );

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

            window.DiscardChanges?.markNotSubmitting(
                form
            );

            window.DiscardChanges?.captureForm(
                form
            );
        }

        function forceCloseDentalCreateReportModal() {
            window.closeModal?.(
                'createReportModal', {
                    force: true
                }
            );

            window.setTimeout(
                () => {
                    resetDentalReportForm();
                },
                180
            );
        }

        function closeCreateModal() {
            const modal =
                document.getElementById(
                    'createReportModal'
                );

            if (!modal) {
                return;
            }

            if (window.DiscardChanges) {
                window.DiscardChanges.confirmClose(
                    modal,
                    forceCloseDentalCreateReportModal
                );

                return;
            }

            forceCloseDentalCreateReportModal();
        }

        function closeDownloadModal() {
            window.closeModal?.(
                'downloadCompleteModal'
            );
        }

        function openDentalCreateReportModal() {
            const modal =
                document.getElementById(
                    'createReportModal'
                );

            if (!modal) {
                return;
            }

            resetDentalReportForm();

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

            document.dispatchEvent(
                new CustomEvent(
                    'voice:refresh', {
                        detail: {
                            root: modal
                        }
                    }
                )
            );
        }

        window.openDentalCreateReportModal = openDentalCreateReportModal;
        window.closeCreateModal = closeCreateModal;
        window.closeDownloadModal = closeDownloadModal;

        function registerDentalServicesReportValidation() {
            if (
                typeof window
                .registerGlobalFormValidationRule !==
                'function'
            ) {
                return;
            }


            window.registerGlobalFormValidationRule(
                'dentalServicesReport',
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
                        toField.value <
                        fromField.value
                    ) {
                        window
                            .showFormInputValidationMessage?.(
                                toField,
                                'End date must be the same as or later than the start date.'
                            );

                        valid = false;
                        firstInvalid =
                            toField;
                    }


                    if (
                        quantityField?.value !==
                        ''
                    ) {
                        const quantity =
                            Number(
                                quantityField.value
                            );

                        if (
                            !Number.isInteger(
                                quantity
                            ) ||
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
            registerDentalServicesReportValidation
        );

        document.addEventListener(
            'DOMContentLoaded',
            registerDentalServicesReportValidation
        );

        async function downloadDentalServicesReport() {
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
                ${escapeDentalText(message)}
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

                validationResult
                    ?.firstInvalid
                    ?.focus?.();

                return;
            }


            const reportName =
                document.getElementById(
                    'reportName'
                )?.value
                ?.trim() || '';

            const reportType =
                document.getElementById(
                    'reportType'
                )?.value || '';

            const dateFrom =
                document.getElementById(
                    'dateFrom'
                )?.value || '';

            const dateTo =
                document.getElementById(
                    'dateTo'
                )?.value || '';

            const quantity =
                Number(
                    document.getElementById(
                        'reportQty'
                    )?.value || 0
                );


            if (
                !reportName ||
                !reportType ||
                !dateFrom ||
                !Number.isInteger(quantity) ||
                quantity < 1 ||
                quantity > 100
            ) {
                showBanner();

                return;
            }


            const originalBtnHtml =
                btn.innerHTML;


            btn.disabled = true;

            btn.setAttribute(
                'aria-busy',
                'true'
            );

            btn.innerHTML = `
        <i
            class="
                fa-solid
                fa-spinner
                fa-spin
            "
        ></i>

        <span>
            Generating...
        </span>
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
                    reportName
                );

                formData.append(
                    'document_template_id',
                    reportType
                );

                formData.append(
                    'date_from',
                    dateFrom
                );

                formData.append(
                    'quantity',
                    String(quantity)
                );


                if (dateTo) {
                    formData.append(
                        'date_to',
                        dateTo
                    );
                }


                const response =
                    await fetch(
                        DENTAL_DOWNLOAD_URL, {
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
                            errorData.errors &&
                            typeof errorData.errors ===
                            'object'
                        ) {
                            const firstError =
                                Object
                                .values(
                                    errorData.errors
                                )
                                .flat()
                                .find(Boolean);

                            if (firstError) {
                                message =
                                    String(
                                        firstError
                                    );
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
                    .createObjectURL(
                        blob
                    );


                let fileName =
                    `${reportName
                    .replace(
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


                if (fileNameMatch?.[1]) {
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

                document.body
                    .appendChild(
                        link
                    );

                link.click();
                link.remove();


                window.URL
                    .revokeObjectURL(
                        downloadUrl
                    );


                window.DiscardChanges?.markSubmitting(
                    form
                );

                forceCloseDentalCreateReportModal();

                window.openModal?.(
                    'downloadCompleteModal'
                );

            } catch (error) {
                showBanner(
                    error.message ||
                    'Unable to generate the report. Please try again.'
                );
            } finally {
                btn.disabled =
                    false;

                btn.removeAttribute(
                    'aria-busy'
                );

                btn.innerHTML =
                    originalBtnHtml;
            }
        }

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

        function renderDentalServicesEmptyState() {
            const host =
                document.getElementById(
                    'dentalServicesEmptyState'
                );

            if (!host) {
                return;
            }

            const hasSearch =
                Boolean(
                    searchKeyword.trim()
                );

            const hasFilters =
                Boolean(
                    selectedGender ||
                    selectedType ||
                    selectedDepartment ||
                    nameSort ||
                    dateSort ||
                    selectedPriority.length
                );


            if (hasSearch) {
                window.EmptyState?.renderSearch({
                    host,

                    input: '#searchInput',

                    query: searchKeyword,

                    title: `No results for “${searchKeyword}”`,

                    message: 'Try another patient name, program, contact, or email.',
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
                    onclick="resetDentalFilters()"
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

                title: `No record found for "${getSelectedMonthLabel()}"`,

                message: 'There are no dental service records for this month.',
            });
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
            if (!pagination) {
                return;
            }

            window.renderGlobalPagination?.({
                currentPage: Number(
                    pagination.current_page
                ) || 1,

                lastPage: Number(
                    pagination.last_page
                ) || 1,

                total: Number(
                    pagination.total
                ) || 0,

                from: pagination.from ?? null,

                to: pagination.to ?? null,

                containers: [
                    document.getElementById(
                        'dentalServicesPaginationTop'
                    ),
                    document.getElementById(
                        'dentalServicesPaginationBottom'
                    ),
                ],

                bars: [
                    document.getElementById(
                        'dentalServicesPagebarTop'
                    ),
                    document.getElementById(
                        'dentalServicesPagebarBottom'
                    ),
                ],

                infoElements: [
                    document.getElementById(
                        'dentalServicesPageInfoTop'
                    ),
                    document.getElementById(
                        'dentalServicesPageInfoBottom'
                    ),
                ],

                itemLabel: 'entries',

                onPageChange(page) {
                    serviceCurrentPage =
                        Number(page) || 1;

                    applyFilters();
                },
            });


            const perPageSelect =
                document.getElementById(
                    'servicePerPageSelect'
                );

            if (perPageSelect) {
                perPageSelect.value =
                    String(
                        pagination.per_page
                    );

                window
                    .syncGlobalPageSizeSelect?.(
                        perPageSelect,
                        pagination.per_page
                    );
            }
        }

        function selectDentalServicesPerPage(value) {
            servicePerPage = Number(value) || 10;
            serviceCurrentPage = 1;

            applyFilters();
        }

        window.selectDentalServicesPerPage = selectDentalServicesPerPage;

        function renderRecords(data) {
            const tbody = document.getElementById('dentalServicesTableBody');
            const listView = document.getElementById('dentalServicesListView');
            const grid = document.getElementById('dentalServicesGridView');
            const emptyState = document.getElementById('dentalServicesEmptyState');

            tbody.innerHTML = '';
            if (grid) grid.innerHTML = '';

            const pagination = getDentalPagination(data);
            renderDentalPagebar(pagination);

            if (!data.length) {

                if (listView) {
                    listView.hidden = true;
                    listView.setAttribute('aria-hidden', 'true');
                }

                if (grid) {
                    grid.hidden = true;
                    grid.setAttribute('aria-hidden', 'true');
                }

                renderDentalServicesEmptyState();
                return;
            }

            window.EmptyState?.hide(emptyState);

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
                const signatureUrl = typeof r.signature_url === 'string' ? r.signature_url.trim() : '';

                const emergencyMark = r.type === 'Emergency' ?
                    `<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>` :
                    '';

                const nonEmergencyMark = r.type === 'Non-Emergency' ?
                    `<span class="check-mark"><i class="fa-solid fa-check"></i></span>` :
                    '';

                const signatureCell = signatureUrl ?
                    `
                    <a href="${encodeURI(signatureUrl)}" target="_blank" rel="noopener noreferrer"
                        class="service-signature-link" aria-label="View patient signature">
                        <img src="${encodeURI(signatureUrl)}" alt="Patient signature"
                            class="service-signature-image">
                    </a>
                ` :
                    '<span class="service-signature-empty">No signature</span>';

                tbody.innerHTML += `
            <tr>
                <td class="muted-cell whitespace-nowrap">${safeDate}</td>
                <td class="whitespace-nowrap text-[11px]">${safeTimeIn}</td>
                <td><div class="name-cell" data-patient-name>${safeName}</div></td>
                <td><div class="program-cell">${safeProgram}</div></td>
                <td>${escapeDentalText(r.age)}</td>
                <td>${r.gad?.gender === 'Male' ? '<span class="check-mark"><i class="fa-solid fa-check"></i></span>' : ''}</td>
                <td>${r.gad?.gender === 'Female' ? '<span class="check-mark"><i class="fa-solid fa-check"></i></span>' : ''}</td>
                <td>${(r.gad?.priority || []).includes('Senior') ? '<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>' : ''}</td>
                <td>${(r.gad?.priority || []).includes('PWD') ? '<span class="check-mark check-gold"><i class="fa-solid fa-check"></i></span>' : ''}</td>
                <td class="muted-cell"><div class="email-cell">${safeEmail}</div></td>
                <td class="text-[11px]"><div class="contact-cell">${safeContact}</div></td>
                <td class="whitespace-nowrap text-[11px]">${safeTimeOut}</td>
                <td class="text-[11px]">${safeDuration}</td>
                <td>${emergencyMark}</td>
                <td>${nonEmergencyMark}</td>
                <td>${signatureCell}</td>
            </tr>
        `;

                if (grid) {
                    grid.innerHTML += `
                <article class="service-record-card">
                    <div class="service-record-card-head">
                        <div>
                            <h3 data-patient-name>${safeName}</h3>
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

        async function fetchDentalServicesMonth(month) {
            if (!month) {
                records = [];
                applyFilters(true);
                return;
            }

            if (dentalServicesRequestController) {
                dentalServicesRequestController.abort();
            }

            dentalServicesRequestController =
                new AbortController();

            const monthPicker =
                document.getElementById('monthPicker');

            try {
                monthPicker?.setAttribute(
                    'aria-busy',
                    'true'
                );

                const url =
                    new URL(
                        DENTAL_SERVICES_DATA_URL,
                        window.location.origin
                    );

                url.searchParams.set(
                    'month',
                    month
                );

                const response =
                    await fetch(
                        url.toString(), {
                            headers: {
                                'Accept': 'application/json',

                                'X-Requested-With': 'XMLHttpRequest',
                            },

                            credentials: 'same-origin',

                            signal: dentalServicesRequestController.signal,
                        }
                    );

                if (!response.ok) {
                    throw new Error(
                        'Unable to load dental service records.'
                    );
                }

                const payload =
                    await response.json();

                records =
                    Array.isArray(payload.records) ?
                    payload.records : [];

                selectedMonth =
                    payload.selectedMonth ||
                    month;

                serviceCurrentPage = 1;

                applyFilters(true);
                updateShowResultsButton();
                updateFilterButtonState();
                renderDentalFilterChips();

            } catch (error) {
                if (
                    error.name ===
                    'AbortError'
                ) {
                    return;
                }

                console.error(
                    'Dental services month load failed:',
                    error
                );

                window.showToast?.({
                    type: 'error',
                    title: 'Unable to load records',
                    message: 'Dental service records for the selected month could not be loaded.',
                });
            } finally {
                monthPicker?.removeAttribute(
                    'aria-busy'
                );
            }
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

        function handleDentalServicesSearch(value) {
            searchKeyword = String(value || '')
                .trim()
                .toLowerCase();

            serviceCurrentPage = 1;

            applyFilters();
        }

        window.handleDentalServicesSearch = handleDentalServicesSearch;

        function updateShowResultsButton() {
            const count =
                getFilteredDentalRecords().length;

            window.updateShowResultsText?.(
                count,
                'showResultsText'
            );
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
                closeDentalFilterModal();
            }
        }

        window.resetDentalFilters = resetDentalFilters;

        function openDentalFilterModal() {
            renderDentalFilterChips();
            updateShowResultsButton();

            window.openFilterDrawer?.(
                'filterModal'
            );
        }

        function closeDentalFilterModal() {
            window.closeFilterDrawer?.(
                'filterModal'
            );
        }

        window.openDentalFilterModal =
            openDentalFilterModal;

        window.closeDentalFilterModal =
            closeDentalFilterModal;

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

        document
            .getElementById(
                'externalClearFilterBtn'
            )
            ?.addEventListener(
                'click',
                () => {
                    resetDentalFilters({
                        closePanel: false
                    });
                }
            );

        document.addEventListener('DOMContentLoaded', async function() {

            document.getElementById('clearAllChipsBtn')?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                resetDentalFilters({
                    closePanel: false
                });
            });

            const clearFilterBtn = document.getElementById('clearFilterBtn');
            const monthPicker = document.getElementById('monthPicker');
            const datePickerModule = await window.loadDatePickerModule?.();

            await datePickerModule
                ?.initGlobalDatePickers(
                    document
                );

            const currentMonthValue =
                initialSelectedMonth ||
                new Date()
                .toISOString()
                .slice(0, 7);

            selectedMonth =
                currentMonthValue;

            datePickerModule
                ?.setMonthOnlyPickerValue?.(
                    monthPicker,
                    currentMonthValue,
                    false
                );

            const downloadReportBtn = document.getElementById('downloadReportBtn');

            window.applyDentalFiltersFromDrawer =
                function() {
                    applyFilters();

                    closeDentalFilterModal();
                };

            clearFilterBtn?.addEventListener(
                'click',
                () => {
                    resetDentalFilters({
                        closePanel: false
                    });
                }
            );

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

            monthPicker?.addEventListener(
                'change',
                async event => {
                    const nextMonth =
                        event.target.value || '';

                    if (
                        !nextMonth ||
                        nextMonth === selectedMonth
                    ) {
                        return;
                    }

                    selectedMonth =
                        nextMonth;

                    const url =
                        new URL(
                            window.location.href
                        );

                    url.searchParams.set(
                        'month',
                        selectedMonth
                    );

                    history.replaceState(
                        null,
                        '',
                        url.toString()
                    );

                    await fetchDentalServicesMonth(
                        selectedMonth
                    );
                }
            );

            downloadReportBtn?.addEventListener('click', downloadDentalServicesReport);

            applyFilters(true);
            updateFilterButtonState();
            updateShowResultsButton();
        });
    </script>
@endsection
