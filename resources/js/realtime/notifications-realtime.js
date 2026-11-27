const NOTIFICATION_PAGE_SIZE = 5;
const NOTIFICATION_POLL_LIMIT = 30;

const POLL_INTERVAL = 10000;
const BACKGROUND_POLL_INTERVAL = 30000;

function initNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    const menu = document.getElementById('notifMenu');
    const bell = document.getElementById('notifBtn');

    const body = menu?.querySelector('.header-notif-body');
    const pollUrl = dropdown?.dataset.notifPollUrl;

    if (!dropdown || !menu || !bell || !body || !pollUrl) {
        return;
    }

    if (dropdown.dataset.notificationsInitialized === 'true') {
        return;
    }

    dropdown.dataset.notificationsInitialized = 'true';

    const loadMoreButton = menu.querySelector('[data-notif-load-more]');
    const previousControls = menu.querySelector('[data-notif-previous-controls]');
    const statusElement = menu.querySelector('[data-notif-status]');
    const markAllForm = menu.querySelector('[data-notif-mark-all-form]');
    const tabs = Array.from(menu.querySelectorAll('[data-notif-filter]'));

    const state = {
        filter: 'all',

        nextCursor: menu.dataset.notifNextCursor || null,
        hasMore: menu.dataset.notifHasMore === 'true',

        unreadCount: Number(menu.dataset.notifUnreadCount || 0),
        readCount: Number(menu.dataset.notifReadCount || 0),
        totalCount: Number(menu.dataset.notifTotalCount || 0),

        loading: false,
        stopped: false,
    };

    let queue = Promise.resolve();
    let pendingTasks = 0;
    let pollTimer = null;

    const initialItems = getItems();

    let newestKnown = initialItems[0]
        ? {
            id: initialItems[0].dataset.notifDedupeKey,
            created_at: initialItems[0].dataset.notifCreatedAt,
        }
        : null;

    const knownLatestKeys = new Set(
        initialItems.map(item => item.dataset.notifDedupeKey)
    );

    function enqueue(task) {
        pendingTasks += 1;

        const result = queue.then(async () => {
            if (state.stopped) return;

            return task();
        });

        queue = result.catch(() => { });

        return result.finally(() => {
            pendingTasks -= 1;
        });
    }

    function getItems() {
        return Array.from(
            body.querySelectorAll('[data-notif-item]')
        );
    }

    function element(tag, className = '', content = '') {
        const node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (content !== '') {
            node.textContent = String(content);
        }

        return node;
    }

    function getSafeUrl(value) {
        if (!value || value === '#') {
            return null;
        }

        try {
            const url = new URL(value, window.location.origin);

            if (
                !['http:', 'https:'].includes(url.protocol)
                || url.origin !== window.location.origin
                || url.username
                || url.password
            ) {
                return null;
            }

            return url.href;
        } catch {
            return null;
        }
    }

    function setStatus(message = '') {
        if (!statusElement) return;

        statusElement.textContent = message;
        statusElement.hidden = !message;
    }

    function setLoading(loading) {
        state.loading = loading;

        body.setAttribute(
            'aria-busy',
            loading ? 'true' : 'false'
        );

        if (loadMoreButton) {
            loadMoreButton.disabled = loading;

            loadMoreButton.textContent = loading
                ? 'Loading...'
                : 'See previous notifications';
        }

        tabs.forEach(tab => {
            tab.disabled = loading;
        });

        syncPreviousButton();
    }

    function syncPreviousButton() {
        const visible = state.hasMore && !!state.nextCursor;

        if (previousControls) {
            previousControls.hidden = !visible;
        }

        if (loadMoreButton) {
            loadMoreButton.hidden = !visible;
            loadMoreButton.disabled = state.loading;
        }
    }

    function updateCounts(payload) {
        if (!payload) return;

        if (payload.unread_count !== undefined) {
            state.unreadCount = Math.max(
                0,
                Number(payload.unread_count) || 0
            );
        }

        if (payload.total_count !== undefined) {
            state.totalCount = Math.max(
                0,
                Number(payload.total_count) || 0
            );
        }

        state.readCount = Math.max(
            0,
            Number(
                payload.read_count
                ?? state.totalCount - state.unreadCount
            ) || 0
        );

        menu.dataset.notifUnreadCount = String(state.unreadCount);
        menu.dataset.notifReadCount = String(state.readCount);
        menu.dataset.notifTotalCount = String(state.totalCount);

        const unreadPill = menu.querySelector('[data-notif-unread-pill]');
        const totalPill = menu.querySelector('[data-notif-total-pill]');

        if (unreadPill) {
            unreadPill.textContent = `${state.unreadCount} unread`;
        }

        if (totalPill) {
            totalPill.textContent = `${state.totalCount} total`;
        }

        let badge = bell.querySelector('[data-notif-badge]');

        if (!badge) {
            badge = element('span', 'notif-badge');
            badge.setAttribute('data-notif-badge', '');
            bell.appendChild(badge);
        }

        badge.textContent = state.unreadCount > 9
            ? '9+'
            : String(state.unreadCount);

        badge.hidden = state.unreadCount === 0;

        if (markAllForm) {
            markAllForm.hidden = state.unreadCount === 0;
            markAllForm.classList.remove('hidden');
        }
    }

    function updatePagination(payload) {
        state.nextCursor = payload?.next_cursor || null;

        state.hasMore = Boolean(
            payload?.has_more && state.nextCursor
        );

        menu.dataset.notifNextCursor = state.nextCursor || '';
        menu.dataset.notifHasMore = String(state.hasMore);

        syncPreviousButton();
    }

    function updateActiveTab() {
        tabs.forEach(tab => {
            const active =
                tab.dataset.notifFilter === state.filter;

            tab.classList.toggle('is-active', active);

            tab.setAttribute(
                'aria-pressed',
                String(active)
            );
        });
    }

    function updateEmptyState() {
        body.querySelectorAll('.header-notif-empty')
            .forEach(node => node.remove());

        let empty = body.querySelector('.header-notif-filter-empty');

        if (!empty) {
            empty = element(
                'div',
                'header-notif-filter-empty'
            );

            body.appendChild(empty);
        }

        const hasItems = getItems().length > 0;

        body.appendChild(empty);

        empty.hidden = hasItems;

        if (!hasItems) {
            empty.textContent = state.totalCount === 0
                ? "You're all caught up."
                : state.filter === 'unread'
                    ? 'No unread notifications.'
                    : state.filter === 'read'
                        ? 'No read notifications.'
                        : 'No notifications available.';
        }
    }

    async function fetchNotifications({
        filter = state.filter,
        limit = NOTIFICATION_PAGE_SIZE,
        cursor = null,
    } = {}) {
        const url = new URL(
            pollUrl,
            window.location.origin
        );

        url.searchParams.set('filter', filter);
        url.searchParams.set('limit', String(limit));

        if (cursor) {
            url.searchParams.set('cursor', cursor);
        } else {
            url.searchParams.delete('cursor');
        }

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',

            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },

            signal: AbortSignal.timeout(15000),
        });

        if (!response.ok) {
            throw new Error(
                `Unable to retrieve notifications (${response.status}).`
            );
        }

        return response.json();
    }

    function createOpenLink(url, className, text) {
        const link = element('a', className, text);

        link.href = url;
        link.setAttribute('data-notif-open-link', '');

        return link;
    }

    function createNotificationItem(notification) {
        const id = String(
            notification.id
            ?? notification.dedupe_key
            ?? ''
        );

        const unread = notification.state === 'unread';

        const title = notification.title || 'Notification';

        const message = String(notification.message || '');

        const summary = String(
            notification.summary || message
        );

        const url = getSafeUrl(notification.url);

        const markReadUrl = getSafeUrl(
            notification.mark_read_url
        );

        const item = element(
            'div',
            `header-notif-item ${unread ? 'is-unread' : 'is-read'}`
        );

        item.dataset.notifItem = '';
        item.dataset.notifState = unread ? 'unread' : 'read';
        item.dataset.notifDedupeKey = id;
        item.dataset.notifCreatedAt = notification.created_at || '';

        if (markReadUrl) {
            item.dataset.notifMarkReadUrl = markReadUrl;
        }

        if (unread) {
            const dot = element(
                'span',
                'header-notif-unread-dot'
            );

            dot.setAttribute('aria-hidden', 'true');
            item.appendChild(dot);
        }

        const content = element(
            'div',
            'header-notif-item-content'
        );

        const top = element(
            'div',
            'header-notif-item-top'
        );

        const titleElement = url
            ? createOpenLink(
                url,
                'header-notif-item-title',
                title
            )
            : element(
                'span',
                'header-notif-item-title',
                title
            );

        top.appendChild(titleElement);

        if (notification.created_at_label) {
            top.appendChild(
                element(
                    'span',
                    'header-notif-item-time',
                    notification.created_at_label
                )
            );
        }

        content.appendChild(top);

        if (summary) {
            content.appendChild(
                element(
                    'div',
                    'header-notif-item-message',
                    summary
                )
            );
        }

        if (message && summary !== message) {
            const details = element('details', 'mt-2');

            const detailsTitle = element(
                'summary',
                'header-notif-link-action header-notif-link-action-secondary',
                'View details'
            );

            const fullMessage = element(
                'div',
                'header-notif-item-message mt-2',
                message
            );

            fullMessage.dataset.notifFullMessage = '';

            details.append(
                detailsTitle,
                fullMessage
            );

            content.appendChild(details);
        }

        const actions = element(
            'div',
            'header-notif-item-actions'
        );

        if (url) {
            actions.appendChild(
                createOpenLink(
                    url,
                    'header-notif-link-action',
                    'Open'
                )
            );
        }

        if (unread && markReadUrl) {
            const form = element(
                'form',
                'header-notif-action-form'
            );

            form.method = 'POST';
            form.action = markReadUrl;
            form.dataset.notifMarkReadForm = '';

            const token = element('input');

            token.type = 'hidden';
            token.name = '_token';

            token.value = document.querySelector(
                'meta[name="csrf-token"]'
            )?.content || '';

            const button = element(
                'button',
                'header-notif-link-action header-notif-link-action-secondary',
                'Mark read'
            );

            button.type = 'submit';

            form.append(token, button);
            actions.appendChild(form);
        }

        if (actions.childElementCount > 0) {
            content.appendChild(actions);
        }

        item.appendChild(content);

        item.dataset.notifSignature = JSON.stringify([
            notification.title,
            notification.message,
            notification.summary,
            notification.url,
            notification.state,
            notification.created_at_label,
            notification.mark_read_url,
        ]);

        return item;
    }

    function uniqueNotifications(notifications) {
        const unique = new Map();

        notifications.forEach(notification => {
            const id = notification.id
                ?? notification.dedupe_key;

            if (!id) return;

            unique.set(String(id), notification);
        });

        return Array.from(unique.values());
    }

    function renderNotifications(
        notifications,
        { preserveScroll = true } = {}
    ) {
        const currentItems = getItems();

        const currentMap = new Map(
            currentItems.map(item => [
                item.dataset.notifDedupeKey,
                item,
            ])
        );

        const previousScroll = body.scrollTop;

        const bodyTop = body.getBoundingClientRect().top;

        const anchor = preserveScroll && previousScroll > 0
            ? currentItems.find(item =>
                item.getBoundingClientRect().bottom > bodyTop
            )
            : null;

        const anchorKey = anchor?.dataset.notifDedupeKey;

        const anchorOffset = anchor
            ? anchor.getBoundingClientRect().top - bodyTop
            : 0;

        const fragment = document.createDocumentFragment();

        uniqueNotifications(notifications).forEach(notification => {
            const id = String(
                notification.id ?? notification.dedupe_key
            );

            const previous = currentMap.get(id);
            const next = createNotificationItem(notification);

            if (
                previous
                && previous.dataset.notifSignature
                === next.dataset.notifSignature
            ) {
                fragment.appendChild(previous);
                return;
            }

            if (previous?.querySelector('details[open]')) {
                const details = next.querySelector('details');

                if (details) {
                    details.open = true;
                }
            }

            fragment.appendChild(next);
        });

        body.replaceChildren(fragment);

        updateEmptyState();

        if (!preserveScroll) {
            body.scrollTop = 0;
            return;
        }

        if (anchorKey) {
            const newAnchor = getItems().find(item =>
                item.dataset.notifDedupeKey === anchorKey
            );

            if (newAnchor) {
                const newOffset =
                    newAnchor.getBoundingClientRect().top
                    - body.getBoundingClientRect().top;

                body.scrollTop += newOffset - anchorOffset;
                return;
            }
        }

        body.scrollTop = previousScroll;
    }

    function appendNotifications(notifications) {
        const known = new Set(
            getItems().map(item =>
                item.dataset.notifDedupeKey
            )
        );

        const fragment = document.createDocumentFragment();

        uniqueNotifications(notifications).forEach(notification => {
            const id = String(
                notification.id ?? notification.dedupe_key
            );

            if (known.has(id)) {
                return;
            }

            known.add(id);

            fragment.appendChild(
                createNotificationItem(notification)
            );
        });

        body.appendChild(fragment);

        updateEmptyState();
    }

    function compareNotifications(a, b) {
        const dateA = Date.parse(a.created_at || '') || 0;
        const dateB = Date.parse(b.created_at || '') || 0;

        if (dateA !== dateB) {
            return dateA - dateB;
        }

        const idA = String(a.id || '');
        const idB = String(b.id || '');

        if (idA === idB) return 0;

        return idA > idB ? 1 : -1;
    }

    function trackLatestNotifications(notifications) {
        if (!notifications.length) return;

        const previousNewest = newestKnown;

        if (previousNewest || state.totalCount === 0) {
            notifications.forEach(notification => {
                const id = String(notification.id || '');

                if (
                    id
                    && !knownLatestKeys.has(id)
                    && notification.state === 'unread'
                    && (
                        !previousNewest
                        || compareNotifications(
                            notification,
                            previousNewest
                        ) > 0
                    )
                ) {
                    syncAdminDashboardAppointmentStats(
                        notification
                    );
                }
            });
        }

        notifications.forEach(notification => {
            knownLatestKeys.add(
                String(notification.id || '')
            );
        });

        if (
            !newestKnown
            || compareNotifications(
                notifications[0],
                newestKnown
            ) > 0
        ) {
            newestKnown = notifications[0];
        }

        // Keep the tracking set bounded.
        if (knownLatestKeys.size > 500) {
            knownLatestKeys.clear();

            notifications.forEach(notification => {
                knownLatestKeys.add(
                    String(notification.id || '')
                );
            });
        }
    }

    async function refreshCurrentFilter({
        reset = false,
        firstPayload = null,
    } = {}) {
        const existingItems = reset ? [] : getItems();

        const oldest = existingItems.at(-1);

        const boundary = oldest
            ? {
                id: oldest.dataset.notifDedupeKey,
                created_at: oldest.dataset.notifCreatedAt,
            }
            : null;

        const targetCount = Math.max(
            NOTIFICATION_PAGE_SIZE,
            existingItems.length
        );

        let payload = firstPayload || await fetchNotifications({
            filter: state.filter,
            limit: Math.min(
                targetCount,
                NOTIFICATION_POLL_LIMIT
            ),
        });

        const firstPage = payload;

        let notifications = Array.isArray(payload.notifications)
            ? [...payload.notifications]
            : [];

        const visitedCursors = new Set();

        while (payload.has_more && payload.next_cursor) {
            const last = notifications.at(-1);

            const boundaryReached = !boundary
                || (
                    last
                    && compareNotifications(last, boundary) <= 0
                );

            if (
                boundaryReached
                && notifications.length >= targetCount
            ) {
                break;
            }

            const cursor = payload.next_cursor;

            if (visitedCursors.has(cursor)) {
                break;
            }

            visitedCursors.add(cursor);

            payload = await fetchNotifications({
                filter: state.filter,

                limit: Math.min(
                    NOTIFICATION_POLL_LIMIT,
                    Math.max(
                        NOTIFICATION_PAGE_SIZE,
                        targetCount - notifications.length
                    )
                ),

                cursor,
            });

            const nextItems = Array.isArray(payload.notifications)
                ? payload.notifications
                : [];

            if (!nextItems.length) {
                break;
            }

            notifications.push(...nextItems);
        }

        updateCounts(firstPage);

        renderNotifications(notifications, {
            preserveScroll: !reset,
        });

        updatePagination(payload);
    }

    async function pollNotifications() {
        const isOpen = menu.classList.contains('show');

        const existingCount = getItems().length;

        const limit = state.filter === 'all' && isOpen
            ? Math.min(
                Math.max(
                    existingCount,
                    NOTIFICATION_PAGE_SIZE
                ),
                NOTIFICATION_POLL_LIMIT
            )
            : NOTIFICATION_PAGE_SIZE;

        const latest = await fetchNotifications({
            filter: 'all',
            limit,
        });

        trackLatestNotifications(
            latest.notifications || []
        );

        updateCounts(latest);

        if (!isOpen) {
            return;
        }

        await refreshCurrentFilter({
            firstPayload: state.filter === 'all'
                ? latest
                : null,
        });
    }

    function schedulePoll() {
        window.clearTimeout(pollTimer);

        if (state.stopped) return;

        const delay = document.hidden
            ? BACKGROUND_POLL_INTERVAL
            : POLL_INTERVAL;

        pollTimer = window.setTimeout(runPoll, delay);
    }

    async function runPoll() {
        window.clearTimeout(pollTimer);

        if (state.stopped) return;

        if (pendingTasks > 0) {
            schedulePoll();
            return;
        }

        try {
            await enqueue(pollNotifications);
        } catch (error) {
            console.warn(
                'Notification polling failed.',
                error
            );
        } finally {
            schedulePoll();
        }
    }

    async function loadPreviousNotifications() {
        if (
            state.loading
            || !state.hasMore
            || !state.nextCursor
        ) {
            return;
        }

        setLoading(true);
        setStatus('');

        try {
            await enqueue(async () => {
                const payload = await fetchNotifications({
                    filter: state.filter,
                    limit: NOTIFICATION_PAGE_SIZE,
                    cursor: state.nextCursor,
                });

                updateCounts(payload);

                appendNotifications(
                    payload.notifications || []
                );

                updatePagination(payload);
            });
        } catch (error) {
            setStatus(
                'Unable to load previous notifications. Please try again.'
            );

            console.warn(error);
        } finally {
            setLoading(false);
        }
    }

    async function changeFilter(filter) {
        if (
            !['all', 'unread', 'read'].includes(filter)
            || filter === state.filter
            || state.loading
        ) {
            return;
        }

        setLoading(true);
        setStatus('');

        try {
            await enqueue(async () => {
                const payload = await fetchNotifications({
                    filter,
                    limit: NOTIFICATION_PAGE_SIZE,
                });

                state.filter = filter;

                updateActiveTab();
                updateCounts(payload);

                renderNotifications(
                    payload.notifications || [],
                    {
                        preserveScroll: false,
                    }
                );

                updatePagination(payload);
            });
        } catch (error) {
            setStatus(
                'Unable to load notifications. Please try again.'
            );

            console.warn(error);
        } finally {
            setLoading(false);
        }
    }

    async function submitReadRequest(url) {
        const safeUrl = getSafeUrl(url);

        if (!safeUrl) {
            throw new Error('Invalid notification action.');
        }

        const token = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;

        const response = await fetch(safeUrl, {
            method: 'POST',
            credentials: 'same-origin',

            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || '',
            },

            signal: AbortSignal.timeout(10000),
        });

        if (!response.ok) {
            throw new Error(
                `Notification action failed (${response.status}).`
            );
        }

        const payload = await response.json();

        if (!payload.ok) {
            throw new Error('Notification action was unsuccessful.');
        }

        updateCounts(payload);

        return payload;
    }

    function markItemReadLocally(item) {
        if (!item) return;

        item.dataset.notifState = 'read';

        item.classList.remove('is-unread');
        item.classList.add('is-read');

        delete item.dataset.notifSignature;

        item.querySelector('.header-notif-unread-dot')
            ?.remove();

        item.querySelector('[data-notif-mark-read-form]')
            ?.remove();

        if (state.filter === 'unread') {
            item.remove();
        }
    }

    function isPlainLeftClick(event) {
        return event.button === 0
            && !event.ctrlKey
            && !event.metaKey
            && !event.altKey
            && !event.shiftKey;
    }

    async function openNotification(link, event) {
        if (!isPlainLeftClick(event)) {
            return;
        }

        const item = link.closest('[data-notif-item]');

        const destination = getSafeUrl(link.href);

        if (!item || !destination) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (menu.dataset.notifOpening === 'true') {
            return;
        }

        menu.dataset.notifOpening = 'true';

        try {
            const markReadUrl = item.dataset.notifMarkReadUrl;

            if (
                item.dataset.notifState === 'unread'
                && markReadUrl
            ) {
                await submitReadRequest(markReadUrl);

                markItemReadLocally(item);
            }
        } catch (error) {
            console.warn(
                'Unable to mark notification as read.',
                error
            );
        } finally {
            window.location.assign(destination);
        }
    }

    function syncAdminDashboardAppointmentStats(notification) {
        if (notification.event !== 'appointment.booked') {
            return;
        }

        const counter = document.querySelector(
            '[data-admin-dashboard-counter="appointments-this-month"]'
        );

        if (!counter) return;

        const now = new Date();

        const currentMonth = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: '2-digit',
        }).formatToParts(now);

        const year = currentMonth.find(
            part => part.type === 'year'
        )?.value;

        const month = currentMonth.find(
            part => part.type === 'month'
        )?.value;

        if (
            !notification.appointment_month
            || notification.appointment_month !== `${year}-${month}`
        ) {
            return;
        }

        const currentValue = Number(
            String(counter.textContent)
                .replace(/[^\d]/g, '')
        ) || 0;

        counter.textContent = new Intl.NumberFormat()
            .format(currentValue + 1);

        if (
            !window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches
        ) {
            counter.animate?.(
                [
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.12)' },
                    { transform: 'scale(1)' },
                ],
                {
                    duration: 420,
                    easing: 'ease-out',
                }
            );
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            changeFilter(
                tab.dataset.notifFilter || 'all'
            );
        });
    });

    loadMoreButton?.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();

        loadPreviousNotifications();
    });

    menu.addEventListener('submit', event => {
        const form = event.target;

        if (
            !form.matches(
                '[data-notif-mark-read-form], [data-notif-mark-all-form]'
            )
        ) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (form.dataset.notifSubmitting === 'true') {
            return;
        }

        form.dataset.notifSubmitting = 'true';

        const button = form.querySelector(
            'button[type="submit"]'
        );

        if (button) {
            button.disabled = true;
        }

        const markAll = form.matches(
            '[data-notif-mark-all-form]'
        );

        const item = form.closest('[data-notif-item]');

        const itemId = item?.dataset.notifDedupeKey;

        setStatus('');

        enqueue(async () => {
            await submitReadRequest(form.action);

            if (markAll) {
                getItems().forEach(markItemReadLocally);
            } else {
                const currentItem = getItems().find(node =>
                    node.dataset.notifDedupeKey === itemId
                );

                markItemReadLocally(currentItem);
            }

            updateEmptyState();

            try {
                await refreshCurrentFilter();
            } catch {
                setStatus(
                    'Read status saved. The notification list could not be refreshed.'
                );
            }
        })
            .catch(error => {
                setStatus(
                    'Unable to update notifications. Please try again.'
                );

                console.warn(error);
            })
            .finally(() => {
                delete form.dataset.notifSubmitting;

                if (button) {
                    button.disabled = false;
                }
            });
    });

    menu.addEventListener('click', event => {
        const link = event.target.closest(
            '[data-notif-open-link]'
        );

        if (link && menu.contains(link)) {
            openNotification(link, event);
            return;
        }

        if (
            event.target.closest(
                'button, form, input, a, details, summary, select, textarea'
            )
        ) {
            return;
        }

        const item = event.target.closest(
            '[data-notif-item]'
        );

        if (!item || !isPlainLeftClick(event)) {
            return;
        }

        const destinationLink = item.querySelector(
            '[data-notif-open-link]'
        );

        if (destinationLink) {
            openNotification(destinationLink, event);
        }
    });

    document.addEventListener(
        'notifications:opened',
        () => {
            enqueue(pollNotifications)
                .catch(error => {
                    console.warn(error);
                });
        }
    );

    document.addEventListener(
        'visibilitychange',
        () => {
            if (!document.hidden) {
                runPoll();
            } else {
                schedulePoll();
            }
        }
    );

    window.addEventListener('pagehide', event => {
        if (!event.persisted) {
            state.stopped = true;
            window.clearTimeout(pollTimer);
        }
    });

    window.addEventListener('pageshow', event => {
        if (event.persisted) {
            delete menu.dataset.notifOpening;
            state.stopped = false;
            runPoll();
        }
    });

    updateActiveTab();

    updateCounts({
        unread_count: state.unreadCount,
        read_count: state.readCount,
        total_count: state.totalCount,
    });

    syncPreviousButton();
    updateEmptyState();

    pollTimer = window.setTimeout(runPoll, 2500);
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initNotifications,
        { once: true }
    );
} else {
    initNotifications();
}