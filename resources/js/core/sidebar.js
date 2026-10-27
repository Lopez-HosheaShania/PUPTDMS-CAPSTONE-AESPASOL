function getCurrentRole() {
    const sidebar = document.getElementById('sidebar');

    if (
        document.body.classList.contains('role-admin') ||
        sidebar?.classList.contains('sidebar-admin')
    ) {
        return 'admin';
    }

    if (
        document.body.classList.contains('role-dentist') ||
        sidebar?.classList.contains('sidebar-dentist')
    ) {
        return 'dentist';
    }

    if (
        document.body.classList.contains('role-patient') ||
        sidebar?.classList.contains('sidebar-patient')
    ) {
        return 'patient';
    }

    return 'global';
}

function getSidebarStorageKey() {
    const role = getCurrentRole();

    return {
        admin: 'adminSidebarCollapsed',
        dentist: 'dentistSidebarCollapsed',
        patient: 'patientSidebarCollapsed',
        global: 'sidebarCollapsed',
    }[role] || 'sidebarCollapsed';
}

function getSidebarScrollStorageKey() {
    const role = getCurrentRole();

    return {
        admin: 'adminSidebarScrollTop',
        dentist: 'dentistSidebarScrollTop',
        patient: 'patientSidebarScrollTop',
        global: 'sidebarScrollTop',
    }[role] || 'sidebarScrollTop';
}

function initSidebarScrollMemory() {
    const sidebar = document.getElementById('sidebar');
    const sidebarInner = sidebar?.querySelector('.sidebar-inner');

    if (!sidebar || !sidebarInner) return;

    const storageKey = getSidebarScrollStorageKey();

    let isRestoring = true;
    let saveTimer = null;

    const getSavedScroll = () => {
        const saved = Number(localStorage.getItem(storageKey) || 0);
        return Number.isFinite(saved) && saved > 0 ? saved : 0;
    };

    const restoreSidebarScroll = () => {
        const savedScroll = getSavedScroll();

        if (savedScroll > 0) {
            sidebarInner.scrollTop = savedScroll;
        }
    };

    const saveSidebarScroll = () => {
        if (isRestoring) return;

        clearTimeout(saveTimer);

        saveTimer = setTimeout(() => {
            localStorage.setItem(storageKey, String(sidebarInner.scrollTop || 0));
        }, 80);
    };

    restoreSidebarScroll();

    requestAnimationFrame(() => {
        restoreSidebarScroll();

        isRestoring = false;

        document.documentElement
            .classList
            .remove(
                'sidebar-preload',
                'sidebar-collapsed-init'
            );
    });

    sidebarInner.addEventListener('scroll', saveSidebarScroll, { passive: true });

    document.querySelectorAll('#sidebar a').forEach(link => {
        link.addEventListener('click', () => {
            localStorage.setItem(storageKey, String(sidebarInner.scrollTop || 0));
        });
    });

    window.addEventListener('beforeunload', () => {
        localStorage.setItem(storageKey, String(sidebarInner.scrollTop || 0));
    });

    window.addEventListener('pagehide', () => {
        localStorage.setItem(storageKey, String(sidebarInner.scrollTop || 0));
    });
}

function applySidebarState(isCollapsed) {
    const sidebar = document.getElementById('sidebar');

    if (!sidebar) return;

    const mainContent = document.getElementById('mainContent');
    const currentRole = getCurrentRole();
    const collapsed = Boolean(isCollapsed);

    if (window.innerWidth <= 767 && currentRole !== 'patient') {
        sidebar.classList.remove('collapsed');
        document.body.classList.remove('sidebar-collapsed');

        document.querySelectorAll('#sidebarIcon, #sidebarToggleIcon').forEach(icon => {
            icon.className = 'fa-solid fa-xmark';
        });

        return;
    }

    sidebar.classList.toggle('collapsed', collapsed);
    document.body.classList.toggle('sidebar-collapsed', collapsed);

    if (mainContent) {
        mainContent.classList.toggle('sidebar-content-collapsed', collapsed);
    }

    document.querySelectorAll('#sidebarIcon, #sidebarToggleIcon').forEach(icon => {
        icon.className = collapsed
            ? 'fa-solid fa-bars'
            : 'fa-solid fa-xmark';
    });

    document.querySelectorAll(
        '#sidebarToggleBtn, #desktopSidebarToggle, [data-sidebar-toggle]'
    ).forEach(button => {
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        button.setAttribute(
            'aria-label',
            collapsed ? 'Expand sidebar' : 'Collapse sidebar'
        );
    });

    window.dispatchEvent(
        new CustomEvent(
            'sidebar:statechange',
            {
                detail: {
                    collapsed
                }
            }
        )
    );
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');

    if (!sidebar) return;

    const nextState = !sidebar.classList.contains('collapsed');

    applySidebarState(nextState);

    localStorage.setItem(
        getSidebarStorageKey(),
        nextState ? '1' : '0'
    );
}

