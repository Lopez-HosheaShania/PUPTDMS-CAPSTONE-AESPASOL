@extends('layouts.admin')

@section('title', 'Role Permissions | Admin Dashboard')

@section('content')
@php
$logs = $logs ?? collect([]);
$totalCount = $logs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $logs->total() : $logs->count();
@endphp

<main id="mainContent" class="admin-page-shell role-permissions-page page-enter">
    <div class="role-permission-shell">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title page-banner-title">Roles & Permissions</h1>
                    <p class="page-subtitle">Manage role access and permission groups across the system.</p>
                </div>

                <div class="page-banner-actions">
                    <button type="button" class="btn-new-role" onclick="openNewRoleModal()">
                        <i class="fa-solid fa-plus"></i>
                        <span>New Role</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="main-grid">

            <div>
                @php
                function getRoleBadge($name, $slug)
                {
                $n = strtolower($name);
                $s = strtolower($slug);

                if (str_contains($n, 'super') || str_contains($s, 'super') || $s === 'admin') {
                return ['badgeColor' => '#7B0D0D', 'label' => 'Full Access'];
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

                    <div class="role-card {{ $isFirst ? 'active' : '' }}" data-role-id="{{ $role->id }}"
                        data-role-name="{{ $role->display_name }}" data-granted="{{ $granted }}"
                        data-total="{{ $totalPerms }}" data-pct="{{ $pct }}" data-slug="{{ $role->slug }}"
                        data-is-super="{{ $isSuperRole ? '1' : '0' }}" onclick="selectRole(this)">

                        @if (!$isProtectedRole)
                        <button type="button" class="btn-delete-role"
                            onclick="event.stopPropagation(); openDeleteModal('{{ $role->id }}', '{{ addslashes($role->name) }}')"
                            title="Delete role">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                        @endif

                        <div style="display:flex; align-items:center; gap:12px;">
                            <div class="role-avatar">{{ $initials }}</div>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; gap:7px; margin-bottom:3px;">
                                    <span
                                        style="font-weight:700; font-size:13px; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                        class="role-name-label">{{ $role->display_name }}</span>
                                </div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="badge-pill"
                                        style="background:{{ $c['badgeColor'] }}15; color:{{ $c['badgeColor'] }};">{{
                                        $c['label'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:12px;">
                            <div
                                style="display:flex; justify-content:space-between; font-size:10px; color:#9ca3af; font-weight:700; text-transform:uppercase;">
                                <span>Access</span>
                                <span class="pct-label">{{ $pct }}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:{{ $pct }}%;"></div>
                            </div>
                            <div style="font-size:10px; color:#9ca3af;" class="count-label">
                                {{ $granted }} / {{ $totalPerms }} permissions
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="accent-card">
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
            </div>

            <div>
                <div class="card role-permission-card">
                    <div class="card-header">
                        <div class="perm-search-row voice-search-row relative flex-1 md:flex-none flex items-center gap-2"
                            data-voice-field>
                            <div class="search-wrap global-search flex-1" data-search-wrapper>
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                                <input type="text" id="permSearch" placeholder="Search permissions..."
                                    class="search-input" data-search-input oninput="filterPerms(this.value)">

                                <button type="button" id="permSearchClearBtn" class="search-clear" data-search-clear
                                    title="Clear" aria-label="Clear search">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>

                            <div class="voice-input-toggle">
                                <button type="button" id="permSearchMicBtn" class="voice-search-mic external"
                                    data-voice-trigger data-voice-target="#permSearch"
                                    data-voice-status="#permSearchVoiceStatus"
                                    aria-label="Voice input for permission search">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>

                                <span id="permSearchVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>

                        <div class="card-header-actions">
                            <button type="button" class="btn-view-as" id="globalViewAsBtn" onclick="openViewAs()">
                                <i class="fa-solid fa-eye"></i> View As
                                <span class="va-count-badge" id="globalVaBadge">0</span>
                            </button>
                            <button type="button" class="btn-collapse" id="collapseBtn"
                                onclick="toggleAllGroups()">Collapse All</button>
                            <button type="button" class="btn-reset" id="resetDefaultsBtn" onclick="ajaxResetDefaults()">
                                <i class="fa-solid fa-rotate-left"></i> Reset Defaults
                            </button>
                        </div>
                    </div>

                    <div class="role-permission-card-body">
                        <div class="protected-banner" id="protectedBanner" style="display:none;">
                            <i class="fa-solid fa-shield-halved" style="font-size:24px; color:#d97706;"></i>
                            <div>
                                <div style="font-weight:800; font-size:13px; color:#92400e;">Protected Role</div>
                                <div style="font-size:12px; color:#b45309;">Super Admin has unrestricted access and
                                    cannot be modified.</div>
                            </div>
                        </div>

                        @foreach ($roles as $ri => $role)
                        @php
                        $isSuperRole =
                        in_array(strtolower($role->slug), [
                        'super_admin',
                        'super-admin',
                        'superadmin',
                        ]) || str_contains(strtolower($role->name), 'super');
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

                                <div class="group-card perm-group" data-group="{{ strtolower($module) }}">
                                    <div class="perm-group-header" onclick="togglePermGroup(this)">
                                        <div class="perm-group-icon" style="--module-color: {{ $icol }};">
                                            <i class="fa-solid {{ $ico }}"></i>
                                        </div>

                                        <div class="perm-group-info">
                                            <div class="perm-group-title">{{ $module }}</div>
                                            <div class="group-count">
                                                {{ $roleGranted }} of {{ $mTotal }} enabled
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

                                        <div class="all-toggle-wrap"
                                            onclick="event.stopPropagation(); toggleGroupPerms(this,'{{ $role->id }}','{{ $mSlug }}',{{ $allOn ? 'true' : 'false' }})">
                                            <span>All</span>
                                            <label class="toggle-switch {{ $isSuperRole ? 'disabled' : '' }}"
                                                onclick="event.preventDefault();">
                                                <input type="checkbox" class="group-master" data-role="{{ $role->id }}"
                                                    data-module="{{ $mSlug }}" {{ $allOn ? 'checked' : '' }} {{
                                                    $isSuperRole ? 'disabled' : '' }}>
                                                <span class="toggle-track"></span>
                                            </label>
                                        </div>

                                        <i class="fa-solid fa-chevron-up chevron"></i>
                                    </div>
                                </div>

                                <div class="perm-group-body">
                                    @foreach ($permissions as $permission)
                                    @php $isGranted = $role->permissions->contains('id',$permission->id); @endphp

                                    <div class="perm-row"
                                        data-perm-search="{{ strtolower($permission->name . ' ' . $permission->slug) }}">
                                        <div class="perm-main">
                                            <div class="perm-title-row">
                                                <span class="perm-label">{{ $permission->name }}</span>
                                            </div>
                                            <div class="perm-slug">{{ $permission->slug }}</div>
                                        </div>

                                        <div class="perm-row-actions">
                                            <span
                                                class="perm-status {{ $isGranted ? 'status-granted' : 'status-denied' }}">
                                                {{ $isGranted ? 'Granted' : 'Denied' }}
                                            </span>

                                            <label class="toggle-switch {{ $isSuperRole ? 'disabled' : '' }}">
                                                <input type="checkbox" name="permissions[{{ $role->id }}][]"
                                                    value="{{ $permission->id }}" class="perm-toggle"
                                                    data-role="{{ $role->id }}" data-module="{{ $mSlug }}"
                                                    data-color="{{ $icol }}" data-perm-name="{{ $permission->name }}"
                                                    data-perm-slug="{{ $permission->slug }}" {{ $isGranted ? 'checked'
                                                    : '' }} {{ $isSuperRole ? 'disabled' : '' }}
                                                    onchange="onPermChange(this)">
                                                <span class="toggle-track"></span>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @empty
                            <div style="text-align:center; padding:60px 20px;">
                                <i class="fa-solid fa-shield-halved"
                                    style="font-size:40px; color:#e5e7eb; margin-bottom:12px;"></i>
                                <p style="font-size:14px; font-weight:700; color:#6b7280;">No permissions
                                    found.</p>
                            </div>
                            @endforelse
                    </div>

                    @if (!$isSuperRole)
                    <div class="floating-save-bar" id="footer-bar-{{ $role->id }}">
                        <div class="fsb-text">
                            <span class="fsb-title">Unsaved changes</span>
                            <span class="fsb-sub">0 changes</span>
                        </div>
                        <div class="fsb-actions">
                            <button type="button" class="btn-view-as fsb-view-as" onclick="openViewAs()">
                                <i class="fa-solid fa-eye"></i> View As
                                <span class="va-count-badge">0</span>
                            </button>
                            <button type="button" class="btn-discard"
                                onclick="discardChanges('{{ $role->id }}')">Discard</button>
                            <button type="button" class="btn-save-float" id="save-btn-{{ $role->id }}"
                                onclick="ajaxSaveRole('{{ $role->id }}')">
                                <i class="fa-solid fa-floppy-disk"></i> Save
                            </button>
                        </div>
                    </div>
                    @endif

                    </form>
                    @endforeach

                    <div id="permSearchEmptyState" class="empty-state perm-search-empty-state" hidden>
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <h3 class="empty-state-title">No permissions found</h3>
                        <p class="empty-state-sub" id="permSearchEmptyText">Try a different permission name or
                            slug.</p>
                        <button type="button" class="empty-state-btn" id="permSearchEmptyClearBtn">
                            <i class="fa-solid fa-xmark"></i>
                            Clear search
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </div>
</main>

<dialog id="newRoleModal" class="modern-modal global-dialog role-modal role-modal-create">
    <div class="modal-icon primary modal-icon-hoverable">
        <i class="fa-solid fa-user-shield"></i>
    </div>

    <h2 class="modal-title">Create New Role</h2>
    <div class="modal-body">Define a new role and assign permissions to it right away.</div>

    <form id="createRoleForm" action="{{ route('admin.role_permissions.store_role') }}" method="POST">
        @csrf

        <div class="modal-form-group st-form-group">
            <label class="modal-label field-label">Role Name</label>
            <div class="st-input-wrap">
                <i class="fa-solid fa-tag st-input-icon"></i>
                <input type="text" id="newRoleName" name="name" class="st-input" placeholder="e.g. Dental Intern"
                    autocomplete="off">
            </div>
        </div>

        <div class="modal-form-group st-form-group">
            <label class="modal-label field-label">Role Slug</label>
            <div class="st-input-wrap">
                <i class="fa-solid fa-link st-input-icon"></i>
                <input type="text" id="newRoleSlug" name="slug" class="st-input" placeholder="e.g. dental-intern"
                    autocomplete="off">
            </div>
        </div>

        <div id="newRoleError" class="modal-inline-error" style="display:none;"></div>

        <div class="modal-actions">
            <button type="button" class="modal-btn-cancel modal-btn-ghost" onclick="closeNewRoleModal()">
                Cancel
            </button>

            <button type="submit" class="modal-btn-confirm modal-btn-confirm-approve primary" id="btnSubmitNewRole">
                <i class="fa-solid fa-plus"></i>
                Create Role
            </button>
        </div>
    </form>
</dialog>

<dialog id="deleteRoleModal" class="modern-modal global-dialog role-modal role-modal-delete">
    <div class="modal-icon danger modal-icon-hoverable">
        <i class="fa-solid fa-trash-can"></i>
    </div>

    <h2 class="modal-title">Delete Role</h2>

    <div class="modal-body">
        Are you sure you want to permanently delete
        <span class="modal-highlight" id="deleteRoleName"></span>?

        <span class="modal-danger-note">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <span>This action cannot be undone.</span>
        </span>
    </div>

    <div class="modal-actions">
        <button type="button" class="modal-btn-cancel modal-btn-ghost" onclick="closeDeleteModal()">
            Cancel
        </button>

        <form id="deleteRoleForm" method="POST" style="margin:0;">
            @csrf
            @method('DELETE')

            <button type="submit" class="modal-btn-confirm modal-btn-confirm-reject danger">
                <i class="fa-solid fa-trash-can"></i>
                Delete
            </button>
        </form>
    </div>
</dialog>

<dialog id="resetConfirmModal" class="modern-modal global-dialog role-modal role-modal-reset">
    <div class="modal-icon warning modal-icon-hoverable">
        <i class="fa-solid fa-rotate-left"></i>
    </div>

    <h2 class="modal-title">Reset to Defaults?</h2>

    <div class="modal-body reset-defaults-body">
        <p class="reset-defaults-copy">
            This restores original permissions for
            <strong>Super Admin</strong>, <strong>Dentist</strong>, and <strong>Patient</strong>.
        </p>

        <div class="modal-warning-note">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Custom changes will be lost. This cannot be undone.</span>
        </div>
    </div>

    <div class="modal-actions role-reset-modal-actions">
        <button type="button" class="modal-btn-cancel modal-btn-ghost" onclick="closeResetConfirm()">
            Cancel
        </button>

        <button type="button" id="resetConfirmBtn" class="modal-btn-confirm modal-btn-confirm-warning warning"
            onclick="confirmResetDefaults()">
            <i class="fa-solid fa-rotate-left"></i>
            Yes, Reset
        </button>
    </div>
</dialog>

<div id="vaOverlay">
    <div class="va-panel">
        <div class="va-head">
            <div
                style="width:40px;height:40px;border-radius:10px;background:#eff6ff;color:#3b82f6;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                <i class="fa-solid fa-eye"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:1.1rem;font-weight:800;color:#111827;margin-bottom:2px;">View As Role</div>
                <div style="font-size:.8rem;color:#6b7280;" id="vaSubtitle">Select a role to preview dashboard access
                </div>
            </div>
            <button onclick="closeViewAs()"
                style="width:32px;height:32px;border-radius:8px;background:#f3f4f6;border:none;cursor:pointer;color:#6b7280;"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="va-body">
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
    </div>
</div>

<div id="patientPickerOverlay">
    <div class="va-panel">
        <div class="va-head">
            <div
                style="width:40px;height:40px;border-radius:10px;background:#f0fdf4;color:#22c55e;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                <i class="fa-solid fa-user-injured"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:1.1rem;font-weight:800;color:#111827;margin-bottom:2px;">Select Patient Account
                </div>
                <div style="font-size:.8rem;color:#6b7280;">Choose which patient to impersonate</div>
            </div>
            <button onclick="closePatientPicker()"
                style="width:32px;height:32px;border-radius:8px;background:#f3f4f6;border:none;cursor:pointer;color:#6b7280;"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="va-body">
            <div class="search-wrap global-search patient-picker-search" data-search-wrapper
                style="margin-bottom: 16px;">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="patientPickerSearch" placeholder="Search patient name or email..."
                    class="search-input no-voice" data-search-input oninput="filterPatientPicker(this.value)">
                <button type="button" id="patientPickerSearchClearBtn" class="search-clear" data-search-clear
                    aria-label="Clear patient search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="patientPickerList"></div>
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
        module: 'Data Backup',
        color: '#ea580c'
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

    function openRoleDialog(dialog) {
        if (!dialog) return;

        dialog.classList.remove('is-closing');

        if (!dialog.open) {
            dialog.showModal();
        }

        dialog.style.top = '50%';
        dialog.style.left = '50%';
        dialog.style.removeProperty('transform');

        requestAnimationFrame(() => {
            dialog.classList.add('is-open');
        });
    }

    function closeRoleDialog(dialogId, afterClose = null) {
        const dialog = document.getElementById(dialogId);
        if (!dialog) return;

        dialog.classList.remove('is-open');
        dialog.classList.add('is-closing');

        setTimeout(() => {
            dialog.classList.remove('is-closing');

            if (dialog.open) {
                dialog.close();
            }

            if (typeof afterClose === 'function') {
                afterClose();
            }
        }, 220);
    }

    function openRoleOverlay(overlay) {
        if (!overlay) return;

        overlay.classList.remove('is-closing');

        if (overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }

        overlay.classList.add('open');

        requestAnimationFrame(() => {
            overlay.classList.add('is-open');
        });

        document.body.style.overflow = 'hidden';
    }

    function closeRoleOverlay(overlayId, afterClose = null) {
        const overlay = document.getElementById(overlayId);
        if (!overlay || overlay.classList.contains('is-closing')) return;

        overlay.classList.remove('is-open');
        overlay.classList.add('is-closing');

        requestAnimationFrame(() => {
            overlay.classList.remove('open');
        });

        setTimeout(() => {
            overlay.classList.remove('is-closing');
            document.body.style.overflow = '';

            if (typeof afterClose === 'function') {
                afterClose();
            }
        }, 220);
    }

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

    function initRoleForms() {
        mountFloatingSaveBars();
        initialStates = {};
        savedGrants = {};

        const firstActiveCard = document.querySelector('.role-card.active');
        if (firstActiveCard) activeRoleId = firstActiveCard.dataset.roleId;

        document.querySelectorAll('.role-form').forEach(form => {
            const roleId = form.dataset.roleId;
            if (!roleId) return;

            initialStates[roleId] = {};
            savedGrants[roleId] = [];

            form.querySelectorAll('.perm-toggle').forEach(input => {
                initialStates[roleId][input.value] = input.checked;
                if (input.checked) {
                    savedGrants[roleId].push({
                        name: input.dataset.permName || '',
                        slug: input.dataset.permSlug || '',
                        color: input.dataset.color || '#4b5563'
                    });
                }
            });

            const bar = document.getElementById('footer-bar-' + roleId);
            if (bar) bar.classList.remove('show');

            const modules = [...new Set(Array.from(form.querySelectorAll('.perm-toggle')).map(t => t.dataset
                .module).filter(Boolean))];
            modules.forEach(module => {
                const sample = form.querySelector(`.perm-toggle[data-module="${module}"]`);
                if (!sample) return;
                syncGroupMaster(roleId, module);
                updateGroupCount(roleId, module);
                updateDots(roleId, module, sample.dataset.color || '#4b5563');
            });
        });

        updateViewAsBtn();
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

    document.addEventListener('DOMContentLoaded', () => {
        mountFloatingSaveBars();
        const firstCard = document.querySelector('.role-card');
        const protectedBanner = document.getElementById('protectedBanner');
        const permSearch = document.getElementById('permSearch');
        const permSearchClearBtn = document.getElementById('permSearchClearBtn');

        function syncPermSearchClear() {
            if (!permSearch || !permSearchClearBtn) return;
            permSearchClearBtn.classList.toggle('show', (permSearch.value || '').trim().length > 0);
        }

        function clearPermissionSearch() {
            if (!permSearch) return;
            permSearch.value = '';
            filterPerms('');
            syncPermSearchClear();

            const status = permSearch.closest('.perm-search-row')?.querySelector('[data-voice-status]');
            if (status) status.classList.add('hidden');

            permSearch.focus();
        }

        if (permSearchClearBtn && !permSearchClearBtn.dataset.bound) {
            permSearchClearBtn.dataset.bound = '1';
            permSearchClearBtn.addEventListener('click', clearPermissionSearch);
        }

        document.getElementById('permSearchEmptyClearBtn')?.addEventListener('click', clearPermissionSearch);

        if (permSearch && !permSearch.dataset.clearSyncBound) {
            permSearch.dataset.clearSyncBound = '1';
            permSearch.addEventListener('input', syncPermSearchClear);
        }

        window.initGlobalVoiceInputs?.(document);
        document.dispatchEvent(new CustomEvent('voice:refresh', {
            detail: { root: document }
        }));

        syncPermSearchClear();

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

        document.getElementById('newRoleName')?.addEventListener('input', function () {
            document.getElementById('newRoleSlug').value = this.value.toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
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

        const permSearch = document.getElementById('permSearch');
        if (permSearch) permSearch.value = '';
        const permSearchClearBtn = document.getElementById('permSearchClearBtn');
        if (permSearchClearBtn) {
            permSearchClearBtn.classList.remove('show');
            permSearchClearBtn.classList.remove('hidden');
        }
        filterPerms('');

        activeRoleId = roleId;
        updateFABVisibility();
    }

    function updateViewAsBtn() {
        let totalSavedRoles = 0;
        Object.values(savedGrants).forEach(grants => {
            if (grants.length > 0) totalSavedRoles++;
        });

        document.querySelectorAll('.btn-view-as:not(.fsb-view-as)').forEach(btn => {
            if (totalSavedRoles > 0) {
                btn.classList.add('show');
            } else {
                btn.classList.remove('show');
            }
            const badge = btn.querySelector('.va-count-badge');
            if (badge) badge.textContent = totalSavedRoles;
        });

        updateFABVisibility();
    }

    function updateFABVisibility() {
        document.querySelectorAll('.floating-save-bar').forEach(b => b.classList.remove('show'));

        if (!activeRoleId) return;
        const bar = document.getElementById('footer-bar-' + activeRoleId);
        if (!bar) return;

        if (isModalActive) {
            return;
        }

        const form = document.getElementById('form-role-' + activeRoleId);
        if (!form) return;

        let isDirty = false;
        let changesCount = 0;

        form.querySelectorAll('.perm-toggle').forEach(t => {
            const isInitiallyChecked = initialStates[activeRoleId][t.value];
            if (t.checked !== isInitiallyChecked) {
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
        const btnViewAs = bar.querySelector('.btn-view-as.fsb-view-as');

        if (isDirty) {
            bar.classList.add('show');
            title.textContent = 'Unsaved changes';
            sub.textContent = changesCount + ' unsaved change' + (changesCount > 1 ? 's' : '');
            sub.style.display = 'block';
            btnDiscard.style.display = 'inline-block';
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

    function discardChanges(roleId) {
        const form = document.getElementById('form-role-' + roleId);
        if (!form) return;

        form.querySelectorAll('.perm-toggle').forEach(t => {
            const initVal = initialStates[roleId][t.value];
            if (t.checked !== initVal) {
                t.checked = initVal;
                updatePermVisuals(t);
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
        updateFABVisibility();
    }

    function updatePermVisuals(input) {
        const row = input.closest('.perm-row');
        const badge = row.querySelector('.perm-status');
        const label = row.querySelector('.perm-label');
        const color = input.dataset.color;

        if (input.checked) {
            badge.textContent = 'Granted';
            badge.className = 'perm-status status-granted';
            badge.style.removeProperty('background');
            badge.style.removeProperty('color');
            label?.classList.remove('is-denied');
        } else {
            badge.textContent = 'Denied';
            badge.className = 'perm-status status-denied';
            badge.style.removeProperty('background');
            badge.style.removeProperty('color');
            label?.classList.add('is-denied');
        }
    }

    let allExpanded = true;

    function togglePermGroup(header) {
        const body = header.nextElementSibling;
        const chev = header.querySelector('.chevron');
        const isCollapsed = body.classList.contains('collapsed');
        body.classList.toggle('collapsed');
        chev.classList.toggle('collapsed', !isCollapsed);
    }

    function toggleAllGroups() {
        const btn = document.getElementById('collapseBtn');
        const form = [...document.querySelectorAll('.role-form')].find(f => f.style.display === 'block');
        if (!form) return;
        allExpanded = !allExpanded;
        form.querySelectorAll('.perm-group-body').forEach(b => b.classList.toggle('collapsed', !allExpanded));
        form.querySelectorAll('.chevron').forEach(c => c.classList.toggle('collapsed', !allExpanded));
        btn.textContent = allExpanded ? 'Collapse All' : 'Expand All';
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

    function toggleGroupPerms(wrapper, roleId, mSlug, currentlyAllOn) {
        const newState = !currentlyAllOn;
        wrapper.setAttribute('onclick',
            `event.stopPropagation(); toggleGroupPerms(this,'${roleId}','${mSlug}',${newState})`);
        const form = document.getElementById('form-role-' + roleId);
        if (!form) return;
        form.querySelectorAll(`.perm-toggle[data-module="${mSlug}"]`).forEach(t => {
            if (t.disabled) return;
            t.checked = newState;
            updatePermVisuals(t);
        });
        const master = form.querySelector(`.group-master[data-module="${mSlug}"]`);
        if (master) master.checked = newState;

        updateDots(roleId, mSlug, form.querySelector(`.perm-toggle[data-module="${mSlug}"]`)?.dataset.color);
        updateGroupCount(roleId, mSlug);
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

    function updateGroupCount(roleId, mSlug) {
        const form = document.getElementById('form-role-' + roleId);
        if (!form) return;
        const dotsEl = form.querySelector(`[id="dots-${roleId}-${mSlug}"]`);
        if (!dotsEl) return;
        const gc = dotsEl.closest('.group-card');
        if (!gc) return;
        const all = [...gc.querySelectorAll('.perm-toggle')];
        const countEl = gc.querySelector('.group-count');
        if (countEl) countEl.textContent = `${all.filter(t => t.checked).length} of ${all.length} enabled`;
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

        fetch('{{ route('admin.role_permissions.update') }}', {
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
                form.querySelectorAll('.perm-toggle').forEach(input => {
                    initialStates[roleId][input.value] = input.checked;
                });

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
        q = (q || '').toLowerCase().trim();
        const form = [...document.querySelectorAll('.role-form')].find(f => f.style.display === 'block');
        if (!form) return;

        const permSearchClearBtn = document.getElementById('permSearchClearBtn');
        if (permSearchClearBtn) permSearchClearBtn.classList.toggle('show', q.length > 0);

        let visibleGroups = 0;

        form.querySelectorAll('.perm-row').forEach(row => {
            row.style.display = (!q || (row.dataset.permSearch || '').includes(q)) ? '' : 'none';
        });

        form.querySelectorAll('.perm-group').forEach(group => {
            const visible = [...group.querySelectorAll('.perm-row')].some(r => r.style.display !== 'none');
            group.style.display = visible ? '' : 'none';
            if (visible) visibleGroups++;
            if (q && visible) {
                const b = group.querySelector('.perm-group-body');
                if (b) b.classList.remove('collapsed');
            }
        });

        const empty = document.getElementById('permSearchEmptyState');
        const emptyText = document.getElementById('permSearchEmptyText');

        if (empty) {
            const hasNoMatches = q.length > 0 && visibleGroups === 0;
            empty.hidden = !hasNoMatches;
            empty.classList.toggle('show', hasNoMatches);
            if (emptyText) {
                emptyText.textContent = hasNoMatches ?
                    `No permission matched “${q}”. Try another keyword.` :
                    'Try a different permission name or slug.';
            }
        }
    }

    function openNewRoleModal() {
        isModalActive = true;
        updateFABVisibility();

        document.getElementById('newRoleName').value = '';
        document.getElementById('newRoleSlug').value = '';
        document.getElementById('newRoleError').style.display = 'none';

        const modal = document.getElementById('newRoleModal');
        openRoleDialog(modal);

        document.dispatchEvent(new CustomEvent('voice:refresh', {
            detail: { root: modal }
        }));
    }

    function closeNewRoleModal() {
        closeRoleDialog('newRoleModal', () => {
            isModalActive = false;
            updateFABVisibility();
        });
    }

    document.getElementById('createRoleForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const name = document.getElementById('newRoleName').value.trim();
        const slug = document.getElementById('newRoleSlug').value.trim();
        const errEl = document.getElementById('newRoleError');

        if (!name || !slug) {
            errEl.textContent = 'Please fill out all fields.';
            errEl.style.display = 'block';
            return;
        }
        if (document.querySelector(`.role-card[data-slug="${slug}"]`)) {
            errEl.textContent = 'A role with this slug already exists.';
            errEl.style.display = 'block';
            return;
        }

        const form = this;
        const btn = document.getElementById('btnSubmitNewRole');

        btn.disabled = true;
        btn.innerHTML = 'Creating...';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(async res => {
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    let errorMsg = 'Could not create role.';
                    if (data.errors) errorMsg = Object.values(data.errors).flat().join(' ');
                    else if (data.message) errorMsg = data.message;
                    throw new Error(errorMsg);
                }
                return res;
            })
            .then(() => {
                closeNewRoleModal();
                if (typeof showToast === 'function') {
                    showToast('Success', 'Role created successfully.', 'success');
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
                                    detail: { root: currentGrid }
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
                        btn.innerHTML = 'Create Role';
                    });
            })
            .catch(err => {
                errEl.textContent = err.message;
                errEl.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Create Role';
            });
    });

    const PROTECTED_ROLE_SLUGS = ['admin', 'patient', 'dentist', 'super_admin', 'super-admin', 'superadmin'];

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
        document.getElementById('deleteRoleForm').action = `/admin/role-permissions/${roleId}/destroy`;
        const deleteModal = document.getElementById('deleteRoleModal');
        openRoleDialog(deleteModal);
    }

    function closeDeleteModal() {
        closeRoleDialog('deleteRoleModal', () => {
            isModalActive = false;
            updateFABVisibility();
        });
    }

    document.getElementById('deleteRoleForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('.modal-btn-confirm');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(async res => {
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || 'Could not delete role.');
                }
                return res;
            })
            .then(() => {
                closeDeleteModal();
                if (typeof showToast === 'function') {
                    showToast('Success', 'Role deleted successfully.', 'success');
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
        isModalActive = true;
        updateFABVisibility();

        const resetModal = document.getElementById('resetConfirmModal');
        openRoleDialog(resetModal);
    }

    function closeResetConfirm() {
        closeRoleDialog('resetConfirmModal', () => {
            isModalActive = false;
            updateFABVisibility();
        });
    }

    function confirmResetDefaults() {
        const confirmBtn = document.getElementById('resetConfirmBtn');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = 'Resetting…';

        fetch('/admin/role-permissions/reset', {
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
                            currentGrid.innerHTML = newGrid.innerHTML;

                            document.dispatchEvent(new CustomEvent('voice:refresh', {
                                detail: { root: currentGrid }
                            }));
                            const firstRole = document.querySelector('.role-card');
                            if (firstRole) selectRole(firstRole);

                            initRoleForms();
                            keepRoleListLayout();
                            syncScrollStateForSaveBar();
                        }
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = 'Yes, Reset';
                    });
            })
            .catch(err => {
                closeResetConfirm();
                if (typeof showToast === 'function') {
                    showToast('Error', err.message || 'Could not reset.', 'error');
                }
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = 'Yes, Reset';
            });
    }

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
        openRoleOverlay(overlay);
    }

    function closeViewAs() {
        closeRoleOverlay('vaOverlay', () => {
            isModalActive = false;
            updateFABVisibility();
        });
    }

    let patientAccountsCache = [];

    function escapeHtml(v) {
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closePatientPicker() {
        closeRoleOverlay('patientPickerOverlay', () => {
            isModalActive = false;
            updateFABVisibility();
        });
    }

    function redirectToRole(roleId, roleName, roleSlug, color) {
        if (roleSlug === 'patient' || roleSlug === 'patient_role') {
            closeViewAs();
            openPatientPicker(roleName, roleSlug, color);
            return;
        }

        closeViewAs();
        triggerRedirect(roleName, roleSlug, null, color, `Loading ${roleName} view for Super Admin`);
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
                document.getElementById('patientPickerSearchClearBtn')?.classList.remove('show');
                const patientOverlay = document.getElementById('patientPickerOverlay');

                if (patientOverlay && patientOverlay.parentElement !== document.body) {
                    document.body.appendChild(patientOverlay);
                }
                openRoleOverlay(patientOverlay);
            })
            .catch(err => {
                if (typeof showToast === 'function') {
                    showToast('Error', err.message || 'Unable to load patients', 'error');
                }
            });
    }

    function renderPatientPicker(patients) {
        const list = document.getElementById('patientPickerList');
        if (!list) return;
        if (!patients.length) {
            list.innerHTML = `
                <div class="empty-state patient-picker-empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <h3 class="empty-state-title">No patients found</h3>
                    <p class="empty-state-sub">Try a different patient name or email.</p>
                </div>`;
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
        q = (q || '').toLowerCase().trim();
        const clearBtn = document.getElementById('patientPickerSearchClearBtn');
        if (clearBtn) clearBtn.classList.toggle('show', q.length > 0);

        if (!q) {
            renderPatientPicker(patientAccountsCache);
            return;
        }

        const filtered = patientAccountsCache.filter(p => ((p.name || '') + (p.email || '')).toLowerCase().includes(q));
        renderPatientPicker(filtered);
    }

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('#patientPickerSearchClearBtn');
        if (!btn) return;

        const input = document.getElementById('patientPickerSearch');
        if (!input) return;

        input.value = '';
        filterPatientPicker('');
        input.focus();
    });

    function startPatientImpersonation(roleName, roleSlug, color, patientId, patientName) {
        closePatientPicker();
        triggerRedirect(patientName, roleSlug, patientId, color, 'Loading patient dashboard for Super Admin');
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