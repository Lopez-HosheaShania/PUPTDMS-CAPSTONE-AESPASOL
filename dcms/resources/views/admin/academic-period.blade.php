@extends('layouts.admin')

@section('title', 'Academic Period | PUP Taguig Dental Clinic')

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
'color' => '#6b7280',
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
]
: null;
@endphp

<main id="mainContent" class="admin-page-shell academic-period-page page-enter mode-list">
    <div class="admin-page-container">

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

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Academic Periods</h1>
                </div>

                <div class="flex items-center gap-3 flex-wrap w-full sm:w-auto">
                    <button type="button" class="um-hero-btn ap-banner-add-btn" data-sync-flss-trigger>
                        <i class="fa-solid fa-rotate"></i>
                        <span>Sync from FLSS</span>
                    </button>

                    <button id="openAddPeriodBtn" type="button" data-open-modal="addModal"
                        onclick="openModal('addModal')" class="um-hero-btn ap-banner-add-btn">
                        <i class="fa-solid fa-plus"></i>
                        <span>Add Period</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="active-banner mb-6" id="activeBannerWrap">
            <div class="active-banner-inner">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fa-solid fa-calendar text-[#8B0000] text-sm"></i>
                            <p class="text-[10px] tracking-widest text-gray-500 uppercase font-semibold">Current
                                Semester</p>
                        </div>
                        <p class="text-xl font-bold text-gray-800" id="bannerSem">
                            {{ $activePeriod?->semester ?? 'No Active Period' }}
                        </p>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex h-4 w-4 items-center justify-center leading-none shrink-0">
                                <i class="fa-solid fa-graduation-cap text-[#8B0000] text-sm"></i>
                            </span>
                            <p class="text-[10px] tracking-widest text-gray-500 uppercase font-semibold">Academic Year
                            </p>
                        </div>
                        <p class="text-xl font-bold text-gray-800" id="bannerYear">
                            {{ $activePeriod?->academic_year ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <i class="fa-solid fa-clock text-[#8B0000] text-sm"></i>
                            <p class="text-[10px] tracking-widest text-gray-500 uppercase font-semibold">Period Ends</p>
                        </div>
                        <p class="text-xl font-bold text-gray-800" id="bannerEnd">
                            {{ $activePeriod ? $activePeriod->end_date->format('F d, Y') : '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 lg:flex-shrink-0 lg:w-64">
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-[10px] text-gray-500 uppercase tracking-widest font-semibold">Semester
                                Progress</span>
                            <span class="text-[11px] font-bold text-[#8B0000]" id="bannerPct">
                                {{ $activePeriod?->progress_percent ?? 0 }}%
                            </span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" id="bannerFill"
                                style="width:{{ $activePeriod?->progress_percent ?? 0 }}%;">
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1" id="bannerDaysLeft">
                            {{ $activePeriod
                            ? $activePeriod->days_remaining . ' day' . ($activePeriod->days_remaining !== 1 ? 's' : '')
                            . ' remaining'
                            : 'No active period' }}
                        </p>
                    </div>

                    <button type="button"
                        onclick='@if ($activePeriodPayload) openEditModal(@json($activePeriodPayload)) @endif'
                        class="bg-[#8B0000] hover:bg-[#760000] text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-gear"></i> Manage Period
                    </button>
                </div>
            </div>
        </div>

        <div class="ap-content-layout grid grid-cols-1 gap-6 mb-6">
            <div class="ap-periods-column">
                <div class="bg-white rounded-xl shadow overflow-hidden">

                    <div class="px-5 py-4 border-b bg-gray-50 ap-toolbar">
                        <div class="ap-toolbar-left">
                            <i class="fa-solid fa-school text-[#8B0000]"></i>
                            <h2 class="font-bold text-gray-800 text-sm">All Academic Periods</h2>
                            <span id="periodCount"
                                class="text-[10px] font-bold bg-[#8B0000] text-white px-2 py-0.5 rounded-full">
                                {{ $academicPeriods->total() }}
                            </span>
                        </div>

                        <form method="GET" action="{{ route('admin.academic_periods') }}" id="filterForm"
                            class="ap-toolbar-right">

                            <input type="hidden" name="semester" id="semesterFilter" value="{{ request('semester') }}">
                            <input type="hidden" name="status" id="statusFilter" value="{{ request('status') }}">

                            <div class="voice-search-row ap-search-row">
                                <div class="search-wrap global-search" data-search-wrapper>
                                    <i class="fa-solid fa-magnifying-glass search-icon"></i>

                                    <input id="searchInput" name="search" type="text" placeholder="Search periods…"
                                        value="{{ request('search') }}" autocomplete="off" class="search-input"
                                        data-search-input>

                                    <button type="button" id="clearSearch"
                                        class="search-clear {{ request('search') ? 'show' : '' }}" data-search-clear
                                        aria-label="Clear search">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </div>

                                <div class="voice-input-toggle">
                                    <button type="button" id="apMicToggleBtn" class="voice-search-mic external"
                                        data-global-voice-trigger data-voice-target="#searchInput"
                                        data-voice-status="#apVoiceStatus" aria-label="Toggle voice search"
                                        aria-pressed="false">
                                        <i class="fa-solid fa-microphone"></i>
                                    </button>

                                    <span id="apVoiceStatus" class="voice-status hidden" aria-live="polite"
                                        data-voice-status></span>
                                </div>
                            </div>

                            <button id="filterBtn" type="button" class="global-filter-btn"
                                onclick="openAcademicFilterModal()">
                                <i class="fa-solid fa-sliders"></i>
                                <span>Filter</span>
                                <span id="filterBadge" class="filter-badge" style="display:none;"></span>
                            </button>

                            <div class="view-toggle-container hidden md:flex" id="academicViewToggle">
                                <div class="view-slider"></div>
                                <button type="button" class="btn-view-mode active" id="academicListBtn"
                                    title="List view" aria-label="List view">
                                    <i class="fa-solid fa-list text-sm"></i>
                                </button>
                                <button type="button" class="btn-view-mode" id="academicGridBtn" title="Grid view"
                                    aria-label="Grid view">
                                    <i class="fa-solid fa-grip"></i>
                                </button>
                            </div>

                            <button id="externalClearFilterBtn" type="button" onclick="resetAcademicFilters()"
                                class="global-filter-reset-btn hidden" title="Reset filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        </form>
                    </div>

                    <div id="academicListView" class="ap-table-wrap scrollbar-thin">
                        <table class="ap-table text-sm">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr class="text-[10px] uppercase tracking-wide text-[#8B0000] font-bold">
                                    <th class="py-3 px-4 text-left">#</th>
                                    <th class="py-3 px-4 text-left">Year</th>
                                    <th class="py-3 px-4 text-left">Semester</th>
                                    <th class="py-3 px-4 text-left">Start</th>
                                    <th class="py-3 px-4 text-left">End</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="academicTableBody">
                                @forelse($academicPeriods as $index => $period)
                                @php
                                $statusClass = match ($period->status) {
                                'Active' => 's-active',
                                'Upcoming' => 's-upcoming',
                                'Ended' => 's-ended',
                                default => 's-inactive',
                                };

                                $semStyle = match ($period->semester) {
                                'First Semester', '1st Semester' => [
                                'bg' => '#fee2e2',
                                'color' => '#8B0000',
                                ],
                                'Second Semester', '2nd Semester' => [
                                'bg' => '#dbeafe',
                                'color' => '#1d4ed8',
                                ],
                                'Summer' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
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
                                ];
                                @endphp

                                <tr class="tbl-row academic-item {{ $period->is_active ? 'is-active' : '' }} border-b border-gray-50 last:border-0"
                                    data-semester="{{ $period->semester }}" data-status="{{ $period->status }}"
                                    data-search="{{ strtolower($period->academic_year . ' ' . $period->semester . ' ' . $period->status . ' ' . optional($period->start_date)->format('M d, Y') . ' ' . optional($period->end_date)->format('M d, Y')) }}">
                                    <td class="py-3 px-4 text-sm">{{ $academicPeriods->firstItem() + $index }}
                                    </td>

                                    <td class="py-3 px-4 col-year">
                                        <div class="flex items-center">
                                            @if ($period->is_active)
                                            <span class="dot-pulse"
                                                style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#22c55e;margin-right:6px;"></span>
                                            @else
                                            <span
                                                style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#e5e7eb;margin-right:6px;"></span>
                                            @endif
                                            <span class="font-bold text-sm">{{ $period->academic_year }}</span>
                                        </div>
                                    </td>

                                    <td class="py-3 px-4 col-semester">
                                        <span class="sem-pill"
                                            style="background:{{ $semStyle['bg'] }};color:{{ $semStyle['color'] }};">
                                            <i class="fa-solid {{ $period->semester === 'Summer' ? 'fa-sun' : 'fa-book' }}"
                                                style="font-size:9px;"></i>
                                            {{ $semesterLabel }}
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 text-xs text-gray-600">
                                        {{ optional($period->start_date)->format('M d, Y') }}
                                    </td>
                                    <td class="py-3 px-4 text-xs text-gray-600">
                                        {{ optional($period->end_date)->format('M d, Y') }}
                                    </td>

                                    <td class="py-3 px-4 text-center">
                                        <span class="status-badge {{ $statusClass }}"
                                            style="display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;">
                                            {{ $period->status }}
                                        </span>
                                    </td>

                                    <td class="py-3 px-4">
                                        <div class="ap-actions">
                                            <button type="button" class="ap-action-btn ap-action-edit" title="Edit"
                                                onclick='openEditModal(@json($periodPayload))'>
                                                <i class="fa-solid fa-pen"></i>
                                            </button>

                                            @if (!$period->is_active)
                                            <form method="POST"
                                                action="{{ route('admin.academic_periods.set_active', $period) }}"
                                                class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="ap-action-btn ap-action-active"
                                                    title="Set as active">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </button>
                                            </form>
                                            @else
                                            <span class="ap-action-btn ap-action-pinned" title="Active period"><i
                                                    class="fa-solid fa-star" style="font-size:10px;"></i></span>
                                            @endif

                                            @php
                                            $label =
                                            $period->academic_year .
                                            ' — ' .
                                            str_replace(
                                            ['1st', '2nd'],
                                            ['First', 'Second'],
                                            $period->semester,
                                            );
                                            @endphp

                                            <button type="button" class="ap-action-btn ap-action-delete" title="Delete"
                                                data-delete-url="{{ route('admin.academic_periods.destroy', $period) }}"
                                                data-delete-label="{{ $label }}"
                                                onclick="openDeleteModalFromButton(this)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr id="serverEmptyState">
                                    <td colspan="7" class="text-center text-gray-400 ap-empty">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <i class="fa-solid fa-school text-3xl mb-3 opacity-30 block"></i>
                                            <p class="text-sm font-medium">No academic periods found.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div id="academicGridView" class="academic-grid-view">
                        @forelse($academicPeriods as $index => $period)
                        @php
                        $statusClass = match ($period->status) {
                        'Active' => 's-active',
                        'Upcoming' => 's-upcoming',
                        'Ended' => 's-ended',
                        default => 's-inactive',
                        };

                        $semStyle = match ($period->semester) {
                        'First Semester', '1st Semester' => ['bg' => '#fee2e2', 'color' => '#8B0000'],
                        'Second Semester', '2nd Semester' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                        'Summer' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                        default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
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
                        ];

                        $label =
                        $period->academic_year .
                        ' — ' .
                        str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester);
                        @endphp

                        <div class="academic-card academic-item {{ $period->is_active ? 'is-active' : '' }}"
                            data-semester="{{ $period->semester }}" data-status="{{ $period->status }}"
                            data-search="{{ strtolower($period->academic_year . ' ' . $period->semester . ' ' . $period->status . ' ' . optional($period->start_date)->format('M d, Y') . ' ' . optional($period->end_date)->format('M d, Y')) }}">

                            <div class="academic-card-top">
                                <div class="academic-card-year">
                                    @if ($period->is_active)
                                    <span class="dot-pulse"
                                        style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;"></span>
                                    @else
                                    <span
                                        style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#e5e7eb;"></span>
                                    @endif
                                    <div class="academic-card-year-text">{{ $period->academic_year }}</div>
                                </div>

                                <span class="status-badge {{ $statusClass }}"
                                    style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;padding:4px 9px;border-radius:99px;">
                                    {{ $period->status }}
                                </span>
                            </div>

                            <div class="academic-card-meta">
                                <div class="academic-card-row">
                                    <div class="academic-card-label">Semester</div>
                                    <div class="academic-card-value">
                                        <span class="sem-pill"
                                            style="background:{{ $semStyle['bg'] }};color:{{ $semStyle['color'] }};">
                                            <i class="fa-solid {{ $period->semester === 'Summer' ? 'fa-sun' : 'fa-book' }}"
                                                style="font-size:9px;"></i>
                                            {{ $semesterLabel }}
                                        </span>
                                    </div>
                                </div>

                                <div class="academic-card-row">
                                    <div class="academic-card-label">Start</div>
                                    <div class="academic-card-value">
                                        {{ optional($period->start_date)->format('M d, Y') }}</div>
                                </div>

                                <div class="academic-card-row">
                                    <div class="academic-card-label">End</div>
                                    <div class="academic-card-value">
                                        {{ optional($period->end_date)->format('M d, Y') }}</div>
                                </div>
                            </div>

                            <div class="academic-card-actions">
                                <button type="button" class="ap-action-btn ap-action-edit" title="Edit"
                                    onclick='openEditModal(@json($periodPayload))'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                @if (!$period->is_active)
                                <form method="POST" action="{{ route('admin.academic_periods.set_active', $period) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="ap-action-btn ap-action-active" title="Set as active">
                                        <i class="fa-solid fa-circle-check" style="font-size:10px;"></i>
                                    </button>
                                </form>
                                @else
                                <span class="ap-action-btn ap-action-pinned" title="Active period"><i
                                        class="fa-solid fa-star" style="font-size:10px;"></i></span>
                                @endif

                                <button type="button" class="ap-action-btn ap-action-delete" title="Delete"
                                    data-delete-url="{{ url('/admin/academic-periods/' . $period->id) }}"
                                    data-delete-label="{{ $label }}" onclick="openDeleteModalFromButton(this)">
                                    <i class="fa-solid fa-trash" style="font-size:10px;"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div id="serverEmptyStateGrid" class="text-center text-gray-400 ap-empty">
                            <div class="flex flex-col items-center justify-center text-center">
                                <i class="fa-solid fa-school text-3xl mb-3 opacity-30 block"></i>
                                <p class="text-sm font-medium">No academic periods found.</p>
                            </div>
                        </div>
                        @endforelse
                    </div>

                    <div
                        class="px-5 py-3.5 border-t border-gray-100 bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p class="text-xs text-gray-500">
                            Showing
                            <strong>{{ $academicPeriods->firstItem() ?? 0 }}–{{ $academicPeriods->lastItem() ?? 0
                                }}</strong>
                            of <strong>{{ $academicPeriods->total() }}</strong> periods
                        </p>

                        <div class="overflow-x-auto scrollbar-thin w-full md:w-auto">
                            {{ $academicPeriods->onEachSide(2)->links('vendor.pagination.tailwind') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">

                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-5 py-4 border-b bg-gray-50 flex items-center gap-2">
                        <i class="fa-solid fa-bolt text-[#8B0000]"></i>
                        <h2 class="font-bold text-gray-800 text-sm">Quick Actions</h2>
                    </div>
                    <div class="quick-actions-list">
                        <button id="openAddPeriodQuickBtn" type="button" data-open-modal="addModal"
                            onclick="openModal('addModal')" class="quick-action quick-action-card">
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

                        <button type="button" class="quick-action quick-action-card" data-sync-flss-trigger>
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
                </div>

                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-5 py-4 border-b bg-gray-50 flex items-center gap-2">
                        <i class="fa-solid fa-clock text-[#8B0000]"></i>
                        <h2 class="font-bold text-gray-800 text-sm">Date &amp; Time</h2>
                        <span class="ml-auto text-[10px] text-gray-400 font-semibold">Philippine Time</span>
                    </div>
                    <div class="p-5 text-center">
                        <div id="liveClock"
                            class="text-4xl font-extrabold text-[#8B0000] tracking-tight leading-none mb-1">
                            00:00:00
                        </div>
                        <div id="liveAmPm" class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">AM
                        </div>
                        <div id="liveDate" class="text-sm font-semibold text-gray-700 mb-1"></div>
                        <div id="liveDay" class="text-xs text-gray-400"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow overflow-hidden cal-card">
                    <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="ap-calendar-title-icon fa-solid fa-calendar-days"></i>
                            <h2 class="font-bold text-gray-800 text-sm">PUP Academic Calendar</h2>
                        </div>
                        <span id="calYear"
                            class="text-[9px] font-bold text-[#8B0000] bg-red-50 px-1.5 py-0.5 rounded-full whitespace-nowrap">
                            Academic Periods
                        </span>
                    </div>
                    <div id="calendarList" class="p-4 space-y-1 overflow-y-auto scrollbar-thin"
                        style="max-height:485px;"></div>
                    <div class="px-4 pb-4">
                        <a href="https://www.pup.edu.ph/calendar/" target="_blank"
                            class="ap-pup-calendar-link flex items-center justify-center gap-2 w-full py-2 rounded-lg border text-xs font-semibold transition-all mt-2">
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            View Full PUP Calendar
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<div id="filterModal" class="filter-drawer-wrapper academic-filter-modal">
    <div class="filter-drawer-overlay" data-ap-close-filter></div>

    <div class="filter-drawer-panel flex flex-col bg-white">
        <div
            class="filter-drawer-header px-6 py-5 flex items-center justify-between flex-shrink-0 bg-white border-b border-gray-100">
            <div class="filter-drawer-title flex items-center gap-2">
                <i class="fa-solid fa-sliders text-xl"></i>
                <h2 class="text-xl font-extrabold">Filters</h2>
            </div>

            <button id="closeFilterModalBtn" type="button" class="text-gray-400 hover:text-gray-700 transition-colors"
                onclick="closeAcademicFilterModal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="filter-drawer-body px-6 py-5 flex flex-col gap-6 flex-1 overflow-y-auto bg-white">
            <div id="activeFiltersSection" class="hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[13px] font-bold text-gray-800">Active Filters</span>
                    <button id="clearAllChipsBtn" type="button"
                        class="text-xs font-bold text-[#8B0000] hover:underline">
                        Clear All
                    </button>
                </div>
                <div id="activeChipsContainer" class="flex flex-wrap gap-2 pb-4 border-b border-gray-100"></div>
            </div>

            <div>
                <h3 class="filter-section-title">Semester</h3>
                <div class="filter-chip-row" id="semesterChipGroup">
                    <label class="choice-chip">
                        <input type="radio" name="filter_semester" value="" class="filter-input radio-red chip-radio">
                        <span>All Semesters</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="filter_semester" value="First Semester"
                            class="filter-input radio-red chip-radio">
                        <span>First Semester</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="filter_semester" value="Second Semester"
                            class="filter-input radio-red chip-radio">
                        <span>2nd Semester</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="filter_semester" value="Summer"
                            class="filter-input radio-red chip-radio">
                        <span>Summer</span>
                    </label>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Status</h3>
                <div class="filter-chip-row" id="statusChipGroup">
                    <label class="choice-chip">
                        <input type="radio" name="filter_status" value="" class="filter-input radio-red chip-radio">
                        <span>All Status</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="filter_status" value="Active"
                            class="filter-input radio-red chip-radio">
                        <span>Active</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="filter_status" value="Upcoming"
                            class="filter-input radio-red chip-radio">
                        <span>Upcoming</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="filter_status" value="Ended"
                            class="filter-input radio-red chip-radio">
                        <span>Ended</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="filter_status" value="Inactive"
                            class="filter-input radio-red chip-radio">
                        <span>Inactive</span>
                    </label>
                </div>
            </div>
        </div>

        <div
            class="filter-drawer-footer px-6 py-5 bg-white flex flex-col sm:flex-row items-center justify-between flex-shrink-0 border-t border-gray-100 gap-4 sm:gap-0 relative z-20">
            <button id="clearFiltersModal" type="button"
                class="filter-clear-btn flex items-center gap-2 transition-colors w-full sm:w-auto justify-center sm:justify-start">
                <i class="fa-regular fa-trash-can text-lg"></i>
                <span class="text-[13px] font-bold leading-none whitespace-nowrap">Clear Filters</span>
            </button>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button id="cancelFilterBtn" type="button"
                    class="filter-cancel-btn flex-1 sm:flex-none px-5 py-2.5 text-sm font-bold rounded-lg transition-colors"
                    onclick="closeAcademicFilterModal()">
                    Cancel
                </button>

                <button id="applyFilters" type="button"
                    class="filter-show-results-btn filter-apply-btn flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold rounded-lg transition-colors shadow-sm">
                    <i class="fa-solid fa-check"></i>
                    <span id="showResultsText">Show {{ $academicPeriods->total() }} results</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="ui-modal modal-overlay ap-add-modal" id="addModal" onclick="closeModalOutside(event,'addModal')">
    <div class="modal-box modal-box-inner ap-academic-modal ap-academic-modal-lg">
        <form method="POST" action="{{ route('admin.academic_periods.store') }}" class="ap-add-form" data-discard-form
            data-discard-title="Discard new academic period?"
            data-discard-subtitle="You have unsaved academic period details."
            data-discard-message="Closing this modal will remove the academic period draft you entered. Do you want to discard your changes?">
            @csrf

            <div class="ap-add-header-left">
                <div class="ap-add-header-icon">
                    <i class="fa-solid fa-calendar-plus text-xl"></i>
                </div>

                <div>
                    <h3 class="ap-add-header-title">Add Academic Period</h3>
                    <p class="ap-add-header-subtitle">Add new semester or academic term schedule</p>
                </div>
            </div>

            <button type="button" data-close-modal="addModal" data-discard-close="addModal" class="ap-add-close">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div class="ap-add-body">
                <div class="ap-panel ap-panel-soft">
                    <div class="ap-label">
                        <span class="ap-label-text">Academic Year <span class="text-red-500">*</span></span>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <div class="ap-input-wrap" id="addAcademicYearWrap" style="flex: 1;">
                            <span class="ap-input-icon">
                                <i class="fa-solid fa-calendar"></i>
                            </span>
                            <input name="academic_year" id="addYear" type="text" placeholder="e.g. 2026-2027"
                                class="ap-input field-input no-voice" required>
                        </div>
                        <div class="ap-voice-toggle" style="margin-top: 0; position: relative;">
                            <button type="button" class="voice-search-mic external" id="addYearMicBtn"
                                data-global-voice-trigger data-voice-target="#addYear"
                                data-voice-status="#addYearVoiceStatus" aria-label="Voice input for academic year"
                                aria-pressed="false">
                                <i class="fa-solid fa-microphone"></i>
                            </button>
                            <span id="addYearVoiceStatus" class="voice-status hidden" aria-live="polite"
                                data-voice-status></span>
                        </div>
                    </div>

                    <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                </div>

                <div class="ap-panel ap-panel-soft">
                    <div class="ap-label">
                        <span class="ap-label-text">Semester <span class="text-red-500">*</span></span>
                    </div>

                    <div class="ap-semester-grid-redesign">
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

                    <span class="sem-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                </div>

                <div class="ap-col-span-2 ap-panel">
                    <div class="ap-date-grid-redesign">
                        <div>
                            <div class="ap-label">
                                <span class="ap-label-text">Start Date <span class="text-red-500">*</span></span>
                            </div>

                            <div class="ap-input-wrap">
                                <span class="ap-input-icon">
                                    <i class="fa-solid fa-calendar-day"></i>
                                </span>
                                <input name="start_date" type="text" class="ap-input field-input js-flatpickr-date"
                                    placeholder="Select start date" required readonly min="1900-01-01" max="9999-12-31">
                            </div>
                            <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                        </div>

                        <div>
                            <div class="ap-label">
                                <span class="ap-label-text">End Date <span class="text-red-500">*</span></span>
                            </div>

                            <div class="ap-input-wrap">
                                <span class="ap-input-icon">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </span>
                                <input name="end_date" type="text" class="ap-input field-input js-flatpickr-date"
                                    placeholder="Select end date" required readonly>
                            </div>
                            <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                        </div>
                    </div>
                </div>

                <div class="ap-col-span-2 ap-panel ap-desc-panel">
                    <div class="ap-label">
                        <span class="ap-label-text">Description</span>
                        <span class="ap-label-hint">Optional</span>
                    </div>

                    <div class="ap-textarea-wrap" id="addDescWrap">
                        <div class="ap-textarea-inner">
                            <span class="ap-placeholder">Add any notes about this academic period...</span>
                            <textarea name="description" rows="6" class="ap-textarea field-input no-voice" id="addDesc"
                                data-word-limit="150" maxlength="150"></textarea>
                        </div>
                        <div style="position: relative;">
                            <button type="button" class="voice-search-mic external" id="addDescMicBtn"
                                data-global-voice-trigger data-voice-target="#addDesc"
                                data-voice-status="#addDescVoiceStatus" aria-label="Voice input for description"
                                aria-pressed="false">
                                <i class="fa-solid fa-microphone"></i>
                            </button>
                            <span id="addDescVoiceStatus" class="voice-status hidden" aria-live="polite"
                                data-voice-status></span>
                        </div>
                    </div>

                    <div class="ap-desc-meta">
                        <span class="ap-desc-help">Maximum of 150 characters</span>
                        <span class="ap-word-counter" id="addDescCounter">0 / 150 characters</span>
                    </div>
                    <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                </div>

                <div class="ap-col-span-2">
                    <div class="ap-active-card">
                        <div class="ap-active-card-left">
                            <div class="ap-active-badge">
                                <i class="fa-solid fa-star text-sm"></i>
                            </div>

                            <div>
                                <p class="ap-active-title">Set as Active Period</p>
                                <p class="ap-active-desc">This will deactivate the currently active semester.</p>
                            </div>
                        </div>

                        <label class="ap-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1">
                            <span class="ap-switch-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="ap-add-footer">
                <button type="button" data-close-modal="addModal" data-discard-close="addModal"
                    class="ap-add-btn ap-add-btn-cancel">
                    Cancel
                </button>

                <button type="submit" class="ap-add-btn ap-add-btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Period
                </button>
            </div>
        </form>
    </div>
</div>

<div class="ui-modal modal-overlay ap-add-modal" id="editModal" onclick="closeModalOutside(event,'editModal')">
    <div class="modal-box modal-box-inner ap-academic-modal ap-academic-modal-lg">
        <form method="POST" id="editForm" class="ap-add-form" data-discard-form
            data-discard-title="Discard academic period changes?"
            data-discard-subtitle="You have unsaved changes in this academic period."
            data-discard-message="Closing this modal will remove the changes you made. Do you want to discard them?">
            @csrf
            @method('PUT')

            <div class="ap-add-header">
                <div class="ap-add-header-left">
                    <div class="ap-add-header-icon" style="background: linear-gradient(145deg, #2563eb, #1d4ed8);">
                        <i class="fa-solid fa-pen text-xl"></i>
                    </div>

                    <div>
                        <h3 class="ap-add-header-title">Edit Academic Period</h3>
                        <p class="ap-add-header-subtitle" id="editSubtitle">Updating period details</p>
                    </div>
                </div>

                <button type="button" onclick="closeAcademicPeriodModal('editModal')" class="ap-add-close"
                    aria-label="Close edit modal">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="ap-add-body">
                <div class="ap-panel ap-panel-soft">
                    <div class="ap-label">
                        <span class="ap-label-text">Academic Year <span class="text-red-500">*</span></span>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <div class="ap-input-wrap" id="editAcademicYearWrap" style="flex: 1;">
                            <span class="ap-input-icon">
                                <i class="fa-solid fa-calendar"></i>
                            </span>
                            <input type="text" name="academic_year" id="editYear" class="ap-input field-input no-voice"
                                placeholder="e.g. 2026-2027" required>
                        </div>
                        <div class="ap-voice-toggle" style="margin-top: 0; position: relative;">
                            <button type="button" class="voice-search-mic external" id="editYearMicBtn"
                                data-global-voice-trigger data-voice-target="#editYear"
                                data-voice-status="#editYearVoiceStatus" aria-label="Voice input for academic year"
                                aria-pressed="false">
                                <i class="fa-solid fa-microphone"></i>
                            </button>
                            <span id="editYearVoiceStatus" class="voice-status hidden" aria-live="polite"
                                data-voice-status></span>
                        </div>
                    </div>

                    <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                </div>

                <div class="ap-panel ap-panel-soft">
                    <div class="ap-label">
                        <span class="ap-label-text">Semester <span class="text-red-500">*</span></span>
                    </div>

                    <div class="ap-semester-grid-redesign">
                        <label class="ap-semester-item">
                            <input type="radio" name="semester" id="edit-sem-1" value="First Semester" class="edit-sem"
                                required>
                            <div class="ap-semester-card" style="--active-color:#2563eb;">
                                <i class="fa-solid fa-book"></i>
                                <span>First Semester</span>
                            </div>
                        </label>

                        <label class="ap-semester-item">
                            <input type="radio" name="semester" id="edit-sem-2" value="Second Semester" class="edit-sem"
                                required>
                            <div class="ap-semester-card" style="--active-color:#2563eb;">
                                <i class="fa-solid fa-book-open"></i>
                                <span>Second Semester</span>
                            </div>
                        </label>

                        <label class="ap-semester-item">
                            <input type="radio" name="semester" id="edit-sem-3" value="Summer" class="edit-sem"
                                required>
                            <div class="ap-semester-card" style="--active-color:#2563eb;">
                                <i class="fa-solid fa-sun"></i>
                                <span>Summer</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="ap-col-span-2 ap-panel">
                    <div class="ap-date-grid-redesign">
                        <div>
                            <div class="ap-label">
                                <span class="ap-label-text">Start Date <span class="text-red-500">*</span></span>
                            </div>

                            <div class="ap-input-wrap">
                                <span class="ap-input-icon">
                                    <i class="fa-solid fa-calendar-day"></i>
                                </span>
                                <input type="text" name="start_date" id="editStart"
                                    class="ap-input field-input js-flatpickr-date" placeholder="Select start date"
                                    required readonly>
                            </div>
                            <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                        </div>

                        <div>
                            <div class="ap-label">
                                <span class="ap-label-text">End Date <span class="text-red-500">*</span></span>
                            </div>

                            <div class="ap-input-wrap">
                                <span class="ap-input-icon">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </span>
                                <input type="text" name="end_date" id="editEnd"
                                    class="ap-input field-input js-flatpickr-date" placeholder="Select end date"
                                    required readonly>
                            </div>
                            <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                        </div>
                    </div>
                </div>

                <div class="ap-col-span-2 ap-panel ap-desc-panel">
                    <div class="ap-label">
                        <span class="ap-label-text">Description</span>
                        <span class="ap-label-hint">Optional</span>
                    </div>

                    <div class="ap-textarea-wrap" id="editDescWrap">
                        <div class="ap-textarea-inner">
                            <span class="ap-placeholder">Add any notes about this academic period...</span>
                            <textarea rows="6" name="description" id="editDesc" class="ap-textarea field-input no-voice"
                                data-word-limit="150" maxlength="150"></textarea>
                        </div>
                        <div style="position: relative;">
                            <button type="button" class="voice-search-mic external" id="editDescMicBtn"
                                data-global-voice-trigger data-voice-target="#editDesc"
                                data-voice-status="#editDescVoiceStatus" aria-label="Voice input for description"
                                aria-pressed="false">
                                <i class="fa-solid fa-microphone"></i>
                            </button>
                            <span id="editDescVoiceStatus" class="voice-status hidden" aria-live="polite"
                                data-voice-status></span>
                        </div>
                    </div>

                    <div class="ap-desc-meta">
                        <span class="ap-desc-help">Maximum of 150 characters</span>
                        <span class="ap-word-counter" id="editDescCounter">0 / 150 characters</span>
                    </div>
                    <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                </div>

                <div class="ap-col-span-2">
                    <div class="ap-active-card"
                        style="border-color: rgba(37, 99, 235, 0.16); background: linear-gradient(90deg, #eff6ff 0%, #ffffff 100%);">
                        <div class="ap-active-card-left">
                            <div class="ap-active-badge" style="color:#2563eb;">
                                <i class="fa-solid fa-star text-sm"></i>
                            </div>

                            <div>
                                <p class="ap-active-title">Set as Active Period</p>
                                <p class="ap-active-desc">This will deactivate the currently active semester.</p>
                            </div>
                        </div>

                        <label class="ap-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" id="editIsActive" value="1">
                            <span class="ap-switch-slider" style="--switch-color:#2563eb;"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="ap-add-footer">
                <button type="button" onclick="closeAcademicPeriodModal('editModal')"
                    class="ap-add-btn ap-add-btn-cancel">
                    Cancel
                </button>

                <button type="submit" class="ap-add-btn"
                    style="background: linear-gradient(145deg, #2563eb, #1d4ed8); color:#fff; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Update Period
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay ui-modal ap-delete-modal" id="deleteModal" aria-hidden="true">
    <div class="modal-box-inner ap-delete-shell" onclick="event.stopPropagation()" role="dialog" aria-modal="true"
        aria-labelledby="academicDeleteTitle">

        <div class="ap-delete-head">
            <div class="ap-delete-head-left">
                <div class="ap-delete-head-icon">
                    <i class="fa-solid fa-trash"></i>
                </div>

                <div>
                    <h3 id="academicDeleteTitle" class="ap-delete-title">Delete Academic Period</h3>
                    <p class="ap-delete-subtitle">This action requires confirmation</p>
                </div>
            </div>

            <button type="button" onclick="closeModal('deleteModal')" class="ap-delete-x"
                aria-label="Close delete modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" id="deleteForm">
            @csrf
            @method('DELETE')

            <div class="ap-delete-content">
                <div class="ap-delete-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>

                    <div>
                        <p>
                            Are you sure you want to delete
                            <strong id="deletePeriodLabel">—</strong>?
                        </p>
                        <span>This academic period will be permanently removed.</span>
                    </div>
                </div>

                <div class="ap-delete-footer">
                    <button type="button" onclick="closeModal('deleteModal')" class="modal-btn-ghost">
                        Cancel
                    </button>

                    <button type="submit" class="ap-delete-confirm-btn">
                        <i class="fa-solid fa-trash"></i>
                        Delete
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay ui-modal ap-sync-modal" id="syncFlssModal" aria-hidden="true" data-sync-flss-modal>
    <form method="POST" action="{{ route('admin.academic_periods.sync_flss') }}"
        class="modal-box modal-box-inner ap-sync-shell" onclick="event.stopPropagation()">
        @csrf

        <div class="ap-sync-head">
            <div class="ap-sync-head-left">
                <div class="ap-sync-head-icon">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </div>

                <div>
                    <h3 class="ap-sync-title">Sync from FLSS</h3>
                    <p class="ap-sync-subtitle">Update the active academic year and semester</p>
                </div>
            </div>

            <button type="button" class="ap-sync-x" data-sync-flss-close aria-label="Close sync modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="ap-sync-body">
            <div class="ap-sync-alert">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <p>Sync the active academic period from FLSS?</p>
                    <span>This will fetch the current academic year and semester from the external FLSS source.</span>
                </div>
            </div>

            <div class="ap-sync-note">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Existing records will only be updated based on the FLSS response.</span>
            </div>
        </div>

        <div class="ap-sync-footer">
            <button type="button" class="modal-btn-ghost" data-sync-flss-close>
                Cancel
            </button>

            <button type="submit" class="ap-sync-confirm-btn">
                <i class="fa-solid fa-rotate"></i>
                Sync Now
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>

    document.addEventListener('DOMContentLoaded', () => {
        const addForm = document.querySelector('#addModal form');
        if (!addForm) return;

        const yearInput = addForm.querySelector('[name="academic_year"]');
        const startInput = addForm.querySelector('[name="start_date"]');
        const endInput = addForm.querySelector('[name="end_date"]');
        const semRadios = addForm.querySelectorAll('[name="semester"]');
        const addDesc = addForm.querySelector('#addDesc');
        const addDescCounter = addForm.querySelector('#addDescCounter');
        const editDesc = document.getElementById('editDesc');
        const editDescCounter = document.getElementById('editDescCounter');

        function getErr(field) {
            if (field.id === 'addDesc' || field.id === 'editDesc') {
                return field.closest('.ap-desc-panel')?.querySelector('.field-error') || null;
            }

            let current = field;
            while (current) {
                const panel = current.closest('.ap-panel, .ap-panel-soft, .ap-col-span-2');
                if (panel) {
                    return panel.querySelector('.field-error');
                }
                current = current.parentElement;
            }

            return null;
        }

        function setError(field, msg) {
            field.classList.add('field-invalid');
            field.classList.remove('field-valid');
            const err = getErr(field);
            if (err) {
                err.textContent = '⚠ ' + msg;
                err.classList.add('show');
            }
        }

        function clearError(field) {
            field.classList.remove('field-invalid');
            const err = getErr(field);
            if (err) err.classList.remove('show');
        }

        function setValid(field) {
            field.classList.remove('field-invalid');
            field.classList.add('field-valid');
            const err = getErr(field);
            if (err) err.classList.remove('show');
        }

        function validateYear() {
            const v = yearInput.value.trim();
            const pattern = /^\d{4}-\d{4}$/;
            if (!v) {
                setError(yearInput, 'Academic year is required.');
                return false;
            }
            if (!pattern.test(v)) {
                setError(yearInput, 'Format must be YYYY-YYYY (e.g. 2025-2026).');
                return false;
            }
            const [y1, y2] = v.split('-').map(Number);
            if (y2 !== y1 + 1) {
                setError(yearInput, 'Second year must be one after the first.');
                return false;
            }
            setValid(yearInput);
            return true;
        }

        function validateSemester() {
            const checked = [...semRadios].some(r => r.checked);
            const semErr = addForm.querySelector('.sem-error');
            if (!checked) {
                if (semErr) {
                    semErr.textContent = '⚠ Please select a semester.';
                    semErr.classList.add('show');
                }
                return false;
            }
            if (semErr) semErr.classList.remove('show');
            return true;
        }

        function isStrictIsoDate(value) {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;

            const [year, month, day] = value.split('-').map(Number);

            if (year < 1900 || year > 9999) return false;
            if (month < 1 || month > 12) return false;
            if (day < 1 || day > 31) return false;

            const date = new Date(`${value}T00:00:00`);
            if (Number.isNaN(date.getTime())) return false;

            return (
                date.getFullYear() === year &&
                date.getMonth() + 1 === month &&
                date.getDate() === day
            );
        }

        function validateDates() {
            let ok = true;
            const s = startInput.value.trim();
            const e = endInput.value.trim();

            if (!s) {
                setError(startInput, 'Start date is required.');
                ok = false;
            } else if (!isStrictIsoDate(s)) {
                setError(startInput, 'Start date must be a valid date in YYYY-MM-DD format.');
                ok = false;
            } else {
                setValid(startInput);
            }

            if (!e) {
                setError(endInput, 'End date is required.');
                ok = false;
            } else if (!isStrictIsoDate(e)) {
                setError(endInput, 'End date must be a valid date in YYYY-MM-DD format.');
                ok = false;
            } else if (s && isStrictIsoDate(s) && e <= s) {
                setError(endInput, 'End date must be after start date.');
                ok = false;
            } else {
                setValid(endInput);
            }

            return ok;
        }

        yearInput.addEventListener('input', validateYear);
        yearInput.addEventListener('blur', validateYear);
        startInput.addEventListener('change', () => {
            validateDates();
        });
        endInput.addEventListener('change', () => {
            validateDates();
        });

        if (addDesc) {
            addDesc.addEventListener('input', () => {
                const limit = Number(addDesc.dataset.wordLimit || 150);
                if (addDesc.value.length > limit) {
                    addDesc.value = addDesc.value.slice(0, limit);
                }
                validateDescription(addDesc, addDescCounter, setError, clearError);
            });

            addDesc.addEventListener('blur', () => validateDescription(addDesc, addDescCounter, setError,
                clearError));
            updateWordCounter(addDesc, addDescCounter);
        }

        if (editDesc) {
            editDesc.addEventListener('input', () => {
                const limit = Number(editDesc.dataset.wordLimit || 150);
                if (editDesc.value.length > limit) {
                    editDesc.value = editDesc.value.slice(0, limit);
                }
                validateDescription(editDesc, editDescCounter, setError, clearError);
            });

            editDesc.addEventListener('blur', () => validateDescription(editDesc, editDescCounter, setError,
                clearError));
            updateWordCounter(editDesc, editDescCounter);
        }

        addForm.addEventListener('submit', e => {
            const y = validateYear();
            const s = validateSemester();
            const d = validateDates();
            const descOk = validateDescription(addDesc, addDescCounter, setError, clearError);

            if (!y || !s || !d || !descOk) e.preventDefault();
        });
    });

    function countChars(value) {
        return value.length;
    }

    function updateWordCounter(textarea, counter) {
        if (!textarea || !counter) return true;

        const limit = Number(textarea.dataset.wordLimit || 150);
        const chars = countChars(textarea.value);

        counter.textContent = `${chars} / ${limit} characters`;
        counter.classList.remove('is-warning', 'is-danger', 'is-invalid');

        if (chars >= 120 && chars < 140) {
            counter.classList.add('is-warning');
        } else if (chars >= 140 && chars <= limit) {
            counter.classList.add('is-danger');
        } else if (chars > limit) {
            counter.classList.add('is-invalid');
        }

        return chars <= limit;
    }

    function validateDescription(textarea, counter, setErrorFn, clearErrorFn) {
        if (!textarea) return true;

        const limit = Number(textarea.dataset.wordLimit || 150);
        const chars = countChars(textarea.value);

        updateWordCounter(textarea, counter);

        if (chars > limit) {
            if (typeof setErrorFn === 'function') {
                setErrorFn(textarea, `Description must not exceed ${limit} characters.`);
            }
            return false;
        }

        if (typeof clearErrorFn === 'function') {
            clearErrorFn(textarea);
        }

        return true;
    }

    function bindTextareaPlaceholder(textareaId, wrapId) {
        const textarea = document.getElementById(textareaId);
        const wrap = document.getElementById(wrapId);
        if (!textarea || !wrap) return;

        const sync = () => {
            wrap.classList.toggle('has-value', textarea.value.trim().length > 0);
        };

        textarea.addEventListener('focus', () => wrap.classList.add('is-focused'));
        textarea.addEventListener('blur', () => wrap.classList.remove('is-focused'));
        textarea.addEventListener('input', sync);

        sync();
    }

    const calendarPeriods = @json($calendarPeriodsPayload);
    const holidayEvents = @json($holidayEvents);

    function renderCalendar() {
        const list = document.getElementById('calendarList');
        const calYear = document.getElementById('calYear');
        if (!list) return;

        const periodEvents = [];

        calendarPeriods.forEach(period => {
            if (period.start_date) {
                periodEvents.push({
                    date: period.start_date,
                    label: `${period.semester} Start`,
                    year: period.academic_year,
                    color: '#8B0000',
                    type: 'start'
                });
            }

            if (period.end_date) {
                periodEvents.push({
                    date: period.end_date,
                    label: `${period.semester} End`,
                    year: period.academic_year,
                    color: '#2563eb',
                    type: 'end'
                });
            }
        });

        const events = [...periodEvents, ...holidayEvents].sort((a, b) => a.date.localeCompare(b.date));
        const today = todayStr();
        const show = events.sort((a, b) => a.date.localeCompare(b.date));

        if (show.length) {
            const years = [...new Set(show.map(e => e.year))];
            calYear.textContent = years.length === 1 ? years[0] : 'Academic Periods & Holidays';
        } else {
            calYear.textContent = 'Academic Periods & Holidays';
        }

        if (!show.length) {
            list.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">No events found</p>';
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
            let color = e.color;

            if (e.type === 'holiday') color = '#16a34a';
            if (e.type === 'start') color = '#8B0000';
            if (e.type === 'end') color = '#2563eb';

            return `
        <div class="flex items-start gap-3 py-2 border-b border-gray-50 last:border-0 ${isPast ? 'opacity-50' : ''}">
          <div style="flex-shrink:0;width:38px;text-align:center;background:${isToday ? '#8B0000' : isHoliday ? '#f3f4f6' : '#fef2f2'};
                      border-radius:8px;padding:4px 2px;border:1px solid ${isToday ? '#8B0000' : isHoliday ? '#e5e7eb' : '#fde8e8'}">
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:${isToday ? 'rgba(255,255,255,.8)' : isHoliday ? '#6b7280' : '#8B0000'};">${mon}</div>
            <div style="font-size:16px;font-weight:900;line-height:1;color:${isToday ? '#fff' : isHoliday ? '#374151' : '#8B0000'};">${day}</div>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:12px;font-weight:${isToday ? '700' : '600'};color:${isToday ? '#8B0000' : '#374151'};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              ${e.label}
            </div>
            <div style="font-size:10px;color:#9ca3af;margin-top:1px;">
              ${e.year}${isHoliday ? ' • Holiday' : ''}${isToday ? ' • Today' : ''}
            </div>
          </div>
          <div style="width:8px;height:8px;border-radius:50%;background:${color};flex-shrink:0;margin-top:4px;"></div>
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

    function resetModalForm(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        const form = modal.querySelector('form');
        if (!form) return;

        form.reset();

        form.querySelectorAll('.field-invalid, .field-valid').forEach(el => {
            el.classList.remove('field-invalid', 'field-valid');
        });

        form.querySelectorAll('.field-error').forEach(el => {
            el.classList.remove('show');
            el.textContent = '';
        });

        form.querySelectorAll('.sem-error').forEach(el => {
            el.classList.remove('show');
            el.textContent = '';
        });

        const addDesc = document.getElementById('addDesc');
        const addDescCounter = document.getElementById('addDescCounter');
        const editDesc = document.getElementById('editDesc');
        const editDescCounter = document.getElementById('editDescCounter');

        if (id === 'addModal' && typeof updateWordCounter === 'function' && addDesc && addDescCounter) {
            updateWordCounter(addDesc, addDescCounter);
        }

        if (id === 'editModal' && typeof updateWordCounter === 'function' && editDesc && editDescCounter) {
            updateWordCounter(editDesc, editDescCounter);
        }

        if (typeof bindTextareaPlaceholder === 'function') {
            bindTextareaPlaceholder('addDesc', 'addDescWrap');
            bindTextareaPlaceholder('editDesc', 'editDescWrap');
        }
    }

    function setModalState(id, isOpen) {
        const modal = document.getElementById(id);
        if (!modal) return;

        if (isOpen) {
            modal.classList.remove('closing');
            modal.classList.add('open');
            document.body.classList.add('modal-lock');

            requestAnimationFrame(() => {
                document.dispatchEvent(new CustomEvent('ui-modal:opened', {
                    detail: {
                        modal
                    }
                }));

                if (window.DiscardChanges && typeof window.DiscardChanges.captureModal === 'function') {
                    window.DiscardChanges.captureModal(modal);
                }
            });

            return;
        }

        if (id === 'addModal' || id === 'editModal') {
            resetModalForm(id);
        }

        modal.classList.remove('open');
        modal.classList.add('closing');

        setTimeout(() => {
            modal.classList.remove('closing');

            if (!document.querySelector('.ui-modal.open, .ui-modal.closing')) {
                document.body.classList.remove('modal-lock');
            }
        }, 180);
    }

    window.openModal = function (id) {
        setModalState(id, true);
    };

    window.forceCloseModal = function (id) {
        setModalState(id, false);
    };

    window.closeModal = function (id, options = {}) {
        const shouldUseDiscard = !options.force && (id === 'addModal' || id === 'editModal');

        if (shouldUseDiscard && window.DiscardChanges && typeof window.DiscardChanges.confirmClose === 'function') {
            window.DiscardChanges.confirmClose(id, () => setModalState(id, false));
            return;
        }

        setModalState(id, false);
    };

    window.closeAcademicPeriodModal = function (id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        const closeNow = () => {
            setModalState(id, false);
        };

        if (
            (id === 'addModal' || id === 'editModal') &&
            window.DiscardChanges &&
            typeof window.DiscardChanges.confirmClose === 'function'
        ) {
            window.DiscardChanges.confirmClose(id, closeNow);
            return;
        }

        closeNow();
    };

    window.openSyncFlssModal = function () {
        const modal = document.getElementById('syncFlssModal');
        if (!modal) {
            console.warn('syncFlssModal was not found.');
            return;
        }

        modal.classList.remove('closing');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');

        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.style.visibility = 'visible';
        modal.style.pointerEvents = 'auto';

        document.body.classList.add('modal-lock');
    };

    window.closeSyncFlssModal = function () {
        const modal = document.getElementById('syncFlssModal');
        if (!modal) return;

        modal.classList.remove('open');
        modal.classList.add('closing');
        modal.setAttribute('aria-hidden', 'true');

        setTimeout(() => {
            modal.classList.remove('closing');
            modal.style.display = '';
            modal.style.opacity = '';
            modal.style.visibility = '';
            modal.style.pointerEvents = '';

            if (!document.querySelector('.ui-modal.open, .ui-modal.closing')) {
                document.body.classList.remove('modal-lock');
            }
        }, 180);
    };

    document.addEventListener('click', function (event) {
        const openBtn = event.target.closest('[data-sync-flss-trigger]');
        if (openBtn) {
            event.preventDefault();
            event.stopImmediatePropagation();
            window.openSyncFlssModal();
            return;
        }

        const closeBtn = event.target.closest('[data-sync-flss-close]');
        if (closeBtn) {
            event.preventDefault();
            event.stopImmediatePropagation();
            window.closeSyncFlssModal();
            return;
        }

        const syncModal = event.target.closest('[data-sync-flss-modal]');
        if (syncModal && event.target === syncModal) {
            event.preventDefault();
            event.stopImmediatePropagation();
            window.closeSyncFlssModal();
        }
    }, true);

    window.openEditModal = function (period) {
        document.getElementById('editForm').action = `/admin/academic-periods/${period.id}`;
        document.getElementById('editYear').value = period.academic_year ?? '';
        document.getElementById('editStart').value = period.start_date ?? '';
        document.getElementById('editEnd').value = period.end_date ?? '';

        const editDesc = document.getElementById('editDesc');
        const editDescWrap = document.getElementById('editDescWrap');

        editDesc.value = period.description ?? '';

        if (editDescWrap) {
            editDescWrap.classList.toggle('has-value', editDesc.value.trim().length > 0);
            editDescWrap.classList.remove('is-focused');
        }

        if (typeof updateWordCounter === 'function') {
            updateWordCounter(editDesc, document.getElementById('editDescCounter'));
        }

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

        openModal('editModal');
    };

    window.openDeleteModal = function (action, label) {
        document.getElementById('deleteForm').action = action;
        document.getElementById('deletePeriodLabel').textContent = label;
        openModal('deleteModal');
    };

    window.openDeleteModalFromButton = function (button) {
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
        const currentDateTime = document.getElementById('currentDateTime');
        const timeIcon = document.getElementById('timeIcon');

        if (liveClock) liveClock.textContent = `${hh}:${m}:${s}`;
        if (liveAmPm) liveAmPm.textContent = ampm;
        if (liveDate) liveDate.textContent = `${months[ph.getMonth()]} ${ph.getDate()}, ${ph.getFullYear()}`;
        if (liveDay) liveDay.textContent = days[ph.getDay()];
        if (currentDateTime) {
            currentDateTime.textContent =
                `${days[ph.getDay()]}, ${months[ph.getMonth()]} ${ph.getDate()}, ${ph.getFullYear()} · ${hh}:${m} ${ampm}`;
        }

        if (timeIcon) {
            if (ph.getHours() >= 6 && ph.getHours() < 18) {
                timeIcon.className = 'fa-solid fa-sun text-yellow-400 text-xs';
            } else {
                timeIcon.className = 'fa-solid fa-moon text-indigo-400 text-xs';
            }
        }
    }

    function clearAcademicSearch() {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');

        if (searchInput) searchInput.value = '';
        if (clearBtn) clearBtn.classList.remove('visible', 'show');

        const items = document.querySelectorAll('.academic-item');
        items.forEach(item => item.style.display = '');

        const jsEmpty = document.getElementById('jsEmptyState');
        if (jsEmpty) jsEmpty.style.display = 'none';

        const jsEmptyGrid = document.getElementById('jsEmptyStateGrid');
        if (jsEmptyGrid) jsEmptyGrid.style.display = 'none';

        const jsFilterEmpty = document.getElementById('jsFilterEmptyState');
        if (jsFilterEmpty) jsFilterEmpty.style.display = 'none';

        const jsFilterEmptyGrid = document.getElementById('jsFilterEmptyStateGrid');
        if (jsFilterEmptyGrid) jsFilterEmptyGrid.style.display = 'none';

        const serverEmpty = document.getElementById('serverEmptyState');
        if (serverEmpty) {
            const hasRows = document.querySelectorAll('#academicTableBody tr.tbl-row').length > 0;
            serverEmpty.style.display = hasRows ? 'none' : '';
        }

        const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
        if (serverEmptyGrid) {
            const hasCards = document.querySelectorAll('#academicGridView .academic-card').length > 0;
            serverEmptyGrid.style.display = hasCards ? 'none' : '';
        }

        if (searchInput) searchInput.focus();
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
        if (badge) badge.style.display = 'none';

        const resetBtn = document.getElementById('externalClearFilterBtn');
        if (resetBtn) resetBtn.classList.add('hidden');

        const activeFilters = document.getElementById('activeFiltersSection');
        if (activeFilters) activeFilters.classList.add('hidden');
    }

    function getPreferredAcademicView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem('academicView') || 'list';
    }

    function applyAcademicView(view, save = true) {
        const listView = document.getElementById('academicListView');
        const gridView = document.getElementById('academicGridView');
        const listBtn = document.getElementById('academicListBtn');
        const gridBtn = document.getElementById('academicGridBtn');

        if (!listView || !gridView) return;

        const finalView = window.innerWidth <= 767 ? 'grid' : view;

        if (finalView === 'grid') {
            listView.style.display = 'none';
            gridView.style.display = 'grid';
        } else {
            listView.style.display = 'block';
            gridView.style.display = 'none';
        }

        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.classList.toggle('mode-list', finalView === 'list');
            mainContent.classList.toggle('mode-grid', finalView === 'grid');
        }

        if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
        if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

        if (save && window.innerWidth > 767) {
            localStorage.setItem('academicView', finalView);
        }
    }

    function initAcademicViewToggle() {
        const listBtn = document.getElementById('academicListBtn');
        const gridBtn = document.getElementById('academicGridBtn');

        applyAcademicView(getPreferredAcademicView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', () => applyAcademicView('list', true));
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', () => applyAcademicView('grid', true));
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateClock();
        renderCalendar();
        setInterval(updateClock, 1000);
    });

    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');
        const semesterFilter = document.getElementById('semesterFilter');
        const statusFilter = document.getElementById('statusFilter');
        let searchTimer = null;

        const tableBody = document.getElementById('academicTableBody');
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

        const allTableRows = () => tableBody ? tableBody.querySelectorAll('tr.tbl-row') : [];
        const allGridCards = () => gridView ? gridView.querySelectorAll('.academic-card') : [];

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
                filterBadge.textContent = count;
                filterBadge.style.display = count > 0 ? 'inline-flex' : 'none';
            }

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

        window.openAcademicFilterModal = function () {
            if (!filterModal) return;
            syncFilterRadios();
            updateFilterUi();
            filterModal.classList.remove('closing');
            filterModal.classList.add('open');
            document.body.classList.add('filter-lock');
        };

        window.closeAcademicFilterModal = function () {
            if (!filterModal) return;
            filterModal.classList.remove('open');
            filterModal.classList.add('closing');
            document.body.classList.remove('filter-lock');

            window.clearTimeout(window.academicFilterCloseTimer);
            window.academicFilterCloseTimer = window.setTimeout(() => {
                filterModal.classList.remove('closing');
            }, 280);
        };

        function clearAcademicPanelFilters() {
            if (semesterFilter) semesterFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            syncFilterRadios();
            filterItems();
        }

        document.querySelector('[data-ap-close-filter]')?.addEventListener('click', window
            .closeAcademicFilterModal);
        document.getElementById('cancelFilterBtn')?.addEventListener('click', window.closeAcademicFilterModal);
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


        function showSearchEmptyState(query) {
            const safeQuery = String(query || '').replace(/[&<>"']/g, function (match) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[match];
            });

            let rowEmpty = document.getElementById('jsEmptyState');

            if (!rowEmpty && tableBody) {
                rowEmpty = document.createElement('tr');
                rowEmpty.id = 'jsEmptyState';
                rowEmpty.innerHTML = `
            <td colspan="7" class="ap-empty-state-cell">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <p class="empty-state-title">No results for "${safeQuery}"</p>
                    <p class="empty-state-sub">Try a different academic year or semester name.</p>

                    <button type="button" class="empty-state-btn" onclick="clearAcademicSearch()">
                        <i class="fa-solid fa-xmark"></i>
                        Clear search
                    </button>
                </div>
            </td>
        `;

                tableBody.appendChild(rowEmpty);
            } else if (rowEmpty) {
                rowEmpty.innerHTML = `
            <td colspan="7" class="ap-empty-state-cell">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <p class="empty-state-title">No results for "${safeQuery}"</p>
                    <p class="empty-state-sub">Try a different academic year or semester name.</p>

                    <button type="button" class="empty-state-btn" onclick="clearAcademicSearch()">
                        <i class="fa-solid fa-xmark"></i>
                        Clear search
                    </button>
                </div>
            </td>
        `;
            }

            let gridEmpty = document.getElementById('jsEmptyStateGrid');

            if (!gridEmpty && gridView) {
                gridEmpty = document.createElement('div');
                gridEmpty.id = 'jsEmptyStateGrid';
                gridEmpty.className = 'empty-state';
                gridEmpty.innerHTML = `
            <div class="empty-state-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <p class="empty-state-title">No results for "${safeQuery}"</p>
            <p class="empty-state-sub">Try a different academic year or semester name.</p>

            <button type="button" class="empty-state-btn" onclick="clearAcademicSearch()">
                <i class="fa-solid fa-xmark"></i>
                Clear search
            </button>
        `;

                gridView.appendChild(gridEmpty);
            } else if (gridEmpty) {
                gridEmpty.className = 'empty-state';
                gridEmpty.innerHTML = `
            <div class="empty-state-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <p class="empty-state-title">No results for "${safeQuery}"</p>
            <p class="empty-state-sub">Try a different academic year or semester name.</p>

            <button type="button" class="empty-state-btn" onclick="clearAcademicSearch()">
                <i class="fa-solid fa-xmark"></i>
                Clear search
            </button>
        `;
            }

            if (rowEmpty) rowEmpty.style.display = '';
            if (gridEmpty) gridEmpty.style.display = '';

            const serverEmpty = document.getElementById('serverEmptyState');
            if (serverEmpty) serverEmpty.style.display = 'none';

            const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
            if (serverEmptyGrid) serverEmptyGrid.style.display = 'none';
        }

        function hideSearchEmptyState() {
            const rowEmpty = document.getElementById('jsEmptyState');
            const gridEmpty = document.getElementById('jsEmptyStateGrid');
            if (rowEmpty) rowEmpty.style.display = 'none';
            if (gridEmpty) gridEmpty.style.display = 'none';
        }

        function showFilterEmptyState() {
            let rowEmpty = document.getElementById('jsFilterEmptyState');

            if (!rowEmpty && tableBody) {
                rowEmpty = document.createElement('tr');
                rowEmpty.id = 'jsFilterEmptyState';
                rowEmpty.innerHTML = `
            <td colspan="7" class="ap-empty-state-cell">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-sliders"></i>
                    </div>

                    <p class="empty-state-title">No matches for your filters</p>
                    <p class="empty-state-sub">Try removing or adjusting your filter criteria.</p>

                    <button type="button" class="empty-state-btn" onclick="resetAcademicFilters()">
                        <i class="fa-solid fa-xmark"></i>
                        Clear filter
                    </button>
                </div>
            </td>
        `;

                tableBody.appendChild(rowEmpty);
            }

            let gridEmpty = document.getElementById('jsFilterEmptyStateGrid');

            if (!gridEmpty && gridView) {
                gridEmpty = document.createElement('div');
                gridEmpty.id = 'jsFilterEmptyStateGrid';
                gridEmpty.className = 'empty-state';
                gridEmpty.innerHTML = `
            <div class="empty-state-icon">
                <i class="fa-solid fa-sliders"></i>
            </div>

            <p class="empty-state-title">No matches for your filters</p>
            <p class="empty-state-sub">Try removing or adjusting your filter criteria.</p>

            <button type="button" class="empty-state-btn" onclick="resetAcademicFilters()">
                <i class="fa-solid fa-xmark"></i>
                Clear filter
            </button>
        `;

                gridView.appendChild(gridEmpty);
            }

            if (rowEmpty) rowEmpty.style.display = '';
            if (gridEmpty) gridEmpty.style.display = '';

            hideSearchEmptyState();

            const serverEmpty = document.getElementById('serverEmptyState');
            if (serverEmpty) serverEmpty.style.display = 'none';

            const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
            if (serverEmptyGrid) serverEmptyGrid.style.display = 'none';
        }

        function hideFilterEmptyState() {
            const rowEmpty = document.getElementById('jsFilterEmptyState');
            const gridEmpty = document.getElementById('jsFilterEmptyStateGrid');
            if (rowEmpty) rowEmpty.style.display = 'none';
            if (gridEmpty) gridEmpty.style.display = 'none';
        }

        function filterItems() {
            const semesterValue = semesterFilter?.value || '';
            const statusValue = statusFilter?.value || '';
            const searchValue = (searchInput?.value || '').trim().toLowerCase();

            const rows = allTableRows();
            const cards = allGridCards();

            let visibleCount = 0;

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
                    hideFilterEmptyState();
                    showSearchEmptyState(searchInput.value.trim());
                } else {
                    hideSearchEmptyState();
                    showFilterEmptyState();
                }
            } else {
                hideSearchEmptyState();
                hideFilterEmptyState();
            }

            const serverEmpty = document.getElementById('serverEmptyState');
            if (serverEmpty) serverEmpty.style.display = 'none';

            const serverEmptyGrid = document.getElementById('serverEmptyStateGrid');
            if (serverEmptyGrid) serverEmptyGrid.style.display = 'none';

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
        initAcademicViewToggle();

        window.addEventListener('resize', () => {
            applyAcademicView(getPreferredAcademicView(), false);
        });

        document.addEventListener('click', function (event) {
            const openButton = event.target.closest('[data-open-modal]');
            if (!openButton) return;

            event.preventDefault();
            event.stopPropagation();

            const target = openButton.getAttribute('data-open-modal');
            if (!target) return;

            if (typeof window.openModal === 'function') {
                window.openModal(target);
                return;
            }

            const modal = document.getElementById(target);
            if (!modal) return;

            modal.classList.remove('closing');
            modal.classList.add('open');
            document.body.classList.add('modal-lock');
        });

        document.addEventListener('click', function (event) {
            const closeButton = event.target.closest('[data-discard-close]');
            if (!closeButton) return;

            event.preventDefault();
            event.stopPropagation();

            const targetModal = closeButton.getAttribute('data-discard-close');
            if (!targetModal) return;

            window.closeModal(targetModal);
        });

        document.querySelectorAll('[data-close-modal]').forEach(button => {
            button.addEventListener('click', (event) => {
                if (button.hasAttribute('data-discard-close')) return;

                const target = button.getAttribute('data-close-modal');
                if (target) window.closeModal(target);
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.open').forEach(modal => {
                    if (modal.id === 'addModal' || modal.id === 'editModal') {
                        window.closeModal(modal.id);
                    } else {
                        setModalState(modal.id, false);
                    }
                });
                document.body.style.overflow = '';
            }
        });

        const addPeriodButtons = document.querySelectorAll('[data-open-modal="addModal"]');
        const addModalCloseButtons = document.querySelectorAll('[data-close-modal="addModal"]');
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        const addModal = document.getElementById('addModal');

        addPeriodButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                setModalState('addModal', true);
            });
        });

        addModalCloseButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                if (button.hasAttribute('data-discard-close')) return;

                e.preventDefault();
                e.stopPropagation();
                window.closeModal('addModal');
            });
        });

        [addModal, editModal, deleteModal].forEach(modal => {
            if (!modal) return;

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.open').forEach(modal => {
                    if (modal.id === 'addModal' || modal.id === 'editModal') {
                        window.closeModal(modal.id);
                    } else {
                        setModalState(modal.id, false);
                    }
                });
                document.body.style.overflow = '';
            }
        });

        bindTextareaPlaceholder('addDesc', 'addDescWrap');
        bindTextareaPlaceholder('editDesc', 'editDescWrap');

        if (typeof window.initializeVoiceInputs === 'function') {
            window.initializeVoiceInputs(document);
        }
    });
</script>
@endsection