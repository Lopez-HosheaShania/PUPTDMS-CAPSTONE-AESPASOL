function createGlobalPageButton({
    label,
    page,
    current = false,
    disabled = false,
    ariaLabel = '',
    onPageChange = null,
}) {
    const button =
        document.createElement('button');

    button.type = 'button';

    button.className =
        disabled
            ? 'global-page-disabled'
            : current
                ? 'global-page-current'
                : 'global-page-btn';

    button.innerHTML = label;

    if (ariaLabel) {
        button.setAttribute(
            'aria-label',
            ariaLabel
        );
    }

    if (current) {
        button.setAttribute(
            'aria-current',
            'page'
        );
    }

    button.disabled =
        disabled || current;

    if (
        !disabled &&
        !current &&
        typeof onPageChange ===
        'function'
    ) {
        button.addEventListener(
            'click',
            () => {
                onPageChange(page);
            }
        );
    }

    return button;
}

function createGlobalPageEllipsis() {
    const ellipsis =
        document.createElement('span');

    ellipsis.className =
        'global-page-ellipsis';

    ellipsis.textContent = '…';

    ellipsis.setAttribute(
        'aria-hidden',
        'true'
    );

    return ellipsis;
}

function fillGlobalPagination({
    container,
    currentPage,
    lastPage,
    total,
    onPageChange,
    windowSize = 5,
}) {
    if (!container) {
        return;
    }

    container.replaceChildren();

    if (
        total <= 0 ||
        lastPage <= 1
    ) {
        return;
    }

    container.appendChild(
        createGlobalPageButton({
            label:
                '<i class="fa-solid fa-chevron-left global-page-icon"></i>',

            page:
                Math.max(
                    1,
                    currentPage - 1
                ),

            disabled:
                currentPage <= 1,

            ariaLabel:
                'Previous page',

            onPageChange,
        })
    );

    const halfWindow =
        Math.floor(
            windowSize / 2
        );

    let startPage =
        Math.max(
            1,
            currentPage - halfWindow
        );

    let endPage =
        Math.min(
            lastPage,
            startPage +
            windowSize -
            1
        );

    if (
        endPage -
        startPage +
        1 <
        windowSize
    ) {
        startPage =
            Math.max(
                1,
                endPage -
                windowSize +
                1
            );
    }

    if (startPage > 1) {
        container.appendChild(
            createGlobalPageButton({
                label: '1',
                page: 1,
                current:
                    currentPage === 1,
                onPageChange,
            })
        );

        if (startPage > 2) {
            container.appendChild(
                createGlobalPageEllipsis()
            );
        }
    }

    for (
        let page = startPage;
        page <= endPage;
        page += 1
    ) {
        container.appendChild(
            createGlobalPageButton({
                label:
                    String(page),

                page,

                current:
                    page ===
                    currentPage,

                onPageChange,
            })
        );
    }

    if (endPage < lastPage) {
        if (
            endPage <
            lastPage - 1
        ) {
            container.appendChild(
                createGlobalPageEllipsis()
            );
        }

        container.appendChild(
            createGlobalPageButton({
                label:
                    String(
                        lastPage
                    ),

                page:
                    lastPage,

                current:
                    currentPage ===
                    lastPage,

                onPageChange,
            })
        );
    }

    container.appendChild(
        createGlobalPageButton({
            label:
                '<i class="fa-solid fa-chevron-right global-page-icon"></i>',

            page:
                Math.min(
                    lastPage,
                    currentPage + 1
                ),

            disabled:
                currentPage >=
                lastPage,

            ariaLabel:
                'Next page',

            onPageChange,
        })
    );
}

function renderGlobalPagination({
    currentPage = 1,
    lastPage = 1,
    total = 0,
    from = null,
    to = null,

    containers = [],
    infoElements = [],
    bars = [],

    itemLabel = 'entries',

    onPageChange = null,
}) {
    const normalizedCurrent =
        Math.max(
            1,
            Number(currentPage) || 1
        );

    const normalizedLast =
        Math.max(
            1,
            Number(lastPage) || 1
        );

    const normalizedTotal =
        Math.max(
            0,
            Number(total) || 0
        );

    const infoHtml =
        normalizedTotal > 0
            ? `
                Showing
                <strong>${from ?? 0}</strong>–<strong>${to ?? 0}</strong>
                of
                <strong>${normalizedTotal}</strong>
                ${itemLabel}
            `
            : `
                Showing
                <strong>0</strong>
                ${itemLabel}
            `;

    infoElements.forEach(
        element => {
            if (!element) {
                return;
            }

            element.innerHTML =
                infoHtml.trim();
        }
    );

    bars.forEach(bar => {
        if (!bar) {
            return;
        }

        bar.hidden =
            normalizedTotal <= 0;
    });

    containers.forEach(
        container => {
            fillGlobalPagination({
                container,

                currentPage:
                    normalizedCurrent,

                lastPage:
                    normalizedLast,

                total:
                    normalizedTotal,

                onPageChange,
            });
        }
    );
}

window.renderGlobalPagination =
    renderGlobalPagination;

window.fillGlobalPagination =
    fillGlobalPagination;