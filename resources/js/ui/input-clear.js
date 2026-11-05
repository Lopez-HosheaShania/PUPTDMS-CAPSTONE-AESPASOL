function clearSearchInput(input, options = {}) {
    if (!input) return;

    const shouldFocus = options.focus !== false;

    input.value = '';

    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));

    if (shouldFocus) {
        input.focus();
    }
}

function getInputClearButton(input) {
    if (!input) return null;

    if (input.matches('[data-search-input]')) {
        return input
            .closest('[data-search-wrapper]')
            ?.querySelector(
                '[data-search-clear]'
            ) || null;
    }

    return input
        .closest('[data-clearable-field]')
        ?.querySelector(
            '[data-field-clear]'
        ) || null;
}

function syncInputClearButton(input) {
    const clearButton =
        getInputClearButton(input);

    if (!input || !clearButton) return;

    clearButton.classList.toggle(
        'show',
        String(input.value || '')
            .trim()
            .length > 0
    );
}

function bindInputClearButton(input) {
    if (
        !input ||
        input.dataset.clearButtonInitialized ===
        'true'
    ) {
        return;
    }

    const clearButton =
        getInputClearButton(input);

    if (!clearButton) return;

    input.dataset.clearButtonInitialized =
        'true';

    const sync = () => {
        syncInputClearButton(input);
    };

    input.addEventListener('input', sync);
    input.addEventListener('change', sync);

    clearButton.addEventListener(
        'click',
        event => {
            event.preventDefault();
            event.stopPropagation();

            clearSearchInput(input);
            sync();
        }
    );

    sync();
}

function initSearchClearButtons(
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
            [
                '[data-search-input]',
                '[data-clearable-input]'
            ].join(',')
        )
        .forEach(bindInputClearButton);
}

document.addEventListener(
    'reset',
    event => {
        requestAnimationFrame(() => {
            event.target
                ?.querySelectorAll?.(
                    [
                        '[data-search-input]',
                        '[data-clearable-input]'
                    ].join(',')
                )
                .forEach(
                    syncInputClearButton
                );
        });
    }
);

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initSearchClearButtons();
    }
);

document.addEventListener('click', function (event) {
    const clearButton = event.target.closest('[data-clear-search]');
    if (!clearButton) return;

    event.preventDefault();

    const targetSelector = clearButton.dataset.searchTarget || '[data-search-input]';
    const input = document.querySelector(targetSelector);

    clearSearchInput(input);
});

window.syncInputClearButton = syncInputClearButton;
window.clearSearchInput = clearSearchInput;
window.initSearchClearButtons = initSearchClearButtons;