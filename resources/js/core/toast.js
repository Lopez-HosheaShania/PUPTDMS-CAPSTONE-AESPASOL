function escapeToastHTML(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatToastMessage(message) {
    return escapeToastHTML(message)
        .replace(/&lt;strong&gt;(.*?)&lt;\/strong&gt;/g, '<strong>$1</strong>');
}

function normalizeToastArgs(first = 'success', second = '', third = undefined, fourth = undefined) {
    const validTypes = ['success', 'error', 'warning', 'info'];
    const defaultDuration = 5000;

    if (typeof first === 'object' && first !== null) {
        const type = validTypes.includes(String(first.type || '').toLowerCase())
            ? String(first.type).toLowerCase()
            : 'info';

        return {
            type,
            title: first.title || type.charAt(0).toUpperCase() + type.slice(1),
            message: first.message || '',
            duration: Number(first.duration) || defaultDuration,
        };
    }

    const firstLower = String(first || '').toLowerCase();
    const thirdLower = String(third || '').toLowerCase();

    if (validTypes.includes(firstLower)) {
        return {
            type: firstLower,
            title: firstLower.charAt(0).toUpperCase() + firstLower.slice(1),
            message: second || '',
            duration: Number(third) || defaultDuration,
        };
    }

    if (validTypes.includes(thirdLower)) {
        return {
            type: thirdLower,
            title: first || thirdLower.charAt(0).toUpperCase() + thirdLower.slice(1),
            message: second || '',
            duration: Number(fourth) || defaultDuration,
        };
    }

    return {
        type: 'info',
        title: first || 'Notification',
        message: second || '',
        duration: Number(third) || defaultDuration,
    };
}

const TOAST_MAX_VISIBLE = 3;
const activeToastRegistry = new Map();

function getToastKey(type, title, message) {
    return `${type}|${String(message || '').trim()}`;
}

function pruneToastStack(container) {
    if (!container) return;

    const visibleToasts = Array.from(
        container.querySelectorAll('.toast-item:not(.toast-exit)')
    );

    while (visibleToasts.length > TOAST_MAX_VISIBLE) {
        const oldestToast = visibleToasts.shift();

        if (oldestToast?.__closeToast) {
            oldestToast.__closeToast();
        } else {
            oldestToast?.remove();
        }
    }
}

function ensureToastContainer() {
    let container = document.getElementById('toastContainer');

    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
    }

    if (container.parentElement !== document.body) {
        document.body.appendChild(container);
    }

    return container;
}

