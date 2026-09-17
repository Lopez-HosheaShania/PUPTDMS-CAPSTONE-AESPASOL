@extends('layouts.app')

@php
$layoutRole ??= 'dentist';

$isAdminView = $layoutRole === 'admin';

$patientSearchRoute ??=
'dentist.walk-in.search-patient';

$existingAppointmentRoute ??=
'dentist.odontogram.existing-appointment.create';
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

            <div class="tab-group" aria-label="Filter patients by role">

                <button type="button" class="tab-btn active" data-patient-role-filter="">
                    All
                </button>

                <button type="button" class="tab-btn" data-patient-role-filter="patient">
                    Patient
                </button>

                <button type="button" class="tab-btn" data-patient-role-filter="faculty">
                    Faculty
                </button>

                <button type="button" class="tab-btn" data-patient-role-filter="admin">
                    Administrative
                </button>

            </div>
        </div>

        <div class="voice-search-row">
            <x-search-bar id="patientSearchInput" placeholder="Search by name, ID, email, or program..."
                callback="handleExistingRecordSearch" :debounce="300" clear-label="Clear patient search"
                class="flex-1" />

            <x-voice-input target="#patientSearchInput" status-id="existingRecordVoiceStatus" label="Use voice search"
                title="Voice search" />
        </div>

        <x-pagination-bar id="existingRecordPaginationTopBar" info-id="existingRecordPageInfoTop"
            pagination-id="existingRecordPaginationTop" position="top" :show-entries="true"
            page-size-id="existingRecordPerPage" page-size-callback="handleExistingRecordPerPageChange"
            label="patient records" />

        <div id="patientGrid" class="table-record-grid existing-record-patient-grid" aria-live="polite">
        </div>

        <x-pagination-bar id="existingRecordPaginationBottomBar" info-id="existingRecordPageInfoBottom"
            pagination-id="existingRecordPaginationBottom" position="bottom" label="patient records" hidden />

        <div id="existingRecordEmptyState" class="empty-state-host"></div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('patientSearchInput');
        const patientGrid = document.getElementById('patientGrid');
        const searchEndpoint =
            @json(route($patientSearchRoute));

        const recordUrlTemplate =
            @json(route(
                $existingAppointmentRoute,
                ['patient' => '__PATIENT__']));

        const resolveExternalPatientEndpoint =
            @json(route('shared.existing-record.resolve-external-patient'));

        const csrfToken =
            @json(csrf_token());

    window.initGlobalSearchBars?.();
    window.initGlobalVoiceInputs?.();

    let activeRequestId = 0;
    let patientFetchController = null;
    let renderedPatients = [];

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
        function (value) {
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
            .from(
                { length: skeletonCount },
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

    const roleFilterButtons =
        document.querySelectorAll(
            '[data-patient-role-filter]'
        );

    let activeRoleFilter = '';

    roleFilterButtons.forEach(
        button => {
            button.addEventListener(
                'click',
                () => {
                    activeRoleFilter =
                        button.dataset
                            .patientRoleFilter ||
                        '';

                    roleFilterButtons
                        .forEach(item => {
                            item.classList.toggle(
                                'active',
                                item === button
                            );
                        });

                    patientCurrentPage = 1;

                    const query =
                        input.value.trim();

                    loadPatients(
                        query,
                        query === ''
                    );
                }
            );
        }
    );

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
            Array.isArray(result)
                ? result
                : Array.isArray(result?.data)
                    ? result.data
                    : [];

        patientPaginationMeta = {
            currentPage:
                Number(result?.current_page) || 1,

            lastPage:
                Number(result?.last_page) || 1,

            total:
                Number(result?.total) ||
                normalizedPatients.length,

            from:
                result?.from ??
                (
                    normalizedPatients.length
                        ? (
                            (
                                Number(
                                    result?.current_page ||
                                    patientCurrentPage
                                ) - 1
                            ) * patientPageSize
                        ) + 1
                        : null
                ),

            to:
                result?.to ??
                (
                    normalizedPatients.length
                        ? (
                            (
                                Number(
                                    result?.current_page ||
                                    patientCurrentPage
                                ) - 1
                            ) * patientPageSize
                        ) +
                        normalizedPatients.length
                        : null
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
                    `${searchEndpoint}?${params.toString()}`,
                    {
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',

                            Accept:
                                'application/json',
                        },

                        signal:
                            patientFetchController
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
                host:
                    '#existingRecordEmptyState',

                icon:
                    'fa-triangle-exclamation',

                title:
                    'Unable to load patient records',

                message:
                    'Patient records could not be loaded right now. Please try again.',
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

            itemLabel:
                'patient records',

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
                        behavior:
                            'smooth',

                        block:
                            'start',
                    });
            },
        });
    }

    window
        .handleExistingRecordPerPageChange =
        function (value) {
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
                )
                    ? requested
                    : 10;

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

    async function openExistingRecord(patient, button) {
        if (!patient) {
            return;
        }

        const isExternal =
            patient.is_local === false ||
            String(patient.id || '')
                .startsWith('external:');

        if (!isExternal) {
            window.location.href =
                buildRecordUrl(patient);

            return;
        }

        if (!patient.selection_token) {
            window.showToast?.({
                type: 'error',
                title: 'Unable to Select Patient',
                message:
                    'The selected patient could not be verified. Please search for the patient again.',
            });

            return;
        }

        const originalButtonHtml =
            button?.innerHTML || '';

        if (button) {
            button.disabled = true;
            button.setAttribute(
                'aria-busy',
                'true'
            );

            button.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Preparing...</span>
            `;
        }

        try {
            const response =
                await fetch(
                    resolveExternalPatientEndpoint,
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            Accept:
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                csrfToken,
                        },

                        body: JSON.stringify({
                            selection_token:
                                patient.selection_token,
                        }),
                    }
                );

            let result = {};

            try {
                result =
                    await response.json();
            } catch (_) {
                result = {};
            }

            if (
                !response.ok ||
                !result.success ||
                !result.patient?.id
            ) {
                throw new Error(
                    result.message ||
                    'Unable to prepare the selected patient.'
                );
            }

            window.location.href =
                buildRecordUrl(
                    result.patient
                );

        } catch (error) {
            console.error(
                'Existing-record patient resolution error:',
                error
            );

            window.showToast?.({
                type: 'error',
                title:
                    'Unable to Select Patient',

                message:
                    error.message ||
                    'Unable to prepare the selected patient right now.',
            });

            if (button) {
                button.disabled = false;
                button.removeAttribute(
                    'aria-busy'
                );

                button.innerHTML =
                    originalButtonHtml;
            }
        }
    }

    patientGrid?.addEventListener(
        'click',
        function (event) {
            const button =
                event.target.closest(
                    '[data-existing-record-patient-index]'
                );

            if (
                !button ||
                !patientGrid.contains(button)
            ) {
                return;
            }

            const index =
                Number(
                    button.dataset
                        .existingRecordPatientIndex
                );

            const patient =
                renderedPatients[index];

            if (!patient) {
                return;
            }

            openExistingRecord(
                patient,
                button
            );
        }
    );

    function renderPatients(patients) {
        if (!patientGrid) return;

        renderedPatients =
            Array.isArray(patients)
                ? patients
                : [];

        if (!renderedPatients.length) {
            const query =
                input.value.trim();

            patientGrid.innerHTML = '';

            if (query) {
                window.EmptyState?.renderSearch({
                    host:
                        '#existingRecordEmptyState',

                    input:
                        '#patientSearchInput',

                    query,

                    message:
                        'Try a different name, ID, email, or program.',
                });
            } else {
                window.EmptyState?.render({
                    host:
                        '#existingRecordEmptyState',

                    icon:
                        'fa-user-slash',

                    title:
                        'No patient records found',

                    message:
                        'No patient records are currently available.',
                });
            }

            return;
        }

        window.EmptyState?.hide(
            '#existingRecordEmptyState'
        );

        const html = renderedPatients.map(function (patient, index) {
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
                <button
                    type="button"
                    class="ui-btn ui-btn-primary ui-btn-sm"
                    data-existing-record-patient-index="${index}">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    <span>Add Existing Appointment</span>
                </button>
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
        true,
        {
            showLoading: true
        }
    );

    input.focus();
});
</script>
@endsection