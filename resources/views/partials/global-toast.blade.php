<div id="toastContainer" role="region" aria-live="polite"></div>

@php
    $flashToasts = [];
    $activeSessionRole = (string) (
        session('impersonated_role')
            ?: ($layoutRole ?? session('role') ?? optional(auth()->user()?->role)->slug)
    );
    $idleTimeoutSeconds = auth()->user()?->idleTimeoutSeconds($activeSessionRole)
        ?? max(0, (int) config('session.idle_timeout_seconds', 600));
    $idleTimeoutMinutes = max(1, (int) ceil($idleTimeoutSeconds / 60));
    $showSessionTimeout = $idleTimeoutSeconds > 0;

    if (session('success')) {
        $flashToasts[] = [
            'type' => 'success',
            'title' => 'Success',
            'message' => session('success'),
        ];
    }

    if (session('error')) {
        $flashToasts[] = [
            'type' => 'error',
            'title' => 'Error',
            'message' => session('error'),
        ];
    }

    if ($errors->any()) {
        $flashToasts[] = [
            'type' => 'error',
            'title' => 'Validation Error',
            'message' => $errors->first(),
        ];
    }

    if (session('login_as')) {
        $flashToasts[] = [
            'type' => 'success',
            'title' => 'Login Successful',
            'message' => 'Logged in successfully as <strong>' . session('login_as') . '</strong>',
        ];
    }

    if (request()->boolean('logged_out') && request('reason') !== 'idle') {
        $flashToasts[] = [
            'type' => 'success',
            'title' => 'Signed Out',
            'message' => 'You have been signed out successfully.',
        ];
    }
@endphp

@if (\Illuminate\Support\Facades\Auth::check() && $showSessionTimeout)
    <div id="sessionTimeoutModal" class="ui-modal session-timeout-modal" data-session-timeout-modal data-modal-static
        data-session-expired="{{ request()->attributes->get('session_idle_expired', false) ? 'true' : 'false' }}"
        data-session-timeout-seconds="{{ $idleTimeoutSeconds }}"
        data-session-activity-url="{{ route('session.activity') }}"
        data-session-expire-url="{{ route('session.expire') }}" data-session-redirect-url="{{ url('/') }}"
        data-session-activity-key="session:last-activity:{{ auth()->id() }}"
        aria-hidden="true">
        <div class="ui-modal-card session-timeout-card" role="alertdialog" aria-modal="true"
            aria-labelledby="sessionTimeoutTitle" aria-describedby="sessionTimeoutDescription">
            <div class="session-timeout-hero">
                <div class="session-timeout-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Security notice</span>
                </div>

                <div class="session-timeout-icon">
                    <div class="session-timeout-icon-inner">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
            </div>

            <div class="session-timeout-content">
                <h2 id="sessionTimeoutTitle" class="session-timeout-title">
                    Your session has expired
                </h2>

                <p id="sessionTimeoutDescription" class="session-timeout-description">
                    You have been signed out after
                    {{ $idleTimeoutMinutes }}
                    {{ $idleTimeoutMinutes === 1 ? 'minute' : 'minutes' }}
                    of inactivity.
                </p>

                <div class="session-timeout-message">
                    <span class="session-timeout-message-icon">
                        <i class="fa-solid fa-lock"></i>
                    </span>

                    <p>
                        For your security, access to the system
                        has been paused. Sign in again to continue.
                    </p>
                </div>
            </div>

            <div class="session-timeout-actions">
                <button type="button" class="modal-btn-primary session-timeout-button" data-session-timeout-primary>
                    <span>Sign In Again</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
@elseif (\Illuminate\Support\Facades\Auth::check())
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        (() => {
            const activityUrl = @json(route('session.activity'));
            const csrfToken = @json(csrf_token());

            window.setInterval(() => {
                fetch(activityUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    cache: 'no-store',
                }).catch(() => {});
            }, 5 * 60 * 1000);
        })();
    </script>
@endif

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" type="application/json" id="flashToastPayload">
{!! json_encode(
    $flashToasts,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT |
    JSON_UNESCAPED_UNICODE
) !!}
</script>