function initGlobalSidebar() {
    const sidebar = document.getElementById('sidebar');

    if (!sidebar) {
        document.documentElement.classList.remove(
            'sidebar-preload',
            'sidebar-collapsed-init'
        );

        return;
    }

    const storageKey = getSidebarStorageKey();
    const savedState =
        localStorage.getItem(storageKey) === '1';

    applySidebarState(savedState);

    document.querySelectorAll(
        '#sidebarToggleBtn, #desktopSidebarToggle, [data-sidebar-toggle]'
    ).forEach(button => {
        if (
            button.dataset.sidebarInitialized ===
            'true'
        ) {
            return;
        }

        button.dataset.sidebarInitialized = 'true';

        if (!button.hasAttribute('data-ui-click')) {
            button.addEventListener(
                'click',
                event => {
                    event.preventDefault();
                    event.stopPropagation();

                    toggleSidebar();
                }
            );
        }
    });
}

window.addEventListener(
    'pageshow',
    event => {
        if (!event.persisted) {
            return;
        }
        const sidebar =
            document.getElementById('sidebar');

        if (!sidebar) return;

        const savedState =
            localStorage.getItem(
                getSidebarStorageKey()
            ) === '1';

        applySidebarState(savedState);

        document.documentElement.classList.remove(
            'sidebar-preload',
            'sidebar-collapsed-init'
        );
    });

function initSidebarGroupNavigation() {
    const sidebar =
        document.querySelector(
            '#sidebar.sidebar-grouped'
        );

    if (!sidebar) return;

    const groups =
        Array.from(
            sidebar.querySelectorAll(
                '.nav-group'
            )
        );

    const isCollapsed = () =>
        sidebar.classList.contains(
            'collapsed'
        ) ||
        document.body.classList.contains(
            'sidebar-collapsed'
        );

    const getTrigger = group =>
        group.querySelector(
            '[data-sidebar-group-toggle]'
        );

    const syncAria = () => {
        groups.forEach(group => {
            const visible =
                isCollapsed()
                    ? group.classList.contains(
                        'is-flyout-open'
                    )
                    : group.classList.contains(
                        'is-expanded'
                    );

            getTrigger(group)
                ?.setAttribute(
                    'aria-expanded',
                    visible
                        ? 'true'
                        : 'false'
                );
        });
    };

    const closeFlyouts = () => {
        sidebar.classList.remove(
            'has-flyout-open'
        );

        groups.forEach(group => {
            group.classList.remove(
                'is-flyout-open'
            );
        });

        syncAria();
    };

    const openFlyout = targetGroup => {
        sidebar.classList.add(
            'has-flyout-open'
        );

        groups.forEach(group => {
            group.classList.toggle(
                'is-flyout-open',
                group === targetGroup
            );
        });

        syncAria();
    };

    const setExpandedGroup =
        targetGroup => {

            groups.forEach(group => {
                group.classList.toggle(
                    'is-expanded',
                    group === targetGroup
                );
            });

            syncAria();
        };

    const ensureActiveGroupOpen = () => {
        if (isCollapsed()) {
            return;
        }

        const hasExpanded =
            groups.some(group =>
                group.classList.contains(
                    'is-expanded'
                )
            );

        if (!hasExpanded) {
            const activeGroup =
                groups.find(group =>
                    getTrigger(group)
                        ?.classList
                        .contains(
                            'active-group'
                        )
                );

            if (activeGroup) {
                activeGroup.classList.add(
                    'is-expanded'
                );
            }
        }

        syncAria();
    };

    groups.forEach(group => {
        const trigger =
            getTrigger(group);

        const panel =
            group.querySelector(
                '.group-body'
            );

        if (
            !trigger ||
            trigger.dataset
                .groupClickInitialized ===
            'true'
        ) {
            return;
        }

        trigger.dataset
            .groupClickInitialized =
            'true';

        trigger.addEventListener(
            'click',
            event => {
                event.preventDefault();
                event.stopPropagation();

                if (isCollapsed()) {
                    const alreadyOpen =
                        group.classList
                            .contains(
                                'is-flyout-open'
                            );

                    closeFlyouts();

                    if (!alreadyOpen) {
                        openFlyout(
                            group
                        );
                    }

                    return;
                }

                const alreadyExpanded =
                    group.classList
                        .contains(
                            'is-expanded'
                        );

                setExpandedGroup(
                    alreadyExpanded
                        ? null
                        : group
                );
            }
        );

        panel?.addEventListener(
            'click',
            event => {
                event.stopPropagation();
            }
        );
    });

    document.addEventListener(
        'click',
        event => {
            if (
                !sidebar.contains(
                    event.target
                )
            ) {
                closeFlyouts();
            }
        }
    );

    document.addEventListener(
        'keydown',
        event => {
            if (
                event.key === 'Escape'
            ) {
                closeFlyouts();
            }
        }
    );

    window.addEventListener(
        'sidebar:statechange',
        () => {
            closeFlyouts();
            ensureActiveGroupOpen();
        }
    );

    window.addEventListener(
        'resize',
        () => {
            if (!isCollapsed()) {
                closeFlyouts();
                ensureActiveGroupOpen();
            }
        }
    );

    ensureActiveGroupOpen();

    window.closeSidebarGroups = closeFlyouts;
}

