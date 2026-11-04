
const SIGNATURE_MODAL_ID = 'signaturePreviewModal';

function openSignaturePreview(url, patientName = '') {
    const modal = document.getElementById(SIGNATURE_MODAL_ID);
    const image = document.getElementById('signaturePreviewImage');

    if (!modal || !image || !url) return;

    let resolvedUrl;

    try {
        resolvedUrl = new URL(url, window.location.origin);
    } catch {
        return;
    }

    if (
        !['http:', 'https:'].includes(resolvedUrl.protocol) ||
        resolvedUrl.origin !== window.location.origin
    ) {
        return;
    }

    image.src = resolvedUrl.href;
    image.alt = patientName
        ? `Signature of ${patientName}`
        : 'Patient signature';

    window.openModal?.(SIGNATURE_MODAL_ID);
}

document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-signature-preview]');

    if (!trigger) return;

    event.preventDefault();

    openSignaturePreview(
        trigger.dataset.signaturePreviewUrl,
        trigger.dataset.signaturePreviewName
    );
});
