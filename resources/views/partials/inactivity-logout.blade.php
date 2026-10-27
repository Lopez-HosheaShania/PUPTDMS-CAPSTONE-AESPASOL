@php
    $idleTimeoutSeconds = max((int) config('session.idle_timeout_seconds', 600), 60);
@endphp

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        const timeoutMs = {{ $idleTimeoutSeconds * 1000 }};

        if (!timeoutMs || timeoutMs < 60000) {
            return;
        }

        let timerId = null;
        let logoutStarted = false;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @json(route('logout'));
        form.style.display = 'none';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = @json(csrf_token());
        form.appendChild(csrfInput);

        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'reason';
        reasonInput.value = 'idle';
        form.appendChild(reasonInput);

        document.addEventListener('DOMContentLoaded', function () {
            document.body.appendChild(form);
        });

        const startLogout = () => {
            if (logoutStarted) {
                return;
            }

            logoutStarted = true;
            clearTimeout(timerId);
            form.submit();
        };

        const resetTimer = () => {
            if (logoutStarted) {
                return;
            }

            clearTimeout(timerId);
            timerId = window.setTimeout(startLogout, timeoutMs);
        };

        ['click', 'keydown', 'mousemove', 'mousedown', 'scroll', 'touchstart'].forEach((eventName) => {
            document.addEventListener(eventName, resetTimer, { passive: true });
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                resetTimer();
            }
        });

        window.addEventListener('focus', resetTimer);
        resetTimer();
    })();
</script>
