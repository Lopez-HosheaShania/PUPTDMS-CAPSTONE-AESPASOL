function resolveGlobalFilterSelectCallback(name) {
    if (!name) {
        return null;
    }

    const callback =
        window[name];

    return typeof callback ===
        'function'
        ? callback
        : null;
}

function getGlobalFilterSelectParts(root) {
    if (!root) {
        return {};
    }

    return {
        trigger:
            root.querySelector(
                '[data-filter-select-trigger]'
            ),

        input:
            root.querySelector(
                '[data-filter-select-input]'
            ),

        menu:
            root.querySelector(
                '[data-filter-select-menu]'
            ),

        label:
            root.querySelector(
                '[data-filter-select-label]'
            ),

        icon:
            root.querySelector(
                '[data-filter-select-icon]'
            ),

        iconGlyph:
            root.querySelector(
                '[data-filter-select-icon-glyph]'
            ),

        count:
            root.querySelector(
                '[data-filter-select-count]'
            ),

        searchInput:
            root.querySelector(
                '[data-filter-select-search]'
            ),

        searchClear:
            root.querySelector(
                '[data-filter-select-search-clear]'
            ),

        options:
            Array.from(
                root.querySelectorAll(
                    '[data-filter-select-option]'
                )
            ),
    };
}

function isGlobalFilterSelectMultiple(root) {
    return (
        root?.dataset
            .filterSelectMultiple ===
        'true'
    );
}

function getGlobalFilterSelectSelectedValues(root) {

    return Array.from(
        root.querySelectorAll(
            '[data-filter-select-option].is-active'
        )
    ).map(option =>
        String(
            option.dataset.value ?? ''
        )
    );
}

function toggleGlobalFilterSelectOption(
    root,
    option
) {
    const {
        input,
        label,
        count,
        options,
    } =
        getGlobalFilterSelectParts(
            root
        );

    const value =
        String(
            option.dataset.value ?? ''
        );

    const allOption =
        options.find(
            item =>
                String(
                    item.dataset.value ?? ''
                ) === 'all'
        );

    if (value === 'all') {
        options.forEach(item => {
            const isAll =
                String(
                    item.dataset.value ?? ''
                ) === 'all';

            item.classList.toggle(
                'is-active',
                isAll
            );

            item.setAttribute(
                'aria-selected',
                isAll
                    ? 'true'
                    : 'false'
            );
        });
    } else {
        if (allOption) {
            allOption.classList.remove(
                'is-active'
            );

            allOption.setAttribute(
                'aria-selected',
                'false'
            );
        }

        const active =
            !option.classList.contains(
                'is-active'
            );

        option.classList.toggle(
            'is-active',
            active
        );

        option.setAttribute(
            'aria-selected',
            active
                ? 'true'
                : 'false'
        );

        const hasSpecificSelection =
            options.some(item => {
                const itemValue =
                    String(
                        item.dataset.value ?? ''
                    );

                return (
                    itemValue !== 'all' &&
                    item.classList.contains(
                        'is-active'
                    )
                );
            });

        if (
            !hasSpecificSelection &&
            allOption
        ) {
            allOption.classList.add(
                'is-active'
            );

            allOption.setAttribute(
                'aria-selected',
                'true'
            );
        }
    }

    const values =
        getGlobalFilterSelectSelectedValues(
            root
        );

    const effectiveValues =
        values.includes('all')
            ? ['all']
            : values;

    if (input) {
        input.value =
            effectiveValues.join(',');
    }

    root.dataset.filterSelectValue =
        effectiveValues.join(',');

    if (label) {
        if (
            effectiveValues.length === 1 &&
            effectiveValues[0] === 'all'
        ) {
            label.textContent =
                allOption?.dataset.label ||
                'All';
        } else if (
            effectiveValues.length === 1
        ) {
            const selected =
                root.querySelector(
                    '[data-filter-select-option].is-active'
                );

            label.textContent =
                selected?.dataset.label ||
                '1 selected';
        } else {
            label.textContent =
                `${effectiveValues.length} selected`;
        }
    }

    if (count) {
        const specificCount =
            effectiveValues.includes('all')
                ? 0
                : effectiveValues.length;

        count.textContent =
            specificCount
                ? specificCount
                : '';

        count.classList.toggle(
            'hidden',
            !specificCount
        );
    }

    input?.dispatchEvent(
        new Event(
            'change',
            {
                bubbles: true,
            }
        )
    );

    root.dispatchEvent(
        new CustomEvent(
            'global-filter-select:change',
            {
                bubbles: true,

                detail: {
                    id:
                        root.id,

                    values:
                        effectiveValues,
                },
            }
        )
    );

    const callback =
        resolveGlobalFilterSelectCallback(
            root.dataset
                .filterSelectCallback
        );

    callback?.(
        effectiveValues,
        {
            root,
            input,
            option,
        }
    );
}

