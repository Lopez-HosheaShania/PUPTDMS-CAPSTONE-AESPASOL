<div id="globalDiscardModal" class="discard-modal-overlay" aria-hidden="true">
    <div class="discard-modal-shell" role="dialog" aria-modal="true" aria-labelledby="globalDiscardTitle">
        <div class="discard-modal-header">
            <div class="discard-modal-title-wrap">
                <div class="discard-modal-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div>
                    <h3 id="globalDiscardTitle" class="discard-modal-title">Discard changes</h3>
                    <p id="globalDiscardSubtitle" class="discard-modal-subtitle">
                        You have unsaved changes in this form.
                    </p>
                </div>
            </div>
        </div>

        <div class="discard-modal-body">
            <div class="discard-modal-warning">
                <i class="fa-solid fa-circle-info"></i>
                <p id="globalDiscardMessage">
                    Closing this modal will remove the draft you entered. Do you want to discard your changes?
                </p>
            </div>
        </div>

        <div class="discard-modal-footer">
            <button type="button" class="discard-modal-btn discard-modal-btn-keep" data-discard-keep>
                Keep editing
            </button>

            <button type="button" class="discard-modal-btn discard-modal-btn-save" data-discard-save hidden>
                <i class="fa-solid fa-floppy-disk"></i>
                Save Draft
            </button>

            <button type="button" class="discard-modal-btn discard-modal-btn-discard" data-discard-confirm>
                <i class="fa-solid fa-trash"></i>
                Discard
            </button>
        </div>
    </div>
</div>

