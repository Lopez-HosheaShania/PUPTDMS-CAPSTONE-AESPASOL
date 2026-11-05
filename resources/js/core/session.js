function initSessionTimeoutModal() {
    const modal = document.querySelector(
        '[data-session-timeout-modal]'
    );

    if (
        !modal?.id ||
        modal.dataset.sessionTimeoutInitialized ===
        'true'
    ) {
        return;
    }

    modal.dataset.sessionTimeoutInitialized =
        'true';

    const stateIcon = modal.querySelector(
        '[data-session-state-icon] i'
    );

    const stateTitle = modal.querySelector(
        '[data-session-state-title]'
    );

    const stateDescription = modal.querySelector(
        '[data-session-state-description]'
    );

    const countdown = modal.querySelector(
        '[data-session-countdown]'
    );

    const warningActions = modal.querySelector(
        '[data-session-warning-actions]'
    );

    const staySignedInButton = modal.querySelector(
        '[data-session-stay-signed-in]'
    );

    const signOutNowButton = modal.querySelector(
        '[data-session-sign-out-now]'
    );

    const signInAgainButton = modal.querySelector(
        '[data-session-sign-in-again]'
    );

    const timeoutSeconds = Math.max(
        60,
        Number(
            modal.dataset.sessionTimeoutSeconds
        ) || 600
    );

    const warningSeconds = Math.max(
        1,
        Math.min(
            timeoutSeconds - 1,
            Number(
                modal.dataset.sessionWarningSeconds
            ) || 120
        )
    );

    const activityUrl = modal.dataset.sessionActivityUrl;
    const statusUrl = modal.dataset.sessionStatusUrl;
    const expireUrl = modal.dataset.sessionExpireUrl;
    const loginUrl = modal.dataset.sessionLoginUrl || '/login';

    const idleRedirectUrl =
        modal.dataset.sessionRedirectUrl ||
        loginUrl;

    const sharedActivityKey =
        modal.dataset.sessionActivityKey ||
        '';

    const userActivitySyncInterval = 10000;
    const serverStatusSyncInterval = 15000;

    let terminalState = null;
    let modalState = 'active';
    let redirectStarted = false;

    let activityRequestRunning = false;
    let queuedTouchRequest = false;
    let expiryVerificationRunning = false;

    let lastHandledActivityAt = 0;
    let lastTouchSyncAt = 0;
    let deferredTouchTimer = null;

    let statusInterval = null;
    let countdownInterval = null;

    let serverNowAtSyncMs = null;
    let performanceAtSyncMs = null;
    let expiresAtMs = null;

    const requestHeaders = () => ({
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN':
            window.getCsrfToken?.() || '',
    });

    const setButtonLoading = (
        button,
        label
    ) => {
        if (!button) return;

        button.disabled = true;

        button.innerHTML = `
            <i
                class="fa-solid fa-spinner fa-spin"
                aria-hidden="true">
            </i>

            <span>${label}</span>
        `;
    };

    const restoreStayButton = () => {
        if (!staySignedInButton) return;

        staySignedInButton.disabled = false;

        staySignedInButton.innerHTML = `
            <span>Stay signed in</span>
        `;
    };

    const setServerTiming = payload => {
        const serverNow = Number(
            payload?.server_now
        );

        const expiresAt = Number(
            payload?.expires_at
        );

        if (
            !Number.isFinite(serverNow) ||
            !Number.isFinite(expiresAt)
        ) {
            return false;
        }

        serverNowAtSyncMs =
            serverNow * 1000;

        performanceAtSyncMs =
            window.performance.now();

        expiresAtMs =
            expiresAt * 1000;

        return true;
    };

    const estimatedServerNowMs = () => {
        if (
            serverNowAtSyncMs === null ||
            performanceAtSyncMs === null
        ) {
            return null;
        }

        return (
            serverNowAtSyncMs +
            (
                window.performance.now() -
                performanceAtSyncMs
            )
        );
    };

    const getRemainingSeconds = () => {
        const serverNowMs =
            estimatedServerNowMs();

        if (
            serverNowMs === null ||
            expiresAtMs === null
        ) {
            return null;
        }

        return Math.max(
            0,
            Math.ceil(
                (
                    expiresAtMs -
                    serverNowMs
                ) / 1000
            )
        );
    };

    const formatCountdown = seconds => {
        const safeSeconds = Math.max(
            0,
            Number(seconds) || 0
        );

        const minutes = Math.floor(
            safeSeconds / 60
        );

        const remainder =
            safeSeconds % 60;

        return `${minutes}:${String(
            remainder
        ).padStart(2, '0')}`;
    };

    const updateCountdown = () => {
        if (!countdown) return;

        const remaining =
            getRemainingSeconds();

        countdown.textContent =
            formatCountdown(
                remaining ?? 0
            );
    };

    const closeWarningModal = () => {
        if (
            modalState !== 'warning'
        ) {
            return;
        }

        modalState = 'active';

        window.closeModal?.(
            modal.id,
            {
                force: true,
            }
        );
    };

    const renderState = state => {
        if (
            terminalState &&
            state === 'warning'
        ) {
            return;
        }

        modalState = state;

        if (state === 'warning') {
            stateIcon?.setAttribute(
                'class',
                'fa-regular fa-clock fa-swing'
            );

            if (stateTitle) {
                stateTitle.textContent =
                    'Still there?';
            }

            if (stateDescription) {
                stateDescription.textContent =
                    'You will be signed out soon because there has been no activity.';
            }

            if (countdown) {
                countdown.hidden = false;
            }

            if (warningActions) {
                warningActions.hidden = false;
            }

            if (signInAgainButton) {
                signInAgainButton.hidden = true;
            }

            updateCountdown();

            window.openModal?.(
                modal.id
            );

            requestAnimationFrame(
                () => {
                    staySignedInButton?.focus({
                        preventScroll: true,
                    });
                }
            );

            return;
        }

        terminalState = state;

        window.__SESSION_EXPIRED__ =
            true;

        document.documentElement
            .classList
            .add(
                'session-expired'
            );

        document.body
            .classList
            .add(
                'session-expired'
            );

        if (countdown) {
            countdown.hidden = true;
        }

        if (warningActions) {
            warningActions.hidden = true;
        }

        if (signInAgainButton) {
            signInAgainButton.hidden =
                false;

            signInAgainButton.disabled =
                false;

            signInAgainButton.innerHTML = `
                <span>Sign in again</span>
            `;
        }

        if (
            state === 'admin-ended'
        ) {
            stateIcon?.setAttribute(
                'class',
                'fa-solid fa-shield-halved fa-swing'
            );

            if (stateTitle) {
                stateTitle.textContent =
                    'Session ended';
            }

            if (stateDescription) {
                stateDescription.innerHTML =
                    'Your session was ended by an administrator.<br>Please sign in again to continue.';
            }
        } else {
            stateIcon?.setAttribute(
                'class',
                'fa-regular fa-clock fa-swing'
            );

            if (stateTitle) {
                stateTitle.textContent =
                    'Session expired';
            }

            if (stateDescription) {
                stateDescription.innerHTML =
                    'You were inactive for a while.<br>Please sign in again to continue.';
            }
        }

        window.openModal?.(
            modal.id
        );

        requestAnimationFrame(
            () => {
                signInAgainButton?.focus({
                    preventScroll: true,
                });
            }
        );

        document.dispatchEvent(
            new CustomEvent(
                'session:expired',
                {
                    detail: {
                        reason:
                            state ===
                                'admin-ended'
                                ? 'admin_ended'
                                : 'idle',
                    },
                }
            )
        );
    };

    window.testSessionWarning = () => {
        renderState('warning');
    };

    window.testSessionExpired = () => {
        renderState('expired');
    };

    window.testAdminSessionEnded = () => {
        renderState('admin-ended');
    };

    const classifyInvalidSession =
        payload => {
            if (
                payload?.reason ===
                'admin_ended'
            ) {
                return 'admin-ended';
            }

            if (
                payload?.reason === 'idle' ||
                payload?.expired === true
            ) {
                return 'expired';
            }

            const remaining =
                getRemainingSeconds();

            if (
                remaining !== null &&
                remaining <= 0
            ) {
                return 'expired';
            }

            return 'expired';
        };

    const resolveInvalidSession =
        async payload => {
            if (
                payload?.reason === 'idle' ||
                payload?.reason === 'admin_ended'
            ) {
                return classifyInvalidSession(
                    payload
                );
            }

            if (!statusUrl) {
                return classifyInvalidSession(
                    payload
                );
            }

            try {
                const response =
                    await fetch(
                        statusUrl,
                        {
                            method: 'GET',
                            credentials:
                                'same-origin',
                            headers: {
                                Accept:
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            cache: 'no-store',
                        }
                    );

                if (!response.ok) {
                    return classifyInvalidSession(
                        payload
                    );
                }

                const statusPayload =
                    await response.json();

                if (
                    statusPayload?.active === true &&
                    statusPayload?.expired !== true
                ) {
                    return null;
                }

                return classifyInvalidSession(
                    statusPayload
                );
            } catch (_) {
                return classifyInvalidSession(
                    payload
                );
            }
        };

    window.handleInvalidSessionResponse =
        async function (
            payload = {},
            status = 401
        ) {
            if (
                status !== 401 &&
                status !== 419 &&
                payload?.expired !== true
            ) {
                return false;
            }

            const invalidState =
                await resolveInvalidSession(
                    payload
                );

            if (!invalidState) {
                return false;
            }

            renderState(
                invalidState
            );

            return true;
        };

    const writeSharedActivitySignal =
        () => {
            if (!sharedActivityKey) {
                return;
            }

            try {
                window.localStorage
                    .setItem(
                        sharedActivityKey,
                        String(
                            Date.now()
                        )
                    );
            } catch (_) {
            }
        };

    const syncActivityWithServer =
        async ({
            touch = false,
            force = false,
        } = {}) => {
            if (
                terminalState ||
                !activityUrl
            ) {
                return false;
            }

            if (
                activityRequestRunning
            ) {
                if (touch) {
                    queuedTouchRequest =
                        true;
                }

                return false;
            }

            if (
                touch &&
                !force &&
                Date.now() -
                lastTouchSyncAt <
                userActivitySyncInterval
            ) {
                return false;
            }

            activityRequestRunning =
                true;

            if (touch) {
                lastTouchSyncAt =
                    Date.now();
            }

            try {
                const url = new URL(
                    activityUrl,
                    window.location.origin
                );

                url.searchParams.set(
                    'touch',
                    touch ? '1' : '0'
                );

                const response =
                    await fetch(
                        url.toString(),
                        {
                            method: 'POST',
                            credentials:
                                'same-origin',
                            headers:
                                requestHeaders(),
                            cache: 'no-store',
                        }
                    );

                let payload = {};

                try {
                    payload =
                        await response.json();
                } catch (_) {
                }

                if (
                    response.status ===
                    401 ||
                    response.status ===
                    419
                ) {
                    const invalidState =
                        await resolveInvalidSession(
                            payload
                        );

                    if (invalidState) {
                        renderState(
                            invalidState
                        );
                    }

                    return false;
                }

                if (!response.ok) {
                    return false;
                }

                if (
                    payload?.expired ===
                    true
                ) {
                    renderState(
                        classifyInvalidSession(
                            payload
                        )
                    );

                    return false;
                }

                if (
                    !setServerTiming(
                        payload
                    )
                ) {
                    return false;
                }

                if (touch) {
                    writeSharedActivitySignal();

                    if (
                        modalState ===
                        'warning'
                    ) {
                        closeWarningModal();
                    }
                }

                return true;
            } catch (_) {
                return false;
            } finally {
                activityRequestRunning =
                    false;

                if (
                    queuedTouchRequest &&
                    !terminalState
                ) {
                    queuedTouchRequest =
                        false;

                    window.setTimeout(
                        () => {
                            syncActivityWithServer(
                                {
                                    touch: true,
                                    force: true,
                                }
                            );
                        },
                        0
                    );
                }
            }
        };

    const scheduleDeferredTouch =
        () => {
            if (
                terminalState ||
                deferredTouchTimer
            ) {
                return;
            }

            const elapsed =
                Date.now() -
                lastTouchSyncAt;

            const delay = Math.max(
                0,
                userActivitySyncInterval -
                elapsed
            );

            deferredTouchTimer =
                window.setTimeout(
                    async () => {
                        deferredTouchTimer =
                            null;

                        await syncActivityWithServer(
                            {
                                touch: true,
                                force: true,
                            }
                        );
                    },
                    delay
                );
        };

    const registerUserActivity =
        event => {
            if (terminalState) {
                return;
            }

            if (
                modalState ===
                'warning' &&
                event?.target?.closest?.(
                    '#sessionTimeoutModal'
                )
            ) {
                return;
            }

            const now = Date.now();

            if (
                now -
                lastHandledActivityAt <
                800
            ) {
                return;
            }

            lastHandledActivityAt =
                now;

            if (
                now -
                lastTouchSyncAt >=
                userActivitySyncInterval
            ) {
                syncActivityWithServer({
                    touch: true,
                });

                return;
            }

            scheduleDeferredTouch();
        };

    const verifyExpiredDeadline =
        async () => {
            if (
                terminalState ||
                expiryVerificationRunning
            ) {
                return;
            }

            expiryVerificationRunning =
                true;

            try {
                await syncActivityWithServer(
                    {
                        touch: false,
                        force: true,
                    }
                );
            } finally {
                expiryVerificationRunning =
                    false;
            }
        };

    const checkServerDeadline =
        () => {
            if (terminalState) {
                return;
            }

            const remaining =
                getRemainingSeconds();

            if (
                remaining === null
            ) {
                return;
            }

            if (remaining <= 0) {
                verifyExpiredDeadline();
                return;
            }

            if (
                remaining <=
                warningSeconds
            ) {
                if (
                    modalState !==
                    'warning'
                ) {
                    renderState(
                        'warning'
                    );
                }

                updateCountdown();

                return;
            }

            if (
                modalState ===
                'warning'
            ) {
                closeWarningModal();
            }
        };

    [
        'pointerdown',
        'pointermove',
        'keydown',
        'scroll',
        'touchstart',
    ].forEach(eventName => {
        document.addEventListener(
            eventName,
            registerUserActivity,
            {
                capture: true,
                passive: true,
            }
        );
    });

    window.addEventListener(
        'focus',
        () => {
            syncActivityWithServer({
                touch: false,
                force: true,
            });
        }
    );

    document.addEventListener(
        'visibilitychange',
        () => {
            if (
                document.visibilityState !==
                'visible' ||
                terminalState
            ) {
                return;
            }

            syncActivityWithServer({
                touch: false,
                force: true,
            });
        }
    );

    window.addEventListener(
        'storage',
        event => {
            if (
                terminalState ||
                !sharedActivityKey ||
                event.key !==
                sharedActivityKey
            ) {
                return;
            }

            syncActivityWithServer({
                touch: false,
                force: true,
            });
        }
    );

    document.addEventListener(
        'submit',
        event => {
            if (!terminalState) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
        },
        true
    );

    document.addEventListener(
        'click',
        event => {
            if (!terminalState) {
                return;
            }

            const signInAgain =
                event.target.closest(
                    '[data-session-sign-in-again]'
                );

            if (signInAgain) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
        },
        true
    );

    staySignedInButton
        ?.addEventListener(
            'click',
            async () => {
                if (terminalState) {
                    return;
                }

                setButtonLoading(
                    staySignedInButton,
                    'Staying signed in...'
                );

                const active =
                    await syncActivityWithServer(
                        {
                            touch: true,
                            force: true,
                        }
                    );

                if (
                    !terminalState &&
                    !active
                ) {
                    restoreStayButton();

                    return;
                }

                if (
                    !terminalState
                ) {
                    restoreStayButton();
                }
            }
        );

    signOutNowButton
        ?.addEventListener(
            'click',
            async () => {
                if (
                    redirectStarted
                ) {
                    return;
                }

                redirectStarted =
                    true;

                setButtonLoading(
                    signOutNowButton,
                    'Signing out...'
                );

                try {
                    if (expireUrl) {
                        await fetch(
                            expireUrl,
                            {
                                method:
                                    'POST',
                                credentials:
                                    'same-origin',
                                headers:
                                    requestHeaders(),
                                cache:
                                    'no-store',
                            }
                        );
                    }
                } catch (_) {
                } finally {
                    window.location.assign(
                        idleRedirectUrl
                    );
                }
            }
        );

    signInAgainButton
        ?.addEventListener(
            'click',
            async () => {
                if (
                    redirectStarted
                ) {
                    return;
                }

                redirectStarted =
                    true;

                setButtonLoading(
                    signInAgainButton,
                    'Opening Sign In...'
                );

                try {
                    if (expireUrl) {
                        await fetch(
                            expireUrl,
                            {
                                method:
                                    'POST',
                                credentials:
                                    'same-origin',
                                headers:
                                    requestHeaders(),
                                cache:
                                    'no-store',
                            }
                        );
                    }
                } catch (_) {
                } finally {
                    window.location.replace(loginUrl);
                }
            }
        );

    countdownInterval =
        window.setInterval(
            checkServerDeadline,
            500
        );

    statusInterval =
        window.setInterval(
            () => {
                syncActivityWithServer({
                    touch: false,
                });
            },
            serverStatusSyncInterval
        );

    syncActivityWithServer({
        touch: true,
        force: true,
    }).then(() => {
        checkServerDeadline();
    });
}

function acceptTerms() {
    window.closeModal?.(
        'termsModal'
    );
}

function initTermsModal() {
    const modal =
        document.getElementById(
            'termsModal'
        );

    if (!modal) return;

    const checkbox =
        modal.querySelector(
            '#termsCheckbox, [data-terms-checkbox]'
        );

    const continueBtn =
        modal.querySelector(
            '#termsContinueBtn, [data-terms-continue]'
        );

    if (
        checkbox &&
        continueBtn
    ) {
        checkbox.checked = false;
        continueBtn.disabled = true;

        checkbox.addEventListener(
            'change',
            () => {
                continueBtn.disabled =
                    !checkbox.checked;
            }
        );

        continueBtn.addEventListener(
            'click',
            acceptTerms
        );
    }

    const shouldShow =
        modal.dataset.showTerms ===
        'true';

    if (shouldShow) {
        requestAnimationFrame(
            () => {
                window.openModal?.(
                    'termsModal'
                );
            }
        );
    }
}

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initTermsModal();
        initSessionTimeoutModal();
    }
);

window.acceptTerms = acceptTerms;
window.initTermsModal = initTermsModal;
window.initSessionTimeoutModal = initSessionTimeoutModal;