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

        <div class="relative z-10 mt-4 px-4 sm:px-6 lg:px-7 pb-8">

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
                        </div>
                    </div>

                    <div class="backup-history-actions">
                        <button type="button" id="filterBtn" class="global-filter-btn" onclick="openBackupFilterModal()"
                            aria-pressed="false">
                            <i class="fa-solid fa-sliders"></i>
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
                                            <button type="button" class="action-btn dl" aria-label="Download"
                                                data-tooltip="Download"
                                                onclick="openBackupActionModal('download', {{ $backup->id }}, '{{ $backup->backup_id }}', '{{ route('admin.data_backup.download', $backup->id) }}')">
                                                <i class="fa-solid fa-download"></i>
                                            </button>

                                            <button type="button" class="action-btn restore" aria-label="Restore"
                                                data-tooltip="Restore"
                                                onclick="openBackupActionModal('restore', {{ $backup->id }}, '{{ $backup->backup_id }}')">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>

                                            <button type="button" class="action-btn del" aria-label="Delete"
                                                data-tooltip="Delete"
                                                onclick="openBackupActionModal('delete', {{ $backup->id }}, '{{ $backup->backup_id }}')">
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
                                    <button type="button" class="action-btn dl" aria-label="Download"
                                        data-tooltip="Download"
                                        onclick="openBackupActionModal('download', {{ $backup->id }}, '{{ $backup->backup_id }}', '{{ route('admin.data_backup.download', $backup->id) }}')">
                                        <i class="fa-solid fa-download"></i>
                                    </button>

                                    <button type="button" class="action-btn restore" aria-label="Restore"
                                        data-tooltip="Restore"
                                        onclick="openBackupActionModal('restore', {{ $backup->id }}, '{{ $backup->backup_id }}')">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>

                                    <button type="button" class="action-btn del" aria-label="Delete"
                                        data-tooltip="Delete"
                                        onclick="openBackupActionModal('delete', {{ $backup->id }}, '{{ $backup->backup_id }}')">
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
                            <i class="fa-solid fa-pen backup-edit-icon"></i> Edit Schedule
                            Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="backupModal" class="ui-modal modal-overlay backup-progress-modal" aria-hidden="true">
    <div class="backup-modal-inner modal-box-inner" onclick="event.stopPropagation()">
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

        <button type="button" class="terms-cancel-btn backup-progress-close" id="modalClose"
            onclick="closeBackupProgressModal()" disabled>
            Close
        </button>
    </div>
</div>

<div id="scheduleModal" class="ui-modal modal-overlay admin-modal-backdrop backup-schedule-modal" aria-hidden="true">
    <div class="modal-box modal-box-inner admin-modal-card backup-schedule-modal-card"
        onclick="event.stopPropagation()">
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

