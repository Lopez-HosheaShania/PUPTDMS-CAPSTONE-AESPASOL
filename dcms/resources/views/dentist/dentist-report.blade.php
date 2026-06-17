@extends('layouts.dentist')

@section('title', 'Reports & Analytics | PUP Taguig Dental Clinic')

@section('content')

@php
$notifications = collect($notifications ?? []);
$notifCount = $notifications->count();

$cancellationRate =
$appointmentsToday > 0
? round((($cancelledAppointments ?? 0) / max($totalAppointmentsThisMonth ?? 1, 1)) * 100)
: $cancellationRate ?? 0;

$avgPatientsPerDay = $avgPatientsPerDay ?? 0;
$returningPatients = $returningPatients ?? 0;
$newPatients = $newPatients ?? 0;

$topServices = collect($topServices ?? []);
@endphp

<main id="mainContent" class="dentist-page-shell dentist-report-page page-enter mode-list">
    <div class="w-full">

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

                    <div class="report-hero-meta">
                        <span class="summary-tag">
                            <span class="summary-tag-dot bg-red-700"></span>
                            {{ $totalAppointmentsThisMonth ?? 0 }} monthly appointments
                        </span>
                        <span class="summary-tag">
                            <span class="summary-tag-dot bg-green-500"></span>
                            {{ $completedAppointments ?? 0 }} completed
                        </span>
                        <span class="summary-tag">
                            <span class="summary-tag-dot bg-orange-500"></span>
                            Updated {{ now()->format('M d, Y h:i A') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="report-hero-actions">
                <button onclick="openCreateReportModal()" class="report-hero-btn" type="button">
                    <i class="fa-solid fa-plus"></i>
                    Create Report
                </button>
            </div>
        </div>

        <div class="analytics-section-label">
            <i class="fa-solid fa-chart-line"></i>
            Clinic Performance Overview
        </div>

        <div class="kpi-grid-layout report-kpi-grid mb-8">

            <a href="{{ route('dentist.dentist.patients') }}" class="kpi-card kpi-card-patients group">
                <div class="kpi-icon kpi-icon-patients">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="kpi-value">{{ $patientsThisMonth }}</div>
                    <div class="kpi-label">Patients This Month</div>

                    @if (!is_null($patientsDelta))
                    <div class="kpi-delta {{ $patientsDelta >= 0 ? 'up' : 'down' }}">
                        <i class="fa-solid fa-arrow-{{ $patientsDelta >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($patientsDelta) }}%
                    </div>
                    @else
                    <div class="kpi-delta neutral">No data last month</div>
                    @endif
                </div>

                <i class="fa-solid fa-chevron-right kpi-arrow"></i>
            </a>

            <a href="{{ route('dentist.dentist.appointments') }}" class="kpi-card kpi-card-appointments group">
                <div class="kpi-icon kpi-icon-appointments">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="kpi-value">{{ $appointmentsToday }}</div>
                    <div class="kpi-label">Appointments Today</div>

                    @if ($appointmentsDelta > 0)
                    <div class="kpi-delta up">
                        <i class="fa-solid fa-arrow-up"></i>
                        {{ $appointmentsDelta }} more
                    </div>
                    @elseif ($appointmentsDelta < 0) <div class="kpi-delta down">
                        <i class="fa-solid fa-arrow-down"></i>
                        {{ abs($appointmentsDelta) }} fewer
                </div>
                @else
                <div class="kpi-delta neutral">Same as yesterday</div>
                @endif
        </div>

        <i class="fa-solid fa-chevron-right kpi-arrow"></i>
        </a>

        <div class="kpi-card kpi-card-cancellation">
            <div class="kpi-icon kpi-icon-cancellation">
                <i class="fa-solid fa-calendar-xmark"></i>
            </div>

            <div class="flex-1 min-w-0">
                <div class="kpi-value">{{ $cancellationRate }}%</div>
                <div class="kpi-label">Cancellation Rate</div>
                <div class="kpi-delta neutral">Based on recorded appointments</div>
            </div>
        </div>

        <a href="{{ route('dentist.dentist.inventory') }}" class="kpi-card kpi-card-low-stock group">
            <div class="kpi-icon kpi-icon-low-stock">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <div class="flex-1 min-w-0">
                <div class="kpi-value kpi-value-low-stock">{{ $lowStockItems }}</div>
                <div class="kpi-label kpi-label-low-stock">Low Stock Items</div>

                @if ($lowStockItems > 0)
                <div class="kpi-delta down">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Requires reorder
                </div>
                @else
                <div class="kpi-delta up">
                    <i class="fa-solid fa-circle-check"></i>
                    All stocked up
                </div>
                @endif
            </div>
            <i class="fa-solid fa-chevron-right kpi-arrow"></i>
        </a>
    </div>

    <div class="analytics-section-label">Patient and clinic insights</div>

    <div class="analytics-subgrid">
        <div class="mini-kpi">
            <div class="mini-kpi-label">Cases This Month</div>
            <div class="mini-kpi-value">{{ $casesThisMonth }}</div>
        </div>

        <div class="mini-kpi">
            <div class="mini-kpi-label">Avg Patients / Day</div>
            <div class="mini-kpi-value">{{ $avgPatientsPerDay }}</div>
        </div>

        <div class="mini-kpi">
            <div class="mini-kpi-label">Returning Patients</div>
            <div class="mini-kpi-value">{{ $returningPatients }}</div>
        </div>

        <div class="mini-kpi">
            <div class="mini-kpi-label">New Patients</div>
            <div class="mini-kpi-value">{{ $newPatients }}</div>
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

        <div class="chart-card lg:col-span-1">
            <div class="chart-card-header">
                <span class="chart-title"><i class="fa-solid fa-chart-column"></i> GAD Report</span>
                @php $firstCleanPeriod = $cleanPeriods->first(); @endphp

                <div class="report-custom-select report-period-select" data-report-select>
                    <select class="report-native-select period-select" id="gadPeriodSelect" data-report-select-native>
                        @foreach ($cleanPeriods as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="report-select-trigger" data-report-select-trigger
                        aria-expanded="false">
                        <span class="report-select-main">
                            <span class="report-select-icon">
                                <i class="fa-solid fa-calendar-days"></i>
                            </span>
                            <span data-report-select-label>{{ $firstCleanPeriod ?: 'Select period' }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-down report-select-chevron"></i>
                    </button>

                    <div class="report-select-menu" data-report-select-menu>
                        @foreach ($cleanPeriods as $opt)
                        <button type="button" class="report-select-option {{ $loop->first ? 'is-active' : '' }}"
                            data-report-select-option data-value="{{ $opt }}">
                            <span>{{ $opt }}</span>
                            <i class="fa-solid fa-check"></i>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div id="gadChartWrap" class="relative flex-1 min-h-[260px]">
                <canvas id="gadChart"></canvas>
                <div id="gadEmptyState" class="chart-empty hidden absolute inset-0">
                    <i class="fa-solid fa-chart-column"></i>
                    <p>No records found</p><span>for the selected period</span>
                </div>
                <div id="gadLoadingState" class="chart-loading hidden absolute inset-0"><i
                        class="fa-solid fa-spinner"></i></div>
            </div>
        </div>

        <div class="chart-card lg:col-span-1">
            <div class="chart-card-header">
                <span class="chart-title"><i class="fa-solid fa-chart-line"></i> Weekly Cases</span>
                <div class="report-custom-select report-period-select" data-report-select>
                    <select class="report-native-select period-select" id="weeklyPeriodSelect"
                        data-report-select-native>
                        @foreach ($cleanPeriods as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="report-select-trigger" data-report-select-trigger
                        aria-expanded="false">
                        <span class="report-select-main">
                            <span class="report-select-icon">
                                <i class="fa-solid fa-calendar-week"></i>
                            </span>
                            <span data-report-select-label>{{ $firstCleanPeriod ?: 'Select period' }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-down report-select-chevron"></i>
                    </button>

                    <div class="report-select-menu" data-report-select-menu>
                        @foreach ($cleanPeriods as $opt)
                        <button type="button" class="report-select-option {{ $loop->first ? 'is-active' : '' }}"
                            data-report-select-option data-value="{{ $opt }}">
                            <span>{{ $opt }}</span>
                            <i class="fa-solid fa-check"></i>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div id="weeklyChartWrap" class="relative flex-1 min-h-[260px]">
                <canvas id="weeklyDentalCasesChart"></canvas>
                <div id="weeklyEmptyState" class="chart-empty hidden absolute inset-0">
                    <i class="fa-solid fa-chart-line"></i>
                    <p>No appointment data</p><span>for the selected period</span>
                </div>
                <div id="weeklyLoadingState" class="chart-loading hidden absolute inset-0"><i
                        class="fa-solid fa-spinner"></i></div>
            </div>
        </div>

        <div class="pro-card">
            <div class="chart-card-header">
                <span class="chart-title">
                    <i class="fa-solid fa-user-group"></i>
                    Returning vs New Patients
                </span>
            </div>

            <div class="relative h-[280px]">
                @if (($returningPatients ?? 0) > 0 || ($newPatients ?? 0) > 0)
                <canvas id="patientSegmentChart"></canvas>
                @else
                <div class="chart-empty absolute inset-0">
                    <i class="fa-solid fa-user-group"></i>
                    <p>No patient segment data</p>
                    <span>Returning and new patient insights will appear here.</span>
                </div>
                @endif
            </div>
        </div>

    </div>
    <div class="analytics-secondary-grid">
        <div class="pro-card">
            <div class="chart-card-header">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="chart-title">
                        <i class="fa-solid fa-star"></i>
                        Top Dental Services
                    </span>
                </div>
                <span class="metric-chip">Top this month</span>
            </div>

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
            <div class="chart-empty py-6">
                <i class="fa-solid fa-tooth"></i>
                <p>No service data available</p>
                <span>Top performed treatments will appear here.</span>
            </div>
            @endif
        </div>

        <section class="reports-quick-actions-panel">
            <div class="reports-quick-actions-header">
                <span class="reports-quick-actions-head-icon">
                    <i class="fa-solid fa-bolt"></i>
                </span>

                <div class="min-w-0">
                    <h2>Quick Reports</h2>
                    <p>Frequently used report shortcuts</p>
                </div>
            </div>

            <div class="quick-actions-list">
                <a href="{{ route('dentist.dentist.report.dental-services') }}" class="quick-action quick-action-card">
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

                <a href="{{ route('dentist.dentist.report.daily-treatment') }}" class="quick-action quick-action-card">
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

    <div class="section-card printable-forms-card p-5 md:p-6 mb-8">
        <div class="chart-card-header mb-5">
            <div class="chart-title text-base">
                <i class="fa-solid fa-file-signature"></i> Printable Forms
            </div>
            <span class="text-[11px] font-bold text-[#8B0000] bg-red-50 px-3 py-1.5 rounded-lg">
                {{ $documentTemplates->count() }} active templates
            </span>
        </div>

        @if ($documentTemplates->count() > 0)
        <div class="printable-template-grid grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($documentTemplates as $template)
            <div class="action-card printable-template-card group cursor-default items-start">
                <div class="action-icon mt-0.5"><i class="fa-solid fa-file-medical"></i></div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-bold text-gray-800 group-hover:text-[#8B0000] transition-colors truncate">
                        {{ $template->name }}
                    </h4>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        {{ $template->code ?: 'Template Code N/A' }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2 text-[10px] font-bold">
                        <span class="px-2 py-1 rounded-full bg-red-50 text-[#8B0000]">
                            {{ \Illuminate\Support\Str::headline($template->document_type) }}
                        </span>
                        <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                            {{ $template->category ?: 'General' }}
                        </span>
                        <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700">
                            {{ $template->paper_size ?: 'A4' }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('dentist.dentist.report.templates.print', $template->id) }}" target="_blank"
                    rel="noopener" class="printable-template-print-btn ml-auto inline-flex
                    items-center gap-2 px-4 py-2.5 rounded-xl bg-[#8B0000] text-white text-xs font-bold
                    shadow-sm hover:bg-[#6b0000] transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-print"></i>
                    Print
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div
            class="flex flex-col items-center justify-center py-10 text-center border border-dashed border-red-200 rounded-2xl bg-red-50/40">
            <i class="fa-solid fa-file-circle-xmark text-3xl text-red-300 mb-3"></i>
            <p class="text-sm font-bold text-gray-700">No active document templates yet</p>
            <p class="text-xs text-gray-500 mt-1 max-w-md">
                Once a template is created and activated, it will appear here for printing reports or
                certificates.
            </p>
        </div>
        @endif
    </div>
    <div class="inventory-shell report-inventory-shell p-5 md:p-6 mb-8">
        <div class="chart-card-header mb-6">
            <span class="chart-title text-base"><i class="fa-solid fa-boxes-stacked"></i> Inventory
                Analytics</span>
            <a href="{{ route('dentist.dentist.inventory') }}"
                class="text-[11px] font-bold text-[#8B0000] bg-red-50 px-3 py-1.5 rounded-lg hover:bg-red-100 transition-colors">
                Manage Inventory
            </a>
        </div>

        <div class="report-inventory-grid grid grid-cols-1 md:grid-cols-3 gap-8">

            <div class="col-span-1 inventory-chart-panel">
                <h3 class="text-center text-[11px] font-bold text-gray-500 mb-4 uppercase tracking-wider">
                    Medicine
                    Stock</h3>
                <div class="relative h-[220px] w-full">
                    @if ($medicineItems->count() > 0)
                    <canvas id="medicinePieChart"></canvas>
                    @else
                    <a href="{{ route('dentist.dentist.inventory') }}"
                        class="chart-empty absolute inset-0 hover:bg-gray-50 transition cursor-pointer">

                        <i class="fa-solid fa-pills group-hover:scale-110 transition"></i>
                        <p>No medicine stock available</p>
                        <span>Add medicines to track inventory levels.</span>

                        <button type="button"
                            onclick="event.stopPropagation(); window.location='{{ route('dentist.dentist.inventory') }}'"
                            class="mt-4 flex items-center gap-2 px-5 py-2 bg-[#8B0000] text-white text-xs font-semibold rounded-xl shadow-sm hover:bg-[#6b0000] hover:shadow-md transition-all">
                            <div class="w-5 h-5 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-plus text-[8px] text-white leading-none"></i>
                            </div>
                            <span class="text-white">Add Medicine</span>
                        </button>
                    </a>
                    @endif
                </div>
            </div>

            <div class="col-span-1 report-inventory-panel">
                <h3 class="text-center text-[11px] font-bold text-gray-500 mb-4 uppercase tracking-wider">
                    Medical
                    Supplies</h3>
                <div class="relative h-[220px] w-full">
                    @if ($suppliesItems->count() > 0)
                    <canvas id="suppliesPieChart"></canvas>
                    @else
                    <a href="{{ route('dentist.dentist.inventory') }}"
                        class="chart-empty absolute inset-0 hover:bg-gray-50 transition cursor-pointer">

                        <i class="fa-solid fa-box-open group-hover:scale-110 transition"></i>
                        <p>No medical supplies found</p>
                        <span>Add supplies to monitor usage and stock.</span>

                        <button type="button"
                            onclick="event.stopPropagation(); window.location='{{ route('dentist.dentist.inventory') }}'"
                            class="mt-4 flex items-center gap-2 px-5 py-2 bg-[#8B0000] text-white text-xs font-semibold rounded-xl shadow-sm hover:bg-[#6b0000] hover:shadow-md transition-all">
                            <div class="w-5 h-5 rounded-md bg-white/20 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-plus text-[8px] leading-none"></i>
                            </div>
                            <span class="text-white">Add Supply</span>
                        </button>
                    </a>
                    @endif
                </div>
            </div>

            <div class="col-span-1 bg-gray-50 rounded-xl p-5 border border-gray-100 low-stock-alert-card">
                <div class="low-stock-alert-header flex items-center gap-2 mb-4">
                    <span class="low-stock-title-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </span>
                    <span class="text-sm font-bold text-gray-800">Low Stock Alerts</span>
                </div>

                @if ($lowStockMedicine->count() > 0 || $lowStockSupplies->count() > 0)
                <div class="overflow-y-auto max-h-[190px] pr-2 scroll-smooth">

                    @if ($lowStockMedicine->count() > 0)
                    @foreach ($lowStockMedicine as $item)
                    @php
                    $remaining = $item->qty - $item->used;
                    $pct = $item->qty > 0 ? round(($remaining / $item->qty) * 100) : 0;
                    $barClass = $pct <= 15 ? 'bg-red-500' : 'bg-orange-400' ; @endphp <div class="stock-row">
                        <div class="stock-name">
                            <span class="truncate pr-2">{{ $item->name }}</span>
                            <span class="text-red-600 font-bold text-[10px] whitespace-nowrap">{{ $remaining }}
                                left</span>
                        </div>
                        <div class="stock-bar-bg">
                            <div class="stock-bar-fill {{ $barClass }}" style="width:{{ $pct }}%"></div>
                        </div>
                </div>
                @endforeach
                @endif

                @if ($lowStockSupplies->count() > 0)
                @foreach ($lowStockSupplies as $item)
                @php
                $remaining = $item->qty - $item->used;
                $pct = $item->qty > 0 ? round(($remaining / $item->qty) * 100) : 0;
                $barClass = $pct <= 15 ? 'bg-red-500' : 'bg-orange-400' ; @endphp <div class="stock-row">
                    <div class="stock-name">
                        <span class="truncate pr-2">{{ $item->name }}</span>
                        <span class="text-red-600 font-bold text-[10px] whitespace-nowrap">{{ $remaining }}
                            left</span>
                    </div>
                    <div class="stock-bar-bg">
                        <div class="stock-bar-fill {{ $barClass }}" style="width:{{ $pct }}%"></div>
                    </div>
            </div>
            @endforeach
            @endif

        </div>
        @else
        @php
        $hasAnyInventoryData = $medicineItems->count() > 0 || $suppliesItems->count() > 0;
        @endphp

        @if ($hasAnyInventoryData)
        <div class="flex flex-col items-center justify-center h-[160px] text-center">
            <div class="stock-good-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">Stock levels are good</p>
            <p class="text-xs text-gray-500 mt-1">No items require immediate restocking.</p>
        </div>
        @else
        <div class="flex flex-col items-center justify-center h-[160px] text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-boxes-stacked text-gray-400 text-lg"></i>
            </div>
            <p class="text-sm font-bold text-gray-700">No inventory records yet</p>
            <p class="text-xs text-gray-500 mt-1">Add medicine or supply items to monitor low
                stock
                alerts.</p>

            <a href="{{ route('dentist.dentist.inventory') }}"
                class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-[#8B0000] text-white text-xs font-semibold rounded-xl shadow-sm hover:bg-[#6b0000] transition-all">
                <i class="fa-solid fa-plus text-[10px] leading-none"></i>
                <span>Add Item</span>
            </a>
        </div>
        @endif
        @endif
    </div>

    </div>
    </div>

    </div>
</main>

<div id="createReportModal" class="ui-modal modal-overlay" aria-hidden="true"
    onclick="closeModalOnBackdrop(event, 'createReportModal')">
    <div class="modal-box-inner um-user-modal um-user-modal-md report-create-modal" onclick="event.stopPropagation()">

        <div
            class="um-user-modal-header px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3 min-w-0">
                <div
                    class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#8B0000] via-[#a40000] to-[#6B0000] flex items-center justify-center shadow-lg shadow-red-900/20 flex-shrink-0">
                    <i class="fa-solid fa-file-circle-plus text-white text-sm"></i>
                </div>

                <div class="min-w-0">
                    <h3 class="font-extrabold text-gray-800 text-lg leading-tight">Create Custom Report</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Fields marked <span class="text-yellow-500 font-bold">*</span> are required.
                    </p>
                </div>
            </div>

            <button type="button" onclick="closeCreateModal()" data-close-modal="createReportModal" class="um-modal-x"
                aria-label="Close create custom report modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="reportForm" class="flex-1 flex flex-col min-h-0" novalidate>
            <div class="um-user-modal-body">
                <div class="um-user-main-card">
                    <div class="um-section-title">
                        <div class="um-section-icon bg-red-50 text-[#8B0000]">
                            <i class="fa-solid fa-file-lines text-sm"></i>
                        </div>

                        <div>
                            <h4 class="text-base font-extrabold text-gray-800 leading-tight">Report Details</h4>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Choose the report type, date range, and quantity.
                            </p>
                        </div>
                    </div>

                    <div class="um-field-grid">
                        <div class="um-field-full">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide">
                                    Report Name <span class="text-red-500">*</span>
                                </label>

                                <span id="reportNameCounter" class="text-[11px] font-semibold text-gray-400">
                                    0 / 100
                                </span>
                            </div>

                            <div class="voice-search-row" data-voice-field>
                                <input id="reportName" name="report_name" type="text" maxlength="100"
                                    placeholder="e.g. GAD Monthly Report — Dec 2025"
                                    class="field-input flex-1 min-w-0 border border-gray-200 px-3.5 py-3 text-sm bg-white" />

                                <div class="voice-input-toggle">
                                    <button type="button" id="reportNameMicBtn" class="voice-search-mic external"
                                        data-voice-trigger data-voice-target="#reportName"
                                        data-voice-status="#reportNameVoiceStatus"
                                        aria-label="Voice input for report name">
                                        <i class="fa-solid fa-microphone"></i>
                                    </button>

                                    <span id="reportNameVoiceStatus" class="voice-status hidden" data-voice-status
                                        aria-live="polite"></span>
                                </div>
                            </div>

                            <p id="reportNameErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Report name is required.
                            </p>
                        </div>

                        <div class="um-field-full">
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                Report Type <span class="text-red-500">*</span>
                            </label>

                            <div class="report-custom-select report-template-select" data-report-select>
                                <select id="reportType" name="document_template_id" class="report-native-select"
                                    data-report-select-native>
                                    <option value="" disabled selected>Select a report type...</option>

                                    @forelse ($customReportTemplates as $template)
                                    <option value="{{ $template->id }}"
                                        data-document-type="{{ $template->document_type }}">
                                        {{ $template->name }}
                                    </option>
                                    @empty
                                    <option value="" disabled>No active custom report forms available</option>
                                    @endforelse
                                </select>

                                <button type="button" class="report-select-trigger" data-report-select-trigger
                                    data-placeholder="Select a report type..." aria-expanded="false">
                                    <span class="report-select-main">
                                        <span class="report-select-icon">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </span>
                                        <span data-report-select-label>Select a report type...</span>
                                    </span>
                                    <i class="fa-solid fa-chevron-down report-select-chevron"></i>
                                </button>

                                <div class="report-select-menu" data-report-select-menu>
                                    @forelse ($customReportTemplates as $template)
                                    <button type="button" class="report-select-option" data-report-select-option
                                        data-value="{{ $template->id }}"
                                        data-document-type="{{ $template->document_type }}">
                                        <span>{{ $template->name }}</span>
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    @empty
                                    <div class="report-select-empty">No active custom report forms available</div>
                                    @endforelse
                                </div>
                            </div>

                            <p id="reportTypeErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Please select a report type.
                            </p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                From <span class="text-red-500">*</span>
                            </label>

                            <div class="fp-date-input-wrap">
                                <input id="dateFrom" name="date_from" type="text"
                                    class="field-input w-full border border-gray-200 px-3.5 py-3 pr-10 text-sm bg-white js-flatpickr-date-max-today"
                                    placeholder="Select start date" readonly />
                                <i class="fa-regular fa-calendar fp-date-icon"></i>
                            </div>

                            <p id="dateFromErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Start date is required.
                            </p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                To <span class="text-gray-400 normal-case font-normal">(optional)</span>
                            </label>

                            <div class="fp-date-input-wrap">
                                <input id="dateTo" name="date_to" type="text"
                                    class="field-input w-full border border-gray-200 px-3.5 py-3 pr-10 text-sm bg-white js-flatpickr-date-max-today"
                                    placeholder="Select end date" readonly />
                                <i class="fa-regular fa-calendar fp-date-icon"></i>
                            </div>
                        </div>

                        <div class="um-field-full">
                            <p class="text-[11px] text-gray-400 -mt-2">
                                <i class="fa-solid fa-circle-info mr-1"></i>
                                Leave "To" empty to report on a single date.
                            </p>

                            <p id="dateFutureErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Dates cannot be in the future.
                            </p>

                            <p id="dateRangeErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                End date must be on or after start date.
                            </p>
                        </div>

                        <div class="um-field-full">
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                Quantity <span class="text-red-500">*</span>
                            </label>

                            <div class="report-qty-row">
                                <div class="report-qty-control">
                                    <button type="button" class="report-qty-btn" data-qty-minus
                                        aria-label="Decrease quantity">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>

                                    <input id="reportQty" name="quantity" type="number" min="1" max="100" step="1"
                                        placeholder="1 – 100"
                                        class="field-input report-qty-input border border-gray-200 px-3.5 py-3 text-sm bg-white" />

                                    <button type="button" class="report-qty-btn" data-qty-plus
                                        aria-label="Increase quantity">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>

                                <span class="report-qty-helper">Whole numbers only</span>
                            </div>

                            <p id="reportQtyErr" class="text-red-500 text-xs mt-1 hidden items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span id="reportQtyErrMsg">Quantity must be between 1 and 100.</span>
                            </p>
                        </div>

                        <div class="um-field-full">
                            <div id="formErrorBanner" class="report-modal-error hidden">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Please complete all required fields before downloading.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-ft um-user-modal-footer">
                <button type="button" onclick="closeCreateModal()" class="modal-btn-ghost">
                    Cancel
                </button>

                <button type="button" id="downloadReportBtn" class="modal-btn-confirm-reject um-save-user-btn">
                    <span class="btn-confirm-icon">
                        <i class="fa-solid fa-download"></i>
                    </span>
                    <span>Download</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="downloadCompleteModal" class="ui-modal" onclick="closeModalOnBackdrop(event, 'downloadCompleteModal')">
    <div class="ui-modal-card modal-box p-0 rounded-2xl overflow-hidden bg-white shadow-2xl max-w-sm">
        <div class="h-1.5 bg-gradient-to-r from-[#8B0000] to-[#FFD700] w-full"></div>
        <div class="px-8 py-10 text-center">
            <div
                class="w-16 h-16 bg-green-50 border-2 border-green-200 rounded-full flex items-center justify-center mx-auto mb-5">
                <i class="fa-solid fa-check text-green-500 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-[#8B0000] mb-2">Download Complete!</h3>
            <p class="text-gray-500 text-sm leading-relaxed mb-7">Your report has been successfully generated and
                downloaded.</p>
            <button onclick="closeDownloadModal()"
                class="px-8 py-2.5 rounded-xl bg-[#8B0000] hover:bg-[#6b0000] text-white font-bold text-sm shadow-sm transition-all w-full">Done</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const PATIENT_SEGMENT_DATA = {
        labels: ['Returning Patients', 'New Patients'],
        values: [
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

    function getCookieValue(name) {
        return document.cookie
            .split('; ')
            .find(row => row.startsWith(name + '='))
            ?.split('=')[1] || '';
    }

    const CSRF_TOKEN =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        decodeURIComponent(getCookieValue('XSRF-TOKEN')) ||
        "{{ csrf_token() }}";

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

    if (window.Chart) {
        Chart.defaults.color = reportChartTextColor();
        Chart.defaults.borderColor = reportChartGridColor();
    }
</script>

<script>
    let patientSegmentChartInstance = null;

    function buildPatientSegmentChart() {
        const total = PATIENT_SEGMENT_DATA.values.reduce((a, b) => a + b, 0);
        if (total === 0) return;

        if (patientSegmentChartInstance) {
            patientSegmentChartInstance.destroy();
            patientSegmentChartInstance = null;
        }

        patientSegmentChartInstance = new Chart(document.getElementById('patientSegmentChart'), {
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
                                family: 'Inter',
                                size: 11
                            },
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 14
                        }
                    }
                }
            }
        });
    }

    function forceCloseModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.remove('open', 'closing');
        modal.setAttribute('aria-hidden', 'true');

        if (!document.querySelector('.ui-modal.open, .modal-overlay.open, dialog[open]')) {
            document.documentElement.classList.remove('modal-lock');
            document.body.classList.remove('modal-lock');
        }
    }

    function resetCreateReportForm() {
        const form = document.getElementById('reportForm');
        if (form) form.reset();

        syncReportCustomSelects(document.getElementById('createReportModal'));

        const counter = document.getElementById('reportNameCounter');
        if (counter) {
            counter.textContent = '0 / 100';
            counter.classList.remove('text-red-500');
            counter.classList.add('text-gray-400');
        }

        [
            'reportNameErr',
            'reportTypeErr',
            'dateFromErr',
            'dateFutureErr',
            'dateRangeErr',
            'reportQtyErr',
            'formErrorBanner'
        ].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            el.classList.add('hidden');
            el.classList.remove('flex');
        });

        ['reportName', 'reportType', 'dateFrom', 'dateTo', 'reportQty'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            el.classList.remove('border-red-400');
            el.classList.add('border-gray-300');
        });

        document.querySelectorAll('.flatpickr-calendar.open').forEach(cal => {
            cal.classList.remove('open');
        });
    }

    function closeCreateModal() {
        if (typeof window.closeModal === 'function') {
            window.closeModal('createReportModal');
        } else {
            forceCloseModal('createReportModal');
        }

        resetCreateReportForm();
    }

    function ensureReportFlatpickrs() {
        if (!window.flatpickr) return;

        const today = new Date();

        ['dateFrom', 'dateTo'].forEach((id) => {
            const input = document.getElementById(id);
            if (!input) return;

            input.setAttribute('max', today.toISOString().split('T')[0]);

            if (input._flatpickr) {
                input._flatpickr.set('maxDate', 'today');
                return;
            }

            window.flatpickr(input, {
                dateFormat: 'Y-m-d',
                maxDate: 'today',
                allowInput: false,
                clickOpens: true,
                disableMobile: true,
                appendTo: document.body,
                positionElement: input,
                onOpen: (_dates, _str, instance) => {
                    instance.calendarContainer.style.zIndex = '1000000';
                }
            });
        });
    }

    function openCreateReportModal() {
        const modal = document.getElementById('createReportModal');
        if (!modal) return;

        modal.classList.remove('closing');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');

        document.documentElement.classList.add('modal-lock');
        document.body.classList.add('modal-lock');

        initReportCustomSelects(modal);
        syncReportCustomSelects(modal);
        ensureReportFlatpickrs();

        if (window.initGlobalVoiceInputs) {
            window.initGlobalVoiceInputs(modal);
        }

        document.dispatchEvent(new CustomEvent('voice:refresh', {
            detail: { root: modal }
        }));
    }

    function closeReportSelects(except = null) {
        document.querySelectorAll('.report-custom-select.open').forEach((select) => {
            if (select === except) return;

            select.classList.remove('open');
            select.querySelector('[data-report-select-trigger]')?.setAttribute('aria-expanded', 'false');
        });
    }

    function syncReportSelectUI(nativeSelect) {
        if (!nativeSelect) return;

        const wrap = nativeSelect.closest('[data-report-select]');
        if (!wrap) return;

        const label = wrap.querySelector('[data-report-select-label]');
        const trigger = wrap.querySelector('[data-report-select-trigger]');
        const selectedOption = nativeSelect.selectedOptions?.[0];
        const placeholder = trigger?.dataset.placeholder || 'Select option';

        const selectedText = nativeSelect.value ?
            selectedOption?.textContent?.trim() :
            placeholder;

        if (label) label.textContent = selectedText || placeholder;

        wrap.querySelectorAll('[data-report-select-option]').forEach((option) => {
            const isActive = option.dataset.value === nativeSelect.value;

            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function syncReportCustomSelects(root = document) {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

        scope.querySelectorAll('[data-report-select-native]').forEach(syncReportSelectUI);
    }

    function initReportCustomSelects(root = document) {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

        scope.querySelectorAll('[data-report-select]').forEach((wrap) => {
            if (wrap.dataset.reportSelectInitialized === 'true') {
                syncReportSelectUI(wrap.querySelector('[data-report-select-native]'));
                return;
            }

            wrap.dataset.reportSelectInitialized = 'true';

            const nativeSelect = wrap.querySelector('[data-report-select-native]');
            const trigger = wrap.querySelector('[data-report-select-trigger]');

            trigger?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const willOpen = !wrap.classList.contains('open');

                closeReportSelects(wrap);

                wrap.classList.toggle('open', willOpen);
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            wrap.querySelectorAll('[data-report-select-option]').forEach((option) => {
                option.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (!nativeSelect) return;

                    nativeSelect.value = option.dataset.value || '';
                    syncReportSelectUI(nativeSelect);

                    nativeSelect.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));

                    wrap.classList.remove('open');
                    trigger?.setAttribute('aria-expanded', 'false');
                });
            });

            nativeSelect?.addEventListener('change', () => syncReportSelectUI(nativeSelect));

            syncReportSelectUI(nativeSelect);
        });
    }

    let gadChartInstance = null,
        weeklyChartInstance = null;

    function setReportChartState(canvasId, emptyId, loadingId, state) {
        const canvas = document.getElementById(canvasId);
        const empty = document.getElementById(emptyId);
        const loading = document.getElementById(loadingId);

        if (!canvas || !empty || !loading) return;

        empty.classList.remove('flex');
        loading.classList.remove('flex');

        empty.classList.add('hidden');
        loading.classList.add('hidden');

        empty.style.display = 'none';
        loading.style.display = 'none';

        if (state === 'empty') {
            canvas.style.display = 'none';
            empty.classList.remove('hidden');
            empty.classList.add('flex');
            empty.style.display = 'flex';
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

    function showGadEmpty() {
        setReportChartState('gadChart', 'gadEmptyState', 'gadLoadingState', 'empty');
    }

    function showGadLoading() {
        setReportChartState('gadChart', 'gadEmptyState', 'gadLoadingState', 'loading');
    }

    function showGadChart() {
        setReportChartState('gadChart', 'gadEmptyState', 'gadLoadingState', 'chart');
    }

    function showWeeklyEmpty() {
        setReportChartState('weeklyDentalCasesChart', 'weeklyEmptyState', 'weeklyLoadingState', 'empty');
    }

    function showWeeklyLoading() {
        setReportChartState('weeklyDentalCasesChart', 'weeklyEmptyState', 'weeklyLoadingState', 'loading');
    }

    function showWeeklyChart() {
        setReportChartState('weeklyDentalCasesChart', 'weeklyEmptyState', 'weeklyLoadingState', 'chart');
    }

    function buildGadChart(labels, female, male) {
        if (gadChartInstance) {
            gadChartInstance.destroy();
            gadChartInstance = null;
        }
        gadChartInstance = new Chart(document.getElementById('gadChart'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Female',
                    data: female,
                    backgroundColor: '#8B0000',
                    borderRadius: 4
                }, {
                    label: 'Male',
                    data: male,
                    backgroundColor: '#FCA5A5',
                    borderRadius: 4
                }]
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
                                family: 'Inter',
                                size: 11
                            },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${ctx.parsed.x} cases`
                        }
                    }
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
                                family: 'Inter',
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
                                family: 'Inter',
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    function buildWeeklyChart(labels, datasets) {
        if (weeklyChartInstance) {
            weeklyChartInstance.destroy();
            weeklyChartInstance = null;
        }
        weeklyChartInstance = new Chart(document.getElementById('weeklyDentalCasesChart'), {
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
                                family: 'Inter',
                                size: 11
                            },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} cases`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            color: reportChartGridColor()
                        },
                        ticks: {
                            font: {
                                family: 'Inter',
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
                                family: 'Inter',
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

        new Chart(canvas, {
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
                                family: 'Inter',
                                size: 10
                            },
                            usePointStyle: true,
                            boxWidth: 6,
                            padding: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} remaining`
                        }
                    }
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

    function waitForChartJs(maxTries = 30) {
        return new Promise((resolve) => {
            let tries = 0;

            const check = () => {
                if (window.Chart) {
                    resolve(true);
                    return;
                }

                tries += 1;

                if (tries >= maxTries) {
                    console.warn('Chart.js is not available on dentist report page.');
                    resolve(false);
                    return;
                }

                setTimeout(check, 100);
            };

            check();
        });
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
    }

    document.addEventListener('DOMContentLoaded', function () {

        initReportCustomSelects(document);

        if (window.initGlobalVoiceInputs) {
            window.initGlobalVoiceInputs(document.getElementById('createReportModal'));
        }

        waitForChartJs().then(() => {
            initReportCharts();
        });

        document.getElementById('gadPeriodSelect').addEventListener('change', function () {
            reloadGadChart(this.value);
        });
        document.getElementById('weeklyPeriodSelect').addEventListener('change', function () {
            reloadWeeklyChart(this.value);
        });

        const todayStr = new Date().toISOString().split('T')[0];
        document.getElementById('dateFrom').setAttribute('max', todayStr);
        document.getElementById('dateTo').setAttribute('max', todayStr);

        function setError(inputId, errId, show) {
            const input = document.getElementById(inputId);
            const err = document.getElementById(errId);

            if (!input || !err) return;

            const selectWrap = input.closest('[data-report-select]');
            if (selectWrap) {
                selectWrap.classList.toggle('is-invalid', show);
            }

            if (show) {
                err.classList.remove('hidden');
                err.classList.add('flex');
                input.classList.add('border-red-400');
                input.classList.remove('border-gray-300');
            } else {
                err.classList.add('hidden');
                err.classList.remove('flex');
                input.classList.remove('border-red-400');
                input.classList.add('border-gray-300');
            }
        }

        const clearError = (a, b) => setError(a, b, false);

        document.getElementById('downloadReportBtn').addEventListener('click', async function () {
            const btn = document.getElementById('downloadReportBtn');
            const name = document.getElementById('reportName').value.trim();
            const type = document.getElementById('reportType').value;
            const selectedOption = document.getElementById('reportType').selectedOptions[0];
            const documentType = selectedOption ? selectedOption.dataset.documentType : '';
            const from = document.getElementById('dateFrom').value;
            const to = document.getElementById('dateTo').value;
            const qty = parseInt(document.getElementById('reportQty').value, 10);
            const banner = document.getElementById('formErrorBanner');

            let valid = true;

            function showBanner(message =
                'Please complete all required fields before downloading.') {
                banner.innerHTML = `
            <i class="fa-solid fa-triangle-exclamation text-red-500 flex-shrink-0"></i>
            <span>${message}</span>
        `;
                banner.classList.remove('hidden');
                banner.classList.add('flex');
            }

            function hideBanner() {
                banner.classList.add('hidden');
                banner.classList.remove('flex');
            }

            setError('reportName', 'reportNameErr', !name);
            if (!name) valid = false;

            setError('reportType', 'reportTypeErr', !type);
            if (!type) valid = false;

            ['dateFromErr', 'dateFutureErr', 'dateRangeErr'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }
            });

            ['dateFrom', 'dateTo'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.remove('border-red-400');
                    el.classList.add('border-gray-300');
                }
            });

            if (!from) {
                document.getElementById('dateFromErr').classList.remove('hidden');
                document.getElementById('dateFromErr').classList.add('flex');
                document.getElementById('dateFrom').classList.add('border-red-400');
                document.getElementById('dateFrom').classList.remove('border-gray-300');
                valid = false;
            } else {
                const fromFuture = from > todayStr;
                const toFuture = to && to > todayStr;

                if (fromFuture || toFuture) {
                    document.getElementById('dateFutureErr').classList.remove('hidden');
                    document.getElementById('dateFutureErr').classList.add('flex');

                    if (fromFuture) {
                        document.getElementById('dateFrom').classList.add('border-red-400');
                        document.getElementById('dateFrom').classList.remove('border-gray-300');
                    }

                    if (toFuture) {
                        document.getElementById('dateTo').classList.add('border-red-400');
                        document.getElementById('dateTo').classList.remove('border-gray-300');
                    }

                    valid = false;
                } else if (to && new Date(to) < new Date(from)) {
                    document.getElementById('dateRangeErr').classList.remove('hidden');
                    document.getElementById('dateRangeErr').classList.add('flex');
                    document.getElementById('dateTo').classList.add('border-red-400');
                    document.getElementById('dateTo').classList.remove('border-gray-300');
                    valid = false;
                }
            }

            const isCertificateRequest = ['annual_dental_clearance', 'dental_clearance'].includes(
                documentType);

            let qtyInvalid = false;

            if (!isCertificateRequest) {
                qtyInvalid = isNaN(qty) || qty < 1 || qty > 100;

                document.getElementById('reportQtyErrMsg').textContent = (isNaN(qty) || qty < 1) ?
                    'Quantity must be between 1 and 100.' :
                    'Quantity cannot exceed 100.';

                setError('reportQty', 'reportQtyErr', qtyInvalid);

                if (qtyInvalid) valid = false;
            } else {
                clearError('reportQty', 'reportQtyErr');
            }

            if (!valid) {
                showBanner();
                btn.classList.add('animate-bounce');
                setTimeout(() => btn.classList.remove('animate-bounce'), 600);
                return;
            }

            let downloadEndpoint = null;

            if (documentType === 'gad_report') {
                downloadEndpoint = GAD_REPORT_DOWNLOAD_URL;
            } else if (documentType === 'annual_dental_clearance') {
                downloadEndpoint = ANNUAL_CLEARANCE_DOWNLOAD_URL;
            } else if (documentType === 'dental_clearance') {
                downloadEndpoint = DENTAL_CLEARANCE_DOWNLOAD_URL;
            } else if (documentType === 'dental_services') {
                downloadEndpoint = DENTAL_SERVICES_DOWNLOAD_URL;
            } else if (documentType === 'medicine_inventory') {
                downloadEndpoint = MEDICINE_INVENTORY_DOWNLOAD_URL;
            } else if (documentType === 'daily_treatment_record') {
                downloadEndpoint = DAILY_TREATMENT_RECORD_DOWNLOAD_URL;
            } else if (documentType === 'dental_health_record') {
                downloadEndpoint = DENTAL_HEALTH_RECORD_DOWNLOAD_URL;
            } else if (documentType === 'dental_supplies_inventory') {
                downloadEndpoint = DENTAL_SUPPLIES_INVENTORY_DOWNLOAD_URL;
            } else if (documentType === 'dental_cases') {
                downloadEndpoint = DENTAL_CASES_DOWNLOAD_URL;
            } else if (documentType === 'monthly_report') {
                downloadEndpoint = MONTHLY_REPORT_DOWNLOAD_URL;
            } else {
                showBanner('This selected form is not yet connected to official PDF download.');
                btn.classList.add('animate-bounce');
                setTimeout(() => btn.classList.remove('animate-bounce'), 600);
                return;
            }

            hideBanner();

            const originalBtnHtml = btn.innerHTML;

            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';

            try {
                const formData = new FormData();
                formData.append('_token', CSRF_TOKEN);
                formData.append('report_name', name);
                formData.append('document_template_id', type);
                formData.append('date_from', from);
                formData.append('quantity', isCertificateRequest ? '1' : String(qty));

                if (to) {
                    formData.append('date_to', to);
                }

                const response = await fetch(downloadEndpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-XSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/pdf, application/json'
                    },
                    body: formData,
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    let message =
                        `Unable to generate the report. Server returned ${response.status}.`;

                    if (response.status === 403) {
                        message =
                            'You are not authorized to download this report. Please check the dentist account permissions.';
                    }

                    if (response.status === 404) {
                        message = 'The selected report template or PDF file was not found.';
                    }

                    if (response.status === 422) {
                        message =
                            'Some report fields are invalid. Please review the form and try again.';
                    }

                    const contentType = response.headers.get('content-type') || '';

                    if (contentType.includes('application/json')) {
                        const errorData = await response.json();

                        if (errorData.message) {
                            message = errorData.message;
                        }

                        if (errorData.errors) {
                            const firstError = Object.values(errorData.errors)[0];
                            if (Array.isArray(firstError) && firstError.length > 0) {
                                message = firstError[0];
                            }
                        }
                    }

                    throw new Error(message);
                }

                const blob = await response.blob();
                const downloadUrl = window.URL.createObjectURL(blob);

                let fileName = `${name.replace(/[^A-Za-z0-9_-]/g, '_')}.pdf`;
                const disposition = response.headers.get('Content-Disposition') || response.headers
                    .get('content-disposition') || '';
                const fileNameMatch = disposition.match(/filename="?([^"]+)"?/i);

                if (fileNameMatch && fileNameMatch[1]) {
                    fileName = fileNameMatch[1];
                }

                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.URL.revokeObjectURL(downloadUrl);

                closeModal('createReportModal');
                openModal('downloadCompleteModal');

                document.getElementById('reportForm').reset();
                document.getElementById('reportNameCounter').textContent = '0 / 100';
                document.getElementById('reportNameCounter').classList.remove('text-red-500');
                document.getElementById('reportNameCounter').classList.add('text-gray-400');

                ['reportNameErr', 'reportTypeErr', 'dateFromErr', 'dateFutureErr', 'dateRangeErr',
                    'reportQtyErr'
                ]
                    .forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.classList.add('hidden');
                            el.classList.remove('flex');
                        }
                    });

                ['reportName', 'reportType', 'dateFrom', 'dateTo', 'reportQty']
                    .forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.classList.remove('border-red-400');
                            el.classList.add('border-gray-300');
                        }
                    });

            } catch (error) {
                showBanner(error.message || 'Unable to generate the report. Please try again.');
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                btn.innerHTML = originalBtnHtml;
            }
        });
        document.getElementById('reportName').addEventListener('input', function () {
            const len = this.value.length,
                counter = document.getElementById('reportNameCounter');
            counter.textContent = `${len} / 100`;
            counter.classList.toggle('text-red-500', len >= 90);
            counter.classList.toggle('text-gray-400', len < 90);
            if (this.value.trim()) clearError('reportName', 'reportNameErr');
            document.getElementById('formErrorBanner').classList.add('hidden');
        });

        document.getElementById('reportType').addEventListener('change', function () {
            if (this.value) clearError('reportType', 'reportTypeErr');

            const selectedOption = this.selectedOptions[0];
            const documentType = selectedOption ? selectedOption.dataset.documentType : '';
            const qtyInput = document.getElementById('reportQty');
            const qtyErr = document.getElementById('reportQtyErr');
            const banner = document.getElementById('formErrorBanner');
            const qtyButtons = document.querySelectorAll('[data-qty-minus], [data-qty-plus]');

            const isCertificateRequest = ['annual_dental_clearance', 'dental_clearance'].includes(documentType);

            if (isCertificateRequest) {
                qtyInput.value = '';
                qtyInput.placeholder = 'Auto';
                qtyInput.disabled = true;
                qtyInput.classList.add('bg-gray-100', 'cursor-not-allowed');

                qtyButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('is-disabled');
                });

                if (qtyErr) {
                    qtyErr.classList.add('hidden');
                    qtyErr.classList.remove('flex');
                }

                banner.innerHTML = `
            <i class="fa-solid fa-circle-info text-blue-500 flex-shrink-0"></i>
            <span>Certificate reports will generate based on approved requests in the selected date range.</span>
        `;
                banner.classList.remove('hidden');
                banner.classList.add('flex');
                banner.classList.remove('bg-red-50', 'border-red-200', 'text-red-700');
                banner.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-700');
            } else {
                qtyInput.disabled = false;
                qtyInput.placeholder = '1 – 100';
                qtyInput.classList.remove('bg-gray-100', 'cursor-not-allowed');

                qtyButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('is-disabled');
                });

                banner.classList.add('hidden');
                banner.classList.remove('flex', 'bg-blue-50', 'border-blue-200', 'text-blue-700');
                banner.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
            }
        });

        function checkDates() {
            const from = document.getElementById('dateFrom').value,
                to = document.getElementById('dateTo').value;
            ['dateFromErr', 'dateFutureErr', 'dateRangeErr'].forEach(id => {
                let el = document.getElementById(id);
                if (el) {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }
            });
            ['dateFrom', 'dateTo'].forEach(id => {
                let el = document.getElementById(id);
                if (el) {
                    el.classList.remove('border-red-400');
                    el.classList.add('border-gray-300');
                }
            });
            if (!from && !to) return;
            const fromFuture = from && from > todayStr,
                toFuture = to && to > todayStr;
            if (fromFuture || toFuture) {
                document.getElementById('dateFutureErr').classList.remove('hidden');
                document.getElementById('dateFutureErr').classList.add('flex');
                if (fromFuture) {
                    document.getElementById('dateFrom').classList.add('border-red-400');
                    document.getElementById('dateFrom').classList.remove('border-gray-300');
                }
                if (toFuture) {
                    document.getElementById('dateTo').classList.add('border-red-400');
                    document.getElementById('dateTo').classList.remove('border-gray-300');
                }
                return;
            }
            if (from && to && new Date(to) < new Date(from)) {
                document.getElementById('dateRangeErr').classList.remove('hidden');
                document.getElementById('dateRangeErr').classList.add('flex');
                document.getElementById('dateTo').classList.add('border-red-400');
                document.getElementById('dateTo').classList.remove('border-gray-300');
            }
            document.getElementById('formErrorBanner').classList.add('hidden');
        }
        document.getElementById('dateFrom').addEventListener('change', checkDates);
        document.getElementById('dateTo').addEventListener('change', checkDates);

        function setReportQty(value) {
            const qtyInput = document.getElementById('reportQty');
            if (!qtyInput || qtyInput.disabled) return;

            const next = Math.min(100, Math.max(1, Number(value) || 1));

            qtyInput.value = String(next);
            qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
            qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        document.querySelector('[data-qty-minus]')?.addEventListener('click', () => {
            const qtyInput = document.getElementById('reportQty');
            setReportQty((parseInt(qtyInput.value, 10) || 1) - 1);
        });

        document.querySelector('[data-qty-plus]')?.addEventListener('click', () => {
            const qtyInput = document.getElementById('reportQty');
            setReportQty((parseInt(qtyInput.value, 10) || 0) + 1);
        });

        const qtyInput = document.getElementById('reportQty');
        qtyInput.addEventListener('keydown', e => {
            if (['-', '+', 'e', 'E', '.', ','].includes(e.key)) e.preventDefault();
        });
        qtyInput.addEventListener('input', function () {
            let val = this.value.replace(/[^0-9]/g, '');
            if (val !== '' && parseInt(val, 10) > 100) val = '100';
            this.value = val;
            const qty = parseInt(val, 10);
            if (!isNaN(qty) && qty >= 1 && qty <= 100) clearError('reportQty', 'reportQtyErr');
            document.getElementById('formErrorBanner').classList.add('hidden');
        });
        qtyInput.addEventListener('paste', e => {
            e.preventDefault();
            const num = parseInt((e.clipboardData || window.clipboardData).getData('text').replace(
                /[^0-9]/g, ''), 10);
            if (!isNaN(num)) qtyInput.value = Math.min(Math.max(num, 1), 100);
        });

        document.addEventListener('click', () => closeReportSelects());

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeReportSelects();
        });
    });
</script>
@endsection