function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function getNotificationDedupeKey(notification) {
    return notification.dedupe_key
        ?? notification.data?.dedupe_key
        ?? notification.id
        ?? notification.notification_id
        ?? notification.uuid
        ?? notification.data?.id
        ?? null;
}

function getNotificationItem(notification) {
    const dedupeKey = getNotificationDedupeKey(notification);

    if (!dedupeKey) return null;

    const escapedKey = window.CSS?.escape
        ? window.CSS.escape(String(dedupeKey))
        : String(dedupeKey).replace(/"/g, '\\"');

    return document.querySelector(`[data-notif-dedupe-key="${escapedKey}"]`);
}

function notificationAlreadyExists(notification) {
    return Boolean(getNotificationItem(notification));
}

function getKnownNotificationKeys() {
    return new Set(
        Array.from(document.querySelectorAll('[data-notif-dedupe-key]'))
            .map(item => item.dataset.notifDedupeKey)
            .filter(Boolean)
    );
}

function ensureBellBadge() {
    const notifBtn = document.querySelector('#notifBtn');
    if (!notifBtn) return null;

    let badge = notifBtn.querySelector('[data-notif-badge]');

    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'notif-badge';
        badge.setAttribute('data-notif-badge', '');
        notifBtn.appendChild(badge);
    }

    return badge;
}

function syncBellBadge(unreadCount) {
    const badge = ensureBellBadge();
    if (!badge) return;

    badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
    badge.hidden = unreadCount <= 0;
    badge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
    badge.style.visibility = unreadCount > 0 ? 'visible' : 'hidden';
}

function setNotificationCounts(unreadCount = 0, totalCount = 0, readCount = null) {
    const unreadPill = document.querySelector('[data-notif-unread-pill]');
    const totalPill = document.querySelector('[data-notif-total-pill]');
    const allTab = document.querySelector('[data-notif-tab-count="all"]');
    const unreadTab = document.querySelector('[data-notif-tab-count="unread"]');
    const readTab = document.querySelector('[data-notif-tab-count="read"]');
    const markAllForm = document.querySelector('[data-notif-mark-all-form]');

    const unread = Math.max(Number(unreadCount || 0), 0);
    const total = Math.max(Number(totalCount || 0), 0);
    const read = readCount === null ? Math.max(total - unread, 0) : Math.max(Number(readCount || 0), 0);

    if (unreadPill) unreadPill.textContent = `${unread} unread`;
    if (totalPill) totalPill.textContent = `${total} total`;
    if (allTab) allTab.textContent = total;
    if (unreadTab) unreadTab.textContent = unread;
    if (readTab) readTab.textContent = read;

    if (markAllForm) {
        markAllForm.hidden = unread === 0;
        markAllForm.classList.toggle('hidden', unread === 0);
    }

    syncBellBadge(unread);
}

function removeEmptyState() {
    const emptyState = document.querySelector('.header-notif-empty');
    if (emptyState) emptyState.remove();
}

function createEmptyState() {
    const emptyState = document.createElement('div');
    emptyState.className = 'header-notif-empty';
    emptyState.innerHTML = `
        <i class="fa-solid fa-bell-slash"></i>
        <span>You're all caught up.</span>
    `;

    return emptyState;
}

function getCsrfInputMarkup() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    return token
        ? `<input type="hidden" name="_token" value="${escapeHtml(token)}">`
        : '';
}

function getActiveNotificationFilter() {
    return document.querySelector('.header-notif-tab.is-active')?.dataset.notifFilter || 'all';
}

function itemMatchesFilter(item, filter) {
    const state = item.dataset.notifState || 'unread';

    return filter === 'all' || filter === state;
}

function syncActiveNotificationFilter() {
    const notifMenu = document.querySelector('#notifMenu');
    if (!notifMenu) return;

    const filter = getActiveNotificationFilter();
    const items = Array.from(notifMenu.querySelectorAll('[data-notif-item]'));
    const filterEmpty = notifMenu.querySelector('.header-notif-filter-empty');
    let visibleCount = 0;

    items.forEach(item => {
        const visible = itemMatchesFilter(item, filter);
        item.classList.toggle('hidden', !visible);

        if (visible) visibleCount += 1;
    });

    if (filterEmpty) {
        filterEmpty.hidden = items.length === 0 || visibleCount > 0;
    }
}

