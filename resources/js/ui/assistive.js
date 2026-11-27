function initAssistiveHelpPopover() {
    if (
        document.querySelector(
            '.assistive-help-popover'
        )
    ) {
        return;
    }

    const assistiveBtn =
        document.getElementById(
            'assistiveMainFab'
        );

    if (!assistiveBtn) {
        return;
    }

    const popover =
        document.createElement(
            'div'
        );

    popover.className =
        'assistive-help-popover';

    popover.innerHTML = `
        <strong>Need help?</strong>
        <span>
            Open assistance tools for chatbot
            and accessibility options.
        </span>
    `;

    document.body.appendChild(popover);

    const showPopover = () => {
        if (
            document
                .getElementById('assistiveFabGroup')
                ?.classList.contains('open')
        ) {
            return;
        }

        popover.classList.add('show');
    };

    const hidePopover = () => {
        popover.classList.remove('show');
    };

    assistiveBtn.addEventListener('mouseenter', showPopover);
    assistiveBtn.addEventListener('focus', showPopover);
    assistiveBtn.addEventListener('mouseleave', hidePopover);
    assistiveBtn.addEventListener('blur', hidePopover);
    assistiveBtn.addEventListener('click', hidePopover);
}

function initAccessibilitySheetGesture() {
    let startY = 0;
    let currentY = 0;
    let tracking = false;
    let dragging = false;

    document.addEventListener('touchstart', event => {
        const menu = event.target.closest('.asw-menu');

        if (!menu) return;

        const interactiveElement = event.target.closest(
            'button, a, input, select, textarea, label'
        );

        if (interactiveElement) {
            tracking = false;
            dragging = false;
            return;
        }

        startY = event.touches[0].clientY;
        currentY = startY;
        tracking = true;
        dragging = false;
    }, { passive: true });

    document.addEventListener('touchmove', event => {
        if (!tracking) return;

        const menu = document.querySelector('.asw-menu');

        if (!menu) {
            tracking = false;
            dragging = false;
            return;
        }

        currentY = event.touches[0].clientY;

        const difference = Math.max(
            0,
            currentY - startY
        );

        if (difference < 6) return;

        dragging = true;

        menu.classList.add('asw-dragging');

        menu.style.transform =
            `translateX(-50%) translateY(${difference}px)`;
    }, { passive: true });

    document.addEventListener('touchend', () => {
        if (!tracking) return;

        const menu = document.querySelector('.asw-menu');

        tracking = false;

        if (!menu || !dragging) {
            dragging = false;
            return;
        }

        const difference = Math.max(
            0,
            currentY - startY
        );

        menu.classList.remove('asw-dragging');
        menu.style.removeProperty('transform');

        if (difference > 90) {
            menu.querySelector('.asw-menu-close')?.click();
        }

        dragging = false;
    });

    document.addEventListener('touchcancel', () => {
        const menu = document.querySelector('.asw-menu');

        tracking = false;
        dragging = false;

        if (!menu) return;

        menu.classList.remove('asw-dragging');
        menu.style.removeProperty('transform');
    });
}

function fixSiennaPosition() {
    const isMobile = window.matchMedia('(max-width: 640px)').matches;

    const nav = document.getElementById('mobileBottomNav');

    const right = isMobile ? 14 : 22;
    const fabSize = isMobile ? 40 : 46;
    const gap = 14;

    let navClearance = 0;
    let navOverlap = false;

    if (nav) {
        const rect = nav.getBoundingClientRect();
        const style = window.getComputedStyle(nav);

        const isVisible =
            style.display !== 'none' &&
            style.visibility !== 'hidden' &&
            rect.width > 0 &&
            rect.height > 0 &&
            rect.top < window.innerHeight &&
            rect.bottom > window.innerHeight * 0.7;

        const fabLeft = window.innerWidth - right - fabSize;
        const fabRight = window.innerWidth - right;

        if (isVisible) {
            navClearance = Math.max(
                0,
                window.innerHeight - rect.top
            );

            navOverlap =
                fabLeft < rect.right + gap &&
                fabRight > rect.left - gap;
        }
    }

    const assistiveGroupBottom = navOverlap
        ? Math.max(24, navClearance + 12)
        : 24;

    const backTopBottom =
        assistiveGroupBottom + fabSize + gap;

    const root = document.documentElement;

    const variables = {
        '--float-right': `${right}px`,
        '--float-right-final': `${right}px`,
        '--patient-nav-height': `${navClearance}px`,
        '--fab-final-size': `${fabSize}px`,

        '--accessibility-bottom': `${assistiveGroupBottom}px`,
        '--accessibility-bottom-final': `${assistiveGroupBottom}px`,

        '--chatbot-bottom-final': `${assistiveGroupBottom}px`,

        '--back-top-bottom': `${backTopBottom}px`,
        '--back-top-bottom-final': `${backTopBottom}px`
    };

    Object.entries(variables).forEach(([name, value]) => {
        root.style.setProperty(name, value);
    });

    const assistiveGroup =
        document.getElementById('assistiveFabGroup');

    if (assistiveGroup) {
        assistiveGroup.style.removeProperty('right');
        assistiveGroup.style.removeProperty('bottom');
    }

    document
        .querySelectorAll('.asw-widget, .asw-menu-btn')
        .forEach(element => {
            [
                'right',
                'bottom',
                '--asw-off-x',
                '--asw-off-y',
                '--asw-right',
                '--asw-bottom'
            ].forEach(property => {
                element.style.removeProperty(property);
            });
        });
}

let siennaPositionFrame = null;

function scheduleSiennaPosition() {
    cancelAnimationFrame(
        siennaPositionFrame
    );

    siennaPositionFrame =
        requestAnimationFrame(() => {
            fixSiennaPosition();
        });
}

window.addEventListener(
    'load',
    scheduleSiennaPosition
);

window.addEventListener(
    'resize',
    scheduleSiennaPosition,
    { passive: true }
);

window.addEventListener(
    'orientationchange',
    scheduleSiennaPosition,
    { passive: true }
);

window.visualViewport?.addEventListener(
    'resize',
    scheduleSiennaPosition,
    { passive: true }
);

function syncAssistiveFloatingState() {
    const group = document.getElementById('assistiveFabGroup');

    if (!group) return;

    const sync = () => {
        document.body.classList.toggle(
            'assistive-menu-open',
            group.classList.contains('open')
        );
    };

    sync();

    const observer = new MutationObserver(sync);

    observer.observe(group, {
        attributes: true,
        attributeFilter: ['class']
    });
}

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initAssistiveHelpPopover();
        initAccessibilitySheetGesture();
        syncAssistiveFloatingState();
        scheduleSiennaPosition();

        const nav = document.getElementById('mobileBottomNav');

        if (nav && typeof ResizeObserver !== 'undefined') {
            const navObserver = new ResizeObserver(
                scheduleSiennaPosition
            );

            navObserver.observe(nav);
        }
    }
);