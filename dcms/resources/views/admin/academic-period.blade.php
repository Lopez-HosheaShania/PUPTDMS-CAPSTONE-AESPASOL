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

    <main id="mainContent"
        style="padding-top:82px; padding-bottom:2rem; padding-left:1.5rem; padding-right:1.5rem; min-height:100vh;">
        <div style="max-width:1280px; margin:0 auto;">

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

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

            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">Academic Periods</h1>
                    </div>

                    <button id="openAddPeriodBtn" type="button" data-open-modal="addModal" onclick="openModal('addModal')"
                        class="flex items-center gap-2 bg-white hover:bg-gray-100 text-[#8B0000] px-5 py-2.5 rounded-lg font-semibold text-sm shadow transition-all">
                        <i class="fa-solid fa-plus"></i>
                        Add Period
                    </button>
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
                                    ? $activePeriod->days_remaining . ' day' . ($activePeriod->days_remaining !== 1 ? 's' : '') . ' remaining'
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">

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

                                <div class="search-wrap" style="width:220px;">
                                    <i class="fa fa-search"></i>
                                    <input id="searchInput" name="search" type="text" placeholder="Search periods…"
                                        value="{{ request('search') }}" autocomplete="off">
                                    <button type="button" id="clearSearch"
                                        class="search-clear-btn {{ request('search') ? 'visible' : '' }}" title="Clear">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                {{-- ── External circular mic button (search bar) ── --}}
                                <div class="ap-voice-toggle">
                                    <button type="button" id="apMicToggleBtn" class="ap-voice-mic-ext"
                                        aria-label="Toggle voice search" aria-pressed="false">
                                        <i class="fa-solid fa-microphone"></i>
                                    </button>
                                    <span id="apVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
                                </div>

                                <select name="semester" id="semesterFilter" class="filter-select">
                                    <option value="">All Semesters</option>
                                    <option value="1st Semester"
                                        {{ request('semester') === '1st Semester' ? 'selected' : '' }}>1st Semester
                                    </option>
                                    <option value="2nd Semester"
                                        {{ request('semester') === '2nd Semester' ? 'selected' : '' }}>2nd Semester
                                    </option>
                                    <option value="Summer" {{ request('semester') === 'Summer' ? 'selected' : '' }}>Summer
                                    </option>
                                </select>

                                <select name="status" id="statusFilter" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="Upcoming" {{ request('status') === 'Upcoming' ? 'selected' : '' }}>
                                        Upcoming</option>
                                    <option value="Ended" {{ request('status') === 'Ended' ? 'selected' : '' }}>Ended
                                    </option>
                                    <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>
                                        Inactive</option>
                                </select>

                                <button type="button" class="filter-btn">Filter</button>

                                <button type="button" class="reset-btn" onclick="resetAcademicFilters()">
                                    Reset
                                </button>

                                <div class="academic-view-toggle" id="academicViewToggle">
                                    <button type="button" class="academic-view-btn active" id="academicListBtn"
                                        title="List view" aria-label="List view">
                                        <i class="fa-solid fa-table-list"></i>
                                    </button>
                                    <button type="button" class="academic-view-btn" id="academicGridBtn"
                                        title="Grid view" aria-label="Grid view">
                                        <i class="fa-solid fa-grip"></i>
                                    </button>
                                </div>
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
                                                '1st Semester' => ['bg' => '#fee2e2', 'color' => '#8B0000'],
                                                '2nd Semester' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                                                'Summer' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                                default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
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
                                                    {{ str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester) }}
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
                                                    <button type="button" class="act act-edit" title="Edit"
                                                        onclick='openEditModal(@json($periodPayload))'>
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>

                                                    @if (!$period->is_active)
                                                        <form method="POST"
                                                            action="{{ route('admin.academic_periods.set_active', $period) }}"
                                                            class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="act act-star"
                                                                title="Set as active">
                                                                <i class="fa-solid fa-circle-check"
                                                                    style="font-size:10px;"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="act act-pinned"><i class="fa-solid fa-star"
                                                                style="font-size:10px;"></i></span>
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

                                                    <button type="button" class="act act-del" title="Delete"
                                                        onclick='openDeleteModal(@json(route('admin.academic_periods.destroy', $period)), @json($label))'>
                                                        <i class="fa-solid fa-trash" style="font-size:10px;"></i>
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
                                        '1st Semester' => ['bg' => '#fee2e2', 'color' => '#8B0000'],
                                        '2nd Semester' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                                        'Summer' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                        default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
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
                                                    {{ str_replace(['1st', '2nd'], ['First', 'Second'], $period->semester) }}
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
                                        <button type="button" class="act act-edit" title="Edit"
                                            onclick='openEditModal(@json($periodPayload))'>
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        @if (!$period->is_active)
                                            <form method="POST"
                                                action="{{ route('admin.academic_periods.set_active', $period) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="act act-star" title="Set as active">
                                                    <i class="fa-solid fa-circle-check" style="font-size:10px;"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="act act-pinned"><i class="fa-solid fa-star"
                                                    style="font-size:10px;"></i></span>
                                        @endif

                                        <button type="button" class="act act-del" title="Delete"
                                            onclick='openDeleteModal(@json(route('admin.academic_periods.destroy', $period)), @json($label))'>
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
                                <strong>{{ $academicPeriods->firstItem() ?? 0 }}–{{ $academicPeriods->lastItem() ?? 0 }}</strong>
                                of <strong>{{ $academicPeriods->total() }}</strong> periods
                            </p>

                            <div class="overflow-x-auto scrollbar-thin w-full md:w-auto">
                                {{ $academicPeriods->onEachSide(2)->links('vendor.pagination.tailwind') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">

                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                        <div class="px-5 py-4 border-b bg-gray-50 flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-[#8B0000]"></i>
                            <h2 class="font-bold text-gray-800 text-sm">Quick Actions</h2>
                        </div>
                        <div class="p-4 space-y-2.5">
                            <button id="openAddPeriodQuickBtn" type="button" data-open-modal="addModal"
                                onclick="openModal('addModal')"
                                class="w-full flex items-center gap-3 bg-gradient-to-r from-red-50 to-white hover:from-red-100 hover:to-red-50 border border-red-100 rounded-lg px-4 py-3 text-left transition-all group">
                                <div
                                    class="w-10 h-10 rounded-lg bg-white border border-red-200 flex items-center justify-center text-[#8B0000] shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-sm text-[#8B0000]">Add Period</div>
                                    <div class="text-[10px] text-gray-500">Create a new academic term</div>
                                </div>
                                <i
                                    class="fa-solid fa-chevron-right text-gray-300 text-xs group-hover:text-[#8B0000] group-hover:translate-x-1 transition-all"></i>
                            </button>

                            <button id="openEditPeriodQuickBtn" type="button"
                                onclick='@if ($activePeriodPayload) openEditModal(@json($activePeriodPayload)) @endif'
                                class="w-full flex items-center gap-3 bg-gradient-to-r from-red-50 to-white hover:from-red-100 hover:to-red-50 border border-red-100 rounded-lg px-4 py-3 text-left transition-all group">
                                <div
                                    class="w-10 h-10 rounded-lg bg-white border border-red-200 flex items-center justify-center text-[#8B0000] shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-pen"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-sm text-[#8B0000]">Edit Active Period</div>
                                    <div class="text-[10px] text-gray-500">Modify current semester</div>
                                </div>
                                <i
                                    class="fa-solid fa-chevron-right text-gray-300 text-xs group-hover:text-[#8B0000] group-hover:translate-x-1 transition-all"></i>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
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

                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden cal-card">
                        <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-days text-[#8B0000]"></i>
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
                                class="flex items-center justify-center gap-2 w-full py-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-500 hover:bg-red-50 hover:border-[#8B0000] hover:text-[#8B0000] transition-all mt-2">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                View Full PUP Calendar
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    {{-- ════════════════════════════════════════════════
         ADD MODAL
    ════════════════════════════════════════════════ --}}
    <div class="modal-overlay ap-add-modal" id="addModal" onclick="closeModalOutside(event,'addModal')">
        <div class="modal-box">
            <form method="POST" action="{{ route('admin.academic_periods.store') }}" class="ap-add-form">
                @csrf

                <div class="ap-add-header">
                    <div class="ap-add-header-left">
                        <div class="ap-add-header-icon">
                            <i class="fa-solid fa-calendar-plus text-xl"></i>
                        </div>

                        <div>
                            <h3 class="ap-add-header-title">Add Academic Period</h3>
                            <p class="ap-add-header-subtitle">Add new semester or academic term schedule</p>
                        </div>
                    </div>

                    <button type="button" data-close-modal="addModal" class="ap-add-close">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

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
                                <input name="academic_year" type="text" placeholder="e.g. 2026-2027"
                                    class="ap-input field-input no-voice" required>
                            </div>
                            <div class="ap-voice-toggle" style="margin-top: 0; position: relative;">
                                <button type="button" class="ap-voice-mic-ext" id="addYearMicBtn" aria-label="Voice input for academic year" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addYearVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
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
                                <input type="radio" name="semester" value="1st Semester" required>
                                <div class="ap-semester-card">
                                    <i class="fa-solid fa-book"></i>
                                    <span>1st Semester</span>
                                </div>
                            </label>

                            <label class="ap-semester-item">
                                <input type="radio" name="semester" value="2nd Semester" required>
                                <div class="ap-semester-card">
                                    <i class="fa-solid fa-book-open"></i>
                                    <span>2nd Semester</span>
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
                                    <input name="start_date" type="date" class="ap-input field-input"
                                        required min="1900-01-01" max="9999-12-31" inputmode="numeric">
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
                                    <input name="end_date" type="date" class="ap-input field-input"
                                        required min="1900-01-01" max="9999-12-31" inputmode="numeric">
                                </div>
                                <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                            </div>
                        </div>
                    </div>

                    {{-- ── Description with external circular mic button ── --}}
                    <div class="ap-col-span-2 ap-panel ap-desc-panel">
                        <div class="ap-label">
                            <span class="ap-label-text">Description</span>
                            <span class="ap-label-hint">Optional</span>
                        </div>

                        <div class="ap-textarea-wrap" id="addDescWrap">
                            <div class="ap-textarea-inner">
                                <span class="ap-placeholder">Add any notes about this academic period...</span>
                                <textarea name="description" rows="6"
                                    class="ap-textarea field-input no-voice" id="addDesc" data-word-limit="150" maxlength="150"></textarea>
                            </div>
                            <div style="position: relative;">
                                <button type="button" class="ap-voice-mic-ext" id="addDescMicBtn" aria-label="Voice input for description" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addDescVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
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
                    <button type="button" data-close-modal="addModal" class="ap-add-btn ap-add-btn-cancel">
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

    {{-- ════════════════════════════════════════════════
         EDIT MODAL
    ════════════════════════════════════════════════ --}}
    <div class="modal-overlay ap-add-modal" id="editModal" onclick="closeModalOutside(event,'editModal')">
        <div class="modal-box">
            <form method="POST" id="editForm" class="ap-add-form">
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

                    <button type="button" onclick="closeModal('editModal')" class="ap-add-close">
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
                                <button type="button" class="ap-voice-mic-ext" id="editYearMicBtn" aria-label="Voice input for academic year" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editYearVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
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
                                <input type="radio" name="semester" id="edit-sem-1" value="1st Semester"
                                    class="edit-sem" required>
                                <div class="ap-semester-card" style="--active-color:#2563eb;">
                                    <i class="fa-solid fa-book"></i>
                                    <span>1st Semester</span>
                                </div>
                            </label>

                            <label class="ap-semester-item">
                                <input type="radio" name="semester" id="edit-sem-2" value="2nd Semester"
                                    class="edit-sem" required>
                                <div class="ap-semester-card" style="--active-color:#2563eb;">
                                    <i class="fa-solid fa-book-open"></i>
                                    <span>2nd Semester</span>
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
                                    <input type="date" name="start_date" id="editStart" class="ap-input field-input"
                                        required min="1900-01-01" max="9999-12-31" inputmode="numeric">
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
                                    <input type="date" name="end_date" id="editEnd" class="ap-input field-input"
                                        required min="1900-01-01" max="9999-12-31" inputmode="numeric">
                                </div>
                                <span class="field-error hidden text-xs font-semibold text-red-500 mt-1.5"></span>
                            </div>
                        </div>
                    </div>

                    {{-- ── Description with external circular mic button ── --}}
                    <div class="ap-col-span-2 ap-panel ap-desc-panel">
                        <div class="ap-label">
                            <span class="ap-label-text">Description</span>
                            <span class="ap-label-hint">Optional</span>
                        </div>

                        <div class="ap-textarea-wrap" id="editDescWrap">
                            <div class="ap-textarea-inner">
                                <span class="ap-placeholder">Add any notes about this academic period...</span>
                                <textarea rows="6" name="description" id="editDesc"
                                    class="ap-textarea field-input no-voice" data-word-limit="150" maxlength="150"></textarea>
                            </div>
                            <div style="position: relative;">
                                <button type="button" class="ap-voice-mic-ext" id="editDescMicBtn" aria-label="Voice input for description" aria-pressed="false">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editDescVoiceStatus" class="ap-voice-status hidden" aria-live="polite"></span>
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
                    <button type="button" onclick="closeModal('editModal')" class="ap-add-btn ap-add-btn-cancel">
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

    {{-- ════════════════════════════════════════════════
         DELETE MODAL
    ════════════════════════════════════════════════ --}}
    <div class="modal-overlay modal-sm" id="deleteModal" onclick="closeModalOutside(event,'deleteModal')">
        <div class="modal-box ap-delete-shell">
            <form method="POST" id="deleteForm">
                @csrf
                @method('DELETE')

                <div class="ap-delete-body">
                    <div class="ap-modal-icon delete mx-auto">
                        <i class="fa-solid fa-triangle-exclamation text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-extrabold text-gray-800 mb-2">Delete Academic Period?</h3>
                    <p class="text-sm text-gray-500 mb-1">You are about to permanently delete</p>
                    <p class="font-bold text-[#8B0000] text-base mb-4" id="deletePeriodLabel">—</p>

                    <div class="ap-delete-actions">
                        <button type="button" onclick="closeModal('deleteModal')"
                            class="ap-btn ap-btn-ghost">Cancel</button>
                        <button type="submit" class="ap-btn ap-btn-danger">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
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
                // For description fields
                if (field.id === 'addDesc' || field.id === 'editDesc') {
                    return field.closest('.ap-desc-panel')?.querySelector('.field-error') || null;
                }

                // For other fields, search up to the nearest .ap-panel or parent wrapper
                let current = field;
                while (current) {
                    // Check if we're in a .ap-panel or .ap-panel-soft
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
            startInput.addEventListener('change', () => { validateDates(); });
            endInput.addEventListener('change', () => { validateDates(); });

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
                const mon = d.toLocaleDateString('en-US', { month: 'short' });
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
            const ph = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
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

            if (!isOpen && (id === 'addModal' || id === 'editModal')) {
                resetModalForm(id);
            }

            modal.classList.toggle('open', isOpen);
            modal.style.display = isOpen ? 'flex' : 'none';

            const modalBox = modal.querySelector('.modal-box');
            if (modalBox) {
                modalBox.style.display = 'block';
                modalBox.style.visibility = 'visible';
                modalBox.style.opacity = '1';
            }

            const hasOpenModal = document.querySelector('.modal-overlay.open');
            document.body.style.overflow = hasOpenModal ? 'hidden' : '';
        }

        window.openModal = function(id) {
            setModalState(id, true);
        };

        window.closeModal = function(id) {
            setModalState(id, false);
        };

        window.closeModalOutside = function(e, id) {
            if (e.target && e.target.id === id) {
                setModalState(id, false);
            }
        };

        window.openEditModal = function(period) {
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
            };

            document.getElementById('editSubtitle').textContent =
                `${period.academic_year} • ${semMap[period.semester] || period.semester}`;

            document.querySelectorAll('.edit-sem').forEach(radio => {
                radio.checked = radio.value === period.semester;
            });

            openModal('editModal');
        };

        window.openDeleteModal = function(action, label) {
            document.getElementById('deleteForm').action = action;
            document.getElementById('deletePeriodLabel').textContent = label;
            openModal('deleteModal');
        };

        function updateClock() {
            const now = new Date();
            const ph = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
            let h = ph.getHours();
            const m = String(ph.getMinutes()).padStart(2, '0');
            const s = String(ph.getSeconds()).padStart(2, '0');
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            const hh = String(h).padStart(2, '0');

            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December'];

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
            if (clearBtn) clearBtn.classList.remove('visible');

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
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');
            const semesterFilter = document.getElementById('semesterFilter');
            const statusFilter = document.getElementById('statusFilter');
            const items = document.querySelectorAll('.academic-item');

            if (searchInput) searchInput.value = '';
            if (semesterFilter) semesterFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            if (clearBtn) clearBtn.classList.remove('visible');

            items.forEach(item => item.style.display = '');

            ['jsEmptyState', 'jsEmptyStateGrid', 'jsFilterEmptyState', 'jsFilterEmptyStateGrid'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

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

            const allTableRows = () => tableBody ? tableBody.querySelectorAll('tr.tbl-row') : [];
            const allGridCards = () => gridView ? gridView.querySelectorAll('.academic-card') : [];

            function showSearchEmptyState(query) {
                let rowEmpty = document.getElementById('jsEmptyState');
                if (!rowEmpty && tableBody) {
                    rowEmpty = document.createElement('tr');
                    rowEmpty.id = 'jsEmptyState';
                    rowEmpty.innerHTML = `
          <td colspan="7" class="text-center py-12 text-gray-400">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1rem;text-align:center;gap:.5rem;">
              <div style="width:60px;height:60px;border-radius:16px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;margin-bottom:.75rem;">
                <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
              </div>
              <p class="font-semibold text-sm text-gray-500 mb-1">
                No results for "<span id="jsEmptyQuery"></span>"
              </p>
              <p class="text-xs text-gray-400">
                Try a different academic year or semester name.
              </p>
              <button
                type="button"
                onclick="clearAcademicSearch()"
                style="margin-top:.75rem;padding:.5rem 1.1rem;border-radius:10px;border:1.5px dashed #d1d5db;background:none;font-size:.8rem;color:#9ca3af;cursor:pointer;"
                onmouseover="this.style.borderColor='#8B0000';this.style.color='#8B0000';"
                onmouseout="this.style.borderColor='#d1d5db';this.style.color='#9ca3af';">
                <i class="fa-solid fa-xmark" style="margin-right:.4rem;font-size:.7rem;"></i>
                Clear search
              </button>
            </div>
          </td>`;
                    tableBody.appendChild(rowEmpty);
                }

                let gridEmpty = document.getElementById('jsEmptyStateGrid');
                if (!gridEmpty && gridView) {
                    gridEmpty = document.createElement('div');
                    gridEmpty.id = 'jsEmptyStateGrid';
                    gridEmpty.className = 'text-center py-12 text-gray-400';
                    gridEmpty.innerHTML = `
          <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1rem;text-align:center;gap:.5rem;">
            <div style="width:60px;height:60px;border-radius:16px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;margin-bottom:.75rem;">
              <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
            </div>
            <p class="font-semibold text-sm text-gray-500 mb-1">
              No results for "<span id="jsEmptyQueryGrid"></span>"
            </p>
            <p class="text-xs text-gray-400">
              Try a different academic year or semester name.
            </p>
            <button
              type="button"
              onclick="clearAcademicSearch()"
              style="margin-top:.75rem;padding:.5rem 1.1rem;border-radius:10px;border:1.5px dashed #d1d5db;background:none;font-size:.8rem;color:#9ca3af;cursor:pointer;"
              onmouseover="this.style.borderColor='#8B0000';this.style.color='#8B0000';"
              onmouseout="this.style.borderColor='#d1d5db';this.style.color='#9ca3af';">
              <i class="fa-solid fa-xmark" style="margin-right:.4rem;font-size:.7rem;"></i>
              Clear search
            </button>
          </div>`;
                    gridView.appendChild(gridEmpty);
                }

                const q1 = document.getElementById('jsEmptyQuery');
                const q2 = document.getElementById('jsEmptyQueryGrid');
                if (q1) q1.textContent = query;
                if (q2) q2.textContent = query;

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
          <td colspan="7" class="text-center text-gray-400 ap-empty">
            <div class="flex flex-col items-center justify-center text-center">
              <i class="fa-solid fa-filter text-3xl mb-3 opacity-30 block"></i>
              <p class="text-sm font-medium">No academic periods match the selected filters.</p>
            </div>
          </td>
        `;
                    tableBody.appendChild(rowEmpty);
                }

                let gridEmpty = document.getElementById('jsFilterEmptyStateGrid');
                if (!gridEmpty && gridView) {
                    gridEmpty = document.createElement('div');
                    gridEmpty.id = 'jsFilterEmptyStateGrid';
                    gridEmpty.className = 'text-center text-gray-400 ap-empty';
                    gridEmpty.innerHTML = `
          <div class="flex flex-col items-center justify-center text-center">
            <i class="fa-solid fa-filter text-3xl mb-3 opacity-30 block"></i>
            <p class="text-sm font-medium">No academic periods match the selected filters.</p>
          </div>
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
                    clearBtn.classList.toggle('visible', searchValue !== '');
                }
            }

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

            document.querySelectorAll('[data-open-modal]').forEach(button => {
                button.addEventListener('click', () => {
                    const target = button.getAttribute('data-open-modal');
                    if (target) window.openModal(target);
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach(button => {
                button.addEventListener('click', () => {
                    const target = button.getAttribute('data-close-modal');
                    if (target) window.closeModal(target);
                });
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.open').forEach(modal => {
                        setModalState(modal.id, false);
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
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    setModalState('addModal', true);
                });
            });

            addModalCloseButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    setModalState('addModal', false);
                });
            });

            [addModal, editModal, deleteModal].forEach(modal => {
                if (!modal) return;

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        setModalState(modal.id, false);
                    }
                });
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.open').forEach(modal => {
                        setModalState(modal.id, false);
                    });
                    document.body.style.overflow = '';
                }
            });

            bindTextareaPlaceholder('addDesc', 'addDescWrap');
            bindTextareaPlaceholder('editDesc', 'editDescWrap');

            // ── Search bar voice (external circular mic) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const input  = document.getElementById('searchInput');
                const micBtn = document.getElementById('apMicToggleBtn');
                const status = document.getElementById('apVoiceStatus');

                if (!input || !micBtn || !status) return;

                if (!SpeechRecognition) {
                    micBtn.disabled = true;
                    micBtn.setAttribute('aria-disabled', 'true');
                    return;
                }

                let listening   = false;
                let manualStop  = false;
                let recognition = null;

                const setStatus = (text, state) => {
                    status.textContent = text;
                    status.className = 'ap-voice-status';
                    if (state) status.classList.add(`is-${state}`);
                    status.classList.remove('hidden');
                };

                const hideStatus = (delay = 0) =>
                    window.setTimeout(() => status.classList.add('hidden'), delay);

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                };

                const createRecognition = () => {
                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;

                    let sawSpeech = false;
                    let timeoutId = null;

                    const clearT = () => {
                        if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                    };

                    r.onstart = () => {
                        timeoutId = window.setTimeout(() => {
                            if (listening && !sawSpeech) r.stop();
                        }, 6000);
                    };

                    r.onspeechend = () => { clearT(); try { r.stop(); } catch (e) {} };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const res   = event.results[i];
                            const chunk = res?.[0]?.transcript?.trim() || '';
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (res.isFinal) transcript = `${transcript} ${chunk}`.trim();
                            else if (!transcript) transcript = chunk;
                        }
                        transcript = transcript.trim();
                        if (transcript) {
                            clearT();
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            setStatus('Listening...', 'listening');
                        }
                    };

                    r.onerror = () => {
                        clearT();
                        if (manualStop) { manualStop = false; return; }
                        setMicState(false);
                        setStatus("Didn't catch that. Try again.", 'error');
                        hideStatus(2500);
                    };

                    r.onend = () => {
                        clearT();
                        if (manualStop) { manualStop = false; setMicState(false); return; }
                        const hadSpeech = sawSpeech || !!input.value.trim();
                        setMicState(false);
                        if (hadSpeech) { setStatus('Voice captured.', 'success'); hideStatus(2200); }
                        else { setStatus("Didn't catch that. Try again.", 'error'); hideStatus(2500); }
                    };

                    return r;
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) { try { recognition.stop(); } catch (err) {} }
                        return;
                    }

                    recognition = createRecognition();
                    try { recognition.start(); }
                    catch (err) {
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                        setMicState(false);
                        return;
                    }
                    setMicState(true);
                    setStatus('Listening...', 'listening');
                });
            })();

            // ── Academic Year voice mic (Add modal) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const input  = document.getElementById('addAcademicYearWrap')?.querySelector('input');
                const micBtn = document.getElementById('addYearMicBtn');
                const status = document.getElementById('addYearVoiceStatus');

                if (!input || !micBtn) return;
                if (!SpeechRecognition) { micBtn.disabled = true; return; }

                let listening = false;
                let manualStop = false;
                let recognition = null;
                let statusTimeout = null;

                const setStatus = (msg, type) => {
                    if (status) {
                        status.textContent = msg;
                        status.className = `ap-voice-status is-${type}`;
                    }
                };

                const hideStatus = (delay = 1500) => {
                    if (statusTimeout) clearTimeout(statusTimeout);
                    statusTimeout = setTimeout(() => {
                        if (status) status.classList.add('hidden');
                    }, delay);
                };

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                    micBtn.setAttribute('aria-pressed', isActive);
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) {}
                        return;
                    }

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;
                    recognition = r;

                    r.onstart = () => { 
                        setMicState(true); 
                        setStatus('Listening...', 'listening');
                    };
                    r.onend = () => { 
                        setMicState(false); 
                        manualStop = false; 
                    };
                    r.onerror = () => { 
                        if (!manualStop) {
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                        manualStop = false; 
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            if (event.results[i].isFinal) {
                                transcript = event.results[i][0].transcript.trim();
                            }
                        }
                        if (transcript) {
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            setStatus('Voice captured.', 'success');
                            hideStatus(2200);
                        }
                    };

                    try { r.start(); }
                    catch (e) { 
                        setMicState(false);
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                    }
                });
            })();

            // ── Academic Year voice mic (Edit modal) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const input  = document.getElementById('editYear');
                const micBtn = document.getElementById('editYearMicBtn');
                const status = document.getElementById('editYearVoiceStatus');

                if (!input || !micBtn) return;
                if (!SpeechRecognition) { micBtn.disabled = true; return; }

                let listening = false;
                let manualStop = false;
                let recognition = null;
                let statusTimeout = null;

                const setStatus = (msg, type) => {
                    if (status) {
                        status.textContent = msg;
                        status.className = `ap-voice-status is-${type}`;
                    }
                };

                const hideStatus = (delay = 1500) => {
                    if (statusTimeout) clearTimeout(statusTimeout);
                    statusTimeout = setTimeout(() => {
                        if (status) status.classList.add('hidden');
                    }, delay);
                };

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                    micBtn.setAttribute('aria-pressed', isActive);
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) {}
                        return;
                    }

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;
                    recognition = r;

                    r.onstart = () => { 
                        setMicState(true); 
                        setStatus('Listening...', 'listening');
                    };
                    r.onend = () => { 
                        setMicState(false); 
                        manualStop = false; 
                    };
                    r.onerror = () => { 
                        if (!manualStop) {
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                        manualStop = false; 
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            if (event.results[i].isFinal) {
                                transcript = event.results[i][0].transcript.trim();
                            }
                        }
                        if (transcript) {
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            setStatus('Voice captured.', 'success');
                            hideStatus(2200);
                        }
                    };

                    try { r.start(); }
                    catch (e) { 
                        setMicState(false);
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                    }
                });
            })();

            // ── Description textarea voice mic (Add modal) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const textarea = document.getElementById('addDesc');
                const micBtn   = document.getElementById('addDescMicBtn');
                const counter  = document.getElementById('addDescCounter');
                const status   = document.getElementById('addDescVoiceStatus');

                if (!textarea || !micBtn) return;
                if (!SpeechRecognition) { micBtn.disabled = true; return; }

                let listening   = false;
                let manualStop  = false;
                let recognition = null;
                let statusTimeout = null;

                const setStatus = (msg, type) => {
                    if (status) {
                        status.textContent = msg;
                        status.className = `ap-voice-status is-${type}`;
                    }
                };

                const hideStatus = (delay = 1500) => {
                    if (statusTimeout) clearTimeout(statusTimeout);
                    statusTimeout = setTimeout(() => {
                        if (status) status.classList.add('hidden');
                    }, delay);
                };

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                    micBtn.setAttribute('aria-pressed', isActive);
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) {}
                        return;
                    }

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;
                    recognition = r;

                    let sawSpeech = false;

                    r.onstart = () => { 
                        setMicState(true); 
                        setStatus('Listening...', 'listening');
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const res   = event.results[i];
                            const chunk = res?.[0]?.transcript?.trim() || '';
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (res.isFinal) transcript = `${transcript} ${chunk}`.trim();
                            else if (!transcript) transcript = chunk;
                        }
                        transcript = transcript.trim();
                        if (transcript) {
                            const limit = Number(textarea.dataset.wordLimit || 150);
                            const appended = (textarea.value + (textarea.value ? ' ' : '') + transcript).slice(0, limit);
                            textarea.value = appended;
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                            if (typeof updateWordCounter === 'function') updateWordCounter(textarea, counter);
                        }
                    };

                    r.onerror = () => { 
                        if (!manualStop) {
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                        manualStop = false; 
                    };
                    r.onend   = () => { setMicState(false); manualStop = false; };

                    try { r.start(); setMicState(true); }
                    catch (e) { 
                        setMicState(false);
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                    }
                });
            })();

            // ── Description textarea voice mic (Edit modal) ──
            (function () {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const textarea = document.getElementById('editDesc');
                const micBtn   = document.getElementById('editDescMicBtn');
                const counter  = document.getElementById('editDescCounter');
                const status   = document.getElementById('editDescVoiceStatus');

                if (!textarea || !micBtn) return;
                if (!SpeechRecognition) { micBtn.disabled = true; return; }

                let listening   = false;
                let manualStop  = false;
                let recognition = null;
                let statusTimeout = null;

                const setStatus = (msg, type) => {
                    if (status) {
                        status.textContent = msg;
                        status.className = `ap-voice-status is-${type}`;
                    }
                };

                const hideStatus = (delay = 1500) => {
                    if (statusTimeout) clearTimeout(statusTimeout);
                    statusTimeout = setTimeout(() => {
                        if (status) status.classList.add('hidden');
                    }, delay);
                };

                const setMicState = (isActive) => {
                    listening = isActive;
                    micBtn.classList.toggle('mic-active', isActive);
                    micBtn.innerHTML = isActive
                        ? '<i class="fa-solid fa-stop"></i>'
                        : '<i class="fa-solid fa-microphone"></i>';
                    micBtn.setAttribute('aria-pressed', isActive);
                };

                micBtn.addEventListener('click', () => {
                    if (listening && recognition) {
                        manualStop = true;
                        setMicState(false);
                        setStatus('Voice input stopped.', 'success');
                        hideStatus(1200);
                        try { recognition.abort(); } catch (e) {}
                        return;
                    }

                    const r = new SpeechRecognition();
                    r.lang = 'en-US';
                    r.continuous = false;
                    r.interimResults = true;
                    r.maxAlternatives = 1;
                    recognition = r;

                    let sawSpeech = false;

                    r.onstart = () => { 
                        setMicState(true); 
                        setStatus('Listening...', 'listening');
                    };

                    r.onresult = (event) => {
                        let transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const res   = event.results[i];
                            const chunk = res?.[0]?.transcript?.trim() || '';
                            if (!chunk) continue;
                            sawSpeech = true;
                            if (res.isFinal) transcript = `${transcript} ${chunk}`.trim();
                            else if (!transcript) transcript = chunk;
                        }
                        transcript = transcript.trim();
                        if (transcript) {
                            const limit = Number(textarea.dataset.wordLimit || 150);
                            const appended = (textarea.value + (textarea.value ? ' ' : '') + transcript).slice(0, limit);
                            textarea.value = appended;
                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                            if (typeof updateWordCounter === 'function') updateWordCounter(textarea, counter);
                        }
                    };

                    r.onerror = () => { 
                        if (!manualStop) {
                            setMicState(false);
                            setStatus("Didn't catch that. Try again.", 'error');
                            hideStatus(2500);
                        }
                        manualStop = false; 
                    };
                    r.onend   = () => { setMicState(false); manualStop = false; };

                    try { r.start(); setMicState(true); }
                    catch (e) { 
                        setMicState(false);
                        setStatus('Unable to start voice input.', 'error');
                        hideStatus(2500);
                    }
                });
            })();
        });
    </script>
@endsection