function markItemAsRead(item) {
    if (!item) return;

    item.dataset.notifState = 'read';
    item.classList.remove('is-unread');
    item.classList.add('is-read');

    const unreadDot = item.querySelector('.header-notif-unread-dot');
    if (unreadDot) unreadDot.remove();

    const markReadForm = item.querySelector('[data-notif-mark-read-form]');
    if (markReadForm) markReadForm.remove();
}

function syncExistingNotification(notification) {
    const item = getNotificationItem(notification);
    if (!item) return false;

    if ((notification.state || 'unread') === 'read') {
        markItemAsRead(item);
    }

    return true;
}

function createNotificationItem(notification) {
    const title = notification.title ?? 'Notification';
    const message = notification.message ?? '';
    const url = notification.url ?? '#';
    const icon = notification.icon ?? 'fa-bell';
    const createdAtLabel = notification.created_at_label ?? 'Just now';
    const state = notification.state ?? 'unread';
    const dedupeKey = getNotificationDedupeKey(notification);
    const markReadUrl = notification.mark_read_url ?? notification.data?.mark_read_url ?? '';
    const showMarkRead = state === 'unread' && markReadUrl;

    const item = document.createElement('div');
    item.className = `header-notif-item ${state === 'unread' ? 'is-unread' : 'is-read'}`;
    item.setAttribute('data-notif-state', state);
    item.setAttribute('data-notif-item', '');

    if (dedupeKey) {
        item.setAttribute('data-notif-dedupe-key', String(dedupeKey));
    }

    if (markReadUrl) {
        item.setAttribute('data-notif-mark-read-url', String(markReadUrl));
    }

    item.innerHTML = `
        <div class="header-notif-item-icon">
            <i class="fa-solid ${escapeHtml(icon)}"></i>
        </div>

        <div class="header-notif-item-content">
            <div class="header-notif-item-top">
                ${url && url !== '#'
            ? `<a href="${escapeHtml(url)}" class="header-notif-item-title" data-notif-open-link>${escapeHtml(title)}</a>`
            : `<span class="header-notif-item-title">${escapeHtml(title)}</span>`
        }
                <span class="header-notif-item-time">${escapeHtml(createdAtLabel)}</span>
            </div>

            ${message ? `<div class="header-notif-item-message">${escapeHtml(message)}</div>` : ''}

            <div class="header-notif-item-actions">
                ${url && url !== '#'
            ? `<a href="${escapeHtml(url)}" class="header-notif-link-action" data-notif-open-link>Open</a>`
            : ''
        }
                ${showMarkRead
            ? `<form method="POST" action="${escapeHtml(markReadUrl)}" class="header-notif-action-form" data-notif-mark-read-form>
                    ${getCsrfInputMarkup()}
                    <button type="submit" class="header-notif-link-action header-notif-link-action-secondary">
                        Mark read
                    </button>
                </form>`
            : ''
        }
            </div>
        </div>

        ${state === 'unread' ? '<span class="header-notif-unread-dot" aria-hidden="true"></span>' : ''}
    `;

    return item;
}

function prependNotificationItem(notification) {
    const notifBody = document.querySelector('.header-notif-body');
    if (!notifBody) return false;

    if (notificationAlreadyExists(notification)) {
        syncExistingNotification(notification);
        return false;
    }

    removeEmptyState();

    const item = createNotificationItem(notification);
    const filterEmpty = notifBody.querySelector('.header-notif-filter-empty');

    if (filterEmpty) {
        notifBody.insertBefore(item, filterEmpty);
    } else {
        notifBody.prepend(item);
    }

    return true;
}

function renderNotificationList(notifications) {
    const notifBody = document.querySelector('.header-notif-body');
    if (!notifBody) return;

    const filterEmpty = notifBody.querySelector('.header-notif-filter-empty');

    notifBody
        .querySelectorAll('[data-notif-item], .header-notif-empty')
        .forEach(item => item.remove());

    if (!notifications.length) {
        const emptyState = createEmptyState();

        if (filterEmpty) {
            notifBody.insertBefore(emptyState, filterEmpty);
        } else {
            notifBody.appendChild(emptyState);
        }

        return;
    }

    notifications.forEach(notification => {
        const item = createNotificationItem(notification);

        if (filterEmpty) {
            notifBody.insertBefore(item, filterEmpty);
        } else {
            notifBody.appendChild(item);
        }
    });
}

