@extends('layouts.app')

@php
$role =
$role ??
(session('impersonated_role') ??
(session('role') ?? (optional(optional(auth()->user())->role)->slug ?? 'admin')));

$isAdmin = $role === 'admin';
$isDentist = $role === 'dentist';
@endphp

@section('layout-role', $role)

@section('title', 'Document Requests')

@section('styles')
@vite('resources/css/pages/shared/document-requests.css')
@endsection

@section('content')

@php
$docRequestsSource = $requests ?? collect();

if (is_object($docRequestsSource) && method_exists($docRequestsSource, 'getCollection')) {
$docRequestsCollection = $docRequestsSource->getCollection();
} else {
$docRequestsCollection = collect($docRequestsSource);
}

$normalizeStatus = function ($status) {
$status = strtolower(str_replace('_', '-', (string) ($status ?: 'pending')));

if (in_array($status, ['ready', 'ready-for-pickup', 'ready-for-release', 'released'], true)) {
return 'approved';
}

if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
return 'pending';
}

return $status;
};

$formatDocumentType = function ($type) {
return ucwords(str_replace(['_', '-'], ' ', (string) ($type ?: 'Document')));
};

$docRequestsPayload = $docRequestsCollection->map(function ($req) use ($normalizeStatus, $formatDocumentType) {
$patient = $req->patient ?? null;
$createdAt = $req->created_at ?? now();

$patientName = data_get($patient, 'name') ?? 'Unknown Patient';
$studentNumber = trim((string) data_get($patient, 'student_no', ''));
$facultyCode = trim((string) data_get($patient, 'faculty_code', ''));

$patientIdentifier =
$studentNumber !== ''
? $studentNumber
: ($facultyCode !== ''
? 'Faculty: ' . $facultyCode
: 'No identity number');

$documentLabel = $formatDocumentType($req->document_type ?? 'Document');
$status = $normalizeStatus($req->status ?? 'pending');

return [
'id' => $req->id,
'reference_number' =>
$req->reference_number ?? 'DR-' . str_pad((string) $req->id, 5, '0', STR_PAD_LEFT),
'patient_name' => $patientName,
'patient_identifier' => $patientIdentifier,
'sub_label' => $patientIdentifier,
'document_type' => $documentLabel,
'document_type_raw' => $req->document_type ?? $documentLabel,
'purpose' => $req->purpose ?: '—',
'status' => $status,
'request_date' => optional($createdAt)->format('M d, Y') ?? '—',
'request_time' => optional($createdAt)->format('h:i A') ?? '',
'request_sort_date' => optional($createdAt)->format('Y-m-d H:i:s') ?? '',
'filter_date' => optional($createdAt)->format('Y-m-d') ?? '',
'copies_needed' => $req->copies_needed ?? 1,
'patient_photo_url' =>
data_get($patient, 'profile_photo_url') ??
(data_get($patient, 'profile_picture_url') ??
(data_get($patient, 'avatar_url') ?? (data_get($patient, 'photo_url') ?? ''))),
];
});

$statsSource = $stats ?? [];

$countByStatus = function ($status) use ($docRequestsPayload) {
return $docRequestsPayload->filter(fn($req) => ($req['status'] ?? 'pending') === $status)->count();
};

$docRequestStats = [
'all' => $docRequestsPayload->count(),
'pending' => $statsSource['pending'] ?? $countByStatus('pending'),
'approved' => $statsSource['approved'] ?? $countByStatus('approved'),
'rejected' => $statsSource['rejected'] ?? $countByStatus('rejected'),
];

$docRequestStatusOptions = [
[
'value' => 'all',
'label' => 'All Requests',
'icon' => 'fa-layer-group',
'tone' => 'status-all',
'count' => $docRequestStats['all'] ?? 0,
],
[
'value' => 'pending',
'label' => 'Pending',
'icon' => 'fa-clock-rotate-left',
'tone' => 'status-pending',
'count' => $docRequestStats['pending'] ?? 0,
],
[
'value' => 'approved',
'label' => 'Approved',
'icon' => 'fa-file-circle-check',
'tone' => 'status-approved',
'count' => $docRequestStats['approved'] ?? 0,
],
[
'value' => 'rejected',
'label' => 'Rejected',
'icon' => 'fa-file-circle-xmark',
'tone' => 'status-rejected',
'count' => $docRequestStats['rejected'] ?? 0,
],
];

$requestableDocumentTypes = collect([
'Dental Clearance',
'Annual Dental Clearance',
'All Dental Records',
'Medical Records',
'Diagnosis and Treatment',
])
->map(fn($type) => $formatDocumentType($type))
->filter()
->unique(fn($type) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $type))))
->values();

$defaultDocumentTypes = $requestableDocumentTypes;
$docRequestTypes = $requestableDocumentTypes;

$perPage =
$perPage ??
(is_object($requests ?? null) && method_exists($requests, 'perPage') ? $requests->perPage() : 10);

$docRequestPagination = [
'total' =>
is_object($requests ?? null) && method_exists($requests, 'total')
? $requests->total()
: $docRequestsPayload->count(),
'from' =>
is_object($requests ?? null) && method_exists($requests, 'firstItem')
? $requests->firstItem() ?? 0
: ($docRequestsPayload->count()
? 1
: 0),
'to' =>
is_object($requests ?? null) && method_exists($requests, 'lastItem')
? $requests->lastItem() ?? 0
: $docRequestsPayload->count(),
'current_page' =>
is_object($requests ?? null) && method_exists($requests, 'currentPage') ? $requests->currentPage() : 1,
'last_page' =>
is_object($requests ?? null) && method_exists($requests, 'lastPage') ? $requests->lastPage() : 1,
'per_page' => $perPage,
];
@endphp

<main id="mainContent" class="app-page-shell page-enter docreq-page mode-list" data-document-request-role="{{ $role }}">
    <div>

        <div class="docreq-header-wrap mb-5">
            @if ($isDentist)
            <div class="dentist-hero">
                <div class="dentist-hero-content">
                    <div class="dentist-hero-icon">
                        <i class="fa-solid fa-file-medical"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="dentist-hero-eyebrow">
                            <i class="fa-solid fa-tooth"></i>
                            Document Management
                        </div>

                        <h2 class="dentist-hero-title">
                            Document Requests
                        </h2>
                    </div>
                </div>
            </div>
            @else
            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">
                            Document Requests
                        </h1>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="table-card">

            <div class="table-toolbar">

                <div class="table-toolbar-search">
                    <div class="voice-search-row">
                        <x-search-bar id="searchInput" placeholder="Search document"
                            callback="handleDocumentRequestSearch" :debounce="350" />

                        <x-voice-input target="#searchInput" status-id="documentRequestVoiceStatus"
                            label="Use voice search" title="Voice search" />
                    </div>
                </div>

                <div class="table-toolbar-actions">
                    <x-filter-select id="docreqStatusFilter" name="document_request_status" label="Status" value=""
                        :options="$docRequestStatusOptions" callback="handleDocumentRequestStatusSelect" searchable
                        multiple search-placeholder="Search status..." />

                    <button id="filterBtn" type="button" onclick="openFilterModal()" class="global-filter-btn"
                        data-tooltip="Filter" data-tooltip-tone="neutral" aria-label="Filter requests">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Filter</span>
                        <span id="filterBadge" class="filter-badge" style="display:none;"></span>
                    </button>

                    <x-view-toggle id="documentRequestsViewToggle" root="#mainContent"
                        storage-key="documentRequestsViewMode" list-label="List" grid-label="Grid" />

                    <button id="externalClearFilterBtn" type="button" onclick="resetAdvancedFilters()"
                        class="global-filter-reset-btn hidden" aria-label="Reset filters" data-tooltip="Reset filters"
                        data-tooltip-tone="neutral">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>

            </div>

            <x-pagination-bar id="docreqPaginationTopBar" info-id="pageInfoTop" pagination-id="pagControlsTop"
                position="top" :show-entries="true" page-size-id="docreqPerPageSelect"
                page-size-callback="selectDocreqPerPage" :page-size-value="$perPage" page-size-label="per page"
                label="requests" class="docreq-pagebar docreq-pagebar-top" :total="$docRequestPagination['total']"
                :from="$docRequestPagination['from']" :to="$docRequestPagination['to']" />

            <div id="requestListView" class="table-list-view">
                <div class="table-scroll-wrapper docreq-desktop-list-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Date Requested</th>
                                <th>Document</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th class="table-cell-center">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody id="requestListContainer">
                        </tbody>
                    </table>
                </div>

                <div id="requestResponsiveListContainer" class="docreq-responsive-list" aria-live="polite"></div>
            </div>

            <div id="requestGridView" class="table-grid-view" hidden>
                <div id="requestGridContainer" class="table-record-grid">
                </div>
            </div>

            <div id="docreqEmptyState" class="empty-state-host"></div>

            <x-pagination-bar id="documentRequestsPagebarBottom" info-id="documentRequestsPageInfoBottom"
                pagination-id="documentRequestsPaginationBottom" position="bottom" :show-entries="false"
                label="requests" :total="$docRequestPagination['total']" :from="$docRequestPagination['from']"
                :to="$docRequestPagination['to']" />

        </div>
    </div>
</main>

