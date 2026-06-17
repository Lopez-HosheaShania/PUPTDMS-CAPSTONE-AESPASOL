@extends('layouts.admin')

@section('title', 'Document Requests | PUP Taguig Dental Clinic')

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
$patientIdentifier =
data_get($patient, 'student_no') ??
(data_get($patient, 'student_number') ??
(data_get($patient, 'student_id') ??
(data_get($patient, 'faculty_code') ??
(data_get($patient, 'employee_no') ?? (data_get($patient, 'id') ?? 'No ID set')))));

$documentLabel = $formatDocumentType($req->document_type ?? 'Document');
$status = $normalizeStatus($req->status ?? 'pending');

return [
'id' => $req->id,
'reference_number' =>
$req->reference_number ?? 'DR-' . str_pad((string) $req->id, 5, '0', STR_PAD_LEFT),
'patient_name' => $patientName,
'patient_identifier' => $patientIdentifier,
'sub_label' => $patientIdentifier ?: 'No ID set',
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

$docRequestTypes = $docRequestsPayload->pluck('document_type')->filter()->unique()->sort()->values();

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

<main id="mainContent" class="admin-page-shell page-enter docreq-page mode-list">
    <div class="w-full">

        <div class="page-banner docreq-header-wrap">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Document Requests</h1>
                </div>

                <div class="page-banner-actions">
                    <a href="{{ route('admin.document-requests.export') }}" class="ui-btn ui-btn-secondary">
                        <i class="fa-solid fa-file-export text-xs"></i>
                        Export CSV
                    </a>
                    <a href="{{ route('admin.document-requests.print-queue') }}" class="ui-btn ui-btn-primary">
                        <i class="fa-solid fa-print text-xs"></i>
                        Print Queue
                    </a>
                </div>
            </div>
        </div>

        <div class="table-card rounded-2xl border border-gray-200 shadow-sm bg-white">

            <div class="px-4 md:px-6 py-3.5 border-b border-gray-100 bg-[#FAFAFA]/50">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">

                    <div class="order-2 md:order-1">
                        <span id="rowCount"
                            class="text-[11px] md:text-sm font-bold text-gray-400 uppercase tracking-wider">
                            {{ $docRequestStats['all'] }} requests
                        </span>
                    </div>

                    <div
                        class="docreq-toolbar-actions flex items-center gap-2 order-1 md:order-2 w-full md:w-auto justify-end">

                        <div class="docreq-search-wrap flex-1 md:flex-none flex items-center gap-2">
                            <div class="search-wrap global-search flex-1 md:w-64" data-search-wrapper>
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                                <input id="searchInput" type="text" placeholder="Search document…" data-search-input
                                    class="search-input" oninput="onSearch(this)" />

                                <button type="button" class="search-clear" data-search-clear aria-label="Clear search">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>

                            <div class="voice-input-toggle">
                                <span class="voice-status hidden" data-voice-status></span>
                                <button type="button" class="voice-search-mic external" data-global-voice-trigger
                                    data-voice-target="#searchInput" aria-label="Use voice search" title="Voice search">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                            </div>
                        </div>

                        <div id="docreqStatusSelect"
                            class="docreq-custom-select docreq-status-dropdown docreq-sort-dropdown docreq-toolbar-sort"
                            data-status-filter="all">

                            <button type="button" class="docreq-sort-trigger" id="docreqStatusDropdownBtn"
                                data-select-button aria-expanded="false" aria-haspopup="true"
                                aria-controls="docreqStatusMenu" onclick="toggleDocreqDropdown('docreqStatusSelect')">

                                <span class="docreq-sort-trigger-left">
                                    <span class="docreq-sort-icon status-all" id="docreqStatusSelectedIcon">
                                        <i class="fa-solid fa-file-medical"></i>
                                    </span>

                                    <span class="docreq-sort-copy">
                                        <span class="docreq-sort-label">Sort By</span>
                                        <strong class="docreq-sort-value" id="statusDropdownLabel">All Requests</strong>
                                    </span>
                                </span>

                                <span class="docreq-sort-trigger-right">
                                    <span id="statusDropdownCount" class="docreq-sort-count">
                                        {{ $docRequestStats['all'] ?? 0 }}
                                    </span>
                                    <i class="fa-solid fa-chevron-down docreq-sort-chevron"></i>
                                </span>
                            </button>

                            <input type="hidden" id="docreqStatusFilter" value="all">

                            <div class="docreq-sort-panel" id="docreqStatusMenu" role="listbox">
                                <div class="docreq-sort-grid">
                                    <button type="button" class="docreq-sort-option is-active status-all"
                                        data-value="all" data-label="All Requests" data-icon="fa-file-medical"
                                        data-count="{{ $docRequestStats['all'] ?? 0 }}"
                                        onclick="selectStatusFilter('all')">
                                        <span class="docreq-option-icon status-all">
                                            <i class="fa-solid fa-file-medical"></i>
                                        </span>
                                        <span class="docreq-option-label">All Requests</span>
                                        <span class="docreq-sort-option-count docreq-option-count" id="statAll">
                                            {{ $docRequestStats['all'] ?? 0 }}
                                        </span>
                                    </button>

                                    <button type="button" class="docreq-sort-option status-pending" data-value="pending"
                                        data-label="Pending" data-icon="fa-clock-rotate-left"
                                        data-count="{{ $docRequestStats['pending'] ?? 0 }}"
                                        onclick="selectStatusFilter('pending')">
                                        <span class="docreq-option-icon status-pending">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </span>
                                        <span class="docreq-option-label">Pending</span>
                                        <span class="docreq-sort-option-count docreq-option-count" id="statPending">
                                            {{ $docRequestStats['pending'] ?? 0 }}
                                        </span>
                                    </button>

                                    <button type="button" class="docreq-sort-option status-approved"
                                        data-value="approved" data-label="Approved" data-icon="fa-file-circle-check"
                                        data-count="{{ $docRequestStats['approved'] ?? 0 }}"
                                        onclick="selectStatusFilter('approved')">
                                        <span class="docreq-option-icon status-approved">
                                            <i class="fa-solid fa-file-circle-check"></i>
                                        </span>
                                        <span class="docreq-option-label">Approved</span>
                                        <span class="docreq-sort-option-count docreq-option-count" id="statApproved">
                                            {{ $docRequestStats['approved'] ?? 0 }}
                                        </span>
                                    </button>

                                    <button type="button" class="docreq-sort-option status-rejected"
                                        data-value="rejected" data-label="Rejected" data-icon="fa-file-circle-xmark"
                                        data-count="{{ $docRequestStats['rejected'] ?? 0 }}"
                                        onclick="selectStatusFilter('rejected')">
                                        <span class="docreq-option-icon status-rejected">
                                            <i class="fa-solid fa-file-circle-xmark"></i>
                                        </span>
                                        <span class="docreq-option-label">Rejected</span>
                                        <span class="docreq-sort-option-count docreq-option-count" id="statRejected">
                                            {{ $docRequestStats['rejected'] ?? 0 }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button id="filterBtn" type="button" onclick="openFilterModal()" class="global-filter-btn">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Filter</span>
                            <span id="filterBadge" class="filter-badge" style="display:none;"></span>
                        </button>

                        <div class="view-toggle-container" data-global-view-toggle data-view-root="#mainContent"
                            data-storage-key="ViewToggleMode" aria-label="View options">
                            <span class="view-slider" aria-hidden="true"></span>

                            <button type="button" class="btn-view-mode active" title="List view" aria-label="List view"
                                aria-pressed="true" data-view-mode="list">
                                <i class="fa-solid fa-list"></i>
                            </button>

                            <button type="button" class="btn-view-mode" title="Grid view" aria-label="Grid view"
                                aria-pressed="false" data-view-mode="grid">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>

                        <button id="externalClearFilterBtn" type="button" onclick="resetAdvancedFilters()"
                            class="global-filter-reset-btn hidden" title="Reset filters">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="docreqTableHead"
                class="hidden md:grid gap-3 text-[10px] font-bold uppercase tracking-wider text-gray-500 py-3.5 px-6 bg-[#FAFAFA] border-b border-gray-200"
                style="grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1.5fr) minmax(0, 1.5fr) minmax(0, 1fr) 145px;">
                <div class="text-left">Patient</div>
                <div class="flex items-center gap-1.5"><i class="fa-regular fa-calendar text-[10px]"></i>Date
                    Requested</div>
                <div class="text-left">Document</div>
                <div class="text-left">Purpose</div>
                <div class="text-left">Status</div>
                <div class="text-right">Actions</div>
            </div>

            <div class="sl-pagebar sl-pagebar-top docreq-pagebar docreq-pagebar-top">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="sl-pagebar-info docreq-page-info" id="pageInfoTop"></span>

                    <div class="sl-page-size-control global-page-size-control docreq-page-size-control">
                        <label for="docreqPerPageSelect">Show</label>

                        <div class="global-page-size-select" data-global-page-size
                            data-page-size-input="#docreqPerPageSelect" data-page-size-callback="selectDocreqPerPage">
                            <input type="hidden" id="docreqPerPageSelect" class="global-page-size-native" value="10">

                            <button type="button" class="global-page-size-trigger" data-page-size-trigger
                                aria-haspopup="listbox" aria-expanded="false">
                                <span data-page-size-value>10</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>

                            <div class="global-page-size-menu" role="listbox">
                                <button type="button" class="global-page-size-option is-selected" data-page-size-option
                                    data-value="10" role="option" aria-selected="true">
                                    <span>10</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button" class="global-page-size-option" data-page-size-option
                                    data-value="20" role="option" aria-selected="false">
                                    <span>20</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button" class="global-page-size-option" data-page-size-option
                                    data-value="50" role="option" aria-selected="false">
                                    <span>50</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button type="button" class="global-page-size-option" data-page-size-option
                                    data-value="100" role="option" aria-selected="false">
                                    <span>100</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <span>per page</span>
                    </div>
                </div>

                <div class="sl-pagination-wrap docreq-pagination-wrap">
                    <div id="pagControlsTop" class="sl-pagination docreq-page-controls"></div>
                </div>
            </div>

            <div id="requestListContainer" class="docreq-list-container"></div>
            <div id="requestGridContainer" class="docreq-grid"></div>

            <div class="sl-pagebar docreq-pagebar docreq-pagebar-bottom">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="sl-pagebar-info docreq-page-info" id="pageInfo"></span>
                </div>

                <div class="sl-pagination-wrap docreq-pagination-wrap">
                    <div id="pagControls" class="sl-pagination docreq-page-controls"></div>
                </div>
            </div>

        </div>
    </div>
</main>

<div id="filterModal" class="filter-drawer-wrapper">
    <div class="filter-drawer-overlay" onclick="document.getElementById('filterCancelBtn')?.click()"></div>

    <div class="filter-drawer-panel docreq-filter-sheet flex flex-col bg-white">

        <div
            class="filter-drawer-header px-6 py-5 flex items-center justify-between flex-shrink-0 bg-white border-b border-gray-100">
            <div class="filter-drawer-title flex items-center gap-2">
                <i class="fa-solid fa-sliders text-xl"></i>
                <h2 class="text-xl font-extrabold">Filters</h2>
            </div>

            <button id="filterCancelBtn" type="button" class="text-gray-400 hover:text-gray-700 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="filter-drawer-body px-6 py-5 flex flex-col gap-6 flex-1 overflow-y-auto bg-white">

            <div id="activeFiltersSection" class="hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[13px] font-bold text-gray-800">Active Filters</span>
                    <button id="clearAllChipsBtn" type="button"
                        class="text-xs font-bold text-[#8B0000] hover:underline">
                        Clear All
                    </button>
                </div>
                <div id="activeChipsContainer" class="flex flex-wrap gap-2 pb-4 border-b border-gray-100"></div>
            </div>

            <div>
                <h3 class="filter-section-title">Sort By</h3>
                <div class="filter-chip-row" id="fSortGroup">
                    <button type="button" class="ftag ftag-active" data-val="newest">Newest First</button>
                    <button type="button" class="ftag" data-val="oldest">Oldest First</button>
                    <button type="button" class="ftag" data-val="az">Patient Name A-Z</button>
                    <button type="button" class="ftag" data-val="za">Patient Name Z-A</button>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Document Details</h3>
                <input type="hidden" id="fDocType" value="">

                <div id="docTypeSelect" class="docreq-custom-select docreq-filter-select">
                    <button type="button" class="docreq-select-button" data-select-button aria-haspopup="listbox"
                        aria-expanded="false" aria-controls="docTypeSelectMenu"
                        onclick="toggleDocreqDropdown('docTypeSelect')">
                        <span class="docreq-select-leading"><i class="fa-regular fa-file-lines"></i></span>
                        <span class="docreq-select-text">
                            <span>Document type</span>
                            <strong id="docTypeSelectLabel">All document types</strong>
                        </span>
                        <i class="fa-solid fa-chevron-down docreq-select-chevron"></i>
                    </button>

                    <div id="docTypeSelectMenu" class="docreq-select-menu docreq-doc-type-menu" role="listbox">
                        <button type="button" class="docreq-select-option doc-type-option active" data-value=""
                            onclick="setDocTypeFilter('', 'All document types')">
                            <span class="docreq-option-icon doc-type-all">
                                <i class="fa-solid fa-layer-group"></i>
                            </span>

                            <span class="docreq-option-copy">
                                <strong>All document types</strong>
                                <small>No document type filter</small>
                            </span>

                            <i class="fa-solid fa-check docreq-option-check"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Filter by Date Range</h3>
                <div class="filter-chip-row" id="datePresetGroup">
                    <button type="button" class="quick-date-chip" data-range="7">Last 7 Days</button>
                    <button type="button" class="quick-date-chip" data-range="30">Last 30 Days</button>
                    <button type="button" class="quick-date-chip" data-range="90">Last 3 Months</button>
                    <button type="button" class="quick-date-chip" data-range="180">Last 6 Months</button>
                    <button type="button" class="quick-date-chip" data-range="365">Last 12 Months</button>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Custom Date Range</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="filter-date-input-wrap">
                        <input type="text" id="fDateFrom" class="js-flatpickr-date-range-from" placeholder="Start date"
                            readonly autocomplete="off" />
                        <i class="fa-regular fa-calendar"></i>
                    </div>

                    <div class="filter-date-input-wrap">
                        <input type="text" id="fDateTo" class="js-flatpickr-date-range-to" placeholder="End date"
                            readonly autocomplete="off" />
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="filter-drawer-footer px-6 py-5 bg-white flex flex-col sm:flex-row items-center justify-between flex-shrink-0 border-t border-gray-100 gap-4 sm:gap-0 relative z-20">
            <button id="filterResetBtn" type="button"
                class="filter-clear-btn flex items-center gap-2 transition-colors w-full sm:w-auto justify-center sm:justify-start">
                <i class="fa-regular fa-trash-can text-lg"></i>
                <span class="text-[13px] font-bold leading-none whitespace-nowrap">Clear Filters</span>
            </button>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button id="filterCloseBtn" type="button"
                    class="filter-cancel-btn flex-1 sm:flex-none px-5 py-2.5 text-sm font-bold rounded-lg transition-colors">
                    Cancel
                </button>

                <button id="filterApplyBtn" type="button"
                    class="filter-show-results-btn filter-apply-btn flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold rounded-lg transition-colors shadow-sm">
                    <i class="fa-solid fa-check"></i>
                    <span id="showResultsText">Show results</span>
                </button>
            </div>
        </div>

    </div>
</div>

<div id="approveModal" class="modal-overlay docreq-decision-overlay">
    <div class="modal-box-inner docreq-decision-modal docreq-approve-modal">
        <button type="button" class="modal-float-x" id="approveCancelBtn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="approve-hero">
            <div class="approve-icon-ring">
                <div class="approve-icon-inner">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
            </div>

            <div class="approve-hero-title">Approve Request</div>
            <div class="approve-hero-sub">Review the request details before confirming</div>
        </div>

        <div class="docreq-decision-body">
            <div class="docreq-decision-patient-card">
                <div class="docreq-decision-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="docreq-decision-person-copy">
                    <div class="docreq-decision-label">Patient</div>
                    <div id="approvePatientName" class="docreq-decision-patient-name">—</div>
                </div>
            </div>

            <div class="docreq-decision-request-card">
                <div class="docreq-decision-info">
                    <div class="docreq-decision-info-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <div class="docreq-decision-info-copy">
                        <span>Date & Time</span>
                        <strong id="approveRequestDate">—</strong>
                        <small id="approveRequestTime">—</small>
                    </div>
                </div>

                <div class="docreq-decision-info">
                    <div class="docreq-decision-info-icon">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>
                    <div class="docreq-decision-info-copy">
                        <span>Document</span>
                        <strong id="approveRequestDocument">—</strong>
                    </div>
                </div>

                <div class="docreq-decision-info docreq-decision-info-wide">
                    <div class="docreq-decision-info-icon">
                        <i class="fa-solid fa-message"></i>
                    </div>
                    <div class="docreq-decision-info-copy">
                        <span>Purpose</span>
                        <strong id="approveRequestPurpose">—</strong>
                    </div>
                </div>
            </div>

            <div class="approve-info-row">
                <i class="fa-solid fa-circle-info"></i>
                <span>The document will be queued for printing and signing. This action <strong>cannot be
                        undone.</strong></span>
            </div>
        </div>

        <div class="approve-footer">
            <button type="button" class="modal-btn-ghost" id="approveCancelBtn2">
                <i class="fa-solid fa-arrow-left"></i>
                Cancel
            </button>

            <button type="button" class="modal-btn-confirm-approve" id="approveConfirmBtn">
                <span class="btn-confirm-icon">
                    <i class="fa-solid fa-check"></i>
                </span>
                Confirm Approval
            </button>
        </div>
    </div>
</div>

<input type="hidden" id="approveRequestId">

<div id="rejectModal" class="modal-overlay docreq-decision-overlay">
    <div class="modal-box-inner docreq-decision-modal docreq-reject-modal">
        <button type="button" class="modal-float-x modal-float-x--red" id="rejectCancelBtn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="reject-hero">
            <div class="reject-icon-ring">
                <div class="reject-icon-inner">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </div>
            </div>

            <div class="reject-hero-title">Reject Request</div>
            <div class="reject-hero-sub">Review the request details before confirming</div>
        </div>

        <div class="docreq-decision-body">
            <div class="docreq-decision-patient-card">
                <div class="docreq-decision-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="docreq-decision-person-copy">
                    <div class="docreq-decision-label">Patient</div>
                    <div id="rejectPatientName" class="docreq-decision-patient-name">—</div>
                </div>
            </div>

            <div class="docreq-decision-request-card">
                <div class="docreq-decision-info">
                    <div class="docreq-decision-info-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <div class="docreq-decision-info-copy">
                        <span>Date & Time</span>
                        <strong id="rejectRequestDate">—</strong>
                        <small id="rejectRequestTime">—</small>
                    </div>
                </div>

                <div class="docreq-decision-info">
                    <div class="docreq-decision-info-icon">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>
                    <div class="docreq-decision-info-copy">
                        <span>Document</span>
                        <strong id="rejectRequestDocument">—</strong>
                    </div>
                </div>

                <div class="docreq-decision-info docreq-decision-info-wide">
                    <div class="docreq-decision-info-icon">
                        <i class="fa-solid fa-message"></i>
                    </div>
                    <div class="docreq-decision-info-copy">
                        <span>Purpose</span>
                        <strong id="rejectRequestPurpose">—</strong>
                    </div>
                </div>
            </div>

            <div class="docreq-reject-field-group">
                <div class="docreq-field-head">
                    <label class="reject-field-label" for="rejectNotes">
                        Reason for rejection
                        <span>(optional)</span>
                    </label>

                    <div id="rejectNotesCharCounter" class="char-counter">0 / 150 characters</div>
                </div>

                <textarea id="rejectNotes" class="reject-textarea" rows="4" maxlength="150" data-char-limit="150"
                    data-char-counter="#rejectNotesCharCounter" data-char-error="#err-rejectNotes"
                    placeholder="Provide a reason so the patient understands the decision…"></textarea>

                <div class="field-error" id="err-rejectNotes"></div>
            </div>

            <div class="reject-warning-row">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>The patient will be notified of this rejection. Make sure you've reviewed the request
                    carefully.</span>
            </div>
        </div>

        <div class="reject-footer">
            <button type="button" class="modal-btn-ghost modal-btn-ghost--red" id="rejectCancelBtn2">
                <i class="fa-solid fa-arrow-left"></i>
                Cancel
            </button>

            <button type="button" class="modal-btn-confirm-reject" id="rejectConfirmBtn">
                <span class="btn-confirm-icon">
                    <i class="fa-solid fa-ban"></i>
                </span>
                Confirm Rejection
            </button>
        </div>
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

    let allRequests = Array.isArray(ADMIN_DOC_REQUESTS) ? ADMIN_DOC_REQUESTS : [];
    let activeFilter = @json(request('status', 'all') ?: 'all');

    const DOCREQ_DROPDOWN_STATUSES = ['all', 'pending', 'approved', 'rejected'];

    if (!DOCREQ_DROPDOWN_STATUSES.includes(activeFilter)) {
        activeFilter = 'all';
    }

    const DOCREQ_DATA_URL = `${window.location.pathname.replace(/\/$/, '')}/data`;

    let docreqRefreshWatcher = null;

    let searchQuery = '';

    const DOCREQ_INDEX_URL = @json(route('admin.document-requests.index'));
    const DOCREQ_INITIAL_PAGINATION = @json($docRequestPagination);

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
    let filterStatus = activeFilter;
    let filterDocType = '';
    let filterDateFrom = '';
    let filterDateTo = '';
    let filterSort = 'newest';
    let documentTypeOptions = [];

    let currentViewMode = localStorage.getItem('ViewToggleMode') === 'grid' ? 'grid' : 'list';

    function switchView(mode) {
        const mainContent = document.getElementById('mainContent');
        const btnList = document.getElementById('btnListView');
        const btnGrid = document.getElementById('btnGridView');

        if (!mainContent) return;

        const nextMode = mode === 'grid' ? 'grid' : 'list';
        const isGrid = nextMode === 'grid';

        mainContent.classList.toggle('mode-grid', isGrid);
        mainContent.classList.toggle('mode-list', !isGrid);

        if (btnList) {
            btnList.classList.toggle('active', !isGrid);
            btnList.setAttribute('aria-pressed', !isGrid ? 'true' : 'false');
        }

        if (btnGrid) {
            btnGrid.classList.toggle('active', isGrid);
            btnGrid.setAttribute('aria-pressed', isGrid ? 'true' : 'false');
        }

        localStorage.setItem('patientViewMode', nextMode);
    }

    function syncResponsivePatientView() {
        const isMobile = window.matchMedia('(max-width: 767px)').matches;
        const savedMode = localStorage.getItem('patientViewMode');

        switchView(savedMode || (isMobile ? 'grid' : 'list'));
    }

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

        updateStats(recalculateDocreqStats());
        renderDocTypeOptions(documentTypeOptions);
        renderList();
    }

    function applyDocreqServerSnapshot(payload) {
        if (!payload || !Array.isArray(payload.requests)) return;

        allRequests = payload.requests.map(normalizeDocreqRequest);
        docreqKnownIds = new Set(allRequests.map((request) => Number(request.id)));

        documentTypeOptions = normalizeDocTypes(
            Array.isArray(payload.types) && payload.types.length ?
                payload.types :
                allRequests.map((request) => request.document_type)
        );

        renderDocTypeOptions(documentTypeOptions);
        updateStats(payload.stats || recalculateDocreqStats());
        renderList();

        window.removeGlobalRefreshNotice?.('docreq');
    }

    function initDocreqRefreshWatcher() {
        if (!window.initGlobalRefreshWatcher) return;

        docreqRefreshWatcher = window.initGlobalRefreshWatcher({
            key: 'docreq',
            url: DOCREQ_DATA_URL,
            initialItems: allRequests,
            anchorSelector: '#mainContent.docreq-page .table-card',
            itemLabel: 'document request',
            getItems: (payload) => {
                if (Array.isArray(payload)) {
                    return payload.map(normalizeDocreqRequest);
                }

                return Array.isArray(payload?.requests)
                    ? payload.requests.map(normalizeDocreqRequest)
                    : [];
            },
            getItemId: (request) => request?.id,
            title: (count) => `${count} new document request${count === 1 ? '' : 's'} available`,
            subtitle: (count) => `Refresh to see the latest request${count === 1 ? '' : 's'}.`,
            onRefresh: applyDocreqServerSnapshot,
            toast: {
                type: 'info',
                title: 'Document requests updated',
                message: 'Latest document requests are now shown.'
            }
        });
    }

    function loadData() {
        allRequests = Array.isArray(ADMIN_DOC_REQUESTS) ? ADMIN_DOC_REQUESTS : [];

        documentTypeOptions = normalizeDocTypes(
            Array.isArray(ADMIN_DOC_TYPES) && ADMIN_DOC_TYPES.length ?
                ADMIN_DOC_TYPES :
                allRequests.map(r => r.document_type)
        );

        if (filterDocType && !documentTypeOptions.includes(filterDocType)) {
            filterDocType = '';
        }

        renderDocTypeOptions(documentTypeOptions);
        updateStats(ADMIN_DOC_STATS || {});
        renderDocreqPagebar(docreqPagination);
        renderList();
    }

    function showSkeleton() {
        let html = '';
        for (let i = 0; i < 4; i++) {
            html += `
      <div class="req-row desktop-req-row border-b border-gray-100 hidden md:block">
        <div class="req-inner" style="display:grid;grid-template-columns:1.5fr 1fr 1.5fr 1.5fr 1fr 100px;align-items:center;gap:12px;">
          <div style="display:flex;align-items:center;gap:.8rem;">
            <div class="skeleton" style="width:32px;height:32px;border-radius:50%;flex-shrink:0;"></div>
            <div><div class="skeleton" style="height:13px;width:110px;margin-bottom:6px;"></div><div class="skeleton" style="height:10px;width:70px;"></div></div>
          </div>
          <div><div class="skeleton" style="height:13px;width:80px;margin-bottom:5px;"></div><div class="skeleton" style="height:10px;width:60px;"></div></div>
          <div><div class="skeleton" style="height:13px;width:120px;"></div></div>
          <div><div class="skeleton" style="height:13px;width:140px;"></div></div>
          <div><div class="skeleton" style="height:20px;width:60px;border-radius:999px;"></div></div>
          <div class="skeleton" style="height:28px;width:70px;border-radius:8px;"></div>
        </div>
      </div>
      
      <div class="mobile-req-card md:hidden bg-white border border-gray-200 rounded-xl p-4 mb-3 mx-2">
        <div class="mobile-card-inner">
          <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:.75rem;">
            <div class="skeleton" style="width:38px;height:38px;border-radius:10px;flex-shrink:0;"></div>
            <div style="flex:1;">
              <div class="skeleton" style="height:13px;width:130px;margin-bottom:5px;"></div>
              <div class="skeleton" style="height:10px;width:80px;"></div>
            </div>
            <div class="skeleton" style="height:28px;width:60px;border-radius:9px;"></div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.55rem;">
            <div><div class="skeleton" style="height:9px;width:60px;margin-bottom:4px;"></div><div class="skeleton" style="height:12px;width:80px;"></div></div>
            <div><div class="skeleton" style="height:9px;width:50px;margin-bottom:4px;"></div><div class="skeleton" style="height:12px;width:90px;"></div></div>
          </div>
        </div>
      </div>`;
        }
        const listContainer = document.getElementById('requestListContainer');
        const gridContainer = document.getElementById('requestGridContainer');
        const tableHead = document.getElementById('docreqTableHead');

        if (tableHead && currentViewMode === 'list') {
            tableHead.style.display = '';
        } else if (tableHead) {
            tableHead.style.display = 'none';
        }

        if (gridContainer) {
            gridContainer.style.display = 'none';
            gridContainer.innerHTML = '';
        }

        if (listContainer) {
            listContainer.style.display = 'flex';
            listContainer.innerHTML = html;
        }

        const rowCountEl = document.getElementById('rowCount');
        if (rowCountEl) rowCountEl.textContent = 'Loading...';

        const pageInfo = document.getElementById('pageInfo');
        const pagControls = document.getElementById('pagControls');

        if (pageInfo) pageInfo.textContent = '';
        if (pagControls) pagControls.innerHTML = '';
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

            const option = document.querySelector(
                `#docreqStatusSelect .docreq-sort-option[data-value="${CSS.escape(key)}"]`
            );

            if (option) {
                option.dataset.count = value;
            }
        });

        updateStatusDropdownUI(activeFilter);
    }

    function getFiltered() {
        let data = allRequests;
        if (activeFilter !== 'all') data = data.filter(r => r.status === activeFilter);
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
        if (filterDocType) data = data.filter(r => r.document_type === filterDocType);
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
        return searchQuery !== '' || activeFilter !== 'all' || filterDocType !== '' || filterDateFrom !== '' ||
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
        if (activeFilter !== 'all') parts.push(activeFilter.charAt(0).toUpperCase() + activeFilter.slice(1));
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

        activeFilter = 'all';
        filterStatus = 'all';
        filterDocType = '';
        filterDateFrom = '';
        filterDateTo = '';
        filterSort = 'newest';
        currentPage = 1;

        updateStatusDropdownUI('all');

        window.syncFilterTagGroup('fSortGroup', 'newest');

        const dateFrom = document.getElementById('fDateFrom');
        const dateTo = document.getElementById('fDateTo');

        setCustomSelectValue('docTypeSelect', '');
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

        setCustomSelectValue('docTypeSelect', '');

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
            status: activeFilter || 'all',
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
                data.requests.map(normalizeDocreqRequest) :
                [];

            if (Array.isArray(data.types)) {
                documentTypeOptions = normalizeDocTypes(data.types);
                renderDocTypeOptions(documentTypeOptions);
            }

            if (data.stats) {
                updateStats(data.stats);
            }

            if (data.pagination) {
                currentPage = Number(data.pagination.current_page || 1);
                docreqPerPage = Number(data.pagination.per_page || docreqPerPage || 10);
                renderDocreqPagebar(data.pagination);
            }

            renderList();
            renderFilterChips();
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
        const tableHead = document.getElementById('docreqTableHead');
        const isMobile = window.innerWidth <= 767;

        renderDocreqPagebar(docreqPagination);

        const listContainer = document.getElementById('requestListContainer');
        const gridContainer = document.getElementById('requestGridContainer');

        if (listContainer) listContainer.innerHTML = '';
        if (gridContainer) gridContainer.innerHTML = '';

        if (!page.length) {
            const emptyHtml = buildEmptyStateHtml();

            if (tableHead) tableHead.style.display = 'none';

            if (currentViewMode === 'grid' && !isMobile) {
                if (listContainer) {
                    listContainer.style.display = 'none';
                    listContainer.innerHTML = '';
                }

                if (gridContainer) {
                    gridContainer.style.display = 'block';
                    gridContainer.innerHTML = emptyHtml;
                }
            } else {
                if (gridContainer) {
                    gridContainer.style.display = 'none';
                    gridContainer.innerHTML = '';
                }

                if (listContainer) {
                    listContainer.style.display = 'flex';
                    listContainer.innerHTML = emptyHtml;
                }
            }

            return;
        }

        if (isMobile) {
            if (tableHead) tableHead.style.display = 'none';

            if (gridContainer) {
                gridContainer.style.display = 'none';
                gridContainer.innerHTML = '';
            }

            if (listContainer) {
                listContainer.style.display = 'flex';
                listContainer.innerHTML = page.map(r => buildMobileCard(r)).join('');
            }
        } else if (currentViewMode === 'grid') {
            if (tableHead) tableHead.style.display = 'none';

            if (listContainer) {
                listContainer.style.display = 'none';
                listContainer.innerHTML = '';
            }

            if (gridContainer) {
                gridContainer.style.display = 'grid';
                gridContainer.innerHTML = page.map(r => buildGridCard(r)).join('');
            }
        } else {
            if (tableHead) tableHead.style.display = 'none';

            if (gridContainer) {
                gridContainer.style.display = 'none';
                gridContainer.innerHTML = '';
            }

            if (listContainer) {
                listContainer.style.display = 'flex';
                listContainer.innerHTML = page.map(r => buildDesktopRow(r)).join('');
            }
        }
    }

    function getPatientDisplayName(name) {
        const raw = String(name || '').trim();

        if (raw.includes(',')) {
            const [lastName, firstPart] = raw.split(',').map(part => part.trim());
            return `${firstPart} ${lastName}`.replace(/\s+/g, ' ').trim();
        }

        return raw;
    }

    function getRequestInitials(name) {
        const displayName = getPatientDisplayName(name);

        return String(displayName || '?')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map(part => part.charAt(0))
            .join('')
            .toUpperCase() || '?';
    }

    function getPatientPhotoUrl(r) {
        return r.patient_photo_url ||
            r.profile_photo_url ||
            r.profile_picture_url ||
            r.avatar_url ||
            r.photo_url ||
            '';
    }

    function buildPatientAvatar(r, className) {
        const displayName = getPatientDisplayName(r.patient_name);
        const initials = getRequestInitials(displayName);
        const photoUrl = getPatientPhotoUrl(r);

        if (!photoUrl) {
            return `<div class="${className}">${initials}</div>`;
        }

        return `
        <div class="${className} has-photo">
            <img
                src="${esc(photoUrl)}"
                alt="${esc(displayName)}"
                class="docreq-avatar-img"
                loading="lazy"
                onerror="this.parentElement.classList.remove('has-photo'); this.parentElement.textContent='${initials}';"
            >
        </div>
    `;
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
        const normalized = String(status || 'pending').replace(/_/g, '-').toLowerCase();

        if (normalized === 'approved' || normalized === 'ready' || normalized === 'released') {
            return 'badge-approved';
        }

        if (normalized === 'rejected') return 'badge-rejected';

        return 'badge-pending';
    }

    function buildRequestActions(r, layout = 'list') {
        const displayName = getPatientDisplayName(r.patient_name);
        const patientArg = jsStringArg(displayName);
        const statusLabel = getStatusLabel(r.status);
        const status = String(r.status || 'pending').replace(/_/g, '-').toLowerCase();
        const listClass = layout === 'list' ? 'docreq-list-action-btn' : '';

        const approveBtn = `
        <button type="button" class="btn-approve ${listClass}" onclick="event.stopPropagation(); openApprove(${r.id}, ${patientArg})">
            <i class="fa-solid fa-check"></i>
            <span>Approve</span>
        </button>
    `;

        const rejectBtn = `
        <button type="button" class="btn-reject ${listClass}" onclick="event.stopPropagation(); openReject(${r.id}, ${patientArg})">
            <i class="fa-solid fa-xmark"></i>
            <span>Reject</span>
        </button>
    `;

        if (status === 'pending') {
            return `${approveBtn}${rejectBtn}`;
        }

        return `<span class="docreq-mobile-state-note">${statusLabel}</span>`;
    }

    function buildDesktopRow(r) {
        const accentHex = getDocumentAccent(r.status);
        const badgeCls = getStatusBadgeClass(r.status);
        const statusLabel = getStatusLabel(r.status);
        const displayName = getPatientDisplayName(r.patient_name);
        const patientArg = jsStringArg(displayName);
        const avatarHtml = buildPatientAvatar(r, 'docreq-list-avatar');
        const program = r.sub_label ? esc(r.sub_label) : 'No ID set';

        const rowClick = `selectDocumentCard('d', ${r.id})`;
        const actionCol = `
    <div class="docreq-list-direct-actions">
        ${buildRequestActions(r, 'list')}
    </div>
`;

        const detail = '';

        return `
        <article class="docreq-list-row-modern desktop-req-row" id="row-d-${r.id}" onclick="${rowClick}" style="--card-accent:${accentHex};">
            <div class="docreq-list-main">
                <div class="docreq-list-profile">
                   ${avatarHtml}

                    <div class="docreq-list-person">
                        <div class="docreq-list-name">${esc(displayName)}</div>

                        <div class="docreq-list-subline">
                            <span class="status-badge ${badgeCls}">${statusLabel}</span>
                            <span class="docreq-id-pill docreq-program-pill">${program}</span>
                        </div>
                    </div>
                </div>

                <div class="docreq-list-meta">
                    <div class="docreq-list-info">
                        <i class="fa-regular fa-calendar"></i>
                        <div>
                            <span>Date & Time Requested</span>
<strong>${esc(r.request_date)}</strong>
<small>${esc(r.request_time)}</small>
                        </div>
                    </div>

                    <div class="docreq-list-info">
                        <i class="fa-regular fa-file-lines"></i>
                        <div>
                            <span>Document</span>
                            <strong>${esc(r.document_type)}</strong>
                        </div>
                    </div>

                    <div class="docreq-list-info docreq-list-info-purpose">
                        <i class="fa-solid fa-message"></i>
                        <div>
                            <span>Purpose</span>
                            <strong>${esc(r.purpose)}</strong>
                        </div>
                    </div>
                </div>

                <div class="docreq-list-action">
                    ${actionCol}
                </div>
            </div>
        </article>
    `;
    }

    function buildGridCard(r) {
        const accentHex = getDocumentAccent(r.status);
        const badgeCls = getStatusBadgeClass(r.status);
        const statusLabel = getStatusLabel(r.status);
        const displayName = getPatientDisplayName(r.patient_name);
        const patientArg = jsStringArg(displayName);
        const avatarHtml = buildPatientAvatar(r, 'docreq-grid-avatar');
        const program = r.sub_label ? esc(r.sub_label) : 'No ID set';
        const actions = `
        <div class="docreq-grid-actions">
            ${buildRequestActions(r, 'grid')}
        </div>
    `;

        return `
        <article class="docreq-grid-card docreq-grid-card-modern" id="row-g-${r.id}" onclick="selectDocumentCard('g', ${r.id})" style="--card-accent:${accentHex};">
            <div class="docreq-grid-head-modern">
                <div class="docreq-grid-profile">
                    ${avatarHtml}

                    <div class="docreq-grid-person">
                        <div class="docreq-grid-name">${esc(displayName)}</div>
                        <span class="docreq-id-pill docreq-grid-sub">${program}</span>

                        <div class="docreq-grid-status-row">
                            <span class="status-badge docreq-card-status ${badgeCls}">${statusLabel}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="docreq-grid-meta-modern">
                <div class="docreq-info-tile docreq-info-tile-date-time">
                    <div class="docreq-info-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <div class="docreq-info-copy">
                        <div class="docreq-grid-label">Date & Time Requested</div>
                        <div class="docreq-grid-value">${esc(r.request_date)}</div>
                        <small class="docreq-grid-subvalue">${esc(r.request_time)}</small>
                    </div>
                </div>

                <div class="docreq-info-tile">
                    <div class="docreq-info-icon">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>
                    <div class="docreq-info-copy">
                        <div class="docreq-grid-label">Document</div>
                        <div class="docreq-grid-value">${esc(r.document_type)}</div>
                    </div>
                </div>

                <div class="docreq-info-tile docreq-info-tile-purpose">
                    <div class="docreq-info-icon">
                        <i class="fa-solid fa-message"></i>
                    </div>
                    <div class="docreq-info-copy">
                        <div class="docreq-grid-label">Purpose</div>
                        <div class="docreq-grid-value">${esc(r.purpose)}</div>
                    </div>
                </div>
            </div>

            ${actions}
        </article>
    `;
    }

    function buildEmptyStateHtml() {
        const isSearchMiss = searchQuery !== '';
        const isDataEmpty = allRequests.length === 0;

        const statusEmptyCopy = {
            pending: {
                stateClass: 'empty-pending',
                iconHtml: '<i class="fa-solid fa-clock-rotate-left"></i>',
                title: 'No pending requests',
                subtitle: 'Pending document requests will appear here once submitted.'
            },
            approved: {
                stateClass: 'empty-approved',
                iconHtml: '<i class="fa-solid fa-file-circle-check"></i>',
                title: 'No approved requests',
                subtitle: 'Approved document requests will appear here after review.'
            },
            rejected: {
                stateClass: 'empty-rejected',
                iconHtml: '<i class="fa-solid fa-file-circle-xmark"></i>',
                title: 'No rejected requests',
                subtitle: 'Rejected document requests will appear here when applicable.'
            }
        };

        let stateClass = 'empty-neutral';
        let iconHtml = '<i class="fa-regular fa-folder-open"></i>';
        let title = 'No document requests yet';
        let subtitle = 'Incoming patient document requests will appear here once submitted.';
        let buttonHtml = '';

        if (isSearchMiss) {
            stateClass = 'empty-search';
            iconHtml = '<i class="fa-solid fa-magnifying-glass"></i>';
            title = `No results for "${esc(searchQuery)}"`;
            subtitle = 'Try another patient name or clear the search to see all requests.';
            buttonHtml = `
            <button type="button"
                data-clear-search
                data-search-target="#searchInput"
                class="empty-state-btn">
                <i class="fa-solid fa-xmark"></i>
                Clear search
            </button>
        `;
        } else if (activeFilter !== 'all') {
            const copy = statusEmptyCopy[activeFilter] || {
                stateClass: 'empty-filter',
                iconHtml: '<i class="fa-solid fa-filter-circle-xmark"></i>',
                title: 'No matching requests found',
                subtitle: 'No document requests are available for this status.'
            };

            stateClass = copy.stateClass;
            iconHtml = copy.iconHtml;
            title = copy.title;
            subtitle = copy.subtitle;
        } else if (!isDataEmpty) {
            stateClass = 'empty-filter';
            iconHtml = '<i class="fa-solid fa-filter-circle-xmark"></i>';
            title = 'No matching requests found';
            subtitle = 'No document requests match the selected filters.';
        }

        return `
        <div class="empty-state ${stateClass}">
            <div class="empty-state-icon">${iconHtml}</div>
            <p class="empty-state-title">${title}</p>
            <p class="empty-state-sub">${subtitle}</p>
            ${buttonHtml}
        </div>
    `;
    }

    function buildMobileCard(r) {
        const accentHex = getDocumentAccent(r.status);
        const badgeCls = getStatusBadgeClass(r.status);
        const statusLabel = getStatusLabel(r.status);
        const displayName = getPatientDisplayName(r.patient_name);
        const patientArg = jsStringArg(displayName);
        const avatarHtml = buildPatientAvatar(r, 'docreq-mobile-avatar');
        const program = r.sub_label ? esc(r.sub_label) : 'No ID set';

        const rowClick = `selectDocumentCard('m', ${r.id})`;
        const mobileActions = buildRequestActions(r, 'mobile');

        return `
        <article class="docreq-mobile-card" id="row-m-${r.id}" onclick="${rowClick}" style="--card-accent:${accentHex};">
            <div class="docreq-mobile-head">
                <div class="docreq-mobile-profile">
                    ${avatarHtml}

                    <div class="docreq-mobile-person">
                        <div class="mobile-patient-name">${esc(displayName)}</div>
                        <span class="docreq-id-pill mobile-sub-label">${program}</span>
                    </div>
                </div>

                <span class="status-badge docreq-card-status ${badgeCls}">${statusLabel}</span>
            </div>

            <div class="docreq-mobile-meta">
    <div class="wide docreq-mobile-date-time">
        <i class="fa-regular fa-calendar"></i>
        <span>Date & Time Requested</span>
        <strong>${esc(r.request_date)}</strong>
        <small class="docreq-mobile-subvalue">${esc(r.request_time)}</small>
    </div>

    <div class="wide">
        <i class="fa-regular fa-file-lines"></i>
        <span>Document</span>
        <strong>${esc(r.document_type)}</strong>
    </div>

    <div class="wide">
        <i class="fa-solid fa-message"></i>
        <span>Purpose</span>
        <strong>${esc(r.purpose)}</strong>
    </div>
</div>

            <div class="docreq-mobile-footer docreq-mobile-footer-actions">
    ${mobileActions}
</div>
        </article>
    `;
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

    function updateDocreqPerPageUI(value) {
        const allowed = ['10', '20', '50', '100'];
        const selectedValue = allowed.includes(String(value)) ? String(value) : '10';

        const hiddenInput = document.getElementById('docreqPerPageSelect');

        if (hiddenInput) hiddenInput.value = selectedValue;

        window.syncGlobalPageSizeSelect?.(hiddenInput, selectedValue);
    }

    function selectDocreqPerPage(value) {
        const selectedValue = Number(value) || 10;

        docreqPerPage = selectedValue;
        currentPage = 1;

        updateDocreqPerPageUI(selectedValue);
        closeDocreqDropdowns();
        fetchDocRequests();
    }

    window.selectDocreqPerPage = selectDocreqPerPage;

    window.initGlobalPageSizeSelects?.();

    function renderDocreqPagebar(p) {
        if (!p) return;

        docreqPagination = p;

        const from = Number(p.from || 0);
        const to = Number(p.to || 0);
        const total = Number(p.total || 0);

        const infoHtml = total > 0 ?
            `Showing <strong>${from}–${to}</strong> of <strong>${total}</strong> requests` :
            'Showing <strong>0</strong> requests';

        document.querySelectorAll('.docreq-pagebar .sl-pagebar-info').forEach((el) => {
            el.innerHTML = infoHtml;
        });

        const navHtml = buildDocreqPagination(p);

        document.querySelectorAll('.docreq-pagination-wrap').forEach((el) => {
            el.innerHTML = navHtml;
        });

        if (p.per_page) {
            updateDocreqPerPageUI(p.per_page);
        }

        const rowCountEl = document.getElementById('rowCount');
        if (rowCountEl) {
            rowCountEl.textContent = `${total} ${total === 1 ? 'request' : 'requests'}`;
        }
    }

    function buildDocreqPagination(p) {
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

        let html = '<nav class="sl-pagination" aria-label="Document requests pagination">';

        html += current <= 1 ?
            '<button type="button" disabled class="sl-page-disabled" aria-label="Previous page"><i class="fa-solid fa-chevron-left sl-page-icon"></i></button>' :
            `<button type="button" onclick="docreqGoPage(${current - 1})" class="sl-page-btn" aria-label="Previous page"><i class="fa-solid fa-chevron-left sl-page-icon"></i></button>`;

        if (start > 1) {
            html += '<button type="button" onclick="docreqGoPage(1)" class="sl-page-btn">1</button>';
            if (start > 2) html += '<span class="sl-page-ellipsis" aria-hidden="true">&hellip;</span>';
        }

        for (let i = start; i <= end; i++) {
            html += i === current ?
                `<span class="sl-page-current" aria-current="page">${i}</span>` :
                `<button type="button" onclick="docreqGoPage(${i})" class="sl-page-btn">${i}</button>`;
        }

        if (end < last) {
            if (end < last - 1) html += '<span class="sl-page-ellipsis" aria-hidden="true">&hellip;</span>';
            html += `<button type="button" onclick="docreqGoPage(${last})" class="sl-page-btn">${last}</button>`;
        }

        html += current >= last ?
            '<button type="button" disabled class="sl-page-disabled" aria-label="Next page"><i class="fa-solid fa-chevron-right sl-page-icon"></i></button>' :
            `<button type="button" onclick="docreqGoPage(${current + 1})" class="sl-page-btn" aria-label="Next page"><i class="fa-solid fa-chevron-right sl-page-icon"></i></button>`;

        html += '</nav>';

        return html;
    }

    function docreqGoPage(page) {
        currentPage = Number(page) || 1;
        fetchDocRequests();
    }

    window.docreqGoPage = docreqGoPage;

    function toggleDocreqDropdown(selectId) {
        const wrap = document.getElementById(selectId);
        if (!wrap) return;

        const isOpen = wrap.classList.contains('open');
        closeDocreqDropdowns();

        if (!isOpen) {
            wrap.classList.add('open');
            wrap.querySelector('[data-select-button]')?.setAttribute('aria-expanded', 'true');
        }
    }

    function closeDocreqDropdowns() {
        document.querySelectorAll('.docreq-custom-select.open').forEach((wrap) => {
            wrap.classList.remove('open');
            wrap.querySelector('[data-select-button]')?.setAttribute('aria-expanded', 'false');
        });
    }

    function setCustomSelectValue(selectId, value, label = null) {
        const wrap = document.getElementById(selectId);
        if (!wrap) return;

        const hiddenInput = selectId === 'docTypeSelect' ? document.getElementById('fDocType') : null;
        const selected = wrap.querySelector(`.docreq-select-option[data-value="${CSS.escape(value)}"]`);
        const finalLabel = label || selected?.querySelector('.docreq-option-copy strong')?.textContent ||
            'All document types';

        if (hiddenInput) hiddenInput.value = value;

        wrap.querySelectorAll('.docreq-select-option').forEach((option) => {
            option.classList.toggle('active', option.getAttribute('data-value') === value);
        });

        const labelEl = document.getElementById(`${selectId}Label`);
        if (labelEl) labelEl.textContent = finalLabel;
    }

    function normalizeDocTypes(types = []) {
        return [...new Set(
            types
                .map(type => String(type || '').trim())
                .filter(Boolean)
        )].sort((a, b) => a.localeCompare(b));
    }

    function getDocTypeIcon(type = '') {
        const text = String(type).toLowerCase();

        if (text.includes('annual')) return 'fa-file-signature';
        if (text.includes('clearance')) return 'fa-clipboard-check';
        if (text.includes('record')) return 'fa-folder-open';
        if (text.includes('certificate')) return 'fa-certificate';
        if (text.includes('case')) return 'fa-briefcase-medical';
        if (text.includes('treatment')) return 'fa-notes-medical';
        if (text.includes('referral')) return 'fa-envelope-open-text';

        return 'fa-file-lines';
    }

    function getDocTypeTone(type = '') {
        const tones = [
            'doc-type-blue',
            'doc-type-orange',
            'doc-type-green',
            'doc-type-red',
            'doc-type-purple',
            'doc-type-cyan',
            'doc-type-amber'
        ];

        const text = String(type || 'Document');
        let hash = 0;

        for (let i = 0; i < text.length; i++) {
            hash = ((hash << 5) - hash) + text.charCodeAt(i);
            hash |= 0;
        }

        return tones[Math.abs(hash) % tones.length];
    }

    function createDocTypeOption(value, label, isAll = false) {
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'docreq-select-option doc-type-option';
        button.dataset.value = value;
        button.setAttribute('role', 'option');

        button.addEventListener('click', () => {
            setDocTypeFilter(value, label);
        });

        const iconWrap = document.createElement('span');
        iconWrap.className = `docreq-option-icon ${isAll ? 'doc-type-all' : getDocTypeTone(label)}`;

        const icon = document.createElement('i');
        icon.className = `fa-solid ${isAll ? 'fa-layer-group' : getDocTypeIcon(label)}`;
        iconWrap.appendChild(icon);

        const copy = document.createElement('span');
        copy.className = 'docreq-option-copy';

        const strong = document.createElement('strong');
        strong.textContent = label;

        const small = document.createElement('small');
        small.textContent = isAll ? 'No document type filter' : 'From document requests';

        copy.appendChild(strong);
        copy.appendChild(small);

        const check = document.createElement('i');
        check.className = 'fa-solid fa-check docreq-option-check';

        button.appendChild(iconWrap);
        button.appendChild(copy);
        button.appendChild(check);

        return button;
    }

    function renderDocTypeOptions(types = []) {
        const menu = document.getElementById('docTypeSelectMenu');
        if (!menu) return;

        const cleanTypes = normalizeDocTypes(types);

        menu.innerHTML = '';
        menu.appendChild(createDocTypeOption('', 'All document types', true));

        cleanTypes.forEach(type => {
            menu.appendChild(createDocTypeOption(type, type));
        });

        setCustomSelectValue(
            'docTypeSelect',
            filterDocType,
            filterDocType || 'All document types'
        );
    }

    function setDocTypeFilter(value, label) {
        filterDocType = value;
        setCustomSelectValue('docTypeSelect', value, label);
        closeDocreqDropdowns();
        renderFilterChips();
        updateShowResultsButton();
    }

    function getStatusMeta(status) {
        const map = {
            all: {
                label: 'All Requests',
                icon: 'fa-file-medical',
                tone: 'status-all',
                countId: 'statAll'
            },
            pending: {
                label: 'Pending',
                icon: 'fa-clock-rotate-left',
                tone: 'status-pending',
                countId: 'statPending'
            },
            approved: {
                label: 'Approved',
                icon: 'fa-file-circle-check',
                tone: 'status-approved',
                countId: 'statApproved'
            },
            rejected: {
                label: 'Rejected',
                icon: 'fa-file-circle-xmark',
                tone: 'status-rejected',
                countId: 'statRejected'
            }
        };

        return map[status] || map.all;
    }

    function getStatusCount(status) {
        const meta = getStatusMeta(status);
        const option = document.querySelector(
            `#docreqStatusSelect .docreq-sort-option[data-value="${CSS.escape(status)}"]`
        );

        return option?.dataset.count ||
            document.getElementById(meta.countId)?.textContent?.trim() ||
            '0';
    }

    function updateStatusDropdownUI(status) {
        if (!DOCREQ_DROPDOWN_STATUSES.includes(status)) {
            status = 'all';
        }

        const meta = getStatusMeta(status);
        const wrap = document.getElementById('docreqStatusSelect');
        const hiddenInput = document.getElementById('docreqStatusFilter');
        const label = document.getElementById('statusDropdownLabel');
        const count = document.getElementById('statusDropdownCount');
        const leading = document.getElementById('docreqStatusSelectedIcon');

        if (wrap) wrap.dataset.statusFilter = status;
        if (hiddenInput) hiddenInput.value = status;
        if (label) label.textContent = meta.label;
        if (count) count.textContent = getStatusCount(status);

        if (leading) {
            leading.className = `docreq-sort-icon ${meta.tone}`;
            leading.innerHTML = `<i class="fa-solid ${meta.icon}"></i>`;
        }

        document.querySelectorAll('#docreqStatusSelect .docreq-sort-option').forEach((option) => {
            const active = option.getAttribute('data-value') === status;
            option.classList.toggle('is-active', active);
            option.classList.toggle('active', active);
        });
    }

    function selectStatusFilter(status) {
        if (!DOCREQ_DROPDOWN_STATUSES.includes(status)) {
            status = 'all';
        }

        closeDocreqDropdowns();
        setFilter(status);
    }

    function setFilter(f) {
        if (!DOCREQ_DROPDOWN_STATUSES.includes(f)) {
            f = 'all';
        }

        activeFilter = f;
        filterStatus = f;
        currentPage = 1;

        updateStatusDropdownUI(f);
        updateFilterBtn();
        renderFilterChips();
        fetchDocRequests();
    }

    let docreqSearchTimer = null;

    function onSearch(input) {
        searchQuery = input.value.trim();
        currentPage = 1;

        clearTimeout(docreqSearchTimer);
        docreqSearchTimer = setTimeout(() => {
            fetchDocRequests(true);
        }, 350);
    }

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
        const notes = document.getElementById('rejectNotes');

        document.getElementById('rejectRequestId').value = id;
        fillDecisionModal('reject', id, name);

        if (notes) {
            notes.value = '';
            if (window.updateCharCounter) {
                window.updateCharCounter('rejectNotes', 150, 'rejectNotesCharCounter');
            }
        }

        window.openModal('rejectModal');
        setTimeout(() => notes?.focus(), 80);
    }


    function openFilterModal() {
        window.syncFilterTagGroup('fSortGroup', filterSort);
        setCustomSelectValue('docTypeSelect', filterDocType);
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
        filterStatus = activeFilter;
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
            status: activeFilter,
            docType: document.getElementById('fDocType')?.value || '',
            dateFrom: document.getElementById('fDateFrom')?.value || '',
            dateTo: document.getElementById('fDateTo')?.value || '',
            sort: sortActive ? sortActive.getAttribute('data-val') : 'newest'
        };
    }

    function getDraftFilteredDocRequests() {
        const oldActiveFilter = activeFilter;
        const oldFilterStatus = filterStatus;
        const oldFilterDocType = filterDocType;
        const oldFilterDateFrom = filterDateFrom;
        const oldFilterDateTo = filterDateTo;
        const oldFilterSort = filterSort;

        const draft = getDraftDocRequestFilters();

        activeFilter = draft.status;
        filterStatus = draft.status;
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
        const count = getDraftFilteredDocRequests().length;
        window.updateShowResultsText(count);
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
            addChip(`Doc: ${docType}`, () => setCustomSelectValue('docTypeSelect', ''));
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
                setCustomSelectValue('docTypeSelect', '');
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

        setCustomSelectValue('docTypeSelect', '');

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

        const docreqViewToggle = document.querySelector('#mainContent [data-global-view-toggle]');

        window.initGlobalViewToggles?.(document);

        const savedViewMode = window.getGlobalViewMode?.(docreqViewToggle) ||
            localStorage.getItem('ViewToggleMode') ||
            'list';

        currentViewMode = savedViewMode === 'grid' ? 'grid' : 'list';

        window.setGlobalViewMode?.(docreqViewToggle, currentViewMode, {
            persist: false
        });

        docreqViewToggle?.addEventListener('global-view-change', (event) => {
            currentViewMode = event.detail?.mode === 'grid' ? 'grid' : 'list';
            renderList();
        });

        window.addEventListener('resize', renderList);

        updateStatusDropdownUI(activeFilter);
        setCustomSelectValue('docTypeSelect', filterDocType);

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.docreq-custom-select')) closeDocreqDropdowns();
        });

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

        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            closeDocreqDropdowns();
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

        ['approveCancelBtn', 'approveCancelBtn2'].forEach(id =>
            document.getElementById(id)?.addEventListener('click', () => window.closeModal('approveModal'))
        );

        document.getElementById('approveConfirmBtn')?.addEventListener('click', async () => {
            const id = document.getElementById('approveRequestId').value;
            const btn = document.getElementById('approveConfirmBtn');

            if (!id) return;

            btn.disabled = true;

            try {
                const res = await fetch(`/admin/document-requests/${id}/approve`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({})
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    throw new Error(data.message || `Approval failed. Status: ${res.status}`);
                }

                window.closeModal('approveModal');
                syncLocalDocRequestStatus(id, 'approved');

                docreqToast(
                    'success',
                    'Request approved',
                    data.message ||
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

        ['rejectCancelBtn', 'rejectCancelBtn2'].forEach(id =>
            document.getElementById(id)?.addEventListener('click', () => window.closeModal('rejectModal'))
        );

        document.getElementById('rejectConfirmBtn')?.addEventListener('click', async () => {
            const id = document.getElementById('rejectRequestId').value;
            const btn = document.getElementById('rejectConfirmBtn');
            const notes = document.getElementById('rejectNotes')?.value.trim() || '';

            if (!id) return;

            if (window.validateCharLimit && !window.validateCharLimit('rejectNotes', 150,
                'err-rejectNotes')) {
                document.getElementById('rejectNotes')?.focus();
                return;
            }

            btn.disabled = true;

            try {
                const res = await fetch(`/admin/document-requests/${id}/reject`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        reason: notes
                    })
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    throw new Error(data.message || `Rejection failed. Status: ${res.status}`);
                }

                window.closeModal('rejectModal');
                syncLocalDocRequestStatus(id, 'rejected', {
                    rejection_reason: notes
                });

                docreqToast(
                    'success',
                    'Request rejected',
                    data.message ||
                    'The document request has been rejected. The patient will be notified.'
                );
            } catch (error) {
                console.error('Reject request error:', error);

                docreqToast(
                    'error',
                    'Rejection failed',
                    error.message ||
                    'Rejection failed because of a network or JavaScript error.'
                );
            } finally {
                btn.disabled = false;
            }
        });

        updateDocreqPerPageUI(docreqPerPage || 10);

        loadData();
        initDocreqRefreshWatcher();
    });
</script>
@endsection