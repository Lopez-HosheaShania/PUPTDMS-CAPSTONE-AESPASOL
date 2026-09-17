@extends('layouts.app')

@php
    $pageMode = $pageMode ?? 'form';
    $transition = $transition ?? null;
    $types = $types ?? \App\Models\DentistTransition::TYPES;
    $standardTransitionTypes = array_values(array_filter($types, fn($type) => $type !== 'other'));
    $currentTransitionType = old('transition_type', $transition->transition_type ?? '');
    $isCustomTransitionType = $currentTransitionType !== '' && !in_array($currentTransitionType, $types, true);
    $formSelectedTransitionType = old(
        'transition_type_selector',
        $isCustomTransitionType ? 'other' : $currentTransitionType,
    );
    $formOtherTransitionType = old('transition_type_other', $isCustomTransitionType ? $currentTransitionType : '');
    $filterSelectedTransitionType = old('transition_type_filter_selector', $filters['transition_type'] ?? '');
    $filterOtherTransitionType = old('transition_type_other', $filters['transition_type_other'] ?? '');
    $initialPerPage = isset($transitions) ? $transitions->perPage() : 10;
    $initialPage = isset($transitions) ? $transitions->currentPage() : 1;
    $initialLastPage = isset($transitions) ? $transitions->lastPage() : 1;
    $initialTotal = isset($transitions) ? $transitions->total() : 0;
    $initialFrom = isset($transitions) ? $transitions->firstItem() ?? 0 : 0;
    $initialTo = isset($transitions) ? $transitions->lastItem() ?? 0 : 0;
    $successorName =
        $transition?->defaultSuccessor?->name ??
        ($transition?->items?->first(fn($item) => $item->successorDentist?->name)?->successorDentist?->name ??
            'Not assigned yet');
    $approvalStatusLabel = $transition?->approvedBy?->name ? 'Approved' : 'Pending approval';
    $formAccessValue = old('access_ends_at', optional($transition?->access_ends_at)->format('Y-m-d\TH:i'));
    $formAccessDate = $formAccessValue ? substr($formAccessValue, 0, 10) : '';
    $formAccessTime = $formAccessValue ? substr($formAccessValue, 11, 5) : '';
    $formLastWorkingDate = old('last_working_date', optional($transition?->last_working_date)->format('Y-m-d'));

    $formatTransitionType = static function ($value) use ($types) {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        return in_array($value, $types, true) ? str_replace('_', ' ', ucfirst($value)) : $value;
    };
    $routeNames = $routeNames ?? [
        'index' => 'admin.dentist-transitions.index',
        'create' => 'admin.dentist-transitions.create',
        'store' => 'admin.dentist-transitions.store',
        'show' => 'admin.dentist-transitions.show',
        'edit' => 'admin.dentist-transitions.edit',
        'update' => 'admin.dentist-transitions.update',
        'generate_items' => 'admin.dentist-transitions.generate-items',
        'assignments' => 'admin.dentist-transitions.assignments',
        'checklist' => 'admin.dentist-transitions.checklist',
        'finalize' => 'admin.dentist-transitions.finalize',
        'extend_access' => 'admin.dentist-transitions.extend-access',
        'cancel' => 'admin.dentist-transitions.cancel',
    ];

    if ($pageMode === 'index') {
        $pageTitle = 'Dentist Continuity Management';
        $heroTitle = 'Dentist Continuity Management';
        $heroSubtitle = null;
        $heroActions =
            '<a href="' .
            route($routeNames['create']) .
            '" class="ui-btn ui-btn-primary"><i class="fa-solid fa-plus"></i><span>Create Transition</span></a>';
    } elseif ($pageMode === 'show') {
        $pageTitle = 'Dentist Transition Details';
        $heroTitle = $transition->dentist->name ?? 'Unknown dentist';
        $heroSubtitle =
            $formatTransitionType($transition->transition_type) .
            ' transition. Last working date: ' .
            optional($transition->last_working_date)->format('M d, Y') .
            '. Access ends: ' .
            optional($transition->access_ends_at)->format('M d, Y h:i A') .
            '.';
        $heroActions =
            '<span class="dt-badge dt-badge-' .
            e($transition->status) .
            '">' .
            e(str_replace('_', ' ', ucfirst($transition->status))) .
            '</span><a href="' .
            route($routeNames['edit'], $transition) .
            '" class="dt-btn dt-btn-light">Edit Transition</a>';
    } else {
        $pageTitle = $transition && $transition->exists ? 'Edit Dentist Transition' : 'Create Dentist Transition';

        $heroTitle = $transition && $transition->exists ? 'Update Transition Plan' : 'Create Transition Plan';

        $heroSubtitle = null;
        $heroActions = null;
    }
@endphp

@php
    $layoutRole = $layoutRole ?? 'admin';
@endphp

@section('layout-role', $layoutRole)
@section('title', $pageTitle)

@section('styles')
    @vite('resources/css/pages/admin/dentist-continuity.css')
@endsection