function initMobileDrawerGroups() {
    const drawer =
        document.getElementById(
            'mobileDrawer'
        );

    if (!drawer) return;

    const groups =
        Array.from(
            drawer.querySelectorAll(
                '.drawer-group'
            )
        );

    const setExpandedGroup =
        targetGroup => {

            groups.forEach(group => {
                const expanded =
                    group ===
                    targetGroup;

                group.classList.toggle(
                    'is-expanded',
                    expanded
                );

                group.querySelector(
                    '[data-drawer-group-toggle]'
                )?.setAttribute(
                    'aria-expanded',
                    expanded
                        ? 'true'
                        : 'false'
                );
            });
        };

    groups.forEach(group => {
        const trigger =
            group.querySelector(
                '[data-drawer-group-toggle]'
            );

        if (!trigger) return;

        trigger.addEventListener(
            'click',
            () => {
                const alreadyExpanded =
                    group.classList
                        .contains(
                            'is-expanded'
                        );

                setExpandedGroup(
                    alreadyExpanded
                        ? null
                        : group
                );
            }
        );
    });
}

function openDrawer() {
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');

    if (!drawer || !overlay) return;

    overlay.classList.add('open');
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDrawer() {
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');

    if (!drawer || !overlay) return;

    drawer.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
}

function initMobileDrawerControls() {
    const drawerButtons = document.querySelectorAll(
        '#mobileMenuBtn, [data-mobile-menu-toggle], [data-drawer-toggle]'
    );

    drawerButtons.forEach(button => {
        if (button.dataset.drawerToggleInitialized === 'true') return;

        button.dataset.drawerToggleInitialized = 'true';

        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            const drawer = document.getElementById('mobileDrawer');

            if (drawer?.classList.contains('open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
    });

    document.getElementById('mobileDrawerOverlay')?.addEventListener('click', closeDrawer);

    document.querySelectorAll('[data-drawer-close]').forEach(button => {
        if (button.dataset.drawerCloseInitialized === 'true') return;

        button.dataset.drawerCloseInitialized = 'true';

        button.addEventListener('click', event => {
            event.preventDefault();
            closeDrawer();
        });
    });
}

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initGlobalSidebar();
        initSidebarGroupNavigation();
        initSidebarScrollMemory();
        initMobileDrawerControls();
        initMobileDrawerGroups();
    }
);

document.addEventListener(
    'keydown',
    event => {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    }
);

window.openDrawer = openDrawer;
window.closeDrawer = closeDrawer;
window.toggleSidebar = toggleSidebar;
window.applySidebarState = applySidebarState;
