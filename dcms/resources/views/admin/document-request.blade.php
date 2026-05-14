@extends('layouts.admin')

@section('title', 'Document Requests | PUP Taguig Dental Clinic')

@section('content')
<div class="document-requests-page">
    <main class="w-full">
        <div class="max-w-[1280px] mx-auto">

            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Document Requests</h1>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('admin.document-requests.export') }}" class="btn-secondary">
                            <i class="fa-solid fa-file-export text-xs"></i>
                            Export CSV
                        </a>
                        <a href="{{ route('admin.document-requests.print-queue') }}" class="btn-primary">
                            <i class="fa-solid fa-print text-xs"></i>
                            Print Queue
                        </a>
                    </div>
                </div>
            </div>

            @php
            $_s = $stats ?? [];
            $_total = $_s['total'] ?? 0;
            $_pending = $_s['pending'] ?? 0;
            $_approved = $_s['approved'] ?? 0;
            $_ready = $_s['ready'] ?? 0;
            $_released = $_s['released'] ?? 0;
            $_rejected = $_s['rejected'] ?? 0;
            $currentQuery = request()->only(['status', 'type', 'sort', 'date_from', 'date_to']);

            $activeFilterCount = (request('status') ? 1 : 0)
            + (request('type') ? 1 : 0)
            + (request('sort') && request('sort') !== 'newest' ? 1 : 0)
            + (request('date_from') ? 1 : 0)
            + (request('date_to') ? 1 : 0);
            @endphp

            <div id="documentRequestsFragment">

                <div class="stat-grid">
                    <a href="{{ route('admin.document-requests.index', array_filter(array_merge($currentQuery, ['status' => null]))) }}"
                        class="stat-card total js-ajax-nav {{ !request('status') ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div class="stat-value">{{ $_total }}</div>
                        <div class="stat-lbl">Total Requests</div>
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'pending'])) }}"
                        class="stat-card pending js-ajax-nav {{ request('status') === 'pending' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                        <div class="stat-value">{{ $_pending }}</div>
                        <div class="stat-lbl">Pending Review</div>
                        @if($_pending > 0)<span class="stat-trend">Needs action</span>@endif
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'approved'])) }}"
                        class="stat-card approved js-ajax-nav {{ request('status') === 'approved' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-value">{{ $_approved }}</div>
                        <div class="stat-lbl">Approved</div>
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'ready'])) }}"
                        class="stat-card ready js-ajax-nav {{ request('status') === 'ready' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-file-circle-check"></i></div>
                        <div class="stat-value">{{ $_ready }}</div>
                        <div class="stat-lbl">Ready for Pickup</div>
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'released'])) }}"
                        class="stat-card released js-ajax-nav {{ request('status') === 'released' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-paper-plane"></i></div>
                        <div class="stat-value">{{ $_released }}</div>
                        <div class="stat-lbl">Released</div>
                    </a>

                    <a href="{{ route('admin.document-requests.index', array_merge($currentQuery, ['status' => 'rejected'])) }}"
                        class="stat-card rejected js-ajax-nav {{ request('status') === 'rejected' ? 'stat-active' : '' }}">
                        <div class="stat-icon"><i class="fa-solid fa-ban"></i></div>
                        <div class="stat-value">{{ $_rejected }}</div>
                        <div class="stat-lbl">Rejected</div>
                        @if($_rejected > 0)<span class="stat-trend">Review needed</span>@endif
                    </a>
                </div>

                <div class="toolbar">
                    <div class="document-request-search-row">
                        <div class="search-wrap">
                            <i class="fa fa-search search-icon"></i>
                            <input type="text" id="documentRequestSearch" class="no-voice"
                                placeholder="Search name/ID..." value="" autocomplete="off">
                        </div>
                        <button type="button" id="documentRequestClearBtn" class="search-clear-btn hidden"
                            title="Clear">Clear</button>

                        <div class="document-request-voice-toggle">
                            <button type="button" id="documentRequestMicToggleBtn" class="voice-search-mic external"
                                aria-label="Toggle voice input" aria-pressed="false">
                                <i class="fa-solid fa-microphone"></i>
                            </button>
                            <span id="documentRequestVoiceStatus" class="document-request-voice-status hidden"
                                aria-live="polite"></span>
                        </div>
                    </div>

                    <button type="button" id="filterModalOpenBtn"
                        class="filter-btn {{ $activeFilterCount > 0 ? 'active' : '' }}">
                        <i class="fa-solid fa-sliders"></i>
                        Filter
                        <span class="filter-dot {{ $activeFilterCount > 0 ? 'visible' : '' }}"></span>
                    </button>

                    <div class="view-toggle" id="documentRequestViewToggle">
                        <button type="button" class="view-toggle-btn active" data-view="list" id="listViewBtn"
                            title="List view" aria-label="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="view-toggle-btn" data-view="grid" id="gridViewBtn"
                            title="Grid view" aria-label="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('admin.document-requests.index') }}" class="js-ajax-filter-form"
                        id="documentRequestsFilterForm" style="display: none;">
                        <input type="hidden" name="status" id="hiddenStatus" value="{{ request('status') }}">
                        <input type="hidden" name="type" id="hiddenType" value="{{ request('type') }}">
                        <input type="hidden" name="date_from" id="hiddenDateFrom" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" id="hiddenDateTo" value="{{ request('date_to') }}">
                        <input type="hidden" name="sort" id="hiddenSort" value="{{ request('sort', 'newest') }}">
                    </form>
                </div>

                <div class="filter-modal-backdrop" id="filterModalBackdrop">
                    <div class="filter-modal" role="dialog" aria-modal="true" aria-label="Filter requests">

                        <div class="filter-modal-header">
                            <div class="filter-modal-title">
                                <div class="filter-modal-title-icon">
                                    <i class="fa-solid fa-sliders"></i>
                                </div>
                                Filter Requests
                            </div>
                            <button type="button" class="filter-modal-close" id="filterModalCloseBtn" title="Close">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="filter-modal-body">

                            <div class="filter-section">
                                <div class="filter-section-label">Status</div>
                                <div class="filter-chip-group" id="filterStatusChips">
                                    <button type="button" class="filter-chip {{ !request('status') ? 'active' : '' }}"
                                        data-value="">
                                        All
                                    </button>
                                    @foreach([
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'ready' => 'Ready for Pickup',
                                    'released' => 'Released',
                                    'rejected' => 'Rejected',
                                    ] as $val => $label)
                                    <button type="button"
                                        class="filter-chip {{ request('status') === $val ? 'active' : '' }}"
                                        data-value="{{ $val }}">
                                        <span class="chip-dot"></span>
                                        {{ $label }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="filter-divider">

                            <div class="filter-section">
                                <div class="filter-section-label">Document Type</div>
                                <select class="filter-sel" id="filterTypeSelect">
                                    <option value="">All Types</option>
                                    <option value="dental records" {{ request('type')==='dental records' ? 'selected'
                                        : '' }}>Dental Records</option>
                                    <option value="medical records" {{ request('type')==='medical records' ? 'selected'
                                        : '' }}>Medical Records</option>
                                    <option value="dental clearance" {{ request('type')==='dental clearance'
                                        ? 'selected' : '' }}>Dental Clearance</option>
                                    <option value="annual dental clearance" {{
                                        request('type')==='annual dental clearance' ? 'selected' : '' }}>Annual Dental
                                        Clearance</option>
                                </select>
                            </div>

                            <hr class="filter-divider">

                            <div class="filter-section">
                                <div class="filter-section-label">Date Range</div>
                                <div class="filter-date-row">
                                    <div class="filter-date-wrap">
                                        <label for="filterDateFrom">From</label>
                                        <input type="date" id="filterDateFrom" value="{{ request('date_from') }}">
                                    </div>
                                    <div class="filter-date-wrap">
                                        <label for="filterDateTo">To</label>
                                        <input type="date" id="filterDateTo" value="{{ request('date_to') }}">
                                    </div>
                                </div>
                            </div>

                            <hr class="filter-divider">

                            <div class="filter-section">
                                <div class="filter-section-label">Sort Order</div>
                                <div class="filter-chip-group" id="filterSortChips">
                                    <button type="button"
                                        class="filter-chip {{ request('sort', 'newest') === 'newest' ? 'active' : '' }}"
                                        data-value="newest">
                                        <i class="fa-solid fa-arrow-down-wide-short" style="font-size:.65rem;"></i>
                                        Newest First
                                    </button>
                                    <button type="button"
                                        class="filter-chip {{ request('sort') === 'oldest' ? 'active' : '' }}"
                                        data-value="oldest">
                                        <i class="fa-solid fa-arrow-up-wide-short" style="font-size:.65rem;"></i>
                                        Oldest First
                                    </button>
                                    <button type="button"
                                        class="filter-chip {{ request('sort') === 'alpha' ? 'active' : '' }}"
                                        data-value="alpha">
                                        <i class="fa-solid fa-arrow-down-a-z" style="font-size:.65rem;"></i>
                                        Alphabetical
                                    </button>
                                </div>
                            </div>

                        </div>

                        <div class="filter-modal-footer">
                            <button type="button" class="filter-reset-btn" id="filterResetBtn">
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                                Reset all filters
                            </button>
                            <button type="button" class="filter-apply-btn" id="filterApplyBtn">
                                <i class="fa-solid fa-check text-xs"></i>
                                Apply Filters
                            </button>
                        </div>

                    </div>
                </div>

                <div class="content-grid"
                    style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:1rem;align-items:start;">

                    <div class="tbl-wrap">
                        @if(empty($requests) || $requests->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-inbox"></i>
                            <p class="font-semibold text-gray-500 mb-1">No requests found</p>
                            <p class="text-sm">Try adjusting your filters.</p>
                        </div>
                        @else
                        <div class="request-view" id="documentRequestListView">
                            <div class="table-responsive-fix">
                                <table class="tbl">
                                    <thead>
                                        <tr>
                                            <th>Ref No.</th>
                                            <th>Patient</th>
                                            <th>Document</th>
                                            <th>Purpose</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($requests as $req)
                                        @php
                                        $sBg = [
                                        'pending' => '#fef3c7',
                                        'approved' => '#d1fae5',
                                        'ready' => '#dbeafe',
                                        'released' => '#ede9fe',
                                        'rejected' => '#fef2f2',
                                        ];
                                        $sTx = [
                                        'pending' => '#92400e',
                                        'approved' => '#065f46',
                                        'ready' => '#1e40af',
                                        'released' => '#5b21b6',
                                        'rejected' => '#8B0000',
                                        ];
                                        $patientName = $req->patient->name ?? 'Unknown Patient';
                                        $patientStudentId = $req->patient->id ?? 'No ID';
                                        $patientInitial = strtoupper(substr($patientName, 0, 1));
                                        @endphp
                                        <tr class="document-request-row"
                                            data-reference="{{ strtolower($req->reference_number) }}"
                                            data-patient="{{ strtolower($patientName) }}"
                                            data-student="{{ strtolower($patientStudentId) }}"
                                            data-document="{{ strtolower(str_replace('_', ' ', $req->document_type)) }}"
                                            data-purpose="{{ strtolower($req->purpose ?? '') }}"
                                            data-status="{{ strtolower($req->status) }}">
                                            <td>
                                                <span class="font-mono text-xs font-bold text-[#8B0000]">
                                                    {{ $req->reference_number }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#6b0000);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0;">
                                                        {{ $patientInitial }}
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-gray-800 cell-patient-name">
                                                            {{ $patientName }}
                                                        </div>
                                                        <div class="text-[10px] text-gray-400 whitespace-nowrap">
                                                            {{ $patientStudentId }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="cell-document">
                                                <a href="{{ route('admin.document-requests.index', array_merge(request()->only(['status', 'sort', 'date_from', 'date_to']), ['type' => $req->document_type])) }}"
                                                    class="document-badge js-ajax-nav">
                                                    {{ ucwords(str_replace('_', ' ', $req->document_type)) }}
                                                </a>
                                            </td>

                                            <td class="text-xs text-gray-500 cell-purpose">{{ $req->purpose ?: '—' }}
                                            </td>

                                            <td class="text-xs text-gray-400 whitespace-nowrap"
                                                style="overflow: visible; text-overflow: unset;">
                                                {{ $req->created_at->format('M d, Y') }}
                                            </td>

                                            <td>
                                                <span
                                                    style="background:{{ $sBg[$req->status] ?? '#f3f4f6' }};color:{{ $sTx[$req->status] ?? '#6b7280' }};padding:.2rem .65rem;border-radius:999px;font-size:.68rem;font-weight:700;display:inline-block;">
                                                    {{ ucfirst($req->status) }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="flex items-center gap-1">
                                                    <button type="button" class="action-btn view"
                                                        onclick="openPanel({{ $req->id }})" title="View">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                    @if($req->status === 'pending')
                                                    <form action="{{ route('admin.document-requests.approve', $req) }}"
                                                        method="POST" style="display:inline;">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="action-btn tog-on" title="Approve">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                    @endif
                                                    @if(in_array($req->status, ['approved', 'ready']))
                                                    <form action="{{ route('admin.document-requests.release', $req) }}"
                                                        method="POST" style="display:inline;">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="action-btn"
                                                            style="background:#dbeafe;color:#1e40af;" title="Release">
                                                            <i class="fa-solid fa-paper-plane"></i>
                                                        </button>
                                                    </form>
                                                    @endif
                                                    @if(in_array($req->status, ['pending', 'approved']))
                                                    <form action="{{ route('admin.document-requests.reject', $req) }}"
                                                        method="POST" style="display:inline;"
                                                        onsubmit="return confirm('Reject this request?')">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="action-btn del" title="Reject">
                                                            <i class="fa-solid fa-xmark"></i>
                                                        </button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="request-view" id="documentRequestGridView" hidden>
                            <div class="requests-grid">
                                @foreach($requests as $req)
                                @php
                                $sBg = [
                                'pending' => '#fef3c7',
                                'approved' => '#d1fae5',
                                'ready' => '#dbeafe',
                                'released' => '#ede9fe',
                                'rejected' => '#fef2f2',
                                ];
                                $sTx = [
                                'pending' => '#92400e',
                                'approved' => '#065f46',
                                'ready' => '#1e40af',
                                'released' => '#5b21b6',
                                'rejected' => '#8B0000',
                                ];
                                $patientName = $req->patient->name ?? 'Unknown Patient';
                                $patientStudentId = $req->patient->id ?? 'No ID';
                                $patientInitial = strtoupper(substr($patientName, 0, 1));
                                @endphp

                                <div class="request-card document-request-row"
                                    data-reference="{{ strtolower($req->reference_number) }}"
                                    data-patient="{{ strtolower($patientName) }}"
                                    data-student="{{ strtolower($patientStudentId) }}"
                                    data-document="{{ strtolower(str_replace('_', ' ', $req->document_type)) }}"
                                    data-purpose="{{ strtolower($req->purpose ?? '') }}"
                                    data-status="{{ strtolower($req->status) }}">
                                    <div class="request-card-top">
                                        <div class="request-card-ref">{{ $req->reference_number }}</div>
                                        <span
                                            style="background:{{ $sBg[$req->status] ?? '#f3f4f6' }};color:{{ $sTx[$req->status] ?? '#6b7280' }};padding:.2rem .65rem;border-radius:999px;font-size:.68rem;font-weight:700;display:inline-block;white-space:nowrap;">
                                            {{ ucfirst($req->status) }}
                                        </span>
                                    </div>

                                    <div class="request-card-patient">
                                        <div class="request-card-avatar">{{ $patientInitial }}</div>
                                        <div class="request-card-patient-info">
                                            <div class="request-card-name">{{ $patientName }}</div>
                                            <div class="request-card-id">{{ $patientStudentId }}</div>
                                        </div>
                                    </div>

                                    <div class="request-card-meta">
                                        <div class="request-card-field">
                                            <div class="request-card-label">Document</div>
                                            <div class="request-card-value">
                                                <a href="{{ route('admin.document-requests.index', array_merge(request()->only(['status', 'sort', 'date_from', 'date_to']), ['type' => $req->document_type])) }}"
                                                    class="document-badge js-ajax-nav">
                                                    {{ ucwords(str_replace('_', ' ', $req->document_type)) }}
                                                </a>
                                            </div>
                                        </div>

                                        <div class="request-card-field">
                                            <div class="request-card-label">Purpose</div>
                                            <div class="request-card-value request-card-purpose">
                                                {{ $req->purpose ?: '—' }}
                                            </div>
                                        </div>

                                        <div class="request-card-field">
                                            <div class="request-card-label">Date</div>
                                            <div class="request-card-value">
                                                {{ $req->created_at->format('M d, Y') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="request-card-footer">
                                        <button type="button" class="action-btn view"
                                            onclick="openPanel({{ $req->id }})" title="View">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        <div class="request-card-actions">
                                            @if($req->status === 'pending')
                                            <form action="{{ route('admin.document-requests.approve', $req) }}"
                                                method="POST" style="display:inline;">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="action-btn tog-on" title="Approve">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                            @endif

                                            @if(in_array($req->status, ['approved', 'ready']))
                                            <form action="{{ route('admin.document-requests.release', $req) }}"
                                                method="POST" style="display:inline;">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="action-btn"
                                                    style="background:#dbeafe;color:#1e40af;" title="Release">
                                                    <i class="fa-solid fa-paper-plane"></i>
                                                </button>
                                            </form>
                                            @endif

                                            @if(in_array($req->status, ['pending', 'approved']))
                                            <form action="{{ route('admin.document-requests.reject', $req) }}"
                                                method="POST" style="display:inline;"
                                                onsubmit="return confirm('Reject this request?')">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="action-btn del" title="Reject">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div id="documentRequestClientEmpty" class="empty-state" style="display:none;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <p class="font-semibold text-gray-500 mb-1">No matching requests found</p>
                            <p class="text-sm">Try a different patient name or reference number.</p>
                        </div>

                        @if(!empty($requests) && $requests->hasPages())
                        <div class="tbl-pagination">
                            <span>Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of
                                {{ $requests->total() }} requests</span>
                            <div>{{ $requests->withQueryString()->links() }}</div>
                        </div>
                        @endif
                        @endif
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-header-icon">
                                <i class="fa-solid fa-file-circle-check text-white" style="font-size:.7rem;"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-800 text-sm" id="panelRefNo">Select a request</div>
                                <div class="text-[11px] text-gray-400">Document Request</div>
                            </div>
                        </div>

                        <div style="padding:1.25rem;" id="panelBody">
                            <div style="text-align:center;padding:2.5rem 0 2rem;color:#d1d5db;">
                                <div style="width:52px;height:52px;border-radius:14px;background:#fef2f2;display:flex;
                                    align-items:center;justify-content:center;margin:0 auto .9rem;">
                                    <i class="fa-solid fa-file-circle-check"
                                        style="font-size:1.4rem;color:#f9c1c1;"></i>
                                </div>
                                <p style="font-size:.78rem;font-weight:600;color:#cbd5e1;margin-bottom:.25rem;">No
                                    request selected</p>
                                <p style="font-size:.7rem;color:#e2e8f0;">Click a row to view details</p>
                            </div>
                        </div>

                        <div id="panelFoot"
                            style="padding:.9rem 1.25rem;border-top:1px solid #f3f4f6;background:#fafafa;display:none;gap:.5rem;flex-wrap:wrap;">
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>
@endsection

@section('scripts')
<script>
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    const statusBackground = { pending: '#fef3c7', approved: '#d1fae5', ready: '#dbeafe', released: '#ede9fe', rejected: '#fef2f2' };
    const statusText = { pending: '#92400e', approved: '#065f46', ready: '#1e40af', released: '#5b21b6', rejected: '#8B0000' };

    function updateFilterButtonState() {
        const btn = document.getElementById('filterModalOpenBtn');
        const dot = btn ? btn.querySelector('.filter-dot') : null;

        const statusVal = document.getElementById('hiddenStatus')?.value || '';
        const typeVal = document.getElementById('hiddenType')?.value || '';
        const dateFrom = document.getElementById('hiddenDateFrom')?.value || '';
        const dateTo = document.getElementById('hiddenDateTo')?.value || '';
        const sortVal = document.getElementById('hiddenSort')?.value || 'newest';

        const hasFilters = !!statusVal || !!typeVal || !!dateFrom || !!dateTo || (sortVal && sortVal !== 'newest');

        if (btn) btn.classList.toggle('active', hasFilters);
        if (dot) dot.classList.toggle('visible', hasFilters);
    }

    function initFilterModal() {
        const backdrop = document.getElementById('filterModalBackdrop');
        const openBtn = document.getElementById('filterModalOpenBtn');
        const closeBtn = document.getElementById('filterModalCloseBtn');
        const applyBtn = document.getElementById('filterApplyBtn');
        const resetBtn = document.getElementById('filterResetBtn');

        if (!backdrop || !openBtn) return;

        if (!openBtn.dataset.bound) {
            openBtn.dataset.bound = '1';
            openBtn.addEventListener('click', () => {
                backdrop.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        }

        function closeModal() {
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }

        if (closeBtn && !closeBtn.dataset.bound) {
            closeBtn.dataset.bound = '1';
            closeBtn.addEventListener('click', closeModal);
        }

        if (!backdrop.dataset.bound) {
            backdrop.dataset.bound = '1';
            backdrop.addEventListener('click', e => {
                if (e.target === backdrop) closeModal();
            });
        }

        if (!document.body.dataset.documentRequestEscapeBound) {
            document.body.dataset.documentRequestEscapeBound = '1';
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') closeModal();
            });
        }

        const chipGroup = document.getElementById('filterStatusChips');
        if (chipGroup && !chipGroup.dataset.bound) {
            chipGroup.dataset.bound = '1';
            chipGroup.addEventListener('click', e => {
                const chip = e.target.closest('.filter-chip');
                if (!chip) return;
                chipGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
            });
        }

        const sortGroup = document.getElementById('filterSortChips');
        if (sortGroup && !sortGroup.dataset.bound) {
            sortGroup.dataset.bound = '1';
            sortGroup.addEventListener('click', e => {
                const chip = e.target.closest('.filter-chip');
                if (!chip) return;
                sortGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
            });
        }

        if (applyBtn && !applyBtn.dataset.bound) {
            applyBtn.dataset.bound = '1';
            applyBtn.addEventListener('click', () => {
                const activeChip = chipGroup ? chipGroup.querySelector('.filter-chip.active') : null;
                const activeSortChip = sortGroup ? sortGroup.querySelector('.filter-chip.active') : null;

                const statusVal = activeChip ? activeChip.dataset.value : '';
                const sortVal = activeSortChip ? activeSortChip.dataset.value : 'newest';
                const typeVal = document.getElementById('filterTypeSelect')?.value || '';
                const dateFrom = document.getElementById('filterDateFrom')?.value || '';
                const dateTo = document.getElementById('filterDateTo')?.value || '';

                document.getElementById('hiddenStatus').value = statusVal;
                document.getElementById('hiddenType').value = typeVal;
                document.getElementById('hiddenDateFrom').value = dateFrom;
                document.getElementById('hiddenDateTo').value = dateTo;
                document.getElementById('hiddenSort').value = sortVal;

                updateFilterButtonState();
                closeModal();

                const form = document.getElementById('documentRequestsFilterForm');
                const url = new URL(form.action, window.location.origin);
                url.search = '';

                if (statusVal) url.searchParams.set('status', statusVal);
                if (typeVal) url.searchParams.set('type', typeVal);
                if (dateFrom) url.searchParams.set('date_from', dateFrom);
                if (dateTo) url.searchParams.set('date_to', dateTo);
                if (sortVal && sortVal !== 'newest') url.searchParams.set('sort', sortVal);

                loadDocumentRequestsFragment(url.toString());
            });
        }

        if (resetBtn && !resetBtn.dataset.bound) {
            resetBtn.dataset.bound = '1';
            resetBtn.addEventListener('click', () => {
                if (chipGroup) {
                    chipGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                    const allChip = chipGroup.querySelector('.filter-chip[data-value=""]');
                    if (allChip) allChip.classList.add('active');
                }

                if (sortGroup) {
                    sortGroup.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                    const defaultSort = sortGroup.querySelector('.filter-chip[data-value="newest"]');
                    if (defaultSort) defaultSort.classList.add('active');
                }

                const typeSelect = document.getElementById('filterTypeSelect');
                if (typeSelect) typeSelect.value = '';

                const dfrom = document.getElementById('filterDateFrom');
                const dto = document.getElementById('filterDateTo');
                if (dfrom) dfrom.value = '';
                if (dto) dto.value = '';

                document.getElementById('hiddenStatus').value = '';
                document.getElementById('hiddenType').value = '';
                document.getElementById('hiddenDateFrom').value = '';
                document.getElementById('hiddenDateTo').value = '';
                document.getElementById('hiddenSort').value = 'newest';

                updateFilterButtonState();
            });
        }
    }

    function detailRow(label, value) {
        return `<div style="display:flex;gap:.5rem;margin-bottom:.6rem;font-size:.8rem;">
            <span style="color:#9ca3af;min-width:100px;flex-shrink:0;">${label}</span>
            <span style="color:#111;font-weight:600;">${value}</span>
        </div>`;
    }

    async function openPanel(id) {
        const panelRefNo = document.getElementById('panelRefNo');
        const panelBody = document.getElementById('panelBody');
        const panelFoot = document.getElementById('panelFoot');

        panelRefNo.textContent = 'Loading...';
        panelBody.innerHTML = `<div style="text-align:center;padding:2rem 0;color:#d1d5db;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;"></i></div>`;
        panelFoot.style.display = 'none';
        panelFoot.innerHTML = '';

        try {
            const response = await fetch(`/admin/document-requests/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (!response.ok) throw new Error('Failed to fetch.');
            const data = await response.json();

            panelRefNo.textContent = data.reference_number;
            panelBody.innerHTML = `
                <div style="background:#fef2f2;border-radius:10px;padding:.9rem 1rem;margin-bottom:1rem;display:flex;
                    align-items:center;gap:.75rem;border:1px solid #fce8e8;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#8B0000,#6b0000);
                        color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                        ${((data.patient_name || '?')[0] || '?').toUpperCase()}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.84rem;font-weight:700;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${data.patient_name || '—'}</div>
                        <div style="font-size:.7rem;color:#9ca3af;">${data.patient_id || ''}</div>
                    </div>
                    <span style="background:${statusBackground[data.status] || '#f3f4f6'};color:${statusText[data.status] || '#6b7280'};padding:.2rem .65rem;border-radius:999px;font-size:.68rem;font-weight:700;white-space:nowrap;">
                        ${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : '—'}
                    </span>
                </div>
                ${detailRow('Document', formatTitle(data.document_type))}
                ${detailRow('Purpose', data.purpose || '—')}
                ${detailRow('Date', data.created_at || '—')}
                ${detailRow('Copies', data.copies_needed || '1')}
                <div style="margin-top:1rem;">
                    <div style="font-size:.67rem;font-weight:700;color:#9ca3af;text-transform:uppercase;
                        letter-spacing:.08em;margin-bottom:.6rem;">Activity</div>
                    <div style="padding-left:1rem;border-left:2px solid #f0eaea;">
                        ${(data.activities || [{ date: '—', description: 'No activity yet.' }]).map(a => `
                            <div style="position:relative;margin-bottom:.6rem;">
                                <div style="position:absolute;left:-1.35rem;top:.3rem;width:6px;
                                    height:6px;border-radius:50%;background:#8B0000;border:1.5px solid #fef2f2;"></div>
                                <div style="font-size:.67rem;color:#9ca3af;">${a.date}</div>
                                <div style="font-size:.76rem;color:#374151;">${a.description}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            panelFoot.style.display = 'flex';
            let actions = '';

            if (data.status === 'pending') {
                actions += `<form action="/admin/document-requests/${data.id}/approve" method="POST">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="btn-primary" style="font-size:.75rem;padding:.4rem .9rem;">
                        <i class="fa-solid fa-check text-xs"></i> Approve
                    </button>
                </form>`;
            }
            if (data.status === 'approved' || data.status === 'ready') {
                actions += `<form action="/admin/document-requests/${data.id}/release" method="POST">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="btn-secondary" style="font-size:.75rem;padding:.38rem .9rem;">Release</button>
                </form>`;
            }
            if (data.status === 'pending' || data.status === 'approved') {
                actions += `<form action="/admin/document-requests/${data.id}/reject" method="POST" onsubmit="return confirm('Reject this request?')">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="btn-secondary" style="font-size:.75rem;
                        padding:.38rem .9rem;color:#dc2626;border-color:#fce8e8;">Reject</button>
                </form>`;
            }

            panelFoot.innerHTML = actions;
        } catch {
            panelBody.innerHTML = `<p style="color:#dc2626;text-align:center;padding:1.5rem;">Failed to load details.</p>`;
        }
    }

    function formatTitle(value) {
        if (!value) return '—';
        return value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function getPreferredDocumentRequestView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem('documentRequestView') || 'list';
    }

    function applyDocumentRequestView(view, save = true) {
        const listView = document.getElementById('documentRequestListView');
        const gridView = document.getElementById('documentRequestGridView');
        const listBtn = document.getElementById('listViewBtn');
        const gridBtn = document.getElementById('gridViewBtn');

        if (!listView || !gridView) return;

        const finalView = window.innerWidth <= 767 ? 'grid' : view;

        if (finalView === 'grid') {
            listView.hidden = true;
            gridView.hidden = false;
        } else {
            listView.hidden = false;
            gridView.hidden = true;
        }

        if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
        if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

        if (save && window.innerWidth > 767) {
            localStorage.setItem('documentRequestView', finalView);
        }
    }

    function initDocumentRequestViewToggle() {
        const listBtn = document.getElementById('listViewBtn');
        const gridBtn = document.getElementById('gridViewBtn');

        applyDocumentRequestView(getPreferredDocumentRequestView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', () => applyDocumentRequestView('list', true));
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', () => applyDocumentRequestView('grid', true));
        }
    }

    function updateDocumentRequestSearchClear() {
        const searchInput = document.getElementById('documentRequestSearch');
        const clearBtn = document.getElementById('documentRequestClearBtn');
        if (!searchInput || !clearBtn) return;
        clearBtn.classList.toggle('hidden', searchInput.value.trim().length === 0);
    }

    function filterDocumentRequestRows() {
        const searchInput = document.getElementById('documentRequestSearch');
        const rows = Array.from(document.querySelectorAll('.document-request-row'));
        const emptyState = document.getElementById('documentRequestClientEmpty');
        const pagination = document.querySelector('.tbl-pagination');
        if (!searchInput) return;

        const q = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;
        const seenKeys = new Set();

        rows.forEach(row => {
            const haystack = [
                row.dataset.reference,
                row.dataset.patient,
                row.dataset.student,
                row.dataset.document,
                row.dataset.purpose,
                row.dataset.status
            ].join(' ');

            const match = !q || haystack.includes(q);
            row.style.display = match ? '' : 'none';

            if (match) {
                const key = row.dataset.reference || Math.random().toString();
                if (!seenKeys.has(key)) {
                    seenKeys.add(key);
                    visibleCount++;
                }
            }
        });

        if (emptyState) emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
        if (pagination) pagination.style.display = q ? 'none' : '';
    }

    function initDocumentRequestSearch() {
        const searchInput = document.getElementById('documentRequestSearch');
        const clearBtn = document.getElementById('documentRequestClearBtn');
        if (!searchInput) return;

        updateDocumentRequestSearchClear();
        filterDocumentRequestRows();

        if (!searchInput.dataset.searchBound) {
            searchInput.dataset.searchBound = '1';
            searchInput.addEventListener('input', () => {
                updateDocumentRequestSearchClear();
                filterDocumentRequestRows();
            });
        }

        if (clearBtn && !clearBtn.dataset.bound) {
            clearBtn.dataset.bound = '1';
            clearBtn.addEventListener('click', () => {
                const micBtn = document.getElementById('documentRequestMicToggleBtn');
                if (micBtn && micBtn.classList.contains('mic-active')) {
                    micBtn.click();
                }

                searchInput.value = '';
                document.getElementById('documentRequestVoiceStatus')?.classList.add('hidden');
                updateDocumentRequestSearchClear();
                filterDocumentRequestRows();
                searchInput.focus();
            });
        }
    }

    function initDocumentRequestVoice() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const input = document.getElementById('documentRequestSearch');
        const micBtn = document.getElementById('documentRequestMicToggleBtn');
        const status = document.getElementById('documentRequestVoiceStatus');

        if (!input || !micBtn || !status) return;
        if (micBtn.dataset.bound === '1') return;
        micBtn.dataset.bound = '1';

        console.debug('[voice] initDocumentRequestVoice bound');

        if (!SpeechRecognition) {
            micBtn.disabled = true;
            micBtn.setAttribute('aria-disabled', 'true');
            return;
        }

        let listening = false;
        let recognition = null;
        let manualStop = false;

        const setStatus = (text, state) => {
            status.textContent = text;
            status.className = 'document-request-voice-status';
            if (state) status.classList.add(`is-${state}`);
            status.classList.remove('hidden');
        };

        const hideStatus = (delay = 0) => {
            window.setTimeout(() => status.classList.add('hidden'), delay);
        };

        const setMicState = (isActive) => {
            micBtn.classList.toggle('mic-active', isActive);
            micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            micBtn.innerHTML = isActive
                ? '<i class="fa-solid fa-stop"></i>'
                : '<i class="fa-solid fa-microphone"></i>';
            micBtn.title = isActive ? 'Stop listening' : 'Start voice input';
        };

        const stopListeningNow = () => {
            manualStop = true;
            listening = false;
            setMicState(false);
            setStatus('Voice input stopped.', 'success');
            hideStatus(1200);

            if (recognition) {
                try {
                    recognition.abort();
                } catch (e) {
                    try { recognition.stop(); } catch (err) { }
                }
            }
        };

        const createRecognition = () => {
            const r = new SpeechRecognition();
            r.lang = 'en-US';
            r.continuous = false;
            r.interimResults = true;
            r.maxAlternatives = 1;

            let sawSpeech = false;
            let timeoutId = null;
            const LISTEN_TIMEOUT = 6000;

            const clearTimeout_ = () => {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    timeoutId = null;
                }
            };

            r.onstart = () => {
                console.debug('[voice] recognition started');
                timeoutId = window.setTimeout(() => {
                    if (listening && !sawSpeech) {
                        r.stop();
                    }
                }, LISTEN_TIMEOUT);
            };

            r.onspeechend = () => {
                clearTimeout_();
                try { r.stop(); } catch (e) { }
            };

            r.onresult = (event) => {
                console.debug('[voice] onresult', event);
                let transcript = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const result = event.results[i];
                    const chunk = result?.[0]?.transcript?.trim() || '';
                    if (!chunk) continue;

                    sawSpeech = true;

                    if (result.isFinal) {
                        transcript = `${transcript} ${chunk}`.trim();
                    } else if (!transcript) {
                        transcript = chunk;
                    }
                }

                transcript = transcript.trim();

                if (transcript) {
                    clearTimeout_();
                    input.value = transcript;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    setStatus('Listening...', 'listening');
                }
            };

            r.onerror = () => {
                console.debug('[voice] onerror');
                clearTimeout_();
                listening = false;
                if (manualStop) {
                    manualStop = false;
                    return;
                }
                setMicState(false);
                setStatus("Didn't catch that. Try again.", 'error');
                hideStatus(2500);
            };

            r.onend = () => {
                console.debug('[voice] onend', { sawSpeech, inputValue: input.value });
                clearTimeout_();
                if (manualStop) {
                    manualStop = false;
                    listening = false;
                    setMicState(false);
                    return;
                }

                const hadSpeech = sawSpeech || !!input.value.trim();
                listening = false;
                setMicState(false);
                if (hadSpeech) {
                    setStatus('Voice captured.', 'success');
                    hideStatus(2200);
                } else {
                    setStatus("Didn't catch that. Try again.", 'error');
                    hideStatus(2500);
                }
            };

            return r;
        };

        micBtn.addEventListener('click', () => {
            if (listening && recognition) {
                stopListeningNow();
                return;
            }

            recognition = createRecognition();

            try {
                recognition.start();
            } catch (error) {
                setStatus('Unable to start voice input.', 'error');
                hideStatus(2500);
                setMicState(false);
                listening = false;
                return;
            }

            listening = true;
            setMicState(true);
            setStatus('Listening...', 'listening');
        });
    }

    async function loadDocumentRequestsFragment(url, push = true) {
        const fragment = document.getElementById('documentRequestsFragment');
        if (!fragment) return;

        fragment.classList.add('is-loading');

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Failed to load.');

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newFragment = doc.querySelector('#documentRequestsFragment');

            if (!newFragment) { window.location.href = url; return; }

            fragment.innerHTML = newFragment.innerHTML;
            if (push) window.history.pushState({}, '', url);

            bindDocumentRequestAjax();
            initDocumentRequestSearch();
            initDocumentRequestVoice();
            initFilterModal();
            initDocumentRequestViewToggle();
            updateFilterButtonState();
        } catch {
            window.location.href = url;
        } finally {
            fragment.classList.remove('is-loading');
        }
    }

    function bindDocumentRequestAjax() {
        document.querySelectorAll('.js-ajax-nav').forEach(link => {
            if (link.dataset.bound === '1') return;
            link.dataset.bound = '1';

            link.addEventListener('click', function (e) {
                e.preventDefault();
                loadDocumentRequestsFragment(this.href);
            });
        });

        document.querySelectorAll('.tbl-pagination a').forEach(link => {
            if (link.dataset.bound === '1') return;
            link.dataset.bound = '1';

            link.addEventListener('click', function (e) {
                e.preventDefault();
                loadDocumentRequestsFragment(this.href);
            });
        });
    }

    window.addEventListener('popstate', () => loadDocumentRequestsFragment(window.location.href, false));

    document.addEventListener('DOMContentLoaded', () => {
        bindDocumentRequestAjax();
        initDocumentRequestSearch();
        initDocumentRequestVoice();
        initFilterModal();
        initDocumentRequestViewToggle();
        updateFilterButtonState();

        window.addEventListener('resize', () => {
            applyDocumentRequestView(getPreferredDocumentRequestView(), false);
        });
    });
</script>
@endsection