@extends('layouts.admin')

@section('title', 'System Logs | PUP Taguig Dental Clinic')

@section('content')

@php
$logs = $logs ?? collect([]);
$perPage = $perPage ?? 20;
@endphp

<main id="mainContent" class="admin-page-shell">
    <div class="admin-page-container">

        <!-- Page Banner -->
        <div class="page-banner rounded-2xl mb-6">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">System Logs</h1>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="sl-live">
                        <span class="sl-live-dot"></span> Live Monitoring
                    </span>

                    <div class="sl-view-toggle" id="slViewToggle">
                        <button type="button" class="sl-view-toggle-btn active" id="slListViewBtn" title="List view"
                            aria-label="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="sl-view-toggle-btn" id="slGridViewBtn" title="Grid view"
                            aria-label="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- STAT CARDS --}}
        <div class="stat-grid">
            <div class="stat-card s-crimson">                <div class="stat-card-accent"></div>
                <div class="stat-top">
                    <div class="stat-icon">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <span class="stat-badge">Total</span>
                </div>
                <div class="stat-label">Total Logs</div>
                <div class="stat-value" id="statTotal">{{ $totalCount }}</div>
                <div class="stat-footer"><i class="fa-solid fa-list admin-icon-sm"></i>
                    All recorded activity</div>
            </div>
            <div class="stat-card s-red">                <div class="stat-card-accent"></div>
                <div class="stat-top">
                    <div class="stat-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <span class="stat-badge">Admin</span>
                </div>
                <div class="stat-label">Admin Actions</div>
                <div class="stat-value" id="statAdmin">{{ $adminCount }}</div>
                <div class="stat-footer"><i class="fa-solid fa-shield admin-icon-sm"></i>
                    Administrator activity</div>
            </div>
            <div class="stat-card s-blue">                <div class="stat-card-accent"></div>
                <div class="stat-top">
                    <div class="stat-icon"><i class="fa-solid fa-user-doctor"></i></div>
                    <span class="stat-badge">Dentist</span>
                </div>
                <div class="stat-label">Dentist Actions</div>
                <div class="stat-value" id="statDentist">{{ $dentistCount }}</div>
                <div class="stat-footer"><i class="fa-solid fa-stethoscope admin-icon-sm"></i>
                    Dentist activity</div>
            </div>
            <div class="stat-card s-green">                <div class="stat-card-accent"></div>
                <div class="stat-top">
                    <div class="stat-icon"><i class="fa-solid fa-user"></i></div>
                    <span class="stat-badge">Patient</span>
                </div>
                <div class="stat-label">Patient Actions</div>
                <div class="stat-value" id="statPatient">{{ $patientCount }}</div>
                <div class="stat-footer"><i class="fa-solid fa-heart-pulse admin-icon-sm"></i>
                    Patient activity</div>
            </div>
        </div>

        <div class="card">
            {{-- Card Header --}}
            <div class="card-header">
                {{-- Left: icon + title + badge --}}
                <div class="card-header-left">
                    <div class="card-header-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <span class="card-title">Audit Trail</span>
                    <span id="entryBadge"
                        class="bg-red-50 text-[#8B0000] text-[0.68rem] font-bold px-2 py-0.5 rounded-full border border-red-200 ml-1.5">
                        {{ $totalCount }} {{ Str::plural('entry', $totalCount) }}
                    </span>
                </div>

                <div class="card-header-right">
                    <div class="flex items-center gap-2">
                        <div class="search-wrap sl-search-wrap">
                            <i class="fa fa-search"></i>
                            <input id="slSearch" name="search" placeholder="Search logs…" value="{{ $search ?? '' }}"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();slState.search=this.value;slState.page=1;slFetch();}">
                        </div>

                        <button type="button" id="searchClearBtn"
                            class="hidden text-xs font-semibold text-red-600 hover:text-red-800 transition-colors flex-shrink-0"
                            onclick="clearSearch()">
                            Clear
                        </button>
                    </div>

                    <div class="sl-filter-actions-wrap">
                        <button type="button" id="slFilterBtn" class="sl-filter-btn" onclick="openSlFilterPanel()">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Filter</span>
                            <span id="slFilterBadge" class="sl-filter-badge hidden"></span>
                        </button>

                        <button type="button" id="slClearFilterBtn" class="sl-clear-filter-btn hidden"
                            onclick="clearOnlySlFilters()" title="Clear filters">
                            <i class="fa-solid fa-filter-circle-xmark"></i>
                            <span>Clear</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Role Tabs --}}
            @php $activeRole = $role ?? 'all'; @endphp
            <div class="flex gap-1 px-5 py-2.5 border-b border-gray-100 overflow-x-auto">
                @foreach ([['key' => 'all', 'label' => 'All', 'icon' => 'fa-layer-group', 'count' => $totalCount],
                ['key' => 'admin', 'label' => 'Admin', 'icon' => 'fa-user-tie', 'count' => $adminCount], ['key' =>
                'dentist', 'label' => 'Dentist', 'icon' => 'fa-user-doctor', 'count' => $dentistCount], ['key' =>
                'patient', 'label' => 'Patient', 'icon' => 'fa-user', 'count' => $patientCount], ['key' => 'login',
                'label' => 'Logins', 'icon' => 'fa-right-to-bracket', 'count' => $loginCount]] as $tab)
                <button class="tab-btn {{ $activeRole === $tab['key'] ? 'active' : '' }}"
                    onclick="slSetTab(this, '{{ $tab['key'] }}')">
                    <i class="fa-solid {{ $tab['icon'] }} mr-1 text-[0.7rem]"></i>{{ $tab['label'] }}
                    <span
                        class="tab-count {{ $activeRole === $tab['key'] ? 'bg-red-200 text-[#8B0000]' : 'bg-gray-200 text-gray-500' }} text-[0.62rem] font-bold px-1.5 py-0.5 rounded-full ml-1">
                        {{ $tab['count'] }}
                    </span>
                </button>
                @endforeach
            </div>

            {{-- Top pagebar --}}
            <div class="sl-pagebar sl-pagebar-top">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="sl-pagebar-info">
                        @if (method_exists($logs, 'total'))
                        Showing <strong>{{ $logs->firstItem() }}–{{ $logs->lastItem() }}</strong>
                        of <strong>{{ $logs->total() }}</strong> entries
                        @else
                        Showing <strong>{{ $logs->count() }}</strong> {{ Str::plural('entry', $logs->count()) }}
                        @endif
                    </span>
                    <div class="flex items-center gap-1.5">
                        <label class="text-[0.7rem] text-gray-400 font-semibold">Show</label>
                        <select id="perPageSelect"
                            class="h-[30px] px-2 border border-gray-200 rounded-lg text-xs font-semibold text-gray-700 bg-white outline-none cursor-pointer transition-colors focus:border-[#8B0000]">
                            @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" {{ $perPage==$size ? 'selected' : '' }}>
                                {{ $size }}</option>
                            @endforeach
                        </select>
                        <span class="text-[0.7rem] text-gray-400 font-semibold">per page</span>
                    </div>
                </div>
                <div class="sl-pagination-wrap"></div>
            </div>

            {{-- List View --}}
            <div class="sl-view" id="slListView">
                <div class="sl-table-wrap">
                    <table class="data-table" id="slTable">
                        <thead>
                            <tr>
                                <th class="sl-col-id">ID</th>
                                <th class="sl-col-timestamp">Timestamp</th>
                                <th class="sl-col-role">Role</th>
                                <th class="sl-col-user">User</th>
                                <th class="sl-col-action">Action</th>
                                <th class="sl-col-module">Module</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody id="slTableBody">
                            @forelse($logs as $log)
                            @php
                            $role = strtolower($log->actor_role ?? 'other');
                            $action = strtolower($log->action ?? '');
                            $actionClass = match (true) {
                            str_contains($action, 'login') => 'login',
                            str_contains($action, 'logout') => 'logout',
                            str_contains($action, 'create') => 'create',
                            str_contains($action, 'update') => 'update',
                            str_contains($action, 'delete') => 'delete',
                            default => 'default',
                            };
                            $actionIcon = match ($actionClass) {
                            'login' => 'fa-right-to-bracket',
                            'logout' => 'fa-right-from-bracket',
                            'create' => 'fa-plus',
                            'update' => 'fa-pen',
                            'delete' => 'fa-trash',
                            default => 'fa-bolt',
                            };
                            $roleIcon = match ($role) {
                            'admin' => 'fa-user-tie',
                            'dentist' => 'fa-user-doctor',
                            'patient' => 'fa-user',
                            default => 'fa-circle-user',
                            };
                            $avatarLetter = strtoupper(substr($log->actor_name ?? $role, 0, 1));
                            @endphp
                            <tr data-role="{{ $role }}" data-action="{{ $actionClass }}">
                                <td><span class="sl-id">#{{ str_pad($log->id, 3, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>
                                    <span class="sl-date-day">{{ $log->created_at->format('M j, Y') }}</span>
                                    <span class="sl-date-time">{{ $log->created_at->format('h:i:s A') }}</span>
                                </td>
                                <td><span class="sl-role {{ $role }}"><i class="fa-solid {{ $roleIcon }}"></i>{{
                                        ucfirst($role) }}</span>
                                </td>
                                <td>
                                    <div class="sl-user">
                                        <div class="sl-avatar {{ $role }}">{{ $avatarLetter }}</div>
                                        <span class="sl-username">{{ $log->actor_name ?? 'Unknown User' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="sl-action {{ $actionClass }}">
                                        <i class="fa-solid {{ $actionIcon }}"></i>{{ ucwords(str_replace('_', ' ',
                                        $log->action)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="sl-module">
                                        <i class="fa-solid fa-cube"></i>{{ ucfirst(str_replace('_', ' ', $log->module))
                                        }}
                                    </span>
                                </td>
                                <td><span class="sl-desc">{{ $log->description ?? 'No description provided.' }}</span>
                                </td>
                            </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sl-view" id="slGridView" hidden>
                <div class="sl-grid" id="slGridBody">
                    @forelse($logs as $log)
                    @php
                    $role = strtolower($log->actor_role ?? 'other');
                    $action = strtolower($log->action ?? '');
                    $actionClass = match (true) {
                    str_contains($action, 'login') => 'login',
                    str_contains($action, 'logout') => 'logout',
                    str_contains($action, 'create') => 'create',
                    str_contains($action, 'update') => 'update',
                    str_contains($action, 'delete') => 'delete',
                    default => 'default',
                    };
                    $actionIcon = match ($actionClass) {
                    'login' => 'fa-right-to-bracket',
                    'logout' => 'fa-right-from-bracket',
                    'create' => 'fa-plus',
                    'update' => 'fa-pen',
                    'delete' => 'fa-trash',
                    default => 'fa-bolt',
                    };
                    $roleIcon = match ($role) {
                    'admin' => 'fa-user-tie',
                    'dentist' => 'fa-user-doctor',
                    'patient' => 'fa-user',
                    default => 'fa-circle-user',
                    };
                    $avatarLetter = strtoupper(substr($log->actor_name ?? $role, 0, 1));
                    @endphp

                    <div class="sl-grid-card" data-role="{{ $role }}" data-action="{{ $actionClass }}">
                        <div class="sl-grid-top">
                            <div class="sl-grid-id">#{{ str_pad($log->id, 3, '0', STR_PAD_LEFT) }}</div>
                            <span class="sl-action {{ $actionClass }}">
                                <i class="fa-solid {{ $actionIcon }}"></i>{{ ucwords(str_replace('_', ' ',
                                $log->action)) }}
                            </span>
                        </div>

                        <div class="sl-user">
                            <div class="sl-avatar {{ $role }}">{{ $avatarLetter }}</div>
                            <span class="sl-username">{{ $log->actor_name ?? 'Unknown User' }}</span>
                        </div>

                        <div class="sl-grid-meta">
                            <div class="sl-grid-field">
                                <div class="sl-grid-label">Timestamp</div>
                                <div class="sl-grid-value">
                                    {{ $log->created_at->format('M j, Y') }}<br>
                                    {{ $log->created_at->format('h:i:s A') }}
                                </div>
                            </div>

                            <div class="sl-grid-field">
                                <div class="sl-grid-label">Role</div>
                                <div class="sl-grid-value">
                                    <span class="sl-role {{ $role }}">
                                        <i class="fa-solid {{ $roleIcon }}"></i>{{ ucfirst($role) }}
                                    </span>
                                </div>
                            </div>

                            <div class="sl-grid-field">
                                <div class="sl-grid-label">Module</div>
                                <div class="sl-grid-value">
                                    <span class="sl-module">
                                        <i class="fa-solid fa-cube"></i>{{ ucfirst(str_replace('_', ' ', $log->module))
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="sl-grid-field">
                                <div class="sl-grid-label">Description</div>
                                <div class="sl-grid-value">{{ $log->description ?? 'No description provided.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    @endforelse
                </div>
            </div>

            <div id="emptyState" class="admin-hidden"></div>

            {{-- Bottom pagebar --}}
            <div class="sl-pagebar">
                <span class="sl-pagebar-info">
                    @if (method_exists($logs, 'total'))
                    Showing <strong>{{ $logs->firstItem() }}–{{ $logs->lastItem() }}</strong>
                    of <strong>{{ $logs->total() }}</strong> entries
                    @else
                    Showing <strong>{{ $logs->count() }}</strong> {{ Str::plural('entry', $logs->count()) }}
                    @endif
                </span>
                <div class="sl-pagination-wrap"></div>
            </div>
        </div>

    </div>
</main>
<div id="slFilterOverlay" class="sl-filter-overlay" onclick="closeSlFilterPanel()"></div>

<div id="slFilterPanel" class="sl-filter-panel">
    <div class="sl-filter-head">
        <span class="sl-filter-title">
            <i class="fa-solid fa-sliders"></i> Filter logs
        </span>
        <button type="button" class="sl-filter-close" onclick="closeSlFilterPanel()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="sl-filter-body">
        <div id="slActiveFiltersSection" class="sl-active-filters-section hidden">
            <div class="sl-active-filters-head">
                <span class="sl-active-filters-title">Active Filters</span>
                <button id="slClearAllChipsBtn" type="button" class="sl-active-filters-clear-all">
                    Clear All
                </button>
            </div>
            <div id="slActiveChipsContainer" class="sl-active-filters-container"></div>
        </div>

        <div class="sl-filter-section">
            <div class="sl-filter-section-title">Sort order</div>
            <div class="sl-filter-grid">
                <div class="sl-filter-group">
                    <label for="slSortOrder">Sort order</label>
                    <select id="slSortOrder" class="sl-filter-select">
                        <option value="desc">Newest first</option>
                        <option value="asc">Oldest first</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="sl-filter-section">
            <div class="sl-filter-section-title">Date range</div>
            <div class="sl-filter-date-row">
                <div class="sl-filter-date-group">
                    <span class="sl-filter-date-label">From</span>
                    <input type="date" id="slDateFrom" class="sl-filter-input">
                </div>
                <div class="sl-filter-date-group">
                    <span class="sl-filter-date-label">To</span>
                    <input type="date" id="slDateTo" class="sl-filter-input">
                </div>
            </div>
        </div>

        <div class="sl-filter-section">
            <div class="sl-filter-section-title">Action type</div>
            <div class="sl-filter-grid">
                <div class="sl-filter-group">
                    <label for="slActionType">Action type</label>
                    <select id="slActionType" class="sl-filter-select">
                        <option value="">All actions</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="sl-filter-section">
            <div class="sl-filter-section-title">Module</div>
            <div class="sl-filter-grid">
                <div class="sl-filter-group">
                    <label for="slModuleFilter">Module</label>
                    <input type="text" id="slModuleFilter" class="sl-filter-input" placeholder="e.g. appointments">
                </div>
            </div>
        </div>
    </div>

    <div class="sl-filter-footer">
        <button type="button" class="sl-filter-reset" onclick="clearSlFilterPanelDraft()">Clear All</button>
        <button type="button" class="sl-filter-apply" onclick="applySlFilters()">Apply</button>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        syncSlFilterInputs();
        updateSlClearFilterButton();
        initSlViewToggle();


        document.getElementById('slFilterPanel')?.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        ['slSortOrder', 'slDateFrom', 'slDateTo', 'slActionType', 'slModuleFilter'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', renderSlFilterChips);
                el.addEventListener('input', renderSlFilterChips);
            }
        });

        @if (method_exists($logs, 'total') && $logs -> total() > 0)
            slRenderPagebar({
                total: {{ $logs-> total() }},
        from: {{ $logs-> firstItem() ?? 0 }},
        to: {{ $logs-> lastItem() ?? 0 }},
        current_page: {{ $logs-> currentPage() }},
        last_page: {{ $logs-> lastPage() }},
        per_page: {{ $logs-> perPage() }},
                });
    @endif

    var searchInput = document.getElementById('slSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            toggleSearchClear(this);
            clearTimeout(slSearchTimer);
            slSearchTimer = setTimeout(function () {
                slState.search = searchInput.value;
                slState.page = 1;
                slFetch(true);
            }, 400);
        });
    }

    var perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            slState.perPage = parseInt(this.value);
            slState.page = 1;
            slFetch();
        });
    }

    @php $latestLogId = optional(($logs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $logs -> getCollection() : $logs) -> first()) -> id ?? 0; @endphp

    var lastKnownId = {{ $latestLogId }};
    var notifBanner = null;

    function checkForNewLogs() {
        fetch("{{ route('admin.system_logs.check') }}", {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data.latest_id > lastKnownId) {
                    lastKnownId = data.latest_id;
                    showNewLogBanner();
                }
            }).catch(function () { });
    }

    function showNewLogBanner() {
        if (notifBanner) notifBanner.remove();
        notifBanner = document.createElement('div');
        notifBanner.style.cssText =
            'position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;align-items:center;gap:.6rem;background:#fff;border:1.5px solid #a7f3d0;border-radius:12px;padding:.65rem 1.1rem;box-shadow:0 8px 24px rgba(0,0,0,.12);font-size:.78rem;font-weight:600;color:#059669;white-space:nowrap;max-width:90vw;';
        notifBanner.innerHTML =
            '<i class="fa-solid fa-circle-check"></i> New log entries detected. <span class="sl-toast-refresh" onclick="slState.page=1;slFetch();this.closest(\'div\').remove();">Refresh to see</span><button onclick="this.parentElement.remove()" class="sl-toast-close"><i class="fa-solid fa-xmark"></i></button>';
        document.body.appendChild(notifBanner);
    }

    setInterval(checkForNewLogs, 5000);
        });

    var slState = {
        role: '{{ $role ?? 'all' }}',
        search: '{{ $search ?? '' }}',
        perPage: {{ $perPage ?? 20 }},
    page: { { request('page', 1) } },
    sort: '{{ $sort ?? 'desc' }}',
        dateFrom: '{{ $dateFrom ?? '' }}',
            dateTo: '{{ $dateTo ?? '' }}',
                actionType: '{{ $actionType ?? '' }}',
                    module: '{{ $module ?? '' }}',
        };

    var slOverallTotal = {{ $totalCount }};
    var slSearchTimer = null;
    var slController = null;

    function getPreferredSlView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem('systemLogsView') || 'list';
    }

    function applySlView(view, save = true) {
        var listView = document.getElementById('slListView');
        var gridView = document.getElementById('slGridView');
        var listBtn = document.getElementById('slListViewBtn');
        var gridBtn = document.getElementById('slGridViewBtn');

        if (!listView || !gridView) return;

        var finalView = window.innerWidth <= 767 ? 'grid' : view;

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
            localStorage.setItem('systemLogsView', finalView);
        }
    }

    function initSlViewToggle() {
        var listBtn = document.getElementById('slListViewBtn');
        var gridBtn = document.getElementById('slGridViewBtn');

        applySlView(getPreferredSlView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', function () {
                applySlView('list', true);
            });
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', function () {
                applySlView('grid', true);
            });
        }
    }

    function toggleSearchClear(input) {
        var btn = document.getElementById('searchClearBtn');
        if (!btn) return;

        if ((input.value || '').trim().length > 0) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    }

    function clearSearch() {
        var input = document.getElementById('slSearch');
        var btn = document.getElementById('searchClearBtn');
        if (!input) return;

        input.value = '';
        slState.search = '';
        slState.page = 1;

        if (btn) btn.classList.add('hidden');

        slFetch();
        input.focus();
    }

    function slSetTab(el, role) {
        slState.role = role;
        slState.page = 1;
        document.querySelectorAll('.tab-btn').forEach(function (b) {
            b.classList.remove('active');
        });
        el.classList.add('active');
        document.querySelectorAll('.tab-btn .tab-count').forEach(function (span) {
            span.className =
                'tab-count bg-gray-200 text-gray-500 text-[0.62rem] font-bold px-1.5 py-0.5 rounded-full ml-1';
        });
        var activeSpan = el.querySelector('.tab-count');
        if (activeSpan) activeSpan.className =
            'tab-count bg-red-200 text-[#8B0000] text-[0.62rem] font-bold px-1.5 py-0.5 rounded-full ml-1';
        slFetch();
    }

    function slGoPage(page) {
        slState.page = page;
        slFetch();
    }

    function hasActiveSlFilters() {
        return (slState.sort && slState.sort !== 'desc') ||
            !!slState.dateFrom || !!slState.dateTo ||
            !!slState.actionType || !!slState.module;
    }

    function syncSlFilterInputs() {
        var sort = document.getElementById('slSortOrder');
        var from = document.getElementById('slDateFrom');
        var to = document.getElementById('slDateTo');
        var action = document.getElementById('slActionType');
        var module = document.getElementById('slModuleFilter');

        if (sort) sort.value = slState.sort || 'desc';
        if (from) from.value = slState.dateFrom || '';
        if (to) to.value = slState.dateTo || '';
        if (action) action.value = slState.actionType || '';
        if (module) module.value = slState.module || '';
    }

    function openSlFilterPanel() {
        syncSlFilterInputs();
        renderSlFilterChips();

        document.getElementById('slFilterPanel')?.classList.add('open');
        document.getElementById('slFilterOverlay')?.classList.add('open');
        document.getElementById('slFilterBtn')?.classList.add('active');
        document.body.classList.add('overflow-hidden');
    }

    function closeSlFilterPanel() {
        document.getElementById('slFilterPanel')?.classList.remove('open');
        document.getElementById('slFilterOverlay')?.classList.remove('open');
        document.getElementById('slFilterBtn')?.classList.remove('active');
        document.body.classList.remove('overflow-hidden');
    }

    function updateSlClearFilterButton() {
        var btn = document.getElementById('slClearFilterBtn');
        var badge = document.getElementById('slFilterBadge');
        if (!btn || !badge) return;

        var count = 0;
        if (slState.sort && slState.sort !== 'desc') count++;
        if (slState.dateFrom || slState.dateTo) count++;
        if (slState.actionType) count++;
        if (slState.module) count++;

        var has = count > 0;

        btn.classList.toggle('hidden', !has);

        if (has) {
            badge.classList.remove('hidden');
            badge.textContent = count;
        } else {
            badge.classList.add('hidden');
            badge.textContent = '';
        }
    }

    function clearOnlySlFilters() {
        slState.sort = 'desc';
        slState.dateFrom = '';
        slState.dateTo = '';
        slState.actionType = '';
        slState.module = '';
        slState.page = 1;

        syncSlFilterInputs();
        updateSlClearFilterButton();
        closeSlFilterPanel();
        slFetch();
    }

    function clearSlFilterPanelDraft() {
        var sort = document.getElementById('slSortOrder');
        var from = document.getElementById('slDateFrom');
        var to = document.getElementById('slDateTo');
        var action = document.getElementById('slActionType');
        var module = document.getElementById('slModuleFilter');

        if (sort) sort.value = 'desc';
        if (from) from.value = '';
        if (to) to.value = '';
        if (action) action.value = '';
        if (module) module.value = '';

        renderSlFilterChips();
    }

    function renderSlFilterChips() {
        var container = document.getElementById('slActiveChipsContainer');
        var section = document.getElementById('slActiveFiltersSection');
        var clearAllBtn = document.getElementById('slClearAllChipsBtn');

        if (!container || !section) return;

        container.innerHTML = '';
        var hasChips = false;

        function addChip(label, callback) {
            hasChips = true;

            var chip = document.createElement('div');
            chip.className = 'sl-filter-chip';
            chip.innerHTML =
                "<span>" + label +
                "</span><span class='sl-filter-chip-remove'><i class='fa-solid fa-xmark'></i></span>";

            chip.querySelector('.sl-filter-chip-remove').onclick = function () {
                callback();
                renderSlFilterChips();
            };

            container.appendChild(chip);
        }

        var sortVal = document.getElementById('slSortOrder')?.value || 'desc';
        var fromVal = document.getElementById('slDateFrom')?.value || '';
        var toVal = document.getElementById('slDateTo')?.value || '';
        var actionVal = document.getElementById('slActionType')?.value || '';
        var moduleVal = document.getElementById('slModuleFilter')?.value || '';

        if (sortVal === 'asc') {
            addChip('Sort: Oldest first', function () {
                document.getElementById('slSortOrder').value = 'desc';
            });
        }

        if (fromVal || toVal) {
            var lbl = fromVal && toVal ? (fromVal + ' to ' + toVal) : (fromVal ? 'From ' + fromVal : 'Until ' + toVal);
            addChip('Date: ' + lbl, function () {
                document.getElementById('slDateFrom').value = '';
                document.getElementById('slDateTo').value = '';
            });
        }

        if (actionVal) {
            addChip('Action: ' + actionVal.charAt(0).toUpperCase() + actionVal.slice(1), function () {
                document.getElementById('slActionType').value = '';
            });
        }

        if (moduleVal) {
            addChip('Module: ' + moduleVal, function () {
                document.getElementById('slModuleFilter').value = '';
            });
        }

        if (hasChips) {
            section.classList.remove('hidden');

            if (clearAllBtn) {
                clearAllBtn.onclick = function () {
                    clearSlFilterPanelDraft();
                };
            }
        } else {
            section.classList.add('hidden');
        }
    }

    function applySlFilters() {
        slState.sort = document.getElementById('slSortOrder')?.value || 'desc';
        slState.dateFrom = document.getElementById('slDateFrom')?.value || '';
        slState.dateTo = document.getElementById('slDateTo')?.value || '';
        slState.actionType = document.getElementById('slActionType')?.value || '';
        slState.module = document.getElementById('slModuleFilter')?.value || '';
        slState.page = 1;

        updateSlClearFilterButton();
        closeSlFilterPanel();
        slFetch();
    }

    function resetSlFilters() {
        clearOnlySlFilters();
    }

    function slFetch(silent) {
        if (slController) slController.abort();
        slController = new AbortController();

        var params = new URLSearchParams({
            role: slState.role,
            search: slState.search,
            per_page: slState.perPage,
            page: slState.page,
            sort: slState.sort,
            date_from: slState.dateFrom,
            date_to: slState.dateTo,
            action_type: slState.actionType,
            module: slState.module,
        });

        history.replaceState(null, '', window.location.pathname + '?' + params.toString());

        if (!silent) {
            document.getElementById('slTableBody').innerHTML = slSkeletonRows(slState.perPage);
        }
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('slTable').style.display = '';

        fetch('{{ route('admin.system_logs') }}?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
            },
            signal: slController.signal
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                slRenderRows(data.logs);
                slRenderPagebar(data.pagination);
                slRenderCounts(data.counts);
            })
            .catch(function (e) {
                if (e.name !== 'AbortError') console.error('Fetch error:', e);
            });
    }

    function slSkeletonRows(count) {
        var row = '<tr>' +
            '<td><span class="sl-id sl-skeleton sl-skeleton-check">&nbsp;&nbsp;&nbsp;&nbsp;</span></td>' +
            '<td><span class="sl-skeleton sl-skeleton-id">&nbsp;</span></td>' +
            '<td><span class="sl-skeleton sl-skeleton-role">&nbsp;</span></td>' +
            '<td><span class="sl-skeleton sl-skeleton-user">&nbsp;</span></td>' +
            '<td><span class="sl-skeleton sl-skeleton-action">&nbsp;</span></td>' +
            '<td><span class="sl-skeleton sl-skeleton-id">&nbsp;</span></td>' +
            '<td><span class="sl-skeleton sl-skeleton-desc">&nbsp;</span></td>' +
            '</tr>';
        var html = '';
        for (var i = 0; i < Math.min(count, 5); i++) html += row;
        return html;
    }

    function slRenderRows(logs) {
        var tableBody = document.getElementById('slTableBody');
        var gridBody = document.getElementById('slGridBody');

        if (!logs || logs.length === 0) {
            if (tableBody) tableBody.innerHTML = '';
            if (gridBody) gridBody.innerHTML = '';
            showEmptyState(slState.search);
            return;
        }

        var actionIcons = {
            login: 'fa-right-to-bracket',
            logout: 'fa-right-from-bracket',
            create: 'fa-plus',
            update: 'fa-pen',
            delete: 'fa-trash',
            default: 'fa-bolt'
        };

        var roleIcons = {
            admin: 'fa-user-tie',
            dentist: 'fa-user-doctor',
            patient: 'fa-user'
        };

        var tableHtml = '';
        var gridHtml = '';

        logs.forEach(function (log) {
            var role = (log.actor_role || 'other').toLowerCase();
            var action = (log.action || '').toLowerCase();
            var actionClass = action.includes('login') ? 'login' :
                action.includes('logout') ? 'logout' :
                    action.includes('create') ? 'create' :
                        action.includes('update') ? 'update' :
                            action.includes('delete') ? 'delete' :
                                'default';

            var actionIcon = actionIcons[actionClass] || 'fa-bolt';
            var roleIcon = roleIcons[role] || 'fa-circle-user';
            var letter = (log.actor_name || role).charAt(0).toUpperCase();
            var idPadded = '#' + String(log.id).padStart(3, '0');
            var actionLabel = (log.action || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
            var moduleLabel = (log.module || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
            var actorName = log.actor_name ?? log.actor_identifier ?? 'Unknown User';
            var description = log.description || 'No description provided.';

            tableHtml += '<tr data-role="' + role + '" data-action="' + actionClass + '" class="sl-row-new">';
            tableHtml += '<td><span class="sl-id">' + idPadded + '</span></td>';
            tableHtml += '<td><span class="sl-date-day">' + log.created_at_day +
                '</span><span class="sl-date-time">' + log.created_at_time + '</span></td>';
            tableHtml += '<td><span class="sl-role ' + role + '"><i class="fa-solid ' + roleIcon + '"></i>' +
                role.charAt(0).toUpperCase() + role.slice(1) + '</span></td>';
            tableHtml += '<td><div class="sl-user"><div class="sl-avatar ' + role + '">' + letter +
                '</div><span class="sl-username">' + actorName + '</span></div></td>';
            tableHtml += '<td><span class="sl-action ' + actionClass + '"><i class="fa-solid ' + actionIcon +
                '"></i>' + actionLabel + '</span></td>';
            tableHtml += '<td><span class="sl-module"><i class="fa-solid fa-cube"></i>' + moduleLabel +
                '</span></td>';
            tableHtml += '<td><span class="sl-desc" title="' + description + '">' + description +
                '</span></td>';
            tableHtml += '</tr>';

            gridHtml += '<div class="sl-grid-card">';
            gridHtml += '<div class="sl-grid-top">';
            gridHtml += '<div class="sl-grid-id">' + idPadded + '</div>';
            gridHtml += '<span class="sl-action ' + actionClass + '"><i class="fa-solid ' + actionIcon +
                '"></i>' + actionLabel + '</span>';
            gridHtml += '</div>';

            gridHtml += '<div class="sl-user"><div class="sl-avatar ' + role + '">' + letter +
                '</div><span class="sl-username">' + actorName + '</span></div>';

            gridHtml += '<div class="sl-grid-meta">';
            gridHtml +=
                '<div class="sl-grid-field"><div class="sl-grid-label">Timestamp</div><div class="sl-grid-value">' +
                log.created_at_day + '<br>' + log.created_at_time + '</div></div>';
            gridHtml +=
                '<div class="sl-grid-field"><div class="sl-grid-label">Role</div><div class="sl-grid-value"><span class="sl-role ' +
                role + '"><i class="fa-solid ' + roleIcon + '"></i>' + role.charAt(0).toUpperCase() + role
                    .slice(1) + '</span></div></div>';
            gridHtml +=
                '<div class="sl-grid-field"><div class="sl-grid-label">Module</div><div class="sl-grid-value"><span class="sl-module"><i class="fa-solid fa-cube"></i>' +
                moduleLabel + '</span></div></div>';
            gridHtml +=
                '<div class="sl-grid-field"><div class="sl-grid-label">Description</div><div class="sl-grid-value">' +
                description + '</div></div>';
            gridHtml += '</div>';
            gridHtml += '</div>';
        });

        if (tableBody) tableBody.innerHTML = tableHtml;
        if (gridBody) gridBody.innerHTML = gridHtml;

        document.getElementById('emptyState').style.display = 'none';
        applySlView(getPreferredSlView(), false);
    }

    function slRenderPagebar(p) {
        if (!p) return;
        var infoHtml = 'Showing <strong>' + p.from + '–' + p.to + '</strong> of <strong>' + p.total +
            '</strong> entries';
        document.querySelectorAll('.sl-pagebar-info').forEach(function (el) {
            el.innerHTML = infoHtml;
        });
        var navHtml = slBuildPagination(p);
        document.querySelectorAll('.sl-pagination-wrap').forEach(function (el) {
            el.innerHTML = navHtml;
        });
        var badge = document.getElementById('entryBadge');
        if (badge) badge.textContent = slOverallTotal + ' ' + (slOverallTotal === 1 ? 'entry' : 'entries');
    }

    function slBuildPagination(p) {
        if (p.last_page <= 1) return '';
        var current = p.current_page,
            last = p.last_page;
        var winSize = 5,
            half = Math.floor(winSize / 2);
        var start = Math.max(1, current - half);
        var end = Math.min(last, start + winSize - 1);
        if (end - start + 1 < winSize) start = Math.max(1, end - winSize + 1);

        var btn = 'class="sl-page-btn"';
        var btnActive = 'class="sl-page-current"';
        var btnDis = 'class="sl-page-disabled"';
        var dots = '<span class="sl-page-ellipsis">&hellip;</span>';

        var html = '<nav class="sl-pagination">';
        if (current <= 1) {
            html += '<button disabled ' + btnDis +
                '><i class="fa-solid fa-chevron-left sl-page-icon"></i></button>';
        } else {
            html += '<button onclick="slGoPage(' + (current - 1) + ')" ' + btn +
                '><i class="fa-solid fa-chevron-left sl-page-icon"></i></button>';
        }
        if (start > 1) {
            html += '<button onclick="slGoPage(1)" ' + btn + '>1</button>';
            if (start > 2) html += dots;
        }
        for (var i = start; i <= end; i++) {
            html += i === current ? '<span ' + btnActive + '>' + i + '</span>' : '<button onclick="slGoPage(' + i +
                ')" ' + btn + '>' + i + '</button>';
        }
        if (end < last) {
            if (end < last - 1) html += dots;
            html += '<button onclick="slGoPage(' + last + ')" ' + btn + '>' + last + '</button>';
        }
        if (current >= last) {
            html += '<button disabled ' + btnDis +
                '><i class="fa-solid fa-chevron-right sl-page-icon"></i></button>';
        } else {
            html += '<button onclick="slGoPage(' + (current + 1) + ')" ' + btn +
                '><i class="fa-solid fa-chevron-right sl-page-icon"></i></button>';
        }
        html += '</nav>';
        return html;
    }

    function slRenderCounts(counts) {
        if (!counts) return;
        slOverallTotal = counts.total;
        document.getElementById('statTotal').textContent = counts.total;
        document.getElementById('statAdmin').textContent = counts.admin;
        document.getElementById('statDentist').textContent = counts.dentist;
        document.getElementById('statPatient').textContent = counts.patient;
        var badge = document.getElementById('entryBadge');
        if (badge) badge.textContent = slOverallTotal + ' ' + (slOverallTotal === 1 ? 'entry' : 'entries');
    }

    function showEmptyState(query) {
        var emptyState = document.getElementById('emptyState');
        var table = document.getElementById('slTable');
        if (!emptyState) return;
        if (table) table.style.display = '';
        var listView = document.getElementById('slListView');
        var gridView = document.getElementById('slGridView');
        if (listView) listView.hidden = true;
        if (gridView) gridView.hidden = true;
        emptyState.style.display = 'block';

        var icon, title, sub, extra = '';
        if (query) {
            icon = 'fa-magnifying-glass';
            title = 'No results for \u201c' + query + '\u201d';
            sub = 'Try a different name, action, or user.';
            extra =
                '<button onclick="clearSearch()" class="sl-empty-action"><i class="fa-solid fa-xmark"></i>Clear search</button>';
        } else if (slState.role !== 'all') {
            var labels = {
                admin: 'Admin',
                dentist: 'Dentist',
                patient: 'Patient',
                login: 'Login'
            };
            icon = 'fa-filter';
            title = 'No ' + (labels[slState.role] || slState.role) + ' logs found';
            sub = 'There are no logs matching this filter yet.';
            extra =
                '<button onclick="slSetTab(document.querySelector(\'.tab-btn\'),\'all\')" class="sl-empty-action"><i class="fa-solid fa-xmark"></i>Show all logs</button>';
        } else if (hasActiveSlFilters()) {
            icon = 'fa-filter-circle-xmark';
            title = 'No logs match the selected filters';
            sub = 'Try adjusting or clearing the filters.';
            extra =
                '<button onclick="clearOnlySlFilters()" class="sl-empty-action"><i class="fa-solid fa-filter-circle-xmark"></i>Clear filters</button>';
        } else {
            icon = 'fa-clipboard-list';
            title = 'No system logs yet';
            sub = 'Activity will appear here once users interact with the system.';
        }

        emptyState.innerHTML =
            '<div class="sl-empty-state"><div class="sl-empty-icon"><i class="fa-solid ' +
            icon +
            '"></i></div><p class="sl-empty-title">' +
            title + '</p><p class="sl-empty-subtitle">' + sub + '</p>' + extra +
            '</div>';

        window.addEventListener('resize', function () {
            applySlView(getPreferredSlView(), false);
        });
    }
</script>
@endsection
