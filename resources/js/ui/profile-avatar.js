function getPatientInitials(name) {
    const parts =
        String(name || 'Patient')
            .trim()
            .split(/\s+/)
            .filter(Boolean);

    if (!parts.length) {
        return 'P';
    }

    return parts
        .slice(0, 2)
        .map(
            part =>
                part
                    .charAt(0)
                    .toUpperCase()
        )
        .join('');
}

function safePatientUrl(value) {
    const rawValue =
        String(value ?? '')
            .trim();

    if (!rawValue) {
        return '';
    }

    try {
        const url =
            new URL(
                rawValue,
                window.location.origin
            );

        if (
            ![
                'http:',
                'https:',
            ].includes(
                url.protocol
            )
        ) {
            return '';
        }

        return url.href;
    } catch {
        return '';
    }
}

function getPatientRoleClass(type) {
    const value =
        String(type || '')
            .trim()
            .toLowerCase();

    if (
        value.includes('faculty')
    ) {
        return 'role-faculty';
    }

    if (
        value.includes('admin') ||
        value.includes(
            'administrative'
        )
    ) {
        return 'role-admin';
    }

    if (
        value.includes('student')
    ) {
        return 'role-student';
    }

    if (
        value.includes('patient')
    ) {
        return 'role-patient';
    }

    if (
        value.includes('guest')
    ) {
        return 'role-guest';
    }

    if (
        value.includes('dentist')
    ) {
        return 'role-dentist';
    }

    return 'role-none';
}

function getPatientRoleKey(type) {
    const roleClass =
        getPatientRoleClass(type);

    return roleClass.replace(
        'role-',
        ''
    );
}

function buildPatientAvatarHtml(
    {
        name = 'Patient',
        url = '',
        size = 'md',
        escapeHtml = null,
    } = {}
) {
    const safeName =
        String(name || 'Patient');

    const initials =
        getPatientInitials(
            safeName
        );

    const avatarUrl =
        safePatientUrl(url);

    const escape =
        typeof escapeHtml ===
            'function'
            ? escapeHtml
            : value =>
                String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll(
                        "'",
                        '&#039;'
                    );

    const sizeClass =
        ['sm', 'md', 'lg'].includes(
            size
        )
            ? `patient-avatar-${size}`
            : 'patient-avatar-md';

    return `
        <span
            class="
                patient-avatar
                ${sizeClass}
            "
        >
            ${avatarUrl
            ? `
                        <img
                            src="${escape(
                avatarUrl
            )}"
                            alt="${escape(
                safeName
            )}"
                            loading="lazy"
                        >
                    `
            : `
                        <span>
                            ${escape(
                initials
            )}
                        </span>
                    `
        }
        </span>
    `;
}

function renderPatientAvatarElement(
    element
) {
    if (!element) {
        return;
    }

    const name =
        element.dataset.patientName ||
        'Patient';

    const url =
        safePatientUrl(
            element.dataset.patientUrl ||
            ''
        );

    const initials =
        getPatientInitials(
            name
        );

    element.replaceChildren();

    if (url) {
        const image =
            document.createElement('img');

        image.src = url;
        image.alt = name;
        image.loading = 'lazy';

        image.addEventListener(
            'error',
            () => {
                const fallback =
                    document.createElement(
                        'span'
                    );

                fallback.textContent =
                    initials;

                element.replaceChildren(
                    fallback
                );
            },
            {
                once: true,
            }
        );

        element.appendChild(
            image
        );

        return;
    }

    const fallback =
        document.createElement(
            'span'
        );

    fallback.textContent =
        initials;

    element.appendChild(
        fallback
    );
}

function initPatientAvatars(
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
            '[data-patient-avatar]'
        )
        .forEach(element => {
            if (
                element.dataset
                    .patientAvatarReady ===
                'true'
            ) {
                return;
            }

            element.dataset
                .patientAvatarReady =
                'true';

            renderPatientAvatarElement(
                element
            );
        });
}

window.PatientUI = {
    getInitials:
        getPatientInitials,

    safeUrl:
        safePatientUrl,

    getRoleClass:
        getPatientRoleClass,

    getRoleKey:
        getPatientRoleKey,

    buildAvatarHtml:
        buildPatientAvatarHtml,

    renderAvatar:
        renderPatientAvatarElement,

    initAvatars:
        initPatientAvatars,
};

function bootPatientAvatars() {
    initPatientAvatars(
        document
    );
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        bootPatientAvatars,
        {
            once: true
        }
    );
} else {
    bootPatientAvatars();
}

document.addEventListener(
    'patient-avatar:refresh',
    event => {
        initPatientAvatars(
            event?.detail?.root ||
            document
        );
    }
);