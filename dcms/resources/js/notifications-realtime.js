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

function notificationAlreadyExists(notification) {
    const dedupeKey = getNotificationDedupeKey(notification);

    if (!dedupeKey) return false;

    return Array.from(document.querySelectorAll('[data-notif-dedupe-key]'))
        .some(item => item.dataset.notifDedupeKey === String(dedupeKey));
}

function syncBellBadge(unreadCount) {
    const notifBtn = document.querySelector('#notifBtn');
    if (!notifBtn) return;

    let badge = notifBtn.querySelector('[data-notif-badge]');

    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'notif-badge';
        badge.setAttribute('data-notif-badge', '');
        notifBtn.appendChild(badge);
    }

    badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
    badge.hidden = unreadCount <= 0;
    badge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
    badge.style.visibility = unreadCount > 0 ? 'visible' : 'hidden';
}

function updateNotificationCounts(unreadDelta = 0, totalDelta = 0) {
    const unreadPill = document.querySelector('[data-notif-unread-pill]');
    const totalPill = document.querySelector('[data-notif-total-pill]');
    const allTab = document.querySelector('[data-notif-tab-count="all"]');
    const unreadTab = document.querySelector('[data-notif-tab-count="unread"]');
    const readTab = document.querySelector('[data-notif-tab-count="read"]');

    let currentUnread = parseInt(unreadTab?.textContent?.trim() || '0', 10);
    let currentTotal = parseInt(allTab?.textContent?.trim() || '0', 10);

    currentUnread += unreadDelta;
    currentTotal += totalDelta;

    if (currentUnread < 0) currentUnread = 0;
    if (currentTotal < 0) currentTotal = 0;

    const currentRead = Math.max(currentTotal - currentUnread, 0);

    if (unreadPill) unreadPill.textContent = `${currentUnread} unread`;
    if (totalPill) totalPill.textContent = `${currentTotal} total`;
    if (allTab) allTab.textContent = currentTotal;
    if (unreadTab) unreadTab.textContent = currentUnread;
    if (readTab) readTab.textContent = currentRead;

    syncBellBadge(currentUnread);
}

function removeEmptyState() {
    const emptyState = document.querySelector('.header-notif-empty');
    if (emptyState) emptyState.remove();
}

function prependNotificationItem(notification) {
    const notifBody = document.querySelector('.header-notif-body');
    if (!notifBody) return false;

    if (notificationAlreadyExists(notification)) {
        return false;
    }

    removeEmptyState();

    const title = notification.title ?? 'Notification';
    const message = notification.message ?? '';
    const url = notification.url ?? '#';
    const icon = notification.icon ?? 'fa-bell';
    const createdAtLabel = notification.created_at_label ?? 'Just now';
    const dedupeKey = getNotificationDedupeKey(notification);

    const item = document.createElement('div');
    item.className = 'header-notif-item is-unread';
    item.setAttribute('data-notif-state', 'unread');
    item.setAttribute('data-notif-item', '');

    if (dedupeKey) {
        item.setAttribute('data-notif-dedupe-key', String(dedupeKey));
    }

    item.innerHTML = `
        <div class="header-notif-item-icon">
            <i class="fa-solid ${escapeHtml(icon)}"></i>
        </div>

        <div class="header-notif-item-content">
            <div class="header-notif-item-top">
                ${url && url !== '#'
            ? `<a href="${escapeHtml(url)}" class="header-notif-item-title">${escapeHtml(title)}</a>`
            : `<span class="header-notif-item-title">${escapeHtml(title)}</span>`
        }
                <span class="header-notif-item-time">${escapeHtml(createdAtLabel)}</span>
            </div>

            ${message ? `<div class="header-notif-item-message">${escapeHtml(message)}</div>` : ''}

            <div class="header-notif-item-actions">
                ${url && url !== '#'
            ? `<a href="${escapeHtml(url)}" class="header-notif-link-action">Open</a>`
            : ''
        }
            </div>
        </div>

        <span class="header-notif-unread-dot" aria-hidden="true"></span>
    `;

    const filterEmpty = notifBody.querySelector('.header-notif-filter-empty');

    if (filterEmpty) {
        notifBody.insertBefore(item, filterEmpty);
    } else {
        notifBody.prepend(item);
    }

    return true;
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


document.addEventListener('DOMContentLoaded', () => {
    if (window.__notificationsRealtimeInitialized) return;
    window.__notificationsRealtimeInitialized = true;

    const userIdMeta = document.querySelector('meta[name="auth-user-id"]');

    if (!userIdMeta || !window.Echo) return;

    const userId = userIdMeta.getAttribute('content');
    if (!userId) return;

    window.Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log('REALTIME NOTIF:', notification);

        const added = prependNotificationItem(notification);

        if (!added) return;

        updateNotificationCounts(1, 1);

        const unreadTab = document.querySelector('[data-notif-tab-count="unread"]');
        syncBellBadge(parseInt(unreadTab?.textContent?.trim() || '0', 10));

        syncAdminDashboardAppointmentStats(notification);
    });
});