<x-filter-drawer id="filterModal" title="Filters" close-id="filterCancelBtn" clear-id="filterResetBtn"
    clear-label="Clear Filters" cancel-id="filterCloseBtn" cancel-label="Cancel" apply-id="filterApplyBtn"
    apply-label="Show results" results-id="filterResultsText" class="docreq-filter-sheet">

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

    <x-filter-group title="Sort By">
        <div id="fSortGroup" class="filter-chip-row">
            <button type="button" class="ftag ftag-active" data-val="newest">
                Newest First
            </button>

            <button type="button" class="ftag" data-val="oldest">
                Oldest First
            </button>

            <button type="button" class="ftag" data-val="az">
                Patient Name A-Z
            </button>

            <button type="button" class="ftag" data-val="za">
                Patient Name Z-A
            </button>
        </div>
    </x-filter-group>

    <x-filter-group title="Document Details">

        <div data-global-selects>
            <select id="fDocType" class="js-custom-select" aria-label="Document type">
                <option value="">
                    All document types
                </option>

                @foreach ($docRequestTypes as $type)
                <option value="{{ $type }}">
                    {{ $type }}
                </option>
                @endforeach
            </select>
        </div>

    </x-filter-group>

    <x-filter-group title="Filter by Date Range">

        <div id="datePresetGroup" class="filter-chip-row">
            <button type="button" class="quick-date-chip" data-range="7">
                Last 7 Days
            </button>

            <button type="button" class="quick-date-chip" data-range="30">
                Last 30 Days
            </button>

            <button type="button" class="quick-date-chip" data-range="90">
                Last 3 Months
            </button>

            <button type="button" class="quick-date-chip" data-range="180">
                Last 6 Months
            </button>

            <button type="button" class="quick-date-chip" data-range="365">
                Last 12 Months
            </button>
        </div>

    </x-filter-group>

    <x-filter-group title="Custom Date Range" class="filter-group-last">

        <div class="filter-date-grid">

            <div class="filter-date-input-wrap">
                <input type="text" id="fDateFrom" class="form-input-custom js-flatpickr-date-range-from" placeholder="Start date" readonly
                    autocomplete="off">

                <i class="fa-regular fa-calendar"></i>
            </div>

            <div class="filter-date-input-wrap">
                <input type="text" id="fDateTo" class="form-input-custom js-flatpickr-date-range-to" placeholder="End date" readonly
                    autocomplete="off">

                <i class="fa-regular fa-calendar"></i>
            </div>

        </div>

    </x-filter-group>

</x-filter-drawer>

<div id="approveModal" class="ui-modal modal-theme-success" aria-hidden="true">

    <div class="ui-modal-card modal-md" onclick="event.stopPropagation()">

        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>

                <div class="modal-copy">
                    <h3 class="modal-title">Approve Request</h3>

                    <p class="modal-subtitle">
                        Review the request details before confirming.
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-bd">
            <div class="global-info-profile">
                <div class="global-info-profile-icon">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="global-info-profile-copy">
                    <span class="global-info-label">Patient</span>

                    <strong id="approvePatientName" class="global-info-profile-name" data-patient-name>
                        —
                    </strong>
                </div>
            </div>

            <div class="global-info-grid global-info-grid-2">
                <div class="global-info-item">
                    <div class="global-info-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </div>

                    <div class="global-info-copy">
                        <span class="global-info-label">Date &amp; Time</span>
                        <strong id="approveRequestDate" class="global-info-value">—</strong>
                        <small id="approveRequestTime" class="global-info-subvalue">—</small>
                    </div>
                </div>

                <div class="global-info-item">
                    <div class="global-info-icon">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>

                    <div class="global-info-copy">
                        <span class="global-info-label">Document</span>
                        <strong id="approveRequestDocument" class="global-info-value">—</strong>
                    </div>
                </div>

                <div class="global-info-item global-info-item-wide">
                    <div class="global-info-icon">
                        <i class="fa-solid fa-message"></i>
                    </div>

                    <div class="global-info-copy">
                        <span class="global-info-label">Purpose</span>
                        <strong id="approveRequestPurpose" class="global-info-value">—</strong>
                    </div>
                </div>
            </div>

            <div class="global-confirm-alert">
                <i class="fa-solid fa-circle-info"></i>

                <div>
                    <p>Confirm this document request?</p>
                    <span>
                        The request will be marked as approved and queued
                        for processing.
                    </span>
                </div>
            </div>
        </div>

        <div class="modal-ft">
            <button type="button" class="ui-btn ui-btn-secondary" id="approveCancelBtn2">
                Cancel
            </button>

            <button type="button" class="ui-btn ui-btn-success" id="approveConfirmBtn">
                <i class="fa-solid fa-check"></i>
                <span>Confirm Approval</span>
            </button>
        </div>
    </div>
</div>

<input type="hidden" id="approveRequestId">


<div id="rejectModal" class="ui-modal modal-theme-danger" aria-hidden="true">

    <div class="ui-modal-card modal-md" onclick="event.stopPropagation()">

        <form id="rejectRequestForm" data-global-validation data-discard-form novalidate>

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-file-circle-xmark"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">Reject Request</h3>

                        <p class="modal-subtitle">
                            Review the request and provide a reason when needed.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="rejectModal" aria-label="Close reject modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="global-info-profile">
                    <div class="global-info-profile-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div class="global-info-profile-copy">
                        <span class="global-info-label">Patient</span>

                        <strong id="rejectPatientName" class="global-info-profile-name" data-patient-name>
                            —
                        </strong>
                    </div>
                </div>

                <div class="global-info-grid global-info-grid-2">
                    <div class="global-info-item">
                        <div class="global-info-icon">
                            <i class="fa-regular fa-calendar"></i>
                        </div>

                        <div class="global-info-copy">
                            <span class="global-info-label">Date &amp; Time</span>
                            <strong id="rejectRequestDate" class="global-info-value">—</strong>
                            <small id="rejectRequestTime" class="global-info-subvalue">—</small>
                        </div>
                    </div>

                    <div class="global-info-item">
                        <div class="global-info-icon">
                            <i class="fa-regular fa-file-lines"></i>
                        </div>

                        <div class="global-info-copy">
                            <span class="global-info-label">Document</span>
                            <strong id="rejectRequestDocument" class="global-info-value">—</strong>
                        </div>
                    </div>

                    <div class="global-info-item global-info-item-wide">
                        <div class="global-info-icon">
                            <i class="fa-solid fa-message"></i>
                        </div>

                        <div class="global-info-copy">
                            <span class="global-info-label">Purpose</span>
                            <strong id="rejectRequestPurpose" class="global-info-value">—</strong>
                        </div>
                    </div>
                </div>

                <div class="global-form-group" data-global-field>
                    <div class="global-label-row">
                        <label class="global-form-label" for="rejectNotes">
                            Reason for rejection
                            <span>(optional)</span>
                        </label>

                        <div id="rejectNotesCharCounter" class="char-counter">
                            0 / 150 characters
                        </div>
                    </div>

                    <div class="global-textarea-field" data-clearable-field data-voice-field>
                        <textarea id="rejectNotes" name="reason" class="form-input-custom global-form-textarea" rows="4"
                            maxlength="150" data-char-limit="150" data-char-counter="#rejectNotesCharCounter"
                            data-field-label="Reason for rejection" data-clearable-input
                            placeholder="Provide a clear reason for rejecting the request…"></textarea>

                        <button type="button" id="rejectNotesClearBtn"
                            class="search-clear field-clear-btn field-clear-btn--textarea" data-field-clear
                            aria-label="Clear rejection reason" title="Clear rejection reason">

                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="global-textarea-tools">
                        <x-voice-input target="#rejectNotes" status-id="rejectNotesVoiceStatus"
                            label="Voice input for rejection reason" title="Voice input" />
                    </div>

                    <div class="global-field-error" data-error-for="rejectNotes" aria-hidden="true">
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="rejectModal">
                    Cancel
                </button>

                <button type="submit" class="ui-btn ui-btn-danger" id="rejectConfirmBtn">
                    <i class="fa-solid fa-ban"></i>
                    <span>Confirm Rejection</span>
                </button>
            </div>
        </form>
    </div>
</div>

<input type="hidden" id="rejectRequestId">
@endsection

