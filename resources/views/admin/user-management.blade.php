@extends('layouts.app')

@section('layout-role', $layoutRole ?? 'admin')

@section('title', 'User Management')

@section('styles')
    @vite('resources/css/pages/admin/user-management.css')
@endsection

@section('content')

    @php
        $totalUsers = $totalUsers ?? ($allUsersCount ?? ($users->total() ?? 0));
        $activeCount = $activeCount ?? 0;
        $inactiveCount = $inactiveCount ?? 0;
        $authUser = auth()->user();
        $canViewAccounts = $authUser?->hasPermission('view_account_details') ?? false;
        $canCreateUsers = $authUser?->hasPermission('create_users') ?? false;
        $canDisableUsers = $authUser?->hasPermission('disable_users') ?? false;
        $canUpdateUserRole = $authUser?->hasPermission('update_user_role') ?? false;
        $canUpdateUserPassword = $authUser?->hasPermission('update_user_password') ?? false;
    @endphp

    <main id="mainContent" class="app-page-shell user-management-page page-enter mode-list">
        <div class="w-full">
            <div class="page-banner">
                <div class="page-banner-inner">
                    <div>
                        <h1 class="page-title">User Management</h1>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap w-full sm:w-auto">
                        @if ($canCreateUsers)
                            <button type="button" onclick="openModal('addModal', this)" class="ui-btn ui-btn-primary">
                                <i class="fa-solid fa-user-plus"></i>
                                <span>Add New User</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            @if (session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showSuccessToast("{{ session('success') }}");
                    });
                </script>
            @endif

            @if (session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showErrorToast("{{ session('error') }}");
                    });
                </script>
            @endif

            <div class="admin-page-body">

                <div id="statCards" class="stat-grid admin-dashboard-stat-grid user-management-stat-grid mb-6">
                    <div class="stat-card s-all">
                        <div class="stat-card-info">
                            <span class="stat-label">Total Users</span>
                            <span class="stat-num" id="countTotalUsers">{{ $totalUsers }}</span>
                            <span class="stat-footer">
                                <i class="fa-solid fa-users"></i>
                                All registered system accounts
                            </span>
                        </div>

                        <div class="stat-icon-wrapper">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>

                    <div class="stat-card s-approved">
                        <div class="stat-card-info">
                            <span class="stat-label">Active</span>
                            <span class="stat-num" id="countActiveUsers">{{ $activeCount }}</span>
                            <span class="stat-footer">
                                <i class="fa-solid fa-circle-check"></i>
                                Accounts currently enabled
                            </span>
                        </div>

                        <div class="stat-icon-wrapper">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>

                    <div class="stat-card s-rejected">
                        <div class="stat-card-info">
                            <span class="stat-label">Inactive</span>
                            <span class="stat-num" id="countInactiveUsers">{{ $inactiveCount }}</span>
                            <span class="stat-footer">
                                <i class="fa-solid fa-user-slash"></i>
                                Accounts currently disabled
                            </span>
                        </div>

                        <div class="stat-icon-wrapper">
                            <i class="fa-solid fa-user-slash"></i>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="table-toolbar-title">
                            <div class="card-header-icon">
                                <i class="fa-solid fa-users-gear"></i>
                            </div>

                            <h2 class="card-title">All System Users</h2>

                            <span id="countBadgeUsers" class="table-tag table-tag-neutral">
                                {{ $totalUsers }}
                            </span>
                        </div>

                        <form method="GET" action="{{ route($routeNames['index'] ?? 'admin.user_management') }}"
                            id="umFilterForm" class="table-toolbar-search">
                            <div class="voice-search-row">
                                <x-search-bar id="umSearch" name="search" placeholder="Search name or email…"
                                    :value="$search ?? ''" callback="handleUserManagementSearch" :debounce="350"
                                    clear-label="Clear user search" />

                                <x-voice-input target="#umSearch" status-id="umSearchVoiceStatus" label="Voice search users"
                                    title="Voice search" />
                            </div>
                        </form>

                        <div id="umToolbarActions" class="table-toolbar-actions">
                            <x-view-toggle id="umViewToggle" storage-key="userManagementView" list-view="#umListView"
                                grid-view="#umGridView" />
                        </div>
                    </div>

                    <x-pagination-bar id="umPaginationTopBar" info-id="umPaginationInfoTop" pagination-id="umPaginationTop"
                        position="top" :show-entries="true" page-size-id="umPerPageSelect"
                        page-size-callback="handleUserManagementPerPageChange" :page-size-value="$perPage ?? 10" page-size-label="per page"
                        label="users" :total="$users->total()" :from="$users->firstItem() ?? 0" :to="$users->lastItem() ?? 0" />

                    <div class="table-list-view" id="umListView">
                        <div class="table-list-header um-list-header">
                            <div>User</div>
                            <div>Role</div>
                            <div>Status</div>
                            <div class="um-registered-col">Registered</div>
                            <div class="table-cell-center">Actions</div>
                        </div>

                        <div id="umTableBody">
                            @forelse($users as $user)
                                @php
                                    $displayName = $user->patient?->name ?? $user->name;
                                    $roleSlug = optional($user->role)->slug ?? 'none';
                                    $roleName =
                                        optional($user->role)->display_name ??
                                        (optional($user->role)->name ?? 'Patient');

                                    $userDetails = [
                                        'id' => $user->id,
                                        'name' => $displayName,
                                        'email' => $user->email,
                                        'role' => $roleName,
                                        'status' => ucfirst($user->status),
                                        'source' => 'Users',
                                        'created_at' => $user->created_at
                                            ? $user->created_at->format('M d, Y h:i A')
                                            : 'N/A',
                                        'updated_at' => $user->updated_at
                                            ? $user->updated_at->format('M d, Y h:i A')
                                            : 'N/A',
                                        'phone' => optional($user->patient)->phone ?: ($user->phone ?: 'N/A'),
                                        'birthdate' =>
                                            optional(optional($user->patient)->birthdate)?->format('M d, Y') ??
                                            (optional($user->birthdate)?->format('M d, Y') ?? 'N/A'),
                                        'gender' => optional($user->patient)->gender ?: ($user->gender ?: 'N/A'),
                                        'phone_raw' => optional($user->patient)->phone ?? ($user->phone ?? ''),
                                        'birthdate_raw' =>
                                            optional(optional($user->patient)->birthdate)?->format('Y-m-d') ??
                                            (optional($user->birthdate)?->format('Y-m-d') ?? ''),
                                        'gender_raw' => optional($user->patient)->gender ?? ($user->gender ?? ''),
                                        'patient_profile' => $user->patient ? 'Linked' : 'Not linked',
                                        'last_login_at' =>
                                            optional($user->last_login_at)?->format('M d, Y h:i A') ?? 'Never',
                                    ];
                                @endphp

                                <div class="table-list-row user-table-row" data-name="{{ strtolower($displayName) }}"
                                    data-email="{{ strtolower($user->email) }}" data-role="{{ strtolower($roleName) }}">
                                    <div class="um-list-layout">
                                        <div class="table-primary um-user-cell">
                                            <div class="patient-avatar patient-avatar-md" data-patient-avatar
                                                data-patient-name="{{ $displayName }}"></div>

                                            <div class="um-user-copy">
                                                <strong class="um-user-name">{{ $displayName }}</strong>
                                                <span class="um-user-email">{{ $user->email }}</span>
                                            </div>
                                        </div>

                                        <div class="um-role-cell">
                                            <span class="badge-role role-{{ $roleSlug }}">
                                                {{ $roleName }}
                                            </span>
                                        </div>

                                        <div class="um-status-cell">
                                            <span
                                                class="status-pill {{ $user->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                                <span class="status-dot"></span>
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </div>

                                        <div class="um-registered-cell">
                                            <span class="table-date">
                                                <i class="fa-regular fa-calendar"></i>
                                                {{ $user->created_at->format('M d, Y') }}
                                            </span>
                                        </div>

                                        <div class="um-actions-cell table-action-cell">
                                            <div class="ui-action-group">
                                                @if ($canUpdateUserRole)
                                                    <button type="button"
                                                        data-user-details='@json($userDetails)'
                                                        onclick="openEditModalFromButton(this, 'users', {{ $user->id }}, @js($displayName), @js($user->email), @js($user->role_id), @js($user->status))"
                                                        class="ui-action-btn ui-action-edit" data-tooltip="Edit account"
                                                        data-tooltip-tone="edit" aria-label="Edit account">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>
                                                @endif

                                                @if ($canDisableUsers)
                                                    <button type="button"
                                                        onclick="openToggleConfirm({{ $user->id }}, @js($user->status), @js($displayName))"
                                                        class="ui-action-btn {{ $user->status === 'active' ? 'ui-action-warning' : 'ui-action-success' }}"
                                                        data-tooltip="{{ $user->status === 'active' ? 'Deactivate account' : 'Activate account' }}"
                                                        data-tooltip-tone="{{ $user->status === 'active' ? 'reschedule' : 'start' }}"
                                                        aria-label="{{ $user->status === 'active' ? 'Deactivate account' : 'Activate account' }}">
                                                        <i
                                                            class="fa-solid {{ $user->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                    </button>
                                                @endif

                                                @if ($canUpdateUserPassword)
                                                    <button type="button"
                                                        onclick="openResetModal('users', {{ $user->id }}, @js($displayName))"
                                                        class="ui-action-btn ui-action-reset"
                                                        data-tooltip="Reset password" data-tooltip-tone="reset"
                                                        aria-label="Reset password">
                                                        <i class="fa-solid fa-key"></i>
                                                    </button>
                                                @endif

                                                @if ($canViewAccounts)
                                                    <button type="button"
                                                        data-user-details='@json($userDetails)'
                                                        onclick="openViewModalFromButton(this)"
                                                        class="ui-action-btn ui-action-view" data-tooltip="View details"
                                                        data-tooltip-tone="view" aria-label="View details">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div id="umTableEmptyState" class="empty-state-host"></div>
                            @endforelse
                        </div>
                    </div>

                    <div class="table-grid-view" id="umGridView" hidden>
                        <div class="table-record-grid" id="umGridBody">
                            @forelse($users as $user)
                                @php
                                    $roleSlug = optional($user->role)->slug ?? 'none';
                                    $roleName =
                                        optional($user->role)->display_name ??
                                        (optional($user->role)->name ?? 'Patient');
                                    $displayName = $user->patient?->name ?? $user->name;

                                    $userDetails = [
                                        'id' => $user->id,
                                        'name' => $displayName,
                                        'email' => $user->email,
                                        'role' => $roleName,
                                        'status' => ucfirst($user->status),
                                        'source' => 'Users',
                                        'created_at' => $user->created_at
                                            ? $user->created_at->format('M d, Y h:i A')
                                            : 'N/A',
                                        'updated_at' => $user->updated_at
                                            ? $user->updated_at->format('M d, Y h:i A')
                                            : 'N/A',
                                        'phone' => optional($user->patient)->phone ?: ($user->phone ?: 'N/A'),
                                        'birthdate' =>
                                            optional(optional($user->patient)->birthdate)?->format('M d, Y') ??
                                            (optional($user->birthdate)?->format('M d, Y') ?? 'N/A'),
                                        'gender' => optional($user->patient)->gender ?: ($user->gender ?: 'N/A'),
                                        'phone_raw' => optional($user->patient)->phone ?? ($user->phone ?? ''),
                                        'birthdate_raw' =>
                                            optional(optional($user->patient)->birthdate)?->format('Y-m-d') ??
                                            (optional($user->birthdate)?->format('Y-m-d') ?? ''),
                                        'gender_raw' => optional($user->patient)->gender ?? ($user->gender ?? ''),
                                        'patient_profile' => $user->patient ? 'Linked' : 'Not linked',
                                        'last_login_at' =>
                                            optional($user->last_login_at)?->format('M d, Y h:i A') ?? 'Never',
                                    ];
                                @endphp

                                <div class="table-record-card">
                                    <div class="table-record-card-layout">
                                        <div class="table-record-content">
                                            <div class="table-record-header">
                                                <div class="table-primary">
                                                    <div class="patient-avatar patient-avatar-sm" data-patient-avatar
                                                        data-patient-name="{{ $displayName }}"></div>

                                                    <div class="um-user-copy">
                                                        <strong class="table-record-title">{{ $displayName }}</strong>
                                                        <span class="ui-muted-text">{{ $user->email }}</span>
                                                    </div>
                                                </div>

                                                <span
                                                    class="status-pill {{ $user->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                                    <span class="status-dot"></span>
                                                    {{ ucfirst($user->status) }}
                                                </span>
                                            </div>

                                            <div class="table-record-meta">
                                                <div class="table-record-row">
                                                    <span class="table-record-label">Role</span>
                                                    <span class="table-record-value">
                                                        <span class="badge-role role-{{ $roleSlug }}">
                                                            {{ $roleName }}
                                                        </span>
                                                    </span>
                                                </div>

                                                <div class="table-record-row">
                                                    <span class="table-record-label">Registered</span>
                                                    <span class="table-record-value">
                                                        <span class="table-date">
                                                            <i class="fa-regular fa-calendar"></i>
                                                            {{ $user->created_at->format('M d, Y') }}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-record-actions">
                                            <div class="ui-action-group">
                                                @if ($canUpdateUserRole)
                                                    <button type="button"
                                                        data-user-details='@json($userDetails)'
                                                        onclick="openEditModalFromButton(this, 'users', {{ $user->id }}, @js($displayName), @js($user->email), @js($user->role_id), @js($user->status))"
                                                        class="ui-action-btn ui-action-edit" data-tooltip="Edit account"
                                                        data-tooltip-tone="edit" aria-label="Edit account">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>
                                                @endif

                                                @if ($canDisableUsers)
                                                    <button type="button"
                                                        onclick="openToggleConfirm({{ $user->id }}, @js($user->status), @js($displayName))"
                                                        class="ui-action-btn {{ $user->status === 'active' ? 'ui-action-warning' : 'ui-action-success' }}"
                                                        data-tooltip="{{ $user->status === 'active' ? 'Deactivate account' : 'Activate account' }}"
                                                        data-tooltip-tone="{{ $user->status === 'active' ? 'reschedule' : 'start' }}"
                                                        aria-label="{{ $user->status === 'active' ? 'Deactivate account' : 'Activate account' }}">
                                                        <i
                                                            class="fa-solid {{ $user->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                    </button>
                                                @endif

                                                @if ($canUpdateUserPassword)
                                                    <button type="button"
                                                        onclick="openResetModal('users', {{ $user->id }}, @js($displayName))"
                                                        class="ui-action-btn ui-action-reset"
                                                        data-tooltip="Reset password" data-tooltip-tone="reset"
                                                        aria-label="Reset password">
                                                        <i class="fa-solid fa-key"></i>
                                                    </button>
                                                @endif

                                                @if ($canViewAccounts)
                                                    <button type="button"
                                                        data-user-details='@json($userDetails)'
                                                        onclick="openViewModalFromButton(this)"
                                                        class="ui-action-btn ui-action-view" data-tooltip="View details"
                                                        data-tooltip-tone="view" aria-label="View details">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div id="umGridEmptyState" class="empty-state-host table-grid-empty"></div>
                            @endforelse
                        </div>
                    </div>

                    <x-pagination-bar id="umPaginationBottomBar" info-id="umPaginationInfoBottom"
                        pagination-id="umPaginationBottom" position="bottom" :show-entries="false" label="users"
                        :total="$users->total()" :from="$users->firstItem() ?? 0" :to="$users->lastItem() ?? 0" />
                </div>
            </div>
        </div>
    </main>

    <div id="addModal" class="ui-modal modal-theme-primary" aria-hidden="true">

        <div class="ui-modal-card modal-xl modal-split-card">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">Add New User</h3>
                        <p class="modal-subtitle">
                            Create a system account and assign access permissions.
                        </p>
                    </div>
                </div>

                <button type="button" data-discard-close="addModal" class="modal-x" aria-label="Close add user modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route($routeNames['store'] ?? 'admin.user_management.store') }}"
                id="addUserForm" class="modal-card-form" data-global-validation data-global-selects data-discard-form
                data-discard-title="Discard new user?" data-discard-subtitle="You have unsaved account details."
                data-discard-message="Closing this modal will remove the user information you entered. Do you want to discard your changes?"
                novalidate>
                @csrf

                <div class="modal-bd modal-scroll-body">
                    @if ($errors->any())
                        <div class="modal-error-banner show">
                            <i class="fa-solid fa-circle-exclamation"></i>

                            <div>
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="modal-form-grid-2">

                        {{-- LEFT COLUMN --}}
                        <div class="modal-form-section">
                            <div class="modal-section-heading">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-id-card"></i>
                                </div>

                                <div>
                                    <h4>Account Details</h4>
                                    <p>
                                        Basic identity and access information.
                                    </p>
                                </div>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="addNameInput">
                                    Full Name
                                    <span class="required-mark">*</span>
                                </label>

                                <div class="modal-inline-control">
                                    <div class="modal-inline-main">
                                        <input type="text" id="addNameInput" name="name"
                                            value="{{ old('name') }}" class="form-input-custom"
                                            placeholder="e.g. Juan dela Cruz" autocomplete="name" maxlength="255"
                                            data-field-label="Full Name" data-validation-rule="personName"
                                            data-required-message="Please enter the user's full name."
                                            data-pattern-message="Only letters, spaces, apostrophe, period, and hyphen are allowed."
                                            required>
                                    </div>

                                    <x-voice-input target="#addNameInput" status-id="addNameVoiceStatus"
                                        label="Voice input for full name" title="Voice input" />
                                </div>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="addEmailInput">
                                    Email Address
                                    <span class="required-mark">*</span>
                                </label>

                                <div class="global-control-wrap">
                                    <i class="fa-solid fa-envelope global-control-icon"></i>

                                    <input type="email" id="addEmailInput" name="email" value="{{ old('email') }}"
                                        class="form-input-custom global-form-icon" placeholder="user@pup.edu.ph"
                                        autocomplete="email" data-field-label="Email Address"
                                        data-required-message="Please enter an email address."
                                        data-type-message="Please enter a valid email address." required>
                                </div>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="addRoleSelect">
                                    Role
                                    <span class="required-mark">*</span>
                                </label>

                                <select id="addRoleSelect" name="role_id" class="js-custom-select"
                                    data-placeholder="Select role" data-field-label="Role" required>

                                    @php
                                        $patientRole = $roles->first(
                                            fn($role) => strtolower($role->slug) === 'patient' ||
                                                strtolower($role->name) === 'patient',
                                        );
                                    @endphp

                                    @if ($patientRole)
                                        <option value="{{ $patientRole->id }}"
                                            {{ old('role_id', $patientRole->id) == $patientRole->id ? 'selected' : '' }}>
                                            {{ $patientRole->display_name }}
                                        </option>
                                    @endif

                                    @foreach ($roles as $role)
                                        @continue($patientRole && (int) $role->id === (int) $patientRole->id)

                                        <option value="{{ $role->id }}"
                                            {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                            {{ $role->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="global-form-group">
                                <label class="global-form-label">
                                    Account Type
                                </label>

                                <div class="global-readonly-field">
                                    <i class="fa-solid fa-user-shield"></i>
                                    <span>System-managed user account</span>
                                </div>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="addPhoneInput">
                                    Phone Number
                                </label>

                                <div class="global-control-wrap">
                                    <i class="fa-solid fa-phone global-control-icon"></i>

                                    <input type="tel" id="addPhoneInput" name="phone" value="{{ old('phone') }}"
                                        class="form-input-custom global-form-icon" placeholder="09XX XXX XXXX"
                                        inputmode="numeric" autocomplete="tel" maxlength="13"
                                        data-field-label="Phone Number">
                                </div>

                                <p id="addPhoneInputFeedback" class="modal-helper-text">
                                    Format: 09XX XXX XXXX
                                </p>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="addBirthdateInput">
                                    Birthdate
                                </label>

                                <input type="text" id="addBirthdateInput" name="birthdate"
                                    value="{{ old('birthdate') }}" class="form-input-custom js-flatpickr-date-max-today"
                                    placeholder="Select birthdate" autocomplete="off" data-field-label="Birthdate"
                                    data-validation-rule="notFutureDate" data-flatpickr-min-year="1900" readonly>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="addGenderInput">
                                    Gender
                                </label>

                                <select id="addGenderInput" name="gender" class="js-custom-select"
                                    data-placeholder="Select gender" data-field-label="Gender">

                                    <option value="" disabled selected>
                                        Select gender
                                    </option>

                                    <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>
                                        Male
                                    </option>

                                    <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>
                                        Female
                                    </option>
                                </select>
                            </div>
                        </div>

                        {{-- RIGHT COLUMN --}}
                        <div class="modal-form-section">
                            <div class="modal-section-heading">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-lock"></i>
                                </div>

                                <div>
                                    <h4>Security Setup</h4>
                                    <p>
                                        A secure temporary password will be generated.
                                    </p>
                                </div>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="addPassword">
                                    Generated Password
                                </label>

                                <div class="global-control-wrap">
                                    <i class="fa-solid fa-lock global-control-icon"></i>

                                    <input type="password" name="password" id="addPassword" minlength="8"
                                        class="form-input-custom global-form-icon global-control-with-action"
                                        placeholder="Auto-generated password" readonly>

                                    <button type="button" class="global-input-action"
                                        onclick="togglePassVis('addPassword', 'addEye')"
                                        aria-label="Show or hide generated password">

                                        <i class="fa-regular fa-eye" id="addEye"></i>
                                    </button>
                                </div>

                                <div class="modal-inline-actions">
                                    <button type="button" onclick="refreshGeneratedPassword()"
                                        class="ui-btn ui-btn-secondary ui-btn-sm">

                                        <i class="fa-solid fa-rotate"></i>
                                        <span>Generate New</span>
                                    </button>

                                    <button type="button" onclick="copyFieldValue('addPassword')"
                                        class="ui-btn ui-btn-secondary ui-btn-sm">

                                        <i class="fa-regular fa-copy"></i>
                                        <span>Copy</span>
                                    </button>
                                </div>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="addPasswordConf">
                                    Confirm Password
                                </label>

                                <div class="global-control-wrap">
                                    <i class="fa-solid fa-lock global-control-icon"></i>

                                    <input type="password" name="password_confirmation" id="addPasswordConf"
                                        class="form-input-custom global-form-icon global-control-with-action"
                                        placeholder="Repeat password" readonly>

                                    <button type="button" class="global-input-action"
                                        onclick="togglePassVis('addPasswordConf', 'addEye2')"
                                        aria-label="Show or hide confirmed password">

                                        <i class="fa-regular fa-eye" id="addEye2"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="global-confirm-alert modal-theme-warning">
                                <i class="fa-solid fa-circle-info"></i>
                                <p>
                                    Temporary password
                                    <span>
                                        The generated password shown here is the exact password
                                        that will be saved for the account.
                                    </span>
                                </p>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label">
                                    Status
                                    <span class="required-mark">*</span>
                                </label>

                                <div class="global-choice-group" role="radiogroup" aria-label="Account status">

                                    <label class="global-choice-card">
                                        <input type="radio" name="status" id="addStatusActive" value="active"
                                            class="global-choice-input" data-field-label="Status"
                                            data-required-message="Please select an account status."
                                            {{ old('status', 'active') === 'active' ? 'checked' : '' }} required>

                                        <span class="global-choice-indicator">
                                            <i class="fa-solid fa-check"></i>
                                        </span>

                                        <span class="global-choice-copy">
                                            <strong class="global-choice-title">
                                                Active
                                            </strong>

                                            <small class="global-choice-description">
                                                User can access the system immediately
                                            </small>
                                        </span>
                                    </label>

                                    <label class="global-choice-card">
                                        <input type="radio" name="status" id="addStatusInactive" value="inactive"
                                            class="global-choice-input"
                                            {{ old('status') === 'inactive' ? 'checked' : '' }}>

                                        <span class="global-choice-indicator">
                                            <i class="fa-solid fa-ban"></i>
                                        </span>

                                        <span class="global-choice-copy">
                                            <strong class="global-choice-title">
                                                Inactive
                                            </strong>

                                            <small class="global-choice-description">
                                                User login will be disabled
                                            </small>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-ft">
                    <button type="button" data-discard-close="addModal" class="ui-btn ui-btn-secondary">
                        Cancel
                    </button>

                    <button type="submit" class="ui-btn ui-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="ui-modal modal-theme-edit" id="editModal" aria-hidden="true">
        <div class="ui-modal-card modal-lg modal-split-card">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">Edit User</h3>
                        <p class="modal-subtitle" id="editModalSubtitle">
                            Updating user details
                        </p>
                    </div>
                </div>

                <button type="button" data-discard-close="editModal" class="modal-x">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" id="editForm" class="modal-card-form" data-global-validation data-global-selects
                data-discard-form data-discard-title="Discard user changes?"
                data-discard-subtitle="You have unsaved account updates."
                data-discard-message="Closing this modal will remove the changes made to this user. Do you want to discard them?"
                novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" id="editOriginalRole" value="">
                <div class="modal-bd modal-scroll-body">
                    <div class="modal-form-grid">
                        <div class="global-form-group" data-global-field>
                            <label class="global-form-label" for="editName">
                                Full Name
                                <span class="required-mark">*</span>
                            </label>

                            <div class="modal-inline-control">
                                <div class="modal-inline-main">
                                    <input type="text" name="name" id="editName" class="form-input-custom"
                                        placeholder="Full name" autocomplete="name" maxlength="255"
                                        data-field-label="Full Name" data-validation-rule="personName"
                                        data-required-message="Please enter the user's full name."
                                        data-pattern-message="Only letters, spaces, apostrophe, period, and hyphen are allowed."
                                        required>
                                </div>

                                <x-voice-input target="#editName" status-id="editNameVoiceStatus"
                                    label="Voice input for edit full name" title="Voice input" />
                            </div>
                        </div>

                        <div class="global-form-group" data-global-field>

                            <label class="global-form-label" for="editEmail">
                                Email Address
                                <span class="required-mark">*</span>
                            </label>

                            <div class="global-control-wrap">
                                <i class="fa-solid fa-envelope global-control-icon"></i>

                                <input type="email" name="email" id="editEmail"
                                    class="form-input-custom global-form-icon" placeholder="user@pup.edu.ph"
                                    autocomplete="email" data-field-label="Email Address"
                                    data-required-message="Please enter an email address."
                                    data-type-message="Please enter a valid email address." required>
                            </div>
                        </div>

                        <div class="global-form-group" data-global-field>
                            <label for="editRole" class="global-form-label">
                                Role
                            </label>

                            <select name="role_id" id="editRole" class="js-custom-select" data-placeholder="Patient"
                                data-field-label="Role">
                                @if ($patientRole)
                                    <option value="{{ $patientRole->id }}">
                                        {{ $patientRole->display_name }}
                                    </option>
                                @endif

                                @foreach ($roles as $role)
                                    @continue($patientRole && (int) $role->id === (int) $patientRole->id)

                                    <option value="{{ $role->id }}">
                                        {{ $role->display_name }}
                                    </option>
                                @endforeach
                            </select>

                            <div id="editRoleConfirmPanel" class="global-confirm-panel hidden">
                                <div class="global-confirm-alert">
                                    <i class="fa-solid fa-shield-halved"></i>

                                    <p>
                                        Confirm Role Change
                                        <span>
                                            Enter your current admin password before changing this user's role.
                                        </span>
                                    </p>
                                </div>

                                <div class="global-form-group" data-global-field>
                                    <label class="global-form-label" for="editAdminCurrentPassword">
                                        Current Admin Password
                                        <span class="required-mark">*</span>
                                    </label>

                                    <div class="global-control-wrap">
                                        <i class="fa-solid fa-lock global-control-icon"></i>

                                        <input type="password" name="admin_current_password"
                                            id="editAdminCurrentPassword"
                                            class="form-input-custom global-form-icon global-control-with-action"
                                            placeholder="Enter current admin password" autocomplete="current-password"
                                            data-field-label="Current Admin Password"
                                            data-required-message="Enter your current admin password.">

                                        <button type="button" class="global-input-action"
                                            onclick="togglePassVis(
                        'editAdminCurrentPassword',
                        'editAdminPasswordEye'
                    )"
                                            aria-label="Show or hide current admin password">
                                            <i class="fa-regular fa-eye" id="editAdminPasswordEye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-form-section">
                            <div class="modal-section-heading">
                                <div class="modal-section-icon">
                                    <i class="fa-solid fa-address-card"></i>
                                </div>

                                <div>
                                    <h4>Backup Information</h4>
                                    <p>
                                        Optional contact and personal information.
                                    </p>
                                </div>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="editPhone">
                                    Phone Number
                                </label>

                                <div class="global-control-wrap">
                                    <i class="fa-solid fa-phone global-control-icon"></i>

                                    <input type="tel" name="phone" id="editPhone"
                                        class="form-input-custom global-form-icon" placeholder="09XX XXX XXXX"
                                        inputmode="numeric" autocomplete="tel" maxlength="13"
                                        data-field-label="Phone Number"
                                        data-pattern-message="Enter a valid 11-digit Philippine mobile number.">
                                </div>

                                <p id="editPhoneFeedback" class="modal-helper-text">
                                    Format: 09XX XXX XXXX
                                </p>
                            </div>

                            <div class="global-form-group" data-global-field>

                                <label class="global-form-label" for="editBirthdate">
                                    Birthdate
                                </label>

                                <input type="text" id="editBirthdate" name="birthdate"
                                    class="form-input-custom js-flatpickr-date-max-today" placeholder="Select birthdate"
                                    autocomplete="off" data-field-label="Birthdate" data-validation-rule="notFutureDate"
                                    data-flatpickr-min-year="1900" readonly>
                            </div>

                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="editGender">
                                    Gender
                                </label>

                                <select name="gender" id="editGender" class="js-custom-select"
                                    data-placeholder="Select gender" data-field-label="Gender">

                                    <option value="" disabled>Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="global-form-group" data-global-field>
                        <label class="global-form-label">
                            Status
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-choice-group" role="radiogroup" aria-label="Account status">

                            <label class="global-choice-card">
                                <input type="radio" name="status" id="editStatusActive" value="active"
                                    class="global-choice-input" required>

                                <span class="global-choice-indicator">
                                    <i class="fa-solid fa-check"></i>
                                </span>

                                <span class="global-choice-copy">
                                    <strong class="global-choice-title">Active</strong>
                                    <small class="global-choice-description">
                                        User can access the system
                                    </small>
                                </span>
                            </label>

                            <label class="global-choice-card">
                                <input type="radio" name="status" id="editStatusInactive" value="inactive"
                                    class="global-choice-input">

                                <span class="global-choice-indicator">
                                    <i class="fa-solid fa-ban"></i>
                                </span>

                                <span class="global-choice-copy">
                                    <strong class="global-choice-title">Inactive</strong>
                                    <small class="global-choice-description">
                                        User login will be disabled
                                    </small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-ft modal-sticky-footer">
                    <button type="button" data-discard-close="editModal" class="ui-btn ui-btn-secondary">
                        Cancel
                    </button>

                    <button type="submit" class="ui-btn ui-btn-edit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Update User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="resetModal" class="ui-modal modal-theme-reset" aria-hidden="true">

        <div class="ui-modal-card modal-sm">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-key"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">
                            Reset Password
                        </h3>

                        <p class="modal-subtitle" id="resetModalSubtitle">
                            Set a new password
                        </p>
                    </div>
                </div>

                <button type="button" data-discard-close="resetModal" class="modal-x"
                    aria-label="Close reset password modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" id="resetForm" class="modal-card-form" data-global-validation data-discard-form
                data-discard-title="Discard password reset?" data-discard-subtitle="A new password has not been saved."
                data-discard-message="Closing this modal will remove the password you entered. Do you want to discard it?"
                novalidate>
                @csrf
                <div class="modal-bd modal-scroll-body">
                    <div class="modal-form-grid">

                        <div class="global-form-group" data-global-field>
                            <label class="global-form-label" for="resetPassword">
                                New Password
                                <span class="required-mark">*</span>
                            </label>

                            <div class="global-control-wrap">
                                <i class="fa-solid fa-lock global-control-icon"></i>

                                <input type="password" name="password" id="resetPassword"
                                    class="form-input-custom global-form-icon global-control-with-action"
                                    placeholder="Enter new password" autocomplete="new-password" minlength="8"
                                    data-field-label="New Password" data-required-message="Please enter a new password."
                                    data-validation-rule="strongPassword" required>

                                <button type="button" class="global-input-action"
                                    onclick="togglePassVis('resetPassword', 'resetEye')"
                                    aria-label="Show or hide new password">
                                    <i class="fa-regular fa-eye" id="resetEye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="global-form-group" data-global-field>
                            <label class="global-form-label" for="resetPasswordConf">
                                Confirm Password
                                <span class="required-mark">*</span>
                            </label>

                            <div class="global-control-wrap">
                                <i class="fa-solid fa-lock global-control-icon"></i>

                                <input type="password" name="password_confirmation" id="resetPasswordConf"
                                    class="form-input-custom global-form-icon global-control-with-action"
                                    placeholder="Repeat new password" autocomplete="new-password"
                                    data-field-label="Confirm Password"
                                    data-required-message="Please confirm the new password." required>

                                <button type="button" class="global-input-action"
                                    onclick="togglePassVis('resetPasswordConf', 'resetEye2')"
                                    aria-label="Show or hide confirmed password">
                                    <i class="fa-regular fa-eye" id="resetEye2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-ft modal-sticky-footer">
                    <button type="button" data-discard-close="resetModal" class="ui-btn ui-btn-secondary">
                        Cancel
                    </button>

                    <button type="submit" class="ui-btn ui-btn-reset-password">
                        <i class="fa-solid fa-key"></i>
                        <span>Reset Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewModal" class="ui-modal" aria-hidden="true">
        <div class="ui-modal-card modal-xl">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">Account Details</h3>
                        <p class="modal-subtitle">
                            Review selected account information
                        </p>
                    </div>
                </div>

                <button type="button" onclick="closeModal('viewModal')" class="modal-x"
                    aria-label="Close account details modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="modal-profile-card">
                    <div class="modal-profile-main">
                        <div id="viewInitial" class="patient-avatar patient-avatar-lg" data-patient-avatar
                            data-patient-name="Patient"></div>

                        <div class="modal-profile-main-copy">
                            <span class="modal-profile-eyebrow">Selected Account</span>
                            <strong id="viewName" class="modal-profile-name"></strong>
                            <span id="viewEmail" class="ui-muted-text"></span>
                        </div>
                    </div>

                    <div class="modal-profile-details">
                        <div class="modal-profile-detail">
                            <span class="modal-profile-detail-icon">
                                <i class="fa-solid fa-user-shield"></i>
                            </span>
                            <div>
                                <span class="modal-profile-label">Role</span>
                                <strong id="viewRole" class="modal-profile-value"></strong>
                            </div>
                        </div>

                        <div class="modal-profile-detail">
                            <span class="modal-profile-detail-icon">
                                <i class="fa-solid fa-circle-check"></i>
                            </span>
                            <div>
                                <span class="modal-profile-label">Status</span>
                                <strong id="viewStatus" class="status-pill status-inactive"></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-profile-details">
                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-hashtag"></i></span>
                        <div>
                            <span class="modal-profile-label">User ID</span>
                            <strong id="viewId" class="modal-profile-value"></strong>
                        </div>
                    </div>

                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-database"></i></span>
                        <div>
                            <span class="modal-profile-label">Source</span>
                            <strong id="viewSource" class="modal-profile-value"></strong>
                        </div>
                    </div>

                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                        <div>
                            <span class="modal-profile-label">Created At</span>
                            <strong id="viewCreatedAt" class="modal-profile-value"></strong>
                        </div>
                    </div>

                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-phone"></i></span>
                        <div>
                            <span class="modal-profile-label">Phone</span>
                            <strong id="viewPhone" class="modal-profile-value"></strong>
                        </div>
                    </div>

                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-cake-candles"></i></span>
                        <div>
                            <span class="modal-profile-label">Birthdate</span>
                            <strong id="viewBirthdate" class="modal-profile-value"></strong>
                        </div>
                    </div>

                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-venus-mars"></i></span>
                        <div>
                            <span class="modal-profile-label">Gender</span>
                            <strong id="viewGender" class="modal-profile-value"></strong>
                        </div>
                    </div>

                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-notes-medical"></i></span>
                        <div>
                            <span class="modal-profile-label">Patient Profile</span>
                            <strong id="viewPatientProfile" class="modal-profile-value"></strong>
                        </div>
                    </div>

                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <div>
                            <span class="modal-profile-label">Last Login</span>
                            <strong id="viewLastLoginAt" class="modal-profile-value"></strong>
                        </div>
                    </div>

                    <div class="modal-profile-detail">
                        <span class="modal-profile-detail-icon"><i class="fa-solid fa-pen-to-square"></i></span>
                        <div>
                            <span class="modal-profile-label">Updated At</span>
                            <strong id="viewUpdatedAt" class="modal-profile-value"></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" onclick="closeModal('viewModal')" class="ui-btn ui-btn-secondary">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="toggleConfirmModal" class="ui-modal modal-theme-warning" aria-hidden="true">

        <div class="ui-modal-card modal-sm">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon" id="toggleModalIcon">
                        <i class="fa-solid fa-question"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title" id="toggleModalTitle">
                            Confirm Action
                        </h3>

                        <p class="modal-subtitle" id="toggleModalSubtitle">
                            Please confirm this change
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div id="toggleModalBody" class="global-confirm-alert">
                    <i id="toggleModalBodyIcon" class="fa-solid fa-triangle-exclamation"></i>
                    <p>
                        <span id="toggleModalUserName" class="global-confirm-value">
                        </span>
                        <span id="toggleModalMessage"></span>
                    </p>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" onclick="closeModal('toggleConfirmModal')" class="ui-btn ui-btn-secondary">
                    Cancel
                </button>

                <form id="toggleConfirmForm" method="POST">
                    @csrf

                    <button type="submit" id="toggleConfirmBtn" class="ui-btn ui-btn-warning">
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const umCanViewAccounts = @json($canViewAccounts);
        const umCanCreateUsers = @json($canCreateUsers);
        const umCanDisableUsers = @json($canDisableUsers);
        const umCanUpdateUserRole = @json($canUpdateUserRole);
        const umCanUpdateUserPassword = @json($canUpdateUserPassword);

        var umState = {
            search: @js($search ?? ''),
            role: @js($roleFilter ?? ''),
            status: @js($statusFilter ?? ''),
            perPage: {{ $perPage ?? 10 }},
            page: @json((int) request('page', 1)),
        };

        var umController = null;


        window.closeAllModals = function() {
            document
                .querySelectorAll(
                    '.modal-overlay.open, .ui-modal.open'
                )
                .forEach(function(modal) {
                    if (modal.id) {
                        window.closeModal?.(modal.id);
                    }
                });
        };

        @if ($errors->any() && old('_method') !== 'PUT')
            document.addEventListener('DOMContentLoaded', () => openModal('addModal'));
        @endif

        function openToggleConfirm(
            userId,
            currentStatus,
            userName
        ) {
            const isActive =
                currentStatus === 'active';

            const modal =
                document.getElementById(
                    'toggleConfirmModal'
                );

            const icon =
                document.getElementById(
                    'toggleModalIcon'
                );

            const title =
                document.getElementById(
                    'toggleModalTitle'
                );

            const subtitle =
                document.getElementById(
                    'toggleModalSubtitle'
                );

            const bodyIcon =
                document.getElementById(
                    'toggleModalBodyIcon'
                );

            const name =
                document.getElementById(
                    'toggleModalUserName'
                );

            const message =
                document.getElementById(
                    'toggleModalMessage'
                );

            const button =
                document.getElementById(
                    'toggleConfirmBtn'
                );

            const form =
                document.getElementById(
                    'toggleConfirmForm'
                );

            if (
                !modal ||
                !icon ||
                !title ||
                !subtitle ||
                !bodyIcon ||
                !name ||
                !message ||
                !button ||
                !form
            ) {
                return;
            }

            modal.classList.remove(
                'modal-theme-warning',
                'modal-theme-success',
                'is-activate',
                'is-deactivate'
            );

            form.dataset.userId =
                String(userId);

            form.dataset.currentStatus =
                String(currentStatus);

            form.dataset.userName =
                String(userName);

            form.action =
                `/admin/user-management/${userId}/toggle-status`;

            button.disabled = false;
            name.textContent = userName;

            if (isActive) {
                modal.classList.add(
                    'modal-theme-warning',
                    'is-deactivate'
                );

                icon.innerHTML =
                    '<i class="fa-solid fa-user-slash"></i>';

                bodyIcon.className =
                    'fa-solid fa-triangle-exclamation';

                title.textContent =
                    'Deactivate User';

                subtitle.textContent =
                    'This will restrict their access';

                message.textContent =
                    'will be deactivated. They will no longer be able to log in until reactivated.';

                button.className =
                    'ui-btn ui-btn-warning';

                button.innerHTML =
                    '<i class="fa-solid fa-user-slash"></i><span>Deactivate</span>';
            } else {
                modal.classList.add(
                    'modal-theme-success',
                    'is-activate'
                );

                icon.innerHTML =
                    '<i class="fa-solid fa-user-check"></i>';

                bodyIcon.className =
                    'fa-solid fa-circle-check';

                title.textContent =
                    'Activate User';

                subtitle.textContent =
                    'This will restore their access';

                message.textContent =
                    'will be activated. They will regain access to the system.';

                button.className =
                    'ui-btn ui-btn-success';

                button.innerHTML =
                    '<i class="fa-solid fa-user-check"></i><span>Activate</span>';
            }

            button.dataset.originalHtml =
                button.innerHTML;

            openModal('toggleConfirmModal');
        }

        function syncEditRoleConfirmation() {
            const form =
                document.getElementById(
                    'editForm'
                );

            const roleInput =
                document.getElementById(
                    'editRole'
                );

            const originalRoleInput =
                document.getElementById(
                    'editOriginalRole'
                );

            const panel =
                document.getElementById(
                    'editRoleConfirmPanel'
                );

            const password =
                document.getElementById(
                    'editAdminCurrentPassword'
                );

            if (
                !form ||
                !roleInput ||
                !originalRoleInput ||
                !panel ||
                !password
            ) {
                return;
            }

            const roleChanged =
                String(roleInput.value || '') !==
                String(originalRoleInput.value || '');

            const shouldConfirm =
                form.dataset.source !== 'patients' &&
                roleChanged;

            const wasHidden =
                panel.classList.contains('hidden');

            if (shouldConfirm) {
                panel.classList.remove('hidden');

                if (wasHidden) {
                    panel.classList.remove(
                        'slide-down-fade-in'
                    );

                    void panel.offsetWidth;

                    panel.classList.add(
                        'slide-down-fade-in'
                    );
                }
            } else {
                panel.classList.add('hidden');

                panel.classList.remove(
                    'slide-down-fade-in'
                );
            }

            password.required =
                shouldConfirm;

            if (!shouldConfirm) {
                password.value = '';

                password.setCustomValidity('');
            }
        }

        function setEditRoleDisabled(isDisabled) {
            const select = document.getElementById('editRole');

            if (!select) return;

            select.disabled = isDisabled;

            const wrapper = select.closest('.custom-select');

            if (wrapper) {
                wrapper.classList.toggle('is-disabled', isDisabled);
                window.syncCustomSelect?.(wrapper);
            }
        }

        function openEditModal(source, id, name, email, roleId, status, details = null) {
            const form = document.getElementById('editForm');
            const payload = details && typeof details === 'object' ? details : {};

            if (source === 'patients') {
                form.action = `/admin/user-management/patient/${id}`;
                setEditRoleDisabled(true);
                document.getElementById('editStatusActive').disabled = true;
                document.getElementById('editStatusInactive').disabled = true;
            } else {
                form.action = `/admin/user-management/${id}`;
                let methodInput = form.querySelector('input[name="_method"]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    form.appendChild(methodInput);
                }
                methodInput.value = 'PUT';
                setEditRoleDisabled(false);
                document.getElementById('editStatusActive').disabled = false;
                document.getElementById('editStatusInactive').disabled = false;

                const editGender =
                    document.getElementById('editGender');

                if (editGender) {
                    editGender.value =
                        payload.gender_raw ||
                        payload.gender ||
                        '';

                    window.syncCustomSelect?.(
                        editGender.closest('.custom-select')
                    );
                }
            }

            form.dataset.source = source;

            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editModalSubtitle').textContent = 'Editing: ' + name;
            const editRoleSelect = document.getElementById('editRole');
            const normalizedRoleId = String(roleId ?? '');

            document.getElementById('editOriginalRole').value = normalizedRoleId;
            document.getElementById('editAdminCurrentPassword').value = '';

            if (editRoleSelect) {
                editRoleSelect.value = normalizedRoleId;

                const roleWrapper = editRoleSelect.closest('.custom-select');

                if (roleWrapper) {
                    window.syncCustomSelect?.(roleWrapper);
                }
            }

            document.getElementById('editPhone').value = payload.phone_raw || payload.phone || '';

            const editBirthdate =
                document.getElementById('editBirthdate');

            const birthdateValue =
                payload.birthdate_raw || '';

            if (editBirthdate?._flatpickr) {
                editBirthdate._flatpickr.setDate(
                    birthdateValue,
                    false
                );
            } else if (editBirthdate) {
                editBirthdate.value = birthdateValue;
            }

            document.getElementById('editGender').value = payload.gender_raw || payload.gender || '';
            document.getElementById('editPhone').value = formatUserPhoneDisplay(
                getNormalizedUserPhoneValue(document.getElementById('editPhone'))
            );

            syncEditRoleConfirmation();

            document.getElementById('editStatusActive').checked = (status === 'active');
            document.getElementById('editStatusInactive').checked = (status === 'inactive');

            openModal('editModal');
        }

        function openResetModal(source, id, name) {
            const resetForm =
                document.getElementById('resetForm');

            const resetPassword =
                document.getElementById('resetPassword');

            const resetPasswordConf =
                document.getElementById('resetPasswordConf');

            const resetSubtitle =
                document.getElementById('resetModalSubtitle');

            if (
                !resetForm ||
                !resetPassword ||
                !resetPasswordConf
            ) {
                return;
            }

            resetForm.action =
                source === 'patients' ?
                `/admin/user-management/patient/${id}/reset-password` :
                `/admin/user-management/${id}/reset-password`;

            if (resetSubtitle) {
                resetSubtitle.textContent =
                    `Resetting password for: ${name}`;
            }

            resetForm.reset();

            window.showFormInputValidationMessage?.(
                resetPassword,
                ''
            );

            window.showFormInputValidationMessage?.(
                resetPasswordConf,
                ''
            );

            openModal('resetModal');
        }

        function buildGeneratedPassword(length = 12) {
            const lower = 'abcdefghijkmnopqrstuvwxyz';
            const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const numbers = '23456789';
            const symbols = '@#$%*!?';
            const all = lower + upper + numbers + symbols;

            const pick = (chars) => chars.charAt(Math.floor(Math.random() * chars.length));
            const chars = [
                pick(lower),
                pick(upper),
                pick(numbers),
                pick(symbols),
            ];

            while (chars.length < length) {
                chars.push(pick(all));
            }

            for (let i = chars.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [chars[i], chars[j]] = [chars[j], chars[i]];
            }

            return chars.join('');
        }

        function refreshGeneratedPassword() {
            const password = buildGeneratedPassword();
            const passwordInput = document.getElementById('addPassword');
            const confirmInput = document.getElementById('addPasswordConf');

            if (passwordInput) passwordInput.value = password;
            if (confirmInput) confirmInput.value = password;
        }

        async function copyFieldValue(inputId) {
            const input = document.getElementById(inputId);
            if (!input || !input.value) return;

            try {
                await navigator.clipboard.writeText(input.value);
                showSuccessToast('Password copied to clipboard.');
            } catch (error) {
                input.removeAttribute('readonly');
                input.select();
                document.execCommand('copy');
                input.setAttribute('readonly', 'readonly');
                showSuccessToast('Password copied to clipboard.');
            }
        }

        let userPhoneFeedbackTimer = null;

        function formatUserPhoneDisplay(rawDigits) {
            const digits = String(rawDigits || '').slice(0, 11);
            let out = '';

            if (digits.length > 0) out += digits.slice(0, 4);
            if (digits.length > 4) out += ' ' + digits.slice(4, 7);
            if (digits.length > 7) out += ' ' + digits.slice(7, 11);

            return out;
        }

        function getNormalizedUserPhoneValue(input) {
            return String(input?.value || '').replace(/\D/g, '');
        }

        function bindUserPhoneValidation(inputId) {
            const input = document.getElementById(inputId);

            if (
                !input ||
                input.dataset.phoneFormattingReady === 'true'
            ) {
                return;
            }

            input.dataset.phoneFormattingReady = 'true';

            const syncPhone = () => {
                let digits =
                    getNormalizedUserPhoneValue(input);

                if (digits.startsWith('9')) {
                    digits = `0${digits}`;
                }

                input.value =
                    formatUserPhoneDisplay(
                        digits.slice(0, 11)
                    );

                window.validateFormInputField?.(input);
            };

            input.addEventListener('input', syncPhone);
            input.addEventListener('blur', syncPhone);
        }

        function parseUserDetailsFromButton(button) {
            if (!button) return {};

            const raw = button.getAttribute('data-user-details');

            if (!raw) return {};

            try {
                return JSON.parse(raw);
            } catch (error) {
                console.warn('Unable to parse user details payload.', error);
                return {};
            }
        }

        function openEditModalFromButton(button, source, id, name, email, roleId, status) {
            if (!umCanUpdateUserRole) {
                return;
            }

            openEditModal(source, id, name, email, roleId, status, parseUserDetailsFromButton(button));
        }

        function openViewModalFromButton(button) {
            if (!umCanViewAccounts) {
                return;
            }

            openViewModal(parseUserDetailsFromButton(button));
        }

        function openViewModal(payloadOrName, email, role, status, source, createdAt, details) {
            const payload = typeof payloadOrName === 'object' && payloadOrName !== null ?
                payloadOrName : {
                    name: payloadOrName,
                    email,
                    role,
                    status,
                    source,
                    created_at: createdAt,
                    ...(details || {}),
                };

            const viewName = document.getElementById('viewName');
            const viewEmail = document.getElementById('viewEmail');
            const viewId = document.getElementById('viewId');
            const viewRole = document.getElementById('viewRole');
            const viewStatus = document.getElementById('viewStatus');
            const viewSource = document.getElementById('viewSource');
            const viewCreatedAt = document.getElementById('viewCreatedAt');
            const viewUpdatedAt = document.getElementById('viewUpdatedAt');
            const viewPhone = document.getElementById('viewPhone');
            const viewBirthdate = document.getElementById('viewBirthdate');
            const viewGender = document.getElementById('viewGender');
            const viewPatientProfile = document.getElementById('viewPatientProfile');
            const viewLastLoginAt = document.getElementById('viewLastLoginAt');
            const viewInitial = document.getElementById('viewInitial');

            if (viewName) viewName.textContent = payload.name || 'Unknown User';
            if (viewEmail) viewEmail.textContent = payload.email || 'No email available';
            if (viewId) viewId.textContent = payload.id || 'N/A';
            if (viewRole) viewRole.textContent = payload.role || 'Patient';
            if (viewSource) viewSource.textContent = payload.source || 'Users';
            if (viewCreatedAt) viewCreatedAt.textContent = payload.created_at || 'N/A';
            if (viewUpdatedAt) viewUpdatedAt.textContent = payload.updated_at || 'N/A';
            if (viewPhone) viewPhone.textContent = payload.phone || 'N/A';
            if (viewBirthdate) viewBirthdate.textContent = payload.birthdate || 'N/A';
            if (viewGender) viewGender.textContent = payload.gender || 'N/A';
            if (viewPatientProfile) viewPatientProfile.textContent = payload.patient_profile || 'Not linked';
            if (viewLastLoginAt) viewLastLoginAt.textContent = payload.last_login_at || 'Never';

            if (viewInitial) {
                viewInitial.dataset.patientName =
                    payload.name ||
                    'Patient';

                viewInitial.dataset.patientUrl =
                    payload.avatar_url ||
                    '';

                window.PatientUI?.renderAvatar?.(
                    viewInitial
                );
            }

            if (viewStatus) {
                const normalizedStatus = String(payload.status || '').toLowerCase();

                viewStatus.textContent = payload.status || 'Unknown';
                viewStatus.classList.remove(
                    'status-active',
                    'status-inactive'
                );

                viewStatus.classList.add(
                    normalizedStatus === 'active' ?
                    'status-active' :
                    'status-inactive'
                );
            }

            openModal('viewModal');
        }

        function togglePassVis(inputId, iconId) {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);

            if (!inp || !ico) return;

            if (inp.type === 'password') {
                inp.type = 'text';
                ico.className = ico.className.replace('fa-eye', 'fa-eye-slash');
            } else {
                inp.type = 'password';
                ico.className = ico.className.replace('fa-eye-slash', 'fa-eye');
            }
        }

        window.openToggleConfirm = openToggleConfirm;
        window.openEditModal = openEditModal;
        window.openEditModalFromButton = openEditModalFromButton;
        window.openResetModal = openResetModal;
        window.openViewModal = openViewModal;
        window.openViewModalFromButton = openViewModalFromButton;
        window.togglePassVis = togglePassVis;
        window.refreshGeneratedPassword = refreshGeneratedPassword;
        window.copyFieldValue = copyFieldValue;

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof applyTheme === 'function') applyTheme(localStorage.getItem('theme') || 'light');

            refreshGeneratedPassword();
            bindUserPhoneValidation('addPhoneInput');
            bindUserPhoneValidation('editPhone');

        });

        function umFetch(silent) {
            if (umController) umController.abort();
            umController = new AbortController();

            var params = new URLSearchParams({
                search: umState.search,
                role: umState.role,
                status: umState.status,
                per_page: umState.perPage,
                page: umState.page,
            });

            history.replaceState(null, '', window.location.pathname + '?' + params.toString());

            fetch('{{ route($routeNames['index'] ?? 'admin.user_management') }}?' + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: umController.signal
                })
                .then(async function(res) {
                    if (!res.ok) {
                        throw new Error(`User request failed: ${res.status}`);
                    }

                    return res.json();
                })
                .then(function(data) {
                    umRenderRows(data.users);
                    umRenderPagebar(data.pagination);
                    if (data.counts) {
                        umRenderCounts(data.counts);
                    }
                })
                .catch(function(e) {
                    if (e.name !== 'AbortError') console.error(e);
                });
        }


        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalizeUmRole(user) {
            const rawName = String(
                user.role_name ??
                user.role?.display_name ??
                user.role?.name ??
                ''
            ).trim();

            const rawSlug = String(
                user.role_slug ??
                user.role?.slug ??
                ''
            ).trim().toLowerCase();

            const derivedSlug = rawName
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            const invalidValues = [
                '',
                '-',
                '—',
                'null',
                'undefined',
                'none',
                'no-role'
            ];

            const hasNoRole =
                invalidValues.includes(rawName.toLowerCase()) ||
                !user.role_id;

            return {
                label: hasNoRole ? 'Patient' : rawName,
                slug: hasNoRole ? 'none' : rawSlug || derivedSlug || 'none'
            };
        }

        function umRenderRows(users) {
            function jsAttr(value) {
                return JSON
                    .stringify(value ?? '')
                    .replace(/"/g, '&quot;');
            }

            function buildDetails(user, roleLabel, statusLabel, createdFull) {
                return user.details || {
                    id: user.id,
                    name: user.name || '',
                    email: user.email || '',
                    role: roleLabel || 'Patient',
                    status: statusLabel || 'Unknown',
                    source: 'Users',
                    created_at: createdFull || 'N/A',
                    updated_at: 'N/A',
                    phone: 'N/A',
                    birthdate: 'N/A',
                    gender: 'N/A',
                    phone_raw: '',
                    birthdate_raw: '',
                    gender_raw: '',
                    patient_profile: 'Not linked',
                    last_login_at: 'Never'
                };
            }

            function buildActionButtons(user, detailsPayload) {
                const isActive =
                    String(user.status || '').toLowerCase() === 'active';

                const toggleClass =
                    isActive ?
                    'ui-action-warning' :
                    'ui-action-success';

                const toggleIcon =
                    isActive ?
                    'fa-toggle-on' :
                    'fa-toggle-off';

                const toggleTooltip =
                    isActive ?
                    'Deactivate account' :
                    'Activate account';

                const toggleTone =
                    isActive ?
                    'reschedule' :
                    'start';

                const editButton = umCanUpdateUserRole ? `
                <button
                    type="button"
                    data-user-details="${detailsPayload}"
                    onclick="openEditModalFromButton(
                        this,
                        'users',
                        ${Number(user.id)},
                        ${jsAttr(user.name)},
                        ${jsAttr(user.email)},
                        ${jsAttr(user.role_id)},
                        ${jsAttr(user.status)}
                    )"
                    class="ui-action-btn ui-action-edit"
                    data-tooltip="Edit account"
                    data-tooltip-tone="edit"
                    aria-label="Edit account">
                    <i class="fa-solid fa-pen"></i>
                </button>
            ` : '';

                const toggleButton = umCanDisableUsers ? `
                <button
                    type="button"
                    onclick="openToggleConfirm(
                        ${Number(user.id)},
                        ${jsAttr(user.status)},
                        ${jsAttr(user.name)}
                    )"
                    class="ui-action-btn ${toggleClass}"
                    data-tooltip="${toggleTooltip}"
                    data-tooltip-tone="${toggleTone}"
                    aria-label="${toggleTooltip}">
                    <i class="fa-solid ${toggleIcon}"></i>
                </button>
            ` : '';

                const resetButton = umCanUpdateUserPassword ? `
                <button
                    type="button"
                    onclick="openResetModal(
                        'users',
                        ${Number(user.id)},
                        ${jsAttr(user.name)}
                    )"
                    class="ui-action-btn ui-action-reset"
                    data-tooltip="Reset password"
                    data-tooltip-tone="reset"
                    aria-label="Reset password">
                    <i class="fa-solid fa-key"></i>
                </button>
            ` : '';

                const viewButton = umCanViewAccounts ? `
                <button
                    type="button"
                    data-user-details="${detailsPayload}"
                    onclick="openViewModalFromButton(this)"
                    class="ui-action-btn ui-action-view"
                    data-tooltip="View details"
                    data-tooltip-tone="view"
                    aria-label="View details">
                    <i class="fa-solid fa-eye"></i>
                </button>
            ` : '';

                return `
                    <div class="ui-action-group">
                        ${editButton}
                        ${toggleButton}
                        ${resetButton}
                        ${viewButton}
                    </div>
                `;
            }

            const listBody =
                document.getElementById('umTableBody');

            const gridBody =
                document.getElementById('umGridBody');

            if (!listBody || !gridBody) {
                return;
            }

            if (
                !Array.isArray(users) ||
                users.length === 0
            ) {
                const searchValue =
                    String(
                        umState.search || ''
                    ).trim();

                listBody.innerHTML = `
                    <div
                        id="umTableEmptyState"
                        class="empty-state-host">
                    </div>
                `;

                gridBody.innerHTML = `
                    <div
                        id="umGridEmptyState"
                        class="empty-state-host table-grid-empty">
                    </div>
                `;

                const renderEmptyState =
                    host => {
                        if (!host) {
                            return;
                        }

                        if (searchValue) {
                            window.EmptyState
                                ?.renderSearch({
                                    host,
                                    input: document.getElementById(
                                        'umSearch'
                                    ),
                                    query: searchValue,
                                    message: 'Try a different name or email.',
                                });

                            return;
                        }

                        window.EmptyState
                            ?.render({
                                host,
                                icon: 'fa-users',
                                title: 'No users found',
                                message: 'Users matching the selected filters will appear here.',
                            });
                    };

                renderEmptyState(
                    document.getElementById(
                        'umTableEmptyState'
                    )
                );

                renderEmptyState(
                    document.getElementById(
                        'umGridEmptyState'
                    )
                );

                return;
            }

            let listHtml = '';
            let gridHtml = '';

            users.forEach(function(user) {
                const normalizedRole =
                    normalizeUmRole(user);

                const roleSlug =
                    normalizedRole.slug || 'none';

                const roleLabel =
                    normalizedRole.label || 'Patient';

                const normalizedStatus =
                    String(user.status || '')
                    .trim()
                    .toLowerCase();

                const isActive =
                    normalizedStatus === 'active';

                const statusClass =
                    isActive ?
                    'status-active' :
                    'status-inactive';

                const statusLabel =
                    normalizedStatus ?
                    normalizedStatus.charAt(0).toUpperCase() +
                    normalizedStatus.slice(1) :
                    'Unknown';

                const registeredDay =
                    user.created_at_day || '—';

                const createdFull =
                    registeredDay +
                    (
                        user.created_at_time ?
                        ` ${user.created_at_time}` :
                        ''
                    );

                const safeName =
                    escapeHtml(user.name || '');

                const safeEmail =
                    escapeHtml(user.email || '');

                const safeRoleLabel =
                    escapeHtml(roleLabel);

                const safeRoleSlug =
                    escapeHtml(roleSlug);

                const safeRegisteredDay =
                    escapeHtml(registeredDay);

                const details =
                    buildDetails(
                        user,
                        roleLabel,
                        statusLabel,
                        createdFull
                    );

                const detailsPayload =
                    escapeHtml(
                        JSON.stringify(details)
                    );

                const actionButtons =
                    buildActionButtons(
                        user,
                        detailsPayload
                    );

                const avatarMd =
                    window.PatientUI?.buildAvatarHtml?.({
                        name: user.name || 'Patient',
                        size: 'md',
                        escapeHtml
                    }) || '';

                const avatarSm =
                    window.PatientUI?.buildAvatarHtml?.({
                        name: user.name || 'Patient',
                        size: 'sm',
                        escapeHtml
                    }) || '';

                listHtml += `
                    <div
                        class="table-list-row user-table-row"
                        data-name="${safeName.toLowerCase()}"
                        data-email="${safeEmail.toLowerCase()}"
                        data-role="${safeRoleLabel.toLowerCase()}">

                        <div class="um-list-layout">
                            <div class="table-primary um-user-cell">
                                ${avatarMd}

                                <div class="um-user-copy">
                                    <strong class="um-user-name">
                                        ${safeName}
                                    </strong>

                                    <span class="um-user-email">
                                        ${safeEmail}
                                    </span>
                                </div>
                            </div>

                            <div class="um-role-cell">
                                <span class="badge-role role-${safeRoleSlug}">
                                    ${safeRoleLabel}
                                </span>
                            </div>

                            <div class="um-status-cell">
                                <span class="status-pill ${statusClass}">
                                    <span class="status-dot"></span>
                                    ${statusLabel}
                                </span>
                            </div>

                            <div class="um-registered-cell">
                                <span class="table-date">
                                    <i class="fa-regular fa-calendar"></i>
                                    ${safeRegisteredDay}
                                </span>
                            </div>

                            <div class="um-actions-cell table-action-cell">
                                ${actionButtons}
                            </div>
                        </div>
                    </div>
                `;

                gridHtml += `
                    <div class="table-record-card">
                        <div class="table-record-card-layout">
                            <div class="table-record-content">
                                <div class="table-record-header">
                                    <div class="table-primary">
                                        ${avatarSm}

                                        <div class="um-user-copy">
                                            <strong class="table-record-title">
                                                ${safeName}
                                            </strong>

                                            <span class="ui-muted-text">
                                                ${safeEmail}
                                            </span>
                                        </div>
                                    </div>

                                    <span class="status-pill ${statusClass}">
                                        <span class="status-dot"></span>
                                        ${statusLabel}
                                    </span>
                                </div>

                                <div class="table-record-meta">
                                    <div class="table-record-row">
                                        <span class="table-record-label">
                                            Role
                                        </span>

                                        <span class="table-record-value">
                                            <span class="badge-role role-${safeRoleSlug}">
                                                ${safeRoleLabel}
                                            </span>
                                        </span>
                                    </div>

                                    <div class="table-record-row">
                                        <span class="table-record-label">
                                            Registered
                                        </span>

                                        <span class="table-record-value">
                                            <span class="table-date">
                                                <i class="fa-regular fa-calendar"></i>
                                                ${safeRegisteredDay}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="table-record-actions">
                                ${actionButtons}
                            </div>
                        </div>
                    </div>
                `;
            });

            listBody.innerHTML =
                listHtml;

            gridBody.innerHTML =
                gridHtml;
        }

        function umRenderPagebar(p) {
            if (!p) {
                return;
            }

            const currentPage =
                Number(p.current_page || 1);

            const lastPage =
                Math.max(
                    1,
                    Number(p.last_page || 1)
                );

            const total =
                Number(p.total || 0);

            const from =
                Number(p.from || 0);

            const to =
                Number(p.to || 0);

            const paginationContainers = [
                document.getElementById(
                    'umPaginationTop'
                ),
                document.getElementById(
                    'umPaginationBottom'
                ),
            ].filter(Boolean);

            const infoElements = [
                document.getElementById(
                    'umPaginationInfoTop'
                ),
                document.getElementById(
                    'umPaginationInfoBottom'
                ),
            ].filter(Boolean);

            const bars = [
                document.getElementById(
                    'umPaginationTopBar'
                ),
                document.getElementById(
                    'umPaginationBottomBar'
                ),
            ].filter(Boolean);

            window.renderGlobalPagination?.({
                currentPage,
                lastPage,
                total,
                from,
                to,

                containers: paginationContainers,

                infoElements,

                bars,

                itemLabel: 'users',

                onPageChange(page) {
                    umState.page = page;
                    umFetch();
                },
            });

            const perPageSelect =
                document.getElementById(
                    'umPerPageSelect'
                );

            if (
                perPageSelect &&
                p.per_page
            ) {
                perPageSelect.value =
                    String(p.per_page);

                window.syncGlobalPageSizeSelect?.(
                    perPageSelect,
                    p.per_page
                );
            }
        }

        function umRenderCounts(counts) {
            if (!counts) return;

            var totalEl = document.getElementById('countTotalUsers');
            var activeEl = document.getElementById('countActiveUsers');
            var inactiveEl = document.getElementById('countInactiveUsers');
            var badgeEl = document.getElementById('countBadgeUsers');

            if (totalEl) totalEl.textContent = counts.all ?? 0;
            if (activeEl) activeEl.textContent = counts.active ?? 0;
            if (inactiveEl) inactiveEl.textContent = counts.inactive ?? 0;
            if (badgeEl) badgeEl.textContent = counts.all ?? 0;
        }

        const UM_TOAST_CACHE = new Map();

        function showUserManagementToast(type, message) {
            const normalizedMessage = String(message || '').trim();

            if (!normalizedMessage) return;

            const normalizedType = type === 'error' ? 'error' : 'success';
            const cacheKey = `${normalizedType}:${normalizedMessage}`;
            const now = Date.now();

            if (UM_TOAST_CACHE.has(cacheKey) && now - UM_TOAST_CACHE.get(cacheKey) < 1200) {
                return;
            }

            UM_TOAST_CACHE.set(cacheKey, now);

            if (typeof window.showToast === 'function') {
                window.showToast({
                    type: normalizedType,
                    title: normalizedType === 'error' ? 'Error' : 'Success',
                    message: normalizedMessage,
                    duration: normalizedType === 'error' ? 7000 : 6000,
                });

                return;
            }

            alert(normalizedMessage);
        }

        function showSuccessToast(message) {
            showUserManagementToast('success', message);
        }

        function showErrorToast(message) {
            showUserManagementToast('error', message);
        }

        window.handleUserManagementPerPageChange =
            function(value) {

                const parsed =
                    Number(value);

                umState.perPage = [10, 20, 50, 100].includes(parsed) ?
                    parsed :
                    10;

                umState.page = 1;

                umFetch();
            };

        window.handleUserManagementSearch = function(value) {
            umState.search = String(value || '').trim();
            umState.page = 1;

            umFetch(true);
        };

        document.addEventListener('DOMContentLoaded', function() {

            const initialTableEmptyState =
                document.getElementById(
                    'umTableEmptyState'
                );

            const initialGridEmptyState =
                document.getElementById(
                    'umGridEmptyState'
                );

            const initialSearchValue =
                String(
                    umState.search || ''
                ).trim();

            const renderInitialEmptyState =
                host => {

                    if (!host) {
                        return;
                    }

                    if (initialSearchValue) {
                        window.EmptyState
                            ?.renderSearch({
                                host,
                                input: document.getElementById(
                                    'umSearch'
                                ),
                                query: initialSearchValue,
                                message: 'Try a different name or email.',
                            });

                        return;
                    }

                    window.EmptyState
                        ?.render({
                            host,
                            icon: 'fa-users',
                            title: 'No users found',
                            message: 'Users matching the selected filters will appear here.',
                        });
                };

            renderInitialEmptyState(
                initialTableEmptyState
            );

            renderInitialEmptyState(
                initialGridEmptyState
            );

            const editRoleSelect = document.getElementById('editRole');

            if (
                editRoleSelect &&
                editRoleSelect.dataset.roleChangeBound !== 'true'
            ) {
                editRoleSelect.dataset.roleChangeBound = 'true';

                editRoleSelect.addEventListener(
                    'change',
                    syncEditRoleConfirmation
                );
            }

            if (typeof applyTheme === 'function') applyTheme(localStorage.getItem('theme') || 'light');

            const initialPagination = {
                total: {{ $users->total() }},

                from: {{ $users->firstItem() ?? 0 }},

                to: {{ $users->lastItem() ?? 0 }},

                current_page: {{ $users->currentPage() }},

                last_page: {{ $users->lastPage() }},

                per_page: {{ $users->perPage() }},
            };

            if (
                typeof window.loadPaginationBarModule ===
                'function'
            ) {
                window
                    .loadPaginationBarModule()
                    .then(() => {
                        umRenderPagebar(
                            initialPagination
                        );
                    })
                    .catch(error => {
                        console.error(
                            'Unable to initialize user pagination.',
                            error
                        );
                    });
            } else {
                umRenderPagebar(
                    initialPagination
                );
            }

            window.initGlobalPageSizeSelects?.();

            var toggleForm = document.getElementById('toggleConfirmForm');
            if (toggleForm) {
                toggleForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var form = this;
                    var url = form.action;
                    var btn = document.getElementById('toggleConfirmBtn');
                    var originalHtml = btn.dataset.originalHtml || btn.innerHTML;

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing…';

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: '_token={{ csrf_token() }}'
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
                            if (result.ok && result.data.success) {
                                closeAllModals();
                                showSuccessToast(result.data.message);
                                umFetch(true);
                            } else {
                                showErrorToast(result.data.message || 'Something went wrong.');
                            }
                        })
                        .catch(function() {
                            showErrorToast('Something went wrong. Please try again.');
                        })
                        .finally(function() {
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        });
                });
            }

            var editForm = document.getElementById('editForm');

            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const validation = window.validateGlobalForm?.(this);

                    if (validation && !validation.valid) {
                        return;
                    }

                    var form = this;
                    var url = form.action;
                    var submitBtn = form.querySelector('button[type="submit"]');
                    var originalHtml = submitBtn.innerHTML;
                    var editPhoneInput = document.getElementById('editPhone');

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

                    var params = new URLSearchParams();
                    params.append('_token', '{{ csrf_token() }}');
                    params.append('_method', 'PUT');
                    params.append('name', document.getElementById('editName').value);
                    params.append('email', document.getElementById('editEmail').value);
                    params.append('phone', getNormalizedUserPhoneValue(editPhoneInput));
                    params.append('birthdate', document.getElementById('editBirthdate').value);
                    params.append('gender', document.getElementById('editGender').value);
                    params.append('role_id', document.getElementById('editRole').value);
                    params.append('status', form.querySelector('input[name="status"]:checked')?.value ??
                        '');

                    if (String(document.getElementById('editRole').value || '') !== String(document
                            .getElementById(
                                'editOriginalRole').value || '')) {
                        params.append('admin_current_password', document.getElementById(
                                'editAdminCurrentPassword')
                            .value);
                    }

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: params.toString()
                        })
                        .then(function(res) {
                            return res.json().then(function(data) {
                                return {
                                    ok: res.ok,
                                    status: res.status,
                                    data: data
                                };
                            });
                        })
                        .then(function(result) {
                            if (
                                result.status === 422 &&
                                result.data?.errors
                            ) {
                                Object.entries(
                                    result.data.errors
                                ).forEach(([name, messages]) => {
                                    const field =
                                        form.querySelector(
                                            `[name="${CSS.escape(name)}"]`
                                        );

                                    const message =
                                        Array.isArray(messages) ?
                                        messages[0] :
                                        messages;

                                    if (field) {
                                        window
                                            .showFormInputValidationMessage
                                            ?.(field, message);
                                    }
                                });

                                showErrorToast(
                                    result.data.message ||
                                    'Please review the highlighted fields.'
                                );

                                return;
                            }

                            if (
                                result.ok &&
                                result.data?.success
                            ) {
                                closeAllModals();

                                showSuccessToast(
                                    result.data.message ||
                                    'User updated successfully.'
                                );

                                umFetch(true);
                                return;
                            }

                            showErrorToast(
                                result.data?.message ||
                                'Something went wrong.'
                            );
                        })
                        .catch(function() {
                            showErrorToast('Something went wrong. Please try again.');
                        })
                        .finally(function() {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        });
                });
            }

            var addUserForm =
                document.getElementById('addUserForm');

            if (addUserForm) {
                addUserForm.addEventListener(
                    'submit',
                    function(event) {
                        const validation =
                            window.validateGlobalForm?.(this);

                        if (
                            validation &&
                            !validation.valid
                        ) {
                            event.preventDefault();
                            return;
                        }

                        const addPhoneInput =
                            document.getElementById(
                                'addPhoneInput'
                            );

                        if (addPhoneInput) {
                            addPhoneInput.value =
                                getNormalizedUserPhoneValue(
                                    addPhoneInput
                                );
                        }
                    }
                );
            }

            var resetForm = document.getElementById('resetForm');
            if (resetForm) {
                resetForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const validation = window.validateGlobalForm?.(this);

                    if (validation && !validation.valid) {
                        return;
                    }

                    var form = this;
                    var url = form.action;
                    var submitBtn = form.querySelector('button[type="submit"]');
                    var originalHtml = submitBtn.innerHTML;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resetting…';

                    var formData = new FormData(form);

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData
                        })
                        .then(function(res) {
                            return res.json().then(function(data) {
                                return {
                                    ok: res.ok,
                                    status: res.status,
                                    data: data
                                };
                            });
                        })
                        .then(function(result) {
                            if (result.status === 422 && result.data.errors) {
                                var msgs = Object.values(result.data.errors).flat().join(' ');
                                showErrorToast(msgs);
                            } else if (result.ok && result.data.success) {
                                closeAllModals();
                                showSuccessToast(result.data.message || 'Password reset successfully.');
                                document.getElementById('resetPassword').value = '';
                                document.getElementById('resetPasswordConf').value = '';
                            } else {
                                showErrorToast(result.data.message || 'Something went wrong.');
                            }
                        })
                        .catch(function() {
                            showErrorToast('Something went wrong. Please try again.');
                        })
                        .finally(function() {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        });
                });
            }
        });
    </script>
@endsection
