@extends('layouts.admin')

@section('title', 'User Management | PUP Taguig Dental Clinic')

@section('content')

@php
$totalUsers = $totalUsers ?? ($allUsersCount ?? ($users->total() ?? 0));
$activeCount = $activeCount ?? 0;
$inactiveCount = $inactiveCount ?? 0;
@endphp

<main id="mainContent"
    class="admin-page-shell user-management-page page-enter mode-list px-3 sm:px-6 pt-[82px] pb-8 min-h-screen">
    <div style="max-width:1280px; margin:0 auto;">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">User Management</h1>
                </div>

                <div class="flex items-center gap-3 flex-wrap w-full sm:w-auto">
                    <button type="button" onclick="openModal('addModal', this)" class="um-hero-btn">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Add New User</span>
                    </button>
                </div>
            </div>
        </div>

        @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showSuccessToast("{{ session('success') }}");
            });
        </script>
        @endif

        @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showErrorToast("{{ session('error') }}");
            });
        </script>
        @endif

        <div class="relative z-10 mt-4 px-4 sm:px-6 lg:px-7 pb-8">

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

            <div class="um-users-card card bg-white rounded-xl shadow border border-gray-100 overflow-visible mb-6">
                <div class="um-users-toolbar px-4 sm:px-5 py-4 border-b bg-gray-50">
                    <div class="um-users-heading">
                        <div class="card-header-icon">
                            <i class="fa-solid fa-users-gear"></i>
                        </div>

                        <h2 class="font-bold text-gray-800 text-sm">All System Users</h2>

                        <span id="countBadgeUsers"
                            class="text-[10px] font-bold bg-[#8B0000] text-white px-2 py-0.5 rounded-full">
                            {{ $totalUsers }}
                        </span>
                    </div>

                    <form method="GET" action="{{ route('admin.user_management') }}" id="umFilterForm"
                        class="um-users-filter-form">
                        <div class="um-search-mobile um-search-row voice-search-row" data-voice-field>
                            <div class="search-wrap global-search" data-search-wrapper>
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                                <input id="umSearch" name="search" class="search-input no-voice"
                                    placeholder="Search name or email…" value="{{ $search ?? '' }}" autocomplete="off"
                                    data-search-input onkeydown="if(event.key==='Enter'){event.preventDefault();}" />

                                <button type="button" class="search-clear" data-search-clear aria-label="Clear search">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>

                            <div class="voice-input-toggle">
                                <button type="button" id="umSearchMicBtn" class="voice-search-mic external"
                                    data-voice-trigger data-voice-target="#umSearch"
                                    data-voice-status="#umSearchVoiceStatus" aria-label="Voice input for user search">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>

                                <span id="umSearchVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>

                        <div class="view-toggle-container um-view-toggle" id="umViewToggle" aria-label="View options">
                            <span class="view-slider" aria-hidden="true"></span>

                            <button type="button" class="btn-view-mode um-view-toggle-btn active" id="umListViewBtn"
                                title="List view" aria-label="List view" aria-pressed="true">
                                <i class="fa-solid fa-table-list"></i>
                            </button>

                            <button type="button" class="btn-view-mode um-view-toggle-btn" id="umGridViewBtn"
                                title="Grid view" aria-label="Grid view" aria-pressed="false">
                                <i class="fa-solid fa-grip"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="um-view um-list-view" id="umListView">
                <div class="um-table-scroll overflow-x-auto">
                    <table class="w-full text-sm data-table um-table">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr class="text-[10px] uppercase tracking-wide text-[#8B0000] font-bold">
                                <th class="py-3 px-3 sm:px-5 text-left w-12 hidden sm:table-cell">#</th>
                                <th class="py-3 px-4 text-left">User</th>
                                <th class="py-3 px-4 text-left">Role</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-left hidden lg:table-cell">Registered</th>
                                <th class="py-3 px-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="umTableBody">
                            @forelse($users as $user)
                            <tr class="user-table-row border-b border-gray-50 last:border-0"
                                data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}"
                                data-role="{{ strtolower(optional($user->role)->name ?? '') }}">
                                <td class="py-3.5 px-3 sm:px-5 hidden sm:table-cell">
                                    <span class="text-xs text-gray-400 font-medium">{{ $users->firstItem() +
                                        $loop->index
                                        }}</span>
                                </td>

                                <td class="py-3.5 px-2 sm:px-4">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-800 text-sm leading-tight">
                                                {{ $user->name }}
                                            </div>
                                            <div class="text-[11px] text-gray-400 mt-0.5 hidden sm:block">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @php $roleSlug = optional($user->role)->slug ?? 'none'; @endphp

                                    <span class="badge-role role-{{ $roleSlug }}">
                                        {{ optional($user->role)->name ?? 'No Role' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $user->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 hidden lg:table-cell">
                                    <span class="text-xs text-gray-600">{{ $user->created_at->format('M d, Y') }}</span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="um-action-group flex items-center justify-center gap-1">
                                        <button type="button" onclick="openEditModal(
                                                    'users',
                                                    {{ $user->id }},
                                                    @js($user->name),
                                                    @js($user->email),
                                                    @js($user->role_id),
                                                    @js($user->status)
                                                  )" class="action-btn btn-edit" title="Edit account">
                                            <i class="fa-solid fa-pen text-[11px]"></i>
                                        </button>

                                        <button type="button"
                                            onclick="openToggleConfirm({{ $user->id }}, @js($user->status), @js($user->name))"
                                            class="action-btn {{ $user->status === 'active' ? 'btn-toggle-on' : 'btn-toggle-off' }}"
                                            title="{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                            <i
                                                class="fa-solid {{ $user->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }} text-[11px]"></i>
                                        </button>

                                        <button type="button"
                                            onclick="openResetModal('users', {{ $user->id }}, @js($user->name))"
                                            class="action-btn btn-reset" title="Reset password">
                                            <i class="fa-solid fa-key text-[11px]"></i>
                                        </button>

                                        <button type="button" onclick="openViewModal(
                                                    @js($user->name),
                                                    @js($user->email),
                                                    @js(optional($user->role)->name ?? 'No Role'),
                                                    @js(ucfirst($user->status)),
                                                    'Users',
                                                    @js($user->created_at ? $user->created_at->format('M d, Y h:i A') : 'N/A')
                                                  )" class="action-btn btn-view-details" title="View details">
                                            <i class="fa-solid fa-eye text-[11px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr id="dbEmptyRow">
                                <td colspan="6" style="padding:3.5rem 1rem;text-align:center;">
                                    <div
                                        style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;background:#f3f4f6;border-radius:18px;margin-bottom:1rem;">
                                        <i class="fa-solid fa-magnifying-glass"
                                            style="font-size:1.6rem;color:#d1d5db;"></i>
                                    </div>
                                    <p style="font-size:.9rem;font-weight:700;color:#374151;margin:0 0 .3rem;">No
                                        users
                                        found</p>
                                    <p style="font-size:.78rem;color:#9ca3af;margin:0;">Try adjusting your filters.
                                    </p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="um-view" id="umGridView" hidden>
                <div class="um-grid-wrap">
                    <div class="um-grid" id="umGridBody">
                        @forelse($users as $user)
                        @php
                        $roleSlug = optional($user->role)->slug;
                        $roleName = optional($user->role)->name ?? 'No Role';
                        $roleBg =
                        $roleSlug === 'patient'
                        ? '#dbeafe'
                        : ($roleSlug === 'dentist'
                        ? '#d1fae5'
                        : '#fee2e2');
                        $roleColor =
                        $roleSlug === 'patient'
                        ? '#1d4ed8'
                        : ($roleSlug === 'dentist'
                        ? '#065f46'
                        : '#8B0000');
                        @endphp

                        <div class="um-grid-card">
                            <div class="um-grid-top">
                                <div class="um-grid-number">#{{ $users->firstItem() + $loop->index }}</div>
                                <span
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $user->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-800 text-sm leading-tight">
                                        {{ $user->name }}
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        {{ $user->email }}
                                    </div>
                                </div>
                            </div>

                            <div class="um-grid-meta">
                                <div class="um-grid-field">
                                    <div class="um-grid-label">Role</div>
                                    <div class="um-grid-value">
                                        <span class="badge-role role-{{ $roleSlug ?? 'none' }}">
                                            {{ $roleName }}
                                        </span>
                                    </div>
                                </div>

                                <div class="um-grid-field">
                                    <div class="um-grid-label">Registered</div>
                                    <div class="um-grid-value">{{ $user->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>

                            <div class="um-action-group flex items-center justify-end gap-1 flex-wrap">
                                <button type="button" onclick="openEditModal(
                                                'users',
                                                {{ $user->id }},
                                                @js($user->name),
                                                @js($user->email),
                                                @js($user->role_id),
                                                @js($user->status)
                                            )" class="action-btn btn-edit" title="Edit account">
                                    <i class="fa-solid fa-pen text-[11px]"></i>
                                </button>

                                <button type="button"
                                    onclick="openToggleConfirm({{ $user->id }}, @js($user->status), @js($user->name))"
                                    class="action-btn {{ $user->status === 'active' ? 'btn-toggle-on' : 'btn-toggle-off' }}"
                                    title="{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                    <i
                                        class="fa-solid {{ $user->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }} text-[11px]"></i>
                                </button>

                                <button type="button"
                                    onclick="openResetModal('users', {{ $user->id }}, @js($user->name))"
                                    class="action-btn btn-reset" title="Reset password">
                                    <i class="fa-solid fa-key text-[11px]"></i>
                                </button>

                                <button type="button" onclick="openViewModal(
                                                @js($user->name),
                                                @js($user->email),
                                                @js($roleName),
                                                @js(ucfirst($user->status)),
                                                'Users',
                                                @js($user->created_at ? $user->created_at->format('M d, Y h:i A') : 'N/A')
                                            )" class="action-btn btn-view-details" title="View details">
                                    <i class="fa-solid fa-eye text-[11px]"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div
            class="px-4 sm:px-5 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-wrap">
                <p class="text-xs text-gray-500 um-pagebar-info">
                    Showing
                    <strong>{{ $users->firstItem() ?? 0 }}</strong>–<strong>{{ $users->lastItem() ?? 0 }}</strong>
                    of <strong>{{ $users->total() }}</strong> users
                </p>

                <div class="global-page-size-control um-page-size-control">
                    <label for="umPerPageSelect">Show</label>

                    <div class="global-page-size-select" data-global-page-size data-page-size-input="#umPerPageSelect">
                        <select id="umPerPageSelect" class="global-page-size-native" tabindex="-1" aria-hidden="true">
                            @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) ($perPage ?? 10)===$size ? 'selected' : '' }}>
                                {{ $size }}</option>
                            @endforeach
                        </select>

                        <button type="button" class="global-page-size-trigger" data-page-size-trigger
                            aria-haspopup="listbox" aria-expanded="false">
                            <span data-page-size-value>{{ (int) ($perPage ?? 10) }}</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                        <div class="global-page-size-menu" role="listbox">
                            @foreach ([10, 20, 50, 100] as $size)
                            <button type="button"
                                class="global-page-size-option {{ (int) ($perPage ?? 10) === $size ? 'is-selected' : '' }}"
                                data-page-size-option data-value="{{ $size }}" role="option"
                                aria-selected="{{ (int) ($perPage ?? 10) === $size ? 'true' : 'false' }}">
                                <span>{{ $size }}</span>
                                <i class="fa-solid fa-check"></i>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <span>per page</span>
                </div>
            </div>

            <div class="um-pagination-wrap flex items-center gap-1.5"></div>
        </div>
    </div>
    </div>
