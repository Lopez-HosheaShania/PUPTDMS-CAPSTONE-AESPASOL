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
                    </div>

                    <div class="page-banner-actions">
                        @if ($canCreateRoles)
                            <button type="button" class="ui-btn ui-btn-primary" data-ui-click="action-39518fccd42a">
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
                        function getRoleMeta($name, $slug)
                        {
                            $name = strtolower($name);
                            $slug = strtolower($slug);

                            if (
                                $slug === 'admin' ||
                                str_contains($name, 'admin') ||
                                str_contains($slug, 'staff') ||
                                str_contains($slug, 'clinic')
                            ) {
                                return [
                                    'theme' => 'admin',
                                ];
                            }

                            if (str_contains($name, 'dentist') || str_contains($slug, 'dentist')) {
                                return [
                                    'theme' => 'dentist',
                                ];
                            }

                            if (
                                str_contains($name, 'patient') ||
                                str_contains($slug, 'patient') ||
                                str_contains($name, 'student') ||
                                str_contains($slug, 'student')
                            ) {
                                return [
                                    'theme' => 'patient',
                                ];
                            }

                            return [
                                'theme' => 'none',
                                'label' => 'Custom',
                            ];
                        }

                        $totalPerms = $groupedPermissions->flatten()->count();
                    @endphp

                    <div class="role-list-header">Active Roles ({{ $roles->count() }})</div>
                    <div class="sidebar-stack">

                        <div class="role-list-container role-list-view" id="roleListContainer">
                            @foreach ($roles as $i => $role)
                                @php
                                    $roleMeta = getRoleMeta($role->name, $role->slug);
                                    $granted = $role->permissions->count();
                                    $pct = $totalPerms > 0 ? round(($granted / $totalPerms) * 100) : 0;
                                    $isHighlighted =
                                        isset($highlightRoleId) && (int) $highlightRoleId === (int) $role->id;
                                    $isFirst = isset($highlightRoleId) ? $isHighlighted : $i === 0;
                                    $isProtectedRole = in_array(strtolower($role->slug), [
                                        'admin',
                                        'patient',
                                        'dentist',
                                    ]);
                                @endphp

                                <div class="card role-card {{ $isFirst ? 'active' : '' }}"
                                    data-role-id="{{ $role->id }}" data-role-name="{{ $role->display_name }}"
                                    data-role-theme="{{ $roleMeta['theme'] }}" data-granted="{{ $granted }}"
                                    data-total="{{ $totalPerms }}" data-pct="{{ $pct }}"
                                    data-slug="{{ $role->slug }}" data-ui-click="action-eee17b73e521">

                                    <div class="role-card-header">

                                        <div class="role-card-copy">
                                            <div class="role-card-title-row">
                                                <span class="role-name-label">
                                                    {{ $role->display_name }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="count-label">
                                            Permissions: {{ $granted }} / {{ $totalPerms }}
                                        </div>

                                        @if (!$isProtectedRole && $canDeleteCustomRoles)
                                            <button type="button" class="ui-action-btn ui-action-delete"
                                                data-ui-click="action-4befe37bff71"
                                                data-ui-click-arg-0="{{ $role->id }}"
                                                data-ui-click-arg-1="{{ json_encode($role->name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                                                data-tooltip="Delete role" data-tooltip-tone="delete"
                                                aria-label="Delete role">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        @endif

                                    </div>

                                    <progress class="progress-bar" value="{{ $pct }}" max="100"></progress>
                                </div>
                            @endforeach
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
                                            id="globalViewAsBtn" data-ui-click="action-e9aac4adc59f"
                                            data-tooltip="View as role" data-tooltip-tone="view" aria-label="View as role">
                                            <i class="fa-solid fa-eye"></i>

                                            <span class="va-count-badge" id="globalVaBadge">
                                                0
                                            </span>
                                        </button>
                                    @endif

                                    <x-button id="collapseBtn" type="button" variant="secondary" size="sm"
                                        icon="fa-solid fa-angles-up" data-ui-click="action-b1ec55a9368e">
                                        Collapse All
                                    </x-button>

                                    <x-button id="resetDefaultsBtn" type="button" variant="warning" size="sm"
                                        icon="fa-solid fa-rotate-left" data-ui-click="action-93a40e63c926">
                                        Reset Defaults
                                    </x-button>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="role-permission-content">

                        @foreach ($roles as $ri => $role)
                            @php
                                $isActiveRole = isset($highlightRoleId)
                                    ? (int) $highlightRoleId === (int) $role->id
                                    : $ri === 0;

                                $moduleIcons = [
                                    'Dental Records' => 'fa-notes-medical',
                                    'Patients' => 'fa-user-group',
                                    'Appointments' => 'fa-calendar-days',
                                    'Document Requests' => 'fa-envelope-open-text',
                                    'Document Templates' => 'fa-file-lines',
                                    'Reports' => 'fa-chart-pie',
                                    'General Access' => 'fa-user-shield',
                                    'Inventory' => 'fa-boxes-stacked',
                                    'User Management' => 'fa-user-cog',
                                    'System Settings' => 'fa-screwdriver-wrench',
                                ];
                            @endphp

                            <form id="form-role-{{ $role->id }}" class="role-form" data-role-id="{{ $role->id }}"
                                data-discard-form data-discard-title="Discard permission changes?"
                                data-discard-subtitle="You have unsaved changes for this role."
                                data-discard-message="The permission changes for this role will be restored to their last saved state."
                                {{ !$isActiveRole ? 'hidden' : '' }}>
                                @csrf
                                <input type="hidden" name="role_id" value="{{ $role->id }}">

                                <div class="groups-container">
                                    @forelse($groupedPermissions as $module => $permissions)
                                        @php
                                            $moduleIcon = $moduleIcons[$module] ?? 'fa-shield-halved';
                                            $moduleSlug = Str::slug($module);
                                            $moduleTotal = $permissions->count();
                                            $grantedCount = 0;
                                            foreach ($permissions as $_p) {
                                                if ($role->permissions->contains('id', $_p->id)) {
                                                    $grantedCount++;
                                                }
                                            }
                                            $allGranted = $grantedCount === $moduleTotal;
                                        @endphp

                                        <div class="table-card permission-module-card"
                                            data-group="{{ strtolower($module) }}">

                                            <div class="table-toolbar perm-group-header perm-group"
                                                data-ui-click="action-47ae060a446f">

                                                <div class="table-toolbar-title">

                                                    <div class="card-header-icon">
                                                        <i class="fa-solid {{ $moduleIcon }}"></i>
                                                    </div>

                                                    <div>
                                                        <div class="card-title">
                                                            {{ $module }}
                                                        </div>

                                                        <div class="card-subtitle">
                                                            {{ $grantedCount }} of {{ $moduleTotal }} enabled
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="table-toolbar-actions">

                                                    <div class="dot-row"
                                                        id="dots-{{ $role->id }}-{{ $moduleSlug }}">
                                                        @for ($index = 0; $index < $moduleTotal; $index++)
                                                            <span
                                                                class="status-dot {{ $index < $grantedCount ? 'status-granted' : 'status-denied' }}"></span>
                                                        @endfor
                                                    </div>

                                                    <div class="all-toggle-wrap" data-ui-click="action-2b6cb68857e5">
                                                        <span>All</span>

                                                        <x-switch class="group-master" :checked="$allGranted" :disabled="!$canUpdateRolePermissions"
                                                            data-role="{{ $role->id }}"
                                                            data-module="{{ $moduleSlug }}" data-discard-ignore="true"
                                                            data-ui-change="action-76cbb6258bf9" />
                                                    </div>

                                                    <i class="fa-solid fa-chevron-up chevron"></i>

                                                </div>

                                            </div>

                                            <div class="perm-group-body">

                                                <div class="table-scroll">

                                                    <table class="data-table">
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
                                                                        <x-switch
                                                                            name="permissions[{{ $role->id }}][]"
                                                                            value="{{ $permission->id }}"
                                                                            class="perm-toggle" :checked="$isGranted"
                                                                            :disabled="!$canUpdateRolePermissions"
                                                                            data-role="{{ $role->id }}"
                                                                            data-module="{{ $moduleSlug }}"
                                                                            data-perm-name="{{ $permission->name }}"
                                                                            data-perm-slug="{{ $permission->slug }}"
                                                                            data-ui-change="action-f71c950e5fcd" />
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

                                <x-floating-save-bar id="footer-bar-{{ $role->id }}" title="Unsaved changes"
                                    subtitle="0 changes">
                                    <x-slot:actions>

                                        @if ($canViewAsRole)
                                            <button type="button" class="ui-action-btn ui-action-view fsb-view-as"
                                                data-ui-click="action-e9aac4adc59f" data-tooltip="View as role"
                                                data-tooltip-tone="view" aria-label="View as role">
                                                <i class="fa-solid fa-eye"></i>

                                                <span class="va-count-badge">
                                                    0
                                                </span>
                                            </button>
                                        @endif

                                        <x-button type="button" variant="secondary" class="btn-discard"
                                            data-ui-click="action-01fe488d50f6"
                                            data-ui-click-arg-0="{{ $role->id }}">
                                            Discard
                                        </x-button>

                                        <x-button id="save-btn-{{ $role->id }}" type="button" variant="primary"
                                            icon="fa-solid fa-floppy-disk" class="btn-save-float"
                                            data-ui-click="action-e15c3c78bc8e"
                                            data-ui-click-arg-0="{{ $role->id }}">
                                            Save
                                        </x-button>

                                    </x-slot:actions>
                                </x-floating-save-bar>
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

                <form id="createRoleForm" action="{{ route($routePrefix . '.role_permissions.store_role') }}"
                    method="POST" class="modal-card-form" data-global-validation data-form-validation-rule="createRole"
                    data-discard-form data-discard-title="Discard new role?"
                    data-discard-subtitle="You have unsaved role information."
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
                                    <input type="text" id="newRoleName" name="name" class="form-input-custom"
                                        placeholder="e.g. Dental Intern" data-field-label="Role Name"
                                        minlength="3" maxlength="50" pattern="[A-Za-z]+(?: [A-Za-z]+)*"
                                        data-validation-rule="roleName" data-normalize-spaces="true" data-capitalize-words="true"
                                        data-pattern-message="Role Name may only contain letters and single spaces between words."
                                        data-required-message="Please enter a role name." required autocomplete="off">
                                </div>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="newRoleSlug">
                                    Role Slug
                                    <span class="required-mark">*</span>
                                </label>

                                <div class="global-control-wrap">
                                    <input type="text" id="newRoleSlug" name="slug" class="form-input-custom"
                                        placeholder="e.g. dental-intern" data-field-label="Role Slug"
                                        data-required-message="Please enter a role slug."
                                        data-pattern-message="Use lowercase letters, numbers, and hyphens only."
                                        pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required autocomplete="off">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-ft">
                        <x-button type="button" variant="secondary" data-discard-close="newRoleModal">
                            Cancel
                        </x-button>

                        <x-button id="btnSubmitNewRole" type="submit" variant="primary">
                            Create Role
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($canDeleteCustomRoles)
        <x-delete-confirm-modal id="deleteRoleModal" form-id="deleteRoleForm" name-id="deleteRoleName"
            title="Delete Role" subtitle="This action requires confirmation" message="Are you sure you want to delete"
            helper="This role will be permanently removed." close-callback="action-6acb9f0eb412" />
    @endif

    <div id="resetConfirmModal" class="ui-modal modal-theme-warning" aria-hidden="true">
        <div class="ui-modal-card modal-sm" role="dialog" aria-modal="true" aria-labelledby="resetConfirmTitle"
            data-ui-click="action-22499e15f3ee">

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
                    <div>

                        <p>
                            Reset permissions for
                            <strong>
                                Admin, Dentist, and Patient?
                            </strong>
                        </p>

                        <span>
                            Custom permission changes will be lost.
                            This action cannot be undone.
                        </span>

                    </div>

                </div>

            </div>

            <div class="modal-ft">
                <x-button type="button" variant="secondary" data-ui-click="action-394b0354d213">
                    Cancel
                </x-button>

                <x-button id="resetConfirmBtn" type="button" variant="warning" data-ui-click="action-3fbd142ad2bd">
                    Reset
                </x-button>
            </div>

        </div>
    </div>

    <div id="vaOverlay" class="ui-modal modal-theme-primary" aria-hidden="true">
        <div class="ui-modal-card modal-lg">

            <div class="modal-hd">
                <div class="modal-heading">

                    <div class="modal-icon">
                        <i class="fa-solid fa-eye"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">
                            View As Role
                        </h3>

                        <p class="modal-subtitle">
                            Preview the dashboard access assigned to each role.
                        </p>
                    </div>

                </div>

                <button type="button" class="modal-x" data-modal-close="vaOverlay"
                    aria-label="Close role preview modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div id="vaRoleList" class="modal-form-grid"></div>
            </div>

            <div class="modal-ft">
                <x-button type="button" variant="secondary" data-modal-close="vaOverlay">
                    Close
                </x-button>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        let initialStates = {};
        let savedGrants = {};
        let activeRoleId = null;
        let isModalActive = false;

        function syncScrollStateForSaveBar() {
            updateFABVisibility();
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
            window.FloatingSaveBar
                ?.mount(document);

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
                                name: input.dataset.permName || '',

                                slug: input.dataset.permSlug || '',

                                module: input.dataset.module || ''
                            });
                        }
                    });

                const bar =
                    document.getElementById(
                        'footer-bar-' + roleId
                    );

                if (bar) {
                    window.FloatingSaveBar
                        ?.hide(bar);
                }

                const modules = [...new Set(Array.from(form.querySelectorAll('.perm-toggle')).map(t => t.dataset
                    .module).filter(Boolean))];

                modules.forEach(module => {
                    syncGroupMaster(roleId, module);
                    updateGroupCount(roleId, module);
                    updateDots(roleId, module);
                });

                window.DiscardChanges
                    ?.captureForm(form);
            });

            updateViewAsBtn();

            requestAnimationFrame(() => {
                window.FloatingSaveBar
                    ?.hideAll();

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
            function(value) {
                filterPerms(
                    String(value || '')
                );
            };

        document.addEventListener('DOMContentLoaded', () => {

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
            document
                .querySelectorAll('.role-card')
                .forEach(roleCard => {
                    roleCard.classList.remove(
                        'active'
                    );
                });

            card.classList.add('active');

            const roleId =
                card.dataset.roleId;

            document
                .querySelectorAll('.role-form')
                .forEach(form => {
                    form.hidden = true;
                });

            const form =
                document.getElementById(
                    'form-role-' + roleId
                );

            if (form) {
                form.hidden = false;
            }

            const permSearch =
                document.getElementById(
                    'permSearch'
                );

            if (permSearch) {
                permSearch.value = '';
            }

            filterPerms('');

            activeRoleId = roleId;

            updateFABVisibility();
        }

        function updateViewAsBtn() {
            let totalSavedRoles = 0;

            document
                .querySelectorAll(
                    '.role-card'
                )
                .forEach(card => {
                    const roleId =
                        card.dataset.roleId;

                    const roleSlug =
                        card.dataset.slug;

                    if (
                        !canPreviewRole(
                            roleSlug
                        )
                    ) {
                        return;
                    }

                    const permissions =
                        savedGrants[
                            roleId
                        ] || [];

                    if (
                        permissions.length > 0
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
                'patientPickerOverlay',
            ];

            return modalIds.some(
                id => {
                    const modal =
                        document.getElementById(
                            id
                        );

                    if (!modal) {
                        return false;
                    }

                    return (
                        modal.getAttribute(
                            'aria-hidden'
                        ) === 'false' ||
                        modal.classList.contains(
                            'show'
                        ) ||
                        modal.classList.contains(
                            'open'
                        )
                    );
                }
            );
        }

        function updateFABVisibility() {
            window.FloatingSaveBar?.hideAll();

            if (!activeRoleId) {
                return;
            }

            const bar =
                document.getElementById(
                    'footer-bar-' + activeRoleId
                );

            if (!bar) {
                return;
            }

            if (
                isModalActive &&
                hasActiveRolePermissionModal()
            ) {
                return;
            }

            const form =
                document.getElementById(
                    'form-role-' + activeRoleId
                );

            if (!form) {
                return;
            }

            const baseline =
                initialStates[activeRoleId];

            if (!baseline) {
                return;
            }

            let changesCount = 0;

            form
                .querySelectorAll('.perm-toggle')
                .forEach(toggle => {
                    const stateKey =
                        getPermissionStateKey(toggle);

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
                        baseline[stateKey]
                    ) {
                        changesCount++;
                    }
                });

            if (changesCount === 0) {
                window.FloatingSaveBar?.hide(bar);
                return;
            }

            const title =
                bar.querySelector('.fsb-title');

            const subtitle =
                bar.querySelector('.fsb-sub');

            if (title) {
                title.textContent =
                    'Unsaved changes';
            }

            if (subtitle) {
                subtitle.textContent =
                    `${changesCount} unsaved change${changesCount > 1 ? 's' : ''}`;
            }

            const viewAsButton =
                bar.querySelector('.fsb-view-as');

            if (viewAsButton) {
                const badge =
                    viewAsButton.querySelector(
                        '.va-count-badge'
                    );

                if (badge) {
                    const totalSavedRoles =
                        Object.values(savedGrants)
                        .filter(
                            grants =>
                            grants.length > 0
                        )
                        .length;

                    badge.textContent =
                        totalSavedRoles;
                }
            }

            window.FloatingSaveBar?.show(bar);
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

            const modules = [...new Set(Array.from(form.querySelectorAll('.perm-toggle')).map(t => t.dataset.module)
                .filter(
                    Boolean))];
            modules.forEach(module => {
                const sample = form.querySelector(`.perm-toggle[data-module="${module}"]`);
                syncGroupMaster(roleId, module);
                updateGroupCount(roleId, module);
                updateDots(roleId, module);
            });

            updateRoleCardStats(roleId);
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
                form => !form.hidden
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
                mSlug
            );

            updateGroupCount(
                roleId,
                mSlug
            );

            updateRoleCardStats(
                roleId
            );

            updateFABVisibility();
        }

        function onPermChange(input) {
            updatePermVisuals(input);
            const roleId = input.dataset.role;
            const mSlug = input.dataset.module;

            updateDots(roleId, mSlug);
            updateGroupCount(roleId, mSlug);
            syncGroupMaster(roleId, mSlug);
            updateRoleCardStats(roleId);
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

        function updateDots(roleId, moduleSlug) {
            const container =
                document.getElementById(
                    `dots-${roleId}-${moduleSlug}`
                );

            if (!container) return;

            const form =
                document.getElementById(
                    'form-role-' + roleId
                );

            if (!form) return;

            const toggles = [
                ...form.querySelectorAll(
                    `.perm-toggle[data-module="${moduleSlug}"]`
                )
            ];

            const dots =
                container.querySelectorAll(
                    '.status-dot'
                );

            toggles.forEach((toggle, index) => {
                const dot = dots[index];

                if (!dot) return;

                dot.classList.toggle(
                    'status-granted',
                    toggle.checked
                );

                dot.classList.toggle(
                    'status-denied',
                    !toggle.checked
                );
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
                    '.card-subtitle'
                );

            if (countEl) {
                countEl.textContent =
                    `${enabledCount} of ${toggles.length} enabled`;
            }
        }

        function updateRoleCardStats(roleId) {
            const form =
                document.getElementById(
                    'form-role-' + roleId
                );

            if (!form) {
                return;
            }

            const toggles = [
                ...form.querySelectorAll(
                    '.perm-toggle'
                )
            ];

            const total =
                toggles.length;

            const granted =
                toggles.filter(
                    toggle => toggle.checked
                ).length;

            const pct =
                total > 0 ?
                Math.round(
                    granted / total * 100
                ) :
                0;

            const card =
                document.querySelector(
                    `.role-card[data-role-id="${roleId}"]`
                );

            if (!card) {
                return;
            }

            const countLabel =
                card.querySelector(
                    '.count-label'
                );

            const progress =
                card.querySelector(
                    '.progress-bar'
                );

            if (countLabel) {
                countLabel.textContent =
                    `Permissions: ${granted} / ${total}`;
            }

            if (progress) {
                progress.value = pct;
            }

            card.dataset.granted =
                granted;

            card.dataset.pct =
                pct;
        }

        function ajaxSaveRole(roleId) {
            const form = document.getElementById('form-role-' + roleId);
            const btn = document.getElementById('save-btn-' + roleId);
            if (!form || !btn) return;

            const previousState =
                initialStates[
                    roleId
                ] || {};

            const newlyGrantedCount = [
                ...form.querySelectorAll(
                    '.perm-toggle'
                )
            ].filter(input => {
                const key =
                    getPermissionStateKey(
                        input
                    );

                return (
                    input.checked &&
                    previousState[key] ===
                    false
                );
            }).length;

            const checkedIds = [
                ...form.querySelectorAll(
                    '.perm-toggle:checked'
                )
            ].map(toggle => toggle.value);

            const csrf =
                document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content ||
                '{{ csrf_token() }}';

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

                    form
                        .querySelectorAll(
                            '.perm-toggle:checked'
                        )
                        .forEach(input => {
                            savedGrants[
                                roleId
                            ].push({
                                name: input.dataset.permName || '',

                                slug: input.dataset.permSlug || '',

                                module: input.dataset.module || ''
                            });
                        });

                    updateViewAsBtn();

                    if (typeof showToast === 'function') {
                        const toastMessage =
                            newlyGrantedCount > 0 ?
                            `${newlyGrantedCount} newly granted permission${newlyGrantedCount === 1 ? '' : 's'} saved successfully.` :
                            'Permissions updated successfully.';

                        showToast(
                            'Permissions saved',
                            toastMessage,
                            'success'
                        );
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
                form => !form.hidden
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

                        row.hidden = !matches;

                        if (matches) {
                            hasVisibleRow =
                                true;
                        }
                    });

                    moduleCard.hidden = !hasVisibleRow;

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

        roleNameField?.addEventListener('input', function() {
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
            ?.addEventListener('submit', function(e) {
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
                            if (data.errors) {
                                window.applyGlobalServerValidationErrors?.(form, data.errors);
                            }
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

        const PROTECTED_ROLE_SLUGS = [
            'admin',
            'patient',
            'dentist'
        ];

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

        document.getElementById('deleteRoleForm').addEventListener('submit', function(e) {
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

        document.addEventListener(
            'click',
            event => {
                const button =
                    event.target.closest(
                        '[data-patient-impersonate]'
                    );

                if (!button) {
                    return;
                }

                event.preventDefault();

                const patientId =
                    button.dataset
                    .impersonatePatientId;

                const patientName =
                    button.dataset
                    .impersonatePatientName ||
                    'Patient';

                if (!patientId) {
                    return;
                }
            }
        );

        function ajaxResetDefaults() {
            const activeForm =
                activeRoleId ?
                document.getElementById(
                    'form-role-' + activeRoleId
                ) :
                null;

            if (
                activeForm &&
                window.DiscardChanges
                ?.isFormDirty(activeForm)
            ) {
                window.DiscardChanges
                    ?.confirmClose(
                        activeForm,
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
                                        form.hidden = true;
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

        function humanizePermissionToken(value) {
            return String(value || '')
                .trim()
                .replace(/[._-]+/g, ' ')
                .replace(
                    /\b\w/g,
                    character =>
                    character.toUpperCase()
                );
        }

        function resolvePermissionDisplay(
            permission
        ) {
            const slug =
                String(
                    permission.slug || ''
                ).trim();

            const parts =
                slug
                .split('.')
                .filter(Boolean);

            /*
             * Example:
             * inventory.view
             *
             * module  -> Inventory
             * action  -> View
             */
            if (parts.length > 1) {
                const moduleKey =
                    parts[0];

                const actionKey =
                    parts
                    .slice(1)
                    .join(' ');

                return {
                    groupKey: moduleKey,

                    groupLabel: humanizePermissionToken(
                        moduleKey
                    ),

                    permissionLabel: humanizePermissionToken(
                        actionKey
                    ),
                };
            }

            /*
             * If the permission slug does not use
             * dot notation, fall back to the module
             * already produced by $groupedPermissions.
             *
             * This avoids inventing another module.
             */
            return {
                groupKey: permission.module,

                groupLabel: humanizePermissionToken(
                    permission.module
                ),

                permissionLabel: permission.name ||
                    humanizePermissionToken(
                        slug
                    ),
            };
        }

        function groupPermissions(
            permissions
        ) {
            const groups =
                new Map();

            permissions.forEach(permission => {
                const resolved =
                    resolvePermissionDisplay(
                        permission
                    );

                const key =
                    resolved.groupKey;

                if (!groups.has(key)) {
                    groups.set(
                        key, {
                            label: resolved.groupLabel,

                            permissions: [],
                        }
                    );
                }

                groups
                    .get(key)
                    .permissions
                    .push({
                        ...permission,

                        displayName: resolved
                            .permissionLabel,
                    });
            });

            return [
                ...groups.values()
            ];
        }

        function canPreviewRole(
            roleSlug
        ) {
            const slug =
                String(roleSlug || '')
                .trim()
                .toLowerCase();

            return [
                'patient',
                'patient_role',
                'dentist'
            ].includes(slug);
        }

        function openViewAs() {
            isModalActive = true;
            updateFABVisibility();

            const overlay =
                document.getElementById(
                    'vaOverlay'
                );

            const list =
                document.getElementById(
                    'vaRoleList'
                );

            if (
                !overlay ||
                !list
            ) {
                return;
            }

            if (
                overlay.parentElement !==
                document.body
            ) {
                document.body.appendChild(
                    overlay
                );
            }

            list.replaceChildren();

            let renderedRoles = 0;

            document
                .querySelectorAll(
                    '.role-card'
                )
                .forEach(card => {
                    const roleId =
                        card.dataset.roleId;

                    const roleName =
                        card.dataset.roleName ||
                        'Role';

                    const roleSlug =
                        String(
                            card.dataset.slug ||
                            ''
                        )
                        .trim()
                        .toLowerCase();

                    if (
                        !canPreviewRole(
                            roleSlug
                        )
                    ) {
                        return;
                    }

                    const permissions =
                        savedGrants[
                            roleId
                        ] || [];

                    if (
                        permissions.length ===
                        0
                    ) {
                        return;
                    }

                    renderedRoles++;

                    const groups =
                        groupPermissions(
                            permissions
                        );

                    const groupHtml =
                        groups
                        .map(group => {
                            const permissionHtml =
                                group.permissions
                                .map(permission => {
                                    return `
                                        <span
                                            class="global-info-pill"
                                        >
                                            ${escapeHtml(
                                                permission
                                                    .displayName
                                            )}
                                        </span>
                                    `;
                                })
                                .join('');

                            return `
                            <div
                                class="va-permission-row"
                            >
                                <div
                                    class="va-permission-module"
                                >
                                    ${escapeHtml(
                                        group.label
                                    )}
                                </div>

                                <div
                                    class="global-info-group"
                                >
                                    ${permissionHtml}
                                </div>
                            </div>
                        `;
                        })
                        .join('');

                    const roleIcon =
                        roleSlug === 'dentist' ?
                        'fa-user-doctor' :
                        'fa-user';

                    list.insertAdjacentHTML(
                        'beforeend',
                        `
        <details
            class="
                global-accordion
                modal-option-panel
                va-role-card
            "
            data-role-id="${escapeHtml(roleId)}"
            data-role-name="${escapeHtml(roleName)}"
            data-role-slug="${escapeHtml(roleSlug)}"
        >
            <summary
                class="global-accordion-summary"
            >
                <div
                    class="modal-inline-control"
                >
                    <div
                        class="modal-inline-main"
                    >
                        <div
                            class="modal-section-heading"
                        >
                            <div
                                class="modal-section-icon"
                            >
                                <i
                                    class="
                                        fa-solid
                                        ${roleIcon}
                                    "
                                    aria-hidden="true"
                                ></i>
                            </div>

                            <div>
                                <h4>
                                    ${escapeHtml(
                                        roleName
                                    )}
                                </h4>

                                <p
                                    class="
                                        va-role-permission-count
                                    "
                                >
                                    <span>
                                        ${permissions.length}
                                        permission${permissions.length === 1 ? '' : 's'}
                                    </span>

                                    <i
                                        class="
                                            fa-solid
                                            fa-chevron-down
                                            global-accordion-chevron
                                        "
                                        aria-hidden="true"
                                    ></i>
                                </p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="
                            ui-btn
                            ui-btn-primary
                            ui-btn-sm
                        "
                        data-va-preview
                        data-accordion-ignore
                    >
                        <i
                            class="
                                fa-solid
                                fa-eye
                            "
                            aria-hidden="true"
                        ></i>

                        <span>
                            Preview dashboard
                        </span>
                    </button>
                </div>
            </summary>

            <div
                class="global-accordion-body"
            >
                <div
                    class="va-permission-list"
                >
                    ${groupHtml}
                </div>
            </div>
        </details>
    `
                    );
                });

            if (
                renderedRoles === 0
            ) {
                window.EmptyState
                    ?.render({
                        host: list,

                        icon: 'fa-user-shield',

                        title: 'No role permissions to preview',

                        message: 'Save at least one permission for a role before previewing its dashboard access.',

                        className: 'empty-state-compact',
                    });
            }

            window.openModal?.(
                'vaOverlay'
            );
        }

        document.addEventListener(
            'DOMContentLoaded',
            () => {
                const params =
                    new URLSearchParams(
                        window.location.search
                    );

                if (
                    params.get(
                        'open_view_as'
                    ) !== '1'
                ) {
                    return;
                }

                window.requestAnimationFrame(
                    () => {
                        openViewAs();
                    }
                );

                params.delete(
                    'open_view_as'
                );

                const query =
                    params.toString();

                const cleanUrl =
                    window.location.pathname +
                    (
                        query ?
                        `?${query}` :
                        ''
                    ) +
                    window.location.hash;

                window.history.replaceState({},
                    document.title,
                    cleanUrl
                );
            }
        );

        document.addEventListener(
            'click',
            event => {
                const preview =
                    event.target.closest(
                        '[data-va-preview]'
                    );

                if (!preview) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const role =
                    preview.closest(
                        '[data-role-id]'
                    );

                if (!role) {
                    return;
                }

                redirectToRole(
                    role.dataset.roleId,
                    role.dataset.roleName,
                    role.dataset.roleSlug
                );
            }
        );

        function closeViewAs() {
            window.closeModal?.('vaOverlay');

            window.setTimeout(() => {
                isModalActive = false;
                updateFABVisibility();
            }, 180);
        }

        function escapeHtml(value) {
            return String(value)
                .replace(
                    /&/g,
                    '&amp;'
                )
                .replace(
                    /</g,
                    '&lt;'
                )
                .replace(
                    />/g,
                    '&gt;'
                )
                .replace(
                    /"/g,
                    '&quot;'
                )
                .replace(
                    /'/g,
                    '&#039;'
                );
        }

        function redirectToRole(
            roleId,
            roleName,
            roleSlug
        ) {
            let normalizedRole =
                String(
                    roleSlug || ''
                )
                .trim()
                .toLowerCase();

            if (
                normalizedRole ===
                'patient_role'
            ) {
                normalizedRole =
                    'patient';
            }

            if (
                ![
                    'patient',
                    'dentist'
                ].includes(
                    normalizedRole
                )
            ) {
                return;
            }

            closeViewAs();

            startRolePreview(
                roleName,
                normalizedRole
            );
        }

        function startRolePreview(
            roleName,
            roleSlug
        ) {
            window.GlobalLoading
                ?.show({
                    title: `Opening ${roleName} preview…`,

                    message: 'Loading synthetic demo data.',
                });

            fetch(
                    "{{ route('admin.role_preview.start') }}", {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',

                            'X-CSRF-TOKEN': '{{ csrf_token() }}',

                            'Accept': 'application/json',
                        },

                        body: JSON.stringify({
                            role: roleSlug,
                        }),
                    }
                )
                .then(
                    async response => {
                        const data =
                            await response
                            .json();

                        if (
                            !response.ok
                        ) {
                            throw new Error(
                                data.message ||
                                'Unable to start role preview.'
                            );
                        }

                        if (
                            !data.redirect
                        ) {
                            throw new Error(
                                'No preview URL was returned.'
                            );
                        }

                        window.location.href =
                            data.redirect;
                    }
                )
                .catch(
                    error => {
                        window.GlobalLoading
                            ?.hide();

                        if (
                            typeof showToast ===
                            'function'
                        ) {
                            showToast(
                                'Preview Error',
                                error.message ||
                                'Unable to open role preview.',
                                'error'
                            );
                        }
                    }
                );
        }
    </script>
@endsection
