@extends('layouts.dentist')

@section('title', 'Document Request | PUP Taguig Dental Clinic')

@section('content')

@php
$notifications = collect($notifications ?? []);
$notifCount = $notifications->count();
@endphp

<main id="mainContent" class="dentist-page-shell page-enter docreq-page">
    <div class="w-full">

        <div class="docreq-header-wrap">
            <div class="dentist-hero">
                <div class="dentist-hero-content">
                    <div class="dentist-hero-icon">
                        <i class="fa-solid fa-file-circle-check"></i>
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
        </div>

        <div class="table-card rounded-2xl border border-gray-200 shadow-sm overflow-hidden bg-white">

            <div class="px-4 md:px-6 py-3.5 border-b border-gray-100 bg-[#FAFAFA]/50">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">

                    <div class="order-2 md:order-1">
                        <span id="rowCount"
                            class="text-[11px] md:text-sm font-bold text-gray-400 uppercase tracking-wider">
                            0 requests
                        </span>
                    </div>

                    <div
                        class="docreq-toolbar-actions flex items-center gap-2 order-1 md:order-2 w-full md:w-auto justify-end">

                        <div id="docreqStatusSelect"
                            class="docreq-custom-select docreq-status-dropdown docreq-toolbar-sort">
                            <button type="button" class="docreq-select-button" data-select-button
                                aria-haspopup="listbox" aria-expanded="false" aria-controls="docreqStatusMenu"
                                onclick="toggleDocreqDropdown('docreqStatusSelect')">
                                <span class="docreq-select-leading status-all">
                                    <i class="fa-solid fa-file-medical"></i>
                                </span>

                                <span class="docreq-select-text">
                                    <span>Sort by</span>
                                    <strong id="statusDropdownLabel">All Requests</strong>
                                </span>

                                <span id="statusDropdownCount" class="docreq-select-count">0</span>
                                <i class="fa-solid fa-chevron-down docreq-select-chevron"></i>
                            </button>

                            <div id="docreqStatusMenu" class="docreq-select-menu" role="listbox">
                                <button type="button" class="docreq-select-option active" data-value="all"
                                    onclick="selectStatusFilter('all')">
                                    <span class="docreq-option-icon status-all">
                                        <i class="fa-solid fa-file-medical"></i>
                                    </span>
                                    <span class="docreq-option-copy">
                                        <strong>All Requests</strong>
                                        <small>Show every document request</small>
                                    </span>
                                    <span class="docreq-option-count" id="statAll">0</span>
                                </button>

                                <button type="button" class="docreq-select-option" data-value="pending"
                                    onclick="selectStatusFilter('pending')">
                                    <span class="docreq-option-icon status-pending">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </span>
                                    <span class="docreq-option-copy">
                                        <strong>Pending</strong>
                                        <small>Needs review</small>
                                    </span>
                                    <span class="docreq-option-count" id="statPending">0</span>
                                </button>

                                <button type="button" class="docreq-select-option" data-value="approved"
                                    onclick="selectStatusFilter('approved')">
                                    <span class="docreq-option-icon status-approved">
                                        <i class="fa-solid fa-file-circle-check"></i>
                                    </span>
                                    <span class="docreq-option-copy">
                                        <strong>Approved</strong>
                                        <small>Ready for preparation</small>
                                    </span>
                                    <span class="docreq-option-count" id="statApproved">0</span>
                                </button>

                                <button type="button" class="docreq-select-option" data-value="rejected"
                                    onclick="selectStatusFilter('rejected')">
                                    <span class="docreq-option-icon status-rejected">
                                        <i class="fa-solid fa-file-circle-xmark"></i>
                                    </span>
                                    <span class="docreq-option-copy">
                                        <strong>Rejected</strong>
                                        <small>Not approved</small>
                                    </span>
                                    <span class="docreq-option-count" id="statRejected">0</span>
                                </button>
                            </div>
                        </div>

                        <div class="docreq-search-wrap flex-1 md:flex-none flex items-center gap-2">
                            <div class="search-wrap global-search flex-1 md:w-64" data-search-wrapper>
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                                <input id="searchInput" type="text" placeholder="Search patient name…" data-search-input
                                    class="search-input" oninput="onSearch(this)" />

                                <button type="button" class="search-clear" data-search-clear aria-label="Clear search">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <div class="view-toggle-container hidden md:flex" id="docreqViewToggle">
                            <div class="view-slider"></div>

                            <button id="btnListView" type="button" onclick="setViewMode('list', this)"
                                class="btn-view-mode active" data-view="list" title="List View">
                                <i class="fa-solid fa-list text-sm"></i>
                            </button>

                            <button id="btnGridView" type="button" onclick="setViewMode('grid', this)"
                                class="btn-view-mode" data-view="grid" title="Grid View">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>

                        <button id="filterBtn" type="button" onclick="openFilterModal()" class="global-filter-btn">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Filter</span>
                            <span id="filterBadge" class="filter-badge" style="display:none;"></span>
                        </button>

                        <button id="externalClearFilterBtn" type="button" onclick="resetAdvancedFilters()"
                            class="global-filter-reset-btn hidden" title="Reset filters">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="docreqTableHead"
                class="hidden md:grid gap-3 text-[10px] font-bold uppercase tracking-wider text-gray-500 py-3.5 px-6 bg-[#FAFAFA] border-b border-gray-200"
                style="grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1.5fr) minmax(0, 1.5fr) minmax(0, 1fr) 100px;">
                <div class="text-left">Patient</div>
                <div class="flex items-center gap-1.5"><i class="fa-regular fa-calendar text-[10px]"></i>Date
                    Requested</div>
                <div class="text-left">Document</div>
                <div class="text-left">Purpose</div>
                <div class="text-left">Status</div>
                <div class="text-right">Actions</div>
            </div>

            <div id="requestListContainer" class="docreq-list-container"></div>
            <div id="requestGridContainer" class="docreq-grid"></div>

            <div class="tfoot-bar">
                <span style="font-size:12px;color:#9A9490;" id="pageInfo"></span>
                <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;" id="pagControls"></div>
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

