@extends('layouts.app')

@php
    $layoutRole ??= 'dentist';

    $isAdminView = $layoutRole === 'admin';

    $patientSearchRoute ??= 'dentist.walk-in.search-patient';

    $existingAppointmentRoute ??= 'dentist.odontogram.existing-appointment.create';

    $patientRoleFilterOptions = [
        [
            'value' => 'all',
            'label' => 'All Patients',
            'icon' => 'fa-users',
        ],
        [
            'value' => 'patient',
            'label' => 'Patient',
            'icon' => 'fa-user-graduate',
        ],
        [
            'value' => 'faculty',
            'label' => 'Faculty',
            'icon' => 'fa-chalkboard-user',
        ],
        [
            'value' => 'admin',
            'label' => 'Administrative',
            'icon' => 'fa-user-tie',
        ],
    ];
@endphp

@section('layout-role', $layoutRole)

@section('title', 'Add Existing Record')

@section('styles')
    @vite('resources/css/pages/shared/add-existing-record.css')
@endsection

@section('content')
    <main id="mainContent" class="app-page-shell existing-record-page page-enter">
        <div class="w-full">
            @if ($isAdminView)
                <div class="page-banner mb-6">
                    <div class="page-banner-inner">
                        <div class="min-w-0">
                            <h1 class="page-title">
                                Add Existing Record
                            </h1>
                        </div>
                    </div>
                </div>
            @else
                <div class="dentist-hero page-title-row mb-6">
                    <div class="dentist-hero-content">

                        <div class="dentist-hero-icon">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>

                        <div class="min-w-0">

                            <div class="dentist-hero-eyebrow">
                                <i class="fa-solid fa-tooth"></i>
                                Existing Records
                            </div>

                            <h2 class="dentist-hero-title">
                                Add Existing Record
                            </h2>

                        </div>

                    </div>
                </div>
            @endif

            <div class="existing-record-directory mb-5">
                <div class="existing-record-directory-copy">
                    <div class="global-icon-box global-icon-box-sm">
                        <i class="fa-solid fa-database"></i>
                    </div>

                    <div>
                        <p class="existing-record-directory-subtitle">
                            Select a patient from student,
                            faculty, or administrative records
                            to encode an existing appointment.
                        </p>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="table-toolbar-search">
                        <div class="voice-search-row">
                            <x-search-bar id="patientSearchInput" placeholder="Search by name, ID, email, or program..."
                                callback="handleExistingRecordSearch" :debounce="300" clear-label="Clear patient search"
                                class="flex-1" />

                            <x-voice-input target="#patientSearchInput" status-id="existingRecordVoiceStatus"
                                label="Use voice search" title="Voice search" />
                        </div>
                    </div>

                    <div class="table-toolbar-actions">
                        <x-filter-select id="existingRecordRoleFilter" name="existing_record_role" label="Role"
                            value="all" :options="$patientRoleFilterOptions" callback="handleExistingRecordRoleFilter" icon="fa-users"
                            menu-align="right" />
                    </div>
                </div>

                <x-pagination-bar id="existingRecordPaginationTopBar" info-id="existingRecordPageInfoTop"
                    pagination-id="existingRecordPaginationTop" position="top" :show-entries="true"
                    page-size-id="existingRecordPerPage" page-size-callback="handleExistingRecordPerPageChange"
                    :page-size-value="10" page-size-label="per page" label="patient records" />

                <div class="table-grid-view existing-record-grid-view">
                    <div id="patientGrid" class="table-record-grid existing-record-patient-grid" aria-live="polite">
                    </div>
                </div>

                <x-pagination-bar id="existingRecordPaginationBottomBar" info-id="existingRecordPageInfoBottom"
                    pagination-id="existingRecordPaginationBottom" position="bottom" label="patient records" hidden />

                <div id="existingRecordEmptyState" class="empty-state-host"></div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('patientSearchInput');
            const patientGrid = document.getElementById('patientGrid');
            const searchEndpoint =
                @json(route($patientSearchRoute));

            const recordUrlTemplate =
                @json(route($existingAppointmentRoute, ['patient' => '__PATIENT__']));

            window.initGlobalSearchBars?.();
            window.initGlobalVoiceInputs?.();
            window.initGlobalFilterSelects?.();
            window.initGlobalPageSizeSelects?.();

            let activeRequestId = 0;
            let patientFetchController = null;

            const patientResponseCache = new Map();
            let patientCurrentPage = 1;
            let patientPageSize = 10;
            let patientPaginationMeta = {
                currentPage: 1,
                lastPage: 1,
                total: 0,
                from: null,
                to: null,
            };

            renderPatientPagination();
            window.handleExistingRecordSearch =
                function(value) {
                    patientCurrentPage = 1;

                    const query =
                        String(value || '')
                        .trim();

                    loadPatients(
                        query,
                        query === ''
                    );
                };

            function buildPatientSkeletons(
                count = patientPageSize
            ) {
                const skeletonCount =
                    Math.min(
                        Math.max(
                            Number(count) || 10,
                            4
                        ),
                        12
                    );

                return Array
                    .from({
                            length: skeletonCount
                        },
                        () => `
                <div
                    class="
                        skeleton-shell
                        p-4
                        min-h-[170px]
                    "
                    aria-hidden="true"
                >
                    <div
                        class="
                            flex
                            items-start
                            gap-3
                        "
                    >
                        <div
                            class="
                                skeleton-circle
                                w-11
                                h-11
                                flex-shrink-0
                            "
                        ></div>

                        <div
                            class="
                                flex-1
                                min-w-0
                            "
                        >
                            <div
                                class="
                                    skeleton-line
                                    h-4
                                    w-3/5
                                    mb-3
                                "
                            ></div>

                            <div
                                class="
                                    skeleton-line
                                    h-3
                                    w-4/5
                                    mb-2
                                "
                            ></div>

                            <div
                                class="
                                    skeleton-pill
                                    h-6
                                    w-20
                                "
                            ></div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div
                            class="
                                skeleton-line
                                h-3
                                w-2/3
                                mb-2
                            "
                        ></div>

                        <div
                            class="
                                skeleton-block
                                h-9
                                w-full
                                mt-4
                            "
                        ></div>
                    </div>
                </div>
            `
                    )
                    .join('');
            }

            function renderPatientSkeletons() {
                window.EmptyState?.hide(
                    '#existingRecordEmptyState'
                );

                patientGrid.innerHTML =
                    buildPatientSkeletons();
            }

            let activeRoleFilter = '';

            window.handleExistingRecordRoleFilter =
                function(value) {
                    const selectedValue =
                        Array.isArray(value) ?
                        String(value[0] || 'all') :
                        String(value || 'all');

                    activeRoleFilter =
                        selectedValue === 'all' ?
                        '' :
                        selectedValue;

                    patientCurrentPage = 1;

                    const query =
                        input.value.trim();

                    loadPatients(
                        query,
                        query === ''
                    );
                };

            function getPatientRequestCacheKey(query = '') {
                return [
                    String(query || '').trim().toLowerCase(),
                    activeRoleFilter || 'all',
                    patientCurrentPage,
                    patientPageSize,
                ].join('|');
            }

            function applyPatientResponse(result) {
                const normalizedPatients =
                    Array.isArray(result) ?
                    result :
                    Array.isArray(result?.data) ?
                    result.data :
                    [];

                patientPaginationMeta = {
                    currentPage: Number(result?.current_page) || 1,

                    lastPage: Number(result?.last_page) || 1,

                    total: Number(result?.total) ||
                        normalizedPatients.length,

                    from: result?.from ??
                        (
                            normalizedPatients.length ?
                            (
                                (
                                    Number(
                                        result?.current_page ||
                                        patientCurrentPage
                                    ) - 1
                                ) * patientPageSize
                            ) + 1 :
                            null
                        ),

                    to: result?.to ??
                        (
                            normalizedPatients.length ?
                            (
                                (
                                    Number(
                                        result?.current_page ||
                                        patientCurrentPage
                                    ) - 1
                                ) * patientPageSize
                            ) +
                            normalizedPatients.length :
                            null
                        ),
                };

                patientCurrentPage =
                    patientPaginationMeta.currentPage;

                renderPatients(
                    normalizedPatients
                );

                renderPatientPagination();
            }

            async function loadPatients(
                query = '',
                showAll = false,
                options = {}
            ) {
                if (!patientGrid) {
                    return;
                }

                const showLoading =
                    options.showLoading !== false;

                const requestId =
                    ++activeRequestId;

                const cacheKey =
                    getPatientRequestCacheKey(
                        query
                    );

                if (
                    patientResponseCache.has(
                        cacheKey
                    )
                ) {
                    applyPatientResponse(
                        patientResponseCache.get(
                            cacheKey
                        )
                    );

                    return;
                }

                patientFetchController?.abort();

                patientFetchController =
                    new AbortController();

                const params =
                    new URLSearchParams();

                if (query) {
                    params.set(
                        'q',
                        query
                    );
                }

                if (showAll) {
                    params.set(
                        'show_all',
                        '1'
                    );
                }

                if (activeRoleFilter) {
                    params.set(
                        'role',
                        activeRoleFilter
                    );
                }

                params.set(
                    'page',
                    String(
                        patientCurrentPage
                    )
                );

                params.set(
                    'per_page',
                    String(
                        patientPageSize
                    )
                );

                if (showLoading) {
                    renderPatientSkeletons();
                }

                try {
                    const response =
                        await fetch(
                            `${searchEndpoint}?${params.toString()}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',

                                    Accept: 'application/json',
                                },

                                signal: patientFetchController
                                    .signal,
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            `Search failed with status ${response.status}`
                        );
                    }

                    const result =
                        await response.json();

                    if (
                        requestId !==
                        activeRequestId
                    ) {
                        return;
                    }

                    patientResponseCache.set(
                        cacheKey,
                        result
                    );

                    applyPatientResponse(
                        result
                    );

                } catch (error) {

                    if (
                        error.name ===
                        'AbortError'
                    ) {
                        return;
                    }

                    if (
                        requestId !==
                        activeRequestId
                    ) {
                        return;
                    }

                    console.error(
                        'Existing-record patient loading error:',
                        error
                    );

                    patientGrid.innerHTML =
                        '';

                    window.EmptyState?.render({
                        host: '#existingRecordEmptyState',

                        icon: 'fa-triangle-exclamation',

                        title: 'Unable to load patient records',

                        message: 'Patient records could not be loaded right now. Please try again.',
                    });
                }
            }

            function renderPatientPagination() {
                const top =
                    document.getElementById(
                        'existingRecordPaginationTop'
                    );

                const bottom =
                    document.getElementById(
                        'existingRecordPaginationBottom'
                    );

                const topBar =
                    document.getElementById(
                        'existingRecordPaginationTopBar'
                    );

                const bottomBar =
                    document.getElementById(
                        'existingRecordPaginationBottomBar'
                    );

                const topInfo =
                    document.getElementById(
                        'existingRecordPageInfoTop'
                    );

                const bottomInfo =
                    document.getElementById(
                        'existingRecordPageInfoBottom'
                    );

                window.renderGlobalPagination?.({
                    ...patientPaginationMeta,

                    containers: [
                        top,
                        bottom,
                    ],

                    bars: [
                        topBar,
                        bottomBar,
                    ],

                    infoElements: [
                        topInfo,
                        bottomInfo,
                    ],

                    itemLabel: 'patient records',

                    onPageChange(page) {
                        patientCurrentPage =
                            page;

                        const query =
                            input.value.trim();

                        loadPatients(
                            query,
                            query === ''
                        );

                        patientGrid
                            ?.scrollIntoView({
                                behavior: 'smooth',

                                block: 'start',
                            });
                    },
                });
            }

            window
                .handleExistingRecordPerPageChange =
                function(value) {
                    const allowed = [
                        10,
                        20,
                        50,
                        100,
                    ];

                    const requested =
                        Number(value);

                    patientPageSize =
                        allowed.includes(
                            requested
                        ) ?
                        requested :
                        10;

                    patientCurrentPage = 1;

                    const query =
                        input.value.trim();

                    loadPatients(
                        query,
                        query === ''
                    );
                };

            function escapeHtml(value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function buildRecordUrl(patient) {
                return recordUrlTemplate.replace(
                    '__PATIENT__',
                    encodeURIComponent(
                        String(patient.id || '')
                    )
                );
            }

            function renderPatients(patients) {
                if (!patientGrid) return;

                if (!patients.length) {
                    const query =
                        input.value.trim();

                    patientGrid.innerHTML = '';

                    if (query) {
                        window.EmptyState?.renderSearch({
                            host: '#existingRecordEmptyState',

                            input: '#patientSearchInput',

                            query,

                            message: 'Try a different name, ID, email, or program.',
                        });
                    } else {
                        window.EmptyState?.render({
                            host: '#existingRecordEmptyState',

                            icon: 'fa-user-slash',

                            title: 'No patient records found',

                            message: 'No patient records are currently available.',
                        });
                    }

                    return;
                }

                window.EmptyState?.hide(
                    '#existingRecordEmptyState'
                );

                const html = patients.map(function(patient) {
                    const patientName = patient.name || 'Patient';
                    const patientEmail = patient.email || '';
                    const patientType =
                        patient.type || 'Patient';

                    const avatarUrl =
                        window.PatientUI
                        ?.safeUrl(
                            patient.avatar_url
                        ) || '';

                    const patientInitials =
                        window.PatientUI
                        ?.getInitials(
                            patientName
                        ) || 'P';

                    const roleClass =
                        window.PatientUI
                        ?.getRoleClass(
                            patientType
                        ) || 'role-none';

                    const tags = [];

                    if (patient.student_number) {
                        tags.push(`
    <span class="global-info-pill">
        <i class="fa-regular fa-id-card"></i>
        ${escapeHtml(patient.student_number)}
    </span>
`);
                    }

                    if (patient.program) {
                        tags.push(`
    <span class="global-info-pill">
        <i class="fa-solid fa-graduation-cap"></i>
        ${escapeHtml(patient.program)}
    </span>
`);
                    }

                    return `
    <article class="global-record-card ${roleClass}">
        <div class="global-record-card-grid">

            <div class="global-record-profile">

                <span class="patient-avatar patient-avatar-md">
                    ${avatarUrl
                    ? `
                                    <img
                                        src="${escapeHtml(avatarUrl)}"
                                        alt="${escapeHtml(patientName)}"
                                        loading="lazy"
                                        onerror="
                                            this.parentElement.innerHTML =
                                            '<span>${escapeHtml(patientInitials)}</span>';
                                        "
                                    >
                                `
                    : `
                                    <span>
                                        ${escapeHtml(patientInitials)}
                                    </span>
                                `
                }
                </span>

                <div class="global-record-identity">

                    <div class="global-record-name-row">
                        <p
                            class="global-record-name"
                            data-patient-name>
                            ${escapeHtml(patientName)}
                        </p>

                        <span class="badge-role ${roleClass}">
                            ${escapeHtml(patientType)}
                        </span>
                    </div>

                    ${patientEmail
                    ? `
                                <div class="global-record-subline">
                                    <span class="ui-muted-text">
                                        <i class="fa-regular fa-envelope"></i>
                                        ${escapeHtml(patientEmail)}
                                    </span>
                                </div>
                            `
                    : ''
                }

                </div>

            </div>

            ${tags.length
                    ? `
                            <div class="global-record-subline">
                                ${tags.join('')}
                            </div>
                        `
                    : ''
                }

            <div class="global-record-footer">
                <a href="${escapeHtml(buildRecordUrl(patient))}"
                    class="ui-btn ui-btn-primary ui-btn-sm">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    <span>Add Existing Appointment</span>
                </a>
            </div>

        </div>
    </article>
`;
                }).join('');

                if (
                    typeof window
                    .swapSkeletonContent ===
                    'function'
                ) {
                    window.swapSkeletonContent(
                        'patientGrid',
                        html
                    );
                } else {
                    patientGrid.innerHTML =
                        html;
                }
            }
            loadPatients(
                '',
                true, {
                    showLoading: true
                }
            );

            input.focus();
        });
    </script>
@endsection
