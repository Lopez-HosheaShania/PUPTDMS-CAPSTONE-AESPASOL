function formatPatientName(value = '') {
    const formattedName = String(value || '')
        .trim()
        .replace(/\s+/g, ' ')
        .toLocaleLowerCase('en-PH')
        .replace(
            /(^|[\s.'’\-])\p{L}/gu,
            character =>
                character.toLocaleUpperCase('en-PH')
        );
        
    return formattedName.replace(
        /\b(ii|iii|iv|v|vi|vii|viii|ix|x)\.?$/iu,
        suffix => suffix.toLocaleUpperCase('en-PH')
    );
}

function formatPatientNameElement(element) {
    if (!element) return;

    const currentName =
        element.dataset.patientName ||
        element.textContent ||
        '';

    const formattedName =
        formatPatientName(currentName);

    if (!formattedName) return;

    element.textContent =
        formattedName;
}

function initGlobalPatientNames(root = document) {
    const scope =
        root &&
            typeof root.querySelectorAll ===
            'function'
            ? root
            : document;

    const shouldFormatPatientName =
        element =>
            element &&
            !element.matches(
                '[data-patient-avatar]'
            );

    if (
        scope.matches?.(
            '[data-patient-name]'
        ) &&
        shouldFormatPatientName(
            scope
        )
    ) {
        formatPatientNameElement(
            scope
        );
    }

    scope
        .querySelectorAll?.(
            '[data-patient-name]'
        )
        .forEach(element => {
            if (
                !shouldFormatPatientName(
                    element
                )
            ) {
                return;
            }

            formatPatientNameElement(
                element
            );
        });
}

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initGlobalPatientNames();
    }
);

document.addEventListener(
    'ui-modal:opened',
    event => {
        initGlobalPatientNames(
            event.detail?.modal ||
            document
        );
    }
);

window.formatPatientName = formatPatientName;
window.initGlobalPatientNames = initGlobalPatientNames;
