window.__PUP_HEADER_JS_ACTIVE = true;

document.addEventListener('DOMContentLoaded', function () {

    const notifBtn = document.getElementById('notifBtn');
    const notifMenu = document.getElementById('notifMenu');
    const notifDropdown = document.getElementById('notifDropdown');

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

    window.closeHeaderMenus = () => { closeAllMenus(); };

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

    if (notifBtn && notifMenu) {
        notifBtn.setAttribute('aria-expanded', 'false');

        notifBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            toggleMenu('notif');

            if (notifMenu.classList.contains('show')) {
                document.dispatchEvent(
                    new CustomEvent('notifications:opened')
                );
            }

        }, true);
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

    function initLogoutConfirmation() {
        const modal =
            document.getElementById(
                'logoutConfirmModal'
            );

        const confirmButton =
            document.getElementById(
                'confirmLogoutBtn'
            );

        if (
            !modal ||
            !confirmButton ||
            modal.dataset.initialized ===
            'true'
        ) {
            return;
        }

        modal.dataset.initialized =
            'true';

        let pendingLogoutForm = null;

        const defaultConfirmContent =
            confirmButton.innerHTML;

        document.addEventListener(
            'submit',
            event => {
                const form =
                    event.target.closest(
                        '.js-logout-form'
                    );

                if (!form) {
                    return;
                }

                if (
                    form.dataset
                        .logoutConfirmed ===
                    'true'
                ) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                pendingLogoutForm =
                    form;

                closeAllMenus();

                window.closeDrawer?.();

                confirmButton.disabled =
                    false;

                confirmButton.innerHTML =
                    defaultConfirmContent;

                window.openModal?.(
                    modal.id
                );
            }
        );

        confirmButton.addEventListener(
            'click',
            () => {
                if (
                    !pendingLogoutForm
                ) {
                    return;
                }

                confirmButton.disabled =
                    true;

                confirmButton.innerHTML = `
                <i
                    class="fa-solid
                           fa-spinner
                           fa-spin">
                </i>

                <span>
                    Logging Out...
                </span>
            `;

                pendingLogoutForm
                    .dataset
                    .logoutConfirmed =
                    'true';

                pendingLogoutForm
                    .submit();
            }
        );

        document.addEventListener(
            'ui-modal:closed',
            event => {
                if (
                    event.detail?.modal !==
                    modal
                ) {
                    return;
                }

                pendingLogoutForm = null;

                confirmButton.disabled =
                    false;

                confirmButton.innerHTML =
                    defaultConfirmContent;
            }
        );
    }

    initLogoutConfirmation();
});
