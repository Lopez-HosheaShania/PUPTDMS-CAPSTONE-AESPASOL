function initGlobalViewToggles(root = document) {
    const scope =
        root &&
            typeof root.querySelectorAll === 'function'
            ? root
            : document;

    scope
        .querySelectorAll('[data-global-view-toggle]')
        .forEach(toggle => {
            if (
                toggle.dataset.globalViewInitialized ===
                'true'
            ) {
                return;
            }

            const buttons = Array.from(
                toggle.querySelectorAll(
                    '[data-view-mode]'
                )
            );

            if (!buttons.length) return;

            toggle.dataset.globalViewInitialized =
                'true';

            const pageRoot =
                document.querySelector(
                    toggle.dataset.viewRoot ||
                    '#mainContent'
                );

            const listView =
                toggle.dataset.listView
                    ? document.querySelector(
                        toggle.dataset.listView
                    )
                    : null;

            const gridView =
                toggle.dataset.gridView
                    ? document.querySelector(
                        toggle.dataset.gridView
                    )
                    : null;

            const storageKey =
                toggle.dataset.storageKey ||
                'ViewToggleMode';

            const setMode = (
                mode,
                options = {}
            ) => {
                const nextMode =
                    mode === 'grid'
                        ? 'grid'
                        : 'list';

                const isGrid =
                    nextMode === 'grid';

                if (listView) {
                    listView.hidden = isGrid;
                }

                if (gridView) {
                    gridView.hidden = !isGrid;
                }

                pageRoot?.classList.toggle(
                    'mode-grid',
                    isGrid
                );

                pageRoot?.classList.toggle(
                    'mode-list',
                    !isGrid
                );

                buttons.forEach(button => {
                    const active =
                        button.dataset.viewMode ===
                        nextMode;

                    button.classList.toggle(
                        'active',
                        active
                    );

                    button.classList.toggle(
                        'is-active',
                        active
                    );

                    button.setAttribute(
                        'aria-pressed',
                        active ? 'true' : 'false'
                    );
                });

                toggle.dataset.currentView =
                    nextMode;

                if (
                    options.persist !== false
                ) {
                    localStorage.setItem(
                        storageKey,
                        nextMode
                    );
                }

                toggle.dispatchEvent(
                    new CustomEvent(
                        'global-view-change',
                        {
                            bubbles: true,
                            detail: {
                                mode: nextMode
                            }
                        }
                    )
                );
            };

            toggle.__setGlobalViewMode =
                setMode;

            toggle.__getGlobalViewMode =
                () =>
                    toggle.dataset.currentView ||
                    localStorage.getItem(
                        storageKey
                    ) ||
                    'list';

            buttons.forEach(button => {
                button.addEventListener(
                    'click',
                    () => {
                        setMode(
                            button.dataset.viewMode
                        );
                    }
                );
            });

            const savedMode =
                localStorage.getItem(
                    storageKey
                );

            setMode(
                savedMode || 'list',
                {
                    persist: false
                }
            );
        });
}

function setGlobalViewMode(
    toggleOrId,
    mode,
    options = {}
) {
    const toggle =
        typeof toggleOrId === 'string'
            ? document.getElementById(
                toggleOrId
            ) ||
            document.querySelector(
                toggleOrId
            )
            : toggleOrId;

    if (!toggle) return;

    if (
        typeof toggle.__setGlobalViewMode !==
        'function'
    ) {
        initGlobalViewToggles(document);
    }

    toggle.__setGlobalViewMode?.(
        mode,
        options
    );
}

function getGlobalViewMode(toggleOrId) {
    const toggle =
        typeof toggleOrId === 'string'
            ? document.getElementById(
                toggleOrId
            ) ||
            document.querySelector(
                toggleOrId
            )
            : toggleOrId;

    return (
        toggle?.__getGlobalViewMode?.() ||
        toggle?.dataset.currentView ||
        'list'
    );
}

initGlobalViewToggles();

window.initGlobalViewToggles = initGlobalViewToggles;
window.setGlobalViewMode = setGlobalViewMode;
window.getGlobalViewMode = getGlobalViewMode;