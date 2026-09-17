@extends('layouts.app')

@php
    $layoutRole = $layoutRole ?? (request()->routeIs('dentist.*') ? 'dentist' : 'admin');
    $isDentistView = $layoutRole === 'dentist';
    $routePrefix = $isDentistView ? 'dentist' : 'admin';
@endphp

@section('layout-role', $layoutRole)
@section('title', 'CMS Access')

@section('content')
    <main id="mainContent" class="app-page-shell page-enter">
        <div class="w-full">

            @if ($isDentistView)
                <div class="dentist-hero mb-6">
                    <div class="dentist-hero-content">
                        <div class="dentist-hero-icon">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="dentist-hero-eyebrow">
                                <i class="fa-solid fa-tooth"></i>
                                Clinical Operations
                            </div>
                            <h1 class="dentist-hero-title">Assign CMS Access</h1>
                        </div>
                    </div>
                </div>
            @else
                <div class="page-banner mb-6">
                    <div class="page-banner-inner">
                        <div class="min-w-0">
                            <h1 class="page-title">Assign CMS Access</h1>
                        </div>
                    </div>
                </div>
            @endif

            <form id="assignCmsAccessForm" method="POST" action="{{ route($routePrefix . '.assign-cms-access.store') }}"
                novalidate class="mt-4">
                @csrf

                <input type="hidden" name="external_admin_id" id="external_admin_id">
                <input type="hidden" name="fname" id="fname">
                <input type="hidden" name="lname" id="lname">
                <input type="hidden" name="email" id="email">
                <input type="hidden" name="office" id="office">
                <input type="hidden" name="address" id="address">
                <input type="hidden" name="age" id="age">
                <input type="hidden" name="gender" id="gender">
                <input type="hidden" name="contact_number" id="contact_number">

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
                    <div class="grid min-w-0 gap-4 xl:col-span-3">

                        <section class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><span>1</span></div>
                                    <div class="min-w-0">
                                        <h2 class="card-title">Search user</h2>
                                        <p class="card-subtitle">Pulled from the OCMS external administrator directory</p>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="flex items-center justify-between">
                                        <span class="ui-muted-text">Progress</span>
                                        <span id="progressUserPercent" class="ui-muted-text">0%</span>
                                    </div>
                                    <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                        aria-valuenow="0">
                                        <div id="progressUserBar" class="progress-fill" style="width: 0%;"></div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <label for="user_search" class="field-label">
                                        Select User <span class="required-mark">*</span>
                                    </label>

                                    <div class="flex w-full min-w-0 items-start gap-2">
                                        <div class="search-combo min-w-0 flex-1">
                                            <div class="search-wrap global-search" data-search-wrapper>
                                                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                                <input type="text" id="user_search" class="search-input"
                                                    placeholder="Search by name, email, or office" autocomplete="off"
                                                    data-search-input>
                                                <button type="button" id="userSearchClearBtn" class="search-clear"
                                                    data-search-clear aria-label="Clear user search"
                                                    title="Clear user search">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>
                                            <div id="searchResults" class="search-results hidden"></div>
                                        </div>

                                        <div class="voice-input-toggle">
                                            <button type="button" id="cmsSearchMicBtn" class="voice-search-mic external"
                                                data-voice-trigger data-voice-target="#user_search"
                                                data-voice-status="#cmsSearchVoiceStatus" aria-label="Toggle voice input"
                                                aria-pressed="false">
                                                <i class="fa-solid fa-microphone"></i>
                                            </button>
                                            <span id="cmsSearchVoiceStatus" class="voice-status hidden"
                                                aria-live="polite"></span>
                                        </div>
                                    </div>

                                    <div id="userSearchError" class="global-field-error"></div>
                                </div>
                            </div>
                        </section>

                        <section class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><span>2</span></div>
                                    <div class="min-w-0">
                                        <h2 class="card-title">Synced information</h2>
                                        <p class="card-subtitle">Read-only information pulled automatically after selection
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="flex items-center justify-between">
                                        <span class="ui-muted-text">Progress</span>
                                        <span id="progressInformationPercent" class="ui-muted-text">0%</span>
                                    </div>
                                    <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                        aria-valuenow="0">
                                        <div id="progressInformationBar" class="progress-fill" style="width: 0%;"></div>
                                    </div>
                                </div>

                                <div id="userEmptyState" class="global-info-item">
                                    <div class="global-info-icon">
                                        <i class="fa-solid fa-user-shield"></i>
                                    </div>
                                    <div class="global-info-copy">
                                        <span class="global-info-label">No user selected</span>
                                        <span class="global-info-value">Search and select a user record above to display
                                            the synced information.</span>
                                    </div>
                                </div>

                                <div id="userSyncedInformation" hidden>
                                    <div class="flex flex-col gap-5">
                                        <div class="flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-center"
                                            style="border-color: var(--border);">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <div id="summary_avatar" class="patient-avatar patient-avatar-lg">--</div>
                                                <div class="min-w-0">
                                                    <span id="summary_name" class="global-info-profile-name">Not
                                                        provided</span>
                                                    <span id="summary_email" class="global-info-subvalue">Not
                                                        provided</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap gap-2 sm:ml-auto sm:justify-end">
                                                <span id="summary_office" class="status-pill hidden"></span>
                                                <span id="summary_access_level"
                                                    class="status-pill status-all hidden"></span>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                                            <div class="min-w-0">
                                                <div class="section-card-title"><span>Identity</span></div>

                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">External Admin ID</span>
                                                    <span id="display_admin_id" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">First Name</span>
                                                    <span id="display_fname" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Last Name</span>
                                                    <span id="display_lname" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Birthday</span>
                                                    <span id="display_birthday" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Age</span>
                                                    <span id="display_age" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Gender</span>
                                                    <span id="display_gender" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="section-card-title"><span>Organization</span></div>

                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Office</span>
                                                    <span id="display_office" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Access Level</span>
                                                    <span id="display_access_level" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Email</span>
                                                    <span id="display_email" class="global-info-value min-w-0 text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Contact Number</span>
                                                    <span id="display_contact" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                                <div
                                                    class="global-info-item global-info-item-inline justify-between gap-4">
                                                    <span class="ui-muted-text">Senior / PWD</span>
                                                    <span id="display_senior_pwd" class="global-info-value text-right"
                                                        style="margin-top: 0;">Not provided</span>
                                                </div>
                                            </div>

                                            <div class="min-w-0 lg:col-span-2">
                                                <div class="section-card-title"><span>Address</span></div>
                                                <div
                                                    class="global-info-item global-info-item-inline items-start justify-between gap-4">
                                                    <span class="ui-muted-text">Home Address</span>
                                                    <span id="display_address"
                                                        class="global-info-value min-w-0 text-right"
                                                        style="margin-top: 0;">Not provided</span>
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
                                    <div class="card-header-icon"><span>3</span></div>
                                    <div class="min-w-0">
                                        <h2 class="card-title">Access configuration</h2>
                                        <p class="card-subtitle">Assign the CMS role and account status</p>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="flex items-center justify-between">
                                        <span class="ui-muted-text">Setup progress</span>
                                        <span id="progressAccessPercent" class="ui-muted-text">0%</span>
                                    </div>
                                    <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                        aria-valuenow="0">
                                        <div id="progressAccessBar" class="progress-fill" style="width: 0%;"></div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">
                                        CMS Role <span class="required-mark">*</span>
                                    </label>

                                    <div id="cmsRoleGroup" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <label class="global-choice-card">
                                            <input type="radio" name="cms_role" value="patient"
                                                class="global-choice-input" @checked(old('cms_role') === 'patient')>
                                            <span class="global-info-icon"><i class="fa-solid fa-user"></i></span>
                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">Patient</span>
                                                <span class="global-choice-description">Standard patient access</span>
                                            </span>
                                        </label>

                                        <label class="global-choice-card">
                                            <input type="radio" name="cms_role" value="dentist"
                                                class="global-choice-input" @checked(old('cms_role') === 'dentist')>
                                            <span class="global-info-icon"><i class="fa-solid fa-tooth"></i></span>
                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">Dentist</span>
                                                <span class="global-choice-description">Clinical CMS access</span>
                                            </span>
                                        </label>

                                        <label class="global-choice-card">
                                            <input type="radio" name="cms_role" value="admin"
                                                class="global-choice-input" @checked(old('cms_role') === 'admin')>
                                            <span class="global-info-icon"><i class="fa-solid fa-user-gear"></i></span>
                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">Admin</span>
                                                <span class="global-choice-description">Administrative CMS access</span>
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
                                        Account Status <span class="required-mark">*</span>
                                    </label>

                                    <div id="accountStatusGroup" class="global-choice-group">
                                        <label class="global-choice-card status-active">
                                            <input type="radio" name="cms_status" value="active"
                                                class="global-choice-input" @checked(old('cms_status') === 'active')>
                                            <span class="global-info-icon status-active"><i
                                                    class="fa-solid fa-circle-check"></i></span>
                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">Active</span>
                                                <span class="global-choice-description">Allow the account to access
                                                    CMS</span>
                                            </span>
                                        </label>

                                        <label class="global-choice-card status-pending">
                                            <input type="radio" name="cms_status" value="inactive"
                                                class="global-choice-input" @checked(old('cms_status') === 'inactive')>
                                            <span class="global-info-icon status-pending"><i
                                                    class="fa-solid fa-circle-minus"></i></span>
                                            <span class="global-choice-copy">
                                                <span class="global-choice-title">Inactive</span>
                                                <span class="global-choice-description">Keep the record while disabling
                                                    access</span>
                                            </span>
                                        </label>
                                    </div>

                                    <div id="accountStatusError"
                                        class="global-field-error @error('cms_status') show @enderror">
                                        @error('cms_status')
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                            <span>{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                    <button type="button" class="btn-reset" id="resetAssignCmsBtn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Reset
                                    </button>
                                    <button type="submit" class="ui-btn ui-btn-primary">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Save Access
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="grid h-fit min-w-0 self-start content-start gap-4 xl:sticky xl:top-24 xl:col-span-1">
                        <section class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><i class="fa-solid fa-circle-info"></i></div>
                                    <div>
                                        <h2 class="card-title">Quick Notes</h2>
                                        <p class="card-subtitle">Guidance for a cleaner workflow</p>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="sidebar-stack">
                                    <div class="global-info-item global-info-item-inline">
                                        <div class="global-info-icon status-completed"><i class="fa-solid fa-check"></i>
                                        </div>
                                        <div class="global-info-copy">
                                            <span class="ui-muted-text">Always select a user record from the search results
                                                so synced information remains accurate.</span>
                                        </div>
                                    </div>

                                    <div class="global-info-item global-info-item-inline">
                                        <div class="global-info-icon status-all"><i class="fa-solid fa-user-gear"></i>
                                        </div>
                                        <div class="global-info-copy">
                                            <span class="ui-muted-text">Review the user identity, office, and contact
                                                details before assigning a CMS role.</span>
                                        </div>
                                    </div>

                                    <div class="global-info-item global-info-item-inline">
                                        <div class="global-info-icon status-pending"><i class="fa-solid fa-shield"></i>
                                        </div>
                                        <div class="global-info-copy">
                                            <span class="ui-muted-text">Use Inactive when the record should remain stored
                                                but CMS access must be disabled.</span>
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
            const assignForm = document.getElementById('assignCmsAccessForm');
            const searchInput = document.getElementById('user_search');
            const clearSearchButton = document.getElementById('userSearchClearBtn');
            const resultsBox = document.getElementById('searchResults');
            const resetBtn = document.getElementById('resetAssignCmsBtn');

            const externalAdminId = document.getElementById('external_admin_id');
            const fname = document.getElementById('fname');
            const lname = document.getElementById('lname');
            const email = document.getElementById('email');
            const office = document.getElementById('office');
            const address = document.getElementById('address');
            const age = document.getElementById('age');
            const gender = document.getElementById('gender');
            const contactNumber = document.getElementById('contact_number');

            const emptyState = document.getElementById('userEmptyState');
            const syncedInformation = document.getElementById('userSyncedInformation');

            const displayFields = {
                admin_id: document.getElementById('display_admin_id'),
                fname: document.getElementById('display_fname'),
                lname: document.getElementById('display_lname'),
                birthday: document.getElementById('display_birthday'),
                age: document.getElementById('display_age'),
                gender: document.getElementById('display_gender'),
                office: document.getElementById('display_office'),
                access_level: document.getElementById('display_access_level'),
                email: document.getElementById('display_email'),
                contact: document.getElementById('display_contact'),
                senior_pwd: document.getElementById('display_senior_pwd'),
                address: document.getElementById('display_address'),
            };

            const summaryAvatar = document.getElementById('summary_avatar');
            const summaryName = document.getElementById('summary_name');
            const summaryEmail = document.getElementById('summary_email');
            const summaryOffice = document.getElementById('summary_office');
            const summaryAccessLevel = document.getElementById('summary_access_level');

            const userSearchError = document.getElementById('userSearchError');
            const cmsRoleError = document.getElementById('cmsRoleError');
            const accountStatusError = document.getElementById('accountStatusError');

            const cmsRoleInputs = Array.from(document.querySelectorAll('input[name="cms_role"]'));
            const accountStatusInputs = Array.from(document.querySelectorAll('input[name="cms_status"]'));

            const progressUserBar = document.getElementById('progressUserBar');
            const progressUserPercent = document.getElementById('progressUserPercent');
            const progressInformationBar = document.getElementById('progressInformationBar');
            const progressInformationPercent = document.getElementById('progressInformationPercent');
            const progressAccessBar = document.getElementById('progressAccessBar');
            const progressAccessPercent = document.getElementById('progressAccessPercent');

            let fullUserList = [];
            let fullListLoaded = false;
            let usersFetchPromise = null;
            let searchRequestSerial = 0;
            let stateAnimationTimer = null;

            @if ($errors->any())
                window.showToast?.({
                    type: 'error',
                    title: 'Unable to save CMS access',
                    message: @json($errors->first()),
                    duration: 7000,
                });
            @endif

            function valueOrFallback(value, emptyText = 'Not provided') {
                const normalized = String(value ?? '').trim();
                return normalized || emptyText;
            }

            function setDisplayValue(element, value, emptyText = 'Not provided') {
                if (!element) return;

                const normalized = String(value ?? '').trim();
                element.textContent = normalized || emptyText;
                element.classList.toggle('ui-muted-text', !normalized);
            }

            function setSummaryPill(element, value) {
                if (!element) return;

                const normalized = String(value ?? '').trim();
                element.textContent = normalized;
                element.classList.toggle('hidden', !normalized);
            }

            function getUserInitials(user) {
                const first = String(user?.fname ?? '').trim().charAt(0);
                const last = String(user?.lname ?? '').trim().charAt(0);
                return (`${first}${last}`.toUpperCase() || 'UA');
            }

            function getSelectedRadio(name) {
                return document.querySelector(`input[name="${name}"]:checked`);
            }

            function setProgressBar(element, percentageElement, percentage) {
                if (!element) return;

                const normalizedPercentage = Math.max(0, Math.min(100, Number(percentage) || 0));
                element.style.width = `${normalizedPercentage}%`;
                element.style.setProperty(
                    '--progress-fill-color',
                    normalizedPercentage === 100 ?
                    'var(--status-completed-solid)' :
                    'var(--crimson-1)'
                );

                element.closest('[role="progressbar"]')?.setAttribute('aria-valuenow', normalizedPercentage);

                if (percentageElement) {
                    percentageElement.textContent = `${normalizedPercentage}%`;
                }
            }

            function updateProgress() {
                const userSelected = Boolean(externalAdminId.value.trim());
                const roleSelected = Boolean(getSelectedRadio('cms_role'));
                const statusSelected = Boolean(getSelectedRadio('cms_status'));

                setProgressBar(progressUserBar, progressUserPercent, userSelected ? 100 : 0);
                setProgressBar(progressInformationBar, progressInformationPercent, userSelected ? 100 : 0);

                let accessProgress = 0;
                if (roleSelected) accessProgress += 50;
                if (statusSelected) accessProgress += 50;

                setProgressBar(progressAccessBar, progressAccessPercent, accessProgress);
            }

            function showEmptyState() {
                window.clearTimeout(stateAnimationTimer);
                syncedInformation.hidden = true;
                syncedInformation.classList.remove('content-fade-in', 'content-fade-out');
                emptyState.hidden = false;
                emptyState.classList.remove('content-fade-out');
                emptyState.classList.add('content-fade-in');

                stateAnimationTimer = window.setTimeout(() => {
                    emptyState.classList.remove('content-fade-in');
                }, 220);
            }

            function showSyncedInformation() {
                window.clearTimeout(stateAnimationTimer);
                syncedInformation.hidden = false;
                syncedInformation.classList.remove('content-fade-out');
                syncedInformation.classList.add('content-fade-in');
                emptyState.classList.remove('content-fade-in');
                emptyState.classList.add('content-fade-out');

                stateAnimationTimer = window.setTimeout(() => {
                    emptyState.hidden = true;
                    emptyState.classList.remove('content-fade-out');
                    syncedInformation.classList.remove('content-fade-in');
                }, 180);
            }

            function resetSyncedInformation() {
                Object.values(displayFields).forEach(element => setDisplayValue(element, ''));
                summaryAvatar.textContent = '--';
                summaryName.textContent = 'Not provided';
                summaryEmail.textContent = 'Not provided';
                setSummaryPill(summaryOffice, '');
                setSummaryPill(summaryAccessLevel, '');
                showEmptyState();
            }

            function resolveExternalAdminId(user) {
                const directId = String(user?.admin_id ?? '').trim();
                if (directId) return directId;

                const emailFallback = String(user?.email ?? '').trim();
                if (emailFallback) return emailFallback;

                return [user?.fname ?? '', user?.lname ?? '', user?.office ?? '']
                    .map(value => String(value).trim())
                    .filter(Boolean)
                    .join('-');
            }

            function clearHiddenFields() {
                externalAdminId.value = '';
                fname.value = '';
                lname.value = '';
                email.value = '';
                office.value = '';
                address.value = '';
                age.value = '';
                gender.value = '';
                contactNumber.value = '';
            }

            function clearUserSelection() {
                clearHiddenFields();
                resetSyncedInformation();
                setSearchError('');
                updateProgress();
            }

            function fillUser(user) {
                externalAdminId.value = resolveExternalAdminId(user);
                fname.value = user?.fname ?? '';
                lname.value = user?.lname ?? '';
                email.value = user?.email ?? '';
                office.value = user?.office ?? '';
                address.value = user?.address ?? '';
                age.value = user?.age ?? '';
                gender.value = user?.gender ?? '';
                contactNumber.value = user?.contact_number ?? '';

                searchInput.value = user?.full_name ?? [user?.fname, user?.lname].filter(Boolean).join(' ');

                setDisplayValue(displayFields.admin_id, user?.admin_id);
                setDisplayValue(displayFields.fname, user?.fname);
                setDisplayValue(displayFields.lname, user?.lname);
                setDisplayValue(displayFields.birthday, user?.birthday);
                setDisplayValue(displayFields.age, user?.age);
                setDisplayValue(displayFields.gender, user?.gender);
                setDisplayValue(displayFields.office, user?.office);
                setDisplayValue(displayFields.access_level, user?.access_level);
                setDisplayValue(displayFields.email, user?.email);
                setDisplayValue(displayFields.contact, user?.contact_number);
                setDisplayValue(displayFields.senior_pwd, user?.senior_pwd);
                setDisplayValue(displayFields.address, user?.address);

                summaryAvatar.textContent = getUserInitials(user);
                summaryName.textContent = valueOrFallback(user?.full_name, 'Selected user');
                summaryEmail.textContent = valueOrFallback(user?.email);
                setSummaryPill(summaryOffice, user?.office);
                setSummaryPill(summaryAccessLevel, user?.access_level);

                showSyncedInformation();
                setSearchError('');
                toggleUserSearchClear();
                hideResults();
                updateProgress();
            }

            function normalizeSearchText(value) {
                return String(value ?? '')
                    .toLowerCase()
                    .replace(/[^a-z0-9@\s._-]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function buildSearchTokens(value) {
                return normalizeSearchText(value).split(' ').filter(Boolean);
            }

            function scoreSearchValue(haystack, query, tokens) {
                const normalizedHaystack = normalizeSearchText(haystack);
                if (!normalizedHaystack) return -1;
                if (normalizedHaystack === query) return 1000;
                if (normalizedHaystack.startsWith(query)) return 800;
                if (tokens.length && tokens.every(token => normalizedHaystack.includes(token))) {
                    return 500 - normalizedHaystack.indexOf(tokens[0]);
                }
                if (normalizedHaystack.includes(query)) {
                    return 250 - normalizedHaystack.indexOf(query);
                }
                return -1;
            }

            function scoreUser(user, query) {
                const tokens = buildSearchTokens(query);
                if (!tokens.length) return 0;

                const normalizedQuery = normalizeSearchText(query);
                const values = [
                    user?.full_name,
                    `${user?.fname ?? ''} ${user?.lname ?? ''}`,
                    user?.email,
                    user?.office,
                    user?.admin_id,
                    user?.access_level,
                ];

                let best = -1;
                values.forEach((value, index) => {
                    const score = scoreSearchValue(value, normalizedQuery, tokens);
                    if (score >= 0) {
                        best = Math.max(best, score - index * 10);
                    }
                });

                return best;
            }

            function rankUsers(users, query) {
                const normalizedQuery = normalizeSearchText(query);
                if (!normalizedQuery) return users;

                return [...users]
                    .map(user => ({
                        user,
                        score: scoreUser(user, normalizedQuery)
                    }))
                    .filter(entry => entry.score >= 0)
                    .sort((a, b) => b.score - a.score)
                    .map(entry => entry.user);
            }

            function hideResults() {
                resultsBox.classList.add('hidden');
                resultsBox.innerHTML = '';
            }

            function showResults() {
                if (!resultsBox.children.length) {
                    hideResults();
                    return;
                }
                resultsBox.classList.remove('hidden');
            }

            function renderNoResults(message = 'No results found.') {
                resultsBox.innerHTML = '';
                const empty = document.createElement('div');
                empty.className = 'search-empty';
                empty.textContent = message;
                resultsBox.appendChild(empty);
                showResults();
            }

            function renderResults(users) {
                resultsBox.innerHTML = '';

                if (!Array.isArray(users) || !users.length) {
                    renderNoResults();
                    return;
                }

                users.forEach(user => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'search-item';

                    const name = document.createElement('div');
                    name.className = 'search-name';
                    name.textContent = user?.full_name || [user?.fname, user?.lname].filter(Boolean).join(
                        ' ') || 'Unnamed user';

                    const emailText = document.createElement('div');
                    emailText.className = 'search-email';
                    emailText.textContent = user?.email ?? user?.office ?? 'No email available';

                    item.append(name, emailText);
                    item.addEventListener('click', event => {
                        event.preventDefault();
                        fillUser(user);
                    });

                    resultsBox.appendChild(item);
                });

                showResults();
            }

            async function fetchUsers(query = '') {
                const params = new URLSearchParams();
                const trimmed = query.trim();
                if (trimmed) params.set('search', trimmed);

                const response = await fetch(
                    `{{ $routePrefix === 'dentist' ? '/dentist' : '/admin' }}/external-admins/search?${params.toString()}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                if (!data || !data.success || !Array.isArray(data.data)) {
                    throw new Error('Invalid response format');
                }

                return data.data;
            }

            async function fetchAllUsers() {
                if (fullListLoaded) return fullUserList;
                if (usersFetchPromise) return usersFetchPromise;

                usersFetchPromise = fetchUsers('')
                    .then(users => {
                        fullUserList = users;
                        fullListLoaded = true;
                        return fullUserList;
                    })
                    .catch(error => {
                        console.error('Fetch all users error:', error);
                        return fullUserList;
                    })
                    .finally(() => {
                        usersFetchPromise = null;
                    });

                return usersFetchPromise;
            }

            async function openUserSearch() {
                if (!fullListLoaded) {
                    await fetchAllUsers();
                }

                const query = searchInput.value.trim();
                const filtered = rankUsers(fullUserList, query);

                filtered.length ?
                    renderResults(filtered) :
                    renderNoResults('No users available.');
            }

            function toggleUserSearchClear() {
                if (!clearSearchButton) return;
                clearSearchButton.classList.toggle('show', searchInput.value.trim().length > 0);
            }

            function clearUserSearch() {
                searchInput.value = '';
                clearUserSelection();
                hideResults();
                toggleUserSearchClear();
                searchInput.focus();
            }

            function setSearchError(message = '') {
                searchInput.classList.toggle('is-invalid', Boolean(message));
                userSearchError.innerHTML = '';

                if (!message) {
                    userSearchError.classList.remove('show');
                    return;
                }

                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-circle-exclamation';

                const text = document.createElement('span');
                text.textContent = message;

                userSearchError.append(icon, text);
                userSearchError.classList.add('show');
            }

            function setChoiceError(errorElement, message = '') {
                if (!errorElement) return;
                errorElement.innerHTML = '';

                if (!message) {
                    errorElement.classList.remove('show');
                    return;
                }

                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-circle-exclamation';

                const text = document.createElement('span');
                text.textContent = message;

                errorElement.append(icon, text);
                errorElement.classList.add('show');
            }

            function resetAssignCmsForm() {
                assignForm?.reset();
                searchInput.value = '';
                clearHiddenFields();
                resetSyncedInformation();
                setSearchError('');
                setChoiceError(cmsRoleError, '');
                setChoiceError(accountStatusError, '');
                hideResults();
                toggleUserSearchClear();
                updateProgress();
            }

            function validateAssignCmsForm({
                showToastMessage = false
            } = {}) {
                let valid = true;

                if (!externalAdminId.value.trim()) {
                    setSearchError('Please select a user from the search results.');
                    valid = false;
                } else {
                    setSearchError('');
                }

                if (!getSelectedRadio('cms_role')) {
                    setChoiceError(cmsRoleError, 'Please select a CMS role.');
                    valid = false;
                } else {
                    setChoiceError(cmsRoleError, '');
                }

                if (!getSelectedRadio('cms_status')) {
                    setChoiceError(accountStatusError, 'Please select an account status.');
                    valid = false;
                } else {
                    setChoiceError(accountStatusError, '');
                }

                if (!valid && showToastMessage) {
                    window.showToast?.({
                        type: 'error',
                        title: 'Complete required fields',
                        message: 'Select a user, CMS role, and account status before saving.',
                        duration: 6000,
                    });

                    const firstError = assignForm.querySelector('.is-invalid, .global-field-error.show');
                    firstError?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                return valid;
            }

            searchInput?.addEventListener('focus', openUserSearch);
            searchInput?.addEventListener('click', openUserSearch);

            searchInput?.addEventListener('input', async function() {
                toggleUserSearchClear();
                clearUserSelection();

                const query = this.value.trim();

                try {
                    const users = await fetchAllUsers();
                    const filtered = rankUsers(users, query);

                    if (filtered.length) {
                        renderResults(filtered);
                    } else {
                        renderNoResults(
                            query ?
                            'No matching users found.' :
                            'No users available.'
                        );
                    }
                } catch (error) {
                    console.error('User search error:', error);
                    renderNoResults('Unable to search users right now.');
                }
            });

            searchInput?.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') hideResults();
            });

            clearSearchButton?.addEventListener('click', function(event) {
                event.preventDefault();
                clearUserSearch();
            });

            document.addEventListener('click', function(event) {
                const searchWrapper = searchInput.closest('[data-search-wrapper]');
                const clickedInside =
                    searchWrapper?.contains(event.target) ||
                    resultsBox.contains(event.target) ||
                    event.target === searchInput;

                if (!clickedInside) hideResults();
            });

            cmsRoleInputs.forEach(input => {
                input.addEventListener('change', function() {
                    setChoiceError(cmsRoleError, '');
                    updateProgress();
                });
            });

            accountStatusInputs.forEach(input => {
                input.addEventListener('change', function() {
                    setChoiceError(accountStatusError, '');
                    updateProgress();
                });
            });

            resetBtn?.addEventListener('click', function() {
                resetAssignCmsForm();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });

                window.setTimeout(() => {
                    searchInput.focus({
                        preventScroll: true
                    });
                }, 400);
            });

            assignForm?.addEventListener('submit', function(event) {
                if (!validateAssignCmsForm({
                        showToastMessage: true
                    })) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });

            resetSyncedInformation();
            toggleUserSearchClear();
            updateProgress();

            if (window.initSearchClearButtons) {
                window.initSearchClearButtons();
            }

            if (window.initGlobalVoiceInputs) {
                window.initGlobalVoiceInputs(document);
            }
        });
    </script>
@endsection
