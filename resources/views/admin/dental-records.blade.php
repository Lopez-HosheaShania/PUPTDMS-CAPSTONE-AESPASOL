@extends('layouts.app')

@section('layout-role', $layoutRole ?? 'admin')

@section('title', 'Dental Records')

@section('styles')
@vite('resources/css/pages/admin/dental-records.css')
@endsection

@section('content')

@php use Carbon\Carbon;

$routePrefix = request()->routeIs('dentist.*') ? 'dentist' : 'admin';
$reportsRouteName = $routePrefix === 'dentist' ? 'dentist.dentist.report' : 'admin.report-files';
$appointmentsRouteName = $routePrefix === 'dentist' ? 'dentist.dentist.appointments' : 'admin.admin.appointments';

$recordsSource = $records ?? collect();

$recordItems = $recordsSource instanceof \Illuminate\Pagination\AbstractPaginator
? collect($recordsSource->items())
: collect($recordsSource);

$totalRecordsCount = $totalRecords
?? (
$recordsSource instanceof \Illuminate\Pagination\AbstractPaginator
? $recordsSource->total()
: $recordItems->count()
);

$recordsTodayCount = $recordsToday ?? 0;

$pendingCount = $pending
?? $recordItems
->filter(
fn ($record) =>
strtolower(
trim(
$record->status ?? 'pending'
)
) === 'pending'
)
->count();

$recordPaginationMeta = $recordsSource instanceof \Illuminate\Pagination\AbstractPaginator
? [
'current_page' => $recordsSource->currentPage(),
'last_page' => $recordsSource->lastPage(),
'total' => $recordsSource->total(),
'from' => $recordsSource->firstItem(),
'to' => $recordsSource->lastItem(),
]
: null;

$recordPerPage = $recordsSource instanceof \Illuminate\Pagination\AbstractPaginator
? $recordsSource->perPage()
: 10;

$recordAppliedStatus = request('status', 'all');
@endphp