function removeGlobalFilterSelectTone(
    element
) {
    if (!element) {
        return;
    }

    const previousTone =
        element.dataset.tone || '';

    if (previousTone) {
        element.classList.remove(
            previousTone
        );
    }

    element.dataset.tone = '';
}

function applyGlobalFilterSelectTone(
    element,
    tone
) {
    if (!element) {
        return;
    }

    removeGlobalFilterSelectTone(
        element
    );

    const nextTone =
        String(tone || '')
            .trim();

    if (!nextTone) {
        return;
    }

    element.classList.add(
        nextTone
    );

    element.dataset.tone =
        nextTone;
}

function closeGlobalFilterSelect(
    root,
    options = {}
) {
    if (!root) {
        return;
    }

    const {
        trigger,
    } =
        getGlobalFilterSelectParts(
            root
        );

    root.classList.remove(
        'is-open',
        'drop-up'
    );

    trigger?.setAttribute(
        'aria-expanded',
        'false'
    );

    if (options.focusTrigger) {
        trigger?.focus();
    }
}

function closeOtherGlobalFilterSelects(
    current
) {
    document
        .querySelectorAll(
            '[data-global-filter-select].is-open'
        )
        .forEach(root => {
            if (root === current) {
                return;
            }

            closeGlobalFilterSelect(
                root
            );
        });
}

function shouldGlobalFilterSelectDropUp(
    root
) {
    const {
        trigger,
        menu,
    } =
        getGlobalFilterSelectParts(
            root
        );

    if (
        !trigger ||
        !menu
    ) {
        return false;
    }

    const triggerRect =
        trigger.getBoundingClientRect();

    const menuHeight =
        Math.min(
            menu.scrollHeight || 0,
            320
        );

    const spaceBelow =
        window.innerHeight -
        triggerRect.bottom;

    const spaceAbove =
        triggerRect.top;

    return (
        spaceBelow <
        menuHeight + 16 &&
        spaceAbove >
        spaceBelow
    );
}

function openGlobalFilterSelect(
    root
) {
    if (
        !root ||
        root.classList.contains(
            'is-disabled'
        )
    ) {
        return;
    }

    const {
        trigger,
        searchInput,
    } =
        getGlobalFilterSelectParts(
            root
        );

    closeOtherGlobalFilterSelects(
        root
    );

    root.classList.toggle(
        'drop-up',
        shouldGlobalFilterSelectDropUp(
            root
        )
    );

    root.classList.add(
        'is-open'
    );

    trigger?.setAttribute(
        'aria-expanded',
        'true'
    );

    window.requestAnimationFrame(
        () => {
            searchInput?.focus();
        }
    );
}

function toggleGlobalFilterSelect(
    root
) {
    if (!root) {
        return;
    }

    if (
        root.classList.contains(
            'is-open'
        )
    ) {
        closeGlobalFilterSelect(
            root
        );

        return;
    }

    openGlobalFilterSelect(
        root
    );
}

function syncGlobalFilterSelectOptionState(
    root,
    value
) {
    const {
        options,
    } =
        getGlobalFilterSelectParts(
            root
        );

    options.forEach(option => {
        const active =
            String(
                option.dataset.value ?? ''
            ) ===
            String(
                value ?? ''
            );

        option.classList.toggle(
            'is-active',
            active
        );

        option.setAttribute(
            'aria-selected',
            active
                ? 'true'
                : 'false'
        );
    });
}

