@extends('layouts.admin')

@section('title', 'Data Backup | PUP Taguig Dental Clinic')

@section('content')
@php
$totalAllocatedBytes = $totalAllocatedBytes ?? (50 * 1024 * 1024 * 1024);
$storageUsedBytes = $storageUsedBytes ?? 0;
$fullBackupsBytes = $fullBackupsBytes ?? 0;
$incrementalBackupsBytes = $incrementalBackupsBytes ?? 0;
$totalBackups = $totalBackups ?? 0;
$storagePercent = $totalAllocatedBytes > 0 ? round(($storageUsedBytes / $totalAllocatedBytes) * 100, 1) : 0;
$storageFreeBytes = max($totalAllocatedBytes - $storageUsedBytes, 0);

$formatBytes = function ($bytes) {
$bytes = (int) $bytes;
if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
return $bytes . ' B';
};

$autoBackupEnabled = isset($autoBackupEnabled) ? (bool) $autoBackupEnabled : true;
@endphp

<div id="backupModal">
    <div class="backup-modal-inner">
        <div class="admin-modal-icon">
            <i id="modalIcon" class="fa-solid fa-database"></i>
        </div>

        <div id="modalTitle" class="admin-modal-message-title">
            Creating Backup...
        </div>

        <div id="modalSubtitle" class="admin-modal-message-subtitle">
            Please wait while the system archives your data.
        </div>

        <div class="admin-progress-track">
            <div id="modalBar" class="admin-progress-bar">
            </div>
        </div>

        <div id="modalPct" class="admin-progress-label">0%</div>

        <button class="terms-cancel-btn" id="modalClose" onclick="closeModal()" disabled>
            Close
        </button>
    </div>
</div>

