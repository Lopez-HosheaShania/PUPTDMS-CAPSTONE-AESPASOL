const globalRefreshWatchers = new Map();

function normalizeGlobalRefreshItems(payload, getItems) {
    const items = typeof getItems === 'function'
        ? getItems(payload)
        : Array.isArray(payload)
            ? payload
            : [];

    return Array.isArray(items) ? items : [];
}

function getGlobalRefreshNoticeId(key = 'global') {
    return `globalRefreshNotice-${String(key || 'global')}`;
}

function removeGlobalRefreshNotice(key = 'global') {
    document.getElementById(getGlobalRefreshNoticeId(key))?.remove();
}

function collectGlobalLiveItemIds(
    root = document
) {
    const scope =
        root?.querySelectorAll ?
            root :
            document;

    return new Set(
        Array.from(
            scope.querySelectorAll(
                '[data-live-item-id]'
            )
        )
            .map(element =>
                String(
                    element.dataset
                        .liveItemId || ''
                ).trim()
            )
            .filter(Boolean)
    );
}

function highlightGlobalLiveItems(
    root = document,
    ids = [],
    options = {}
) {
    const scope =
        root?.querySelectorAll ?
            root :
            document;

    const targetIds =
        new Set(
            Array.from(ids || [])
                .map(id =>
                    String(id)
                )
        );

    if (!targetIds.size) {
        return;
    }

    const insertDuration =
        Number(
            options.insertDuration
        ) || 420;

    const highlightDuration =
        Number(
            options.highlightDuration
        ) || 5000;

    scope.querySelectorAll(
        '[data-live-item-id]'
    )
        .forEach(element => {
            const id =
                String(
                    element.dataset
                        .liveItemId || ''
                );

            if (!targetIds.has(id)) {
                return;
            }

            element.classList.remove(
                'global-live-insert',
                'global-live-highlight'
            );

            void element.offsetWidth;

            element.classList.add(
                'global-live-insert',
                'global-live-highlight'
            );

            window.setTimeout(
                () => {
                    element.classList.remove(
                        'global-live-insert'
                    );
                },
                insertDuration
            );

            window.setTimeout(
                () => {
                    element.classList.remove(
                        'global-live-highlight'
                    );
                },
                highlightDuration
            );
        });
}