function selectGlobalFilterSelectOption(
    root,
    option,
    settings = {}
) {
    if (
        !root ||
        !option
    ) {
        return;
    }

    const {
        input,
        label,
        icon,
        iconGlyph,
        count,
    } =
        getGlobalFilterSelectParts(
            root
        );

    const value =
        String(
            option.dataset.value ?? ''
        );

    const optionLabel =
        option.dataset.label ||
        option.textContent.trim();

    const optionIcon =
        option.dataset.icon ||
        'fa-filter';

    const tone =
        option.dataset.tone || '';

    const optionCount =
        option.dataset.count ?? '';

    const previousValue =
        String(
            input?.value ?? ''
        );

    if (input) {
        input.value =
            value;
    }

    root.dataset.filterSelectValue =
        value;

    if (label) {
        label.textContent =
            optionLabel;
    }

    if (iconGlyph) {
        iconGlyph.className =
            `fa-solid ${optionIcon}`;
    }

    applyGlobalFilterSelectTone(
        icon,
        tone
    );

    if (count) {
        const hasCount =
            optionCount !== '' &&
            optionCount !== null &&
            optionCount !== undefined;

        count.textContent =
            hasCount
                ? optionCount
                : '';

        count.classList.toggle(
            'hidden',
            !hasCount
        );
    }

    syncGlobalFilterSelectOptionState(
        root,
        value
    );

    closeGlobalFilterSelect(
        root
    );

    if (
        settings.focusTrigger !==
        false
    ) {
        root
            .querySelector(
                '[data-filter-select-trigger]'
            )
            ?.focus();
    }

    if (
        input &&
        previousValue !== value
    ) {
        input.dispatchEvent(
            new Event(
                'change',
                {
                    bubbles: true,
                }
            )
        );
    }

    root.dispatchEvent(
        new CustomEvent(
            'global-filter-select:change',
            {
                bubbles: true,

                detail: {
                    id:
                        root.id,

                    value,

                    label:
                        optionLabel,

                    icon:
                        optionIcon,

                    tone,

                    count:
                        optionCount,

                    previousValue,
                },
            }
        )
    );

    if (
        settings.callback === false
    ) {
        return;
    }

    const callback =
        resolveGlobalFilterSelectCallback(
            root.dataset
                .filterSelectCallback
        );

    callback?.(
        value,
        {
            root,
            input,
            option,

            label:
                optionLabel,

            icon:
                optionIcon,

            tone,

            count:
                optionCount,

            previousValue,
        }
    );
}

function focusGlobalFilterSelectOption(
    root,
    direction
) {
    const {
        options,
    } =
        getGlobalFilterSelectParts(
            root
        );

    if (!options.length) {
        return;
    }

    const enabled =
        options.filter(
            option =>
                !option.disabled &&
                !option.hidden
        );

    if (!enabled.length) {
        return;
    }

    const activeElement =
        document.activeElement;

    let index =
        enabled.indexOf(
            activeElement
        );

    if (index < 0) {
        index =
            enabled.findIndex(
                option =>
                    option.classList
                        .contains(
                            'is-active'
                        )
            );
    }

    if (index < 0) {
        index = 0;
    } else {
        index += direction;

        if (
            index >=
            enabled.length
        ) {
            index = 0;
        }

        if (index < 0) {
            index =
                enabled.length - 1;
        }
    }

    enabled[index]?.focus();
}

