@extends('layouts.app')

@php
$routePrefix = request()->routeIs('dentist.*') ? 'dentist' : 'admin';
$rolePermissionsBasePath = $routePrefix === 'dentist' ? '/dentist/role-permissions' : '/admin/role-permissions';
@endphp

@section('layout-role', 'admin')

@section('title', 'Roles & Permissions')

@section('styles')
@vite('resources/css/pages/admin/role-permissions.css')
@endsection

@section('content')

@php
$logs = $logs ?? collect([]);
$totalCount = $logs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $logs->total() : $logs->count();

$authUser = auth()->user();
$canViewAsRole = $authUser?->hasPermission('access_super_admin_dashboard') ?? false;
$canCreateRoles = $authUser?->hasPermission('create_custom_roles') ?? false;
$canUpdateRolePermissions = $authUser?->hasPermission('update_role_permissions') ?? false;
$canDeleteCustomRoles = $authUser?->hasPermission('delete_custom_roles') ?? false;
@endphp

<main id="mainContent" class="app-page-shell page-enter">
    <div class="role-permission-shell">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Roles & Permissions</h1>
                    <p class="page-subtitle">Manage role access and permission groups across the system.</p>
                </div>

                <div class="page-banner-actions">
                    @if ($canCreateRoles)
                    <button type="button" class="ui-btn ui-btn-primary" onclick="openNewRoleModal()">
                        <i class="fa-solid fa-plus"></i>
                        <span>New Role</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="main-grid">

            <aside class="role-sidebar-sticky">
                @php
                function getRoleBadge($name, $slug)
                {
                $n = strtolower($name);
                $s = strtolower($slug);

                if (str_contains($n, 'super') || str_contains($s, 'super')) {
                return ['badgeColor' => '#7B0D0D', 'label' => 'Protected'];
                }

                if ($s === 'admin') {
                return ['badgeColor' => '#7B0D0D', 'label' => 'Configured'];
                }

                if (str_contains($n, 'dentist') || str_contains($s, 'dentist')) {
                return ['badgeColor' => '#d97706', 'label' => 'Clinical'];
                }

                if (str_contains($n, 'staff') || str_contains($s, 'staff') || str_contains($n, 'clinic')) {
                return ['badgeColor' => '#059669', 'label' => 'Front Desk'];
                }

                if (
                str_contains($n, 'student') ||
                str_contains($s, 'student') ||
                str_contains($n, 'patient') ||
                str_contains($s, 'patient')
                ) {
                return ['badgeColor' => '#4b5563', 'label' => 'Limited'];
                }

                return ['badgeColor' => '#6B7280', 'label' => 'Custom'];
                }
                $totalPerms = $groupedPermissions->flatten()->count();
                @endphp

                <div class="role-list-header">Active Roles ({{ $roles->count() }})</div>

                <div class="role-list-container role-list-view" id="roleListContainer">
                    @foreach ($roles as $i => $role)
                    @php
                    $c = getRoleBadge($role->name, $role->slug);
                    $granted = $role->permissions->count();
                    $pct = $totalPerms > 0 ? round(($granted / $totalPerms) * 100) : 0;
                    $words = array_slice(explode(' ', $role->name), 0, 2);
                    $initials = '';
                    foreach ($words as $_w) {
                    $initials .= strtoupper($_w[0]);
                    }
                    $isHighlighted = isset($highlightRoleId) && (int) $highlightRoleId === (int) $role->id;
                    $isFirst = isset($highlightRoleId) ? $isHighlighted : $i === 0;
                    $isSuperRole =
                    in_array(strtolower($role->slug), ['super_admin', 'super-admin', 'superadmin']) ||
                    str_contains(strtolower($role->name), 'super');
                    $isProtectedRole =
                    $isSuperRole || in_array(strtolower($role->slug), ['admin', 'patient', 'dentist']);
                    @endphp

                    <div class="card role-card {{ $isFirst ? 'active' : '' }}" data-role-id="{{ $role->id }}"
                        data-role-name="{{ $role->display_name }}" data-granted="{{ $granted }}"
                        data-total="{{ $totalPerms }}" data-pct="{{ $pct }}" data-slug="{{ $role->slug }}"
                        data-is-super="{{ $isSuperRole ? '1' : '0' }}" onclick="selectRole(this)">

                        @if (!$isProtectedRole && $canDeleteCustomRoles)
                        <button type="button" class="ui-action-btn ui-action-delete role-delete-action" onclick="event.stopPropagation(); openDeleteModal(
        '{{ $role->id }}',
        @js($role->name)
    )" data-tooltip="Delete role" data-tooltip-tone="delete" aria-label="Delete role">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                        @endif

                        <div class="role-card-header">
                            <div class="patient-avatar patient-avatar-sm">
                                {{ $initials }}</div>
                            <div class="role-card-copy">
                                <div class="role-card-title-row">
                                    <span class="role-name-label">
                                        {{ $role->display_name }}
                                    </span>
                                </div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="badge-pill" style="
        background: {{ $c['badgeColor'] }}15;
        color: {{ $c['badgeColor'] }};
    ">
                                        {{ $c['label'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:12px;">
                            <div class="role-access-meta">
                                <span>Access</span>

                                <span class="pct-label">
                                    {{ $pct }}%
                                </span>
                            </div>
                            <div class="progress-bar role-access-progress">
                                <div class="progress-fill" style="width: {{ $pct }}%;"></div>
                            </div>
                            <div class="count-label">
                                {{ $granted }} / {{ $totalPerms }} permissions
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="card accent-card">
                    @php
                    $fr = isset($highlightRoleId)
                    ? $roles->firstWhere('id', (int) $highlightRoleId)
                    : $roles->first();
                    $fp = $fr
                    ? ($totalPerms > 0
                    ? round(($fr->permissions->count() / $totalPerms) * 100)
                    : 0)
                    : 0;
                    @endphp
                    <div style="font-size:15px; font-weight:800; margin-bottom:4px;" id="accentRoleName">
                        {{ $fr?->display_name ?? '' }}</div>
                    <div style="font-size:32px; font-weight:900; margin-bottom:2px; line-height:1;" id="accentPct">
                        {{ $fp }}%</div>
                    <div style="font-size:11px; opacity:0.8; margin-bottom:14px;" id="accentCount">
                        {{ $fr?->permissions->count() ?? 0 }} of {{ $totalPerms }} active</div>
                    <div style="height:4px; background:rgba(255,255,255,0.2); border-radius:10px;">
                        <div id="accentBar"
                            style="height:100%; width:{{ $fp }}%; background:#fff; border-radius:10px; transition:width 0.4s;">
                        </div>
                    </div>
                </div>
            </aside>

            <section class="role-permission-panel">

                <div class="card role-permission-toolbar-card">

                    <div class="card-header role-permission-toolbar">

                        <div class="card-header-right role-permission-toolbar-row">

                            <div class="perm-search-row voice-search-row">

                                <x-search-bar id="permSearch" placeholder="Search permissions..."
                                    callback="handleRolePermissionSearch" :debounce="150"
                                    clear-label="Clear permission search" class="perm-search-control" />

                                <x-voice-input target="#permSearch" status-id="permSearchVoiceStatus"
                                    label="Voice search permissions" title="Voice search" />

                            </div>

                            <div class="card-header-actions role-permission-actions">

                                @if ($canViewAsRole)
                                <button type="button" class="ui-action-btn ui-action-view role-view-as-action"
                                    id="globalViewAsBtn" onclick="openViewAs()" data-tooltip="View as role"
                                    data-tooltip-tone="view" aria-label="View as role">
                                    <i class="fa-solid fa-eye"></i>

                                    <span class="va-count-badge" id="globalVaBadge">
                                        0
                                    </span>
                                </button>
                                @endif

                                <button type="button" class="ui-btn ui-btn-secondary ui-btn-sm" id="collapseBtn"
                                    onclick="toggleAllGroups()">
                                    <i class="fa-solid fa-angles-up"></i>
                                    <span>Collapse All</span>
                                </button>

                                <button type="button" class="ui-btn ui-btn-warning ui-btn-sm" id="resetDefaultsBtn"
                                    onclick="ajaxResetDefaults()">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    <span>Reset Defaults</span>
                                </button>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="role-permission-content">
                    <div class="protected-banner" id="protectedBanner" style="display:none;">
                        <i class="fa-solid fa-shield-halved" style="font-size:24px; color:#d97706;"></i>
                        <div>
                            <div style="font-weight:800; font-size:13px; color:#92400e;">Protected Role</div>
                            <div style="font-size:12px; color:#b45309;">System roles stay protected from deletion, but
                                permissions remain explicitly defined.</div>
                        </div>
                    </div>

                    @foreach ($roles as $ri => $role)
                    @php
                    $isSuperRole =
                    in_array(strtolower($role->slug), ['super_admin', 'super-admin', 'superadmin']) ||
                    str_contains(strtolower($role->name), 'super');
                    $isActiveRole = isset($highlightRoleId)
                    ? (int) $highlightRoleId === (int) $role->id
                    : $ri === 0;
                    $micons = [
                    'Dental Records' => ['fa-notes-medical', '#8B0000'],
                    'Patients' => ['fa-user-group', '#d97706'],
                    'Appointments' => ['fa-calendar-days', '#059669'],
                    'Document Requests' => ['fa-envelope-open-text', '#2563eb'],
                    'Document Templates' => ['fa-file-lines', '#7c3aed'],
                    'Reports' => ['fa-chart-pie', '#7c3aed'],
                    'General Access' => ['fa-user-shield', '#059669'],
                    'Inventory' => ['fa-boxes-stacked', '#ea580c'],
                    'User Management' => ['fa-user-cog', '#dc2626'],
                    'System Settings' => ['fa-screwdriver-wrench', '#4b5563'],
                    ];
                    @endphp

                    <form id="form-role-{{ $role->id }}" class="role-form" data-role-id="{{ $role->id }}"
                        data-discard-form data-discard-title="Discard permission changes?"
                        data-discard-subtitle="You have unsaved changes for this role."
                        data-discard-message="The permission changes for this role will be restored to their last saved state."
                        style="display:{{ $isActiveRole ? 'block' : 'none' }}; height: 100%;">
                        @csrf
                        <input type="hidden" name="role_id" value="{{ $role->id }}">

                        <div class="groups-container">
                            @forelse($groupedPermissions as $module => $permissions)
                            @php
                            [$ico, $icol] = $micons[$module] ?? ['fa-shield-halved', '#4b5563'];
                            $mSlug = Str::slug($module);
                            $mTotal = $permissions->count();
                            $roleGranted = 0;
                            foreach ($permissions as $_p) {
                            if ($role->permissions->contains('id', $_p->id)) {
                            $roleGranted++;
                            }
                            }
                            $allOn = $roleGranted === $mTotal;
                            @endphp

                            <div class="table-card permission-module-card" data-group="{{ strtolower($module) }}">

                                <div class="table-toolbar perm-group-header perm-group" onclick="togglePermGroup(this)">

                                    <div class="table-toolbar-title">

                                        <div class="perm-group-icon" style="--module-color: {{ $icol }};">
                                            <i class="fa-solid {{ $ico }}"></i>
                                        </div>

                                        <div class="perm-group-info">
                                            <div class="perm-group-title">
                                                {{ $module }}
                                            </div>

                                            <div class="group-count">
                                                {{ $roleGranted }}
                                                of
                                                {{ $mTotal }}
                                                enabled
                                            </div>
                                        </div>

                                    </div>

                                    <div class="perm-group-actions">

                                        <div class="dot-row" id="dots-{{ $role->id }}-{{ $mSlug }}">
                                            @for ($d = 0; $d < $mTotal; $d++) <div
                                                class="dot {{ $d < $roleGranted ? 'is-granted' : '' }}"
                                                style="--dot-color: {{ $icol }};">
                                        </div>
                                        @endfor
                                    </div>

                                    <div class="all-toggle-wrap" onclick="event.stopPropagation();">
                                        <span>All</span>

                                        <label class="global-switch">
                                            <input type="checkbox" class="global-switch-input group-master"
                                                data-role="{{ $role->id }}" data-module="{{ $mSlug }}"
                                                data-discard-ignore="true" {{ $allOn ? 'checked' : '' }} {{ $isSuperRole
                                                || !$canUpdateRolePermissions ? 'disabled' : '' }}
                                                onchange="onGroupMasterChange(this)">

                                            <span class="global-switch-track"></span>
                                        </label>
                                    </div>

                                    <i class="fa-solid fa-chevron-up chevron"></i>

                                </div>

                            </div>

                            <div class="perm-group-body">

                                <div class="table-scroll">

                                    <table class="data-table permission-table">

                                        <thead>
                                            <tr>
                                                <th>Permission</th>
                                                <th class="table-cell-center">
                                                    Status
                                                </th>

                                                <th class="table-cell-center">
                                                    Access
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($permissions as $permission)
                                            @php
                                            $isGranted = $role->permissions->contains(
                                            'id',
                                            $permission->id,
                                            );
                                            @endphp

                                            <tr class="perm-row"
                                                data-perm-search="{{ strtolower($permission->name . ' ' . $permission->slug) }}">
                                                <td class="table-cell-main">

                                                    <span class="table-primary">
                                                        <strong>
                                                            {{ $permission->name }}
                                                        </strong>
                                                    </span>

                                                </td>

                                                <td class="table-cell-center">

                                                    <span
                                                        class="status-pill {{ $isGranted ? 'status-granted' : 'status-denied' }}">
                                                        <span class="status-dot"></span>

                                                        {{ $isGranted ? 'Granted' : 'Denied' }}
                                                    </span>

                                                </td>

                                                <td class="table-cell-center">

                                                    <label class="global-switch">

                                                        <input type="checkbox" name="permissions[{{ $role->id }}][]"
                                                            value="{{ $permission->id }}"
                                                            class="global-switch-input perm-toggle"
                                                            data-role="{{ $role->id }}" data-module="{{ $mSlug }}"
                                                            data-color="{{ $icol }}"
                                                            data-perm-name="{{ $permission->name }}"
                                                            data-perm-slug="{{ $permission->slug }}" {{ $isGranted
                                                            ? 'checked' : '' }} {{ $isSuperRole ||
                                                            !$canUpdateRolePermissions ? 'disabled' : '' }}
                                                            onchange="onPermChange(this)">

                                                        <span class="global-switch-track"></span>

                                                    </label>

                                                </td>

                                            </tr>
                                            @endforeach

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                        @empty

                        <div class="empty-state-host" data-role-permission-empty></div>
                        @endforelse

                </div>

                @if (!$isSuperRole)
                <div class="floating-save-bar" id="footer-bar-{{ $role->id }}">
                    <div class="fsb-text">
                        <span class="fsb-title">Unsaved changes</span>
                        <span class="fsb-sub">0 changes</span>
                    </div>

                    <div class="fsb-actions">
                        @if ($canViewAsRole)
                        <button type="button" class="ui-action-btn ui-action-view fsb-view-as" onclick="openViewAs()"
                            data-tooltip="View as role" data-tooltip-tone="view" aria-label="View as role">
                            <i class="fa-solid fa-eye"></i>

                            <span class="va-count-badge">
                                0
                            </span>
                        </button>
                        @endif

                        <button type="button" class="ui-btn ui-btn-secondary btn-discard"
                            onclick="requestDiscardRoleChanges('{{ $role->id }}')">
                            Discard
                        </button>

                        <button type="button" class="ui-btn ui-btn-primary btn-save-float" id="save-btn-{{ $role->id }}"
                            onclick="ajaxSaveRole('{{ $role->id }}')">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save</span>
                        </button>
                    </div>
                </div>
                @endif
                </form>
                @endforeach

                <div id="permSearchEmptyState" class="empty-state-host perm-search-empty-state" hidden></div>
        </div>

        </section>
    </div>
    </div>
</main>

@if ($canCreateRoles)
<div id="newRoleModal" class="ui-modal modal-theme-primary" aria-hidden="true">

    <div class="ui-modal-card modal-md">
        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon">
                    <i class="fa-solid fa-user-shield"></i>
                </div>

                <div class="modal-copy">
                    <h3 class="modal-title">
                        Create New Role
                    </h3>

                    <p class="modal-subtitle">
                        Define a new role and assign permissions.
                    </p>
                </div>
            </div>

            <button type="button" class="modal-x" data-discard-close="newRoleModal"
                aria-label="Close create role modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="createRoleForm" action="{{ route($routePrefix . '.role_permissions.store_role') }}" method="POST"
            class="modal-card-form" data-global-validation data-form-validation-rule="createRole" data-discard-form
            data-discard-title="Discard new role?" data-discard-subtitle="You have unsaved role information."
            data-discard-message="Closing this modal will remove the role information you entered. Do you want to discard these changes?"
            novalidate>

            @csrf
            <div class="modal-bd">
                <div class="modal-form-grid">
                    <div class="global-form-group" data-global-field>
                        <label class="global-form-label" for="newRoleName">
                            Role Name
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-control-wrap">
                            <i class="fa-solid fa-tag global-control-icon"></i>

                            <input type="text" id="newRoleName" name="name"
                                class="form-input-custom global-form-icon" placeholder="e.g. Dental Intern"
                                data-field-label="Role Name" data-required-message="Please enter a role name." required
                                autocomplete="off">
                        </div>
                    </div>

                    <div class="global-form-group" data-global-field>
                        <label class="global-form-label" for="newRoleSlug">
                            Role Slug
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-control-wrap">
                            <i class="fa-solid fa-link global-control-icon"></i>

                            <input type="text" id="newRoleSlug" name="slug"
                                class="form-input-custom global-form-icon" placeholder="e.g. dental-intern"
                                data-field-label="Role Slug" data-required-message="Please enter a role slug."
                                data-pattern-message="Use lowercase letters, numbers, and hyphens only."
                                pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required autocomplete="off">
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-ft">
                <button type="button" data-discard-close="newRoleModal" class="ui-btn ui-btn-secondary">
                    Cancel
                </button>

                <button type="submit" id="btnSubmitNewRole" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    <span>Create Role</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if ($canDeleteCustomRoles)
<x-delete-confirm-modal id="deleteRoleModal" form-id="deleteRoleForm" name-id="deleteRoleName" title="Delete Role"
    subtitle="This action requires confirmation" message="Are you sure you want to delete"
    helper="This role will be permanently removed." close-callback="closeDeleteModal()" />
@endif

<div id="resetConfirmModal" class="ui-modal modal-theme-warning" aria-hidden="true">
    <div class="ui-modal-card modal-sm" role="dialog" aria-modal="true" aria-labelledby="resetConfirmTitle"
        onclick="event.stopPropagation()">
        <div class="modal-hd">

            <div class="modal-heading">

                <div class="modal-icon">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>

                <div class="modal-copy">

                    <h3 id="resetConfirmTitle" class="modal-title">
                        Reset to Defaults?
                    </h3>

                    <p class="modal-subtitle">
                        Restore the original role permissions.
                    </p>

                </div>

            </div>

        </div>

        <div class="modal-bd">

            <div class="global-confirm-alert">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <div>
                    <p>
                        Reset permissions for <strong>Admin, Dentist, and Patient?</strong>
                    </p>

                    <span>
                        Custom permission changes will be lost.
                        This action cannot be undone.
                    </span>
                </div>

            </div>

        </div>

        <div class="modal-ft">

            <button type="button" onclick="closeResetConfirm()" class="ui-btn ui-btn-secondary">
                Cancel
            </button>

            <button type="button" id="resetConfirmBtn" onclick="confirmResetDefaults()" class="ui-btn ui-btn-warning">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Yes, Reset</span>
            </button>

        </div>
    </div>
</div>

<div id="vaOverlay" class="ui-modal modal-theme-view" aria-hidden="true">

    <div class="ui-modal-card modal-lg">
        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon">
                    <i class="fa-solid fa-eye"></i>
                </div>

                <div class="modal-copy">
                    <h3 class="modal-title">View As Role</h3>
                    <p class="modal-subtitle" id="vaSubtitle">
                        Select a role to preview dashboard access
                    </p>
                </div>
            </div>

            <button type="button" onclick="closeViewAs()" class="modal-x" aria-label="Close role preview modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-bd">
            <div
                style="background: linear-gradient(135deg, var(--crimson), var(--crimson-dark)); border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 14px; margin-bottom: 16px; color: #fff;">
                <i class="fa-solid fa-shield-halved" style="font-size: 20px; opacity: .8;"></i>
                <div>
                    <div
                        style="font-size:11px; opacity:.8; margin-bottom:2px; text-transform:uppercase; font-weight:700;">
                        Newly granted & saved</div>
                    <div><strong style="font-size:20px;" id="vaTotalPerms">0</strong> permissions across <strong
                            style="font-size:20px;" id="vaTotalRoles">0</strong> roles</div>
                </div>
            </div>
            <div id="vaRoleList"></div>
        </div>

        <div class="modal-ft">
            <button type="button" onclick="closeViewAs()" class="ui-btn ui-btn-secondary">
                Close
            </button>
        </div>
    </div>
</div>

<div id="patientPickerOverlay" class="ui-modal modal-theme-success" aria-hidden="true">

    <div class="ui-modal-card modal-lg">
        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon">
                    <i class="fa-solid fa-user-injured"></i>
                </div>

                <div class="modal-copy">
                    <h3 class="modal-title">
                        Select Patient Account
                    </h3>

                    <p class="modal-subtitle">
                        Choose which patient to impersonate
                    </p>
                </div>
            </div>

            <button type="button" onclick="closePatientPicker()" class="modal-x"
                aria-label="Close patient picker modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-bd">
            <div class="patient-picker-search-row">

                <x-search-bar id="patientPickerSearch" placeholder="Search patient name or email..."
                    callback="handlePatientPickerSearch" :debounce="200" clear-label="Clear patient search"
                    class="patient-picker-search" />

            </div>
            <div id="patientPickerList"></div>
        </div>

        <div class="modal-ft">
            <button type="button" onclick="closePatientPicker()" class="ui-btn ui-btn-secondary">
                Close
            </button>
        </div>
    </div>
</div>

<div class="redirect-overlay" id="redirectOverlay">
    <div class="redirect-spinner"></div>
    <div id="redirectText" style="font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:6px;"></div>
    <div id="redirectSub" style="font-size:.85rem;color:rgba(255,255,255,.7);"></div>
</div>

@endsection

@section('scripts')
<script>
    const PERM_MODULES = [{
        module: 'Dashboard',
        color: '#8B0000'
    },
    {
        module: 'Patients',
        color: '#d97706'
    },
    {
        module: 'Appointments',
        color: '#059669'
    },
    {
        module: 'Document Requests',
        color: '#2563eb'
    },
    {
        module: 'Document Template',
        color: '#7c3aed'
    },
    {
        module: 'Reports',
        color: '#7c3aed'
    },
    {
        module: 'Academic Periods',
        color: '#059669'
    },
    {
        module: 'System Logs',
        color: '#dc2626'
    },
    {
        module: 'System Settings',
        color: '#4b5563'
    },
    ];

    function getModuleColor(module) {
        const found = PERM_MODULES.find(m => m.module === module);
        return found ? found.color : '#4b5563';
    }

    let initialStates = {};
    let savedGrants = {};
    let activeRoleId = null;
    let isModalActive = false;

    function syncScrollStateForSaveBar() {
        updateFABVisibility();
    }

    const ROLE_TOAST_DURATION = 7000;

    const flashedViewAs = @json(session('saved_view_as') ?? null);
    if (flashedViewAs && flashedViewAs.role_id) {
        savedGrants[String(flashedViewAs.role_id)] = (flashedViewAs.permissions || []).map(p => ({
            name: p.name,
            slug: p.slug,
            color: getModuleColor(p.module)
        }));
    }

    function mountFloatingSaveBars() {
        document.querySelectorAll('.floating-save-bar').forEach(bar => {
            if (!bar.id) return;

            [...document.body.children].forEach(child => {
                if (child !== bar && child.id === bar.id && child.classList.contains(
                    'floating-save-bar')) {
                    child.remove();
                }
            });

            bar.classList.add('role-permissions-floating-save-bar');
            if (bar.parentElement !== document.body) {
                document.body.appendChild(bar);
            }
        });
    }

    function getPermissionStateKey(
        toggle
    ) {
        return [
            toggle.dataset.module || '',
            toggle.value || ''
        ].join('::');
    }

    function initRoleForms() {
        mountFloatingSaveBars();

        initialStates = {};
        savedGrants = {};
        activeRoleId = null;

        const firstActiveCard =
            document.querySelector(
                '.role-card.active'
            ) ||
            document.querySelector(
                '.role-card'
            );

        if (firstActiveCard) {
            activeRoleId =
                firstActiveCard.dataset.roleId;
        }

        document.querySelectorAll('.role-form').forEach(form => {
            const roleId = form.dataset.roleId;
            if (!roleId) return;

            initialStates[roleId] = {};
            savedGrants[roleId] = [];

            form
                .querySelectorAll(
                    '.perm-toggle'
                )
                .forEach(input => {

                    const stateKey =
                        getPermissionStateKey(
                            input
                        );

                    initialStates[
                        roleId
                    ][stateKey] =
                        input.checked;

                    if (input.checked) {
                        savedGrants[
                            roleId
                        ].push({
                            name: input.dataset
                                .permName || '',

                            slug: input.dataset
                                .permSlug || '',

                            color: input.dataset
                                .color ||
                                '#4b5563'
                        });
                    }
                });

            const bar = document.getElementById('footer-bar-' + roleId);
            if (bar) bar.classList.remove('show');

            const modules = [...new Set(Array.from(form.querySelectorAll('.perm-toggle')).map(t => t.dataset
                .module).filter(Boolean))];
            modules.forEach(module => {
                const sample =
                    form.querySelector(
                        `.perm-toggle[data-module="${module}"]`
                    );

                if (!sample) {
                    return;
                }

                syncGroupMaster(
                    roleId,
                    module
                );

                updateGroupCount(
                    roleId,
                    module
                );

                updateDots(
                    roleId,
                    module,
                    sample.dataset.color ||
                    '#4b5563'
                );
            });

            window.DiscardChanges
                ?.captureForm(form);
        });

        updateViewAsBtn();

        requestAnimationFrame(() => {
            document
                .querySelectorAll(
                    '.floating-save-bar'
                )
                .forEach(bar => {
                    bar.classList.remove(
                        'show'
                    );
                });

            updateFABVisibility();
        });
    }

    function keepRoleListLayout() {
        const container = document.getElementById('roleListContainer');
        const mainContent = document.getElementById('mainContent');
        if (container) {
            container.classList.remove('role-grid-view');
            container.classList.add('role-list-view');
        }
        if (mainContent) {
            mainContent.classList.remove('mode-grid', 'mode-list');
        }
    }

    window.handleRolePermissionSearch =
        function (value) {
            filterPerms(
                String(value || '')
            );
        };

    window.handlePatientPickerSearch =
        function (value) {
            filterPatientPicker(
                String(value || '')
            );
        };

    document.addEventListener('DOMContentLoaded', () => {
        mountFloatingSaveBars();
        const firstCard = document.querySelector('.role-card');
        const protectedBanner = document.getElementById('protectedBanner');

        if (firstCard && protectedBanner && firstCard.dataset.isSuper === '1') {
            protectedBanner.style.display = 'flex';
        }

        initRoleForms();
        keepRoleListLayout();
        syncScrollStateForSaveBar();

        @if (session('success'))
            if (typeof showToast === 'function') {
                showToast('Success', '{!! addslashes(session('success')) !!}', 'success');
            }
        @endif

        @if (session('error'))
            if (typeof showToast === 'function') {
                showToast('Error', '{!! addslashes(session('error')) !!}', 'error');
            }
        @endif

        document
            .querySelectorAll(
                '[data-role-permission-empty]'
            )
            .forEach(host => {
                window.EmptyState?.render({
                    host,
                    icon: 'fa-shield-halved',
                    title: 'No permissions found',
                    message: 'Permission groups will appear here once they are available.',
                });
            });
    });

    function selectRole(card) {
        document.querySelectorAll('.role-card').forEach(c => {
            c.classList.remove('active');
        });

        card.classList.add('active');

        const roleId = card.dataset.roleId;
        const roleName = card.dataset.roleName || '';
        const granted = parseInt(card.dataset.granted || '0', 10);
        const total = parseInt(card.dataset.total || '0', 10);
        const pct = parseInt(card.dataset.pct || '0', 10);

        document.getElementById('accentRoleName').textContent = roleName;
        document.getElementById('accentPct').textContent = pct + '%';
        document.getElementById('accentCount').textContent = granted + ' of ' + total + ' active';
        document.getElementById('accentBar').style.width = pct + '%';

        const slug = (card.dataset.slug || '').toLowerCase();
        const isSuper = ['super_admin', 'super-admin', 'superadmin'].includes(slug) || roleName.toLowerCase().includes(
            'super');
        const banner = document.getElementById('protectedBanner');
        if (banner) banner.style.display = isSuper ? 'flex' : 'none';

        document.querySelectorAll('.role-form').forEach(f => f.style.display = 'none');
        const form = document.getElementById('form-role-' + roleId);
        if (form) form.style.display = 'block';

        const permSearch =
            document.getElementById(
                'permSearch'
            );

        if (permSearch) {
            permSearch.value = '';

            permSearch.dispatchEvent(
                new Event(
                    'input', {
                    bubbles: true,
                }
                )
            );
        }

        filterPerms('');

        activeRoleId = roleId;
        updateFABVisibility();
    }

    function updateViewAsBtn() {
        let totalSavedRoles = 0;

        Object.values(
            savedGrants
        ).forEach(grants => {
            if (
                grants.length > 0
            ) {
                totalSavedRoles++;
            }
        });

        const topButton =
            document.getElementById(
                'globalViewAsBtn'
            );

        if (topButton) {
            const badge =
                topButton.querySelector(
                    '.va-count-badge'
                );

            if (badge) {
                badge.textContent =
                    totalSavedRoles;

                badge.hidden =
                    totalSavedRoles <= 0;
            }
        }

        document
            .querySelectorAll(
                '.fsb-view-as'
            )
            .forEach(btn => {
                const badge =
                    btn.querySelector(
                        '.va-count-badge'
                    );

                if (badge) {
                    badge.textContent =
                        totalSavedRoles;
                }
            });

        updateFABVisibility();
    }

    function hasActiveRolePermissionModal() {
        const modalIds = [
            'newRoleModal',
            'deleteRoleModal',
            'resetConfirmModal',
            'vaOverlay',
            'patientPickerOverlay'
        ];

        return modalIds.some(id => {
            const modal = document.getElementById(id);
            if (!modal) return false;

            const ariaHidden = modal.getAttribute('aria-hidden');
            return ariaHidden === 'false' ||
                modal.classList.contains('show') ||
                modal.classList.contains('open') ||
                !modal.hidden;
        });
    }

    function updateFABVisibility() {
        document.querySelectorAll('.floating-save-bar').forEach(b => b.classList.remove('show'));

        if (!activeRoleId) return;
        const bar = document.getElementById('footer-bar-' + activeRoleId);
        if (!bar) return;

        if (isModalActive && hasActiveRolePermissionModal()) {
            return;
        }

        const form = document.getElementById('form-role-' + activeRoleId);
        if (!form) return;

        let isDirty = false;
        let changesCount = 0;

        const baseline =
            initialStates[
            activeRoleId
            ];

        if (!baseline) {
            return;
        }

        form
            .querySelectorAll(
                '.perm-toggle'
            )
            .forEach(toggle => {

                const stateKey =
                    getPermissionStateKey(
                        toggle
                    );

                if (
                    !Object.prototype
                        .hasOwnProperty
                        .call(
                            baseline,
                            stateKey
                        )
                ) {
                    return;
                }

                if (
                    toggle.checked !==
                    baseline[
                    stateKey
                    ]
                ) {
                    isDirty = true;
                    changesCount++;
                }
            });

        let totalSavedRoles = 0;
        Object.values(savedGrants).forEach(grants => {
            if (grants.length > 0) totalSavedRoles++;
        });

        const title = bar.querySelector('.fsb-title');
        const sub = bar.querySelector('.fsb-sub');
        const btnDiscard = bar.querySelector('.btn-discard');
        const btnSave = bar.querySelector('.btn-save-float');
        const btnViewAs = bar.querySelector('.fsb-view-as');

        if (isDirty) {
            bar.classList.add('show');
            title.textContent = 'Unsaved changes';
            sub.textContent = changesCount + ' unsaved change' + (changesCount > 1 ? 's' : '');
            sub.style.display = 'block';
            btnDiscard.style.display = 'inline-flex';
            btnSave.style.display = 'inline-flex';
            if (btnViewAs) {
                btnViewAs.style.display = 'inline-flex';
                const badge = btnViewAs.querySelector('.va-count-badge');
                if (badge) badge.textContent = totalSavedRoles;
            }
        } else {
            bar.classList.remove('show');
        }
    }

    function requestDiscardRoleChanges(roleId) {
        const form = document.getElementById(
            `form-role-${roleId}`
        );

        if (!form) return;

        window.DiscardChanges?.confirmClose(
            form,
            () => discardChanges(roleId)
        );
    }

    function hasUnsavedRoleChanges(
        roleId
    ) {
        const form =
            document.getElementById(
                'form-role-' + roleId
            );

        const baseline =
            initialStates[
            roleId
            ];

        if (
            !form ||
            !baseline
        ) {
            return false;
        }

        return [
            ...form.querySelectorAll(
                '.perm-toggle'
            )
        ].some(toggle => {

            const stateKey =
                getPermissionStateKey(
                    toggle
                );

            return (
                Object.prototype
                    .hasOwnProperty
                    .call(
                        baseline,
                        stateKey
                    ) &&
                toggle.checked !==
                baseline[stateKey]
            );
        });
    }

    function discardChanges(roleId) {
        const form = document.getElementById('form-role-' + roleId);
        if (!form) return;

        form
            .querySelectorAll(
                '.perm-toggle'
            )
            .forEach(toggle => {

                const stateKey =
                    getPermissionStateKey(
                        toggle
                    );

                const initVal =
                    initialStates[
                    roleId
                    ]?.[
                    stateKey
                    ];

                if (
                    typeof initVal !==
                    'boolean'
                ) {
                    return;
                }

                if (
                    toggle.checked !==
                    initVal
                ) {
                    toggle.checked =
                        initVal;

                    updatePermVisuals(
                        toggle
                    );
                }
            });

        const modules = [...new Set(Array.from(form.querySelectorAll('.perm-toggle')).map(t => t.dataset.module).filter(
            Boolean))];
        modules.forEach(module => {
            const sample = form.querySelector(`.perm-toggle[data-module="${module}"]`);
            syncGroupMaster(roleId, module);
            updateGroupCount(roleId, module);
            updateDots(roleId, module, sample.dataset.color || '#4b5563');
        });

        updateAccentCard(roleId);
        window.DiscardChanges?.captureForm(form);
        updateFABVisibility();
    }

    function updatePermVisuals(input) {
        const row =
            input.closest('.perm-row');

        if (!row) {
            return;
        }

        const status =
            row.querySelector(
                '.status-pill'
            );

        if (!status) {
            return;
        }

        const granted =
            input.checked;

        status.className =
            `status-pill ${granted
                ? 'status-granted'
                : 'status-denied'
            }`;

        status.innerHTML = `
        <span class="status-dot"></span>
        ${granted ? 'Granted' : 'Denied'}
    `;
    }

    let allExpanded = true;

    function getPermissionGroupBody(
        group
    ) {
        if (!group) {
            return null;
        }

        const body =
            group.nextElementSibling;

        if (
            !body ||
            !body.classList.contains(
                'perm-group-body'
            )
        ) {
            return null;
        }

        return body;
    }

    function togglePermGroup(header) {
        const group =
            header.closest(
                '.perm-group'
            );

        const body =
            getPermissionGroupBody(
                group
            );

        if (!body) {
            return;
        }

        const chevron =
            header.querySelector(
                '.chevron'
            );

        const willCollapse = !body.classList.contains(
            'collapsed'
        );

        body.classList.toggle(
            'collapsed',
            willCollapse
        );

        chevron?.classList.toggle(
            'collapsed',
            willCollapse
        );
    }

    function toggleAllGroups() {
        const button =
            document.getElementById(
                'collapseBtn'
            );

        const form = [
            ...document.querySelectorAll(
                '.role-form'
            )
        ].find(
            form =>
                form.style.display ===
                'block'
        );

        if (
            !form ||
            !button
        ) {
            return;
        }

        allExpanded = !allExpanded;

        form
            .querySelectorAll(
                '.perm-group'
            )
            .forEach(group => {

                const body =
                    group.nextElementSibling;

                if (
                    !body ||
                    !body.classList.contains(
                        'perm-group-body'
                    )
                ) {
                    return;
                }

                body.classList.toggle(
                    'collapsed',
                    !allExpanded
                );

                group
                    .querySelector(
                        '.chevron'
                    )
                    ?.classList.toggle(
                        'collapsed',
                        !allExpanded
                    );
            });

        button.innerHTML =
            allExpanded ?
                `
                <i class="fa-solid fa-angles-up"></i>
                <span>Collapse All</span>
            ` :
                `
                <i class="fa-solid fa-angles-down"></i>
                <span>Expand All</span>
            `;
    }

    function onGroupMasterChange(master) {
        const roleId =
            master.dataset.role;

        const mSlug =
            master.dataset.module;

        const form =
            document.getElementById(
                'form-role-' + roleId
            );

        if (!form) {
            return;
        }

        const newState =
            master.checked;

        form
            .querySelectorAll(
                `.perm-toggle[data-module="${mSlug}"]`
            )
            .forEach(toggle => {
                if (toggle.disabled) {
                    return;
                }

                toggle.checked =
                    newState;

                updatePermVisuals(
                    toggle
                );
            });

        const sample =
            form.querySelector(
                `.perm-toggle[data-module="${mSlug}"]`
            );

        updateDots(
            roleId,
            mSlug,
            sample?.dataset.color ||
            '#4b5563'
        );

        updateGroupCount(
            roleId,
            mSlug
        );

        updateAccentCard(
            roleId
        );

        updateFABVisibility();
    }

    function onPermChange(input) {
        updatePermVisuals(input);
        const roleId = input.dataset.role;
        const mSlug = input.dataset.module;

        updateDots(roleId, mSlug, input.dataset.color);
        updateGroupCount(roleId, mSlug);
        syncGroupMaster(roleId, mSlug);
        updateAccentCard(roleId);
        updateFABVisibility();
    }

    function syncGroupMaster(roleId, mSlug) {
        const form = document.getElementById('form-role-' + roleId);
        if (!form) return;
        const all = [...form.querySelectorAll(`.perm-toggle[data-module="${mSlug}"]`)];
        const checked = all.filter(t => t.checked).length;
        const master = form.querySelector(`.group-master[data-module="${mSlug}"]`);
        if (!master) return;
        master.checked = checked === all.length;
        master.indeterminate = checked > 0 && checked < all.length;
    }

    function updateDots(roleId, mSlug, color) {
        const cont = document.getElementById(`dots-${roleId}-${mSlug}`);
        if (!cont) return;
        const form = document.getElementById('form-role-' + roleId);
        if (!form) return;
        const toggles = [...form.querySelectorAll(`.perm-toggle[data-module="${mSlug}"]`)];
        const dots = cont.querySelectorAll('.dot');
        toggles.forEach((t, i) => {
            if (!dots[i]) return;
            dots[i].style.setProperty('--dot-color', color || '#8B0000');
            dots[i].classList.toggle('is-granted', !!t.checked);
        });
    }

    function updateGroupCount(
        roleId,
        mSlug
    ) {
        const form =
            document.getElementById(
                'form-role-' + roleId
            );

        if (!form) {
            return;
        }

        const dotsEl =
            form.querySelector(
                `[id="dots-${roleId}-${mSlug}"]`
            );

        if (!dotsEl) {
            return;
        }

        const group =
            dotsEl.closest(
                '.perm-group'
            );

        if (!group) {
            return;
        }

        const body =
            group.nextElementSibling;

        if (
            !body ||
            !body.classList.contains(
                'perm-group-body'
            )
        ) {
            return;
        }

        const toggles = [
            ...body.querySelectorAll(
                `.perm-toggle[data-module="${mSlug}"]`
            )
        ];

        const enabledCount =
            toggles.filter(
                toggle =>
                    toggle.checked
            ).length;

        const countEl =
            group.querySelector(
                '.group-count'
            );

        if (countEl) {
            countEl.textContent =
                `${enabledCount} of ${toggles.length} enabled`;
        }
    }

    function updateAccentCard(roleId) {
        const form = document.getElementById('form-role-' + roleId);
        if (!form) return;
        const all = [...form.querySelectorAll('.perm-toggle')];
        const total = all.length;
        const checked = all.filter(t => t.checked).length;
        const pct = total > 0 ? Math.round(checked / total * 100) : 0;

        document.getElementById('accentPct').textContent = pct + '%';
        document.getElementById('accentCount').textContent = `${checked} of ${total} active`;
        document.getElementById('accentBar').style.width = pct + '%';

        const card = document.querySelector(`.role-card[data-role-id="${roleId}"]`);
        if (card) {
            card.querySelector('.pct-label').textContent = pct + '%';
            card.querySelector('.count-label').textContent = `${checked} / ${total} permissions`;
            card.querySelector('.progress-fill').style.width = pct + '%';
            card.dataset.granted = checked;
            card.dataset.pct = pct;
        }
    }

    function ajaxSaveRole(roleId) {
        const form = document.getElementById('form-role-' + roleId);
        const btn = document.getElementById('save-btn-' + roleId);
        if (!form || !btn) return;

        const checkedIds = [...form.querySelectorAll('.perm-toggle:checked')].map(t => t.value);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

        fetch('{{ route($routePrefix . '.role_permissions.update') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                role_id: roleId,
                permissions: checkedIds
            })
        })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Server error ' + res.status);
                return data;
            })
            .then(data => {
                form
                    .querySelectorAll(
                        '.perm-toggle'
                    )
                    .forEach(input => {

                        const stateKey =
                            getPermissionStateKey(
                                input
                            );

                        initialStates[
                            roleId
                        ][stateKey] =
                            input.checked;
                    });

                window.DiscardChanges?.captureForm(form);

                savedGrants[roleId] = [];
                form.querySelectorAll('.perm-toggle:checked').forEach(input => {
                    savedGrants[roleId].push({
                        name: input.dataset.permName || '',
                        slug: input.dataset.permSlug || '',
                        color: input.dataset.color || '#4b5563'
                    });
                });

                updateViewAsBtn();

                if (typeof showToast === 'function') {
                    showToast('Success', `Permissions updated successfully.`, 'success');
                }
            })
            .catch(err => {
                if (typeof showToast === 'function') {
                    showToast('Error', err.message || 'Could not save permissions.', 'error');
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save';
            });
    }

    function filterPerms(q) {
        q = String(q || '')
            .toLowerCase()
            .trim();

        const form = [
            ...document.querySelectorAll(
                '.role-form'
            )
        ].find(
            form =>
                form.style.display ===
                'block'
        );

        if (!form) {
            return;
        }

        let visibleGroups = 0;

        form
            .querySelectorAll(
                '.perm-group'
            )
            .forEach(group => {

                const moduleCard =
                    group.closest(
                        '.permission-module-card'
                    );

                const body =
                    getPermissionGroupBody(
                        group
                    );

                if (
                    !moduleCard ||
                    !body
                ) {
                    return;
                }

                const rows = [
                    ...body.querySelectorAll(
                        '.perm-row'
                    )
                ];

                let hasVisibleRow =
                    false;

                rows.forEach(row => {
                    const searchable =
                        String(
                            row.dataset
                                .permSearch ||
                            ''
                        )
                            .toLowerCase();

                    const matches = !q ||
                        searchable.includes(
                            q
                        );

                    row.style.display =
                        matches ?
                            '' :
                            'none';

                    if (matches) {
                        hasVisibleRow =
                            true;
                    }
                });

                moduleCard.style.display =
                    hasVisibleRow ?
                        '' :
                        'none';

                if (hasVisibleRow) {
                    visibleGroups++;
                }

                if (
                    q &&
                    hasVisibleRow
                ) {
                    body.classList.remove(
                        'collapsed'
                    );

                    group
                        .querySelector(
                            '.chevron'
                        )
                        ?.classList.remove(
                            'collapsed'
                        );
                }
            });

        const empty =
            document.getElementById(
                'permSearchEmptyState'
            );

        if (!empty) {
            return;
        }

        const hasNoMatches =
            q.length > 0 &&
            visibleGroups === 0;

        if (hasNoMatches) {
            empty.hidden = false;

            window.EmptyState
                ?.renderSearch({
                    host: empty,
                    input: document.getElementById(
                        'permSearch'
                    ),
                    query: q,
                    message: 'Try a different permission name or slug.',
                });

            return;
        }

        window.EmptyState
            ?.hide(empty);

        empty.hidden = true;
    }

    function openNewRoleModal() {
        isModalActive = true;
        updateFABVisibility();

        const form =
            document.getElementById('createRoleForm');

        const modal =
            document.getElementById('newRoleModal');

        if (!form || !modal) {
            isModalActive = false;
            updateFABVisibility();
            return;
        }

        form.reset();

        form
            .querySelectorAll('.global-field-error')
            .forEach(error => {
                error.innerHTML = '';
                error.classList.remove('show');
                error.setAttribute('aria-hidden', 'true');
            });

        form
            .querySelectorAll('.is-invalid')
            .forEach(field => {
                field.classList.remove('is-invalid');
                field.removeAttribute('aria-invalid');
                field.removeAttribute('aria-describedby');
            });

        window.openModal?.('newRoleModal');

        document.dispatchEvent(
            new CustomEvent('voice:refresh', {
                detail: {
                    root: modal
                }
            })
        );
    }

    const roleNameField =
        document.getElementById('newRoleName');

    const roleSlugField =
        document.getElementById('newRoleSlug');

    roleNameField?.addEventListener('input', function () {
        if (!roleSlugField) return;

        roleSlugField.value = this.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');

        roleSlugField.dispatchEvent(
            new Event('input', {
                bubbles: true
            })
        );
    });

    function closeNewRoleModal() {
        window.closeModal?.('newRoleModal');

        window.setTimeout(() => {
            isModalActive = false;
            updateFABVisibility();
        }, 180);
    }

    document
        .getElementById('createRoleForm')
        ?.addEventListener('submit', function (e) {
            e.preventDefault();

            const validation =
                window.validateGlobalForm?.(this);

            if (!validation || !validation.valid) {
                return;
            }

            const form = this;

            const slug = String(
                form.querySelector('[name="slug"]')
                    ?.value || ''
            ).trim();

            const btn =
                document.getElementById(
                    'btnSubmitNewRole'
                );

            btn.disabled = true;

            btn.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            Creating...
        `;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        let errorMsg = 'Could not create role.';
                        if (data.errors) errorMsg = Object.values(data.errors).flat().join(' ');
                        else if (data.message) errorMsg = data.message;
                        throw new Error(errorMsg);
                    }
                    return data;
                })
                .then(data => {
                    closeNewRoleModal();
                    if (typeof showToast === 'function') {
                        showToast('Success', data.message || 'Role created successfully.', 'success');
                    }

                    fetch(window.location.href)
                        .then(r => r.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newGrid = doc.querySelector('.main-grid');

                            if (newGrid) {
                                const currentGrid = document.querySelector('.main-grid');

                                if (currentGrid) {
                                    currentGrid.innerHTML = newGrid.innerHTML;

                                    document.dispatchEvent(new CustomEvent('voice:refresh', {
                                        detail: {
                                            root: currentGrid
                                        }
                                    }));
                                }
                                initRoleForms();
                                keepRoleListLayout();
                                syncScrollStateForSaveBar();

                                const newRoleCard = document.querySelector(
                                    `.role-card[data-slug="${slug}"]`) || document.querySelector(
                                        '.role-card');
                                if (newRoleCard) selectRole(newRoleCard);
                            }
                            btn.disabled = false;
                            btn.innerHTML = `
                                <i class="fa-solid fa-plus"></i>
                                <span>Create Role</span>
                            `;
                        });
                })
                .catch(err => {
                    if (typeof showToast === 'function') {
                        showToast(
                            'Create Role Failed',
                            err.message || 'Could not create the role.',
                            'error'
                        );
                    }

                    btn.disabled = false;

                    btn.innerHTML = `
        <i class="fa-solid fa-plus"></i>
        <span>Create Role</span>
    `;
                });
        });

    const PROTECTED_ROLE_SLUGS = ['admin', 'patient', 'dentist', 'super_admin', 'super-admin', 'superadmin'];

    function getFallbackRoleName(roleName, roleSlug) {
        const normalizedName = String(roleName || '').toLowerCase();
        const normalizedSlug = String(roleSlug || '').toLowerCase();

        if (normalizedSlug.includes('dentist') || normalizedName.includes('dentist')) {
            return 'Dentist';
        }

        if (
            normalizedSlug.includes('admin') ||
            normalizedName.includes('admin') ||
            normalizedSlug.includes('staff') ||
            normalizedName.includes('staff') ||
            normalizedSlug.includes('clinic') ||
            normalizedName.includes('clinic')
        ) {
            return 'Admin';
        }

        return 'Patient';
    }

    function openDeleteModal(roleId, roleName) {
        const card = document.querySelector(`.role-card[data-role-id="${roleId}"]`);
        const slug = (card?.dataset.slug || '').toLowerCase().trim();
        if (PROTECTED_ROLE_SLUGS.includes(slug)) {
            if (typeof showToast === 'function') {
                showToast('Protected Role', `Cannot delete built-in role.`, 'error');
            }
            return;
        }

        isModalActive = true;
        updateFABVisibility();

        document.getElementById('deleteRoleName').textContent = roleName;
        document.getElementById('deleteRoleForm').action = `${@json($rolePermissionsBasePath)}/${roleId}/destroy`;
        window.openModal?.('deleteRoleModal');
    }

    function closeDeleteModal() {
        window.closeModal?.('deleteRoleModal');

        window.setTimeout(() => {
            isModalActive = false;
            updateFABVisibility();
        }, 180);
    }

    document.getElementById('deleteRoleForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('.ui-btn-danger');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'Could not delete role.');
                }
                return data;
            })
            .then((data) => {
                closeDeleteModal();
                if (typeof showToast === 'function') {
                    showToast('Success', data.message || 'Role deleted successfully.', 'success');
                }

                fetch(window.location.href)
                    .then(r => r.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newGrid = doc.querySelector('.main-grid');

                        if (newGrid) {
                            document.querySelector('.main-grid').innerHTML = newGrid.innerHTML;
                            initRoleForms();
                            keepRoleListLayout();
                            syncScrollStateForSaveBar();
                            const firstRole = document.querySelector('.role-card');
                            if (firstRole) selectRole(firstRole);
                        }
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Delete';
                    });
            })
            .catch(err => {
                closeDeleteModal();
                if (typeof showToast === 'function') {
                    showToast('Error', err.message, 'error');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Delete';
            });
    });

    function ajaxResetDefaults() {
        if (
            activeRoleId &&
            hasUnsavedRoleChanges(
                activeRoleId
            )
        ) {
            const form =
                document.getElementById(
                    'form-role-' +
                    activeRoleId
                );

            window.DiscardChanges
                ?.confirmClose(
                    form,
                    () => {
                        discardChanges(
                            activeRoleId
                        );

                        openResetDefaultsModal();
                    }
                );

            return;
        }

        openResetDefaultsModal();
    }

    function openResetDefaultsModal() {
        isModalActive = true;

        updateFABVisibility();

        window.openModal?.(
            'resetConfirmModal'
        );
    }

    function closeResetConfirm() {
        window.closeModal?.('resetConfirmModal');
        window.setTimeout(() => {
            isModalActive = false;
            updateFABVisibility();
        }, 180);
    }

    function confirmResetDefaults() {
        const confirmBtn = document.getElementById('resetConfirmBtn');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = `
    <i class="fa-solid fa-spinner fa-spin"></i>
    <span>Resetting...</span>
`;

        fetch('{{ $rolePermissionsBasePath }}/reset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Server error ' + res.status);
                return data;
            })
            .then(() => {
                closeResetConfirm();
                if (typeof showToast === 'function') {
                    showToast('Success', 'Permissions reset to defaults.', 'success');
                }

                fetch(window.location.href)
                    .then(res => res.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newGrid = doc.querySelector('.main-grid');
                        const currentGrid = document.querySelector('.main-grid');

                        if (newGrid && currentGrid) {
                            const previousRoleId =
                                activeRoleId;

                            currentGrid.innerHTML =
                                newGrid.innerHTML;

                            isModalActive = false;

                            initRoleForms();

                            keepRoleListLayout();

                            document.dispatchEvent(
                                new CustomEvent(
                                    'voice:refresh', {
                                    detail: {
                                        root: currentGrid
                                    }
                                }
                                )
                            );

                            const roleCard =
                                (
                                    previousRoleId ?
                                        document.querySelector(
                                            `.role-card[data-role-id="${previousRoleId}"]`
                                        ) :
                                        null
                                ) ||
                                document.querySelector(
                                    '.role-card'
                                );

                            document
                                .querySelectorAll(
                                    '.role-form'
                                )
                                .forEach(form => {
                                    form.style.display =
                                        'none';
                                });

                            if (roleCard) {
                                selectRole(roleCard);
                            }

                            syncScrollStateForSaveBar();
                        }
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = `
    <i class="fa-solid fa-rotate-left"></i>
    <span>Yes, Reset</span>
`;
                    });
            })
            .catch(err => {
                closeResetConfirm();
                if (typeof showToast === 'function') {
                    showToast('Error', err.message || 'Could not reset.', 'error');
                }
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = `
    <i class="fa-solid fa-rotate-left"></i>
    <span>Yes, Reset</span>
`;
            });
    }

    function registerCreateRoleValidation() {
        if (
            typeof window.registerGlobalFormValidationRule !==
            'function'
        ) {
            return false;
        }

        window.registerGlobalFormValidationRule(
            'createRole',
            form => {
                const nameField =
                    form.querySelector('[name="name"]');

                const slugField =
                    form.querySelector('[name="slug"]');

                const name =
                    String(nameField?.value || '').trim();

                const slug =
                    String(slugField?.value || '')
                        .trim()
                        .toLowerCase();

                let valid = true;
                let firstInvalid = null;

                const duplicateName = Array.from(
                    document.querySelectorAll('.role-card')
                ).some(card => {
                    return String(
                        card.dataset.roleName || ''
                    ).trim().toLowerCase() ===
                        name.toLowerCase();
                });

                if (name && duplicateName) {
                    window.showFormInputValidationMessage?.(
                        nameField,
                        'A role with this name already exists.'
                    );

                    valid = false;
                    firstInvalid ||= nameField;
                }

                const duplicateSlug = Array.from(
                    document.querySelectorAll('.role-card')
                ).some(card => {
                    return String(
                        card.dataset.slug || ''
                    ).trim().toLowerCase() === slug;
                });

                if (slug && duplicateSlug) {
                    window.showFormInputValidationMessage?.(
                        slugField,
                        'A role with this slug already exists.'
                    );

                    valid = false;
                    firstInvalid ||= slugField;
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
        registerCreateRoleValidation
    );

    document.addEventListener(
        'DOMContentLoaded',
        registerCreateRoleValidation
    );

    function openViewAs() {
        isModalActive = true;
        updateFABVisibility();

        const overlay = document.getElementById('vaOverlay');
        if (overlay && overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }
        const list = document.getElementById('vaRoleList');
        if (!overlay || !list) return;
        list.innerHTML = '';
        let totalPerms = 0,
            totalRoles = 0;

        document.querySelectorAll('.role-card').forEach(card => {
            const roleId = card.dataset.roleId;
            const roleName = card.dataset.roleName || 'Role';
            const roleSlug = (card.dataset.slug || '').toLowerCase();
            const granted = parseInt(card.dataset.granted || '0', 10);
            if (granted <= 0) return;

            const form = document.getElementById(`form-role-${roleId}`);
            if (!form) return;

            const checkedPerms = [...form.querySelectorAll('.perm-toggle:checked')].map(input => ({
                name: input.dataset.permName || 'Permission',
                color: input.dataset.color || '#4b5563'
            }));
            if (!checkedPerms.length) return;

            totalRoles++;
            totalPerms += checkedPerms.length;
            const initials = roleName.split(' ').slice(0, 2).map(w => w[0].toUpperCase()).join('');
            const color = checkedPerms[0]?.color || '#4b5563';
            const isSuperAdmin = ['super_admin', 'super-admin', 'superadmin'].includes(roleSlug) || roleName
                .toLowerCase().includes('super');

            const tags = checkedPerms.map(p =>
                `<span style="font-size:10px; font-weight:700; color:${p.color}; background:${p.color}15; padding:2px 8px; border-radius:12px;">${p.name}</span>`
            ).join('');
            const goBtn = !isSuperAdmin ?
                `<button class="va-go-btn va-redirect-btn" data-role-id="${roleId}" data-role-name="${roleName}" data-role-slug="${roleSlug}" data-color="${color}">Go to Dashboard <i class="fa-solid fa-arrow-right"></i></button>` :
                '';

            list.innerHTML += `
            <div class="va-role-row ${!isSuperAdmin ? 'va-redirect-btn' : ''}" data-role-id="${roleId}" data-role-name="${roleName}" data-role-slug="${roleSlug}" data-color="${color}" style="${isSuperAdmin ? 'cursor:default;' : ''}">
                <div style="width:40px; height:40px; border-radius:10px; background:${color}; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px;">${initials}</div>
                <div style="flex:1;">
                    <div style="font-size:13px; font-weight:800; color:#111827; margin-bottom:2px;">${roleName}</div>
                    <div style="display:flex; flex-wrap:wrap; gap:5px;">${tags}</div>
                </div>
                ${goBtn}
            </div>`;
        });

        list.querySelectorAll('.va-redirect-btn').forEach(el => {
            el.addEventListener('click', function (e) {
                e.stopPropagation();
                const t = this.closest('[data-role-id]') || this;
                redirectToRole(t.dataset.roleId, t.dataset.roleName, t.dataset.roleSlug, t.dataset
                    .color);
            });
        });

        document.getElementById('vaTotalPerms').textContent = totalPerms;
        document.getElementById('vaTotalRoles').textContent = totalRoles;
        window.openModal?.('vaOverlay');
    }

    function closeViewAs() {
        window.closeModal?.('vaOverlay');

        window.setTimeout(() => {
            isModalActive = false;
            updateFABVisibility();
        }, 180);
    }

    let patientAccountsCache = [];

    function escapeHtml(v) {
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closePatientPicker() {
        window.closeModal?.('patientPickerOverlay');

        window.setTimeout(() => {
            isModalActive = false;
            updateFABVisibility();
        }, 180);
    }

    function redirectToRole(roleId, roleName, roleSlug, color) {
        if (roleSlug === 'patient' || roleSlug === 'patient_role') {
            closeViewAs();
            openPatientPicker(roleName, roleSlug, color);
            return;
        }

        closeViewAs();
        triggerRedirect(roleName, roleSlug, null, color, `Loading ${roleName} view for Admin`);
    }

    function openPatientPicker(roleName, roleSlug, color) {
        isModalActive = true;
        updateFABVisibility();

        fetch("{{ route('admin.patients.list') }}", {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(async res => {
                const d = await res.json();
                if (!res.ok) throw new Error(d.message || 'Error');
                patientAccountsCache = Array.isArray(d) ? d : [];
                renderPatientPicker(patientAccountsCache);
                document.getElementById('patientPickerSearch').value = '';
                const patientSearch =
                    document.getElementById(
                        'patientPickerSearch'
                    );

                if (patientSearch) {
                    patientSearch.value = '';

                    patientSearch.dispatchEvent(
                        new Event(
                            'input', {
                            bubbles: true,
                        }
                        )
                    );
                }
                const patientOverlay = document.getElementById('patientPickerOverlay');

                if (patientOverlay && patientOverlay.parentElement !== document.body) {
                    document.body.appendChild(patientOverlay);
                }
                window.openModal?.('patientPickerOverlay');
            })
            .catch(err => {
                if (typeof showToast === 'function') {
                    showToast('Error', err.message || 'Unable to load patients', 'error');
                }
            });
    }

    function renderPatientPicker(patients, query = '') {
        const list = document.getElementById('patientPickerList');
        if (!list) return;
        if (!patients.length) {
            list.innerHTML = `
        <div
            id="patientPickerEmptyState"
            class="empty-state-host">
        </div>
    `;

            const host =
                document.getElementById(
                    'patientPickerEmptyState'
                );

            if (query) {
                window.EmptyState
                    ?.renderSearch({
                        host,
                        input: document.getElementById(
                            'patientPickerSearch'
                        ),
                        query,
                        message: 'Try a different patient name or email.',
                    });
            } else {
                window.EmptyState
                    ?.render({
                        host,
                        icon: 'fa-user-group',
                        title: 'No patients found',
                        message: 'Patient accounts will appear here once available.',
                    });
            }

            return;
        }
        list.innerHTML = patients.map(p => {
            const n = (p.name || 'Patient').replace(/'/g, "\\'");
            const i = (p.name || 'P').charAt(0).toUpperCase();
            return `<div class="va-role-row" onclick="startPatientImpersonation('patient','patient','#059669',${p.id},'${n}')">
            <div style="width:40px; height:40px; border-radius:10px; background:#059669; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px;">${i}</div>
            <div style="flex:1;">
                <div style="font-size:13px; font-weight:800; color:#111827;">${escapeHtml(p.name || 'Unnamed')}</div>
                <div style="font-size:11px; color:#6b7280;">${escapeHtml(p.email || '')} | ID: ${p.id}</div>
            </div>
            <button class="va-go-btn">Impersonate <i class="fa-solid fa-arrow-right"></i></button>
        </div>`;
        }).join('');
    }

    function filterPatientPicker(q) {
        q = String(q || '')
            .toLowerCase()
            .trim();

        if (!q) {
            renderPatientPicker(
                patientAccountsCache
            );
            return;
        }

        const filtered =
            patientAccountsCache.filter(
                patient => {
                    const searchable =
                        `${patient.name || ''} ${patient.email || ''}`
                            .toLowerCase();

                    return searchable.includes(q);
                }
            );

        renderPatientPicker(
            filtered,
            q
        );
    }

    function startPatientImpersonation(roleName, roleSlug, color, patientId, patientName) {
        closePatientPicker();
        triggerRedirect(patientName, roleSlug, patientId, color, 'Loading patient dashboard for Admin');
    }

    function triggerRedirect(title, slug, patientId, color, sub) {
        const ol = document.getElementById('redirectOverlay');
        ol.style.background = `linear-gradient(135deg, ${color}, #1f2937)`;
        document.getElementById('redirectText').textContent = `Redirecting to ${title}…`;
        document.getElementById('redirectSub').textContent = sub;
        ol.classList.add('show');

        const body = {
            role: slug
        };
        if (patientId) body.patient_id = patientId;

        fetch("{{ route('admin.impersonate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(body)
        })
            .then(async res => {
                const d = await res.json();
                if (!res.ok) throw new Error(d.message || 'Error');
                if (d.redirect) {
                    window.location.href = d.redirect;
                    return;
                }
                throw new Error('No redirect');
            })
            .catch(err => {
                ol.classList.remove('show');
                if (typeof showToast === 'function') {
                    showToast('Error', err.message || 'Something went wrong', 'error');
                }
            });
    }

    function hexDarken(hex) {
        const r = parseInt(hex.slice(1, 3), 16),
            g = parseInt(hex.slice(3, 5), 16),
            b = parseInt(hex.slice(5, 7), 16);
        return '#' + Math.max(0, r - 45).toString(16).padStart(2, '0') + Math.max(0, g - 45).toString(16).padStart(2,
            '0') + Math.max(0, b - 45).toString(16).padStart(2, '0');
    }
</script>
@endsection