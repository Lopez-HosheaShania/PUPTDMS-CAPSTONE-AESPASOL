const FLOATING_SAVE_BAR_SELECTOR =
    '[data-floating-save-bar]';

function getFloatingSaveBars(
    root = document
) {
    const bars = [];

    if (
        root instanceof Element &&
        root.matches(
            FLOATING_SAVE_BAR_SELECTOR
        )
    ) {
        bars.push(root);
    }

    root
        ?.querySelectorAll?.(
            FLOATING_SAVE_BAR_SELECTOR
        )
        .forEach(bar => {
            if (!bars.includes(bar)) {
                bars.push(bar);
            }
        });

    return bars;
}

function resolveFloatingSaveBar(
    target
) {
    if (target instanceof Element) {
        return target.matches(
            FLOATING_SAVE_BAR_SELECTOR
        )
            ? target
            : null;
    }

    if (
        typeof target === 'string'
    ) {
        const bar =
            document.getElementById(
                target
            );

        return bar?.matches(
            FLOATING_SAVE_BAR_SELECTOR
        )
            ? bar
            : null;
    }

    return null;
}

export function mountFloatingSaveBars(
    root = document
) {
    getFloatingSaveBars(root)
        .forEach(bar => {
            if (!bar.id) {
                return;
            }

            [
                ...document.body.children
            ].forEach(child => {
                if (
                    child !== bar &&
                    child.id === bar.id &&
                    child.matches?.(
                        FLOATING_SAVE_BAR_SELECTOR
                    )
                ) {
                    child.remove();
                }
            });

            if (
                bar.parentElement !==
                document.body
            ) {
                document.body
                    .appendChild(bar);
            }
        });
}

export function hideFloatingSaveBar(
    target
) {
    const bar =
        resolveFloatingSaveBar(
            target
        );

    if (!bar) {
        return;
    }

    bar.classList.remove(
        'show'
    );

    bar.setAttribute(
        'aria-hidden',
        'true'
    );
}

export function hideFloatingSaveBars(
    except = null
) {
    const exceptBar =
        resolveFloatingSaveBar(
            except
        );

    document
        .querySelectorAll(
            FLOATING_SAVE_BAR_SELECTOR
        )
        .forEach(bar => {
            if (bar === exceptBar) {
                return;
            }

            hideFloatingSaveBar(
                bar
            );
        });
}

export function showFloatingSaveBar(
    target
) {
    const bar =
        resolveFloatingSaveBar(
            target
        );

    if (!bar) {
        return;
    }

    hideFloatingSaveBars(
        bar
    );

    bar.classList.add(
        'show'
    );

    bar.setAttribute(
        'aria-hidden',
        'false'
    );
}

window.FloatingSaveBar = {
    mount:
        mountFloatingSaveBars,

    show:
        showFloatingSaveBar,

    hide:
        hideFloatingSaveBar,

    hideAll:
        hideFloatingSaveBars,
};

function initFloatingSaveBars() {
    mountFloatingSaveBars(
        document
    );
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        initFloatingSaveBars,
        {
            once: true,
        }
    );
} else {
    initFloatingSaveBars();
}