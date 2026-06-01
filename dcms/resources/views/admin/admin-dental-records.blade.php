@extends('layouts.admin')

@section('title', 'Dental Records | PUP Taguig Dental Clinic')

@section('content')
@php
use Carbon\Carbon;

$recordsSource = $records ?? collect();
$recordItems =
$recordsSource instanceof \Illuminate\Pagination\AbstractPaginator
? collect($recordsSource->items())
: collect($recordsSource);

$totalRecordsCount = $totalRecords ?? $recordItems->count();
$recordsTodayCount =
$recordsToday ??
$recordItems
->filter(function ($record) {
return !empty($record->date) && Carbon::parse($record->date)->isToday();
})
->count();
$pendingCount =
$pending ??
$recordItems->filter(fn($record) => strtolower($record->status ?? 'pending') === 'pending')->count();
@endphp

<main id="mainContent" class="admin-page-shell admin-dental-records-page page-enter mode-list">
    <div class="w-full">

        <div class="page-banner mt-2 mb-6">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Dental Records</h1>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="page-badge">
                        <span class="page-badge-dot"></span>
                        {{ number_format($totalRecordsCount) }}
                        {{ \Illuminate\Support\Str::plural('record', $totalRecordsCount) }}
                    </span>

                    <a href="{{ route('admin.reports.index') }}" class="ui-btn ui-btn-secondary">
                        <i class="fa-solid fa-chart-column"></i>
                        <span>View Reports</span>
                    </a>
                </div>
            </div>
        </div>

        <div id="statCards" class="stat-grid admin-dashboard-stat-grid dental-records-stat-grid">
            <div class="stat-card s-all">
                <div class="stat-card-info">
                    <div class="stat-label">Total Records</div>
                    <div class="stat-num">{{ number_format($totalRecordsCount) }}</div>
                    <div class="stat-footer">all dental records</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
            </div>

            <div class="stat-card s-today">
                <div class="stat-card-info">
                    <div class="stat-label">Added Today</div>
                    <div class="stat-num">{{ number_format($recordsTodayCount) }}</div>
                    <div class="stat-footer">new entries</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
            </div>

            <div class="stat-card s-pending">
                <div class="stat-card-info">
                    <div class="stat-label">Pending Records</div>
                    <div class="stat-num">{{ number_format($pendingCount) }}</div>
                    <div class="stat-footer">needs action</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-user-clock"></i></div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">
            <section class="table-card rounded-2xl border border-gray-200 shadow-sm overflow-visible">
                <div class="patient-table-toolbar record-toolbar px-4 md:px-6 py-4 border-b border-gray-100">
                    <div class="record-toolbar-layout">
                        <span id="recordRowCount" class="sr-only">
                            {{ $recordItems->count() }} records
                        </span>

                        <div class="record-sort-dropdown" id="recordStatusField" data-status-filter="all">
                            <button type="button" class="record-sort-trigger" id="recordStatusDropdownBtn"
                                aria-expanded="false" aria-haspopup="true">
                                <span class="record-sort-icon" id="recordStatusSelectedIcon">
                                    <i class="fa-solid fa-layer-group"></i>
                                </span>

                                <span class="record-sort-copy">
                                    <span class="record-sort-label">Sort By</span>
                                    <span class="record-sort-value" id="recordStatusSelectedLabel">All Records</span>
                                </span>

                                <span class="record-sort-count" id="recordSortCount">
                                    {{ $recordItems->count() }}
                                </span>

                                <i class="fa-solid fa-chevron-down record-sort-chevron"></i>
                            </button>

                            <input type="hidden" id="recordStatusFilter" value="all">

                            <div class="record-sort-menu" id="recordStatusMenu">
                                <button type="button" class="record-sort-option is-active" data-filter="all"
                                    data-label="All Records" data-icon="fa-layer-group">
                                    <span class="record-option-icon"><i class="fa-solid fa-layer-group"></i></span>
                                    <span>All Records</span>
                                </button>

                                <button type="button" class="record-sort-option" data-filter="today"
                                    data-label="Added Today" data-icon="fa-clock">
                                    <span class="record-option-icon"><i class="fa-solid fa-clock"></i></span>
                                    <span>Added Today</span>
                                </button>

                                <button type="button" class="record-sort-option" data-filter="pending"
                                    data-label="Pending" data-icon="fa-user-clock">
                                    <span class="record-option-icon"><i class="fa-solid fa-user-clock"></i></span>
                                    <span>Pending</span>
                                </button>

                                <button type="button" class="record-sort-option" data-filter="ongoing"
                                    data-label="Ongoing" data-icon="fa-spinner">
                                    <span class="record-option-icon"><i class="fa-solid fa-spinner"></i></span>
                                    <span>Ongoing</span>
                                </button>

                                <button type="button" class="record-sort-option" data-filter="completed"
                                    data-label="Completed" data-icon="fa-check">
                                    <span class="record-option-icon"><i class="fa-solid fa-check"></i></span>
                                    <span>Completed</span>
                                </button>

                                <button type="button" class="record-sort-option" data-filter="cancelled"
                                    data-label="Cancelled" data-icon="fa-xmark">
                                    <span class="record-option-icon"><i class="fa-solid fa-xmark"></i></span>
                                    <span>Cancelled</span>
                                </button>
                            </div>
                        </div>

                        <div class="record-toolbar-actions">
                            <div class="record-search-row voice-search-row">
                                <div class="search-wrap global-search flex-1" data-search-wrapper>
                                    <i class="fa-solid fa-magnifying-glass search-icon"></i>

                                    <input id="dentalRecordSearch" type="text" placeholder="Search patient name..."
                                        autocomplete="off" data-search-input class="search-input" />

                                    <button type="button" class="search-clear" data-search-clear
                                        aria-label="Clear search">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </div>

                                <div class="voice-input-toggle">
                                    <button type="button" id="dentalRecordMicToggleBtn"
                                        class="voice-search-mic external" data-voice-trigger
                                        data-voice-target="#dentalRecordSearch"
                                        data-voice-status="#dentalRecordVoiceStatus"
                                        aria-label="Voice search dental records">
                                        <i class="fa-solid fa-microphone"></i>
                                    </button>

                                    <span id="dentalRecordVoiceStatus" class="voice-status hidden" data-voice-status
                                        aria-live="polite"></span>
                                </div>
                            </div>

                            <div class="view-toggle-container record-view-toggle" id="dentalRecordViewToggle"
                                data-global-view-toggle data-view-root="#mainContent"
                                data-list-view="#dentalRecordListView" data-grid-view="#dentalRecordGridView"
                                data-storage-key="admin_dental_records_view" aria-label="Record view options">
                                <span class="view-slider" aria-hidden="true"></span>

                                <button type="button" class="btn-view-mode active" title="List view"
                                    aria-label="List view" aria-pressed="true" data-view-mode="list">
                                    <i class="fa-solid fa-list"></i>
                                </button>

                                <button type="button" class="btn-view-mode" title="Grid view" aria-label="Grid view"
                                    aria-pressed="false" data-view-mode="grid">
                                    <i class="fa-solid fa-grip"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($recordItems->isEmpty())
                <div id="dentalRecordEmptyState" class="empty-state-host show"></div>
                @else
                <div id="dentalRecordListView" class="dental-record-list-view overflow-x-auto">
                    <table class="w-full min-w-[880px] border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-left">
                                <th class="px-5 py-3 text-[11px] font-black uppercase tracking-wider text-gray-400">
                                    Patient</th>
                                <th class="px-5 py-3 text-[11px] font-black uppercase tracking-wider text-gray-400">
                                    Procedure</th>
                                <th class="px-5 py-3 text-[11px] font-black uppercase tracking-wider text-gray-400">
                                    Dentist</th>
                                <th class="px-5 py-3 text-[11px] font-black uppercase tracking-wider text-gray-400">
                                    Date</th>
                                <th class="px-5 py-3 text-[11px] font-black uppercase tracking-wider text-gray-400">
                                    Status</th>
                                <th
                                    class="px-5 py-3 text-[11px] font-black uppercase tracking-wider text-gray-400 text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dentalRecordsTableBody" class="divide-y divide-gray-100">
                            @foreach ($recordsSource as $record)
                            @php
                            $rawStatus = strtolower(trim($record->status ?? 'pending'));
                            $normalizedStatus = str_replace([' ', '_'], '-', $rawStatus);
                            $statusClass = match ($normalizedStatus) {
                            'completed' => 'status-completed',
                            'ongoing', 'in-progress' => 'status-ongoing',
                            'cancelled', 'canceled' => 'status-cancelled',
                            default => 'status-pending',
                            };
                            $patientName =
                            $record->patient_name ??
                            (data_get($record, 'patient.name') ??
                            (data_get($record, 'patient.full_name') ?? 'Unknown Patient'));
                            $dentistName =
                            $record->dentist_name ??
                            (data_get($record, 'dentist.name') ??
                            (data_get($record, 'dentist.full_name') ?? '—'));
                            $procedure = $record->procedure ?? '—';
                            $recordDate = !empty($record->date) ? Carbon::parse($record->date) : null;
                            $dateText = $recordDate ? $recordDate->format('M d, Y') : '—';
                            $dateIso = $recordDate ? $recordDate->toDateString() : '';
                            $initial = strtoupper(substr($patientName, 0, 1));
                            @endphp

                            <tr class="dental-record-row dental-record-item hover:bg-red-50/40 transition-colors cursor-pointer"
                                data-patient="{{ strtolower($patientName) }}"
                                data-procedure="{{ strtolower($procedure) }}"
                                data-dentist="{{ strtolower($dentistName) }}" data-status="{{ $normalizedStatus }}"
                                data-date="{{ $dateIso }}" @if (!empty($record->id)) onclick="openRecordPanel({{
                                $record->id }})" @endif>
                                <td class="px-5 py-4 align-middle">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-9 h-9 rounded-full bg-gradient-to-br from-[#8B0000] to-[#6b0000] text-white flex items-center justify-center text-xs font-black flex-shrink-0">
                                            {{ $initial }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-black text-gray-800 text-sm truncate">
                                                {{ $patientName }}</div>
                                            <div class="text-[11px] font-bold text-gray-400">Dental record
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-middle">
                                    <span class="text-sm font-bold text-gray-700 break-words">{{ $procedure }}</span>
                                </td>
                                <td class="px-5 py-4 align-middle">
                                    <span class="text-sm font-bold text-gray-600">{{ $dentistName }}</span>
                                </td>
                                <td class="px-5 py-4 align-middle">
                                    <div class="inline-flex items-center gap-2 text-sm font-bold text-gray-700">
                                        <i class="fa-solid fa-calendar-day text-gray-400 text-xs"></i>
                                        {{ $dateText }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-middle">
                                    <span class="status-pill {{ $statusClass }}">
                                        <span class="status-dot"></span>
                                        {{ ucfirst(str_replace('-', ' ', $normalizedStatus)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-middle text-right">
                                    @if (!empty($record->id))
                                    <button type="button" class="ui-icon-btn edit"
                                        onclick="event.stopPropagation(); openRecordPanel({{ $record->id }})"
                                        title="View record">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="dentalRecordGridView" class="dental-record-grid-view" hidden>
                    <div class="dental-record-grid">
                        @foreach ($recordsSource as $record)
                        @php
                        $rawStatus = strtolower(trim($record->status ?? 'pending'));
                        $normalizedStatus = str_replace([' ', '_'], '-', $rawStatus);

                        $statusClass = match ($normalizedStatus) {
                        'completed' => 'status-completed',
                        'ongoing', 'in-progress' => 'status-ongoing',
                        'cancelled', 'canceled' => 'status-cancelled',
                        default => 'status-pending',
                        };

                        $patientName =
                        $record->patient_name ??
                        (data_get($record, 'patient.name') ??
                        (data_get($record, 'patient.full_name') ?? 'Unknown Patient'));

                        $dentistName =
                        $record->dentist_name ??
                        (data_get($record, 'dentist.name') ??
                        (data_get($record, 'dentist.full_name') ?? '—'));

                        $procedure = $record->procedure ?? '—';
                        $recordDate = !empty($record->date) ? Carbon::parse($record->date) : null;
                        $dateText = $recordDate ? $recordDate->format('M d, Y') : '—';
                        $dateIso = $recordDate ? $recordDate->toDateString() : '';
                        $initial = strtoupper(substr($patientName, 0, 1));
                        @endphp

                        <article class="dental-record-grid-card dental-record-item"
                            data-patient="{{ strtolower($patientName) }}" data-procedure="{{ strtolower($procedure) }}"
                            data-dentist="{{ strtolower($dentistName) }}" data-status="{{ $normalizedStatus }}"
                            data-date="{{ $dateIso }}" @if (!empty($record->id)) onclick="openRecordPanel({{ $record->id
                            }})" @endif>

                            <div class="dental-record-grid-top">
                                <div class="dental-record-grid-avatar">
                                    {{ $initial }}
                                </div>

                                <div class="dental-record-grid-main">
                                    <div class="dental-record-grid-name">{{ $patientName }}</div>
                                    <div class="dental-record-grid-sub">Dental record</div>
                                </div>

                                <span class="status-pill {{ $statusClass }}">
                                    <span class="status-dot"></span>
                                    {{ ucfirst(str_replace('-', ' ', $normalizedStatus)) }}
                                </span>
                            </div>

                            <div class="dental-record-grid-meta">
                                <div class="dental-record-grid-meta-item">
                                    <span class="dental-record-grid-label">Procedure</span>
                                    <span class="dental-record-grid-value">{{ $procedure }}</span>
                                </div>

                                <div class="dental-record-grid-meta-item">
                                    <span class="dental-record-grid-label">Dentist</span>
                                    <span class="dental-record-grid-value">{{ $dentistName }}</span>
                                </div>

                                <div class="dental-record-grid-meta-item">
                                    <span class="dental-record-grid-label">Date</span>
                                    <span class="dental-record-grid-value">
                                        <i class="fa-solid fa-calendar-day"></i>
                                        {{ $dateText }}
                                    </span>
                                </div>
                            </div>
                        </article>
                        @endforeach
                    </div>
                </div>
                <div id="dentalRecordEmptyState" class="empty-state-host"></div>

                @if ($recordsSource instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="px-4 md:px-6 py-4 border-t border-gray-100">
                    {{ $recordsSource->links() }}
                </div>
                @endif
                @endif
            </section>

            <aside class="space-y-5">
                <section
                    class="table-card rounded-2xl border border-gray-200 shadow-sm overflow-hidden xl:sticky xl:top-6">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div
                            class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#8B0000] to-[#6b0000] text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                            <i class="fa-solid fa-notes-medical"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 id="panelRecordTitle" class="text-sm font-black text-gray-800 truncate">Select a
                                record</h2>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Dental Record</p>
                        </div>
                    </div>

                    <div id="panelBody" class="p-5">
                        <div class="text-center py-8">
                            <div class="empty-state-icon !w-[64px] !h-[64px] !rounded-2xl !mb-4">
                                <i class="fa-solid fa-notes-medical !text-[26px]"></i>
                            </div>
                            <h3 class="empty-state-title !text-[15px]">No record selected</h3>
                            <p class="empty-state-sub !text-[13px] !mt-2">Click a row to view the record details.</p>
                        </div>
                    </div>

                    <div id="panelFoot" class="hidden px-5 py-4 border-t border-gray-100 bg-gray-50 flex-wrap gap-2">
                    </div>
                </section>

                <section class="table-card rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div
                            class="dental-req-quick-actions-icon w-10 h-10 rounded-2xl bg-red-50 text-[#8B0000] border border-red-100 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-gray-800">Record Insights</h2>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Summary statistics
                            </p>
                        </div>
                    </div>

                    <div class="record-insights-list">
                        <div class="record-insight-row px-5 py-4 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-[11px] font-black uppercase tracking-wider text-gray-400">Most Common
                                    Procedure</div>
                                <div class="text-sm font-black text-gray-800 truncate">
                                    {{ $topProcedure ?? 'No data yet' }}</div>
                            </div>
                            <span class="status-pill status-default"><i class="fa-solid fa-tooth"></i></span>
                        </div>

                        <div class="record-insight-row px-5 py-4 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-black uppercase tracking-wider text-gray-400">Completed
                                    This Week</div>
                                <div class="text-sm font-black text-gray-800">
                                    {{ number_format($completedThisWeek ?? 0) }}</div>
                            </div>
                            <span class="status-pill status-completed"><i class="fa-solid fa-circle-check"></i></span>
                        </div>

                        <div class="record-insight-row px-5 py-4 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-black uppercase tracking-wider text-gray-400">Patients For
                                    Follow-Up</div>
                                <div class="text-sm font-black text-gray-800">
                                    {{ number_format($patientsForFollowUp ?? 0) }}</div>
                            </div>
                            <span class="status-pill status-pending"><i class="fa-solid fa-user-clock"></i></span>
                        </div>
                    </div>
                </section>

                <section class="table-card rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div
                            class="dental-req-quick-actions-icon w-10 h-10 rounded-2xl bg-red-50 text-[#8B0000] border border-red-100 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-gray-800">Quick Actions</h2>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Common tasks</p>
                        </div>
                    </div>

                    <div class="quick-actions-list">
                        <a href="{{ route('admin.reports.index') }}" class="quick-action quick-action-card">
                            <span class="quick-action-icon">
                                <i class="fa-solid fa-chart-column"></i>
                            </span>

                            <span class="quick-action-copy">
                                <span class="quick-action-title">Dental Reports</span>
                                <span class="quick-action-sub">View analytics and summaries</span>
                            </span>

                            <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                            <i class="fa-solid fa-chart-column quick-action-bg-icon"></i>
                        </a>

                        <a href="{{ route('admin.appointments') }}" class="quick-action quick-action-card">
                            <span class="quick-action-icon">
                                <i class="fa-solid fa-calendar-check"></i>
                            </span>

                            <span class="quick-action-copy">
                                <span class="quick-action-title">Appointments</span>
                                <span class="quick-action-sub">Check scheduled clinic visits</span>
                            </span>

                            <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                            <i class="fa-solid fa-calendar-check quick-action-bg-icon"></i>
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script>
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    function escapeHtml(value) {
        return String(value ?? '—')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeStatus(value) {
        return String(value || 'pending').trim().toLowerCase().replace(/[\s_]+/g, '-');
    }

    function statusPillClass(status) {
        const normalized = normalizeStatus(status);
        if (normalized === 'completed') return 'status-completed';
        if (normalized === 'ongoing' || normalized === 'in-progress') return 'status-ongoing';
        if (normalized === 'cancelled' || normalized === 'canceled') return 'status-cancelled';
        return 'status-pending';
    }

    function statusLabel(status) {
        const normalized = normalizeStatus(status);
        return normalized
            .split('-')
            .map(part => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ');
    }

    function detailRow(label, value) {
        return `
            <div class="flex items-start gap-3 py-2 border-b border-gray-100 last:border-0">
                <span class="w-28 flex-shrink-0 text-[11px] font-black uppercase tracking-wider text-gray-400">${escapeHtml(label)}</span>
                <span class="min-w-0 text-sm font-bold text-gray-800 break-words">${escapeHtml(value || '—')}</span>
            </div>`;
    }

    async function openRecordPanel(id) {
        const title = document.getElementById('panelRecordTitle');
        const panelBody = document.getElementById('panelBody');
        const panelFoot = document.getElementById('panelFoot');

        if (!title || !panelBody || !panelFoot) return;

        title.textContent = 'Loading...';
        panelBody.innerHTML = `
            <div class="text-center py-10 text-gray-300">
                <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
            </div>`;
        panelFoot.classList.add('hidden');
        panelFoot.classList.remove('flex');
        panelFoot.innerHTML = '';

        try {
            const res = await fetch(`/admin/dental-records/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!res.ok) throw new Error('Failed to fetch record.');

            const data = await res.json();
            const status = normalizeStatus(data.status);
            const patientName = data.patient_name || 'Record Details';
            const initial = (patientName.charAt(0) || '?').toUpperCase();

            title.textContent = patientName;

            panelBody.innerHTML = `
                <div class="rounded-2xl border border-red-100 bg-red-50/70 p-4 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#8B0000] to-[#6b0000] text-white flex items-center justify-center text-sm font-black flex-shrink-0">
                        ${escapeHtml(initial)}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-black text-gray-900 truncate">${escapeHtml(patientName)}</div>
                        <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Selected record</div>
                    </div>
                    <span class="status-pill ${statusPillClass(status)}">
                        <span class="status-dot"></span>
                        ${escapeHtml(statusLabel(status))}
                    </span>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3">
                    ${detailRow('Procedure', data.procedure || '—')}
                    ${detailRow('Dentist', data.dentist_name || '—')}
                    ${detailRow('Date', data.date || '—')}
                    ${data.notes ? detailRow('Notes', data.notes) : ''}
                </div>`;

            panelFoot.classList.remove('hidden');
            panelFoot.classList.add('flex');
            panelFoot.innerHTML = `
                <a href="/admin/dental-records/${escapeHtml(data.id || id)}" class="ui-btn ui-btn-primary w-full">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>View Full Record</span>
                </a>`;
        } catch (error) {
            panelBody.innerHTML = `
                <div class="text-center py-8">
                    <div class="empty-state-icon !w-[64px] !h-[64px] !rounded-2xl !mb-4">
                        <i class="fa-solid fa-triangle-exclamation !text-[26px]"></i>
                    </div>
                    <h3 class="empty-state-title !text-[15px]">Failed to load details</h3>
                    <p class="empty-state-sub !text-[13px] !mt-2">Please try opening the record again.</p>
                </div>`;
        }
    }

    function todayIso() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function closeRecordStatusDropdown() {
        const field = document.getElementById('recordStatusField');
        const btn = document.getElementById('recordStatusDropdownBtn');

        field?.classList.remove('is-open');
        btn?.setAttribute('aria-expanded', 'false');
    }

    function toggleRecordStatusDropdown() {
        const field = document.getElementById('recordStatusField');
        const btn = document.getElementById('recordStatusDropdownBtn');

        if (!field || !btn) return;

        const isOpen = field.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function setRecordStatusFilter(value) {
        const statusFilter = document.getElementById('recordStatusFilter');
        const statusField = document.getElementById('recordStatusField');
        const selectedLabel = document.getElementById('recordStatusSelectedLabel');
        const selectedIcon = document.getElementById('recordStatusSelectedIcon');

        const selectedOption = document.querySelector(`.record-sort-option[data-filter="${value}"]`);
        const label = selectedOption?.dataset.label || 'All Records';
        const icon = selectedOption?.dataset.icon || 'fa-layer-group';

        if (statusFilter) statusFilter.value = value;
        if (statusField) statusField.dataset.statusFilter = value;
        if (selectedLabel) selectedLabel.textContent = label;

        if (selectedIcon) {
            selectedIcon.innerHTML = `<i class="fa-solid ${icon}"></i>`;
        }

        document.querySelectorAll('.record-sort-option').forEach(option => {
            option.classList.toggle('is-active', option.dataset.filter === value);
        });
    }

    function buildDentalRecordEmptyStateHtml({ icon, title, sub, actionHtml = '' }) {
        return `
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fa-solid ${icon}"></i>
            </div>

            <p class="empty-state-title">${title}</p>
            <p class="empty-state-sub">${sub}</p>

            ${actionHtml}
        </div>
    `;
    }

    function filterDentalRecords() {
        const input = document.getElementById('dentalRecordSearch');
        const statusFilter = document.getElementById('recordStatusFilter');
        const statusField = document.getElementById('recordStatusField');
        const listView = document.getElementById('dentalRecordListView');
        const gridView = document.getElementById('dentalRecordGridView');
        const rows = Array.from(document.querySelectorAll('.dental-record-row'));
        const cards = Array.from(document.querySelectorAll('.dental-record-grid-card'));
        const emptyState = document.getElementById('dentalRecordEmptyState');
        const rowCount = document.getElementById('recordRowCount');
        const sortCount = document.getElementById('recordSortCount');

        const q = (input?.value || '').trim().toLowerCase();
        const selectedStatus = statusFilter?.value || 'all';
        const today = todayIso();
        let visible = 0;

        if (statusField) statusField.dataset.statusFilter = selectedStatus;

        const matchesItem = item => {
            const haystack = [
                item.dataset.patient,
                item.dataset.procedure,
                item.dataset.dentist,
                item.dataset.status
            ].join(' ').toLowerCase();

            const rowStatus = item.dataset.status;
            const matchesSearch = !q || haystack.includes(q);

            const matchesStatus = selectedStatus === 'all' ||
                (selectedStatus === 'today' && item.dataset.date === today) ||
                rowStatus === selectedStatus ||
                (selectedStatus === 'ongoing' && rowStatus === 'in-progress') ||
                (selectedStatus === 'cancelled' && rowStatus === 'canceled');

            return matchesSearch && matchesStatus;
        };

        rows.forEach(row => {
            const isVisible = matchesItem(row);
            row.style.display = isVisible ? '' : 'none';

            if (isVisible) visible++;
        });

        cards.forEach(card => {
            const isVisible = matchesItem(card);
            card.style.display = isVisible ? '' : 'none';
        });

        const hasSearch = q.length > 0;
        const hasFilter = selectedStatus !== 'all';
        const shouldShowEmpty = rows.length === 0 || visible === 0;

        if (emptyState) {
            if (shouldShowEmpty) {
                if (listView) listView.hidden = true;
                if (gridView) gridView.hidden = true;

                let icon = 'fa-notes-medical';
                let title = 'No dental records found';
                let sub = 'New records will appear here once they are added.';
                let actionHtml = '';

                if (hasSearch) {
                    icon = 'fa-magnifying-glass';
                    title = `No results for "${q}"`;
                    sub = 'Try a different patient name, procedure, dentist, or status.';
                    actionHtml = `
                    <button type="button" data-clear-search data-search-target="#dentalRecordSearch" class="empty-state-btn">
                        <i class="fa-solid fa-xmark"></i>
                        Clear search
                    </button>
                `;
                } else if (hasFilter) {
                    icon = 'fa-sliders';
                    title = 'No matches for your filter';
                    sub = 'Try selecting another record status.';
                    actionHtml = `
                    <button type="button" onclick="resetRecordFilters()" class="empty-state-btn">
                        <i class="fa-solid fa-rotate-left"></i>
                        Reset filters
                    </button>
                `;
                }

                emptyState.innerHTML = buildDentalRecordEmptyStateHtml({
                    icon,
                    title,
                    sub,
                    actionHtml
                });

                emptyState.classList.add('show', 'is-visible');
            } else {
                emptyState.classList.remove('show', 'is-visible');
                emptyState.innerHTML = '';

                const activeMode = window.getGlobalViewMode?.('dentalRecordViewToggle') || 'list';
                window.setGlobalViewMode?.('dentalRecordViewToggle', activeMode, { persist: false });
            }
        }

        if (rowCount) rowCount.textContent = `${visible} ${visible === 1 ? 'record' : 'records'}`;
        if (sortCount) sortCount.textContent = visible;
    }

    function resetRecordFilters() {
        const input = document.getElementById('dentalRecordSearch');

        if (input) {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        setRecordStatusFilter('all');
        filterDentalRecords();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('dentalRecordSearch');
        const dropdownBtn = document.getElementById('recordStatusDropdownBtn');

        input?.addEventListener('input', filterDentalRecords);

        dropdownBtn?.addEventListener('click', event => {
            event.stopPropagation();
            toggleRecordStatusDropdown();
        });

        document.querySelectorAll('.record-sort-option').forEach(option => {
            option.addEventListener('click', event => {
                event.stopPropagation();

                const value = option.dataset.filter || 'all';
                setRecordStatusFilter(value);
                filterDentalRecords();
                closeRecordStatusDropdown();
            });
        });

        document.addEventListener('click', event => {
            const field = document.getElementById('recordStatusField');

            if (field && !field.contains(event.target)) {
                closeRecordStatusDropdown();
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeRecordStatusDropdown();
        });

        window.initSearchClearButtons?.();
        window.initGlobalViewToggles?.();

        setRecordStatusFilter('all');
        filterDentalRecords();
    });
</script>
@endsection