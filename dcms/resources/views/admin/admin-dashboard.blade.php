@extends('layouts.admin')

@section('title', 'Admin Dashboard | PUP Taguig Dental Clinic')

@section('content')

@php $logs = $logs ?? collect([]); @endphp

<main id="mainContent" style="padding-top: var(--header-h); min-height: 100vh;">

    <x-dashboard-loading-status />

    <div class="page-banner">
        <div class="page-banner-inner">
            <div>
                <div class="page-greeting">
                    <i id="currentDateIcon" class="fa-solid fa-calendar-day" style="color:#fcd34d;"></i>
                    <span id="currentDate"></span>
                </div>
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-subtitle">Welcome back, Administrator. Here's what's happening today.</p>
            </div>

            <div class="period-pill">
                <div class="period-item">
                    <span class="period-label"><i class="fa-solid fa-calendar" style="margin-right:3px;"></i>
                        Semester</span>
                    <span class="period-value">
                        {{ $activePeriod?->semester ?? 'No Active Period' }}
                    </span>
                </div>
                <div class="period-divider"></div>
                <div class="period-item">
                    <span class="period-label"><i class="fa-solid fa-graduation-cap" style="margin-right:3px;"></i>
                        Academic
                        Year</span>
                    <span class="period-value">
                        {{ $activePeriod?->academic_year ?? 'Not Set' }}
                    </span>
                </div>
                <div class="period-divider"></div>
                <div class="period-item">
                    <span class="period-label"><i class="fa-solid fa-clock" style="margin-right:3px;"></i> Period
                        Ends</span>
                    <span class="period-value">
                        {{ $activePeriod?->end_date ? $activePeriod->end_date->format('F d, Y') : 'Not Set' }}
                    </span>
                </div>
                <a href="{{ route('admin.academic_periods') }}" class="manage-btn">
                    <i class="fa-solid fa-gear"></i> Manage
                </a>
            </div>

        </div>
    </div>

    <div class="content-lift">

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card-accent" style="background: linear-gradient(90deg, var(--crimson), #c0392b);">
                </div>
                <div class="stat-top">
                    <div class="stat-icon" style="background:#fef2f2;">
                        <i class="fa-solid fa-users" style="color:var(--crimson);"></i>
                    </div>
                    <span class="stat-badge" style="background:#fef2f2;color:var(--crimson);">All time</span>
                </div>
                <div class="stat-label">Total Patients</div>
                <div class="stat-value">{{ number_format($totalPatients) }}</div>
                <div class="stat-footer">
                    <i class="fa-solid fa-user-plus" style="font-size:.65rem;color:var(--crimson);"></i>
                    All registered patients
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-accent" style="background: linear-gradient(90deg, #3b82f6, #2563eb);"></div>
                <div class="stat-top">
                    <div class="stat-icon" style="background:#eff6ff;">
                        <i class="fa-solid fa-calendar-check" style="color:#3b82f6;"></i>
                    </div>
                    <span class="stat-badge" style="background:#eff6ff;color:#3b82f6;">{{
                        \Carbon\Carbon::now()->format('F Y') }}</span>
                </div>
                <div class="stat-label">Appointments</div>
                <div class="stat-value">{{ $appointmentsThisMonth }}</div>
                <div class="stat-footer">
                    <i class="fa-solid fa-clock" style="font-size:.65rem;color:#3b82f6;"></i>
                    This month
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-accent" style="background: linear-gradient(90deg, #22c55e, #16a34a);"></div>
                <div class="stat-top">
                    <div class="stat-icon" style="background:#f0fdf4;">
                        <i class="fa-solid fa-file-arrow-up" style="color:#22c55e;"></i>
                    </div>
                    <span class="stat-badge" style="background:#f0fdf4;color:#16a34a;">{{
                        \Carbon\Carbon::now()->format('F Y') }}</span>
                </div>
                <div class="stat-label">Documents Issued</div>
                <div class="stat-value">{{ $documentsThisMonth }}</div>
                <div class="stat-footer">
                    <i class="fa-solid fa-file-lines" style="font-size:.65rem;color:#22c55e;"></i>
                    This month
                </div>
            </div>
        </div>

        <div class="main-grid">

            <div style="display:flex;flex-direction:column;gap:1.25rem;">

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                            <span class="card-title">System Logs Overview</span>
                        </div>

                        <div class="card-header-actions">
                            <div class="view-toggle" id="dashboardLogsViewToggle">
                                <button type="button" class="view-toggle-btn active" data-view="list"
                                    id="dashboardLogsListBtn" title="List view" aria-label="List view">
                                    <i class="fa-solid fa-table-list"></i>
                                </button>
                                <button type="button" class="view-toggle-btn" data-view="grid" id="dashboardLogsGridBtn"
                                    title="Grid view" aria-label="Grid view">
                                    <i class="fa-solid fa-grip"></i>
                                </button>
                            </div>

                            <a href="{{ route('admin.system_logs') }}" class="card-link">
                                View All <i class="fa-solid fa-arrow-right" style="font-size:.65rem;"></i>
                            </a>
                        </div>
                    </div>

                    <div class="log-stats-row">
                        <div class="log-stat" style="background:#f5f3ff;">
                            <div class="log-stat-value" style="color:#7c3aed;">{{ $logThisMonth ?? 0 }}</div>
                            <div class="log-stat-label" style="color:#7c3aed;">This Month</div>
                        </div>
                        <div class="log-stat" style="background:#eff6ff;">
                            <div class="log-stat-value" style="color:#2563eb;">{{ $logInfo ?? 0 }}</div>
                            <div class="log-stat-label" style="color:#3b82f6;">Views</div>
                        </div>
                        <div class="log-stat" style="background:#fffbeb;">
                            <div class="log-stat-value" style="color:#d97706;">{{ $logWarnings ?? 0 }}</div>
                            <div class="log-stat-label" style="color:#f59e0b;">Logins</div>
                        </div>
                        <div class="log-stat" style="background:#f0fdf4;">
                            <div class="log-stat-value" style="color:#16a34a;">{{ $logBackups ?? 0 }}</div>
                            <div class="log-stat-label" style="color:#22c55e;">Backups</div>
                        </div>
                        <div class="log-stat" style="background:#fef2f2;">
                            <div class="log-stat-value" style="color:var(--crimson);">{{ $logErrors ?? 0 }}</div>
                            <div class="log-stat-label" style="color:#ef4444;">Errors</div>
                        </div>
                    </div>

                    @if (($recentLogs ?? collect())->isEmpty())
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
                        <p style="font-size:.82rem;font-weight:700;color:#6b7280;margin-bottom:.25rem;">
                            No logs yet
                        </p>
                        <p style="font-size:.72rem;color:#b0b7c3;">System activity will appear here</p>
                    </div>
                    @else
                    <div class="logs-view" id="dashboardLogsListView">
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">ID</th>
                                        <th style="width:160px;">Date & Time</th>
                                        <th>Description</th>
                                        <th style="width:120px;">User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentLogs ?? [] as $log)
                                    @php
                                    $logId = data_get($log, 'id', '—');
                                    $logDate = data_get($log, 'created_at');
                                    $logDesc = data_get(
                                    $log,
                                    'description',
                                    'No description provided.',
                                    );
                                    $logActor = data_get($log, 'actor_identifier', '—');
                                    $logRole = data_get($log, 'actor_role', '');
                                    @endphp
                                    <tr>
                                        <td style="color:#9ca3af;font-size:.72rem;">#{{ $logId }}</td>
                                        <td>
                                            <div style="font-size:.74rem;font-weight:600;">
                                                {{ $logDate ? \Carbon\Carbon::parse($logDate)->format('M j, Y') : '—' }}
                                            </div>
                                            <div style="font-size:.68rem;color:#9ca3af;">
                                                {{ $logDate ? \Carbon\Carbon::parse($logDate)->format('h:i:s A') : '—'
                                                }}
                                            </div>
                                        </td>
                                        <td style="font-size:.76rem;">{{ $logDesc }}</td>
                                        <td>
                                            <span style="font-size:.72rem;font-weight:600;">{{ $logActor }}</span>
                                            <div style="font-size:.65rem;color:#9ca3af;text-transform:capitalize;">
                                                {{ $logRole }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="logs-view" id="dashboardLogsGridView" hidden>
                        <div class="logs-grid">
                            @foreach ($recentLogs ?? [] as $log)
                            @php
                            $logId = data_get($log, 'id', '—');
                            $logDate = data_get($log, 'created_at');
                            $logDesc = data_get($log, 'description', 'No description provided.');
                            $logActor = data_get($log, 'actor_identifier', '—');
                            $logRole = data_get($log, 'actor_role', '');
                            $logInitial = strtoupper(substr(trim($logActor), 0, 1));
                            @endphp

                            <div class="log-card">
                                <div class="log-card-top">
                                    <div class="log-card-id">#{{ $logId }}</div>
                                    <div class="log-card-date">
                                        {{ $logDate ? \Carbon\Carbon::parse($logDate)->format('M d, Y h:i A') : '—' }}
                                    </div>
                                </div>

                                <div class="log-card-desc">
                                    {{ $logDesc }}
                                </div>

                                <div class="log-card-user">
                                    <div class="log-card-avatar">{{ $logInitial ?: '—' }}</div>
                                    <div class="log-card-user-info">
                                        <div class="log-card-user-name">{{ $logActor }}</div>
                                        <div class="log-card-user-role">{{ $logRole ?: 'No role' }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <div class="bottom-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-chart-pie"></i></div>
                                <span class="card-title">GAD Analytics</span>
                            </div>
                            <a href="#" class="card-link">View <i class="fa-solid fa-arrow-right"
                                    style="font-size:.65rem;"></i></a>
                        </div>
                        <div style="padding:1.25rem;">
                            <div class="gad-placeholder">
                                <div style="text-align:center;">
                                    <i class="fa-solid fa-chart-area gad-placeholder-icon"></i>
                                    <span class="gad-placeholder-text">Chart coming soon</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card inventory-overview-card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                                <span class="card-title">Inventory Overview</span>
                            </div>
                            <a href="{{ route('admin.inventory') }}" class="card-link">
                                View <i class="fa-solid fa-arrow-right" style="font-size:.65rem;"></i>
                            </a>
                        </div>

                        <div class="inventory-chart-card-body">

                            <div id="inventoryOverviewEmpty" class="inventory-empty" style="display:none;">
                                <div>
                                    <i id="inventoryOverviewEmptyIcon" class="fa-solid fa-box-open"
                                        style="font-size:2rem;color:#e5e7eb;display:block;margin-bottom:.5rem;"></i>
                                    <span id="inventoryOverviewEmptyText"
                                        style="font-size:.72rem;color:#b0b7c3;font-weight:600;">
                                        No inventory records yet
                                    </span>
                                </div>
                            </div>

                            <div id="inventoryOverviewContent" class="skeleton-fade-swap">
                                <div class="space-y-4">
                                    <div class="inventory-top-layout">
                                        <div class="inventory-donut-wrap">
                                            <div class="inventory-donut-box">
                                                <div class="inventory-donut-center">
                                                    <span class="skeleton-line"
                                                        style="width: 42px; height: 18px;"></span>
                                                    <small class="skeleton-line"
                                                        style="width: 34px; height: 8px; margin-top: 8px;"></small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="inventory-legend">
                                            <div class="inventory-legend-item pointer-events-none"
                                                data-stock-filter="in-stock">
                                                <span class="legend-bubble"></span>
                                                <span class="legend-bubble-sm"></span>
                                                <div class="inventory-legend-left">
                                                    <span class="inventory-legend-dot in-stock"></span>
                                                    <div class="space-y-2">
                                                        <div class="skeleton-line" style="width: 74px; height: 12px;">
                                                        </div>
                                                        <div class="skeleton-line" style="width: 102px; height: 10px;">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="skeleton-line" style="width: 32px; height: 18px;"></div>
                                            </div>

                                            <div class="inventory-legend-item pointer-events-none"
                                                data-stock-filter="low-stock">
                                                <span class="legend-bubble"></span>
                                                <span class="legend-bubble-sm"></span>
                                                <div class="inventory-legend-left">
                                                    <span class="inventory-legend-dot low-stock"></span>
                                                    <div class="space-y-2">
                                                        <div class="skeleton-line" style="width: 66px; height: 12px;">
                                                        </div>
                                                        <div class="skeleton-line" style="width: 110px; height: 10px;">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="skeleton-line" style="width: 32px; height: 18px;"></div>
                                            </div>

                                            <div class="inventory-legend-item pointer-events-none"
                                                data-stock-filter="out-stock">
                                                <span class="legend-bubble"></span>
                                                <span class="legend-bubble-sm"></span>
                                                <div class="inventory-legend-left">
                                                    <span class="inventory-legend-dot out-stock"></span>
                                                    <div class="space-y-2">
                                                        <div class="skeleton-line" style="width: 82px; height: 12px;">
                                                        </div>
                                                        <div class="skeleton-line" style="width: 122px; height: 10px;">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="skeleton-line" style="width: 32px; height: 18px;"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="inventory-mini-stats-row">
                                        <div class="inventory-mini-pill total">
                                            <div class="inventory-mini-pill-label">Total</div>
                                            <div class="inventory-mini-pill-value skeleton-line"
                                                style="width: 42px; height: 18px; margin: 0 auto;"></div>
                                        </div>

                                        <div class="inventory-mini-pill medicine">
                                            <div class="inventory-mini-pill-label">Medicine</div>
                                            <div class="inventory-mini-pill-value skeleton-line"
                                                style="width: 42px; height: 18px; margin: 0 auto;"></div>
                                        </div>

                                        <div class="inventory-mini-pill supplies">
                                            <div class="inventory-mini-pill-label">Supplies</div>
                                            <div class="inventory-mini-pill-value skeleton-line"
                                                style="width: 42px; height: 18px; margin: 0 auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:1.25rem;">

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon"><i class="fa-solid fa-bolt"></i></div>
                            <span class="card-title">Quick Actions</span>
                        </div>
                    </div>
                    <div style="padding:1rem;">
                        <button class="qa-btn">
                            <div class="qa-icon"><i class="fa-solid fa-file-circle-plus"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">New Template</span>
                                <span class="qa-sub">Create document format</span>
                            </div>
                            <i class="fa-solid fa-chevron-right qa-arrow"></i>
                        </button>
                        <button class="qa-btn">
                            <div class="qa-icon"><i class="fa-solid fa-file-invoice"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Generate Report</span>
                                <span class="qa-sub">Create report documents</span>
                            </div>
                            <i class="fa-solid fa-chevron-right qa-arrow"></i>
                        </button>
                        <button class="qa-btn">
                            <div class="qa-icon"><i class="fa-solid fa-chart-column"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">View Reports</span>
                                <span class="qa-sub">All reports & analytics</span>
                            </div>
                            <i class="fa-solid fa-chevron-right qa-arrow"></i>
                        </button>
                        <a href="{{ route('admin.user_management') }}" class="qa-btn">
                            <div class="qa-icon"><i class="fa-solid fa-user-plus"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Add User</span>
                                <span class="qa-sub">Register new account</span>
                            </div>
                            <i class="fa-solid fa-chevron-right qa-arrow"></i>
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon"><i class="fa-solid fa-database"></i></div>
                            <span class="card-title">Data Backup</span>
                        </div>

                        @if ($autoBackupEnabled)
                        <span style="font-size:.65rem;font-weight:700;background:#f0fdf4;color:#16a34a;
                                        padding:.25rem .6rem;border-radius:20px;border:1px solid #bbf7d0;">
                            Active
                        </span>
                        @else
                        <span style="font-size:.65rem;font-weight:700;background:#fef3c7;color:#a16207;
                                        padding:.25rem .6rem;border-radius:20px;border:1px solid #fcd34d;">
                            Paused
                        </span>
                        @endif
                    </div>

                    <div style="padding:1rem;">
                        <div class="backup-status">
                            <div class="backup-check">
                                <i class="fa-solid {{ $lastBackup ? 'fa-check' : 'fa-clock' }}"></i>
                            </div>
                            <div>
                                <span class="backup-label">Last Backup</span>
                                <span class="backup-date">
                                    {{ $lastBackup ? $lastBackup->created_at->format('F d, Y h:i A') : 'No backup yet'
                                    }}
                                </span>
                                <span class="backup-sub">
                                    {{ $lastBackup ? '✓ Completed successfully' : 'No completed backup found' }}
                                </span>
                            </div>
                        </div>

                        <div class="next-backup">
                            <i class="fa-regular fa-clock next-icon"></i>
                            <div>
                                <div class="next-label">Next Scheduled</div>
                                <div class="next-date">
                                    {{ $nextBackupDate ? $nextBackupDate->format('F d, Y h:i A') : 'No schedule set' }}
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('admin.data_backup') }}" class="run-backup-btn" style="text-decoration:none;">
                            <i class="fa-solid fa-database"></i>
                            View Backups ({{ $totalBackups }})
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

@endsection

@section('scripts')
@if (session('activeAppointmentModal'))
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var modal = document.getElementById("activeAppointmentModal");
        var closeBtn = document.getElementById("closeActiveApptModalBtn");
        if (!modal) return;
        modal.showModal();
        modal.addEventListener('click', function (e) {
            var box = modal.querySelector('.modal-box');
            if (box && !box.contains(e.target)) e.preventDefault();
        });
        modal.addEventListener('cancel', function (e) {
            e.preventDefault();
        });
        if (closeBtn) closeBtn.addEventListener("click", function () {
            modal.close();
        });
    });