@section('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const ADMIN_DOC_REQUESTS = @json($docRequestsPayload -> values());
    const ADMIN_DOC_STATS = @json($docRequestStats);
    const ADMIN_DOC_TYPES = @json($docRequestTypes -> values());
    const DOCREQ_DEFAULT_DOC_TYPES = @json($defaultDocumentTypes -> values());

    let allRequests = Array.isArray(ADMIN_DOC_REQUESTS) ? ADMIN_DOC_REQUESTS : [];

    function normalizeDocreqStatusValues(value) {
        const rawValues = Array.isArray(value)
            ? value
            : String(value || 'all').split(',');

        const allowed = [
            'all',
            'pending',
            'approved',
            'rejected'
        ];

        const values = rawValues
            .map(item => String(item || '').trim().toLowerCase())
            .filter(item => allowed.includes(item));

        if (!values.length || values.includes('all')) {
            return ['all'];
        }

        return [...new Set(values)];
    }

    function serializeDocreqStatusValues(value) {
        const values = normalizeDocreqStatusValues(value);
        return values.includes('all') ? 'all' : values.join(',');
    }

    let activeFilter = normalizeDocreqStatusValues(
        @json(request('status', 'all') ?: 'all')
    );

    const DOCREQ_DATA_URL = `${window.location.pathname.replace(/\/$/, '')}/data`;

    let docreqRefreshWatcher = null;

    let searchQuery = '';
    let currentViewMode = 'list';

    const DOCREQ_ROUTES = @json($routes ?? []);
    const DOCREQ_METHODS = @json($methods ?? []);
    const DOCREQ_REFRESH_ITEMS = @json($refreshItems ?? []);
    const DOCREQ_INDEX_URL = DOCREQ_ROUTES.index || window.location.pathname;
    const DOCREQ_INITIAL_PAGINATION = @json($docRequestPagination);
    const DOCREQ_REFRESH_KEY =
        @json(
            $isDentist
                ? 'dentist-docreq'
                : 'admin-docreq'
        );

    let docreqPagination = DOCREQ_INITIAL_PAGINATION || {
        total: allRequests.length,
        from: allRequests.length ? 1 : 0,
        to: allRequests.length,
        current_page: 1,
        last_page: 1,
        per_page: 10
    };

    let docreqPerPage = Number(docreqPagination.per_page || 10);
    let currentPage = Number(docreqPagination.current_page || 1);
    let filterStatus = [...activeFilter];
    let filterDocType = '';
    let filterDateFrom = '';
    let filterDateTo = '';
    let filterSort = 'newest';
    let documentTypeOptions = [];

    function docreqToast(type, title, message) {
        if (typeof window.showToast === 'function') {
            window.showToast({
                type,
                title,
                message,
                duration: 5000
            });
            return;
        }

        alert(`${title}\n${message}`);
    }

    function normalizeDocreqStatus(status) {
        const normalized = String(status || 'pending').replace(/_/g, '-').toLowerCase();

        if (['ready', 'ready-for-pickup', 'ready-for-release', 'released'].includes(normalized)) {
            return 'approved';
        }

        if (!['pending', 'approved', 'rejected'].includes(normalized)) {
            return 'pending';
        }

        return normalized;
    }

    function normalizeDocreqRequest(request) {
        return {
            ...request,
            status: normalizeDocreqStatus(request.status)
        };
    }

    function recalculateDocreqStats(source = allRequests) {
        const normalized = source.map(normalizeDocreqRequest);

        return {
            all: normalized.length,
            pending: normalized.filter((request) => request.status === 'pending').length,
            approved: normalized.filter((request) => request.status === 'approved').length,
            rejected: normalized.filter((request) => request.status === 'rejected').length
        };
    }

    function syncLocalDocRequestStatus(id, status, extra = {}) {
        const normalizedStatus = normalizeDocreqStatus(status);

        allRequests = allRequests.map((request) => {
            if (Number(request.id) !== Number(id)) return request;

            return normalizeDocreqRequest({
                ...request,
                ...extra,
                status: normalizedStatus
            });
        });

        updateStats(
            recalculateDocreqStats()
        );

        renderList();
    }

    function applyDocreqServerSnapshot(
        payload
    ) {
        if (
            !payload ||
            !Array.isArray(payload.requests)
        ) {
            return;
        }

        allRequests =
            payload.requests.map(
                normalizeDocreqRequest
            );

        documentTypeOptions =
            normalizeDocTypes(
                Array.isArray(payload.types) &&
                    payload.types.length ?
                    payload.types :
                    allRequests.map(
                        request =>
                            request.document_type
                    )
            );

        updateStats(
            payload.stats ||
            recalculateDocreqStats()
        );

        renderList();
    }

    function initDocreqRefreshWatcher() {
        if (!window.initGlobalRefreshWatcher) {
            return;
        }

        docreqRefreshWatcher =
            window.initGlobalRefreshWatcher({
                key:
                    DOCREQ_REFRESH_KEY,

                url:
                    DOCREQ_DATA_URL,

                initialItems:
                    DOCREQ_REFRESH_ITEMS,

                anchorSelector:
                    '#mainContent.docreq-page .table-card',

                itemLabel:
                    'document request',

                getItems(payload) {
                    if (Array.isArray(payload)) {
                        return payload;
                    }

                    return Array.isArray(
                        payload?.requests
                    )
                        ? payload.requests
                        : [];
                },

                getItemId(request) {
                    return request?.id;
                },

                title(count) {
                    return `${count} new document request${count === 1 ? '' : 's'
                        } available`;
                },

                subtitle(count) {
                    return `Refresh to see the latest request${count === 1 ? '' : 's'
                        }.`;
                },

                onRefresh() {
                    currentPage = 1;

                    fetchDocRequests();
                },

                toast: {
                    type: 'info',
                    title:
                        'Document requests updated',

                    message:
                        'Latest document requests are now shown.'
                }
            });
    }

    function loadData() {
        allRequests =
            Array.isArray(ADMIN_DOC_REQUESTS)
                ? ADMIN_DOC_REQUESTS.map(
                    normalizeDocreqRequest
                )
                : [];

        documentTypeOptions =
            normalizeDocTypes(
                Array.isArray(ADMIN_DOC_TYPES) &&
                    ADMIN_DOC_TYPES.length
                    ? ADMIN_DOC_TYPES
                    : allRequests.map(
                        request =>
                            request.document_type
                    )
            );

        if (
            filterDocType &&
            !documentTypeOptions.includes(
                filterDocType
            )
        ) {
            filterDocType = '';
        }

        updateStats(
            ADMIN_DOC_STATS || {}
        );

        renderDocreqPagebars(
            docreqPagination
        );

        renderList();
    }

    function buildDocRequestSkeletonHtml(
        count = 4
    ) {
        return Array.from(
            { length: count },
            () => `
        <article
            class="
                skeleton-shell
                docreq-skeleton-card
            ">

            <div
                class="
                    docreq-skeleton-profile
                ">

                <span
                    class="
                        skeleton-circle
                        docreq-skeleton-avatar
                    ">
                </span>

                <div
                    class="
                        docreq-skeleton-copy
                    ">

                    <span
                        class="
                            skeleton-line
                            docreq-skeleton-name
                        ">
                    </span>

                    <span
                        class="
                            skeleton-line
                            docreq-skeleton-meta
                        ">
                    </span>
                </div>
            </div>

            <div
                class="
                    docreq-skeleton-details
                ">

                <span
                    class="skeleton-block">
                </span>

                <span
                    class="skeleton-block">
                </span>

                <span
                    class="skeleton-block">
                </span>
            </div>

            <div
                class="
                    docreq-skeleton-actions
                ">

                <span
                    class="skeleton-circle">
                </span>

                <span
                    class="skeleton-circle">
                </span>
            </div>
        </article>
    `
        ).join('');
    }

    function buildDocRequestListSkeletonHtml(
        count = 4
    ) {
        return Array.from(
            { length: count },
            () => `
        <tr class="docreq-skeleton-table-row" aria-hidden="true">

            <td class="table-cell-main">
                <div class="docreq-skeleton-table-patient">

                    <span
                        class="
                            skeleton-circle
                            docreq-skeleton-table-avatar
                        ">
                    </span>

                    <div class="docreq-skeleton-table-copy">
                        <span
                            class="
                                skeleton-line
                                docreq-skeleton-table-name
                            ">
                        </span>

                        <span
                            class="
                                skeleton-pill
                                docreq-skeleton-table-id
                            ">
                        </span>
                    </div>

                </div>
            </td>

            <td>
                <div class="docreq-skeleton-table-date">
                    <span class="skeleton-line"></span>
                    <span class="skeleton-line"></span>
                </div>
            </td>

            <td>
                <span
                    class="
                        skeleton-block
                        docreq-skeleton-table-document
                    ">
                </span>
            </td>

            <td>
                <span
                    class="
                        skeleton-block
                        docreq-skeleton-table-purpose
                    ">
                </span>
            </td>

            <td>
                <span
                    class="
                        skeleton-block
                        docreq-skeleton-table-status
                    ">
                </span>
            </td>

            <td class="table-action-cell">
                <div class="docreq-skeleton-table-actions">

                    <span
                        class="
                            skeleton-circle
                            docreq-skeleton-table-action
                        ">
                    </span>

                    <span
                        class="
                            skeleton-circle
                            docreq-skeleton-table-action
                        ">
                    </span>

                </div>
            </td>

        </tr>
    `
        ).join('');
    }

    function buildDocRequestResponsiveListSkeletonHtml(
        count = 4
    ) {
        return Array.from(
            { length: count },
            () => `
        <article class="docreq-responsive-record docreq-responsive-skeleton" aria-hidden="true">
            <div class="docreq-responsive-record-head">
                <div class="table-primary docreq-responsive-patient">
                    <span class="skeleton-circle docreq-skeleton-table-avatar"></span>

                    <div class="docreq-responsive-patient-copy">
                        <span class="skeleton-line docreq-skeleton-table-name"></span>
                        <span class="skeleton-pill docreq-skeleton-table-id"></span>
                    </div>
                </div>

                <span class="skeleton-pill docreq-skeleton-table-status"></span>
            </div>

            <div class="docreq-responsive-meta">
                <div class="docreq-responsive-field">
                    <span class="skeleton-line docreq-responsive-skeleton-label"></span>
                    <span class="skeleton-block docreq-responsive-skeleton-value"></span>
                </div>

                <div class="docreq-responsive-field">
                    <span class="skeleton-line docreq-responsive-skeleton-label"></span>
                    <span class="skeleton-block docreq-responsive-skeleton-value"></span>
                </div>

                <div class="docreq-responsive-field">
                    <span class="skeleton-line docreq-responsive-skeleton-label"></span>
                    <span class="skeleton-block docreq-responsive-skeleton-value docreq-responsive-skeleton-value-short"></span>
                </div>

                <div class="docreq-responsive-field">
                    <span class="skeleton-line docreq-responsive-skeleton-label"></span>
                    <span class="skeleton-block docreq-responsive-skeleton-value"></span>
                </div>
            </div>

            <div class="docreq-responsive-actions">
                <div class="docreq-skeleton-table-actions">
                    <span class="skeleton-circle docreq-skeleton-table-action"></span>
                    <span class="skeleton-circle docreq-skeleton-table-action"></span>
                </div>
            </div>
        </article>
    `
        ).join('');
    }

    function showSkeleton() {
        const listView =
            document.getElementById('requestListView');

        const gridView =
            document.getElementById('requestGridView');

        const listContainer =
            document.getElementById('requestListContainer');

        const responsiveListContainer =
            document.getElementById('requestResponsiveListContainer');

        const gridContainer =
            document.getElementById('requestGridContainer');

        const isGrid =
            document
                .getElementById('mainContent')
                ?.classList
                .contains('mode-grid');

        if (listView) {
            listView.hidden = isGrid;
        }

        if (gridView) {
            gridView.hidden = !isGrid;
        }

        if (listContainer) {
            listContainer.innerHTML =
                isGrid
                    ? ''
                    : buildDocRequestListSkeletonHtml(4);
        }

        if (responsiveListContainer) {
            responsiveListContainer.innerHTML =
                isGrid
                    ? ''
                    : buildDocRequestResponsiveListSkeletonHtml(4);
        }

        if (gridContainer) {
            gridContainer.innerHTML =
                isGrid
                    ? buildDocRequestSkeletonHtml(6)
                    : '';
        }
    }

    function updateStats(stats) {
        stats = stats || {};

        const values = {
            all: stats.all ?? stats.total ?? allRequests.length ?? 0,
            pending: stats.pending ?? 0,
            approved: stats.approved ?? 0,
            rejected: stats.rejected ?? 0
        };

        const ids = {
            all: ['statAll', 'miniStatAll'],
            pending: ['statPending', 'miniStatPending'],
            approved: ['statApproved', 'miniStatApproved'],
            rejected: ['statRejected', 'miniStatRejected']
        };

        Object.keys(ids).forEach((key) => {
            const value = values[key] ?? 0;

            ids[key].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value;
            });
        });
    }

    function getFiltered() {
        let data = allRequests;

        if (!activeFilter.includes('all')) {
            data = data.filter(r =>
                activeFilter.includes(
                    normalizeDocreqStatus(r.status)
                )
            );
        }
        if (searchQuery) {
            const q = searchQuery.toLowerCase();
            data = data.filter(r => {
                const displayName = getPatientDisplayName(r.patient_name).toLowerCase();
                const rawName = String(r.patient_name || '').toLowerCase();
                const identifier = String(r.sub_label || r.patient_identifier || '').toLowerCase();
                const reference = String(r.reference_number || '').toLowerCase();
                const documentType = String(r.document_type || '').toLowerCase();
                const purpose = String(r.purpose || '').toLowerCase();
                const status = String(r.status || '').toLowerCase();

                return displayName.includes(q) ||
                    rawName.includes(q) ||
                    identifier.includes(q) ||
                    reference.includes(q) ||
                    documentType.includes(q) ||
                    purpose.includes(q) ||
                    status.includes(q);
            });
        }
        if (filterDocType) data = data.filter(r => sameDocType(r.document_type, filterDocType));
        if (filterDateFrom) {
            data = data.filter(r => String(r.filter_date || '').slice(0, 10) >= filterDateFrom);
        }
        if (filterDateTo) {
            data = data.filter(r => String(r.filter_date || '').slice(0, 10) <= filterDateTo);
        }
        data = [...data].sort((a, b) => {
            const dateA = new Date(a.request_sort_date || a.filter_date || a.request_date || 0);
            const dateB = new Date(b.request_sort_date || b.filter_date || b.request_date || 0);

            if (filterSort === 'oldest') return dateA - dateB;
            if (filterSort === 'az') {
                const displayNameA = getPatientDisplayName(a.patient_name);
                const displayNameB = getPatientDisplayName(b.patient_name);
                return displayNameA.localeCompare(displayNameB);
            }
            if (filterSort === 'za') {
                const displayNameA = getPatientDisplayName(a.patient_name);
                const displayNameB = getPatientDisplayName(b.patient_name);
                return displayNameB.localeCompare(displayNameA);
            }
            return dateB - dateA;
        });
        return data;
    }

    function hasActiveFilters() {
        return searchQuery !== '' || !activeFilter.includes('all') || filterDocType !== '' || filterDateFrom !== '' ||
            filterDateTo !== '' || filterSort !== 'newest';
    }

    function countAdvancedFilters() {
        let n = 0;

        if (filterDocType) n++;
        if (filterDateFrom || filterDateTo) n++;
        if (filterSort !== 'newest') n++;

        return n;
    }

    function updateFilterBtn() {
        const badge = document.getElementById('filterBadge');
        const externalClear = document.getElementById('externalClearFilterBtn');
        const count = countAdvancedFilters();

        if (count > 0) {
            if (badge) {
                badge.textContent = count;
                badge.style.display = 'inline-flex';
            }
            if (externalClear) externalClear.classList.remove('hidden');
        } else {
            if (badge) badge.style.display = 'none';
            if (externalClear) externalClear.classList.add('hidden');
        }
    }

    function buildClearFilterBtn() {
        const parts = [];
        if (searchQuery) parts.push(`"${esc(searchQuery)}"`);
        if (!activeFilter.includes('all')) {
            parts.push(
                activeFilter
                    .map(status => status.charAt(0).toUpperCase() + status.slice(1))
                    .join(', ')
            );
        }
        if (filterDocType) parts.push(filterDocType);
        if (filterDateFrom || filterDateTo) parts.push('Date range');
        if (filterSort !== 'newest') parts.push('Sort');
        const label = parts.length ? `Clear filter${parts.length > 1 ? 's' : ''} (${parts.join(', ')})` :
            'Reset';
        return `<div style="margin-top:1.25rem;"><button class="btn-clear-filter" onclick="resetAllFilters()"><i class="fa-solid fa-filter-circle-xmark"></i>${label}</button></div>`;
    }

    function resetAllFilters() {
        const searchInput = document.getElementById('searchInput');

        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            searchInput.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }

        searchQuery = '';

        activeFilter = ['all'];
        filterStatus = ['all'];

        window.setGlobalFilterSelectValue?.(
            'docreqStatusFilter',
            'all',
            {
                callback: false,
                focus: false
            }
        );

        filterDocType = '';
        filterDateFrom = '';
        filterDateTo = '';
        filterSort = 'newest';
        currentPage = 1;

        window.syncFilterTagGroup('fSortGroup', 'newest');

        const dateFrom = document.getElementById('fDateFrom');
        const dateTo = document.getElementById('fDateTo');

        setDocTypeSelectValue('');
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';

        document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => {
            btn.classList.remove('active');
        });

        updateFilterBtn();
        renderFilterChips();
        fetchDocRequests();
    }

    function resetAdvancedFilters() {
        filterDocType = '';
        filterDateFrom = '';
        filterDateTo = '';
        filterSort = 'newest';
        currentPage = 1;

        if (window.syncFilterTagGroup) {
            window.syncFilterTagGroup('fSortGroup', 'newest');
        }

        setDocTypeSelectValue('');

        const dateFrom = document.getElementById('fDateFrom');
        const dateTo = document.getElementById('fDateTo');

        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';

        document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => {
            btn.classList.remove('active');
        });

        updateFilterBtn();
        renderFilterChips();
        fetchDocRequests();
    }

    let docreqFetchController = null;

    function getDocreqFetchParams() {
        return new URLSearchParams({
            search: searchQuery || '',
            status: serializeDocreqStatusValues(activeFilter),
            type: filterDocType || '',
            date_from: filterDateFrom || '',
            date_to: filterDateTo || '',
            sort: filterSort || 'newest',
            per_page: docreqPerPage || 10,
            page: currentPage || 1,
        });
    }

    async function fetchDocRequests(silent = false) {
        if (docreqFetchController) {
            docreqFetchController.abort();
        }

        docreqFetchController = new AbortController();

        const params = getDocreqFetchParams();
        history.replaceState(null, '', `${window.location.pathname}?${params.toString()}`);

        if (!silent) {
            showSkeleton();
        }

        const skeletonStartedAt = performance.now();
        const minimumSkeletonDuration = silent ? 0 : 320;

        try {
            const response = await fetch(`${DOCREQ_INDEX_URL}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF
                },
                signal: docreqFetchController.signal
            });

            if (!response.ok) {
                throw new Error(`Request failed. Status: ${response.status}`);
            }

            const data = await response.json();

            allRequests = Array.isArray(data.requests) ?
                data.requests.map(normalizeDocreqRequest) : [];

            if (Array.isArray(data.types)) {
                documentTypeOptions = normalizeDocTypes(data.types);
            }

            if (data.stats) {
                updateStats(data.stats);
            }

            if (data.pagination) {
                docreqPagination = data.pagination;

                currentPage = Number(
                    docreqPagination.current_page || 1
                );

                docreqPerPage = Number(
                    docreqPagination.per_page || docreqPerPage || 10
                );

                renderDocreqPagebars(docreqPagination);
            }

            const elapsed = performance.now() - skeletonStartedAt;
            const remaining = minimumSkeletonDuration - elapsed;

            if (remaining > 0) {
                await new Promise(resolve => setTimeout(resolve, remaining));
            }

            renderList();
            renderFilterChips();
            const activeContainer =
                currentViewMode === 'grid'
                    ? document.getElementById('requestGridContainer')
                    : window.matchMedia('(max-width: 1023px)').matches
                        ? document.getElementById('requestResponsiveListContainer')
                        : document.getElementById('requestListContainer');

            if (activeContainer) {
                activeContainer.classList.remove('content-reveal');
                void activeContainer.offsetWidth;
                activeContainer.classList.add('content-reveal');

                setTimeout(() => {
                    activeContainer.classList.remove('content-reveal');
                }, 460);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Document request fetch failed:', error);

                if (typeof window.showToast === 'function') {
                    window.showToast({
                        type: 'error',
                        title: 'Load failed',
                        message: 'Document requests could not be refreshed.'
                    });
                }
            }
        }
    }

    function renderList() {
        const page = getFiltered();

        const tableHead =
            document.getElementById('docreqTableHead');

        const listView =
            document.getElementById('requestListView');

        const gridView =
            document.getElementById('requestGridView');

        const listContainer =
            document.getElementById('requestListContainer');

        const responsiveListContainer =
            document.getElementById('requestResponsiveListContainer');

        const gridContainer =
            document.getElementById('requestGridContainer');

        renderDocreqPagebars(docreqPagination);

        if (!page.length) {
            if (tableHead) {
                tableHead.hidden = true;
            }

            if (listView) {
                listView.hidden = true;
            }

            if (gridView) {
                gridView.hidden = true;
            }

            if (listContainer) {
                listContainer.innerHTML = '';
            }

            if (responsiveListContainer) {
                responsiveListContainer.innerHTML = '';
            }

            if (gridContainer) {
                gridContainer.innerHTML = '';
            }

            renderDocreqEmptyState();

            return;
        }

        window.EmptyState?.hide('#docreqEmptyState');

        /*
         * Render every responsive representation.
         * CSS decides whether desktop list, compact list, or grid is visible.
         */
        if (listContainer) {
            listContainer.innerHTML =
                page
                    .map(request => buildDesktopRow(request))
                    .join('');
        }

        if (responsiveListContainer) {
            responsiveListContainer.innerHTML =
                page
                    .map(request => buildResponsiveListRecord(request))
                    .join('');
        }

        if (gridContainer) {
            gridContainer.innerHTML =
                page
                    .map(request => buildGridCard(request))
                    .join('');
        }

        syncDocumentRequestView();
    }

    function syncDocumentRequestView() {
        const mainContent =
            document.getElementById(
                'mainContent'
            );

        const listView =
            document.getElementById(
                'requestListView'
            );

        const gridView =
            document.getElementById(
                'requestGridView'
            );

        if (!mainContent) {
            return;
        }

        const isGrid =
            mainContent.classList.contains(
                'mode-grid'
            );

        if (listView) {
            listView.hidden = isGrid;
        }

        if (gridView) {
            gridView.hidden = !isGrid;
        }
    }

    function getPatientDisplayName(name) {
        const rawName =
            String(
                name ||
                'Unknown Patient'
            ).trim();

        return (
            window.formatPatientName?.(
                rawName
            ) ||
            rawName
        );
    }

    function buildPatientAvatar(
        request,
        size = 'md'
    ) {
        const displayName =
            getPatientDisplayName(
                request.patient_name
            );

        return (
            window.PatientUI
                ?.buildAvatarHtml({
                    name:
                        displayName,

                    url:
                        request
                            .patient_photo_url ||
                        request
                            .profile_photo_url ||
                        request
                            .profile_picture_url ||
                        request
                            .avatar_url ||
                        request
                            .photo_url ||
                        '',

                    size:
                        size,

                    escapeHtml:
                        esc,
                }) ||
            ''
        );
    }

    function getStatusLabel(status) {
        const normalized = String(status || 'pending').replace(/_/g, '-').toLowerCase();

        if (normalized === 'approved' || normalized === 'ready' || normalized === 'released') {
            return 'Approved';
        }

        if (normalized === 'rejected') return 'Rejected';

        return 'Pending';
    }

    function jsStringArg(value) {
        return JSON.stringify(String(value ?? '')).replace(/"/g, '&quot;');
    }

    function getStatusBadgeClass(status) {
        const normalized =
            normalizeDocreqStatus(status);

        return `status-${normalized}`;
    }

    function buildRequestActions(request) {
        const displayName =
            getPatientDisplayName(
                request.patient_name
            );

        const patientArg =
            jsStringArg(displayName);

        const status =
            normalizeDocreqStatus(
                request.status
            );

        if (status !== 'pending') {
            return '';
        }

        return `
        <div class="ui-action-group">
            <button
                type="button"
                class="ui-action-btn ui-action-success"
                data-tooltip="Approve request" data-tooltip-tone="start"
                aria-label="Approve request"
                onclick="event.stopPropagation(); openApprove(${request.id}, ${patientArg})">
                <i class="fa-solid fa-check"></i>
            </button>

            <button
                type="button"
                class="ui-action-btn ui-action-delete"
                data-tooltip="Reject request" data-tooltip-tone="cancel"
                aria-label="Reject request"
                onclick="event.stopPropagation(); openReject(${request.id}, ${patientArg})">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    `;
    }

    function buildDesktopRow(r) {
        const badgeCls =
            getStatusBadgeClass(r.status);

        const statusLabel =
            getStatusLabel(r.status);

        const displayName =
            getPatientDisplayName(
                r.patient_name
            );

        const avatarHtml =
            buildPatientAvatar(
                r,
                'sm'
            );

        const identifier =
            r.sub_label
                ? esc(r.sub_label)
                : 'No ID set';

        return `
        <tr
            id="row-d-${r.id}"
            onclick="
                selectDocumentCard(
                    'd',
                    ${r.id}
                )
            "
        >
            <td class="table-cell-main">
                <div class="table-primary">
                    ${avatarHtml}

                    <div>
                        <strong data-patient-name>
                            ${esc(displayName)}
                        </strong>

                        <div>
                            <span class="global-info-pill">
                                <i class="fa-regular fa-id-card"></i>
                                ${identifier}
                            </span>
                        </div>
                    </div>
                </div>
            </td>

            <td class="table-cell-main">
                <div class="table-date">
                    <i class="fa-regular fa-calendar"></i>
                    ${esc(r.request_date)}
                </div>

                <div class="table-date">
                    <i class="fa-regular fa-clock"></i>
                    ${esc(r.request_time)}
                </div>
            </td>

            <td class="table-cell-main">
                <strong>
                    ${esc(r.document_type)}
                </strong>
            </td>

            <td class="table-cell-main">
                ${esc(r.purpose)}
            </td>

            <td>
                <span class="status-badge ${badgeCls}">
                    ${statusLabel}
                </span>
            </td>

            <td class="table-action-cell">
                ${buildRequestActions(r)}
            </td>
        </tr>
    `;
    }

    function buildResponsiveListRecord(r) {
        const badgeCls =
            getStatusBadgeClass(r.status);

        const statusLabel =
            getStatusLabel(r.status);

        const displayName =
            getPatientDisplayName(
                r.patient_name
            );

        const avatarHtml =
            buildPatientAvatar(
                r,
                'sm'
            );

        const identifier =
            r.sub_label
                ? esc(r.sub_label)
                : 'No ID set';

        const isPending =
            normalizeDocreqStatus(r.status) === 'pending';

        return `
        <article
            class="docreq-responsive-record"
            id="row-m-${r.id}"
            onclick="selectDocumentCard('m', ${r.id})"
        >
            <div class="docreq-responsive-record-head">
                <div class="table-primary docreq-responsive-patient">
                    ${avatarHtml}

                    <div class="docreq-responsive-patient-copy">
                        <strong data-patient-name>
                            ${esc(displayName)}
                        </strong>

                        <span class="global-info-pill docreq-responsive-identifier">
                            <i class="fa-regular fa-id-card"></i>
                            ${identifier}
                        </span>
                    </div>
                </div>

                <span class="status-badge ${badgeCls}">
                    ${statusLabel}
                </span>
            </div>

            <div class="docreq-responsive-meta">
                <div class="docreq-responsive-field">
                    <span class="table-record-label">Document</span>
                    <span class="table-record-value">
                        ${esc(r.document_type)}
                    </span>
                </div>

                <div class="docreq-responsive-field">
                    <span class="table-record-label">Date Requested</span>
                    <span class="table-record-value">
                        <span class="table-date">
                            <i class="fa-regular fa-calendar"></i>
                            ${esc(r.request_date)}
                        </span>
                    </span>
                </div>

                <div class="docreq-responsive-field">
                    <span class="table-record-label">Time</span>
                    <span class="table-record-value">
                        <span class="table-date">
                            <i class="fa-regular fa-clock"></i>
                            ${esc(r.request_time)}
                        </span>
                    </span>
                </div>

                <div class="docreq-responsive-field docreq-responsive-purpose">
                    <span class="table-record-label">Purpose</span>
                    <span class="table-record-value">
                        ${esc(r.purpose)}
                    </span>
                </div>
            </div>

            ${isPending
                ? `
                    <div class="docreq-responsive-actions">
                        ${buildRequestActions(r)}
                    </div>
                `
                : ''
            }
        </article>
    `;
    }

    function buildGridCard(r) {
        const badgeCls =
            getStatusBadgeClass(r.status);

        const statusLabel =
            getStatusLabel(r.status);

        const displayName =
            getPatientDisplayName(
                r.patient_name
            );

        const avatarHtml =
            buildPatientAvatar(
                r,
                'sm'
            );

        const identifier =
            r.sub_label
                ? esc(r.sub_label)
                : 'No ID set';

        const isPending =
            normalizeDocreqStatus(r.status) === 'pending';

        return `
        <article
            class="table-record-card docreq-grid-card"
            id="row-g-${r.id}"
            onclick="selectDocumentCard('g', ${r.id})"
        >
            <div class="table-record-card-layout">

                <div class="table-record-content">

                    <div class="table-record-header docreq-grid-header">

                        <div class="table-primary docreq-grid-patient">
                            ${avatarHtml}

                            <div class="docreq-grid-patient-copy">
                                <div
                                    class="table-record-title"
                                    data-patient-name
                                >
                                    ${esc(displayName)}
                                </div>

                                <span class="global-info-pill docreq-grid-identifier">
                                    <i class="fa-regular fa-id-card"></i>
                                    ${identifier}
                                </span>
                            </div>
                        </div>

                        <span class="status-badge ${badgeCls}">
                            ${statusLabel}
                        </span>

                    </div>

                    <div class="table-record-meta docreq-grid-meta">

                        <div class="table-record-row">
                            <span class="table-record-label">
                                Document
                            </span>

                            <span class="table-record-value">
                                ${esc(r.document_type)}
                            </span>
                        </div>

                        <div class="table-record-row">
                            <span class="table-record-label">
                                Purpose
                            </span>

                            <span class="table-record-value">
                                ${esc(r.purpose)}
                            </span>
                        </div>

                        <div class="docreq-grid-datetime">
                            <div class="table-record-row docreq-grid-date-field">
                                <span class="table-record-label">
                                    Date Requested
                                </span>

                                <span class="table-record-value">
                                    <span class="table-date">
                                        <i class="fa-regular fa-calendar"></i>
                                        ${esc(r.request_date)}
                                    </span>
                                </span>
                            </div>

                            <div class="table-record-row docreq-grid-time-field">
                                <span class="table-record-label">
                                    Time
                                </span>

                                <span class="table-record-value">
                                    <span class="table-date">
                                        <i class="fa-regular fa-clock"></i>
                                        ${esc(r.request_time)}
                                    </span>
                                </span>
                            </div>
                        </div>

                    </div>

                </div>

                ${isPending
                ? `
                        <div class="table-record-actions docreq-grid-actions">
                            ${buildRequestActions(r)}
                        </div>
                    `
                : ''
            }

            </div>
        </article>
    `;
    }

    function renderDocreqEmptyState() {
        const isSearchMiss =
            searchQuery !== '';

        const isDataEmpty =
            allRequests.length === 0;

        if (isSearchMiss) {
            window.EmptyState?.renderSearch({
                host:
                    '#docreqEmptyState',

                input:
                    '#searchInput',

                query:
                    searchQuery,

                message:
                    'Try another patient name or clear the search to see all requests.',
            });

            return;
        }

        const statusEmptyCopy = {
            pending: {
                icon:
                    'fa-clock-rotate-left',

                title:
                    'No pending requests',

                message:
                    'Pending document requests will appear here once submitted.',
            },

            approved: {
                icon:
                    'fa-file-circle-check',

                title:
                    'No approved requests',

                message:
                    'Approved document requests will appear here after review.',
            },

            rejected: {
                icon:
                    'fa-file-circle-xmark',

                title:
                    'No rejected requests',

                message:
                    'Rejected document requests will appear here when applicable.',
            },
        };

        if (!activeFilter.includes('all')) {
            const copy =
                activeFilter.length === 1
                    ? statusEmptyCopy[
                    activeFilter[0]
                    ] || {
                        icon:
                            'fa-filter-circle-xmark',

                        title:
                            'No matching requests found',

                        message:
                            'No document requests are available for this status.',
                    }
                    : {
                        icon:
                            'fa-filter-circle-xmark',

                        title:
                            'No matching requests found',

                        message:
                            'No document requests are available for the selected statuses.',
                    };

            window.EmptyState?.render({
                host:
                    '#docreqEmptyState',

                ...copy,
            });

            return;
        }

        if (!isDataEmpty) {
            const hasAdvancedFilters =
                countAdvancedFilters() > 0;

            window.EmptyState?.render({
                host:
                    '#docreqEmptyState',

                icon:
                    'fa-filter-circle-xmark',

                title:
                    hasAdvancedFilters
                        ? 'No requests match your filters'
                        : 'No matching requests found',

                message:
                    hasAdvancedFilters
                        ? 'Try removing or changing the selected filter criteria.'
                        : 'No document requests match the selected status.',

                actionHtml:
                    hasAdvancedFilters
                        ? `
                    <button
                        type="button"
                        class="empty-state-btn"
                        data-docreq-clear-filters
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear filters
                    </button>
                `
                        : '',
            });

            document
                .querySelector(
                    '#docreqEmptyState [data-docreq-clear-filters]'
                )
                ?.addEventListener(
                    'click',
                    function () {
                        resetAdvancedFilters();
                    }
                );

            return;
        }

        window.EmptyState?.render({
            host:
                '#docreqEmptyState',

            icon:
                'fa-folder-open',

            title:
                'No document requests yet',

            message:
                'Incoming patient document requests will appear here once submitted.',
        });
    }

    function handleDesktopAccordionClick(event, id) {
        if (event.target.closest('button, a, input, textarea, select, label, .docreq-row-detail')) return;

        const btn = document.querySelector(`#row-d-${id} .docreq-review-btn`);
        toggleDesktopDetail(btn, id);
    }

    function toggleDesktopDetail(btn, id) {
        const panel = document.getElementById(`detail-${id}`);
        if (!panel) return;

        const opening = !panel.classList.contains('open');
        panel.classList.toggle('open', opening);

        selectDocumentCard('d', id, opening);

        const realBtn = btn || document.querySelector(`#row-d-${id} .docreq-review-btn`);
        if (realBtn) {
            realBtn.innerHTML = opening ?
                '<i class="fa-solid fa-eye-slash"></i> Hide' :
                '<i class="fa-solid fa-eye"></i> View';
        }
    }

    function closeDesktopDetail(id) {
        const panel = document.getElementById(`detail-${id}`);
        if (panel) panel.classList.remove('open');

        selectDocumentCard('d', id, false);

        const btn = document.querySelector(`#row-d-${id} .docreq-review-btn`);
        if (btn) btn.innerHTML = '<i class="fa-solid fa-eye"></i> View';
    }

    function handleMobileAccordionClick(event, id) {
        if (event.target.closest('button, a, input, textarea, select, label, .docreq-mobile-detail')) return;

        const btn = document.getElementById(`mbtn-${id}`);
        toggleMobileDetail(btn, id);
    }

    function toggleMobileDetail(btn, id) {
        const panel = document.getElementById(`mdetail-${id}`);
        const textEl = document.getElementById(`mtext-${id}`);
        const iconEl = document.getElementById(`micon-${id}`);
        if (!panel) return;

        const opening = !panel.classList.contains('open');
        panel.classList.toggle('open', opening);

        selectDocumentCard('m', id, opening);

        if (textEl) textEl.textContent = opening ? 'Hide' : 'View';
        if (iconEl) iconEl.className = opening ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    }

    function closeMobileDetail(id) {
        const panel = document.getElementById(`mdetail-${id}`);
        const textEl = document.getElementById(`mtext-${id}`);
        const iconEl = document.getElementById(`micon-${id}`);

        if (panel) panel.classList.remove('open');

        selectDocumentCard('m', id, false);

        if (textEl) textEl.textContent = 'View';
        if (iconEl) iconEl.className = 'fa-solid fa-eye';
    }

    function getCssVar(name, fallback) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
    }

    function getDocumentAccent(status) {
        const normalized = String(status || 'pending').replace(/_/g, '-').toLowerCase();

        if (normalized === 'approved' || normalized === 'ready' || normalized === 'released') {
            return getCssVar('--status-approved-solid', '#16A34A');
        }

        if (normalized === 'rejected') {
            return getCssVar('--status-rejected-solid', '#DC2626');
        }

        return getCssVar('--status-pending-solid', '#D97706');
    }

    function esc(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
            '&quot;');
    }

    function selectDocreqPerPage(value) {
        const selectedValue =
            Number(value) || 10;

        docreqPerPage =
            selectedValue;

        currentPage = 1;

        const input =
            document.getElementById(
                'docreqPerPageSelect'
            );

        if (input) {
            input.value =
                String(selectedValue);

            window
                .syncGlobalPageSizeSelect?.(
                    input,
                    selectedValue
                );
        }

        fetchDocRequests();
    }

    window.selectDocreqPerPage = selectDocreqPerPage;

    window.initGlobalPageSizeSelects?.();

    window.handleDocumentRequestStatusSelect =
        function (value) {
            const nextStatuses =
                normalizeDocreqStatusValues(
                    value
                );

            activeFilter =
                nextStatuses;

            filterStatus =
                [...nextStatuses];

            searchQuery = '';

            const searchInput =
                document.getElementById(
                    'searchInput'
                );

            if (searchInput) {
                searchInput.value = '';

                window.syncInputClearButton?.(
                    searchInput
                );
            }

            currentPage = 1;

            fetchDocRequests();
        };

    function renderDocreqPagebars(
        pagination
    ) {
        if (!pagination) {
            return;
        }

        window.renderGlobalPagination?.({
            currentPage:
                Number(
                    pagination.current_page
                ) || 1,

            lastPage:
                Number(
                    pagination.last_page
                ) || 1,

            total:
                Number(
                    pagination.total
                ) || 0,

            from:
                pagination.from ?? null,

            to:
                pagination.to ?? null,

            containers: [
                document.getElementById(
                    'pagControlsTop'
                ),
                document.getElementById(
                    'pagControls'
                ),
            ],

            bars: [
                document.getElementById(
                    'docreqPaginationTopBar'
                ),
                document.getElementById(
                    'docreqPaginationBottomBar'
                ),
            ],

            infoElements: [
                document.getElementById(
                    'pageInfoTop'
                ),
                document.getElementById(
                    'pageInfo'
                ),
            ],

            itemLabel: 'requests',

            onPageChange(page) {
                currentPage = page;

                fetchDocRequests();
            },
        });

        const input =
            document.getElementById(
                'docreqPerPageSelect'
            );

        if (
            input &&
            pagination.per_page
        ) {
            input.value =
                String(
                    pagination.per_page
                );

            window
                .syncGlobalPageSizeSelect?.(
                    input,
                    pagination.per_page
                );
        }
    }

    function docreqGoPage(page) {
        currentPage = Number(page) || 1;
        fetchDocRequests();
    }

    window.docreqGoPage = docreqGoPage;

    function formatDocTypeLabel(type = '') {
        return String(type || '')
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\w\S*/g, word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase());
    }

    function normalizeDocTypeKey(type = '') {
        return formatDocTypeLabel(type).toLowerCase();
    }

    function sameDocType(a = '', b = '') {
        return normalizeDocTypeKey(a) === normalizeDocTypeKey(b);
    }

    function normalizeDocTypes(types = []) {
        const typeMap = new Map();
        const requestableTypes = Array.isArray(DOCREQ_DEFAULT_DOC_TYPES) && DOCREQ_DEFAULT_DOC_TYPES.length ?
            DOCREQ_DEFAULT_DOC_TYPES :
            types;

        requestableTypes.forEach(type => {
            const label = formatDocTypeLabel(type);
            const key = normalizeDocTypeKey(label);

            if (label && !typeMap.has(key)) {
                typeMap.set(key, label);
            }
        });

        return Array.from(typeMap.values());
    }

    function setDocTypeSelectValue(value = '') {
        const select =
            document.getElementById('fDocType');

        if (!select) return;

        select.value = value;

        select.dispatchEvent(
            new Event('change', {
                bubbles: true
            })
        );
    }

    let docreqSearchTimer = null;

    window.handleDocumentRequestSearch =
        function (value) {
            searchQuery =
                String(value || '')
                    .trim();

            currentPage = 1;

            fetchDocRequests(true);
        };

    function outside(id) {
        const el = document.getElementById(id);
        if (!el) return;

        el.addEventListener('click', e => {
            if (e.target === el) window.closeModal(id);
        });
    }

    function getDecisionRequest(id) {
        return allRequests.find(r => Number(r.id) === Number(id));
    }

    function setDecisionText(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = value && String(value).trim() ? value : '—';
    }

    function fillDecisionModal(prefix, id, fallbackName = '') {
        const request = getDecisionRequest(id);

        const patientName = request ?
            getPatientDisplayName(request.patient_name) :
            getPatientDisplayName(fallbackName);

        setDecisionText(`${prefix}PatientName`, patientName);
        setDecisionText(`${prefix}RequestDate`, request?.request_date);
        setDecisionText(`${prefix}RequestTime`, request?.request_time);
        setDecisionText(`${prefix}RequestDocument`, request?.document_type);
        setDecisionText(`${prefix}RequestPurpose`, request?.purpose);
    }

    function openApprove(id, name) {
        document.getElementById('approveRequestId').value = id;
        fillDecisionModal('approve', id, name);
        window.openModal('approveModal');
    }

    function openReject(id, name) {
        const modal =
            document.getElementById(
                'rejectModal'
            );

        const form =
            document.getElementById(
                'rejectRequestForm'
            );

        const notes =
            document.getElementById(
                'rejectNotes'
            );

        document.getElementById(
            'rejectRequestId'
        ).value = id;

        fillDecisionModal(
            'reject',
            id,
            name
        );

        if (notes) {
            notes.value = '';

            notes.dispatchEvent(
                new Event('input', {
                    bubbles: true
                })
            );

            notes.dispatchEvent(
                new Event('change', {
                    bubbles: true
                })
            );
        }

        window.DiscardChanges?.captureForm(form);
        window.openModal('rejectModal');

        if (
            !window.matchMedia(
                '(max-width: 767px)'
            ).matches
        ) {
            setTimeout(
                () => notes?.focus(),
                80
            );
        }
    }
    function openFilterModal() {
        window.syncFilterTagGroup('fSortGroup', filterSort);
        setDocTypeSelectValue(
            filterDocType
        );
        document.getElementById('fDateFrom').value = filterDateFrom;
        document.getElementById('fDateTo').value = filterDateTo;
        renderFilterChips();
        updateShowResultsButton();

        if (window.openFilterDrawer) {
            window.openFilterDrawer('filterModal');
            return;
        }

        if (window.openModal) {
            window.openModal('filterModal');
            return;
        }

        document.getElementById('filterModal')?.classList.add('open');
    }

    function closeFilterModal() {
        if (window.closeFilterDrawer) {
            window.closeFilterDrawer('filterModal');
            return;
        }

        if (window.closeModal) {
            window.closeModal('filterModal');
            return;
        }

        const modal = document.getElementById('filterModal');
        if (modal) modal.classList.remove('open', 'closing');
        document.body.classList.remove('filter-lock');
        document.documentElement.classList.remove('filter-lock');
        document.body.style.overflow = '';
    }

    function applyFilterModal() {
        filterStatus = [...activeFilter];
        const sortActive = document.querySelector('#fSortGroup .ftag.ftag-active');
        filterSort = sortActive ? sortActive.getAttribute('data-val') : 'newest';
        filterDocType = document.getElementById('fDocType').value;
        filterDateFrom = document.getElementById('fDateFrom').value;
        filterDateTo = document.getElementById('fDateTo').value;

        updateFilterBtn();
        renderFilterChips();
        currentPage = 1;
        closeFilterModal();
        fetchDocRequests();
    }

    function getDraftDocRequestFilters() {
        const sortActive = document.querySelector('#fSortGroup .ftag.ftag-active');

        return {
            status: serializeDocreqStatusValues(activeFilter),
            docType: document.getElementById('fDocType')?.value || '',
            dateFrom: document.getElementById('fDateFrom')?.value || '',
            dateTo: document.getElementById('fDateTo')?.value || '',
            sort: sortActive ? sortActive.getAttribute('data-val') : 'newest'
        };
    }

    function getDraftFilteredDocRequests() {
        const oldActiveFilter = [...activeFilter];
        const oldFilterStatus = [...filterStatus];
        const oldFilterDocType = filterDocType;
        const oldFilterDateFrom = filterDateFrom;
        const oldFilterDateTo = filterDateTo;
        const oldFilterSort = filterSort;

        const draft = getDraftDocRequestFilters();

        activeFilter = normalizeDocreqStatusValues(draft.status);
        filterStatus = [...activeFilter];
        filterDocType = draft.docType;
        filterDateFrom = draft.dateFrom;
        filterDateTo = draft.dateTo;
        filterSort = draft.sort;

        const results = getFiltered();

        activeFilter = oldActiveFilter;
        filterStatus = oldFilterStatus;
        filterDocType = oldFilterDocType;
        filterDateFrom = oldFilterDateFrom;
        filterDateTo = oldFilterDateTo;
        filterSort = oldFilterSort;

        return results;
    }

    function updateShowResultsButton() {
        const target =
            document.getElementById(
                'filterResultsText'
            );

        if (!target) {
            return;
        }

        const count =
            getDraftFilteredDocRequests()
                .length;

        target.textContent =
            `Show ${count} ${count === 1
                ? 'result'
                : 'results'
            }`;
    }

    function renderFilterChips() {
        const container = document.getElementById("activeChipsContainer");
        const section = document.getElementById("activeFiltersSection");
        if (!container || !section) return;

        container.innerHTML = "";
        let hasChips = false;

        function addChip(label, onRemove) {
            hasChips = true;
            const chip = document.createElement("div");
            chip.className = "filter-chip";
            chip.innerHTML =
                `<span>${label}</span><span class="filter-chip-remove"><i class="fa-solid fa-xmark"></i></span>`;
            chip.querySelector(".filter-chip-remove").addEventListener("click", () => {
                onRemove();
                renderFilterChips();
                updateShowResultsButton();
            });
            container.appendChild(chip);
        }

        const docType = document.getElementById('fDocType').value;
        if (docType) {
            addChip(
                `Doc: ${docType}`,
                () =>
                    setDocTypeSelectValue('')
            );
        }

        const activePresetBtn = document.querySelector('#datePresetGroup .quick-date-chip.active');
        const fDate = document.getElementById('fDateFrom').value;
        const tDate = document.getElementById('fDateTo').value;

        if (activePresetBtn) {
            addChip(`Date: ${activePresetBtn.textContent.trim()}`, () => {
                activePresetBtn.classList.remove('active');
                document.getElementById('fDateFrom').value = "";
                document.getElementById('fDateTo').value = "";
            });
        } else if (fDate || tDate) {
            let lbl = (fDate && tDate) ? `${fDate} to ${tDate}` : (fDate ? `From ${fDate}` : `Until ${tDate}`);
            addChip(`Date: ${lbl}`, () => {
                document.getElementById('fDateFrom').value = "";
                document.getElementById('fDateTo').value = "";
            });
        }

        const sortActive = document.querySelector('#fSortGroup .ftag.ftag-active');
        if (sortActive && sortActive.getAttribute('data-val') !== 'newest') {
            addChip(`Sort: ${sortActive.textContent.trim()}`, () => window.syncFilterTagGroup('fSortGroup', 'newest'));
        }

        if (hasChips) {
            section.classList.remove("hidden");
            document.getElementById("clearAllChipsBtn").onclick = () => {
                setDocTypeSelectValue('');
                document.getElementById('fDateFrom').value = "";
                document.getElementById('fDateTo').value = "";

                document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => {
                    btn.classList.remove('active');
                });

                window.syncFilterTagGroup('fSortGroup', 'newest');

                renderFilterChips();
                updateShowResultsButton();
            };
        } else {
            section.classList.add("hidden");
        }

        updateShowResultsButton();
    }

    function resetFilterModal() {
        filterDocType = '';
        filterDateFrom = '';
        filterDateTo = '';
        filterSort = 'newest';

        window.syncFilterTagGroup('fSortGroup', 'newest');

        setDocTypeSelectValue('');

        const dateFrom = document.getElementById('fDateFrom');
        const dateTo = document.getElementById('fDateTo');

        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';

        document.querySelectorAll('#datePresetGroup .quick-date-chip').forEach(btn => {
            btn.classList.remove('active');
        });

        renderFilterChips();
        updateShowResultsButton();
    }

    document.addEventListener("DOMContentLoaded", () => {

        window.initCustomSelects?.(
            document.getElementById(
                'filterModal'
            ) || document
        );

        const docreqViewToggle =
            document.getElementById(
                'documentRequestsViewToggle'
            );

        window.initGlobalViewToggles?.(document);

        currentViewMode =
            window.getGlobalViewMode?.(
                docreqViewToggle
            ) || 'list';

        docreqViewToggle?.addEventListener(
            'global-view-change',
            event => {
                currentViewMode =
                    event.detail?.mode === 'grid' ?
                        'grid' :
                        'list';

                renderList();
            }
        );

        if (activeFilter.length === 1) {
            window.setGlobalFilterSelectValue?.(
                'docreqStatusFilter',
                activeFilter[0],
                {
                    callback: false,
                    focus: false
                }
            );
        }

        window.addEventListener('resize', syncDocumentRequestView);

        window.bindQuickDatePresets({
            groupId: 'datePresetGroup',
            fromId: 'fDateFrom',
            toId: 'fDateTo',
            onChange: () => {
                renderFilterChips();
                updateShowResultsButton();
            }
        });

        window.bindFilterTagGroup({
            groupId: 'fSortGroup',
            onChange: () => {
                renderFilterChips();
                updateShowResultsButton();
            }
        });

        const docTypeSelect =
            document.getElementById('fDocType');

        docTypeSelect?.addEventListener(
            'change',
            () => {
                renderFilterChips();
                updateShowResultsButton();
            }
        );

        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            ['approveModal', 'rejectModal', 'filterModal']

                .forEach(id => {
                    const m = document.getElementById(id);
                    if (!m?.classList.contains('open')) return;
                    if (id === 'filterModal') closeFilterModal();
                    else window.closeModal(id);
                });
        });

        ['approveModal', 'rejectModal', 'filterModal']

            .forEach(id => outside(id));

        const filterModal = document.getElementById('filterModal');
        document.getElementById('filterCloseBtn').addEventListener('click', closeFilterModal);
        document.getElementById('filterCancelBtn').addEventListener('click', closeFilterModal);
        document.getElementById('filterApplyBtn').addEventListener('click', applyFilterModal);
        document.getElementById('filterResetBtn').addEventListener('click', () => {
            resetAdvancedFilters();
            resetFilterModal();
            updateShowResultsButton();
        });

        document.getElementById('approveCancelBtn2')?.addEventListener(
            'click',
            () => window.closeModal('approveModal')
        );

        document.getElementById('approveConfirmBtn')?.addEventListener('click', async () => {
            const id = document.getElementById('approveRequestId').value;
            const btn = document.getElementById('approveConfirmBtn');

            if (!id) return;

            btn.disabled = true;

            try {
                const approveUrl = String(
                    DOCREQ_ROUTES.approve || ''
                ).replace('__ID__', id);

                const res = await fetch(approveUrl, {
                    method: DOCREQ_METHODS.approve || 'POST',
                    headers: {
                        'Accept': 'application/pdf, application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({})
                });

                const contentType = res.headers.get('content-type') || '';

                if (!res.ok) {
                    let message =
                        res.status === 403
                            ? 'Unauthorized.'
                            : `Approval failed. Status: ${res.status}`;

                    if (contentType.includes('application/json')) {
                        const data = await res.json().catch(() => ({}));
                        message = data.message || message;
                    }

                    throw new Error(message);
                }

                if (!contentType.toLowerCase().includes('application/pdf')) {
                    throw new Error('The server did not return a valid PDF file.');
                }

                const blob = await res.blob();
                const downloadUrl = URL.createObjectURL(blob);

                let fileName = `approved-document-request-${id}.pdf`;
                const disposition = res.headers.get('Content-Disposition') || '';
                const fileNameMatch = disposition.match(/filename="?([^"]+)"?/i);

                if (fileNameMatch?.[1]) {
                    fileName = fileNameMatch[1];
                }

                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.setTimeout(() => {
                    URL.revokeObjectURL(downloadUrl);
                }, 30000);

                window.closeModal('approveModal');
                syncLocalDocRequestStatus(id, 'approved');

                docreqToast(
                    'success',
                    'Request approved',
                    res.headers.get('X-Approval-Message') ||
                    'The document request has been approved. The patient will be notified.'
                );
            } catch (error) {
                console.error('Approve request error:', error);

                docreqToast(
                    'error',
                    'Approval failed',
                    error.message || 'Approval failed because of a network or JavaScript error.'
                );
            } finally {
                btn.disabled = false;
            }
        });

        document.getElementById('rejectRequestForm')?.addEventListener('submit', async event => {
            event.preventDefault();

            const form = event.currentTarget;

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

            const id =
                document.getElementById(
                    'rejectRequestId'
                ).value;

            const button =
                document.getElementById(
                    'rejectConfirmBtn'
                );

            const notes =
                document.getElementById(
                    'rejectNotes'
                )?.value.trim() || '';

            if (!id || !button) return;

            button.disabled = true;

            window.DiscardChanges?.markSubmitting(
                form
            );

            try {
                const rejectUrl = String(
                    DOCREQ_ROUTES.reject || ''
                ).replace('__ID__', id);

                const response = await fetch(
                    rejectUrl,
                    {
                        method: DOCREQ_METHODS.reject || 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            reason: notes
                        })
                    }
                );

                const data =
                    await response.json()
                        .catch(() => ({}));

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                        `Rejection failed. Status: ${response.status}`
                    );
                }

                window.closeModal(
                    'rejectModal'
                );

                syncLocalDocRequestStatus(
                    id,
                    'rejected', {
                    rejection_reason: notes
                }
                );

                docreqToast(
                    'success',
                    'Request rejected',
                    data.message ||
                    'The document request has been rejected.'
                );
            } catch (error) {
                window.DiscardChanges
                    ?.markNotSubmitting(form);

                docreqToast(
                    'error',
                    'Rejection failed',
                    error.message ||
                    'The request could not be rejected.'
                );
            } finally {
                button.disabled = false;
            }
        }
        );

        showSkeleton();

        requestAnimationFrame(() => {
            setTimeout(() => {
                loadData();
                initDocreqRefreshWatcher();
            }, 350);
        });
    });
</script>
@endsection