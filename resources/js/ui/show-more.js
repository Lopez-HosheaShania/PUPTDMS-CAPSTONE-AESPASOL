const ROOT_SELECTOR = '[data-show-more]';
const DISCLOSURE_MODE = 'disclosure';

function isDisclosureShowMore(
    root
) {
    return (
        root?.dataset.showMoreMode ===
        DISCLOSURE_MODE
    );
}

function getDisclosureTarget(
    root
) {
    const button =
        root?.querySelector(
            '[data-show-more-button]'
        );

    const targetId =
        button?.getAttribute(
            'aria-controls'
        );

    if (!targetId) {
        return null;
    }

    return document.getElementById(
        targetId
    );
}

function setDisclosureExpanded(
    root,
    expanded,
    options = {}
) {
    if (!root) {
        return;
    }

    const button =
        root.querySelector(
            '[data-show-more-button]'
        );

    if (!button) {
        return;
    }

    const text =
        button.querySelector(
            '[data-show-more-text]'
        );

    const summary =
        root.querySelector(
            '[data-show-more-summary]'
        );

    const target =
        getDisclosureTarget(
            root
        );

    const isExpanded =
        Boolean(expanded);

    root.dataset.showMoreExpanded =
        String(isExpanded);

    button.setAttribute(
        'aria-expanded',
        String(isExpanded)
    );

    button.dataset.mode =
        isExpanded
            ? 'less'
            : 'more';

    if (text) {
        text.textContent =
            isExpanded
                ? 'Show less'
                : 'Show more';
    }

    if (target) {
        target.hidden =
            !isExpanded;

        target.setAttribute(
            'aria-hidden',
            String(!isExpanded)
        );
    }

    if (
        summary &&
        root.dataset
            .showMoreReplaceSummary ===
        'true'
    ) {
        summary.hidden =
            isExpanded;
    }

    if (
        options.emit !== false
    ) {
        root.dispatchEvent(
            new CustomEvent(
                'global-show-more:change',
                {
                    bubbles: true,

                    detail: {
                        expanded:
                            isExpanded,

                        targetId:
                            button.getAttribute(
                                'aria-controls'
                            ),
                    },
                }
            )
        );
    }
}

function bindDisclosureShowMore(
    root
) {
    const button =
        root.querySelector(
            '[data-show-more-button]'
        );

    if (!button) {
        return;
    }

    button.addEventListener(
        'click',
        () => {
            setDisclosureExpanded(
                root,
                button.getAttribute(
                    'aria-expanded'
                ) !== 'true'
            );
        }
    );

    setDisclosureExpanded(
        root,
        root.dataset
            .showMoreExpanded ===
        'true',
        {
            emit: false,
        }
    );
}

function collapseShowMoreDisclosures(
    scope = document
) {
    scope
        ?.querySelectorAll?.(
            '[data-show-more][data-show-more-mode="disclosure"]'
        )
        .forEach(root => {
            setDisclosureExpanded(
                root,
                false,
                {
                    emit: false,
                }
            );
        });
}

function getStep(root) {
    return Math.max(
        1,
        Number(
            root?.dataset.showMoreStep ||
            5
        )
    );
}

function getLists(root) {
    if (!root) return [];

    return Array.from(
        root.querySelectorAll(
            '[data-show-more-list]'
        )
    );
}

function getItems(list) {
    if (!list) return [];

    return Array.from(
        list.querySelectorAll(
            ':scope > [data-show-more-item]'
        )
    );
}

function itemMatches(item) {
    if (!item) return false;

    if (
        item.dataset.filterMatch === '0' ||
        item.dataset.showMoreMatch === '0'
    ) {
        return false;
    }

    return true;
}

function setItemVisible(
    item,
    visible
) {
    if (!item) return;

    item.hidden = !visible;

    item.classList.toggle(
        'hidden',
        !visible
    );
}

function getReferenceList(root) {
    const lists = getLists(root);

    return (
        lists.find(
            list =>
                list.dataset.showMoreReference ===
                'true'
        ) ||
        lists[0] ||
        null
    );
}

