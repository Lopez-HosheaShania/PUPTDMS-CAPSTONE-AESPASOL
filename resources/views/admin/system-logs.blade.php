@extends('layouts.app')

@section('layout-role', $layoutRole ?? 'admin')

@section('title', 'System Logs')

@section('styles')
    @vite('resources/css/pages/admin/system-logs.css')
@endsection

@section('content')

    @php
        $logs = $logs ?? collect([]);
        $perPage = $perPage ?? 10;
        $status = $status ?? 'active';
        $authUser = auth()->user();
        $activeRole = session('impersonated_role') ?: session('role') ?: $authUser?->role?->slug;
        $isSuperAdmin = $authUser?->role?->slug === 'super_admin' && $activeRole === 'super_admin';
        $canViewSystemLogs =
            $isSuperAdmin ||
            ($authUser?->hasAnyPermission(['view_system_logs', 'export_system_logs', 'archive_system_logs']) ?? false);
        $canExportSystemLogs = $isSuperAdmin || ($authUser?->hasPermission('export_system_logs') ?? false);
        $canArchiveSystemLogs = $isSuperAdmin || ($authUser?->hasPermission('archive_system_logs') ?? false);
    @endphp

    <main id="mainContent" class="app-page-shell system-logs-page page-enter mode-list">
        <div class="w-full">

            <x-page-banner title="System Logs" class="rounded-2xl mb-6" />

            <div id="statCards" class="stat-grid">
                <x-stat-card label="Total Logs" :value="$totalCount" tone="crimson" icon="fa-solid fa-clipboard-list"
                    footer="All recorded activity" value-class="stat-num" :value-attributes="['id' => 'statTotal']" />

                <x-stat-card label="Admin Actions" :value="$adminCount" tone="red" icon="fa-solid fa-user-tie"
                    footer="Administrator activity" value-class="stat-num" :value-attributes="['id' => 'statAdmin']" />

                <x-stat-card label="Dentist Actions" :value="$dentistCount" tone="blue" icon="fa-solid fa-user-doctor"
                    footer="Dentist activity" value-class="stat-num" :value-attributes="['id' => 'statDentist']" />

                <x-stat-card label="Patient Actions" :value="$patientCount" tone="green" icon="fa-solid fa-user"
                    footer="Patient activity" value-class="stat-num" :value-attributes="['id' => 'statPatient']" />
            </div>

            <div class="card">
                <x-card-header title="Audit Trail" icon="fa-solid fa-clipboard-list" actions-class="system-logs-toolbar">
                    <x-slot:actions>

                        <div class="table-toolbar-search">
                            <div class="global-voice-row">

                                <x-search-bar id="slSearch" name="search" placeholder="Search logs…" :value="$search ?? ''"
                                    callback="handleSystemLogsSearch" :debounce="400" />

                                <x-voice-input target="#slSearch" status-id="slSearchVoiceStatus"
                                    label="Voice search system logs" title="Voice search" />

                            </div>
                        </div>

                        <div class="table-toolbar-actions">

                            <x-filter-select id="slStatusSelect" label="Status" :value="$status ?? 'all'"
                                callback="handleSystemLogsStatusChange" icon="fa-wave-square" menu-align="right" searchable
                                multiple search-placeholder="Search status..." :options="[
                                    [
                                        'value' => 'all',
                                        'label' => 'All Logs',
                                        'icon' => 'fa-layer-group',
                                        'tone' => 's-all',
                                        'count' => ($activeCount ?? 0) + ($archivedCount ?? 0),
                                    ],
                                    [
                                        'value' => 'active',
                                        'label' => 'Active Logs',
                                        'icon' => 'fa-wave-square',
                                        'tone' => 's-active',
                                        'count' => $activeCount ?? 0,
                                    ],
                                    [
                                        'value' => 'archived',
                                        'label' => 'Archived Logs',
                                        'icon' => 'fa-box-archive',
                                        'tone' => 's-archived',
                                        'count' => $archivedCount ?? 0,
                                    ],
                                ]" />

                            <x-filter-select id="slRoleSelect" label="Role" :value="$role ?? 'all'"
                                callback="handleSystemLogsRoleChange" icon="fa-users" menu-align="right" searchable multiple
                                search-placeholder="Search role..." :options="[
                                    [
                                        'value' => 'all',
                                        'label' => 'All Roles',
                                        'icon' => 'fa-users',
                                        'tone' => 's-all',
                                        'count' => $totalCount ?? 0,
                                    ],
                                    [
                                        'value' => 'admin',
                                        'label' => 'Admin',
                                        'icon' => 'fa-user-tie',
                                        'tone' => 'role-admin',
                                        'count' => $adminCount ?? 0,
                                    ],
                                    [
                                        'value' => 'dentist',
                                        'label' => 'Dentist',
                                        'icon' => 'fa-user-doctor',
                                        'tone' => 'role-dentist',
                                        'count' => $dentistCount ?? 0,
                                    ],
                                    [
                                        'value' => 'patient',
                                        'label' => 'Patient',
                                        'icon' => 'fa-user',
                                        'tone' => 'role-patient',
                                        'count' => $patientCount ?? 0,
                                    ],
                                ]" />

                            <button type="button" id="slFilterBtn" class="global-filter-btn"
                                data-ui-click="action-23adcae5c050" aria-pressed="false">
                                <i class="fa-solid fa-sliders"></i>
                                <span>Filter</span>
                                <span id="slFilterBadge" class="filter-badge hidden"></span>
                            </button>

                            <button id="slExternalClearFilterBtn" type="button" data-ui-click="action-2f4e0d8e81f5"
                                class="global-filter-reset-btn hidden" title="Reset filters" aria-label="Reset filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>

                            <div id="slOverflowMenu" class="sl-overflow-menu">
                                <button type="button" id="slOverflowTrigger" class="ui-action-btn"
                                    aria-label="More actions" aria-haspopup="menu" aria-expanded="false"
                                    data-tooltip="More actions" data-tooltip-tone="neutral"
                                    data-ui-click="action-d8c154b64c66">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div id="slOverflowPanel" class="sl-overflow-panel" role="menu"
                                    aria-label="System log actions">
                                    <button type="button" id="slArchiveBtn" class="sl-overflow-item" role="menuitem"
                                        data-ui-click="action-478b7a7c7da5">
                                        <span class="sl-overflow-item-icon ui-action-warning">
                                            <i class="fa-solid fa-box-archive"></i>
                                        </span>

                                        <span class="sl-overflow-item-copy">
                                            <strong>Archive Old Logs</strong>
                                            <small>Move older active logs to archive</small>
                                        </span>
                                    </button>

                                    <button type="button" id="slExportBtn" class="sl-overflow-item" role="menuitem"
                                        data-ui-click="action-9294a71d2860">
                                        <span class="sl-overflow-item-icon ui-action-view">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </span>

                                        <span class="sl-overflow-item-copy">
                                            <strong>Export PDF</strong>
                                            <small>Generate a filtered system log report</small>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <x-view-toggle id="slViewToggle" storage-key="systemLogsView" list-view="#slListView"
                                grid-view="#slGridView" />

                        </div>
                    </x-slot:actions>
                </x-card-header>

                <x-pagination-bar id="systemLogsPaginationTopBar" info-id="systemLogsPageInfoTop"
                    pagination-id="systemLogsPaginationTop" position="top" :show-entries="true"
                    page-size-id="perPageSelect" page-size-callback="handleSystemLogsPerPageChange" :page-size-value="$perPage"
                    page-size-label="per page" label="entries" :total="$logs->total()" :from="$logs->firstItem() ?? 0" :to="$logs->lastItem() ?? 0" />

                <div class="table-list-view" id="slListView">
                    <div class="table-scroll">
                        <table class="data-table" id="slTable">
                            <thead>
                                <tr>
                                    <th class="sl-col-id">ID</th>
                                    <th class="sl-col-timestamp">Timestamp</th>
                                    <th class="sl-col-role">Role</th>
                                    <th class="sl-col-user">User</th>
                                    <th class="sl-col-action">Action</th>
                                    <th class="sl-col-module">Module</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody id="slTableBody">
                                @forelse($logs as $log)
                                    @php
                                        $logRole = strtolower($log->actor_role ?? 'other');
                                        $action = strtolower($log->action ?? '');
                                        $moduleName = strtolower($log->module ?? '');
                                        $actionClass = match (true) {
                                            str_contains($action, 'error') ||
                                                str_contains($action, 'failed') ||
                                                str_contains($action, 'exception')
                                                => 'error',

                                            str_contains($action, 'login') => 'login',
                                            str_contains($action, 'logout') => 'logout',
                                            str_contains($action, 'view') => 'view',
                                            str_contains($action, 'create') => 'create',
                                            str_contains($action, 'update') => 'update',
                                            str_contains($action, 'delete') => 'delete',
                                            str_contains($action, 'book') => 'book',
                                            str_contains($action, 'archive') => 'archive',
                                            str_contains($action, 'export') => 'export',
                                            default => 'default',
                                        };

                                        $actionIcon = match ($actionClass) {
                                            'login' => 'fa-right-to-bracket',
                                            'logout' => 'fa-right-from-bracket',
                                            'view' => 'fa-eye',
                                            'create' => 'fa-plus',
                                            'update' => 'fa-pen',
                                            'delete' => 'fa-trash',
                                            'error' => 'fa-triangle-exclamation',
                                            'book' => 'fa-calendar-plus',
                                            'archive' => 'fa-box-archive',
                                            'export' => 'fa-file-export',
                                            default => 'fa-bolt',
                                        };
                                        $roleIcon = match ($logRole) {
                                            'admin' => 'fa-user-tie',
                                            'dentist' => 'fa-user-doctor',
                                            'patient' => 'fa-user',
                                            default => 'fa-circle-user',
                                        };
                                        $avatarLetter = strtoupper(substr($log->actor_name ?? $logRole, 0, 1));
                                        $roleBadgeClass = match ($logRole) {
                                            'admin' => 'role-admin',
                                            'dentist' => 'role-dentist',
                                            'patient' => 'role-patient',
                                            default => 'role-none',
                                        };
                                        $actionStatusClass = match ($actionClass) {
                                            'login' => 'status-active',
                                            'logout' => 'status-ended',
                                            'view' => 'status-all',
                                            'create' => 'status-approved',
                                            'update' => 'status-rescheduled',
                                            'delete' => 'status-cancelled',
                                            'error' => 'status-failed',
                                            'book' => 'status-upcoming',
                                            'archive' => 'status-archived',
                                            'export' => 'status-released',
                                            default => 'status-default',
                                        };
                                        $actionLabel = match ($actionClass) {
                                            'login' => 'Login',
                                            'logout' => 'Logout',
                                            'view' => 'View',
                                            'create' => 'Create',
                                            'update' => 'Update',
                                            'delete' => 'Delete',
                                            'error' => 'Error',
                                            'book' => 'Book',
                                            'archive' => 'Archive',
                                            'export' => 'Export',
                                            default => ucwords(str_replace('_', ' ', $log->action)),
                                        };
                                    @endphp
                                    <tr data-live-item-id="{{ $log->id }}" data-role="{{ $logRole }}"
                                        data-action="{{ $actionClass }}">
                                        <td><span class="sl-id">#{{ str_pad($log->id, 3, '0', STR_PAD_LEFT) }}</span>
                                        </td>
                                        <td>
                                            <span class="sl-date-day">{{ $log->created_at->format('M j, Y') }}</span>
                                            <span class="sl-date-time">{{ $log->created_at->format('h:i:s A') }}</span>
                                        </td>
                                        <td><span class="badge-role {{ $roleBadgeClass }}">
                                                <i class="fa-solid {{ $roleIcon }}"></i>
                                                {{ ucfirst($logRole) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-primary">

                                                <span class="patient-avatar patient-avatar-sm" data-patient-avatar
                                                    data-patient-name="{{ $log->actor_name ?? 'Unknown User' }}"></span>

                                                <span class="sl-username">
                                                    {{ $log->actor_name ?? 'Unknown User' }}
                                                </span>

                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $actionStatusClass }}">
                                                <i
                                                    class="fa-solid {{ $actionIcon }}
        {{ $actionClass === 'error' ? 'sl-action-alert' : '' }}"></i>

                                                {{ $actionLabel }}
                                            </span>
                                            @if ($log->is_archived)
                                                <span class="status-pill s-archived"
                                                    title="Archived {{ optional($log->archived_at)->format('M j, Y h:i A') }}">
                                                    <span class="status-dot"></span>
                                                    <i class="fa-solid fa-box-archive"></i>
                                                    Archived
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="table-tag table-tag-neutral">
                                                <i class="fa-solid fa-cube"></i>

                                                {{ ucfirst(str_replace('_', ' ', $log->module)) }}
                                            </span>
                                        </td>
                                        @php
                                            $description = trim((string) ($log->description ?? ''));

                                            $fullDescription = trim((string) ($log->display_details ?? ''));

                                            $hasMeaningfulDetails =
                                                $fullDescription !== '' && $fullDescription !== $description;

                                            $displayDescription = trim(
                                                (string) ($log->display_description ?? $description ?:
                                                'No description provided.'),
                                            );

                                            $tableDetailId = 'sl-table-detail-' . $log->id;

                                            $tableCopyId = 'sl-table-full-description-' . $log->id;
                                        @endphp

                                        <td>
                                            <div class="global-show-more-disclosure" data-show-more
                                                data-show-more-mode="disclosure" data-log-show-more
                                                data-log-id="{{ $log->id }}">
                                                <span class="sl-desc global-text-clamp-2" data-show-more-summary>
                                                    {{ $displayDescription }}
                                                </span>

                                                @if ($hasMeaningfulDetails)
                                                    <x-show-more variant="disclosure" :target="$tableDetailId" />
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    @if ($hasMeaningfulDetails)
                                        <tr id="{{ $tableDetailId }}" class="sl-log-detail-row" data-show-more-panel
                                            aria-hidden="true" hidden>
                                            <td colspan="7" class="sl-log-detail-cell">
                                                <div
                                                    class="global-info-item global-info-item-wide slide-down-fade-in
                                                            sl-log-detail-box">
                                                    <div class="global-info-copy">
                                                        <span class="global-info-label">
                                                            Full Description
                                                        </span>

                                                        <span id="{{ $tableCopyId }}"
                                                            class="global-info-value sl-log-detail-value">
                                                            {{ $fullDescription }}
                                                        </span>
                                                    </div>

                                                    <x-button type="button" variant="secondary" size="sm"
                                                        icon="fa-regular fa-copy"
                                                        data-log-copy-target="{{ $tableCopyId }}">
                                                        Copy
                                                    </x-button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-grid-view" id="slGridView" hidden>
                    <div class="table-record-grid" id="slGridBody">
                        @forelse($logs as $log)
                            @php
                                $logRole = strtolower($log->actor_role ?? 'other');
                                $action = strtolower($log->action ?? '');
                                $moduleName = strtolower($log->module ?? '');
                                $actionClass = match (true) {
                                    str_contains($action, 'error') ||
                                        str_contains($action, 'failed') ||
                                        str_contains($action, 'exception')
                                        => 'error',

                                    str_contains($action, 'login') => 'login',
                                    str_contains($action, 'logout') => 'logout',
                                    str_contains($action, 'view') => 'view',
                                    str_contains($action, 'create') => 'create',
                                    str_contains($action, 'update') => 'update',
                                    str_contains($action, 'delete') => 'delete',
                                    str_contains($action, 'book') => 'book',
                                    str_contains($action, 'archive') => 'archive',
                                    str_contains($action, 'export') => 'export',
                                    default => 'default',
                                };
                                $actionIcon = match ($actionClass) {
                                    'login' => 'fa-right-to-bracket',
                                    'logout' => 'fa-right-from-bracket',
                                    'view' => 'fa-eye',
                                    'create' => 'fa-plus',
                                    'update' => 'fa-pen',
                                    'delete' => 'fa-trash',
                                    'error' => 'fa-triangle-exclamation',
                                    'book' => 'fa-calendar-plus',
                                    'archive' => 'fa-box-archive',
                                    'export' => 'fa-file-export',
                                    default => 'fa-bolt',
                                };
                                $roleIcon = match ($logRole) {
                                    'admin' => 'fa-user-tie',
                                    'dentist' => 'fa-user-doctor',
                                    'patient' => 'fa-user',
                                    default => 'fa-circle-user',
                                };
                                $avatarLetter = strtoupper(substr($log->actor_name ?? $logRole, 0, 1));
                                $roleBadgeClass = match ($logRole) {
                                    'admin' => 'role-admin',
                                    'dentist' => 'role-dentist',
                                    'patient' => 'role-patient',
                                    default => 'role-none',
                                };
                                $actionStatusClass = match ($actionClass) {
                                    'login' => 'status-active',
                                    'logout' => 'status-ended',
                                    'view' => 'status-all',
                                    'create' => 'status-approved',
                                    'update' => 'status-rescheduled',
                                    'delete' => 'status-cancelled',
                                    'error' => 'status-failed',
                                    'book' => 'status-upcoming',
                                    'archive' => 'status-archived',
                                    'export' => 'status-released',
                                    default => 'status-default',
                                };
                                $actionLabel = match ($actionClass) {
                                    'login' => 'Login',
                                    'logout' => 'Logout',
                                    'view' => 'View',
                                    'create' => 'Create',
                                    'update' => 'Update',
                                    'delete' => 'Delete',
                                    'error' => 'Error',
                                    'book' => 'Book',
                                    'archive' => 'Archive',
                                    'export' => 'Export',
                                    default => ucwords(str_replace('_', ' ', $log->action)),
                                };
                            @endphp
                            <article class="table-record-card" data-live-item-id="{{ $log->id }}"
                                data-role="{{ $logRole }}" data-action="{{ $actionClass }}">
                                <div class="table-record-card-layout">
                                    <div class="table-record-content">

                                        <div class="table-record-header">
                                            <div class="table-primary">
                                                <strong>
                                                    <span class="sl-id">
                                                        #{{ str_pad($log->id, 3, '0', STR_PAD_LEFT) }}
                                                    </span>
                                                </strong>
                                            </div>

                                            <span class="status-pill {{ $actionStatusClass }}">

                                                <i
                                                    class="fa-solid {{ $actionIcon }}
                                                    {{ $actionClass === 'error' ? 'sl-action-alert' : '' }}">
                                                </i>

                                                {{ $actionLabel }}
                                            </span>
                                        </div>

                                        <div class="table-primary">
                                            <span class="patient-avatar patient-avatar-sm" data-patient-avatar
                                                data-patient-name="{{ $log->actor_name ?? 'Unknown User' }}"></span>

                                            <span class="sl-username">
                                                {{ $log->actor_name ?? 'Unknown User' }}
                                            </span>
                                        </div>

                                        <div class="table-record-meta">

                                            <div class="table-record-row">
                                                <span class="table-record-label">
                                                    Timestamp
                                                </span>

                                                <span class="table-record-value">
                                                    {{ $log->created_at->format('M j, Y') }}
                                                    ·
                                                    {{ $log->created_at->format('h:i:s A') }}
                                                </span>
                                            </div>

                                            <div class="table-record-row">
                                                <span class="table-record-label">
                                                    Role
                                                </span>

                                                <span class="table-record-value">
                                                    <span class="badge-role {{ $roleBadgeClass }}">
                                                        <i class="fa-solid {{ $roleIcon }}"></i>
                                                        {{ ucfirst($logRole) }}
                                                    </span>
                                                </span>
                                            </div>

                                            <div class="table-record-row">
                                                <span class="table-record-label">
                                                    Module
                                                </span>

                                                <span class="table-record-value">
                                                    <span class="table-tag table-tag-neutral">
                                                        <i class="fa-solid fa-cube"></i>
                                                        {{ ucfirst(str_replace('_', ' ', $log->module)) }}
                                                    </span>
                                                </span>
                                            </div>

                                            <div class="table-record-row" data-system-log-description-row>
                                                <span class="table-record-label">
                                                    Description
                                                </span>

                                                @php
                                                    $description = trim(
                                                        (string) ($log->description ?? 'No description provided.'),
                                                    );

                                                    $fullDescription = trim((string) ($log->display_details ?? ''));

                                                    $displayDescription = trim(
                                                        (string) ($log->display_description ?? $description),
                                                    );

                                                    $hasExpandableDescription =
                                                        $fullDescription !== '' && $fullDescription !== $description;

                                                    $gridDetailId = 'sl-grid-detail-' . $log->id;
                                                @endphp

                                                <div class="table-record-value">
                                                    <div class="global-show-more-disclosure" data-show-more
                                                        data-show-more-mode="disclosure" data-log-show-more
                                                        data-log-id="{{ $log->id }}">
                                                        <span class="sl-desc global-text-clamp-2" data-show-more-summary>
                                                            {{ $displayDescription }}
                                                        </span>

                                                        @if ($hasExpandableDescription)
                                                            <div id="{{ $gridDetailId }}" class="sl-grid-log-detail"
                                                                data-show-more-panel aria-hidden="true" hidden>
                                                                <span class="sl-desc">
                                                                    {{ $fullDescription }}
                                                                </span>
                                                            </div>

                                                            <x-show-more variant="disclosure" :target="$gridDetailId" />
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        @if ($log->is_archived)
                                            <span class="status-pill s-archived"
                                                title="Archived {{ optional($log->archived_at)->format('M j, Y h:i A') }}">
                                                <i class="fa-solid fa-box-archive"></i>
                                                Archived
                                            </span>
                                        @endif

                                    </div>
                                </div>
                            </article>
                        @empty
                        @endforelse
                    </div>
                </div>

                <div id="emptyState" class="empty-state-host"></div>

                <x-pagination-bar id="systemLogsPaginationBottomBar" info-id="systemLogsPageInfoBottom"
                    pagination-id="systemLogsPaginationBottom" position="bottom" :page-size-value="$perPage" label="entries"
                    :total="$logs->total()" :from="$logs->firstItem() ?? 0" :to="$logs->lastItem() ?? 0" />

            </div>
        </div>
    </main>

    <x-filter-drawer id="filterModal" title="Filters" close-callback="action-463079bd5291"
        clear-id="slClearFilterPanelBtn" clear-callback="action-1e0663440b55" clear-label="Clear Filters"
        cancel-id="filterCloseBtn" cancel-callback="action-463079bd5291" cancel-label="Cancel" apply-id="filterApplyBtn"
        apply-callback="action-c059940c46c7" apply-label="Show 0 results" results-id="slShowResultsText">

        <div id="slActiveFiltersSection" class="filter-active-section hidden">

            <div class="filter-active-header">

                <span class="filter-active-title">
                    Active Filters
                </span>

                <x-button id="slClearAllChipsBtn" type="button" variant="secondary" size="sm"
                    icon="fa-solid fa-rotate-left" class="filter-clear-all">
                    Clear All
                </x-button>

            </div>

            <div id="slActiveChipsContainer" class="active-filters-container"></div>

        </div>

        <x-filter-group title="Sort By">

            <input type="hidden" id="slSortOrder" value="desc">

            <div id="slSortGroup" class="filter-chip-row">

                <button type="button" class="ftag" data-sl-sort="desc">
                    Newest First
                </button>

                <button type="button" class="ftag" data-sl-sort="asc">
                    Oldest First
                </button>

            </div>

        </x-filter-group>

        <x-filter-group title="Filter by Date Range">

            <input type="hidden" id="slDatePreset" value="">

            <div id="slDatePresetGroup" class="filter-chip-row">

                <button type="button" class="quick-date-chip" data-sl-date-preset="today"
                    data-ui-click="action-ae64a4dd44f5">
                    Today
                </button>

                <button type="button" class="quick-date-chip" data-sl-date-preset="week"
                    data-ui-click="action-c457a107a6f8">
                    Last 7 Days
                </button>

                <button type="button" class="quick-date-chip" data-sl-date-preset="month"
                    data-ui-click="action-673f2e1d1880">
                    Last 30 Days
                </button>

            </div>

        </x-filter-group>

        <x-filter-group title="Custom Date Range">

            <div class="filter-date-grid">

                <div class="filter-date-input-wrap">

                    <input type="text" id="slDateFrom" class="form-input-custom js-flatpickr-date-max-today"
                        placeholder="Start date" readonly autocomplete="off">

                </div>

                <div class="filter-date-input-wrap">

                    <input type="text" id="slDateTo" class="form-input-custom js-flatpickr-date-max-today"
                        placeholder="End date" readonly autocomplete="off">

                </div>

            </div>

        </x-filter-group>

        <x-filter-group title="Action Type">

            <x-select id="slActionType">
                <option value="">
                    All Actions
                </option>

                @foreach ($actionTypes as $value => $label)
                    <option value="{{ $value }}" @selected($actionType === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </x-select>

        </x-filter-group>

        <x-filter-group title="Module">
            <div class="global-control-wrap">
                <i class="fa-solid fa-cube global-control-icon"></i>

                <input type="text" id="slModuleFilter" class="form-input-custom global-form-icon"
                    placeholder="e.g. appointments">
            </div>
        </x-filter-group>

    </x-filter-drawer>

    <div id="slArchiveModal" class="ui-modal modal-theme-warning" aria-hidden="true">
        <div class="ui-modal-card modal-md" role="dialog" aria-modal="true" aria-labelledby="slArchiveModalTitle">

            <form id="slArchiveForm" class="modal-card-form" data-discard-form
                data-discard-title="Discard archive settings?" data-discard-subtitle="You have unsaved archive settings."
                data-discard-message="Closing this modal will reset the archive age you selected. Do you want to discard these changes?">

                <div class="modal-hd">
                    <div class="modal-heading">
                        <div class="modal-icon">
                            <i class="fa-solid fa-box-archive"></i>
                        </div>

                        <div class="modal-copy">
                            <h3 id="slArchiveModalTitle" class="modal-title">
                                Archive System Logs
                            </h3>

                            <p class="modal-subtitle">
                                Move older log records out of the active view.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="modal-x" data-discard-close="slArchiveModal"
                        aria-label="Close archive modal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="modal-bd">
                    <div class="modal-form-grid">

                        <div class="global-confirm-alert">
                            <i class="fa-solid fa-circle-info"></i>

                            <p>
                                Archive older active logs

                                <span>
                                    Only active logs older than the selected
                                    number of days will be archived. Archived
                                    records remain accessible and are not deleted.
                                </span>
                            </p>
                        </div>

                        <div class="global-form-group" data-global-field>
                            <label for="slArchiveDaysInput" class="global-form-label">
                                Archive logs older than
                            </label>

                            <div class="modal-inline-control">

                                <div class="modal-inline-main">

                                    <x-number-stepper id="slArchiveDaysInput" name="older_than_days" value="90"
                                        min="1" max="3650" step="1" inputmode="numeric" required
                                        decrease-label="Decrease archive age" increase-label="Increase archive age"
                                        data-field-label="Archive Age"
                                        data-required-message="Please enter the number of days."
                                        data-validation-rule="wholeNumber" />

                                </div>

                                <span class="modal-helper-text">
                                    days
                                </span>

                            </div>

                            <div id="slArchiveError" class="global-field-error" data-error-for="slArchiveDaysInput"
                                aria-live="polite" aria-hidden="true"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-ft">
                    <x-button type="button" variant="secondary" data-discard-close="slArchiveModal">
                        Cancel
                    </x-button>

                    <x-button id="slArchiveConfirmBtn" type="button" variant="warning" icon="fa-solid fa-box-archive"
                        data-ui-click="action-215d4806d34c">
                        Archive Logs
                    </x-button>
                </div>
            </form>
        </div>
    </div>

    <div id="slExportModal" class="ui-modal modal-theme-primary" aria-hidden="true">
        <div class="ui-modal-card modal-lg modal-report-like" role="dialog" aria-modal="true"
            aria-labelledby="slExportModalTitle">

            <form id="slExportForm" class="modal-card-form" data-global-validation data-discard-form
                data-discard-title="Discard export settings?" data-discard-subtitle="You have unsaved export settings."
                data-discard-message="Closing this modal will reset the export options you selected. Do you want to discard these changes?">

                <div class="modal-hd">

                    <div class="modal-heading">

                        <div class="modal-icon">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>

                        <div class="modal-copy">

                            <h3 id="slExportModalTitle" class="modal-title">
                                Export System Logs
                            </h3>

                            <p class="modal-subtitle">
                                Select which system logs you want to include in the PDF.
                            </p>

                        </div>
                    </div>

                    <button type="button" class="modal-x" data-discard-close="slExportModal"
                        aria-label="Close export modal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

                <div class="modal-bd">

                    <div class="modal-form-grid">

                        <div class="modal-field modal-field-full sl-export-filter-section">

                            <div class="report-date-range-heading">

                                <div>
                                    <div class="report-date-range-title">
                                        Filter Logs
                                    </div>

                                    <p class="report-date-range-subtitle">
                                        Select the role and action to include in the exported PDF.
                                    </p>
                                </div>

                            </div>

                            <div class="sl-export-filter-grid">

                                <div class="modal-field sl-export-filter-field" data-global-field>
                                    <label class="global-form-label" for="slExportRole">
                                        Role
                                        <span class="required-mark">*</span>
                                    </label>

                                    <x-select id="slExportRole" name="role" required data-field-label="Role"
                                        data-required-message="Please select a role.">
                                        <option value="" selected disabled>
                                            Select role
                                        </option>

                                        <option value="all">All Roles</option>
                                        <option value="admin">Admin</option>
                                        <option value="dentist">Dentist</option>
                                        <option value="patient">Patient</option>
                                    </x-select>

                                    <div class="global-field-error" data-error-for="slExportRole" aria-live="polite"
                                        aria-hidden="true"></div>
                                </div>

                                <div class="modal-field sl-export-filter-field" data-global-field>
                                    <label class="global-form-label" for="slExportAction">
                                        Action
                                        <span class="required-mark">*</span>
                                    </label>

                                    <x-select id="slExportAction" name="action_type" required data-field-label="Action"
                                        data-required-message="Please select an action.">
                                        <option value="" selected disabled>
                                            Select action
                                        </option>

                                        <option value="all">
                                            All Actions
                                        </option>

                                        @foreach ($actionTypes as $value => $label)
                                            <option value="{{ $value }}">
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </x-select>

                                    <div class="global-field-error" data-error-for="slExportAction" aria-live="polite"
                                        aria-hidden="true"></div>
                                </div>

                            </div>
                        </div>

                        <div class="modal-field modal-field-full report-date-range-section" data-global-field>
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

                                    <label class="global-form-label" for="slExportDateFrom">
                                        From

                                        <span class="required-mark">
                                            *
                                        </span>
                                    </label>

                                    <input id="slExportDateFrom" name="date_from" type="text"
                                        class="form-input-custom js-flatpickr-date-max-today"
                                        placeholder="Select start date" data-field-label="From Date"
                                        data-required-message="Please select a start date."
                                        data-validation-rule="notFutureDate" readonly autocomplete="off" required>

                                    <div class="global-field-error" data-error-for="slExportDateFrom" aria-live="polite"
                                        aria-hidden="true"></div>
                                </div>

                                <div class="modal-field" data-global-field>

                                    <label class="global-form-label" for="slExportDateTo">
                                        To

                                        <span class="modal-helper-text">
                                            (optional)
                                        </span>
                                    </label>

                                    <input id="slExportDateTo" name="date_to" type="text"
                                        class="form-input-custom js-flatpickr-date-max-today"
                                        placeholder="Select end date" data-field-label="To Date"
                                        data-validation-rule="notFutureDate" readonly autocomplete="off">

                                    <div class="global-field-error" data-error-for="slExportDateTo" aria-live="polite"
                                        aria-hidden="true"></div>
                                </div>

                            </div>

                            <p class="report-date-range-helper">
                                <i class="fa-solid fa-circle-info"></i>

                                Leave "To" empty to export a single date.
                            </p>

                        </div>

                    </div>
                </div>

                <div class="modal-ft">
                    <x-button type="button" variant="secondary" data-discard-close="slExportModal">
                        Cancel
                    </x-button>

                    <x-button id="slExportConfirmBtn" type="button" variant="primary" icon="fa-solid fa-file-pdf"
                        data-ui-click="action-6a1c2afb8e04">
                        Export PDF
                    </x-button>
                </div>
            </form>
        </div>
    </div>

    <div id="slDetailsModal" class="ui-modal modal-theme-primary" aria-hidden="true">
        <div class="ui-modal-card modal-lg" role="dialog" aria-modal="true" aria-labelledby="slDetailsModalTitle">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 id="slDetailsModalTitle" class="modal-title">
                            Log Details
                        </h3>

                        <p class="modal-subtitle">
                            Additional information recorded
                            for this activity.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-modal-close="slDetailsModal" aria-label="Close log details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="global-confirm-alert">
                    <i class="fa-solid fa-circle-info"></i>

                    <p>
                        <strong id="slDetailsSummary">
                            Activity details
                        </strong>

                        <span id="slDetailsMeta"></span>
                    </p>
                </div>

                <div id="slDetailsContent" class="global-info-grid"></div>
            </div>

            <div class="modal-ft">
                <x-button type="button" variant="secondary" data-modal-close="slDetailsModal">
                    Close
                </x-button>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        var slArchiveDaysInput = null;
        var slArchiveError = null;
        var slArchiveConfirmBtn = null;
        var slExportDateFromInput = null;
        var slExportDateToInput = null;
        var slExportRoleInput = null;
        var slExportActionInput = null;
        var slExportConfirmBtn = null;
        var perPageSelect = null;
        const CAN_VIEW_SYSTEM_LOGS = @json($canViewSystemLogs);
        const CAN_EXPORT_SYSTEM_LOGS = @json($canExportSystemLogs);
        const CAN_ARCHIVE_SYSTEM_LOGS = @json($canArchiveSystemLogs);

        const SYSTEM_LOGS_CHECK_URL =
            @json(route($routeNames['check'] ?? 'admin.system_logs.check'));

        const SYSTEM_LOGS_FETCH_URL =
            @json(route($routeNames['fetch'] ?? 'admin.system_logs.fetch'));

        function getSystemLogsLiveParams() {
            return new URLSearchParams({
                role: slState.role ||
                    'all',

                search: slState.search ||
                    '',

                status: slState.status ||
                    'active',

                date_from: slState.dateFrom ||
                    '',

                date_to: slState.dateTo ||
                    '',

                action_type: slState.actionType ||
                    '',

                module: slState.module ||
                    '',
            });
        }

        function getSystemLogsLiveCheckUrl(
            context = {}
        ) {
            const params =
                getSystemLogsLiveParams();

            if (
                context.cursor !==
                null &&
                context.cursor !==
                undefined &&
                context.cursor !== ''
            ) {
                params.set(
                    'since_id',
                    String(
                        context.cursor
                    )
                );
            }

            if (
                slStatsCursor !==
                null &&
                slStatsCursor !==
                undefined
            ) {
                params.set(
                    'stats_since_id',
                    String(
                        slStatsCursor
                    )
                );
            }

            params.set('batch_limit', '50');

            return (
                SYSTEM_LOGS_CHECK_URL +
                '?' +
                params.toString()
            );
        }

        function getSystemLogsLiveFetchUrl(
            afterId,
            throughId
        ) {
            const params =
                getSystemLogsLiveParams();

            params.set(
                'after_id',
                String(
                    Number(afterId) ||
                    0
                )
            );

            params.set(
                'through_id',
                String(
                    Number(throughId) ||
                    0
                )
            );

            params.set(
                'limit',
                '50'
            );

            return (
                SYSTEM_LOGS_FETCH_URL +
                '?' +
                params.toString()
            );
        }

        function showSystemLogsUnauthorized(actionLabel) {
            window.showToast?.({
                type: 'error',
                title: 'Unauthorized',
                message: actionLabel ?
                    `You do not have permission to ${actionLabel}.` :
                    'You do not have permission to perform this action.'
            });
        }

        async function exportSystemLogsPdf(exportOptions = {}) {
            if (!CAN_EXPORT_SYSTEM_LOGS) {
                showSystemLogsUnauthorized('export system logs');
                return;
            }

            const button =
                document.getElementById(
                    'slExportBtn'
                );

            if (!button || button.disabled) {
                return;
            }

            const params =
                new URLSearchParams({
                    role: exportOptions.role || 'all',

                    status: slState.status || 'active',

                    sort: 'desc',

                    date_from: exportOptions.dateFrom || '',

                    date_to: exportOptions.dateTo || '',

                    action_type: exportOptions.actionType || ''
                });

            const endpoint =
                @json(route($routeNames['export'] ?? 'admin.system_logs.export'));

            const url =
                endpoint +
                '?' +
                params.toString();

            const originalHtml =
                button.innerHTML;

            button.disabled = true;

            button.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        <span>Generating...</span>
    `;

            try {
                const response =
                    await fetch(
                        url, {
                            method: 'GET',

                            credentials: 'same-origin',

                            cache: 'no-store',

                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',

                                'Accept': 'application/pdf, application/json'
                            }
                        }
                    );

                const contentType =
                    (
                        response.headers.get(
                            'content-type'
                        ) || ''
                    ).toLowerCase();

                if (!response.ok) {
                    let message =
                        `Unable to export system logs. Server returned ${response.status}.`;

                    if (
                        contentType.includes(
                            'application/json'
                        )
                    ) {
                        const data =
                            await response
                            .json()
                            .catch(
                                () => ({})
                            );

                        message =
                            data.message ||
                            Object.values(
                                data.errors || {}
                            )
                            .flat()
                            .find(Boolean) ||
                            message;
                    } else {
                        const body =
                            await response
                            .text()
                            .catch(
                                () => ''
                            );

                        console.error(
                            'System Logs export server response:',
                            body
                        );
                    }

                    throw new Error(
                        message
                    );
                }

                if (
                    !contentType.includes(
                        'application/pdf'
                    )
                ) {
                    const body =
                        await response
                        .text()
                        .catch(
                            () => ''
                        );

                    console.error(
                        'Expected PDF but received:', {
                            contentType,
                            body
                        }
                    );

                    throw new Error(
                        'The export endpoint did not return a PDF file.'
                    );
                }

                const blob =
                    await response.blob();

                if (!blob.size) {
                    throw new Error(
                        'The generated PDF file is empty.'
                    );
                }

                const downloadUrl =
                    URL.createObjectURL(
                        blob
                    );

                let fileName =
                    `system-logs-${new Date()
                    .toISOString()
                    .slice(0, 10)}.pdf`;

                const disposition =
                    response.headers.get(
                        'content-disposition'
                    ) || '';

                const filenameMatch =
                    disposition.match(
                        /filename="?([^";]+)"?/i
                    );

                if (filenameMatch?.[1]) {
                    fileName =
                        filenameMatch[1]
                        .trim();
                }

                const link =
                    document.createElement(
                        'a'
                    );

                link.href =
                    downloadUrl;

                link.download =
                    fileName;

                document.body.appendChild(
                    link
                );

                link.click();
                link.remove();

                window.setTimeout(
                    function() {
                        URL.revokeObjectURL(
                            downloadUrl
                        );
                    },
                    30000
                );

                window.showToast?.({
                    type: 'success',
                    title: 'Export complete',
                    message: 'System logs PDF downloaded successfully.'
                });

            } catch (error) {
                console.error(
                    'System logs PDF export failed:',
                    error
                );

                window.showToast?.({
                    type: 'error',
                    title: 'Export failed',
                    message: error.message ||
                        'Unable to export the system logs PDF.'
                });

            } finally {
                button.disabled =
                    false;

                button.innerHTML =
                    originalHtml;
            }
        }

        function handleSlExportButtonClick() {
            if (!CAN_EXPORT_SYSTEM_LOGS) {
                showSystemLogsUnauthorized('export system logs');
                return;
            }

            try {
                openSlExportModal();
            } catch (error) {
                console.error(
                    'Unable to open system logs export modal:',
                    error
                );

                window.showToast?.({
                    type: 'error',
                    title: 'Export unavailable',
                    message: 'Unable to open the export options right now. Please refresh the page and try again.'
                });
            }
        }

        window.handleSlExportButtonClick =
            handleSlExportButtonClick;

        function openSlExportModal() {
            if (!CAN_EXPORT_SYSTEM_LOGS) {
                showSystemLogsUnauthorized('export system logs');
                return;
            }

            const modal =
                document.getElementById(
                    'slExportModal'
                );

            if (!modal) {
                throw new Error(
                    'Export modal was not found.'
                );
            }

            const exportForm =
                document.getElementById(
                    'slExportForm'
                );

            if (slExportRoleInput) {
                slExportRoleInput.value = '';

                window.syncCustomSelect?.(
                    slExportRoleInput.closest(
                        '.custom-select'
                    )
                );
            }

            if (slExportActionInput) {
                slExportActionInput.value = '';

                window.syncCustomSelect?.(
                    slExportActionInput.closest(
                        '.custom-select'
                    )
                );
            }

            if (slExportDateFromInput) {
                slExportDateFromInput.value =
                    '';

                slExportDateFromInput
                    ._flatpickr
                    ?.clear();
            }

            if (slExportDateToInput) {
                slExportDateToInput.value =
                    '';

                slExportDateToInput
                    ._flatpickr
                    ?.clear();
            }

            exportForm
                ?.querySelectorAll(
                    'input, select, textarea'
                )
                .forEach(field => {
                    window
                        .showFormInputValidationMessage?.(
                            field,
                            ''
                        );
                });

            [
                slExportDateFromInput,
                slExportDateToInput,
            ].forEach(function(input) {
                if (!input?._flatpickr) {
                    return;
                }

                input._flatpickr.set(
                    'position',
                    'above center'
                );
            });

            try {

                if (
                    typeof window.openModal ===
                    'function'
                ) {
                    window.openModal(
                        'slExportModal'
                    );

                    window.initCustomSelects?.(
                        modal
                    );

                } else {

                    window.openModal(
                        'slExportModal'
                    );

                    document.documentElement
                        .classList.add(
                            'modal-open'
                        );

                    document.body
                        .classList.add(
                            'modal-open'
                        );
                }

            } catch (error) {

                console.error(
                    'System logs export modal open failed:',
                    error
                );

                modal.classList.add(
                    'open'
                );

                modal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.documentElement
                    .classList.add(
                        'modal-open'
                    );

                document.body
                    .classList.add(
                        'modal-open'
                    );
            }
        }

        window.openSlExportModal =
            openSlExportModal;

        function closeSlExportModal() {
            const modal =
                document.getElementById(
                    'slExportModal'
                );

            const form =
                document.getElementById(
                    'slExportForm'
                );

            if (!modal) {
                return;
            }

            window.DiscardChanges
                ?.markSubmitting(
                    form
                );

            window.closeModal?.(
                'slExportModal'
            );

            window.setTimeout(
                function() {
                    form?.reset();

                    window.DiscardChanges
                        ?.markNotSubmitting(
                            form
                        );

                    window.DiscardChanges
                        ?.captureForm(
                            form
                        );
                },
                180
            );
        }

        async function submitSlExportModal() {
            if (!CAN_EXPORT_SYSTEM_LOGS) {
                showSystemLogsUnauthorized(
                    'export system logs'
                );

                return;
            }

            const form =
                document.getElementById(
                    'slExportForm'
                );

            if (!form) {
                return;
            }

            const validation =
                window.validateGlobalForm?.(
                    form
                );

            if (
                validation &&
                !validation.valid
            ) {
                return;
            }

            const role =
                slExportRoleInput?.value || '';

            const rawActionType =
                slExportActionInput?.value || '';

            const dateFrom =
                slExportDateFromInput?.value || '';

            const dateTo =
                slExportDateToInput?.value || '';

            if (
                dateFrom &&
                dateTo &&
                dateFrom > dateTo
            ) {
                window
                    .showFormInputValidationMessage?.(
                        slExportDateToInput,
                        'End date must be the same as or later than the start date.'
                    );

                window
                    .focusGlobalInvalidField?.(
                        slExportDateToInput
                    );

                return;
            }

            window
                .showFormInputValidationMessage?.(
                    slExportDateToInput,
                    ''
                );

            const actionType =
                rawActionType === 'all' ?
                '' :
                rawActionType;

            closeSlExportModal();

            await exportSystemLogsPdf({
                role,
                actionType,
                dateFrom,
                dateTo,
            });
        }

        window.submitSlExportModal = submitSlExportModal;

        var slState = {
            role: @json($role ?? 'all'),
            search: @json($search ?? ''),
            status: @json($status ?? 'active'),
            perPage: {{ (int) ($perPage ?? 10) }},
            page: @json((int) request('page', 1)),
            sort: @json($sort ?? 'desc'),
            dateFrom: @json($dateFrom ?? ''),
            dateTo: @json($dateTo ?? ''),
            actionType: @json($actionType ?? ''),
            module: @json($module ?? ''),
        };

        var slOverallTotal = {{ (int) ($totalCount ?? 0) }};

        var slController = null;
        var slDraftCountController = null;
        var slDraftCountTimer = null;
        var systemLogsRefreshWatcher = null;
        var slVisibleLogs = @json($initialLogsPayload ?? []);
        var slCurrentPagination = null;
        var slStatsCursor = null;

        function updateSystemLogStatCards(counts) {
            if (
                !counts ||
                typeof counts !==
                'object'
            ) {
                return;
            }

            const cards = {
                statTotal: 'total',
                statAdmin: 'admin',
                statDentist: 'dentist',
                statPatient: 'patient',
            };

            Object
                .entries(cards)
                .forEach(
                    ([elementId, key]) => {
                        const element =
                            document
                            .getElementById(
                                elementId
                            );

                        if (
                            !element ||
                            counts[key] ===
                            undefined ||
                            counts[key] ===
                            null
                        ) {
                            return;
                        }

                        element.textContent =
                            String(
                                Number(
                                    counts[key]
                                ) || 0
                            );
                    }
                );

            if (
                counts.total !==
                undefined &&
                counts.total !==
                null
            ) {
                slOverallTotal =
                    Number(
                        counts.total
                    ) || 0;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            syncSlFilterInputs();
            updateSlClearFilterButton();

            window.initGlobalVoiceInputs?.();

            ['slDateFrom', 'slDateTo'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;

                el.addEventListener('change', function() {
                    var preset = document.getElementById('slDatePreset');
                    if (preset) preset.value = '';

                    syncSlQuickDateChips();
                    renderSlFilterChips();
                    updateSlShowResultsButton();
                });

                el.addEventListener('input', function() {
                    var preset = document.getElementById('slDatePreset');
                    if (preset) preset.value = '';

                    syncSlQuickDateChips();
                    renderSlFilterChips();
                    updateSlShowResultsButton();
                });
            });

            ['slModuleFilter', 'slActionType'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;

                el.addEventListener('change', function() {
                    renderSlFilterChips();
                    updateSlShowResultsButton();
                });

                el.addEventListener('input', function() {
                    renderSlFilterChips();
                    updateSlShowResultsButton();
                });
            });

            document.querySelectorAll('#slSortGroup [data-sl-sort]').forEach(function(button) {
                button.addEventListener('click', function() {
                    var sort = document.getElementById('slSortOrder');

                    if (sort) {
                        sort.value = this.dataset.slSort || 'desc';
                    }

                    syncSlFilterChoiceControls();
                    renderSlFilterChips();
                    updateSlShowResultsButton();
                });
            });

            syncSlFilterChoiceControls();
            updateSlShowResultsButton();

            const initialSystemLogsPagination = {
                @if (method_exists($logs, 'total') && $logs->total() > 0)

                    total: {{ (int) $logs->total() }},

                    from: {{ (int) ($logs->firstItem() ?? 0) }},

                    to: {{ (int) ($logs->lastItem() ?? 0) }},

                    current_page: {{ (int) $logs->currentPage() }},

                    last_page: {{ (int) $logs->lastPage() }},

                    per_page: {{ (int) $logs->perPage() }},
                @else

                    total: {{ method_exists($logs, 'count') ? (int) $logs->count() : 0 }},

                    from: 0,

                    to: 0,

                    current_page: 1,

                    last_page: 1,

                    per_page: {{ (int) ($perPage ?? 10) }},
                @endif
            };

            if (
                typeof window.loadPaginationBarModule ===
                'function'
            ) {
                window
                    .loadPaginationBarModule()
                    .then(() => {
                        slRenderPagebar(
                            initialSystemLogsPagination
                        );
                    })
                    .catch(error => {
                        console.error(
                            'Unable to initialize system logs pagination.',
                            error
                        );
                    });
            } else {
                slRenderPagebar(
                    initialSystemLogsPagination
                );
            }

            @if (method_exists($logs, 'count') && $logs->count() === 0)
                showEmptyState(slState.search);
            @endif

            slArchiveDaysInput = document.getElementById('slArchiveDaysInput');
            slArchiveError = document.getElementById('slArchiveError');
            slArchiveConfirmBtn = document.getElementById('slArchiveConfirmBtn');
            slExportRoleInput =
                document.getElementById('slExportRole');

            slExportActionInput =
                document.getElementById('slExportAction');

            slExportRoleInput?.addEventListener(
                'change',
                syncSlExportActionOptions
            );

            function syncSlExportActionOptions() {
                if (
                    !slExportRoleInput ||
                    !slExportActionInput
                ) {
                    return;
                }

                const selectedRole =
                    slExportRoleInput.value || 'all';

                const bookOption =
                    slExportActionInput.querySelector(
                        'option[value="book"]'
                    );

                if (!bookOption) {
                    return;
                }

                const allowBook =
                    selectedRole === 'all' ||
                    selectedRole === 'patient';

                bookOption.disabled = !allowBook;

                if (
                    !allowBook &&
                    slExportActionInput.value === 'book'
                ) {
                    slExportActionInput.value = '';
                }

                const wrapper =
                    slExportActionInput.closest(
                        '.custom-select'
                    );

                if (wrapper) {
                    window.syncCustomSelect?.(
                        wrapper
                    );
                }
            }

            slExportDateFromInput =
                document.getElementById('slExportDateFrom');

            slExportDateToInput =
                document.getElementById('slExportDateTo');

            slExportConfirmBtn =
                document.getElementById('slExportConfirmBtn');
            perPageSelect =
                document.getElementById('perPageSelect');

            if (perPageSelect) {
                perPageSelect.value =
                    String(slState.perPage || 10);

                window.syncGlobalPageSizeSelect?.(
                    perPageSelect,
                    slState.perPage || 10
                );
            }

            if (slArchiveDaysInput) {
                slArchiveDaysInput.addEventListener('input', clearSlArchiveError);
                slArchiveDaysInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        submitSlArchiveModal();
                    }
                });
            }

            systemLogsRefreshWatcher =
                window
                .initGlobalRefreshWatcher?.({
                    key: 'system-logs',

                    url: getSystemLogsLiveCheckUrl,

                    interval: 5000,

                    initialItems: [],

                    autoStart: false,

                    autoApply: true,

                    getItems: function(payload) {
                        const latestId =
                            Number(
                                payload?.latest_id || 0
                            );

                        return latestId > 0 ? [{
                            id: latestId,
                        }] : [];
                    },

                    getItemId: function(item) {
                        return item?.id;
                    },

                    shouldApply: function(payload) {
                        return !!payload?.counts;
                    },

                    getCursor: function(payload) {
                        return (
                            payload?.latest_id ??
                            null
                        );
                    },

                    onRefresh: async function(
                        payload,
                        newItems,
                        context
                    ) {
                        const root = document.getElementById('mainContent');

                        if (
                            !context
                            ?.isCurrent?.()
                        ) {
                            return;
                        }

                        if (payload?.counts) {
                            updateSystemLogStatCards(
                                payload.counts
                            );
                        }

                        if (
                            payload
                            ?.stats_latest_id !==
                            undefined &&
                            payload
                            ?.stats_latest_id !==
                            null
                        ) {
                            slStatsCursor =
                                Number(
                                    payload
                                    .stats_latest_id
                                ) || 0;
                        }

                        if (
                            !newItems.length
                        ) {
                            return;
                        }

                        if (
                            payload
                            ?.requires_full_refresh
                        ) {
                            const beforeIds =
                                window
                                .GlobalLiveUpdate
                                ?.collectIds(
                                    root
                                ) ||
                                new Set();

                            await slFetch(
                                true
                            );

                            if (
                                !context
                                ?.isCurrent?.()
                            ) {
                                return;
                            }

                            const afterIds =
                                window
                                .GlobalLiveUpdate
                                ?.collectIds(
                                    root
                                ) ||
                                new Set();

                            const newIds =
                                Array
                                .from(
                                    afterIds
                                )
                                .filter(
                                    id =>
                                    !beforeIds
                                    .has(id)
                                );

                            const latestVisibleId =
                                Math.max(
                                    0,
                                    ...slVisibleLogs
                                    .map(
                                        log =>
                                        Number(
                                            log?.id
                                        ) || 0
                                    )
                                );

                            if (
                                latestVisibleId >
                                Number(
                                    payload
                                    ?.latest_id ||
                                    0
                                )
                            ) {
                                payload.latest_id =
                                    latestVisibleId;
                            }

                            window
                                .GlobalLiveUpdate
                                ?.highlightItems(
                                    root,
                                    newIds, {
                                        highlightDuration: 5000,

                                        insertDuration: 420,
                                    }
                                );

                            return;
                        }

                        const sinceId =
                            Number(
                                payload
                                ?.since_id ??
                                context?.cursor ??
                                0
                            );

                        const throughId =
                            Number(
                                payload
                                ?.latest_id ||
                                0
                            );

                        const response =
                            await fetch(
                                getSystemLogsLiveFetchUrl(
                                    sinceId,
                                    throughId
                                ), {
                                    cache: 'no-store',

                                    credentials: 'same-origin',

                                    headers: {
                                        'Accept': 'application/json',

                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                }
                            );

                        if (!response.ok) {
                            throw new Error(
                                'Unable to fetch new system logs.'
                            );
                        }

                        const batch =
                            await response.json();

                        if (
                            !context
                            ?.isCurrent?.()
                        ) {
                            return;
                        }

                        const newLogs =
                            Array.isArray(
                                batch.logs
                            ) ?
                            batch.logs : [];

                        if (
                            !newLogs.length
                        ) {
                            return;
                        }

                        const logMap =
                            new Map();

                        [
                            ...newLogs,
                            ...slVisibleLogs,
                        ]
                        .forEach(log => {
                            const id =
                                String(
                                    log?.id ??
                                    ''
                                );

                            if (
                                !id ||
                                logMap.has(id)
                            ) {
                                return;
                            }

                            logMap.set(
                                id,
                                log
                            );
                        });

                        const perPage =
                            Number(
                                slState
                                .perPage
                            ) || 10;

                        slVisibleLogs =
                            Array
                            .from(
                                logMap
                                .values()
                            )
                            .sort(
                                (a, b) =>
                                Number(
                                    b.id
                                ) -
                                Number(
                                    a.id
                                )
                            )
                            .slice(
                                0,
                                perPage
                            );

                        slRenderRows(slVisibleLogs);

                        const addedCount =
                            Number(
                                payload
                                ?.new_count ??
                                newLogs.length
                            );

                        const oldTotal =
                            Number(
                                slCurrentPagination
                                ?.total ??
                                0
                            );

                        const newTotal =
                            oldTotal +
                            addedCount;

                        slRenderPagebar({
                            total: newTotal,

                            from: newTotal > 0 ?
                                1 : 0,

                            to: Math.min(
                                perPage,
                                newTotal
                            ),

                            current_page: 1,

                            last_page: Math.max(
                                1,
                                Math.ceil(
                                    newTotal /
                                    perPage
                                )
                            ),

                            per_page: perPage,
                        });

                        const newIds =
                            newLogs
                            .map(
                                log =>
                                String(
                                    log.id
                                )
                            );

                        window
                            .GlobalLiveUpdate
                            ?.highlightItems(
                                root,
                                newIds, {
                                    highlightDuration: 5000,

                                    insertDuration: 420,
                                }
                            );
                    },

                    toast: false,
                });

            async function initializeSystemLogsRefreshWatcher() {
                const selectedStatuses =
                    String(
                        slState.status || 'all'
                    )
                    .split(',')
                    .map(value =>
                        value.trim().toLowerCase()
                    )
                    .filter(Boolean);

                const includesActive =
                    selectedStatuses.includes('all') ||
                    selectedStatuses.includes('active');

                const canLiveUpdate =
                    includesActive &&
                    Number(
                        slState.page || 1
                    ) === 1 &&
                    (
                        slState.sort ||
                        'desc'
                    ) === 'desc';

                if (
                    !systemLogsRefreshWatcher ||
                    !canLiveUpdate
                ) {
                    systemLogsRefreshWatcher?.stop();
                    return;
                }

                try {
                    const response =
                        await fetch(
                            getSystemLogsLiveCheckUrl(), {
                                cache: 'no-store',

                                credentials: 'same-origin',

                                headers: {
                                    'Accept': 'application/json',

                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }
                        );

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();

                    systemLogsRefreshWatcher.sync(payload);

                    slStatsCursor =
                        Number(
                            payload
                            ?.stats_latest_id ||
                            0
                        );

                    systemLogsRefreshWatcher.start();

                } catch (error) {
                    console.warn(
                        'Unable to initialize system logs refresh watcher:',
                        error
                    );
                }
            }

            window.handleSystemLogsStatusChange =
                function(values) {
                    const selectedStatuses =
                        (
                            Array.isArray(values) ?
                            values :
                            String(values || '')
                            .split(',')
                        )
                        .map(value =>
                            String(value || '')
                            .trim()
                            .toLowerCase()
                        )
                        .filter(Boolean);

                    const effectiveStatuses =
                        selectedStatuses.includes('all') ||
                        !selectedStatuses.length ? ['all'] :
                        selectedStatuses;

                    slState.status = effectiveStatuses.join(',');
                    slState.page = 1;

                    return slFetch();
                };

            window.handleSystemLogsRoleChange =
                function(values) {
                    const selectedRoles =
                        (
                            Array.isArray(values) ?
                            values :
                            String(values || '')
                            .split(',')
                        )
                        .map(value =>
                            String(value || '')
                            .trim()
                            .toLowerCase()
                        )
                        .filter(Boolean);

                    const effectiveRoles =
                        selectedRoles.includes('all') ||
                        !selectedRoles.length ? ['all'] :
                        selectedRoles;

                    slState.role =
                        effectiveRoles.join(',');

                    slState.page = 1;

                    return slFetch();
                };

            initializeSystemLogsRefreshWatcher();

            const initialStatuses =
                String(
                    slState.status || 'all'
                )
                .split(',')
                .map(value =>
                    value.trim().toLowerCase()
                )
                .filter(Boolean);

            if (
                !initialStatuses.includes('all') &&
                !initialStatuses.includes('active')
            ) {
                systemLogsRefreshWatcher?.stop();
            }

            function escapeSlHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function formatSlDetailLabel(
                value
            ) {
                return String(value || '')
                    .replaceAll('_', ' ')
                    .replace(
                        /\b\w/g,
                        character =>
                        character.toUpperCase()
                    );
            }

            function renderSlDetailPrimitive(
                value
            ) {
                if (
                    value === null ||
                    value === undefined ||
                    value === ''
                ) {
                    return 'Not set';
                }

                if (
                    typeof value ===
                    'boolean'
                ) {
                    return value ?
                        'Yes' :
                        'No';
                }

                return escapeSlHtml(
                    String(value)
                );
            }

            function renderSlDetailObject(
                object
            ) {
                if (
                    !object ||
                    typeof object !== 'object'
                ) {
                    return (
                        '<div class="global-info-item global-info-item-compact">' +
                        '<div class="global-info-copy">' +
                        '<span class="global-info-value">' +
                        renderSlDetailPrimitive(
                            object
                        ) +
                        '</span>' +
                        '</div>' +
                        '</div>'
                    );
                }

                if (Array.isArray(object)) {
                    if (!object.length) {
                        return (
                            '<div class="global-info-item global-info-item-compact">' +
                            '<div class="global-info-copy">' +
                            '<span class="global-info-value">' +
                            'None' +
                            '</span>' +
                            '</div>' +
                            '</div>'
                        );
                    }

                    return object
                        .map(
                            (item, index) => {
                                if (
                                    item &&
                                    typeof item ===
                                    'object'
                                ) {
                                    return (
                                        '<div class="global-info-item global-info-item-wide">' +
                                        '<div class="global-info-copy">' +
                                        '<span class="global-info-label">' +
                                        'Item ' +
                                        (index + 1) +
                                        '</span>' +
                                        '<div class="global-info-grid">' +
                                        renderSlDetailObject(
                                            item
                                        ) +
                                        '</div>' +
                                        '</div>' +
                                        '</div>'
                                    );
                                }

                                return (
                                    '<div class="global-info-item global-info-item-compact">' +
                                    '<div class="global-info-copy">' +
                                    '<span class="global-info-label">' +
                                    'Item ' +
                                    (index + 1) +
                                    '</span>' +
                                    '<span class="global-info-value">' +
                                    renderSlDetailPrimitive(
                                        item
                                    ) +
                                    '</span>' +
                                    '</div>' +
                                    '</div>'
                                );
                            }
                        )
                        .join('');
                }

                return Object
                    .entries(object)
                    .map(
                        ([key, value]) => {
                            const label =
                                escapeSlHtml(
                                    formatSlDetailLabel(
                                        key
                                    )
                                );

                            const isNested =
                                value &&
                                typeof value ===
                                'object';

                            if (isNested) {
                                return (
                                    '<div class="global-info-item global-info-item-wide">' +
                                    '<div class="global-info-copy">' +
                                    '<span class="global-info-label">' +
                                    label +
                                    '</span>' +

                                    '<div class="global-info-grid global-info-grid-2">' +
                                    renderSlDetailObject(
                                        value
                                    ) +
                                    '</div>' +
                                    '</div>' +
                                    '</div>'
                                );
                            }

                            return (
                                '<div class="global-info-item global-info-item-compact">' +
                                '<div class="global-info-copy">' +

                                '<span class="global-info-label">' +
                                label +
                                '</span>' +

                                '<span class="global-info-value">' +
                                renderSlDetailPrimitive(
                                    value
                                ) +
                                '</span>' +

                                '</div>' +
                                '</div>'
                            );
                        }
                    )
                    .join('');
            }

            function closeSlOverflowMenu() {
                const menu =
                    document.getElementById(
                        'slOverflowMenu'
                    );

                const trigger =
                    document.getElementById(
                        'slOverflowTrigger'
                    );

                menu?.classList.remove(
                    'is-open'
                );

                trigger?.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }

            function toggleSlOverflowMenu(event) {
                event?.preventDefault();
                event?.stopPropagation();

                const menu =
                    document.getElementById(
                        'slOverflowMenu'
                    );

                const trigger =
                    document.getElementById(
                        'slOverflowTrigger'
                    );

                if (!menu) {
                    return;
                }

                const willOpen = !menu.classList.contains(
                    'is-open'
                );

                menu.classList.toggle(
                    'is-open',
                    willOpen
                );

                trigger?.setAttribute(
                    'aria-expanded',
                    willOpen ? 'true' : 'false'
                );
            }

            document.addEventListener(
                'click',
                function(event) {
                    const menu =
                        document.getElementById(
                            'slOverflowMenu'
                        );

                    if (
                        menu &&
                        !menu.contains(event.target)
                    ) {
                        closeSlOverflowMenu();
                    }
                }
            );

            document.addEventListener(
                'keydown',
                function(event) {
                    if (event.key === 'Escape') {
                        closeSlOverflowMenu();
                    }
                }
            );

            window.toggleSlOverflowMenu =
                toggleSlOverflowMenu;

            window.closeSlOverflowMenu =
                closeSlOverflowMenu;

            window.handleSystemLogsSearch =
                function(value) {
                    if (!CAN_VIEW_SYSTEM_LOGS) {
                        showSystemLogsUnauthorized('view system logs');
                        return;
                    }

                    const query =
                        String(value || '')
                        .trim();

                    slState.search =
                        query;

                    slState.page = 1;

                    return slFetch();
                };

            function openSlArchiveModal() {
                if (!CAN_ARCHIVE_SYSTEM_LOGS) {
                    showSystemLogsUnauthorized('archive system logs');
                    return;
                }

                const selectedStatuses =
                    String(
                        slState.status || 'all'
                    )
                    .split(',')
                    .map(value =>
                        value.trim().toLowerCase()
                    )
                    .filter(Boolean);

                const includesActive =
                    selectedStatuses.includes('all') ||
                    selectedStatuses.includes('active');

                if (!includesActive) {
                    window.showToast?.({
                        type: 'warning',
                        title: 'Archive unavailable',
                        message: 'Switch to Active or All Logs before archiving.'
                    });

                    return;
                }

                var modal =
                    document.getElementById("slArchiveModal");

                if (!modal) {
                    console.error(
                        "Archive modal #slArchiveModal was not found."
                    );
                    return;
                }

                clearSlArchiveError();

                if (
                    slArchiveDaysInput &&
                    !slArchiveDaysInput.value
                ) {
                    slArchiveDaysInput.value = "90";
                }

                if (typeof window.openModal === "function") {
                    window.openModal(
                        'slArchiveModal'
                    );

                    window.initGlobalNumberSteppers?.(
                        document.getElementById(
                            'slArchiveModal'
                        )
                    );
                } else {
                    window.openModal('slArchiveModal');
                    document.documentElement.classList.add("modal-open");
                    document.body.classList.add("modal-open");
                }

                window.setTimeout(function() {
                    slArchiveDaysInput?.focus();
                    slArchiveDaysInput?.select();
                }, 100);
            }

            function closeSlArchiveModal() {
                const modal =
                    document.getElementById(
                        'slArchiveModal'
                    );

                const form =
                    document.getElementById(
                        'slArchiveForm'
                    );

                if (!modal) {
                    return;
                }

                clearSlArchiveError();

                window.DiscardChanges
                    ?.markSubmitting(
                        form
                    );

                window.closeModal?.(
                    'slArchiveModal'
                );

                window.setTimeout(
                    function() {
                        if (form) {
                            form.reset();
                        }

                        if (slArchiveDaysInput) {
                            slArchiveDaysInput.value =
                                '90';

                            slArchiveDaysInput.dispatchEvent(
                                new Event(
                                    'input', {
                                        bubbles: true
                                    }
                                )
                            );
                        }

                        window.DiscardChanges
                            ?.markNotSubmitting(
                                form
                            );

                        window.DiscardChanges
                            ?.captureForm(
                                form
                            );
                    },
                    180
                );
            }

            function clearSlArchiveError() {
                if (!slArchiveDaysInput) {
                    return;
                }

                window.showFormInputValidationMessage?.(
                    slArchiveDaysInput,
                    ''
                );
            }

            function submitSlArchiveModal() {
                if (!CAN_ARCHIVE_SYSTEM_LOGS) {
                    showSystemLogsUnauthorized('archive system logs');
                    return;
                }

                var olderThanDays = Number(slArchiveDaysInput?.value || '');

                if (
                    !Number.isFinite(olderThanDays) ||
                    !Number.isInteger(olderThanDays) ||
                    olderThanDays < 1 ||
                    olderThanDays > 3650
                ) {
                    window.showFormInputValidationMessage?.(
                        slArchiveDaysInput,
                        'Please enter a whole number from 1 to 3650.'
                    );

                    window.focusGlobalInvalidField?.(
                        slArchiveDaysInput
                    );

                    return;
                }

                window.showFormInputValidationMessage?.(
                    slArchiveDaysInput,
                    ''
                );

                var body = new URLSearchParams({
                    older_than_days: String(Math.floor(olderThanDays)),
                    role: slState.role || 'all',
                    search: slState.search || '',
                    sort: slState.sort || 'desc',
                    date_from: slState.dateFrom || '',
                    date_to: slState.dateTo || '',
                    action_type: slState.actionType || '',
                    module: slState.module || '',
                });

                if (slArchiveConfirmBtn) {
                    slArchiveConfirmBtn.disabled = true;
                    slArchiveConfirmBtn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i><span>Archiving...</span>';
                }

                const archiveForm =
                    document.getElementById(
                        'slArchiveForm'
                    );

                window.DiscardChanges
                    ?.markSubmitting(
                        archiveForm
                    );

                fetch('{{ route($routeNames['archive'] ?? 'admin.system_logs.archive') }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'Accept': 'application/json'
                        },
                        body: body.toString()
                    })
                    .then(function(res) {
                        return res.json().then(function(data) {
                            return {
                                ok: res.ok,
                                data: data
                            };
                        });
                    })
                    .then(function(result) {
                        if (!result.ok) {
                            throw new Error(result.data?.message || 'Archive request failed');
                        }

                        closeSlArchiveModal();
                        window.showToast?.({
                            type: 'success',
                            title: 'Logs archived',
                            message: result.data?.message ||
                                'Logs archived successfully.'
                        });
                        slState.page = 1;
                        slFetch();
                    })
                    .catch(function(error) {
                        window.DiscardChanges
                            ?.markNotSubmitting(
                                archiveForm
                            );

                        window.showToast?.({
                            type: 'error',
                            title: 'Error',
                            message: error.message ||
                                'Unable to archive logs right now.'
                        });
                    })
                    .finally(function() {
                        if (slArchiveConfirmBtn) {
                            slArchiveConfirmBtn.disabled = false;
                            slArchiveConfirmBtn.innerHTML =
                                '<i class="fa-solid fa-box-archive"></i><span>Archive Logs</span>';
                        }
                    });
            }

            function hasActiveSlFilters() {
                return (
                    (
                        slState.sort &&
                        slState.sort !== 'desc'
                    ) ||
                    !!slState.dateFrom ||
                    !!slState.dateTo ||
                    !!slState.actionType ||
                    !!slState.module
                );
            }

            function detectSlDatePreset(from, to) {
                if (!from || !to) return '';

                var today = new Date();
                today.setHours(0, 0, 0, 0);

                var todayValue = formatSlDate(today);

                var week = new Date(today);
                week.setDate(today.getDate() - 6);

                var month = new Date(today);
                month.setDate(today.getDate() - 29);

                if (from === todayValue && to === todayValue) return 'today';
                if (from === formatSlDate(week) && to === todayValue) return 'week';
                if (from === formatSlDate(month) && to === todayValue) return 'month';

                return '';
            }

            function getSlDatePresetText(preset) {
                if (preset === 'today') return 'Today';
                if (preset === 'week') return 'Last 7 Days';
                if (preset === 'month') return 'Last 30 Days';
                return '';
            }

            function getSlDateChipLabel(from, to) {
                var preset = document.getElementById('slDatePreset')?.value || '';

                if (preset) {
                    return getSlDatePresetText(preset);
                }

                if (from && to) return from + ' to ' + to;
                if (from) return 'From ' + from;
                if (to) return 'Until ' + to;

                return '';
            }

            function syncSlQuickDateChips() {
                var preset = document.getElementById('slDatePreset')?.value || '';

                document.querySelectorAll('#slDatePresetGroup .quick-date-chip').forEach(function(button) {
                    button.classList.toggle('active', String(button.dataset.slDatePreset || '') === String(
                        preset ||
                        ''));
                });
            }

            function syncSlFilterChoiceControls() {
                var sortEl = document.getElementById('slSortOrder');
                var actionEl = document.getElementById('slActionType');

                var sortValue =
                    sortEl ?
                    sortEl.value || 'desc' :
                    slState.sort || 'desc';

                document.querySelectorAll('#slSortGroup [data-sl-sort]').forEach(function(button) {
                    button.classList.toggle('ftag-active', String(button.dataset.slSort || '') === String(
                        sortValue ||
                        'desc'));
                });

                window.syncCustomSelect?.(actionEl?.closest('.custom-select'));
                syncSlQuickDateChips();
            }

            function syncSlFilterInputs() {
                var sort = document.getElementById('slSortOrder');
                var from = document.getElementById('slDateFrom');
                var to = document.getElementById('slDateTo');
                var action = document.getElementById('slActionType');
                var module = document.getElementById('slModuleFilter');
                var preset = document.getElementById('slDatePreset');

                if (sort) sort.value = slState.sort || 'desc';
                if (from) from.value = slState.dateFrom || '';
                if (to) to.value = slState.dateTo || '';
                if (preset) preset.value = detectSlDatePreset(slState.dateFrom || '', slState.dateTo || '');
                if (action) action.value = slState.actionType || '';
                if (module) module.value = slState.module || '';

                syncSlFilterChoiceControls();
            }

            function getSlFilterModal() {
                return document.getElementById('filterModal');
            }

            async function openSlFilterPanel() {
                syncSlFilterInputs();
                renderSlFilterChips();

                const filterModal =
                    document.getElementById(
                        'filterModal'
                    );

                if (
                    typeof window.openFilterDrawer ===
                    'function'
                ) {
                    await window.openFilterDrawer(
                        'filterModal'
                    );
                } else {
                    filterModal?.classList.add(
                        'open'
                    );

                    document.documentElement
                        .classList.add(
                            'filter-lock'
                        );

                    document.body
                        .classList.add(
                            'filter-lock'
                        );
                }

                filterModal?.setAttribute(
                    'aria-hidden',
                    'false'
                );

                window.initCustomSelects?.(filterModal);

                const datePickerModule =
                    await window
                    .loadDatePickerModule?.();

                await datePickerModule
                    ?.initGlobalDatePickers(
                        filterModal
                    );
            }

            function closeSlFilterPanel() {
                if (typeof window.closeFilterDrawer === 'function') {
                    window.closeFilterDrawer('filterModal');
                } else {
                    document.getElementById('filterModal')?.classList.remove('open');
                    document.documentElement.classList.remove('filter-lock');
                    document.body.classList.remove('filter-lock');
                }

                document.getElementById('filterModal')?.setAttribute('aria-hidden', 'true');
            }

            function updateSlClearFilterButton() {
                var count = 0;

                if (
                    slState.sort &&
                    slState.sort !== 'desc'
                ) {
                    count++;
                }

                if (
                    slState.dateFrom ||
                    slState.dateTo
                ) {
                    count++;
                }

                if (slState.actionType) {
                    count++;
                }

                if (slState.module) {
                    count++;
                }

                var has =
                    count > 0;

                var filterBtn =
                    document.getElementById(
                        'slFilterBtn'
                    );

                var filterBadge =
                    document.getElementById(
                        'slFilterBadge'
                    );

                var externalClearFilterBtn =
                    document.getElementById(
                        'slExternalClearFilterBtn'
                    );

                if (filterBtn) {
                    filterBtn.classList.toggle(
                        'has-filters',
                        has
                    );

                    filterBtn.setAttribute(
                        'aria-pressed',
                        has ? 'true' : 'false'
                    );
                }

                if (filterBadge) {
                    filterBadge.classList.toggle(
                        'show',
                        has
                    );

                    filterBadge.textContent =
                        has ?
                        String(count) :
                        '';
                }

                if (externalClearFilterBtn) {
                    externalClearFilterBtn
                        .classList.toggle(
                            'hidden',
                            !has
                        );

                    externalClearFilterBtn
                        .classList.toggle(
                            'show',
                            has
                        );
                }
            }

            async function clearOnlySlFilters() {
                slState.role = 'all';
                slState.status = 'active';
                slState.sort = 'desc';
                slState.dateFrom = '';
                slState.dateTo = '';
                slState.actionType = '';
                slState.module = '';
                slState.page = 1;

                await window
                    .setGlobalFilterSelectValue?.(
                        'slRoleSelect',
                        'all', {
                            callback: false,
                            focus: false,
                        }
                    );

                await window
                    .setGlobalFilterSelectValue?.(
                        'slStatusSelect',
                        'active', {
                            callback: false,
                            focus: false,
                        }
                    );

                syncSlFilterInputs();
                renderSlFilterChips();
                updateSlClearFilterButton();

                closeSlFilterPanel();
                return slFetch();
            }

            function clearSlFilterPanelDraft() {
                var sort = document.getElementById('slSortOrder');
                var from = document.getElementById('slDateFrom');
                var to = document.getElementById('slDateTo');
                var action = document.getElementById('slActionType');
                var module = document.getElementById('slModuleFilter');

                if (sort) sort.value = 'desc';
                if (from) from.value = '';
                if (to) to.value = '';
                if (action) action.value = '';
                if (module) module.value = '';

                syncSlFilterChoiceControls();
                renderSlFilterChips();
            }

            function renderSlFilterChips() {
                var container = document.getElementById('slActiveChipsContainer');
                var section = document.getElementById('slActiveFiltersSection');
                var clearAllBtn = document.getElementById('slClearAllChipsBtn');

                if (!container || !section) return;

                container.innerHTML = '';
                var hasChips = false;

                function addChip(label, callback) {
                    hasChips = true;

                    var chip = document.createElement('div');
                    chip.className = 'filter-chip sl-filter-chip';
                    chip.innerHTML =
                        '<span>' + escapeSlHtml(label) +
                        '</span><span class="filter-chip-remove sl-filter-chip-remove"><i class="fa-solid fa-xmark"></i></span>';

                    chip.querySelector('.sl-filter-chip-remove').onclick = function() {
                        callback();
                        syncSlFilterChoiceControls();
                        renderSlFilterChips();
                    };

                    container.appendChild(chip);
                }

                var sortVal = document.getElementById('slSortOrder')?.value || 'desc';
                var fromVal = document.getElementById('slDateFrom')?.value || '';
                var toVal = document.getElementById('slDateTo')?.value || '';
                var actionVal = document.getElementById('slActionType')?.value || '';
                var moduleVal = document.getElementById('slModuleFilter')?.value || '';

                if (sortVal === 'asc') {
                    addChip('Sort: Oldest first', function() {
                        document.getElementById('slSortOrder').value = 'desc';
                    });
                }

                if (fromVal || toVal) {
                    var lbl = getSlDateChipLabel(fromVal, toVal);

                    addChip('Date: ' + lbl, function() {
                        document.getElementById('slDateFrom').value = '';
                        document.getElementById('slDateTo').value = '';

                        var preset = document.getElementById('slDatePreset');
                        if (preset) preset.value = '';
                    });
                }

                if (actionVal) {
                    addChip('Action: ' + actionVal.charAt(0).toUpperCase() + actionVal.slice(1), function() {
                        document.getElementById('slActionType').value = '';
                    });
                }

                if (moduleVal) {
                    addChip('Module: ' + moduleVal, function() {
                        document.getElementById('slModuleFilter').value = '';
                    });
                }

                section.classList.toggle('hidden', !hasChips);

                if (clearAllBtn) {
                    clearAllBtn.onclick = function() {
                        clearSlFilterPanelDraft();
                    };
                }

                updateSlShowResultsButton();
            }

            function formatSlDate(date) {
                var yyyy = date.getFullYear();
                var mm = String(date.getMonth() + 1).padStart(2, '0');
                var dd = String(date.getDate()).padStart(2, '0');
                return yyyy + '-' + mm + '-' + dd;
            }

            function setSlQuickDate(type) {
                var from = document.getElementById('slDateFrom');
                var to = document.getElementById('slDateTo');
                var preset = document.getElementById('slDatePreset');

                var today = new Date();
                today.setHours(0, 0, 0, 0);

                if (!from || !to) return;
                if (!['today', 'week', 'month'].includes(type)) return;

                var start = new Date(today);

                if (type === 'week') {
                    start.setDate(today.getDate() - 6);
                } else if (type === 'month') {
                    start.setDate(today.getDate() - 29);
                }

                from.value = formatSlDate(start);
                to.value = formatSlDate(today);

                if (preset) {
                    preset.value = type;
                }

                syncSlQuickDateChips();
                renderSlFilterChips();
                updateSlShowResultsButton();
            }

            function setSlShowResultsText(total) {
                var text = document.getElementById('slShowResultsText');
                if (!text) return;

                var count = Number(total || 0);
                text.textContent = 'Show ' + count + ' ' + (count === 1 ? 'result' : 'results');
            }

            function getSlDraftFilterParams() {
                return new URLSearchParams({
                    search: slState.search || '',
                    role: slState.role || 'all',
                    status: slState.status || 'active',
                    per_page: 1,
                    page: 1,
                    sort: document.getElementById('slSortOrder')?.value || 'desc',
                    date_from: document.getElementById('slDateFrom')?.value || '',
                    date_to: document.getElementById('slDateTo')?.value || '',
                    action_type: document.getElementById('slActionType')?.value || '',
                    module: document.getElementById('slModuleFilter')?.value.trim() || '',
                });
            }

            function updateSlShowResultsButton(total) {
                if (typeof total === 'number') {
                    setSlShowResultsText(total);
                    return;
                }

                clearTimeout(slDraftCountTimer);

                slDraftCountTimer = setTimeout(function() {
                    if (slDraftCountController) {
                        slDraftCountController.abort();
                    }

                    slDraftCountController = new AbortController();

                    fetch('{{ route($routeNames['index'] ?? 'admin.system_logs') }}?' +
                            getSlDraftFilterParams().toString(), {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        ?.content ?? ''
                                },
                                signal: slDraftCountController.signal
                            })
                        .then(function(res) {
                            if (!res.ok) throw new Error('Draft count request failed');
                            return res.json();
                        })
                        .then(function(data) {
                            var total = Number(data.pagination?.total ?? 0);
                            setSlShowResultsText(total);
                        })
                        .catch(function(e) {
                            if (e.name !== 'AbortError') {
                                setSlShowResultsText(slOverallTotal || 0);
                            }
                        });
                }, 220);
            }

            function applySlFilters() {
                slState.sort = document.getElementById('slSortOrder')?.value || 'desc';
                slState.dateFrom = document.getElementById('slDateFrom')?.value || '';
                slState.dateTo = document.getElementById('slDateTo')?.value || '';
                slState.actionType = document.getElementById('slActionType')?.value || '';
                slState.module = document.getElementById('slModuleFilter')?.value.trim() || '';
                slState.page = 1;

                updateSlClearFilterButton();
                closeSlFilterPanel();
                slFetch();
            }

            function resetSlFilters() {
                clearOnlySlFilters();
            }

            function slFetch(silent) {
                if (slController) slController.abort();

                slController = new AbortController();

                var params = new URLSearchParams({
                    role: slState.role || 'all',
                    search: slState.search || '',
                    status: slState.status || 'active',
                    per_page: slState.perPage || 10,
                    page: slState.page || 1,
                    sort: slState.sort || 'desc',
                    date_from: slState.dateFrom || '',
                    date_to: slState.dateTo || '',
                    action_type: slState.actionType || '',
                    module: slState.module || '',
                });

                history.replaceState(null, '', window.location.pathname + '?' + params.toString());

                var tableBody = document.getElementById('slTableBody');
                var gridBody = document.getElementById('slGridBody');

                if (!silent) {
                    if (tableBody) tableBody.innerHTML = slSkeletonRows(slState.perPage);
                    if (gridBody) gridBody.innerHTML = slSkeletonCards(slState.perPage);
                }

                window.EmptyState?.hide(
                    '#emptyState'
                );

                console.log(
                    '[System Logs] request:',
                    params.toString()
                );

                return fetch(
                        '{{ route($routeNames['index'] ?? 'admin.system_logs') }}?' +
                        params.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                            },
                            signal: slController.signal
                        })
                    .then(function(res) {
                        if (!res.ok) throw new Error('Request failed');
                        return res.json();
                    })
                    .then(function(data) {
                        console.log(
                            '[System Logs] response:', {
                                filters: data.filters,

                                total: data.pagination?.total,

                                logs: data.logs
                            }
                        );

                        updateSystemLogStatCards(data.counts);

                        slVisibleLogs =
                            Array.isArray(
                                data.logs
                            ) ?
                            data.logs : [];

                        slRenderRows(slVisibleLogs);
                        slRenderPagebar(data.pagination);
                        updateSlClearFilterButton();

                        updateSlShowResultsButton(
                            Number(
                                data.pagination?.total ??
                                0
                            )
                        );

                        if (!silent) {
                            initializeSystemLogsRefreshWatcher();
                        }

                        return data;
                    })
                    .catch(function(error) {
                        if (
                            error.name ===
                            'AbortError'
                        ) {
                            return;
                        }

                        console.error(
                            'System Logs fetch error:',
                            error
                        );

                        throw error;
                    });
            }

            function clearSlExpandedLogs() {
                const mainContent =
                    document.getElementById(
                        'mainContent'
                    );

                if (!mainContent) {
                    return;
                }

                window.ShowMore
                    ?.collapseDisclosures?.(
                        mainContent
                    );
            }

            function handleSystemLogsPerPageChange(value) {
                const nextPerPage = Number(value) || 10;

                if (slState.perPage === nextPerPage) {
                    return;
                }

                clearSlExpandedLogs();
                slState.perPage = nextPerPage;
                slState.page = 1;

                slFetch();
            }

            window.handleSystemLogsPerPageChange =
                handleSystemLogsPerPageChange;

            function slSkeletonRows(count) {
                var row = '<tr>' +
                    '<td><span class="sl-id sl-skeleton sl-skeleton-check">&nbsp;&nbsp;&nbsp;&nbsp;</span></td>' +
                    '<td><span class="sl-skeleton sl-skeleton-id">&nbsp;</span></td>' +
                    '<td><span class="sl-skeleton sl-skeleton-role">&nbsp;</span></td>' +
                    '<td><span class="sl-skeleton sl-skeleton-user">&nbsp;</span></td>' +
                    '<td><span class="sl-skeleton sl-skeleton-action">&nbsp;</span></td>' +
                    '<td><span class="sl-skeleton sl-skeleton-id">&nbsp;</span></td>' +
                    '<td><span class="sl-skeleton sl-skeleton-desc">&nbsp;</span></td>' +
                    '</tr>';
                var html = '';
                for (var i = 0; i < Math.min(Number(count) || 5, 5); i++) html += row;
                return html;
            }

            function slSkeletonCards(count) {
                var html = '';
                for (var i = 0; i < Math.min(Number(count) || 4, 4); i++) {
                    html += '<div class="sl-grid-card sl-grid-card-skeleton">' +
                        '<span class="sl-skeleton sl-skeleton-id"></span>' +
                        '<span class="sl-skeleton sl-skeleton-user"></span>' +
                        '<span class="sl-skeleton sl-skeleton-desc"></span>' +
                        '<span class="sl-skeleton sl-skeleton-desc"></span>' +
                        '</div>';
                }
                return html;
            }

            function restoreSlCurrentView() {
                const listView =
                    document.getElementById(
                        'slListView'
                    );

                const gridView =
                    document.getElementById(
                        'slGridView'
                    );

                const savedView =
                    localStorage.getItem(
                        'systemLogsView'
                    );

                const useGrid =
                    savedView === 'grid';

                if (listView) {
                    listView.hidden =
                        useGrid;
                }

                if (gridView) {
                    gridView.hidden = !useGrid;
                }
            }

            function slRenderRows(logs) {
                var tableBody = document.getElementById('slTableBody');
                var gridBody = document.getElementById('slGridBody');

                if (!logs || logs.length === 0) {
                    if (tableBody) tableBody.innerHTML = '';
                    if (gridBody) gridBody.innerHTML = '';
                    showEmptyState(slState.search);
                    return;
                }

                restoreSlCurrentView();

                window.EmptyState?.hide(
                    '#emptyState'
                );

                var actionIcons = {
                    login: 'fa-right-to-bracket',
                    logout: 'fa-right-from-bracket',
                    view: 'fa-eye',
                    create: 'fa-plus',
                    update: 'fa-pen',
                    delete: 'fa-trash',
                    error: 'fa-triangle-exclamation',
                    book: 'fa-calendar-plus',
                    archive: 'fa-box-archive',
                    export: 'fa-file-export',
                    default: 'fa-bolt'
                };

                var roleIcons = {
                    admin: 'fa-user-tie',
                    dentist: 'fa-user-doctor',
                    patient: 'fa-user'
                };

                var tableHtml = '';
                var gridHtml = '';

                logs.forEach(function(log) {
                    var role = (log.actor_role || 'other').toLowerCase();
                    var action = (log.action || '').toLowerCase();
                    var actionClass =
                        (
                            action.includes('error') ||
                            action.includes('failed') ||
                            action.includes('exception')
                        ) ? 'error' :

                        action.includes('login') ?
                        'login' :

                        action.includes('logout') ?
                        'logout' :

                        action.includes('view') ?
                        'view' :

                        action.includes('create') ?
                        'create' :

                        action.includes('update') ?
                        'update' :

                        action.includes('delete') ?
                        'delete' :

                        action.includes('book') ?
                        'book' :

                        action.includes('archive') ?
                        'archive' :

                        action.includes('export') ?
                        'export' :

                        'default';

                    var actionIcon = actionIcons[actionClass] || 'fa-bolt';

                    var roleBadgeClasses = {
                        admin: 'role-admin',
                        dentist: 'role-dentist',
                        patient: 'role-patient',
                        other: 'role-none'
                    };

                    var roleBadgeClass =
                        roleBadgeClasses[role] ||
                        'role-none';

                    var actionStatusClasses = {
                        login: 'status-active',
                        logout: 'status-ended',
                        view: 'status-all',
                        create: 'status-approved',
                        update: 'status-rescheduled',
                        delete: 'status-cancelled',
                        error: 'status-failed',
                        book: 'status-upcoming',
                        archive: 'status-archived',
                        export: 'status-released',
                        default: 'status-default'
                    };

                    var actionStatusClass =
                        actionStatusClasses[
                            actionClass
                        ] || 'status-default';

                    var actionIconHtml = '<i class="fa-solid ' + actionIcon + (actionClass === 'error' ?
                        ' sl-action-alert' : '') + '"></i>';
                    var roleIcon = roleIcons[role] || 'fa-circle-user';
                    var letter = escapeSlHtml((log.actor_name || role).charAt(0).toUpperCase());
                    var idPadded = '#' + String(log.id || '').padStart(3, '0');

                    var actionLabels = {
                        login: 'Login',
                        logout: 'Logout',
                        view: 'View',
                        create: 'Create',
                        update: 'Update',
                        delete: 'Delete',
                        book: 'Book',
                        archive: 'Archive',
                        export: 'Export',
                        error: 'Error'
                    };

                    var actionLabel =
                        escapeSlHtml(
                            actionLabels[actionClass] ||
                            (log.action || '')
                            .replace(/_/g, ' ')
                            .replace(
                                /\b\w/g,
                                function(c) {
                                    return c.toUpperCase();
                                }
                            )
                        );
                    var moduleLabel = escapeSlHtml((log.module || '').replace(/_/g, ' ').replace(/\b\w/g,
                        function(c) {
                            return c.toUpperCase();
                        }));

                    var archiveBadge =
                        log.is_archived ?
                        `
            <span
                class="status-pill s-archived"
                title="${escapeSlHtml(
                        log.archived_at ||
                        'Archived'
                    )}"
            >
                <span class="status-dot"></span>
                <i class="fa-solid fa-box-archive"></i>
                Archived
            </span>
        ` :
                        '';
                    var actorName = escapeSlHtml(log.actor_name ?? log.actor_identifier ?? 'Unknown User');
                    var rawDescription =
                        String(
                            log.description ||
                            'No description provided.'
                        ).trim();

                    var rawFullDescription =
                        String(
                            log.full_description || ''
                        ).trim();

                    var description =
                        escapeSlHtml(
                            rawDescription
                        );

                    var hasGridExpandableDescription =
                        rawFullDescription !== '' &&
                        rawFullDescription !== rawDescription;

                    var hasMeaningfulDetails =
                        rawFullDescription !== '' &&
                        rawFullDescription !==
                        rawDescription;

                    var displayDescription =
                        escapeSlHtml(
                            String(
                                log.display_description ||
                                rawDescription
                            ).trim()
                        );

                    var fullDescription =
                        escapeSlHtml(
                            rawFullDescription
                        );

                    var tableDetailId =
                        'sl-table-detail-' +
                        String(log.id || '');

                    var tableCopyId =
                        'sl-table-full-description-' +
                        String(log.id || '');

                    var createdDay = escapeSlHtml(log.created_at_day || '');
                    var createdTime = escapeSlHtml(log.created_at_time || '');

                    tableHtml +=
                        '<tr data-live-item-id="' +
                        escapeSlHtml(
                            log.id || ''
                        ) +
                        '" data-role="' +
                        escapeSlHtml(
                            role
                        ) +
                        '" data-action="' +
                        escapeSlHtml(
                            actionClass
                        ) +
                        '">';
                    tableHtml += '<td><span class="sl-id">' + idPadded + '</span></td>';
                    tableHtml += '<td><span class="sl-date-day">' + createdDay +
                        '</span><span class="sl-date-time">' + createdTime + '</span></td>';
                    tableHtml +=
                        '<td>' +
                        '<span class="badge-role ' +
                        roleBadgeClass +
                        '">' +
                        '<i class="fa-solid ' +
                        roleIcon +
                        '"></i>' +
                        escapeSlHtml(
                            role.charAt(0).toUpperCase() +
                            role.slice(1)
                        ) +
                        '</span>' +
                        '</td>';
                    tableHtml +=
                        '<td>' +
                        '<div class="table-primary">' +

                        '<span ' +
                        'class="patient-avatar patient-avatar-sm" ' +
                        'data-patient-avatar ' +
                        'data-patient-name="' + actorName + '"' +
                        '></span>' +

                        '<span class="sl-username">' +
                        actorName +
                        '</span>' +

                        '</div>' +
                        '</td>';
                    tableHtml +=
                        '<td>' +
                        '<span class="status-pill ' +
                        actionStatusClass +
                        '">' +
                        actionIconHtml +
                        actionLabel +
                        '</span>' +
                        archiveBadge +
                        '</td>';
                    tableHtml +=
                        '<td>' +
                        '<span class="table-tag table-tag-neutral">' +
                        '<i class="fa-solid fa-cube"></i>' +
                        moduleLabel +
                        '</span>' +
                        '</td>';
                    tableHtml +=
                        '<td>' +
                        '<div ' +
                        'class="global-show-more-disclosure" ' +
                        'data-show-more ' +
                        'data-show-more-mode="disclosure" ' +
                        'data-log-show-more ' +
                        'data-log-id="' +
                        escapeSlHtml(log.id || '') +
                        '"' +
                        '>' +

                        '<span ' +
                        'class="sl-desc global-text-clamp-2" ' +
                        'data-show-more-summary' +
                        '>' +
                        displayDescription +
                        '</span>' +

                        (
                            hasMeaningfulDetails ?
                            (
                                '<div ' +
                                'class="' +
                                'global-show-more-controls ' +
                                'global-show-more-controls-inline' +
                                '" ' +
                                'data-show-more-controls' +
                                '>' +

                                '<button ' +
                                'type="button" ' +
                                'class="' +
                                'global-show-more-btn ' +
                                'global-show-more-btn-inline' +
                                '" ' +
                                'data-show-more-button ' +
                                'data-mode="more" ' +
                                'aria-expanded="false" ' +
                                'aria-controls="' +
                                tableDetailId +
                                '"' +
                                '>' +

                                '<span data-show-more-text>' +
                                'Show more' +
                                '</span>' +

                                '<i ' +
                                'class="' +
                                'fa-solid ' +
                                'fa-chevron-down' +
                                '" ' +
                                'aria-hidden="true"' +
                                '></i>' +

                                '</button>' +

                                '</div>'
                            ) :
                            ''
                        ) +

                        '</div>' +
                        '</td>';

                    tableHtml += '</tr>';

                    if (hasMeaningfulDetails) {
                        tableHtml +=
                            '<tr ' +
                            'id="' + tableDetailId + '" ' +
                            'class="sl-log-detail-row" ' +
                            'data-show-more-panel ' +
                            'aria-hidden="true" ' +
                            'hidden' +
                            '>' +

                            '<td ' +
                            'colspan="7" ' +
                            'class="sl-log-detail-cell"' +
                            '>' +

                            '<div ' +
                            'class="' +
                            'global-info-item ' +
                            'global-info-item-wide ' +
                            'slide-down-fade-in ' +
                            'sl-log-detail-box' +
                            '"' +
                            '>' +

                            '<div class="global-info-copy">' +

                            '<span class="global-info-label">' +
                            'Full Description' +
                            '</span>' +

                            '<span ' +
                            'id="' + tableCopyId + '" ' +
                            'class="' +
                            'global-info-value ' +
                            'sl-log-detail-value' +
                            '"' +
                            '>' +
                            fullDescription +
                            '</span>' +

                            '</div>' +

                            '<button ' +
                            'type="button" ' +
                            'class="' +
                            'ui-btn ' +
                            'ui-btn-secondary ' +
                            'ui-btn-sm' +
                            '" ' +
                            'data-log-copy-target="' +
                            tableCopyId +
                            '"' +
                            '>' +

                            '<i class="fa-regular fa-copy"></i>' +
                            '<span>Copy</span>' +

                            '</button>' +

                            '</div>' +

                            '</td>' +

                            '</tr>';
                    }

                    var gridDetailId =
                        'sl-grid-detail-' +
                        String(log.id || '');

                    var gridDetailsHtml =
                        hasGridExpandableDescription ?
                        `
        <div
            id="${gridDetailId}"
            class="sl-grid-log-detail"
            data-show-more-panel
            aria-hidden="true"
            hidden
        >
            <span class="sl-desc">
                ${fullDescription}
            </span>
        </div>

        <div
            class="
                global-show-more-controls
                global-show-more-controls-inline
            "
            data-show-more-controls
        >
            <button
                type="button"
                class="
                    global-show-more-btn
                    global-show-more-btn-inline
                "
                data-show-more-button
                data-mode="more"
                aria-expanded="false"
                aria-controls="${gridDetailId}"
            >
                <span data-show-more-text>
                    Show more
                </span>

                <i
                    class="
                        fa-solid
                        fa-chevron-down
                        global-accordion-chevron
                    "
                    aria-hidden="true"
                ></i>
            </button>
        </div>
    ` :
                        '';

                    gridHtml += `
    <article
    class="table-record-card"
    data-live-item-id="${escapeSlHtml(log.id || '')}"
    data-role="${escapeSlHtml(role)}"
    data-action="${escapeSlHtml(actionClass)}">
    <div class="table-record-card-layout">
        <div class="table-record-content">

            <div class="table-record-header">
                <div class="table-primary">
                    <strong>
                        <span class="sl-id">
                            ${idPadded}
                        </span>
                    </strong>
                </div>

                <span class="status-pill ${actionStatusClass}">

                    ${actionIconHtml}

                    ${actionLabel}
                </span>
            </div>

            <div class="table-primary">

                <span
                    class="patient-avatar patient-avatar-sm"
                    data-patient-avatar
                    data-patient-name="${actorName}"
                ></span>

                <span class="sl-username">
                    ${actorName}
                </span>

            </div>

            <div class="table-record-meta">

                <div class="table-record-row">
                    <span class="table-record-label">
                        Timestamp
                    </span>

                    <span class="table-record-value">
                        ${createdDay} · ${createdTime}
                    </span>
                </div>

                <div class="table-record-row">
                    <span class="table-record-label">
                        Role
                    </span>

                    <span class="table-record-value">
                        <span class="badge-role ${roleBadgeClass}">
                            <i class="fa-solid ${roleIcon}"></i>
                            ${escapeSlHtml(
                role.charAt(0).toUpperCase() +
                role.slice(1)
            )
                }
                        </span>
                    </span>
                </div>

                <div class="table-record-row">
                    <span class="table-record-label">
                        Module
                    </span>

                    <span class="table-record-value">
                        <span class="table-tag table-tag-neutral">
                            <i class="fa-solid fa-cube"></i>
                            ${moduleLabel}
                        </span>
                    </span>
                </div>

                <div
    class="table-record-row"
    data-system-log-description-row>
    <span class="table-record-label">
        Description
    </span>

    <div class="table-record-value">
        <div
            class="global-show-more-disclosure"
            data-show-more
            data-show-more-mode="disclosure"
            data-log-show-more
            data-log-id="${escapeSlHtml(log.id || '')}">
            <span
    class="sl-desc global-text-clamp-2"
    data-show-more-summary>
    ${displayDescription}
