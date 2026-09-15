@php
    use App\Models\Role;

    $authUser = auth()->user();

    $role = $role ?? (optional(optional($authUser)->role)->slug ?? (session('role') ?? 'patient'));
    $resolveNotificationUrl = function (array $payload, string $activeRole): string {
        $fallbackUrl = data_get($payload, 'url') ?? (data_get($payload, 'action_url') ?? '#');

        $event = data_get($payload, 'event') ?? data_get($payload, 'type');

        return match ($event) {
            'appointment.booked', 'appointment.rescheduled' => match ($activeRole) {
                'admin', 'super_admin' => Route::has('admin.admin.appointments')
                    ? route('admin.admin.appointments')
                    : $fallbackUrl,

                'dentist' => Route::has('dentist.dentist.appointments')
                    ? route('dentist.dentist.appointments')
                    : $fallbackUrl,

                'patient' => Route::has('patient.appointment.index')
                    ? route('patient.appointment.index')
                    : $fallbackUrl,

                default => $fallbackUrl,
            },

            'appointment.cancelled' => match ($activeRole) {
                'patient' => Route::has('patient.record')
                    ? route('patient.record', [
                        'appointment' => data_get($payload, 'appointment_id'),
                    ])
                    : $fallbackUrl,

                'admin', 'super_admin' => Route::has('admin.admin.appointments')
                    ? route('admin.admin.appointments')
                    : $fallbackUrl,

                'dentist' => Route::has('dentist.dentist.appointments')
                    ? route('dentist.dentist.appointments')
                    : $fallbackUrl,

                default => $fallbackUrl,
            },

            'document.request.submitted', 'document_request_submitted' => match ($activeRole) {
                'admin', 'super_admin' => Route::has('admin.document-requests.index')
                    ? route('admin.document-requests.index')
                    : $fallbackUrl,

                'dentist' => Route::has('dentist.dentist.documentrequests')
                    ? route('dentist.dentist.documentrequests')
                    : $fallbackUrl,

                default => $fallbackUrl,
            },

            'document.request.approved',
            'document_request_approved',
            'document.request.rejected',
            'document_request_rejected'
                => match ($activeRole) {
                'patient' => Route::has('patient.record')
                    ? route('patient.record', [
                        'section' => 'document-requests',
                    ])
                    : $fallbackUrl,

                default => $fallbackUrl,
            },

            default => $fallbackUrl,
        };
    };

    $legacyNotifications = collect($notifications ?? []);
    $databaseNotifications = collect();

    if ($authUser) {
        $databaseNotifications = $authUser
            ->currentRoleNotifications()
            ->latest()
            ->take(15)
            ->get()
            ->map(function ($notification) use ($resolveNotificationUrl, $role) {
                $payload = $notification->data ?? [];
                $title =
                    data_get($payload, 'title') ??
                    (data_get($payload, 'subject') ?? class_basename($notification->type));
                $message =
                    data_get($payload, 'message') ?? (data_get($payload, 'body') ?? data_get($payload, 'description'));
                $event = (string) data_get($payload, 'event');
                $expirationDate = data_get($payload, 'expiration_date');

                if (str_starts_with($event, 'inventory.expiration.') && $expirationDate) {
                    $date = \Illuminate\Support\Carbon::parse($expirationDate);
                    $message = str_replace($date->format('M d, Y'), $date->format('F d, Y'), (string) $message);
                }
                $actionUrl = $resolveNotificationUrl($payload, $role);

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
            })
            ->reject(fn($notification) => data_get($notification, 'event') === 'inventory.low_stock')
            ->values();
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

    $showMobileMenu = $showMobileMenu ?? in_array($role, ['admin', 'super_admin', 'dentist'], true);
    $showSettings = $showSettings ?? in_array($role, ['admin', 'super_admin'], true);
    $activeHeaderRole = session('impersonated_role') ?: $role;
    $isPatientHeader = in_array($activeHeaderRole, ['patient', 'patient_role'], true);
    $clinicTitle = $clinicTitle ?? 'PUP TAGUIG DENTAL CLINIC';

    if ($role === 'patient') {
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
        $displayRole = session()->has('impersonated_role')
            ? Role::displayNameFor(session('impersonated_role'))
            : $authUser?->display_role_name ?? Role::displayNameFor($role);

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
                            data-notif-state="{{ $n['state'] ?? 'unread' }}"
                            @if (!empty($n['mark_read_url'])) data-notif-mark-read-url="{{ $n['mark_read_url'] }}" @endif
                            data-notif-item>
                            <div class="header-notif-item-icon">
                                <i class="fa-solid {{ $n['icon'] ?? 'fa-bell' }}"></i>
                            </div>

                            <div class="header-notif-item-content">
                                <div class="header-notif-item-top">
                                    @if (!empty($n['url']) && $n['url'] !== '#')
                                        <a href="{{ $n['url'] }}" data-notif-open-link
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
                                        <a href="{{ $n['url'] }}" class="header-notif-link-action"
                                            data-notif-open-link>Open</a>
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

        <div id="userDropdown">
            <button class="header-user-btn" id="userBtn" type="button" aria-haspopup="menu" aria-controls="userMenu"
                aria-expanded="false">
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

            <div id="userMenu" class="header-dropdown-menu header-user-menu" role="menu">
                <div class="dropdown-menu-list">

                    @if ($isPatientHeader)
                        <label id="darkModeToggleItem" class="dropdown-menu-item patient-mobile-theme-item">
                            <div class="dropdown-item-content">
                                <span class="dropdown-item-icon">
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
                                <span class="dropdown-item-icon">
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
                                <span class="dropdown-item-icon">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </span>

                                <span class="dropdown-item-text">
                                    Active Sessions
                                </span>
                            </div>

                            <i class="fa-solid fa-chevron-right dropdown-item-chevron" aria-hidden="true"></i>
                        </a>
                    @endif


                    @if ($isPatientHeader)
                        <div class="dropdown-menu-divider patient-mobile-logout-divider" role="separator"></div>

                        <form action="{{ route('logout') }}" method="POST"
                            class="js-logout-form patient-mobile-logout-form">
                            @csrf

                            <button type="submit" class="dropdown-menu-item patient-mobile-logout-item">
                                <div class="dropdown-item-content">
                                    <span class="dropdown-item-icon">
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
    </div>
</header>