function initGlobalRefreshWatcher(config = {}) {
    const key = config.key || 'global';

    if (globalRefreshWatchers.has(key)) {
        const existing = globalRefreshWatchers.get(key);
        existing.sync(config.initialItems || []);
        return existing;
    }

    const interval = Number(config.interval) || 15000;
    const getItems = config.getItems || ((payload) => Array.isArray(payload) ? payload : []);
    const getItemId = config.getItemId || ((item) => item?.id);
    const itemLabel = config.itemLabel || 'item';
    const anchorSelector = config.anchorSelector || '.table-card';
    const noticeId = getGlobalRefreshNoticeId(key);

    let pendingPayload = null;
    let pendingItems = [];
    let timer = null;
    let checking = false;
    let cursor = config.initialCursor ?? null;
    let requestController = null;
    let lifecycleVersion = 0;

    let knownIds = new Set(
        normalizeGlobalRefreshItems(config.initialItems || [], getItems)
            .map(getItemId)
            .filter((id) => id !== null && id !== undefined)
            .map(String)
    );

    const countLabel = (count) => `${itemLabel}${count === 1 ? '' : 's'}`;

    const showNotice = (count) => {
        let notice = document.getElementById(noticeId);

        if (!notice) {
            notice = document.createElement('div');
            notice.id = noticeId;
            notice.className = 'global-refresh-notice';

            const anchor = document.querySelector(anchorSelector);
            anchor?.parentNode?.insertBefore(notice, anchor);
        }

        const title = typeof config.title === 'function'
            ? config.title(count)
            : config.title || `${count} new ${countLabel(count)} available`;

        const subtitle = typeof config.subtitle === 'function'
            ? config.subtitle(count)
            : config.subtitle || `Refresh to see the latest ${countLabel(count)}.`;

        notice.innerHTML = `
            <div class="global-refresh-copy">
                <span class="global-refresh-icon">
                    <i class="fa-solid fa-rotate"></i>
                </span>

                <div>
                    <strong>${title}</strong>
                    <small>${subtitle}</small>
                </div>
            </div>

            <button type="button" class="global-refresh-btn">
                <i class="fa-solid fa-arrows-rotate"></i>
                Refresh
            </button>
        `;

        notice.querySelector('.global-refresh-btn')?.addEventListener('click', () => {
            controller.apply();
        });
    };

    const controller = {
        sync(source = []) {
            knownIds = new Set(
                normalizeGlobalRefreshItems(
                    source,
                    getItems
                )
                    .map(getItemId)
                    .filter(
                        id =>
                            id !== null &&
                            id !== undefined
                    )
                    .map(String)
            );

            if (
                typeof config.getCursor ===
                'function'
            ) {
                const nextCursor =
                    config.getCursor(
                        source
                    );

                if (
                    nextCursor !== null &&
                    nextCursor !== undefined &&
                    nextCursor !== ''
                ) {
                    cursor =
                        nextCursor;
                }
            }

            pendingPayload = null;
            pendingItems = [];

            removeGlobalRefreshNotice(
                key
            );
        },

        getCursor() {
            return cursor;
        },

        async check() {
            const checkVersion = lifecycleVersion;

            const url =
                typeof config.url ===
                    'function'
                    ? config.url({
                        cursor,
                        key,
                        controller,
                    })
                    : config.url;

            if (
                !url ||
                checking
            ) {
                return;
            }

            checking = true;
            requestController = new AbortController();
            const activeRequest = requestController;

            try {
                const response =
                    await fetch(
                        url,
                        {
                            cache: 'no-store',
                            signal: activeRequest.signal,

                            headers: {
                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest'
                            }
                        }
                    );

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();

                if (
                    checkVersion !==
                    lifecycleVersion
                ) {
                    return;
                }

                const incoming =
                    normalizeGlobalRefreshItems(
                        payload,
                        getItems
                    );

                const newItems =
                    incoming.filter(
                        item => {
                            const id =
                                getItemId(item);

                            return (
                                id !== null &&
                                id !== undefined &&
                                !knownIds.has(
                                    String(id)
                                )
                            );
                        }
                    );

                const shouldApplyWithoutItems =
                    typeof config.shouldApply ===
                    'function' &&
                    config.shouldApply(
                        payload,
                        {
                            newItems,
                            cursor,
                        }
                    );

                if (
                    !newItems.length &&
                    !shouldApplyWithoutItems
                ) {
                    return;
                }

                pendingPayload = payload;
                pendingItems = newItems;

                if (
                    config.autoApply ===
                    true
                ) {
                    await controller.apply();
                    return;
                }

                showNotice(newItems.length);

            } catch (error) {
                if (
                    error.name ===
                    'AbortError'
                ) {
                    return;
                }

                console.warn(
                    `${key} refresh check failed:`,
                    error
                );

            } finally {
                if (
                    requestController ===
                    activeRequest
                ) {
                    requestController =
                        null;
                }

                checking = false;
            }
        },

        async apply() {
            if (!pendingPayload) {
                return;
            }

            const payload = pendingPayload;
            const newItems = pendingItems.slice();
            const applyVersion = lifecycleVersion;

            try {
                if (
                    typeof config.onRefresh ===
                    'function'
                ) {
                    await config.onRefresh(
                        payload, newItems,
                        {
                            cursor,

                            isCurrent() {
                                return (
                                    applyVersion ===
                                    lifecycleVersion
                                );
                            },
                        }
                    );
                }

                if (
                    applyVersion !==
                    lifecycleVersion
                ) {
                    return;
                }

                this.sync(payload);

                if (
                    config.toast !== false &&
                    typeof window.showToast ===
                    'function'
                ) {
                    window.showToast({
                        type:
                            config.toast?.type ||
                            'info',

                        title:
                            config.toast?.title ||
                            'Updated',

                        message:
                            config.toast?.message ||
                            'Latest records are now shown.',

                        duration:
                            config.toast?.duration ||
                            3500
                    });
                }

            } catch (error) {
                console.error(
                    `${key} refresh failed:`,
                    error
                );

                window.showToast?.({
                    type: 'error',
                    title: 'Refresh failed',
                    message:
                        'Unable to load the latest records.'
                });
            }
        },

        start() {
            lifecycleVersion += 1;

            if (timer) {
                clearInterval(
                    timer
                );
            }

            requestController
                ?.abort();

            timer =
                setInterval(
                    () =>
                        this.check(),
                    interval
                );
        },

        stop() {
            lifecycleVersion += 1;

            if (timer) {
                clearInterval(
                    timer
                );
            }

            timer = null;

            requestController
                ?.abort();

            requestController =
                null;

            pendingPayload =
                null;

            pendingItems = [];

            removeGlobalRefreshNotice(
                key
            );
        }
    };

    globalRefreshWatchers.set(key, controller);

    if (config.autoStart !== false) {
        controller.start();
    }

    return controller;
}

function syncGlobalRefreshWatcher(key = 'global', source = []) {
    globalRefreshWatchers.get(key)?.sync(source);
}

window.initGlobalRefreshWatcher = initGlobalRefreshWatcher;
window.syncGlobalRefreshWatcher = syncGlobalRefreshWatcher;
window.removeGlobalRefreshNotice = removeGlobalRefreshNotice;

window.GlobalLiveUpdate = {
    collectIds: collectGlobalLiveItemIds,
    highlightItems: highlightGlobalLiveItems,
};