@section('content')
    <main id="mainContent" class="app-page-shell page-enter mode-list continuity-page mode-{{ $pageMode }}">
        <div class="w-full dt-wrap">
            <div class="page-banner dt-hero">
                <div class="page-banner-inner">
                    <div class="dt-hero-copy">
                        <h1 class="page-title">{{ $heroTitle }}</h1>
                        @if ($heroSubtitle)
                            <p class="dt-subtitle">{{ $heroSubtitle }}</p>
                        @endif
                    </div>

                    @if ($heroActions)
                        <div class="dt-btn-row">
                            {!! $heroActions !!}
                        </div>
                    @endif
                </div>
            </div>

            @if (session('success'))
                <div class="dt-alert dt-alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="dt-alert dt-alert-error">{{ session('error') }}</div>
            @endif

            @if ($pageMode === 'index')
                <div class="admin-page-body">
                    <div id="statCards" class="stat-grid admin-dashboard-stat-grid dt-stat-grid mb-6">
                        <div class="stat-card s-all">
                            <div class="stat-card-info">
                                <span class="stat-label">Total Transitions</span>
                                <span class="stat-num"
                                    id="dtCountTotal">{{ $stats['total'] ?? $transitions->total() }}</span>
                                <span class="stat-footer">
                                    <i class="fa-solid fa-users-gear"></i>
                                    All recorded continuity plans
                                </span>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-users-gear"></i>
                            </div>
                        </div>

                        <div class="stat-card s-ongoing">
                            <div class="stat-card-info">
                                <span class="stat-label">Active Handover</span>
                                <span class="stat-num" id="dtCountActive">{{ $stats['active'] ?? 0 }}</span>
                                <span class="stat-footer">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                    Draft, review, and scheduled plans
                                </span>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </div>
                        </div>

                        <div class="stat-card s-approved">
                            <div class="stat-card-info">
                                <span class="stat-label">Completed</span>
                                <span class="stat-num" id="dtCountCompleted">{{ $stats['completed'] ?? 0 }}</span>
                                <span class="stat-footer">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Fully finalized transitions
                                </span>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                        </div>
                    </div>

                    <section class="dt-directory-card table-card">
                        <div class="dt-directory-toolbar">
                            <div class="dt-directory-heading">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-users-gear"></i>
                                </div>

                                <h2 class="font-bold text-gray-800 text-sm">All Transition Records</h2>

                                <span class="dt-count-badge" id="dtCountBadge">
                                    {{ $transitions->total() }}
                                </span>
                            </div>

                            <form method="GET" action="{{ route($routeNames['index']) }}" id="continuityToolbarForm"
                                class="dt-directory-filter-form">
                                <input type="hidden" name="status" id="dtStatusInput"
                                    value="{{ $filters['status'] ?? '' }}">
                                <input type="hidden" name="transition_type" id="dtTypeInput"
                                    value="{{ $filters['transition_type'] ?? '' }}">
                                <input type="hidden" name="transition_type_other" id="dtTypeOtherInput"
                                    value="{{ $filters['transition_type_other'] ?? '' }}">
                                <input type="hidden" name="successor" id="dtSuccessorInput"
                                    value="{{ $filters['successor'] ?? '' }}">
                                <input type="hidden" name="effective_date" id="dtEffectiveDateInput"
                                    value="{{ $filters['effective_date'] ?? '' }}">
                                <input type="hidden" name="per_page" id="dtPerPageInput"
                                    value="{{ $perPage ?? $initialPerPage }}">
                                <input type="hidden" name="page" id="dtPageInput"
                                    value="{{ $initialPage > 1 ? $initialPage : '' }}">

                                <div class="dt-search-row voice-search-row">
                                    <x-search-bar id="dtSearchInput" name="search"
                                        placeholder="Search dentist or successor…" :value="$filters['search'] ?? ''"
                                        callback="handleDentistContinuitySearch" :debounce="350"
                                        clear-label="Clear continuity search" class="table-toolbar-search" />

                                    <x-voice-input target="#dtSearchInput" status-id="dtSearchVoiceStatus"
                                        label="Voice search transitions" title="Voice search" />
                                </div>

                                <div class="dt-toolbar-right">
                                    <x-view-toggle id="dtViewToggle" class="dt-view-toggle"
                                        storage-key="dentistContinuityView" root="#mainContent" list-view="#dtListView"
                                        grid-view="#dtGridView" />
                                </div>
                            </form>
                        </div>

                        <x-pagination-bar id="dtPaginationTopBar" info-id="dtPaginationInfoTop"
                            pagination-id="dtPaginationTop" position="top" :show-entries="true"
                            page-size-id="dtPerPageSelect" page-size-callback="handleDentistContinuityPerPageChange"
                            :page-size-value="$perPage ?? $initialPerPage" page-size-label="entries" label="transitions" />

                        <div class="dt-directory-content">
                            <div id="dtListView" class="table-list-view">
                                <div class="table-scroll dt-table-wrap">
                                    <table class="data-table dt-table">
                                        <colgroup>
                                            <col style="width: 28%;">
                                            <col style="width: 24%;">
                                            <col style="width: 16%;">
                                            <col style="width: 18%;">
                                            <col style="width: 14%;">
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th>Dentist</th>
                                                <th>Successor</th>
                                                <th class="table-cell-center">Last Working Date</th>
                                                <th class="table-cell-center">Status</th>
                                                <th class="table-cell-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dtTableBody">
                                            @forelse ($transitions as $transition)
                                                <tr class="dt-table-row">
                                                    <td class="table-cell-main">
                                                        <div class="dt-cell-title">
                                                            {{ $transition->dentist->name ?? 'Unknown dentist' }}</div>
                                                        <div class="dt-cell-sub">
                                                            {{ $transition->dentist->email ?? 'No email' }}</div>
                                                    </td>
                                                    <td><span
                                                            class="dt-inline-value dt-inline-value-sm">{{ $transition->defaultSuccessor->name ?? 'Not assigned' }}</span>
                                                    </td>
                                                    <td class="table-cell-center">
                                                        <span class="dt-inline-value dt-inline-value-sm">
                                                            {{ optional($transition->last_working_date)->format('M d, Y') ?? 'Not set' }}
                                                        </span>
                                                    </td>
                                                    <td class="table-cell-center">
                                                        <span
                                                            class="dt-badge dt-badge-{{ $transition->status }}">{{ str_replace('_', ' ', ucfirst($transition->status)) }}</span>
                                                    </td>
                                                    <td class="table-cell-center table-action-cell">
                                                        <div class="ui-action-group dt-table-actions">
                                                            @if ($layoutRole === 'admin' && !in_array($transition->status, ['scheduled', 'completed', 'cancelled'], true))
                                                                <a href="{{ route($routeNames['edit'], $transition) }}"
                                                                    class="ui-action-btn ui-action-edit"
                                                                    data-tooltip="Edit transition"
                                                                    aria-label="Edit transition">
                                                                    <i class="fa-solid fa-pen"></i>
                                                                </a>
                                                            @endif

                                                            <a href="{{ route($routeNames['show'], $transition) }}"
                                                                class="ui-action-btn ui-action-view"
                                                                data-tooltip="View transition"
                                                                aria-label="View transition">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="table-empty-state-cell">
                                                        <div id="dtTableEmptyState" class="empty-state-host"></div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="dtGridView" class="table-grid-view" hidden>
                                <div class="table-record-grid dt-grid" id="dtGridBody">
                                    @forelse ($transitions as $transition)
                                        <article class="table-record-card dt-grid-card">
                                            <div class="dt-grid-top">
                                                <div class="dt-grid-number">
                                                    #{{ $transitions->firstItem() + $loop->index }}</div>
                                                <span
                                                    class="dt-badge dt-badge-{{ $transition->status }}">{{ str_replace('_', ' ', ucfirst($transition->status)) }}</span>
                                            </div>

                                            <div class="dt-grid-card-head">
                                                <div class="dt-grid-identity">
                                                    <strong>{{ $transition->dentist->name ?? 'Unknown dentist' }}</strong>
                                                    <span>{{ $transition->dentist->email ?? 'No email' }}</span>
                                                </div>
                                            </div>

                                            <div class="dt-grid-meta">
                                                <div class="dt-grid-field">
                                                    <div class="dt-grid-label">Transition Type</div>
                                                    <div class="dt-grid-value">
                                                        {{ $formatTransitionType($transition->transition_type) }}</div>
                                                </div>
                                                <div class="dt-grid-field">
                                                    <div class="dt-grid-label">Last Working Date</div>
                                                    <div class="dt-grid-value">
                                                        {{ optional($transition->last_working_date)->format('M d, Y') }}
                                                    </div>
                                                </div>
                                                <div class="dt-grid-field">
                                                    <div class="dt-grid-label">Access Expiration</div>
                                                    <div class="dt-grid-value">
                                                        {{ optional($transition->access_ends_at)->format('M d, Y') }}</div>
                                                </div>
                                                <div class="dt-grid-field">
                                                    <div class="dt-grid-label">Default Successor</div>
                                                    <div class="dt-grid-value">
                                                        {{ $transition->defaultSuccessor->name ?? 'Not assigned' }}</div>
                                                </div>
                                            </div>

                                            <div class="dt-grid-progress">
                                                <div class="dt-progress">
                                                    <div class="dt-progress-bar"
                                                        style="width: {{ $transition->progress_percentage }}%;"></div>
                                                </div>
                                                <span>{{ $transition->progress_percentage }}% complete</span>
                                            </div>

                                            <div class="ui-action-group dt-grid-actions">
                                                @if ($layoutRole === 'admin' && !in_array($transition->status, ['scheduled', 'completed', 'cancelled'], true))
                                                    <a href="{{ route($routeNames['edit'], $transition) }}"
                                                        class="ui-action-btn ui-action-edit"
                                                        data-tooltip="Edit transition" aria-label="Edit transition">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                @endif

                                                <a href="{{ route($routeNames['show'], $transition) }}"
                                                    class="ui-action-btn ui-action-view" data-tooltip="View transition"
                                                    aria-label="View transition">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </div>
                                        </article>
                                    @empty
                                        <div id="dtGridEmptyState" class="empty-state-host table-grid-empty"></div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <x-pagination-bar id="dtPaginationBottomBar" info-id="dtPaginationInfoBottom"
                            pagination-id="dtPaginationBottom" position="bottom" :show-entries="false" label="transitions" />
                    </section>

                    <x-filter-drawer id="dtFilterModal" title="Filters" close-id="dtCloseFilterModalBtn"
                        clear-id="dtClearFiltersModal" clear-label="Clear Filters" cancel-id="dtCancelFilterBtn"
                        cancel-label="Cancel" apply-id="dtApplyFilters" apply-label="Apply Filters">
                        <x-filter-group title="Filter By Status">
                            <div class="filter-date-input-wrap">
                                <select id="dtFilterStatus" data-native-select>
                                    <option value="">All statuses</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                            {{ str_replace('_', ' ', ucfirst($status)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </x-filter-group>

                        <x-filter-group title="Filter By Reason">
                            <div class="filter-date-input-wrap">
                                <select id="dtFilterType" data-native-select data-transition-type-filter>
                                    <option value="">All reasons</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type }}" @selected($filterSelectedTransitionType === $type)>
                                            {{ str_replace('_', ' ', ucfirst($type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </x-filter-group>

                        <x-filter-group title="Custom Reason">
                            <div id="dtFilterOtherWrap" class="filter-date-input-wrap"
                                {{ $filterSelectedTransitionType !== 'other' ? 'hidden' : '' }}>
                                <input id="dtFilterTypeOther" type="text" value="{{ $filterOtherTransitionType }}"
                                    placeholder="Enter custom reason" data-transition-type-other-filter
                                    {{ $filterSelectedTransitionType !== 'other' ? 'hidden disabled' : '' }}>
                            </div>
                        </x-filter-group>

                        <x-filter-group title="Default Successor">
                            <div class="filter-date-input-wrap">
                                <input id="dtFilterSuccessor" type="text" value="{{ $filters['successor'] ?? '' }}"
                                    placeholder="Search successor" autocomplete="off">
                            </div>
                        </x-filter-group>

                        <x-filter-group title="Effective Date">
                            <div class="filter-date-input-wrap">
                                <input id="dtFilterEffectiveDate" type="text" class="js-flatpickr-date"
                                    value="{{ $filters['effective_date'] ?? '' }}" placeholder="Select date"
                                    autocomplete="off">
                            </div>
                        </x-filter-group>
                    </x-filter-drawer>
                </div>
            @elseif ($pageMode === 'show')
                <div class="admin-page-body dt-show-layout">
                    <div class="dt-summary-grid stat-grid admin-dashboard-stat-grid dt-stat-grid">
                        <div class="stat-card s-all">
                            <div class="stat-card-info">
                                <span class="stat-label">Future Appointments</span>
                                <span class="stat-num">{{ $summary['future_appointments'] }}</span>
                                <span class="stat-footer">
                                    <i class="fa-solid fa-calendar-check"></i>
                                    Appointments currently under the departing dentist.
                                </span>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                        </div>

                        <div class="stat-card s-ongoing">
                            <div class="stat-card-info">
                                <span class="stat-label">Ready To Transfer</span>
                                <span class="stat-num">{{ $summary['ready_to_transfer'] }}</span>
                                <span class="stat-footer">
                                    <i class="fa-solid fa-people-arrows-left-right"></i>
                                    Items with valid successor coverage.
                                </span>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-people-arrows-left-right"></i>
                            </div>
                        </div>

                        <div class="stat-card s-approved">
                            <div class="stat-card-info">
                                <span class="stat-label">Transferred</span>
                                <span class="stat-num">{{ $summary['transferred_items'] }}</span>
                                <span class="stat-footer">
                                    <i class="fa-solid fa-file-circle-check"></i>
                                    Records already completed by the handover process.
                                </span>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-file-circle-check"></i>
                            </div>
                        </div>

                        <div class="stat-card s-rejected">
                            <div class="stat-card-info">
                                <span class="stat-label">Critical Unresolved</span>
                                <span class="stat-num">{{ $summary['critical_unresolved_items'] }}</span>
                                <span class="stat-footer">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    Items still blocking finalization.
                                </span>
                            </div>

                            <div class="stat-icon-wrapper">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                        </div>
                    </div>

                    <div class="dt-page-grid">
                        <section class="dt-panel table-card dt-show-panel">
                            <div class="dt-section-head">
                                <div class="dt-show-head">
                                    <div class="card-header-icon">
                                        <i class="fa-solid fa-clipboard-list"></i>
                                    </div>

                                    <div>
                                        <h2>Transition Information</h2>
                                        <p>{{ $formatTransitionType($transition->transition_type) }} &middot;
                                            {{ $transition->dentist->name ?? 'Unknown dentist' }}</p>
                                    </div>
                                </div>
                                @if ($layoutRole === 'admin')
                                    <div class="dt-show-actions">
                                        <span
                                            class="dt-badge dt-badge-{{ $transition->status }}">{{ str_replace('_', ' ', ucfirst($transition->status)) }}</span>
                                        <form action="{{ route($routeNames['generate_items'], $transition) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="dt-btn dt-btn-primary dt-btn-sm dt-btn-impact"><i
                                                    class="fa-solid fa-rotate"></i>Refresh Impact Summary</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="dt-show-actions">
                                        <span
                                            class="dt-badge dt-badge-{{ $transition->status }}">{{ str_replace('_', ' ', ucfirst($transition->status)) }}</span>
                                    </div>
                                @endif
                            </div>

                            <section class="dt-show-block">
                                <div class="dt-show-block-label"><i class="fa-solid fa-users"></i><span>People</span>
                                </div>
                                <div class="dt-people-layout">
                                    <div class="dt-people-primary">
                                        <div class="dt-people-card">
                                            <div class="dt-people-card-icon">
                                                <i class="fa-solid fa-user"></i>
                                            </div>
                                            <div class="dt-people-card-copy">
                                                <span>Departing Dentist</span>
                                                <strong>{{ $transition->dentist->name ?? 'Unknown dentist' }}</strong>
                                            </div>
                                        </div>
                                        <div class="dt-people-card dt-people-card-warning">
                                            <div class="dt-people-card-icon">
                                                <i class="fa-solid fa-user-plus"></i>
                                            </div>
                                            <div class="dt-people-card-copy">
                                                <span>Default Successor</span>
                                                <strong>{{ $successorName }}</strong>
                                            </div>
                                        </div>
                                        <div class="dt-people-card dt-people-card-warning">
                                            <div class="dt-people-card-icon">
                                                <i class="fa-solid fa-shield-halved"></i>
                                            </div>
                                            <div class="dt-people-card-copy">
                                                <span>Approval Status</span>
                                                <strong>{{ $approvalStatusLabel }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <dl class="dt-people-secondary">
                                        <div class="dt-people-secondary-item">
                                            <div class="dt-people-secondary-icon">
                                                <i class="fa-regular fa-user"></i>
                                            </div>
                                            <div>
                                                <dt>Initiated by</dt>
                                                <dd>{{ $transition->initiatedBy->name ?? 'Unknown admin' }}</dd>
                                            </div>
                                        </div>
                                        <div class="dt-people-secondary-item">
                                            <div class="dt-people-secondary-icon">
                                                <i class="fa-regular fa-circle-check"></i>
                                            </div>
                                            <div>
                                                <dt>Reviewed by</dt>
                                                <dd>{{ $transition->reviewedBy->name ?? 'Pending review' }}</dd>
                                            </div>
                                        </div>
                                    </dl>
                                </div>
                            </section>

                            <section class="dt-show-block">
                                <div class="dt-show-block-label"><i
                                        class="fa-regular fa-calendar-days"></i><span>Timeline</span></div>
                                <div class="dt-timeline-strip">
                                    <div class="dt-timeline-strip-line" aria-hidden="true"></div>
                                    <div class="dt-timeline-strip-node" aria-hidden="true"></div>
                                    <div class="dt-timeline-strip-node" aria-hidden="true"></div>
                                    <div class="dt-timeline-strip-node" aria-hidden="true"></div>
                                </div>
                                <div class="dt-timeline-grid">
                                    <div class="dt-timeline-card">
                                        <div class="dt-timeline-card-icon">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </div>
                                        <div class="dt-timeline-card-copy">
                                            <span class="dt-timeline-card-step">01</span>
                                            <span class="dt-timeline-card-label">Transition Type</span>
                                            <strong>{{ $formatTransitionType($transition->transition_type) }}</strong>
                                        </div>
                                    </div>
                                    <div class="dt-timeline-card">
                                        <div class="dt-timeline-card-icon">
                                            <i class="fa-regular fa-calendar"></i>
                                        </div>
                                        <div class="dt-timeline-card-copy">
                                            <span class="dt-timeline-card-step">02</span>
                                            <span class="dt-timeline-card-label">Last Working Date</span>
                                            <strong>{{ optional($transition->last_working_date)->format('M d, Y') ?? 'Not set' }}</strong>
                                        </div>
                                    </div>
                                    <div class="dt-timeline-card">
                                        <div class="dt-timeline-card-icon">
                                            <i class="fa-regular fa-clock"></i>
                                        </div>
                                        <div class="dt-timeline-card-copy">
                                            <span class="dt-timeline-card-step">03</span>
                                            <span class="dt-timeline-card-label">Access Expiration</span>
                                            <strong>{{ optional($transition->access_ends_at)->format('M d, Y h:i A') ?? 'Not set' }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            @if ($transition->handover_notes || $transition->remarks)
                                <div class="dt-note-grid">
                                    @if ($transition->handover_notes)
                                        <div class="dt-note-block">
                                            <h3>Handover Notes</h3>
                                            <p>{{ $transition->handover_notes }}</p>
                                        </div>
                                    @endif

                                    @if ($transition->remarks)
                                        <div class="dt-note-block">
                                            <h3>Admin Remarks</h3>
                                            <p>{{ $transition->remarks }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    </div>

                    <section class="dt-panel table-card dt-show-panel">
                        <div class="dt-section-head">
                            <div class="dt-show-head">
                                <div class="card-header-icon">
                                    <i class="fa-solid fa-people-arrows-left-right"></i>
                                </div>

                                <div>
                                    <h2>Active Responsibility Table</h2>
                                    <p>Assign a default successor or override per eligible record before finalization.</p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route($routeNames['assignments'], $transition) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="dt-assign-toolbar-card dt-responsibility-topcard">
                                <div class="dt-assign-toolbar">
                                    <div class="dt-assign-field dt-assign-field-wide" data-global-field>
                                        <label class="dt-label dt-label-emphasis"
                                            for="default_successor_dentist_id">Default successor</label>
                                        <select id="default_successor_dentist_id" name="default_successor_dentist_id"
                                            class="dt-select js-custom-select">
                                            <option value="">Select a dentist...</option>
                                            @foreach ($dentists as $dentist)
                                                @continue($dentist->id === $transition->dentist_id)
                                                <option value="{{ $dentist->id }}" @selected($transition->default_successor_dentist_id == $dentist->id)>
                                                    {{ $dentist->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <button type="submit" class="dt-btn dt-btn-primary dt-btn-save-assignments">
                                        <i class="fa-regular fa-floppy-disk"></i>
                                        <span>Save Assignments</span>
                                    </button>
                                </div>
                            </div>

                            <div class="table-scroll dt-table-wrap dt-responsibility-wrap">
                                <table class="data-table dt-table dt-responsibility-table">
                                    <thead>
                                        <tr>
                                            <th>Record</th>
                                            <th>Patient</th>
                                            <th>Assignment</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $pendingTasks = $transition->items->filter(function ($item) {
                                                return in_array(
                                                    $item->item_type,
                                                    ['appointment', 'document_request'],
                                                    true,
                                                );
                                            });
                                        @endphp
                                        @forelse ($pendingTasks as $item)
                                            @php
                                                $taskLabel = match ($item->item_type) {
                                                    'document_request' => 'Review and endorse pending document request',
                                                    'appointment' => 'Reassign and review upcoming appointment',
                                                    default => 'Review and transfer continuity responsibility',
                                                };
                                                $taskHint = match ($item->item_type) {
                                                    'document_request'
                                                        => 'Include this request in the continuity handoff to the successor dentist.',
                                                    'appointment'
                                                        => 'Transfer this active appointment to the successor dentist for continued care.',
                                                    default
                                                        => 'Mark this item if it should be handed over during continuity review.',
                                                };
                                                $isSelectedForTransfer = !in_array(
                                                    $item->transfer_status,
                                                    ['excluded', 'manually_resolved'],
                                                    true,
                                                );
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="dt-record-checkcell" data-transfer-item-row>
                                                        <input type="hidden"
                                                            name="items[{{ $item->id }}][selected_for_transfer]"
                                                            value="0">
                                                        <button type="button"
                                                            class="dt-record-selectcard @if ($isSelectedForTransfer) is-selected @endif"
                                                            data-transfer-toggle
                                                            aria-pressed="{{ $isSelectedForTransfer ? 'true' : 'false' }}">
                                                            <input type="hidden"
                                                                name="items[{{ $item->id }}][selected_for_transfer]"
                                                                value="{{ $isSelectedForTransfer ? '1' : '0' }}"
                                                                data-transfer-selected>
                                                            <span class="dt-record-selectmark" aria-hidden="true">
                                                                <i class="fa-solid fa-check"></i>
                                                            </span>
                                                            <span class="dt-table-stack">
                                                                <span
                                                                    class="dt-inline-value dt-inline-value-sm">{{ str_replace('_', ' ', ucfirst($item->item_type)) }}</span>
                                                                <span
                                                                    class="dt-inline-value dt-inline-value-xs">{{ $item->reference_label }}</span>
                                                            </span>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td><span
                                                        class="dt-inline-value dt-inline-value-sm">{{ $item->patient->name ?? 'Unknown patient' }}</span>
                                                </td>
                                                <td>
                                                    <div class="dt-task-cell">
                                                        <span class="dt-task-copy">
                                                            <strong>{{ $taskLabel }}</strong>
                                                            <small>{{ $taskHint }}</small>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="hidden"
                                                        name="items[{{ $item->id }}][transfer_status]"
                                                        value="{{ $item->transfer_status }}" data-transfer-status>
                                                    <input type="hidden"
                                                        name="items[{{ $item->id }}][resolution_type]"
                                                        value="{{ $item->resolution_type }}">
                                                    <input class="dt-input" type="text"
                                                        name="items[{{ $item->id }}][remarks]"
                                                        value="{{ $item->remarks }}">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="dt-empty dt-responsibility-empty">
                                                    <div
                                                        class="empty-state empty-state-compact dt-responsibility-empty-state">
                                                        <div class="empty-state-icon">
                                                            <i class="fa-regular fa-clipboard-check"></i>
                                                        </div>
                                                        <h3 class="empty-state-title">No responsibilities to transfer</h3>
                                                        <p class="empty-state-sub">There are currently no active
                                                            dentist-owned records assigned to this account.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="dt-responsibility-footnote">
                                <i class="fa-regular fa-shield-check"></i>
                            </div>
                        </form>
                    </section>

                    <section class="dt-panel table-card dt-show-panel dt-handover-controls-panel">

                        {{-- HEADER --}}
                        <div class="dt-handover-controls-header">
                            <div class="dt-handover-header-icon">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>

                            <div>
                                <h2>Handover Controls</h2>
                                <p>Manage remaining access or cancel this handover.</p>
                            </div>
                        </div>

                        {{-- TWO CONTROL CARDS --}}
                        <div class="dt-handover-controls-grid">

                            {{-- EXTEND ACCESS --}}
                            <form action="{{ route($routeNames['extend_access'], $transition) }}" method="POST"
                                class="dt-handover-control-card" id="dtExtendAccessForm">
                                @csrf

                                <div class="dt-handover-card-header">
                                    <div class="dt-handover-card-icon">
                                        <i class="fa-regular fa-calendar"></i>
                                    </div>

                                    <div>
                                        <h3>Extend Access</h3>
                                        <p>
                                            Push back when the departing dentist's system access ends.
                                        </p>
                                    </div>
                                </div>

                                <div class="dt-handover-access-fields">

                                    {{-- DATE --}}
                                    <div class="dt-handover-field">
                                        <label for="dtExtendAccessDate">
                                            Date
                                        </label>

                                        <div class="global-control-wrap dt-handover-input-wrap" data-flatpickr-trigger>
                                            <i class="fa-regular fa-calendar global-control-icon" aria-hidden="true"></i>

                                            <input id="dtExtendAccessDate" type="text"
                                                class="form-input-custom global-form-icon js-flatpickr-date"
                                                value="{{ optional($transition->access_ends_at)->format('Y-m-d') }}"
                                                placeholder="Select date" readonly required>
                                        </div>
                                    </div>

                                    {{-- TIME --}}
                                    <div class="dt-handover-field">
                                        <label for="dtExtendAccessTime">
                                            Time
                                        </label>

                                        <div class="global-control-wrap dt-handover-input-wrap">
                                            <i class="fa-regular fa-clock global-control-icon" aria-hidden="true"></i>

                                            <input id="dtExtendAccessTime" type="text"
                                                class="form-input-custom global-form-icon js-flatpickr-time"
                                                value="{{ optional($transition->access_ends_at)->format('H:i') }}"
                                                placeholder="Select time" readonly required>
                                        </div>
                                    </div>

                                </div>

                                {{-- VALUE SENT TO BACKEND --}}
                                <input id="dtExtendAccessValue" type="hidden" name="access_ends_at"
                                    value="{{ optional($transition->access_ends_at)->format('Y-m-d\\TH:i') }}" required>

                                <button type="submit" class="dt-btn dt-handover-extend-btn">
                                    Extend Access
                                </button>
                            </form>


                            {{-- CANCEL HANDOVER --}}
                            <form action="{{ route($routeNames['cancel'], $transition) }}" method="POST"
                                class="dt-handover-control-card dt-handover-cancel-card">
                                @csrf

                                <div class="dt-handover-card-header">
                                    <div class="dt-handover-card-icon dt-handover-card-icon-danger">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                    </div>

                                    <div>
                                        <h3>Cancel Handover</h3>
                                        <p>
                                            This stops the handover and restores normal access.
                                        </p>
                                    </div>
                                </div>

                                <div class="dt-handover-field dt-handover-cancel-field">
                                    <label for="dtCancellationReason">
                                        Reason for cancellation
                                    </label>

                                    <textarea id="dtCancellationReason" name="cancellation_reason" class="dt-textarea dt-handover-cancel-textarea"
                                        rows="5" placeholder="Explain why this handover is being cancelled..." required></textarea>
                                </div>

                                <button type="submit" class="dt-btn dt-handover-cancel-btn" @disabled(in_array($transition->status, ['completed', 'cancelled'], true))>
                                    Cancel Handover
                                </button>
                            </form>

                        </div>
                    </section>
                </div>
            @else
                <div class="dt-panel">
                    <form action="{{ $formAction }}" method="POST" data-global-validation data-global-selects
                        id="dtTransitionForm">
                        @csrf
                        @if (($formMethod ?? 'POST') !== 'POST')
                            @method($formMethod)
                        @endif

                        <div class="dt-form-layout">
                            <section class="dt-form-master-shell dt-span-2">
                                <section class="dt-form-hero-card dt-span-2 dt-form-info-card">
                                    <div class="dt-form-stage-card">
                                        <div class="dt-form-stage-head">
                                            <div class="card-header-icon dt-form-stage-step" aria-hidden="true"><i
                                                    class="fa-solid fa-user-group"></i></div>
                                            <h4>Dentist handover</h4>
                                        </div>

                                        <div class="dt-form-stage-grid">
                                            <div class="dt-form-stage-field">

                                                <div data-global-field class="dt-stage-control dt-stage-control-user">
                                                    <label class="dt-label" for="dentist_id">Departing Dentist <span
                                                            class="required-mark">*</span></label>
                                                    <select id="dentist_id" name="dentist_id"
                                                        class="dt-select js-custom-select"
                                                        data-field-label="Departing Dentist"
                                                        data-required-message="Please select a departing dentist."
                                                        @if ($transition->exists) disabled @endif required>
                                                        <option value="">Select dentist</option>
                                                        @foreach ($dentists as $dentist)
                                                            <option value="{{ $dentist->id }}"
                                                                @selected(old('dentist_id', $transition->dentist_id) == $dentist->id)>
                                                                {{ $dentist->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if ($transition->exists)
                                                        <input type="hidden" name="dentist_id"
                                                            value="{{ $transition->dentist_id }}">
                                                    @endif
                                                    @error('dentist_id')
                                                        <p class="dt-error">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                <div class="dt-form-stage-note">
                                                    <i class="fa-regular fa-circle-check"></i>
                                                    <span>Current responsibilities will be transitioned from this
                                                        dentist.</span>
                                                </div>
                                            </div>

                                            <div class="dt-form-stage-arrow" aria-hidden="true">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </div>

                                            <div class="dt-form-stage-field">

                                                <div data-global-field class="dt-stage-control dt-stage-control-user">
                                                    <label class="dt-label" for="default_successor_dentist_id">Default
                                                        Successor Dentist <span class="required-mark">*</span></label>
                                                    <select id="default_successor_dentist_id"
                                                        name="default_successor_dentist_id"
                                                        class="dt-select js-custom-select"
                                                        data-field-label="Default Successor Dentist"
                                                        data-required-message="Please select a default successor dentist."
                                                        required>
                                                        <option value="">Select successor dentist</option>
                                                        @foreach ($dentists as $dentist)
                                                            @continue($dentist->id === $transition->dentist_id)
                                                            <option value="{{ $dentist->id }}"
                                                                @selected(old('default_successor_dentist_id', $transition->default_successor_dentist_id) == $dentist->id)>
                                                                {{ $dentist->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('default_successor_dentist_id')
                                                        <p class="dt-error">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                <div class="dt-form-stage-note">
                                                    <i class="fa-regular fa-circle-check"></i>
                                                    <span>This dentist will receive applicable cases after handover.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="dt-form-stage-card dt-form-stage-card-divider">
                                        <div class="dt-form-stage-head">
                                            <div class="card-header-icon dt-form-stage-step" aria-hidden="true"><i
                                                    class="fa-solid fa-calendar-check"></i></div>
                                            <h4>Transition details</h4>
                                        </div>

                                        <div class="dt-form-stage-grid dt-form-stage-grid-details">
                                            <div class="dt-form-stage-field">

                                                <div data-global-field class="dt-stage-control dt-stage-control-flag">
                                                    <label class="dt-label" for="transition_type">Reason for Transition
                                                        <span class="required-mark">*</span></label>
                                                    <select id="transition_type" name="transition_type"
                                                        class="dt-select js-custom-select"
                                                        data-field-label="Transition Reason"
                                                        data-required-message="Please select a transition reason."
                                                        data-transition-type-selector required>
                                                        <option value="">Select reason</option>
                                                        @foreach ($types as $type)
                                                            <option value="{{ $type }}"
                                                                @selected($formSelectedTransitionType === $type)>
                                                                {{ str_replace('_', ' ', ucfirst($type)) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="dt-other-transition-wrap" data-transition-type-other-wrap
                                                        @if ($formSelectedTransitionType !== 'other') hidden @endif>
                                                        <input type="text" name="transition_type_other"
                                                            class="dt-input dt-other-transition-field"
                                                            value="{{ $formOtherTransitionType }}"
                                                            placeholder="Enter custom transition reason"
                                                            data-transition-type-other
                                                            data-field-label="Other Transition Reason"
                                                            data-required-message="Please enter the other transition reason."
                                                            @if ($formSelectedTransitionType !== 'other') hidden disabled @endif>
                                                    </div>
                                                    @error('transition_type')
                                                        <p class="dt-error">{{ $message }}</p>
                                                    @enderror
                                                    @error('transition_type_other')
                                                        <p class="dt-error">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="dt-form-stage-field">
                                                <div data-global-field class="dt-stage-control dt-stage-control-calendar">
                                                    <label class="dt-label" for="last_working_date">
                                                        Last Working Date
                                                        <span class="required-mark">*</span>
                                                    </label>

                                                    <div class="global-control-wrap dt-form-control-wrap">
                                                        <input id="last_working_date" type="text"
                                                            name="last_working_date"
                                                            class="form-input-custom js-flatpickr-date"
                                                            data-field-label="Last Working Date"
                                                            data-required-message="Please select the last working date."
                                                            value="{{ $formLastWorkingDate }}" placeholder="Select date"
                                                            readonly required>
                                                    </div>

                                                    <div class="dt-form-stage-note">
                                                        <i class="fa-regular fa-clock"></i>
                                                        <span>
                                                            Dentist access will automatically end at
                                                            11:59 PM on the selected last working date.
                                                        </span>
                                                    </div>

                                                    @error('last_working_date')
                                                        <p class="dt-error">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <div class="dt-form-grid dt-form-grid-enhanced dt-additional-notes-wrap">
                                    <section class="dt-step-section dt-form-card dt-span-2">

                                        <div data-global-field>
                                            <label class="dt-label" for="handover_notes">
                                                Additional Notes
                                                <span class="text-muted">(Optional)</span>
                                            </label>

                                            <textarea id="handover_notes" name="handover_notes" class="dt-input dt-textarea dt-form-textarea" rows="4"
                                                data-field-label="Additional Notes" placeholder="Add optional notes or instructions...">{{ old('handover_notes', $transition->handover_notes) }}</textarea>

                                            @error('handover_notes')
                                                <p class="dt-error">{{ $message }}</p>
                                            @enderror

                                        </div>
                                    </section>
                                </div>

                                <div class="dt-form-actions">
                                    <a href="{{ route($routeNames['index']) }}" class="dt-btn dt-btn-secondary">
                                        Back
                                    </a>

                                    <button type="submit" class="dt-btn dt-btn-primary">
                                        {{ $transition->exists ? 'Update Handover' : 'Confirm Handover' }}
                                    </button>
                                </div>

                            </section>
                        </div>

                    </form>
                </div>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pageMode = @js($pageMode);
            const continuityToolbarForm = document.getElementById('continuityToolbarForm');
            const filterBtn = document.getElementById('dtFilterBtn');
            const filterBadgeId = 'dtFilterBadge';
            const searchInput = document.getElementById('dtSearchInput');
            const filterModalId = 'dtFilterModal';
            const pageInput = document.getElementById('dtPageInput');
            const perPageInput = document.getElementById('dtPerPageInput');
            const statusInput = document.getElementById('dtStatusInput');
            const typeInput = document.getElementById('dtTypeInput');
            const typeOtherInput = document.getElementById('dtTypeOtherInput');
            const successorInput = document.getElementById('dtSuccessorInput');
            const effectiveDateInput = document.getElementById('dtEffectiveDateInput');
            const filterStatus = document.getElementById('dtFilterStatus');
            const filterType = document.getElementById('dtFilterType');
            const filterTypeOther = document.getElementById('dtFilterTypeOther');
            const filterSuccessor = document.getElementById('dtFilterSuccessor');
            const filterEffectiveDate = document.getElementById('dtFilterEffectiveDate');
            const transitionForm = document.getElementById('dtTransitionForm');
            const extendAccessForm = document.getElementById('dtExtendAccessForm');
            const extendAccessDate = document.getElementById('dtExtendAccessDate');
            const extendAccessTime = document.getElementById('dtExtendAccessTime');
            const extendAccessValue = document.getElementById('dtExtendAccessValue');
            let syncToolbarInputs = () => {};
            let dtFetch = () => {};
            const resetToFirstPage = () => {
                if (pageInput) {
                    pageInput.value = 1;
                }
            };

            const dtState = {
                search: String({{ Js::from($filters['search'] ?? '') }} || '').trim(),
                status: String({{ Js::from($filters['status'] ?? '') }} || '').trim(),
                transitionType: String({{ Js::from($filters['transition_type'] ?? '') }} || '').trim(),
                transitionTypeOther: String({{ Js::from($filters['transition_type_other'] ?? '') }} || '')
                    .trim(),
                successor: String({{ Js::from($filters['successor'] ?? '') }} || '').trim(),
                effectiveDate: String({{ Js::from($filters['effective_date'] ?? '') }} || '').trim(),
                perPage: Number({{ (int) ($perPage ?? $initialPerPage) }}) || 10,
                page: Number({{ $initialPage }}) || 1,
            };

            let dtController = null;

            const bindOtherTransitionToggle = (selector, targetSelector, wrapperSelector = null) => {
                const select = document.querySelector(selector);
                const target = document.querySelector(targetSelector);

                if (!select || !target) return;

                const wrapper = wrapperSelector ?
                    document.querySelector(wrapperSelector) :
                    target.closest('.dt-filter-extra') || target.parentElement;

                const syncState = () => {
                    const isOther = select.value === 'other';

                    if (wrapper) {
                        wrapper.hidden = !isOther;
                    }

                    target.hidden = !isOther;
                    target.disabled = !isOther;
                    target.required = isOther;

                    if (!isOther) {
                        target.value = '';
                    }
                };

                syncState();
                select.addEventListener('change', syncState);
            };

            bindOtherTransitionToggle('[data-transition-type-selector]', '[data-transition-type-other]',
                '[data-transition-type-other-wrap]');

            if (extendAccessForm && extendAccessDate && extendAccessTime && extendAccessValue) {
                const syncExtendAccessValue = () => {
                    const dateValue = String(extendAccessDate.value || '').trim();
                    const timeValue = String(extendAccessTime.value || '').trim();

                    if (!dateValue || !timeValue) {
                        extendAccessValue.value = '';
                        return;
                    }

                    extendAccessValue.value = `${dateValue}T${timeValue}`;
                };

                syncExtendAccessValue();
                extendAccessDate.addEventListener('change', syncExtendAccessValue);
                extendAccessTime.addEventListener('change', syncExtendAccessValue);
                extendAccessForm.addEventListener('submit', syncExtendAccessValue);
            }

            document.querySelectorAll('[data-transfer-item-row]').forEach(row => {
                const toggle = row.querySelector('[data-transfer-toggle]');
                const selectedField = row.querySelector('[data-transfer-selected]');
                const statusField = row.closest('tr')?.querySelector('[data-transfer-status]');

                if (!toggle || !selectedField || !statusField) {
                    return;
                }

                const syncTransferRow = () => {
                    const isSelected = String(selectedField.value || '0') === '1';

                    toggle.classList.toggle('is-selected', isSelected);
                    toggle.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                    statusField.value = isSelected ? 'pending' : 'excluded';
                };

                syncTransferRow();
                toggle.addEventListener('click', () => {
                    selectedField.value = selectedField.value === '1' ? '0' : '1';
                    syncTransferRow();
                });
            });

            if (pageMode === 'index') {
                bindOtherTransitionToggle('[data-transition-type-filter]', '#dtFilterTypeOther',
                    '#dtFilterOtherWrap');
            }

            const updateFilterBadge = () => {
                if (typeof window.setGlobalFilterButtonState !== 'function') {
                    return;
                }

                const fields = [statusInput, typeInput, successorInput, effectiveDateInput];
                let count = fields.reduce((total, field) => total + (String(field?.value || '').trim() !== '' ?
                    1 : 0), 0);

                if (String(typeInput?.value || '').trim() === 'other' && String(typeOtherInput?.value || '')
                    .trim() !== '') {
                    count += 1;
                }

                window.setGlobalFilterButtonState({
                    buttonId: 'dtFilterBtn',
                    badgeId: filterBadgeId,
                    count,
                });
            };

            const syncDrawerFromToolbar = () => {
                if (filterStatus) filterStatus.value = statusInput?.value || '';
                if (filterType) filterType.value = typeInput?.value || '';
                if (filterTypeOther) filterTypeOther.value = typeOtherInput?.value || '';
                if (filterSuccessor) filterSuccessor.value = successorInput?.value || '';
                if (filterEffectiveDate) filterEffectiveDate.value = effectiveDateInput?.value || '';
                filterType?.dispatchEvent(new Event('change'));
            };

            const applyDrawerFilters = () => {
                dtState.status = filterStatus?.value || '';
                dtState.transitionType = filterType?.value || '';
                dtState.successor = filterSuccessor?.value || '';
                dtState.effectiveDate = filterEffectiveDate?.value || '';
                dtState.transitionTypeOther = dtState.transitionType === 'other' ? (filterTypeOther?.value ||
                    '') : '';
                syncToolbarInputs();
                updateFilterBadge();
                dtState.page = 1;
                dtFetch();
            };

            const clearDrawerFilters = () => {
                if (filterStatus) filterStatus.value = '';
                if (filterType) filterType.value = '';
                if (filterTypeOther) filterTypeOther.value = '';
                if (filterSuccessor) filterSuccessor.value = '';
                if (filterEffectiveDate) filterEffectiveDate.value = '';
                filterType?.dispatchEvent(new Event('change'));
            };

            if (pageMode === 'index') {
                const tableBody = document.getElementById('dtTableBody');
                const gridBody = document.getElementById('dtGridBody');
                const initialTableEmptyState = document.getElementById('dtTableEmptyState');
                const initialGridEmptyState = document.getElementById('dtGridEmptyState');
                const initialSearchValue = dtState.search;

                const escapeHtml = value => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                syncToolbarInputs = () => {
                    if (searchInput) searchInput.value = dtState.search;
                    if (statusInput) statusInput.value = dtState.status;
                    if (typeInput) typeInput.value = dtState.transitionType;
                    if (typeOtherInput) typeOtherInput.value = dtState.transitionTypeOther;
                    if (successorInput) successorInput.value = dtState.successor;
                    if (effectiveDateInput) effectiveDateInput.value = dtState.effectiveDate;
                    if (perPageInput) perPageInput.value = String(dtState.perPage);
                    if (pageInput) pageInput.value = dtState.page > 1 ? String(dtState.page) : '';
                };

                const renderEmptyState = host => {
                    if (!host) {
                        return;
                    }

                    if (dtState.search) {
                        window.EmptyState?.renderSearch({
                            host,
                            input: searchInput,
                            query: dtState.search,
                            message: 'Try a different dentist or successor name.',
                        });

                        return;
                    }

                    window.EmptyState?.render({
                        host,
                        icon: 'fa-users-gear',
                        title: 'No transition records found',
                        message: 'Transition plans matching the selected filters will appear here.',
                    });
                };

                const renderInitialEmptyState = host => {
                    renderEmptyState(host);
                };

                const renderTableEmptyState = () => {
                    if (!tableBody) {
                        return;
                    }

                    tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="table-empty-state-cell">
                        <div id="dtTableEmptyState" class="empty-state-host"></div>
                    </td>
                </tr>
            `;

                    renderEmptyState(document.getElementById('dtTableEmptyState'));
                };

                const renderGridEmptyState = () => {
                    if (!gridBody) {
                        return;
                    }

                    gridBody.innerHTML =
                        '<div id="dtGridEmptyState" class="empty-state-host table-grid-empty"></div>';
                    renderEmptyState(document.getElementById('dtGridEmptyState'));
                };

                const renderCounts = stats => {
                    document.getElementById('dtCountTotal')?.replaceChildren(document.createTextNode(String(
                        stats?.total ?? 0)));
                    document.getElementById('dtCountActive')?.replaceChildren(document.createTextNode(String(
                        stats?.active ?? 0)));
                    document.getElementById('dtCountCompleted')?.replaceChildren(document.createTextNode(String(
                        stats?.completed ?? 0)));
                    document.getElementById('dtCountBadge')?.replaceChildren(document.createTextNode(String(
                        stats?.total ?? 0)));
                };

                const renderRows = transitions => {
                    if (!tableBody || !gridBody) {
                        return;
                    }

                    if (!Array.isArray(transitions) || transitions.length === 0) {
                        renderTableEmptyState();
                        renderGridEmptyState();
                        return;
                    }

                    tableBody.innerHTML = transitions.map(transition => `
                <tr class="dt-table-row">
                    <td class="table-cell-main">
                        <div class="dt-cell-title">${escapeHtml(transition.dentist_name)}</div>
                        <div class="dt-cell-sub">${escapeHtml(transition.dentist_email)}</div>
                    </td>
                    <td><span class="dt-inline-value dt-inline-value-sm">${escapeHtml(transition.successor_name)}</span></td>
                   <td class="table-cell-center">
    <span class="dt-inline-value dt-inline-value-sm">
        ${escapeHtml(transition.last_working_date || 'Not set')}
    </span>
</td>
                    <td class="table-cell-center">
                        <span class="dt-badge dt-badge-${escapeHtml(transition.status)}">${escapeHtml(transition.status_label)}</span>
                    </td>
                    <td class="table-cell-center table-action-cell">
                        <div class="ui-action-group dt-table-actions">
                            ${transition.edit_url ? `
                                                                                                            <a href="${escapeHtml(transition.edit_url)}" class="ui-action-btn ui-action-edit" data-tooltip="Edit transition" aria-label="Edit transition">
                                                                                                                <i class="fa-solid fa-pen"></i>
                                                                                                            </a>
                                                                                                        ` : ''}
                            <a href="${escapeHtml(transition.show_url)}" class="ui-action-btn ui-action-view" data-tooltip="View transition" aria-label="View transition">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            `).join('');

                    gridBody.innerHTML = transitions.map((transition, index) => `
                <article class="table-record-card dt-grid-card">
                    <div class="dt-grid-top">
                        <div class="dt-grid-number">#${index + 1}</div>
                        <span class="dt-badge dt-badge-${escapeHtml(transition.status)}">${escapeHtml(transition.status_label)}</span>
                    </div>

                    <div class="dt-grid-card-head">
                        <div class="dt-grid-identity">
                            <strong>${escapeHtml(transition.dentist_name)}</strong>
                            <span>${escapeHtml(transition.dentist_email)}</span>
                        </div>
                    </div>

                    <div class="dt-grid-meta">
                        <div class="dt-grid-field">
                            <div class="dt-grid-label">Transition Type</div>
                            <div class="dt-grid-value">${escapeHtml(transition.transition_type_label)}</div>
                        </div>
                        <div class="dt-grid-field">
                            <div class="dt-grid-label">Last Working Date</div>
                            <div class="dt-grid-value">${escapeHtml(transition.last_working_date || '')}</div>
                        </div>
                        <div class="dt-grid-field">
                            <div class="dt-grid-label">Access Expiration</div>
                            <div class="dt-grid-value">${escapeHtml(transition.access_expiration || '')}</div>
                        </div>
                        <div class="dt-grid-field">
                            <div class="dt-grid-label">Default Successor</div>
                            <div class="dt-grid-value">${escapeHtml(transition.successor_name)}</div>
                        </div>
                    </div>

                    <div class="dt-grid-progress">
                        <div class="dt-progress">
                            <div class="dt-progress-bar" style="width: ${Number(transition.progress_percentage || 0)}%;"></div>
                        </div>
                        <span>${Number(transition.progress_percentage || 0)}% complete</span>
                    </div>

                    <div class="ui-action-group dt-grid-actions">
                        ${transition.edit_url ? `
                                                                                                        <a href="${escapeHtml(transition.edit_url)}" class="ui-action-btn ui-action-edit" data-tooltip="Edit transition" aria-label="Edit transition">
                                                                                                            <i class="fa-solid fa-pen"></i>
                                                                                                        </a>
                                                                                                    ` : ''}
                        <a href="${escapeHtml(transition.show_url)}" class="ui-action-btn ui-action-view" data-tooltip="View transition" aria-label="View transition">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </div>
                </article>
            `).join('');
                };

                const renderPagebar = pagination => {
                    if (!pagination) {
                        return;
                    }

                    window.renderGlobalPagination?.({
                        currentPage: Number(pagination.current_page || 1),
                        lastPage: Math.max(1, Number(pagination.last_page || 1)),
                        total: Number(pagination.total || 0),
                        from: Number(pagination.from || 0),
                        to: Number(pagination.to || 0),
                        containers: [
                            document.getElementById('dtPaginationTop'),
                            document.getElementById('dtPaginationBottom'),
                        ].filter(Boolean),
                        infoElements: [
                            document.getElementById('dtPaginationInfoTop'),
                            document.getElementById('dtPaginationInfoBottom'),
                        ].filter(Boolean),
                        bars: [
                            document.getElementById('dtPaginationTopBar'),
                            document.getElementById('dtPaginationBottomBar'),
                        ].filter(Boolean),
                        itemLabel: 'transitions',
                        onPageChange(page) {
                            dtState.page = page;
                            syncToolbarInputs();
                            dtFetch();
                        },
                    });

                    const perPageSelect = document.getElementById('dtPerPageSelect');

                    if (perPageSelect && pagination.per_page) {
                        perPageSelect.value = String(pagination.per_page);
                        window.syncGlobalPageSizeSelect?.(perPageSelect, pagination.per_page);
                    }
                };

                dtFetch = silent => {
                    if (dtController) {
                        dtController.abort();
                    }

                    dtController = new AbortController();
                    syncToolbarInputs();

                    const params = new URLSearchParams({
                        search: dtState.search,
                        status: dtState.status,
                        transition_type: dtState.transitionType,
                        transition_type_other: dtState.transitionTypeOther,
                        successor: dtState.successor,
                        effective_date: dtState.effectiveDate,
                        per_page: String(dtState.perPage),
                        page: String(dtState.page),
                    });

                    history.replaceState(null, '', window.location.pathname + '?' + params.toString());

                    fetch('{{ route($routeNames['index']) }}?' + params.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            signal: dtController.signal,
                        })
                        .then(async res => {
                            if (!res.ok) {
                                throw new Error(`Dentist continuity request failed: ${res.status}`);
                            }

                            return res.json();
                        })
                        .then(data => {
                            renderRows(data.transitions || []);
                            renderPagebar(data.pagination || null);
                            renderCounts(data.stats || null);
                        })
                        .catch(error => {
                            if (error.name !== 'AbortError') {
                                console.error(error);
                            }
                        });
                };

                renderInitialEmptyState(initialTableEmptyState);
                renderInitialEmptyState(initialGridEmptyState);

                filterBtn?.addEventListener('click', event => {
                    event.preventDefault();
                    syncDrawerFromToolbar();
                    window.openFilterDrawer?.(filterModalId);
                });

                document.getElementById('dtCloseFilterModalBtn')?.addEventListener('click', () => {
                    window.closeFilterDrawer?.(filterModalId);
                });

                document.getElementById('dtCancelFilterBtn')?.addEventListener('click', () => {
                    window.closeFilterDrawer?.(filterModalId);
                });

                document.getElementById('dtApplyFilters')?.addEventListener('click', () => {
                    applyDrawerFilters();
                    window.closeFilterDrawer?.(filterModalId);
                });

                document.getElementById('dtClearFiltersModal')?.addEventListener('click', () => {
                    clearDrawerFilters();
                });

                window.handleDentistContinuityPerPageChange = value => {
                    dtState.perPage = [10, 20, 50, 100].includes(Number(value)) ? Number(value) : 10;
                    dtState.page = 1;
                    syncToolbarInputs();
                    dtFetch();
                };

                window.handleDentistContinuitySearch = value => {
                    dtState.search = String(value || '').trim();
                    dtState.page = 1;
                    syncToolbarInputs();
                    dtFetch(true);
                };

                window.renderGlobalPagination?.({
                    currentPage: {{ $initialPage }},
                    lastPage: {{ $initialLastPage }},
                    total: {{ $initialTotal }},
                    from: {{ $initialFrom }},
                    to: {{ $initialTo }},
                    containers: [
                        document.getElementById('dtPaginationTop'),
                        document.getElementById('dtPaginationBottom'),
                    ].filter(Boolean),
                    infoElements: [
                        document.getElementById('dtPaginationInfoTop'),
                        document.getElementById('dtPaginationInfoBottom'),
                    ].filter(Boolean),
                    bars: [
                        document.getElementById('dtPaginationTopBar'),
                        document.getElementById('dtPaginationBottomBar'),
                    ].filter(Boolean),
                    itemLabel: 'transitions',
                    onPageChange(page) {
                        dtState.page = page;
                        syncToolbarInputs();
                        dtFetch();
                    },
                });

                syncToolbarInputs();
                window.initGlobalPageSizeSelects?.();
                updateFilterBadge();
            }
        });
    </script>
@endpush
