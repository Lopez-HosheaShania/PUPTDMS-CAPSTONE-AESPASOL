window.__PUP_HEADER_JS_ACTIVE = true;

document.addEventListener('DOMContentLoaded', function () {
    function updateSidebarToggleIcon() {
        const icon = document.getElementById('sidebarToggleIcon');
        if (!icon) return;

        if (document.body.classList.contains('sidebar-collapsed')) {
            icon.classList.remove('fa-xmark');
            icon.classList.add('fa-bars');
        } else {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-xmark');
        }
    }

    const body = document.body;
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
    const desktopBreakpoint = window.matchMedia('(min-width: 1200px)');

    if (desktopSidebarToggle) {
        desktopSidebarToggle.addEventListener('click', function (e) {
            if (!desktopBreakpoint.matches) return;
            e.preventDefault();
            toggleDesktopSidebar();
        });
    }

    function getRoleFromBody() {
        if (body.classList.contains('role-dentist')) return 'dentist';
        if (body.classList.contains('role-patient')) return 'patient';
        if (body.classList.contains('role-admin')) return 'admin';
        return 'default';
    }

    function getSidebarStorageKey() {
        return `sidebar-collapsed-${getRoleFromBody()}`;
    }

    function applySidebarStateFromStorage() {
        if (!desktopBreakpoint.matches) return;
        const saved = localStorage.getItem(getSidebarStorageKey());
        body.classList.toggle('sidebar-collapsed', saved === 'true');
    }

    function toggleDesktopSidebar() {
        if (!desktopBreakpoint.matches) return;

        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem(
            getSidebarStorageKey(),
            body.classList.contains('sidebar-collapsed') ? 'true' : 'false'
        );

        updateSidebarToggleIcon();
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function (e) {
            if (desktopBreakpoint.matches) {
                e.preventDefault();
                toggleDesktopSidebar();
                return;
            }

            if (body.classList.contains('role-dentist') && typeof openDrawer === 'function') {
                openDrawer();
            }
        });
    }

    applySidebarStateFromStorage();
    updateSidebarToggleIcon();

    window.addEventListener('resize', function () {
        if (desktopBreakpoint.matches) {
            applySidebarStateFromStorage();
        } else {
            body.classList.remove('sidebar-collapsed');
        }
    });

    const notifBtn = document.getElementById('notifBtn');
    const notifMenu = document.getElementById('notifMenu');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifTabs = notifMenu ? notifMenu.querySelectorAll('[data-notif-filter]') : [];
    const notifItems = notifMenu ? notifMenu.querySelectorAll('[data-notif-item]') : [];
    const notifFilterEmpty = notifMenu ? notifMenu.querySelector('.header-notif-filter-empty') : null;
    const notifBadge = notifDropdown ? notifDropdown.querySelector('[data-notif-badge]') : null;
    const unreadPill = notifMenu ? notifMenu.querySelector('[data-notif-unread-pill]') : null;
    const totalPill = notifMenu ? notifMenu.querySelector('[data-notif-total-pill]') : null;
    const markAllForm = notifMenu ? notifMenu.querySelector('[data-notif-mark-all-form]') : null;
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    const userDropdown = document.getElementById('userDropdown');

    function setButtonState(button, isActive) {
        if (!button) return;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-expanded', isActive ? 'true' : 'false');
    }

    function openMenu(menu, button) {
        if (!menu) return;
        menu.classList.add('show');
        setButtonState(button, true);
    }

    function closeMenu(menu, button) {
        if (!menu) return;
        menu.classList.remove('show');
        setButtonState(button, false);
    }

    function closeAllMenus(except = null) {
        if (except !== 'notif') closeMenu(notifMenu, notifBtn);
        if (except !== 'user') closeMenu(userMenu, userBtn);
    }

    function toggleMenu(type) {
        if (type === 'notif' && notifMenu) {
            const willOpen = !notifMenu.classList.contains('show');
            closeAllMenus('notif');
            if (willOpen) {
                openMenu(notifMenu, notifBtn);
            } else {
                closeMenu(notifMenu, notifBtn);
            }
        }

        if (type === 'user' && userMenu) {
            const willOpen = !userMenu.classList.contains('show');
            closeAllMenus('user');
            if (willOpen) {
                openMenu(userMenu, userBtn);
            } else {
                closeMenu(userMenu, userBtn);
            }
        }
    }

    function setNotifFilter(filter) {
        if (!notifMenu) return;

        notifTabs.forEach(function (button) {
            const isActive = button.dataset.notifFilter === filter;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        let visibleCount = 0;

        notifItems.forEach(function (item) {
            const state = item.dataset.notifState || 'unread';
            const isVisible = filter === 'all' || filter === state;

            item.classList.toggle('hidden', !isVisible);

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (notifFilterEmpty) {
            notifFilterEmpty.hidden = notifItems.length === 0 || visibleCount > 0;
        }
    }

    function getNotifCounts() {
        let unread = 0;
        let read = 0;

        notifItems.forEach(function (item) {
            if ((item.dataset.notifState || 'unread') === 'unread') {
                unread += 1;
            } else {
                read += 1;
            }
        });

        return {
            unread,
            read,
            total: unread + read,
        };
    }

    function updateNotifSummaryUi() {
        const counts = getNotifCounts();

        if (notifBadge) {
            if (counts.unread > 0) {
                notifBadge.textContent = counts.unread;
                notifBadge.hidden = false;
            } else {
                notifBadge.hidden = true;
            }
        }

        if (unreadPill) {
            unreadPill.textContent = counts.unread + ' unread';
        }

        if (totalPill) {
            totalPill.textContent = counts.total + ' total';
        }

        const allCount = notifMenu ? notifMenu.querySelector('[data-notif-tab-count="all"]') : null;
        const unreadCount = notifMenu ? notifMenu.querySelector('[data-notif-tab-count="unread"]') : null;
        const readCount = notifMenu ? notifMenu.querySelector('[data-notif-tab-count="read"]') : null;

        if (allCount) allCount.textContent = counts.total;
        if (unreadCount) unreadCount.textContent = counts.unread;
        if (readCount) readCount.textContent = counts.read;

        if (markAllForm) {
            markAllForm.hidden = counts.unread === 0;
        }

        const activeFilterBtn = notifMenu ? notifMenu.querySelector('.header-notif-tab.is-active') : null;
        const activeFilter = activeFilterBtn ? activeFilterBtn.dataset.notifFilter : 'all';
        setNotifFilter(activeFilter || 'all');
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

    async function submitNotifFormAjax(form) {
        if (!form || !form.action) return false;

        const formData = new FormData(form);
        return submitNotifReadRequest(form.action, formData);
    }

    async function submitNotifReadRequest(url, body = null) {
        if (!url) return false;

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        };

        if (csrfTokenMeta && csrfTokenMeta.content) {
            headers['X-CSRF-TOKEN'] = csrfTokenMeta.content;
        }

        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers,
            body,
        });

        return response.ok;
    }

    function isPlainLeftClick(event) {
        return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
    }

    async function markItemAsReadBeforeOpen(link, event) {
        const item = link.closest('[data-notif-item]');
        const markReadUrl = item ? item.dataset.notifMarkReadUrl : null;

        if (!item || !markReadUrl || (item.dataset.notifState || 'unread') !== 'unread') {
            return;
        }

        if (!isPlainLeftClick(event)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const destination = link.href;

        try {
            await submitNotifReadRequest(markReadUrl);
            markItemAsRead(item);
            updateNotifSummaryUi();
        } catch (_err) {
            // Keep navigation working even if the read-state request fails.
        } finally {
            window.location.href = destination;
        }
    }

    if (notifBtn && notifMenu) {
        notifBtn.setAttribute('aria-expanded', 'false');
        notifBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            toggleMenu('notif');
        }, true);

        notifTabs.forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                setNotifFilter(button.dataset.notifFilter || 'all');
            });
        });

        notifMenu.addEventListener('submit', async function (e) {
            const form = e.target;

            if (!form.matches('[data-notif-mark-read-form], [data-notif-mark-all-form]')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const success = await submitNotifFormAjax(form);

                if (!success) {
                    form.submit();
                    return;
                }

                if (form.matches('[data-notif-mark-read-form]')) {
                    markItemAsRead(form.closest('[data-notif-item]'));
                } else if (form.matches('[data-notif-mark-all-form]')) {
                    notifItems.forEach(function (item) {
                        markItemAsRead(item);
                    });
                }

                updateNotifSummaryUi();
            } catch (_err) {
                form.submit();
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });

        notifMenu.addEventListener('click', function (e) {
            const openLink = e.target.closest('[data-notif-open-link]');

            if (!openLink || !notifMenu.contains(openLink)) {
                return;
            }

            markItemAsReadBeforeOpen(openLink, e);
        });

        setNotifFilter('all');
        updateNotifSummaryUi();
    }

    if (userBtn && userMenu) {
        userBtn.setAttribute('aria-expanded', 'false');
        userBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            toggleMenu('user');
        }, true);
    }

    document.addEventListener('click', function (e) {
        const clickedNotif = notifDropdown && notifDropdown.contains(e.target);
        const clickedUser = userDropdown && userDropdown.contains(e.target);

        if (!clickedNotif && !clickedUser) {
            closeAllMenus();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllMenus();
        }
    });

    const themeCheckbox = document.getElementById('themeSwitchCheckbox');
    const themeIcon = document.getElementById('themeIcon');

    if (themeCheckbox && themeIcon) {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const currentTheme = savedTheme || (prefersDark ? 'dark' : 'light');

        if (currentTheme === 'dark') {
            themeCheckbox.checked = true;
            document.documentElement.setAttribute('data-theme', 'dark');
            themeIcon.className = 'fa-solid fa-moon text-gray-400 text-base';
        } else {
            themeCheckbox.checked = false;
            document.documentElement.setAttribute('data-theme', 'light');
            themeIcon.className = 'fa-regular fa-sun text-gray-400 text-base';
        }

        themeCheckbox.addEventListener('change', (e) => {
            if (e.target.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeIcon.className = 'fa-solid fa-moon text-gray-400 text-base';
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                themeIcon.className = 'fa-regular fa-sun text-gray-400 text-base';
            }
        });
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
            if (localStorage.getItem('theme')) return;

            const theme = e.matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
            themeCheckbox.checked = theme === 'dark';
            themeIcon.className = theme === 'dark'
                ? 'fa-solid fa-moon text-gray-400 text-base'
                : 'fa-regular fa-sun text-gray-400 text-base';
        });
    }
});
