@extends('layouts.app')

@php
$currentLayoutRole = $layoutRole ?? 'admin';
$isDentistLayoutView = $isDentistView ?? $currentLayoutRole === 'dentist';
$currentPageShellClass = trim((string) ($pageShellClass ?? ''));

if ($currentPageShellClass === '') {
$currentPageShellClass = $isDentistLayoutView
? 'app-page-shell dentist-report-page'
: 'app-page-shell';
}

if (!str_contains($currentPageShellClass, 'app-page-shell')) {
$currentPageShellClass = 'app-page-shell ' . $currentPageShellClass;
}

if ($isDentistLayoutView && !str_contains($currentPageShellClass, 'dentist-report-page')) {
$currentPageShellClass .= ' dentist-report-page';
}

$analyticsRoutePrefix = request()->routeIs('dentist.reports*') ? 'dentist' : 'admin';
@endphp

@section('layout-role', $currentLayoutRole)

@section('title', $pageTitle ?? 'Reports & Analytics')

@section('styles')
@vite('resources/css/pages/shared/reports.css')
@if ($isDentistLayoutView && $currentLayoutRole === 'admin')
@vite('resources/css/pages/dentist/dentist-shared.css')
@endif
@endsection

@section('body-class', $isDentistLayoutView ? 'bg-[#F9FAFB]' : 'bg-[#F4F4F4]')

@section('content')

@php
$layoutRole = $currentLayoutRole;

$isAdminView = $isAdminView ?? $layoutRole === 'admin';
$isDentistView = $isDentistLayoutView;

$pageShellClass = $currentPageShellClass;

$pageTitle = $pageTitle ?? 'Reports & Analytics';

$reportStats = $reportStats ?? [];
$reportCharts = $reportCharts ?? [];
$reportInventory = $reportInventory ?? [];
$canGenerateAiReports = auth()->user()?->hasPermission('create_ai_generative_reports') ?? false;
$shouldAutoOpenAiReport = request()->query('open') === 'ai' && $canGenerateAiReports;

/* Admin */
$treatments = $reportStats['treatments'] ?? [
'total' => 0,
'breakdown' => collect(),
];

$appointments = $reportStats['appointments'] ?? [
'total' => 0,
'completed' => 0,
'cancelled' => 0,
'no_show' => 0,
'completion_rate' => 0,
'cancelled_rate' => 0,
'no_show_rate' => 0,
];

$documentRequests = $reportStats['document_requests'] ?? [
'total' => 0,
'pending' => 0,
'approved' => 0,
'rejected' => 0,
'approval_rate' => 0,
'rejection_rate' => 0,
'most_requested' => 'No requests yet',
'most_requested_count' => 0,
];

$inventory = $reportInventory;
$charts = $reportCharts;

/* Dentist */
$patientsThisMonth = $reportStats['patients_this_month'] ?? 0;
$patientsDelta = $reportStats['patients_delta'] ?? null;

$appointmentsToday = $reportStats['appointments_today'] ?? 0;
$appointmentsDelta = $reportStats['appointments_delta'] ?? 0;

$casesThisMonth = $reportStats['cases_this_month'] ?? 0;

$totalAppointmentsThisMonth = $reportStats['total_appointments_this_month'] ?? 0;
$completedAppointments = $reportStats['completed_appointments'] ?? $casesThisMonth;
$cancellationRate = $reportStats['cancellation_rate'] ?? 0;
$avgPatientsPerDay = $reportStats['average_patients_per_day'] ?? 0;
$returningPatients = $reportStats['returning_patients'] ?? 0;
$newPatients = $reportStats['new_patients'] ?? 0;
$lowStockItems = $reportStats['low_stock_items'] ?? 0;
$gadLabels = $reportCharts['gad']['labels'] ?? [];
$gadFemale = $reportCharts['gad']['female'] ?? [];
$gadMale = $reportCharts['gad']['male'] ?? [];
$weekLabels = $reportCharts['weekly']['labels'] ?? [];
$weeklyDatasets = $reportCharts['weekly']['datasets'] ?? [];
$medicineItems = collect($reportInventory['medicine_items'] ?? []);
$suppliesItems = collect($reportInventory['supplies_items'] ?? []);
$lowStockMedicine = collect($reportInventory['low_stock_medicine'] ?? []);
$lowStockSupplies = collect($reportInventory['low_stock_supplies'] ?? []);

$topServices = collect($topServices ?? []);
$periodOptions = $periodOptions ?? [];
$documentTemplates = collect($documentTemplates ?? []);
$customReportTemplates = collect($customReportTemplates ?? []);
@endphp

