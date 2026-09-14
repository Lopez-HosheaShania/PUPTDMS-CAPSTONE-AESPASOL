function initGlobalActionTooltips() {
    const tooltip = document.getElementById('globalActionTooltip');

    if (!tooltip || tooltip.dataset.initialized === 'true') {
        return;
    }

    tooltip.dataset.initialized = 'true';

    const toneClasses = [
        'tooltip-view',
        'tooltip-start',
        'tooltip-reschedule',
        'tooltip-cancel',
        'tooltip-delete',
        'tooltip-edit',
        'tooltip-reset',
        'tooltip-locked',
        'tooltip-neutral'
    ];

    let activeTarget = null;

    function hideTooltip() {
        tooltip.classList.remove(
            'show',
            ...toneClasses
        );

        tooltip.style.removeProperty(
            '--tooltip-bg'
        );

        tooltip.setAttribute(
            'aria-hidden',
            'true'
        );

        tooltip.textContent = '';
        activeTarget = null;
    }

    window.hideGlobalActionTooltip = hideTooltip;

    function positionTooltip(target) {
        const targetRect = target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        const viewportPadding = 12;
        const gap = 10;

        let left =
            targetRect.left +
            targetRect.width / 2 -
            tooltipRect.width / 2;

        let top =
            targetRect.top -
            tooltipRect.height -
            gap;

        if (top < viewportPadding) {
            top = targetRect.bottom + gap;
        }

        const maximumLeft =
            window.innerWidth -
            tooltipRect.width -
            viewportPadding;

        const maximumTop =
            window.innerHeight -
            tooltipRect.height -
            viewportPadding;

        left = Math.min(
            Math.max(viewportPadding, left),
            Math.max(viewportPadding, maximumLeft)
        );

        top = Math.min(
            Math.max(viewportPadding, top),
            Math.max(viewportPadding, maximumTop)
        );

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    }

    function showTooltip(target) {
        const message = target.dataset.tooltip?.trim();

        if (!message) {
            return;
        }

        activeTarget = target;

        const tone =
            target.dataset.tooltipTone ||
            (target.classList.contains('ui-action-view')
                ? 'view'
                : target.classList.contains('ui-action-success')
                    ? 'start'
                    : target.classList.contains('ui-action-warning')
                        ? 'reschedule'
                        : target.classList.contains('ui-action-delete')
                            ? 'delete'
                            : target.classList.contains('ui-action-edit')
                                ? 'edit'
                                : target.classList.contains('ui-action-reset')
                                    ? 'reset'
                                    : 'neutral');

        tooltip.classList.remove(
            ...toneClasses
        );

        tooltip.classList.add(
            `tooltip-${tone}`
        );

        tooltip.style.removeProperty(
            '--tooltip-bg'
        );

        const customColor =
            target.dataset.tooltipColor?.trim();

        if (customColor) {
            tooltip.style.setProperty(
                '--tooltip-bg',
                customColor
            );
        }

        tooltip.textContent = message;
        tooltip.setAttribute('aria-hidden', 'false');
        tooltip.classList.add('show');

        requestAnimationFrame(() => {
            if (activeTarget === target) {
                positionTooltip(target);
            }
        });
    }

    document.addEventListener('pointerover', event => {
        const target = event.target.closest('[data-tooltip]');

        if (!target) {
            return;
        }

        showTooltip(target);
    });

    document.addEventListener('pointerout', event => {
        const target = event.target.closest('[data-tooltip]');

        if (!target) {
            return;
        }

        if (target.contains(event.relatedTarget)) {
            return;
        }

        hideTooltip();
    });

    document.addEventListener('focusin', event => {
        const target = event.target.closest('[data-tooltip]');

        if (target) {
            showTooltip(target);
        }
    });

    document.addEventListener('focusout', event => {
        if (event.target.closest('[data-tooltip]')) {
            hideTooltip();
        }
    });

    window.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);
}

function renderGlobalChartTooltip(context) {
    const {
        chart,
        tooltip
    } = context;

    const element =
        document.getElementById(
            'globalActionTooltip'
        );

    if (!element) return;

    const toneClasses = [
        'tooltip-view',
        'tooltip-start',
        'tooltip-reschedule',
        'tooltip-cancel',
        'tooltip-delete',
        'tooltip-edit',
        'tooltip-reset',
        'tooltip-locked',
        'tooltip-neutral'
    ];

    if (
        !tooltip ||
        tooltip.opacity === 0
    ) {
        element.classList.remove(
            'show',
            ...toneClasses
        );

        element.setAttribute(
            'aria-hidden',
            'true'
        );

        element.replaceChildren();

        element.style.left = '';
        element.style.top = '';

        return;
    }

    element.replaceChildren();

    const titleText =
        tooltip.title
            ?.filter(Boolean)
            .join(' · ') || '';

    if (titleText) {
        const title =
            document.createElement('strong');

        title.textContent = titleText;
        element.appendChild(title);
    }

    const lines =
        tooltip.body
            ?.flatMap(
                item => item.lines || []
            ) || [];

    lines.forEach(line => {
        const row =
            document.createElement('span');

        row.textContent = line;
        element.appendChild(row);
    });

    element.classList.remove(
        ...toneClasses
    );

    element.style.removeProperty(
        '--tooltip-bg'
    );

    element.classList.add(
        'show',
        'tooltip-neutral'
    );

    element.setAttribute(
        'aria-hidden',
        'false'
    );

    const canvasRect =
        chart.canvas.getBoundingClientRect();

    const tooltipRect =
        element.getBoundingClientRect();

    const viewportPadding = 12;
    const gap = 10;

    let left =
        canvasRect.left +
        tooltip.caretX -
        tooltipRect.width / 2;

    let top =
        canvasRect.top +
        tooltip.caretY -
        tooltipRect.height -
        gap;

    left = Math.min(
        Math.max(viewportPadding, left),
        window.innerWidth -
        tooltipRect.width -
        viewportPadding
    );

    if (top < viewportPadding) {
        top =
            canvasRect.top +
            tooltip.caretY +
            gap;
    }

    element.style.left = `${left}px`;
    element.style.top = `${top}px`;
}

function getGlobalChartTooltipOptions(
    callbacks = {}
) {
    return {
        enabled: false,
        external: renderGlobalChartTooltip,
        callbacks
    };
}

document.addEventListener('DOMContentLoaded', initGlobalActionTooltips);

window.renderGlobalChartTooltip = renderGlobalChartTooltip;
window.getGlobalChartTooltipOptions = getGlobalChartTooltipOptions;