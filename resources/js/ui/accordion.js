const OPEN_DURATION = 280;
const CLOSE_DURATION = 220;

function prefersReducedMotion() {
    return window.matchMedia?.(
        '(prefers-reduced-motion: reduce)'
    ).matches === true;
}

function getAccordionSummary(
    accordion
) {
    return accordion?.querySelector(
        ':scope > .global-accordion-summary'
    );
}

function revealAccordionContent(
    accordion
) {
    if (
        !accordion ||
        !accordion.hasAttribute(
            'data-accordion-reveal-on-open'
        )
    ) {
        return;
    }

    const body =
        accordion.querySelector(
            ':scope > .global-accordion-body'
        );

    if (!body) {
        return;
    }

    const target =
        body.querySelector(
            '[data-show-more-item]'
        ) ||
        body;

    window.setTimeout(
        () => {
            target.scrollIntoView({
                behavior:
                    prefersReducedMotion()
                        ? 'auto'
                        : 'smooth',

                block: 'nearest'
            });
        },
        90
    );
}

function syncAccordionState(
    accordion
) {
    if (!accordion) {
        return;
    }

    const summary =
        getAccordionSummary(
            accordion
        );

    summary?.setAttribute(
        'aria-expanded',
        String(accordion.open)
    );
}

function setAccordionOpen(
    accordion,
    shouldOpen
) {
    if (!accordion) {
        return;
    }

    const open =
        Boolean(shouldOpen);

    if (
        accordion.dataset.animating ===
        'true'
    ) {
        return;
    }

    if (
        accordion.open === open &&
        !accordion.classList.contains(
            'is-closing'
        )
    ) {
        syncAccordionState(
            accordion
        );

        if (open) {
            revealAccordionContent(
                accordion
            );
        }

        return;
    }

    if (prefersReducedMotion()) {
        accordion.classList.remove(
            'is-opening',
            'is-closing'
        );

        accordion.open =
            open;

        accordion.dataset.animating =
            'false';

        syncAccordionState(
            accordion
        );

        if (open) {
            revealAccordionContent(
                accordion
            );
        }

        return;
    }

    if (open) {
        accordion.open =
            true;

        accordion.dataset.animating =
            'true';

        accordion.classList.remove(
            'is-closing'
        );

        accordion.classList.add(
            'is-opening'
        );

        syncAccordionState(
            accordion
        );

        revealAccordionContent(
            accordion
        );

        window.setTimeout(
            () => {
                accordion.classList.remove(
                    'is-opening'
                );

                accordion.dataset.animating =
                    'false';

                syncAccordionState(
                    accordion
                );
            },
            OPEN_DURATION
        );

        return;
    }

    accordion.dataset.animating =
        'true';

    accordion.classList.remove(
        'is-opening'
    );

    accordion.classList.add(
        'is-closing'
    );

    window.setTimeout(
        () => {
            accordion.open =
                false;

            accordion.classList.remove(
                'is-closing'
            );

            accordion.dataset.animating =
                'false';

            syncAccordionState(
                accordion
            );
        },
        CLOSE_DURATION
    );
}

function toggleAccordion(
    accordion
) {
    if (
        !accordion ||
        accordion.dataset.animating ===
        'true'
    ) {
        return;
    }

    setAccordionOpen(
        accordion,
        !accordion.open
    );
}

function initializeAccordion(
    accordion
) {
    if (
        !accordion ||
        accordion.dataset
            .globalAccordionReady ===
        'true'
    ) {
        return;
    }

    const summary =
        getAccordionSummary(
            accordion
        );

    if (!summary) {
        return;
    }

    accordion.dataset.globalAccordionReady =
        'true';

    syncAccordionState(
        accordion
    );

    summary.addEventListener(
        'click',
        event => {
            event.preventDefault();

            toggleAccordion(
                accordion
            );
        }
    );
}

export function initGlobalAccordions(
    root = document
) {
    if (!root) {
        return;
    }

    if (
        root.matches?.(
            'details.global-accordion'
        )
    ) {
        initializeAccordion(
            root
        );
    }

    root
        .querySelectorAll?.(
            'details.global-accordion'
        )
        .forEach(
            initializeAccordion
        );
}

window.GlobalAccordion = {
    init: initGlobalAccordions,

    open(accordion) {
        setAccordionOpen(
            accordion,
            true
        );
    },

    close(accordion) {
        setAccordionOpen(
            accordion,
            false
        );
    },

    toggle(accordion) {
        toggleAccordion(
            accordion
        );
    },

    setOpen(
        accordion,
        shouldOpen
    ) {
        setAccordionOpen(
            accordion,
            Boolean(shouldOpen)
        );
    }
};

function initializePageAccordions() {
    initGlobalAccordions(
        document
    );
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        initializePageAccordions,
        {
            once: true
        }
    );
} else {
    initializePageAccordions();
}

document.addEventListener(
    'ui-modal:opened',
    event => {
        initGlobalAccordions(
            event.detail?.modal ||
            document
        );
    }
);