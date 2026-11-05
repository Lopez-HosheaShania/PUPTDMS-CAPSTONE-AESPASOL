function syncFilterTagGroup(
    groupId,
    value
) {
    const group =
        document.getElementById(
            groupId
        );

    if (!group) return;

    group
        .querySelectorAll('.ftag')
        .forEach(button => {
            button.classList.toggle(
                'ftag-active',
                button.getAttribute(
                    'data-val'
                ) === value
            );
        });
}

function bindFilterTagGroup({
    groupId,
    onChange
}) {
    const group =
        document.getElementById(
            groupId
        );

    if (!group) return;

    group.addEventListener(
        'click',
        event => {
            const button =
                event.target.closest(
                    '.ftag'
                );

            if (!button) return;

            group
                .querySelectorAll(
                    '.ftag'
                )
                .forEach(item => {
                    item.classList.remove(
                        'ftag-active'
                    );
                });

            button.classList.add(
                'ftag-active'
            );

            if (
                typeof onChange ===
                'function'
            ) {
                onChange(
                    button.getAttribute(
                        'data-val'
                    ),
                    button
                );
            }
        }
    );
}

function updateShowResultsText(
    count,
    targetId =
        'showResultsText'
) {
    const label =
        document.getElementById(
            targetId
        );

    if (!label) return;

    label.textContent =
        `Show ${count} ${count === 1
            ? 'result'
            : 'results'
        }`;
}

function setGlobalFilterButtonState({
    buttonId = 'filterBtn',
    badgeId = 'filterBadge',
    resetId =
    'externalClearFilterBtn',
    count = 0
} = {}) {
    const button =
        document.getElementById(
            buttonId
        );

    const badge =
        document.getElementById(
            badgeId
        );

    const reset =
        document.getElementById(
            resetId
        );

    const hasFilters =
        Number(count) > 0;

    if (button) {
        button.classList.toggle(
            'has-filters',
            hasFilters
        );

        button.setAttribute(
            'aria-pressed',
            hasFilters
                ? 'true'
                : 'false'
        );
    }

    if (badge) {
        badge.classList.toggle(
            'show',
            hasFilters
        );

        badge.textContent =
            hasFilters
                ? String(count)
                : '';
    }

    if (reset) {
        reset.classList.toggle(
            'hidden',
            !hasFilters
        );

        reset.classList.toggle(
            'show',
            hasFilters
        );
    }
}

export {
    syncFilterTagGroup,
    bindFilterTagGroup,
    updateShowResultsText,
    setGlobalFilterButtonState
};