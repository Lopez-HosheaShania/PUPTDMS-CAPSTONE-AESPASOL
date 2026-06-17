@php
    $authUser = auth()->user();

    $role = $role ?? (optional(optional($authUser)->role)->slug ?? (session('role') ?? 'patient'));

    $legacyNotifications = collect($notifications ?? []);
    $databaseNotifications = collect();

    if ($authUser) {
        $databaseNotifications = $authUser
            ->notifications()
            ->latest()
            ->take(15)
            ->get()
            ->map(function ($notification) {
                $payload = $notification->data ?? [];
                $title =
                    data_get($payload, 'title') ??
                    (data_get($payload, 'subject') ?? class_basename($notification->type));
                $message =
                    data_get($payload, 'message') ?? (data_get($payload, 'body') ?? data_get($payload, 'description'));
                $actionUrl = data_get($payload, 'url') ?? (data_get($payload, 'action_url') ?? '#');

                return [
                    'id' => $notification->id,
                    'title' => $title ?: 'Notification',
                    'message' => $message,
                    'url' => $actionUrl ?: '#',
                    'state' => $notification->read_at ? 'read' : 'unread',
                    'created_at' => $notification->created_at,
                    'created_at_label' => optional($notification->created_at)->diffForHumans(),
                    'icon' => data_get($payload, 'icon') ?? 'fa-bell',
                    'mark_read_url' => Route::has('notifications.mark-read')
                        ? route('notifications.mark-read', ['notificationId' => $notification->id])
                        : null,
                ];
            });
    }

    if ($databaseNotifications->isNotEmpty()) {
        $notifications = $databaseNotifications->values();
    } else {
        $notifications = $legacyNotifications
            ->map(function ($notification) {
                $createdAt = $notification['created_at'] ?? null;

                if (is_string($createdAt) && $createdAt !== '') {
                    $createdAt = \Illuminate\Support\Carbon::parse($createdAt);
                }

                return [
                    'id' => $notification['id'] ?? uniqid('notif_', true),
                    'title' => $notification['title'] ?? 'Notification',
                    'message' => $notification['message'] ?? null,
                    'url' => $notification['url'] ?? '#',
                    'state' => $notification['state'] ?? 'unread',
                    'created_at' => $createdAt,
                    'created_at_label' =>
                        $notification['created_at_label'] ?? ($createdAt ? $createdAt->diffForHumans() : null),
                    'icon' => $notification['icon'] ?? 'fa-bell',
                    'mark_read_url' => $notification['mark_read_url'] ?? null,
                ];
            })
            ->values();
    }

    $unreadNotifications = $notifications->where('state', 'unread')->values();
    $readNotifications = $notifications->where('state', 'read')->values();
    $notifCount = $unreadNotifications->count();
    $notifTotalCount = $notifications->count();

    $showMobileMenu = $showMobileMenu ?? in_array($role, ['admin', 'super_admin', 'dentist']);
    $showSettings = $showSettings ?? in_array($role, ['admin', 'super_admin']);

    $clinicTitle = $clinicTitle ?? 'PUP TAGUIG DENTAL CLINIC';

    if ($role === 'patient') {
        $displayName = ucwords(strtolower(optional($patient)->name ?? ($authUser->name ?? 'Patient User')));
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

        if ($role === 'super_admin') {
            $displayRole = 'Administrator';
        } elseif ($role === 'admin') {
            $displayRole = 'Administrator';
        } elseif ($role === 'dentist') {
            $displayRole = 'Dentist';
        } else {
            $displayRole = ucwords(str_replace('_', ' ', $role));
        }

        if (!empty($authUser->profile_image)) {
            $avatarUrl = asset('storage/' . $authUser->profile_image);
        } else {
            $avatarUrl =
                'https://ui-avatars.com/api/?name=' .
                urlencode($displayName) .
                '&background=8B0000&color=ffffff&bold=true';
        }
    }

    $logoutRoute = route('logout');
    $settingsRoute = $settingsRoute ?? (Route::has('admin.system_settings') ? route('admin.system_settings') : '#');
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
        <div id="notifDropdown">
            <button class="hdr-icon-btn" id="notifBtn" type="button" aria-label="Notifications">
                <i class="fa-regular fa-bell"></i>
                @if ($notifCount > 0)
                    <span class="notif-badge" data-notif-badge>
                        {{ $notifCount > 9 ? '9+' : $notifCount }}
                    </span>
                @endif
            </button>

            <div id="notifMenu" class="header-dropdown-menu header-notif-menu" aria-label="Notifications panel">
                <div class="header-notif-head">
                    <div class="header-notif-head-copy">
                        <div class="header-notif-head-title">
                            <i class="fa-solid fa-bell"></i>
                            <span>Notifications</span>
                        </div>
                        <div class="header-notif-head-meta">
                            <span class="header-notif-pill" data-notif-unread-pill>
                                {{ $notifCount > 9 ? '9+' : $notifCount }} unread
                            </span>
                            <span class="header-notif-pill header-notif-pill-muted"
                                data-notif-total-pill>{{ $notifTotalCount }} total</span>
                        </div>
                    </div>

                    @if ($notifCount > 0 && Route::has('notifications.mark-all-read'))
                        <form method="POST" action="{{ route('notifications.mark-all-read') }}"
                            class="header-notif-actions" data-notif-mark-all-form>
                            @csrf
                            <button type="submit" class="header-notif-mark-all">Mark all as read</button>
                        </form>
                    @endif
                </div>

                <div class="header-notif-tabs" role="tablist" aria-label="Notification filters">
                    <button type="button" class="header-notif-tab is-active" data-notif-filter="all">
                        <span>All</span>
                        <span data-notif-tab-count="all">{{ $notifTotalCount }}</span>
                    </button>
                    <button type="button" class="header-notif-tab" data-notif-filter="unread">
                        <span>Unread</span>
                        <span data-notif-tab-count="unread">{{ $notifCount }}</span>
                    </button>
                    <button type="button" class="header-notif-tab" data-notif-filter="read">
                        <span>Read</span>
                        <span data-notif-tab-count="read">{{ $readNotifications->count() }}</span>
                    </button>
                </div>

                <div class="header-notif-body">
                    @forelse($notifications as $n)
                        <div class="header-notif-item {{ ($n['state'] ?? 'unread') === 'unread' ? 'is-unread' : 'is-read' }}"
                            data-notif-state="{{ $n['state'] ?? 'unread' }}" data-notif-item>
                            <div class="header-notif-item-icon">
                                <i class="fa-solid {{ $n['icon'] ?? 'fa-bell' }}"></i>
                            </div>

                            <div class="header-notif-item-content">
                                <div class="header-notif-item-top">
                                    @if (!empty($n['url']) && $n['url'] !== '#')
                                        <a href="{{ $n['url'] }}"
                                            class="header-notif-item-title">{{ $n['title'] ?? 'Notification' }}</a>
                                    @else
                                        <span
                                            class="header-notif-item-title">{{ $n['title'] ?? 'Notification' }}</span>
                                    @endif

                                    @if (!empty($n['created_at_label']))
                                        <span class="header-notif-item-time">{{ $n['created_at_label'] }}</span>
                                    @endif
                                </div>

                                @if (!empty($n['message']))
                                    <div class="header-notif-item-message">{{ $n['message'] }}</div>
                                @endif

                                <div class="header-notif-item-actions">
                                    @if (!empty($n['url']) && $n['url'] !== '#')
                                        <a href="{{ $n['url'] }}" class="header-notif-link-action">Open</a>
                                    @endif

                                    @if (($n['state'] ?? 'unread') === 'unread' && !empty($n['mark_read_url']))
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

                            @if (($n['state'] ?? 'unread') === 'unread')
                                <span class="header-notif-unread-dot" aria-hidden="true"></span>
                            @endif
                        </div>
                    @empty
                        <div class="header-notif-empty">
                            <i class="fa-solid fa-bell-slash"></i>
                            <span>You're all caught up.</span>
                        </div>
                    @endforelse

                    <div class="header-notif-filter-empty" hidden>
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        <span>No notifications match this filter.</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($showSettings)
            <a href="{{ $settingsRoute }}" class="hdr-icon-btn" aria-label="System Settings">
                <i class="fa-solid fa-gear"></i>
            </a>
        @endif

        <div id="userDropdown">
            <button class="header-user-btn" id="userBtn" type="button">
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
                    style="font-size:.65rem; opacity:.75; margin-left:4px;"></i>
            </button>

            <div id="userMenu" class="header-dropdown-menu header-user-menu">

                <div class="dropdown-profile-card">
                    <img src="{{ $avatarUrl }}" class="dropdown-avatar" alt="Profile large avatar">
                    <div class="dropdown-user-details">
                        <div class="dropdown-name">{{ $displayName }}</div>
                        <div class="dropdown-role">{{ $displayRole }}</div>
                    </div>
                </div>

                <div class="dropdown-divider"></div>

                <div class="dropdown-menu-list">
                    <label class="dropdown-menu-item" id="darkModeToggleItem">
                        <div class="dropdown-item-content">
                            <i class="fa-regular fa-sun text-gray-400 text-base" id="themeIcon"></i>
                            <span class="dropdown-item-text">Dark Mode</span>
                        </div>
                        <div class="modern-switch">
                            <input type="checkbox" id="themeSwitchCheckbox" class="theme-switch-input"
                                aria-label="Toggle Dark Mode">
                            <span class="switch-slider"></span>
                        </div>
                    </label>

                    <form method="POST" action="{{ $logoutRoute }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="dropdown-menu-item dropdown-logout-item">
                            <div class="dropdown-item-content">
                                <i class="fa-solid fa-right-from-bracket text-red-500 text-base"></i>
                                <span class="dropdown-item-text text-red-600">Log out</span>
                            </div>
                            <i class="fa-solid fa-chevron-right text-gray-300 text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