<div class="modal-overlay ui-modal backup-action-modal ap-delete-modal" id="backupActionModal" aria-hidden="true">
    <div class="modal-box modal-box-inner ap-delete-shell backup-action-shell" onclick="event.stopPropagation()"
        role="dialog" aria-modal="true" aria-labelledby="backupActionTitle">

        <div class="ap-delete-head">
            <div class="ap-delete-head-left">
                <div id="backupActionIcon" class="ap-delete-head-icon backup-action-icon">
                    <i class="fa-solid fa-download"></i>
                </div>

                <div>
                    <h3 id="backupActionTitle" class="ap-delete-title">Download backup</h3>
                    <p id="backupActionSubtitle" class="ap-delete-subtitle">This action requires confirmation</p>
                </div>
            </div>

            <button type="button" onclick="closeBackupActionModal()" class="ap-delete-x"
                aria-label="Close action modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="ap-delete-content backup-action-content">
            <div id="backupActionWarning" class="ap-delete-warning backup-action-warning">
                <i id="backupActionWarningIcon" class="fa-solid fa-circle-info"></i>

                <div>
                    <p id="backupActionMessage">
                        Are you sure you want to continue?
                    </p>
                    <span id="backupActionHint">Please review this action before continuing.</span>
                </div>
            </div>

            <div class="ap-delete-footer">
                <button type="button" onclick="closeBackupActionModal()" class="modal-btn-ghost">
                    Cancel
                </button>

                <button type="button" id="backupActionConfirmBtn" class="ap-delete-confirm-btn backup-action-confirm">
                    <i class="fa-solid fa-download"></i>
                    <span>Download</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="filterModal" class="filter-drawer-wrapper backup-filter-modal">
    <div class="filter-drawer-overlay" onclick="closeBackupFilterModal()"></div>

    <div class="filter-drawer-panel flex flex-col bg-white">
        <div class="px-6 py-5 flex items-center justify-between flex-shrink-0 bg-white border-b border-gray-100">
            <div class="filter-drawer-title flex items-center gap-2">
                <i class="fa-solid fa-sliders text-xl"></i>
                <h2 class="text-xl font-extrabold">Filters</h2>
            </div>

            <button id="closeFilterModalBtn" type="button" class="text-gray-400 hover:text-gray-700 transition-colors"
                onclick="closeBackupFilterModal()" aria-label="Close filters">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="px-6 py-5 flex flex-col gap-6 flex-1 overflow-y-auto bg-white">
            <div id="activeFiltersSection" class="hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[13px] font-bold text-gray-800">Active Filters</span>

                    <button id="clearAllChipsBtn" type="button" class="text-xs font-bold text-[#8B0000] hover:underline"
                        onclick="resetAjaxFilters()">
                        Clear All
                    </button>
                </div>

                <div id="activeChipsContainer" class="flex flex-wrap gap-2 pb-4 border-b border-gray-100"></div>
            </div>

            <div>
                <h3 class="filter-section-title">Sort By</h3>
                <div class="filter-chip-row" id="backupSortGroup">
                    <button type="button" class="ftag ftag-active" data-val="newest">Newest First</button>
                    <button type="button" class="ftag" data-val="oldest">Oldest First</button>
                    <button type="button" class="ftag" data-val="largest">Largest Size</button>
                    <button type="button" class="ftag" data-val="smallest">Smallest Size</button>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Filter by Date Range</h3>
                <div class="filter-chip-row" id="backupDatePresetGroup">
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
                        <input id="backupFromDate" type="text" class="js-flatpickr-date-range-from"
                            placeholder="Start date" readonly autocomplete="off" />
                        <i class="fa-regular fa-calendar"></i>
                    </div>

                    <div class="filter-date-input-wrap">
                        <input id="backupToDate" type="text" class="js-flatpickr-date-range-to" placeholder="End date"
                            readonly autocomplete="off" />
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Backup Type</h3>
                <div id="backupTypeGroup" class="filter-chip-row">
                    <button type="button" class="ftag ftag-active" data-val="">All Types</button>
                    <button type="button" class="ftag" data-val="full">Full</button>
                    <button type="button" class="ftag" data-val="incremental">Incremental</button>
                </div>
            </div>

            <div class="pb-6">
                <h3 class="filter-section-title">Backup Status</h3>
                <div id="backupStatusGroup" class="filter-chip-row">
                    <button type="button" class="ftag ftag-active" data-val="">All Status</button>
                    <button type="button" class="ftag" data-val="completed">Completed</button>
                    <button type="button" class="ftag" data-val="failed">Failed</button>
                    <button type="button" class="ftag" data-val="in_progress">In Progress</button>
                </div>
            </div>
        </div>

        <div
            class="px-6 py-5 bg-white flex flex-col sm:flex-row items-center justify-between flex-shrink-0 border-t border-gray-100 gap-4 sm:gap-0 relative z-20">
            <button id="clearFiltersModal" type="button"
                class="filter-clear-btn flex items-center gap-2 transition-colors w-full sm:w-auto justify-center sm:justify-start"
                onclick="resetAjaxFilters()">
                <i class="fa-regular fa-trash-can text-lg"></i>
                <span class="text-[13px] font-bold leading-none whitespace-nowrap">Clear Filters</span>
            </button>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button id="cancelFilterBtn" type="button"
                    class="filter-cancel-btn flex-1 sm:flex-none px-5 py-2.5 text-sm font-bold rounded-lg transition-colors"
                    onclick="closeBackupFilterModal()">
                    Cancel
                </button>

                <button id="backupApplyFiltersBtn" type="button"
                    class="filter-show-results-btn filter-apply-btn flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold rounded-lg transition-colors shadow-sm">
                    <i class="fa-solid fa-check"></i>
                    <span id="backupShowResultsText">Show results</span>
                </button>
            </div>
        </div>
    </div>
