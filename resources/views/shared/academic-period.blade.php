@extends('layouts.app')

@section('layout-role', $layoutRole ?? (request()->routeIs('dentist.*') ? 'dentist' : 'admin'))

@section('title', 'Academic Period')

@section('styles')
    @vite('resources/css/pages/shared/academic-period.css')
@endsection

@section('content')

    @php
        $calendarPeriodsPayload = collect($calendarPeriods ?? [])
            ->sortBy('start_date')
            ->map(function ($period) {
                return [
                    'id' => $period->id,
                    'academic_year' => $period->academic_year,
                    'semester' => $period->semester,
                    'start_date' => optional($period->start_date)->format('Y-m-d'),
                    'end_date' => optional($period->end_date)->format('Y-m-d'),
                ];
            })
            ->values()
            ->all();

        $holidayEvents = collect($holidays ?? [])
            ->map(function ($name, $date) {
                return [
                    'date' => $date,
                    'label' => $name,
                    'year' => date('Y', strtotime($date)),
                    'type' => 'holiday',
                ];
            })
            ->values()
            ->all();

        $activePeriodPayload = $activePeriod
            ? [
                'id' => $activePeriod->id,
                'academic_year' => $activePeriod->academic_year,
                'semester' => $activePeriod->semester,
                'start_date' => optional($activePeriod->start_date)->format('Y-m-d'),
                'end_date' => optional($activePeriod->end_date)->format('Y-m-d'),
                'description' => $activePeriod->description,
                'is_active' => (bool) $activePeriod->is_active,

                'update_url' => route($routeNames['update'], $activePeriod),
            ]
            : null;

        $layoutRole = $layoutRole ?? (request()->routeIs('dentist.*') ? 'dentist' : 'admin');
        $isDentistView = $layoutRole === 'dentist';

        $authUser = auth()->user();
        $canCreateAcademicPeriod = $authUser?->hasPermission('create_academic_period') ?? false;
        $createAcademicPeriodUnauthorizedMessage = 'You are not authorized to add academic periods.';
    @endphp

    <main id="mainContent" class="app-page-shell page-enter mode-list">
        <div class="full">

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <div class="font-bold mb-1">Please fix the following:</div>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <div class="flex items-center gap-2 font-bold">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if ($isDentistView)
                <div class="dentist-hero mb-6">
                    <div class="dentist-hero-content">
                        <div class="dentist-hero-icon">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="dentist-hero-eyebrow">
                                <i class="fa-solid fa-tooth"></i>
                                Appointment Management
                            </div>

                            <h2 class="dentist-hero-title">Academic Periods</h2>
                        </div>
                    </div>

                    <div class="dentist-hero-actions">
                        <button type="button" class="ui-btn ui-btn-secondary" data-open-modal="syncFlssModal">
                            <i class="fa-solid fa-rotate"></i>
                            <span>Sync from FLSS</span>
                        </button>

                        <button id="openAddPeriodBtn" type="button" class="ui-btn ui-btn-primary"
                            @if ($canCreateAcademicPeriod) data-open-modal="addModal"
                        @else
                            data-academic-permission-trigger="create"
                            aria-disabled="true"
                            title="{{ $createAcademicPeriodUnauthorizedMessage }}" @endif>
                            <i class="fa-solid fa-plus"></i>
                            <span>Add Period</span>
                        </button>
                    </div>
                </div>
            @else
                <div class="page-banner">
                    <div class="page-banner-inner">
                        <div>
                            <h1 class="page-title">Academic Periods</h1>
                        </div>

                        <div class="admin-banner-actions">
                            <button type="button" class="ui-btn ui-btn-secondary" data-open-modal="syncFlssModal">
                                <i class="fa-solid fa-rotate"></i>
                                <span>Sync from FLSS</span>
                            </button>

                            <button id="openAddPeriodBtn" type="button" class="ui-btn ui-btn-secondary"
                                @if ($canCreateAcademicPeriod) data-open-modal="addModal"
                            @else
                                data-academic-permission-trigger="create"
                                aria-disabled="true"
                                title="{{ $createAcademicPeriodUnauthorizedMessage }}" @endif>
                                <i class="fa-solid fa-plus"></i>
                                <span>Add Period</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="w-full">

                <div class="active-banner mb-6" id="activeBannerWrap">
                    <div class="active-banner-inner">
                        <div class="ap-active-summary-grid">
                            <div>
                                <div class="ap-active-summary-heading">
                                    <i class="fa-solid fa-calendar text-sm"></i>
                                    <p class="text-[10px] tracking-widest uppercase font-semibold">Current
                                        Semester</p>
                                </div>
                                <p class="text-xl font-bold" id="bannerSem">
                                    {{ $activePeriod?->semester ?? 'No Active Period' }}
                                </p>
                            </div>
                            <div>
                                <div class="ap-active-summary-heading">
                                    <span class="inline-flex h-4 w-4 items-center justify-center leading-none shrink-0">
                                        <i class="fa-solid fa-graduation-cap text-sm"></i>
                                    </span>
                                    <p class="text-[10px] tracking-widest uppercase font-semibold">Academic
                                        Year
                                    </p>
                                </div>
                                <p class="text-xl font-bold" id="bannerYear">
                                    {{ $activePeriod?->academic_year ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <div class="ap-active-summary-heading">
                                    <i class="fa-solid fa-clock text-sm"></i>
                                    <p class="text-[10px] tracking-widest uppercase font-semibold">Period Ends
                                    </p>
                                </div>
                                <p class="text-xl font-bold" id="bannerEnd">
                                    {{ $activePeriod ? $activePeriod->end_date->format('F d, Y') : '—' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 lg:flex-shrink-0 lg:w-64">
                            <div>
                                <div class="flex justify-between items-center mb-1.5">
                                    <span class="text-[10px] uppercase tracking-widest font-semibold">Semester
                                        Progress</span>
                                    <span class="text-[11px] font-bold" id="bannerPct">
                                        {{ $activePeriod?->progress_percent ?? 0 }}%
                                    </span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" id="bannerFill"
                                        style="width:{{ $activePeriod?->progress_percent ?? 0 }}%;">
                                    </div>
                                </div>
                                <p class="text-[10px] mt-1" id="bannerDaysLeft">
                                    {{ $activePeriod
                                        ? $activePeriod->days_remaining . ' day' . ($activePeriod->days_remaining !== 1 ? 's' : '') . ' remaining'
                                        : 'No active period' }}
                                </p>
                            </div>

                            <button type="button" id="manageActivePeriodBtn"
                                onclick='@if ($activePeriodPayload) openEditModal(@json($activePeriodPayload)) @endif'
                                class="ui-btn ui-btn-primary ">
                                <i class="fa-solid fa-gear"></i>
                                <span>Manage Period</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="ap-content-layout">
                    <div class="ap-periods-column">
                        <div class="table-card">

                            <div class="table-toolbar">

                                <div class="table-toolbar-title">
                                    <div class="card-header-icon">
                                        <i class="fa-solid fa-school"></i>
                                    </div>

                                    <span class="card-title">
                                        All Academic Periods
                                    </span>
                                </div>

                                <div class="table-toolbar-search">
                                    <div class="voice-search-row">
                                        <x-search-bar id="searchInput" name="search" placeholder="Search periods…"
                                            :value="request('search')" clear-label="Clear academic period search" class="flex-1" />

                                        <x-voice-input target="#searchInput" status-id="apVoiceStatus"
                                            label="Voice search academic periods" title="Voice search" />
                                    </div>
                                </div>

                                <form method="GET" action="{{ route($routeNames['index']) }}" id="filterForm"
                                    class="table-toolbar-actions">
                                    <input type="hidden" name="semester" id="semesterFilter"
                                        value="{{ request('semester') }}">

                                    <input type="hidden" name="status" id="statusFilter"
                                        value="{{ request('status') }}">

                                    <button id="filterBtn" type="button" class="global-filter-btn"
                                        onclick="openAcademicFilterModal()">
                                        <i class="fa-solid fa-sliders"></i>
                                        <span>Filter</span>
                                        <span id="filterBadge" class="filter-badge"></span>
                                    </button>

                                    <x-view-toggle id="academicViewToggle" storage-key="academicView"
                                        list-view="#academicListView" grid-view="#academicGridView" />

                                    <button id="externalClearFilterBtn" type="button" onclick="resetAcademicFilters()"
                                        class="global-filter-reset-btn hidden" title="Reset filters">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </form>

                            </div>

                            <div id="academicListView" class="table-list-view">

                                <div class="table-scroll hidden xl:block">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Year</th>
                                                <th>Semester</th>
                                                <th>Start</th>
                                                <th>End</th>
                                                <th class="table-cell-center">Status</th>
                                                <th class="table-cell-center">Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody id="academicTableBody">
                                            @forelse($academicPeriods as $index => $period)
                                                @php
                                                    $statusClass = match ($period->status) {
                                                        'Active' => 'status-active',
                                                        'Upcoming' => 'status-upcoming',
                                                        'Ended' => 'status-cancelled',
                                                        default => 'status-pending',
                                                    };

                                                    $semesterClass = match ($period->semester) {
                                                        'First Semester', '1st Semester' => 'table-tag-danger',
                                                        'Second Semester', '2nd Semester' => 'table-tag-info',
                                                        'Summer' => 'table-tag-warning',
                                                        default => 'table-tag-neutral',
                                                    };

                                                    $semesterLabel = match ($period->semester) {
                                                        '1st Semester' => 'First Semester',
                                                        '2nd Semester' => 'Second Semester',
                                                        default => $period->semester,
                                                    };

                                                    $periodPayload = [
                                                        'id' => $period->id,
                                                        'academic_year' => $period->academic_year,
                                                        'semester' => $period->semester,
                                                        'start_date' => optional($period->start_date)->format('Y-m-d'),
                                                        'end_date' => optional($period->end_date)->format('Y-m-d'),
                                                        'description' => $period->description,
                                                        'is_active' => (bool) $period->is_active,
                                                        'update_url' => route($routeNames['update'], $period),
                                                    ];

                                                    $label =
                                                        $period->academic_year .
                                                        ' — ' .
                                                        str_replace(
                                                            ['1st', '2nd'],
                                                            ['First', 'Second'],
                                                            $period->semester,
                                                        );
                                                @endphp

                                                <tr data-record-row data-period-id="{{ $period->id }}"
                                                    data-set-active-url="{{ route($routeNames['set_active'], $period) }}"
                                                    data-semester="{{ $period->semester }}"
                                                    data-status="{{ $period->status }}"
                                                    data-search="{{ strtolower(
                                                        $period->academic_year .
                                                            ' ' .
                                                            $period->semester .
                                                            ' ' .
                                                            $period->status .
                                                            ' ' .
                                                            optional($period->start_date)->format('M d, Y') .
                                                            ' ' .
                                                            optional($period->end_date)->format('M d, Y'),
                                                    ) }}">
                                                    <td>
                                                        <div class="table-primary">
                                                            <span
                                                                class="table-dot {{ $period->is_active ? 'table-dot-success' : 'table-dot-muted' }}">
                                                            </span>
                                                            <strong>{{ $period->academic_year }}</strong>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span class="table-tag {{ $semesterClass }}">
                                                            <i
                                                                class="fa-solid {{ $period->semester === 'Summer' ? 'fa-sun' : 'fa-book' }}">
                                                            </i>
                                                            <span>{{ $semesterLabel }}</span>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <span class="table-date">
                                                            <i class="fa-regular fa-calendar"></i>
                                                            <span>{{ optional($period->start_date)->format('M d, Y') }}</span>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <span class="table-date">
                                                            <i class="fa-regular fa-calendar-check"></i>
                                                            <span>{{ optional($period->end_date)->format('M d, Y') }}</span>
                                                        </span>
                                                    </td>

                                                    <td class="table-cell-center">
                                                        <span class="status-badge {{ $statusClass }}">
                                                            {{ $period->status }}
                                                        </span>
                                                    </td>

                                                    <td class="table-action-cell">
                                                        <div class="ui-action-group">
                                                            <button type="button" class="ui-action-btn ui-action-edit"
                                                                data-tooltip="Edit period" aria-label="Edit period"
                                                                onclick='openEditModal(@json($periodPayload))'>
                                                                <i class="fa-solid fa-pen"></i>
                                                            </button>

                                                            @if (!$period->is_active)
                                                                <form method="POST"
                                                                    action="{{ route($routeNames['set_active'], $period) }}"
                                                                    class="inline">
                                                                    @csrf
                                                                    @method('PATCH')

                                                                    <button type="button"
                                                                        class="ui-action-btn ui-action-success"
                                                                        data-tooltip="Set as active"
                                                                        aria-label="Set as active"
                                                                        onclick="openSetActiveModal(
                                                                            @js(route($routeNames['set_active'], $period)),
                                                                            @js($label)
                                                                        )">
                                                                        <i class="fa-solid fa-circle-check"></i>
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <button type="button"
                                                                    class="ui-action-btn ui-action-warning"
                                                                    data-tooltip="Active period"
                                                                    aria-label="Active period" aria-disabled="true">
                                                                    <i class="fa-solid fa-star"></i>
                                                                </button>
                                                            @endif

                                                            <button type="button" class="ui-action-btn ui-action-delete"
                                                                data-tooltip="Delete period" aria-label="Delete period"
                                                                data-delete-url="{{ route($routeNames['destroy'], $period) }}"
                                                                data-delete-label="{{ $label }}"
                                                                onclick="openDeleteModalFromButton(this)">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr id="serverEmptyState">
                                                    <td colspan="6" class="table-empty-state-cell">
                                                        <div id="academicListEmptyState" class="empty-state-host"></div>
                                                    </td>
                                                </tr>
                                            @endforelse

                                            <tr id="academicDynamicListEmptyRow" hidden>
                                                <td colspan="6" class="table-empty-state-cell">
                                                    <div id="academicDynamicListEmpty" class="empty-state-host"></div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div id="academicMobileListBody" class="xl:hidden">
                                    @forelse($academicPeriods as $index => $period)
                                        @php
                                            $statusClass = match ($period->status) {
                                                'Active' => 'status-active',
                                                'Upcoming' => 'status-upcoming',
                                                'Ended' => 'status-cancelled',
                                                default => 'status-pending',
                                            };

                                            $semesterClass = match ($period->semester) {
                                                'First Semester', '1st Semester' => 'table-tag-danger',
                                                'Second Semester', '2nd Semester' => 'table-tag-info',
                                                'Summer' => 'table-tag-warning',
                                                default => 'table-tag-neutral',
                                            };

                                            $semesterLabel = match ($period->semester) {
                                                '1st Semester' => 'First Semester',
                                                '2nd Semester' => 'Second Semester',
                                                default => $period->semester,
                                            };

                                            $periodPayload = [
                                                'id' => $period->id,
                                                'academic_year' => $period->academic_year,
                                                'semester' => $period->semester,
                                                'start_date' => optional($period->start_date)->format('Y-m-d'),
                                                'end_date' => optional($period->end_date)->format('Y-m-d'),
                                                'description' => $period->description,
                                                'is_active' => (bool) $period->is_active,
                                                'update_url' => route($routeNames['update'], $period),
                                            ];

                                            $label =
                                                $period->academic_year .
                                                ' — ' .
                                                str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester);
                                        @endphp

                                        <div class="table-list-row" data-record-mobile
                                            data-period-id="{{ $period->id }}"
                                            data-set-active-url="{{ route($routeNames['set_active'], $period) }}"
                                            data-semester="{{ $period->semester }}" data-status="{{ $period->status }}"
                                            data-search="{{ strtolower(
                                                $period->academic_year .
                                                    ' ' .
                                                    $period->semester .
                                                    ' ' .
                                                    $period->status .
                                                    ' ' .
                                                    optional($period->start_date)->format('M d, Y') .
                                                    ' ' .
                                                    optional($period->end_date)->format('M d, Y'),
                                            ) }}">
                                            <div class="table-record-card-layout">
                                                <div class="table-record-content">
                                                    <div class="flex items-center justify-between gap-2 min-w-0">
                                                        <div class="table-primary">
                                                            <span
                                                                class="table-dot {{ $period->is_active ? 'table-dot-success' : 'table-dot-muted' }}">
                                                            </span>

                                                            <h3 class="table-record-title">
                                                                {{ $period->academic_year }}
                                                            </h3>
                                                        </div>

                                                        <span class="status-badge {{ $statusClass }} shrink-0">
                                                            {{ $period->status }}
                                                        </span>
                                                    </div>

                                                    <div class="table-record-meta grid-cols-2">
                                                        <div class="col-span-2 min-w-0">
                                                            <span class="table-record-label block mb-1">
                                                                Semester
                                                            </span>

                                                            <span class="table-tag {{ $semesterClass }}">
                                                                <i
                                                                    class="fa-solid {{ $period->semester === 'Summer' ? 'fa-sun' : 'fa-book' }}">
                                                                </i>
                                                                <span>{{ $semesterLabel }}</span>
                                                            </span>
                                                        </div>

                                                        <div class="min-w-0">
                                                            <span class="table-record-label block mb-1">
                                                                Start
                                                            </span>

                                                            <span class="table-date">
                                                                {{ optional($period->start_date)->format('M d, Y') }}
                                                            </span>
                                                        </div>

                                                        <div class="min-w-0">
                                                            <span class="table-record-label block mb-1">
                                                                End
                                                            </span>

                                                            <span class="table-date">
                                                                {{ optional($period->end_date)->format('M d, Y') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="table-record-actions">
                                                    <div class="ui-action-group">
                                                        <button type="button" class="ui-action-btn ui-action-edit"
                                                            data-tooltip="Edit period" aria-label="Edit period"
                                                            onclick='openEditModal(@json($periodPayload))'>
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>

                                                        @if (!$period->is_active)
                                                            <form method="POST"
                                                                action="{{ route($routeNames['set_active'], $period) }}"
                                                                class="inline">
                                                                @csrf
                                                                @method('PATCH')

                                                                <button type="button"
                                                                    class="ui-action-btn ui-action-success"
                                                                    data-tooltip="Set as active"
                                                                    aria-label="Set as active"
                                                                    onclick="openSetActiveModal(
                                                                        @js(route($routeNames['set_active'], $period)),
                                                                        @js($label)
                                                                    )">
                                                                    <i class="fa-solid fa-circle-check"></i>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <button type="button" class="ui-action-btn ui-action-delete"
                                                            data-tooltip="Delete period" aria-label="Delete period"
                                                            data-delete-url="{{ route($routeNames['destroy'], $period) }}"
                                                            data-delete-label="{{ $label }}"
                                                            onclick="openDeleteModalFromButton(this)">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div id="serverMobileEmptyState" class="table-empty-state-cell">
                                            <div id="academicMobileListEmptyState" class="empty-state-host"></div>
                                        </div>
                                    @endforelse

                                    <div id="academicDynamicMobileEmptyRow" class="table-empty-state-cell" hidden>
                                        <div id="academicDynamicMobileEmpty" class="empty-state-host"></div>
                                    </div>
                                </div>
                            </div>

                            <div id="academicGridView" class="table-body-surface table-grid-view" hidden>

                                <div class="table-record-grid">
                                    @forelse($academicPeriods as $index => $period)
                                        @php
                                            $statusClass = match ($period->status) {
                                                'Active' => 'status-active',
                                                'Upcoming' => 'status-upcoming',
                                                'Ended' => 'status-cancelled',
                                                default => 'status-pending',
                                            };

                                            $semesterClass = match ($period->semester) {
                                                'First Semester', '1st Semester' => 'table-tag-danger',

                                                'Second Semester', '2nd Semester' => 'table-tag-info',

                                                'Summer' => 'table-tag-warning',

                                                default => 'table-tag-neutral',
                                            };

                                            $semesterLabel = match ($period->semester) {
                                                '1st Semester' => 'First Semester',
                                                '2nd Semester' => 'Second Semester',
                                                default => $period->semester,
                                            };

                                            $periodPayload = [
                                                'id' => $period->id,
                                                'academic_year' => $period->academic_year,
                                                'semester' => $period->semester,
                                                'start_date' => optional($period->start_date)->format('Y-m-d'),
                                                'end_date' => optional($period->end_date)->format('Y-m-d'),
                                                'description' => $period->description,
                                                'is_active' => (bool) $period->is_active,

                                                'update_url' => route($routeNames['update'], $period),
                                            ];

                                            $label =
                                                $period->academic_year .
                                                ' — ' .
                                                str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester);
                                        @endphp

                                        <article data-period-id="{{ $period->id }}"
                                            class="table-record-card table-record-card-layout
        {{ $period->is_active ? 'is-active' : '' }}"
                                            data-record-card data-semester="{{ $period->semester }}"
                                            data-set-active-url="{{ route($routeNames['set_active'], $period) }}"
                                            data-status="{{ $period->status }}"
                                            data-search="{{ strtolower(
                                                $period->academic_year .
                                                    ' ' .
                                                    $period->semester .
                                                    ' ' .
                                                    $period->status .
                                                    ' ' .
                                                    optional($period->start_date)->format('M d, Y') .
                                                    ' ' .
                                                    optional($period->end_date)->format('M d, Y'),
                                            ) }}">

                                            <div class="table-record-content">

                                                <div class="flex items-center justify-center min-w-0">
                                                    <h3 class="table-record-title text-center">
                                                        {{ $period->academic_year }}
                                                    </h3>
                                                </div>

                                                <div class="table-record-meta grid-cols-2">

                                                    <div class="table-record-row col-span-2">
                                                        <span class="table-record-label">
                                                            Semester
                                                        </span>

                                                        <span class="table-record-value gap-2 flex-wrap">
                                                            <span class="table-tag {{ $semesterClass }}">
                                                                <i
                                                                    class="fa-solid
                                                                    {{ $period->semester === 'Summer' ? 'fa-sun' : 'fa-book' }}">
                                                                </i>

                                                                {{ $semesterLabel }}
                                                            </span>

                                                            <span class="status-badge {{ $statusClass }}">
                                                                {{ $period->status }}
                                                            </span>
                                                        </span>
                                                    </div>

                                                    <div class="min-w-0">
                                                        <span class="table-record-label block mb-1">
                                                            Start
                                                        </span>

                                                        <span class="table-date">
                                                            {{ optional($period->start_date)->format('M d, Y') }}
                                                        </span>
                                                    </div>

                                                    <div class="min-w-0">
                                                        <span class="table-record-label block mb-1">
                                                            End
                                                        </span>

                                                        <span class="table-date">
                                                            {{ optional($period->end_date)->format('M d, Y') }}
                                                        </span>
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="table-record-actions">
                                                <div class="ui-action-group">
                                                    <button type="button" class="ui-action-btn ui-action-edit"
                                                        data-tooltip="Edit period" aria-label="Edit period"
                                                        onclick='openEditModal(@json($periodPayload))'>

                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>

                                                    @if (!$period->is_active)
                                                        <form method="POST"
                                                            action="{{ route($routeNames['set_active'], $period) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="button" class="ui-action-btn ui-action-success"
                                                                data-tooltip="Set as active" aria-label="Set as active"
                                                                onclick="openSetActiveModal(
        @js(route($routeNames['set_active'], $period)),
        @js($period->academic_year . ' — ' . str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester))
    )">

                                                                <i class="fa-solid fa-circle-check"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <button type="button" class="ui-action-btn ui-action-delete"
                                                        data-tooltip="Delete period" aria-label="Delete period"
                                                        data-delete-url="{{ route($routeNames['destroy'], $period) }}"
                                                        data-delete-label="{{ $label }}"
                                                        onclick="openDeleteModalFromButton(this)">

                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    @empty

                                        <div id="academicGridEmptyState" class="empty-state-host table-grid-empty"></div>
                                    @endforelse
                                    <div id="academicDynamicGridEmpty" class="empty-state-host table-grid-empty"></div>
                                </div>
                            </div>

                            <x-pagination-bar id="academicPeriodPagebar" info-id="academicPeriodPageInfo"
                                pagination-id="academicPeriodPagination" position="bottom" :show-entries="false"
                                label="periods" data-current-page="{{ $academicPeriods->currentPage() }}"
                                data-last-page="{{ $academicPeriods->lastPage() }}"
                                data-total="{{ $academicPeriods->total() }}"
                                data-from="{{ $academicPeriods->firstItem() ?? 0 }}"
                                data-to="{{ $academicPeriods->lastItem() ?? 0 }}" />
                        </div>

                        <section class="table-card">

                            <div class="table-toolbar">
                                <div class="table-toolbar-title">
                                    <div class="card-header-icon">
                                        <i class="fa-solid fa-calendar-days"></i>
                                    </div>

                                    <div>
                                        <h2 class="card-title">PUP Academic Calendar</h2>
                                        <p class="card-subtitle">Academic periods and university holidays</p>
                                    </div>
                                </div>
                            </div>

                            <div id="calendarList" class="table-body-surface ap-calendar-list scrollbar-thin">
                            </div>

                            <div class="ap-calendar-footer">
                                <a href="https://www.pup.edu.ph/calendar/" target="_blank" rel="noopener noreferrer"
                                    class="ui-btn ui-btn-secondary">

                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>

                                    <span>
                                        View Full PUP Calendar
                                    </span>
                                </a>
                            </div>

                        </section>
                    </div>

                    <aside class="ap-side-column">

                        <section class="card academic-period-time-card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <div class="card-header-icon">
                                        <i class="fa-solid fa-clock"></i>
                                    </div>

                                    <div>
                                        <h2 class="card-title">Date &amp; Time</h2>
                                        <p class="card-subtitle">Current local date and time</p>
                                    </div>
                                </div>

                                <span class="card-header-meta">
                                    Philippine Time
                                </span>
                            </div>

                            <div class="card-body text-center">
                                <div id="liveClock" class="text-4xl font-extrabold tracking-tight leading-none mb-1">
                                    00:00:00
                                </div>

                                <div id="liveAmPm" class="text-xs font-bold uppercase tracking-widest mb-3">
                                    AM
                                </div>

                                <div id="liveDate" class="text-sm font-semibold mb-1">
                                </div>

                                <div id="liveDay" class="text-xs">
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
                                        <h2 class="card-title">
                                            Quick Actions
                                        </h2>

                                        <p class="card-subtitle">
                                            Common academic period tasks
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="quick-actions-list">
                                <button id="openAddPeriodQuickBtn" type="button" class="quick-action quick-action-card"
                                    @if ($canCreateAcademicPeriod) data-open-modal="addModal"
                                @else
                                    data-academic-permission-trigger="create"
                                    aria-disabled="true"
                                    title="{{ $createAcademicPeriodUnauthorizedMessage }}" @endif>
                                    <span class="quick-action-icon">
                                        <i class="fa-solid fa-plus"></i>
                                    </span>

                                    <span class="quick-action-copy">
                                        <span class="quick-action-title">Add Period</span>
                                        <span class="quick-action-sub">Create a new academic term</span>
                                    </span>

                                    <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                                    <i class="fa-solid fa-plus quick-action-bg-icon"></i>
                                </button>

                                <button id="openEditPeriodQuickBtn" type="button"
                                    onclick='@if ($activePeriodPayload) openEditModal(@json($activePeriodPayload)) @endif'
                                    class="quick-action quick-action-card">
                                    <span class="quick-action-icon">
                                        <i class="fa-solid fa-pen"></i>
                                    </span>

                                    <span class="quick-action-copy">
                                        <span class="quick-action-title">Edit Active Period</span>
                                        <span class="quick-action-sub">Modify current semester</span>
                                    </span>

                                    <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                                    <i class="fa-solid fa-pen quick-action-bg-icon"></i>
                                </button>

                                <button type="button" class="quick-action quick-action-card"
                                    data-open-modal="syncFlssModal">
                                    <span class="quick-action-icon">
                                        <i class="fa-solid fa-rotate"></i>
                                    </span>

                                    <span class="quick-action-copy">
                                        <span class="quick-action-title">Sync from FLSS</span>
                                        <span class="quick-action-sub">Fetch active academic year automatically</span>
                                    </span>

                                    <i class="fa-solid fa-chevron-right quick-action-arrow"></i>
                                    <i class="fa-solid fa-cloud-arrow-down quick-action-bg-icon"></i>
                                </button>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <x-filter-drawer id="filterModal" title="Filters" close-id="closeFilterModalBtn"
        close-callback="closeAcademicFilterModal()" clear-id="clearFiltersModal" clear-label="Clear Filters"
        cancel-id="cancelFilterBtn" cancel-callback="closeAcademicFilterModal()" cancel-label="Cancel"
        apply-id="applyFilters" apply-label="Show {{ $academicPeriods->total() }} results" results-id="showResultsText">

        <div id="activeFiltersSection" class="filter-active-section hidden">
            <div class="filter-active-header">

                <span class="filter-active-title">
                    Active Filters
                </span>

                <button id="clearAllChipsBtn" type="button" class="filter-clear-all ui-btn ui-btn-secondary ui-btn-sm">
                    <i class="fa-solid fa-rotate-left"></i>

                    <span>
                        Clear All
                    </span>
                </button>

            </div>

            <div id="activeChipsContainer" class="active-filters-container"></div>
        </div>

        <x-filter-group title="Semester">

            <div id="semesterChipGroup" class="filter-chip-row">

                <label class="choice-chip">
                    <input type="radio" name="filter_semester" value=""
                        class="filter-input radio-red chip-radio">

                    <span>
                        All Semesters
                    </span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="filter_semester" value="First Semester"
                        class="filter-input radio-red chip-radio">

                    <span>
                        First Semester
                    </span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="filter_semester" value="Second Semester"
                        class="filter-input radio-red chip-radio">

                    <span>
                        2nd Semester
                    </span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="filter_semester" value="Summer"
                        class="filter-input radio-red chip-radio">

                    <span>
                        Summer
                    </span>
                </label>

            </div>

        </x-filter-group>

        <x-filter-group title="Status" class="filter-group-last">

            <div id="statusChipGroup" class="filter-chip-row">

                <label class="choice-chip">
                    <input type="radio" name="filter_status" value="" class="filter-input radio-red chip-radio">

                    <span>
                        All Status
                    </span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="filter_status" value="Active" class="filter-input radio-red chip-radio">

                    <span>
                        Active
                    </span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="filter_status" value="Upcoming"
                        class="filter-input radio-red chip-radio">

                    <span>
                        Upcoming
                    </span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="filter_status" value="Ended" class="filter-input radio-red chip-radio">

                    <span>
                        Ended
                    </span>
                </label>

                <label class="choice-chip">
                    <input type="radio" name="filter_status" value="Inactive"
                        class="filter-input radio-red chip-radio">

                    <span>
                        Inactive
                    </span>
                </label>

            </div>

        </x-filter-group>

    </x-filter-drawer>

    <div id="addModal" class="ui-modal modal-theme-primary" aria-hidden="true">
        <form method="POST" id="addPeriodForm" action="{{ route($routeNames['store']) }}"
            class="ui-modal-card modal-xl modal-card-form" data-global-validation
            data-form-validation-rule="academicPeriod" data-discard-form data-discard-title="Discard new academic period?"
            data-discard-subtitle="You have unsaved academic period details."
            data-discard-message="Closing this modal will remove the academic period draft you entered. Do you want to discard your changes?"
            novalidate>

            @csrf

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">Add Academic Period</h3>
                        <p class="modal-subtitle">Add new semester or academic term schedule</p>
                    </div>
                </div>

                <button type="button" data-discard-close="addModal" class="modal-x"
                    aria-label="Close add academic period modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd modal-scroll-body">
                <div class="modal-form-grid">
                    <div class="modal-form-grid-2">
                        <div class="info-card global-form-group" data-global-field>
                            <label class="global-form-label" for="addYear">
                                Academic Year <span class="required-mark">*</span>
                            </label>

                            <div class="modal-inline-control">
                                <div class="global-control-wrap modal-inline-main" id="addAcademicYearWrap">
                                    <i class="fa-solid fa-calendar global-control-icon" aria-hidden="true"></i>
                                    <input name="academic_year" id="addYear" type="text"
                                        placeholder="e.g. 2026-2027"
                                        class="form-input-custom global-form-icon no-voice"
                                        data-field-label="Academic Year"
                                        data-required-message="Please enter the academic year."
                                        data-validation-rule="academicYear" required>
                                </div>

                                <x-voice-input target="#addYear" status-id="addYearVoiceStatus"
                                    label="Voice input for academic year" title="Voice input" />
                            </div>

                            <div id="addYearError" class="global-field-error" data-error-for="addYear"
                                aria-live="polite" aria-hidden="true"></div>
                        </div>

                        <div class="info-card global-form-group" data-global-field>
                            <label class="global-form-label">
                                Semester <span class="required-mark">*</span>
                            </label>

                            <div id="addSemesterGroup" class="ap-semester-grid-redesign">
                                <label class="ap-semester-item">
                                    <input type="radio" name="semester" value="First Semester" required>
                                    <div class="ap-semester-card">
                                        <i class="fa-solid fa-book"></i>
                                        <span>First Semester</span>
                                    </div>
                                </label>

                                <label class="ap-semester-item">
                                    <input type="radio" name="semester" value="Second Semester" required>
                                    <div class="ap-semester-card">
                                        <i class="fa-solid fa-book-open"></i>
                                        <span>Second Semester</span>
                                    </div>
                                </label>

                                <label class="ap-semester-item">
                                    <input type="radio" name="semester" value="Summer" required>
                                    <div class="ap-semester-card">
                                        <i class="fa-solid fa-sun"></i>
                                        <span>Summer</span>
                                    </div>
                                </label>
                            </div>

                            <div class="global-field-error" data-error-for="semester" aria-live="polite"
                                aria-hidden="true"></div>
                        </div>
                    </div>

                    <div class="modal-form-grid-2">
                        <div class="info-card">
                            <div class="modal-form-grid-2">
                                <div class="global-form-group" data-global-field>
                                    <label class="global-form-label" for="addStart">
                                        Start Date <span class="required-mark">*</span>
                                    </label>

                                    <input id="addStart" name="start_date" type="text"
                                        class="form-input-custom js-flatpickr-date" placeholder="Select start date"
                                        data-field-label="Start Date" data-required-message="Please select a start date."
                                        data-validation-rule="strictIsoDate" required readonly>

                                    <div class="global-field-error" data-error-for="addStart" aria-live="polite"
                                        aria-hidden="true"></div>
                                </div>

                                <div class="global-form-group" data-global-field>
                                    <label class="global-form-label" for="addEnd">
                                        End Date <span class="required-mark">*</span>
                                    </label>

                                    <input id="addEnd" name="end_date" type="text"
                                        class="form-input-custom js-flatpickr-date" placeholder="Select end date"
                                        data-field-label="End Date" data-required-message="Please select an end date."
                                        data-validation-rule="strictIsoDate" required readonly>

                                    <div class="global-field-error" data-error-for="addEnd" aria-live="polite"
                                        aria-hidden="true"></div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card modal-form-section">
                            <div class="global-label-row">
                                <div class="global-label-main">
                                    <label class="global-form-label" for="addDesc">
                                        Description <span class="field-optional">(Optional)</span>
                                    </label>
                                </div>

                                <span class="char-counter" id="addDescCounter">0 / 150 characters</span>
                            </div>

                            <div class="modal-inline-control">
                                <div class="modal-inline-main" data-clearable-field>
                                    <textarea name="description" rows="6" class="form-input-custom global-form-textarea no-voice" id="addDesc"
                                        placeholder="Add any notes about this academic period..." maxlength="150" data-char-limit="150"
                                        data-char-counter="#addDescCounter" data-clearable-input></textarea>

                                    <button type="button" id="addDescClearBtn"
                                        class="search-clear field-clear-btn field-clear-btn--textarea" data-field-clear
                                        aria-label="Clear description" title="Clear description">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <x-voice-input target="#addDesc" status-id="addDescVoiceStatus"
                                    label="Voice input for description" title="Voice input" />
                            </div>
                        </div>
                    </div>

                    <div class="modal-option-panel">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div class="modal-form-section">
                                <div class="modal-section-heading">
                                    <div class="modal-section-icon">
                                        <i class="fa-solid fa-star"></i>
                                    </div>

                                    <div>
                                        <h4>Set as Active Period</h4>
                                        <p>This will deactivate the currently active semester.</p>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="is_active" value="0">

                            <label class="global-switch shrink-0">
                                <input type="checkbox" name="is_active" id="addIsActive" value="1"
                                    class="global-switch-input" aria-label="Set as active academic period">
                                <span class="global-switch-track" aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-ft modal-sticky-footer">
                <button type="button" data-discard-close="addModal" class="ui-btn ui-btn-secondary">Cancel</button>

                <button type="submit" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Period</span>
                </button>
            </div>
        </form>
    </div>

    <div id="editModal" class="ui-modal modal-theme-edit" aria-hidden="true">
        <form method="POST" id="editForm" class="ui-modal-card modal-xl modal-card-form" data-global-validation
            data-form-validation-rule="academicPeriod" data-discard-form
            data-discard-title="Discard academic period changes?"
            data-discard-subtitle="You have unsaved changes in this academic period."
            data-discard-message="Closing this modal will remove the changes you made. Do you want to discard them?"
            novalidate>

            @csrf
            @method('PUT')

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-pen"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">Edit Academic Period</h3>
                        <p class="modal-subtitle" id="editSubtitle">Updating period details</p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="editModal"
                    aria-label="Close edit academic period modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd modal-scroll-body">
                <div class="modal-form-grid">
                    <div class="modal-form-grid-2">
                        <div class="info-card global-form-group" data-global-field>
                            <label class="global-form-label" for="editYear">
                                Academic Year <span class="required-mark">*</span>
                            </label>

                            <div class="modal-inline-control">
                                <div class="global-control-wrap modal-inline-main" id="editAcademicYearWrap">
                                    <i class="fa-solid fa-calendar global-control-icon" aria-hidden="true"></i>
                                    <input type="text" name="academic_year" id="editYear"
                                        class="form-input-custom global-form-icon no-voice"
                                        placeholder="e.g. 2026-2027" data-field-label="Academic Year"
                                        data-required-message="Please enter the academic year."
                                        data-validation-rule="academicYear" required>
                                </div>

                                <x-voice-input target="#editYear" status-id="editYearVoiceStatus"
                                    label="Voice input for academic year" title="Voice input" />
                            </div>

                            <div class="global-field-error" data-error-for="editYear" aria-live="polite"
                                aria-hidden="true"></div>
                        </div>

                        <div class="info-card global-form-group" data-global-field>
                            <label class="global-form-label">
                                Semester <span class="required-mark">*</span>
                            </label>

                            <div id="editSemesterGroup" class="ap-semester-grid-redesign">
                                <label class="ap-semester-item">
                                    <input type="radio" name="semester" id="edit-sem-1" value="First Semester"
                                        class="edit-sem" required>
                                    <div class="ap-semester-card">
                                        <i class="fa-solid fa-book"></i>
                                        <span>First Semester</span>
                                    </div>
                                </label>

                                <label class="ap-semester-item">
                                    <input type="radio" name="semester" id="edit-sem-2" value="Second Semester"
                                        class="edit-sem" required>
                                    <div class="ap-semester-card">
                                        <i class="fa-solid fa-book-open"></i>
                                        <span>Second Semester</span>
                                    </div>
                                </label>

                                <label class="ap-semester-item">
                                    <input type="radio" name="semester" id="edit-sem-3" value="Summer"
                                        class="edit-sem" required>
                                    <div class="ap-semester-card">
                                        <i class="fa-solid fa-sun"></i>
                                        <span>Summer</span>
                                    </div>
                                </label>
                            </div>

                            <div class="global-field-error" data-error-for="semester" aria-live="polite"
                                aria-hidden="true"></div>
                        </div>
                    </div>

                    <div class="modal-form-grid-2">
                        <div class="info-card">
                            <div class="modal-form-grid-2">
                                <div class="global-form-group" data-global-field>
                                    <label class="global-form-label" for="editStart">
                                        Start Date <span class="required-mark">*</span>
                                    </label>

                                    <input type="text" name="start_date" id="editStart"
                                        class="form-input-custom js-flatpickr-date" placeholder="Select start date"
                                        data-field-label="Start Date" data-required-message="Please select a start date."
                                        data-validation-rule="strictIsoDate" required readonly>

                                    <div class="global-field-error" data-error-for="editStart" aria-live="polite"
                                        aria-hidden="true"></div>
                                </div>

                                <div class="global-form-group" data-global-field>
                                    <label class="global-form-label" for="editEnd">
                                        End Date <span class="required-mark">*</span>
                                    </label>

                                    <input type="text" name="end_date" id="editEnd"
                                        class="form-input-custom js-flatpickr-date" placeholder="Select end date"
                                        data-field-label="End Date" data-required-message="Please select an end date."
                                        data-validation-rule="strictIsoDate" required readonly>

                                    <div class="global-field-error" data-error-for="editEnd" aria-live="polite"
                                        aria-hidden="true"></div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card modal-form-section">
                            <div class="global-label-row">
                                <div class="global-label-main">
                                    <label class="global-form-label" for="editDesc">
                                        Description <span class="field-optional">(Optional)</span>
                                    </label>
                                </div>

                                <span class="char-counter" id="editDescCounter">0 / 150 characters</span>
                            </div>

                            <div class="modal-inline-control">
                                <div class="modal-inline-main" data-clearable-field>
                                    <textarea name="description" rows="6" class="form-input-custom global-form-textarea no-voice" id="editDesc"
                                        placeholder="Add any notes about this academic period..." maxlength="150" data-char-limit="150"
                                        data-char-counter="#editDescCounter" data-clearable-input></textarea>

                                    <button type="button" id="editDescClearBtn"
                                        class="search-clear field-clear-btn field-clear-btn--textarea" data-field-clear
                                        aria-label="Clear description" title="Clear description">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <x-voice-input target="#editDesc" status-id="editDescVoiceStatus"
                                    label="Voice input for description" title="Voice input" />
                            </div>
                        </div>
                    </div>

                    <div class="modal-option-panel">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div class="modal-form-section">
                                <div class="modal-section-heading">
                                    <div class="modal-section-icon">
                                        <i class="fa-solid fa-star"></i>
                                    </div>

                                    <div>
                                        <h4>Set as Active Period</h4>
                                        <p>This will deactivate the currently active semester.</p>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="is_active" value="0">

                            <label class="global-switch shrink-0">
                                <input type="checkbox" name="is_active" id="editIsActive" value="1"
                                    class="global-switch-input" aria-label="Set as active academic period">
                                <span class="global-switch-track" aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-ft modal-sticky-footer">
                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="editModal">Cancel</button>

                <button type="submit" class="ui-btn ui-btn-edit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Update Period</span>
                </button>
            </div>
        </form>
    </div>

    <div id="setActiveModal" class="ui-modal modal-theme-success" aria-hidden="true">

        <form id="setActiveForm" method="POST" action="" class="ui-modal-card modal-sm modal-card-form">

            @csrf
            @method('PATCH')

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">
                            Set Active Period
                        </h3>

                        <p class="modal-subtitle">
                            Confirm the active academic period
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div class="global-confirm-alert">
                    <i class="fa-solid fa-circle-check"></i>

                    <div>
                        <p>
                            Set
                            <strong id="setActivePeriodName" class="global-confirm-value">
                            </strong>
                            as the active academic period?
                        </p>

                        <span>
                            The currently active period will be deactivated.
                        </span>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-modal-close="setActiveModal">
                    Cancel
                </button>

                <button type="submit" class="ui-btn ui-btn-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Set as Active</span>
                </button>
            </div>
        </form>
    </div>

    <x-delete-confirm-modal id="deleteModal" form-id="deleteForm" name-id="deletePeriodLabel"
        title="Delete Academic Period" helper="This academic period will be permanently removed." />

    <div id="syncFlssModal" class="ui-modal modal-theme-primary" aria-hidden="true">

        <form id="syncFlssForm" method="POST" action="{{ route($routeNames['sync_flss']) }}"
            class="ui-modal-card modal-md modal-card-form">

            @csrf

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">
                            Sync from FLSS
                        </h3>

                        <p class="modal-subtitle">
                            Update the active academic year and semester
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div class="global-confirm-alert">
                    <i class="fa-solid fa-cloud-arrow-down"></i>

                    <div>
                        <p>
                            Sync the active academic period from FLSS?
                        </p>

                        <span>
                            The current academic year and semester will be fetched
                            from the FLSS source and applied to the system.
                        </span>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-modal-close="syncFlssModal">
                    Cancel
                </button>

                <button type="submit" id="syncFlssSubmitBtn" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Sync Now</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        window.openSetActiveModal = function(
            action,
            label
        ) {
            const form =
                document.getElementById('setActiveForm');

            const name =
                document.getElementById(
                    'setActivePeriodName'
                );

            if (!form || !name) return;

            form.action = action;
            name.textContent = label;

            window.openModal?.('setActiveModal');
        };

        const existingAcademicPeriods =
            @json($calendarPeriodsPayload);

        const academicSemesterAliases = {
            '1st Semester': [
                'First Semester',
                '1st Semester',
            ],

            'First Semester': [
                'First Semester',
                '1st Semester',
            ],

            '2nd Semester': [
                'Second Semester',
                '2nd Semester',
            ],

            'Second Semester': [
                'Second Semester',
                '2nd Semester',
            ],

            Summer: [
                'Summer',
            ],
        };


        function registerAcademicPeriodValidation() {
            if (
                typeof window
                .registerGlobalValidationRule !==
                'function' ||

                typeof window
                .registerGlobalFormValidationRule !==
                'function'
            ) {
                return false;
            }


            window.registerGlobalValidationRule(
                'academicYear',
                field => {
                    const value =
                        String(
                            field.value || ''
                        ).trim();

                    if (!value) {
                        return '';
                    }

                    if (
                        !/^\d{4}-\d{4}$/.test(
                            value
                        )
                    ) {
                        return (
                            'Format must be YYYY-YYYY ' +
                            '(e.g. 2025-2026).'
                        );
                    }

                    const [
                        firstYear,
                        secondYear,
                    ] =
                    value
                        .split('-')
                        .map(Number);

                    if (
                        secondYear !==
                        firstYear + 1
                    ) {
                        return (
                            'Second year must be ' +
                            'one after the first.'
                        );
                    }

                    return '';
                }
            );


            window.registerGlobalValidationRule(
                'strictIsoDate',
                field => {
                    const value =
                        String(
                            field.value || ''
                        ).trim();

                    if (!value) {
                        return '';
                    }

                    if (
                        !/^\d{4}-\d{2}-\d{2}$/
                        .test(value)
                    ) {
                        return (
                            'Please select a valid date.'
                        );
                    }

                    const [
                        year,
                        month,
                        day,
                    ] =
                    value
                        .split('-')
                        .map(Number);

                    const date =
                        new Date(
                            `${value}T00:00:00`
                        );

                    if (
                        Number.isNaN(
                            date.getTime()
                        ) ||

                        date.getFullYear() !==
                        year ||

                        date.getMonth() + 1 !==
                        month ||

                        date.getDate() !==
                        day
                    ) {
                        return (
                            'Please select a valid date.'
                        );
                    }

                    return '';
                }
            );


            window.registerGlobalFormValidationRule(
                'academicPeriod',
                form => {
                    const yearField =
                        form.querySelector(
                            '[name="academic_year"]'
                        );

                    const startField =
                        form.querySelector(
                            '[name="start_date"]'
                        );

                    const endField =
                        form.querySelector(
                            '[name="end_date"]'
                        );

                    const semester =
                        form.querySelector(
                            '[name="semester"]:checked'
                        );

                    const semesterGroup =
                        form.querySelector(
                            '.ap-semester-grid-redesign'
                        );

                    let valid = true;
                    let firstInvalid = null;


                    if (!semester) {
                        window.showGlobalGroupError?.(
                            semesterGroup,
                            'semester',
                            'Please select a semester.'
                        );

                        valid = false;

                        firstInvalid ||=
                            form.querySelector(
                                '[name="semester"]'
                            );
                    } else {
                        window.clearGlobalGroupError?.(
                            semesterGroup,
                            'semester'
                        );
                    }


                    if (
                        startField?.value &&
                        endField?.value &&
                        endField.value <=
                        startField.value
                    ) {
                        window
                            .showFormInputValidationMessage
                            ?.(
                                endField,
                                'End date must be after the start date.'
                            );

                        valid = false;

                        firstInvalid ||=
                            endField;
                    }


                    if (
                        yearField?.value &&
                        semester
                    ) {
                        const aliases =
                            academicSemesterAliases[
                                semester.value
                            ] || [
                                semester.value,
                            ];

                        const ignoreId =
                            form.dataset
                            .academicPeriodId ||
                            null;

                        const duplicate =
                            existingAcademicPeriods
                            .find(period => {
                                if (
                                    ignoreId &&
                                    String(
                                        period.id
                                    ) ===
                                    String(
                                        ignoreId
                                    )
                                ) {
                                    return false;
                                }

                                return (
                                    period
                                    .academic_year ===
                                    yearField.value
                                    .trim() &&

                                    aliases.includes(
                                        period.semester
                                    )
                                );
                            });

                        if (duplicate) {
                            window
                                .showFormInputValidationMessage
                                ?.(
                                    yearField,

                                    `${yearField.value.trim()} ` +
                                    `${semester.value} already exists.`
                                );

                            valid = false;

                            firstInvalid ||=
                                yearField;
                        }
                    }


                    return {
                        valid,
                        firstInvalid,
                    };
                }
            );

            return true;
        }


        window.addEventListener(
            'global-validation-ready',
            registerAcademicPeriodValidation
        );

        document.addEventListener(
            'DOMContentLoaded',
            registerAcademicPeriodValidation
        );


        let calendarPeriods = @json($calendarPeriodsPayload);
        const holidayEvents = @json($holidayEvents);

        function renderCalendar() {
            const list = document.getElementById('calendarList');
            if (!list) return;

            const periodEvents = [];

            calendarPeriods.forEach(period => {
                if (period.start_date) {
                    periodEvents.push({
                        date: period.start_date,
                        label: `${period.semester} Start`,
                        year: period.academic_year,
                        type: 'start'
                    });
                }

                if (period.end_date) {
                    periodEvents.push({
                        date: period.end_date,
                        label: `${period.semester} End`,
                        year: period.academic_year,
                        type: 'end'
                    });
                }
            });

            const show = [
                ...periodEvents,
                ...holidayEvents
            ].sort((a, b) =>
                a.date.localeCompare(b.date)
            );

            const today = todayStr();

            if (!show.length) {
                list.innerHTML = '<p class="text-xs text-center py-3">No events found</p>';
                return;
            }

            list.innerHTML = show.map(e => {
                const d = new Date(e.date + 'T00:00:00');
                const isToday = e.date === today;
                const isPast = e.date < today;
                const mon = d.toLocaleDateString('en-US', {
                    month: 'short'
                });
                const day = d.getDate();
                const isHoliday = e.type === 'holiday';

                return `
    <div class="ap-calendar-event ${isPast ? 'is-past' : ''} ${isToday ? 'is-today' : ''} ${isHoliday ? 'is-holiday' : ''}">

        <div class="ap-calendar-date">
            <span class="ap-calendar-month">
                ${mon}
            </span>

            <strong class="ap-calendar-day">
                ${day}
            </strong>
        </div>

        <div class="ap-calendar-event-copy">
            <strong class="ap-calendar-event-title">
                ${e.label}
            </strong>

            <span class="ap-calendar-event-meta">
                ${e.year}${isHoliday ? ' • Holiday' : ''}${isToday ? ' • Today' : ''}
            </span>
        </div>

        <span class="ap-calendar-event-dot ap-calendar-event-dot-${e.type}">
        </span>

    </div>
`;
            }).join('');
        }

        function todayStr() {
            const now = new Date();
            const ph = new Date(now.toLocaleString('en-US', {
                timeZone: 'Asia/Manila'
            }));
            return `${ph.getFullYear()}-${String(ph.getMonth() + 1).padStart(2, '0')}-${String(ph.getDate()).padStart(2, '0')}`;
        }

        function updateSyncedAcademicBanner(period) {
            if (!period) return;

            const semester =
                document.getElementById('bannerSem');

            const year =
                document.getElementById('bannerYear');

            const end =
                document.getElementById('bannerEnd');

            const percentage =
                document.getElementById('bannerPct');

            const progress =
                document.getElementById('bannerFill');

            const daysLeft =
                document.getElementById('bannerDaysLeft');

            if (semester) {
                semester.textContent =
                    period.semester || 'No Active Period';
            }

            if (year) {
                year.textContent =
                    period.academic_year || '—';
            }

            if (end) {
                end.textContent =
                    period.end_date_long || '—';
            }

            const progressValue = Math.max(
                0,
                Math.min(
                    100,
                    Number(period.progress_percent || 0)
                )
            );

            if (percentage) {
                percentage.textContent =
                    `${progressValue}%`;
            }

            if (progress) {
                progress.style.width =
                    `${progressValue}%`;
            }

            const remainingDays =
                Number(period.days_remaining || 0);

            if (daysLeft) {
                daysLeft.textContent =
                    `${remainingDays} day` +
                    `${remainingDays === 1 ? '' : 's'} remaining`;
            }

            updateManageActivePeriodButton(period);
        }

        function updateManageActivePeriodButton(period) {
            const button =
                document.getElementById(
                    'manageActivePeriodBtn'
                );

            if (!button || !period) return;

            button.onclick = function() {
                window.openEditModal?.(period);
            };
        }

        function updateSyncedAcademicRecords(period) {
            if (!period?.id) return;

            document
                .querySelectorAll(
                    '[data-record-row], [data-record-mobile], [data-record-card]'
                )
                .forEach(function(record) {
                    const isCurrent =
                        String(record.dataset.periodId) ===
                        String(period.id);

                    if (record.matches('[data-record-card]')) {
                        record.classList.toggle(
                            'is-active',
                            isCurrent
                        );
                    } else {
                        record.classList.remove('is-active');
                    }

                    if (!isCurrent) {
                        record.dataset.status = getAcademicPeriodStatus(
                            record
                        );
                    }

                    const statusBadge =
                        record.querySelector('.status-badge');

                    if (!statusBadge) return;

                    statusBadge.classList.remove(
                        'status-active',
                        'status-upcoming',
                        'status-cancelled',
                        'status-pending'
                    );

                    if (isCurrent) {
                        record.dataset.status = 'Active';
                        statusBadge.textContent = 'Active';
                        statusBadge.classList.add(
                            'status-active'
                        );

                        updateActiveRecordButton(record);
                        return;
                    }

                    const status =
                        getAcademicPeriodStatus(record);

                    record.dataset.status = status;
                    statusBadge.textContent = status;

                    statusBadge.classList.add(
                        getAcademicPeriodStatusClass(status)
                    );

                    updateInactiveRecordButton(record);
                });
        }

        function getAcademicPeriodStatus(record) {
            const currentStatus =
                record.dataset.status || 'Inactive';

            return currentStatus === 'Active' ?
                'Inactive' :
                currentStatus;
        }

        function getAcademicPeriodStatusClass(status) {
            if (status === 'Active') {
                return 'status-active';
            }

            if (status === 'Upcoming') {
                return 'status-upcoming';
            }

            if (status === 'Ended') {
                return 'status-cancelled';
            }

            return 'status-pending';
        }

        function updateActiveRecordButton(record) {
            const actionGroup =
                record.querySelector('.ui-action-group');

            if (!actionGroup) return;

            const setActiveButton =
                Array.from(
                    actionGroup.querySelectorAll(
                        '.ui-action-btn'
                    )
                ).find(function(button) {
                    return (
                        button.getAttribute(
                            'data-tooltip'
                        ) === 'Set as active' ||
                        button.getAttribute(
                            'data-tooltip'
                        ) === 'Active period'
                    );
                });

            if (!setActiveButton) return;

            const form =
                setActiveButton.closest('form');

            if (record.matches('[data-record-card]')) {
                if (form) {
                    form.remove();
                } else {
                    setActiveButton.remove();
                }

                return;
            }

            const disabledButton =
                document.createElement('button');

            disabledButton.type = 'button';
            disabledButton.setAttribute(
                'aria-disabled',
                'true'
            );
            disabledButton.className =
                'ui-action-btn ui-action-warning';

            disabledButton.setAttribute(
                'data-tooltip',
                'Active period'
            );

            disabledButton.setAttribute(
                'aria-label',
                'Active period'
            );

            disabledButton.innerHTML =
                '<i class="fa-solid fa-star"></i>';

            if (form) {
                form.replaceWith(disabledButton);
            } else {
                setActiveButton.replaceWith(
                    disabledButton
                );
            }
        }

        function updateInactiveRecordButton(record) {
            const actionGroup =
                record.querySelector('.ui-action-group');

            if (!actionGroup) return;

            const existingSetActiveButton =
                Array.from(
                    actionGroup.querySelectorAll(
                        '.ui-action-btn'
                    )
                ).find(function(button) {
                    return (
                        button.getAttribute(
                            'data-tooltip'
                        ) === 'Set as active'
                    );
                });

            if (existingSetActiveButton) return;

            const activeButton =
                Array.from(
                    actionGroup.querySelectorAll(
                        '.ui-action-btn'
                    )
                ).find(function(button) {
                    return (
                        button.getAttribute(
                            'data-tooltip'
                        ) === 'Active period'
                    );
                });

            const action =
                record.dataset.setActiveUrl || '';

            if (!action) return;

            const label =
                record.dataset.search || 'academic period';

            const setActiveButton =
                document.createElement('button');

            setActiveButton.type = 'button';
            setActiveButton.className =
                'ui-action-btn ui-action-success';

            setActiveButton.setAttribute(
                'data-tooltip',
                'Set as active'
            );

            setActiveButton.setAttribute(
                'aria-label',
                'Set as active'
            );

            setActiveButton.innerHTML =
                '<i class="fa-solid fa-circle-check"></i>';

            setActiveButton.addEventListener(
                'click',
                function() {
                    window.openSetActiveModal?.(
                        action,
                        label
                    );
                }
            );

            if (record.matches('[data-record-card]')) {
                const deleteButton =
                    Array.from(
                        actionGroup.querySelectorAll(
                            '.ui-action-btn'
                        )
                    ).find(function(button) {
                        return (
                            button.getAttribute(
                                'data-tooltip'
                            ) === 'Delete period'
                        );
                    });

                actionGroup.insertBefore(
                    setActiveButton,
                    deleteButton || null
                );

                return;
            }

            if (!activeButton) return;

            activeButton.replaceWith(
                setActiveButton
            );
        }

        window.openEditModal = function(period) {
            document.getElementById('editForm').action = period.update_url || '';
            document.getElementById('editYear').value = period.academic_year ?? '';
            document.getElementById('editStart').value = period.start_date ?? '';
            document.getElementById('editEnd').value = period.end_date ?? '';

            const editDesc = document.getElementById('editDesc');
            editDesc.value = period.description ?? '';


            window.initCharLimitFields?.(
                document.getElementById('editModal')
            );

            editDesc.dispatchEvent(
                new Event('input', {
                    bubbles: true
                })
            );

            document.getElementById('editIsActive').checked = !!period.is_active;

            const semMap = {
                '1st Semester': 'First Semester',
                '2nd Semester': 'Second Semester',
                'First Semester': 'First Semester',
                'Second Semester': 'Second Semester',
                'Summer': 'Summer',
            };

            const normalizedSemester = semMap[period.semester] || period.semester;

            document.getElementById('editSubtitle').textContent =
                `${period.academic_year} • ${normalizedSemester}`;

            document.querySelectorAll('.edit-sem').forEach(radio => {
                radio.checked = radio.value === normalizedSemester;
            });

            const editForm =
                document.getElementById(
                    'editForm'
                );

            if (editForm) {
                editForm.dataset.academicPeriodId =
                    period.id;
            }
            openModal('editModal');
        };

        window.openDeleteModal = function(action, label) {
            window.openDeleteConfirmModal({
                modalId: 'deleteModal',
                formId: 'deleteForm',
                nameId: 'deletePeriodLabel',
                action: action,
                itemName: label,
            });
        };

        window.openDeleteModalFromButton = function(button) {
            if (!button) return;

            openDeleteModal(
                button.dataset.deleteUrl || '',
                button.dataset.deleteLabel || 'this academic period'
            );
        };

        function updateClock() {
            const now = new Date();
            const ph = new Date(now.toLocaleString('en-US', {
                timeZone: 'Asia/Manila'
            }));
            let h = ph.getHours();
            const m = String(ph.getMinutes()).padStart(2, '0');
            const s = String(ph.getSeconds()).padStart(2, '0');
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            const hh = String(h).padStart(2, '0');

            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December'
            ];

            const liveClock = document.getElementById('liveClock');
            const liveAmPm = document.getElementById('liveAmPm');
            const liveDate = document.getElementById('liveDate');
            const liveDay = document.getElementById('liveDay');

            if (liveClock) liveClock.textContent = `${hh}:${m}:${s}`;
            if (liveAmPm) liveAmPm.textContent = ampm;
            if (liveDate) liveDate.textContent = `${months[ph.getMonth()]} ${ph.getDate()}, ${ph.getFullYear()}`;
            if (liveDay) liveDay.textContent = days[ph.getDay()];
        }

        function clearAcademicSearch() {
            const searchInput =
                document.getElementById(
                    'searchInput'
                );

            const clearBtn =
                searchInput
                ?.closest(
                    '[data-search-wrapper]'
                )
                ?.querySelector(
                    '[data-search-clear]'
                );

            if (searchInput) {
                searchInput.value = '';

                searchInput.dispatchEvent(
                    new Event(
                        'input', {
                            bubbles: true,
                        }
                    )
                );
            }

            clearBtn?.classList.remove(
                'visible',
                'show'
            );

            document
                .querySelectorAll(
                    '[data-record-row], ' +
                    '[data-record-mobile], ' +
                    '[data-record-card]'
                )
                .forEach(item => {
                    item.style.display = '';
                });

            const dynamicRow =
                document.getElementById(
                    'academicDynamicListEmptyRow'
                );

            if (dynamicRow) {
                dynamicRow.hidden = true;
            }

            const dynamicMobileRow =
                document.getElementById(
                    'academicDynamicMobileEmptyRow'
                );

            if (dynamicMobileRow) {
                dynamicMobileRow.hidden = true;
            }

            window.EmptyState?.hide(
                '#academicDynamicListEmpty'
            );

            window.EmptyState?.hide(
                '#academicDynamicMobileEmpty'
            );

            window.EmptyState?.hide(
                '#academicDynamicGridEmpty'
            );

            searchInput?.focus();
        }

        function renderAcademicBaseEmptyStates() {
            const hasRecords =
                document.querySelector(
                    '[data-record-row]'
                );

            if (hasRecords) {
                return;
            }

            const options = {
                icon: 'fa-school',

                title: 'No academic periods found',

                message: 'Academic periods will appear here once they are added.',
            };

            window.EmptyState?.render({
                host: '#academicListEmptyState',

                ...options,
            });

            window.EmptyState?.render({
                host: '#academicMobileListEmptyState',

                ...options,
            });

            window.EmptyState?.render({
                host: '#academicGridEmptyState',

                ...options,
            });
        }

        function resetAcademicFilters() {
            const semesterFilter = document.getElementById('semesterFilter');
            const statusFilter = document.getElementById('statusFilter');

            if (semesterFilter) semesterFilter.value = '';
            if (statusFilter) statusFilter.value = '';

            document.querySelectorAll('input[name="filter_semester"], input[name="filter_status"]').forEach(radio => {
                radio.checked = radio.value === '';
            });

            if (typeof window.academicFilterItems === 'function') {
                window.academicFilterItems();
            }

            const badge = document.getElementById('filterBadge');
            badge?.classList.remove(
                'show'
            );

            document
                .getElementById('filterBtn')
                ?.classList.remove(
                    'has-filters'
                );

            const resetBtn = document.getElementById('externalClearFilterBtn');
            if (resetBtn) resetBtn.classList.add('hidden');

            const activeFilters = document.getElementById('activeFiltersSection');
            if (activeFilters) activeFilters.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateClock();
            renderCalendar();
            setInterval(updateClock, 1000);
        });

        function showAcademicPermissionToast(message) {
            window.showToast?.({
                type: 'error',
                title: 'Unauthorized',
                message: message || 'You are not authorized to perform this action.',
                duration: 4500,
            });
        }

        function bindAcademicPermissionTrigger(selector, message) {
            document.querySelectorAll(selector).forEach(button => {
                button.addEventListener('click', event => {
                    event.preventDefault();
                    event.stopPropagation();
                    showAcademicPermissionToast(message);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput =
                document.getElementById(
                    'searchInput'
                );
            const canCreateAcademicPeriod = @json($canCreateAcademicPeriod);
            const createAcademicPeriodUnauthorizedMessage = @json($createAcademicPeriodUnauthorizedMessage);
            const addPeriodForm =
                document.getElementById(
                    'addPeriodForm'
                );

            const clearBtn =
                searchInput
                ?.closest(
                    '[data-search-wrapper]'
                )
                ?.querySelector(
                    '[data-search-clear]'
                );
            const semesterFilter = document.getElementById('semesterFilter');
            const statusFilter = document.getElementById('statusFilter');
            let searchTimer = null;

            if (!canCreateAcademicPeriod) {
                bindAcademicPermissionTrigger(
                    '[data-academic-permission-trigger="create"]',
                    createAcademicPeriodUnauthorizedMessage
                );

                addPeriodForm?.addEventListener('submit', event => {
                    event.preventDefault();
                    event.stopPropagation();
                    showAcademicPermissionToast(
                        createAcademicPeriodUnauthorizedMessage
                    );
                });
            }

            const tableBody = document.getElementById('academicTableBody');
            const mobileListBody = document.getElementById('academicMobileListBody');
            const gridView = document.getElementById('academicGridView');
            const filterForm = document.getElementById('filterForm');
            const filterModal = document.getElementById('filterModal');
            const filterBadge = document.getElementById('filterBadge');
            const externalClearFilterBtn = document.getElementById('externalClearFilterBtn');
            const filterResultCount = document.getElementById('filterResultCount');
            const showResultsText = document.getElementById('showResultsText');
            const activeFiltersSection = document.getElementById('activeFiltersSection');
            const activeChipsContainer = document.getElementById('activeChipsContainer');
            const semesterRadios = Array.from(document.querySelectorAll('input[name="filter_semester"]'));
            const statusRadios = Array.from(document.querySelectorAll('input[name="filter_status"]'));

            const academicPagebar =
                document.getElementById(
                    'academicPeriodPagebar'
                );

            const academicPageInfo =
                document.getElementById(
                    'academicPeriodPageInfo'
                );

            const academicPagination =
                document.getElementById(
                    'academicPeriodPagination'
                );

            let academicPageLoading = false;

            const allTableRows = () =>
                tableBody ?
                tableBody.querySelectorAll(
                    '[data-record-row]'
                ) : [];

            const allMobileRows = () =>
                mobileListBody ?
                mobileListBody.querySelectorAll(
                    '[data-record-mobile]'
                ) : [];

            const allGridCards = () =>
                gridView ?
                gridView.querySelectorAll(
                    '[data-record-card]'
                ) : [];

            filterForm?.addEventListener('submit', (event) => event.preventDefault());

            function syncFilterRadios() {
                const semesterValue = semesterFilter?.value || '';
                const statusValue = statusFilter?.value || '';

                semesterRadios.forEach(radio => {
                    radio.checked = radio.value === semesterValue;
                });

                statusRadios.forEach(radio => {
                    radio.checked = radio.value === statusValue;
                });
            }

            function getFilterCount() {
                return (semesterFilter?.value ? 1 : 0) + (statusFilter?.value ? 1 : 0);
            }

            function getPreviewCount() {
                const semesterValue = (semesterRadios.find(radio => radio.checked)?.value ?? semesterFilter
                    ?.value ?? '');
                const statusValue = (statusRadios.find(radio => radio.checked)?.value ?? statusFilter?.value ?? '');

                return Array.from(allTableRows()).filter(row => {
                    const semesterMatch = !semesterValue || row.dataset.semester === semesterValue;
                    const statusMatch = !statusValue || row.dataset.status === statusValue;
                    return semesterMatch && statusMatch;
                }).length;
            }

            function renderActiveFilterChips() {
                if (!activeFiltersSection || !activeChipsContainer) return;

                const chips = [];
                if (semesterFilter?.value) {
                    chips.push({
                        type: 'semester',
                        label: semesterFilter.value
                    });
                }
                if (statusFilter?.value) {
                    chips.push({
                        type: 'status',
                        label: statusFilter.value
                    });
                }

                activeFiltersSection.classList.toggle('hidden', chips.length === 0);
                activeChipsContainer.innerHTML = chips.map(chip => `
                    <span class="filter-chip">
                        <span>${chip.label}</span>
                        <button type="button" class="filter-chip-remove" data-filter-chip-remove="${chip.type}" aria-label="Remove ${chip.type} filter">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </span>
                `).join('');
            }

            function updateFilterUi() {
                const count = getFilterCount();

                if (filterBadge) {
                    filterBadge.textContent =
                        count;

                    filterBadge.classList.toggle(
                        'show',
                        count > 0
                    );
                }

                document
                    .getElementById('filterBtn')
                    ?.classList.toggle(
                        'has-filters',
                        count > 0
                    );

                if (externalClearFilterBtn) {
                    externalClearFilterBtn.classList.toggle('hidden', count === 0);
                }

                const previewCount = getPreviewCount();

                if (filterResultCount) {
                    filterResultCount.textContent = previewCount;
                }

                if (showResultsText) {
                    showResultsText.textContent = `Show ${previewCount} result${previewCount === 1 ? '' : 's'}`;
                }

                renderActiveFilterChips();
            }

            window.openAcademicFilterModal = function() {
                syncFilterRadios();
                updateFilterUi();

                window.openFilterDrawer?.(
                    'filterModal'
                );
            };

            window.closeAcademicFilterModal = function() {
                window.closeFilterDrawer?.(
                    'filterModal'
                );
            };

            function clearAcademicPanelFilters() {
                if (semesterFilter) semesterFilter.value = '';
                if (statusFilter) statusFilter.value = '';
                syncFilterRadios();
                filterItems();
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && filterModal?.classList.contains('open')) {
                    window.closeAcademicFilterModal();
                }
            });
            document.getElementById('clearFiltersModal')?.addEventListener('click', clearAcademicPanelFilters);
            document.getElementById('clearAllChipsBtn')?.addEventListener('click', clearAcademicPanelFilters);

            document.getElementById('applyFilters')?.addEventListener('click', () => {
                if (semesterFilter) {
                    semesterFilter.value = semesterRadios.find(radio => radio.checked)?.value || '';
                }
                if (statusFilter) {
                    statusFilter.value = statusRadios.find(radio => radio.checked)?.value || '';
                }
                filterItems();
                window.closeAcademicFilterModal();
            });

            [...semesterRadios, ...statusRadios].forEach(radio => {
                radio.addEventListener('change', updateFilterUi);
            });

            activeChipsContainer?.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-filter-chip-remove]');
                if (!removeButton) return;

                const filterType = removeButton.dataset.filterChipRemove;
                if (filterType === 'semester' && semesterFilter) semesterFilter.value = '';
                if (filterType === 'status' && statusFilter) statusFilter.value = '';
                syncFilterRadios();
                filterItems();
            });

            function hideAcademicDynamicEmptyStates() {
                const desktopRow =
                    document.getElementById(
                        'academicDynamicListEmptyRow'
                    );

                const mobileRow =
                    document.getElementById(
                        'academicDynamicMobileEmptyRow'
                    );

                if (desktopRow) {
                    desktopRow.hidden = true;
                }

                if (mobileRow) {
                    mobileRow.hidden = true;
                }

                window.EmptyState?.hide(
                    '#academicDynamicListEmpty'
                );

                window.EmptyState?.hide(
                    '#academicDynamicMobileEmpty'
                );

                window.EmptyState?.hide(
                    '#academicDynamicGridEmpty'
                );
            }

            function showSearchEmptyState(query) {
                const desktopRow =
                    document.getElementById(
                        'academicDynamicListEmptyRow'
                    );

                const mobileRow =
                    document.getElementById(
                        'academicDynamicMobileEmptyRow'
                    );

                if (desktopRow) {
                    desktopRow.hidden = false;
                }

                if (mobileRow) {
                    mobileRow.hidden = false;
                }

                window.EmptyState?.renderSearch({
                    host: '#academicDynamicListEmpty',
                    input: '#searchInput',
                    query,
                    message: 'Try a different academic year or semester name.',
                });

                window.EmptyState?.renderSearch({
                    host: '#academicDynamicMobileEmpty',
                    input: '#searchInput',
                    query,
                    message: 'Try a different academic year or semester name.',
                });

                window.EmptyState?.renderSearch({
                    host: '#academicDynamicGridEmpty',
                    input: '#searchInput',
                    query,
                    message: 'Try a different academic year or semester name.',
                });
            }

            function showFilterEmptyState() {
                const desktopRow =
                    document.getElementById(
                        'academicDynamicListEmptyRow'
                    );

                const mobileRow =
                    document.getElementById(
                        'academicDynamicMobileEmptyRow'
                    );

                if (desktopRow) {
                    desktopRow.hidden = false;
                }

                if (mobileRow) {
                    mobileRow.hidden = false;
                }

                const actionHtml = `
        <button
            type="button"
            class="empty-state-btn"
            onclick="resetAcademicFilters()"
        >
            <i class="fa-solid fa-xmark"></i>
            Clear filter
        </button>
    `;

                const options = {
                    icon: 'fa-sliders',
                    title: 'No matches for your filters',
                    message: 'Try removing or adjusting your filter criteria.',
                    actionHtml,
                };

                window.EmptyState?.render({
                    host: '#academicDynamicListEmpty',
                    ...options,
                });

                window.EmptyState?.render({
                    host: '#academicDynamicMobileEmpty',
                    ...options,
                });

                window.EmptyState?.render({
                    host: '#academicDynamicGridEmpty',
                    ...options,
                });
            }

            function getAcademicPaginationMeta(
                pagebar = academicPagebar
            ) {
                return {
                    currentPage: Number(
                        pagebar?.dataset.currentPage
                    ) || 1,

                    lastPage: Number(
                        pagebar?.dataset.lastPage
                    ) || 1,

                    total: Number(
                        pagebar?.dataset.total
                    ) || 0,

                    from: Number(
                        pagebar?.dataset.from
                    ) || 0,

                    to: Number(
                        pagebar?.dataset.to
                    ) || 0,
                };
            }


            function renderAcademicPagination() {
                if (
                    !academicPagebar ||
                    !academicPageInfo ||
                    !academicPagination
                ) {
                    return;
                }

                const meta =
                    getAcademicPaginationMeta();

                window.renderGlobalPagination?.({
                    currentPage: meta.currentPage,

                    lastPage: meta.lastPage,

                    total: meta.total,

                    from: meta.from,

                    to: meta.to,

                    containers: [
                        academicPagination
                    ],

                    infoElements: [
                        academicPageInfo
                    ],

                    bars: [
                        academicPagebar
                    ],

                    itemLabel: 'periods',

                    onPageChange: page =>
                        refreshAcademicPeriods(
                            page
                        ),
                });
            }

            function buildAcademicPeriodUrl(
                page = null
            ) {
                const url =
                    new URL(
                        window.location.href
                    );

                const searchValue =
                    searchInput?.value
                    ?.trim() || '';

                const semesterValue =
                    semesterFilter?.value || '';

                const statusValue =
                    statusFilter?.value || '';

                if (page !== null) {
                    url.searchParams.set(
                        'page',
                        String(page)
                    );
                }

                if (searchValue) {
                    url.searchParams.set(
                        'search',
                        searchValue
                    );
                } else {
                    url.searchParams.delete(
                        'search'
                    );
                }

                if (semesterValue) {
                    url.searchParams.set(
                        'semester',
                        semesterValue
                    );
                } else {
                    url.searchParams.delete(
                        'semester'
                    );
                }

                if (statusValue) {
                    url.searchParams.set(
                        'status',
                        statusValue
                    );
                } else {
                    url.searchParams.delete(
                        'status'
                    );
                }

                return url;
            }


            function applyAcademicPeriodResponse(
                parsed
            ) {
                const newTableBody =
                    parsed.getElementById(
                        'academicTableBody'
                    );

                const newMobileListBody =
                    parsed.getElementById(
                        'academicMobileListBody'
                    );

                const newRecordGrid =
                    parsed.querySelector(
                        '#academicGridView ' +
                        '.table-record-grid'
                    );

                const newPagebar =
                    parsed.getElementById(
                        'academicPeriodPagebar'
                    );

                const newEntryBadge =
                    parsed.getElementById(
                        'entryBadge'
                    );

                if (
                    !newTableBody ||
                    !newMobileListBody ||
                    !newRecordGrid ||
                    !newPagebar
                ) {
                    throw new Error(
                        'Invalid academic period response.'
                    );
                }

                tableBody.innerHTML =
                    newTableBody.innerHTML;

                if (mobileListBody) {
                    mobileListBody.innerHTML =
                        newMobileListBody.innerHTML;
                }

                const currentRecordGrid =
                    gridView?.querySelector(
                        '.table-record-grid'
                    );

                if (currentRecordGrid) {
                    currentRecordGrid.innerHTML =
                        newRecordGrid.innerHTML;
                }

                [
                    'currentPage',
                    'lastPage',
                    'total',
                    'from',
                    'to',
                ].forEach(key => {
                    academicPagebar.dataset[key] =
                        newPagebar.dataset[key] || '';
                });

                const entryBadge =
                    document.getElementById(
                        'entryBadge'
                    );

                if (
                    entryBadge &&
                    newEntryBadge
                ) {
                    entryBadge.textContent =
                        newEntryBadge.textContent;
                }
            }


            function restoreAcademicViewMode() {
                const activeMode =
                    window.getGlobalViewMode?.(
                        'academicViewToggle'
                    ) || 'list';

                window.setGlobalViewMode?.(
                    'academicViewToggle',
                    activeMode, {
                        persist: false,
                    }
                );
            }


            async function refreshAcademicPeriods(
                page = null,
                options = {}
            ) {
                if (academicPageLoading) {
                    return false;
                }

                const {
                    updateUrl = true,
                        showErrorToast = true,
                } = options;

                academicPageLoading = true;

                const requestedPage =
                    page ??
                    getAcademicPaginationMeta()
                    .currentPage;

                const url =
                    buildAcademicPeriodUrl(
                        requestedPage
                    );

                try {
                    academicPagebar?.classList.add(
                        'is-loading'
                    );

                    const response =
                        await fetch(
                            url.toString(), {
                                headers: {
                                    Accept: 'text/html',

                                    'X-Requested-With': 'XMLHttpRequest',
                                },

                                credentials: 'same-origin',
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            'Unable to load academic periods.'
                        );
                    }

                    const html =
                        await response.text();

                    const parsed =
                        new DOMParser()
                        .parseFromString(
                            html,
                            'text/html'
                        );

                    applyAcademicPeriodResponse(
                        parsed
                    );

                    if (updateUrl) {
                        window.history.replaceState({},
                            '',
                            url.toString()
                        );
                    }

                    renderAcademicPagination();
                    filterItems();
                    renderAcademicBaseEmptyStates();
                    restoreAcademicViewMode();

                    return true;

                } catch (error) {
                    if (showErrorToast) {
                        window.showToast?.({
                            type: 'error',

                            title: 'Unable to load records',

                            message: error.message ||
                                'Please try again.',

                            duration: 4500,
                        });
                    }

                    return false;

                } finally {
                    academicPageLoading = false;

                    academicPagebar?.classList.remove(
                        'is-loading'
                    );
                }
            }

            window.refreshAcademicPeriods = refreshAcademicPeriods;

            function filterItems() {
                const semesterValue = semesterFilter?.value || '';
                const statusValue = statusFilter?.value || '';
                const searchValue = (searchInput?.value || '').trim().toLowerCase();

                const rows = allTableRows();
                const mobileRows = allMobileRows();
                const cards = allGridCards();

                let visibleCount = 0;

                if (rows.length === 0) {
                    hideAcademicDynamicEmptyStates();
                    renderAcademicBaseEmptyStates();

                    if (clearBtn) {
                        clearBtn.classList.toggle('show', searchValue !== '');
                        clearBtn.classList.toggle('visible', searchValue !== '');
                    }

                    updateFilterUi();
                    return;
                }

                rows.forEach(row => {
                    const rowSemester = row.dataset.semester || '';
                    const rowStatus = row.dataset.status || '';
                    const rowSearch = row.dataset.search || '';

                    const semesterMatch = !semesterValue || rowSemester === semesterValue;
                    const statusMatch = !statusValue || rowStatus === statusValue;
                    const searchMatch = !searchValue || rowSearch.includes(searchValue);

                    const show = semesterMatch && statusMatch && searchMatch;
                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                mobileRows.forEach(row => {
                    const rowSemester = row.dataset.semester || '';
                    const rowStatus = row.dataset.status || '';
                    const rowSearch = row.dataset.search || '';

                    const semesterMatch = !semesterValue || rowSemester === semesterValue;
                    const statusMatch = !statusValue || rowStatus === statusValue;
                    const searchMatch = !searchValue || rowSearch.includes(searchValue);

                    const show = semesterMatch && statusMatch && searchMatch;
                    row.style.display = show ? '' : 'none';
                });

                cards.forEach(card => {
                    const rowSemester = card.dataset.semester || '';
                    const rowStatus = card.dataset.status || '';
                    const rowSearch = card.dataset.search || '';

                    const semesterMatch = !semesterValue || rowSemester === semesterValue;
                    const statusMatch = !statusValue || rowStatus === statusValue;
                    const searchMatch = !searchValue || rowSearch.includes(searchValue);

                    const show = semesterMatch && statusMatch && searchMatch;
                    card.style.display = show ? '' : 'none';
                });

                if (visibleCount === 0) {
                    if (searchValue) {
                        hideAcademicDynamicEmptyStates();
                        showSearchEmptyState(searchInput.value.trim());
                    } else {
                        hideAcademicDynamicEmptyStates();
                        showFilterEmptyState();
                    }
                } else {
                    hideAcademicDynamicEmptyStates();
                }

                const serverEmpty = document.getElementById('serverEmptyState');
                const serverMobileEmpty = document.getElementById('serverMobileEmptyState');

                if (rows.length > 0) {
                    if (serverEmpty) serverEmpty.style.display = 'none';
                    if (serverMobileEmpty) serverMobileEmpty.style.display = 'none';
                } else {
                    renderAcademicBaseEmptyStates();
                }

                if (clearBtn) {
                    clearBtn.classList.toggle('show', searchValue !== '');
                    clearBtn.classList.toggle('visible', searchValue !== '');
                }

                updateFilterUi();
            }

            window.academicFilterItems = filterItems;

            clearBtn?.addEventListener('click', () => {
                clearAcademicSearch();
                filterItems();
            });

            searchInput?.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => filterItems(), 250);
            });

            searchInput?.addEventListener('keydown', e => {
                if (e.key === 'Enter') e.preventDefault();
            });

            semesterFilter?.addEventListener('change', filterItems);
            statusFilter?.addEventListener('change', filterItems);

            filterItems();
            renderAcademicPagination();

            const syncFlssForm =
                document.getElementById('syncFlssForm');

            const syncFlssSubmitBtn =
                document.getElementById('syncFlssSubmitBtn');

            syncFlssForm?.addEventListener(
                'submit',
                async function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (
                        !syncFlssSubmitBtn ||
                        syncFlssSubmitBtn.disabled
                    ) {
                        return;
                    }

                    const originalContent =
                        syncFlssSubmitBtn.innerHTML;

                    syncFlssSubmitBtn.disabled = true;
                    syncFlssSubmitBtn.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            <span>Syncing...</span>
        `;

                    try {
                        const response = await fetch(
                            syncFlssForm.action, {
                                method: 'POST',

                                headers: {
                                    Accept: 'application/json',

                                    'X-Requested-With': 'XMLHttpRequest',
                                },

                                body: new FormData(
                                    syncFlssForm
                                ),
                            }
                        );

                        const contentType =
                            response.headers.get(
                                'content-type'
                            ) || '';

                        if (
                            !contentType.includes(
                                'application/json'
                            )
                        ) {
                            throw new Error(
                                'The server returned an invalid response.'
                            );
                        }

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(
                                data.message ||
                                'Unable to sync from FLSS.'
                            );
                        }

                        const period =
                            data.academic_period;

                        updateSyncedAcademicBanner(
                            period
                        );

                        updateSyncedAcademicRecords(
                            period
                        );

                        const existingIndex =
                            calendarPeriods.findIndex(
                                function(item) {
                                    return (
                                        String(item.id) ===
                                        String(period.id)
                                    );
                                }
                            );

                        const calendarPayload = {
                            id: period.id,
                            academic_year: period.academic_year,
                            semester: period.semester,
                            start_date: period.start_date,
                            end_date: period.end_date,
                        };

                        if (existingIndex >= 0) {
                            calendarPeriods[
                                existingIndex
                            ] = calendarPayload;
                        } else {
                            calendarPeriods.push(
                                calendarPayload
                            );
                        }

                        renderCalendar();

                        await refreshAcademicPeriods(1);

                        window.closeModal?.(
                            'syncFlssModal'
                        );

                        window.showToast?.({
                            type: data.already_synced ?
                                'info' : 'success',

                            title: data.already_synced ?
                                'Already Synced' : 'FLSS Sync Complete',

                            message: data.message,

                            duration: 5000,
                        });

                    } catch (error) {
                        window.showToast?.({
                            type: 'error',
                            title: 'FLSS Sync Failed',

                            message: error.message ||
                                'Unable to sync the academic period.',

                            duration: 6000,
                        });
                    } finally {
                        syncFlssSubmitBtn.disabled =
                            false;

                        syncFlssSubmitBtn.innerHTML =
                            originalContent;
                    }
                }
            );

        });
    </script>
@endsection
