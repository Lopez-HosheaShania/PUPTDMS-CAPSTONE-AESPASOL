function closeCustomSelects(except = null) {
    document.querySelectorAll('.custom-select.is-open').forEach(wrapper => {
        if (wrapper === except) return;

        wrapper.classList.remove('is-open', 'drop-up');

        wrapper
            .querySelector('.custom-select-button')
            ?.setAttribute('aria-expanded', 'false');
    });
}

export function syncCustomSelect(wrapper) {
    const select = wrapper.querySelector('select');
    const valueText = wrapper.querySelector('[data-custom-select-value]');
    const button = wrapper.querySelector('.custom-select-button');

    if (!select || !valueText || !button) return;

    const selectedOption = select.options[select.selectedIndex];

    valueText.textContent =
        selectedOption?.textContent?.trim() ||
        select.dataset.placeholder ||
        'Select option';

    wrapper.classList.toggle('is-disabled', select.disabled);
    wrapper.classList.toggle('has-value', Boolean(select.value));

    button.disabled = select.disabled;

    wrapper
        .querySelectorAll('.custom-select-option')
        .forEach(option => {
            const isActive =
                Number(option.dataset.index) === select.selectedIndex;

            option.classList.toggle('is-active', isActive);
            option.setAttribute(
                'aria-selected',
                isActive ? 'true' : 'false'
            );
        });
}

function positionCustomSelectMenu(
    wrapper
) {
    if (!wrapper) return;

    const button =
        wrapper.querySelector(
            '.custom-select-button'
        );

    const menu =
        wrapper.querySelector(
            '.custom-select-menu'
        );

    if (!button || !menu) {
        return;
    }

    if (
        wrapper.closest(
            '.flatpickr-calendar'
        )
    ) {
        const isYearSelect =
            Boolean(
                wrapper.querySelector(
                    '.custom-flatpickr-year'
                )
            );

        menu.style.setProperty(
            '--custom-select-max-height',
            isYearSelect
                ? '220px'
                : '210px'
        );

        return;
    }

    const buttonRect =
        button.getBoundingClientRect();

    const scrollContainer =
        wrapper.closest(
            [
                '.um-user-modal-body',
                '.modal-body',
                '.modal-bd',
                '[data-modal-scroll]',
                '.ui-modal-card',
                'dialog'
            ].join(',')
        );

    const boundaryRect =
        scrollContainer
            ?.getBoundingClientRect();

    const boundaryTop =
        boundaryRect
            ? Math.max(
                boundaryRect.top,
                8
            )
            : 8;

    const boundaryBottom =
        boundaryRect
            ? Math.min(
                boundaryRect.bottom,
                window.innerHeight - 8
            )
            : window.innerHeight - 8;

    const spaceBelow =
        Math.max(
            0,
            boundaryBottom -
            buttonRect.bottom -
            10
        );

    const spaceAbove =
        Math.max(
            0,
            buttonRect.top -
            boundaryTop -
            10
        );

    const previousDisplay =
        menu.style.display;

    const previousVisibility =
        menu.style.visibility;

    const previousPointerEvents =
        menu.style.pointerEvents;

    const previousTransition =
        menu.style.transition;

    menu.style.display =
        'block';

    menu.style.visibility =
        'hidden';

    menu.style.pointerEvents =
        'none';

    menu.style.transition =
        'none';

    const naturalHeight =
        Math.max(
            menu.scrollHeight,
            96
        );

    const preferredHeight =
        Math.min(
            naturalHeight,
            360
        );

    const shouldOpenUp =
        spaceBelow < preferredHeight &&
        spaceAbove > spaceBelow;

    wrapper.classList.toggle(
        'drop-up',
        shouldOpenUp
    );

    const availableSpace =
        shouldOpenUp
            ? spaceAbove
            : spaceBelow;

    const maxHeight =
        Math.max(
            96,
            Math.min(
                360,
                availableSpace
            )
        );

    menu.style.setProperty(
        '--custom-select-max-height',
        `${maxHeight}px`
    );

    const filterPanel =
        wrapper.closest(
            '.filter-drawer-panel'
        );

    if (filterPanel) {
        const panelRect =
            filterPanel.getBoundingClientRect();

        menu.classList.add(
            'is-floating'
        );

        menu.style.left =
            `${buttonRect.left - panelRect.left}px`;

        menu.style.right =
            'auto';

        menu.style.width =
            `${buttonRect.width}px`;

        if (shouldOpenUp) {
            menu.style.top =
                'auto';

            menu.style.bottom =
                `${panelRect.bottom -
                buttonRect.top +
                8
                }px`;
        } else {
            menu.style.top =
                `${buttonRect.bottom -
                panelRect.top +
                8
                }px`;

            menu.style.bottom =
                'auto';
        }
    }

    menu.style.display =
        previousDisplay;

    menu.style.visibility =
        previousVisibility;

    menu.style.pointerEvents =
        previousPointerEvents;

    menu.style.transition =
        previousTransition;
}