</div>
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
        sort: @json(request('sort', 'newest')),
        date_range: '',
        date_from: @json(request('date_from', '')),
        date_to: @json(request('date_to', '')),
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

    function getBackupFilterGroupValue(groupId) {
        const active = document.querySelector(`#${groupId} .ftag.ftag-active`);
        return active?.getAttribute('data-val') || '';
    }

    function getBackupDatePresetValue() {
        const active = document.querySelector('#backupDatePresetGroup .quick-date-chip.active');
        return active?.getAttribute('data-range') || '';
    }

    function syncBackupChipGroup(groupId, value) {
        document.querySelectorAll(`#${groupId} .ftag`).forEach(button => {
            button.classList.toggle('ftag-active', button.dataset.val === String(value || ''));
        });
    }

    function syncBackupDatePresets(value) {
        document.querySelectorAll('#backupDatePresetGroup .quick-date-chip').forEach(button => {
            button.classList.toggle('active', button.dataset.range === String(value || ''));
        });
    }

    function getActiveBackupFilterCount() {
        return ['sort', 'date_range', 'date_from', 'date_to', 'type', 'status'].filter(key => {
            const value = String(currentFilters[key] || '').trim();

            if (key === 'sort') {
                return value !== '' && value !== 'newest';
            }

            return value !== '';
        }).length;
    }

    function updateBackupActiveChips() {
        const section = document.getElementById('activeFiltersSection');
        const container = document.getElementById('activeChipsContainer');

        if (!section || !container) return;

        const labels = {
            sort: {
                newest: 'Sort: Newest First',
                oldest: 'Sort: Oldest First',
                largest: 'Sort: Largest Size',
                smallest: 'Sort: Smallest Size',
            },
            date_range: {
                7: 'Last 7 Days',
                30: 'Last 30 Days',
                90: 'Last 3 Months',
                180: 'Last 6 Months',
                365: 'Last 12 Months',
            },
            type: {
                full: 'Type: Full',
                incremental: 'Type: Incremental',
            },
            status: {
                completed: 'Status: Completed',
                failed: 'Status: Failed',
                in_progress: 'Status: In Progress',
            },
        };

        const chips = [];

        if (currentFilters.sort && currentFilters.sort !== 'newest') {
            chips.push(`
            <span class="filter-chip">
                <span>${labels.sort[currentFilters.sort] || 'Sort filter'}</span>
                <button type="button" class="filter-chip-remove" onclick="clearBackupFilter('sort')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </span>
        `);
        }

        if (currentFilters.date_range) {
            chips.push(`
            <span class="filter-chip">
                <span>${labels.date_range[currentFilters.date_range] || 'Date range'}</span>
                <button type="button" class="filter-chip-remove" onclick="clearBackupFilter('date_range')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </span>
        `);
        }

        if (currentFilters.date_from || currentFilters.date_to) {
            chips.push(`
            <span class="filter-chip">
                <span>Date: ${currentFilters.date_from || 'Any'} - ${currentFilters.date_to || 'Any'}</span>
                <button type="button" class="filter-chip-remove" onclick="clearBackupFilter('custom_date')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </span>
        `);
        }

        ['type', 'status'].forEach(key => {
            const value = currentFilters[key];

            if (!value || !labels[key]?.[value]) return;

            chips.push(`
            <span class="filter-chip">
                <span>${labels[key][value]}</span>
                <button type="button" class="filter-chip-remove" onclick="clearBackupFilter('${key}')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </span>
        `);
        });

        container.innerHTML = chips.join('');
        section.classList.toggle('hidden', chips.length === 0);
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
        syncBackupChipGroup('backupSortGroup', currentFilters.sort || 'newest');
        syncBackupChipGroup('backupTypeGroup', currentFilters.type || '');
        syncBackupChipGroup('backupStatusGroup', currentFilters.status || '');
        syncBackupDatePresets(currentFilters.date_range || '');

        const fromDate = document.getElementById('backupFromDate');
        const toDate = document.getElementById('backupToDate');

        if (fromDate) fromDate.value = currentFilters.date_from || '';
        if (toDate) toDate.value = currentFilters.date_to || '';

        updateResetButtonVisibility();
    }

    function clearBackupFilter(key) {
        if (key === 'sort') {
            currentFilters.sort = 'newest';
        } else if (key === 'date_range') {
            currentFilters.date_range = '';
        } else if (key === 'custom_date') {
            currentFilters.date_from = '';
            currentFilters.date_to = '';
        } else if (Object.prototype.hasOwnProperty.call(currentFilters, key)) {
            currentFilters[key] = '';
        }

        syncFilterInputs();
        fetchBackupTable();
    }

    function openBackupFilterModal() {
        const modal = document.getElementById('filterModal');

        if (!modal) return;

        // Keep the filter drawer outside grid/list containers.
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        modal.classList.remove('closing');
        modal.classList.add('open');

        document.documentElement.classList.add('filter-lock');
        document.body.classList.add('filter-lock');

        if (window.initGlobalFlatpickr) {
            window.initGlobalFlatpickr();
        }

        syncFilterInputs();
    }

    function closeBackupFilterModal() {
        const modal = document.getElementById('filterModal');

        if (!modal) return;

        modal.classList.add('closing');
        modal.classList.remove('open');

        setTimeout(() => {
            modal.classList.remove('closing');
            document.documentElement.classList.remove('filter-lock');
            document.body.classList.remove('filter-lock');
        }, 260);
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
                            <button type="button" class="action-btn dl" aria-label="Download" data-tooltip="Download"
                                onclick="openBackupActionModal('download', ${backup.id}, '${backup.backup_id}', '${backup.download_url}')">
                                <i class="fa-solid fa-download"></i>
                            </button>

                            <button type="button" class="action-btn restore" aria-label="Restore" data-tooltip="Restore"
                                onclick="openBackupActionModal('restore', ${backup.id}, '${backup.backup_id}')">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>

                            <button type="button" class="action-btn del" aria-label="Delete" data-tooltip="Delete"
                                onclick="openBackupActionModal('delete', ${backup.id}, '${backup.backup_id}')">
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
                            <button type="button" class="action-btn dl"
                            aria-label="Download" data-tooltip="Download"
                            onclick="openBackupActionModal('download', ${backup.id}, '${backup.backup_id}', '${backup.download_url}')">
                            <i class="fa-solid fa-download"></i>
                        </button>

                        <button type="button" class="action-btn restore"
                            aria-label="Restore" data-tooltip="Restore"
                            onclick="openBackupActionModal('restore', ${backup.id}, '${backup.backup_id}')">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>

                        <button type="button" class="action-btn del"
                            aria-label="Delete" data-tooltip="Delete"
                            onclick="openBackupActionModal('delete', ${backup.id}, '${backup.backup_id}')">
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
            if (currentFilters.sort && currentFilters.sort !== 'newest') url.searchParams.set('sort', currentFilters.sort);
            if (currentFilters.date_range) url.searchParams.set('date_range', currentFilters.date_range);
            if (currentFilters.date_from) url.searchParams.set('date_from', currentFilters.date_from);
            if (currentFilters.date_to) url.searchParams.set('date_to', currentFilters.date_to);
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
            sort: 'newest',
            date_range: '',
            date_from: '',
            date_to: '',
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
        openBackupProgressModal();

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
            btn.removeAttribute('disabled');
            btn.style.pointerEvents = 'auto';

            showToast('Backup Complete', result.message || 'New backup saved successfully.', 'success');
            await fetchBackupTable();
        } catch (error) {
            clearInterval(fakeProgress);
            title.textContent = 'Backup Failed';
            sub.textContent = error.message;
            icon.className = 'fa-solid fa-circle-exclamation';
            btn.disabled = false;
            btn.removeAttribute('disabled');
            btn.style.pointerEvents = 'auto';
            showToast('Backup Failed', error.message, 'error');
        } finally {
            if (backupBtn) {
                backupBtn.disabled = false;
                backupBtn.style.opacity = '';
                backupBtn.style.pointerEvents = '';
            }
        }
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

    function setBackupTimePickerValue(inputId, value) {
        const input = document.getElementById(inputId);
        if (!input) return;

        if (input._flatpickr) {
            input._flatpickr.setDate(value, false, 'H:i');
        } else {
            input.value = value;
        }
    }

    const dataBackupModalTimers = {};

    function openDataBackupModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        if (dataBackupModalTimers[id]) {
            clearTimeout(dataBackupModalTimers[id]);
            dataBackupModalTimers[id] = null;
        }

        modal.classList.remove('closing', 'is-closing');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add('modal-lock');

        requestAnimationFrame(() => {
            document.dispatchEvent(new CustomEvent('ui-modal:opened', {
                detail: { modal }
            }));
        });
    }

    function closeDataBackupModal(id, afterClose = null) {
        const modal = document.getElementById(id);

        if (!modal || (!modal.classList.contains('open') && !modal.classList.contains('closing'))) return;

        modal.classList.remove('open');
        modal.classList.add('closing');
        modal.setAttribute('aria-hidden', 'true');

        if (dataBackupModalTimers[id]) {
            clearTimeout(dataBackupModalTimers[id]);
        }

        dataBackupModalTimers[id] = setTimeout(() => {
            modal.classList.remove('closing', 'is-closing');
            dataBackupModalTimers[id] = null;

            if (!document.querySelector('.ui-modal.open, .ui-modal.closing')) {
                document.body.classList.remove('modal-lock');
            }

            if (typeof afterClose === 'function') {
                afterClose();
            }
        }, 180);
    }

    function openBackupProgressModal() {
        openDataBackupModal('backupModal');
    }

    function closeBackupProgressModal() {
        closeDataBackupModal('backupModal');
    }

    function openScheduleModal(updateUrl = false) {
        document.getElementById('daily_enabled').checked = !!backupSchedule.daily_enabled;
        document.getElementById('weekly_enabled').checked = !!backupSchedule.weekly_enabled;
        document.getElementById('monthly_enabled').checked = !!backupSchedule.monthly_enabled;

        setBackupTimePickerValue('daily_time', backupSchedule.daily_time);
        setBackupTimePickerValue('weekly_time', backupSchedule.weekly_time);
        setBackupTimePickerValue('monthly_time', backupSchedule.monthly_time);

        openDataBackupModal('scheduleModal');

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
        closeDataBackupModal('scheduleModal', () => {
            const url = new URL(window.location.href);

            if (url.searchParams.get('stat') === 'auto') {
                url.searchParams.delete('stat');
                window.history.replaceState({}, '', url);
            }
        });
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

    let pendingBackupAction = null;

    function openBackupActionModal(action, id, backupId, downloadUrl = '') {
        const modal = document.getElementById('backupActionModal');
        const title = document.getElementById('backupActionTitle');
        const subtitle = document.getElementById('backupActionSubtitle');
        const message = document.getElementById('backupActionMessage');
        const hint = document.getElementById('backupActionHint');
        const iconWrap = document.getElementById('backupActionIcon');
        const warning = document.getElementById('backupActionWarning');
        const warningIcon = document.getElementById('backupActionWarningIcon');
        const confirmBtn = document.getElementById('backupActionConfirmBtn');

        if (!modal || !title || !message || !confirmBtn || !iconWrap || !warning) return;

        const config = {
            download: {
                title: 'Download Backup',
                subtitle: 'Save a copy of this backup file',
                message: `Download backup ${backupId}?`,
                hint: 'The backup file will be downloaded to your device.',
                icon: 'fa-download',
                warningIcon: 'fa-circle-info',
                tone: 'download',
                button: 'Download',
            },
            restore: {
                title: 'Restore Backup',
                subtitle: 'This action requires confirmation',
                message: `Are you sure you want to restore backup ${backupId}?`,
                hint: 'Make sure this is the correct snapshot before continuing.',
                icon: 'fa-rotate-left',
                warningIcon: 'fa-triangle-exclamation',
                tone: 'restore',
                button: 'Restore',
            },
            delete: {
                title: 'Delete Backup',
                subtitle: 'This action requires confirmation',
                message: `Are you sure you want to delete backup ${backupId}?`,
                hint: 'This backup record and file will be permanently removed.',
                icon: 'fa-trash-can',
                warningIcon: 'fa-triangle-exclamation',
                tone: 'delete',
                button: 'Delete',
            },
        }[action];

        if (!config) return;

        pendingBackupAction = { action, id, backupId, downloadUrl };

        title.textContent = config.title;
        subtitle.textContent = config.subtitle;
        message.textContent = config.message;

        if (hint) hint.textContent = config.hint;

        iconWrap.className = `ap-delete-head-icon backup-action-icon is-${config.tone}`;
        iconWrap.innerHTML = `<i class="fa-solid ${config.icon}"></i>`;

        warning.className = `ap-delete-warning backup-action-warning is-${config.tone}`;

        if (warningIcon) {
            warningIcon.className = `fa-solid ${config.warningIcon}`;
        }

        confirmBtn.className = `ap-delete-confirm-btn backup-action-confirm is-${config.tone}`;
        confirmBtn.innerHTML = `<i class="fa-solid ${config.icon}"></i><span>${config.button}</span>`;

        openDataBackupModal('backupActionModal');
    }

    function closeBackupActionModal() {
        closeDataBackupModal('backupActionModal', () => {
            pendingBackupAction = null;
        });
    }

    async function runBackupAction() {
        if (!pendingBackupAction) return;

        const { action, id, backupId, downloadUrl } = pendingBackupAction;
        const confirmBtn = document.getElementById('backupActionConfirmBtn');

        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = `<i class="fa-solid fa-spinner spin"></i> Processing`;
        }

        try {
            if (action === 'download') {
                closeBackupActionModal();
                window.location.href = downloadUrl;
                return;
            }

            if (action === 'restore') {
                const response = await fetch(restoreUrlTemplate.replace('__ID__', id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Restore failed.');
                }

                closeBackupActionModal();
                window.showToast?.('Restore', result.message, 'success');
                return;
            }

            if (action === 'delete') {
                const response = await fetch(deleteUrlTemplate.replace('__ID__', id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Delete failed.');
                }

                closeBackupActionModal();
                await fetchBackupTable();
                window.showToast?.('Deleted', result.message, 'success');
            }
        } catch (error) {
            window.showToast?.('Action Failed', error.message, 'error');
        } finally {
            if (confirmBtn) {
                confirmBtn.disabled = false;

                const currentAction = pendingBackupAction?.action || action;
                const labels = {
                    download: ['fa-download', 'Download'],
                    restore: ['fa-rotate-left', 'Restore'],
                    delete: ['fa-trash-can', 'Delete'],
                };

                const [icon, label] = labels[currentAction] || ['fa-check', 'Continue'];
                confirmBtn.innerHTML = `<i class="fa-solid ${icon}"></i><span>${label}</span>`;
            }
        }
    }

    document.getElementById('backupActionConfirmBtn')?.addEventListener('click', runBackupAction);

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
        document.querySelectorAll('#backupSortGroup .ftag').forEach(button => {
            button.addEventListener('click', () => {
                currentFilters.sort = button.dataset.val || 'newest';
                syncFilterInputs();
            });
        });

        document.querySelectorAll('#backupTypeGroup .ftag').forEach(button => {
            button.addEventListener('click', () => {
                currentFilters.type = button.dataset.val || '';
                syncFilterInputs();
            });
        });

        document.querySelectorAll('#backupStatusGroup .ftag').forEach(button => {
            button.addEventListener('click', () => {
                currentFilters.status = button.dataset.val || '';
                syncFilterInputs();
            });
        });

        document.querySelectorAll('#backupDatePresetGroup .quick-date-chip').forEach(button => {
            button.addEventListener('click', () => {
                currentFilters.date_range = button.dataset.range || '';
                currentFilters.date_from = '';
                currentFilters.date_to = '';
                syncFilterInputs();
            });
        });

        document.getElementById('backupFromDate')?.addEventListener('change', function () {
            currentFilters.date_from = this.value;
            currentFilters.date_range = '';
            syncFilterInputs();
        });

        document.getElementById('backupToDate')?.addEventListener('change', function () {
            currentFilters.date_to = this.value;
            currentFilters.date_range = '';
            syncFilterInputs();
        });

        document.getElementById('backupApplyFiltersBtn')?.addEventListener('click', function () {
            fetchBackupTable();
            closeBackupFilterModal();
        });

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

            closeBackupFilterModal();
        });

        window.initGlobalViewToggles?.(document);
        syncFilterInputs();

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