</main>

<div class="modal-overlay" id="addModal" aria-hidden="true">
    <div class="modal-box-inner um-user-modal um-user-modal-lg" onclick="event.stopPropagation()">
        <div
            class="um-user-modal-header px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3 min-w-0">
                <div
                    class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#8B0000] via-[#a40000] to-[#6B0000] flex items-center justify-center shadow-lg shadow-red-900/20 flex-shrink-0">
                    <i class="fa-solid fa-user-plus text-white text-sm"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-extrabold text-gray-800 text-lg leading-tight">Add New User</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Create a system account and assign access permissions.</p>
                </div>
            </div>

            <button type="button" onclick="closeModal('addModal')" data-close-modal="addModal" class="um-modal-x"
                aria-label="Close add user modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.user_management.store') }}" id="addUserForm"
            class="flex-1 flex flex-col min-h-0">
            @csrf

            <div class="um-user-modal-body">
                @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 rounded-2xl p-3 text-xs text-red-700 space-y-1.5">
                    @foreach ($errors->all() as $error)
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-xmark mt-0.5"></i>
                        <span>{{ $error }}</span>
                    </div>
                    @endforeach
                </div>
                @endif

                <div class="um-user-modal-grid">
                    <div class="um-user-main-card">
                        <div class="um-section-title">
                            <div class="um-section-icon bg-red-50 text-[#8B0000]">
                                <i class="fa-solid fa-id-card text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-extrabold text-gray-800 leading-tight">Account Details</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Basic identity, role assignment, and account
                                    status.</p>
                            </div>
                        </div>

                        <div class="um-field-grid">
                            <div class="um-field-full">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <div class="voice-search-row" data-voice-field>
                                    <input type="text" id="addNameInput" name="name" value="{{ old('name') }}"
                                        class="field-input flex-1 min-w-0 border border-gray-200 px-3.5 py-3 text-sm bg-white"
                                        placeholder="e.g. Juan dela Cruz" required>
                                    <div class="voice-input-toggle">
                                        <button type="button" id="addNameMicBtn" class="voice-search-mic external"
                                            data-voice-trigger data-voice-target="#addNameInput"
                                            data-voice-status="#addNameVoiceStatus"
                                            aria-label="Voice input for full name">
                                            <i class="fa-solid fa-microphone"></i>
                                        </button>
                                        <span id="addNameVoiceStatus" class="voice-status hidden" data-voice-status
                                            aria-live="polite"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="um-field-full">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <div class="voice-search-row" data-voice-field>
                                    <i class="fa-solid fa-envelope text-gray-400 text-xs flex-shrink-0 pl-1"></i>
                                    <input type="email" id="addEmailInput" name="email" value="{{ old('email') }}"
                                        class="field-input flex-1 min-w-0 border border-gray-200 px-3.5 py-3 text-sm bg-white"
                                        placeholder="user@pup.edu.ph" required>
                                    <div class="voice-input-toggle">
                                        <button type="button" id="addEmailMicBtn" class="voice-search-mic external"
                                            data-voice-trigger data-voice-target="#addEmailInput"
                                            data-voice-status="#addEmailVoiceStatus" aria-label="Voice input for email">
                                            <i class="fa-solid fa-microphone"></i>
                                        </button>
                                        <span id="addEmailVoiceStatus" class="voice-status hidden" data-voice-status
                                            aria-live="polite"></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                    Role
                                </label>
                                <select name="role_id"
                                    class="field-input w-full border border-gray-200 px-3.5 py-3 text-sm bg-white">
                                    <option value="">No Role</option>
                                    @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id')==$role->id ? 'selected' : '' }}>
                                        {{ $role->display_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                    Account Type
                                </label>
                                <div
                                    class="field-input w-full border border-dashed border-gray-200 px-3.5 py-3 text-sm bg-gray-50 text-gray-500 flex items-center">
                                    System-managed user account
                                </div>
                            </div>
                        </div>

                        <div class="um-divider"></div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>

                            <div class="um-status-grid">
                                <label class="um-status-card um-status-card--active">
                                    <input type="radio" name="status" value="active" {{ old('status', 'active'
                                        )==='active' ? 'checked' : '' }}
                                        style="accent-color:#8B0000; margin-top:.22rem;">
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-emerald-800 leading-tight">Active</div>
                                        <div class="text-[11px] text-emerald-700 mt-0.5">Can access the system
                                            immediately</div>
                                    </div>
                                </label>

                                <label class="um-status-card um-status-card--inactive">
                                    <input type="radio" name="status" value="inactive" {{ old('status')==='inactive'
                                        ? 'checked' : '' }} style="accent-color:#8B0000; margin-top:.22rem;">
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-gray-700 leading-tight">Inactive</div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">Account exists but login is
                                            disabled</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="um-user-side-card">
                        <div class="um-section-title">
                            <div class="um-section-icon bg-blue-50 text-blue-600">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-extrabold text-gray-800 leading-tight">Security Setup</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Set the initial login credentials.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    <input type="password" name="password" id="addPassword"
                                        placeholder="Min. 8 characters"
                                        class="field-input w-full border border-gray-200 pl-10 pr-11 py-3 text-sm bg-white"
                                        required>
                                    <button type="button" onclick="togglePassVis('addPassword','addEye')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i class="fa-regular fa-eye text-sm" id="addEye"></i>
                                    </button>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1.5">Use at least 8 characters for better
                                    security.</p>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    <input type="password" name="password_confirmation" id="addPasswordConf"
                                        placeholder="Repeat password"
                                        class="field-input w-full border border-gray-200 pl-10 pr-11 py-3 text-sm bg-white"
                                        required>
                                    <button type="button" onclick="togglePassVis('addPasswordConf','addEye2')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i class="fa-regular fa-eye text-sm" id="addEye2"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="um-password-note">
                                The user can update their password after first sign-in depending on your account
                                workflow.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-ft um-user-modal-footer">
                <button type="button" onclick="closeModal('addModal')" class="modal-btn-ghost">
                    Cancel
                </button>

                <button type="submit" class="modal-btn-confirm-reject um-save-user-btn">
                    <span class="btn-confirm-icon">
                        <i class="fa-solid fa-floppy-disk"></i>
                    </span>
                    <span>Save User</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editModal" aria-hidden="true">
    <div class="modal-box-inner um-user-modal um-user-modal-md" onclick="event.stopPropagation()">
        <div
            class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow">
                    <i class="fa-solid fa-user-pen text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 text-base">Edit User</h3>
                    <p class="text-[12px] text-gray-500" id="editModalSubtitle">Updating user details</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('editModal')" data-close-modal="editModal" class="um-modal-x"
                aria-label="Close edit user modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" id="editForm" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                    Full Name <span class="text-red-500">*</span>
                </label>

                <div class="voice-search-row" data-voice-field>
                    <input type="text" name="name" id="editName" placeholder="Full name"
                        class="field-input flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-2.5 text-sm"
                        required>

                    <div class="voice-input-toggle">
                        <button type="button" id="editNameMicBtn" class="voice-search-mic external" data-voice-trigger
                            data-voice-target="#editName" data-voice-status="#editNameVoiceStatus"
                            aria-label="Voice input for edit full name">
                            <i class="fa-solid fa-microphone"></i>
                        </button>

                        <span id="editNameVoiceStatus" class="voice-status hidden" data-voice-status
                            aria-live="polite"></span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                    Email Address <span class="text-red-500">*</span>
                </label>

                <div class="voice-search-row" data-voice-field>
                    <div class="relative flex-1 min-w-0">
                        <i
                            class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>

                        <input type="email" name="email" id="editEmail" placeholder="user@pup.edu.ph"
                            class="field-input w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm"
                            required>
                    </div>

                    <div class="voice-input-toggle">
                        <button type="button" id="editEmailMicBtn" class="voice-search-mic external" data-voice-trigger
                            data-voice-target="#editEmail" data-voice-status="#editEmailVoiceStatus"
                            aria-label="Voice input for edit email">
                            <i class="fa-solid fa-microphone"></i>
                        </button>

                        <span id="editEmailVoiceStatus" class="voice-status hidden" data-voice-status
                            aria-live="polite"></span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Role</label>
                <div class="um-custom-select" id="editRoleSelect" data-custom-select>
                    <input type="hidden" name="role_id" id="editRole" value="">

                    <button type="button" class="um-custom-select-btn" id="editRoleBtn" aria-haspopup="listbox"
                        aria-expanded="false">
                        <span id="editRoleText">No Role</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>

                    <div class="um-custom-select-menu" id="editRoleMenu" role="listbox">
                        <button type="button" class="um-custom-select-option" data-value="">
                            <span>No Role</span>
                            <i class="fa-solid fa-check"></i>
                        </button>

                        @foreach ($roles as $role)
                        <button type="button" class="um-custom-select-option" data-value="{{ $role->id }}">
                            <span>{{ $role->display_name }}</span>
                            <i class="fa-solid fa-check"></i>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Status
                    <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="status" id="editStatusActive" value="active"
                            style="accent-color:#8B0000;">
                        <span class="text-sm text-gray-700 font-medium">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="status" id="editStatusInactive" value="inactive"
                            style="accent-color:#8B0000;">
                        <span class="text-sm text-gray-700 font-medium">Inactive</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('editModal')" class="modal-btn-ghost">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Update User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetModal" aria-hidden="true">
    <div class="modal-box-inner um-user-modal um-user-modal-sm" onclick="event.stopPropagation()">
        <div
            class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow">
                    <i class="fa-solid fa-key text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 text-base">Reset Password</h3>
                    <p class="text-[10px] text-gray-500" id="resetModalSubtitle">Set a new password</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('resetModal')" data-close-modal="resetModal" class="um-modal-x"
                aria-label="Close reset password modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" id="resetForm" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">New
                    Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="password" name="password" id="resetPassword" placeholder="Min. 8 characters"
                        class="field-input w-full border border-gray-200 rounded-lg pl-9 pr-10 py-2.5 text-sm" required>
                    <button type="button" onclick="togglePassVis('resetPassword','resetEye')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fa-regular fa-eye text-xs" id="resetEye"></i>
                    </button>
                </div>

                <div class="password-strength" id="resetPasswordStrength" data-strength="empty">
                    <div class="password-strength-track">
                        <span class="password-strength-fill"></span>
                    </div>

                    <div class="password-strength-meta">
                        <span id="resetPasswordStrengthLabel">Enter a password</span>
                        <span id="resetPasswordStrengthHint">Use 8+ chars, number, uppercase, and symbol.</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Confirm
                    Password <span class="text-red-500">*</span></label>

                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="password" name="password_confirmation" id="resetPasswordConf"
                        placeholder="Repeat password"
                        class="field-input w-full border border-gray-200 rounded-lg pl-9 pr-10 py-2.5 text-sm" required>
                    <button type="button" onclick="togglePassVis('resetPasswordConf','resetEye2')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fa-regular fa-eye text-xs" id="resetEye2"></i>
                    </button>
                </div>

                <div class="password-match" id="resetPasswordMatch" data-match="empty">
                    <span class="password-match-dot"></span>
                    <span id="resetPasswordMatchText">Confirm your password.</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('resetModal')" class="modal-btn-ghost">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-key"></i> Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="viewModal" aria-hidden="true">
    <div class="modal-box-inner um-user-modal um-user-modal-md um-view-details-modal" onclick="event.stopPropagation()">
        <div class="um-view-details-head">
            <div class="um-view-head-left">
                <div class="um-view-head-icon">
                    <i class="fa-solid fa-id-card-clip"></i>
                </div>

                <div>
                    <h3>Account Details</h3>
                    <p>Review selected account information</p>
                </div>
            </div>

            <button type="button" onclick="closeModal('viewModal')" data-close-modal="viewModal" class="um-modal-x"
                aria-label="Close account details modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="um-view-details-body">
            <div class="um-view-profile-card">
                <div class="um-view-avatar" id="viewInitial">?</div>

                <div class="um-view-profile-copy">
                    <div id="viewName" class="um-view-name"></div>
                    <div id="viewEmail" class="um-view-email"></div>
                </div>
            </div>

            <div class="um-view-info-grid">
                <div class="um-view-info-card">
                    <div class="um-view-info-icon role">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>

                    <div>
                        <span class="um-view-label">Role</span>
                        <strong id="viewRole" class="um-view-value"></strong>
                    </div>
                </div>

                <div class="um-view-info-card">
                    <div class="um-view-info-icon status">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>
                        <span class="um-view-label">Status</span>
                        <strong id="viewStatus" class="um-view-value um-view-status-pill"></strong>
                    </div>
                </div>

                <div class="um-view-info-card">
                    <div class="um-view-info-icon source">
                        <i class="fa-solid fa-database"></i>
                    </div>

                    <div>
                        <span class="um-view-label">Source</span>
                        <strong id="viewSource" class="um-view-value"></strong>
                    </div>
                </div>

                <div class="um-view-info-card">
                    <div class="um-view-info-icon date">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </div>

                    <div>
                        <span class="um-view-label">Created At</span>
                        <strong id="viewCreatedAt" class="um-view-value"></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-ft um-view-details-foot">
            <button type="button" onclick="closeModal('viewModal')" class="modal-btn-ghost">
                Close
            </button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="toggleConfirmModal" aria-hidden="true">
    <div class="modal-box-inner um-user-modal um-user-modal-sm" onclick="event.stopPropagation()">
        <div
            class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <div id="toggleModalIcon" class="w-10 h-10 rounded-xl flex items-center justify-center shadow">
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 text-base" id="toggleModalTitle">Confirm Action</h3>
                    <p class="text-[10px] text-gray-500" id="toggleModalSubtitle">Please confirm this change</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('toggleConfirmModal')" data-close-modal="toggleConfirmModal"
                class="um-modal-x" aria-label="Close confirm action modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6">
            <div id="toggleModalBody" class="rounded-xl p-4 mb-5 flex items-start gap-3 text-sm"></div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('toggleConfirmModal')" class="modal-btn-ghost">
                    Cancel
                </button>
                <form id="toggleConfirmForm" method="POST">
                    @csrf
                    <button type="submit" id="toggleConfirmBtn"
                        class="px-6 py-2.5 rounded-lg text-white text-sm font-bold shadow transition-all flex items-center gap-2">
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const currentDateEl = document.getElementById('currentDate');
    if (currentDateEl) {
        currentDateEl.textContent = new Date().toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    var umState = {
        search: @js($search ?? ''),
        role: @js($roleFilter ?? ''),
        status: @js($statusFilter ?? ''),
        perPage: {{ $perPage ?? 10 }},
    page: @json((int) request('page', 1)),
        };

    var umSearchTimer = null;
    var umController = null;

    function getPreferredUmView() {
        if (window.innerWidth <= 767) return 'grid';
        return localStorage.getItem('userManagementView') || 'list';
    }

    function applyUmView(view, save = true) {
        var listView = document.getElementById('umListView');
        var gridView = document.getElementById('umGridView');
        var listBtn = document.getElementById('umListViewBtn');
        var gridBtn = document.getElementById('umGridViewBtn');

        if (!listView || !gridView) return;

        var finalView = window.innerWidth <= 767 ? 'grid' : view;

        if (finalView === 'grid') {
            listView.hidden = true;
            gridView.hidden = false;
        } else {
            listView.hidden = false;
            gridView.hidden = true;
        }

        document.getElementById('mainContent')?.classList.toggle('mode-grid', finalView === 'grid');
        document.getElementById('mainContent')?.classList.toggle('mode-list', finalView === 'list');

        if (listBtn) {
            listBtn.classList.toggle('active', finalView === 'list');
            listBtn.setAttribute('aria-pressed', finalView === 'list' ? 'true' : 'false');
        }

        if (gridBtn) {
            gridBtn.classList.toggle('active', finalView === 'grid');
            gridBtn.setAttribute('aria-pressed', finalView === 'grid' ? 'true' : 'false');
        }

        if (save && window.innerWidth > 767) {
            localStorage.setItem('userManagementView', finalView);
        }
    }

    function initUmViewToggle() {
        var listBtn = document.getElementById('umListViewBtn');
        var gridBtn = document.getElementById('umGridViewBtn');

        applyUmView(getPreferredUmView(), false);

        if (listBtn && !listBtn.dataset.bound) {
            listBtn.dataset.bound = '1';
            listBtn.addEventListener('click', function () {
                applyUmView('list', true);
            });
        }

        if (gridBtn && !gridBtn.dataset.bound) {
            gridBtn.dataset.bound = '1';
            gridBtn.addEventListener('click', function () {
                applyUmView('grid', true);
            });
        }
    }

    const UM_MODAL_ANIMATION_MS = 220;

    function forceCloseModal(modal) {
        if (!modal) return;

        clearTimeout(modal._closeTimer);

        modal.classList.remove('open', 'closing', 'is-open', 'is-closing');
        modal.setAttribute('aria-hidden', 'true');
        modal.style.pointerEvents = '';
    }

    window.closeAllModals = function () {
        document.querySelectorAll('.modal-overlay.open').forEach(function (modal) {
            window.closeModal(modal.id);
        });
    };

    window.openModal = function (id, trigger = null) {
        var modal = document.getElementById(id);
        if (!modal) return;

        window.lastModalTrigger = trigger || document.activeElement;

        document.querySelectorAll('.modal-overlay.open').forEach(function (m) {
            if (m.id !== id) {
                forceCloseModal(m);
            }
        });

        clearTimeout(modal._closeTimer);

        modal.classList.remove('closing', 'is-closing');
        modal.classList.add('open', 'is-open');
        modal.setAttribute('aria-hidden', 'false');
        modal.style.pointerEvents = 'auto';

        document.body.classList.add('modal-open', 'modal-lock');

        var firstField = modal.querySelector('input, select, textarea, button');
        if (firstField) {
            setTimeout(function () {
                firstField.focus();
            }, 80);
        }
    };

    window.closeModal = function (id) {
        var modal = document.getElementById(id);
        if (!modal || modal.classList.contains('closing')) return;

        var activeEl = document.activeElement;
        if (activeEl && modal.contains(activeEl)) {
            activeEl.blur();
        }

        modal.classList.remove('is-open');
        modal.classList.add('closing', 'is-closing');
        modal.style.pointerEvents = 'none';

        clearTimeout(modal._closeTimer);

        modal._closeTimer = setTimeout(function () {
            modal.classList.remove('open', 'closing', 'is-closing');
            modal.setAttribute('aria-hidden', 'true');
            modal.style.pointerEvents = '';

            if (!document.querySelector('.modal-overlay.open')) {
                document.body.classList.remove('modal-open', 'modal-lock');
            }

            if (window.lastModalTrigger && typeof window.lastModalTrigger.focus === 'function') {
                setTimeout(function () {
                    window.lastModalTrigger.focus();
                }, 30);
            }
        }, UM_MODAL_ANIMATION_MS);
    };

    window.closeModalOutside = function (e, id) {
        if (e.target && e.target.id === id) {
            window.closeModal(id);
        }
    };

    document.addEventListener('click', function (e) {
        var closeBtn = e.target.closest('[data-close-modal]');
        if (!closeBtn) return;

        e.preventDefault();
        e.stopPropagation();

        window.closeModal(closeBtn.getAttribute('data-close-modal'));
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    @if ($errors -> any() && old('_method') !== 'PUT')
        document.addEventListener('DOMContentLoaded', () => openModal('addModal'));
    @endif

    function openToggleConfirm(userId, currentStatus, userName) {
        var isActive = currentStatus === 'active';
        var icon = document.getElementById('toggleModalIcon');
        var title = document.getElementById('toggleModalTitle');
        var subtitle = document.getElementById('toggleModalSubtitle');
        var body = document.getElementById('toggleModalBody');
        var btn = document.getElementById('toggleConfirmBtn');
        var form = document.getElementById('toggleConfirmForm');

        var modal = document.getElementById('toggleConfirmModal');

        modal.classList.remove('is-activate', 'is-deactivate');
        modal.classList.add(isActive ? 'is-deactivate' : 'is-activate');
        form.dataset.userId = userId;
        form.dataset.currentStatus = currentStatus;
        form.dataset.userName = userName;
        form.action = '/admin/user-management/' + userId + '/toggle-status';

        btn.disabled = false;

        if (isActive) {
            icon.className =
                'w-10 h-10 rounded-xl flex items-center justify-center shadow bg-gradient-to-br from-amber-400 to-orange-500';
            icon.innerHTML = '<i class="fa-solid fa-user-slash text-white text-sm"></i>';
            title.textContent = 'Deactivate User';
            subtitle.textContent = 'This will restrict their access';
            body.className = 'rounded-xl p-4 mb-5 flex items-start gap-3 text-sm bg-amber-50 border border-amber-100';
            body.innerHTML =
                '<i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 flex-shrink-0"></i><div><strong class="text-amber-800">' +
                userName +
                '</strong><span class="text-amber-700"> will be <strong>deactivated</strong>. They will no longer be able to log in until reactivated.</span></div>';
            btn.className =
                'px-6 py-2.5 rounded-lg text-white text-sm font-bold shadow transition-all flex items-center gap-2 bg-amber-500 hover:bg-amber-600';
            btn.innerHTML = '<i class="fa-solid fa-user-slash"></i> Deactivate';
        } else {
            icon.className =
                'w-10 h-10 rounded-xl flex items-center justify-center shadow bg-gradient-to-br from-emerald-500 to-green-600';
            icon.innerHTML = '<i class="fa-solid fa-user-check text-white text-sm"></i>';
            title.textContent = 'Activate User';
            subtitle.textContent = 'This will restore their access';
            body.className =
                'rounded-xl p-4 mb-5 flex items-start gap-3 text-sm bg-emerald-50 border border-emerald-100';
            body.innerHTML =
                '<i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 flex-shrink-0"></i><div><strong class="text-emerald-800">' +
                userName +
                '</strong><span class="text-emerald-700"> will be <strong>activated</strong>. They will regain full access to the system.</span></div>';
            btn.className =
                'px-6 py-2.5 rounded-lg text-white text-sm font-bold shadow transition-all flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700';
            btn.innerHTML = '<i class="fa-solid fa-user-check"></i> Activate';
        }

        btn.dataset.originalHtml = btn.innerHTML;

        openModal('toggleConfirmModal');
    }

    function closeEditRoleDropdown() {
        const wrapper = document.getElementById('editRoleSelect');
        const button = document.getElementById('editRoleBtn');

        if (!wrapper || !button) return;

        wrapper.classList.remove('is-open');
        button.setAttribute('aria-expanded', 'false');
    }

    function setEditRoleValue(value) {
        const hiddenInput = document.getElementById('editRole');
        const label = document.getElementById('editRoleText');
        const menu = document.getElementById('editRoleMenu');

        if (!hiddenInput || !label || !menu) return;

        const normalizedValue = value ? String(value) : '';
        hiddenInput.value = normalizedValue;

        const options = Array.from(menu.querySelectorAll('.um-custom-select-option'));
        const selected = options.find(function (option) {
            return String(option.dataset.value || '') === normalizedValue;
        }) || options[0];

        options.forEach(function (option) {
            option.classList.toggle('active', option === selected);
            option.setAttribute('aria-selected', option === selected ? 'true' : 'false');
        });

        label.textContent = selected ? selected.querySelector('span').textContent.trim() : '— No Role —';
    }

    function setEditRoleDisabled(isDisabled) {
        const wrapper = document.getElementById('editRoleSelect');
        const button = document.getElementById('editRoleBtn');
        const hiddenInput = document.getElementById('editRole');

        if (!wrapper || !button || !hiddenInput) return;

        wrapper.classList.toggle('is-disabled', isDisabled);
        button.disabled = isDisabled;
        hiddenInput.disabled = isDisabled;

        if (isDisabled) {
            closeEditRoleDropdown();
        }
    }

    (function initEditRoleDropdown() {
        const wrapper = document.getElementById('editRoleSelect');
        const button = document.getElementById('editRoleBtn');
        const menu = document.getElementById('editRoleMenu');

        if (!wrapper || !button || !menu) return;

        button.addEventListener('click', function () {
            if (button.disabled) return;

            const isOpen = wrapper.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        menu.querySelectorAll('.um-custom-select-option').forEach(function (option) {
            option.addEventListener('click', function () {
                setEditRoleValue(option.dataset.value || '');
                closeEditRoleDropdown();
            });
        });

        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target)) {
                closeEditRoleDropdown();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeEditRoleDropdown();
            }
        });
    })();

    function openEditModal(source, id, name, email, roleId, status) {
        const form = document.getElementById('editForm');

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
        }

        form.dataset.source = source;

        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editModalSubtitle').textContent = 'Editing: ' + name;

        setEditRoleValue(roleId || '');

        document.getElementById('editStatusActive').checked = (status === 'active');
        document.getElementById('editStatusInactive').checked = (status === 'inactive');

        openModal('editModal');
    }

    function openResetModal(source, id, name) {
        if (source === 'patients') {
            document.getElementById('resetForm').action = `/admin/user-management/patient/${id}/reset-password`;
        } else {
            document.getElementById('resetForm').action = `/admin/user-management/${id}/reset-password`;
        }

        document.getElementById('resetModalSubtitle').textContent = 'Resetting password for: ' + name;
        document.getElementById('resetPassword').value = '';
        document.getElementById('resetPasswordConf').value = '';
        updateResetPasswordFeedback();
        openModal('resetModal');
    }

    function openViewModal(name, email, role, status, source, createdAt) {
        const viewName = document.getElementById('viewName');
        const viewEmail = document.getElementById('viewEmail');
        const viewRole = document.getElementById('viewRole');
        const viewStatus = document.getElementById('viewStatus');
        const viewSource = document.getElementById('viewSource');
        const viewCreatedAt = document.getElementById('viewCreatedAt');
        const viewInitial = document.getElementById('viewInitial');

        if (viewName) viewName.textContent = name || 'Unknown User';
        if (viewEmail) viewEmail.textContent = email || 'No email available';
        if (viewRole) viewRole.textContent = role || 'No Role';
        if (viewSource) viewSource.textContent = source || 'Users';
        if (viewCreatedAt) viewCreatedAt.textContent = createdAt || 'N/A';

        if (viewInitial) {
            viewInitial.textContent = String(name || '?').trim().charAt(0).toUpperCase() || '?';
        }

        if (viewStatus) {
            const normalizedStatus = String(status || '').toLowerCase();

            viewStatus.textContent = status || 'Unknown';
            viewStatus.classList.remove('is-active', 'is-inactive');

            if (normalizedStatus === 'active') {
                viewStatus.classList.add('is-active');
            } else {
                viewStatus.classList.add('is-inactive');
            }
        }

        openModal('viewModal');
    }

    function getPasswordStrength(password) {
        const value = String(password || '');

        if (!value.length) {
            return {
                state: 'empty',
                width: '0%',
                label: 'Enter a password',
                hint: 'Use 8+ chars, number, uppercase, and symbol.',
            };
        }

        let score = 0;

        if (value.length >= 8) score++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        if (score <= 1) {
            return {
                state: 'weak',
                width: '35%',
                label: 'Weak password',
                hint: 'Add more characters and numbers.',
            };
        }

        if (score <= 3) {
            return {
                state: 'medium',
                width: '68%',
                label: 'Medium password',
                hint: 'Add uppercase or symbol to improve.',
            };
        }

        return {
            state: 'strong',
            width: '100%',
            label: 'Strong password',
            hint: 'Good password strength.',
        };
    }

    function updateResetPasswordStrength() {
        const input = document.getElementById('resetPassword');
        const meter = document.getElementById('resetPasswordStrength');
        const label = document.getElementById('resetPasswordStrengthLabel');
        const hint = document.getElementById('resetPasswordStrengthHint');

        if (!input || !meter || !label || !hint) return;

        const result = getPasswordStrength(input.value);

        meter.dataset.strength = result.state;
        meter.style.setProperty('--strength-width', result.width);
        label.textContent = result.label;
        hint.textContent = result.hint;
    }

    function updateResetPasswordMatch() {
        const password = document.getElementById('resetPassword');
        const confirm = document.getElementById('resetPasswordConf');
        const match = document.getElementById('resetPasswordMatch');
        const text = document.getElementById('resetPasswordMatchText');

        if (!password || !confirm || !match || !text) return;

        const passwordValue = password.value.trim();
        const confirmValue = confirm.value.trim();

        confirm.classList.remove('is-password-match', 'is-password-mismatch');

        if (!confirmValue.length) {
            match.dataset.match = 'empty';
            text.textContent = 'Confirm your password.';
            return;
        }

        if (passwordValue === confirmValue) {
            match.dataset.match = 'matched';
            text.textContent = 'Passwords match.';
            confirm.classList.add('is-password-match');
            return;
        }

        match.dataset.match = 'mismatch';
        text.textContent = 'Passwords do not match.';
        confirm.classList.add('is-password-mismatch');
    }

    function updateResetPasswordFeedback() {
        updateResetPasswordStrength();
        updateResetPasswordMatch();
    }

    document.addEventListener('input', function (event) {
        if (!event.target) return;

        if (event.target.id === 'resetPassword' || event.target.id === 'resetPasswordConf') {
            updateResetPasswordFeedback();
        }
    });

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
    window.openResetModal = openResetModal;
    window.openViewModal = openViewModal;
    window.togglePassVis = togglePassVis;

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof applyTheme === 'function') applyTheme(localStorage.getItem('theme') || 'light');
        document.querySelectorAll('.theme-option').forEach(o =>
            o.addEventListener('click', e => {
                e.stopPropagation();
                if (typeof applyTheme === 'function') applyTheme(o.getAttribute('data-theme'));
            })
        );

        document.querySelectorAll('.flash-alert').forEach(el => {
            setTimeout(() => {
                el.style.transition = 'opacity .4s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 400);
            }, 4000);
        });
    });

    function clearSearch() {
        var input = document.getElementById('umSearch');

        if (!input) return;

        if (window.clearSearchInput) {
            window.clearSearchInput(input);
        } else {
            input.value = '';
            input.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            input.dispatchEvent(new Event('change', {
                bubbles: true
            }));
            input.focus();
        }
    }

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

        fetch('{{ route('admin.user_management') }}?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            signal: umController.signal
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                umRenderRows(data.users);
                umRenderPagebar(data.pagination);
                if (data.counts) {
                    umRenderCounts(data.counts);
                }
            })
            .catch(function (e) {
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

    function umRenderRows(users) {
        function jsAttr(value) {
            return JSON.stringify(value ?? '').replace(/"/g, '&quot;');
        }

        var tbody = document.getElementById('umTableBody');
        var gridBody = document.getElementById('umGridBody');

        if (!tbody || !gridBody) return;

        if (!users || users.length === 0) {
            var searchVal = umState.search || '';
            var hasSearch = searchVal.trim() !== '';
            var escapedSearch = escapeHtml(searchVal);
            var emptyTitle = hasSearch ?
                'No results for “' + escapedSearch + '”' :
                'No users found';
            var emptySub = hasSearch ?
                'Try a different name or email.' :
                'Try adjusting your filters.';
            var clearBtn = hasSearch ?
                '<button type="button" class="empty-state-btn" data-clear-search data-search-target="#umSearch"><i class="fa-solid fa-xmark"></i> Clear search</button>' :
                '';

            var emptyInner = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid ${hasSearch ? 'fa-magnifying-glass' : 'fa-users'}"></i>
                    </div>
                    <h3 class="empty-state-title">${emptyTitle}</h3>
                    <p class="empty-state-sub">${emptySub}</p>
                    ${clearBtn}
                </div>
            `;

            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="p-0">
                        ${emptyInner}
                    </td>
                </tr>
            `;

            gridBody.innerHTML = emptyInner;
            window.initSearchClearButtons?.();
            window.initGlobalVoiceInputs?.(document.getElementById('addModal') || document);
            return;
        }

        var startNumber = ((umState.page - 1) * umState.perPage) + 1;
        var tableHtml = '';
        var gridHtml = '';

        users.forEach(function (user, index) {
            var rowNumber = startNumber + index;
            var roleSlug = (user.role_slug || '').toLowerCase();
            var roleLabel = user.role_name || 'No Role';
            var registeredDay = user.created_at_day || '—';

            var statusClass = user.status === 'active' ? 'badge-active' : 'badge-inactive';
            var initial = (user.name || 'U').charAt(0).toUpperCase();
            var statusLabel = (user.status || '').charAt(0).toUpperCase() + (user.status || '').slice(1);
            var createdFull = (user.created_at_day || '—') + (user.created_at_time ? ' ' + user
                .created_at_time : '');

            tableHtml += `
                <tr class="user-table-row border-b border-gray-50 last:border-0">
                    <td class="py-3.5 px-3 sm:px-5 hidden sm:table-cell">
                        <span class="text-xs text-gray-400 font-medium">${rowNumber}</span>
                    </td>

                    <td class="py-3.5 px-2 sm:px-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div
                                class="w-9 h-9 rounded-full bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                                ${initial}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800 text-sm leading-tight">
                                    ${user.name}
                                </div>
                                <div class="text-[11px] text-gray-400 mt-0.5 hidden sm:block">
                                    ${user.email}
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="py-3.5 px-4">
                        <span class="badge-role role-${roleSlug || 'none'}">
    ${roleLabel}
</span>
                    </td>

                    <td class="py-3.5 px-4 text-center">
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full ${statusClass}">
                            ${statusLabel}
                        </span>
                    </td>

                    <td class="py-3.5 px-4 hidden lg:table-cell">
                        <span class="text-xs text-gray-600">${registeredDay}</span>
                    </td>

                    <td class="py-3.5 px-4">
                        <div class="um-action-group flex items-center justify-center gap-1">
                            <button type="button"
                                onclick="openEditModal(
                                    'users',
                                    ${user.id},
                                    ${jsAttr(user.name)},
                                    ${jsAttr(user.email)},
                                    ${jsAttr(user.role_id)},
                                    ${jsAttr(user.status)}
                                )"
                                class="action-btn btn-edit" title="Edit account">
                                <i class="fa-solid fa-pen text-[11px]"></i>
                            </button>

                            <button type="button"
                                onclick="openToggleConfirm(${user.id}, ${jsAttr(user.status)}, ${jsAttr(user.name)})"
                                class="action-btn ${user.status === 'active' ? 'btn-toggle-on' : 'btn-toggle-off'}"
                                title="${user.status === 'active' ? 'Deactivate' : 'Activate'}">
                                <i class="fa-solid ${user.status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'} text-[11px]"></i>
                            </button>

                            <button type="button"
                                onclick="openResetModal('users', ${user.id}, ${jsAttr(user.name)})"
                                class="action-btn btn-reset" title="Reset password">
                                <i class="fa-solid fa-key text-[11px]"></i>
                            </button>

                            <button type="button"
                                onclick="openViewModal(
                                    ${jsAttr(user.name)},
                                    ${jsAttr(user.email)},
                                    ${jsAttr(roleLabel)},
                                    ${jsAttr(statusLabel)},
                                    'Users',
                                    ${jsAttr(createdFull)}
                                )"
                                class="action-btn btn-view-details"
                                title="View details">
                                <i class="fa-solid fa-eye text-[11px]"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;

            gridHtml += `
                <div class="um-grid-card">
                    <div class="um-grid-top">
                        <div class="um-grid-number">#${rowNumber}</div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full ${statusClass}">
                            ${statusLabel}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                            ${initial}
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-800 text-sm leading-tight">${user.name}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">${user.email}</div>
                        </div>
                    </div>

                    <div class="um-grid-meta">
                        <div class="um-grid-field">
                            <div class="um-grid-label">Role</div>
                            <div class="um-grid-value">
                                <span class="badge-role role-${roleSlug || 'none'}">
    ${roleLabel}
</span>
                            </div>
                        </div>

                        <div class="um-grid-field">
                            <div class="um-grid-label">Registered</div>
                            <div class="um-grid-value">${registeredDay}</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-1 flex-wrap">
                        <button type="button"
                            onclick="openEditModal(
                                'users',
                                ${user.id},
                                ${jsAttr(user.name)},
                                ${jsAttr(user.email)},
                                ${jsAttr(user.role_id)},
                                ${jsAttr(user.status)}
                            )"
                            class="action-btn btn-edit" title="Edit account">
                            <i class="fa-solid fa-pen text-[11px]"></i>
                        </button>

                        <button type="button"
                            onclick="openToggleConfirm(${user.id}, ${jsAttr(user.status)}, ${jsAttr(user.name)})"
                            class="action-btn ${user.status === 'active' ? 'btn-toggle-on' : 'btn-toggle-off'}"
                            title="${user.status === 'active' ? 'Deactivate' : 'Activate'}">
                            <i class="fa-solid ${user.status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'} text-[11px]"></i>
                        </button>

                        <button type="button"
                            onclick="openResetModal('users', ${user.id}, ${jsAttr(user.name)})"
                            class="action-btn btn-reset" title="Reset password">
                            <i class="fa-solid fa-key text-[11px]"></i>
                        </button>

                        <button type="button"
                            onclick="openViewModal(
                                ${jsAttr(user.name)},
                                ${jsAttr(user.email)},
                                ${jsAttr(roleLabel)},
                                ${jsAttr(statusLabel)},
                                'Users',
                                ${jsAttr(createdFull)}
                            )"
                            class="action-btn btn-view-details"
                            title="View details">
                            <i class="fa-solid fa-eye text-[11px]"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        tbody.innerHTML = tableHtml;
        gridBody.innerHTML = gridHtml;
        applyUmView(getPreferredUmView(), false);
    }

    function umGoPage(page) {
        umState.page = page;
        umFetch();
    }

    function umRenderPagebar(p) {
        if (!p) return;

        document.querySelectorAll('.um-pagebar-info').forEach(function (el) {
            el.innerHTML = 'Showing <strong>' + p.from + '–' + p.to + '</strong> of <strong>' + p.total +
                '</strong> users';
        });

        var html = umBuildPagination(p);
        document.querySelectorAll('.um-pagination-wrap').forEach(function (el) {
            el.innerHTML = html;
        });

        var umPerPageSelect = document.getElementById('umPerPageSelect');
        if (umPerPageSelect && p.per_page) {
            umPerPageSelect.value = String(p.per_page);
            window.syncGlobalPageSizeSelect?.(umPerPageSelect, p.per_page);
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

    function umBuildPagination(p) {
        if (p.last_page <= 1) return '';

        var current = p.current_page;
        var last = p.last_page;
        var windowSize = 5;
        var half = Math.floor(windowSize / 2);
        var start = Math.max(1, current - half);
        var end = Math.min(last, start + windowSize - 1);

        if (end - start + 1 < windowSize) {
            start = Math.max(1, end - windowSize + 1);
        }

        var btn =
            'style="height:32px;min-width:32px;padding:0 10px;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;color:#374151;font-size:.75rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"';
        var btnActive =
            'style="height:32px;min-width:32px;padding:0 10px;border-radius:8px;border:1.5px solid #8B0000;background:linear-gradient(135deg,#8B0000,#6b0000);color:#fff;font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;"';
        var btnDis =
            'style="height:32px;min-width:32px;padding:0 10px;border-radius:8px;border:1.5px solid #e5e7eb;background:#f9fafb;color:#d1d5db;font-size:.75rem;font-weight:600;cursor:not-allowed;display:inline-flex;align-items:center;justify-content:center;"';

        var html = '<nav style="display:flex;align-items:center;gap:.35rem;flex-wrap:nowrap;">';

        if (current <= 1) {
            html += '<button disabled ' + btnDis +
                '><i class="fa-solid fa-chevron-left" style="font-size:.65rem;"></i></button>';
        } else {
            html += '<button onclick="umGoPage(' + (current - 1) + ')" ' + btn +
                '><i class="fa-solid fa-chevron-left" style="font-size:.65rem;"></i></button>';
        }

        for (var i = start; i <= end; i++) {
            if (i === current) {
                html += '<span ' + btnActive + '>' + i + '</span>';
            } else {
                html += '<button onclick="umGoPage(' + i + ')" ' + btn + '>' + i + '</button>';
            }
        }

        if (current >= last) {
            html += '<button disabled ' + btnDis +
                '><i class="fa-solid fa-chevron-right" style="font-size:.65rem;"></i></button>';
        } else {
            html += '<button onclick="umGoPage(' + (current + 1) + ')" ' + btn +
                '><i class="fa-solid fa-chevron-right" style="font-size:.65rem;"></i></button>';
        }

        html += '</nav>';
        return html;
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

    function setRoleFilter(el, role) {
        document.querySelectorAll('#umFilterForm [data-role]').forEach(function (b) {
            b.classList.remove('active');
        });
        el.classList.add('active');

        umState.role = (role === 'all' || role === '') ? '' : role;
        umState.page = 1;
        umFetch();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof applyTheme === 'function') applyTheme(localStorage.getItem('theme') || 'light');
        initUmViewToggle();
        document.querySelectorAll('.theme-option').forEach(function (o) {
            o.addEventListener('click', function (e) {
                e.stopPropagation();
                if (typeof applyTheme === 'function') applyTheme(o.getAttribute('data-theme'));
            });
        });

        umRenderPagebar({
            total: {{ $users-> total() }},
        from: {{ $users-> firstItem() ?? 0 }},
        to: {{ $users-> lastItem() ?? 0 }},
        current_page: {{ $users-> currentPage() }},
        last_page: {{ $users-> lastPage() }},
        per_page: {{ $users-> perPage() }},
            });

    var searchInput = document.getElementById('umSearch');

    window.initSearchClearButtons?.();
    window.initGlobalPageSizeSelects?.();

    var umPerPageSelect = document.getElementById('umPerPageSelect');
    if (umPerPageSelect) {
        umPerPageSelect.value = String(umState.perPage || 10);
        window.syncGlobalPageSizeSelect?.(umPerPageSelect, umState.perPage || 10);

        umPerPageSelect.addEventListener('change', function () {
            umState.perPage = Number(this.value) || 10;
            umState.page = 1;
            umFetch();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(umSearchTimer);
            var val = this.value;
            umSearchTimer = setTimeout(function () {
                umState.search = val;
                umState.page = 1;
                umFetch(true);
            }, 350);
        });
    }

    var statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function () {
            umState.status = this.value;
            umState.page = 1;
            umFetch();
        });
    }

    var toggleForm = document.getElementById('toggleConfirmForm');
    if (toggleForm) {
        toggleForm.addEventListener('submit', function (e) {
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
                .then(function (res) {
                    return res.json().then(function (data) {
                        return {
                            ok: res.ok,
                            data: data
                        };
                    });
                })
                .then(function (result) {
                    if (result.ok && result.data.success) {
                        closeAllModals();
                        showSuccessToast(result.data.message);
                        umFetch(true);
                    } else {
                        showErrorToast(result.data.message || 'Something went wrong.');
                    }
                })
                .catch(function () {
                    showErrorToast('Something went wrong. Please try again.');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
        });
    }

    var editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var form = this;
            var url = form.action;
            var submitBtn = form.querySelector('button[type="submit"]');
            var originalHtml = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

            var params = new URLSearchParams();
            params.append('_token', '{{ csrf_token() }}');
            params.append('_method', 'PUT');
            params.append('name', document.getElementById('editName').value);
            params.append('email', document.getElementById('editEmail').value);
            params.append('role_id', document.getElementById('editRole').value);
            params.append('status', form.querySelector('input[name="status"]:checked')?.value ??
                '');

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
                .then(function (res) {
                    return res.json().then(function (data) {
                        return {
                            ok: res.ok,
                            status: res.status,
                            data: data
                        };
                    });
                })
                .then(function (result) {
                    if (result.status === 422 && result.data.errors) {
                        var msgs = Object.values(result.data.errors).flat().join(' ');
                        showErrorToast(msgs);
                    } else if (result.ok && result.data.success) {
                        closeAllModals();
                        showSuccessToast(result.data.message || 'User updated successfully.');
                        umFetch(true);
                    } else {
                        showErrorToast(result.data.message || 'Something went wrong.');
                    }
                })
                .catch(function () {
                    showErrorToast('Something went wrong. Please try again.');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                });
        });
    }

    var resetForm = document.getElementById('resetForm');
    if (resetForm) {
        resetForm.addEventListener('submit', function (e) {
            e.preventDefault();

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
                .then(function (res) {
                    return res.json().then(function (data) {
                        return {
                            ok: res.ok,
                            status: res.status,
                            data: data
                        };
                    });
                })
                .then(function (result) {
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
                .catch(function () {
                    showErrorToast('Something went wrong. Please try again.');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                });
        });
    }

        });

    window.addEventListener('resize', function () {
        applyUmView(getPreferredUmView(), false);
    });
</script>
@endsection