function showToast(first = 'success', second = '', third = undefined, fourth = undefined) {
    const { type, title, message, duration } = normalizeToastArgs(first, second, third, fourth);

    const container = ensureToastContainer();

    const toastKey = getToastKey(type, title, message);
    const existingToast = activeToastRegistry.get(toastKey);

    if (
        existingToast &&
        document.body.contains(existingToast) &&
        !existingToast.classList.contains('toast-exit')
    ) {
        existingToast.__restartToast?.(duration);

        existingToast.classList.remove('toast-bumped');
        void existingToast.offsetWidth;
        existingToast.classList.add('toast-bumped');

        return existingToast;
    }

    const toast = document.createElement('div');
    toast.className = `toast-item toast-${type} ${type}`;
    toast.dataset.toastKey = toastKey;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

    const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info',
    };

    toast.innerHTML = `
        <div class="toast-icon-wrap">
            <i class="fa-solid ${icons[type] || icons.info}"></i>
        </div>

        <div class="toast-content">
            <div class="toast-title">${escapeToastHTML(title)}</div>
            <div class="toast-message">${formatToastMessage(message)}</div>
        </div>

        <button type="button" class="toast-close" aria-label="Close notification">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="toast-progress"></div>
    `;

    container.appendChild(toast);
    activeToastRegistry.set(toastKey, toast);

    const progress = toast.querySelector('.toast-progress');

    let remaining = duration;
    let startedAt = Date.now();
    let timeoutId = null;
    let closed = false;

    const resetProgress = (nextDuration = duration) => {
        if (!progress) return;

        progress.style.animation = 'none';
        void progress.offsetWidth;
        progress.style.animation = `toastProgress ${nextDuration}ms linear forwards`;
    };

    const closeToast = () => {
        if (closed) return;

        closed = true;
        clearTimeout(timeoutId);

        if (activeToastRegistry.get(toastKey) === toast) {
            activeToastRegistry.delete(toastKey);
        }

        toast.classList.remove('is-paused', 'toast-bumped');
        toast.classList.add('toast-exit');

        setTimeout(() => {
            toast.remove();
        }, 320);
    };

    const startTimer = () => {
        clearTimeout(timeoutId);
        startedAt = Date.now();
        timeoutId = setTimeout(closeToast, remaining);
    };

    const restartToast = (nextDuration = duration) => {
        if (closed) return;

        clearTimeout(timeoutId);

        remaining = Number(nextDuration) || duration;
        startedAt = Date.now();

        toast.classList.remove('is-paused');
        resetProgress(remaining);

        timeoutId = setTimeout(closeToast, remaining);
    };

    const pauseToast = () => {
        if (closed) return;

        clearTimeout(timeoutId);
        remaining -= Date.now() - startedAt;
        remaining = Math.max(remaining, 0);

        toast.classList.add('is-paused');

        if (progress) {
            progress.style.animationPlayState = 'paused';
        }
    };

    const resumeToast = () => {
        if (closed) return;

        toast.classList.remove('is-paused');

        if (progress) {
            progress.style.animationPlayState = 'running';
        }

        if (remaining <= 0) {
            closeToast();
            return;
        }

        startTimer();
    };

    toast.__closeToast = closeToast;
    toast.__restartToast = restartToast;

    toast.querySelector('.toast-close')?.addEventListener('click', closeToast);

    toast.addEventListener('mouseenter', pauseToast);
    toast.addEventListener('mouseleave', resumeToast);

    resetProgress(duration);
    startTimer();
    pruneToastStack(container);

    return toast;
}

function dismissToast(toast) {
    if (!toast) return;

    const targetToast = toast.closest ? toast.closest('.toast-item') : toast;

    if (!targetToast || targetToast.classList.contains('toast-exit')) return;

    targetToast.classList.remove('is-paused');
    targetToast.classList.add('toast-exit');

    setTimeout(() => {
        targetToast.remove();
    }, 320);
}

function initSessionStorageToasts() {
    const keys = ['dentistToast', 'adminToast', 'patientToast', 'globalToast'];

    keys.forEach(key => {
        const raw = sessionStorage.getItem(key);
        if (!raw) return;

        try {
            const toast = JSON.parse(raw);

            showToast({
                type: toast.type || (toast.tone === 'danger' ? 'error' : toast.tone) || 'success',
                title: toast.title || 'Notification',
                message: toast.message || '',
                duration: toast.duration || 4000,
            });
        } catch (_) {
        }

        sessionStorage.removeItem(key);
    });
}

function readJsonPayload(id) {
    const el = document.getElementById(id);
    if (!el) return null;

    try {
        return JSON.parse(el.textContent || 'null');
    } catch (_) {
        return null;
    }
}

function initFlashToasts() {
    const payload = readJsonPayload('flashToastPayload');

    if (!Array.isArray(payload)) return;

    payload.forEach((toast) => {
        if (!toast || !toast.message) return;

        showToast({
            type: toast.type || 'info',
            title: toast.title || 'Notification',
            message: toast.message,
        });
    });
}

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initSessionStorageToasts();
        initFlashToasts();
    }
);

window.showToast = showToast;
window.dismissToast = dismissToast;
window.initSessionStorageToasts = initSessionStorageToasts;
window.initFlashToasts = initFlashToasts;