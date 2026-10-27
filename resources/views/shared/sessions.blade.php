@extends('layouts.app')

@section('title', 'Account Sessions')

@section('layout-role', $role)

@section('body-class', 'bg-[#F4F4F4]')

@section('styles')
@vite('resources/css/pages/shared/active-sessions.css')
@endsection

@section('content')

<main id="mainContent" class="
        session-page
        page-enter
        {{ $role === 'admin'
            ? 'app-page-shell'
            : ($role === 'dentist'
                ? 'app-page-shell'
                : 'app-page-shell') }}
    ">
    <div class="session-shell">

        @if ($role === 'admin')

        <section class="page-banner">
            <div class="page-banner-inner">

                <div>
                    <h1 class="page-title">
                        Account Sessions
                    </h1>

                    <p class="page-subtitle">
                        Review active devices and recent sign-in activity for your account.
                    </p>
                </div>

            </div>
        </section>

        @elseif ($role === 'dentist')

        <section class="dentist-hero">
            <div class="dentist-hero-content">

                <div class="dentist-hero-left">

                    <div class="dentist-hero-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="dentist-hero-eyebrow">
                            Account Security
                        </div>

                        <h1 class="dentist-hero-title">
                            Account Sessions
                        </h1>
                    </div>

                </div>

            </div>
        </section>

        @elseif ($role === 'patient')

        <section class="patient-hero">

            <h1 class="patient-hero-title">
                Account Sessions
            </h1>

            <p class="patient-hero-subtitle">
                Review active devices and recent
                sign-in activity for your account.
            </p>

            <div class="patient-hero-stats">

                <span class="patient-hero-stat">
                    <i class="fa-solid fa-shield-halved"></i>
                    Account Security
                </span>

                <span class="patient-hero-stat">
                    <i class="fa-solid fa-circle-check"></i>
                    Security Activity Visible
                </span>

            </div>

        </section>

        @endif

        <div class="session-stats">
            <div class="session-stat">
                <span class="session-stat-label global-info-label">Current Device</span>
                <span class="session-stat-value global-info-value">{{ $currentSessionCount }}</span>
                <span class="session-stat-note">The browser you are using right now</span>
            </div>

            <div class="session-stat">
                <span class="session-stat-label global-info-label">Other Active Sessions</span>
                <span class="session-stat-value global-info-value">{{ $otherSessionsCount }}</span>
                <span class="session-stat-note">Other browsers still signed in</span>
            </div>

            <div class="session-stat">
                <span class="session-stat-label global-info-label">Role Session Limit</span>
                <span class="session-stat-value global-info-value">{{ $sessionLimit }}</span>
                <span class="session-stat-note">Maximum active sessions for your role</span>
            </div>

            <div class="session-stat">
                <span class="session-stat-label global-info-label">Recent History</span>
                <span class="session-stat-value global-info-value">{{ $history->count() }}</span>
                <span class="session-stat-note">Recent sign-in and logout activity</span>
            </div>
        </div>

        <div class="session-grid">
            <section class="session-panel">
                <div class="session-panel-head">
                    <div>
                        <h2>Signed-in Devices</h2>
                        <p>These are your currently active sessions. Review your sessions and end any sessions you no
                            longer recognize or need.</p>
                    </div>

                    <div class="session-panel-actions">
                        <div class="session-action-stack">
                            @if ($otherSessionsCount > 0)
                            <form id="logoutOtherDevicesForm" method="POST"
                                action="{{ route('security.sessions.destroy-others') }}">
                                @csrf
                                @method('DELETE')

                                <button type="button" class="ui-btn ui-btn-secondary" data-session-confirm
                                    data-form-id="logoutOtherDevicesForm" data-title="Log Out Other Devices?"
                                    data-subtitle="Confirm other device logout"
                                    data-message="Are you sure you want to log out all other devices?"
                                    data-helper="Your current browser session will remain active."
                                    data-confirm-label="Log Out Other Devices">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    Log Out Other Devices
                                </button>
                            </form>
                            @endif

                            @if ($sessions->isNotEmpty())
                            <form id="logoutAllDevicesForm" method="POST"
                                action="{{ route('security.sessions.destroy-all') }}">
                                @csrf
                                @method('DELETE')

                                <button type="button" class="ui-btn ui-btn-danger" data-session-confirm
                                    data-form-id="logoutAllDevicesForm" data-title="Log Out All Devices?"
                                    data-subtitle="Confirm account-wide session logout"
                                    data-message="Are you sure you want to log out all signed-in devices?"
                                    data-helper="This will end all active sessions for your account, including the device you are currently using."
                                    data-confirm-label="Log Out All Devices">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                    Log Out All Devices
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($sessions->isEmpty())
                <div class="session-empty">
                    <strong>No Active Sessions Found</strong>
                    Your account does not have any currently active sessions to display.
                </div>
                @else
                <div class="session-list">
                    @foreach ($sessions as $session)
                    <article class="session-card {{ $session['is_current'] ? 'session-card-current' : '' }}">
                        <div class="session-card-top">
                            <div>
                                <div class="session-device-title">
                                    <h3>{{ $session['device_label'] }}</h3>

                                    @if ($session['is_current'])
                                    <span class="session-badge status-pill session-badge-current">This
                                        Device</span>
                                    @else
                                    <span class="session-badge status-pill">Other Active Session</span>
                                    @endif
                                </div>

                                <div class="session-agent">
                                    {{ $session['browser_label'] }}
                                    <span aria-hidden="true">·</span>
                                    {{ $session['os_label'] }}
                                </div>
                            </div>

                            <div class="session-card-actions">
                                @unless ($session['is_current'])
                                <form id="logoutDeviceForm{{ $loop->index }}" method="POST"
                                    action="{{ route('security.sessions.destroy', $session['reference']) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="button" class="ui-btn ui-btn-danger" data-session-confirm
                                        data-form-id="logoutDeviceForm{{ $loop->index }}"
                                        data-title="Log Out This Device?" data-subtitle="Confirm device logout"
                                        data-message="Are you sure you want to log out this device?"
                                        data-helper="The selected browser session will be ended. Your current session will remain signed in."
                                        data-confirm-label="Log Out This Device">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                        Log Out This Device
                                    </button>
                                </form>
                                @else
                                <form id="logoutCurrentSessionForm" method="POST"
                                    action="{{ route('security.sessions.destroy-current') }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="button" class="ui-btn ui-btn-secondary" data-session-confirm
                                        data-form-id="logoutCurrentSessionForm" data-title="Log Out This Session?"
                                        data-subtitle="Confirm current session logout"
                                        data-message="Are you sure you want to log out this session?"
                                        data-helper="You will be signed out from this browser and will need to sign in again to continue."
                                        data-confirm-label="Log Out This Session">
                                        Log Out This Session
                                    </button>
                                </form>
                                @endunless
                            </div>
                        </div>

                        <div class="session-meta">
                            <div class="session-meta-item">
                                <span class="session-meta-label global-info-label">Browser</span>
                                <span class="session-meta-value global-info-value">{{ $session['browser_label']
                                    }}</span>
                            </div>

                            <div class="session-meta-item">
                                <span class="session-meta-label global-info-label">IP Address</span>
                                <span class="session-meta-value global-info-value">{{ $session['ip_address'] }}</span>
                            </div>

                            <div class="session-meta-item">
                                <span class="session-meta-label global-info-label">Last Activity</span>
                                <span class="session-meta-value global-info-value">{{ $session['last_activity_label']
                                    }}</span>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>
                @endif
            </section>

            <section class="session-panel">
                <div class="session-panel-head">
                    <div>
                        <h2>Sign-in and Session Activity</h2>
                        <p>This section shows your recent sign-in and session-related activity, including browsers used
                            in the past.</p>
                    </div>
                </div>

                @if ($history->isEmpty())
                <div class="session-empty">
                    <strong>No Recent History Yet</strong>
                    Once your account records sign-ins or session events, your recent browser history will appear
                    here.
                </div>
                @else
                <div class="session-history-wrap">
                    @foreach ($history as $entry)
                    <article class="session-history-item">

                        <span class="session-history-marker session-history-marker-{{ $entry['action_tone'] }}">
                        </span>

                        <div class="session-history-content">

                            <div class="session-history-top">
                                <div class="session-history-title">
                                    <span
                                        class="session-history-badge status-pill session-history-badge-{{ $entry['action_tone'] }}">
                                        {{ $entry['action_label'] }}
                                    </span>

                                    <strong>
                                        {{ $entry['device_label'] }}
                                    </strong>
                                </div>

                                <span class="session-history-time">
                                    {{ $entry['occurred_at_label'] }}
                                </span>
                            </div>

                            <p class="session-history-desc">
                                {{ $entry['description'] }}
                            </p>

                            <div class="session-history-details">
                                <span>
                                    <i class="fa-solid fa-globe"></i>
                                    {{ $entry['browser_label'] }}
                                </span>

                                <span>
                                    <i class="fa-solid fa-network-wired"></i>
                                    {{ $entry['ip_address'] }}
                                </span>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>
                @endif
            </section>
        </div>
    </div>