function getCurrentMonthKey() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');

    return `${year}-${month}`;
}

function parseCounterNumber(value) {
    const cleaned = String(value ?? '').replace(/[^\d]/g, '');
    return Number(cleaned || 0);
}

function formatCounterNumber(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
}

function animateDashboardCounter(counter, card = null) {
    if (counter?.animate) {
        counter.animate(
            [
                { transform: 'scale(1)', opacity: 1 },
                { transform: 'scale(1.12)', opacity: 0.85 },
                { transform: 'scale(1)', opacity: 1 },
            ],
            {
                duration: 420,
                easing: 'ease-out',
            }
        );
    }

    if (card?.animate) {
        card.animate(
            [
                { transform: 'translateY(0)' },
                { transform: 'translateY(-4px)' },
                { transform: 'translateY(0)' },
            ],
            {
                duration: 420,
                easing: 'ease-out',
            }
        );
    }
}

function syncAdminDashboardAppointmentStats(notification) {
    if ((notification.event ?? notification.data?.event) !== 'appointment.booked') {
        return;
    }

    const counter = document.querySelector('[data-admin-dashboard-counter="appointments-this-month"]');

    if (!counter) {
        return;
    }

    const appointmentMonth = notification.appointment_month ?? notification.data?.appointment_month ?? null;
    const currentMonth = getCurrentMonthKey();

    if (appointmentMonth && appointmentMonth !== currentMonth) {
        return;
    }

    const currentValue = parseCounterNumber(counter.textContent);
    const nextValue = currentValue + 1;

    counter.textContent = formatCounterNumber(nextValue);

    const card =
        document.querySelector('[data-admin-dashboard-card="appointments-this-month"]') ||
        counter.closest('.stat-card');

    animateDashboardCounter(counter, card);
}

async function fetchNotificationPoll(pollUrl) {
    if (window.axios) {
        const response = await window.axios.get(pollUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        return response.data;
    }

    const response = await fetch(pollUrl, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`Notification poll failed with status ${response.status}`);
    }

    return response.json();
}

function applyNotificationPollPayload(payload) {
    const notifications = Array.isArray(payload?.notifications) ? payload.notifications : [];
    const knownKeys = getKnownNotificationKeys();

    renderNotificationList(notifications);

    notifications.forEach(notification => {
        const key = getNotificationDedupeKey(notification);

        if (key && !knownKeys.has(String(key)) && (notification.state || 'unread') === 'unread') {
            syncAdminDashboardAppointmentStats(notification);
        }
    });

    setNotificationCounts(
        payload?.unread_count ?? 0,
        payload?.total_count ?? notifications.length,
        payload?.read_count ?? null
    );

    syncActiveNotificationFilter();
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.__notificationsPollingInitialized) return;
    window.__notificationsPollingInitialized = true;

    const notifDropdown = document.querySelector('#notifDropdown');
    const pollUrl = notifDropdown?.dataset?.notifPollUrl;

    if (!pollUrl) return;

    let stopped = false;
    let pollTimer = null;
    let polling = false;

    const scheduleNextPoll = () => {
        if (stopped) return;

        const delay = document.hidden ? 30000 : 10000;
        window.clearTimeout(pollTimer);
        pollTimer = window.setTimeout(runPoll, delay);
    };

    const runPoll = async () => {
        if (stopped || polling) return;

        polling = true;

        try {
            const payload = await fetchNotificationPoll(pollUrl);
            applyNotificationPollPayload(payload);
        } catch (_error) {
        } finally {
            polling = false;
            scheduleNextPoll();
        }
    };

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            window.clearTimeout(pollTimer);
            runPoll();
        }
    });

    document.querySelector('#notifBtn')?.addEventListener('click', () => {
        window.clearTimeout(pollTimer);
        runPoll();
    });

    pollTimer = window.setTimeout(runPoll, 2500);
});