function initGlobalFilterSelect(
    root
) {
    if (
        !root ||
        root.dataset
            .filterSelectInitialized ===
        'true'
    ) {
        return;
    }

    const { trigger, options, searchInput, searchClear, } =
        getGlobalFilterSelectParts(
            root
        );

    if (!trigger) {
        return;
    }

    root.dataset
        .filterSelectInitialized =
        'true';

    trigger.addEventListener(
        'click',
        event => {
            event.preventDefault();

            toggleGlobalFilterSelect(
                root
            );
        }
    );

    trigger.addEventListener(
        'keydown',
        event => {
            if (
                event.key ===
                'ArrowDown' ||
                event.key ===
                'ArrowUp'
            ) {
                event.preventDefault();

                openGlobalFilterSelect(
                    root
                );

                focusGlobalFilterSelectOption(
                    root,
                    event.key ===
                        'ArrowDown'
                        ? 1
                        : -1
                );

                return;
            }

            if (
                event.key ===
                'Escape'
            ) {
                event.preventDefault();

                closeGlobalFilterSelect(
                    root
                );
            }
        }
    );

    function syncGlobalFilterSelectSearchClear() {
        if (
            !searchInput ||
            !searchClear
        ) {
            return;
        }

        const hasValue =
            searchInput.value
                .trim()
                .length > 0;

        searchClear.classList.toggle(
            'show',
            hasValue
        );

        searchClear.hidden =
            !hasValue;
    }

    function filterGlobalFilterSelectOptions() {
        if (!searchInput) {
            return;
        }

        const query =
            searchInput.value
                .trim()
                .toLowerCase();

        options.forEach(option => {
            const optionLabel =
                String(
                    option.dataset.label ||
                    ''
                ).toLowerCase();

            option.hidden =
                Boolean(
                    query &&
                    !optionLabel.includes(
                        query
                    )
                );
        });

        syncGlobalFilterSelectSearchClear();
    }

    searchInput?.addEventListener(
        'input',
        filterGlobalFilterSelectOptions
    );

    searchClear?.addEventListener(
        'click',
        event => {
            event.preventDefault();
            event.stopPropagation();

            if (!searchInput) {
                return;
            }

            searchInput.value = '';

            options.forEach(option => {
                option.hidden = false;
            });

            syncGlobalFilterSelectSearchClear();

            searchInput.focus();
        }
    );

    syncGlobalFilterSelectSearchClear();

    options.forEach(option => {
        option.addEventListener(
            'click',
            event => {
                event.preventDefault();

                if (
                    isGlobalFilterSelectMultiple(
                        root
                    )
                ) {
                    toggleGlobalFilterSelectOption(
                        root,
                        option
                    );
                } else {
                    selectGlobalFilterSelectOption(
                        root,
                        option
                    );
                }
            }
        );


        option.addEventListener(
            'keydown',
            event => {
                if (
                    event.key ===
                    'ArrowDown' ||
                    event.key ===
                    'ArrowUp'
                ) {
                    event.preventDefault();

                    focusGlobalFilterSelectOption(
                        root,
                        event.key ===
                            'ArrowDown'
                            ? 1
                            : -1
                    );

                    return;
                }

                if (
                    event.key ===
                    'Enter' ||
                    event.key ===
                    ' '
                ) {
                    event.preventDefault();

                    if (
                        isGlobalFilterSelectMultiple(
                            root
                        )
                    ) {
                        toggleGlobalFilterSelectOption(
                            root,
                            option
                        );
                    } else {
                        selectGlobalFilterSelectOption(
                            root,
                            option
                        );
                    }

                    return;
                }

                if (
                    event.key ===
                    'Escape'
                ) {
                    event.preventDefault();

                    closeGlobalFilterSelect(
                        root,
                        {
                            focusTrigger:
                                true,
                        }
                    );
                }
            }
        );
    });
}

function initGlobalFilterSelects(
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
            '[data-global-filter-select]'
        )
        .forEach(
            initGlobalFilterSelect
        );
}

function setGlobalFilterSelectValue(
    id,
    value,
    options = {}
) {
    const root =
        typeof id ===
            'string'
            ? document.getElementById(
                id
            )
            : id;

    if (!root) {
        return false;
    }

    const option =
        Array.from(
            root.querySelectorAll(
                '[data-filter-select-option]'
            )
        )
            .find(
                item =>
                    String(
                        item.dataset.value ??
                        ''
                    ) ===
                    String(
                        value ?? ''
                    )
            );

    if (!option) {
        return false;
    }

    selectGlobalFilterSelectOption(
        root,
        option,
        {
            callback:
                options.callback !==
                false,

            focusTrigger:
                options.focus ===
                true,
        }
    );

    return true;
}

document.addEventListener(
    'click',
    event => {
        document
            .querySelectorAll(
                '[data-global-filter-select].is-open'
            )
            .forEach(root => {
                if (
                    root.contains(
                        event.target
                    )
                ) {
                    return;
                }

                closeGlobalFilterSelect(
                    root
                );
            });
    }
);


document.addEventListener(
    'keydown',
    event => {
        if (
            event.key !==
            'Escape'
        ) {
            return;
        }

        document
            .querySelectorAll(
                '[data-global-filter-select].is-open'
            )
            .forEach(root => {
                closeGlobalFilterSelect(
                    root
                );
            });
    }
);


window.addEventListener(
    'resize',
    () => {
        document
            .querySelectorAll(
                '[data-global-filter-select].is-open'
            )
            .forEach(root => {
                root.classList.toggle(
                    'drop-up',
                    shouldGlobalFilterSelectDropUp(
                        root
                    )
                );
            });
    }
);


if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        () => {
            initGlobalFilterSelects();
        }
    );
} else {
    initGlobalFilterSelects();
}

export {
    initGlobalFilterSelects,
    setGlobalFilterSelectValue,
    closeGlobalFilterSelect
};