</span>

            ${gridDetailsHtml}
        </div>
    </div>
</div>

            </div>

            ${archiveBadge}
</div>
        </div>
    </article>
`;
                });

                if (tableBody) {
                    tableBody.innerHTML = tableHtml;

                    window.PatientUI?.initAvatars(
                        tableBody
                    );
                }

                window.ShowMore?.init(tableBody);

                if (gridBody) {
                    gridBody.innerHTML =
                        gridHtml;

                    window.PatientUI?.initAvatars(
                        gridBody
                    );

                    window.ShowMore?.init(
                        gridBody
                    );
                }
            }

            function slRenderPagebar(p) {
                if (!p) {
                    return;
                }

                slCurrentPagination = {
                    total: Number(
                        p.total
                    ) || 0,

                    from: p.from ?? null,

                    to: p.to ?? null,

                    current_page: Number(
                        p.current_page
                    ) || 1,

                    last_page: Number(
                        p.last_page
                    ) || 1,

                    per_page: Number(
                            p.per_page
                        ) ||
                        Number(
                            slState.perPage
                        ) ||
                        10,
                };

                window.renderGlobalPagination?.({
                    currentPage: Number(
                        p.current_page
                    ) || 1,

                    lastPage: Number(
                        p.last_page
                    ) || 1,

                    total: Number(
                        p.total
                    ) || 0,

                    from: p.from ?? null,

                    to: p.to ?? null,

                    containers: [
                        document.getElementById(
                            'systemLogsPaginationTop'
                        ),
                        document.getElementById(
                            'systemLogsPaginationBottom'
                        ),
                    ],

                    bars: [
                        document.getElementById(
                            'systemLogsPaginationTopBar'
                        ),
                        document.getElementById(
                            'systemLogsPaginationBottomBar'
                        ),
                    ],

                    infoElements: [
                        document.getElementById(
                            'systemLogsPageInfoTop'
                        ),
                        document.getElementById(
                            'systemLogsPageInfoBottom'
                        ),
                    ],

                    itemLabel: 'entries',

                    onPageChange(page) {
                        clearSlExpandedLogs();

                        slState.page =
                            page;

                        slFetch();
                    },
                });

                const perPageSelect =
                    document.getElementById(
                        'perPageSelect'
                    );

                if (
                    perPageSelect &&
                    p.per_page
                ) {
                    perPageSelect.value =
                        String(p.per_page);

                    window
                        .syncGlobalPageSizeSelect?.(
                            perPageSelect,
                            p.per_page
                        );
                }

                const badge =
                    document.getElementById(
                        'entryBadge'
                    );

                if (badge) {
                    badge.textContent =
                        `${slOverallTotal} ${slOverallTotal === 1
                    ? 'entry'
                    : 'entries'
                }`;
                }
            }

            function showEmptyState(query) {
                var listView =
                    document.getElementById(
                        'slListView'
                    );

                var gridView =
                    document.getElementById(
                        'slGridView'
                    );

                if (listView) {
                    listView.hidden = true;
                }

                if (gridView) {
                    gridView.hidden = true;
                }

                if (query) {
                    window.EmptyState?.renderSearch({
                        host: '#emptyState',

                        input: '#slSearch',

                        query,

                        message: 'Try a different name, action, module, or user.',
                    });

                    return;
                }

                const emptyStatuses =
                    String(
                        slState.status || 'all'
                    )
                    .split(',')
                    .map(value =>
                        value.trim().toLowerCase()
                    )
                    .filter(Boolean);

                const emptyRoles =
                    String(
                        slState.role || 'all'
                    )
                    .split(',')
                    .map(value =>
                        value.trim().toLowerCase()
                    )
                    .filter(Boolean);

                const archivedOnly =
                    emptyStatuses.length === 1 &&
                    emptyStatuses[0] === 'archived';

                const allRoles =
                    emptyRoles.includes('all');

                if (
                    archivedOnly &&
                    !hasActiveSlFilters() &&
                    allRoles
                ) {
                    window.EmptyState?.render({
                        host: '#emptyState',

                        icon: 'fa-box-archive',

                        title: 'No archived logs yet',

                        message: 'Archive older records to keep the active log view easier to manage.',
                    });

                    return;
                }

                if (hasActiveSlFilters()) {
                    window.EmptyState?.render({
                        host: '#emptyState',

                        icon: 'fa-filter-circle-xmark',

                        title: 'No logs match the selected filters',

                        message: 'Try adjusting the filter panel or clearing all filters.',

                        actionHtml: `
                <button
                    type="button"
                    class="empty-state-btn"
                    data-empty-action="clear-filters"
                >
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                    Clear filters
                </button>
            `,
                    });

                    document
                        .querySelector(
                            '#emptyState [data-empty-action="clear-filters"]'
                        )
                        ?.addEventListener(
                            'click',
                            clearOnlySlFilters
                        );

                    return;
                }

                window.EmptyState?.render({
                    host: '#emptyState',

                    icon: 'fa-clipboard-list',

                    title: 'No system logs yet',

                    message: 'Activity will appear here once users interact with the system.',
                });
            }

            document.addEventListener('click', async function(event) {
                const button =
                    event.target.closest(
                        '[data-log-copy-target]'
                    );

                if (!button) {
                    return;
                }

                const target =
                    document.getElementById(
                        button.dataset
                        .logCopyTarget
                    );

                const text =
                    target?.textContent
                    ?.trim();

                if (!text) {
                    return;
                }

                try {
                    await navigator
                        .clipboard
                        .writeText(text);

                    window.showToast?.({
                        type: 'success',
                        title: 'Copied',
                        message: 'Full description copied to clipboard.',
                    });
                } catch (error) {
                    console.error(
                        'Unable to copy log description.',
                        error
                    );

                    window.showToast?.({
                        type: 'error',
                        title: 'Copy failed',
                        message: 'Unable to copy the description.',
                    });
                }
            });

            window.openSlFilterPanel = openSlFilterPanel;
            window.closeSlFilterPanel = closeSlFilterPanel;
            window.applySlFilters = applySlFilters;
            window.clearOnlySlFilters = clearOnlySlFilters;
            window.clearSlFilterPanelDraft = clearSlFilterPanelDraft;
            window.setSlQuickDate = setSlQuickDate;
            window.openSlArchiveModal = openSlArchiveModal;
            window.submitSlArchiveModal = submitSlArchiveModal;
            window.handleSlExportButtonClick = handleSlExportButtonClick;
            window.submitSlExportModal = submitSlExportModal;
        });
    </script>
@endsection
