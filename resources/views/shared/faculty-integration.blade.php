@extends('layouts.app')

@php
    $layoutRole = $layoutRole ?? (request()->routeIs('dentist.*') ? 'dentist' : 'admin');

    $isDentistView = $layoutRole === 'dentist';

    $routePrefix = $isDentistView ? 'dentist' : 'admin';
@endphp

@section('layout-role', $layoutRole)

@section('title', 'Faculty Integration')

@section('content')

    <main id="mainContent" class="app-page-shell page-enter">
        <div class="w-full">

            @if ($isDentistView)
                <div class="dentist-hero mb-6">
                    <div class="dentist-hero-content">
                        <div class="dentist-hero-icon">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="dentist-hero-eyebrow">
                                <i class="fa-solid fa-tooth"></i>
                                Clinical Operations
                            </div>

                            <h1 class="dentist-hero-title">
                                Faculty Integration
                            </h1>
                        </div>
                    </div>
                </div>
            @else
                <div class="page-banner mb-6">
                    <div class="page-banner-inner">
                        <div class="min-w-0">
                            <h1 class="page-title">
                                Faculty Integration
                            </h1>
                        </div>
                    </div>
                </div>
            @endif

            <form id="facultyIntegrationForm" method="POST" action="{{ route($routePrefix . '.faculty.store') }}"
                novalidate class="mt-4">
                @csrf

                <input type="hidden" id="faculty_json" name="faculty_json" value="{{ old('faculty_json') }}">

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">

                    <div class="grid min-w-0 gap-4 xl:col-span-3">

                        <section id="facultySearchCard" class="card">

                            <div class="card-header">
                                <div class="card-header-left">

                                    <div class="card-header-icon">
                                        <span>1</span>
                                    </div>

                                    <div class="min-w-0">
                                        <h2 class="card-title">
                                            Search faculty
                                        </h2>

                                        <p class="card-subtitle">
                                            Pulled from the FLSS faculty directory
                                        </p>
                                    </div>

                                </div>
                            </div>

                            <div class="card-body">

                                <div class="mb-4">

                                    <div class="flex items-center justify-between">
                                        <span class="ui-muted-text">Progress</span>

                                        <span id="progressFacultyPercent" class="ui-muted-text">
                                            0%
                                        </span>
                                    </div>

                                    <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                        aria-valuenow="0">

                                        <div id="progressFacultyBar" class="progress-fill" style="width: 0%;">
                                        </div>

                                    </div>

                                </div>

                                <div class="field-group">
                                    <label for="faculty_search" class="field-label">
                                        Select Faculty
                                        <span class="required-mark">*</span>
                                    </label>

                                    <div class="flex w-full min-w-0 items-start gap-2">

                                        <div class="search-combo min-w-0 flex-1">

                                            <div class="search-wrap global-search" data-search-wrapper>
                                                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                                                <input type="text" id="faculty_search" class="search-input"
                                                    placeholder="Search by name, email, or faculty code" autocomplete="off"
                                                    data-search-input>

                                                <button type="button" id="facultySearchClearBtn" class="search-clear"
                                                    data-search-clear aria-label="Clear faculty search"
                                                    title="Clear faculty search">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>

                                            <div id="facultyResults" class="search-results hidden"></div>

                                        </div>

                                        <div class="voice-input-toggle">
                                            <button type="button" id="facultyMicBtn" class="voice-search-mic external"
                                                data-voice-trigger data-voice-target="#faculty_search"
                                                data-voice-status="#facultyVoiceStatus" aria-label="Toggle voice input"
                                                aria-pressed="false">
                                                <i class="fa-solid fa-microphone"></i>
                                            </button>

                                            <span id="facultyVoiceStatus" class="voice-status hidden"
                                                aria-live="polite"></span>
                                        </div>

                                    </div>

                                    <div id="facultySearchError"
                                        class="global-field-error @error('faculty_json') show @enderror">
                                        @error('faculty_json')
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <span>{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="card">

                            <div class="card-header">
                                <div class="card-header-left">

                                    <div class="card-header-icon">
                                        <span>2</span>
                                    </div>

                                    <div class="min-w-0">
                                        <h2 class="card-title">
                                            Synced information
                                        </h2>

                                        <p class="card-subtitle">
                                            Read-only information pulled automatically after selection
                                        </p>
                                    </div>

                                </div>
                            </div>

                            <div class="card-body">

                                <div class="mb-4">

                                    <div class="flex items-center justify-between">
                                        <span class="ui-muted-text">Progress</span>

                                        <span id="progressInformationPercent" class="ui-muted-text">
                                            0%
                                        </span>
                                    </div>

                                    <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                        aria-valuenow="0">

                                        <div id="progressInformationBar" class="progress-fill" style="width: 0%;">
                                        </div>

                                    </div>

                                </div>

                                <div id="facultyEmptyState" class="global-info-item">
                                    <div class="global-info-icon">
                                        <i class="fa-solid fa-user-graduate"></i>
                                    </div>

                                    <div class="global-info-copy">
                                        <span class="global-info-label">
                                            No faculty selected
                                        </span>

                                        <span class="global-info-value">
                                            Search and select a faculty record above to display the synced information.
                                        </span>
                                    </div>
                                </div>

                                <div id="facultySyncedInformation" hidden>

                                    <div class="flex flex-col gap-5">

                                        <div class="flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-center"
                                            style="border-color: var(--border);">

                                            <div class="flex min-w-0 items-center gap-3">

                                                <div id="summary_avatar" class="patient-avatar patient-avatar-lg">
                                                    --
                                                </div>

                                                <div class="min-w-0">

                                                    <span id="summary_name" class="global-info-profile-name">
                                                        Unassigned
                                                    </span>

                                                    <span id="summary_email" class="global-info-subvalue">
                                                        Not provided
                                                    </span>

                                                </div>

                                            </div>

                                            <div class="flex flex-wrap gap-2 sm:ml-auto sm:justify-end">

                                                <span id="summary_type" class="status-pill status-cancelled hidden">
                                                </span>

                                                <span id="summary_department" class="status-pill hidden">
                                                </span>

                                            </div>

                                        </div>

                                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

                                            <div class="min-w-0">

                                                <div class="section-card-title">
                                                    <span>Identity</span>
                                                </div>

                                                <div>

                                                    <div class="global-info-item global-info-item-inline justify-between gap-4"
                                                        style="border-color: var(--border);">

                                                        <span class="ui-muted-text">
                                                            Faculty ID
                                                        </span>

                                                        <span id="faculty_id" class="global-info-value text-right"
                                                            style="margin-top: 0;">
                                                            Not provided
                                                        </span>

                                                    </div>

                                                    <div class="global-info-item global-info-item-inline justify-between gap-4"
                                                        style="border-color: var(--border);">

                                                        <span class="ui-muted-text">
                                                            Faculty Code
                                                        </span>

                                                        <span id="faculty_code" class="global-info-value text-right"
                                                            style="margin-top: 0;">
                                                            Not provided
                                                        </span>

                                                    </div>

                                                    <div class="global-info-item global-info-item-inline justify-between gap-4"
                                                        style="border-color: var(--border);">

                                                        <span class="ui-muted-text">
                                                            Birthday
                                                        </span>

                                                        <span id="birthday" class="global-info-value text-right"
                                                            style="margin-top: 0;">
                                                            Not provided
                                                        </span>

                                                    </div>

                                                    <div
                                                        class="global-info-item global-info-item-inline justify-between gap-4 py-2">

                                                        <span class="ui-muted-text">
                                                            Gender
                                                        </span>

                                                        <span id="gender" class="global-info-value text-right"
                                                            style="margin-top: 0;">
                                                            Not provided
                                                        </span>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="min-w-0">

                                                <div class="section-card-title">
                                                    <span>Employment</span>
                                                </div>

                                                <div>

                                                    <div class="global-info-item global-info-item-inline justify-between gap-4"
                                                        style="border-color: var(--border);">

                                                        <span class="ui-muted-text">
                                                            Faculty Type
                                                        </span>

                                                        <span id="faculty_type" class="global-info-value text-right"
                                                            style="margin-top: 0;">
                                                            Not provided
                                                        </span>

                                                    </div>

                                                    <div class="global-info-item global-info-item-inline justify-between gap-4"
                                                        style="border-color: var(--border);">

                                                        <span class="ui-muted-text">
                                                            Department
                                                        </span>

                                                        <span id="department" class="global-info-value text-right"
                                                            style="margin-top: 0;">
                                                            Not provided
                                                        </span>

                                                    </div>

                                                    <div
                                                        class="global-info-item global-info-item-inline justify-between gap-4 py-2">

                                                        <span class="ui-muted-text">
                                                            Email
                                                        </span>

                                                        <span id="email" class="global-info-value min-w-0 text-right"
                                                            style="margin-top: 0;">
                                                            Not provided
                                                        </span>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="min-w-0 lg:col-span-2">

                                                <div class="section-card-title">
                                                    <span>Address</span>
                                                </div>

                                                <div
                                                    class="global-info-item global-info-item-inline items-start justify-between gap-4 py-2">

                                                    <span class="ui-muted-text">
                                                        Home Address
                                                    </span>

                                                    <span id="home_address" class="global-info-value min-w-0 text-right"
                                                        style="margin-top: 0;">
                                                        Not provided
                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>
                            </div>
                        </section>

                        <section class="card">

                            <div class="card-header">
                                <div class="card-header-left">

                                    <div class="card-header-icon">
                                        <span>3</span>
                                    </div>

                                    <div class="min-w-0">
                                        <h2 class="card-title">
                                            Access configuration
                                        </h2>

                                        <p class="card-subtitle">
                                            Assign the CMS role and account status
                                        </p>
                                    </div>

                                </div>
                            </div>

                            <div class="card-body">
                                <div class="mb-4">

                                    <div class="flex items-center justify-between">
                                        <span class="ui-muted-text">
                                            Setup progress
                                        </span>

                                        <span id="progressAccessPercent" class="ui-muted-text">
                                            0%
                                        </span>
                                    </div>

                                    <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                        aria-valuenow="0">

                                        <div id="progressAccessBar" class="progress-fill" style="width: 0%;">
                                        </div>

                                    </div>

                                </div>

                                <div class="field-group">

                                    <label class="field-label">
                                        CMS Role
                                        <span class="required-mark">*</span>
                                    </label>

                                    <div id="cmsRoleGroup" class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                                        <label class="global-choice-card">

                                            <input type="radio" name="cms_role" value="patient"
                                                class="global-choice-input" @checked(old('cms_role') === 'patient')>

                                            <span class="global-info-icon">
                                                <i class="fa-solid fa-user"></i>
                                            </span>

                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">
                                                    Patient
                                                </span>

                                                <span class="global-choice-description">
                                                    Standard patient access
                                                </span>
                                            </span>

                                        </label>


                                        <label class="global-choice-card">

                                            <input type="radio" name="cms_role" value="dentist"
                                                class="global-choice-input" @checked(old('cms_role') === 'dentist')>

                                            <span class="global-info-icon">
                                                <i class="fa-solid fa-tooth"></i>
                                            </span>

                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">
                                                    Dentist
                                                </span>

                                                <span class="global-choice-description">
                                                    Clinical CMS access
                                                </span>
                                            </span>

                                        </label>


                                        <label class="global-choice-card">

                                            <input type="radio" name="cms_role" value="admin"
                                                class="global-choice-input" @checked(old('cms_role') === 'admin')>

                                            <span class="global-info-icon">
                                                <i class="fa-solid fa-user-gear"></i>
                                            </span>

                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">
                                                    Admin
                                                </span>

                                                <span class="global-choice-description">
                                                    Administrative CMS access
                                                </span>
                                            </span>

                                        </label>

                                    </div>

                                    <div id="cmsRoleError" class="global-field-error @error('cms_role') show @enderror">
                                        @error('cms_role')
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <span>{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>

                                <div class="field-group mt-5">

                                    <label class="field-label">
                                        Account Status
                                        <span class="required-mark">*</span>
                                    </label>

                                    <div id="accountStatusGroup" class="global-choice-group">

                                        <label class="global-choice-card status-active">

                                            <input type="radio" name="account_status" value="Active"
                                                class="global-choice-input" @checked(old('account_status') === 'Active')>

                                            <span class="global-info-icon status-active">
                                                <i class="fa-solid fa-circle-check"></i>
                                            </span>

                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">
                                                    Active
                                                </span>

                                                <span class="global-choice-description">
                                                    Allow the account to access CMS
                                                </span>
                                            </span>

                                        </label>


                                        <label class="global-choice-card status-pending">

                                            <input type="radio" name="account_status" value="Inactive"
                                                class="global-choice-input" @checked(old('account_status') === 'Inactive')>

                                            <span class="global-info-icon status-pending">
                                                <i class="fa-solid fa-circle-minus"></i>
                                            </span>

                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">
                                                    Inactive
                                                </span>

                                                <span class="global-choice-description">
                                                    Keep the record while disabling access
                                                </span>
                                            </span>

                                        </label>

                                    </div>

                                    <div id="accountStatusError"
                                        class="global-field-error @error('account_status') show @enderror">
                                        @error('account_status')
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <span>{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>

                                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">

                                    <button type="button" class="btn-reset" id="resetFacultyBtn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Reset
                                    </button>

                                    <button type="submit" class="ui-btn ui-btn-primary">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Save Faculty
                                    </button>

                                </div>

                            </div>
                        </section>

                    </div>

                    <aside class="grid h-fit min-w-0 self-start content-start gap-4 xl:sticky xl:top-24 xl:col-span-1">

                        <section class="card">

                            <div class="card-header">

                                <div class="card-header-left">

                                    <div class="card-header-icon">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </div>

                                    <div>
                                        <h2 class="card-title">
                                            Quick Notes
                                        </h2>

                                        <p class="card-subtitle">
                                            Guidance for a cleaner workflow
                                        </p>
                                    </div>

                                </div>

                            </div>

                            <div class="card-body">

                                <div class="sidebar-stack">

                                    <div class="global-info-item global-info-item-inline">

                                        <div class="global-info-icon status-completed">
                                            <i class="fa-solid fa-check"></i>
                                        </div>

                                        <div class="global-info-copy">
                                            <span class="ui-muted-text">
                                                Always select a faculty record from the search results to keep information
                                                accurately synced.
                                            </span>
                                        </div>

                                    </div>


                                    <div class="global-info-item global-info-item-inline">

                                        <div class="global-info-icon status-all">
                                            <i class="fa-solid fa-user-check"></i>
                                        </div>

                                        <div class="global-info-copy">
                                            <span class="ui-muted-text">
                                                Review the faculty type, department, and email before assigning a CMS role.
                                            </span>
                                        </div>

                                    </div>


                                    <div class="global-info-item global-info-item-inline">

                                        <div class="global-info-icon status-pending">
                                            <i class="fa-solid fa-shield"></i>
                                        </div>

                                        <div class="global-info-copy">
                                            <span class="ui-muted-text">
                                                Use Inactive when the record should remain stored but CMS access must be
                                                disabled.
                                            </span>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </form>
        </div>
    </main>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const facultyForm =
                document.getElementById('facultyIntegrationForm');

            const searchInput =
                document.getElementById('faculty_search');

            const clearSearchButton =
                document.getElementById('facultySearchClearBtn');

            const resultsBox =
                document.getElementById('facultyResults');

            const facultyJson =
                document.getElementById('faculty_json');

            const emptyState =
                document.getElementById('facultyEmptyState');

            const syncedInformation =
                document.getElementById('facultySyncedInformation');

            const resetFacultyBtn =
                document.getElementById('resetFacultyBtn');

            const displayFields = {
                faculty_id: document.getElementById('faculty_id'),

                faculty_code: document.getElementById('faculty_code'),

                faculty_type: document.getElementById('faculty_type'),

                department: document.getElementById('department'),

                email: document.getElementById('email'),

                birthday: document.getElementById('birthday'),

                gender: document.getElementById('gender'),

                home_address: document.getElementById('home_address'),
            };

            const summaryAvatar =
                document.getElementById('summary_avatar');

            const summaryName =
                document.getElementById('summary_name');

            const summaryEmail =
                document.getElementById('summary_email');

            const summaryType =
                document.getElementById('summary_type');

            const summaryDepartment =
                document.getElementById('summary_department');

            const facultySearchError =
                document.getElementById('facultySearchError');

            const cmsRoleError =
                document.getElementById('cmsRoleError');

            const accountStatusError =
                document.getElementById('accountStatusError');

            const cmsRoleInputs =
                Array.from(
                    document.querySelectorAll(
                        'input[name="cms_role"]'
                    )
                );

            const accountStatusInputs =
                Array.from(
                    document.querySelectorAll(
                        'input[name="account_status"]'
                    )
                );

            const progressFacultyBar = document.getElementById('progressFacultyBar');
            const progressFacultyPercent = document.getElementById('progressFacultyPercent');
            const progressInformationBar = document.getElementById('progressInformationBar');
            const progressInformationPercent = document.getElementById('progressInformationPercent');
            const progressAccessBar = document.getElementById('progressAccessBar');
            const progressAccessPercent = document.getElementById('progressAccessPercent');

            let faculties = [];
            let facultiesLoading = true;
            let facultyLoadError = '';
            let facultyStateAnimationTimer = null;

            function valueOrFallback(value) {
                const normalized =
                    String(value ?? '').trim();

                return normalized || 'Not provided';
            }

            function formatHomeAddress(address = {}) {

                const parts = [
                        address?.house_num,
                        address?.street,
                        address?.barangay,
                        address?.city,
                        address?.province,
                        address?.country,
                        address?.zipcode,
                    ]
                    .map(value =>
                        String(value ?? '').trim()
                    )
                    .filter(Boolean);

                return parts.join(', ');
            }

            function setDisplayValue(element, value) {
                if (!element) return;

                element.textContent =
                    valueOrFallback(value);

                element.classList.toggle(
                    'ui-muted-text',
                    !String(value ?? '').trim()
                );
            }

            function getFullFacultyName(faculty) {

                return [
                        faculty?.first_name,
                        faculty?.middle_name,
                        faculty?.last_name,
                        faculty?.suffix_name,
                    ]
                    .filter(value =>
                        String(value ?? '').trim()
                    )
                    .join(' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function getFacultyInitials(faculty) {

                const first =
                    String(
                        faculty?.first_name ?? ''
                    )
                    .trim()
                    .charAt(0);

                const last =
                    String(
                        faculty?.last_name ?? ''
                    )
                    .trim()
                    .charAt(0);

                return (
                    `${first}${last}`.toUpperCase() ||
                    'FA'
                );
            }

            function setSummaryPill(element, value) {

                if (!element) return;

                const normalized =
                    String(value ?? '').trim();

                element.textContent = normalized;

                element.classList.toggle(
                    'hidden',
                    !normalized
                );
            }

            function getSelectedRadio(name) {
                return document.querySelector(
                    `input[name="${name}"]:checked`
                );
            }

            function setSearchError(message = '') {

                searchInput.classList.toggle(
                    'is-invalid',
                    Boolean(message)
                );

                facultySearchError.innerHTML = '';

                if (!message) {
                    facultySearchError.classList.remove(
                        'show'
                    );

                    return;
                }

                const icon =
                    document.createElement('i');

                icon.className =
                    'fa-solid fa-circle-exclamation';

                const text =
                    document.createElement('span');

                text.textContent = message;

                facultySearchError.append(
                    icon,
                    text
                );

                facultySearchError.classList.add(
                    'show'
                );
            }


            function setChoiceError(
                errorElement,
                message = ''
            ) {

                if (!errorElement) return;

                errorElement.innerHTML = '';

                if (!message) {

                    errorElement.classList.remove(
                        'show'
                    );

                    return;
                }

                const icon =
                    document.createElement('i');

                icon.className =
                    'fa-solid fa-circle-exclamation';

                const text =
                    document.createElement('span');

                text.textContent = message;

                errorElement.append(
                    icon,
                    text
                );

                errorElement.classList.add(
                    'show'
                );
            }

            function setProgressBar(
                element,
                percentageElement,
                percentage
            ) {

                if (!element) return;

                const normalizedPercentage =
                    Math.max(
                        0,
                        Math.min(
                            100,
                            Number(percentage) || 0
                        )
                    );

                element.style.width =
                    `${normalizedPercentage}%`;

                element.style.setProperty(
                    '--progress-fill-color',
                    normalizedPercentage === 100 ?
                    'var(--status-completed-solid)' :
                    'var(--crimson-1)'
                );

                const progressBar =
                    element.closest(
                        '[role="progressbar"]'
                    );

                progressBar?.setAttribute(
                    'aria-valuenow',
                    normalizedPercentage
                );

                if (percentageElement) {
                    percentageElement.textContent =
                        `${normalizedPercentage}%`;
                }
            }

            function updateProgress() {

                const facultySelected =
                    Boolean(
                        facultyJson.value.trim()
                    );

                const roleSelected =
                    Boolean(
                        getSelectedRadio('cms_role')
                    );

                const statusSelected =
                    Boolean(
                        getSelectedRadio('account_status')
                    );

                const facultyProgress = facultySelected ? 100 : 0;
                const informationProgress = facultySelected ? 100 : 0;

                let accessProgress = 0;

                if (roleSelected) {
                    accessProgress += 50;
                }

                if (statusSelected) {
                    accessProgress += 50;
                }

                setProgressBar(
                    progressFacultyBar,
                    progressFacultyPercent,
                    facultyProgress
                );

                setProgressBar(
                    progressInformationBar,
                    progressInformationPercent,
                    informationProgress
                );

                setProgressBar(
                    progressAccessBar,
                    progressAccessPercent,
                    accessProgress
                );
            }

            function showEmptyState() {

                window.clearTimeout(
                    facultyStateAnimationTimer
                );

                syncedInformation.hidden = true;

                syncedInformation.classList.remove(
                    'content-fade-in',
                    'content-fade-out'
                );

                emptyState.hidden = false;

                emptyState.classList.remove(
                    'content-fade-out'
                );

                emptyState.classList.add(
                    'content-fade-in'
                );

                facultyStateAnimationTimer =
                    window.setTimeout(() => {
                        emptyState.classList.remove(
                            'content-fade-in'
                        );
                    }, 220);
            }

            function showSyncedInformation() {

                window.clearTimeout(
                    facultyStateAnimationTimer
                );

                syncedInformation.hidden = false;

                syncedInformation.classList.remove(
                    'content-fade-out'
                );

                syncedInformation.classList.add(
                    'content-fade-in'
                );

                emptyState.classList.remove(
                    'content-fade-in'
                );

                emptyState.classList.add(
                    'content-fade-out'
                );

                facultyStateAnimationTimer =
                    window.setTimeout(() => {

                        emptyState.hidden = true;

                        emptyState.classList.remove(
                            'content-fade-out'
                        );

                        syncedInformation.classList.remove(
                            'content-fade-in'
                        );

                    }, 180);
            }

            function resetSyncedInformation() {

                Object.values(
                        displayFields
                    )
                    .forEach(element => {
                        setDisplayValue(
                            element,
                            ''
                        );
                    });

                summaryAvatar.textContent = '--';

                summaryName.textContent =
                    'Not provided';

                summaryEmail.textContent =
                    'Not provided';

                setSummaryPill(
                    summaryType,
                    ''
                );

                setSummaryPill(
                    summaryDepartment,
                    ''
                );

                showEmptyState();
            }

            function clearFacultySelection() {

                facultyJson.value = '';

                resetSyncedInformation();

                setSearchError('');

                updateProgress();
            }

            function fillFaculty(faculty) {

                const profile =
                    faculty?.profile ?? {};

                const address =
                    profile?.address ?? {};

                facultyJson.value =
                    JSON.stringify(faculty);

                searchInput.value =
                    getFullFacultyName(faculty);

                setDisplayValue(
                    displayFields.faculty_id,
                    faculty?.faculty_id
                );

                setDisplayValue(
                    displayFields.faculty_code,
                    faculty?.faculty_code
                );

                setDisplayValue(
                    displayFields.faculty_type,
                    faculty?.faculty_type
                );

                setDisplayValue(
                    displayFields.department,
                    faculty?.department
                );

                setDisplayValue(
                    displayFields.email,
                    faculty?.email
                );

                setDisplayValue(
                    displayFields.birthday,
                    profile?.birthday
                );

                setDisplayValue(
                    displayFields.gender,
                    profile?.gender
                );

                setDisplayValue(
                    displayFields.home_address,
                    formatHomeAddress(address)
                );

                summaryAvatar.textContent =
                    getFacultyInitials(faculty);

                summaryName.textContent =
                    getFullFacultyName(faculty) ||
                    'Selected Faculty';

                summaryEmail.textContent =
                    valueOrFallback(
                        faculty?.email
                    );

                setSummaryPill(
                    summaryType,
                    faculty?.faculty_type
                );

                setSummaryPill(
                    summaryDepartment,
                    faculty?.department
                );

                showSyncedInformation();

                setSearchError('');

                toggleFacultySearchClear();

                hideResults();

                updateProgress();
            }

            function normalizeFacultySearchText(
                value
            ) {

                return String(value ?? '')
                    .toLowerCase()
                    .replace(
                        /[^a-z0-9@\s._-]/g,
                        ' '
                    )
                    .replace(
                        /\s+/g,
                        ' '
                    )
                    .trim();
            }

            function buildFacultySearchTokens(
                value
            ) {

                return normalizeFacultySearchText(
                        value
                    )
                    .split(' ')
                    .filter(Boolean);
            }


            function scoreFacultyValue(
                haystack,
                query,
                tokens
            ) {

                const normalizedHaystack =
                    normalizeFacultySearchText(
                        haystack
                    );

                if (!normalizedHaystack) {
                    return -1;
                }

                if (
                    normalizedHaystack === query
                ) {
                    return 1000;
                }

                if (
                    normalizedHaystack.startsWith(
                        query
                    )
                ) {
                    return 800;
                }

                if (
                    tokens.length &&
                    tokens.every(token =>
                        normalizedHaystack.includes(
                            token
                        )
                    )
                ) {
                    return (
                        500 -
                        normalizedHaystack.indexOf(
                            tokens[0]
                        )
                    );
                }

                if (
                    normalizedHaystack.includes(
                        query
                    )
                ) {
                    return (
                        250 -
                        normalizedHaystack.indexOf(
                            query
                        )
                    );
                }

                return -1;
            }

            function scoreFacultyMatch(
                faculty,
                query
            ) {

                const tokens =
                    buildFacultySearchTokens(
                        query
                    );

                if (!tokens.length) {
                    return 0;
                }

                const values = [
                    getFullFacultyName(faculty),
                    faculty?.email,
                    faculty?.faculty_code,
                    faculty?.department,
                    faculty?.faculty_id,
                ];

                let best = -1;

                values.forEach(
                    (value, index) => {

                        const score =
                            scoreFacultyValue(
                                value,
                                query,
                                tokens
                            );

                        if (score >= 0) {
                            best = Math.max(
                                best,
                                score - index * 10
                            );
                        }
                    }
                );

                return best;
            }


            function filterFaculties(query) {

                const normalizedQuery =
                    normalizeFacultySearchText(
                        query
                    );

                if (!normalizedQuery) {
                    return faculties;
                }

                return faculties
                    .map(faculty => ({
                        faculty,
                        score: scoreFacultyMatch(
                            faculty,
                            normalizedQuery
                        ),
                    }))
                    .filter(entry =>
                        entry.score >= 0
                    )
                    .sort(
                        (a, b) =>
                        b.score - a.score
                    )
                    .map(
                        entry =>
                        entry.faculty
                    );
            }

            function hideResults() {

                resultsBox.classList.add(
                    'hidden'
                );

                resultsBox.innerHTML = '';
            }

            function showResults() {

                if (
                    !resultsBox.children.length
                ) {
                    hideResults();

                    return;
                }

                resultsBox.classList.remove(
                    'hidden'
                );
            }

            function renderNoResults(
                message = 'No results found.'
            ) {
                resultsBox.innerHTML = '';

                const empty =
                    document.createElement('div');

                empty.className =
                    'search-empty';

                empty.textContent =
                    message;

                resultsBox.appendChild(
                    empty
                );

                showResults();
            }


            function renderResults(list) {

                resultsBox.innerHTML = '';

                if (
                    !Array.isArray(list) ||
                    !list.length
                ) {
                    renderNoResults();

                    return;
                }

                list.forEach(faculty => {

                    const item =
                        document.createElement(
                            'button'
                        );

                    item.type = 'button';
                    item.className =
                        'search-item';


                    const name =
                        document.createElement(
                            'div'
                        );

                    name.className =
                        'search-name';

                    name.textContent =
                        getFullFacultyName(faculty) ||
                        'Unnamed Faculty';


                    const email =
                        document.createElement(
                            'div'
                        );

                    email.className =
                        'search-email';

                    email.textContent =
                        faculty?.email ??
                        faculty?.faculty_code ??
                        'No email available';


                    item.append(
                        name,
                        email
                    );


                    item.addEventListener(
                        'click',
                        function(event) {

                            event.preventDefault();

                            fillFaculty(
                                faculty
                            );
                        }
                    );


                    resultsBox.appendChild(
                        item
                    );
                });

                showResults();
            }

            function openFacultySearch() {

                if (facultiesLoading) {

                    renderNoResults(
                        'Loading faculty records...'
                    );

                    return;
                }

                if (facultyLoadError) {

                    renderNoResults(
                        facultyLoadError
                    );

                    return;
                }

                const query =
                    searchInput.value.trim();

                const filtered =
                    filterFaculties(query);

                filtered.length ?
                    renderResults(filtered) :
                    renderNoResults(
                        'No faculty records found.'
                    );
            }

            function toggleFacultySearchClear() {

                if (!clearSearchButton) {
                    return;
                }

                clearSearchButton.classList.toggle(
                    'show',
                    searchInput.value.trim().length > 0
                );
            }

            function clearFacultySearch() {

                searchInput.value = '';

                clearFacultySelection();

                hideResults();

                toggleFacultySearchClear();

                searchInput.focus();
            }

            function resetFacultyForm() {

                facultyForm.reset();

                searchInput.value = '';

                facultyJson.value = '';

                resetSyncedInformation();

                setSearchError('');

                setChoiceError(
                    cmsRoleError,
                    ''
                );

                setChoiceError(
                    accountStatusError,
                    ''
                );

                hideResults();

                toggleFacultySearchClear();

                updateProgress();
            }

            function validateFacultyIntegrationForm({
                showToastMessage = false
            } = {}) {

                let valid = true;

                if (
                    !facultyJson.value.trim()
                ) {

                    setSearchError(
                        'Please select a faculty record from the search results.'
                    );

                    valid = false;

                } else {

                    setSearchError('');
                }

                if (
                    !getSelectedRadio(
                        'cms_role'
                    )
                ) {

                    setChoiceError(
                        cmsRoleError,
                        'Please select a CMS role.'
                    );

                    valid = false;

                } else {

                    setChoiceError(
                        cmsRoleError,
                        ''
                    );
                }

                if (
                    !getSelectedRadio(
                        'account_status'
                    )
                ) {

                    setChoiceError(
                        accountStatusError,
                        'Please select an account status.'
                    );

                    valid = false;

                } else {

                    setChoiceError(
                        accountStatusError,
                        ''
                    );
                }

                if (
                    !valid &&
                    showToastMessage
                ) {

                    window.showToast?.({
                        type: 'error',
                        title: 'Complete required fields',
                        message: 'Select a faculty record, CMS role, and account status before saving.',
                        duration: 6000,
                    });


                    const firstError =
                        facultyForm.querySelector(
                            '.is-invalid, .global-field-error.show'
                        );

                    firstError?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                }

                return valid;
            }

            searchInput.addEventListener(
                'focus',
                openFacultySearch
            );

            searchInput.addEventListener(
                'click',
                openFacultySearch
            );

            searchInput.addEventListener(
                'input',
                function() {

                    toggleFacultySearchClear();
                    clearFacultySelection();

                    if (facultiesLoading) {

                        renderNoResults(
                            'Loading faculty records...'
                        );

                        return;
                    }

                    if (facultyLoadError) {

                        renderNoResults(
                            facultyLoadError
                        );

                        return;
                    }

                    const filtered =
                        filterFaculties(
                            this.value
                        );


                    filtered.length ?
                        renderResults(filtered) :
                        renderNoResults(
                            'No faculty records found.'
                        );
                }
            );


            searchInput.addEventListener(
                'keydown',
                function(event) {

                    if (
                        event.key === 'Escape'
                    ) {
                        hideResults();
                    }
                }
            );


            clearSearchButton?.addEventListener(
                'click',
                function(event) {

                    event.preventDefault();

                    clearFacultySearch();
                }
            );

            document.addEventListener(
                'click',
                function(event) {

                    const searchWrapper =
                        searchInput.closest(
                            '[data-search-wrapper]'
                        );

                    const clickedInside =
                        searchWrapper?.contains(
                            event.target
                        ) ||
                        resultsBox.contains(
                            event.target
                        ) ||
                        event.target === searchInput;


                    if (!clickedInside) {
                        hideResults();
                    }
                }
            );

            cmsRoleInputs.forEach(input => {

                input.addEventListener(
                    'change',
                    function() {

                        setChoiceError(
                            cmsRoleError,
                            ''
                        );

                        updateProgress();
                    }
                );
            });

            accountStatusInputs.forEach(
                input => {

                    input.addEventListener(
                        'change',
                        function() {

                            setChoiceError(
                                accountStatusError,
                                ''
                            );

                            updateProgress();
                        }
                    );
                }
            );

            resetFacultyBtn?.addEventListener(
                'click',
                function() {

                    resetFacultyForm();

                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth',
                    });


                    window.setTimeout(
                        () => {

                            searchInput.focus({
                                preventScroll: true,
                            });

                        },
                        400
                    );
                }
            );

            facultyForm?.addEventListener(
                'submit',
                function(event) {

                    if (
                        !validateFacultyIntegrationForm({
                            showToastMessage: true,
                        })
                    ) {

                        event.preventDefault();
                        event.stopPropagation();
                    }
                }
            );

            fetch(
                    @json(route('shared.faculties.index')), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                )
                .then(response => {

                    if (!response.ok) {

                        throw new Error(
                            `HTTP ${response.status}`
                        );
                    }

                    return response.json();
                })
                .then(data => {

                    faculties =
                        Array.isArray(data) ?
                        data : [];

                    facultyLoadError = '';
                })
                .catch(error => {

                    console.error(
                        'Failed to load faculties:',
                        error
                    );

                    faculties = [];

                    facultyLoadError =
                        'Unable to load faculty records. Please check the FLSS API connection.';
                })
                .finally(() => {

                    facultiesLoading = false;
                });

            if (
                facultyJson.value.trim()
            ) {

                try {

                    const savedFaculty =
                        JSON.parse(
                            facultyJson.value
                        );

                    fillFaculty(
                        savedFaculty
                    );

                } catch (error) {

                    console.warn(
                        'Unable to restore previous faculty selection.',
                        error
                    );

                    facultyJson.value = '';

                    resetSyncedInformation();
                }
            } else {

                resetSyncedInformation();
            }

            toggleFacultySearchClear();
            updateProgress();

            if (
                window.initSearchClearButtons
            ) {
                window.initSearchClearButtons();
            }

            if (
                window.initGlobalVoiceInputs
            ) {
                window.initGlobalVoiceInputs(
                    document
                );
            }

        });
    </script>
@endsection