</main>

<div id="sessionLogoutConfirmModal" class="ui-modal logout-confirm-modal" aria-hidden="true">
    <div class="ui-modal-card modal-box-inner logout-confirm-card" role="dialog" aria-modal="true"
        aria-labelledby="sessionLogoutConfirmTitle">
        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon logout-confirm-icon">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </div>

                <div class="modal-copy">
                    <h2 id="sessionLogoutConfirmTitle" class="modal-title">
                        Confirm Logout
                    </h2>

                    <p id="sessionLogoutConfirmSubtitle" class="modal-subtitle">
                        Confirm session logout
                    </p>
                </div>
            </div>

            <button type="button" class="modal-x" id="closeSessionLogoutModal" aria-label="Close confirmation modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-bd">
            <div class="logout-confirm-message">
                <div class="logout-confirm-message-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div>
                    <p id="sessionLogoutConfirmMessage">
                        Are you sure you want to continue?
                    </p>

                    <span id="sessionLogoutConfirmHelper">
                        Your active session will be ended.
                    </span>
                </div>
            </div>
        </div>

        <div class="modal-ft logout-confirm-actions">
            <button type="button" class="btn-close-modal" id="cancelSessionLogout">
                Cancel
            </button>

            <button type="button" class="modal-btn-confirm danger" id="confirmSessionLogout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log Out</span>
            </button>
        </div>
    </div>
