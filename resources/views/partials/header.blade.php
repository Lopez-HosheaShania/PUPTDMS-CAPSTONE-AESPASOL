@php
    use App\Models\Role;
    use App\Http\Controllers\NotificationController;

    $authUser = auth()->user();

    $actualRole = optional(optional($authUser)->role)->slug ?? (session('role') ?? 'patient');

    $isRolePreview =
        (bool) session('role_preview_active') && in_array(session('preview_role'), ['patient', 'dentist'], true);

    $role = $role ?? $actualRole;

    $notificationRole = $actualRole;
    $notifications = collect();

    $notifCount = 0;
    $notifTotalCount = 0;
    $notifReadCount = 0;
    $notifNextCursor = null;
    $notifHasMore = false;

    if ($authUser) {
        $notificationPage = NotificationController::notificationQuery($authUser)
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(5, ['*'], 'notification_cursor');

        $notifications = $notificationPage
            ->getCollection()
            ->map(
                fn($notification) => NotificationController::formatNotification(
                    $notification,
                    $notificationRole,
                    $authUser,
                ),
            )
            ->values();

        $counts = NotificationController::notificationCounts($authUser);

        $notifCount = $counts['unread_count'];
        $notifReadCount = $counts['read_count'];
        $notifTotalCount = $counts['total_count'];

        $notifNextCursor = $notificationPage->nextCursor()?->encode();
        $notifHasMore = $notificationPage->hasMorePages();
    }

    $showMobileMenu = $showMobileMenu ?? in_array($role, ['admin', 'super_admin', 'dentist'], true);
    $showSettings = $showSettings ?? in_array($role, ['admin', 'super_admin'], true);
    $activeHeaderRole = $role;
    $isPatientHeader = in_array($activeHeaderRole, ['patient', 'patient_role'], true);
    $clinicTitle = $clinicTitle ?? 'PUP TAGUIG DENTAL CLINIC';

    if ($isRolePreview) {
        $displayName = $authUser?->name ?? 'Administrator';

        $displayRole = $authUser?->display_role_name ?? Role::displayNameFor($actualRole);

        if (!empty($authUser?->profile_image)) {
            $avatarUrl = asset('storage/' . $authUser->profile_image);
        } else {
            $avatarUrl =
                'https://ui-avatars.com/api/?name=' .
                urlencode($displayName) .
                '&background=8B0000&color=ffffff&bold=true';
        }
    } elseif ($role === 'patient') {
        $displayName = ucwords(strtolower(optional($patient)->name ?? ($authUser->name ?? 'Patient User')));

        $displayName = preg_replace_callback(
            '/\b(ii|iii|iv|v|vi|vii|viii|ix|x)\.?$/i',
            fn($matches) => strtoupper($matches[0]),
            $displayName,
        );

        $displayRole = 'Patient';
        $patientImage = optional($patient)->profile_image ?? null;
        $userImage = $authUser->profile_image ?? null;

        if (!empty($patientImage)) {
            $avatarUrl = asset('storage/' . $patientImage);
        } elseif (!empty($userImage)) {
            $avatarUrl = asset('storage/' . $userImage);
        } else {
            $avatarUrl =
                'https://ui-avatars.com/api/?name=' .
                urlencode($displayName) .
                '&background=8B0000&color=ffffff&bold=true';
        }
    } else {
        $displayName = $authUser->name ?? 'User';
        $displayRole = $authUser?->display_role_name ?? Role::displayNameFor($role);

        if (!empty($authUser->profile_image)) {
            $avatarUrl = asset('storage/' . $authUser->profile_image);
        } else {
            $avatarUrl =
                'https://ui-avatars.com/api/?name=' .
                urlencode($displayName) .
                '&background=8B0000&color=ffffff&bold=true';
        }
    }
@endphp

