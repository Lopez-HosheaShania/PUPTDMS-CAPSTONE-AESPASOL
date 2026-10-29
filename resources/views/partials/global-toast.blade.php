<div id="toastContainer" role="region" aria-live="polite"></div>

@php
    $flashToasts = [];
    $activeSessionRole =
        (string) (session('impersonated_role') ?:
        $layoutRole ?? (session('role') ?? optional(auth()->user()?->role)->slug));
    $idleTimeoutSeconds =
        auth()->user()?->idleTimeoutSeconds($activeSessionRole) ??
        max(0, (int) config('session.idle_timeout_seconds', 600));
    $idleWarningSeconds =
        $idleTimeoutSeconds > 0
            ? min(max(1, $idleTimeoutSeconds - 1), max(10, min(120, (int) floor($idleTimeoutSeconds * 0.2))))
            : 0;
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
    <div id="sessionTimeoutModal" class="ui-modal" role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="sessionTimeoutTitle" aria-describedby="sessionTimeoutDescription" data-modal-static
        data-session-timeout-modal data-session-timeout-seconds="{{ $idleTimeoutSeconds }}"
        data-session-warning-seconds="{{ $idleWarningSeconds }}"
        data-session-activity-url="{{ route('session.activity') }}"
        data-session-expire-url="{{ route('session.expire') }}" data-session-login-url="{{ route('login') }}"
        data-session-redirect-url="{{ route('login', [
            'logged_out' => 1,
            'reason' => 'idle',
        ]) }}"
        data-session-activity-key="pupt-dms-session-activity-{{ auth()->id() }}">

        <div class="ui-modal-card modal-sm">
            <div class="modal-bd">
                <div class="modal-form-section items-center text-center gap-3">
                    <div class="modal-icon" data-session-state-icon aria-hidden="true">
                        <i class="fa-regular fa-clock"></i>
                    </div>

                    <div class="modal-copy modal-form-section items-center text-center gap-2">
                        <h2 id="sessionTimeoutTitle" class="modal-title" data-session-state-title>
                            Still there?
                        </h2>

                        <p id="sessionTimeoutDescription" class="modal-subtitle" data-session-state-description>
                            You will be signed out soon because there has been no activity.
                        </p>
                    </div>

                    <strong class="modal-title" data-session-countdown aria-live="polite" hidden>
                        0:00
                    </strong>
                </div>
            </div>

            <div class="modal-ft modal-ft--centered">
                <div class="modal-form-grid w-full" data-session-warning-actions hidden>
                    <button type="button" class="ui-btn ui-btn-primary w-full" data-session-stay-signed-in
                        data-modal-initial-focus>
                        <span>Stay signed in</span>
                    </button>

                    <button type="button" class="ui-btn ui-btn-secondary w-full" data-session-sign-out-now>
                        <span>Sign out now</span>
                    </button>
                </div>

                <button type="button" class="ui-btn ui-btn-primary" data-session-sign-in-again hidden>
                    <span>Sign in again</span>
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
