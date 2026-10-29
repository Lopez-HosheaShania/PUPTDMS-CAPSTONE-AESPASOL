function escapeValidationHtml(
    value = ''
) {
    if (
        typeof window.escapeHtml ===
        'function'
    ) {
        return window.escapeHtml(
            value
        );
    }

    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function setFieldState(inputId, errorIdOrMessage = '', maybeMessage = null) {
    const message = maybeMessage === null ? errorIdOrMessage : maybeMessage;
    const errorId = maybeMessage === null ? `err-${inputId}` : errorIdOrMessage;

    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);

    if (!input) return;

    if (message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        if (error) {
            error.innerHTML = `<i class="fa-solid fa-circle-exclamation" style="font-size:9px;"></i> ${message}`;
        }
    } else {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');

        if (error) {
            error.innerHTML = '';
        }
    }
}

function updateCharCounter(fieldId, max, counterId = `charCounter-${fieldId}`) {
    const field = document.getElementById(fieldId);
    const counter = document.getElementById(counterId);

    if (!field || !counter) return;

    const limit = Number(max) || 150;

    if (field.value.length > limit) {
        field.value = field.value.slice(0, limit);
    }

    const len = field.value.length;

    counter.textContent = `${len} / ${limit} characters`;
    counter.className = 'char-counter' + (len >= limit ? ' over' : len >= limit * 0.85 ? ' warn' : '');
}

function validateCharLimit(fieldId, max = 150, errorId = null) {
    const field = document.getElementById(fieldId);
    const error = errorId ? document.getElementById(errorId) : null;

    if (!field) return true;

    const limit = Number(max) || 150;

    if (field.value.length > limit) {
        field.value = field.value.slice(0, limit);
    }

    const isValid = field.value.length <= limit;

    field.classList.toggle('is-invalid', !isValid);

    if (error) {
        error.innerHTML = isValid
            ? ''
            : `<i class="fa-solid fa-circle-exclamation" style="font-size:9px;"></i> Maximum of ${limit} characters only.`;
    }

    return isValid;
}