<main id="mainContent" class="app-page-shell admin-dental-records-page page-enter mode-list">
    <div class="w-full">

        <div class="page-banner mt-2 mb-6">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Dental Records</h1>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <a href="{{ route($routePrefix . '.reports.ai-generated') }}" class="ui-btn ui-btn-primary">
                        <i class="fa-solid fa-chart-column"></i>
                        <span>View AI Reports</span>
                    </a>
                </div>
            </div>
        </div>

        <div id="statCards" class="stat-grid dental-records-stat-grid">
            <div class="stat-card s-all" data-filter="all">
                <div class="stat-card-info">
                    <div class="stat-label">Total Records</div>
                    <div class="stat-num">{{ number_format($totalRecordsCount) }}</div>
                    <div class="stat-footer">all dental records</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
            </div>

            <div class="stat-card s-today" data-filter="all">
                <div class="stat-card-info">
                    <div class="stat-label">Added Today</div>
                    <div class="stat-num">{{ number_format($recordsTodayCount) }}</div>
                    <div class="stat-footer">new entries</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
            </div>

            <div class="stat-card s-pending" data-filter="pending">
                <div class="stat-card-info">
                    <div class="stat-label">Pending Records</div>
                    <div class="stat-num">{{ number_format($pendingCount) }}</div>
                    <div class="stat-footer">needs action</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-user-clock"></i></div>
            </div>
        </div>

        <div class="dental-records-layout">
            <section class="table-card dental-records-main-card">

                <div class="patient-table-toolbar record-toolbar px-4 md:px-6 py-4 border-b border-gray-100">
                    <div class="record-toolbar-layout">

                        @php
                        $recordAllCount = $totalRecordsCount;
                        $recordTodayCount = $recordsTodayCount ?? 0;
                        $recordPendingCount = $pendingCount ?? 0;
                        $recordOngoingCount = $ongoingCount ?? $recordItems->where('status', 'ongoing')->count();
                        $recordCompletedCount = $completedCount ?? $recordItems->where('status',
                        'completed')->count();
                        $recordCancelledCount = $cancelledCount ?? $recordItems->where('status',
                        'cancelled')->count();
                        @endphp

                        <div class="record-toolbar-actions">
                            <div class="record-search-row voice-search-row">

                                <x-search-bar id="dentalRecordSearch" placeholder="Search patient name..." type="search"
                                    clear-label="Clear dental record search" class="flex-1" />

                                <x-voice-input target="#dentalRecordSearch" status-id="dentalRecordVoiceStatus"
                                    label="Voice search dental records" title="Voice search" />

                            </div>

                            <div class="record-filter-actions">
                                <button id="dentalRecordFilterBtn" type="button" class="global-filter-btn"
                                    aria-pressed="false" onclick="openDentalRecordFilters()">
                                    <i class="fa-solid fa-sliders"></i>
                                    <span>Filters</span>
                                    <span id="dentalRecordFilterBadge" class="filter-badge"></span>
                                </button>

                                <button id="dentalRecordFilterResetBtn" type="button"
                                    class="global-filter-reset-btn hidden" title="Reset filters"
                                    aria-label="Reset dental record filters" onclick="clearDentalRecordFilters()">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </div>

                            <x-view-toggle id="dentalRecordViewToggle" root="#mainContent"
                                storage-key="admin_dental_records_view" list-view="#dentalRecordListView"
                                grid-view="#dentalRecordGridView" list-label="List" grid-label="Grid"
                                class="record-view-toggle" />
                        </div>
                    </div>
                </div>

                @if ($recordsSource instanceof \Illuminate\Pagination\AbstractPaginator)
                <x-pagination-bar id="dentalRecordsPagebarTop" info-id="dentalRecordsPageInfoTop"
                    pagination-id="dentalRecordsPaginationTop" position="top" :show-entries="true"
                    page-size-id="dentalRecordsPageSize" page-size-callback="changeDentalRecordsPageSize"
                    :page-size-value="$recordPerPage" label="records" :total="$recordsSource->total()"
                    :from="$recordsSource->firstItem() ?? 0" :to="$recordsSource->lastItem() ?? 0" />
                @endif

                <div id="dentalRecordsResultsRegion">
                    @if ($recordItems->isEmpty())
                    <div id="dentalRecordEmptyState" class="empty-state-host show"></div>
                    @else
                    <div id="dentalRecordListView" class="table-list-view table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Procedure</th>
                                    <th>Dentist</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="table-cell-center">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="dentalRecordsTableBody">
                                @foreach ($recordsSource as $record)
                                @php
                                $rawStatus = strtolower(trim($record->status ?? 'pending'));
                                $normalizedStatus = str_replace([' ', '_'], '-', $rawStatus);
                                $statusClass = match ($normalizedStatus) {
                                'completed' => 'status-completed',
                                'ongoing', 'in-progress' => 'status-ongoing',
                                'cancelled', 'canceled' => 'status-cancelled',
                                'not-started' => 'status-default',
                                default => 'status-pending',
                                };

                                $patientName = $record->patient_name ??
                                (data_get($record, 'patient.name') ??
                                (data_get($record, 'patient.full_name') ?? 'Unknown Patient'));

                                $dentistName = $record->dentist_name ??
                                (data_get($record, 'dentist.name') ??
                                (data_get($record, 'dentist.full_name') ?? '—'));

                                $procedure = $record->procedure ?? '—';
                                $recordDate = null;

                                if (!empty($record->date)) {
                                try {
                                $recordDate = Carbon::parse($record->date);
                                } catch (\Throwable $e) {
                                $recordDate = null;
                                }
                                }

                                $dateText = $recordDate ? $recordDate->format( 'M d, Y'): '—';

                                $dateIso = $recordDate? $recordDate->toDateString(): '';
                                @endphp

                                <tr class="dental-record-row dental-record-item"
                                    data-patient="{{ strtolower($patientName) }}"
                                    data-procedure="{{ strtolower($procedure) }}"
                                    data-dentist="{{ strtolower($dentistName) }}" data-status="{{ $normalizedStatus }}"
                                    data-date="{{ $dateIso }}" @if (!empty($record->id)) onclick="openRecordPanel({{
                                    $record->id }})"
                                    @endif>

                                    <td class="table-cell-main">
                                        <div class="table-primary">
                                            <span class="patient-avatar patient-avatar-sm" data-patient-avatar
                                                data-patient-name="{{ $patientName }}"></span>
                                            <div class="dental-record-patient-copy">
                                                <strong class="dental-record-patient-name" data-patient-name>
                                                    {{ $patientName }}
                                                </strong>

                                                <span class="dental-record-patient-sub">
                                                    Dental record
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell-main">
                                        {{ $procedure }}
                                    </td>
                                    <td>
                                        {{ $dentistName }}
                                    </td>
                                    <td>
                                        <span class="table-primary">
                                            <i class="fa-solid fa-calendar-day"></i>
                                            <span>{{ $dateText }}</span>
                                        </span>
                                    </td>
                                    <td class="table-cell-main">
                                        <span class="status-pill {{ $statusClass }}">
                                            <span class="status-dot"></span>
                                            {{ ucfirst(str_replace('-', ' ', $normalizedStatus)) }}
                                        </span>
                                    </td>
                                    <td class="table-action-cell">
                                        <div class="ui-action-group">
                                            @if (!empty($record->id))
                                            <button type="button" class="ui-action-btn ui-action-view"
                                                onclick="event.stopPropagation(); openRecordPanel({{ $record->id }})"
                                                aria-label="View record" data-tooltip="View record">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="dentalRecordGridView" class="table-record-grid dental-record-grid-view" hidden>
                        @foreach ($recordsSource as $record)
                        @php

                        $rawStatus = strtolower(trim($record->status ?? 'pending'));

                        $normalizedStatus = str_replace([' ', '_'],'-',$rawStatus);

                        $statusClass = match ($normalizedStatus) {
                        'completed' => 'status-completed',
                        'ongoing', 'in-progress' => 'status-ongoing',
                        'cancelled','canceled' =>'status-cancelled',
                        'not-started' => 'status-default',
                        default =>'status-pending',
                        };

                        $patientName = $record->patient_name
                        ?? data_get($record,'patient.name')
                        ?? data_get($record,'patient.full_name')
                        ?? 'Unknown Patient';

                        $dentistName = $record->dentist_name
                        ?? data_get($record,'dentist.name')
                        ?? data_get($record,'dentist.full_name')
                        ?? '—';

                        $procedure = $record->procedure ?? '—';
                        $recordDate = null;

                        if (!empty($record->date)) {
                        try {
                        $recordDate = Carbon::parse($record->date);
                        } catch (\Throwable $e) {
                        $recordDate = null;
                        }
                        }

                        $dateText = $recordDate? $recordDate->format('M d, Y'): '—';
                        $dateIso = $recordDate? $recordDate->toDateString(): '';

                        $initial = strtoupper(substr($patientName, 0, 1));
                        @endphp

                        <article class="table-record-card dental-record-grid-card dental-record-item"
                            data-patient="{{ strtolower($patientName) }}" data-procedure="{{ strtolower($procedure) }}"
                            data-dentist="{{ strtolower($dentistName) }}" data-status="{{ $normalizedStatus }}"
                            data-date="{{ $dateIso }}" @if (!empty($record->id))
                            onclick="openRecordPanel({{ $record->id }})"
                            @endif
                            >
                            <div class="table-record-card-layout">
                                <div class="table-record-content">
                                    <div class="table-record-header">
                                        <div class="table-primary">
                                            <span class="patient-avatar patient-avatar-md" data-patient-avatar
                                                data-patient-name="{{ $patientName }}"></span>

                                            <div class="dental-record-patient-copy">
                                                <h3 class="table-record-title" data-patient-name>
                                                    {{ $patientName }}
                                                </h3>

                                                <span class="dental-record-patient-sub">
                                                    Dental record
                                                </span>
                                            </div>
                                        </div>

                                        <span class="status-pill {{ $statusClass }}">
                                            <span class="status-dot"></span>
                                            {{ucfirst(str_replace('-',' ', $normalizedStatus))}}
                                        </span>
                                    </div>

                                    <div class="table-record-meta">
                                        <div class="table-record-row">
                                            <span class="table-record-label">
                                                Procedure
                                            </span>

                                            <span class="table-record-value">
                                                {{ $procedure }}
                                            </span>
                                        </div>

                                        <div class="table-record-row">
                                            <span class="table-record-label">
                                                Dentist
                                            </span>

                                            <span class="table-record-value">
                                                {{ $dentistName }}
                                            </span>
                                        </div>

                                        <div class="table-record-row">
                                            <span class="table-record-label">
                                                Date
                                            </span>

                                            <span class="table-record-value">
                                                <i class="fa-solid fa-calendar-day"></i>
                                                {{ $dateText }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                        @endforeach
                    </div>
                    <div id="dentalRecordEmptyState" class="empty-state-host"></div>
                    @endif
                </div>

                @if ($recordsSource instanceof \Illuminate\Pagination\AbstractPaginator)
                <x-pagination-bar id="dentalRecordsPagebarBottom" info-id="dentalRecordsPageInfoBottom"
                    pagination-id="dentalRecordsPaginationBottom" position="bottom" :show-entries="false"
                    :page-size-value="$recordPerPage" label="records" :total="$recordsSource->total()"
                    :from="$recordsSource->firstItem() ?? 0" :to="$recordsSource->lastItem() ?? 0" />
                @endif
            </section>

            <aside class="dental-records-side-column space-y-5">
                <section class="table-card rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="dental-req-quick-actions-icon w-10 h-10 rounded-2xl bg-red-50 text-[#8B0000] 
                                border border-red-100 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <div>

                            <h2 class="text-sm font-black text-gray-800">Record Insights</h2>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                Summary statistics
                            </p>
                        </div>
                    </div>

                    <div class="record-insights-list">
                        <div class="record-insight-row px-5 py-4 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-[11px] font-black uppercase tracking-wider text-gray-400">Most Common
                                    Procedure
                                </div>

                                <div class="text-sm font-black text-gray-800 truncate">
                                    {{ $topProcedure ?? 'No data yet' }}
                                </div>
                            </div>
                            <span class="status-pill status-default"><i class="fa-solid fa-tooth"></i></span>
                        </div>

                        <div class="record-insight-row px-5 py-4 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-black uppercase tracking-wider text-gray-400">Completed
                                    This Week
                                </div>

                                <div class="text-sm font-black text-gray-800">
                                    {{ number_format($completedThisWeek ?? 0) }}
                                </div>
                            </div>
                            <span class="status-pill status-completed"><i class="fa-solid fa-circle-check"></i></span>
                        </div>

                        <div class="record-insight-row px-5 py-4 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-black uppercase tracking-wider text-gray-400">Patients For
                                    Follow-Up
                                </div>

                                <div class="text-sm font-black text-gray-800">
                                    {{ number_format($patientsForFollowUp ?? 0) }}
                                </div>
                            </div>
                            <span class="status-pill status-pending"><i class="fa-solid fa-user-clock"></i></span>
                        </div>
                    </div>
                </section>

                <section class="card quick-actions-card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <i class="fa-solid fa-bolt"></i>
                            </div>

                            <div>
                                <h2 class="card-title">Quick Actions</h2>
                                <p class="card-subtitle">Common tasks</p>
                            </div>
                        </div>
                    </div>

                    <div class="quick-actions-list">
                        <a href="{{ route($routePrefix . '.reports.ai-generated') }}"
                            class="quick-action quick-action-card">
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

                        <a href="{{ route($appointmentsRouteName) }}" class="quick-action quick-action-card">
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


<x-filter-drawer id="filterModal" title="Filter Records" close-id="dentalRecordFilterCloseBtn"
    close-callback="closeDentalRecordFilters()" clear-id="dentalRecordFilterClearBtn"
    clear-callback="clearDentalRecordFilterDraft()" clear-label="Clear Filters" cancel-id="dentalRecordFilterCancelBtn"
    cancel-callback="closeDentalRecordFilters()" cancel-label="Cancel" apply-id="dentalRecordFilterApplyBtn"
    apply-callback="applyDentalRecordFilters()" apply-label="Show Results" results-id="dentalRecordFilterResultsText">

    <div id="dentalRecordActiveFiltersSection" class="filter-active-section hidden">
        <div class="filter-active-header">
            <span class="filter-active-title">Active Filters</span>

            <button type="button" class="filter-clear-all ui-btn ui-btn-secondary ui-btn-sm"
                onclick="clearDentalRecordFilterDraft()">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Clear All</span>
            </button>
        </div>

        <div id="dentalRecordActiveFilters" class="active-filters-container"></div>
    </div>

    <x-filter-group title="Date Order">
        <div class="filter-chip-row">
            <label class="choice-chip">
                <input type="radio" name="dental_record_sort" value="newest" class="filter-input radio-red chip-radio"
                    data-record-filter="sort">
                <span>Newest First</span>
            </label>

            <label class="choice-chip">
                <input type="radio" name="dental_record_sort" value="oldest" class="filter-input radio-red chip-radio"
                    data-record-filter="sort">
                <span>Oldest First</span>
            </label>
        </div>
    </x-filter-group>

    <x-filter-group title="Sort by Patient Name">
        <div class="filter-chip-row">
            <label class="choice-chip">
                <input type="radio" name="dental_record_name_sort" value="name_asc"
                    class="filter-input radio-red chip-radio" data-record-filter="name_sort">
                <span>A to Z</span>
            </label>

            <label class="choice-chip">
                <input type="radio" name="dental_record_name_sort" value="name_desc"
                    class="filter-input radio-red chip-radio" data-record-filter="name_sort">
                <span>Z to A</span>
            </label>
        </div>
    </x-filter-group>

    <x-filter-group title="Record Status">
        <div class="filter-chip-row">
            @foreach ([
            'not-started' => 'Not Started',
            'pending' => 'Pending',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            ] as $value => $label)
            <label class="choice-chip">
                <input type="radio" name="dental_record_status" value="{{ $value }}"
                    class="filter-input radio-red chip-radio" data-record-filter="status">
                <span>{{ $label }}</span>
            </label>
            @endforeach
        </div>
    </x-filter-group>

    <x-filter-group title="Patient Classification">
        <div class="filter-chip-row">
            @foreach ([
            'student' => 'Student',
            'faculty' => 'Faculty',
            'administrative' => 'Administrative Personnel',
            'dependent_alumni' => 'Dependent & Alumni',
            ] as $value => $label)
            <label class="choice-chip">
                <input type="radio" name="dental_record_classification" value="{{ $value }}"
                    class="filter-input radio-red chip-radio" data-record-filter="classification">
                <span>{{ $label }}</span>
            </label>
            @endforeach
        </div>
    </x-filter-group>

    <x-filter-group title="Filter by Date Range">
        <div id="datePresetGroup" class="filter-chip-row">
            @foreach ([
            'today' => 'Today',
            '7' => 'Last 7 Days',
            '30' => 'Last 30 Days',
            '90' => 'Last 3 Months',
            '180' => 'Last 6 Months',
            '365' => 'Last 12 Months',
            ] as $value => $label)
            <button type="button" class="quick-date-chip" data-record-date="{{ $value }}"
                onclick="setDentalRecordDraftFilter('datePreset', '{{ $value }}')">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </x-filter-group>

    <x-filter-group title="Custom Date Range" class="filter-group-last">
        <div class="filter-date-grid">
            <div class="filter-date-input-wrap">
                <input id="dentalRecordDateFrom" type="text" class="js-flatpickr-date-range-from"
                    placeholder="Start date" readonly autocomplete="off">
                <i class="fa-regular fa-calendar"></i>
            </div>

            <div class="filter-date-input-wrap">
                <input id="dentalRecordDateTo" type="text" class="js-flatpickr-date-range-to" placeholder="End date"
                    readonly autocomplete="off">
                <i class="fa-regular fa-calendar"></i>
            </div>
        </div>
    </x-filter-group>
</x-filter-drawer>

<div id="dentalRecordDetailsModal" class="ui-modal" role="dialog" aria-modal="true"
    aria-labelledby="dentalRecordDetailsModalTitle">
    <div class="ui-modal-card record-modal-wide">
        <div class="modal-hd appointment-modal-header">
            <div class="modal-heading">
                <div class="appointment-modal-header-icon">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
                <div class="appointment-modal-header-copy">
                    <span class="appointment-modal-eyebrow">
                        Patient Clinical Record
                    </span>

                    <h3 id="dentalRecordDetailsModalTitle" class="appointment-modal-title">
                        Dental Record Details
                    </h3>
                </div>
            </div>
            <button type="button" class="modal-x" onclick="closeDentalRecordDetailsModal()"
                aria-label="Close dental record details">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="dentalRecordDetailsModalBody" class="modal-bd">
            <div class="text-center py-8">
                <div class="empty-state-icon !w-[64px] !h-[64px] !rounded-2xl !mb-4">
                    <i class="fa-solid fa-notes-medical !text-[26px]"></i>
                </div>
                <h3 class="empty-state-title !text-[15px]">Select a dental record</h3>
                <p class="empty-state-sub !text-[13px] !mt-2">The full answered patient form will appear here.</p>
            </div>
        </div>

        <div class="modal-ft">
            <button type="button" class="btn-close-modal" onclick="closeDentalRecordDetailsModal()">
                Close
            </button>
        </div>
    </div>
</div>

<template id="dentalRecordOdontogramTemplate">
    <section class="booking-summary-card">
        <div class="booking-summary-card-header flex items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-2 min-w-0">
                <i class="fa-solid fa-tooth"></i>
                <span>Odontogram</span>
            </div>
        </div>
        <div class="booking-summary-card-body">
            @include('components.odontogram-preview', [
                'odontogramData' => [],
                'showEditButton' => false,
            ])
        </div>
    </section>
</template>
@endsection

@section('scripts')
<script>
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    let dentalRecordsLoading = false;
    let dentalRecordsSearchTimer = null;
    let dentalRecordFilterPreviewRequest = 0;

    let dentalRecordsPerPage = Number(@json($recordPerPage)) || 10;
    let dentalRecordsTotal = Number(@json($totalRecordsCount)) || 0;

    let dentalRecordFilters = {
        sort: new URL(window.location.href).searchParams.get('sort') || 'newest',
        status: @json($recordAppliedStatus) || 'all',
        classification: new URL(window.location.href).searchParams.get('classification') || 'all',
        datePreset: new URL(window.location.href).searchParams.get('date_preset') || 'all',
        dateFrom: new URL(window.location.href).searchParams.get('date_from') || '',
        dateTo: new URL(window.location.href).searchParams.get('date_to') || '',
    };

    let dentalRecordFilterDraft = {
        ...dentalRecordFilters
    };

    let dentalRecordsStatus = dentalRecordFilters.status;

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

        if (normalized === 'completed') {
            return 'status-completed';
        }

        if (normalized === 'ongoing' || normalized === 'in-progress') {
            return 'status-ongoing';
        }

        if (normalized === 'cancelled' || normalized === 'canceled') {
            return 'status-cancelled';
        }

        if (normalized === 'not-started') {
            return 'status-default';
        }

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
        const normalizedValue =
            escapeHtml(value || '—')
                .replace(/\n/g, '<br>');

        return `
            <div class="flex items-start gap-3 py-2 border-b border-gray-100 last:border-0">
                <span class="w-28 flex-shrink-0 text-[11px] font-black uppercase tracking-wider text-gray-400">${escapeHtml(label)}</span>
                <span class="min-w-0 text-sm font-bold text-gray-800 break-words">${normalizedValue}</span>
            </div>`;
    }

    function profileInfoRow(item = {}) {
        const icon = item.icon || 'fa-regular fa-circle';
        const label = item.label || 'Item';
        const value = item.value ?? '';

        let formattedValue = escapeHtml(value);
        if (label.toLowerCase() === 'emergency contact') {
            const parts = String(value)
                .split(/\s*•\s*/)
                .map(part => part.trim())
                .filter(Boolean);

            const [name, phone, relationship] = parts;

            formattedValue = `
                    <span class="block">
                        ${escapeHtml(name || 'N/A')}
                    </span>

                    <span class="global-info-subvalue block">
                        ${escapeHtml(phone || 'N/A')}
                        ${relationship ? ` · ${escapeHtml(relationship)}` : ''}
                    </span>
                `;
        }

        if (label.toLowerCase() === 'program / year') {
            const parts = String(value)
                .split(/\s*•\s*/)
                .map(part => part.trim())
                .filter(Boolean);

            const program = parts[0] || 'N/A';
            const academicLevel = parts.slice(1).join(' · ');

            formattedValue = `
                    <span class="block">
                        ${escapeHtml(program)}
                    </span>

                    ${academicLevel
                    ? `
                                <span class="global-info-subvalue block">
                                    ${escapeHtml(academicLevel)}
                                </span>
                            `
                    : ''
                }
                    `;
        }

        return `
                <div class="global-info-item global-info-item-compact">
                    <span class="global-info-icon status-default">
                        <i class="${escapeHtml(icon)}"></i>
                    </span>

                    <div class="global-info-copy min-w-0">
                        <span class="global-info-label">
                            ${escapeHtml(label)}
                        </span>

                        <strong class="global-info-value break-words">
                            ${formattedValue}
                        </strong>
                    </div>
                </div>`;
    }

    function reviewRow(label, value) {
        const hasValue =
            value &&
            String(value).trim() !== '';

        const normalizedValue =
            hasValue
                ? escapeHtml(String(value)).replace(/\n/g, '<br>')
                : '<span class="booking-summary-muted">N/A</span>';

        return `
            <p class="booking-summary-row">
                <span class="booking-summary-row-label">
                    ${escapeHtml(label)}:
                </span>
                ${normalizedValue}
            </p>`;
    }

    function reviewSubSection(title, rows = []) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return '';
        }

        return `
            <section class="booking-summary-section">
                <div class="booking-summary-section-title">
                    ${escapeHtml(title)}
                </div>
                <div class="booking-summary-section-body">
                    <div class="grid grid-cols-1 gap-x-8 gap-y-1 sm:grid-cols-2">
                        ${rows.map(item => reviewRow(item.label, item.value)).join('')}
                    </div>
                </div>
            </section>`;
    }

    function reviewFullWidthSection(title, rows = []) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return '';
        }

        return `
            <section class="booking-summary-section">
                <div class="booking-summary-section-title">
                    ${escapeHtml(title)}
                </div>
                <div class="booking-summary-section-body">
                    <div class="grid grid-cols-1 gap-y-1">
                        ${rows.map(item => reviewRow(item.label, item.value)).join('')}
                    </div>
                </div>
            </section>`;
    }

    function recordSummaryCard(title, icon, body) {
        return `
            <section class="booking-summary-card">
                <div class="booking-summary-card-header flex items-center justify-between gap-4 w-full">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="fa-solid ${escapeHtml(icon)}"></i>
                        <span>${escapeHtml(title)}</span>
                    </div>
                </div>
                <div class="booking-summary-card-body">
                    ${body}
                </div>
            </section>`;
    }

    function renderPatientInformationCard(profileFields = []) {
        if (!Array.isArray(profileFields) || profileFields.length === 0) {
            return '';
        }

        return recordSummaryCard(
            'Patient Information',
            'fa-user',
            `
                <div class="grid grid-cols-1 gap-y-1 sm:grid-cols-2 sm:gap-x-8">
                    ${profileFields.filter(item => {
                const label = String(item.label || '').trim().toLowerCase();
                return !['emergency contact', 'pwd',].includes(label);
            })
                .map(item => reviewRow(
                    item.label || 'Field',
                    item.value || 'N/A'
                )
                )
                .join('')}
                                </div>
                            `
        );
    }

    function renderRecordSection(section = {}) {
        if (Array.isArray(section.groups) && section.groups.length > 0) {
            const body = section.groups
                .map(group => reviewSubSection(group.title || 'Section', Array.isArray(group.rows) ? group.rows : []))
                .filter(Boolean)
                .join('');

            return recordSummaryCard(section.title || 'Record Section', section.icon || 'fa-regular fa-circle', body);
        }

        if (Array.isArray(section.rows) && section.rows.length > 0) {
            return recordSummaryCard(
                section.title || 'Record Section',
                section.icon || 'fa-regular fa-circle',
                `
                    <div class="grid grid-cols-1 gap-y-1">
                        ${section.rows
                    .map(item => reviewRow(item.label, item.value))
                    .join('')}
                    </div>
                `
            );
        }
    }

    function openDentalRecordDetailsModal() {
        const modal = document.getElementById('dentalRecordDetailsModal');

        if (!modal) {
            return;
        }

        modal.classList.remove('closing');
        modal.classList.add('open');
        document.documentElement.classList.add('modal-lock');
        document.body.classList.add('modal-lock');
    }

    function closeDentalRecordDetailsModal() {
        const modal = document.getElementById('dentalRecordDetailsModal');

        if (!modal || !modal.classList.contains('open')) {
            return;
        }

        modal.classList.add('closing');

        setTimeout(() => {
            modal.classList.remove('open', 'closing');
            document.documentElement.classList.remove('modal-lock');
            document.body.classList.remove('modal-lock');
        }, 160);
    }

    function hasOdontogramSnapshot(data) {
        if (Array.isArray(data)) {
            return data.length > 0;
        }

        return data && typeof data === 'object' && Object.keys(data).length > 0;
    }

    async function appendDentalRecordOdontogram(container, odontogramData) {
        if (!container) {
            return;
        }

        if (!hasOdontogramSnapshot(odontogramData)) {
            container.insertAdjacentHTML('beforeend', recordSummaryCard(
                'Odontogram',
                'fa-tooth',
                `
                    <div class="empty-state empty-state-compact">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-tooth"></i>
                        </div>
                        <h3 class="empty-state-title">No odontogram recorded</h3>
                        <p class="empty-state-sub">No odontogram was recorded for this appointment.</p>
                    </div>
                `
            ));
            return;
        }

        const template = document.getElementById('dentalRecordOdontogramTemplate');

        if (!template) {
            return;
        }

        const fragment = template.content.cloneNode(true);
        const preview = fragment.querySelector('[data-odontogram-preview]');

        if (!preview) {
            return;
        }

        preview.dataset.odontogram = JSON.stringify(odontogramData);
        container.appendChild(fragment);

        try {
            const module = await window.loadOdontogramPreviewModule?.();
            module?.initOdontogramPreviews?.(preview);
        } catch (error) {
            console.error('Unable to load appointment odontogram snapshot.', error);
        }
    }

    function setDentalRecordDetailsModalData(data) {
        const title = document.getElementById('dentalRecordDetailsModalTitle');
        const body = document.getElementById('dentalRecordDetailsModalBody');

        if (!title || !body) {
            return;
        }

        title.textContent = data.patient_name || 'Patient Clinical Record';

        const sections = Array.isArray(data.record_sections) ? data.record_sections : [];
        const profileFields = Array.isArray(data.profile_fields) ? data.profile_fields : [];
        const emergencyContact = data.emergency_contact || {};

        body.innerHTML = `
            <div class="space-y-4" data-dental-record-sections>
                ${renderPatientInformationCard(profileFields)}

                ${recordSummaryCard(
            'Emergency Contact',
            'fa-phone',
            `
                        <div class="grid grid-cols-1 gap-y-1 sm:grid-cols-3 sm:gap-x-8">
                            ${reviewRow('Name', emergencyContact.name || 'N/A')}
                            ${reviewRow('Number', emergencyContact.number || 'N/A')}
                            ${reviewRow('Relation', emergencyContact.relation || 'N/A')}
                        </div>
                    `
        )}

                ${sections
                .map(section => renderRecordSection(section))
                .filter(Boolean)
                .join('')
            }
            </div>`;

        appendDentalRecordOdontogram(
            body.querySelector('[data-dental-record-sections]'),
            data.odontogram_data
        );
    }

    function initDentalRecordDetailsModal() {
        const modal = document.getElementById('dentalRecordDetailsModal');
        const card = modal?.querySelector('.ui-modal-card');

        if (!modal || modal.dataset.initialized === 'true') {
            return;
        }

        modal.dataset.initialized = 'true';

        modal.addEventListener('click', event => {
            if (card && !card.contains(event.target)) {
                closeDentalRecordDetailsModal();
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && modal.classList.contains('open')) {
                closeDentalRecordDetailsModal();
            }
        });
    }

    async function openRecordPanel(id) {
        const modalTitle = document.getElementById('dentalRecordDetailsModalTitle');
        const modalBody = document.getElementById('dentalRecordDetailsModalBody');

        if (!modalTitle || !modalBody) return;

        modalTitle.textContent = 'Loading record...';
        modalBody.innerHTML = `
            <div class="text-center py-10 text-gray-300">
                <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
            </div>`;
        openDentalRecordDetailsModal();

        try {
            const res = await fetch(`/admin/dental-records/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!res.ok) throw new Error('Failed to fetch record.');

            const data = await res.json();

            window.currentDentalRecordData = data;
            setDentalRecordDetailsModalData(data);
        } catch (error) {
            modalTitle.textContent = 'Unable to load record';
            modalBody.innerHTML = `
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

    function updateDentalRecordsPagination(pagination) {
        if (!pagination) {
            return;
        }

        const pagebars = [
            document.getElementById('dentalRecordsPagebarTop'),
            document.getElementById('dentalRecordsPagebarBottom'),
        ].filter(Boolean);

        const infoElements = [
            document.getElementById('dentalRecordsPageInfoTop'),
            document.getElementById('dentalRecordsPageInfoBottom'),
        ].filter(Boolean);

        const paginationHosts = [
            document.getElementById('dentalRecordsPaginationTop'),
            document.getElementById('dentalRecordsPaginationBottom'),
        ].filter(Boolean);

        window.renderGlobalPagination?.({
            currentPage: pagination.current_page,
            lastPage: pagination.last_page,
            total: pagination.total,
            from: pagination.from,
            to: pagination.to,
            containers: paginationHosts,
            infoElements: infoElements,
            bars: pagebars,
            itemLabel: 'records',

            onPageChange(page) {
                loadDentalRecordsPage(
                    page
                );
            },
        });
    }

    function dentalRecordFilterCount(filters = dentalRecordFilters) {
        let count = 0;

        if (filters.sort !== 'newest') count++;
        if (filters.status !== 'all') count++;
        if (filters.classification !== 'all') count++;
        if (filters.datePreset !== 'all' || filters.dateFrom || filters.dateTo) count++;

        return count;
    }

    function updateDentalRecordFilterButton() {
        const button = document.getElementById('dentalRecordFilterBtn');
        const badge = document.getElementById('dentalRecordFilterBadge');
        const reset = document.getElementById('dentalRecordFilterResetBtn');
        const count = dentalRecordFilterCount();

        button?.classList.toggle('has-filters', count > 0);
        button?.setAttribute('aria-pressed', count > 0 ? 'true' : 'false');

        if (badge) {
            badge.textContent = count > 0 ? String(count) : '';
            badge.classList.toggle('show', count > 0);
        }

        reset?.classList.toggle('hidden', count === 0);
    }

    function updateDentalRecordFilterDraftUi() {
        document
            .querySelectorAll('#filterModal [data-record-filter]')
            .forEach(input => {
                const key = input.dataset.recordFilter;
                let expected = '';

                if (key === 'sort') {
                    expected =
                        ['newest', 'oldest'].includes(dentalRecordFilterDraft.sort)
                            ? dentalRecordFilterDraft.sort
                            : '';
                }

                if (key === 'name_sort') {
                    expected =
                        ['name_asc', 'name_desc'].includes(dentalRecordFilterDraft.sort)
                            ? dentalRecordFilterDraft.sort
                            : '';
                }

                if (key === 'status') {
                    expected =
                        dentalRecordFilterDraft.status !== 'all'
                            ? dentalRecordFilterDraft.status
                            : '';
                }

                if (key === 'classification') {
                    expected =
                        dentalRecordFilterDraft.classification !== 'all'
                            ? dentalRecordFilterDraft.classification
                            : '';
                }

                input.checked = input.value === expected;
            });

        document.querySelectorAll('#filterModal [data-record-date]')
            .forEach(button => {
                button.classList.toggle(
                    'active',
                    button.dataset.recordDate === dentalRecordFilterDraft.datePreset
                );
            });

        const dateFrom = document.getElementById('dentalRecordDateFrom');
        const dateTo = document.getElementById('dentalRecordDateTo');

        if (dateFrom) {
            dateFrom.value = dentalRecordFilterDraft.dateFrom || '';
        }

        if (dateTo) {
            dateTo.value = dentalRecordFilterDraft.dateTo || '';
        }

        renderDentalRecordActiveFilters();
        updateDentalRecordFilterPreviewCount();

    }

    function renderDentalRecordActiveFilters() {
        const section = document.getElementById('dentalRecordActiveFiltersSection');
        const host = document.getElementById('dentalRecordActiveFilters');

        if (!section || !host) {
            return;
        }

        const chips = [];
        const sortLabels = {
            oldest: 'Oldest First',
            name_asc: 'Patient Name A-Z',
            name_desc: 'Patient Name Z-A',
        };

        if (dentalRecordFilterDraft.sort !== 'newest') {
            chips.push([
                `Sort: ${sortLabels[dentalRecordFilterDraft.sort]
                || 'Newest First'
                }`,
                'sort'
            ]);
        }

        if (dentalRecordFilterDraft.status !== 'all') {
            chips.push([
                `Status: ${statusLabel(
                    dentalRecordFilterDraft.status
                )}`,
                'status'
            ]);
        }

        if (dentalRecordFilterDraft.classification !== 'all') {
            const classificationLabels = {
                student: 'Student',
                faculty: 'Faculty',
                administrative: 'Administrative Personnel',
                dependent_alumni: 'Dependent & Alumni',
            };

            chips.push([
                `Classification: ${classificationLabels[
                dentalRecordFilterDraft.classification
                ] || dentalRecordFilterDraft.classification
                }`,
                'classification'
            ]);
        }

        if (dentalRecordFilterDraft.datePreset !== 'all' || dentalRecordFilterDraft.dateFrom || dentalRecordFilterDraft.dateTo) {
            const dateLabels = {
                today: 'Today',
                7: 'Last 7 Days',
                30: 'Last 30 Days',
                90: 'Last 3 Months',
                180: 'Last 6 Months',
                365: 'Last 12 Months',
            };

            let dateLabel = '';

            if (dentalRecordFilterDraft.datePreset === 'custom') {
                dateLabel = [
                    dentalRecordFilterDraft.dateFrom,
                    dentalRecordFilterDraft.dateTo,
                ]
                    .filter(Boolean)
                    .join(' to ');
            } else {
                dateLabel =
                    dateLabels[
                    dentalRecordFilterDraft.datePreset
                    ] || 'Custom Date';
            }

            chips.push([
                `Date: ${dateLabel}`,
                'date'
            ]);
        }

        host.innerHTML = chips
            .map(([label, key]) => `
                <span class="filter-chip">
                    <span>${escapeHtml(label)}</span>

                    <button
                        type="button"
                        class="filter-chip-remove"
                        onclick="removeDentalRecordDraftFilter('${key}')"
                        aria-label="Remove ${escapeHtml(label)}">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </span>
            `)
            .join('');

        section.classList.toggle(
            'hidden',
            chips.length === 0
        );
    }

    function openDentalRecordFilters() {
        dentalRecordFilterDraft = {
            ...dentalRecordFilters
        };

        updateDentalRecordFilterDraftUi();

        if (typeof window.openFilterDrawer === 'function') {
            window.openFilterDrawer('filterModal');
            return;
        }

        const drawer = document.getElementById('filterModal');

        drawer?.classList.remove('closing');
        drawer?.classList.add('open');
        drawer?.setAttribute('aria-hidden', 'false');

        document.documentElement.classList.add('modal-lock');
        document.body.classList.add('modal-lock');
    }

    function closeDentalRecordFilters() {
        if (typeof window.closeFilterDrawer === 'function') {
            window.closeFilterDrawer(
                'filterModal'
            );
            return;
        }

        const drawer = document.getElementById('filterModal');

        if (!drawer || !drawer.classList.contains('open')) {
            return;
        }

        drawer.classList.add('closing');
        drawer.classList.remove('open');

        window.setTimeout(() => {
            drawer.classList.remove('closing');
            drawer.setAttribute('aria-hidden', 'true');
            document.documentElement.classList.remove('modal-lock');
            document.body.classList.remove('modal-lock');
        }, 300);
    }

    function setDentalRecordDraftFilter(key, value) {
        dentalRecordFilterDraft[key] = value;

        if (key === 'datePreset' && value !== 'custom') {
            dentalRecordFilterDraft.dateFrom = '';
            dentalRecordFilterDraft.dateTo = '';
        }

        updateDentalRecordFilterDraftUi();
    }

    function setDentalRecordCustomDate() {
        dentalRecordFilterDraft.dateFrom = document.getElementById('dentalRecordDateFrom')?.value || '';
        dentalRecordFilterDraft.dateTo = document.getElementById('dentalRecordDateTo')?.value || '';
        dentalRecordFilterDraft.datePreset = dentalRecordFilterDraft.dateFrom || dentalRecordFilterDraft.dateTo
            ? 'custom'
            : 'all';

        updateDentalRecordFilterDraftUi();
    }

    function removeDentalRecordDraftFilter(key) {
        if (key === 'sort') {
            dentalRecordFilterDraft.sort = 'newest';
        }

        if (key === 'status') {
            dentalRecordFilterDraft.status = 'all';
        }

        if (key === 'classification') {
            dentalRecordFilterDraft.classification = 'all';
        }

        if (key === 'date') {
            dentalRecordFilterDraft.datePreset = 'all';
            dentalRecordFilterDraft.dateFrom = '';
            dentalRecordFilterDraft.dateTo = '';
        }

        updateDentalRecordFilterDraftUi();
    }

    function clearDentalRecordFilterDraft() {
        dentalRecordFilterDraft = {
            sort: 'newest',
            status: 'all',
            classification: 'all',
            datePreset: 'all',
            dateFrom: '',
            dateTo: '',
        };

        updateDentalRecordFilterDraftUi();
    }

    function clearDentalRecordFilters() {
        dentalRecordFilters = {
            sort: 'newest',
            status: 'all',
            classification: 'all',
            datePreset: 'all',
            dateFrom: '',
            dateTo: '',
        };

        dentalRecordFilterDraft = {
            ...dentalRecordFilters
        };

        dentalRecordsStatus = 'all';

        updateDentalRecordFilterButton();
        updateDentalRecordFilterDraftUi();
        loadDentalRecordsPage(1);
    }

    function applyDentalRecordFilters() {
        dentalRecordFilters = {
            ...dentalRecordFilterDraft
        };

        dentalRecordsStatus = dentalRecordFilters.status;

        updateDentalRecordFilterButton();
        closeDentalRecordFilters();
        loadDentalRecordsPage(1);
    }

    function syncDentalRecordFilterParams(url, filters = dentalRecordFilters) {

        if (filters.status && filters.status !== 'all') {
            url.searchParams.set('status', filters.status);
        } else {
            url.searchParams.delete('status');
        }

        if (filters.sort && filters.sort !== 'newest') {
            url.searchParams.set('sort', filters.sort);
        } else {
            url.searchParams.delete('sort');
        }

        if (filters.classification && filters.classification !== 'all') {
            url.searchParams.set('classification', filters.classification);
        } else {
            url.searchParams.delete('classification');
        }

        if (filters.datePreset && filters.datePreset !== 'all') {
            url.searchParams.set('date_preset', filters.datePreset);
        } else {
            url.searchParams.delete('date_preset');
        }

        if (filters.dateFrom) {
            url.searchParams.set('date_from', filters.dateFrom);
        } else {
            url.searchParams.delete('date_from');
        }

        if (filters.dateTo) {
            url.searchParams.set('date_to', filters.dateTo);
        } else {
            url.searchParams.delete('date_to');
        }
    }

    async function updateDentalRecordFilterPreviewCount() {
        const requestId = ++dentalRecordFilterPreviewRequest;
        const resultsText = document.getElementById('dentalRecordFilterResultsText');

        if (!resultsText) {
            return;
        }

        const url = new URL(window.location.href);

        url.searchParams.set('page', '1');
        url.searchParams.set('per_page', String(dentalRecordsPerPage));

        const searchInput = document.getElementById('dentalRecordSearch');
        const search = String(searchInput?.value || '').trim();

        if (search) {
            url.searchParams.set(
                'search',
                search
            );
        } else {
            url.searchParams.delete(
                'search'
            );
        }

        syncDentalRecordFilterParams(url, dentalRecordFilterDraft);

        resultsText.textContent = 'Checking results...';

        try {
            const response = await fetch(
                url.toString(),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            if (!response.ok) {
                throw new Error(
                    'Unable to preview filter results.'
                );
            }

            const payload = await response.json();

            if (requestId !== dentalRecordFilterPreviewRequest) {
                return;
            }
            const total = Number(payload.pagination?.total) || 0;

            resultsText.textContent =
                total === 1
                    ? 'Show 1 result'
                    : `Show ${total} results`;
        } catch (error) {
            resultsText.textContent =
                'Show results';
        }
    }

    function renderDentalRecordsEmptyState() {
        const host = document.getElementById('dentalRecordEmptyState');

        if (!host) {
            return;
        }

        const hasRenderedResults =
            (document.querySelectorAll('#dentalRecordsTableBody tr').length > 0) ||
            (document.querySelectorAll('#dentalRecordGridView .dental-record-item').length > 0);

        if (hasRenderedResults) {
            host.innerHTML = '';
            host.classList.remove(
                'show'
            );

            return;
        }

        const searchInput = document.getElementById('dentalRecordSearch');
        const query = String(searchInput?.value || '').trim();

        if (query) {
            window.EmptyState?.renderSearch({
                host,

                input:
                    '#dentalRecordSearch',

                query,

                message:
                    'Try another patient name, procedure, dentist, or status.',
            });

            return;
        }

        if (dentalRecordFilterCount() > 0) {
            const states = {
                today: {
                    icon: 'fa-clock',
                    title: 'No records added today',
                    message: 'Dental records created today will appear here.',
                },

                pending: {
                    icon: 'fa-user-clock',
                    title: 'No pending dental records',
                    message: 'Pending dental records will appear here once available.',
                },

                ongoing: {
                    icon: 'fa-spinner',
                    title: 'No ongoing dental records',
                    message: 'Ongoing dental procedures will appear here once started.',
                },

                completed: {
                    icon: 'fa-check-double',
                    title: 'No completed dental records',
                    message: 'Completed dental records will appear here once finalized.',
                },

                cancelled: {
                    icon: 'fa-calendar-xmark',
                    title: 'No cancelled dental records',
                    message: 'Cancelled dental records will appear here once available.',
                },
            };

            const copy =
                states[
                dentalRecordsStatus
                ] || {
                    icon: 'fa-sliders',
                    title: 'No matching dental records',
                    message: 'Try changing the selected dental record filters.',
                };

            window.EmptyState?.render({
                host, icon: copy.icon, title: copy.title, message: copy.message,

                actionHtml: `
                    <button
                        type="button"
                        class="empty-state-btn"
                        data-empty-action="clear-filters"
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear filters
                    </button>
                `,
            });

            host
                .querySelector('[data-empty-action="clear-filters"]')
                ?.addEventListener('click', clearDentalRecordFilters);

            return;
        }

        window.EmptyState?.render({
            host,
            icon: 'fa-notes-medical',
            title: 'No dental records found',
            message: 'New records will appear here once they are added.',
        });
    }

    function refreshDentalRecordViewToggle() {
        const toggle = document.getElementById('dentalRecordViewToggle');

        if (!toggle) {
            return;
        }

        const savedMode = localStorage.getItem('admin_dental_records_view') || toggle.dataset.currentView || 'list';

        delete toggle.dataset.globalViewInitialized;
        delete toggle.dataset.currentView;

        toggle.__setGlobalViewMode = null;
        toggle.__getGlobalViewMode = null;

        window.initGlobalViewToggles?.(document);

        window.setGlobalViewMode?.(
            'dentalRecordViewToggle',
            savedMode,
            {
                persist: false,
            }
        );
    }

    async function loadDentalRecordsPage(
        page = 1
    ) {
        if (dentalRecordsLoading) {
            return;
        }

        dentalRecordsLoading = true;

        const searchInput = document.getElementById('dentalRecordSearch');

        const pagebars = [
            document.getElementById(
                'dentalRecordsPagebarTop'
            ),
            document.getElementById(
                'dentalRecordsPagebarBottom'
            ),
        ].filter(Boolean);

        const search = String(searchInput?.value || '').trim();
        const url = new URL(window.location.href);

        url.searchParams.set(
            'page',
            String(page)
        );

        url.searchParams.set(
            'per_page',
            String(
                dentalRecordsPerPage
            )
        );

        if (search) {
            url.searchParams.set(
                'search',
                search
            );
        } else {
            url.searchParams.delete(
                'search'
            );
        }

        syncDentalRecordFilterParams(url);

        try {
            pagebars.forEach(
                pagebar => {
                    pagebar?.classList.add(
                        'is-loading'
                    );
                }
            );

            const response =
                await fetch(
                    url.toString(),
                    {
                        headers: {
                            Accept:
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',
                        },

                        credentials:
                            'same-origin',
                    }
                );

            if (!response.ok) {
                throw new Error(
                    'Unable to load dental records.'
                );
            }

            const payload = await response.json();

            if (!payload.success || !payload.html) {
                throw new Error(
                    'Invalid dental records response.'
                );
            }

            const parsed =
                new DOMParser()
                    .parseFromString(
                        payload.html,
                        'text/html'
                    );

            const nextRegion =
                parsed.getElementById(
                    'dentalRecordsResultsRegion'
                );

            const currentRegion =
                document.getElementById(
                    'dentalRecordsResultsRegion'
                );

            if (!nextRegion || !currentRegion) {
                throw new Error(
                    'Dental records results region was not found.'
                );
            }

            currentRegion.innerHTML = nextRegion.innerHTML;
            window.PatientUI?.initAvatars?.(currentRegion);

            window.initGlobalPageSizeSelects?.(currentRegion);

            updateDentalRecordsPagination(payload.pagination);

            dentalRecordsTotal = Number(payload.pagination?.total) || 0;

            updateDentalRecordFilterDraftUi();
            updateDentalRecordFilterButton();

            window.history.replaceState(
                {},
                '',
                url.toString()
            );

            refreshDentalRecordViewToggle();
            renderDentalRecordsEmptyState();

        } catch (error) {
            window.showToast?.({
                type: 'error',

                title:
                    'Unable to load records',

                message:
                    error.message ||
                    'Please try again.',
            });
        } finally {
            dentalRecordsLoading = false;

            pagebars.forEach(
                pagebar => {
                    pagebar?.classList.remove(
                        'is-loading'
                    );
                }
            );
        }
    }

    window.changeDentalRecordsPageSize =
        function (value) {
            const size = Number(value);

            dentalRecordsPerPage =
                [10, 20, 50, 100]
                    .includes(size)
                    ? size
                    : 10;

            loadDentalRecordsPage(1);
        };

    document.addEventListener(
        'DOMContentLoaded',
        () => {
            const searchInput = document.getElementById('dentalRecordSearch');

            searchInput?.addEventListener(
                'input',
                () => {
                    clearTimeout(
                        dentalRecordsSearchTimer
                    );

                    dentalRecordsSearchTimer =
                        window.setTimeout(
                            () => {
                                loadDentalRecordsPage(
                                    1
                                );
                            },
                            300
                        );
                }
            );

            window.initSearchClearButtons?.(document);
            window.initGlobalVoiceInputs?.(document);
            window.initGlobalViewToggles?.(document);
            window.PatientUI?.initAvatars?.(document);

            initDentalRecordDetailsModal();

            const searchWrapper = searchInput?.closest('[data-search-wrapper]');
            const searchClear = searchWrapper?.querySelector('[data-search-clear]');

            searchClear?.addEventListener('click', () => {
                clearTimeout(dentalRecordsSearchTimer);

                if (typeof window.clearSearchInput === 'function') {
                    window.clearSearchInput(searchInput);
                    return;
                }

                if (searchInput) {
                    searchInput.value = '';

                    searchInput.dispatchEvent(
                        new Event('input', {
                            bubbles: true,
                        })
                    );

                    searchInput.focus();
                }
            });

            document.querySelectorAll(
                '#filterModal [data-record-filter]'
            )
                .forEach(input => {
                    input.addEventListener(
                        'change',
                        () => {
                            if (!input.checked) {
                                return;
                            }

                            const key = input.dataset.recordFilter;

                            if (key === 'sort' || key === 'name_sort') {
                                dentalRecordFilterDraft.sort = input.value;
                            }

                            if (key === 'status') {
                                dentalRecordFilterDraft.status = input.value;
                            }

                            if (key === 'classification') {
                                dentalRecordFilterDraft.classification = input.value;
                            }

                            updateDentalRecordFilterDraftUi();
                        }
                    );
                });

            const dateFrom = document.getElementById('dentalRecordDateFrom');
            const dateTo = document.getElementById('dentalRecordDateTo');

            [dateFrom, dateTo]
                .filter(Boolean)
                .forEach(input => {
                    input.addEventListener(
                        'change',
                        setDentalRecordCustomDate
                    );
                });

            updateDentalRecordsPagination(
                @json($recordPaginationMeta)
            );

            updateDentalRecordFilterButton();
            updateDentalRecordFilterDraftUi();
            refreshDentalRecordViewToggle();
            renderDentalRecordsEmptyState();
        });
</script>
@endsection
