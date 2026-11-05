function getGlobalPageSizeControl(inputOrSelector) {
    if (!inputOrSelector) return null;

    const input = typeof inputOrSelector === 'string'
        ? document.querySelector(inputOrSelector)
        : inputOrSelector;

    if (!input) return null;

    return document.querySelector(`[data-global-page-size][data-page-size-input="#${input.id}"]`)
        || input.closest('[data-global-page-size]');
}

function syncGlobalPageSizeSelect(controlOrInput, value) {
    const control = controlOrInput?.matches?.('[data-global-page-size]')
        ? controlOrInput
        : getGlobalPageSizeControl(controlOrInput);

    if (!control) return;

    const inputSelector = control.dataset.pageSizeInput;
    const nativeInput = inputSelector ? document.querySelector(inputSelector) : control.querySelector('.global-page-size-native');
    const nextValue = String(value || nativeInput?.value || control.dataset.defaultValue || '10');

    if (nativeInput) nativeInput.value = nextValue;

    control.querySelectorAll('[data-page-size-value]').forEach(label => {
        label.textContent = nextValue;
    });

    control.querySelectorAll('[data-page-size-option], .global-page-size-option').forEach(option => {
        const selected = String(option.dataset.value) === nextValue;

        option.classList.toggle('is-selected', selected);
        option.classList.toggle('is-active', selected);
        option.classList.toggle('active', selected);
        option.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
}

function closeGlobalPageSizeSelect(control) {
    if (!control) return;

    control.classList.remove('open');

    const trigger = control.querySelector('[data-page-size-trigger]');
    trigger?.setAttribute('aria-expanded', 'false');
}

function openGlobalPageSizeSelect(control) {
    document.querySelectorAll('[data-global-page-size].open').forEach(item => {
        if (item !== control) closeGlobalPageSizeSelect(item);
    });

    control.classList.add('open');

    const trigger = control.querySelector('[data-page-size-trigger]');
    trigger?.setAttribute('aria-expanded', 'true');
}

function setGlobalPageSizeValue(control, value) {
    const inputSelector = control.dataset.pageSizeInput;
    const nativeInput = inputSelector ? document.querySelector(inputSelector) : control.querySelector('.global-page-size-native');
    const nextValue = String(value || '10');

    if (nativeInput) {
        nativeInput.value = nextValue;
        nativeInput.dispatchEvent(new Event('input', { bubbles: true }));
        nativeInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    syncGlobalPageSizeSelect(control, nextValue);

    const callbackName = control.dataset.pageSizeCallback;

    if (callbackName && typeof window[callbackName] === 'function') {
        window[callbackName](Number(nextValue) || nextValue, control);
    }
}

function initGlobalPageSizeSelects(root = document) {

    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

    scope.querySelectorAll('[data-global-page-size]').forEach(control => {
        if (control.dataset.pageSizeInitialized === 'true') {
            syncGlobalPageSizeSelect(control);
            return;
        }

        control.dataset.pageSizeInitialized = 'true';

        const trigger = control.querySelector('[data-page-size-trigger]');
        const inputSelector = control.dataset.pageSizeInput;
        const nativeInput = inputSelector ? document.querySelector(inputSelector) : control.querySelector('.global-page-size-native');

        trigger?.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            control.classList.contains('open')
                ? closeGlobalPageSizeSelect(control)
                : openGlobalPageSizeSelect(control);
        });

        control.querySelectorAll('[data-page-size-option], .global-page-size-option').forEach(option => {
            option.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                setGlobalPageSizeValue(control, option.dataset.value || '10');
                closeGlobalPageSizeSelect(control);
            });
        });

        nativeInput?.addEventListener('change', () => {
            syncGlobalPageSizeSelect(control, nativeInput.value);
        });

        syncGlobalPageSizeSelect(control, nativeInput?.value);
    });
}

document.addEventListener('click', event => {
    if (event.target.closest('[data-global-page-size]')) return;

    document.querySelectorAll('[data-global-page-size].open').forEach(closeGlobalPageSizeSelect);
});

document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;

    document.querySelectorAll('[data-global-page-size].open').forEach(closeGlobalPageSizeSelect);
});

document.addEventListener('DOMContentLoaded', () => {
    initGlobalPageSizeSelects();
});

window.initGlobalPageSizeSelects = initGlobalPageSizeSelects;
window.syncGlobalPageSizeSelect = syncGlobalPageSizeSelect;
window.setGlobalPageSizeValue = setGlobalPageSizeValue;