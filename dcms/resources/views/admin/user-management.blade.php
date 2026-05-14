@extends('layouts.admin')

@section('title', 'User Management | PUP Taguig Dental Clinic')

@section('content')

@php
$totalUsers = $totalUsers ?? ($allUsersCount ?? ($users->total() ?? 0));
$activeCount = $activeCount ?? 0;
$inactiveCount = $inactiveCount ?? 0;
@endphp

<main id="mainContent" class="px-3 sm:px-6 pt-[82px] pb-8 min-h-screen">
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

        <div class="um-stats-grid mb-6">
            <div class="um-stat-card um-stat-card--users">
                <div class="um-stat-top">
                    <div class="um-stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="um-stat-trend">Overview</span>
                </div>
                <div class="um-stat-body">
                    <p class="um-stat-label">Total Users</p>
                    <p class="um-stat-value" id="countTotalUsers">{{ $totalUsers }}</p>
                    <p class="um-stat-caption">All registered system accounts</p>
                </div>
            </div>

            <div class="um-stat-card um-stat-card--active">
                <div class="um-stat-top">
                    <div class="um-stat-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <span class="um-stat-trend">Healthy</span>
                </div>
                <div class="um-stat-body">
                    <p class="um-stat-label">Active</p>
                    <p class="um-stat-value" id="countActiveUsers">{{ $activeCount }}</p>
                    <p class="um-stat-caption">Accounts currently enabled</p>
                </div>
            </div>

            <div class="um-stat-card um-stat-card--inactive">
                <div class="um-stat-top">
                    <div class="um-stat-icon">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <span class="um-stat-trend">Attention</span>
                </div>
                <div class="um-stat-body">
                    <p class="um-stat-label">Inactive</p>
                    <p class="um-stat-value" id="countInactiveUsers">{{ $inactiveCount }}</p>
                    <p class="um-stat-caption">Accounts currently disabled</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-visible mb-6">
            <div class="px-4 sm:px-5 py-4 border-b bg-gray-50 flex flex-col gap-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-users-gear text-[#8B0000]"></i>
                    <h2 class="font-bold text-gray-800 text-sm">All System Users</h2>
                    <span id="countBadgeUsers"
                        class="text-[10px] font-bold bg-[#8B0000] text-white px-2 py-0.5 rounded-full">{{ $totalUsers
                        }}</span>
                </div>

                <form method="GET" action="{{ route('admin.user_management') }}" id="umFilterForm"
                    class="flex items-center gap-2.5 flex-wrap">
                    {{-- Search --}}
                    <div class="um-search-mobile um-search-row">
                        <div class="search-wrap">
                            <i class="fa fa-search" style="color:#8B0000;font-size:13px;flex-shrink:0;"></i>
                            <input id="umSearch" name="search" class="no-voice" placeholder="Search name or email…"
                                value="{{ $search ?? '' }}" autocomplete="off" oninput="toggleSearchClear(this)"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();}" />
                        </div>

                        <button type="button" id="searchClearBtn"
                            class="search-clear-btn {{ $search ?? '' ? '' : 'hidden' }}" onclick="clearSearch()"
                            title="Clear">Clear</button>

                        <div class="patient-voice-toggle">
                            <button type="button" id="umMicToggleBtn" class="voice-search-mic external"
                                aria-label="Toggle voice input" aria-pressed="false">
                                <i class="fa-solid fa-microphone"></i>
                            </button>
                            <span id="umVoiceStatus" class="patient-voice-status hidden" aria-live="polite"></span>
                        </div>
                    </div>

                    <div class="um-view-toggle" id="umViewToggle">
                        <button type="button" class="um-view-toggle-btn active" id="umListViewBtn" title="List view"
                            aria-label="List view">
                            <i class="fa-solid fa-table-list"></i>
                        </button>
                        <button type="button" class="um-view-toggle-btn" id="umGridViewBtn" title="Grid view"
                            aria-label="Grid view">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                    </div>
            </div>
            </form>
        </div>

        <div class="um-view" id="umListView">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr class="text-[10px] uppercase tracking-wide text-[#8B0000] font-bold">
                            <th class="py-3 px-3 sm:px-5 text-left w-12 hidden sm:table-cell">#</th>
                            <th class="py-3 px-4 text-left">User</th>
                            <th class="py-3 px-4 text-left">Role</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-left hidden lg:table-cell">Registered</th>
                            <th class="py-3 px-5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="umTableBody">
                        @forelse($users as $user)
                        <tr class="user-table-row border-b border-gray-50 last:border-0"
                            data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}"
                            data-role="{{ strtolower(optional($user->role)->name ?? '') }}">
                            <td class="py-3.5 px-3 sm:px-5 hidden sm:table-cell">
                                <span class="text-xs text-gray-400 font-medium">{{ $users->firstItem() + $loop->index
                                    }}</span>
                            </td>

                            <td class="py-3.5 px-3 sm:px-4">
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <div
                                        class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
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
                                @php $roleSlug = optional($user->role)->slug; @endphp
                                <span class="badge-role" style="background:
                {{ $roleSlug === 'patient' ? '#dbeafe' : ($roleSlug === 'dentist' ? '#d1fae5' : '#fee2e2') }};
                color:
                {{ $roleSlug === 'patient' ? '#1d4ed8' : ($roleSlug === 'dentist' ? '#065f46' : '#8B0000') }};">
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
                                <span class="text-xs text-gray-400">{{ $user->created_at->format('M d, Y') }}</span>
                            </td>

                            <td class="py-3.5 px-2 sm:px-5">
                                <div class="flex items-center justify-center gap-1">
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
                                                  )" class="action-btn" style="background:#f3f4f6;color:#374151;"
                                        title="View details">
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
                                    <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
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
                                    <span class="badge-role" style="background:{{ $roleBg }};color:{{ $roleColor }};">
                                        {{ $roleName }}
                                    </span>
                                </div>
                            </div>

                            <div class="um-grid-field">
                                <div class="um-grid-label">Registered</div>
                                <div class="um-grid-value">{{ $user->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-1 flex-wrap">
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

                            <button type="button" onclick="openResetModal('users', {{ $user->id }}, @js($user->name))"
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
                                            )" class="action-btn" style="background:#f3f4f6;color:#374151;"
                                title="View details">
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
        <p class="text-xs text-gray-500 um-pagebar-info">
            Showing
            <strong>{{ $users->firstItem() ?? 0 }}</strong>–<strong>{{ $users->lastItem() ?? 0 }}</strong>
            of <strong>{{ $users->total() }}</strong> users
        </p>
        <div class="um-pagination-wrap flex items-center gap-1.5"></div>
    </div>
    </div>
    </div>
</main>

<!-- Global Toast Container -->
<div id="toastContainer"
    style="position:fixed;top:16px;right:16px;z-index:99999;display:flex;flex-direction:column;gap:8px;align-items:flex-end;pointer-events:none;width:340px;">
</div>

<div class="modal-overlay" id="addModal" aria-hidden="true" onclick="closeModalOutside(event,'addModal')">
    <div class="modal-box um-user-modal">
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

            <button type="button" onclick="closeModal('addModal')"
                class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:border-red-200 hover:text-[#8B0000] transition-all flex-shrink-0">
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
                                <div class="flex items-center gap-2">
                                    <input type="text" id="addNameInput" name="name" value="{{ old('name') }}"
                                        class="no-voice field-input flex-1 min-w-0 border border-gray-200 px-3.5 py-3 text-sm bg-white"
                                        placeholder="e.g. Juan dela Cruz" required>
                                    <div class="patient-voice-toggle">
                                        <button type="button" id="addNameMicBtn" class="voice-search-mic external"
                                            aria-label="Voice input for full name">
                                            <i class="fa-solid fa-microphone"></i>
                                        </button>
                                        <span id="addNameVoiceStatus" class="patient-voice-status hidden"
                                            aria-live="polite"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="um-field-full">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-envelope text-gray-400 text-xs flex-shrink-0 pl-1"></i>
                                    <input type="email" id="addEmailInput" name="email" value="{{ old('email') }}"
                                        class="no-voice field-input flex-1 min-w-0 border border-gray-200 px-3.5 py-3 text-sm bg-white"
                                        placeholder="user@pup.edu.ph" required>
                                    <div class="patient-voice-toggle">
                                        <button type="button" id="addEmailMicBtn" class="voice-search-mic external"
                                            aria-label="Voice input for email">
                                            <i class="fa-solid fa-microphone"></i>
                                        </button>
                                        <span id="addEmailVoiceStatus" class="patient-voice-status hidden"
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
                                    <option value="">— No Role —</option>
                                    @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id')==$role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
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

            <div class="um-user-modal-footer">
                <button type="button" onclick="closeModal('addModal')"
                    class="btn-cancel px-5 py-2.5 border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all inline-flex items-center">
                    Cancel
                </button>

                <button type="submit"
                    class="btn-save px-6 py-2.5 bg-[#8B0000] hover:bg-[#760000] text-white text-sm font-bold shadow transition-all inline-flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save User</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editModal" aria-hidden="true" onclick="closeModalOutside(event,'editModal')">
    <div class="modal-box">
        <div
            class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow">
                    <i class="fa-solid fa-user-pen text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 text-base">Edit User</h3>
                    <p class="text-[10px] text-gray-500" id="editModalSubtitle">Updating user details</p>
                </div>
            </div>
            <button type="button" data-close-modal="editModal"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#8B0000] transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" id="editForm" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Full Name
                    <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="editName" placeholder="Full name"
                    class="field-input w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm" required>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Email
                    Address <span class="text-red-500">*</span></label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="email" name="email" id="editEmail" placeholder="user@pup.edu.ph"
                        class="field-input w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm" required>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Role</label>
                <select name="role_id" id="editRole"
                    class="field-input w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white">
                    <option value="">— No Role —</option>
                    @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
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
                <button type="button" onclick="closeModal('editModal')"
                    class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
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
<div class="modal-overlay" id="resetModal" aria-hidden="true" onclick="closeModalOutside(event,'resetModal')">
    <div class="modal-box modal-sm">
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
            <button type="button" data-close-modal="resetModal"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#8B0000] transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" id="resetForm" class="p-6 space-y-4">
            @csrf
            @method('PUT')

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
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('resetModal')"
                    class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
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

<div class="modal-overlay" id="viewModal" aria-hidden="true" onclick="closeModalOutside(event,'viewModal')">
    <div class="modal-box modal-sm">
        <div
            class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center shadow">
                    <i class="fa-solid fa-eye text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 text-base">Account Details</h3>
                    <p class="text-[10px] text-gray-500">View selected account information</p>
                </div>
            </div>
            <button type="button" data-close-modal="viewModal"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#8B0000] transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6 space-y-4 text-sm">
            <div>
                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Name</div>
                <div id="viewName" class="text-gray-800 font-semibold mt-1"></div>
            </div>

            <div>
                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Email</div>
                <div id="viewEmail" class="text-gray-800 mt-1"></div>
            </div>

            <div>
                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Role</div>
                <div id="viewRole" class="text-gray-800 mt-1"></div>
            </div>

            <div>
                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status</div>
                <div id="viewStatus" class="text-gray-800 mt-1"></div>
            </div>

            <div>
                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Source</div>
                <div id="viewSource" class="text-gray-800 mt-1"></div>
            </div>

            <div>
                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Created At</div>
                <div id="viewCreatedAt" class="text-gray-800 mt-1"></div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" onclick="closeModal('viewModal')"
                    class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="toggleConfirmModal" aria-hidden="true"
    onclick="closeModalOutside(event,'toggleConfirmModal')">
    <div class="modal-box modal-sm">
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
            <button type="button" data-close-modal="toggleConfirmModal"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#8B0000] transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6">
            <div id="toggleModalBody" class="rounded-xl p-4 mb-5 flex items-start gap-3 text-sm"></div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('toggleConfirmModal')"
                    class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                    Cancel
                </button>
                <form id="toggleConfirmForm" method="POST" style="display:inline;">
                    @csrf
                    @method('PATCH')
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
        search: '{{ $search ?? '' }}',
        role: '{{ $roleFilter ?? '' }}',
        status: '{{ $statusFilter ?? '' }}',
        perPage: {{ $perPage ?? 10 }},
    page: { { request('page', 1) } },
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

        if (listBtn) listBtn.classList.toggle('active', finalView === 'list');
        if (gridBtn) gridBtn.classList.toggle('active', finalView === 'grid');

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

    window.closeAllModals = function () {
        document.querySelectorAll('.modal-overlay').forEach(function (modal) {
            var activeEl = document.activeElement;

            if (activeEl && modal.contains(activeEl)) {
                activeEl.blur();
            }

            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        });

        document.body.classList.remove('modal-open');
    };

    window.openModal = function (id, trigger = null) {
        var modal = document.getElementById(id);
        if (!modal) return;

        window.lastModalTrigger = trigger || document.activeElement;

        document.querySelectorAll('.modal-overlay.open').forEach(function (m) {
            if (m.id !== id) {
                m.classList.remove('open');
                m.setAttribute('aria-hidden', 'true');
            }
        });

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        var firstField = modal.querySelector('input, select, textarea, button');
        if (firstField) {
            setTimeout(function () {
                firstField.focus();
            }, 30);
        }
    };

    window.closeModal = function (id) {
        var modal = document.getElementById(id);
        if (!modal) return;

        var activeEl = document.activeElement;
        if (activeEl && modal.contains(activeEl)) {
            activeEl.blur();
        }

        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');

        if (!document.querySelector('.modal-overlay.open')) {
            document.body.classList.remove('modal-open');
        }

        if (window.lastModalTrigger && typeof window.lastModalTrigger.focus === 'function') {
            setTimeout(function () {
                window.lastModalTrigger.focus();
            }, 30);
        }
    };

    window.closeModalOutside = function (e, id) {
        if (e.target.id === id) {
            window.closeModal(id);
        }
    };

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
            btn.innerHTML = '<i class="fa-solid fa-user-slash"></i> Yes, Deactivate';
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
            btn.innerHTML = '<i class="fa-solid fa-user-check"></i> Yes, Activate';
        }

        btn.dataset.originalHtml = btn.innerHTML;

        openModal('toggleConfirmModal');
    }

    function openEditModal(source, id, name, email, roleId, status) {
        const form = document.getElementById('editForm');

        if (source === 'patients') {
            form.action = `/admin/user-management/patient/${id}`;
            document.getElementById('editRole').disabled = true;
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
            document.getElementById('editRole').disabled = false;
            document.getElementById('editStatusActive').disabled = false;
            document.getElementById('editStatusInactive').disabled = false;
        }

        form.dataset.source = source;

        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editModalSubtitle').textContent = 'Editing: ' + name;

        const roleSelect = document.getElementById('editRole');
        roleSelect.value = roleId || '';

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
        openModal('resetModal');
    }

    function openViewModal(name, email, role, status, source, createdAt) {
        document.getElementById('viewName').textContent = name;
        document.getElementById('viewEmail').textContent = email;
        document.getElementById('viewRole').textContent = role;
        document.getElementById('viewStatus').textContent = status;
        document.getElementById('viewSource').textContent = source;
        document.getElementById('viewCreatedAt').textContent = createdAt;

        openModal('viewModal');
    }

    function togglePassVis(inputId, iconId) {
        const inp = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        if (inp.type === 'password') {
            inp.type = 'text';
            ico.className = 'fa-regular fa-eye-slash text-xs';
        } else {
            inp.type = 'password';
            ico.className = 'fa-regular fa-eye text-xs';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(localStorage.getItem('theme') || 'light');
        document.querySelectorAll('.theme-option').forEach(o =>
            o.addEventListener('click', e => {
                e.stopPropagation();
                applyTheme(o.getAttribute('data-theme'));
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

    function toggleSearchClear(input) {
        document.getElementById('searchClearBtn')?.classList.toggle('hidden', input.value.trim().length === 0);
    }

    function clearSearch() {
        var input = document.getElementById('umSearch');
        if (!input) return;

        if (window.umListening && window.umRecognition) {
            try {
                window.umRecognition.stop();
            } catch (e) { }
            window.umListening = false;
        }

        input.value = '';
        document.getElementById('searchClearBtn')?.classList.add('hidden');
        document.getElementById('umVoiceStatus')?.classList.add('hidden');
        var micBtn = document.getElementById('umMicToggleBtn');
        if (micBtn) {
            micBtn.classList.remove('mic-active');
            micBtn.setAttribute('aria-pressed', 'false');
            micBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
        }
        umState.search = '';
        umState.page = 1;
        umFetch();
        input.focus();
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

    function umRenderRows(users) {
        function jsAttr(value) {
            return JSON.stringify(value ?? '').replace(/"/g, '&quot;');
        }

        var tbody = document.getElementById('umTableBody');
        var gridBody = document.getElementById('umGridBody');

        if (!tbody || !gridBody) return;

        if (!users || users.length === 0) {
            var searchVal = umState.search || '';
            var emptyTitle = searchVal ?
                'No results for &ldquo;' + searchVal + '&rdquo;' :
                'No users found';
            var emptySub = searchVal ?
                'Try a different name or email.' :
                'Try adjusting your filters.';
            var clearBtn = searchVal ?
                '<button onclick="clearSearch()" style="margin-top:.75rem;display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border-radius:99px;border:1.5px dashed #d1d5db;background:none;font-size:.78rem;color:#9ca3af;cursor:pointer;transition:all .2s;" onmouseover="this.style.borderColor=\'#8B0000\';this.style.color=\'#8B0000\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#9ca3af\';"><i class=\"fa-solid fa-xmark\" style=\"font-size:.7rem;\"></i> Clear search</button>' :
                '';

            var emptyHtml = `
                    <div style="padding:3.5rem 1rem;text-align:center;grid-column:1 / -1;">
                        <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;background:#f3f4f6;border-radius:18px;margin-bottom:1rem;">
                            <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
                        </div>
                        <p style="font-size:.9rem;font-weight:700;color:#374151;margin:0 0 .3rem;">${emptyTitle}</p>
                        <p style="font-size:.78rem;color:#9ca3af;margin:0;">${emptySub}</p>
                        ${clearBtn}
                    </div>
                `;

            tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="padding:3.5rem 1rem;text-align:center;">
                            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;background:#f3f4f6;border-radius:18px;margin-bottom:1rem;">
                                <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem;color:#d1d5db;"></i>
                            </div>
                            <p style="font-size:.9rem;font-weight:700;color:#374151;margin:0 0 .3rem;">${emptyTitle}</p>
                            <p style="font-size:.78rem;color:#9ca3af;margin:0;">${emptySub}</p>
                            ${clearBtn}
                        </td>
                    </tr>
                `;

            gridBody.innerHTML = emptyHtml;
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

            var roleBg = '#fee2e2';
            var roleColor = '#8B0000';

            if (roleSlug === 'patient') {
                roleBg = '#dbeafe';
                roleColor = '#1d4ed8';
            } else if (roleSlug === 'dentist') {
                roleBg = '#d1fae5';
                roleColor = '#065f46';
            }

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

                    <td class="py-3.5 px-3 sm:px-4">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#8B0000] to-[#b00000] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
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
                        <span class="badge-role" style="background:${roleBg};color:${roleColor};">
                            ${roleLabel}
                        </span>
                    </td>

                    <td class="py-3.5 px-4 text-center">
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full ${statusClass}">
                            ${statusLabel}
                        </span>
                    </td>

                    <td class="py-3.5 px-4 hidden lg:table-cell">
                        <span class="text-xs text-gray-400">${registeredDay}</span>
                    </td>

                    <td class="py-3.5 px-2 sm:px-5">
                        <div class="flex items-center justify-center gap-1">
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
                                class="action-btn" style="background:#f3f4f6;color:#374151;"
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
                                <span class="badge-role" style="background:${roleBg};color:${roleColor};">
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
                            class="action-btn" style="background:#f3f4f6;color:#374151;"
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

    function showSuccessToast(message) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const normalizedMessage = String(message || '').trim();

        const existing = Array.from(container.querySelectorAll('[data-toast-type="success"]'))
            .find(toast => toast.dataset.toastMessage === normalizedMessage);

        if (existing) return;

        const toast = document.createElement('div');
        toast.dataset.toastType = 'success';
        toast.dataset.toastMessage = normalizedMessage;

        toast.style.cssText =
            'pointer-events:auto;position:relative;overflow:hidden;display:flex;align-items:flex-start;gap:10px;background:#fff;border:1px solid #d1fae5;box-shadow:0 8px 24px rgba(0,0,0,.12);border-radius:14px;padding:10px 12px;width:320px;animation:slideIn .35s ease forwards;';

        toast.innerHTML = `
        <div class="absolute inset-y-0 left-0 w-1 bg-emerald-500"></div>

        <div class="flex-shrink-0 ml-1">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
            </div>
        </div>

        <div class="flex-1 min-w-0 pr-1">
            <h3 class="text-[13px] sm:text-sm font-extrabold text-gray-800 leading-tight">Success</h3>
            <p class="text-[12px] sm:text-[13px] text-gray-500 leading-4 mt-0.5 break-words">${normalizedMessage}</p>
        </div>

        <button
            type="button"
            class="flex-shrink-0 w-7 h-7 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
            onclick="this.parentElement.remove()"
        >
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    function showErrorToast(message) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const normalizedMessage = String(message || '').trim();

        const existing = Array.from(container.querySelectorAll('[data-toast-type="error"]'))
            .find(toast => toast.dataset.toastMessage === normalizedMessage);

        if (existing) return;

        const toast = document.createElement('div');
        toast.dataset.toastType = 'error';
        toast.dataset.toastMessage = normalizedMessage;

        toast.style.cssText =
            'pointer-events:auto;position:relative;overflow:hidden;display:flex;align-items:flex-start;gap:10px;background:#fff;border:1px solid #fee2e2;box-shadow:0 8px 24px rgba(0,0,0,.12);border-radius:14px;padding:10px 12px;width:320px;animation:slideIn .35s ease forwards;';

        toast.innerHTML = `
        <div class="absolute inset-y-0 left-0 w-1 bg-red-500"></div>

        <div class="flex-shrink-0 ml-1">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                <i class="fa-solid fa-circle-exclamation text-red-500 text-base"></i>
            </div>
        </div>

        <div class="flex-1 min-w-0 pr-1">
            <h3 class="text-[13px] sm:text-sm font-extrabold text-gray-800 leading-tight">Error</h3>
            <p class="text-[12px] sm:text-[13px] text-gray-500 leading-4 mt-0.5 break-words">${normalizedMessage}</p>
        </div>

        <button
            type="button"
            class="flex-shrink-0 w-7 h-7 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
            onclick="this.parentElement.remove()"
        >
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
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
        applyTheme(localStorage.getItem('theme') || 'light');
        initUmViewToggle();
        document.querySelectorAll('.theme-option').forEach(function (o) {
            o.addEventListener('click', function (e) {
                e.stopPropagation();
                applyTheme(o.getAttribute('data-theme'));
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
    if (searchInput) {
        toggleSearchClear(searchInput);
        searchInput.addEventListener('input', function () {
            toggleSearchClear(this);
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
                body: '_method=PATCH&_token={{ csrf_token() }}'
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
            params.append('status', form.querySelector('input[name="status"]:checked')?.value ?? '');

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

    // ─── Voice input for Add New User form fields ───────────────────────────
    (function initAddFormVoice() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) return;

        const inputs = [
            { inputId: 'addNameInput', micId: 'addNameMicBtn', statusId: 'addNameVoiceStatus' },
            { inputId: 'addEmailInput', micId: 'addEmailMicBtn', statusId: 'addEmailVoiceStatus' }
        ];

        inputs.forEach(function (config) {
            const input = document.getElementById(config.inputId);
            const micBtn = document.getElementById(config.micId);
            const status = document.getElementById(config.statusId);

            if (!input || !micBtn || !status) return;

            let listening = false;
            let recognition = null;
            let manualStop = false;

            // ── helpers ──────────────────────────────────────────────────────
            const setStatus = function (text, state) {
                status.textContent = text;
                status.className = 'patient-voice-status';
                if (state) status.classList.add('is-' + state);
                if (text) {
                    status.classList.remove('hidden');
                } else {
                    status.classList.add('hidden');
                }
            };

            const hideStatus = function (delay) {
                window.setTimeout(function () {
                    status.classList.add('hidden');
                }, delay || 0);
            };

            const setMicState = function (isActive) {
                micBtn.classList.toggle('mic-active', isActive);
                micBtn.innerHTML = isActive
                    ? '<i class="fa-solid fa-stop"></i>'
                    : '<i class="fa-solid fa-microphone"></i>';
            };

            const stopListeningNow = function () {
                manualStop = true;
                listening = false;
                setMicState(false);
                setStatus('Voice captured.', 'success');
                hideStatus(1200);
                if (recognition) {
                    try { recognition.abort(); } catch (e) {
                        try { recognition.stop(); } catch (err) { }
                    }
                }
            };

            // ── recognition factory ──────────────────────────────────────────
            const createRecognition = function () {
                const r = new SpeechRecognition();
                r.lang = 'en-US';
                r.continuous = false;
                r.interimResults = true;
                r.maxAlternatives = 1;

                let sawSpeech = false;
                let timeoutId = null;
                const LISTEN_TIMEOUT = 6000;

                const clearTimeout_ = function () {
                    if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                };

                r.onstart = function () {
                    timeoutId = window.setTimeout(function () {
                        if (listening && !sawSpeech) {
                            try { r.stop(); } catch (e) { }
                        }
                    }, LISTEN_TIMEOUT);
                };

                r.onspeechend = function () {
                    clearTimeout_();
                    try { r.stop(); } catch (e) { }
                };

                r.onresult = function (event) {
                    let transcript = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const result = event.results[i];
                        const chunk = (result && result[0] && result[0].transcript
                            ? result[0].transcript : '').trim();
                        if (!chunk) continue;
                        sawSpeech = true;
                        if (result.isFinal) {
                            transcript = (transcript + ' ' + chunk).trim();
                        } else if (!transcript) {
                            transcript = chunk;
                        }
                    }
                    transcript = transcript.trim();
                    if (transcript) {
                        clearTimeout_();
                        input.value = transcript;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        setStatus('Listening...', 'listening');
                    }
                };

                r.onerror = function () {
                    clearTimeout_();
                    listening = false;
                    if (manualStop) { manualStop = false; return; }
                    setMicState(false);
                    setStatus("Didn't catch that. Try again.", 'error');
                    hideStatus(2500);
                };

                r.onend = function () {
                    clearTimeout_();
                    if (manualStop) {
                        manualStop = false;
                        listening = false;
                        setMicState(false);
                        return;
                    }
                    const hadSpeech = sawSpeech || !!input.value.trim();
                    listening = false;
                    setMicState(false);
                    if (hadSpeech) {
                        setStatus('Voice captured.', 'success');
                        hideStatus(2200);
                    } else {
                        setStatus("Didn't catch that. Try again.", 'error');
                        hideStatus(2500);
                    }
                };

                return r;
            };

            // ── click handler ────────────────────────────────────────────────
            micBtn.addEventListener('click', function () {
                if (listening && recognition) { stopListeningNow(); return; }

                recognition = createRecognition();
                try {
                    recognition.start();
                } catch (error) {
                    setStatus('Unable to start voice input.', 'error');
                    hideStatus(2500);
                    setMicState(false);
                    listening = false;
                    return;
                }
                listening = true;
                setMicState(true);
                setStatus('Listening...', 'listening');
            });
        });
    })();

    // ─── Voice input for search bar ─────────────────────────────────────────
    (function () {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const input = document.getElementById('umSearch');
        const micBtn = document.getElementById('umMicToggleBtn');
        const status = document.getElementById('umVoiceStatus');

        if (!input || !micBtn || !status || !SpeechRecognition) {
            if (micBtn) {
                micBtn.disabled = true;
                micBtn.setAttribute('aria-disabled', 'true');
            }
            return;
        }

        window.umRecognition = null;
        window.umListening = false;
        window.umManualStop = false;

        const setStatus = function (text, state) {
            status.textContent = text;
            status.className = 'patient-voice-status';
            if (state) status.classList.add('is-' + state);
            status.classList.remove('hidden');
        };

        const hideStatus = function (delay) {
            window.setTimeout(function () {
                status.classList.add('hidden');
            }, delay || 0);
        };

        const setMicState = function (isActive) {
            micBtn.classList.toggle('mic-active', isActive);
            micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            micBtn.innerHTML = isActive
                ? '<i class="fa-solid fa-stop"></i>'
                : '<i class="fa-solid fa-microphone"></i>';
        };

        const stopListeningNow = function () {
            window.umManualStop = true;
            window.umListening = false;
            setMicState(false);
            setStatus('Voice input stopped.', 'success');
            hideStatus(1200);
            if (window.umRecognition) {
                try { window.umRecognition.abort(); } catch (e) {
                    try { window.umRecognition.stop(); } catch (err) { }
                }
            }
        };

        const createRecognition = function () {
            const recognition = new SpeechRecognition();
            recognition.lang = 'en-US';
            recognition.continuous = false;
            recognition.interimResults = true;
            recognition.maxAlternatives = 1;

            let sawSpeech = false;
            let listenTimeoutId = null;
            const LISTEN_TIMEOUT = 6000;

            const clearListenTimeout = function () {
                if (listenTimeoutId) { clearTimeout(listenTimeoutId); listenTimeoutId = null; }
            };

            recognition.onstart = function () {
                listenTimeoutId = window.setTimeout(function () {
                    if (window.umListening && !sawSpeech) {
                        try { recognition.stop(); } catch (e) { }
                    }
                }, LISTEN_TIMEOUT);
            };

            recognition.onspeechend = function () {
                clearListenTimeout();
                try { recognition.stop(); } catch (e) { }
            };

            recognition.onresult = function (event) {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const result = event.results[i];
                    const chunk = (result && result[0] && result[0].transcript
                        ? result[0].transcript : '').trim();
                    if (!chunk) continue;
                    sawSpeech = true;
                    if (result.isFinal) {
                        transcript = (transcript + ' ' + chunk).trim();
                    } else if (!transcript) {
                        transcript = chunk;
                    }
                }
                transcript = transcript.trim();
                if (transcript) {
                    clearListenTimeout();
                    input.value = transcript;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    setStatus('Listening...', 'listening');
                }
            };

            recognition.onerror = function () {
                clearListenTimeout();
                window.umListening = false;
                if (window.umManualStop) { window.umManualStop = false; return; }
                setMicState(false);
                setStatus("Didn't catch that. Try again.", 'error');
                hideStatus(2500);
            };

            recognition.onend = function () {
                clearListenTimeout();
                if (window.umManualStop) {
                    window.umManualStop = false;
                    window.umListening = false;
                    setMicState(false);
                    return;
                }
                const hadSpeech = sawSpeech || !!input.value.trim();
                window.umListening = false;
                setMicState(false);
                if (hadSpeech) {
                    setStatus('Voice captured.', 'success');
                    hideStatus(2200);
                } else {
                    setStatus("Didn't catch that. Try again.", 'error');
                    hideStatus(2500);
                }
            };

            return recognition;
        };

        micBtn.addEventListener('click', function () {
            if (window.umListening && window.umRecognition) { stopListeningNow(); return; }

            window.umRecognition = createRecognition();
            try {
                window.umRecognition.start();
            } catch (error) {
                setStatus('Unable to start voice input.', 'error');
                hideStatus(2500);
                setMicState(false);
                window.umListening = false;
                return;
            }
            window.umListening = true;
            setMicState(true);
            setStatus('Listening...', 'listening');
        });
    })();
        });

    window.addEventListener('resize', function () {
        applyUmView(getPreferredUmView(), false);
    });
</script>
@endsection