<script>
    (() => {
        if (window.DiscardChanges) return;

        const formState = new WeakMap();
        let pendingDiscardCallback = null;
        let pendingDiscardForm = null;
        let lastFocusedElement = null;

        function getDiscardModal() {
            return document.getElementById('globalDiscardModal');
        }

        function getWatchedForms(root = document) {
            const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
            const forms = [];

            if (scope.matches?.('form[data-discard-form]')) {
                forms.push(scope);
            }

            scope.querySelectorAll?.('form[data-discard-form]').forEach(form => forms.push(form));

            return [...new Set(forms)];
        }

        function getControlKey(control, index) {
            return control.name || control.id || `control-${index}`;
        }

        function serializeForm(form) {
            const controls = Array.from(
                form.querySelectorAll(
                    'input, textarea, select, [data-discard-track]'
                )
            ).filter(control => {
                if (control.disabled) return false;

                if (
                    control.dataset.discardIgnore === 'true'
                ) {
                    return false;
                }

                if (
                    control.name === '_token' ||
                    control.name === '_method'
                ) {
                    return false;
                }

                return true;
            });

            return JSON.stringify(
                controls.map((control, index) => {
                    const type =
                        (control.type || '').toLowerCase();

                    const key =
                        control.dataset.discardKey ||
                        getControlKey(control, index);

                    if (
                        control.matches(
                            '[data-discard-track]'
                        )
                    ) {
                        return [
                            key,
                            'custom',
                            control.dataset.discardValue ??
                            control.getAttribute(
                                'aria-pressed'
                            ) ??
                            control.classList.contains(
                                'active'
                            ) ??
                            control.classList.contains(
                                'selected'
                            )
                        ];
                    }

                    if (
                        type === 'checkbox' ||
                        type === 'radio'
                    ) {
                        return [
                            key,
                            type,
                            control.value,
                            control.checked
                        ];
                    }

                    if (
                        control.tagName.toLowerCase() ===
                        'select' &&
                        control.multiple
                    ) {
                        return [
                            key,
                            'select-multiple',
                            Array.from(
                                control.selectedOptions
                            ).map(option => option.value)
                        ];
                    }

                    return [
                        key,
                        type ||
                        control.tagName.toLowerCase(),
                        control.value ?? ''
                    ];
                })
            );
        }

        function captureForm(form) {
            if (!form) return;

            formState.set(form, {
                initial: serializeForm(form),
                submitting: false,
            });

            form.dataset.discardReady = 'true';
        }

        function captureModal(modal) {
            if (!modal) return;
            getWatchedForms(modal).forEach(captureForm);
        }

        function captureAll(root = document) {
            getWatchedForms(root).forEach(captureForm);
        }

        function isFormDirty(form) {
            if (!form) return false;

            const state = formState.get(form);

            if (!state) {
                captureForm(form);
                return false;
            }

            if (state.submitting) return false;

            return serializeForm(form) !== state.initial;
        }

        function isModalDirty(modal) {
            if (!modal) return false;
            return getWatchedForms(modal).some(isFormDirty);
        }

        function getModalText(modal) {
            const form = getWatchedForms(modal)[0];

            return {
                title: form?.dataset.discardTitle || modal?.dataset.discardTitle || 'Discard changes?',
                subtitle: form?.dataset.discardSubtitle || modal?.dataset.discardSubtitle ||
                    'You have unsaved changes in this form.',
                message: form?.dataset.discardMessage || modal?.dataset.discardMessage ||
                    'Closing this modal will remove the draft you entered. Do you want to discard your changes?',
            };
        }

        function openDiscardModal(modal, onDiscard) {
            const discardModal =
                getDiscardModal();

            if (!discardModal) {
                onDiscard?.();
                return;
            }

            const form =
                modal?.matches?.(
                    'form[data-discard-form]'
                ) ?
                modal :
                getWatchedForms(
                    modal
                )[0] || null;

            pendingDiscardCallback =
                onDiscard;

            pendingDiscardForm =
                form;

            lastFocusedElement =
                document.activeElement;

            const text =
                getModalText(modal);

            const title =
                discardModal.querySelector(
                    '#globalDiscardTitle'
                );

            const subtitle =
                discardModal.querySelector(
                    '#globalDiscardSubtitle'
                );

            const message =
                discardModal.querySelector(
                    '#globalDiscardMessage'
                );

            if (title) {
                title.textContent =
                    text.title;
            }

            if (subtitle) {
                subtitle.textContent =
                    text.subtitle;
            }

            if (message) {
                message.textContent =
                    text.message;
            }

            const saveButton =
                discardModal.querySelector(
                    '[data-discard-save]'
                );

            const saveHandlerName =
                form?.dataset
                .discardSaveHandler;

            const canSaveDraft =
                Boolean(
                    saveHandlerName &&
                    typeof window[
                        saveHandlerName
                    ] === 'function'
                );

            if (saveButton) {
                saveButton.hidden = !canSaveDraft;
            }

            discardModal.classList.add(
                'open'
            );

            discardModal.setAttribute(
                'aria-hidden',
                'false'
            );

            window.syncModalBackground?.();

            document.body.classList.add(
                'modal-lock',
                'discard-lock'
            );

            requestAnimationFrame(
                () => {
                    discardModal
                        .querySelector(
                            '[data-discard-keep]'
                        )
                        ?.focus();
                }
            );
        }

        function closeDiscardModal() {
            const discardModal = getDiscardModal();

            if (!discardModal) return;

            discardModal.classList.remove('open');
            discardModal.setAttribute('aria-hidden', 'true');
            window.syncModalBackground?.();
            pendingDiscardCallback = null;
            pendingDiscardForm = null;
            document.body.classList.remove('discard-lock');

            if (!document.querySelector('.ui-modal.open, .modal-overlay.open')) {
                document.body.classList.remove('modal-lock');
            }

            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }

            lastFocusedElement = null;
        }

        async function discardNow() {
            const callback =
                pendingDiscardCallback;

            const form =
                pendingDiscardForm;

            const handlerName =
                form?.dataset
                .discardHandler;

            if (
                handlerName &&
                typeof window[
                    handlerName
                ] === 'function'
            ) {
                const result =
                    await window[
                        handlerName
                    ](form);

                if (result === false) {
                    return;
                }
            }

            closeDiscardModal();

            callback?.();
        }

        async function saveDraftNow() {
            const callback =
                pendingDiscardCallback;

            const form =
                pendingDiscardForm;

            const handlerName =
                form?.dataset
                .discardSaveHandler;

            if (
                !handlerName ||
                typeof window[
                    handlerName
                ] !== 'function'
            ) {
                return;
            }

            const discardModal =
                getDiscardModal();

            const saveButton =
                discardModal?.querySelector(
                    '[data-discard-save]'
                );

            const originalHtml =
                saveButton?.innerHTML;

            if (saveButton) {
                saveButton.disabled = true;

                saveButton.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            Saving...
        `;
            }

            try {
                const result =
                    await window[
                        handlerName
                    ](form);

                if (result === false) {
                    return;
                }

                closeDiscardModal();

                callback?.();

            } catch (error) {
                console.error(
                    'Unable to save draft.',
                    error
                );

            } finally {
                if (saveButton) {
                    saveButton.disabled =
                        false;

                    saveButton.innerHTML =
                        originalHtml;
                }
            }
        }

        function confirmClose(modalOrId, onDiscard) {
            const modal = typeof modalOrId === 'string' ?
                document.getElementById(modalOrId) :
                modalOrId;

            if (!modal || !isModalDirty(modal)) {
                onDiscard?.();
                return true;
            }

            openDiscardModal(modal, onDiscard);
            return false;
        }

        function shouldPrompt(modalOrId) {
            const modal = typeof modalOrId === 'string' ?
                document.getElementById(modalOrId) :
                modalOrId;

            return isModalDirty(modal);
        }

        function markSubmitting(form) {
            const state = formState.get(form) || {};
            state.submitting = true;
            formState.set(form, state);
        }

        function markNotSubmitting(form) {
            if (!form) return;

            const state = formState.get(form);

            if (!state) {
                captureForm(form);
                return;
            }

            state.submitting = false;
            formState.set(form, state);
        }

        document.addEventListener('DOMContentLoaded', () => {
            captureAll(document);

            document.addEventListener('submit', event => {
                const form = event.target.closest?.('form[data-discard-form]');
                if (form) markSubmitting(form);
            }, true);

            document.addEventListener('input', event => {
                const form = event.target.closest?.('form[data-discard-form]');
                if (form && form.dataset.discardReady !== 'true') captureForm(form);
            }, true);

            document.addEventListener('change', event => {
                const form = event.target.closest?.('form[data-discard-form]');
                if (form && form.dataset.discardReady !== 'true') captureForm(form);
            }, true);
        });

        document.addEventListener('ui-modal:opened', event => {
            captureModal(event.detail?.modal);
        });

        document.addEventListener('click', event => {
            const closeButton = event.target.closest('[data-discard-close]');
            if (!closeButton) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            const modalId = closeButton.dataset.discardClose || closeButton.closest(
                '.ui-modal, .modal-overlay')?.id;
            const modal = document.getElementById(modalId);

            confirmClose(modal, () => {
                if (typeof window.forceCloseModal === 'function') {
                    window.forceCloseModal(modalId);
                } else if (typeof window.closeModal === 'function') {
                    window.closeModal(modalId, {
                        force: true
                    });
                } else {
                    modal?.classList.remove('open');
                }
            });
        }, true);

        document.addEventListener(
            'click',
            event => {
                if (
                    event.target.closest(
                        '[data-discard-keep]'
                    )
                ) {
                    event.preventDefault();

                    closeDiscardModal();

                    return;
                }

                if (
                    event.target.closest(
                        '[data-discard-save]'
                    )
                ) {
                    event.preventDefault();

                    saveDraftNow();

                    return;
                }

                if (
                    event.target.closest(
                        '[data-discard-confirm]'
                    )
                ) {
                    event.preventDefault();

                    discardNow();
                }
            }
        );

        document.addEventListener('keydown', event => {
            const discardModal = getDiscardModal();
            if (!discardModal?.classList.contains('open')) return;

            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopImmediatePropagation();
                closeDiscardModal();
            }
        });

        document.addEventListener(
            'click',
            event => {
                const link =
                    event.target.closest(
                        '[data-discard-navigation]'
                    );

                if (!link) {
                    return;
                }

                const href =
                    link.getAttribute(
                        'href'
                    );

                if (
                    !href ||
                    href === '#' ||
                    link.target === '_blank'
                ) {
                    return;
                }

                const formSelector =
                    link.dataset
                    .discardFormTarget;

                const form =
                    formSelector ?
                    document.querySelector(
                        formSelector
                    ) :
                    document.querySelector(
                        'form[data-discard-form]'
                    );

                if (
                    !form ||
                    !isFormDirty(form)
                ) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                openDiscardModal(
                    form,
                    () => {
                        window.location.href =
                            href;
                    }
                );
            },
            true
        );

        window.DiscardChanges = {
            captureForm,
            captureModal,
            captureAll,
            isFormDirty,
            isModalDirty,
            shouldPrompt,
            confirmClose,
            closeDiscardModal,
            markSubmitting,
            markNotSubmitting,
        };
    })();
</script>
