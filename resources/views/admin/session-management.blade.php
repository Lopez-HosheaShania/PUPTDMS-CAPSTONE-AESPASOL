@extends('layouts.app')

@section('layout-role', 'admin')

@section('title', 'Session Dashboard')

@section('styles')
    @vite('resources/css/pages/admin/session-management.css')
@endsection

@section('content')
    <main id="mainContent" class="app-page-shell session-admin-page">
        <div class="w-full">
            <section class="page-banner">
                <div class="page-banner-inner">
                    <div class="page-banner-copy">
                        <h1 class="page-title">Session Dashboard</h1>
                    </div>

                </div>
            </section>

            <section id="statCards" class="stat-grid">
                <article class="stat-card s-crimson">
                    <div class="stat-card-info">
                        <span class="stat-label">Active Sessions</span>
                        <span class="stat-num">{{ $stats['total_sessions'] ?? 0 }}</span>
                        <div class="stat-footer">All currently tracked browser sessions</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-signal"></i>
                    </div>
                </article>

                <article class="stat-card s-purple">
                    <div class="stat-card-info">
                        <span class="stat-label">Active Users</span>
                        <span class="stat-num">{{ $stats['active_users'] ?? 0 }}</span>
                        <div class="stat-footer">Accounts with active sessions</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </article>

                <article class="stat-card admin">
                    <div class="stat-card-info">
                        <span class="stat-label">Admin Sessions</span>
                        <span class="stat-num">{{ $stats['admin_sessions'] ?? 0 }}</span>
                        <div class="stat-footer">Privileged access accounts</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                </article>

                <article class="stat-card dentist">
                    <div class="stat-card-info">
                        <span class="stat-label">Dentist Sessions</span>
                        <span class="stat-num">{{ $stats['dentist_sessions'] ?? 0 }}</span>
                        <div class="stat-footer">Clinic-side operational accounts</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                </article>

                <article class="stat-card patient">
                    <div class="stat-card-info">
                        <span class="stat-label">Patient Sessions</span>
                        <span class="stat-num">{{ $stats['patient_sessions'] ?? 0 }}</span>
                        <div class="stat-footer">Self-service access online</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </article>
            </section>

            <section class="session-admin-card table-card">
                <div class="session-admin-toolbar table-toolbar">
                    <div class="session-admin-toolbar-copy">
                        <h2>All Active User Sessions</h2>
                        <p>Monitor and manage active user sessions across roles and devices.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="voice-search-row">
                            <x-search-bar id="sessionSearchInput" name="search" :value="$filters['search'] ?? ''"
                                placeholder="Search user, email, IP, or browser" />
                            <x-voice-input target="#sessionSearchInput" status-id="sessionSearchVoiceStatus"
                                label="Use voice search" title="Voice search" />
                        </div>

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
                            grid-view="#sessionAdminGridView" list-label="List" grid-label="Grid"
                            class="session-view-toggle" />
                    </div>
                </div>

                <div class="session-admin-resultsbar" id="sessionResultsBar">
                    <div id="sessionTopPagebarWrap" class="session-admin-top-pagebar"
                        {{ $sessions->count() === 0 ? 'style=display:none;' : '' }}>
                        <x-pagination-bar id="sessionAdminTopPagebar" infoId="sessionAdminTopPagebarInfo"
                            paginationId="sessionAdminTopPaginationNav" position="top" :showEntries="true"
                            pageSizeId="sessionPageSizeSelect" pageSizeCallback="window.changeSessionPageSize"
                            pageSizeValue="{{ $perPage ?? 10 }}" pageSizeLabel="entries" label="sessions" :total="$sessions->total()"
                            :from="$sessions->firstItem() ?? 0" :to="$sessions->lastItem() ?? 0" />
                    </div>

                    <div class="global-info-group" id="sessionRoleQuickFilters">
                        <button type="button" class="global-info-pill session-role-filter" data-role="admin">
                            <i class="fa-solid fa-user-shield"></i>
                            Admin {{ $stats['admin_sessions'] ?? 0 }}
                        </button>

                        <button type="button" class="global-info-pill session-role-filter" data-role="dentist">
                            <i class="fa-solid fa-user-doctor"></i>
                            Dentist {{ $stats['dentist_sessions'] ?? 0 }}
                        </button>

                        <button type="button" class="global-info-pill session-role-filter" data-role="patient">
                            <i class="fa-solid fa-user"></i>
                            Patient {{ $stats['patient_sessions'] ?? 0 }}
                        </button>
                    </div>
                </div>

                <div class="session-admin-list" id="sessionAdminList"
                    {{ $sessions->count() === 0 ? 'style=display:none;' : '' }}>
                    @foreach ($sessions as $session)
                        @php
                            $roleClass = match ($session->role_slug) {
                                'admin', 'super_admin' => 'role-admin',
                                'dentist' => 'role-dentist',
                                'patient' => 'role-patient',
                                default => 'role-none',
                            };

                            $statusClass =
                                strtolower((string) $session->user_status) === 'active' ? 's-active' : 's-inactive';

                        @endphp

                        <article
                            class="session-admin-item table-record-card {{ $session->is_current ? 'session-admin-item-current' : '' }}"
                            data-session-key="{{ $session->reference }}"
                            data-role="{{ strtolower((string) $session->role_slug) }}"
                            data-device="{{ strtolower((string) $session->device_type) }}"
                            data-current="{{ $session->is_current ? '1' : '0' }}"
                            data-last-activity="{{ $session->last_activity_at->getTimestamp() }}">
                            <div class="session-admin-item-shell">
                                <div class="session-admin-userblock">
                                    <span class="patient-avatar patient-avatar-sm" data-patient-avatar
                                        data-patient-name="{{ $session->user_name }}"></span>

                                    <div>
                                        <div class="session-admin-userhead">
                                            <span class="session-admin-user-name">{{ $session->user_name }}</span>
                                        </div>

                                        <div class="session-admin-user-email">{{ $session->user_email }}</div>

                                        @if ($session->is_current)
                                            <div class="mt-1">
                                                <span
                                                    class="session-admin-current-pill session-admin-current-pill status-pill s-active">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Current Admin Session
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="session-admin-meta-col session-admin-role-col">
                                    <span class="global-info-label">Role</span>
                                    <div>
                                        <span class="badge-role {{ $roleClass }}">
                                            {{ $session->role_label }}
                                        </span>
                                    </div>
                                </div>

                                <div class="session-admin-meta-col session-admin-status-col">
                                    <span class="global-info-label">Account Status</span>
                                    <div>
                                        <span class="session-admin-status status-pill {{ $statusClass }}">
                                            {{ ucfirst((string) $session->user_status) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="session-admin-meta-col">
                                    <span class="global-info-label">Device</span>

                                    <span class="session-admin-device-title global-info-value">
                                        @if ($session->device_type === 'mobile')
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                        @elseif ($session->device_type === 'tablet')
                                            <i class="fa-solid fa-tablet-screen-button"></i>
                                        @else
                                            <i class="fa-solid fa-desktop"></i>
                                        @endif

                                        {{ $session->device_label }}
                                    </span>

                                    <span class="session-admin-device-sub global-info-subvalue">
                                        {{ $session->browser_label }} · {{ $session->os_label }}
                                    </span>
                                </div>

                                <div class="session-admin-meta-col">
                                    <span class="global-info-label">IP and Last Activity</span>

                                    <span class="session-admin-ip global-info-value">
                                        {{ $session->ip_address }}
                                    </span>

                                    <span class="session-admin-activity global-info-subvalue">
                                        {{ $session->last_activity_label }}
                                    </span>
                                </div>

                                <div class="session-admin-actions">
                                    @if (!$session->is_current)
                                        <button type="button" class="ui-btn ui-btn-secondary trigger-session-logout"
                                            data-action="{{ route('admin.session_management.destroy_session', $session->reference) }}"
                                            data-title="Terminate This Session?" data-user="{{ $session->user_name }}"
                                            data-email="{{ $session->user_email }}"
                                            data-device="{{ $session->device_label }} ({{ $session->browser_label }})"
                                            data-type="single">
                                            Log Out This Session
                                        </button>
                                    @else
                                        <button type="button" class="ui-btn ui-btn-warning" disabled>
                                            Protected
                                        </button>
                                    @endif

                                    <button type="button"
                                        class="ui-btn {{ $session->is_current ? 'ui-btn-secondary' : 'ui-btn-danger' }} trigger-session-logout"
                                        data-action="{{ route('admin.session_management.destroy_user_sessions', $session->user_id) }}"
                                        data-title="{{ $session->is_current ? 'Log Out Other Sessions?' : 'Log Out All Sessions?' }}"
                                        data-user="{{ $session->user_name }}" data-email="{{ $session->user_email }}"
                                        data-device="{{ $session->is_current ? 'All other active devices except this current session.' : 'All active devices logged into this account.' }}"
                                        data-type="all">
                                        {{ $session->is_current ? 'Log Out Other Sessions' : 'Log Out All Sessions' }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div id="sessionAdminGridView" class="session-admin-grid-view table-record-grid" hidden>
                    @foreach ($sessions as $session)
                        @php
                            $roleClass = match ($session->role_slug) {
                                'admin', 'super_admin' => 'role-admin',
                                'dentist' => 'role-dentist',
                                'patient' => 'role-patient',
                                default => 'role-none',
                            };

                            $statusClass =
                                strtolower((string) $session->user_status) === 'active' ? 's-active' : 's-inactive';

                        @endphp

                        <article
                            class="session-admin-grid-item table-record-card {{ $session->is_current ? 'session-admin-item-current' : '' }}"
                            data-session-key="{{ $session->reference }}"
                            data-role="{{ strtolower((string) $session->role_slug) }}"
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
                                                <h3 class="table-record-title">
                                                    {{ $session->user_name }}
                                                </h3>

                                                <span class="global-info-subvalue">
                                                    {{ $session->user_email }}
                                                </span>

                                                @if ($session->is_current)
                                                    <div class="mt-1">
                                                        <span class="session-admin-current-pill status-pill s-active">
                                                            <i class="fa-solid fa-circle-check"></i>
                                                            Current Admin Session
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-record-meta">
                                        <div class="table-record-row">
                                            <span class="table-record-label">
                                                Role
                                            </span>

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
                                                {{ $session->device_label }}
                                                ·
                                                {{ $session->browser_label }}
                                            </span>
                                        </div>

                                        <div class="table-record-row">
                                            <span class="table-record-label">IP Address</span>
                                            <span class="table-record-value">
                                                {{ $session->ip_address }}
                                            </span>
                                        </div>

                                        <div class="table-record-row">
                                            <span class="table-record-label">Last Activity</span>
                                            <span class="table-record-value">
                                                {{ $session->last_activity_label }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="session-admin-grid-actions">
                                        @if (!$session->is_current)
                                            <button type="button" class="ui-btn ui-btn-secondary trigger-session-logout"
                                                data-action="{{ route('admin.session_management.destroy_session', $session->reference) }}"
                                                data-title="Terminate This Session?"
                                                data-user="{{ $session->user_name }}"
                                                data-email="{{ $session->user_email }}"
                                                data-device="{{ $session->device_label }} ({{ $session->browser_label }})"
                                                data-type="single">
                                                Log Out This Session
                                            </button>
                                        @else
                                            <button type="button" class="ui-btn ui-btn-warning" disabled>
                                                Protected
                                            </button>
                                        @endif

                                        <button type="button"
                                            class="ui-btn {{ $session->is_current ? 'ui-btn-secondary' : 'ui-btn-danger' }} trigger-session-logout"
                                            data-action="{{ route('admin.session_management.destroy_user_sessions', $session->user_id) }}"
                                            data-title="{{ $session->is_current ? 'Log Out Other Sessions?' : 'Log Out All Sessions?' }}"
                                            data-user="{{ $session->user_name }}"
                                            data-email="{{ $session->user_email }}"
                                            data-device="{{ $session->is_current ? 'All other active devices except this current session.' : 'All active devices logged into this account.' }}"
                                            data-type="all">
                                            {{ $session->is_current ? 'Log Out Other Sessions' : 'Log Out All Sessions' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="empty-state" id="sessionEmptyState"
                    {{ $sessions->count() > 0 ? 'style=display:none;' : '' }}>
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <h3 class="empty-state-title" id="emptyTitle">
                        @if (!empty($filters['search']))
                            No results for "{{ $filters['search'] }}"
                        @else
                            No Active Sessions Found
                        @endif
                    </h3>

                    <p class="empty-state-sub" id="emptySub">
                        @if (!empty($filters['search']))
                            Try a different name, email, IP, or browser.
                        @else
                            The current filters did not match any active sessions.
                            Try changing the role filter or clearing the search query.
                        @endif
                    </p>

                    <button type="button" class="empty-state-btn" id="clearSearchBtn">
                        <i class="fa-solid fa-xmark"></i> Clear search
                    </button>
                </div>
                <div id="sessionBottomPagebarWrap" class="session-admin-bottom-pagebar"
                    {{ $sessions->count() === 0 ? 'style=display:none;' : '' }}>
                    <x-pagination-bar id="sessionAdminBottomPagebar" infoId="sessionAdminBottomPagebarInfo"
                        paginationId="sessionAdminBottomPaginationNav" position="bottom" :showEntries="false"
                        label="sessions" :total="$sessions->total()" :from="$sessions->firstItem() ?? 0" :to="$sessions->lastItem() ?? 0" />
                </div>
            </section>
        </div>
    </main>

    <div class="ui-modal logout-confirm-modal" id="sessionConfirmModal" role="dialog" aria-modal="true"
        aria-hidden="true">
        <div class="ui-modal-card logout-confirm-card">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon logout-confirm-icon">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </div>
                    <div class="modal-copy">
                        <h3 class="modal-title" id="confirmModalTitle">Terminate Active Session?</h3>
                        <p class="modal-subtitle">Security verification confirmation</p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div class="logout-confirm-message mb-4">
                    <div class="logout-confirm-message-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <p id="confirmModalTargetName">User Account Session</p>
                        <span id="confirmModalTargetEmail">user@example.com</span>
                    </div>
                </div>

                <div class="confirmed-modal-schedule-note">
                    <i class="fa-solid fa-circle-info"></i>
                    <span id="confirmModalDevice">Device info will appear here.</span>
                </div>
            </div>

            <div class="modal-ft logout-confirm-actions">
                <button type="button" class="btn-close-modal close-session-modal">
                    Cancel
                </button>

                <form id="sessionConfirmForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="modal-btn-confirm danger">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Confirm Log Out
                    </button>
                </form>
            </div>
        </div>
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

            <x-filter-group title="User Role">
                <div class="filter-chip-row" id="fStatusGroup">
                    @foreach (['all' => 'All Roles', 'admin' => 'Admin', 'dentist' => 'Dentist', 'patient' => 'Patient'] as $val => $lbl)
                        <label class="choice-chip">
                            <input type="radio" name="role" value="{{ $val }}"
                                class="filter-input radio-red chip-radio"
                                {{ ($filters['role'] ?? 'all') === $val ? 'checked' : '' }}>
                            <span>{{ $lbl }}</span>
                        </label>
                    @endforeach
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
            const sessionItems = document.querySelectorAll('#sessionAdminList .session-admin-item');
            const sessionGridItems = document.querySelectorAll('#sessionAdminGridView .session-admin-grid-item');

            const sessionList = document.getElementById('sessionAdminList');
            const resultsBar = document.getElementById('sessionResultsBar');
            const emptyState = document.getElementById('sessionEmptyState');

            const topPagebarWrap = document.getElementById('sessionTopPagebarWrap');
            const bottomPagebarWrap = document.getElementById('sessionBottomPagebarWrap');
            const topPaginationContainer = document.getElementById('sessionAdminTopPaginationNav');
            const bottomPaginationContainer = document.getElementById('sessionAdminBottomPaginationNav');
            const topPaginationInfo = document.getElementById('sessionAdminTopPagebarInfo');
            const bottomPaginationInfo = document.getElementById('sessionAdminBottomPagebarInfo');
            const topPaginationBar = document.getElementById('sessionAdminTopPagebar');
            const bottomPaginationBar = document.getElementById('sessionAdminBottomPagebar');

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

            const drawer = document.getElementById('filterModal');
            const filterForm = document.getElementById('sessionFilterForm');
            const filterButton = document.getElementById('sessionFilterBtn');
            const filterResetButton = document.getElementById('sessionFilterResetBtn');

            const confirmModal = document.getElementById('sessionConfirmModal');
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

            let sessionFilterState = {
                sort: filterForm?.querySelector('input[name="sort"]:checked')?.value ||
                    'recent',

                role: filterForm?.querySelector('input[name="role"]:checked')?.value ||
                    'all',

                device: filterForm?.querySelector('input[name="device"]:checked')?.value ||
                    'all',

                scope: filterForm?.querySelector('input[name="scope"]:checked')?.value ||
                    'all'
            };


            let sessionFilterDraft = {
                ...sessionFilterState
            };

            function syncSessionFilterInputs() {
                if (!filterForm) return;

                Object.entries(sessionFilterDraft).forEach(([name, value]) => {
                    const input = filterForm.querySelector(
                        `input[name="${name}"][value="${value}"]`
                    );

                    if (input) {
                        input.checked = true;
                    }
                });
            }

            function getSessionFilterCount(state = sessionFilterState) {
                let count = 0;

                if (state.sort !== 'recent') count++;
                if (state.role !== 'all') count++;
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

                const filters = {
                    search: searchInput?.value?.trim() || '',
                    sort: sessionFilterState.sort,
                    role: sessionFilterState.role,
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

                if (state.role !== 'all') {
                    roleMatch =
                        role ===
                        state.role.toLowerCase();
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

                sessionItems.forEach(item => {
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

                if (sessionFilterDraft.role !== 'all') {
                    chips.push({
                        key: 'role',
                        label: 'Role: ' +
                            sessionFilterDraft.role
                            .charAt(0)
                            .toUpperCase() +
                            sessionFilterDraft.role.slice(1),

                        reset: 'all'
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

                sessionFilterDraft.role = filterForm?.querySelector(
                    'input[name="role"]:checked'
                )?.value || 'all';

                sessionFilterDraft.device = filterForm?.querySelector(
                    'input[name="device"]:checked'
                )?.value || 'all';

                sessionFilterDraft.scope = filterForm?.querySelector(
                    'input[name="scope"]:checked'
                )?.value || 'all';
            }

            function openSessionDrawer() {
                sessionFilterDraft = {
                    ...sessionFilterState
                };

                syncSessionFilterInputs();
                renderSessionFilterChips();
                updateSessionShowResults();

                if (
                    typeof window.openFilterDrawer ===
                    'function'
                ) {
                    window.openFilterDrawer(
                        'filterModal'
                    );
                    return;
                }

                if (!drawer) return;

                drawer.classList.remove('closing');
                drawer.classList.add('open');
                drawer.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.documentElement.classList.add(
                    'modal-lock'
                );
                document.body.classList.add(
                    'modal-lock'
                );
            }

            function closeSessionDrawer() {
                if (
                    typeof window.closeFilterDrawer ===
                    'function'
                ) {
                    window.closeFilterDrawer(
                        'filterModal'
                    );
                    return;
                }

                if (!drawer) return;

                drawer.classList.add('closing');
                drawer.classList.remove('open');

                setTimeout(() => {
                    drawer.classList.remove('closing');

                    drawer.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                    document.documentElement.classList.remove(
                        'modal-lock'
                    );
                    document.body.classList.remove(
                        'modal-lock'
                    );
                }, 300);
            }

            window.cancelSessionFilters = function() {

                sessionFilterDraft = {
                    ...sessionFilterState
                };

                syncSessionFilterInputs();
                closeSessionDrawer();
            };

            window.clearSessionFilterDraft = function() {
                sessionFilterDraft = {
                    sort: 'recent',
                    role: 'all',
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
                    ...sessionFilterDraft
                };

                closeSessionDrawer();

                const targetUrl = buildSessionUrl().toString();

                if (targetUrl !== window.location.href) {
                    window.location.href = targetUrl;
                    return;
                }

                applySessionFilters();
                updateSessionFilterButton();
                updateSessionRoleQuickFilters();
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

                sessionItems.forEach(item => {
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

                    sessionGridItems.forEach(gridItem => {
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

                if (topPaginationInfo) {
                    if (visibleCount === 0) {
                        topPaginationInfo.textContent = 'Showing 0 sessions';
                    } else {
                        topPaginationInfo.textContent =
                            `Showing ${visibleCount} ${visibleCount === 1 ? 'session' : 'sessions'}`;
                    }
                }

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

                sortVisibleItems(sessionItems, sessionList);
                sortVisibleItems(
                    sessionGridItems,
                    document.getElementById('sessionAdminGridView')
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

                    bottomPagebarWrap &&
                        (bottomPagebarWrap.style.display = 'none');

                    if (emptyState) {
                        emptyState.style.display =
                            'flex';
                    }

                    const hasActiveFilters =
                        getSessionFilterCount() > 0;

                    if (query !== '') {
                        if (emptyTitle) {
                            emptyTitle.textContent =
                                `No results for "${query}"`;
                        }

                        if (emptySub) {
                            emptySub.textContent =
                                hasActiveFilters ?
                                'No sessions match this search with the selected filters.' :
                                'Try a different name, email, IP, or browser.';
                        }

                        if (clearSearchBtn) {
                            clearSearchBtn.innerHTML =
                                '<i class="fa-solid fa-xmark"></i> Clear search';

                            clearSearchBtn.dataset.emptyAction =
                                'search';
                        }

                    } else if (hasActiveFilters) {
                        if (emptyTitle) {
                            emptyTitle.textContent =
                                'No Matching Sessions';
                        }

                        if (emptySub) {
                            emptySub.textContent =
                                'The selected filters did not match any active sessions.';
                        }

                        if (clearSearchBtn) {
                            clearSearchBtn.innerHTML =
                                '<i class="fa-solid fa-rotate-left"></i> Clear filters';

                            clearSearchBtn.dataset.emptyAction =
                                'filters';
                        }

                    } else {
                        if (emptyTitle) {
                            emptyTitle.textContent =
                                'No Active Sessions Found';
                        }

                        if (emptySub) {
                            emptySub.textContent =
                                'There are no active sessions to display.';
                        }

                        if (clearSearchBtn) {
                            clearSearchBtn.style.display =
                                'none';

                            clearSearchBtn.dataset.emptyAction =
                                'none';
                        }
                    }

                    if (
                        clearSearchBtn &&
                        (
                            query !== '' ||
                            hasActiveFilters
                        )
                    ) {
                        clearSearchBtn.style.display =
                            '';
                    }

                } else {
                    const gridView =
                        document.getElementById(
                            'sessionAdminGridView'
                        );

                    sessionList?.style.removeProperty(
                        'display'
                    );

                    gridView?.style.removeProperty(
                        'display'
                    );

                    const activeMode =
                        window.getGlobalViewMode?.(
                            'sessionViewToggle'
                        ) || 'list';

                    window.setGlobalViewMode?.(
                        'sessionViewToggle',
                        activeMode, {
                            persist: false,
                        }
                    );

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
                        sort: 'recent',
                        role: 'all',
                        device: 'all',
                        scope: 'all'
                    };

                    sessionFilterDraft = {
                        ...sessionFilterState
                    };

                    syncSessionFilterInputs();
                    applySessionFilters();
                    updateSessionFilterButton();
                    updateSessionRoleQuickFilters();
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
                            sort: 'recent',
                            role: 'all',
                            device: 'all',
                            scope: 'all'
                        };

                        sessionFilterDraft = {
                            ...sessionFilterState
                        };

                        syncSessionFilterInputs();
                        applySessionFilters();
                        updateSessionFilterButton();
                        updateSessionRoleQuickFilters();
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

            const sessionRoleQuickFilters =
                document.querySelectorAll('.session-role-filter');

            function updateSessionRoleQuickFilters() {
                sessionRoleQuickFilters.forEach(button => {
                    const role = button.dataset.role;

                    button.classList.toggle(
                        'active',
                        sessionFilterState.role === role
                    );
                });
            }

            sessionRoleQuickFilters.forEach(button => {
                button.addEventListener('click', function() {
                    const role = this.dataset.role;

                    sessionFilterState.role =
                        sessionFilterState.role === role ?
                        'all' :
                        role;

                    sessionFilterDraft = {
                        ...sessionFilterState
                    };

                    syncSessionFilterInputs();
                    applySessionFilters();
                    updateSessionFilterButton();
                    updateSessionRoleQuickFilters();
                });
            });

            window.initSearchClearButtons?.(document);

            window.initGlobalVoiceInputs?.(document);

            window.initGlobalViewToggles?.(document);

            window.PatientUI?.initAvatars?.(document);

            updateSessionFilterButton();
            updateSessionRoleQuickFilters();
            applySessionFilters();

            document.querySelectorAll('.trigger-session-logout').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.dataset.action;
                    const title = this.dataset.title;
                    const user = this.dataset.user;
                    const email = this.dataset.email;
                    const device = this.dataset.device;

                    if (confirmForm) confirmForm.action = action;
                    if (confirmTitle) confirmTitle.textContent = title;
                    if (confirmName) confirmName.textContent = user;
                    if (confirmEmail) confirmEmail.textContent = email;
                    if (confirmDevice) confirmDevice.textContent = device;

                    if (confirmModal) {
                        confirmModal.classList.add('open');
                        confirmModal.setAttribute('aria-hidden', 'false');
                    }
                });
            });

            function closeConfirmModal() {
                if (!confirmModal) return;
                confirmModal.classList.add('closing');
                confirmModal.classList.remove('open');

                setTimeout(() => {
                    confirmModal.classList.remove('closing');
                    confirmModal.setAttribute('aria-hidden', 'true');
                }, 200);
            }

            document.querySelectorAll('.close-session-modal').forEach(btn => {
                btn.addEventListener('click', closeConfirmModal);
            });

            if (confirmModal) {
                confirmModal.addEventListener('click', function(e) {
                    if (e.target === this) closeConfirmModal();
                });
            }
        });
    </script>
@endsection
