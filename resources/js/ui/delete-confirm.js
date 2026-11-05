export function openDeleteConfirmModal({
    modalId,
    formId,
    nameId,
    action,
    itemName,
    recordId = null,
} = {}) {
    const modal =
        document.getElementById(modalId);

    const form =
        document.getElementById(formId);

    const name =
        document.getElementById(nameId);

    if (!modal || !form || !name) {
        console.error(
            'Global delete modal elements not found.',
            {
                modalId,
                formId,
                nameId,
            }
        );

        return null;
    }

    form.action =
        String(action || '');

    name.textContent =
        String(itemName || '');

    if (recordId !== null) {
        form.dataset.recordId =
            String(recordId);
    }

    window.openModal?.(modalId);

    return form;
}