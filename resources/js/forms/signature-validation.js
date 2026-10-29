export async function validateSignatureUpload(file, validationUrl, {
    fieldName = 'patient_signature',
    declinedMessage = 'Signature could not be processed. Please try again.',
    signal,
} = {}) {
    const formData = new FormData();
    formData.append(fieldName, file);

    const response = await fetch(validationUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            Accept: 'application/json',
        },
        body: formData,
        signal,
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || data.valid === false || data.accepted === false) {
        const error = new Error(data.message || declinedMessage);
        error.data = data;
        throw error;
    }

    return data;
}