</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', () => {
        @if (session('success'))
            if (typeof window.showToast === 'function') {
                window.showToast(
                    'Success',
                    @json(session('success')),
                    'success'
                );
            }
        @endif

        @if (session('error'))
            if (typeof window.showToast === 'function') {
                window.showToast(
                    'Error',
                    @json(session('error')),
                    'error'
                );
            }
        @endif

        const modal = document.getElementById('sessionLogoutConfirmModal');

        if (!modal) return;

        const title = document.getElementById('sessionLogoutConfirmTitle');
        const subtitle = document.getElementById('sessionLogoutConfirmSubtitle');
        const message = document.getElementById('sessionLogoutConfirmMessage');
        const helper = document.getElementById('sessionLogoutConfirmHelper');

        const confirmButton = document.getElementById('confirmSessionLogout');
        const confirmButtonLabel = confirmButton?.querySelector('span');
        const cancelButton = document.getElementById('cancelSessionLogout');
        const closeButton = document.getElementById('closeSessionLogoutModal');

        let pendingForm = null;

        function openSessionLogoutModal(trigger) {
            const formId = trigger.dataset.formId;
            pendingForm = document.getElementById(formId);

            if (!pendingForm) return;

            title.textContent = trigger.dataset.title || 'Confirm Logout';
            subtitle.textContent = trigger.dataset.subtitle || 'Confirm session logout';
            message.textContent = trigger.dataset.message || 'Are you sure you want to continue?';
            helper.textContent = trigger.dataset.helper || 'Your active session will be ended.';

            if (confirmButtonLabel) {
                confirmButtonLabel.textContent = trigger.dataset.confirmLabel || 'Log Out';
            }

            confirmButton.disabled = false;
            modal.classList.remove('closing');
            modal.classList.add('open');

            modal.setAttribute('aria-hidden', 'false');
            document.documentElement
                .classList
                .add('modal-lock');

            document.body
                .classList
                .add('modal-lock');

            closeButton?.focus();
        }

        function closeSessionLogoutModal() {
            if (!modal.classList.contains('open')) {
                return;
            }

            modal.classList.add('closing');

            window.setTimeout(() => {
                modal.classList.remove(
                    'open',
                    'closing'
                );

                modal.setAttribute('aria-hidden', 'true');

                document.documentElement
                    .classList
                    .remove('modal-lock');

                document.body
                    .classList
                    .remove('modal-lock');

                pendingForm = null;

                confirmButton.disabled = false;
            }, 160);
        }

        document
            .querySelectorAll(
                '[data-session-confirm]'
            )
            .forEach(button => {
                button.addEventListener(
                    'click',
                    () => {
                        openSessionLogoutModal(
                            button
                        );
                    }
                );
            });

        cancelButton?.addEventListener('click', closeSessionLogoutModal);
        closeButton?.addEventListener('click', closeSessionLogoutModal);

        modal.addEventListener(
            'click',
            event => {
                if (event.target === modal) {
                    closeSessionLogoutModal();
                }
            }
        );

        document.addEventListener(
            'keydown',
            event => {
                if (event.key === 'Escape' && modal.classList.contains('open')) {
                    closeSessionLogoutModal();
                }
            }
        );

        confirmButton?.addEventListener(
            'click',
            () => {
                if (!pendingForm) return;

                confirmButton.disabled = true;

                pendingForm.submit();
            }
        );
    });
</script>
@endsection