<header class="header">
    <div class="header-left">
        @if ($showMobileMenu)
            <button id="mobileMenuBtn" class="hdr-icon-btn" type="button" data-drawer-toggle aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        @endif

        <img src="{{ asset('images/PUP.png') }}" class="header-logo" alt="PUP Logo">
        <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" class="header-logo" alt="Clinic Logo">
        <div class="header-divider"></div>
        <span class="header-title">{{ $clinicTitle }}</span>
    </div>

    <div class="header-right">
        <div id="notifDropdown"
            @if (Route::has('notifications.poll')) data-notif-poll-url="{{ route('notifications.poll') }}" @endif>
            <button class="hdr-icon-btn" id="notifBtn" type="button" aria-label="Notifications"
                aria-controls="notifMenu" aria-expanded="false">

                <i class="fa-regular fa-bell"></i>

                <span class="notif-badge" data-notif-badge @if ($notifCount === 0) hidden @endif>

                    {{ $notifCount > 9 ? '9+' : $notifCount }}

                </span>

            </button>

            <div id="notifMenu" class="header-dropdown-menu header-notif-menu" aria-label="Notifications panel"
                data-notif-next-cursor="{{ $notifNextCursor }}"
                data-notif-has-more="{{ $notifHasMore ? 'true' : 'false' }}"
                data-notif-unread-count="{{ $notifCount }}" data-notif-read-count="{{ $notifReadCount }}"
                data-notif-total-count="{{ $notifTotalCount }}">

                <div class="header-notif-head">

                    <div class="header-notif-head-copy">

                        <div class="header-notif-head-title">
                            Notifications
                        </div>

                        <div class="header-notif-head-meta">

                            <span class="header-notif-pill" data-notif-unread-pill>
                                {{ $notifCount }} unread
                            </span>

                            <span aria-hidden="true">·</span>

                            <span class="header-notif-pill header-notif-pill-muted" data-notif-total-pill>
                                {{ $notifTotalCount }} total
                            </span>

                        </div>

                    </div>

                    @if (Route::has('notifications.mark-all-read'))
                        <form method="POST" action="{{ route('notifications.mark-all-read') }}"
                            class="header-notif-actions" data-notif-mark-all-form
                            @if ($notifCount === 0) hidden @endif>

                            @csrf

                            <button type="submit" class="header-notif-mark-all">
                                Mark all as read
                            </button>

                        </form>
                    @endif

                </div>

                <div class="header-notif-tabs" role="group" aria-label="Notification filters">

                    <button type="button" class="header-notif-tab is-active" data-notif-filter="all"
                        aria-pressed="true">
                        All
                    </button>

                    <button type="button" class="header-notif-tab" data-notif-filter="unread" aria-pressed="false">
                        Unread
                    </button>

                    <button type="button" class="header-notif-tab" data-notif-filter="read" aria-pressed="false">
                        Read
                    </button>

                </div>

                <div class="header-notif-body" id="notificationList" aria-busy="false">

                    @forelse ($notifications as $n)
                        @php
                            $isUnread = $n['state'] === 'unread';

                            $hasUrl = !empty($n['url']) && $n['url'] !== '#';

                            $summary = $n['summary'] ?: $n['message'];

                            $hasFullMessage = !empty($n['message']) && $summary !== $n['message'];
                        @endphp

                        <div class="header-notif-item {{ $isUnread ? 'is-unread' : 'is-read' }}" data-notif-item
                            data-notif-state="{{ $n['state'] }}" data-notif-dedupe-key="{{ $n['id'] }}"
                            data-notif-created-at="{{ $n['created_at'] }}"
                            data-notif-mark-read-url="{{ $n['mark_read_url'] }}">

                            @if ($isUnread)
                                <span class="header-notif-unread-dot" aria-hidden="true"></span>
                            @endif

                            <div class="header-notif-item-content">

                                <div class="header-notif-item-top">

                                    @if ($hasUrl)
                                        <a href="{{ $n['url'] }}" class="header-notif-item-title"
                                            data-notif-open-link>

                                            {{ $n['title'] }}

                                        </a>
                                    @else
                                        <span class="header-notif-item-title">
                                            {{ $n['title'] }}
                                        </span>
                                    @endif

                                    @if (!empty($n['created_at_label']))
                                        <span class="header-notif-item-time">
                                            {{ $n['created_at_label'] }}
                                        </span>
                                    @endif

                                </div>

                                @if ($summary !== '')
                                    <div class="header-notif-item-message">
                                        {{ $summary }}
                                    </div>
                                @endif

                                @if ($hasFullMessage)
                                    <details class="mt-2">
                                        <summary class="header-notif-link-action header-notif-link-action-secondary">
                                            View details
                                        </summary>

                                        <div class="header-notif-item-message mt-2" data-notif-full-message>

                                            {{ $n['message'] }}

                                        </div>
                                    </details>
                                @endif

                                <div class="header-notif-item-actions">

                                    @if ($hasUrl)
                                        <a href="{{ $n['url'] }}" class="header-notif-link-action"
                                            data-notif-open-link>
                                            Open
                                        </a>
                                    @endif

                                    @if ($isUnread && !empty($n['mark_read_url']))
                                        <form method="POST" action="{{ $n['mark_read_url'] }}"
                                            class="header-notif-action-form" data-notif-mark-read-form>

                                            @csrf

                                            <button type="submit"
                                                class="header-notif-link-action header-notif-link-action-secondary">
                                                Mark read
                                            </button>

                                        </form>
                                    @endif

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="header-notif-empty">
                            <span>You're all caught up.</span>
                        </div>
                    @endforelse

                    <div class="header-notif-filter-empty" hidden>
                        <span>No notifications match this filter.</span>
                    </div>

                </div>

                <div class="global-show-more-controls" data-notif-previous-controls
                    @if (!$notifHasMore) hidden @endif>

                    <x-button type="button" variant="secondary" class="w-full" data-notif-load-more
                        aria-controls="notificationList">

                        See previous notifications

                    </x-button>

                </div>

                <p class="ui-muted-text text-center px-3 py-2" data-notif-status role="status" aria-live="polite"
                    hidden>
                </p>

            </div>
        </div>

        <div id="userDropdown">
            <button class="header-user-btn" id="userBtn" type="button" aria-haspopup="menu"
                aria-controls="userMenu" aria-expanded="false">
                <div class="avatar-wrapper">
                    <img src="{{ $avatarUrl }}" class="header-avatar" alt="Profile avatar">
                    <div class="mobile-chevron-badge">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>

                <div class="header-user-info">
                    <div class="header-name">{{ $displayName }}</div>
                    <div class="header-role">{{ $displayRole }}</div>
                </div>

                <i class="fa-solid fa-chevron-down desktop-chevron"
                    data-ui-presentation="presentation-1e7752fa6c28"></i>
            </button>

            <div id="userMenu" class="header-dropdown-menu header-user-menu" role="menu">
                <div class="dropdown-menu-list">

                    @if ($isPatientHeader)
                        <label id="darkModeToggleItem" class="dropdown-menu-item patient-mobile-theme-item">
                            <div class="dropdown-item-content">
                                <span class="sidebar-group-icon">
                                    <i id="themeIcon" class="fa-solid fa-moon"></i>
                                </span>

                                <span class="dropdown-item-text">
                                    Dark Mode
                                </span>
                            </div>

                            <span class="modern-switch">
                                <input id="themeSwitchCheckbox" type="checkbox" class="theme-switch-input"
                                    aria-label="Toggle dark mode">

                                <span class="switch-slider"></span>
                            </span>
                        </label>
                    @endif

                    @if ($showSettings && Route::has('admin.system_settings'))
                        <a href="{{ route('admin.system_settings') }}" class="dropdown-menu-item" role="menuitem">
                            <div class="dropdown-item-content">
                                <span class="sidebar-group-icon">
                                    <i class="fa-solid fa-gear"></i>
                                </span>

                                <span class="dropdown-item-text">
                                    System Settings
                                </span>
                            </div>

                            <i class="fa-solid fa-chevron-right dropdown-item-chevron" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if ($isPatientHeader && Route::has('security.sessions.index'))
                        <div class="dropdown-menu-divider patient-mobile-theme-divider" role="separator"></div>
                    @endif

                    @if ($showSettings && Route::has('admin.system_settings') && Route::has('security.sessions.index'))
                        <div class="dropdown-menu-divider" role="separator"></div>
                    @endif

                    @if (Route::has('security.sessions.index'))
                        <a href="{{ route('security.sessions.index') }}" class="dropdown-menu-item" role="menuitem">
                            <div class="dropdown-item-content">
                                <span class="sidebar-group-icon">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </span>

                                <span class="dropdown-item-text">
                                    Active Sessions
                                </span>
                            </div>

                            <i class="fa-solid fa-chevron-right dropdown-item-chevron" aria-hidden="true"></i>
                        </a>

                        <div class="dropdown-menu-divider header-desktop-logout-divider" role="separator"></div>

                        <form action="{{ route('logout') }}" method="POST"
                            class="js-logout-form header-logout-form header-desktop-logout-form">
                            @csrf

                            <button type="submit" class="dropdown-menu-item header-logout-item">
                                <div class="dropdown-item-content">
                                    <span class="sidebar-group-icon">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                    </span>

                                    <span class="dropdown-item-text">
                                        Log Out
                                    </span>
                                </div>
                            </button>
                        </form>
                    @endif

                </div>

            </div>
        </div>
    </div>
</header>
