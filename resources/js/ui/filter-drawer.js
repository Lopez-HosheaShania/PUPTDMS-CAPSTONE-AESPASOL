export function openFilterDrawer(
    panelId = 'filterModal',
    overlayId = null
) {
    const panel =
        document.getElementById(panelId);

    if (!panel) return;

    const overlay =
        overlayId
            ? document.getElementById(
                overlayId
            )
            : panel.querySelector(
                '.filter-drawer-overlay'
            );

    document.documentElement
        .classList.add(
            'filter-lock'
        );

    document.body
        .classList.add(
            'filter-lock'
        );

    panel.classList.remove(
        'closing'
    );

    panel.classList.add(
        'open'
    );

    panel.setAttribute(
        'aria-hidden',
        'false'
    );

    const body =
        panel.querySelector(
            '.filter-drawer-body'
        );

    if (body) {
        body.scrollTop = 0;
    }

    overlay?.classList.add(
        'open'
    );
}

export function closeFilterDrawer(
    panelId = 'filterModal',
    overlayId = null
) {
    const panel =
        document.getElementById(panelId);

    if (!panel) return;

    const overlay =
        overlayId
            ? document.getElementById(
                overlayId
            )
            : panel.querySelector(
                '.filter-drawer-overlay'
            );

    overlay?.classList.remove(
        'open'
    );

    panel.classList.remove(
        'open'
    );

    panel.classList.add(
        'closing'
    );

    panel.setAttribute(
        'aria-hidden',
        'true'
    );

    window.clearTimeout(
        panel.__filterCloseTimer
    );

    panel.__filterCloseTimer =
        window.setTimeout(
            () => {
                panel.classList.remove(
                    'closing'
                );

                const anotherDrawerIsActive =
                    document.querySelector(
                        [
                            '.filter-drawer-wrapper.open',
                            '.filter-drawer-wrapper.closing'
                        ].join(', ')
                    );

                if (
                    !anotherDrawerIsActive
                ) {
                    document.documentElement
                        .classList.remove(
                            'filter-lock'
                        );

                    document.body
                        .classList.remove(
                            'filter-lock'
                        );
                }
            },
            300
        );
}

if (
    !window
        .__filterDrawerOutsideClickBound
) {
    document.addEventListener(
        'click',
        function handleFilterDrawerOutsideClick(
            event
        ) {
            const overlay =
                event.target.closest?.(
                    '.filter-drawer-overlay'
                );

            if (!overlay) {
                return;
            }

            const drawer =
                overlay.closest(
                    '.filter-drawer-wrapper'
                );

            if (
                !drawer?.id ||
                !drawer.classList.contains(
                    'open'
                )
            ) {
                return;
            }

            closeFilterDrawer(
                drawer.id
            );
        }
    );

    window
        .__filterDrawerOutsideClickBound =
        true;
}