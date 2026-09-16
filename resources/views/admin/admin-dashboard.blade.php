@extends('layouts.app')

@section('layout-role', 'admin')

@section('title', 'Admin Dashboard')

@section('styles')
    @vite('resources/css/pages/admin/admin-dashboard.css')
@endsection

@section('content')

    @php $logs = $logs ?? collect([]); @endphp

    <main id="mainContent" class="app-page-shell admin-dashboard-page page-enter mode-list">

        <x-dashboard-loading-status />

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <div class="page-greeting">
                        <i id="currentDateIcon" class="fa-solid fa-calendar-day"></i>
                        <span id="currentDate"></span>
                    </div>
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="page-subtitle">Welcome back, Administrator. Here's what's happening today.</p>
                </div>

                <div class="period-pill">
                    <div class="period-item">
                        <span class="period-label"><i class="fa-solid fa-calendar"></i>
                            Semester</span>
                        <span class="period-value">
                            {{ $activePeriod?->semester ?? 'No Active Period' }}
                        </span>
                    </div>
                    <div class="period-divider"></div>
                    <div class="period-item">
                        <span class="period-label"><i class="fa-solid fa-graduation-cap"></i>
                            Academic
                            Year</span>
                        <span class="period-value">
                            {{ $activePeriod?->academic_year ?? 'Not Set' }}
                        </span>
                    </div>
                    <div class="period-divider"></div>
                    <div class="period-item">
                        <span class="period-label"><i class="fa-solid fa-clock"></i> Period
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

        <div class="admin-page-body">

            <div class="stat-grid" id="statCards">
                <div class="stat-card s-crimson">
                    <div class="stat-card-info">
                        <div class="stat-label">Total Patients</div>
                        <div class="stat-value">{{ number_format($totalPatients) }}</div>
                        <div class="stat-footer">
                            <i class="fa-solid fa-user-plus"></i>
                            All registered patients
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <div class="stat-card s-ongoing" data-admin-dashboard-card="appointments-this-month">
                    <div class="stat-card-info">
                        <div class="stat-label">Appointments</div>

                        <div class="stat-value" data-admin-dashboard-counter="appointments-this-month">
                            {{ $appointmentsThisMonth }}
                        </div>

                        <div class="stat-footer">
                            <i class="fa-solid fa-clock"></i>
                            This month
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>

                <div class="stat-card s-approved">
                    <div class="stat-card-info">
                        <div class="stat-label">Documents Issued</div>
                        <div class="stat-value">{{ $documentsThisMonth }}</div>
                        <div class="stat-footer">
                            <i class="fa-solid fa-file-lines"></i>
                            This month
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-file-arrow-up"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">

                <div class="flex flex-col gap-5 min-w-0">

                    <div class="card">

                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                </div>

                                <span class="card-title">
                                    System Logs Overview
                                </span>

                                <x-view-toggle id="dashboardLogsViewToggle" root="#mainContent"
                                    storage-key="admin_dashboard_logs_view" list-view="#dashboardLogsListView"
                                    grid-view="#dashboardLogsGridView" class="ml-auto" />
                            </div>

                            <div class="card-header-right">
                                <a href="{{ route('admin.system_logs') }}" class="card-link">
                                    View All
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="log-stats-row">
                            <div class="log-stat">
                                <div class="log-stat-value">{{ $logThisMonth ?? 0 }}</div>
                                <div class="log-stat-label">This Month</div>
                            </div>
                            <div class="log-stat">
                                <div class="log-stat-value">{{ $logInfo ?? 0 }}</div>
                                <div class="log-stat-label">Views</div>
                            </div>
                            <div class="log-stat">
                                <div class="log-stat-value">{{ $logWarnings ?? 0 }}</div>
                                <div class="log-stat-label">Logins</div>
                            </div>
                            <div class="log-stat">
                                <div class="log-stat-value">{{ $logErrors ?? 0 }}</div>
                                <div class="log-stat-label">Errors</div>
                            </div>
                        </div>

                        @if (($recentLogs ?? collect())->isEmpty())

                            <div id="dashboardLogsEmptyState" class="empty-state-host"></div>
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
                                                            {{ $logDate ? \Carbon\Carbon::parse($logDate)->format('h:i:s A') : '—' }}
                                                        </div>
                                                    </td>
                                                    <td style="font-size:.76rem;">{{ $logDesc }}</td>
                                                    <td>
                                                        <span
                                                            style="font-size:.72rem;font-weight:600;">{{ $logActor }}</span>
                                                        <div
                                                            style="font-size:.65rem;color:#9ca3af;text-transform:capitalize;">
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
                                    @foreach (collect($recentLogs ?? [])->take(6) as $log)
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

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mt-5">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><i class="fa-solid fa-chart-pie"></i></div>
                                    <span class="card-title">GAD Analytics</span>
                                </div>
                                <a href="#" class="card-link">
                                    View
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="dashboard-empty-state-wrap">
                                <div id="dashboardGadEmptyState" class="empty-state-host"></div>
                            </div>
                        </div>

                        <div class="card inventory-overview-card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                                    <span class="card-title">Inventory Overview</span>
                                </div>
                                <a href="{{ route('admin.inventory') }}" class="card-link">
                                    View <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>

                            <div class="inventory-chart-card-body">

                                <div id="inventoryOverviewEmpty" class="empty-state-host"></div>

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
                                                            <div class="skeleton-line"
                                                                style="width: 102px; height: 10px;">
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
                                                            <div class="skeleton-line"
                                                                style="width: 110px; height: 10px;">
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
                                                            <div class="skeleton-line"
                                                                style="width: 122px; height: 10px;">
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

                <div class="flex flex-col gap-5 min-w-0 order-first xl:order-none">

                    <div class="card quick-actions-card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-bolt"></i>
                                </div>
                                <div>
                                    <div class="card-title">Quick Actions</div>
                                    <div class="card-subtitle">Frequently used admin shortcuts</div>
                                </div>
                            </div>
                        </div>

                        <div class="quick-actions-list">
                            <a href="{{ route('admin.reports.ai-generated') }}" class="quick-action quick-action-card">
                                <span class="quick-action-icon">
                                    <i class="fa-solid fa-chart-column"></i>
                                </span>

                                <span class="quick-action-copy">
                                    <span class="quick-action-title">AI Reports</span>
                                    <span class="quick-action-sub">View AI-generated analytics and summaries</span>
                                </span>

                                <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                                <i class="fa-solid fa-chart-column quick-action-bg-icon"></i>
                            </a>

                            <a href="{{ route('admin.user_management') }}" class="quick-action quick-action-card">
                                <span class="quick-action-icon">
                                    <i class="fa-solid fa-user-plus"></i>
                                </span>

                                <span class="quick-action-copy">
                                    <span class="quick-action-title">User Management</span>
                                    <span class="quick-action-sub">Manage accounts and roles</span>
                                </span>

                                <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                                <i class="fa-solid fa-user-plus quick-action-bg-icon"></i>
                            </a>

                            <a href="{{ route('admin.inventory') }}" class="quick-action quick-action-card">
                                <span class="quick-action-icon">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </span>

                                <span class="quick-action-copy">
                                    <span class="quick-action-title">Inventory</span>
                                    <span class="quick-action-sub">Check supplies and stock levels</span>
                                </span>

                                <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                                <i class="fa-solid fa-boxes-stacked quick-action-bg-icon"></i>
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
            document.addEventListener("DOMContentLoaded", function() {
                var modal = document.getElementById("activeAppointmentModal");
                var closeBtn = document.getElementById("closeActiveApptModalBtn");
                if (!modal) return;
                modal.showModal();
                modal.addEventListener('click', function(e) {
                    var box = modal.querySelector('.modal-box');
                    if (box && !box.contains(e.target)) e.preventDefault();
                });
                modal.addEventListener('cancel', function(e) {
                    e.preventDefault();
                });
                if (closeBtn) closeBtn.addEventListener("click", function() {
                    modal.close();
                });
            });
        </script>
    @endif

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

        function setInventoryOverviewEmptyState(
            message,
            isError = false
        ) {
            const emptyEl =
                document.getElementById(
                    'inventoryOverviewEmpty'
                );

            const contentEl =
                document.getElementById(
                    'inventoryOverviewContent'
                );

            if (!emptyEl || !contentEl) {
                return;
            }

            contentEl.style.display = 'none';

            window.EmptyState?.render({
                host: emptyEl,

                icon: isError ?
                    'fa-triangle-exclamation' : 'fa-box-open',

                title: isError ?
                    'Unable to load inventory' : 'No inventory records yet',

                message: message ||
                    (
                        isError ?
                        'Inventory data could not be loaded.' :
                        'Inventory records will appear here once items are added.'
                    )
            });
        }

        function showInventoryOverviewContent() {
            const emptyEl =
                document.getElementById(
                    'inventoryOverviewEmpty'
                );

            const contentEl =
                document.getElementById(
                    'inventoryOverviewContent'
                );

            window.EmptyState?.hide(
                emptyEl
            );

            if (contentEl) {
                contentEl.style.display = 'block';
            }
        }

        function bindInventoryLegendClicks() {
            document.querySelectorAll('.inventory-legend-item').forEach(card => {
                if (card.dataset.bound === '1') return;

                card.dataset.bound = '1';
                card.addEventListener('click', function() {
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

                window.swapSkeletonContent('inventoryOverviewContent', buildInventoryOverviewHtml({
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

                    const rootStyles =
                        getComputedStyle(document.documentElement);

                    const inventoryColors = {
                        inStock: rootStyles
                            .getPropertyValue('--status-completed-solid')
                            .trim(),

                        lowStock: rootStyles
                            .getPropertyValue('--status-pending-solid')
                            .trim(),

                        outStock: rootStyles
                            .getPropertyValue('--status-cancelled-solid')
                            .trim(),

                        card: rootStyles
                            .getPropertyValue('--card')
                            .trim(),
                    };
                    adminInventoryOverviewChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                            datasets: [{
                                data: [
                                    inStock,
                                    lowStock,
                                    outStock
                                ],

                                backgroundColor: [
                                    inventoryColors.inStock,
                                    inventoryColors.lowStock,
                                    inventoryColors.outStock
                                ],

                                hoverBackgroundColor: [
                                    inventoryColors.inStock,
                                    inventoryColors.lowStock,
                                    inventoryColors.outStock
                                ],

                                borderColor: inventoryColors.card,

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

        document.addEventListener('DOMContentLoaded', function() {

            if (typeof window.setDashboardLoadingStatus === 'function') {
                window.setDashboardLoadingStatus(
                    'Loading system dashboard',
                    22
                );
            }

            const logsEmptyState =
                document.getElementById(
                    'dashboardLogsEmptyState'
                );

            if (logsEmptyState) {
                window.EmptyState?.render({
                    host: logsEmptyState,
                    icon: 'fa-clipboard-list',
                    title: 'No system logs yet',
                    message: 'System activity will appear here once records are available.'
                });
            }

            const gadEmptyState =
                document.getElementById(
                    'dashboardGadEmptyState'
                );

            if (gadEmptyState) {
                window.EmptyState?.render({
                    host: gadEmptyState,
                    icon: 'fa-venus-mars',
                    title: 'No GAD analytics data yet',
                    message: 'Gender and development insights will appear once records are available.'
                });
            }

            loadAdminDashboardInventoryOverview();
        });
    </script>
@endsection