const unsafeFormInputPatterns = [
    /<\s*\/?\s*script\b/i,
    /<\s*(iframe|object|embed|svg|math|link|meta|style)\b/i,
    /\bon[a-z]+\s*=/i,
    /\bjavascript\s*:/i,
    /\bdata\s*:\s*text\/html/i,
    /(?:^|[\s'"`])or\s+1\s*=\s*1(?:$|[\s;#\-)])/i,
    /(?:^|[\s'"`])or\s+['"]?1['"]?\s*=\s*['"]?1['"]?(?:$|[\s;#\-)])/i,
    /;\s*(drop|delete|insert|update|alter|truncate)\s+/i,
    /--\s*$/,
];

function isUnsafeFormText(value = '') {
    const decoded = document.createElement('textarea');
    decoded.innerHTML = String(value);

    return unsafeFormInputPatterns.some(pattern => pattern.test(decoded.value));
}

function isContactInput(field) {
    const key = `${field.name || ''} ${field.id || ''}`.toLowerCase();

    return /(^|[\s._-])(phone|mobile|contact[-_]?number|contact[-_]?no|emergency[-_]?number|emergency[-_]?contact[-_]?no)($|[\s._-])/.test(key);
}

function getGlobalFieldLabel(field) {
    if (!field) {
        return 'This field';
    }

    const explicitLabel = field.id
        ? document.querySelector(
            `label[for="${CSS.escape(field.id)}"]`
        )
        : null;

    const questionRow =
        field.closest('.global-question-row');

    const questionText =
        questionRow
            ?.querySelector('.global-question-text')
            ?.textContent;

    const radioGroupLabel =
        field
            .closest('[role="radiogroup"]')
            ?.getAttribute('aria-label');

    const friendlyNames = {
        name: 'Full Name',
        email: 'Email Address',
        password: 'Password',
        password_confirmation: 'Confirm Password',
        role_id: 'Role',
        status: 'Status',

        last_dental_visit: 'Last Dental Visit',
        previous_dentist: 'Previous Dentist',
        service_type: 'Dental Service',
        emergency_person: 'Emergency Contact Person',
        emergency_number: 'Emergency Contact Number',
        emergency_relation: 'Relation to Patient',
    };

    const humanizedName = String(
        field.name || ''
    )
        .replace(/\[\]$/, '')
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, character =>
            character.toUpperCase()
        );

    const fallbackName =
        friendlyNames[field.name] ||
        humanizedName ||
        'This field';

    const labelText =
        field.dataset.fieldLabel ||
        questionText ||
        radioGroupLabel ||
        explicitLabel?.textContent ||
        field.closest('[data-global-field]')
            ?.querySelector(
                ':scope > label, :scope > .global-form-label'
            )
            ?.textContent ||
        field.closest(
            '.field-group, .form-group, .st-form-group'
        )?.querySelector('label')?.textContent ||
        fallbackName;

    return String(labelText)
        .replace(/\*/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function getFormInputValidationMessage(field) {
    if (!field || field.disabled) return '';

    const requiredWhenVisible =
        field.dataset.requiredWhenVisible ===
        'true' &&
        !field.closest(
            '.hidden, [hidden]'
        );

    const isRequired =
        field.required ||
        requiredWhenVisible;

    if (
        field.readOnly &&
        !isRequired &&
        !field.hasAttribute(
            'data-validation-rule'
        )
    ) {
        return '';
    }

    const type = String(field.type || '').toLowerCase();
    const value = String(field.value || '').trim();
    const label = getGlobalFieldLabel(field);

    if (type === 'radio') {
        if (!isRequired) {
            return '';
        }

        const form = field.form || document;

        const radioGroup = form.querySelectorAll(
            `input[type="radio"][name="${CSS.escape(field.name)}"]`
        );

        const hasChecked = Array
            .from(radioGroup)
            .some(radio => radio.checked);

        if (hasChecked) {
            return '';
        }

        const requiredMessage =
            field.dataset.requiredMessage ||
            Array.from(radioGroup).find(
                radio =>
                    radio.dataset.requiredMessage
            )?.dataset.requiredMessage;

        if (requiredMessage) {
            return requiredMessage;
        }

        const isYesNoQuestion =
            Boolean(
                field.closest(
                    '.global-question-row'
                )
            );

        if (isYesNoQuestion) {
            return 'Please select Yes or No.';
        }

        return `Please select ${label.toLowerCase()}.`;
    }

    if (type === 'checkbox') {
        if (
            isRequired &&
            !field.checked
        ) {
            return (
                field.dataset.requiredMessage ||
                `Please check ${label.toLowerCase()}.`
            );
        }

        return '';
    }

    if (isRequired && !value) {
        if (field.dataset.requiredMessage) {
            return field.dataset.requiredMessage;
        }

        const isConditionalInstruction =
            /^if\s+(yes|no)\b/i.test(
                label
            );

        if (isConditionalInstruction) {
            return 'Please provide the required details.';
        }

        if (field instanceof HTMLSelectElement) {
            return `Please select a ${label.toLowerCase()}.`;
        }

        if (type === 'email') {
            return 'Please enter an email address.';
        }

        if (type === 'password') {
            if (field.name === 'password_confirmation') {
                return 'Please confirm the password.';
            }

            return 'Please enter a password.';
        }

        return `Please enter ${label.toLowerCase()}.`;
    }

    if (!value) return '';

    if (type === 'email' && !field.validity.valid) {
        return 'Please enter a valid email address.';
    }

    if (
        field.hasAttribute('minlength') &&
        value.length < Number(field.getAttribute('minlength'))
    ) {
        return field.dataset.minlengthMessage || `${label} must contain at least ${field.getAttribute('minlength')} characters.`;
    }
    if (
        field.name === 'password_confirmation' &&
        field.form
    ) {
        const passwordField =
            field.form.querySelector('[name="password"]');

        if (
            passwordField &&
            value !== String(passwordField.value || '')
        ) {
            return 'Passwords do not match.';
        }
    }
    if (
        field.hasAttribute('maxlength') &&
        value.length > Number(field.getAttribute('maxlength'))
    ) {
        return field.dataset.maxlengthMessage || `${label} must not exceed ${field.getAttribute('maxlength')} characters.`;
    }

    if (field.validity.patternMismatch) {
        return field.dataset.patternMessage ||
            `Please enter a valid ${label.toLowerCase()}.`;
    }

    if (isContactInput(field)) {
        const digits = value.replace(/\D/g, '');

        if (digits && !digits.startsWith('09')) {
            return 'Contact number must start with 09.';
        }

        if (digits && digits.length !== 11) {
            return 'Contact number must contain exactly 11 digits.';
        }
    }

    if (isUnsafeFormText(value)) {
        return 'Please enter readable text only. Scripts and SQL-like input are not allowed.';
    }
    const customRuleMessage = runGlobalValidationRule(field);

    if (customRuleMessage) {
        return customRuleMessage;
    }
    return '';
}

const globalValidationRules = new Map();

function registerGlobalValidationRule(name, validator) {
    if (!name || typeof validator !== 'function') return;

    globalValidationRules.set(name, validator);
}

registerGlobalValidationRule('bookingDuration', function (field) {
    const value =
        String(
            field.value || ''
        ).trim();

    if (!value) {
        return '';
    }

    if (
        !/^\d{2}:\d{2}:\d{2}$/.test(
            value
        )
    ) {
        return 'Use the HH:MM:SS format.';
    }

    const [
        hours,
        minutes,
        seconds
    ] =
        value
            .split(':')
            .map(Number);

    if (
        minutes > 59 ||
        seconds > 59
    ) {
        return 'Minutes and seconds must be between 00 and 59.';
    }

    if (
        hours === 0 &&
        minutes === 0 &&
        seconds === 0
    ) {
        return 'Procedure duration must be greater than 00:00:00.';
    }

    return '';
}
);

registerGlobalValidationRule('philippineMobile', function (field) {
    const digits =
        String(field.value || '')
            .replace(/\D/g, '');

    if (!digits) {
        return '';
    }

    if (!digits.startsWith('09')) {
        return 'Contact number must start with 09.';
    }

    if (digits.length !== 11) {
        return 'Contact number must contain exactly 11 digits.';
    }

    return '';
}
);

function runGlobalValidationRule(field) {
    const ruleName = field?.dataset?.validationRule;

    if (!ruleName) return '';

    const validator = globalValidationRules.get(ruleName);

    if (typeof validator !== 'function') return '';

    return validator(field) || '';
}

registerGlobalValidationRule('notFutureDate', function (field) {
    if (!field.value) return '';

    const picked = new Date(
        `${field.value}T00:00:00`
    );

    if (Number.isNaN(picked.getTime())) {
        return 'Please enter a valid date.';
    }

    const today = new Date();
    today.setHours(23, 59, 59, 999);

    return picked > today
        ? 'Date cannot be in the future.'
        : '';
}
);

registerGlobalValidationRule('wholeNumber', function (field) {
    if (!field.value) return '';

    const value = Number(field.value);

    if (
        !Number.isInteger(value) ||
        value < 0
    ) {
        return 'Please enter a whole number greater than or equal to 0.';
    }

    return '';
}
);

registerGlobalValidationRule('inventoryConsumed', function (field) {
    if (!field.value) return '';

    const consumed = Number(field.value);

    if (
        !Number.isInteger(consumed) ||
        consumed < 0
    ) {
        return 'Consumed must be a whole number greater than or equal to 0.';
    }

    const form = field.form;

    const quantityField =
        form?.querySelector(
            '[name="qty"]'
        );

    const quantity = Number(
        quantityField?.value || 0
    );

    return consumed > quantity
        ? 'Consumed cannot exceed quantity.'
        : '';
}
);

registerGlobalValidationRule('strongPassword', function (field) {
    const value = String(field.value || '');

    if (!value) {
        return '';
    }

    if (value.length < 8) {
        return 'Password must contain at least 8 characters.';
    }

    if (!/[a-z]/.test(value)) {
        return 'Password must contain at least one lowercase letter.';
    }

    if (!/[A-Z]/.test(value)) {
        return 'Password must contain at least one uppercase letter.';
    }

    if (!/\d/.test(value)) {
        return 'Password must contain at least one number.';
    }

    if (!/[^A-Za-z0-9]/.test(value)) {
        return 'Password must contain at least one special character.';
    }

    return '';
}
);

registerGlobalValidationRule('guestName', function (field) {
    const value = String(field.value || '').trim();

    if (!value) {
        return '';
    }

    return /^[A-Za-zÑñ\s.'-]+$/.test(value)
        ? ''
        : 'Only letters, spaces, apostrophe, period, and hyphen are allowed.';
}
);

registerGlobalValidationRule('personName', function (field) {
    const value =
        String(
            field.value || ''
        ).trim();

    if (!value) {
        return '';
    }

    return /^[A-Za-zÑñ\s.'-]+$/
        .test(value)
        ? ''
        : 'Only letters, spaces, apostrophe, period, and hyphen are allowed.';
}
);

registerGlobalValidationRule('studentNumber', function (field) {
    const value = String(field.value || '').trim();

    if (!value) {
        return '';
    }

    return /^\d{4}-\d{5}-TG-\d$/i
        .test(value)
        ? ''
        : 'Student number must follow the format 0000-00000-TG-0.';
}
);

registerGlobalValidationRule('facultyCode', function (field) {
    const value = String(field.value || '').trim();

    if (!value) {
        return '';
    }

    return /^FA\d{4}TG\d{4}$/i
        .test(value)
        ? ''
        : 'Faculty code must follow the format FA0000TG0000.';
}
);

registerGlobalValidationRule('yearLevel', function (field) {
    const value = String(field.value || '').trim();

    if (!value) {
        return '';
    }

    return /^[1-9][0-9]*$/.test(value)
        ? ''
        : 'Please enter a valid year level using whole numbers only.';
}
);

registerGlobalValidationRule('sectionCode', function (field) {
    const value = String(field.value || '').trim();

    if (!value) {
        return '';
    }

    return /^[A-Za-z0-9\s.-]+$/.test(value)
        ? ''
        : 'Section can only contain letters, numbers, spaces, periods, and hyphens.';
}
);

const globalFormValidationRules = new Map();

function registerGlobalFormValidationRule(name, validator) {
    if (!name || typeof validator !== 'function') return;

    globalFormValidationRules.set(name, validator);
}

function runGlobalFormValidationRule(form) {
    const ruleName = form?.dataset?.formValidationRule;

    if (!ruleName) {
        return {
            valid: true,
            firstInvalid: null
        };
    }

    const validator = globalFormValidationRules.get(ruleName);

    if (typeof validator !== 'function') {
        return {
            valid: true,
            firstInvalid: null
        };
    }

    return validator(form) || {
        valid: true,
        firstInvalid: null
    };
}

function ensureGlobalGroupError(group, key) {
    if (!group) return null;

    const container =
        group.closest('[data-global-field]') ||
        group.parentElement;

    if (!container) return null;

    let error = container.querySelector(
        `.global-field-error[data-error-for="${CSS.escape(key)}"]`
    );

    if (!error) {
        error = document.createElement('div');
        error.className = 'global-field-error';
        error.dataset.errorFor = key;
        container.appendChild(error);
    }

    return error;
}

function restartGlobalFieldErrorAnimation(error) {
    if (
        !error ||
        !error.classList.contains(
            'show'
        )
    ) {
        return;
    }

    error.style.animation =
        'none';

    void error.offsetWidth;

    error.style.removeProperty(
        'animation'
    );
}

function cancelGlobalFieldErrorHide(error) {
    if (!error) {
        return;
    }

    if (
        error.__globalFieldErrorHideTimer
    ) {
        window.clearTimeout(
            error.__globalFieldErrorHideTimer
        );

        error.__globalFieldErrorHideTimer =
            null;
    }

    error.classList.remove(
        'is-hiding'
    );
}

function showGlobalFieldErrorElement(error) {
    if (!error) {
        return;
    }

    cancelGlobalFieldErrorHide(
        error
    );

    error.style.removeProperty(
        'display'
    );

    error.classList.remove(
        'hidden'
    );

    error.classList.add(
        'show'
    );

    error.setAttribute(
        'aria-hidden',
        'false'
    );
}

function hideGlobalFieldErrorElement(error) {
    if (!error) {
        return;
    }

    cancelGlobalFieldErrorHide(
        error
    );

    error.style.removeProperty(
        'display'
    );

    if (
        !error.classList.contains(
            'show'
        )
    ) {
        error.innerHTML = '';

        error.classList.remove(
            'is-success'
        );

        error.setAttribute(
            'aria-hidden',
            'true'
        );

        return;
    }

    error.classList.add(
        'is-hiding'
    );

    error.__globalFieldErrorHideTimer =
        window.setTimeout(
            () => {
                error.classList.remove(
                    'show',
                    'is-hiding',
                    'is-success'
                );

                error.innerHTML = '';

                error.setAttribute(
                    'aria-hidden',
                    'true'
                );

                error
                    .__globalFieldErrorHideTimer =
                    null;
            },
            180
        );
}

function showGlobalGroupError(group, key, message) {
    if (!group) return;

    const error =
        ensureGlobalGroupError(
            group,
            key
        );

    group.classList.toggle(
        'is-invalid',
        Boolean(message)
    );

    if (!error) return;

    if (message) {
        error.innerHTML = `
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>${escapeValidationHtml(message)}</span>
    `;

        error.classList.remove('is-success');
        showGlobalFieldErrorElement(error);
        restartGlobalFieldErrorAnimation(error);

        return;
    }

    hideGlobalFieldErrorElement(error);
}

function clearGlobalGroupError(group, key) {
    showGlobalGroupError(group, key, '');
}

function getGlobalFieldContainer(field) {
    if (!field) return null;

    const explicitContainer = field.closest('[data-global-field]');

    if (explicitContainer) {
        return explicitContainer;
    }

    const userManagementContainer = field.closest(
        '.um-field-full, .um-field-grid > div, .um-user-side-card .space-y-4 > div'
    );

    if (userManagementContainer) {
        return userManagementContainer;
    }

    return field.closest(
        '.field-group, .form-group, .st-form-group, [data-field-wrapper]'
    ) || field.parentElement;
}

function getGlobalFieldControlHost(field) {
    if (!field) return null;

    if (field instanceof HTMLSelectElement) {
        return field.closest('.custom-select') || field;
    }

    if (field.type === 'radio' || field.type === 'checkbox') {
        return field.closest(
            '.global-choice-group, .um-status-grid, .radio-group, .checkbox-group'
        ) || field;
    }

    return field;
}

function ensureGlobalFieldError(field) {
    const container = getGlobalFieldContainer(field);

    if (!container) return null;

    const fieldKey = field.id || field.name;

    if (!fieldKey) return null;

    let error = container.querySelector(
        `.global-field-error[data-error-for="${CSS.escape(fieldKey)}"]`
    );

    if (error) {
        return error;
    }

    error = document.createElement('div');
    error.className = 'global-field-error';
    error.dataset.errorFor = fieldKey;

    container.appendChild(error);

    return error;
}

function showFormInputValidationMessage(
    field,
    message = '',
    successMessage = ''
) {
    if (!field) return;

    field.setCustomValidity('');

    const hasError =
        Boolean(message);

    const hasValue =
        String(field.value || '')
            .trim()
            .length > 0;

    const hasSuccess =
        !hasError &&
        hasValue &&
        Boolean(successMessage);

    const controlHost =
        getGlobalFieldControlHost(field);

    const customSelect =
        field.closest?.('.custom-select');

    field.classList.toggle(
        'is-invalid',
        hasError
    );

    field.classList.toggle(
        'is-valid',
        !hasError && hasValue
    );

    if (
        controlHost &&
        controlHost !== field &&
        !customSelect
    ) {
        controlHost.classList.toggle(
            'is-invalid',
            hasError
        );

        controlHost.classList.toggle(
            'is-valid',
            !hasError && hasValue
        );
    }

    customSelect?.classList.toggle(
        'is-invalid',
        hasError
    );

    customSelect?.classList.toggle(
        'is-valid',
        !hasError && hasValue
    );

    const indicator =
        ensureGlobalFieldError(field);

    if (indicator) {
        if (hasError) {
            indicator.innerHTML = `
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>${escapeValidationHtml(message)}</span>
        `;

            indicator.classList.remove(
                'is-success'
            );

            showGlobalFieldErrorElement(
                indicator
            );

            restartGlobalFieldErrorAnimation(
                indicator
            );

        } else if (hasSuccess) {
            indicator.innerHTML = `
            <i class="fa-solid fa-circle-check"></i>
            <span>${escapeValidationHtml(successMessage)}</span>
        `;

            indicator.classList.add(
                'is-success'
            );

            showGlobalFieldErrorElement(
                indicator
            );

        } else {
            hideGlobalFieldErrorElement(
                indicator
            );
        }
    }

    if (field.id && indicator) {
        indicator.id =
            `${field.id}-global-error`;

        if (hasError) {
            field.setAttribute(
                'aria-invalid',
                'true'
            );
        } else {
            field.removeAttribute(
                'aria-invalid'
            );
        }

        if (hasError || hasSuccess) {
            field.setAttribute(
                'aria-describedby',
                indicator.id
            );
        } else if (
            field.getAttribute(
                'aria-describedby'
            ) === indicator.id
        ) {
            field.removeAttribute(
                'aria-describedby'
            );
        }
    }
}

function clearFormInputValidation(field) {
    if (!field) return;

    showFormInputValidationMessage(field, '', '');
}

function validateFormInputField(field) {
    if (!field) return true;

    const message =
        getFormInputValidationMessage(field);

    let successMessage = '';

    if (
        !message &&
        field.name === 'password_confirmation' &&
        String(field.value || '').length > 0 &&
        field.form
    ) {
        const passwordField =
            field.form.querySelector(
                '[name="password"]'
            );

        if (
            passwordField &&
            field.value === passwordField.value
        ) {
            successMessage =
                'Passwords match.';
        }
    }

    showFormInputValidationMessage(
        field,
        message,
        successMessage
    );

    return !message;
}

function focusGlobalInvalidField(
    field
) {
    if (!field) return;

    const target =
        field.closest(
            [
                '[data-global-field]',
                '.global-question-row',
                '.global-form-group',
                '.global-choice-group'
            ].join(',')
        ) || field;

    target.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });

    window.setTimeout(
        () => {
            const focusTarget =
                field.matches(
                    'input, select, textarea, button'
                )
                    ? field
                    : target.querySelector(
                        'input, select, textarea, button'
                    );

            focusTarget?.focus({
                preventScroll: true
            });
        },
        320
    );
}

window.focusGlobalInvalidField = focusGlobalInvalidField;

function getGlobalPhilippineMobileDigits(value = '') {
    return String(value || '')
        .replace(/\D/g, '')
        .slice(0, 11);
}

function formatGlobalPhilippineMobile(value = '') {
    const digits =
        getGlobalPhilippineMobileDigits(
            value
        );

    const parts = [];

    if (digits.length > 0) {
        parts.push(
            digits.slice(0, 4)
        );
    }

    if (digits.length > 4) {
        parts.push(
            digits.slice(4, 7)
        );
    }

    if (digits.length > 7) {
        parts.push(
            digits.slice(7, 11)
        );
    }

    return parts.join(' ');
}

function normalizeGlobalPhilippineMobile(field) {
    if (
        !field ||
        (
            field.dataset.validationRule !==
            'philippineMobile' &&
            !isContactInput(field)
        )
    ) {
        return;
    }

    const formatted =
        formatGlobalPhilippineMobile(
            field.value
        );

    if (
        field.value !==
        formatted
    ) {
        field.value =
            formatted;
    }
}

function isGlobalPhoneField(field) {
    return (
        field instanceof
        HTMLInputElement &&
        (
            field.dataset.validationRule ===
            'philippineMobile' ||
            isContactInput(field)
        )
    );
}

function normalizeGlobalPersonName(field) {
    if (
        !field ||
        field.dataset.validationRule !==
        'personName'
    ) {
        return;
    }

    const normalized =
        String(
            field.value || ''
        ).replace(
            /[^A-Za-zÑñ\s.'-]/g,
            ''
        );

    if (
        field.value !==
        normalized
    ) {
        field.value =
            normalized;
    }
}

function bindFormInputValidation(root = document) {
    const scope =
        root && typeof root.querySelectorAll === 'function'
            ? root
            : document;

    scope
        .querySelectorAll('form[data-global-validation]')
        .forEach(form => {
            if (
                form.dataset.formInputValidationInitialized === 'true'
            ) {
                return;
            }

            form.dataset.formInputValidationInitialized = 'true';

            form.setAttribute('novalidate', '');

            form.addEventListener('formdata', event => {
                form
                    .querySelectorAll(
                        'input[name]'
                    )
                    .forEach(field => {
                        if (
                            !isGlobalPhoneField(
                                field
                            )
                        ) {
                            return;
                        }

                        event.formData.set(
                            field.name,
                            getGlobalPhilippineMobileDigits(
                                field.value
                            )
                        );
                    });
            }
            );

            form.querySelectorAll('input').forEach(field => {
                normalizeGlobalPhilippineMobile(
                    field
                );

                normalizeGlobalPersonName(
                    field
                );
            });

            form.addEventListener(
                'beforeinput',
                event => {
                    const field =
                        event.target;

                    if (
                        !(
                            field instanceof
                            HTMLInputElement
                        )
                    ) {
                        return;
                    }

                    const insertedText =
                        event.data;

                    if (!insertedText) {
                        return;
                    }

                    if (
                        isGlobalPhoneField(
                            field
                        ) &&
                        /\D/.test(
                            insertedText
                        )
                    ) {
                        event.preventDefault();

                        return;
                    }

                    if (
                        field.dataset.validationRule ===
                        'personName' &&
                        /[^A-Za-zÑñ\s.'-]/.test(
                            insertedText
                        )
                    ) {
                        event.preventDefault();
                    }
                },
                true
            );

            const validateEventField = event => {
                const field =
                    event.target;

                if (
                    !(
                        field instanceof
                        HTMLInputElement
                    ) &&
                    !(
                        field instanceof
                        HTMLTextAreaElement
                    ) &&
                    !(
                        field instanceof
                        HTMLSelectElement
                    )
                ) {
                    return;
                }

                if (
                    field instanceof
                    HTMLInputElement &&
                    [
                        'hidden',
                        'submit',
                        'button',
                        'file'
                    ].includes(
                        field.type
                    )
                ) {
                    return;
                }

                normalizeGlobalPhilippineMobile(
                    field
                );

                normalizeGlobalPersonName(
                    field
                );

                validateFormInputField(
                    field
                );
            };
            form.addEventListener('input', validateEventField, true);
            form.addEventListener('change', validateEventField, true);
            form.addEventListener('blur', validateEventField, true);

            form.addEventListener('submit', event => {
                const result = validateGlobalForm(form);

                if (result.valid) return;

                event.preventDefault();
                event.stopPropagation();
            });

            const passwordField =
                form.querySelector('[name="password"]');

            const confirmationField =
                form.querySelector('[name="password_confirmation"]');

            passwordField?.addEventListener('input', () => {
                if (confirmationField?.value) {
                    validateFormInputField(confirmationField);
                }
            });
        });
}

function bindGlobalLinkedGroupErrors(root = document) {
    const scope =
        root &&
            typeof root.querySelectorAll ===
            'function'
            ? root
            : document;

    const groups = [];

    if (
        scope.matches?.(
            '[data-global-linked-input]'
        )
    ) {
        groups.push(scope);
    }

    scope
        .querySelectorAll?.(
            '[data-global-linked-input]'
        )
        .forEach(group => {
            groups.push(group);
        });

    groups.forEach(group => {
        if (
            group.dataset
                .globalLinkedErrorInitialized ===
            'true'
        ) {
            return;
        }

        const selector =
            group.dataset
                .globalLinkedInput;

        if (!selector) {
            return;
        }

        const linkedField =
            group.ownerDocument
                ?.querySelector(
                    selector
                );

        if (!linkedField) {
            return;
        }

        const errorKey =
            group.dataset
                .globalErrorKey ||
            linkedField.id ||
            linkedField.name;

        const sync = () => {
            const hasValue =
                String(
                    linkedField.value ||
                    ''
                ).trim() !== '';

            if (!hasValue) {
                return;
            }

            clearGlobalGroupError(
                group,
                errorKey
            );
        };

        const queueSync = () => {
            window.setTimeout(
                sync,
                0
            );

            window.setTimeout(
                sync,
                120
            );
        };

        linkedField.addEventListener(
            'input',
            sync
        );

        linkedField.addEventListener(
            'change',
            sync
        );

        group.addEventListener(
            'click',
            queueSync,
            true
        );

        group.addEventListener(
            'change',
            queueSync,
            true
        );

        group.dataset
            .globalLinkedErrorInitialized =
            'true';

        sync();
    });
}

function validateGlobalLinkedGroups(
    root = document
) {
    const scope =
        root &&
            typeof root.querySelectorAll ===
            'function'
            ? root
            : document;

    const groups = [];

    if (
        scope.matches?.(
            '[data-global-linked-input]' +
            '[data-global-required="true"]'
        )
    ) {
        groups.push(
            scope
        );
    }

    scope
        .querySelectorAll?.(
            '[data-global-linked-input]' +
            '[data-global-required="true"]'
        )
        .forEach(group => {
            groups.push(
                group
            );
        });

    let firstInvalid = null;

    groups.forEach(group => {
        const selector =
            group.dataset
                .globalLinkedInput;

        if (!selector) {
            return;
        }

        let linkedField = null;

        try {
            linkedField =
                scope.querySelector?.(
                    selector
                ) ||
                group.ownerDocument
                    ?.querySelector(
                        selector
                    );
        } catch (error) {
            return;
        }

        if (!linkedField) {
            return;
        }

        const errorKey =
            group.dataset
                .globalErrorKey ||
            linkedField.id ||
            linkedField.name;

        const message =
            group.dataset
                .globalRequiredMessage ||
            'Please make a selection.';

        const hasValue =
            String(
                linkedField.value || ''
            ).trim() !== '';

        if (hasValue) {
            clearGlobalGroupError(
                group,
                errorKey
            );

            return;
        }

        showGlobalGroupError(
            group,
            errorKey,
            message
        );

        if (!firstInvalid) {
            firstInvalid =
                group;
        }
    });

    return {
        valid:
            !firstInvalid,

        firstInvalid
    };
}

function bindCharLimitField(field) {
    if (!field || field.dataset.charLimitInitialized === 'true') return;

    const limit = Number(field.dataset.charLimit || field.getAttribute('maxlength') || 150);
    const counterSelector = field.dataset.charCounter;
    const errorSelector = field.dataset.charError;

    const counterId = counterSelector ? counterSelector.replace('#', '') : `charCounter-${field.id}`;
    const errorId = errorSelector ? errorSelector.replace('#', '') : null;

    field.dataset.charLimitInitialized = 'true';
    field.setAttribute('maxlength', String(limit));

    const sync = () => {
        if (field.value.length > limit) {
            field.value = field.value.slice(0, limit);
        }

        updateCharCounter(field.id, limit, counterId);
        validateCharLimit(field.id, limit, errorId);
    };

    field.addEventListener('input', sync);
    field.addEventListener('change', sync);
    field.addEventListener('paste', () => {
        requestAnimationFrame(sync);
    });

    sync();
}

function initCharLimitFields(root = document) {
    root.querySelectorAll('[data-char-limit]').forEach(bindCharLimitField);
}

function bootGlobalValidation() {
    initCharLimitFields();
    bindFormInputValidation();
    bindGlobalLinkedGroupErrors();
    initGlobalNumberSteppers();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        bootGlobalValidation,
        {
            once: true
        }
    );
} else {
    bootGlobalValidation();
}

function getGlobalNumberStepperConfig(
    stepper,
    input
) {
    const readNumber = (
        value,
        fallback
    ) => {
        const parsed = Number(value);

        return Number.isFinite(parsed)
            ? parsed
            : fallback;
    };

    return {
        min: readNumber(
            input.min ||
            stepper.dataset.min,
            Number.NEGATIVE_INFINITY
        ),

        max: readNumber(
            input.max ||
            stepper.dataset.max,
            Number.POSITIVE_INFINITY
        ),

        step: Math.abs(
            readNumber(
                input.step ||
                stepper.dataset.step,
                1
            )
        ) || 1
    };
}

function syncGlobalNumberStepper(
    stepper
) {
    const input =
        stepper.querySelector(
            '[data-number-stepper-input]'
        );

    if (!input) return;

    const {
        min,
        max
    } = getGlobalNumberStepperConfig(
        stepper,
        input
    );

    const value =
        Number(input.value);

    const hasValue =
        input.value !== '' &&
        Number.isFinite(value);

    stepper
        .querySelectorAll(
            '[data-number-step]'
        )
        .forEach(button => {
            const direction =
                Number(
                    button.dataset.numberStep
                );

            button.disabled =
                hasValue &&
                (
                    direction < 0 &&
                    value <= min ||
                    direction > 0 &&
                    value >= max
                );
        });
}

function setGlobalNumberStepperValue(
    stepper,
    nextValue
) {
    const input =
        stepper.querySelector(
            '[data-number-stepper-input]'
        );

    if (!input) return;

    const {
        min,
        max
    } = getGlobalNumberStepperConfig(
        stepper,
        input
    );

    const value = Math.max(
        min,
        Math.min(
            max,
            Number(nextValue)
        )
    );

    input.value =
        Number.isFinite(value)
            ? String(value)
            : '';

    input.dispatchEvent(
        new Event('input', {
            bubbles: true
        })
    );

    input.dispatchEvent(
        new Event('change', {
            bubbles: true
        })
    );

    syncGlobalNumberStepper(stepper);
}

function bindGlobalNumberStepper(
    stepper
) {
    if (
        !stepper ||
        stepper.dataset.numberStepperInitialized ===
        'true'
    ) {
        return;
    }

    const input =
        stepper.querySelector(
            '[data-number-stepper-input]'
        );

    if (!input) return;
    const {
        min
    } = getGlobalNumberStepperConfig(
        stepper,
        input
    );

    if (
        input.value === '' &&
        Number.isFinite(min) &&
        min > 0
    ) {
        input.value =
            String(min);
    }

    stepper.dataset.numberStepperInitialized =
        'true';

    stepper.addEventListener(
        'click',
        event => {
            const button =
                event.target.closest(
                    '[data-number-step]'
                );

            if (
                !button ||
                !stepper.contains(button)
            ) {
                return;
            }

            event.preventDefault();

            const {
                min,
                step
            } = getGlobalNumberStepperConfig(
                stepper,
                input
            );

            const current =
                input.value === ''
                    ? Number.isFinite(min)
                        ? min
                        : 0
                    : Number(input.value);

            const direction =
                Number(
                    button.dataset.numberStep
                );

            setGlobalNumberStepperValue(
                stepper,
                current +
                direction * step
            );
        }
    );

    input.addEventListener(
        'input',
        () => {
            syncGlobalNumberStepper(
                stepper
            );
        }
    );

    input.addEventListener(
        'blur',
        () => {
            if (input.value === '') return;

            setGlobalNumberStepperValue(
                stepper,
                Number(input.value)
            );
        }
    );

    input.addEventListener(
        'keydown',
        event => {
            if (
                event.key !== 'ArrowUp' &&
                event.key !== 'ArrowDown'
            ) {
                return;
            }

            event.preventDefault();

            const button =
                stepper.querySelector(
                    event.key === 'ArrowUp'
                        ? '[data-number-step="1"]'
                        : '[data-number-step="-1"]'
                );

            button?.click();
        }
    );

    syncGlobalNumberStepper(stepper);
}

function initGlobalNumberSteppers(
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
            '[data-global-number-stepper]'
        )
        .forEach(
            bindGlobalNumberStepper
        );
}

document.addEventListener(
    'ui-modal:opened',
    event => {
        initGlobalNumberSteppers(
            event.detail?.modal ||
            document
        );
    }
);

function shakeGlobalFieldErrors(form) {
    if (!form) return;

    form
        .querySelectorAll(
            '.global-field-error.show'
        )
        .forEach(error => {
            error.style.animation = 'none';

            void error.offsetWidth;

            error.style.animation = '';
        });
}

function validateGlobalSection(
    section,
    options = {}
) {
    if (!section) {
        return {
            valid: true,
            firstInvalid: null
        };
    }

    const fields = Array.from(
        section.querySelectorAll(
            [
                'input:not([type="hidden"])',
                'textarea',
                'select'
            ].join(',')
        )
    ).filter(field => {
        return (
            !field.disabled &&
            field.type !== 'button' &&
            field.type !== 'submit' &&
            field.type !== 'file'
        );
    });

    const processedRadioGroups =
        new Set();

    let firstInvalid = null;

    fields.forEach(field => {
        if (
            field.type === 'radio' &&
            processedRadioGroups.has(
                field.name
            )
        ) {
            return;
        }

        if (
            field.type === 'radio'
        ) {
            processedRadioGroups.add(
                field.name
            );
        }

        const valid =
            validateFormInputField(
                field
            );

        if (
            !valid &&
            !firstInvalid
        ) {
            firstInvalid =
                field;
        }
    });

    const linkedResult =
        validateGlobalLinkedGroups(
            section
        );

    if (
        !linkedResult.valid &&
        !firstInvalid
    ) {
        firstInvalid =
            linkedResult
                .firstInvalid;
    }

    if (
        firstInvalid &&
        options.focus !== false
    ) {
        focusGlobalInvalidField(
            firstInvalid
        );
    }

    if (firstInvalid) {
        shakeGlobalFieldErrors(
            section
        );
    }

    return {
        valid: !firstInvalid,
        firstInvalid
    };
}

function validateGlobalForm(form, options = {}) {
    if (!form) {
        return {
            valid: true,
            firstInvalid: null
        };
    }

    const fields = Array.from(
        form.querySelectorAll(
            'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), textarea, select'
        )
    );

    const processedRadioGroups = new Set();
    let firstInvalid = null;

    fields.forEach(field => {
        if (
            field.type === 'radio' &&
            processedRadioGroups.has(field.name)
        ) {
            return;
        }

        if (field.type === 'radio') {
            processedRadioGroups.add(field.name);
        }

        const valid = validateFormInputField(field);

        if (!valid && !firstInvalid) {
            firstInvalid = field;
        }
    });

    const linkedResult =
        validateGlobalLinkedGroups(
            form
        );

    if (
        !linkedResult.valid &&
        !firstInvalid
    ) {
        firstInvalid =
            linkedResult
                .firstInvalid;
    }

    if (
        firstInvalid &&
        options.focus !== false
    ) {
        focusGlobalInvalidField(firstInvalid);
    }

    const customResult =
        runGlobalFormValidationRule(form);

    if (
        !customResult.valid &&
        !firstInvalid
    ) {
        firstInvalid =
            customResult.firstInvalid ||
            null;
    }

    const valid =
        !firstInvalid &&
        customResult.valid;

    if (!valid) {
        shakeGlobalFieldErrors(form);
    }

    return {
        valid,
        firstInvalid:
            firstInvalid ||
            customResult.firstInvalid ||
            null
    };
}

function formatStockNo(input) {
    if (!input) return;

    let digits = input.value.replace(/\D/g, '');
    if (digits.length > 5) digits = digits.slice(0, 5);

    input.value = digits.length <= 2 ? digits : `${digits.slice(0, 2)}-${digits.slice(2)}`;
}

window.registerGlobalValidationRule = registerGlobalValidationRule;
window.registerGlobalFormValidationRule = registerGlobalFormValidationRule;
window.showGlobalGroupError = showGlobalGroupError;
window.clearGlobalGroupError = clearGlobalGroupError;
window.focusGlobalInvalidField = focusGlobalInvalidField;
window.normalizeGlobalPhilippineMobile = normalizeGlobalPhilippineMobile;
window.getGlobalPhilippineMobileDigits = getGlobalPhilippineMobileDigits;
window.bindGlobalLinkedGroupErrors = bindGlobalLinkedGroupErrors;
window.validateGlobalLinkedGroups = validateGlobalLinkedGroups;
window.initGlobalNumberSteppers = initGlobalNumberSteppers;
window.validateGlobalSection = validateGlobalSection;
window.validateGlobalForm = validateGlobalForm;
window.validateCharLimit = validateCharLimit;
window.initCharLimitFields = initCharLimitFields;
window.bindFormInputValidation = bindFormInputValidation;
window.validateFormInputField = validateFormInputField;
window.showFormInputValidationMessage = showFormInputValidationMessage;
window.clearFormInputValidation = clearFormInputValidation;

window.setFieldState = setFieldState;
window.updateCharCounter = updateCharCounter;
window.formatStockNo = formatStockNo;

window.dispatchEvent(
    new CustomEvent('global-validation-ready')
);
