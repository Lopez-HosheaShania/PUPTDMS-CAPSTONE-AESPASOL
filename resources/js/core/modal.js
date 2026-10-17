
const modalTimers = Object.create(null);
const modalFocusOrigins = new WeakMap();
const originalInertStates = new Map();

const focusableSelector = [
    'a[href]',
    'button:not(:disabled)',
    'input:not(:disabled):not([type="hidden"])',
    'select:not(:disabled)',
    'textarea:not(:disabled)',
    '[tabindex]:not([tabindex="-1"])',
    '[contenteditable="true"]'
].join(',');

function getTopOpenModal() {
    const modals = document.querySelectorAll(
        '.ui-modal.open, .modal-overlay.open'
    );

    return modals[modals.length - 1] || null;
}

function getFocusableElements(modal) {
    return [...modal.querySelectorAll(focusableSelector)]
        .filter(element => {
            return (
                !element.closest('[inert], [aria-hidden="true"]') &&
                element.tabIndex >= 0 &&
                element.getClientRects().length > 0 &&
                getComputedStyle(element).visibility !== 'hidden'
            );
        });
}

function focusModal(modal) {
    const target =
        modal.querySelector('[data-modal-initial-focus]') ||
        getFocusableElements(modal)[0] ||
        modal;

    if (target === modal && !modal.hasAttribute('tabindex')) {
        modal.tabIndex = -1;
    }

    target.focus({ preventScroll: true });
}

function syncModalBackground() {
    for (const [element, originalValue] of originalInertStates) {
        element.inert = originalValue;
    }

    originalInertStates.clear();

    let current = getTopOpenModal();

    while (current && current !== document.body) {
        const parent = current.parentElement;
        if (!parent) break;

        for (const sibling of parent.children) {
            if (
                sibling === current ||
                !(sibling instanceof HTMLElement)
            ) continue;

            originalInertStates.set(sibling, sibling.inert);
            sibling.inert = true;
        }

        current = parent;
    }
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    if (modalTimers[id]) {
        clearTimeout(modalTimers[id]);
        delete modalTimers[id];
    }

    if (!modal.classList.contains('open')) {
        modalFocusOrigins.set(modal, document.activeElement);
    }

    modal.classList.remove('closing');
    modal.classList.add('open');

    modal.setAttribute('aria-hidden', 'false');
    modal.inert = false;

    document.body.classList.add('modal-lock');

    syncModalBackground();
    focusModal(modal);

    document.dispatchEvent(
        new CustomEvent('ui-modal:opened', {
            detail: { modal }
        })
    );
}

function closeModal(id) {
    const modal = document.getElementById(id);

    if (
        !modal ||
        (
            !modal.classList.contains('open') &&
            !modal.classList.contains('closing')
        )
    ) return;

    const focusedInside = modal.contains(document.activeElement);
    const previousFocus = modalFocusOrigins.get(modal);

    modal.classList.remove('open');
    modal.classList.add('closing');

    syncModalBackground();

    if (focusedInside) {
        let restored = false;

        if (
            previousFocus instanceof HTMLElement &&
            previousFocus.isConnected &&
            previousFocus !== document.body &&
            !previousFocus.closest('[inert]')
        ) {
            previousFocus.focus({ preventScroll: true });
            restored = document.activeElement === previousFocus;
        }

        if (!restored) {
            const activeModal = getTopOpenModal();

            if (activeModal) {
                focusModal(activeModal);
            } else {
                document.activeElement?.blur?.();
            }
        }
    }

    modal.setAttribute('aria-hidden', 'true');
    modal.inert = true;

    if (modalTimers[id]) {
        clearTimeout(modalTimers[id]);
    }

    const duration = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches ? 0 : 180;

    modalTimers[id] = setTimeout(() => {
        modal.classList.remove('closing');
        modal.inert = false;

        syncModalBackground();

        delete modalTimers[id];

        const anotherModal = document.querySelector(
            '.ui-modal.open, .ui-modal.closing, ' +
            '.modal-overlay.open, .modal-overlay.closing'
        );

        if (!anotherModal) {
            document.body.classList.remove('modal-lock');
        }

        document.dispatchEvent(
            new CustomEvent('ui-modal:closed', {
                detail: { modal }
            })
        );
    }, duration);
}

function closeModalOnBackdrop(event, id) {
    return false;
}

document.addEventListener('click', event => {
    const button = event.target.closest('[data-open-modal]');
    if (!button?.dataset.openModal) return;

    event.preventDefault();
    openModal(button.dataset.openModal);
});

document.addEventListener('click', event => {
    const button = event.target.closest('[data-modal-close]');
    if (!button?.dataset.modalClose) return;

    event.preventDefault();
    event.stopPropagation();

    closeModal(button.dataset.modalClose);
});

document.addEventListener('click', event => {
    const button = event.target.closest(
        '[data-start-modal-confirm]'
    );

    if (!button) return;

    const modal = button.closest(
        '[data-start-procedure-modal]'
    );

    if (!modal) return;

    const startUrl = modal.dataset.startUrl;

    if (!startUrl) {
        console.error('Start Procedure URL is missing.');
        return;
    }

    event.preventDefault();
    window.location.href = startUrl;
});

document.addEventListener('keydown', event => {
    const modal = getTopOpenModal();
    if (!modal) return;

    if (event.key === 'Tab') {
        const elements = getFocusableElements(modal);

        if (elements.length === 0) {
            event.preventDefault();
            focusModal(modal);
            return;
        }

        const first = elements[0];
        const last = elements[elements.length - 1];
        const active = document.activeElement;
        const isTabbable = elements.includes(active);

        if (
            event.shiftKey &&
            (!isTabbable || active === first)
        ) {
            event.preventDefault();
            last.focus();

        } else if (
            !event.shiftKey &&
            (!isTabbable || active === last)
        ) {
            event.preventDefault();
            first.focus();
        }

        return;
    }

    if (event.key !== 'Escape') return;

    event.preventDefault();
    event.stopPropagation();

    if (modal.hasAttribute('data-modal-static')) {
        event.stopImmediatePropagation();
        return;
    }

    const hasDiscardForm = modal.querySelector(
        'form[data-discard-form]'
    );

    if (hasDiscardForm && window.DiscardChanges) {
        event.stopImmediatePropagation();

        window.DiscardChanges.confirmClose(
            modal,
            () => closeModal(modal.id)
        );

        return;
    }

    closeModal(modal.id);
});

window.openModal = openModal;
window.closeModal = closeModal;
window.closeModalOnBackdrop = closeModalOnBackdrop;

window.openInventoryModal = openModal;
window.closeInventoryModal = closeModal;
window.closeOnBackdrop = closeModalOnBackdrop;