<div id="approvedResultModal" class="modal-overlay">
    <div class="modal-box-inner">
        <div
            style="background:linear-gradient(135deg,#15803d,#16a34a);padding:2.5rem 2rem;text-align:center;color:#fff;">
            <div
                style="width:58px;height:58px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .9rem;">
                <i class="fa-solid fa-circle-check" style="font-size:1.7rem;"></i>
            </div>
            <div style="font-size:1.55rem;margin-bottom:.5rem;">Request Approved!</div>
            <p style="font-size:.82rem;opacity:.85;line-height:1.6;">The document has been approved and will
                be<br>prepared
                for printing. The patient will be notified.</p>
            <button id="approvedResultClose"
                style="margin-top:1.4rem;background:rgba(255,255,255,.2);color:#fff;border:2px solid rgba(255,255,255,.35);border-radius:9px;padding:.5rem 1.5rem;font-weight:700;cursor:pointer;font-size:.83rem;">
                Done
            </button>
        </div>
    </div>
</div>

<div id="rejectedResultModal" class="modal-overlay">
    <div class="modal-box-inner">
        <div
            style="background:linear-gradient(135deg,#991b1b,#b91c1c);padding:2.5rem 2rem;text-align:center;color:#fff;">
            <div
                style="width:58px;height:58px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .9rem;">
                <i class="fa-solid fa-circle-xmark" style="font-size:1.7rem;"></i>
            </div>
            <div style="font-size:1.55rem;margin-bottom:.5rem;">Request Rejected</div>
            <p style="font-size:.82rem;opacity:.85;line-height:1.6;">The request has been rejected. The patient<br>will
                be
                notified of the decision.</p>
            <button id="rejectedResultClose"
                style="margin-top:1.4rem;background:rgba(255,255,255,.2);color:#fff;border:2px solid rgba(255,255,255,.35);border-radius:9px;padding:.5rem 1.5rem;font-weight:700;cursor:pointer;font-size:.83rem;">
                Done
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let allRequests = [];
    let activeFilter = 'all';
    let searchQuery = '';
    const PER_PAGE = 8;
    let currentPage = 1;
    let filterStatus = 'all';
    let filterDocType = '';
    let filterDateFrom = '';
    let filterDateTo = '';
    let filterSort = 'newest';
    let documentTypeOptions = [];

    let currentViewMode = window.innerWidth <= 767 ? 'grid' : 'list';

    function setViewMode(mode, btn) {
        const mainContent = document.getElementById('mainContent');
        currentViewMode = mode;

        if (mainContent) {
            mainContent.classList.toggle('mode-grid', mode === 'grid');
            mainContent.classList.toggle('mode-list', mode !== 'grid');
        }

        document.querySelectorAll('.btn-view-mode').forEach(function (b) {
            b.classList.remove('active');
        });

        const targetBtn = btn || document.querySelector('.btn-view-mode[data-view="' + mode + '"]');
        if (targetBtn) targetBtn.classList.add('active');

        if (window.innerWidth > 767) {
            localStorage.setItem('docreqViewMode', mode);
        }
        renderList();
    }

    async function loadData() {
        showSkeleton();
        try {
            const res = await fetch('/dentist/document-requests/data', {
                cache: 'no-store'
            });
            const json = await res.json();

            allRequests = json.requests || [];

            documentTypeOptions = normalizeDocTypes(
                Array.isArray(json.types) && json.types.length
                    ? json.types
                    : allRequests.map(r => r.document_type)
            );

            if (filterDocType && !documentTypeOptions.includes(filterDocType)) {
                filterDocType = '';
            }

            renderDocTypeOptions(documentTypeOptions);
            updateStats(json.stats || {});
            renderList();
        } catch (e) {
            const listContainer = document.getElementById('requestListContainer');
            const gridContainer = document.getElementById('requestGridContainer');
            const tableHead = document.getElementById('docreqTableHead');

            if (tableHead) tableHead.style.display = 'none';
            if (gridContainer) {
                gridContainer.style.display = 'none';
                gridContainer.innerHTML = '';
            }
            console.error('Document request load failed:', e);

            const rowCountEl = document.getElementById('rowCount');
            const pageInfoEl = document.getElementById('pageInfo');

            if (rowCountEl) rowCountEl.textContent = '0 requests';
            if (pageInfoEl) pageInfoEl.textContent = '';

            if (listContainer) {
                listContainer.style.display = 'flex';
                listContainer.style.flexDirection = 'column';
                listContainer.innerHTML = `
        <div class="empty-state-wrapper compact">
            <div class="empty-icon-box"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="empty-title">Failed to load requests</div>
            <div class="empty-sub">Could not fetch document requests. Please refresh the page and try again.</div>
        </div>`;
            }
        }
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

        document.getElementById('pageInfo').textContent = '';
        document.getElementById('pagControls').innerHTML = '';
    }

    function updateStats(stats) {
        stats = stats || {};
        const values = {
            all: stats.all ?? 0,
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
            ids[key].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.textContent = values[key];
            });
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

                return displayName.includes(q) || rawName.includes(q) || identifier.includes(q);
            });
        }
        if (filterDocType) data = data.filter(r => r.document_type === filterDocType);
        if (filterDateFrom) {
            const from = new Date(filterDateFrom);
            data = data.filter(r => new Date(r.request_date) >= from);
        }
        if (filterDateTo) {
            const to = new Date(filterDateTo);
            to.setHours(23, 59, 59, 999);
            data = data.filter(r => new Date(r.request_date) <= to);
        }
        data = [...data].sort((a, b) => {
            if (filterSort === 'oldest') return new Date(a.request_date) - new Date(b.request_date);
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
            return new Date(b.request_date) - new Date(a.request_date);
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

        window.syncFilterTagGroup('fStatusGroup', filterStatus);
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
        renderList();
    }

    function resetAdvancedFilters() {
        filterDocType = '';
        filterDateFrom = '';
        filterDateTo = '';
        filterSort = 'newest';
        currentPage = 1;

        if (window.syncFilterTagGroup) {
            window.syncFilterTagGroup('fSortGroup', 'newest');
            window.syncFilterTagGroup('fStatusGroup', filterStatus);
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
        renderList();
    }

    function renderList() {
        const filtered = getFiltered();
        const total = filtered.length;
        const tableHead = document.getElementById('docreqTableHead');
        const isMobile = window.innerWidth <= 767;
        const lastPage = Math.max(1, Math.ceil(total / PER_PAGE));
        if (currentPage > lastPage) currentPage = lastPage;
        const start = (currentPage - 1) * PER_PAGE;
        const page = filtered.slice(start, start + PER_PAGE);

        const rowCountEl = document.getElementById('rowCount');
        if (rowCountEl) rowCountEl.textContent = `${total} ${total === 1 ? 'request' : 'requests'}`;

        document.getElementById('pageInfo').textContent =
            total === 0 ? '' : `Showing ${start + 1}–${Math.min(start + PER_PAGE, total)} of ${total} requests`;

        renderPagination(total, lastPage);

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

        // Converts: "Romero, Dianna Rain Margaja" -> "Dianna Rain Margaja Romero"
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
        return String(status || 'pending').charAt(0).toUpperCase() + String(status || 'pending').slice(1);
    }

    function jsStringArg(value) {
        return JSON.stringify(String(value ?? '')).replace(/"/g, '&quot;');
    }

    function buildDesktopRow(r) {
        const accentHex = getDocumentAccent(r.status);
        const badgeCls = r.status === 'approved' ? 'badge-approved' : r.status === 'rejected' ? 'badge-rejected' :
            'badge-pending';
        const statusLabel = getStatusLabel(r.status);
        const displayName = getPatientDisplayName(r.patient_name);
        const patientArg = jsStringArg(displayName);
        const avatarHtml = buildPatientAvatar(r, 'docreq-list-avatar');
        const program = r.sub_label ? esc(r.sub_label) : 'No ID set';

        const rowClick = `selectDocumentCard('d', ${r.id})`;

        const actionCol = r.status === 'pending' ? `
    <div class="docreq-list-direct-actions">
        <button type="button" class="btn-approve docreq-list-action-btn" onclick="event.stopPropagation(); openApprove(${r.id}, ${patientArg})">
            <i class="fa-solid fa-check"></i>
            <span>Approve</span>
        </button>

        <button type="button" class="btn-reject docreq-list-action-btn" onclick="event.stopPropagation(); openReject(${r.id}, ${patientArg})">
            <i class="fa-solid fa-xmark"></i>
            <span>Reject</span>
        </button>
    </div>
` : `
    <span class="docreq-list-state-note">
        <i class="fa-solid fa-circle-check"></i>
        ${statusLabel}
    </span>
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
        const badgeCls = r.status === 'approved' ? 'badge-approved' : r.status === 'rejected' ? 'badge-rejected' :
            'badge-pending';
        const statusLabel = getStatusLabel(r.status);
        const displayName = getPatientDisplayName(r.patient_name);
        const patientArg = jsStringArg(displayName);
        const avatarHtml = buildPatientAvatar(r, 'docreq-grid-avatar');
        const program = r.sub_label ? esc(r.sub_label) : 'No ID set';

        const actions = r.status === 'pending' ? `
        <div class="docreq-grid-actions">
            <button class="btn-approve" onclick="event.stopPropagation(); openApprove(${r.id}, ${patientArg})">
                <i class="fa-solid fa-check"></i>
                Approve
            </button>

            <button class="btn-reject" onclick="event.stopPropagation(); openReject(${r.id}, ${patientArg})">
                <i class="fa-solid fa-xmark"></i>
                Reject
            </button>
        </div>
    ` : `
        <div class="docreq-grid-actions">
            <span class="docreq-mobile-state-note">${statusLabel}</span>
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
        const hasAdvancedFilters =
            filterDocType !== '' ||
            filterDateFrom !== '' ||
            filterDateTo !== '' ||
            filterSort !== 'newest' ||
            activeFilter !== 'all';

        const isDataEmpty = allRequests.length === 0;

        let stateClass = 'empty-neutral';
        let iconHtml = '<i class="fa-regular fa-folder-open"></i>';
        let title = 'No document requests yet';
        let subtitle = 'Incoming patient document requests will appear here once submitted.';
        let buttonLabel = '';
        let buttonAction = '';
        const compactClass = 'compact';

        if (isSearchMiss) {
            stateClass = 'empty-search';
            iconHtml = '<i class="fa-solid fa-magnifying-glass"></i>';
            title = `No results for "${esc(searchQuery)}"`;
            subtitle = 'Try another patient name or clear the search to see all requests.';
            buttonLabel = 'Clear search';
            buttonAction = 'clearSearch()';
        } else if (activeFilter === 'pending') {
            stateClass = 'empty-pending';
            iconHtml = '<i class="fa-solid fa-clock-rotate-left"></i>';
            title = 'No pending requests';
            subtitle = isDataEmpty && !hasAdvancedFilters ?
                'There are no document requests waiting for review right now.' :
                'No pending requests match your current filters.';
            if (hasAdvancedFilters && !isDataEmpty) {
                buttonLabel = 'Clear filters';
                buttonAction = 'resetAllFilters()';
            }
        } else if (activeFilter === 'approved') {
            stateClass = 'empty-approved';
            iconHtml = '<i class="fa-solid fa-file-circle-check"></i>';
            title = 'No approved requests';
            subtitle = isDataEmpty && !hasAdvancedFilters ?
                'Approved document requests will appear here after review.' :
                'No approved requests match your current filters.';
            if (hasAdvancedFilters && !isDataEmpty) {
                buttonLabel = 'Clear filters';
                buttonAction = 'resetAllFilters()';
            }
        } else if (activeFilter === 'rejected') {
            stateClass = 'empty-rejected';
            iconHtml = '<i class="fa-solid fa-file-circle-xmark"></i>';
            title = 'No rejected requests';
            subtitle = isDataEmpty && !hasAdvancedFilters ?
                'Rejected document requests will appear here when applicable.' :
                'No rejected requests match your current filters.';
            if (hasAdvancedFilters && !isDataEmpty) {
                buttonLabel = 'Clear filters';
                buttonAction = 'resetAllFilters()';
            }
        } else if (isDataEmpty && !hasAdvancedFilters) {
            stateClass = 'empty-neutral';
            iconHtml = '<i class="fa-regular fa-folder-open"></i>';
            title = 'No document requests yet';
            subtitle = 'Incoming patient document requests will appear here once submitted.';
        } else {
            stateClass = 'empty-filter';
            iconHtml = '<i class="fa-solid fa-filter-circle-xmark"></i>';
            title = 'No matching requests found';
            subtitle = 'Your current filters did not return any records. Try adjusting or clearing them.';
            buttonLabel = 'Clear filters';
            buttonAction = 'resetAllFilters()';
        }

        return `
  <div class="empty-state ${stateClass}">
    <div class="empty-state-icon">${iconHtml}</div>
    <p class="empty-state-title">${title}</p>
    <p class="empty-state-sub">${subtitle}</p>

    ${buttonLabel ? `
              <button
                type="button"
                ${buttonAction === 'clearSearch()' ? 'data-clear-search data-search-target="#searchInput"' : `onclick="${buttonAction}"`}
                class="empty-state-btn"
              >
                <i class="fa-solid fa-xmark"></i>
                ${buttonLabel}
              </button>
            ` : ''}
  </div>
`;
    }

    function buildMobileCard(r) {
        const accentHex = getDocumentAccent(r.status);
        const badgeCls = r.status === 'approved' ? 'badge-approved' : r.status === 'rejected' ? 'badge-rejected' :
            'badge-pending';
        const statusLabel = getStatusLabel(r.status);
        const displayName = getPatientDisplayName(r.patient_name);
        const patientArg = jsStringArg(displayName);
        const avatarHtml = buildPatientAvatar(r, 'docreq-mobile-avatar');
        const program = r.sub_label ? esc(r.sub_label) : 'No ID set';

        const rowClick = `selectDocumentCard('m', ${r.id})`;

        const mobileActions = r.status === 'pending' ? `
    <div class="mobile-action-btns docreq-mobile-actions docreq-mobile-direct-actions">
        <button class="btn-approve" onclick="event.stopPropagation(); openApprove(${r.id}, ${patientArg})">
            <i class="fa-solid fa-check"></i>
            Approve
        </button>

        <button class="btn-reject" onclick="event.stopPropagation(); openReject(${r.id}, ${patientArg})">
            <i class="fa-solid fa-xmark"></i>
            Reject
        </button>
    </div>
` : `
    <span class="docreq-mobile-state-note">${statusLabel}</span>
`;

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

    function getDocumentAccent(status) {
        return status === 'approved' ? '#15803d' : status === 'rejected' ? '#b91c1c' : '#c2410c';
    }

    function esc(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
            '&quot;');
    }

    function renderPagination(total, lastPage) {
        const ctrl = document.getElementById('pagControls');
        if (lastPage <= 1) {
            ctrl.innerHTML = '';
            return;
        }
        let html = '';
        html +=
            `<button class="pag-btn" ${currentPage > 1 ? '' : 'disabled'} onclick="goPage(${currentPage - 1})">‹ Prev</button>`;
        for (let p = 1; p <= lastPage; p++) html +=
            `<button class="pag-btn ${p === currentPage ? 'pag-active' : ''}" onclick="goPage(${p})">${p}</button>`;
        html +=
            `<button class="pag-btn" ${currentPage < lastPage ? '' : 'disabled'} onclick="goPage(${currentPage + 1})">Next ›</button>`;
        ctrl.innerHTML = html;
    }

    function goPage(p) {
        currentPage = p;
        renderList();
        const activeContainer = currentViewMode === 'grid' ?
            document.getElementById('requestGridContainer') :
            document.getElementById('requestListContainer');

        if (activeContainer) {
            activeContainer.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

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
        return document.getElementById(meta.countId)?.textContent || '0';
    }

    function updateStatusDropdownUI(status) {
        const meta = getStatusMeta(status);
        const label = document.getElementById('statusDropdownLabel');
        const count = document.getElementById('statusDropdownCount');
        const leading = document.querySelector('#docreqStatusSelect .docreq-select-leading');

        if (label) label.textContent = meta.label;
        if (count) count.textContent = getStatusCount(status);

        if (leading) {
            leading.className = `docreq-select-leading ${meta.tone}`;
            leading.innerHTML = `<i class="fa-solid ${meta.icon}"></i>`;
        }

        document.querySelectorAll('#docreqStatusSelect .docreq-select-option').forEach((option) => {
            option.classList.toggle('active', option.getAttribute('data-value') === status);
        });
    }

    function selectStatusFilter(status) {
        closeDocreqDropdowns();
        setFilter(status);
    }

    function setFilter(f) {
        activeFilter = f;
        filterStatus = f;
        currentPage = 1;

        updateStatusDropdownUI(f);
        if (window.syncFilterTagGroup) window.syncFilterTagGroup('fStatusGroup', f);

        updateFilterBtn();
        renderFilterChips();
        renderList();
    }

    function onSearch(input) {
        searchQuery = input.value.trim();
        currentPage = 1;
        renderList();
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

        const patientName = request
            ? getPatientDisplayName(request.patient_name)
            : getPatientDisplayName(fallbackName);

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
        renderList();
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
                window.syncFilterTagGroup('fStatusGroup', filterStatus);
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

        window.syncFilterTagGroup('fStatusGroup', filterStatus);
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

        const savedViewMode = localStorage.getItem('docreqViewMode');
        currentViewMode = window.innerWidth <= 767 ? 'grid' : (savedViewMode || 'list');
        setViewMode(currentViewMode, document.querySelector('.btn-view-mode[data-view="' + currentViewMode +
            '"]'));

        if (window.innerWidth <= 767) {
            document.getElementById('docreqViewToggle')?.classList.add('hidden');
        }

        window.addEventListener('resize', function () {
            const toggle = document.getElementById('docreqViewToggle');
            if (window.innerWidth <= 767) {
                toggle?.classList.add('hidden');
                if (currentViewMode !== 'grid') {
                    setViewMode('grid', document.querySelector('.btn-view-mode[data-view="grid"]'));
                }
            } else {
                toggle?.classList.remove('hidden');
            }
            renderList();
        });

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
            ['approveModal', 'rejectModal', 'approvedResultModal', 'rejectedResultModal', 'filterModal']
                .forEach(id => {
                    const m = document.getElementById(id);
                    if (!m?.classList.contains('open')) return;
                    if (id === 'filterModal') closeFilterModal();
                    else window.closeModal(id);
                });
        });

        ['approveModal', 'rejectModal', 'approvedResultModal', 'rejectedResultModal', 'filterModal']
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

        const approveModal = document.getElementById('approveModal');
        const approvedModal = document.getElementById('approvedResultModal');
        ['approveCancelBtn', 'approveCancelBtn2'].forEach(id =>
            document.getElementById(id)?.addEventListener('click', () => window.closeModal('approveModal')));
        document.getElementById('approvedResultClose').addEventListener('click', () => {
            window.closeModal('approvedResultModal');
            loadData();
        });
        document.getElementById('approveConfirmBtn').addEventListener('click', async () => {
            const id = document.getElementById('approveRequestId').value;
            const btn = document.getElementById('approveConfirmBtn');
            if (!id) return;
            btn.disabled = true;
            const res = await fetch(`/dentist/document-requests/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
                body: '{}'
            });
            btn.disabled = false;
            if (res.ok) {
                window.closeModal('approveModal');
                window.openModal('approvedResultModal');
            } else alert('Something went wrong.');
        });

        const rejectModal = document.getElementById('rejectModal');
        const rejectedModal = document.getElementById('rejectedResultModal');
        ['rejectCancelBtn', 'rejectCancelBtn2'].forEach(id =>
            document.getElementById(id)?.addEventListener('click', () => window.closeModal('rejectModal')));
        document.getElementById('rejectedResultClose').addEventListener('click', () => {
            window.closeModal('rejectedResultModal');
            loadData();
        });
        document.getElementById('rejectConfirmBtn').addEventListener('click', async () => {
            const id = document.getElementById('rejectRequestId').value;
            const btn = document.getElementById('rejectConfirmBtn');
            const notes = document.getElementById('rejectNotes').value.trim();

            if (!id) return;

            if (window.validateCharLimit && !window.validateCharLimit('rejectNotes', 150, 'err-rejectNotes')) {
                document.getElementById('rejectNotes')?.focus();
                return;
            }

            btn.disabled = true;
            const res = await fetch(`/dentist/document-requests/${id}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
                body: JSON.stringify({
                    reason: notes
                })
            });
            btn.disabled = false;
            if (res.ok) {
                window.closeModal('rejectModal');
                window.openModal('rejectedResultModal');
            } else alert('Something went wrong.');
        });

        loadData();
    });
</script>
@endsection