<main id="mainContent" class="{{ $pageShellClass }} shared-reports-page page-enter mode-list">
    <div class="w-full">

        @if ($isDentistView)
        <div class="dentist-hero page-title-row mb-6">
            <div class="dentist-hero-content">
                <div class="dentist-hero-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <div class="min-w-0">
                    <div class="dentist-hero-eyebrow">
                        <i class="fa-solid fa-tooth"></i>
                        Clinic Insights
                    </div>

                    <h2 class="dentist-hero-title">
                        Reports &amp; Analytics
                    </h2>
                </div>
            </div>

            <div class="report-hero-actions">
                <button type="button" class="ui-btn ui-btn-primary" onclick="openCreateReportModal()">

                    <i class="fa-solid fa-plus"></i>
                    Create Report
                </button>
            </div>
        </div>
        @else
        <div class="page-banner mb-7">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">
                        Reports &amp; Analytics
                    </h1>
                </div>

                @if ($canGenerateAiReports)
                <button type="button" id="openAiReportConfirmModal" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    AI Generated Report
                </button>
                @endif
            </div>
        </div>
        @endif

        @if ($isAdminView)
        <div class="section-label">
            <i class="fa-solid fa-users"></i>
            Patient statistics
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-left">
                    <span class="card-header-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </span>

                    <div>
                        <h2 class="card-title">Patient Growth</h2>
                        <p class="card-subtitle">
                            Monthly new registrations and cumulative patient count
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="chart-legend">
                    <span>
                        <span class="legend-dot" style="background:#378ADD;"></span>
                        Total patients
                    </span>

                    <span>
                        <span class="legend-dot" style="background:#1D9E75;"></span>
                        New patients
                    </span>
                </div>

                <div class="chart-wrap" style="height:260px;">
                    <canvas id="lineChart" role="img" aria-label="Line chart showing patient growth over time">
                    </canvas>
                </div>
            </div>
        </div>

        <hr class="section-divider">
        <div class="section-label">
            <i class="fa-solid fa-tooth"></i>
            Treatment reports
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-left">
                    <span class="card-header-icon">
                        <i class="fa-solid fa-chart-pie"></i>
                    </span>

                    <div>
                        <h2 class="card-title">Treatment Distribution</h2>
                        <p class="card-subtitle">
                            {{ number_format($treatments['total']) }} completed procedures this month
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if (collect($treatments['breakdown'])->count())
                <div class="chart-legend">
                    @php
                    $tColors = ['#378ADD', '#1D9E75', '#D85A30', '#BA7517', '#7F77DD', '#9ca3af'];
                    @endphp

                    @foreach ($treatments['breakdown'] as $i => $proc)
                    <span>
                        <span class="legend-dot" style="background:{{ $tColors[$i] ?? '#9ca3af' }};">
                        </span>

                        {{ $proc['name'] }} {{ $proc['pct'] }}%
                    </span>
                    @endforeach
                </div>

                <div class="chart-wrap" style="height:260px;">
                    <canvas id="pieChart" role="img" aria-label="Doughnut chart of treatment distribution">
                    </canvas>
                </div>
                @else
                <div id="treatmentDistributionEmptyState" class="empty-state-host"></div>
                @endif
            </div>
        </div>

        <hr class="section-divider">
        <div class="section-label">
            <i class="fa-solid fa-calendar-check"></i>
            Appointment reports
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-left">
                    <span class="card-header-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </span>

                    <div>
                        <h2 class="card-title">Appointment Status Overview</h2>
                        <p class="card-subtitle">
                            {{ number_format($appointments['total']) }} appointments recorded this month
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="chart-legend">
                    <span>
                        <span class="legend-dot" style="background:#1D9E75;"></span>
                        Completed {{ $appointments['completed'] }}
                    </span>

                    <span>
                        <span class="legend-dot" style="background:#D85A30;"></span>
                        Cancelled {{ $appointments['cancelled'] }}
                    </span>

                    <span>
                        <span class="legend-dot" style="background:#EF9F27;"></span>
                        No-show {{ $appointments['no_show'] }}
                    </span>
                </div>

                <div class="chart-wrap" style="height:260px;">
                    <canvas id="appointmentStatusChart" role="img"
                        aria-label="Doughnut chart showing appointment status distribution">
                    </canvas>
                </div>
            </div>
        </div>
        <hr class="section-divider">

        <div class="section-label">
            <i class="fa-solid fa-file-signature"></i>
            Document request reports
        </div>

        @php
        $docPending = (int) ($documentRequests['pending'] ?? 0);

        $docApproved = (int) ($documentRequests['approved'] ?? 0);

        $docRejected = (int) ($documentRequests['rejected'] ?? 0);

        $docMostRequested = $documentRequests['most_requested'] ?? 'No requests yet';

        $docMostRequestedCount = (int) ($documentRequests['most_requested_count'] ?? 0);
        @endphp

        <div class="card">
            <div class="card-header">
                <div class="card-header-left">
                    <span class="card-header-icon">
                        <i class="fa-solid fa-file-signature"></i>
                    </span>

                    <div>
                        <h2 class="card-title">Document Request Status</h2>
                        <p class="card-subtitle">
                            Most requested: {{ $docMostRequested }}
                            · {{ number_format($docMostRequestedCount) }}
                            {{ Str::plural('request', $docMostRequestedCount) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="chart-legend">
                    <span>
                        <span class="legend-dot" style="background:#EF9F27;"></span>
                        Pending {{ number_format($docPending) }}
                    </span>

                    <span>
                        <span class="legend-dot" style="background:#1D9E75;"></span>
                        Approved {{ number_format($docApproved) }}
                    </span>

                    <span>
                        <span class="legend-dot" style="background:#D85A30;"></span>
                        Rejected {{ number_format($docRejected) }}
                    </span>
                </div>

                <div class="chart-wrap" style="height:250px;">
                    <canvas id="documentRequestChart" role="img"
                        aria-label="Bar chart showing document request statuses">
                    </canvas>
                </div>
            </div>
        </div>

        @php
        $inventoryItems = collect($inventory['items'] ?? []);
        $inventoryDaysElapsed = max(1, now()->day);

        $inventoryLowStockCount =
        (int) ($inventory['low_stock_count'] ??
        $inventoryItems
        ->filter(function ($item) {
        $inStock = (int) ($item['in_stock'] ?? 0);
        $minLevel = (int) ($item['min_level'] ?? 0);

        return $minLevel > 0 && $inStock < $minLevel; }) ->count());

            $inventoryTotalUsed = $inventoryItems->sum(fn($item) => (int) ($item['used'] ?? 0));
            $inventoryTotalStock = $inventoryItems->sum(fn($item) => (int) ($item['in_stock'] ?? 0));

            $inventoryCriticalCount = $inventoryItems
            ->filter(function ($item) {
            $inStock = (int) ($item['in_stock'] ?? 0);
            $minLevel = (int) ($item['min_level'] ?? 0);

            return $inStock <= 0 || ($minLevel> 0 && $inStock < $minLevel * 0.5); }) ->count();

                    $inventoryReorderUnits = $inventoryItems->sum(function ($item) {
                    $used = (int) ($item['used'] ?? 0);
                    $inStock = (int) ($item['in_stock'] ?? 0);
                    $minLevel = (int) ($item['min_level'] ?? 0);
                    $targetStock = max($minLevel * 2, $used);

                    return max(0, $targetStock - $inStock);
                    });
                    @endphp

                    <hr class="section-divider">
                    <div class="section-label">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        Inventory usage
                    </div>

                    @if ($inventoryLowStockCount > 0)
                    <div class="alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>
                            <strong>{{ $inventoryLowStockCount }}
                                {{ Str::plural('item', $inventoryLowStockCount) }}</strong>
                            below minimum stock threshold — review inventory before next clinic day.
                        </span>
                    </div>
                    @endif

                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-box-open"></i></div>
                                <div>
                                    <h2 class="card-title">Dental Supplies Usage</h2>
                                    <p class="card-subtitle">
                                        Units consumed this month versus available stock
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if ($inventoryItems->count())
                        <div class="table-container report-inventory-table-wrap">
                            <table class="data-table report-inventory-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Used</th>
                                        <th>In stock</th>
                                        <th>Min. level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventoryItems as $item)
                                    <tr>
                                        <td class="item-cell">{{ $item['name'] }}</td>
                                        <td>{{ $item['used'] }}</td>
                                        <td>{{ $item['in_stock'] }}</td>
                                        <td>{{ $item['min_level'] }}</td>
                                        <td>
                                            @if ($item['in_stock'] <= 0) <span
                                                class="status-pill report-stock-pill pill-out">Out of
                                                stock</span>
                                                @elseif($item['in_stock'] < $item['min_level'] * 0.5) <span
                                                    class="status-pill report-stock-pill pill-critical">Critical
                                                    level</span>
                                                    @elseif($item['in_stock'] < $item['min_level']) <span
                                                        class="status-pill report-stock-pill pill-low">Below minimum
                                                        level</span>
                                                        @else
                                                        <span class="status-pill report-stock-pill pill-ok">Sufficient
                                                            stock</span>
                                                        @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div id="adminInventoryEmptyState" class="empty-state-host"></div>
                        @endif
                    </div>

                    <div class="card report-movement-card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                                <div>
                                    <h2 class="card-title">Stock movement & reorder forecast</h2>
                                    <p class="card-subtitle">Estimated movement, daily consumption, days remaining,
                                        and suggested reorder quantity</p>
                                </div>
                            </div>
                        </div>

                        @if ($inventoryItems->count())
                        <div class="stat-grid">

                            <div class="stat-card s-blue">
                                <div class="stat-card-info">
                                    <div class="stat-label">Total Used</div>
                                    <div class="stat-num">
                                        {{ number_format($inventoryTotalUsed) }}
                                    </div>
                                    <div class="stat-footer">This month</div>
                                </div>

                                <div class="stat-icon-wrapper">
                                    <i class="fa-solid fa-arrow-trend-down"></i>
                                </div>
                            </div>

                            <div class="stat-card s-green">
                                <div class="stat-card-info">
                                    <div class="stat-label">Available Stock</div>
                                    <div class="stat-num">
                                        {{ number_format($inventoryTotalStock) }}
                                    </div>
                                    <div class="stat-footer">Units remaining</div>
                                </div>

                                <div class="stat-icon-wrapper">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </div>
                            </div>

                            <div class="stat-card s-red">
                                <div class="stat-card-info">
                                    <div class="stat-label">Critical Items</div>
                                    <div class="stat-num">
                                        {{ number_format($inventoryCriticalCount) }}
                                    </div>
                                    <div class="stat-footer">Need urgent review</div>
                                </div>

                                <div class="stat-icon-wrapper">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                            </div>

                            <div class="stat-card s-amber">
                                <div class="stat-card-info">
                                    <div class="stat-label">Suggested Reorder</div>
                                    <div class="stat-num">
                                        {{ number_format($inventoryReorderUnits) }}
                                    </div>
                                    <div class="stat-footer">Total units</div>
                                </div>

                                <div class="stat-icon-wrapper">
                                    <i class="fa-solid fa-cart-plus"></i>
                                </div>
                            </div>

                        </div>

                        <div class="forecast-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Opening stock is estimated from current stock and monthly usage unless actual
                            opening/restock data is already provided by the inventory records.
                        </div>

                        <div class="table-container report-movement-table-wrap">
                            <table class="data-table report-movement-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Opening stock</th>
                                        <th>Stock in</th>
                                        <th>Used</th>
                                        <th>Ending stock</th>
                                        <th>Avg/day</th>
                                        <th>Days left</th>
                                        <th>Suggested reorder</th>
                                        <th>Reorder status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventoryItems as $item)
                                    @php
                                    $used = (int) ($item['used'] ?? 0);
                                    $inStock = (int) ($item['in_stock'] ?? 0);
                                    $minLevel = (int) ($item['min_level'] ?? 0);

                                    $stockIn =
                                    (int) ($item['stock_in'] ??
                                    ($item['restocked'] ?? ($item['received'] ?? 0)));

                                    $openingStock =
                                    (int) ($item['opening_stock'] ?? max(0, $inStock + $used - $stockIn));

                                    $averageDailyUsage = $used > 0 ? $used / $inventoryDaysElapsed : 0;

                                    $daysRemaining =
                                    $averageDailyUsage > 0
                                    ? (int) floor($inStock / $averageDailyUsage)
                                    : null;

                                    $targetStock = max($minLevel * 2, $used);
                                    $suggestedReorder = max(0, $targetStock - $inStock);

                                    $reorderClass = 'pill-ok';
                                    $reorderLabel = 'Stable';

                                    if ($inStock <= 0) { $reorderClass='pill-out' ; $reorderLabel='Out of stock' ; }
                                        elseif ($minLevel> 0 && $inStock < $minLevel * 0.5) {
                                            $reorderClass='pill-critical' ; $reorderLabel='Critical reorder' ; } elseif
                                            ( ($minLevel> 0 && $inStock < $minLevel) || (!is_null($daysRemaining) &&
                                                $daysRemaining <=7) ) { $reorderClass='pill-low' ;
                                                $reorderLabel='Reorder soon' ; } elseif (!is_null($daysRemaining) &&
                                                $daysRemaining <=14) { $reorderClass='pill-watch' ;
                                                $reorderLabel='Monitor stock' ; } @endphp <tr>
                                                <td class="item-cell" data-label="Item">{{ $item['name'] }}</td>
                                                <td class="forecast-num forecast-opening" data-label="Opening stock">
                                                    {{ number_format($openingStock) }}</td>
                                                <td class="forecast-num forecast-stock-in" data-label="Stock in">
                                                    {{ $stockIn > 0 ? number_format($stockIn) : '—' }}</td>
                                                <td class="forecast-num forecast-used" data-label="Used">
                                                    {{ number_format($used) }}</td>
                                                <td class="forecast-num forecast-ending" data-label="Ending stock">
                                                    {{ number_format($inStock) }}</td>
                                                <td class="forecast-num forecast-average" data-label="Avg/day">
                                                    {{ number_format($averageDailyUsage, 1) }}</td>
                                                <td class="forecast-days" data-label="Days left">
                                                    {{ is_null($daysRemaining)
                                                    ? 'No usage yet'
                                                    : number_format($daysRemaining) . ' ' . Str::plural('day',
                                                    $daysRemaining) }}
                                                </td>
                                                <td class="forecast-num forecast-reorder"
                                                    data-label="Suggested reorder">
                                                    {{ number_format($suggestedReorder) }}</td>
                                                <td data-label="Reorder status">
                                                    <span class="status-pill report-stock-pill {{ $reorderClass }}">{{
                                                        $reorderLabel }}</span>
                                                </td>
                                                </tr>
                                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div id="adminStockMovementEmptyState" class="empty-state-host"></div>
                        @endif
                    </div>

                    <hr class="section-divider">

                    <div class="section-label">
                        <i class="fa-solid fa-chart-column"></i>
                        Monthly treatment activity
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <span class="card-header-icon">
                                    <i class="fa-solid fa-chart-bar"></i>
                                </span>

                                <div>
                                    <h2 class="card-title">Treatments per Month</h2>
                                    <p class="card-subtitle">
                                        Number of completed procedures recorded throughout the year
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="chart-legend">
                                <span>
                                    <span class="legend-dot" style="background:#378ADD;"></span>
                                    Completed procedures
                                </span>
                            </div>

                            <div class="chart-wrap" style="height:260px;">
                                <canvas id="barChart" role="img"
                                    aria-label="Bar chart showing completed treatments per month">
                                </canvas>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if ($isDentistView)
                    <div class="analytics-section-label">
                        <i class="fa-solid fa-chart-line"></i>
                        Clinic Performance Overview
                    </div>

                    <div class="stat-grid">

                        <a href="{{ route('dentist.dentist.patients') }}" class="stat-card s-crimson stat-card-link">
                            <div class="stat-card-info">
                                <div class="stat-label">Patients This Month</div>

                                <div class="stat-num">
                                    {{ $patientsThisMonth }}
                                </div>

                                <div class="stat-footer">
                                    @if (!is_null($patientsDelta))
                                    <span class="{{ $patientsDelta >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        <i class="fa-solid fa-arrow-{{ $patientsDelta >= 0 ? 'up' : 'down' }}"></i>
                                        {{ abs($patientsDelta) }}%
                                    </span>
                                    @else
                                    <span>No data last month</span>
                                    @endif
                                </div>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-users"></i>
                            </div>

                            <span class="stat-card-link-indicator" aria-hidden="true">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </span>
                        </a>

                        <a href="{{ route('dentist.dentist.appointments') }}" class="stat-card s-amber stat-card-link">

                            <div class="stat-card-info">
                                <div class="stat-label">Appointments Today</div>

                                <div class="stat-num">
                                    {{ $appointmentsToday }}
                                </div>

                                <div class="stat-footer">
                                    @if ($appointmentsDelta > 0)
                                    <span class="text-green-600">
                                        <i class="fa-solid fa-arrow-up"></i>
                                        {{ $appointmentsDelta }} more
                                    </span>
                                    @elseif ($appointmentsDelta < 0) <span class="text-red-600">
                                        <i class="fa-solid fa-arrow-down"></i>
                                        {{ abs($appointmentsDelta) }} fewer
                                        </span>
                                        @else
                                        <span>Same as yesterday</span>
                                        @endif
                                </div>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>

                            <span class="stat-card-link-indicator" aria-hidden="true">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </span>
                        </a>

                        <div class="stat-card s-red">
                            <div class="stat-card-info">
                                <div class="stat-label">
                                    Cancellation Rate
                                </div>

                                <div class="stat-num">
                                    {{ $cancellationRate }}%
                                </div>

                                <div class="stat-footer">
                                    Based on recorded appointments
                                </div>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-calendar-xmark"></i>
                            </div>
                        </div>

                        <a href="{{ route('dentist.dentist.inventory') }}" class="stat-card s-amber stat-card-link">
                            <div class="stat-card-info">
                                <div class="stat-label">
                                    Low Stock Items
                                </div>

                                <div class="stat-num">
                                    {{ $lowStockItems }}
                                </div>

                                <div class="stat-footer">
                                    @if ($lowStockItems > 0)
                                    <span class="text-red-600">
                                        <i class="fa-solid fa-circle-exclamation"></i>
                                        Requires reorder
                                    </span>
                                    @else
                                    <span class="text-green-600">
                                        <i class="fa-solid fa-circle-check"></i>
                                        All stocked up
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>

                            <span class="stat-card-link-indicator" aria-hidden="true">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </span>
                        </a>
                    </div>

                    <div class="analytics-section-label">
                        <i class="fa-solid fa-chart-column"></i>
                        Patient and Clinic Insights
                    </div>

                    <div class="card mb-6">
                        <div class="card-header">
                            <div class="card-header-left">
                                <span class="card-header-icon">
                                    <i class="fa-solid fa-chart-simple"></i>
                                </span>

                                <div>
                                    <h3 class="card-title">Monthly Clinic Overview</h3>
                                    <p class="card-subtitle">
                                        Comparison of patient and treatment activity
                                    </p>
                                </div>
                            </div>

                            <span class="metric-chip">Current month</span>
                        </div>

                        <div class="card-body">
                            <div class="relative h-[300px]">
                                <canvas id="clinicOverviewChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="analytics-main-grid">
                        @php
                        $cleanPeriods = collect($periodOptions)
                        ->unique()
                        ->sortByDesc(function ($date) {
                        return \Carbon\Carbon::parse($date);
                        });
                        @endphp

                        <div class="card lg:col-span-1">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <span class="card-header-icon">
                                        <i class="fa-solid fa-chart-column"></i>
                                    </span>

                                    <div>
                                        <h3 class="card-title">GAD Report</h3>
                                        <p class="card-subtitle">
                                            Gender-disaggregated clinic records
                                        </p>
                                    </div>
                                </div>

                                <div class="card-header-right">
                                    <select id="gadPeriodSelect" class="js-custom-select"
                                        data-placeholder="Select period">
                                        @foreach ($cleanPeriods as $opt)
                                        <option value="{{ $opt }}">
                                            {{ $opt }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="report-chart-legend"></div>
                                <div id="gadChartWrap" class="relative flex-1 min-h-[260px]">
                                    <canvas id="gadChart"></canvas>
                                    <div id="gadEmptyState"
                                        class="empty-state-host absolute inset-0 pointer-events-none">
                                    </div>
                                    <div id="gadLoadingState"
                                        class="chart-loading hidden absolute inset-0 pointer-events-none">
                                        <i class="fa-solid fa-spinner"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card lg:col-span-1">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <span class="card-header-icon">
                                        <i class="fa-solid fa-chart-line"></i>
                                    </span>

                                    <div>
                                        <h3 class="card-title">Weekly Cases</h3>
                                        <p class="card-subtitle">
                                            Weekly treatment and appointment activity
                                        </p>
                                    </div>
                                </div>

                                <div class="card-header-right">
                                    <select id="weeklyPeriodSelect" class="js-custom-select"
                                        data-placeholder="Select period">
                                        @foreach ($cleanPeriods as $opt)
                                        <option value="{{ $opt }}">
                                            {{ $opt }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="weeklyChartWrap" class="relative flex-1 min-h-[260px]">
                                    <canvas id="weeklyDentalCasesChart"></canvas>
                                    <div id="weeklyEmptyState"
                                        class="empty-state-host absolute inset-0 pointer-events-none">
                                    </div>

                                    <div id="weeklyLoadingState"
                                        class="chart-loading hidden absolute inset-0 pointer-events-none">
                                        <i class="fa-solid fa-spinner"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <span class="card-header-icon">
                                        <i class="fa-solid fa-user-group"></i>
                                    </span>

                                    <div>
                                        <h3 class="card-title">
                                            Returning vs New Patients
                                        </h3>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="relative h-[280px]">
                                    @if (($returningPatients ?? 0) > 0 || ($newPatients ?? 0) > 0)
                                    <canvas id="patientSegmentChart"></canvas>
                                    @else
                                    <div id="patientSegmentEmptyState" class="empty-state-host"></div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="analytics-secondary-grid">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <span class="card-header-icon">
                                        <i class="fa-solid fa-star"></i>
                                    </span>

                                    <div>
                                        <h3 class="card-title">
                                            Top Dental Services
                                        </h3>
                                    </div>
                                </div>

                                <div class="card-header-right">
                                    <span class="metric-chip">
                                        Top this month
                                    </span>
                                </div>
                            </div>

                            <div class="card-body">
                                @if ($topServices->count() > 0)
                                <div class="service-list">
                                    @foreach ($topServices->take(5)->values() as $index => $service)
                                    <div class="service-row">
                                        <div class="service-meta">
                                            <div class="service-rank">{{ $index + 1 }}</div>
                                            <div class="service-name">
                                                {{ $service->name ?? ($service['name'] ?? 'Service') }}
                                            </div>
                                        </div>
                                        <div class="service-count">
                                            {{ $service->total ?? ($service['total'] ?? 0) }} cases
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div id="topServicesEmptyState" class="empty-state-host"></div>
                                @endif
                            </div>
                        </div>

                        <section class="card quick-actions-card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <span class="card-header-icon">
                                        <i class="fa-solid fa-bolt"></i>
                                    </span>

                                    <div>
                                        <h2 class="card-title">Quick Reports</h2>
                                        <p class="card-subtitle">
                                            Frequently used report shortcuts
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-actions-list">
                                <a href="{{ route('dentist.dentist.report.dental-services') }}"
                                    class="quick-action quick-action-card">
                                    <span class="quick-action-icon">
                                        <i class="fa-solid fa-tooth"></i>
                                    </span>

                                    <span class="quick-action-copy">
                                        <span class="quick-action-title">Dental Services</span>
                                        <span class="quick-action-sub">View and export full service logs</span>
                                    </span>

                                    <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                                    <i class="fa-solid fa-tooth quick-action-bg-icon"></i>
                                </a>

                                <a href="{{ route('dentist.dentist.report.daily-treatment') }}"
                                    class="quick-action quick-action-card">
                                    <span class="quick-action-icon">
                                        <i class="fa-solid fa-notes-medical"></i>
                                    </span>

                                    <span class="quick-action-copy">
                                        <span class="quick-action-title">Daily Treatment Record</span>
                                        <span class="quick-action-sub">Track daily patient treatments</span>
                                    </span>

                                    <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                                    <i class="fa-solid fa-notes-medical quick-action-bg-icon"></i>
                                </a>
                            </div>
                        </section>
                    </div>

                    <section class="card mb-8">
                        <div class="card-header">
                            <div class="card-header-left">
                                <span class="card-header-icon">
                                    <i class="fa-solid fa-file-signature"></i>
                                </span>

                                <div>
                                    <h2 class="card-title">Printable Forms</h2>
                                    <p class="card-subtitle">
                                        Clinic forms and certificates available for printing
                                    </p>
                                </div>
                            </div>

                            <span class="metric-chip">
                                {{ $documentTemplates->count() }}
                                active {{ Str::plural('template', $documentTemplates->count()) }}
                            </span>
                        </div>

                        <div class="card-body">
                            @if ($documentTemplates->count())

                            <div id="printableFormsCarousel" class="printable-forms-carousel" data-printable-carousel>
                                <div class="printable-carousel-toolbar" data-printable-toolbar>
                                    <button type="button" class="card-arrow-btn printable-carousel-btn"
                                        data-printable-prev aria-label="Previous printable forms"
                                        data-tooltip="Previous forms">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>

                                    <span class="printable-carousel-counter" data-printable-counter>
                                        1 / 1
                                    </span>

                                    <button type="button" class="card-arrow-btn printable-carousel-btn"
                                        data-printable-next aria-label="Next printable forms" data-tooltip="Next forms">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>

                                <div class="printable-template-grid" data-printable-grid>
                                    @foreach ($documentTemplates as $template)
                                    <article class="printable-template-item" data-printable-item>
                                        <div class="printable-template-main">
                                            <span class="printable-template-icon">
                                                <i class="fa-solid fa-file-medical"></i>
                                            </span>

                                            <div class="printable-template-copy">
                                                <h3 class="printable-template-title">
                                                    {{ $template->name }}
                                                </h3>

                                                <p class="printable-template-code">
                                                    {{ $template->code ?: 'Template Code N/A' }}
                                                </p>

                                                <div class="printable-template-meta">
                                                    <span class="status-pill s-active">
                                                        {{ Str::headline($template->document_type) }}
                                                    </span>

                                                    <span class="status-pill s-neutral">
                                                        {{ $template->category ?: 'General' }}
                                                    </span>

                                                    <span class="status-pill s-ongoing">
                                                        {{ $template->paper_size ?: 'A4' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="{{ route('dentist.dentist.report.templates.print', $template->id) }}"
                                            target="_blank" rel="noopener" class="ui-btn ui-btn-primary ui-btn-sm"
                                            data-tooltip="Print form" data-tooltip-tone="view"
                                            aria-label="Print {{ $template->name }}">
                                            <i class="fa-solid fa-print"></i>

                                            <span class="printable-print-label">
                                                Print
                                            </span>
                                        </a>
                                    </article>
                                    @endforeach
                                </div>
                            </div>
                            @else
                            <div id="printableFormsEmptyState" class="empty-state-host"></div>
                            @endif
                        </div>
                    </section>

                    <div class="card mb-8">
                        <div class="card-header">
                            <div class="card-header-left">
                                <span class="card-header-icon">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </span>

                                <div>
                                    <h3 class="card-title">
                                        Inventory Analytics
                                    </h3>

                                    <p class="card-subtitle">
                                        Current medicine and dental supply stock overview
                                    </p>
                                </div>
                            </div>

                            <div class="card-header-right">
                                <a href="{{ route('dentist.dentist.inventory') }}"
                                    class="ui-btn ui-btn-secondary ui-btn-sm">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                    <span>Manage Inventory</span>
                                </a>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="report-inventory-grid grid grid-cols-1 md:grid-cols-3 gap-8">

                                <div class="inventory-chart-panel">
                                    <div class="report-panel-heading">
                                        <span class="card-header-icon">
                                            <i class="fa-solid fa-capsules"></i>
                                        </span>

                                        <div>
                                            <h3 class="card-title">
                                                Medicine Stock
                                            </h3>

                                            <p class="card-subtitle">
                                                Available medicine inventory
                                            </p>
                                        </div>
                                    </div>

                                    <div class="report-inventory-content">
                                        @if ($medicineItems->count() > 0)
                                        <canvas id="medicinePieChart"></canvas>
                                        @else
                                        <div id="medicineInventoryEmptyState" class="empty-state-host"></div>
                                        @endif
                                    </div>
                                </div>

                                <div class="report-inventory-panel">
                                    <div class="report-panel-heading">
                                        <span class="card-header-icon">
                                            <i class="fa-solid fa-box-open"></i>
                                        </span>

                                        <div>
                                            <h3 class="card-title">
                                                Medical Supplies
                                            </h3>

                                            <p class="card-subtitle">
                                                Available dental and clinic supplies
                                            </p>
                                        </div>
                                    </div>

                                    <div class="report-inventory-content">
                                        @if ($suppliesItems->count() > 0)
                                        <canvas id="suppliesPieChart"></canvas>
                                        @else
                                        <div id="medicalSuppliesEmptyState" class="empty-state-host"></div>
                                        @endif
                                    </div>
                                </div>

                                <div class="low-stock-alert-card">
                                    <div class="report-panel-heading">
                                        <span class="card-header-icon">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </span>

                                        <div>
                                            <h3 class="card-title">
                                                Low Stock Alerts
                                            </h3>

                                            <p class="card-subtitle">
                                                Items that may require restocking
                                            </p>
                                        </div>
                                    </div>

                                    @if ($lowStockMedicine->count() > 0 || $lowStockSupplies->count() > 0)
                                    <div class="overflow-y-auto max-h-[190px] pr-2 scroll-smooth">

                                        @if ($lowStockMedicine->count() > 0)
                                        @foreach ($lowStockMedicine as $item)
                                        @php
                                        $remaining = $item->qty - $item->used;
                                        $pct = $item->qty > 0 ? round(($remaining / $item->qty) * 100) : 0;
                                        $barClass = $pct <= 15 ? 'bg-red-500' : 'bg-orange-400' ; @endphp <div
                                            class="stock-row">
                                            <div class="stock-name">
                                                <span class="truncate pr-2">{{ $item->name }}</span>
                                                <span class="text-red-600 font-bold text-[10px] whitespace-nowrap">{{
                                                    $remaining }}
                                                    left</span>
                                            </div>
                                            <div class="stock-bar-bg">
                                                <div class="stock-bar-fill {{ $barClass }}" style="width:{{ $pct }}%">
                                                </div>
                                            </div>
                                    </div>
                                    @endforeach
                                    @endif

                                    @if ($lowStockSupplies->count() > 0)
                                    @foreach ($lowStockSupplies as $item)
                                    @php
                                    $remaining = $item->qty - $item->used;
                                    $pct = $item->qty > 0 ? round(($remaining / $item->qty) * 100) : 0;
                                    $barClass = $pct <= 15 ? 'bg-red-500' : 'bg-orange-400' ; @endphp <div
                                        class="stock-row">
                                        <div class="stock-name">
                                            <span class="truncate pr-2">{{ $item->name }}</span>
                                            <span class="text-red-600 font-bold text-[10px] whitespace-nowrap">{{
                                                $remaining }}
                                                left</span>
                                        </div>
                                        <div class="stock-bar-bg">
                                            <div class="stock-bar-fill {{ $barClass }}" style="width:{{ $pct }}%">
                                            </div>
                                        </div>

                                        @endforeach
                                        @endif
                                </div>
                            </div>
                            @else
                            @php
                            $hasAnyInventoryData = $medicineItems->count() > 0 || $suppliesItems->count() > 0;
                            @endphp

                            @if ($hasAnyInventoryData)
                            <div id="inventoryHealthyState" class="empty-state-host"></div>
                            @else
                            <div id="inventoryRecordsEmptyState" class="empty-state-host"></div>
                            @endif
                            @endif
                        </div>

                        @endif

                    </div>
</main>

@if ($isAdminView)
<div id="aiReportConfirmModal" class="ui-modal modal-theme-primary" aria-hidden="true">
    <div class="ui-modal-card modal-md" role="dialog" aria-modal="true" aria-labelledby="aiReportConfirmTitle"
        onclick="event.stopPropagation()">

        <div class="modal-hd">
            <div class="modal-heading">
                <span class="modal-icon">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </span>

                <div class="modal-copy">
                    <h2 id="aiReportConfirmTitle" class="modal-title">
                        Generate AI Report?
                    </h2>

                    <p class="modal-subtitle">
                        Review the report scope before continuing.
                    </p>
                </div>
            </div>

            <button type="button" id="closeAiReportConfirmModal" class="modal-x"
                aria-label="Close AI report confirmation">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-bd">
            <div class="global-confirm-alert">
                <i class="fa-solid fa-circle-info"></i>

                <div>
                    <strong>
                        Generate the latest AI clinic report?
                    </strong>

                    <span>
                        The system will analyze available patient,
                        appointment, treatment, inventory, and
                        document-request records. Review all generated
                        findings before official use.
                    </span>
                </div>
            </div>

            <div id="aiReportReviewField" class="mt-4" data-global-field>
                <div id="aiReportReviewGroup" class="global-choice-group global-choice-group-single">

                    <label class="global-choice-card">
                        <input type="checkbox" id="confirmAiReportReview" class="global-choice-input">

                        <span class="global-choice-indicator">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        <span class="global-choice-copy">
                            <span class="global-choice-title">
                                I understand this report is AI-generated
                            </span>

                            <span class="global-choice-description">
                                The report must be reviewed before it is saved,
                                distributed, or used for clinic decisions.
                            </span>
                        </span>
                    </label>
                </div>

                <div id="aiReportReviewError" class="global-field-error" data-error-for="ai-report-review"
                    aria-live="polite" aria-hidden="true">
                </div>
            </div>
        </div>

        <div class="modal-ft">
            <button type="button" id="cancelAiReportConfirm" class="ui-btn ui-btn-secondary">
                Cancel
            </button>

            <button type="button" id="confirmGenerateAiReport" class="ui-btn ui-btn-primary" aria-busy="false">

                <i class="fa-solid fa-wand-magic-sparkles" data-ai-report-button-icon>
                </i>

                <span data-ai-report-button-label>
                    Generate AI Report
                </span>
            </button>
        </div>
    </div>
</div>
@endif

@if ($isDentistView)
<div id="createReportModal" class="ui-modal" aria-hidden="true">

    <div class="ui-modal-card modal-lg" role="dialog" aria-modal="true" aria-labelledby="createReportTitle"
        onclick="event.stopPropagation()">

        <form id="reportForm" class="modal-card-form" data-global-validation data-form-validation-rule="customReport"
            data-discard-form data-discard-title="Discard custom report?"
            data-discard-subtitle="You have unsaved report details."
            data-discard-message="Closing this modal will remove the report name, selected type, date range, and quantity you entered. Do you want to discard these changes?"
            novalidate>
            <div class="modal-hd">
                <div class="modal-heading">
                    <span class="modal-icon">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </span>

                    <div class="modal-copy">
                        <h2 id="createReportTitle" class="modal-title">
                            Create Custom Report
                        </h2>

                        <p class="modal-subtitle">
                            Fields marked with an asterisk are required.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="createReportModal"
                    aria-label="Close create custom report modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="modal-form-grid">
                    <div class="modal-error-banner hidden" id="formErrorBanner">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>
                            Please complete all required fields before downloading.
                        </span>
                    </div>

                    <div class="modal-form-grid-2">
                        <div class="modal-field" data-global-field>
                            <div class="global-label-row">
                                <label class="global-form-label" for="reportName">
                                    Report Name
                                    <span class="required-mark">*</span>
                                </label>

                                <span id="reportNameCounter" class="char-counter">
                                    0 / 100
                                </span>
                            </div>

                            <div class="global-voice-row" data-voice-field>
                                <div class="global-voice-control">
                                    <input id="reportName" name="report_name" type="text" minlength="3" maxlength="100"
                                        class="form-input-custom" placeholder="e.g. GAD Monthly Report — Dec 2025"
                                        data-field-label="Report Name"
                                        data-required-message="Please enter a report name." data-char-limit="100"
                                        data-char-counter="#reportNameCounter" required>

                                    <div id="reportNameErr" class="global-field-error" data-error-for="reportName"
                                        aria-live="polite" aria-hidden="true">
                                    </div>
                                </div>

                                <x-voice-input target="#reportName" status-id="reportNameVoiceStatus"
                                    label="Voice input for report name" title="Voice input" />
                            </div>
                        </div>

                        <div class="modal-field" data-global-field>
                            <label class="global-form-label" for="reportType">
                                Report Type
                                <span class="required-mark">*</span>
                            </label>

                            <select id="reportType" name="document_template_id" class="js-custom-select"
                                data-placeholder="Select a report type..." data-field-label="Report Type"
                                data-required-message="Please select a report type." required>

                                <option value="" selected disabled>
                                    Select a report type...
                                </option>

                                @forelse ($customReportTemplates as $template)
                                <option value="{{ $template->id }}" data-document-type="{{ $template->document_type }}">
                                    {{ $template->name }}
                                </option>
                                @empty
                                <option value="" disabled>
                                    No active custom report forms available
                                </option>
                                @endforelse
                            </select>

                            <div id="reportTypeErr" class="global-field-error" data-error-for="reportType"
                                aria-live="polite" aria-hidden="true">
                            </div>
                        </div>
                    </div>

                    <div class="modal-field" data-global-field>
                        <div class="modal-field modal-field-full report-date-range-section">
                            <div class="report-date-range-heading">
                                <div>
                                    <div class="report-date-range-title">
                                        Date Range
                                    </div>

                                    <p class="report-date-range-subtitle">
                                        Select a single date or define a custom range.
                                    </p>
                                </div>
                            </div>

                            <div class="report-date-range-grid">

                                <div class="modal-field" data-global-field>
                                    <label class="global-form-label" for="dateFrom">
                                        From
                                        <span class="required-mark">*</span>
                                    </label>

                                    <div class="fp-date-input-wrap">
                                        <input id="dateFrom" name="date_from" type="text"
                                            class="form-input-custom js-flatpickr-date-max-today"
                                            placeholder="Select start date" data-field-label="From Date"
                                            data-required-message="Please select a start date."
                                            data-validation-rule="notFutureDate" readonly required>

                                        <i class="fa-regular fa-calendar fp-date-icon" aria-hidden="true"></i>
                                    </div>

                                    <div id="dateFromErr" class="global-field-error" data-error-for="dateFrom"
                                        aria-live="polite" aria-hidden="true"></div>
                                </div>

                                <div class="modal-field" data-global-field>
                                    <label class="global-form-label" for="dateTo">
                                        To
                                        <span class="modal-helper-text">
                                            (optional)
                                        </span>
                                    </label>

                                    <div class="fp-date-input-wrap">
                                        <input id="dateTo" name="date_to" type="text"
                                            class="form-input-custom js-flatpickr-date-max-today"
                                            placeholder="Select end date" data-field-label="To Date"
                                            data-required-message="Please select an end date."
                                            data-validation-rule="notFutureDate" readonly>

                                        <i class="fa-regular fa-calendar fp-date-icon" aria-hidden="true"></i>
                                    </div>

                                    <div id="dateToErr" class="global-field-error" data-error-for="dateTo"
                                        aria-live="polite" aria-hidden="true"></div>
                                </div>

                            </div>

                            <p class="report-date-range-helper">
                                <i class="fa-solid fa-circle-info"></i>
                                Leave “To” empty to report on a single date.
                            </p>
                        </div>

                        <div class="modal-field modal-field-full" data-global-field>
                            <label class="global-form-label" for="reportQty">
                                Quantity
                                <span class="required-mark">*</span>
                            </label>

                            <div class="modal-inline-control">
                                <div class="modal-inline-main">
                                    <div class="global-number-stepper" data-global-number-stepper>
                                        <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                                            aria-label="Decrease quantity">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>

                                        <input id="reportQty" name="quantity" type="number" value="1" min="1" max="100"
                                            step="1" class="global-number-stepper-input" data-number-stepper-input
                                            data-field-label="Quantity" data-required-message="Please enter a quantity."
                                            data-validation-rule="wholeNumber" required>

                                        <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                            aria-label="Increase quantity">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                </div>

                                <span class="modal-helper-text">
                                    Whole numbers only
                                </span>
                            </div>

                            <div id="reportQtyErr" class="global-field-error" data-error-for="reportQty"
                                aria-live="polite" aria-hidden="true"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="createReportModal">
                    Cancel
                </button>

                <button type="button" id="downloadReportBtn" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-download"></i>
                    Download
                </button>
            </div>
        </form>
    </div>
</div>

<div id="downloadCompleteModal" class="ui-modal modal-theme-success" aria-hidden="true">

    <div class="ui-modal-card modal-sm" role="dialog" aria-modal="true" aria-labelledby="downloadCompleteTitle"
        onclick="event.stopPropagation()">

        <div class="modal-hd">
            <div class="modal-heading">
                <span class="modal-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </span>

                <div class="modal-copy">
                    <h2 id="downloadCompleteTitle" class="modal-title">
                        Download Complete
                    </h2>

                    <p class="modal-subtitle">
                        The custom report was generated successfully.
                    </p>
                </div>
            </div>

            <button type="button" class="modal-x" onclick="closeDownloadModal()"
                aria-label="Close download complete modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-bd">
            <div class="global-confirm-alert">
                <i class="fa-solid fa-file-circle-check"></i>

                <div>
                    <p>Your report is ready.</p>
                    <span>
                        The generated file has been downloaded to your device.
                    </span>
                </div>
            </div>
        </div>

        <div class="modal-ft">
            <button type="button" id="downloadCompleteDoneBtn" class="ui-btn ui-btn-success"
                onclick="closeDownloadModal()">
                <i class="fa-solid fa-check"></i>
                Done
            </button>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if ($isAdminView)
<script>
    document.addEventListener('DOMContentLoaded', async function () {
        try {
            await window.loadChartJs();
        } catch (error) {
            console.error(
                'Admin Reports: Unable to load Chart.js.',
                error
            );

            return;
        }

        const barLabels = @json($charts['bar']['labels'] ?? []);
        const barData = @json($charts['bar']['data'] ?? []);
        const pieLabels = @json($charts['pie']['labels'] ?? []);
        const pieData = @json($charts['pie']['data'] ?? []);
        const lineLabels = @json($charts['line']['labels'] ?? []);
        const lineTotals = @json($charts['line']['totals'] ?? []);
        const lineNew = @json($charts['line']['new_patients'] ?? []);

        const isAdminReportDark = () =>
            document.documentElement
                .getAttribute('data-theme') ===
            'dark' ||
            document.documentElement
                .classList
                .contains('dark');

        const adminChartTextColor = () =>
            isAdminReportDark() ?
                '#C9D1D9' :
                '#374151';

        const adminChartGridColor = () =>
            isAdminReportDark() ?
                'rgba(255,255,255,0.10)' :
                'rgba(148,163,184,0.22)';

        const adminChartBorderColor = () =>
            isAdminReportDark() ?
                '#161B22' :
                '#ffffff';

        const textColor =
            adminChartTextColor();

        const gridColor =
            adminChartGridColor();

        const sharedScales = {
            x: {
                ticks: {
                    color: textColor,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: gridColor
                },
                border: {
                    display: false
                }
            },
            y: {
                ticks: {
                    color: textColor,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: gridColor
                },
                border: {
                    display: false
                }
            }
        };

        const renderEmptyState = (
            host,
            options
        ) => {
            if (
                !window.EmptyState ||
                !document.getElementById(host)
            ) {
                return;
            }

            window.EmptyState.render({
                host: `#${host}`,
                ...options
            });
        };

        renderEmptyState(
            'treatmentDistributionEmptyState', {
            title: 'No treatment data available',
            message: 'Treatment distribution will appear after completed appointments are recorded.',
            icon: 'fa-chart-pie'
        }
        );

        renderEmptyState(
            'adminInventoryEmptyState', {
            title: 'No dental supply records available',
            message: 'There are no inventory usage records to display yet.',
            icon: 'fa-box-open'
        }
        );

        renderEmptyState(
            'adminStockMovementEmptyState', {
            title: 'No stock movement data available',
            message: 'There are no inventory records available for reorder forecasting yet.',
            icon: 'fa-truck-ramp-box'
        }
        );

        const appointmentStatusCanvas =
            document.getElementById('appointmentStatusChart');

        if (appointmentStatusCanvas) {
            new window.Chart(appointmentStatusCanvas, {
                type: 'doughnut',

                data: {
                    labels: [
                        'Completed',
                        'Cancelled',
                        'No-show'
                    ],

                    datasets: [{
                        data: [
                            Number(@json($appointments['completed'] ?? 0)),
                            Number(@json($appointments['cancelled'] ?? 0)),
                            Number(@json($appointments['no_show'] ?? 0))
                        ],

                        backgroundColor: [
                            '#1D9E75',
                            '#D85A30',
                            '#EF9F27'
                        ],

                        borderWidth: 3,
                        borderColor: adminChartBorderColor(),
                        hoverOffset: 7
                    }]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: window.getGlobalChartTooltipOptions?.({
                            label(context) {
                                const value =
                                    Number(context.raw || 0);

                                return `${context.label}: ${value}`;
                            }
                        }) || {}
                    }
                }
            });
        }

        const documentRequestCanvas =
            document.getElementById('documentRequestChart');

        if (documentRequestCanvas) {
            new window.Chart(documentRequestCanvas, {
                type: 'bar',

                data: {
                    labels: [
                        'Pending',
                        'Approved',
                        'Rejected'
                    ],

                    datasets: [{
                        label: 'Requests',

                        data: [
                            Number(@json($docPending)),
                            Number(@json($docApproved)),
                            Number(@json($docRejected))
                        ],

                        backgroundColor: [
                            '#EF9F27',
                            '#1D9E75',
                            '#D85A30'
                        ],

                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 70
                    }]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: window.getGlobalChartTooltipOptions?.({
                            label(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }) || {}
                    },

                    scales: {
                        x: {
                            grid: {
                                display: false
                            },

                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                }
                            },

                            border: {
                                display: false
                            }
                        },

                        y: {
                            beginAtZero: true,

                            ticks: {
                                precision: 0,
                                color: textColor,
                                font: {
                                    size: 11
                                }
                            },

                            grid: {
                                color: gridColor
                            },

                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        const barCanvas = document.getElementById('barChart');

        if (barCanvas) {
            new window.Chart(barCanvas, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                        label: 'Procedures',
                        data: barData,
                        backgroundColor: '#378ADD',
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: window.getGlobalChartTooltipOptions?.({
                            label(context) {
                                return `${context.dataset.label}: ${context.formattedValue}`;
                            }
                        }) || {}
                    },
                    scales: sharedScales
                }
            });
        }

        const pieColors = ['#378ADD', '#1D9E75', '#D85A30', '#BA7517', '#7F77DD', '#9ca3af'];

        if (document.getElementById('pieChart') && pieLabels.length && pieData.length) {
            new window.Chart(document.getElementById('pieChart'), {
                type: 'doughnut',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: pieColors.slice(0, pieLabels.length),
                        borderWidth: 3,
                        borderColor: adminChartBorderColor(),
                        hoverOffset: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: window.getGlobalChartTooltipOptions?.({
                            label(context) {
                                return `${context.label}: ${context.formattedValue}`;
                            }
                        }) || {}
                    }
                }
            });
        }

        const lineCanvas = document.getElementById('lineChart');
        if (lineCanvas) {
            new window.Chart(lineCanvas, {
                type: 'line',
                data: {
                    labels: lineLabels,
                    datasets: [{
                        label: 'Total patients',
                        data: lineTotals,
                        borderColor: '#378ADD',
                        backgroundColor: 'rgba(55,138,221,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#378ADD',
                        borderWidth: 2
                    },
                    {
                        label: 'New patients',
                        data: lineNew,
                        borderColor: '#1D9E75',
                        backgroundColor: 'rgba(29,158,117,0.07)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#1D9E75',
                        borderWidth: 2,
                        borderDash: [5, 3]
                    }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: window.getGlobalChartTooltipOptions?.({
                            label(context) {
                                return `${context.dataset.label}: ${context.formattedValue}`;
                            }
                        }) || {}
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                },
                                maxRotation: 0
                            },
                            grid: {
                                color: gridColor
                            },
                            border: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: gridColor
                            },
                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function applyAdminReportChartTheme() {
            if (!window.Chart) {
                return;
            }

            const textColor =
                adminChartTextColor();

            const gridColor =
                adminChartGridColor();

            const borderColor =
                adminChartBorderColor();

            [
                'lineChart',
                'pieChart',
                'appointmentStatusChart',
                'documentRequestChart',
                'barChart'
            ].forEach(chartId => {
                const chart =
                    window.Chart.getChart?.(
                        chartId
                    );

                if (!chart) {
                    return;
                }

                const scales =
                    chart.options?.scales;

                if (scales) {
                    Object.values(scales)
                        .forEach(scale => {
                            if (scale.ticks) {
                                scale.ticks.color =
                                    textColor;
                            }

                            if (scale.grid) {
                                scale.grid.color =
                                    gridColor;
                            }

                            if (scale.title) {
                                scale.title.color =
                                    textColor;
                            }
                        });
                }

                const legendLabels =
                    chart.options
                        ?.plugins
                        ?.legend
                        ?.labels;

                if (legendLabels) {
                    legendLabels.color =
                        textColor;
                }

                if (
                    chart.config.type ===
                    'doughnut'
                ) {
                    chart.data.datasets
                        .forEach(dataset => {
                            dataset.borderColor =
                                borderColor;
                        });
                }

                chart.update('none');
            });
        }

        window.addEventListener(
            'global-theme-change',
            applyAdminReportChartTheme
        );
    });
</script>

<script>
    (() => {
        const modal =
            document.getElementById(
                'aiReportConfirmModal'
            );

        const openButton =
            document.getElementById(
                'openAiReportConfirmModal'
            );

        const closeButton =
            document.getElementById(
                'closeAiReportConfirmModal'
            );

        const cancelButton =
            document.getElementById(
                'cancelAiReportConfirm'
            );

        const confirmCheckbox =
            document.getElementById(
                'confirmAiReportReview'
            );

        const confirmButton =
            document.getElementById(
                'confirmGenerateAiReport'
            );

        const choiceGroup =
            document.getElementById(
                'aiReportReviewGroup'
            );

        const reportUrl =
            @json(route($analyticsRoutePrefix. '.reports.ai-generated'));

        let isGenerating = false;

        const confirmButtonIcon =
            confirmButton.querySelector(
                '[data-ai-report-button-icon]'
            );

        const confirmButtonLabel =
            confirmButton.querySelector(
                '[data-ai-report-button-label]'
            );

        if (
            !modal ||
            !openButton ||
            !confirmCheckbox ||
            !confirmButton ||
            !choiceGroup
        ) {
            return;
        }

        function setGeneratingState(isLoading) {
            isGenerating = isLoading;

            confirmButton.disabled = isLoading;

            confirmButton.setAttribute(
                'aria-busy',
                isLoading ? 'true' : 'false'
            );

            if (confirmButtonIcon) {
                confirmButtonIcon.className = isLoading ?
                    'fa-solid fa-spinner fa-spin' :
                    'fa-solid fa-wand-magic-sparkles';
            }

            if (confirmButtonLabel) {
                confirmButtonLabel.textContent = isLoading ?
                    'Generating...' :
                    'Generate AI Report';
            }

            if (cancelButton) {
                cancelButton.disabled = isLoading;
            }

            if (closeButton) {
                closeButton.disabled = isLoading;
            }

            confirmCheckbox.disabled = isLoading;
        }

        function clearConfirmationError() {
            if (
                typeof window.clearGlobalGroupError ===
                'function'
            ) {
                window.clearGlobalGroupError(
                    choiceGroup,
                    'ai-report-review'
                );

                return;
            }

            choiceGroup.classList.remove(
                'is-invalid'
            );
        }

        function showConfirmationError() {
            const message =
                'Please confirm that you understand this report is AI-generated.';

            if (
                typeof window.showGlobalGroupError ===
                'function'
            ) {
                window.showGlobalGroupError(
                    choiceGroup,
                    'ai-report-review',
                    message
                );

                return;
            }

            choiceGroup.classList.add(
                'is-invalid'
            );
        }

        function openModal() {
            setGeneratingState(false);

            confirmCheckbox.checked = false;

            clearConfirmationError();

            window.openModal?.(
                'aiReportConfirmModal'
            );

            window.setTimeout(() => {
                confirmCheckbox.focus();
            }, 50);
        }

        function closeModal() {
            if (isGenerating) {
                return;
            }

            clearConfirmationError();

            window.closeModal?.(
                'aiReportConfirmModal'
            );

            openButton?.focus();
        }

        openButton.addEventListener(
            'click',
            openModal
        );

        closeButton?.addEventListener(
            'click',
            closeModal
        );

        cancelButton?.addEventListener(
            'click',
            closeModal
        );

        confirmCheckbox.addEventListener(
            'change',
            () => {
                if (confirmCheckbox.checked) {
                    clearConfirmationError();
                }
            }
        );

        confirmButton.addEventListener(
            'click',
            () => {
                if (isGenerating) {
                    return;
                }

                if (!confirmCheckbox.checked) {
                    showConfirmationError();

                    if (
                        typeof window.focusGlobalInvalidField ===
                        'function'
                    ) {
                        window.focusGlobalInvalidField(
                            confirmCheckbox
                        );
                    } else {
                        confirmCheckbox.focus();
                    }

                    return;
                }

                clearConfirmationError();
                setGeneratingState(true);

                window.setTimeout(() => {
                    window.location.assign(
                        reportUrl
                    );
                }, 100);
            }
        );

        window.addEventListener(
            'pageshow',
            () => {
                setGeneratingState(false);
            }
        );

    })();
</script>
@endif

@if ($isDentistView)
<script>
    function initPrintableFormsCarousel() {
        const carousel =
            document.querySelector(
                '[data-printable-carousel]'
            );

        if (!carousel) {
            return;
        }

        const grid =
            carousel.querySelector(
                '[data-printable-grid]'
            );

        const items =
            Array.from(
                carousel.querySelectorAll(
                    '[data-printable-item]'
                )
            );

        const toolbar =
            carousel.querySelector(
                '[data-printable-toolbar]'
            );

        const previousButton =
            carousel.querySelector(
                '[data-printable-prev]'
            );

        const nextButton =
            carousel.querySelector(
                '[data-printable-next]'
            );

        const counter =
            carousel.querySelector(
                '[data-printable-counter]'
            );

        if (!grid || !items.length) {
            return;
        }

        const ITEMS_PER_PAGE = 2;

        let currentPage = 0;
        let animating = false;

        const mobileQuery =
            window.matchMedia(
                '(max-width: 700px)'
            );

        function isMobile() {
            return mobileQuery.matches;
        }

        function totalPages() {
            return Math.ceil(
                items.length /
                ITEMS_PER_PAGE
            );
        }

        function renderPage() {
            if (!isMobile()) {
                items.forEach(item => {
                    item.hidden = false;
                });

                if (toolbar) {
                    toolbar.hidden = true;
                }

                return;
            }

            const pages =
                totalPages();

            currentPage =
                Math.min(
                    currentPage,
                    pages - 1
                );

            const start =
                currentPage *
                ITEMS_PER_PAGE;

            const end =
                start +
                ITEMS_PER_PAGE;

            items.forEach(
                (item, index) => {
                    item.hidden =
                        index < start ||
                        index >= end;
                }
            );

            if (toolbar) {
                toolbar.hidden =
                    pages <= 1;
            }

            if (counter) {
                counter.textContent =
                    `${currentPage + 1} / ${pages}`;
            }

            if (previousButton) {
                previousButton.disabled =
                    currentPage === 0;
            }

            if (nextButton) {
                nextButton.disabled =
                    currentPage ===
                    pages - 1;
            }
        }

        function changePage(direction) {
            if (
                !isMobile() ||
                animating
            ) {
                return;
            }

            const nextPage =
                currentPage +
                direction;

            const pages =
                totalPages();

            if (
                nextPage < 0 ||
                nextPage >= pages
            ) {
                return;
            }

            const reducedMotion =
                window.matchMedia(
                    '(prefers-reduced-motion: reduce)'
                ).matches;

            const outClass =
                direction > 0 ?
                    'global-carousel-out-left' :
                    'global-carousel-out-right';

            const inClass =
                direction > 0 ?
                    'global-carousel-in-right' :
                    'global-carousel-in-left';

            if (reducedMotion) {
                currentPage =
                    nextPage;

                renderPage();

                return;
            }

            animating = true;

            grid.classList.add(
                outClass
            );

            window.setTimeout(() => {
                grid.classList.remove(
                    outClass
                );

                currentPage =
                    nextPage;

                renderPage();

                grid.classList.add(
                    inClass
                );

                window.setTimeout(() => {
                    grid.classList.remove(
                        inClass
                    );

                    animating = false;
                }, 280);

            }, 180);
        }

        previousButton?.addEventListener(
            'click',
            () => changePage(-1)
        );

        nextButton?.addEventListener(
            'click',
            () => changePage(1)
        );

        mobileQuery.addEventListener(
            'change',
            () => {
                currentPage = 0;
                animating = false;

                grid.classList.remove(
                    'global-carousel-out-left',
                    'global-carousel-out-right',
                    'global-carousel-in-left',
                    'global-carousel-in-right'
                );

                renderPage();
            }
        );

        renderPage();
    }

    const PATIENT_SEGMENT_DATA = {
        labels: ['Returning Patients', 'New Patients'],
        values: [
            Number(@json($returningPatients ?? 0)),
            Number(@json($newPatients ?? 0))
        ]
    };

    const CLINIC_OVERVIEW_DATA = {
        labels: [
            'Cases This Month',
            'Avg. Patients / Day',
            'Returning Patients',
            'New Patients'
        ],
        values: [
            Number(@json($casesThisMonth ?? 0)),
            Number(@json($avgPatientsPerDay ?? 0)),
            Number(@json($returningPatients ?? 0)),
            Number(@json($newPatients ?? 0))
        ]
    };

    const GAD_DATA = {
        labels: @json($gadLabels),
        female: @json($gadFemale),
        male: @json($gadMale)
    };
    const WEEKLY_DATA = {
        labels: @json($weekLabels),
        datasets: @json($weeklyDatasets)
    };
    const MEDICINE_ITEMS = @json($medicineItems);
    const SUPPLIES_ITEMS = @json($suppliesItems);
    const AJAX_GAD_URL = "{{ route('dentist.dentist.report.gad-data') }}";
    const AJAX_WEEKLY_URL = "{{ route('dentist.dentist.report.weekly-data') }}";

    const GAD_REPORT_DOWNLOAD_URL = "{{ route('dentist.dentist.report.gad-download') }}";
    const ANNUAL_CLEARANCE_DOWNLOAD_URL = "{{ route('dentist.dentist.report.annual-clearance-download') }}";
    const DENTAL_CLEARANCE_DOWNLOAD_URL = "{{ route('dentist.dentist.report.dental-clearance-download') }}";
    const DENTAL_SERVICES_DOWNLOAD_URL = "{{ route('dentist.dentist.report.dental-services-download') }}";
    const MEDICINE_INVENTORY_DOWNLOAD_URL = "{{ route('dentist.dentist.report.medicine-inventory-download') }}";
    const DAILY_TREATMENT_RECORD_DOWNLOAD_URL =
        "{{ route('dentist.dentist.report.daily-treatment-record-download') }}";
    const DENTAL_HEALTH_RECORD_DOWNLOAD_URL = "{{ route('dentist.dentist.report.dental-health-record-download') }}";
    const DENTAL_SUPPLIES_INVENTORY_DOWNLOAD_URL =
        "{{ route('dentist.dentist.report.dental-supplies-inventory-download') }}";
    const DENTAL_CASES_DOWNLOAD_URL = "{{ route('dentist.dentist.report.dental-cases-download') }}";
    const MONTHLY_REPORT_DOWNLOAD_URL = "{{ route('dentist.dentist.report.monthly-report-download') }}";
    const CSRF_REFRESH_URL = "{{ route('csrf.token') }}";

    function getCookieValue(name) {
        return document.cookie
            .split('; ')
            .find(row => row.startsWith(name + '='))
            ?.split('=')[1] || '';
    }

    let CSRF_TOKEN =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        decodeURIComponent(getCookieValue('XSRF-TOKEN')) ||
        "{{ csrf_token() }}";

    async function refreshCsrfToken() {
        const response = await fetch(CSRF_REFRESH_URL, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            cache: 'no-store'
        });

        if (!response.ok) return CSRF_TOKEN;

        const data = await response.json();

        if (data.token) {
            CSRF_TOKEN = data.token;
            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', CSRF_TOKEN);
        }

        return CSRF_TOKEN;
    }

    const PIE_COLORS = [
        '#8B0000',
        '#b30000',
        '#cc3333',
        '#e06666',
        '#f4cccc',
        '#d9534f',
        '#c0392b',
        '#922b21',
        '#641e16',
        '#f1948a'
    ];

    const isReportDark = () => document.documentElement.getAttribute('data-theme') === 'dark' || document
        .documentElement.classList.contains('dark');
    const reportChartTextColor = () => isReportDark() ? '#C9D1D9' : '#374151';
    const reportChartGridColor = () => isReportDark() ? 'rgba(255,255,255,0.10)' : 'rgba(148,163,184,0.22)';
    const reportChartBorderColor = () => isReportDark() ? '#161B22' : '#ffffff';

    const REPORT_CHART_IDS = [
        'clinicOverviewChart',
        'gadChart',
        'weeklyDentalCasesChart',
        'patientSegmentChart',
        'medicinePieChart',
        'suppliesPieChart'
    ];

    function applyReportChartTheme() {
        if (!window.Chart) {
            return;
        }

        const textColor =
            reportChartTextColor();

        const gridColor =
            reportChartGridColor();

        const borderColor =
            reportChartBorderColor();

        window.Chart.defaults.color =
            textColor;

        window.Chart.defaults.borderColor =
            gridColor;

        REPORT_CHART_IDS.forEach(chartId => {
            const chart =
                window.Chart.getChart?.(
                    chartId
                );

            if (!chart) {
                return;
            }

            const scales =
                chart.options?.scales;

            if (scales) {
                Object.values(scales)
                    .forEach(scale => {
                        if (scale.ticks) {
                            scale.ticks.color =
                                textColor;
                        }

                        if (scale.grid) {
                            scale.grid.color =
                                gridColor;
                        }

                        if (scale.title) {
                            scale.title.color =
                                textColor;
                        }
                    });
            }

            const legendLabels =
                chart.options
                    ?.plugins
                    ?.legend
                    ?.labels;

            if (legendLabels) {
                legendLabels.color =
                    textColor;
            }

            if (
                chart.config.type ===
                'doughnut'
            ) {
                chart.data.datasets
                    .forEach(dataset => {
                        dataset.borderColor =
                            borderColor;
                    });
            }

            chart.update('none');
        });
    }
    window.addEventListener(
        'global-theme-change',
        () => {
            applyReportChartTheme();
        }
    );
</script>

<script>
    let patientSegmentChartInstance = null;
    let clinicOverviewChartInstance = null;

    function registerCustomReportValidation() {
        if (
            typeof window.registerGlobalFormValidationRule !==
            'function'
        ) {
            return false;
        }

        window.registerGlobalFormValidationRule(
            'customReport',
            form => {
                const typeField =
                    form.querySelector('#reportType');

                const fromField =
                    form.querySelector('#dateFrom');

                const toField =
                    form.querySelector('#dateTo');

                const quantityField =
                    form.querySelector('#reportQty');

                const documentType =
                    typeField?.selectedOptions?.[0]
                        ?.dataset?.documentType || '';

                const automaticQuantity = [
                    'annual_dental_clearance',
                    'dental_clearance'
                ].includes(documentType);

                let valid = true;
                let firstInvalid = null;

                if (
                    fromField?.value &&
                    toField?.value &&
                    toField.value < fromField.value
                ) {
                    window.showFormInputValidationMessage?.(
                        toField,
                        'End date must be the same as or later than the start date.'
                    );

                    valid = false;
                    firstInvalid = toField;
                } else {
                    window.showFormInputValidationMessage?.(
                        toField,
                        ''
                    );
                }

                if (automaticQuantity) {
                    window.showFormInputValidationMessage?.(
                        quantityField,
                        ''
                    );
                } else if (quantityField?.value !== '') {
                    const quantity =
                        Number(quantityField.value);

                    if (
                        !Number.isInteger(quantity) ||
                        quantity < 1 ||
                        quantity > 100
                    ) {
                        window.showFormInputValidationMessage?.(
                            quantityField,
                            'Quantity must be a whole number between 1 and 100.'
                        );

                        valid = false;
                        firstInvalid ||= quantityField;
                    } else {
                        window.showFormInputValidationMessage?.(
                            quantityField,
                            ''
                        );
                    }
                }

                return {
                    valid,
                    firstInvalid
                };
            }
        );

        return true;
    }

    window.addEventListener(
        'global-validation-ready',
        registerCustomReportValidation
    );

    document.addEventListener(
        'DOMContentLoaded',
        registerCustomReportValidation
    );

    function buildClinicOverviewChart() {
        const canvas = document.getElementById('clinicOverviewChart');

        if (!canvas || !window.Chart) {
            return;
        }

        if (clinicOverviewChartInstance) {
            clinicOverviewChartInstance.destroy();
        }

        clinicOverviewChartInstance = new window.Chart(canvas, {
            type: 'bar',

            data: {
                labels: CLINIC_OVERVIEW_DATA.labels,

                datasets: [{
                    label: 'Recorded value',
                    data: CLINIC_OVERVIEW_DATA.values,

                    backgroundColor: [
                        'rgba(139, 0, 0, 0.88)',
                        'rgba(37, 99, 235, 0.78)',
                        'rgba(5, 150, 105, 0.78)',
                        'rgba(217, 119, 6, 0.78)'
                    ],

                    borderColor: [
                        '#8B0000',
                        '#2563EB',
                        '#059669',
                        '#D97706'
                    ],

                    borderWidth: 1,
                    borderRadius: 9,
                    borderSkipped: false,
                    maxBarThickness: 54
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    intersect: false,
                    mode: 'index'
                },

                scales: {
                    x: {
                        grid: {
                            display: false
                        },

                        ticks: {
                            font: {
                                
                                size: 11,
                                weight: '600'
                            }
                        }
                    },

                    y: {
                        beginAtZero: true,

                        ticks: {
                            precision: 0,
                            font: {
                                
                                size: 11
                            }
                        },

                        grid: {
                            color: reportChartGridColor()
                        }
                    }
                },

                plugins: {
                    legend: {
                        display: false
                    },

                    tooltip: window.getGlobalChartTooltipOptions?.({
                        label(context) {
                            return `${context.label}: ${context.raw}`;
                        }
                    }) || {}
                }
            }
        });
    }

    function buildPatientSegmentChart() {
        const total = PATIENT_SEGMENT_DATA.values.reduce((a, b) => a + b, 0);
        const canvas = document.getElementById('patientSegmentChart');

        if (total === 0 || !canvas || !window.Chart) return;

        if (patientSegmentChartInstance) {
            patientSegmentChartInstance.destroy();
            patientSegmentChartInstance = null;
        }

        patientSegmentChartInstance = new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: PATIENT_SEGMENT_DATA.labels,
                datasets: [{
                    data: PATIENT_SEGMENT_DATA.values,
                    backgroundColor: ['#8B0000', '#FCA5A5'],
                    borderColor: reportChartBorderColor(),
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',

                        labels: {
                            font: {
                                
                                size: 11
                            },

                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 14
                        }
                    },

                    tooltip: window.getGlobalChartTooltipOptions?.({
                        label(context) {
                            return `${context.label}: ${context.formattedValue}`;
                        }
                    }) || {}
                }
            }
        });
    }

    function openCreateReportModal() {
        resetCreateReportForm();

        const modal = document.getElementById('createReportModal');

        window.initCustomSelects?.(modal);

        modal?.querySelectorAll('.custom-select').forEach(wrapper => {
            window.syncCustomSelect?.(wrapper);
        });

        window.openModal?.('createReportModal');
        window.initGlobalVoiceInputs?.(modal);

        document.dispatchEvent(
            new CustomEvent('voice:refresh', {
                detail: {
                    root: modal
                }
            })
        );
    }

    function forceCloseCreateReportModal() {
        window.closeModal?.('createReportModal', {
            force: true
        });

        window.setTimeout(() => {
            resetCreateReportForm();
        }, 180);
    }

    function closeCreateModal() {
        const modal = document.getElementById('createReportModal');

        if (!modal) return;

        if (window.DiscardChanges) {
            window.DiscardChanges.confirmClose(
                modal,
                forceCloseCreateReportModal
            );

            return;
        }

        forceCloseCreateReportModal();
    }

    function openDownloadCompleteModal() {
        window.openModal?.('downloadCompleteModal');
    }

    function closeDownloadModal() {
        window.closeModal?.('downloadCompleteModal');
    }

    window.openCreateReportModal = openCreateReportModal;
    window.closeCreateModal = closeCreateModal;
    window.closeDownloadModal = closeDownloadModal;

    function resetCreateReportForm() {
        const form = document.getElementById('reportForm');

        if (!form) return;

        form.reset();

        const quantityField =
            document.getElementById(
                'reportQty'
            );

        if (quantityField) {
            quantityField.value = '1';

            quantityField.dispatchEvent(
                new Event(
                    'input', {
                    bubbles: true
                }
                )
            );
        }

        ['dateFrom', 'dateTo'].forEach(id => {
            const input = document.getElementById(id);

            input?._flatpickr?.clear(false);
        });

        form.querySelectorAll(
            'input, textarea, select'
        ).forEach(field => {
            field.classList.remove(
                'is-invalid',
                'is-valid'
            );

            field.removeAttribute('aria-invalid');
            field.removeAttribute('aria-describedby');
            field.setCustomValidity('');
        });

        form.querySelectorAll(
            '.custom-select'
        ).forEach(wrapper => {
            wrapper.classList.remove('is-invalid');
            window.syncCustomSelect?.(wrapper);
        });

        form.querySelectorAll(
            '.global-field-error'
        ).forEach(error => {
            error.classList.remove('show');
            error.innerHTML = '';
            error.setAttribute('aria-hidden', 'true');
        });

        const banner =
            document.getElementById('formErrorBanner');

        banner?.classList.add('hidden');

        if (quantityField) {
            quantityField.disabled = false;
            quantityField.placeholder = '1–100';
        }

        const counter =
            document.getElementById('reportNameCounter');

        if (counter) {
            counter.textContent = '0 / 100';
            counter.className = 'char-counter';
        }

        document
            .querySelectorAll(
                '.flatpickr-calendar.open'
            )
            .forEach(calendar => {
                calendar.classList.remove('open');
            });

        window.DiscardChanges?.markNotSubmitting(form);
        window.DiscardChanges?.captureForm(form);
    }

    let gadChartInstance = null,
        weeklyChartInstance = null;

    function setReportChartState(
        canvasId,
        emptyId,
        loadingId,
        state,
        emptyOptions = {}
    ) {
        const canvas =
            document.getElementById(canvasId);

        const empty =
            document.getElementById(emptyId);

        const loading =
            document.getElementById(loadingId);

        if (!canvas || !empty || !loading) {
            return;
        }

        loading.classList.add('hidden');
        loading.classList.remove('flex');
        loading.style.display = 'none';

        window.EmptyState?.hide(empty);

        if (state === 'empty') {
            canvas.style.display = 'none';

            window.EmptyState?.render({
                host: empty,
                title: emptyOptions.title ||
                    'No records found',
                message: emptyOptions.message ||
                    '',
                icon: emptyOptions.icon ||
                    'fa-folder-open'
            });

            return;
        }

        if (state === 'loading') {
            canvas.style.display = 'none';

            loading.classList.remove('hidden');
            loading.classList.add('flex');
            loading.style.display = 'flex';

            return;
        }
        canvas.style.display = 'block';
    }

    function renderReportEmptyState(host, options) {
        const element =
            document.querySelector(host);

        if (!element || !window.EmptyState) {
            return;
        }

        window.EmptyState.render({
            host: element,
            ...options
        });
    }

    function renderDentistReportEmptyStates() {
        if (!window.EmptyState) {
            return;
        }

        renderReportEmptyState(
            '#inventoryHealthyState', {
            title: 'Stock levels are good',
            message: 'No items require immediate restocking.',
            icon: 'fa-circle-check',
            className: 'empty-state-compact'
        }
        );

        renderReportEmptyState(
            '#inventoryRecordsEmptyState', {
            title: 'No inventory records yet',
            message: 'Add medicine or supply items to monitor low stock alerts.',
            icon: 'fa-boxes-stacked',
            className: 'empty-state-compact',

            actionHtml: `
            <button
                type="button"
                class="ui-btn ui-btn-primary ui-btn-sm"
                onclick="window.location.assign(
                    '{{ route('dentist.dentist.inventory') }}'
                )"
            >
                <i class="fa-solid fa-plus"></i>
                Add Item
            </button>
        `
        }
        );
        renderReportEmptyState(
            '#printableFormsEmptyState', {
            title: 'No active document templates',
            message: 'Active clinic forms and certificates will appear here.',
            icon: 'fa-file-circle-xmark'
        }
        );

        renderReportEmptyState(
            '#topServicesEmptyState', {
            title: 'No service data available',
            message: 'Top performed treatments will appear here.',
            icon: 'fa-tooth'
        }
        );

        renderReportEmptyState(
            '#medicineInventoryEmptyState', {
            title: 'No medicine stock available',
            message: 'Add medicines to track inventory levels.',
            icon: 'fa-pills',
            className: 'empty-state-compact',

            actionHtml: `
            <button
                type="button"
                class="ui-btn ui-btn-primary ui-btn-sm"
                onclick="window.location.assign(
                    '{{ route('dentist.dentist.inventory') }}'
                )"
            >
                <i class="fa-solid fa-plus"></i>
                Add Medicine
            </button>
        `
        }
        );

        renderReportEmptyState(
            '#patientSegmentEmptyState', {
            title: 'No patient segment data',
            message: 'Returning and new patient insights will appear here.',
            icon: 'fa-user-group'
        }
        );

        renderReportEmptyState(
            '#medicalSuppliesEmptyState', {
            title: 'No medical supplies found',
            message: 'Add supplies to monitor usage and stock.',
            icon: 'fa-box-open',
            className: 'empty-state-compact',

            actionHtml: `
            <button
                type="button"
                class="ui-btn ui-btn-primary ui-btn-sm"
                onclick="window.location.assign(
                    '{{ route('dentist.dentist.inventory') }}'
                )"
            >
                <i class="fa-solid fa-plus"></i>
                Add Supply
            </button>
        `
        }
        );
    }

    function showGadEmpty() {
        setReportChartState(
            'gadChart',
            'gadEmptyState',
            'gadLoadingState',
            'empty', {
            title: 'No records found',
            message: 'No GAD records are available for the selected period.',
            icon: 'fa-chart-column'
        }
        );
    }

    function showGadLoading() {
        setReportChartState('gadChart', 'gadEmptyState', 'gadLoadingState', 'loading');
    }

    function showGadChart() {
        setReportChartState('gadChart', 'gadEmptyState', 'gadLoadingState', 'chart');
    }

    function showWeeklyEmpty() {
        setReportChartState(
            'weeklyDentalCasesChart',
            'weeklyEmptyState',
            'weeklyLoadingState',
            'empty', {
            title: 'No appointment data',
            message: 'No weekly treatment or appointment activity is available for the selected period.',
            icon: 'fa-chart-line'
        }
        );
    }

    function showWeeklyLoading() {
        setReportChartState('weeklyDentalCasesChart', 'weeklyEmptyState', 'weeklyLoadingState', 'loading');
    }

    function showWeeklyChart() {
        setReportChartState('weeklyDentalCasesChart', 'weeklyEmptyState', 'weeklyLoadingState', 'chart');
    }

    function buildGadChart(labels, female, male) {
        const canvas = document.getElementById('gadChart');

        if (!canvas || !window.Chart) {
            return;
        }

        if (gadChartInstance) {
            gadChartInstance.destroy();
            gadChartInstance = null;
        }
        gadChartInstance = new window.Chart(document.getElementById('gadChart'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Female',
                    data: female,
                    backgroundColor: '#EC4899',
                    borderColor: '#EC4899',
                    borderRadius: 4
                },
                {
                    label: 'Male',
                    data: male,
                    backgroundColor: '#60A5FA',
                    borderColor: '#60A5FA',
                    borderRadius: 4
                }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                
                                size: 11
                            },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: window.getGlobalChartTooltipOptions?.({
                        label(context) {
                            return `${context.dataset.label}: ${context.parsed.x} cases`;
                        }
                    }) || {}
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [4, 4],
                            color: reportChartGridColor()
                        },
                        title: {
                            display: true,
                            text: 'Number of Cases',
                            font: {
                                
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                            color: reportChartGridColor()
                        },
                        ticks: {
                            font: {
                                
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    function buildWeeklyChart(labels, datasets) {
        const canvas = document.getElementById('weeklyDentalCasesChart');

        if (!canvas || !window.Chart) {
            return;
        }

        if (weeklyChartInstance) {
            weeklyChartInstance.destroy();
            weeklyChartInstance = null;
        }
        weeklyChartInstance = new window.Chart(document.getElementById('weeklyDentalCasesChart'), {
            type: 'line',
            data: {
                labels,
                datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                
                                size: 11
                            },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: window.getGlobalChartTooltipOptions?.({
                        label(context) {
                            return `${context.dataset.label}: ${context.parsed.y} cases`;
                        }
                    }) || {}
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            color: reportChartGridColor()
                        },
                        ticks: {
                            font: {
                                
                                size: 10
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [4, 4],
                            color: reportChartGridColor()
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                
                                size: 10
                            }
                        },
                        title: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function makePieChart(canvasId, items) {
        const canvas = document.getElementById(canvasId);

        if (!canvas || !window.Chart || !Array.isArray(items) || items.length === 0) return;

        new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: items.map(i => i.name),
                datasets: [{
                    data: items.map(i => Math.max(0, Number(i.qty || 0) - Number(i.used || 0))),
                    backgroundColor: PIE_COLORS.slice(0, items.length),
                    borderWidth: 2,
                    borderColor: reportChartBorderColor()
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                
                                size: 10
                            },
                            usePointStyle: true,
                            boxWidth: 6,
                            padding: 12
                        }
                    },
                    tooltip: window.getGlobalChartTooltipOptions?.({
                        label(context) {
                            return `${context.label}: ${context.parsed} remaining`;
                        }
                    }) || {}
                }
            }
        });
    }

    async function reloadGadChart(period) {
        showGadLoading();
        try {
            const res = await fetch(`${AJAX_GAD_URL}?period=${encodeURIComponent(period)}`);
            const data = await res.json();
            if (data.empty) {
                showGadEmpty();
                return;
            }
            showGadChart();
            buildGadChart(data.labels, data.female, data.male);
        } catch (e) {
            showGadEmpty();
        }
    }
    async function reloadWeeklyChart(period) {
        showWeeklyLoading();
        try {
            const res = await fetch(`${AJAX_WEEKLY_URL}?period=${encodeURIComponent(period)}`);
            const data = await res.json();
            if (data.empty || !data.datasets || data.datasets.length === 0) {
                showWeeklyEmpty();
                return;
            }
            showWeeklyChart();
            buildWeeklyChart(data.labels, data.datasets);
        } catch (e) {
            showWeeklyEmpty();
        }
    }

    function initReportCharts() {
        if (!window.Chart) {
            showGadEmpty();
            showWeeklyEmpty();
            return;
        }

        const gadFemale = Array.isArray(GAD_DATA.female) ? GAD_DATA.female : [];
        const gadMale = Array.isArray(GAD_DATA.male) ? GAD_DATA.male : [];

        const gadHasData =
            gadFemale.reduce((a, b) => a + Number(b || 0), 0) +
            gadMale.reduce((a, b) => a + Number(b || 0), 0) > 0;

        if (gadHasData) {
            showGadChart();
            buildGadChart(GAD_DATA.labels || [], gadFemale, gadMale);
        } else {
            showGadEmpty();
        }

        if (WEEKLY_DATA.datasets && WEEKLY_DATA.datasets.length > 0) {
            showWeeklyChart();
            buildWeeklyChart(WEEKLY_DATA.labels || [], WEEKLY_DATA.datasets);
        } else {
            showWeeklyEmpty();
        }

        makePieChart('medicinePieChart', Array.isArray(MEDICINE_ITEMS) ? MEDICINE_ITEMS : []);
        makePieChart('suppliesPieChart', Array.isArray(SUPPLIES_ITEMS) ? SUPPLIES_ITEMS : []);

        buildPatientSegmentChart();
        buildClinicOverviewChart();
    }

    document.addEventListener('DOMContentLoaded', async function () {

        initPrintableFormsCarousel();

        window.initCustomSelects?.(document);

        if (window.EmptyState) {
            renderDentistReportEmptyStates();
        } else {
            window.addEventListener(
                'load',
                renderDentistReportEmptyStates, {
                once: true
            }
            );
        }

        if (window.initGlobalVoiceInputs) {
            window.initGlobalVoiceInputs(document.getElementById('createReportModal'));
        }

        try {
            await window.loadChartJs();

            applyReportChartTheme();
            initReportCharts();
        } catch (error) {
            console.error(
                'Dentist Reports: Unable to load Chart.js.',
                error
            );

            showGadEmpty();
            showWeeklyEmpty();
        }

        const gadPeriodSelect =
            document.getElementById('gadPeriodSelect');

        const weeklyPeriodSelect =
            document.getElementById('weeklyPeriodSelect');

        gadPeriodSelect?.addEventListener('change', function () {
            reloadGadChart(this.value);
        });

        weeklyPeriodSelect?.addEventListener('change', function () {
            reloadWeeklyChart(this.value);
        });

        const reportForm =
            document.getElementById('reportForm');

        const reportType =
            document.getElementById('reportType');

        const quantityField =
            document.getElementById('reportQty');

        const downloadButton =
            document.getElementById('downloadReportBtn');

        const formBanner =
            document.getElementById('formErrorBanner');

        const endpointMap = {
            dpt_emergency: "{{ route('dentist.dentist.report.dpt-download') }}",
            dpt_non_emergency: "{{ route('dentist.dentist.report.dpt-download') }}",
            gad_report: GAD_REPORT_DOWNLOAD_URL,
            annual_dental_clearance: ANNUAL_CLEARANCE_DOWNLOAD_URL,
            dental_clearance: DENTAL_CLEARANCE_DOWNLOAD_URL,
            dental_services: DENTAL_SERVICES_DOWNLOAD_URL,
            medicine_inventory: MEDICINE_INVENTORY_DOWNLOAD_URL,
            daily_treatment_record: DAILY_TREATMENT_RECORD_DOWNLOAD_URL,
            dental_health_record: DENTAL_HEALTH_RECORD_DOWNLOAD_URL,
            dental_supplies_inventory: DENTAL_SUPPLIES_INVENTORY_DOWNLOAD_URL,
            dental_cases: DENTAL_CASES_DOWNLOAD_URL,
            monthly_report: MONTHLY_REPORT_DOWNLOAD_URL
        };

        function showReportBanner(message) {
            if (!formBanner) return;

            formBanner.innerHTML = `
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>${message}</span>
    `;

            formBanner.classList.remove('hidden');
        }

        function hideReportBanner() {
            formBanner?.classList.add('hidden');
        }

        function syncReportQuantityState() {
            if (!reportType || !quantityField) return;

            const documentType =
                reportType.selectedOptions?.[0]
                    ?.dataset?.documentType || '';

            const automaticQuantity = [
                'annual_dental_clearance',
                'dental_clearance'
            ].includes(documentType);

            quantityField.disabled = automaticQuantity;
            quantityField.required = !automaticQuantity;
            quantityField.placeholder =
                automaticQuantity ? 'Auto' : '1–100';

            if (automaticQuantity) {
                quantityField.value = '';
            }

            quantityField
                .closest('[data-global-number-stepper]')
                ?.querySelectorAll('[data-number-step]')
                .forEach(button => {
                    button.disabled = automaticQuantity;
                });

            window.showFormInputValidationMessage?.(
                quantityField,
                ''
            );
        }

        reportType?.addEventListener('change', function () {
            window.validateFormInputField?.(this);
            syncReportQuantityState();
            hideReportBanner();
        });

        reportForm?.addEventListener('input', hideReportBanner);
        reportForm?.addEventListener('change', hideReportBanner);

        downloadButton?.addEventListener('click', async function () {
            const validation =
                window.validateGlobalForm?.(reportForm) || {
                    valid: reportForm?.checkValidity?.() ?? true,
                    firstInvalid: null
                };

            if (!validation.valid) {
                showReportBanner(
                    'Please correct the highlighted fields before downloading.'
                );

                window.focusGlobalInvalidField?.(
                    validation.firstInvalid
                );

                return;
            }

            hideReportBanner();

            const reportName =
                document.getElementById('reportName')
                    .value
                    .trim();

            const documentType =
                reportType.selectedOptions?.[0]
                    ?.dataset?.documentType || '';

            const automaticQuantity = [
                'annual_dental_clearance',
                'dental_clearance'
            ].includes(documentType);

            const quantity = automaticQuantity ?
                1 :
                Number(quantityField.value);

            const endpoint =
                endpointMap[documentType];

            if (!endpoint) {
                showReportBanner(
                    'This selected form is not yet connected to an official PDF download.'
                );

                return;
            }

            const originalHtml = this.innerHTML;

            this.disabled = true;
            this.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Generating...
    `;

            try {
                window.DiscardChanges?.markSubmitting(
                    reportForm
                );

                const csrfToken =
                    await refreshCsrfToken();

                const formData = new FormData();

                formData.append('_token', csrfToken);
                formData.append('report_name', reportName);
                formData.append(
                    'document_template_id',
                    reportType.value
                );
                formData.append(
                    'date_from',
                    document.getElementById('dateFrom').value
                );
                formData.append(
                    'quantity',
                    String(quantity)
                );

                const dateTo =
                    document.getElementById('dateTo').value;

                if (dateTo) {
                    formData.append('date_to', dateTo);
                }

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/pdf, application/json'
                    },
                    body: formData,
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    let message =
                        `Unable to generate the report. Server returned ${response.status}.`;

                    const contentType =
                        response.headers.get('content-type') || '';

                    if (contentType.includes('application/json')) {
                        const data = await response.json();

                        message =
                            data.message ||
                            Object.values(data.errors || {})
                                .flat()
                                .find(Boolean) ||
                            message;
                    }

                    throw new Error(message);
                }

                const contentType =
                    response.headers.get('content-type') || '';

                if (
                    !contentType
                        .toLowerCase()
                        .includes('application/pdf')
                ) {
                    throw new Error(
                        'The server did not return a valid PDF file.'
                    );
                }

                const blob = await response.blob();
                const downloadUrl =
                    URL.createObjectURL(blob);

                let fileName =
                    `${reportName.replace(
                        /[^A-Za-z0-9_-]/g,
                        '_'
                    )}.pdf`;

                const disposition =
                    response.headers.get(
                        'Content-Disposition'
                    ) || '';

                const fileNameMatch =
                    disposition.match(
                        /filename="?([^"]+)"?/i
                    );

                if (fileNameMatch?.[1]) {
                    fileName = fileNameMatch[1];
                }

                const link =
                    document.createElement('a');

                link.href = downloadUrl;
                link.download = fileName;

                document.body.appendChild(link);
                link.click();
                link.remove();

                window.setTimeout(() => {
                    URL.revokeObjectURL(downloadUrl);
                }, 30000);

                forceCloseCreateReportModal();
                openDownloadCompleteModal();

            } catch (error) {
                window.DiscardChanges?.markNotSubmitting(
                    reportForm
                );

                showReportBanner(
                    error.message ||
                    'Unable to generate the report. Please try again.'
                );
            } finally {
                this.disabled = false;
                this.innerHTML = originalHtml;
            }
        });

        syncReportQuantityState();

        if (
            @json($shouldAutoOpenAiReport) &&
            typeof openAiReportConfirmModal === 'function'
        ) {
            window.setTimeout(() => {
                openAiReportConfirmModal();
            }, 150);
        }

    });
</script>
@endif
@endsection
