import '../forms/validation.js';
import '../forms/booking-signature.js';
import { validateSignatureUpload } from '../forms/signature-validation.js';

function initReportSignatory() {
    const modal = document.getElementById('reportSignatoryModal');
    const form = modal?.querySelector('form');
    if (!form) return;

    const name = form.querySelector('#signatoryName');
    const license = form.querySelector('#signatoryLicense');
    const input = form.querySelector('#signatorySignature');
    const sourceInput = form.querySelector('#reportSignatureSource');
    const group = input.closest('[data-global-field]');
    const preview = form.querySelector('#signaturePreview');
    const image = form.querySelector('#signaturePreviewImage');
    const label = form.querySelector('#signaturePreviewLabel');
    const status = form.querySelector('#signatureFileStatus');
    const clear = form.querySelector('#clearSignatureSelection');
    const save = form.querySelector('[type="submit"]');
    const saveLabel = save.querySelector('[data-signatory-save-label]');
    const saveIcon = save.querySelector('i');
    const savedSource = image.getAttribute('src');
    let objectUrl;
    let controller;
    let revision = 0;
    let verifiedFile = null;
    let checking = false;
    let submitting = false;
    let validationError = '';
    let signatureOrigin = 'upload';
    let drawingPending = false;

    function syncSaveState() {
        const busy = checking || submitting;
        save.disabled = busy;
        save.setAttribute('aria-busy', String(busy));
        saveLabel.textContent = checking
            ? (signatureOrigin === 'drawn' ? 'Preparing signature...' : 'Checking signature...')
            : submitting ? 'Saving...' : 'Save';
        saveIcon.classList.toggle('fa-check', !busy);
        saveIcon.classList.toggle('fa-spinner', busy);
        saveIcon.classList.toggle('fa-spin', busy);
    }

    window.bindFormInputValidation(form.parentElement);
    window.BookingSignature.init(modal);
    const drawing = form.querySelector('[data-report-signature-drawing]');
    const drawingRoot = drawing.querySelector('[data-booking-signature]');
    const drawingController = window.BookingSignature.get(drawingRoot);

    drawing.addEventListener('toggle', () => {
        if (drawing.open) requestAnimationFrame(() => drawingController.resize());
    });
    document.addEventListener('ui-modal:opened', event => {
        if (event.detail?.modal === modal && drawing.open) {
            requestAnimationFrame(() => drawingController.resize());
        }
    });
    drawingRoot.addEventListener('booking-signature:ready', event => {
        if (!event.detail?.file) return;
        const transfer = new DataTransfer();
        transfer.items.add(event.detail.file);
        input.files = transfer.files;
        drawingPending = false;
        input.dispatchEvent(new CustomEvent('change', { bubbles: true, detail: { source: 'drawn' } }));
    });
    drawingRoot.addEventListener('booking-signature:changed', () => {
        drawingPending = !drawingController.isBlank();
        if (signatureOrigin === 'drawn') {
            input.value = '';
            validateSelection();
        }
    });

    function showError(message) {
        validationError = message;
        input.setCustomValidity(message);
        input.setAttribute('aria-invalid', 'true');
        input.classList.add('is-invalid');
        window.showGlobalGroupError(group, input.id, message);
        group.querySelector(`[data-error-for="${input.id}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function restorePreview() {
        if (savedSource) image.src = savedSource;
        else image.removeAttribute('src');
        preview.hidden = !savedSource;
        label.textContent = 'Saved signature';
    }

    async function validateSelection() {
        const currentRevision = ++revision;
        controller?.abort();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        verifiedFile = null;
        validationError = '';
        checking = false;
        syncSaveState();
        input.setCustomValidity('');
        input.removeAttribute('aria-invalid');
        input.classList.remove('is-invalid');
        window.clearGlobalGroupError(group, input.id);
        status.hidden = true;
        status.classList.remove('is-success');
        status.setAttribute('aria-live', 'polite');
        restorePreview();

        const file = input.files[0];
        const isDrawn = signatureOrigin === 'drawn';
        sourceInput.value = file && isDrawn ? 'drawn' : 'upload';
        clear.hidden = !file;
        if (!file) return;

        if (!['image/png', 'image/jpeg'].includes(file.type)) {
            showError('Signature must be a JPG or PNG file.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            showError('The signature image must be 2 MB or smaller.');
            return;
        }

        checking = true;
        syncSaveState();
        status.textContent = isDrawn ? 'Preparing drawn signature...' : 'Checking signature image...';
        status.hidden = false;
        controller = new AbortController();
        const signal = controller.signal;
        const validationController = controller;
        const timeout = window.setTimeout(() => {
            validationController.abort(new DOMException('Signature verification timed out. Please try again.', 'TimeoutError'));
        }, 75000);
        objectUrl = URL.createObjectURL(file);
        const selectedSource = objectUrl;

        try {
            const decodedImage = new Image();
            decodedImage.src = selectedSource;
            await decodedImage.decode();
            if (currentRevision !== revision) return;

            if (!isDrawn && (decodedImage.width < 120 || decodedImage.height < 60)) {
                throw new Error('Signature image is too small. Please upload a clearer signature.');
            }
            if (decodedImage.width > 4000 || decodedImage.height > 4000) {
                throw new Error('Signature image is too large. Please upload a smaller image (maximum 4000 × 4000).');
            }

            image.src = selectedSource;
            preview.hidden = false;
            label.textContent = isDrawn ? 'Drawn signature' : 'Checking selected signature';
            status.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

            const result = isDrawn
                ? { accepted: true, message: 'Drawn signature accepted.' }
                : await validateSignatureUpload(file, form.dataset.signatoryValidationUrl, {
                    fieldName: 'signature',
                    signal,
                });
            if (currentRevision !== revision) return;

            if (result.accepted !== true) throw new Error('Unable to check the signature. Please try again.');
            verifiedFile = file;
            validationError = '';
            input.setCustomValidity('');
            input.removeAttribute('aria-invalid');
            input.classList.remove('is-invalid');
            window.clearGlobalGroupError(group, input.id);
            image.src = selectedSource;
            preview.hidden = false;
            label.textContent = 'Selected signature';
            status.textContent = `${file.name} — ${result.message || 'Signature verified and accepted'}`;
            status.classList.add('is-success');
        } catch (error) {
            if (currentRevision !== revision || error.name === 'AbortError') return;
            status.hidden = true;
            if (image.getAttribute('src') === selectedSource) label.textContent = 'Signature not verified';
            showError(error.data?.errors?.signature?.[0] || error.data?.reason ||
                (error.name === 'EncodingError'
                    ? 'This image could not be opened. Please choose another PNG or JPG.'
                    : error instanceof TypeError
                        ? 'Unable to check the signature. Please select the file again to retry.'
                        : error.message));
        } finally {
            window.clearTimeout(timeout);
            if (currentRevision === revision) {
                checking = false;
                syncSaveState();
            }
        }
    }

    [[name, /[^\p{L}\p{M} ]/u], [license, /[^0-9]/]].forEach(([field, invalid]) => {
        field?.addEventListener('beforeinput', event => {
            if (!event.isComposing && event.inputType === 'insertText' && event.data && invalid.test(event.data)) {
                event.preventDefault();
            }
        });
        field?.addEventListener('blur', () => {
            field.value = field.value.trim();
            window.validateFormInputField(field);
        });
    });

    input.addEventListener('change', event => {
        signatureOrigin = event.detail?.source === 'drawn' ? 'drawn' : 'upload';
        if (signatureOrigin === 'upload') {
            drawingController.clear();
            drawingPending = false;
        }
        validateSelection();
    });
    clear.addEventListener('click', () => {
        input.value = '';
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.focus();
    });
    form.addEventListener('reset', () => requestAnimationFrame(() => {
        drawingController.clear();
        drawingPending = false;
        validateSelection();
    }));
    form.addEventListener('submit', () => {
        name.value = name.value.trim();
        if (license) license.value = license.value.trim();
    }, true);
    form.addEventListener('submit', event => {
        const fieldsValid = window.validateGlobalForm(form).valid;
        const file = input.files[0];
        const signatureValid = !checking && !drawingPending && (file ? verifiedFile === file : !input.required);

        if (submitting || !fieldsValid || !signatureValid) {
            event.preventDefault();
            if (!signatureValid) {
                showError(drawingPending ? 'Click Use Signature to apply your drawing, or clear the pad.' :
                    checking ? 'Please wait while the signature is being checked.' :
                    validationError || 'Please upload a valid signature image.');
            }
            return;
        }

        submitting = true;
        syncSaveState();
    });

    if (form.dataset.signatoryHasErrors === 'true') window.openModal?.('reportSignatoryModal');
    if (form.dataset.signatorySaved) window.showToast?.('success', form.dataset.signatorySaved);
    window.addEventListener('pagehide', () => {
        controller?.abort();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        syncSaveState();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReportSignatory, { once: true });
} else {
    initReportSignatory();
}