export function initCustomSelects(root = document) {
    const scope =
        root && typeof root.querySelectorAll === 'function'
            ? root
            : document;

    const customSelectSelector = [
        'select.js-custom-select',

        '[data-global-selects] select:not([data-native-select])' +
        ':not(.global-page-size-native)' +
        ':not(.flatpickr-monthDropdown-months)'
    ].join(',');

    scope.querySelectorAll(customSelectSelector).forEach(select => {
        if (select.dataset.customSelectReady === 'true') return;

        select.dataset.customSelectReady = 'true';
        select.classList.add('custom-select-native');

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'custom-select-button';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');

        const value = document.createElement('span');
        value.dataset.customSelectValue = '';

        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-chevron-down';
        icon.setAttribute('aria-hidden', 'true');

        button.append(value, icon);

        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.setAttribute('role', 'listbox');

        Array.from(select.options).forEach(option => {
            if (option.hidden || option.disabled) return;

            const item = document.createElement('button');

            item.type = 'button';
            item.className = 'custom-select-option';
            item.dataset.index = String(option.index);
            item.dataset.value = option.value;
            item.setAttribute('role', 'option');

            const label = document.createElement('span');
            label.textContent = option.textContent.trim();

            const check = document.createElement('i');
            check.className =
                'fa-solid fa-check custom-select-check';
            check.setAttribute('aria-hidden', 'true');

            item.append(label, check);

            item.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                if (select.disabled) return;

                select.selectedIndex =
                    option.index;

                syncCustomSelect(wrapper);

                wrapper.classList.remove(
                    'is-open',
                    'drop-up'
                );

                button.setAttribute(
                    'aria-expanded',
                    'false'
                );

                select.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );

                select.dispatchEvent(
                    new Event('change', {
                        bubbles: true
                    })
                );
            });

            menu.appendChild(item);
        });

        select.parentNode.insertBefore(wrapper, select);

        wrapper.append(select, button, menu);

        button.addEventListener('pointerdown', event => {
            event.stopPropagation();
        });

        menu.addEventListener('pointerdown', event => {
            event.stopPropagation();
        });

        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            if (select.disabled) return;

            const willOpen =
                !wrapper.classList.contains('is-open');

            closeCustomSelects(wrapper);

            if (willOpen) {
                positionCustomSelectMenu(wrapper);
                wrapper.classList.add('is-open');

                if (select.classList.contains('custom-flatpickr-year')) {
                    requestAnimationFrame(() => {
                        const activeOption =
                            wrapper.querySelector('.custom-select-option.is-active');

                        activeOption?.scrollIntoView({
                            block: 'center',
                            inline: 'nearest'
                        });

                        activeOption?.focus({
                            preventScroll: true
                        });
                    });
                }
            } else {
                wrapper.classList.remove('is-open', 'drop-up');
            }

            button.setAttribute(
                'aria-expanded',
                willOpen ? 'true' : 'false'
            );
        });

        select.addEventListener('change', () => {
            syncCustomSelect(wrapper);
        });

        syncCustomSelect(wrapper);
    });
}

document.addEventListener('scroll', event => {
    if (
        event.target instanceof Element &&
        event.target.closest(
            '.filter-drawer-body'
        )
    ) {
        closeCustomSelects();
    }
},
    true
);

document.addEventListener('pointerdown', event => {
    if (
        event.target.closest?.(
            '.custom-select'
        )
    ) {
        return;
    }

    closeCustomSelects();
}
);

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        closeCustomSelects();
    }
});

window.initCustomSelects = initCustomSelects;
window.syncCustomSelect = syncCustomSelect;

function bootCustomSelects() {
    initCustomSelects(
        document
    );
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        bootCustomSelects,
        {
            once: true
        }
    );
} else {
    bootCustomSelects();
}