<div id="scheduleModal" class="admin-modal-backdrop backup-schedule-modal">
    <div class="admin-modal-card backup-schedule-modal-card">
        <div class="admin-modal-head">
            <div>
                <div class="admin-modal-title">
                    Edit Backup Schedule
                </div>
                <div class="admin-modal-subtitle">
                    Update recurring backup schedule settings
                </div>
            </div>

            <button type="button" onclick="closeScheduleModal()" class="um-modal-x backup-modal-x"
                aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="scheduleForm">
            <div class="admin-modal-grid backup-schedule-grid">
                <div class="backup-schedule-field">
                    <label class="backup-check-card" for="daily_enabled">
                        <span class="backup-check-copy">
                            <span class="backup-check-title">Daily Incremental</span>
                            <span class="backup-check-sub">Small database changes every day</span>
                        </span>

                        <span class="backup-check-toggle">
                            <input type="checkbox" id="daily_enabled" {{ $backupSchedule['daily_enabled'] ? 'checked'
                                : '' }}>
                            <span class="backup-check-slider"></span>
                        </span>
                    </label>

                    <div class="backup-time-wrap">
                        <input type="text" id="daily_time" value="{{ $backupSchedule['daily_time'] }}"
                            class="admin-modal-input js-flatpickr-time backup-time-input" placeholder="Select time">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                </div>

                <div class="backup-schedule-field">
                    <label class="backup-check-card" for="weekly_enabled">
                        <span class="backup-check-copy">
                            <span class="backup-check-title">Weekly Full Backup</span>
                            <span class="backup-check-sub">Complete copy every Sunday</span>
                        </span>

                        <span class="backup-check-toggle">
                            <input type="checkbox" id="weekly_enabled" {{ $backupSchedule['weekly_enabled'] ? 'checked'
                                : '' }}>
                            <span class="backup-check-slider"></span>
                        </span>
                    </label>

                    <div class="backup-time-wrap">
                        <input type="text" id="weekly_time" value="{{ $backupSchedule['weekly_time'] }}"
                            class="admin-modal-input js-flatpickr-time backup-time-input" placeholder="Select time">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                </div>

                <div class="backup-schedule-field">
                    <label class="backup-check-card" for="monthly_enabled">
                        <span class="backup-check-copy">
                            <span class="backup-check-title">Monthly Archive</span>
                            <span class="backup-check-sub">Long-term monthly archive copy</span>
                        </span>

                        <span class="backup-check-toggle">
                            <input type="checkbox" id="monthly_enabled" {{ $backupSchedule['monthly_enabled']
                                ? 'checked' : '' }}>
                            <span class="backup-check-slider"></span>
                        </span>
                    </label>

                    <div class="backup-time-wrap">
                        <input type="text" id="monthly_time" value="{{ $backupSchedule['monthly_time'] }}"
                            class="admin-modal-input js-flatpickr-time backup-time-input" placeholder="Select time">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="backup-modal-actions">
                <button type="button" onclick="closeScheduleModal()" class="modal-btn-ghost backup-modal-cancel">
                    Cancel
                </button>

                <button type="submit" class="backup-run-btn backup-save-btn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<main id="mainContent" class="admin-page-shell backup-page page-enter mode-list">
    <div class="admin-page-container">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Data Backup</h1>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <button id="backupNowBtn" type="button" onclick="startBackup()" class="backup-banner-action">
                        <i class="fa-solid fa-database"></i>
                        <span>Backup Now</span>
                    </button>
                </div>
            </div>
        </div>
        <div id="filterOverlay" class="filter-overlay-ui backup-filter-overlay"
            onclick="closeFilterDrawer('filterPanel', 'filterOverlay')">
        </div>

        <aside id="filterPanel" class="filter-drawer-ui backup-filter-drawer" aria-label="Backup filters">
            <div class="filter-drawer-header px-6 py-5 border-b">
                <div class="flex items-center justify-between gap-4">
                    <div class="filter-drawer-title flex items-center gap-3">
                        <i class="fa-solid fa-filter"></i>
                        <div>
                            <h2 class="text-xl font-black leading-none">Filters</h2>
                            <p class="text-xs font-bold text-gray-500 mt-1">Refine backup history results</p>
                        </div>
                    </div>

                    <button type="button" class="admin-icon-button"
                        onclick="closeFilterDrawer('filterPanel', 'filterOverlay')" aria-label="Close filters">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="filter-drawer-body px-6 py-5 space-y-6">
                <div id="activeFiltersSection">
                    <div class="flex items-center justify-between mb-3">
                        <span class="filter-section-title !mb-0">Active Filters</span>

                        <button type="button" id="clearAllChipsBtn" class="text-xs font-black"
                            onclick="resetAjaxFilters()">
                            Clear all
                        </button>
                    </div>

                    <div id="activeChipsContainer"></div>
                </div>

                <div class="filter-soft-divider"></div>

                <section>
                    <div class="filter-section-title">Backup Type</div>

                    <div id="backupTypeGroup" class="filter-chip-row">
                        <button type="button" class="ftag" data-val="">All Types</button>
                        <button type="button" class="ftag" data-val="full">Full</button>
                        <button type="button" class="ftag" data-val="incremental">Incremental</button>
                    </div>
                </section>

                <section>
                    <div class="filter-section-title">Backup Status</div>

                    <div id="backupStatusGroup" class="filter-chip-row">
                        <button type="button" class="ftag" data-val="">All Status</button>
                        <button type="button" class="ftag" data-val="completed">Completed</button>
                        <button type="button" class="ftag" data-val="failed">Failed</button>
                        <button type="button" class="ftag" data-val="in_progress">In Progress</button>
                    </div>
                </section>
            </div>

            <div class="filter-drawer-footer px-6 py-4 border-t">
                <div class="flex items-center justify-between gap-3">
                    <button type="button" id="filterResetBtn" class="filter-clear-btn" onclick="resetAjaxFilters()">
                        Reset Filters
                    </button>

                    <div class="flex items-center gap-3">
                        <button type="button" class="filter-cancel-btn px-5 py-3 rounded-xl font-black text-sm"
                            onclick="closeFilterDrawer('filterPanel', 'filterOverlay')">
                            Cancel
                        </button>

                        <button type="button" id="backupApplyFiltersBtn"
                            class="filter-apply-btn filter-show-results-btn px-5 py-3 rounded-xl font-black text-sm">
                            <span id="backupShowResultsText">Show results</span>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <div class="backup-stats admin-dashboard-stat-grid" id="backupStats">
            <div class="backup-stat stat-card s-all">
                <span class="stat-icon-wrapper">
                    <i class="fa-solid fa-database"></i>
                </span>

                <span class="stat-card-info">
                    <span class="backup-stat-value stat-num" id="totalBackupsStat">{{ $totalBackups }}</span>
                    <span class="backup-stat-label stat-label">Total Backups</span>
                </span>
            </div>

            <div class="backup-stat stat-card s-month">
                <span class="stat-icon-wrapper">
                    <i class="fa-solid fa-calendar-days"></i>
                </span>

                <span class="stat-card-info">
                    <span class="backup-stat-value stat-num" id="thisMonthBackupsStat">{{ $thisMonthBackups ?? 0
                        }}</span>
                    <span class="backup-stat-label stat-label">This Month</span>
                </span>
            </div>

            <div class="backup-stat stat-card s-last">
                <span class="stat-icon-wrapper">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </span>

                <span class="stat-card-info">
                    <span class="backup-stat-value stat-num" id="lastBackupStat">
                        {{ isset($lastBackup) && $lastBackup ? $lastBackup->created_at->format('M d') : '—' }}
                    </span>
                    <span class="backup-stat-label stat-label">Last Backup</span>
                </span>
            </div>

            <div class="backup-stat stat-card s-auto">
                <span class="stat-icon-wrapper">
                    <i class="fa-solid fa-rotate"></i>
                </span>

                <span class="stat-card-info">
                    <span class="backup-stat-value stat-num" id="autoScheduleStatValue">
                        {{ $autoBackupEnabled ? 'Active' : 'Paused' }}
                    </span>
                    <span class="backup-stat-label stat-label">Auto-Schedule</span>
                </span>
            </div>
        </div>

        <div class="backup-main">
            <div class="card backup-history-card" id="backupHistoryCard">
                <div class="loading-overlay" id="tableLoading">
                    <div class="loading-spinner"></div>
                </div>

                <div class="card-header">
                    <div class="card-header-left">
                        <div class="card-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div>
                            <div class="card-title">Backup History</div>
                            <div class="card-subtitle">All archived snapshots</div>
                        </div>
                    </div>

                    <div class="backup-history-actions">
                        <button type="button" id="filterBtn" class="global-filter-btn"
                            onclick="openFilterDrawer('filterPanel', 'filterOverlay')" aria-pressed="false">
                            <i class="fa-solid fa-filter"></i>
                            <span>Filters</span>
                            <span id="filterBadge" class="filter-badge"></span>
                        </button>

                        <button type="button" id="externalClearFilterBtn" class="global-filter-reset-btn hidden"
                            onclick="resetAjaxFilters()" aria-label="Reset filters">
                            <i class="fa-solid fa-arrow-rotate-left"></i>
                        </button>

                        <div class="view-toggle-container backup-view-toggle" id="backupHistoryViewToggle"
                            data-global-view-toggle data-view-root="#mainContent"
                            data-list-view="#backupHistoryListView" data-grid-view="#backupHistoryGridView"
                            data-storage-key="backupHistoryView">
                            <span class="view-slider"></span>

                            <button type="button" class="btn-view-mode active" id="backupListViewBtn"
                                data-view-mode="list" title="List view" aria-label="List view">
                                <i class="fa-solid fa-table-list"></i>
                            </button>

                            <button type="button" class="btn-view-mode" id="backupGridViewBtn" data-view-mode="grid"
                                title="Grid view" aria-label="Grid view">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="backupHistoryListView" class="backup-history-view">
                    <div class="backup-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Backup ID</th>
                                    <th>Date & Time</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="backupTableBody">
                                @forelse($backups as $backup)
                                <tr id="row-{{ $backup->id }}">
                                    <td>
                                        <div class="backup-id">{{ $backup->backup_id }}</div>
                                    </td>
                                    <td>
                                        {{ $backup->created_at ? $backup->created_at->format('M d, Y h:i A') : '—' }}
                                    </td>
                                    <td>
                                        <span class="type-pill {{ $backup->type === 'full' ? 'full' : 'incremental' }}">
                                            {{ ucfirst($backup->type ?? 'full') }}
                                        </span>
                                    </td>
                                    <td class="backup-strong">
                                        {{ isset($backup->size_formatted) ? $backup->size_formatted :
                                        $formatBytes($backup->size_bytes ?? 0) }}
                                    </td>
                                    <td>
                                        <span class="status-pill {{ $backup->status ?? 'completed' }}">
                                            {{ ucfirst(str_replace('_', ' ', $backup->status ?? 'completed')) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="action-btn dl" title="Download"
                                                href="{{ route('admin.data_backup.download', $backup->id) }}">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <button type="button" class="action-btn restore" title="Restore"
                                                onclick="restoreBackup({{ $backup->id }}, '{{ $backup->backup_id }}')">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                            <button type="button" class="action-btn del" title="Delete"
                                                onclick="deleteBackup({{ $backup->id }}, '{{ $backup->backup_id }}')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state backup-empty-state">
                                            <div class="empty-state-icon">
                                                <i class="fa-solid fa-database"></i>
                                            </div>
                                            <h3 class="empty-state-title">No backups found</h3>
                                            <p class="empty-state-sub">Create your first backup to start protecting
                                                system data.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="backupHistoryGridView" class="backup-history-view" hidden>
                    <div class="backup-grid" id="backupGridBody">
                        @forelse($backups as $backup)
                        <div class="backup-grid-card" id="grid-row-{{ $backup->id }}">
                            <div class="backup-grid-top">
                                <div class="backup-grid-id">{{ $backup->backup_id }}</div>
                                <span class="status-pill {{ $backup->status ?? 'completed' }}">
                                    {{ ucfirst(str_replace('_', ' ', $backup->status ?? 'completed')) }}
                                </span>
                            </div>

                            <div class="backup-grid-meta">
                                <div>
                                    <div class="backup-grid-label">Date & Time</div>
                                    <div class="backup-grid-value">
                                        {{ $backup->created_at ? $backup->created_at->format('M d, Y h:i A') : '—' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="backup-grid-label">Type</div>
                                    <div class="backup-grid-value">
                                        <span class="type-pill {{ $backup->type === 'full' ? 'full' : 'incremental' }}">
                                            {{ ucfirst($backup->type ?? 'full') }}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <div class="backup-grid-label">Size</div>
                                    <div class="backup-grid-value backup-strong">
                                        {{ isset($backup->size_formatted) ? $backup->size_formatted :
                                        $formatBytes($backup->size_bytes ?? 0) }}
                                    </div>
                                </div>
                            </div>

                            <div class="backup-grid-footer">
                                <div class="table-actions">
                                    <a class="action-btn dl" title="Download"
                                        href="{{ route('admin.data_backup.download', $backup->id) }}">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                    <button type="button" class="action-btn restore" title="Restore"
                                        onclick="restoreBackup({{ $backup->id }}, '{{ $backup->backup_id }}')">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <button type="button" class="action-btn del" title="Delete"
                                        onclick="deleteBackup({{ $backup->id }}, '{{ $backup->backup_id }}')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="empty-state backup-empty-state backup-grid-empty">
                            <div class="empty-state-icon">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <h3 class="empty-state-title">No backups found</h3>
                            <p class="empty-state-sub">Create your first backup to start protecting system data.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <div class="table-footer" id="backupTableFooter">
                    <span id="backupTableSummary">
                        Showing {{ $backups->firstItem() ?? 0 }}–{{ $backups->lastItem() ?? 0 }} of {{ $backups->total()
                        }} backups
                    </span>
                    <div id="backupPagination">
                        {{ $backups->links() }}
                    </div>
                </div>

            </div>

            <div class="side-stack">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-icon"><i class="fa-solid fa-database"></i></div>
                            <div>
                                <div class="card-title">Storage Usage</div>
                                <div class="card-subtitle" id="totalAllocatedStat">{{ $formatBytes($totalAllocatedBytes)
                                    }} total allocated</div>
                            </div>
                        </div>
                    </div>

                    <div class="mini-card-body">
                        <div class="usage-row">
                            <span id="storageUsedStat">{{ $formatBytes($storageUsedBytes) }} used</span>
                            <span class="percent" id="storagePercentStat">{{ $storagePercent }}%</span>
                        </div>

                        <div class="usage-bar">
                            <div class="usage-fill" id="storageUsageBar"
                                style="width: {{ min($storagePercent, 100) }}%;"></div>
                        </div>

                        <div class="usage-grid">
                            <div class="usage-box full">
                                <div class="usage-box-label">Full Backups</div>
                                <div class="usage-box-value" id="fullBackupsBytesStat">{{
                                    $formatBytes($fullBackupsBytes) }}</div>
                            </div>

                            <div class="usage-box incremental">
                                <div class="usage-box-label">Incremental</div>
                                <div class="usage-box-value" id="incrementalBackupsBytesStat">{{
                                    $formatBytes($incrementalBackupsBytes) }}</div>
                            </div>
                        </div>

                        <div id="freeSpaceStat" class="backup-storage-note">
                            Free Space: {{ $formatBytes($storageFreeBytes) }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-icon"><i class="fa-solid fa-clock"></i></div>
                            <div>
                                <div class="card-title">Auto-Backup Schedule</div>
                                <div class="card-subtitle">Configure recurring backups</div>
                            </div>
                        </div>
                    </div>

                    <div class="mini-card-body" id="scheduleCard">
                        <div class="schedule-item">
                            <div>
                                <div class="schedule-title">Daily Incremental</div>
                                <div class="schedule-time" data-time-label="daily">
                                    Every day at {{ \Carbon\Carbon::createFromFormat('H:i',
                                    $backupSchedule['daily_time'])->format('g:i A') }}
                                </div>
                            </div>
                            <span class="schedule-pill {{ $backupSchedule['daily_enabled'] ? 'active' : 'paused' }}">
                                {{ $backupSchedule['daily_enabled'] ? 'Active' : 'Paused' }}
                            </span>
                            <div class="schedule-toggle" onclick="toggleSchedule('daily')"
                                style="background:{{ $backupSchedule['daily_enabled'] ? '#8B0000' : '#d1d5db' }};">
                                <div class="schedule-thumb"
                                    style="left:{{ $backupSchedule['daily_enabled'] ? '16px' : '2px' }};"></div>
                            </div>
                        </div>

                        <div class="schedule-item">
                            <div>
                                <div class="schedule-title">Weekly Full Backup</div>
                                <div class="schedule-time" data-time-label="weekly">
                                    Every Sunday at {{ \Carbon\Carbon::createFromFormat('H:i',
                                    $backupSchedule['weekly_time'])->format('g:i A') }}
                                </div>
                            </div>
                            <span class="schedule-pill {{ $backupSchedule['weekly_enabled'] ? 'active' : 'paused' }}">
                                {{ $backupSchedule['weekly_enabled'] ? 'Active' : 'Paused' }}
                            </span>
                            <div class="schedule-toggle" onclick="toggleSchedule('weekly')"
                                style="background:{{ $backupSchedule['weekly_enabled'] ? '#8B0000' : '#d1d5db' }};">
                                <div class="schedule-thumb"
                                    style="left:{{ $backupSchedule['weekly_enabled'] ? '16px' : '2px' }};"></div>
                            </div>
                        </div>

                        <div class="schedule-item">
                            <div>
                                <div class="schedule-title">Monthly Archive</div>
                                <div class="schedule-time" data-time-label="monthly">
                                    1st of every month at {{ \Carbon\Carbon::createFromFormat('H:i',
                                    $backupSchedule['monthly_time'])->format('g:i A') }}
                                </div>
                            </div>
                            <span class="schedule-pill {{ $backupSchedule['monthly_enabled'] ? 'active' : 'paused' }}">
                                {{ $backupSchedule['monthly_enabled'] ? 'Active' : 'Paused' }}
                            </span>
                            <div class="schedule-toggle" onclick="toggleSchedule('monthly')"
                                style="background:{{ $backupSchedule['monthly_enabled'] ? '#8B0000' : '#d1d5db' }};">
                                <div class="schedule-thumb"
                                    style="left:{{ $backupSchedule['monthly_enabled'] ? '16px' : '2px' }};"></div>
                            </div>
                        </div>

                        <button class="schedule-edit-btn" type="button" onclick="openScheduleModal()">
                            <i class="fa-solid fa-pen-to-square backup-edit-icon"></i> Edit Schedule
                            Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let scheduleOn = @json($autoBackupEnabled);
    let backupSchedule = @json($backupSchedule);

    const restoreUrlTemplate = @json(route('admin.data_backup.restore', ['id' => '__ID__']));
    const deleteUrlTemplate = @json(route('admin.data_backup.delete', ['id' => '__ID__']));
    const dataBackupUrl = @json(route('admin.data_backup'));

    let currentFilters = {
        type: @json(request('type', '')),
        status: @json(request('status', '')),
        scope: @json(request('scope', '')),
        stat: @json(request('stat', ''))
    };

    function formatTimeTo12Hour(time24) {
        if (!time24) return '';
        const parts = time24.split(':');
        const hours = parseInt(parts[0], 10);
        const minutes = parts[1] || '00';
        const suffix = hours >= 12 ? 'PM' : 'AM';
        const hour12 = hours % 12 || 12;
        return `${hour12}:${minutes} ${suffix}`;
    }

    function refreshAutoScheduleStat() {
        const statValue = document.getElementById('autoScheduleStatValue');
        if (!statValue) return;

        const hasEnabled =
            !!backupSchedule.daily_enabled ||
            !!backupSchedule.weekly_enabled ||
            !!backupSchedule.monthly_enabled;

        scheduleOn = hasEnabled;
        statValue.textContent = hasEnabled ? 'Active' : 'Paused';
    }

    function updateScheduleUI() {
        const items = document.querySelectorAll('#scheduleCard .schedule-item');
        const types = ['daily', 'weekly', 'monthly'];

        items.forEach((item, index) => {
            const type = types[index];
            const enabled = !!backupSchedule[type + '_enabled'];

            const pill = item.querySelector('.schedule-pill');
            pill.textContent = enabled ? 'Active' : 'Paused';
            pill.className = 'schedule-pill ' + (enabled ? 'active' : 'paused');

            const toggle = item.querySelector('.schedule-toggle');
            const thumb = item.querySelector('.schedule-thumb');

            toggle.style.background = enabled ? '#8B0000' : '#d1d5db';
            thumb.style.left = enabled ? '16px' : '2px';
        });

        const dailyTimeLabel = document.querySelector('[data-time-label="daily"]');
        const weeklyTimeLabel = document.querySelector('[data-time-label="weekly"]');
        const monthlyTimeLabel = document.querySelector('[data-time-label="monthly"]');

        if (dailyTimeLabel) {
            dailyTimeLabel.textContent = `Every day at ${formatTimeTo12Hour(backupSchedule.daily_time)}`;
        }

        if (weeklyTimeLabel) {
            weeklyTimeLabel.textContent = `Every Sunday at ${formatTimeTo12Hour(backupSchedule.weekly_time)}`;
        }

        if (monthlyTimeLabel) {
            monthlyTimeLabel.textContent = `1st of every month at ${formatTimeTo12Hour(backupSchedule.monthly_time)}`;
        }

        refreshAutoScheduleStat();
    }

    function setLoading(show) {
        const loading = document.getElementById('tableLoading');
        if (!loading) return;
        loading.classList.toggle('show', show);
    }

    function updateUrlFromFilters() {
        const url = new URL(window.location.href);

        ['type', 'status', 'scope', 'stat'].forEach(key => {
            if (currentFilters[key]) {
                url.searchParams.set(key, currentFilters[key]);
            } else {
                url.searchParams.delete(key);
            }
        });

        window.history.replaceState({}, '', url);
    }

    function getActiveBackupFilterCount() {
        return ['type', 'status', 'scope', 'stat'].filter(key => {
            return String(currentFilters[key] || '').trim() !== '';
        }).length;
    }

    function updateBackupActiveChips() {
        const section = document.getElementById('activeFiltersSection');
        const container = document.getElementById('activeChipsContainer');

        if (!section || !container) return;

        const chips = [];

        const labels = {
            type: {
                full: 'Type: Full',
                incremental: 'Type: Incremental',
            },
            status: {
                completed: 'Status: Completed',
                failed: 'Status: Failed',
                in_progress: 'Status: In Progress',
            },
            scope: {
                month: 'This Month',
            },
            stat: {
                last: 'Last Backup',
                auto: 'Auto-Schedule',
            },
        };

        Object.keys(labels).forEach(key => {
            const value = currentFilters[key];

            if (!value || !labels[key][value]) return;

            chips.push(`
            <span class="filter-chip">
                <span>${labels[key][value]}</span>
                <button type="button" class="filter-chip-remove" onclick="clearBackupFilter('${key}')" aria-label="Remove ${labels[key][value]}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </span>
        `);
        });

        container.innerHTML = chips.join('');
        section.style.display = chips.length ? '' : 'none';
    }

    function getActiveBackupFilterCount() {
        return ['type', 'status', 'scope', 'stat'].filter(key => {
            return String(currentFilters[key] || '').trim() !== '';
        }).length;
    }

    function updateBackupActiveChips() {
        const section = document.getElementById('activeFiltersSection');
        const container = document.getElementById('activeChipsContainer');

        if (!section || !container) return;

        const labels = {
            type: {
                full: 'Type: Full',
                incremental: 'Type: Incremental',
            },
            status: {
                completed: 'Status: Completed',
                failed: 'Status: Failed',
                in_progress: 'Status: In Progress',
            },
            scope: {
                month: 'This Month',
            },
            stat: {
                last: 'Last Backup',
                auto: 'Auto-Schedule',
            },
        };

        const chips = [];

        Object.keys(labels).forEach(key => {
            const value = currentFilters[key];

            if (!value || !labels[key][value]) return;

            chips.push(`
            <span class="filter-chip">
                <span>${labels[key][value]}</span>
                <button type="button" class="filter-chip-remove"
                    onclick="clearBackupFilter('${key}')"
                    aria-label="Remove ${labels[key][value]}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </span>
        `);
        });

        container.innerHTML = chips.join('');
        section.style.display = chips.length ? '' : 'none';
    }

    function updateResetButtonVisibility() {
        const count = getActiveBackupFilterCount();

        window.setGlobalFilterButtonState?.({
            buttonId: 'filterBtn',
            badgeId: 'filterBadge',
            resetId: 'externalClearFilterBtn',
            count,
        });

        updateBackupActiveChips();
    }

    function syncFilterInputs() {
        window.syncFilterTagGroup?.('backupTypeGroup', currentFilters.type || '');
        window.syncFilterTagGroup?.('backupStatusGroup', currentFilters.status || '');

        updateResetButtonVisibility();
    }

    function clearBackupFilter(key) {
        if (!Object.prototype.hasOwnProperty.call(currentFilters, key)) return;

        currentFilters[key] = '';

        syncFilterInputs();
        fetchBackupTable();
    }

    function getBackupFilterGroupValue(groupId) {
        const active = document.querySelector(`#${groupId} .ftag.ftag-active`);
        return active?.getAttribute('data-val') || '';
    }

    function setStatActiveByFilters() {
        const statAll = document.getElementById('stat-all');
        const statMonth = document.getElementById('stat-month');

        if (!statAll || !statMonth) return;

        if (currentFilters.scope === 'month') {
            setActiveStat(statMonth);
        } else if (!currentFilters.type && !currentFilters.status && !currentFilters.scope && currentFilters.stat !== 'auto' && currentFilters.stat !== 'last') {
            setActiveStat(statAll);
        }
    }

    function renderTableRows(rows) {
        const tbody = document.getElementById('backupTableBody');
        const gridBody = document.getElementById('backupGridBody');

        if (!tbody && !gridBody) return;

        if (!rows || rows.length === 0) {
            const emptyInner = `
        <div class="empty-state backup-empty-state">
            <div class="empty-state-icon">
                <i class="fa-solid fa-database"></i>
            </div>
            <h3 class="empty-state-title">No backups found</h3>
            <p class="empty-state-sub">Create your first backup to start protecting system data.</p>
        </div>
    `;

            if (tbody) {
                tbody.innerHTML = `
            <tr>
                <td colspan="6" class="p-0">
                    ${emptyInner}
                </td>
            </tr>
        `;
            }

            if (gridBody) {
                gridBody.innerHTML = `
            <div class="backup-grid-empty">
                ${emptyInner}
            </div>
        `;
            }

            return;
        }

        if (tbody) {
            tbody.innerHTML = rows.map(backup => `
                <tr id="row-${backup.id}">
                    <td>
                        <div class="backup-id">${backup.backup_id}</div>
                    </td>
                    <td>${backup.created_at_formatted || '—'}</td>
                    <td>
                        <span class="type-pill ${backup.type === 'full' ? 'full' : 'incremental'}">
                            ${(backup.type || 'full').charAt(0).toUpperCase() + (backup.type || 'full').slice(1)}
                        </span>
                    </td>
                    <td class="backup-strong">
                        ${backup.size_formatted || '0 B'}
                    </td>
                    <td>
                        <span class="status-pill ${backup.status || 'completed'}">
                            ${String(backup.status || 'completed').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a class="action-btn dl" title="Download" href="${backup.download_url}">
                                <i class="fa-solid fa-download"></i>
                            </a>
                            <button type="button" class="action-btn restore" title="Restore" onclick="restoreBackup(${backup.id}, '${backup.backup_id}')">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                            <button type="button" class="action-btn del" title="Delete" onclick="deleteBackup(${backup.id}, '${backup.backup_id}')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        if (gridBody) {
            gridBody.innerHTML = rows.map(backup => `
                <div class="backup-grid-card" id="grid-row-${backup.id}">
                    <div class="backup-grid-top">
                        <div class="backup-grid-id">${backup.backup_id}</div>
                        <span class="status-pill ${backup.status || 'completed'}">
                            ${String(backup.status || 'completed').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}
                        </span>
                    </div>

                    <div class="backup-grid-meta">
                        <div>
                            <div class="backup-grid-label">Date & Time</div>
                            <div class="backup-grid-value">${backup.created_at_formatted || '—'}</div>
                        </div>

                        <div>
                            <div class="backup-grid-label">Type</div>
                            <div class="backup-grid-value">
                                <span class="type-pill ${backup.type === 'full' ? 'full' : 'incremental'}">
                                    ${(backup.type || 'full').charAt(0).toUpperCase() + (backup.type || 'full').slice(1)}
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="backup-grid-label">Size</div>
                            <div class="backup-grid-value backup-strong">
                                ${backup.size_formatted || '0 B'}
                            </div>
                        </div>
                    </div>

                    <div class="backup-grid-footer">
                        <div class="table-actions">
                            <a class="action-btn dl" title="Download" href="${backup.download_url}">
                                <i class="fa-solid fa-download"></i>
                            </a>
                            <button type="button" class="action-btn restore" title="Restore" onclick="restoreBackup(${backup.id}, '${backup.backup_id}')">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                            <button type="button" class="action-btn del" title="Delete" onclick="deleteBackup(${backup.id}, '${backup.backup_id}')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    }

    function renderTableFooter(meta) {
        const summary = document.getElementById('backupTableSummary');
        const pagination = document.getElementById('backupPagination');

        if (summary) {
            summary.textContent = `Showing ${meta.from ?? 0}–${meta.to ?? 0} of ${meta.total ?? 0} backups`;
        }

        window.updateShowResultsText?.(Number(meta.total ?? 0), 'backupShowResultsText');

        if (pagination) {
            pagination.innerHTML = '';
        }
    }

    function updateBackupStats(stats = {}) {
        const totalBackupsStat = document.getElementById('totalBackupsStat');
        const thisMonthBackupsStat = document.getElementById('thisMonthBackupsStat');
        const lastBackupStat = document.getElementById('lastBackupStat');

        const totalAllocatedStat = document.getElementById('totalAllocatedStat');
        const storageUsedStat = document.getElementById('storageUsedStat');
        const storagePercentStat = document.getElementById('storagePercentStat');
        const storageUsageBar = document.getElementById('storageUsageBar');
        const fullBackupsBytesStat = document.getElementById('fullBackupsBytesStat');
        const incrementalBackupsBytesStat = document.getElementById('incrementalBackupsBytesStat');
        const freeSpaceStat = document.getElementById('freeSpaceStat');

        if (totalBackupsStat && stats.total_backups !== undefined) {
            totalBackupsStat.textContent = stats.total_backups;
        }

        if (thisMonthBackupsStat && stats.this_month_backups !== undefined) {
            thisMonthBackupsStat.textContent = stats.this_month_backups;
        }

        if (lastBackupStat && stats.last_backup_label !== undefined) {
            lastBackupStat.textContent = stats.last_backup_label || '—';
        }

        if (totalAllocatedStat && stats.total_allocated_formatted !== undefined) {
            totalAllocatedStat.textContent = `${stats.total_allocated_formatted} total allocated`;
        }

        if (storageUsedStat && stats.storage_used_formatted !== undefined) {
            storageUsedStat.textContent = `${stats.storage_used_formatted} used`;
        }

        if (storagePercentStat && stats.storage_percent !== undefined) {
            storagePercentStat.textContent = `${stats.storage_percent}%`;
        }

        if (storageUsageBar && stats.storage_percent !== undefined) {
            storageUsageBar.style.width = `${Math.min(stats.storage_percent, 100)}%`;
        }

        if (fullBackupsBytesStat && stats.full_backups_formatted !== undefined) {
            fullBackupsBytesStat.textContent = stats.full_backups_formatted;
        }

        if (incrementalBackupsBytesStat && stats.incremental_backups_formatted !== undefined) {
            incrementalBackupsBytesStat.textContent = stats.incremental_backups_formatted;
        }

        if (freeSpaceStat && stats.free_space_formatted !== undefined) {
            freeSpaceStat.textContent = `Free Space: ${stats.free_space_formatted}`;
        }
    }

    async function fetchBackupTable() {
        try {
            setLoading(true);

            const url = new URL(dataBackupUrl, window.location.origin);
            if (currentFilters.type) url.searchParams.set('type', currentFilters.type);
            if (currentFilters.status) url.searchParams.set('status', currentFilters.status);
            if (currentFilters.scope) url.searchParams.set('scope', currentFilters.scope);
            if (currentFilters.stat) url.searchParams.set('stat', currentFilters.stat);

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to load backups.');
            }

            renderTableRows(result.rows || []);
            renderTableFooter(result.meta || {});
            updateBackupStats(result.stats || {});
            updateUrlFromFilters();
            updateResetButtonVisibility();
            syncFilterInputs();
            setStatActiveByFilters();
            moveStatsIndicator();
        } catch (error) {
            showToast('Load Failed', error.message, 'error');
        } finally {
            setLoading(false);
        }
    }

    function applyFilters(newValues = {}, clickedStatEl = null) {
        currentFilters = {
            ...currentFilters,
            ...newValues
        };

        if (clickedStatEl) {
            setActiveStat(clickedStatEl);
        }

        fetchBackupTable();
    }

    function resetAjaxFilters() {
        currentFilters = {
            type: '',
            status: '',
            scope: '',
            stat: ''
        };

        syncFilterInputs();
        fetchBackupTable();
    }

    function clearBackupFilter(key) {
        if (!Object.prototype.hasOwnProperty.call(currentFilters, key)) return;

        currentFilters[key] = '';

        syncFilterInputs();
        fetchBackupTable();
    }

    function getBackupFilterGroupValue(groupId) {
        const active = document.querySelector(`#${groupId} .ftag.ftag-active`);
        return active?.getAttribute('data-val') || '';
    }

    async function startBackup() {
        const modal = document.getElementById('backupModal');
        const bar = document.getElementById('modalBar');
        const pct = document.getElementById('modalPct');
        const title = document.getElementById('modalTitle');
        const sub = document.getElementById('modalSubtitle');
        const btn = document.getElementById('modalClose');
        const icon = document.getElementById('modalIcon');
        const backupBtn = document.getElementById('backupNowBtn');

        if (backupBtn) {
            backupBtn.disabled = true;
            backupBtn.style.opacity = '.7';
            backupBtn.style.pointerEvents = 'none';
        }

        bar.style.width = '0%';
        pct.textContent = '0%';
        title.textContent = 'Creating Backup...';
        sub.textContent = 'Please wait while the system archives your data.';
        icon.className = 'fa-solid fa-database spin';
        btn.disabled = true;
        modal.classList.add('open');

        let p = 0;
        const fakeProgress = setInterval(() => {
            if (p < 90) {
                p += 5;
                bar.style.width = p + '%';
                pct.textContent = p + '%';
            }
        }, 150);

        try {
            const response = await fetch("{{ route('admin.data_backup.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ type: 'full' })
            });

            const result = await response.json();
            clearInterval(fakeProgress);

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Backup failed.');
            }

            bar.style.width = '100%';
            pct.textContent = '100%';
            title.textContent = 'Backup Complete!';
            sub.textContent = result.message || 'Your data has been successfully backed up.';
            icon.className = 'fa-solid fa-circle-check';
            btn.disabled = false;

            showToast('Backup Complete', result.message || 'New backup saved successfully.', 'success');
            await fetchBackupTable();
        } catch (error) {
            clearInterval(fakeProgress);
            title.textContent = 'Backup Failed';
            sub.textContent = error.message;
            icon.className = 'fa-solid fa-circle-exclamation';
            btn.disabled = false;
            showToast('Backup Failed', error.message, 'error');
        } finally {
            if (backupBtn) {
                backupBtn.disabled = false;
                backupBtn.style.opacity = '';
                backupBtn.style.pointerEvents = '';
            }
        }
    }

    function closeModal() {
        document.getElementById('backupModal').classList.remove('open');
    }

    function setBackupTimePickerValue(inputId, value) {
        const input = document.getElementById(inputId);
        if (!input) return;

        if (input._flatpickr) {
            input._flatpickr.setDate(value, false, 'H:i');
        } else {
            input.value = value;
        }
    }

    function openScheduleModal(updateUrl = false) {
        document.getElementById('daily_enabled').checked = !!backupSchedule.daily_enabled;
        document.getElementById('weekly_enabled').checked = !!backupSchedule.weekly_enabled;
        document.getElementById('monthly_enabled').checked = !!backupSchedule.monthly_enabled;

        setBackupTimePickerValue('daily_time', backupSchedule.daily_time);
        setBackupTimePickerValue('weekly_time', backupSchedule.weekly_time);
        setBackupTimePickerValue('monthly_time', backupSchedule.monthly_time);

        document.getElementById('scheduleModal').style.display = 'flex';

        if (window.initGlobalFlatpickr) {
            window.initGlobalFlatpickr();
        }

        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('stat', 'auto');
            window.history.replaceState({}, '', url);
        }
    }

    function closeScheduleModal() {
        document.getElementById('scheduleModal').style.display = 'none';

        const url = new URL(window.location.href);
        if (url.searchParams.get('stat') === 'auto') {
            url.searchParams.delete('stat');
            window.history.replaceState({}, '', url);
        }
    }

    async function toggleSchedule(type) {
        const originalValue = !!backupSchedule[type + '_enabled'];

        try {
            backupSchedule[type + '_enabled'] = !originalValue;
            updateScheduleUI();

            const response = await fetch("{{ route('admin.data_backup.update_schedule') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    daily_enabled: !!backupSchedule.daily_enabled,
                    daily_time: backupSchedule.daily_time,
                    weekly_enabled: !!backupSchedule.weekly_enabled,
                    weekly_time: backupSchedule.weekly_time,
                    monthly_enabled: !!backupSchedule.monthly_enabled,
                    monthly_time: backupSchedule.monthly_time,
                })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                backupSchedule[type + '_enabled'] = originalValue;
                updateScheduleUI();
                throw new Error(result.message || 'Failed to update schedule.');
            }

            scheduleOn = !!result.auto_backup_enabled;
            refreshAutoScheduleStat();
            showToast('Schedule Updated', result.message, 'success');
        } catch (error) {
            backupSchedule[type + '_enabled'] = originalValue;
            updateScheduleUI();
            showToast('Schedule Update Failed', error.message, 'error');
        }
    }

    async function restoreBackup(id, backupId) {
        if (!confirm(`Restore ${backupId}?`)) return;

        try {
            const response = await fetch(restoreUrlTemplate.replace('__ID__', id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Restore failed.');
            }

            showToast('Restore', result.message, 'success');
        } catch (error) {
            showToast('Restore Failed', error.message, 'error');
        }
    }

    async function deleteBackup(id, backupId) {
        if (!confirm(`Delete ${backupId}? This cannot be undone.`)) return;

        try {
            const response = await fetch(deleteUrlTemplate.replace('__ID__', id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Delete failed.');
            }

            const row = document.getElementById(`row-${id}`);
            if (row) row.remove();

            showToast('Deleted', result.message, 'success');
        } catch (error) {
            showToast('Delete Failed', error.message, 'error');
        }
    }

    function setActiveStat(el) {
        document.querySelectorAll('.backup-stat').forEach(stat => stat.classList.remove('active'));
        el.classList.add('active');
        requestAnimationFrame(moveStatsIndicator);
    }

    function moveStatsIndicator() {
        const container = document.getElementById('backupStats');
        const indicator = document.getElementById('statsIndicator');
        const active = container ? container.querySelector('.backup-stat.active') : null;

        if (!container || !indicator || !active) return;

        const containerRect = container.getBoundingClientRect();
        const activeRect = active.getBoundingClientRect();

        const left = activeRect.left - containerRect.left;
        const width = activeRect.width;

        if (!indicator.dataset.ready) {
            indicator.style.transition = 'none';
            indicator.style.left = left + 'px';
            indicator.style.width = width + 'px';
            indicator.offsetHeight;
            indicator.style.transition = '';
            indicator.dataset.ready = '1';
        } else {
            indicator.style.left = left + 'px';
            indicator.style.width = width + 'px';
        }
    }

    function scrollToTable() {
        const table = document.getElementById('backupTableBody');

        const url = new URL(window.location.href);
        url.searchParams.set('stat', 'last');
        window.history.replaceState({}, '', url);

        if (table) {
            table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {

        const scheduleForm = document.getElementById('scheduleForm');
        window.bindFilterTagGroup?.({
            groupId: 'backupTypeGroup',
            onChange: () => { },
        });

        window.bindFilterTagGroup?.({
            groupId: 'backupStatusGroup',
            onChange: () => { },
        });

        document.getElementById('backupApplyFiltersBtn')?.addEventListener('click', function () {
            applyFilters({
                type: getBackupFilterGroupValue('backupTypeGroup'),
                status: getBackupFilterGroupValue('backupStatusGroup'),
                stat: '',
            });

            closeFilterDrawer('filterPanel', 'filterOverlay');
        });

        window.initGlobalViewToggles?.(document);
        syncFilterInputs(); F

        if (scheduleForm) {
            scheduleForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                try {
                    const response = await fetch("{{ route('admin.data_backup.update_schedule') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            daily_enabled: document.getElementById('daily_enabled').checked,
                            daily_time: document.getElementById('daily_time').value,
                            weekly_enabled: document.getElementById('weekly_enabled').checked,
                            weekly_time: document.getElementById('weekly_time').value,
                            monthly_enabled: document.getElementById('monthly_enabled').checked,
                            monthly_time: document.getElementById('monthly_time').value,
                        })
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to update schedule.');
                    }

                    backupSchedule.daily_enabled = document.getElementById('daily_enabled').checked;
                    backupSchedule.daily_time = document.getElementById('daily_time').value;
                    backupSchedule.weekly_enabled = document.getElementById('weekly_enabled').checked;
                    backupSchedule.weekly_time = document.getElementById('weekly_time').value;
                    backupSchedule.monthly_enabled = document.getElementById('monthly_enabled').checked;
                    backupSchedule.monthly_time = document.getElementById('monthly_time').value;

                    scheduleOn = !!result.auto_backup_enabled;

                    closeScheduleModal();
                    updateScheduleUI();
                    showToast('Schedule Updated', result.message, 'success');
                } catch (error) {
                    showToast('Schedule Update Failed', error.message, 'error');
                }
            });
        }

        updateScheduleUI();
        updateResetButtonVisibility();
    });
</script>
@endsection