</script>
@endif

<script>
    function getPreferredDashboardLogsView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem('dashboardLogsView') || 'list';
    }

    function applyDashboardLogsView(view, save = true) {
        const listView = document.getElementById('dashboardLogsListView');
        const gridView = document.getElementById('dashboardLogsGridView');
        const listBtn = document.getElementById('dashboardLogsListBtn');
        const gridBtn = document.getElementById('dashboardLogsGridBtn');

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
            localStorage.setItem('dashboardLogsView', finalView);
        }
    }

    function initDashboardLogsViewToggle() {
        const listBtn = document.getElementById('dashboardLogsListBtn');
        const gridBtn = document.getElementById('dashboardLogsGridBtn');

        applyDashboardLogsView(getPreferredDashboardLogsView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', () => applyDashboardLogsView('list', true));
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', () => applyDashboardLogsView('grid', true));
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initDashboardLogsViewToggle();

        window.addEventListener('resize', function () {
            applyDashboardLogsView(getPreferredDashboardLogsView(), false);
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let adminInventoryOverviewChart = null;

    function buildInventoryOverviewHtml(values) {
        return `
                <div class="inventory-top-layout">
                    <div class="inventory-donut-wrap">
                        <div class="inventory-donut-box">
                            <div class="inventory-donut-center">
                                <span id="inventoryDonutTotal">${values.total}</span>
                                <small>Items</small>
                            </div>
                            <canvas id="inventoryDonutChart"></canvas>
                        </div>
                    </div>

                    <div class="inventory-legend">
                        <div class="inventory-legend-item" data-stock-filter="in-stock">
                            <span class="legend-bubble"></span>
                            <span class="legend-bubble-sm"></span>
                            <div class="inventory-legend-left">
                                <span class="inventory-legend-dot in-stock"></span>
                                <div>
                                    <div class="inventory-legend-label">In Stock</div>
                                    <div class="inventory-legend-sub">Sufficient supply</div>
                                </div>
                            </div>
                            <div class="inventory-legend-value" id="inventoryInStockValue">${values.inStock}</div>
                        </div>

                        <div class="inventory-legend-item" data-stock-filter="low-stock">
                            <span class="legend-bubble"></span>
                            <span class="legend-bubble-sm"></span>
                            <div class="inventory-legend-left">
                                <span class="inventory-legend-dot low-stock"></span>
                                <div>
                                    <div class="inventory-legend-label">Low Stock</div>
                                    <div class="inventory-legend-sub">Replenishment required</div>
                                </div>
                            </div>
                            <div class="inventory-legend-value" id="inventoryLowStockValue">${values.lowStock}</div>
                        </div>

                        <div class="inventory-legend-item" data-stock-filter="out-stock">
                            <span class="legend-bubble"></span>
                            <span class="legend-bubble-sm"></span>
                            <div class="inventory-legend-left">
                                <span class="inventory-legend-dot out-stock"></span>
                                <div>
                                    <div class="inventory-legend-label">Out of Stock</div>
                                    <div class="inventory-legend-sub">Currently unavailable</div>
                                </div>
                            </div>
                            <div class="inventory-legend-value" id="inventoryOutStockValue">${values.outStock}</div>
                        </div>
                    </div>
                </div>

                <div class="inventory-mini-stats-row">
                    <div class="inventory-mini-pill total">
                        <div class="inventory-mini-pill-label">Total</div>
                        <div class="inventory-mini-pill-value" id="inventoryTotalValue">${values.total}</div>
                    </div>

                    <div class="inventory-mini-pill medicine">
                        <div class="inventory-mini-pill-label">Medicine</div>
                        <div class="inventory-mini-pill-value" id="inventoryMedicineValue">${values.medicine}</div>
                    </div>

                    <div class="inventory-mini-pill supplies">
                        <div class="inventory-mini-pill-label">Supplies</div>
                        <div class="inventory-mini-pill-value" id="inventorySuppliesValue">${values.supplies}</div>
                    </div>
                </div>
            `;
    }

    function animateInventoryLegendCard(card) {
        if (!card) return;
        card.classList.remove('pulse-pop');
        void card.offsetWidth;
        card.classList.add('pulse-pop');
    }

    function setInventoryOverviewEmptyState(message, isError = false) {
        const emptyEl = document.getElementById('inventoryOverviewEmpty');
        const contentEl = document.getElementById('inventoryOverviewContent');
        const textEl = document.getElementById('inventoryOverviewEmptyText');
        const iconEl = document.getElementById('inventoryOverviewEmptyIcon');

        if (!emptyEl || !contentEl) return;

        emptyEl.style.display = 'flex';
        contentEl.style.display = 'none';

        if (textEl) {
            textEl.textContent = message || 'No inventory records yet';
        }

        if (iconEl) {
            iconEl.className = isError ?
                'fa-solid fa-triangle-exclamation' :
                'fa-solid fa-box-open';
            iconEl.style.color = isError ? '#f59e0b' : '#e5e7eb';
        }
    }

    function showInventoryOverviewContent() {
        const emptyEl = document.getElementById('inventoryOverviewEmpty');
        const contentEl = document.getElementById('inventoryOverviewContent');

        if (emptyEl) emptyEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'block';
    }

    function bindInventoryLegendClicks() {
        document.querySelectorAll('.inventory-legend-item').forEach(card => {
            if (card.dataset.bound === '1') return;

            card.dataset.bound = '1';
            card.addEventListener('click', function () {
                document.querySelectorAll('.inventory-legend-item').forEach(el => el.classList.remove(
                    'active'));
                this.classList.add('active');
                animateInventoryLegendCard(this);

                const filter = this.dataset.stockFilter || '';
                const target = "{{ route('admin.inventory') }}";
                const url = filter ? `${target}?stock_filter=${filter}` : target;

                setTimeout(() => {
                    window.location.href = url;
                }, 120);
            });
        });
    }

    async function loadAdminDashboardInventoryOverview() {
        const contentEl = document.getElementById('inventoryOverviewContent');
        if (!contentEl) return;

        if (typeof window.setDashboardLoadingStatus === 'function') {
            window.setDashboardLoadingStatus('Loading inventory overview', 58);
        }

        try {
            const res = await fetch("{{ route('admin.inventory.data') }}", {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const contentType = res.headers.get('content-type') || '';

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            if (!contentType.includes('application/json')) {
                throw new Error('Inventory endpoint did not return JSON');
            }

            const items = await res.json();

            if (!Array.isArray(items)) {
                throw new Error('Inventory response is not an array');
            }

            const total = items.length;
            const medicine = items.filter(item => item.category === 'Medicine').length;
            const supplies = items.filter(item => item.category === 'Supplies').length;
            const inStock = items.filter(item => Number(item.qty) - Number(item.used) > 5).length;
            const lowStock = items.filter(item => {
                const bal = Number(item.qty) - Number(item.used);
                return bal >= 1 && bal <= 5;
            }).length;
            const outStock = items.filter(item => Number(item.qty) - Number(item.used) <= 0).length;

            if (total <= 0) {
                setInventoryOverviewEmptyState('No inventory records yet', false);
                if (typeof window.finishDashboardLoading === 'function') {
                    window.finishDashboardLoading();
                }
                return;
            }

            swapSkeletonContent('inventoryOverviewContent', buildInventoryOverviewHtml({
                total,
                medicine,
                supplies,
                inStock,
                lowStock,
                outStock,
            }));

            showInventoryOverviewContent();

            setTimeout(() => {
                const ctx = document.getElementById('inventoryDonutChart');
                if (!ctx) {
                    if (typeof window.finishDashboardLoading === 'function') {
                        window.finishDashboardLoading();
                    }
                    return;
                }

                if (adminInventoryOverviewChart) {
                    adminInventoryOverviewChart.destroy();
                }

                adminInventoryOverviewChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                        datasets: [{
                            data: [inStock, lowStock, outStock],
                            backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                            hoverBackgroundColor: ['#16a34a', '#d97706', '#dc2626'],
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                bindInventoryLegendClicks();

                console.log('Dashboard inventory overview loaded:', {
                    total,
                    medicine,
                    supplies,
                    inStock,
                    lowStock,
                    outStock
                });

                if (typeof window.finishDashboardLoading === 'function') {
                    window.finishDashboardLoading();
                }
            }, 170);

        } catch (error) {
            console.error('Dashboard inventory overview error:', error);
            setInventoryOverviewEmptyState('Failed to load inventory overview', true);

            if (typeof window.finishDashboardLoading === 'function') {
                window.finishDashboardLoading();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.setDashboardLoadingStatus === 'function') {
            window.setDashboardLoadingStatus('Loading system dashboard', 22);
        }

        loadAdminDashboardInventoryOverview();
    });

    window.addEventListener('focus', function () {
        loadAdminDashboardInventoryOverview();
    });
</script>
@endsection