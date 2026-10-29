@extends('layouts.app')

@section('layout-role', 'admin')

@section('title', 'Session Dashboard')

@section('content')
    <main id="mainContent" class="app-page-shell pb-11">
        <div class="w-full">
            <section class="page-banner mb-4">
                <div class="page-banner-inner">
                    <div class="page-banner-copy">
                        <h1 class="page-title">Session Dashboard</h1>
                    </div>

                </div>
            </section>

            <section id="statCards" class="stat-grid">
                <article class="stat-card s-crimson">
                    <div class="stat-card-info">
                        <span class="stat-label">
                            Active Sessions
                        </span>

                        <span id="activeSessionsStat" class="stat-num">
                            {{ $stats['total_sessions'] ?? 0 }}
                        </span>

                        <div class="stat-footer">
                            All currently tracked browser sessions
                        </div>
                    </div>

                    <div class="stat-icon">
                        <i class="fa-solid fa-signal"></i>
                    </div>
                </article>

                <article class="stat-card s-purple">
                    <div class="stat-card-info">
                        <span class="stat-label">
                            Active Users
                        </span>

                        <span id="activeUsersStat" class="stat-num">
                            {{ $stats['active_users'] ?? 0 }}
                        </span>

                        <div class="stat-footer">
                            Accounts with active sessions
                        </div>
                    </div>

                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </article>

                <article class="stat-card s-amber">
                    <div class="stat-card-info">
                        <span class="stat-label">
                            Other Sessions
                        </span>

                        <span id="otherSessionsStat" class="stat-num">
                            {{ $stats['other_sessions'] ?? 0 }}
                        </span>

                        <div class="stat-footer">
                            Sessions outside the current admin session
                        </div>
                    </div>

                    <div class="stat-icon">
                        <i class="fa-solid fa-window-restore"></i>
                    </div>
                </article>

                <article class="stat-card s-blue">
                    <div class="stat-card-info">
                        <span class="stat-label">
                            Active Devices
                        </span>

                        <span id="activeDevicesStat" class="stat-num">
                            {{ $stats['active_devices'] ?? 0 }}
                        </span>

                        <div class="stat-footer">
                            Devices currently connected to the system
                        </div>
                    </div>

                    <div class="stat-icon">
                        <i class="fa-solid fa-display"></i>
                    </div>
                </article>
            </section>

            <section class="table-card">
                <div class="table-toolbar">
                    <div class="table-toolbar-search">
                        <div class="voice-search-row">
                            <x-search-bar id="sessionSearchInput" name="search" :value="$filters['search'] ?? ''"
                                placeholder="Search user, email, IP, or browser" />
                            <x-voice-input target="#sessionSearchInput" status-id="sessionSearchVoiceStatus"
                                label="Use voice search" title="Voice search" />
                        </div>
                    </div>

                    @php
                        $sessionRoleFilterOptions = [
                            [
                                'value' => 'all',
                                'label' => 'All Roles',
                                'icon' => 'fa-users',
                                'count' => $stats['total_sessions'] ?? 0,
                            ],
                            [
                                'value' => 'admin',
                                'label' => 'Admin',
                                'icon' => 'fa-user-shield',
                                'count' => $stats['admin_sessions'] ?? 0,
                            ],
                            [
                                'value' => 'dentist',
                                'label' => 'Dentist',
                                'icon' => 'fa-user-doctor',
                                'count' => $stats['dentist_sessions'] ?? 0,
                            ],
                            [
                                'value' => 'patient',
                                'label' => 'Patient',
                                'icon' => 'fa-user',
                                'count' => $stats['patient_sessions'] ?? 0,
                            ],
                        ];
                    @endphp

                    <div class="table-toolbar-actions">
                        <x-filter-select id="sessionRoleFilter" name="session_roles" label="User Role" value="all"
                            :options="$sessionRoleFilterOptions" callback="handleSessionRoleFilter" menu-align="right" :searchable="true"
                            :multiple="true" search-placeholder="Search role..." />

                        <button type="button" id="sessionFilterBtn" class="global-filter-btn" aria-pressed="false">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Filter</span>
                            <span id="sessionFilterBadge" class="filter-badge hidden"></span>
                        </button>

                        <button type="button" id="sessionFilterResetBtn" class="global-filter-reset-btn hidden"
                            title="Reset filters" aria-label="Reset session filters">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>

                        <x-view-toggle id="sessionViewToggle" root="#mainContent"
                            storage-key="admin_session_management_view" list-view="#sessionAdminList"
                            grid-view="#sessionAdminGridView" list-label="List" grid-label="Grid" />
                    </div>
                </div>

                <div id="sessionTopPagebarWrap" {{ $sessions->count() === 0 ? 'style=display:none;' : '' }}>
                    <x-pagination-bar id="sessionAdminTopPagebar" infoId="sessionAdminTopPagebarInfo"
                        paginationId="sessionAdminTopPaginationNav" position="top" :showEntries="true"
                        pageSizeId="sessionPageSizeSelect" pageSizeCallback="window.changeSessionPageSize"
                        pageSizeValue="{{ $perPage ?? 10 }}" pageSizeLabel="entries" label="sessions" :total="$sessions->total()"
                        :from="$sessions->firstItem() ?? 0" :to="$sessions->lastItem() ?? 0" />
                </div>

                <div id="sessionAdminList" class="table-list-view"
                    {{ $sessions->count() === 0 ? 'style=display:none;' : '' }}>
                    <div
                        class="table-list-header grid grid-cols-[minmax(0,1.65fr)_minmax(0,.65fr)_minmax(0,.8fr)_minmax(0,1.05fr)_minmax(0,1fr)_minmax(0,1.15fr)] items-center gap-3">
                        <div>User</div>
                        <div>Role</div>
                        <div>Account Status</div>
                        <div>Device</div>
                        <div>IP and Last Activity</div>
                        <div class="text-right">Actions</div>
                    </div>

                    <div data-session-list>
                        @foreach ($sessions as $session)
                            @php
                                $roleClass = match ($session->role_slug) {
                                    'admin', 'super_admin' => 'role-admin',
                                    'dentist' => 'role-dentist',
                                    'patient' => 'role-patient',
                                    default => 'role-none',
                                };

                                $filterRole = in_array($session->role_slug, ['admin', 'super_admin'], true)
                                    ? 'admin'
                                    : strtolower((string) $session->role_slug);

                                $statusClass =
                                    strtolower((string) $session->user_status) === 'active' ? 's-active' : 's-inactive';
                            @endphp

                            <div class="table-list-row grid grid-cols-[minmax(0,1.65fr)_minmax(0,.65fr)_minmax(0,.8fr)_minmax(0,1.05fr)_minmax(0,1fr)_minmax(0,1.15fr)] items-center gap-3 px-5 py-3 {{ $session->is_current ? 'bg-[var(--status-approved-bg)]' : '' }}"
                                data-session-list-item data-session-key="{{ $session->reference }}"
                                data-live-item-id="{{ $session->reference }}" data-role="{{ $filterRole }}"
                                data-device="{{ strtolower((string) $session->device_type) }}"
                                data-current="{{ $session->is_current ? '1' : '0' }}"
                                data-last-activity="{{ $session->last_activity_at->getTimestamp() }}">

                                <div class="min-w-0">
                                    <div class="table-primary w-full">
                                        <span class="patient-avatar patient-avatar-sm" data-patient-avatar
                                            data-patient-name="{{ $session->user_name }}"></span>

                                        <div class="min-w-0">
                                            <strong class="block truncate">{{ $session->user_name }}</strong>
                                            <span class="global-info-subvalue truncate">
                                                {{ $session->user_email }}
                                            </span>

                                            @if ($session->is_current)
                                                <span class="status-pill s-active mt-1">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Current Admin Session
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <span class="badge-role {{ $roleClass }}">
                                        {{ $session->role_label }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <span class="status-pill {{ $statusClass }}">
                                        {{ ucfirst((string) $session->user_status) }}
                                    </span>
                                </div>

                                <div class="global-info-copy min-w-0">
                                    <span class="global-info-value mt-0 truncate">
                                        @if ($session->device_type === 'mobile')
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                        @elseif ($session->device_type === 'tablet')
                                            <i class="fa-solid fa-tablet-screen-button"></i>
                                        @else
                                            <i class="fa-solid fa-desktop"></i>
                                        @endif
                                        {{ $session->device_label }}
                                    </span>
                                    <span class="global-info-subvalue truncate">
                                        {{ $session->browser_label }} · {{ $session->os_label }}
                                    </span>
                                </div>

                                <div class="global-info-copy min-w-0">
                                    <span class="global-info-value mt-0 truncate">{{ $session->ip_address }}</span>
                                    <span class="global-info-subvalue truncate">{{ $session->last_activity_label }}</span>
                                </div>

                                <div class="ui-action-group justify-end">
                                    @if (!$session->is_current)
                                        <button type="button"
                                            class="ui-action-btn ui-action-warning trigger-session-logout"
                                            aria-label="Log out this session" data-tooltip="Log out this session"
                                            data-tooltip-tone="neutral"
                                            data-action="{{ route('admin.session_management.destroy_session', $session->reference) }}"
                                            data-title="Terminate This Session?" data-user="{{ $session->user_name }}"
                                            data-email="{{ $session->user_email }}"
                                            data-device="{{ $session->device_label }} ({{ $session->browser_label }})"
                                            data-type="single">
                                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                        </button>
                                    @else
                                        <span class="status-pill status-pending"
                                            data-tooltip="Current session is protected" data-tooltip-tone="neutral">
                                            <i class="fa-solid fa-shield-halved"></i>
                                            Protected
                                        </span>
                                    @endif

                                    <button type="button"
                                        class="ui-action-btn {{ $session->is_current ? 'ui-action-neutral' : 'ui-action-delete' }} trigger-session-logout"
                                        aria-label="{{ $session->is_current ? 'Log out other sessions' : 'Log out all sessions' }}"
                                        data-tooltip="{{ $session->is_current ? 'Log out other sessions' : 'Log out all sessions' }}"
                                        data-tooltip-tone="{{ $session->is_current ? 'neutral' : 'delete' }}"
                                        data-action="{{ route('admin.session_management.destroy_user_sessions', $session->user_id) }}"
                                        data-title="{{ $session->is_current ? 'Log Out Other Sessions?' : 'Log Out All Sessions?' }}"
                                        data-user="{{ $session->user_name }}" data-email="{{ $session->user_email }}"
                                        data-device="{{ $session->is_current ? 'All other active devices except this current session.' : 'All active devices logged into this account.' }}"
                                        data-type="all">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="sessionAdminGridView" class="table-grid-view" hidden>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" data-session-grid>
                        @foreach ($sessions as $session)
                            @php
                                $roleClass = match ($session->role_slug) {
                                    'admin', 'super_admin' => 'role-admin',
                                    'dentist' => 'role-dentist',
                                    'patient' => 'role-patient',
                                    default => 'role-none',
                                };

                                $filterRole = in_array($session->role_slug, ['admin', 'super_admin'], true)
                                    ? 'admin'
                                    : strtolower((string) $session->role_slug);

                                $statusClass =
                                    strtolower((string) $session->user_status) === 'active' ? 's-active' : 's-inactive';
                            @endphp

                            <article class="table-record-card h-full {{ $session->is_current ? 'is-active' : '' }}"
                                data-session-grid-item data-live-item-id="{{ $session->reference }}"
                                data-session-key="{{ $session->reference }}" data-role="{{ $filterRole }}"
                                data-device="{{ strtolower((string) $session->device_type) }}"
                                data-current="{{ $session->is_current ? '1' : '0' }}"
                                data-last-activity="{{ $session->last_activity_at->getTimestamp() }}">
                                <div class="table-record-card-layout">
                                    <div class="table-record-content">
                                        <div class="table-record-header">
                                            <div class="table-primary">
                                                <span class="patient-avatar patient-avatar-md" data-patient-avatar
                                                    data-patient-name="{{ $session->user_name }}"></span>

                                                <div class="min-w-0">
                                                    <h3 class="table-record-title">{{ $session->user_name }}</h3>
                                                    <span class="global-info-subvalue">{{ $session->user_email }}</span>
                                                </div>
                                            </div>

                                            @if ($session->is_current)
                                                <span class="status-pill s-active">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Current Admin Session
                                                </span>
                                            @endif
                                        </div>

                                        <div class="table-record-meta">
                                            <div class="table-record-row">
                                                <span class="table-record-label">Role</span>
                                                <span class="table-record-value">
                                                    <span class="badge-role {{ $roleClass }}">
                                                        {{ $session->role_label }}
                                                    </span>
                                                </span>
                                            </div>

                                            <div class="table-record-row">
                                                <span class="table-record-label">Account Status</span>
                                                <span class="table-record-value">
                                                    <span class="status-pill {{ $statusClass }}">
                                                        {{ ucfirst((string) $session->user_status) }}
                                                    </span>
                                                </span>
                                            </div>

                                            <div class="table-record-row">
                                                <span class="table-record-label">Device</span>
                                                <span class="table-record-value">
                                                    {{ $session->device_label }} · {{ $session->browser_label }}
                                                </span>
                                            </div>

                                            <div class="table-record-row">
                                                <span class="table-record-label">IP Address</span>
                                                <span class="table-record-value">{{ $session->ip_address }}</span>
                                            </div>

                                            <div class="table-record-row">
                                                <span class="table-record-label">Last Activity</span>
                                                <span
                                                    class="table-record-value">{{ $session->last_activity_label }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-record-actions">
                                        <div class="ui-action-group">
                                            @if (!$session->is_current)
                                                <button type="button"
                                                    class="ui-action-btn ui-action-warning trigger-session-logout"
                                                    aria-label="Log out this session" data-tooltip="Log out this session"
                                                    data-tooltip-tone="neutral"
                                                    data-action="{{ route('admin.session_management.destroy_session', $session->reference) }}"
                                                    data-title="Terminate This Session?"
                                                    data-user="{{ $session->user_name }}"
                                                    data-email="{{ $session->user_email }}"
                                                    data-device="{{ $session->device_label }} ({{ $session->browser_label }})"
                                                    data-type="single">
                                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                                </button>
                                            @else
                                                <span class="status-pill status-pending"
                                                    data-tooltip="Current session is protected"
                                                    data-tooltip-tone="neutral">
                                                    <i class="fa-solid fa-shield-halved"></i>
                                                    Protected
                                                </span>
                                            @endif

                                            <button type="button"
                                                class="ui-action-btn {{ $session->is_current ? 'ui-action-neutral' : 'ui-action-delete' }} trigger-session-logout"
                                                aria-label="{{ $session->is_current ? 'Log out other sessions' : 'Log out all sessions' }}"
                                                data-tooltip="{{ $session->is_current ? 'Log out other sessions' : 'Log out all sessions' }}"
                                                data-tooltip-tone="{{ $session->is_current ? 'neutral' : 'delete' }}"
                                                data-action="{{ route('admin.session_management.destroy_user_sessions', $session->user_id) }}"
                                                data-title="{{ $session->is_current ? 'Log Out Other Sessions?' : 'Log Out All Sessions?' }}"
                                                data-user="{{ $session->user_name }}"
                                                data-email="{{ $session->user_email }}"
                                                data-device="{{ $session->is_current ? 'All other active devices except this current session.' : 'All active devices logged into this account.' }}"
                                                data-type="all">
                                                <i class="fa-solid fa-right-from-bracket"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="empty-state" id="sessionEmptyState"
                    {{ $sessions->count() > 0 ? 'style=display:none;' : '' }}>
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <h3 class="empty-state-title" id="emptyTitle">
                        @if (!empty($filters['search']))
                            No Matching Sessions
                        @else
                            No Active Sessions
                        @endif
                    </h3>

                    <p class="empty-state-sub" id="emptySub">
                        @if (!empty($filters['search']))
                            No active sessions match your search.
                            Try using a different name, email, IP address, or browser.
                        @else
                            There are currently no active user sessions to display.
                        @endif
                    </p>
                    <button type="button" class="empty-state-btn" id="clearSearchBtn">
                        <i class="fa-solid fa-xmark"></i>
                        Clear search
                    </button>
                </div>

                <div id="sessionBottomPagebarWrap" {{ $sessions->count() === 0 ? 'style=display:none;' : '' }}>
                    <x-pagination-bar id="sessionAdminBottomPagebar" infoId="sessionAdminBottomPagebarInfo"
                        paginationId="sessionAdminBottomPaginationNav" position="bottom" :showEntries="false"
                        label="sessions" :total="$sessions->total()" :from="$sessions->firstItem() ?? 0" :to="$sessions->lastItem() ?? 0" />
                </div>
            </section>
        </div>
    </main>

    <div id="sessionConfirmModal" class="ui-modal" role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="confirmModalTitle">

        <form id="sessionConfirmForm" method="POST" action="" class="ui-modal-card modal-sm">

            @csrf
            @method('DELETE')

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 id="confirmModalTitle" class="modal-title">
                            Terminate Active Session?
                        </h3>

                        <p class="modal-subtitle">
                            Security verification confirmation
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-modal-close="sessionConfirmModal"
                    aria-label="Close session confirmation">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="global-confirm-alert">
                    <div>
                        <strong id="confirmModalTargetName">
                            User Account Session
                        </strong>

                        <span id="confirmModalTargetEmail">
                            user@example.com
                        </span>
                    </div>
                </div>

                <div class="confirmed-modal-schedule-note">
                    <i class="fa-solid fa-circle-info"></i>

                    <span id="confirmModalDevice">
                        Device information will appear here.
                    </span>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-modal-close="sessionConfirmModal">
                    Cancel
                </button>

                <button type="submit" class="ui-btn ui-btn-danger">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    Confirm Log Out
                </button>
            </div>
        </form>
    </div>

    <form data-ui-submit="action-44daeba3893a" id="sessionFilterForm">
        <x-filter-drawer id="filterModal" title="Filters" closeCallback="window.cancelSessionFilters()"
            clearCallback="window.clearSessionFilterDraft()" clearLabel="Clear Filters"
            cancelCallback="window.cancelSessionFilters()" cancelLabel="Cancel"
            applyCallback="window.applySessionFilters()" applyLabel="Show 0 results" resultsId="sessionShowResultsText">

            <div id="sessionActiveFiltersSection" class="filter-active-section hidden">
                <div class="filter-active-header">
                    <span class="filter-active-title">
                        Active Filters
                    </span>

                    <button id="sessionClearAllChipsBtn" type="button"
                        class="filter-clear-all ui-btn ui-btn-secondary ui-btn-sm">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Clear All</span>
                    </button>
                </div>

                <div id="sessionActiveChipsContainer" class="active-filters-container"></div>
            </div>

            <x-filter-group title="Sort By">
                <div class="filter-chip-row" id="fSortGroup">
                    <label class="choice-chip">
                        <input type="radio" name="sort" value="recent" class="filter-input radio-red chip-radio"
                            {{ ($filters['sort'] ?? 'recent') === 'recent' ? 'checked' : '' }}>
                        <span><i class="fa-solid fa-arrow-down-short-wide"></i> Most Recent Activity</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="sort" value="oldest" class="filter-input radio-red chip-radio"
                            {{ ($filters['sort'] ?? '') === 'oldest' ? 'checked' : '' }}>
                        <span><i class="fa-solid fa-arrow-up-wide-short"></i> Oldest Activity</span>
                    </label>
                </div>
            </x-filter-group>


            <div class="filter-soft-divider"></div>

            <x-filter-group title="Device Type">
                <div class="filter-chip-row">
                    <label class="choice-chip">
                        <input type="radio" name="device" value="all" class="filter-input radio-red chip-radio"
                            {{ ($filters['device'] ?? 'all') === 'all' ? 'checked' : '' }}>
                        <span>All Devices</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="device" value="desktop" class="filter-input radio-red chip-radio"
                            {{ ($filters['device'] ?? '') === 'desktop' ? 'checked' : '' }}>
                        <span><i class="fa-solid fa-desktop"></i> Desktop</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="device" value="mobile" class="filter-input radio-red chip-radio"
                            {{ ($filters['device'] ?? '') === 'mobile' ? 'checked' : '' }}>
                        <span><i class="fa-solid fa-mobile-screen-button"></i> Mobile</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="device" value="tablet" class="filter-input radio-red chip-radio"
                            {{ ($filters['device'] ?? '') === 'tablet' ? 'checked' : '' }}>
                        <span><i class="fa-solid fa-tablet-screen-button"></i> Tablet</span>
                    </label>
                </div>
            </x-filter-group>

            <div class="filter-soft-divider"></div>

            <x-filter-group title="Session Scope">
                <div class="filter-chip-row">
                    <label class="choice-chip">
                        <input type="radio" name="scope" value="all" class="filter-input radio-red chip-radio"
                            {{ ($filters['scope'] ?? 'all') === 'all' ? 'checked' : '' }}>
                        <span>All Sessions</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="scope" value="current" class="filter-input radio-red chip-radio"
                            {{ ($filters['scope'] ?? '') === 'current' ? 'checked' : '' }}>
                        <span><i class="fa-solid fa-user-shield"></i> Current Session Only</span>
                    </label>
                    <label class="choice-chip">
                        <input type="radio" name="scope" value="others" class="filter-input radio-red chip-radio"
                            {{ ($filters['scope'] ?? '') === 'others' ? 'checked' : '' }}>
                        <span><i class="fa-solid fa-users-gear"></i> Other Active Sessions</span>
                    </label>
                </div>
            </x-filter-group>
        </x-filter-drawer>
    </form>

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('sessionSearchInput');
            const sessionList = document.getElementById('sessionAdminList');
            const sessionListBody = document.querySelector('#sessionAdminList [data-session-list]');
            const sessionGrid = document.querySelector('#sessionAdminGridView [data-session-grid]');
            const emptyState = document.getElementById('sessionEmptyState');
            const topPagebarWrap = document.getElementById('sessionTopPagebarWrap');
            const bottomPagebarWrap = document.getElementById('sessionBottomPagebarWrap');
            const topPaginationContainer = document.getElementById('sessionAdminTopPaginationNav');
            const bottomPaginationContainer = document.getElementById('sessionAdminBottomPaginationNav');
            const topPaginationInfo = document.getElementById('sessionAdminTopPagebarInfo');
            const bottomPaginationInfo = document.getElementById('sessionAdminBottomPagebarInfo');
            const topPaginationBar = document.getElementById('sessionAdminTopPagebar');
            const bottomPaginationBar = document.getElementById('sessionAdminBottomPagebar');

            const SESSION_MANAGEMENT_CHECK_URL =
                @json(route('admin.session_management.check'));

            const SESSION_MANAGEMENT_INDEX_URL =
                @json(route('admin.session_management.index'));

            function getSessionItems() {
                return document.querySelectorAll(
                    '#sessionAdminList ' +
                    '[data-session-list-item]'
                );
            }

            function getSessionGridItems() {
                return document.querySelectorAll(
                    '#sessionAdminGridView ' +
                    '[data-session-grid-item]'
                );
            }

            function goToSessionPage(page) {
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                window.location.href = url.toString();
            }

            window.changeSessionPageSize = function(size) {
                const url = new URL(window.location.href);

                url.searchParams.set('per_page', size);
                url.searchParams.delete('page');

                window.location.href = url.toString();
            };

            if (typeof window.renderGlobalPagination === 'function') {
                window.renderGlobalPagination({
                    currentPage: {{ $sessions->currentPage() }},
                    lastPage: {{ $sessions->lastPage() }},
                    total: {{ $sessions->total() }},
                    from: {{ $sessions->firstItem() ?? 0 }},
                    to: {{ $sessions->lastItem() ?? 0 }},

                    containers: [
                        topPaginationContainer,
                        bottomPaginationContainer
                    ],

                    infoElements: [
                        topPaginationInfo,
                        bottomPaginationInfo
                    ],

                    bars: [
                        topPaginationBar,
                        bottomPaginationBar
                    ],

                    itemLabel: 'sessions',

                    onPageChange: goToSessionPage
                });
            }

            const emptyTitle = document.getElementById('emptyTitle');
            const emptySub = document.getElementById('emptySub');
            const clearSearchBtn = document.getElementById('clearSearchBtn');

            const filterForm = document.getElementById('sessionFilterForm');
            const filterButton = document.getElementById('sessionFilterBtn');
            const filterResetButton = document.getElementById('sessionFilterResetBtn');

            const confirmForm = document.getElementById('sessionConfirmForm');
            const confirmTitle = document.getElementById('confirmModalTitle');
            const confirmName = document.getElementById('confirmModalTargetName');
            const confirmEmail = document.getElementById('confirmModalTargetEmail');
            const confirmDevice = document.getElementById('confirmModalDevice');

            const sessionFilterBadge =
                document.getElementById('sessionFilterBadge');

            const activeFiltersSection =
                document.getElementById('sessionActiveFiltersSection');

            const activeChipsContainer =
                document.getElementById('sessionActiveChipsContainer');

            const clearAllChipsBtn =
                document.getElementById('sessionClearAllChipsBtn');

            const showResultsText =
                document.getElementById('sessionShowResultsText');

            const SESSION_ROLE_VALUES = ['admin', 'dentist', 'patient'];

            function normalizeSessionRoleValues(values) {
                const rawValues = Array.isArray(values) ?
                    values :
                    String(values || '').split(',');

                const normalized = Array.from(
                    new Set(
                        rawValues
                        .map(value => String(value).trim().toLowerCase())
                        .filter(Boolean)
                    )
                );

                if (normalized.includes('all')) {
                    return ['all'];
                }

                const validRoles = normalized.filter(value =>
                    SESSION_ROLE_VALUES.includes(value)
                );

                return validRoles.length ? validRoles : ['all'];
            }

            const roleQuery = new URL(window.location.href)
                .searchParams
                .get('roles');

            const initialRoleValues = normalizeSessionRoleValues(
                roleQuery || @json($filters['role'] ?? 'all')
            );

            let sessionFilterState = {
                sort: filterForm?.querySelector('input[name="sort"]:checked')?.value || 'recent',
                roles: initialRoleValues,
                device: filterForm?.querySelector('input[name="device"]:checked')?.value || 'all',
                scope: filterForm?.querySelector('input[name="scope"]:checked')?.value || 'all'
            };

            let sessionFilterDraft = {
                ...sessionFilterState,
                roles: [...sessionFilterState.roles]
            };

            function syncSessionFilterInputs() {
                if (!filterForm) return;

                ['sort', 'device', 'scope'].forEach(name => {
                    const value = sessionFilterDraft[name];
                    const input = filterForm.querySelector(
                        `input[name="${name}"][value="${value}"]`
                    );

                    if (input) {
                        input.checked = true;
                    }
                });
            }

            function hasSpecificSessionRoles(state = sessionFilterState) {
                return !normalizeSessionRoleValues(state.roles).includes('all');
            }

            function getSessionFilterCount(state = sessionFilterState) {
                let count = 0;

                if (state.sort !== 'recent') count++;
                if (state.device !== 'all') count++;
                if (state.scope !== 'all') count++;

                return count;
            }

            function updateSessionFilterButton() {
                const count = getSessionFilterCount();

                if (sessionFilterBadge) {
                    sessionFilterBadge.textContent = count > 0 ? String(count) : '';
                    sessionFilterBadge.classList.toggle('hidden', count === 0);
                    sessionFilterBadge.classList.toggle('show', count > 0);
                }

                filterButton?.classList.toggle('has-filters', count > 0);
                filterButton?.setAttribute('aria-pressed', count > 0 ? 'true' : 'false');

                filterResetButton?.classList.toggle('hidden', count === 0);
                filterResetButton?.classList.toggle('show', count > 0);
            }

            function normalizeSessionDeviceValue(value) {
                const normalized =
                    String(value || '')
                    .trim()
                    .toLowerCase();

                if (['phone', 'mobile', 'smartphone'].includes(normalized)) {
                    return 'mobile';
                }

                if (['tablet', 'ipad'].includes(normalized)) {
                    return 'tablet';
                }

                if (['desktop', 'laptop', 'computer', 'pc'].includes(normalized)) {
                    return 'desktop';
                }

                return normalized;
            }

            function getSessionLastActivity(item) {
                const timestamp = Number.parseInt(
                    item?.dataset?.lastActivity || '0',
                    10
                );

                return Number.isFinite(timestamp) ?
                    timestamp :
                    0;
            }

            function buildSessionUrl() {
                const url = new URL(window.location.href);
                url.searchParams.delete('page');

                const selectedRoles = normalizeSessionRoleValues(
                    sessionFilterState.roles
                );

                url.searchParams.delete('role');

                if (selectedRoles.includes('all')) {
                    url.searchParams.delete('roles');
                } else {
                    url.searchParams.set('roles', selectedRoles.join(','));
                }

                const filters = {
                    search: searchInput?.value?.trim() || '',
                    sort: sessionFilterState.sort,
                    device: sessionFilterState.device,
                    scope: sessionFilterState.scope,
                };

                Object.entries(filters).forEach(([key, value]) => {
                    if (value && !['recent', 'all'].includes(value)) {
                        url.searchParams.set(key, value);
                        return;
                    }

                    if (key === 'search' && value !== '') {
                        url.searchParams.set(key, value);
                        return;
                    }

                    url.searchParams.delete(key);
                });

                return url;
            }

            async function refreshSessionManagement(
                payload,
                context
            ) {
                const root =
                    document.getElementById(
                        'mainContent'
                    );

                const beforeIds =
                    window
                    .GlobalLiveUpdate
                    ?.collectIds(root) ||
                    new Set();

                const url =
                    buildSessionUrl();

                url.pathname =
                    new URL(
                        SESSION_MANAGEMENT_INDEX_URL,
                        window.location.origin
                    ).pathname;

                const response =
                    await fetch(
                        url.toString(), {
                            cache: 'no-store',

                            credentials: 'same-origin',

                            headers: {
                                'Accept': 'text/html',

                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        }
                    );

                if (!response.ok) {
                    throw new Error(
                        'Unable to refresh active sessions.'
                    );
                }

                const html =
                    await response.text();

                if (
                    !context
                    ?.isCurrent?.()
                ) {
                    return;
                }

                const documentParser =
                    new DOMParser();

                const nextDocument =
                    documentParser.parseFromString(
                        html,
                        'text/html'
                    );

                const nextList =
                    nextDocument.querySelector(
                        '#sessionAdminList ' +
                        '[data-session-list]'
                    );

                const nextGrid =
                    nextDocument.querySelector(
                        '#sessionAdminGridView ' +
                        '[data-session-grid]'
                    );

                if (
                    sessionListBody &&
                    nextList
                ) {
                    sessionListBody.innerHTML =
                        nextList.innerHTML;
                }

                if (
                    sessionGrid &&
                    nextGrid
                ) {
                    sessionGrid.innerHTML =
                        nextGrid.innerHTML;
                }

                window.PatientUI
                    ?.initAvatars?.(
                        root
                    );

                applySessionFilters();

                const afterIds =
                    window
                    .GlobalLiveUpdate
                    ?.collectIds(root) ||
                    new Set();

                const newIds =
                    Array
                    .from(afterIds)
                    .filter(
                        id =>
                        !beforeIds.has(id)
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
            }

            function syncSessionUrlState() {
                const url = buildSessionUrl();
                window.history.replaceState({}, '', url.toString());
            }

            function sessionMatchesFilters(item, state) {
                const role =
                    String(
                        item.dataset.role || ''
                    ).toLowerCase();

                const device =
                    normalizeSessionDeviceValue(
                        item.dataset.device || ''
                    );

                const isCurrent =
                    item.dataset.current === '1';

                let roleMatch = true;
                let deviceMatch = true;
                let scopeMatch = true;

                const selectedRoles = normalizeSessionRoleValues(
                    state.roles
                );

                if (!selectedRoles.includes('all')) {
                    roleMatch = selectedRoles.includes(role);
                }

                if (state.device !== 'all') {
                    deviceMatch =
                        device ===
                        normalizeSessionDeviceValue(
                            state.device
                        );
                }

                if (state.scope === 'current') {
                    scopeMatch = isCurrent;
                }

                if (state.scope === 'others') {
                    scopeMatch = !isCurrent;
                }

                return (
                    roleMatch &&
                    deviceMatch &&
                    scopeMatch
                );
            }

            function getSessionDraftResultCount() {
                let count = 0;

                getSessionItems().forEach(item => {
                    if (
                        sessionMatchesFilters(
                            item,
                            sessionFilterDraft
                        )
                    ) {
                        count++;
                    }
                });

                return count;
            }

            function updateSessionShowResults() {
                if (!showResultsText) return;
                showResultsText.textContent = 'Apply filters';
            }

            function renderSessionFilterChips() {
                if (
                    !activeFiltersSection ||
                    !activeChipsContainer
                ) {
                    return;
                }

                const chips = [];

                if (sessionFilterDraft.sort === 'oldest') {
                    chips.push({
                        key: 'sort',
                        label: 'Sort: Oldest First',
                        reset: 'recent'
                    });
                }


                if (sessionFilterDraft.device !== 'all') {
                    chips.push({
                        key: 'device',
                        label: 'Device: ' +
                            sessionFilterDraft.device
                            .charAt(0)
                            .toUpperCase() +
                            sessionFilterDraft.device.slice(1),

                        reset: 'all'
                    });
                }

                if (sessionFilterDraft.scope !== 'all') {
                    chips.push({
                        key: 'scope',

                        label: sessionFilterDraft.scope === 'current' ?
                            'Scope: Current Session' : 'Scope: Other Sessions',

                        reset: 'all'
                    });
                }

                activeFiltersSection.classList.toggle(
                    'hidden',
                    chips.length === 0
                );

                activeChipsContainer.innerHTML =
                    chips.map(chip => `
                    <span class="filter-chip">
                        <span>${chip.label}</span>

                        <button
                            type="button"
                            class="filter-chip-remove"
                            data-session-filter-remove="${chip.key}"
                            aria-label="Remove ${chip.label}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </span>
                `).join('');

                activeChipsContainer
                    .querySelectorAll(
                        '[data-session-filter-remove]'
                    )
                    .forEach(button => {

                        button.addEventListener(
                            'click',
                            function() {

                                const key =
                                    this.dataset
                                    .sessionFilterRemove;

                                const chip =
                                    chips.find(
                                        item =>
                                        item.key === key
                                    );

                                if (!chip) return;

                                sessionFilterDraft[key] =
                                    chip.reset;

                                syncSessionFilterInputs();
                                renderSessionFilterChips();
                                updateSessionShowResults();
                            }
                        );
                    });
            }

            function readSessionDraftFromInputs() {
                sessionFilterDraft.sort = filterForm?.querySelector(
                    'input[name="sort"]:checked'
                )?.value || 'recent';


                sessionFilterDraft.device = filterForm?.querySelector(
                    'input[name="device"]:checked'
                )?.value || 'all';

                sessionFilterDraft.scope = filterForm?.querySelector(
                    'input[name="scope"]:checked'
                )?.value || 'all';
            }

            function openSessionDrawer() {
                sessionFilterDraft = {
                    ...sessionFilterState,
                    roles: [...sessionFilterState.roles]
                };

                syncSessionFilterInputs();
                renderSessionFilterChips();
                updateSessionShowResults();

                window.openFilterDrawer?.('filterModal');
            }

            function closeSessionDrawer() {
                window.closeFilterDrawer?.('filterModal');
            }

            window.cancelSessionFilters = function() {

                sessionFilterDraft = {
                    ...sessionFilterState,
                    roles: [...sessionFilterState.roles]
                };

                syncSessionFilterInputs();
                closeSessionDrawer();
            };

            window.clearSessionFilterDraft = function() {
                sessionFilterDraft = {
                    ...sessionFilterDraft,
                    sort: 'recent',
                    device: 'all',
                    scope: 'all'
                };

                syncSessionFilterInputs();
                renderSessionFilterChips();
                updateSessionShowResults();
            };

            window.applySessionFilters = function() {
                readSessionDraftFromInputs();

                sessionFilterState = {
                    ...sessionFilterDraft,
                    roles: [...sessionFilterDraft.roles]
                };

                closeSessionDrawer();

                const targetUrl = buildSessionUrl().toString();

                if (targetUrl !== window.location.href) {
                    window.location.href = targetUrl;
                    return;
                }

                applySessionFilters();
                updateSessionFilterButton();
                syncSessionRoleFilterSelect();
            };

            function applySessionFilters() {
                syncSessionUrlState();

                const query =
                    searchInput ?
                    searchInput.value
                    .trim()
                    .toLowerCase() :
                    '';

                let visibleCount = 0;

                getSessionItems().forEach(item => {
                    const searchText =
                        item.textContent
                        .toLowerCase();

                    const searchMatch =
                        query === '' ||
                        searchText.includes(query);

                    const filterMatch =
                        sessionMatchesFilters(
                            item,
                            sessionFilterState
                        );

                    const isVisible =
                        searchMatch &&
                        filterMatch;

                    item.style.display =
                        isVisible ?
                        '' :
                        'none';

                    const sessionKey =
                        item.dataset.sessionKey;

                    getSessionGridItems().forEach(gridItem => {
                        if (
                            gridItem.dataset.sessionKey ===
                            sessionKey
                        ) {
                            gridItem.style.display =
                                isVisible ?
                                '' :
                                'none';
                        }
                    });

                    if (isVisible) {
                        visibleCount++;
                    }
                });

                [
                    topPaginationInfo,
                    bottomPaginationInfo
                ].forEach(info => {
                    if (!info) return;

                    info.textContent =
                        visibleCount === 0 ?
                        'Showing 0 sessions' :
                        `Showing ${visibleCount} ${visibleCount === 1 ? 'session' : 'sessions'}`;
                });

                const sortDirection =
                    sessionFilterState.sort === 'oldest' ?
                    'asc' :
                    'desc';

                const sortVisibleItems = (items, container) => {
                    if (!container) return;

                    Array.from(items)
                        .filter(item => item.style.display !== 'none')
                        .sort((a, b) => {
                            const aTimestamp = getSessionLastActivity(a);
                            const bTimestamp = getSessionLastActivity(b);

                            return sortDirection === 'asc' ?
                                aTimestamp - bTimestamp :
                                bTimestamp - aTimestamp;
                        })
                        .forEach(item => container.appendChild(item));
                };

                sortVisibleItems(
                    getSessionItems(),
                    sessionListBody
                );

                sortVisibleItems(
                    getSessionGridItems(),
                    sessionGrid
                );

                if (visibleCount === 0) {
                    const gridView =
                        document.getElementById(
                            'sessionAdminGridView'
                        );

                    if (sessionList) {
                        sessionList.hidden = true;
                        sessionList.style.removeProperty('display');
                    }

                    if (gridView) {
                        gridView.hidden = true;
                        gridView.style.removeProperty('display');
                    }

                    topPagebarWrap &&
                        (topPagebarWrap.style.display = 'none');

                    bottomPagebarWrap &&
                        (bottomPagebarWrap.style.display = 'none');

                    if (emptyState) {
                        emptyState.style.display =
                            'flex';
                    }

                    const hasPanelFilters =
                        getSessionFilterCount() > 0;

                    const hasRoleFilters =
                        hasSpecificSessionRoles();

                    const hasActiveFilters =
                        hasPanelFilters || hasRoleFilters;

                    if (query !== '') {
                        if (emptyTitle) {
                            emptyTitle.textContent =
                                `No results for "${query}"`;
                        }

                        if (emptySub) {
                            emptySub.textContent =
                                hasActiveFilters ?
                                'No active sessions match your search and selected filters.' :
                                'No active sessions match your search. Try a different name, email, IP address, or browser.';
                        }

                        if (clearSearchBtn) {
                            clearSearchBtn.innerHTML =
                                '<i class="fa-solid fa-xmark"></i> Clear search';

                            clearSearchBtn.dataset.emptyAction =
                                'search';
                        }

                    } else if (hasPanelFilters) {
                        if (emptyTitle) {
                            emptyTitle.textContent =
                                'No Matching Sessions';
                        }

                        if (emptySub) {
                            emptySub.textContent =
                                'The selected panel filters did not match any active sessions.';
                        }

                        if (clearSearchBtn) {
                            clearSearchBtn.innerHTML =
                                '<i class="fa-solid fa-rotate-left"></i> Clear panel filters';

                            clearSearchBtn.dataset.emptyAction =
                                'filters';
                        }

                    } else if (hasRoleFilters) {
                        if (emptyTitle) {
                            emptyTitle.textContent =
                                'No Matching Sessions';
                        }

                        if (emptySub) {
                            emptySub.textContent =
                                'No active sessions are available for the selected user roles.';
                        }

                        if (clearSearchBtn) {
                            clearSearchBtn.style.display =
                                'none';

                            clearSearchBtn.dataset.emptyAction =
                                'none';
                        }

                    } else {
                        if (emptyTitle) {
                            emptyTitle.textContent =
                                'No Active Sessions';
                        }

                        if (emptySub) {
                            emptySub.textContent =
                                'There are currently no active user sessions to display.';
                        }

                        if (clearSearchBtn) {
                            clearSearchBtn.style.display =
                                'none';

                            clearSearchBtn.dataset.emptyAction =
                                'none';
                        }
                    }

                    if (clearSearchBtn) {
                        const shouldShowEmptyAction =
                            query !== '' ||
                            hasPanelFilters;

                        clearSearchBtn.style.display =
                            shouldShowEmptyAction ?
                            '' :
                            'none';
                    }

                } else {
                    const gridView =
                        document.getElementById(
                            'sessionAdminGridView'
                        );

                    const storedMode =
                        localStorage.getItem(
                            'admin_session_management_view'
                        ) || 'list';

                    const activeMode =
                        storedMode === 'grid' ?
                        'grid' :
                        'list';

                    if (sessionList) {
                        sessionList.hidden =
                            activeMode !== 'list';

                        sessionList.style.removeProperty(
                            'display'
                        );
                    }

                    if (gridView) {
                        gridView.hidden =
                            activeMode !== 'grid';

                        gridView.style.removeProperty(
                            'display'
                        );
                    }

                    window.setGlobalViewMode?.(
                        'sessionViewToggle',
                        activeMode, {
                            persist: false,
                        }
                    );

                    topPagebarWrap &&
                        (topPagebarWrap.style.display = '');

                    bottomPagebarWrap &&
                        (bottomPagebarWrap.style.display = '');

                    emptyState &&
                        (emptyState.style.display = 'none');
                }
            }

            filterForm
                ?.querySelectorAll(
                    'input[type="radio"]'
                )
                .forEach(input => {

                    input.addEventListener(
                        'change',
                        function() {

                            readSessionDraftFromInputs();
                            renderSessionFilterChips();
                            updateSessionShowResults();
                        }
                    );
                });

            clearAllChipsBtn?.addEventListener(
                'click',
                function() {
                    window.clearSessionFilterDraft();
                }
            );

            filterButton?.addEventListener(
                'click',
                function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    openSessionDrawer();
                }
            );

            filterResetButton?.addEventListener(
                'click',
                function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    sessionFilterState = {
                        ...sessionFilterState,
                        sort: 'recent',
                        device: 'all',
                        scope: 'all'
                    };

                    sessionFilterDraft = {
                        ...sessionFilterState,
                        roles: [...sessionFilterState.roles]
                    };

                    syncSessionFilterInputs();
                    applySessionFilters();
                    updateSessionFilterButton();
                }
            );

            if (searchInput) {

                searchInput.addEventListener(
                    'input',
                    function() {
                        applySessionFilters();
                    }
                );

                const searchWrapper =
                    searchInput.closest(
                        '[data-search-wrapper]'
                    );

                const clearInputBtn =
                    searchWrapper?.querySelector(
                        '[data-search-clear]'
                    );

                clearInputBtn?.addEventListener(
                    'click',
                    function() {

                        if (
                            typeof window.clearSearchInput ===
                            'function'
                        ) {
                            window.clearSearchInput(
                                searchInput
                            );

                            window.setTimeout(
                                applySessionFilters,
                                0
                            );

                            return;
                        }

                        searchInput.value = '';

                        searchInput.dispatchEvent(
                            new Event(
                                'input', {
                                    bubbles: true,
                                }
                            )
                        );
                    }
                );
            }

            clearSearchBtn?.addEventListener(
                'click',
                function(event) {

                    event.preventDefault();

                    const action =
                        this.dataset.emptyAction ||
                        'search';

                    if (action === 'filters') {
                        sessionFilterState = {
                            ...sessionFilterState,
                            sort: 'recent',
                            device: 'all',
                            scope: 'all'
                        };

                        sessionFilterDraft = {
                            ...sessionFilterState,
                            roles: [...sessionFilterState.roles]
                        };

                        syncSessionFilterInputs();
                        applySessionFilters();
                        updateSessionFilterButton();
                        return;
                    }

                    if (action === 'search') {
                        if (
                            searchInput &&
                            typeof window.clearSearchInput ===
                            'function'
                        ) {
                            window.clearSearchInput(
                                searchInput
                            );

                            window.setTimeout(
                                applySessionFilters,
                                0
                            );

                            return;
                        }

                        if (searchInput) {
                            searchInput.value = '';

                            searchInput.dispatchEvent(
                                new Event(
                                    'input', {
                                        bubbles: true,
                                    }
                                )
                            );

                            searchInput.focus();
                        }
                    }
                }
            );

            function syncSessionRoleFilterSelect() {
                const root = document.getElementById('sessionRoleFilter');
                if (!root) return;

                const selectedRoles = normalizeSessionRoleValues(
                    sessionFilterState.roles
                );

                const input = root.querySelector('[data-filter-select-input]');
                const label = root.querySelector('[data-filter-select-label]');
                const count = root.querySelector('[data-filter-select-count]');
                const options = Array.from(
                    root.querySelectorAll('[data-filter-select-option]')
                );

                options.forEach(option => {
                    const value = String(option.dataset.value || '');
                    const active = selectedRoles.includes('all') ?
                        value === 'all' :
                        selectedRoles.includes(value);

                    option.classList.toggle('is-active', active);
                    option.setAttribute(
                        'aria-selected',
                        active ? 'true' : 'false'
                    );
                });

                const serialized = selectedRoles.join(',');

                if (input) {
                    input.value = serialized;
                }

                root.dataset.filterSelectValue = serialized;

                if (label) {
                    if (selectedRoles.includes('all')) {
                        label.textContent = 'All Roles';
                    } else if (selectedRoles.length === 1) {
                        const selectedOption = options.find(option =>
                            selectedRoles.includes(
                                String(option.dataset.value || '')
                            )
                        );

                        label.textContent =
                            selectedOption?.dataset.label || '1 selected';
                    } else {
                        label.textContent = `${selectedRoles.length} selected`;
                    }
                }

                if (count) {
                    const selectedCount = selectedRoles.includes('all') ?
                        0 :
                        selectedRoles.length;

                    count.textContent = selectedCount ?
                        String(selectedCount) :
                        '';

                    count.classList.toggle('hidden', selectedCount === 0);
                }
            }

            window.handleSessionRoleFilter = function(values) {
                sessionFilterState.roles = normalizeSessionRoleValues(values);
                sessionFilterDraft = {
                    ...sessionFilterState,
                    roles: [...sessionFilterState.roles]
                };

                syncSessionRoleFilterSelect();
                applySessionFilters();
            };


            updateSessionFilterButton();
            syncSessionRoleFilterSelect();
            applySessionFilters();

            async function initializeSessionRefreshWatcher() {
                if (!sessionRefreshWatcher) {
                    return;
                }

                try {
                    const response =
                        await fetch(
                            getSessionManagementCheckUrl(), {
                                cache: 'no-store',

                                credentials: 'same-origin',

                                headers: {
                                    'Accept': 'application/json',

                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            }
                        );

                    if (!response.ok) {
                        return;
                    }

                    const payload =
                        await response.json();

                    sessionRefreshWatcher.sync(
                        payload
                    );

                    sessionRefreshWatcher.start();

                } catch (error) {
                    console.warn(
                        'Unable to initialize session refresh watcher:',
                        error
                    );
                }
            }

            initializeSessionRefreshWatcher();

            let sessionRefreshWatcher = window
                .initGlobalRefreshWatcher?.({
                    key: 'session-management',

                    url: getSessionManagementCheckUrl,

                    interval: 5000,

                    initialItems: [],

                    autoStart: false,

                    autoApply: false,

                    anchorSelector: '#mainContent .table-card',

                    itemLabel: 'session update',

                    getItems: function(payload) {
                        const signature =
                            String(
                                payload
                                ?.signature ||
                                ''
                            );

                        return signature ? [{
                            id: signature,
                        }] : [];
                    },

                    getItemId: function(item) {
                        return item?.id;
                    },

                    title: function() {
                        return (
                            'Session activity changed'
                        );
                    },

                    subtitle: function() {
                        return (
                            'Refresh to load the latest active sessions.'
                        );
                    },

                    onRefresh: async function(
                        payload,
                        newItems,
                        context
                    ) {
                        updateSessionStats(
                            payload?.stats || {}
                        );

                        await refreshSessionManagement(
                            payload,
                            context
                        );
                    },

                    toast: {
                        type: 'success',

                        title: 'Sessions updated',

                        message: 'Latest active sessions are now shown.',
                    },
                });

            function updateSessionStats(
                stats = {}
            ) {
                const statValues = {
                    activeSessionsStat: stats.total_sessions,

                    activeUsersStat: stats.active_users,

                    otherSessionsStat: stats.other_sessions,

                    activeDevicesStat: stats.active_devices,
                };

                Object.entries(
                    statValues
                ).forEach(
                    ([id, value]) => {
                        const element =
                            document.getElementById(
                                id
                            );

                        if (
                            !element ||
                            value === undefined ||
                            value === null
                        ) {
                            return;
                        }

                        element.textContent =
                            Number(value)
                            .toLocaleString();
                    }
                );
            }

            document.addEventListener(
                'click',
                function(event) {
                    const btn =
                        event.target.closest(
                            '.trigger-session-logout'
                        );

                    if (!btn) {
                        return;
                    }

                    event.preventDefault();

                    const action =
                        btn.dataset.action;

                    const title =
                        btn.dataset.title;

                    const user =
                        btn.dataset.user;

                    const email =
                        btn.dataset.email;

                    const device =
                        btn.dataset.device;

                    if (confirmForm) {
                        confirmForm.action =
                            action;
                    }

                    if (confirmTitle) {
                        confirmTitle.textContent =
                            title;
                    }

                    if (confirmName) {
                        confirmName.textContent =
                            user;
                    }

                    if (confirmEmail) {
                        confirmEmail.textContent =
                            email;
                    }

                    if (confirmDevice) {
                        confirmDevice.textContent =
                            device;
                    }

                    window.openModal?.(
                        'sessionConfirmModal'
                    );
                }
            );
        });
    </script>
@endsection
