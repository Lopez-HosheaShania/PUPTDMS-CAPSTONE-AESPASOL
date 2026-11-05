function escapeEmptyStateHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function resolveEmptyStateElement(
    target
) {
    if (!target) {
        return null;
    }

    if (
        target instanceof
        HTMLElement
    ) {
        return target;
    }

    if (
        typeof target ===
        'string'
    ) {
        return document.querySelector(
            target
        );
    }

    return null;
}

function buildEmptyStateHtml({
    title = 'No records found',
    message = '',
    icon = 'fa-folder-open',
    className = '',
    actionHtml = '',
    searchTarget = '',
    showClearSearch = false,
} = {}) {
    const clearSearchHtml =
        showClearSearch &&
            searchTarget
            ? `
                <button
                    type="button"
                    class="empty-state-btn"
                    data-clear-search
                    data-search-target="${escapeEmptyStateHtml(
                searchTarget
            )}"
                >
                    <i
                        class="
                            fa-solid
                            fa-xmark
                        "
                    ></i>

                    Clear search
                </button>
            `
            : '';

    return `
        <div
            class="
                empty-state
                ${escapeEmptyStateHtml(
        className
    )}
            "
        >
            <div class="empty-state-icon">
                <i
                    class="
                        fa-solid
                        ${escapeEmptyStateHtml(
        icon
    )}
                    "
                    aria-hidden="true"
                ></i>
            </div>

            <p class="empty-state-title">
                ${escapeEmptyStateHtml(
        title
    )}
            </p>

            ${message
            ? `
                        <p class="empty-state-sub">
                            ${escapeEmptyStateHtml(
                message
            )}
                        </p>
                    `
            : ''
        }

            ${clearSearchHtml}

            ${actionHtml}
        </div>
    `;
}

function renderGlobalEmptyState({
    host,
    title = 'No records found',
    message = '',
    icon = 'fa-folder-open',
    className = '',
    actionHtml = '',
    searchTarget = '',
    showClearSearch = false,
} = {}) {
    const hostElement =
        resolveEmptyStateElement(
            host
        );

    if (!hostElement) {
        return false;
    }

    hostElement.classList.add(
        'empty-state-host',
        'show'
    );

    hostElement.classList.remove(
        'is-visible'
    );

    hostElement.innerHTML =
        buildEmptyStateHtml({
            title,
            message,
            icon,
            className,
            actionHtml,
            searchTarget,
            showClearSearch,
        });

    return true;
}

function renderGlobalSearchEmptyState({
    host,
    input,
    query = '',
    title = '',
    message =
    'Try a different search term.',
    icon =
    'fa-magnifying-glass',
    className =
    'empty-search',
} = {}) {
    const inputElement =
        resolveEmptyStateElement(
            input
        );

    const normalizedQuery =
        String(
            query ||
            inputElement?.value ||
            ''
        ).trim();

    const searchTarget =
        inputElement?.id
            ? `#${inputElement.id}`
            : (
                typeof input ===
                    'string'
                    ? input
                    : ''
            );

    return renderGlobalEmptyState({
        host,

        title:
            title ||
            (
                normalizedQuery
                    ? `No results for “${normalizedQuery}”`
                    : 'No results found'
            ),

        message,

        icon,

        className,

        searchTarget,

        showClearSearch:
            Boolean(
                searchTarget
            ),
    });
}

function hideGlobalEmptyState(
    host
) {
    const hostElement =
        resolveEmptyStateElement(
            host
        );

    if (!hostElement) {
        return;
    }

    hostElement.classList.remove(
        'show',
        'is-visible'
    );

    hostElement.replaceChildren();
}

function isGlobalEmptyStateVisible(
    host
) {
    const hostElement =
        resolveEmptyStateElement(
            host
        );

    if (!hostElement) {
        return false;
    }

    return (
        hostElement.classList.contains(
            'show'
        ) ||
        hostElement.classList.contains(
            'is-visible'
        )
    );
}

window.EmptyState = {
    render:
        renderGlobalEmptyState,

    renderSearch:
        renderGlobalSearchEmptyState,

    hide:
        hideGlobalEmptyState,

    isVisible:
        isGlobalEmptyStateVisible,

    buildHtml:
        buildEmptyStateHtml,
};

window.renderGlobalEmptyState =
    renderGlobalEmptyState;

window.renderGlobalSearchEmptyState =
    renderGlobalSearchEmptyState;

window.hideGlobalEmptyState =
    hideGlobalEmptyState;