function refreshShowMore(root) {
    if (!root) return;

    if (
        isDisclosureShowMore(
            root
        )
    ) {
        setDisclosureExpanded(
            root,
            root.dataset
                .showMoreExpanded ===
            'true',
            {
                emit: false,
            }
        );

        return;
    }

    const step =
        getStep(root);

    const currentVisible =
        Math.max(
            step,
            Number(
                root.dataset.showMoreVisible ||
                step
            )
        );

    root.dataset.showMoreVisible =
        String(currentVisible);

    const lists =
        getLists(root);

    const referenceList =
        getReferenceList(root);

    if (!referenceList) {
        return;
    }

    const referenceItems =
        getItems(referenceList)
            .filter(itemMatches);

    const total =
        referenceItems.length;

    lists.forEach(list => {
        const items =
            getItems(list);

        const matchingItems =
            items.filter(itemMatches);

        items.forEach(item => {
            if (!itemMatches(item)) {
                setItemVisible(
                    item,
                    false
                );
            }
        });

        matchingItems.forEach(
            (item, index) => {
                setItemVisible(
                    item,
                    index < currentVisible
                );
            }
        );
    });

    const countElement =
        root.querySelector(
            '[data-show-more-count]'
        );

    if (countElement) {
        const label =
            countElement.dataset
                .showMoreCountLabel ||
            root.dataset
                .showMoreLabel ||
            'items';

        const singular =
            countElement.dataset
                .showMoreCountSingular ||
            label.replace(/s$/, '');

        countElement.textContent =
            `${total} ${total === 1
                ? singular
                : label
            }`;
    }

    const button =
        root.querySelector(
            '[data-show-more-button]'
        );

    const text =
        root.querySelector(
            '[data-show-more-text]'
        );

    const controls =
        root.querySelector(
            '[data-show-more-controls]'
        );

    if (!button || !text) {
        return;
    }

    const hasMore =
        total > step;

    if (controls) {
        controls.hidden =
            !hasMore;
    }

    button.hidden =
        !hasMore;

    if (!hasMore) {
        button.dataset.mode =
            'more';

        text.textContent =
            'Show more';

        return;
    }

    const fullyExpanded =
        currentVisible >= total;

    const icon =
        button.querySelector('i');

    if (fullyExpanded) {
        text.textContent =
            'Show less';

        button.dataset.mode =
            'less';

        return;
    }

    const remaining =
        Math.min(
            step,
            total - currentVisible
        );

    text.textContent =
        `Show ${remaining} more`;

    button.dataset.mode =
        'more';
}

function resetShowMore(root) {
    if (!root) return;

    if (
        isDisclosureShowMore(
            root
        )
    ) {
        setDisclosureExpanded(
            root,
            false
        );

        return;
    }

    root.dataset.showMoreVisible =
        String(
            getStep(root)
        );

    refreshShowMore(root);
}

function bindShowMore(root) {
    if (
        !root ||
        root.dataset.showMoreReady ===
        'true'
    ) {
        return;
    }

    root.dataset.showMoreReady =
        'true';

    if (
        isDisclosureShowMore(
            root
        )
    ) {
        bindDisclosureShowMore(
            root
        );

        return;
    }

    const step =
        getStep(root);

    root.dataset.showMoreVisible =
        root.dataset.showMoreVisible ||
        String(step);

    const button =
        root.querySelector(
            '[data-show-more-button]'
        );

    button?.addEventListener(
        'click',
        () => {
            const current =
                Number(
                    root.dataset
                        .showMoreVisible ||
                    step
                );

            if (
                button.dataset.mode ===
                'less'
            ) {
                root.dataset
                    .showMoreVisible =
                    String(step);
            } else {
                root.dataset
                    .showMoreVisible =
                    String(
                        current + step
                    );
            }

            refreshShowMore(root);
        }
    );

    refreshShowMore(root);
}

function initShowMore(
    root = document
) {
    const scope =
        root &&
            typeof root.querySelectorAll ===
            'function'
            ? root
            : document;

    scope
        .querySelectorAll(
            ROOT_SELECTOR
        )
        .forEach(bindShowMore);
}

window.ShowMore = {
    init: initShowMore,
    refresh: refreshShowMore,
    reset: resetShowMore,
    collapseDisclosures: collapseShowMoreDisclosures,
};

function bootShowMore() {
    initShowMore(
        document
    );
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        bootShowMore,
        {
            once: true
        }
    );
} else {
    bootShowMore();
}