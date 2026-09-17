let flatpickrPromise = null;

async function loadFlatpickr() {
    if (window.flatpickr) {
        return window.flatpickr;
    }

    if (!flatpickrPromise) {
        flatpickrPromise = Promise.all([
            import('flatpickr'),
            import('flatpickr/dist/flatpickr.min.css')
        ])
            .then(([module]) => {
                window.flatpickr =
                    module.default;

                return module.default;
            })
            .catch(error => {
                flatpickrPromise = null;
                throw error;
            });
    }

    return flatpickrPromise;
}

function normalizeDateOnly(value) {
    if (!value) return null;

    const date = value instanceof Date ? new Date(value) : new Date(value);

    if (Number.isNaN(date.getTime())) return null;

    date.setHours(0, 0, 0, 0);
    return date;
}

function normalizeCalendarDays(days) {
    if (Array.isArray(days)) return days;

    if (typeof days === 'string') {
        try {
            const parsed = JSON.parse(days);
            if (Array.isArray(parsed)) return parsed;
        } catch (_) {
            return days.split(',').map(day => day.trim()).filter(Boolean);
        }
    }

    return [];
}

function isCalendarRuleActive(rule) {
    return (
        rule?.is_active === true ||
        rule?.is_active === 1 ||
        rule?.is_active === '1'
    );
}

function getCalendarDayAbbr(dateObj) {
    return dateObj.toLocaleDateString('en-US', {
        weekday: 'short',
    }).replace('.', '');
}

function toIsoDate(dateObj) {
    const date = normalizeDateOnly(dateObj);

    if (!date) return '';

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function createCalendarSource(config = {}) {
    const blockedDates = Array.isArray(config.blockedDates) ? config.blockedDates : [];
    const blockedDateSet = new Set(blockedDates.map(value => String(value).trim()).filter(Boolean));
    const holidaysMap = config.holidaysMap && typeof config.holidaysMap === 'object'
        ? config.holidaysMap
        : {};
    const scheduleRules = Array.isArray(config.scheduleRules) ? config.scheduleRules : [];
    const useDynamicScheduleRules = config.useDynamicScheduleRules === true;

    return {
        blockedDates,
        blockedDateSet,
        holidaysMap,
        scheduleRules,
        useDynamicScheduleRules,
        rawConfig: config,

        toIsoDate(dateObj) {
            return toIsoDate(dateObj);
        },

        getRuleForDate(dateObj) {
            if (!useDynamicScheduleRules) return null;

            const dayAbbr = getCalendarDayAbbr(dateObj);

            return scheduleRules.find(rule => {
                const days = normalizeCalendarDays(rule?.days);

                return isCalendarRuleActive(rule) && days.includes(dayAbbr);
            }) || null;
        },

        getMaxPerDay(dateObj) {
            const rule = this.getRuleForDate(dateObj);
            return Number(rule?.max_slots ?? 0) || 0;
        },

        isBlocked(iso) {
            return this.blockedDateSet.has(String(iso || '').trim());
        },

        getHoliday(iso) {
            return this.holidaysMap?.[iso] || null;
        },

        getHolidayName(iso) {
            const holiday =
                this.getHoliday(iso);

            if (!holiday) {
                return null;
            }

            if (typeof holiday === 'string') {
                return holiday;
            }

            return String(
                holiday.name ||
                'Philippine Holiday'
            );
        },

        isHolidayBlocked(iso) {
            const holiday =
                this.getHoliday(iso);

            if (!holiday) {
                return false;
            }

            if (typeof holiday === 'string') {
                return true;
            }

            if (
                typeof holiday
                    .is_blocked_for_booking ===
                'boolean'
            ) {
                return holiday
                    .is_blocked_for_booking;
            }

            if (
                holiday.type ===
                'special_working' ||
                holiday.is_working_day ===
                true
            ) {
                return false;
            }

            if (
                holiday.type === 'islamic' &&
                holiday.eid_confirmed === false
            ) {
                return false;
            }

            return true;
        },

        isDateSchedulable(dateObj, iso = this.toIsoDate(dateObj)) {
            if (!iso) return false;

            if (this.isBlocked(iso)) {
                return false;
            }

            if (this.isHolidayBlocked(iso)) {
                return false;
            }

            if (!useDynamicScheduleRules) {
                return true;
            }

            const rule = this.getRuleForDate(dateObj);
            const status = String(rule?.status || '').trim().toLowerCase();

            if (!rule || !isCalendarRuleActive(rule) || status === 'closed') {
                return false;
            }

            return true;
        },

        buildFlatpickrOptions(overrides = {}) {
            const existingDisable = Array.isArray(overrides.disable)
                ? overrides.disable
                : [];

            return {
                ...overrides,
                disable: [
                    ...existingDisable,
                    dateObj => !this.isDateSchedulable(dateObj),
                ],
            };
        },
    };
}

window.createCalendarSource = createCalendarSource;
window.buildFlatpickrCalendarOptions = function buildFlatpickrCalendarOptions(config = {}, overrides = {}) {
    return createCalendarSource(config).buildFlatpickrOptions(overrides);
};

function decorateFlatpickrDay(instance, dayElem) {
    const minDate =
        normalizeDateOnly(
            instance.config?.minDate
        );

    const maxDate =
        normalizeDateOnly(
            instance.config?.maxDate
        );

    dayElem.removeAttribute('data-tooltip');
    dayElem.removeAttribute('data-tooltip-tone');

    if (!dayElem.dateObj) {
        return;
    }

    const dayDate = normalizeDateOnly(dayElem.dateObj);

    if (!dayDate) {
        return;
    }

    if (minDate && dayDate < minDate) {
        dayElem.setAttribute(
            'data-tooltip',
            "You can't select previous date"
        );

        return;
    }

    if (maxDate && dayDate > maxDate) {
        dayElem.setAttribute(
            'data-tooltip',
            "You can't select future date"
        );

        return;
    }

    const disabledDateTooltip = String(
        instance.input?.dataset?.flatpickrDisabledDateTooltip || ''
    ).trim();

    if (!disabledDateTooltip) {
        return;
    }

    const disabledDates = new Set(
        normalizeCalendarDays(
            instance.input?.dataset?.flatpickrDisabledDates
        ).map(value => String(value).trim()).filter(Boolean)
    );

    if (disabledDates.has(toIsoDate(dayDate))) {
        dayElem.setAttribute('data-tooltip', disabledDateTooltip);
        dayElem.setAttribute('data-tooltip-tone', 'locked');
    }
}

function decorateFlatpickrDays(instance) {
    if (!instance?.calendarContainer) {
        return;
    }

    instance.calendarContainer
        .querySelectorAll('.flatpickr-day')
        .forEach(dayElem => decorateFlatpickrDay(instance, dayElem));
}

function syncFlatpickrHeader(instance) {
    if (!instance?.calendarContainer) return;

    const monthSelect = instance.calendarContainer.querySelector(
        '.custom-flatpickr-month'
    );

    const yearSelect = instance.calendarContainer.querySelector(
        '.custom-flatpickr-year'
    );

    if (monthSelect) {
        monthSelect.value = String(instance.currentMonth);

        const monthWrapper = monthSelect.closest('.custom-select');

        if (monthWrapper) {
            window.syncCustomSelect?.(
                monthWrapper
            );
        }
    }

    if (yearSelect) {
        yearSelect.value = String(instance.currentYear);

        const yearWrapper = yearSelect.closest('.custom-select');

        if (yearWrapper) {
            window.syncCustomSelect?.(
                yearWrapper
            );
        }
    }
}

function updateMonthOnlyInput(instance, options = {}) {
    if (!instance?.input?.matches?.('[data-month-only-picker]')) return;

    const shouldDispatch = options.dispatch !== false;
    const month = String(instance.currentMonth + 1).padStart(2, '0');
    const value = `${instance.currentYear}-${month}`;
    const label = `${instance.l10n.months.longhand[instance.currentMonth]} ${instance.currentYear}`;

    instance.input.value = value;

    if (instance.altInput) {
        instance.altInput.value = label;
    }

    if (shouldDispatch) {
        instance.input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function buildFlatpickrHeader(instance) {
    const GLOBAL_CALENDAR_MIN_YEAR = 2000;
    const GLOBAL_CALENDAR_FUTURE_YEARS = 10;

    if (!instance?.calendarContainer) return;

    const currentMonth = instance.calendarContainer.querySelector(
        '.flatpickr-current-month'
    );

    if (!currentMonth) return;

    const existing = currentMonth.querySelector(
        '.custom-flatpickr-selects'
    );

    if (existing) {
        syncFlatpickrHeader(instance);
        return;
    }

    const monthSelect = document.createElement('select');

    monthSelect.className = [
        'js-custom-select',
        'custom-flatpickr-select',
        'custom-flatpickr-month'
    ].join(' ');

    monthSelect.setAttribute('aria-label', 'Select month');

    instance.l10n.months.longhand.forEach((month, index) => {
        const option = document.createElement('option');

        option.value = String(index);
        option.textContent = month;

        monthSelect.appendChild(option);
    });

    const yearSelect = document.createElement('select');

    yearSelect.className = [
        'js-custom-select',
        'custom-flatpickr-select',
        'custom-flatpickr-year'
    ].join(' ');

    yearSelect.setAttribute('aria-label', 'Select year');

    const fieldMinimumYear =
        Number(
            instance.input?.dataset
                ?.flatpickrMinYear
        );

    const minimumYear =
        instance.config.minDate instanceof Date
            ? instance.config.minDate.getFullYear()
            : (
                Number.isFinite(fieldMinimumYear) &&
                    fieldMinimumYear > 0
                    ? fieldMinimumYear
                    : GLOBAL_CALENDAR_MIN_YEAR
            );

    const maximumYear =
        instance.config.maxDate instanceof Date
            ? instance.config.maxDate.getFullYear()
            : instance.currentYear + GLOBAL_CALENDAR_FUTURE_YEARS;

    for (let year = maximumYear; year >= minimumYear; year--) {
        const option = document.createElement('option');

        option.value = String(year);
        option.textContent = String(year);

        yearSelect.appendChild(option);
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'custom-flatpickr-selects';

    wrapper.append(monthSelect, yearSelect);

    currentMonth.replaceChildren(wrapper);

    monthSelect.addEventListener('change', () => {
        const selectedMonth = Number(monthSelect.value);

        instance.changeMonth(
            selectedMonth - instance.currentMonth
        );

        syncFlatpickrHeader(instance);
        updateMonthOnlyInput(instance);
    });

    yearSelect.addEventListener('change', () => {
        instance.changeYear(Number(yearSelect.value));

        syncFlatpickrHeader(instance);
        updateMonthOnlyInput(instance);
    });

    window.initCustomSelects?.(
        currentMonth
    );

    syncFlatpickrHeader(instance);
}

function refreshFlatpickr(instance) {
    buildFlatpickrHeader(instance);
    decorateFlatpickrDays(instance);

    if (instance?.input?.matches?.('[data-month-only-picker]')) {
        instance.calendarContainer.classList.add('flatpickr-month-only');
    }
}

const GLOBAL_DATE_INPUT_SELECTOR = [
    '.js-flatpickr-date',
    '.js-flatpickr-date-min-today',
    '.js-flatpickr-date-max-today',
    '.js-flatpickr-date-range-from',
    '.js-flatpickr-date-range-to',
    '[data-month-only-picker]',
].join(',');

function ensureGlobalDateInputIcons(
    root = document
) {
    const scope =
        root &&
            typeof root.querySelectorAll === 'function'
            ? root
            : document;

    const inputs = [];

    if (
        scope.matches?.(
            GLOBAL_DATE_INPUT_SELECTOR
        )
    ) {
        inputs.push(scope);
    }

    inputs.push(
        ...scope.querySelectorAll(
            GLOBAL_DATE_INPUT_SELECTOR
        )
    );

    inputs.forEach(input => {
        if (
            input.closest(
                '[data-flatpickr-trigger]'
            )?.querySelector(
                '.global-control-icon'
            )
        ) {
            return;
        }

        let wrapper =
            input.closest(
                '.fp-date-input-wrap'
            );

        if (!wrapper) {
            wrapper =
                document.createElement('div');

            wrapper.className =
                'fp-date-input-wrap';

            input.parentNode.insertBefore(
                wrapper,
                input
            );

            wrapper.appendChild(input);
        }

        input.classList.add(
            'fp-date-input'
        );

        if (
            wrapper.querySelector(
                '.fp-date-icon'
            )
        ) {
            return;
        }

        const icon =
            document.createElement('i');

        icon.className =
            'fa-regular fa-calendar fp-date-icon';

        icon.setAttribute(
            'aria-hidden',
            'true'
        );

        wrapper.appendChild(icon);
    });
}

function initGlobalFlatpickr(root = document) {
    if (!window.flatpickr) return;

    const scope =
        root &&
        typeof root.querySelectorAll === 'function'
            ? root
            : document;

    const baseOptions = {
        dateFormat: "Y-m-d",
        allowInput: false,
        clickOpens: true,
        closeOnSelect: true,
        disableMobile: true,
        position: "auto center",

        onReady: (_dates, _str, instance) => refreshFlatpickr(instance),
        onMonthChange: (_dates, _str, instance) => refreshFlatpickr(instance),
        onYearChange: (_dates, _str, instance) => refreshFlatpickr(instance),
        onDayCreate: (_dates, _str, instance, dayElem) => {
            decorateFlatpickrDay(instance, dayElem);
        },

        onChange: (
            selectedDates,
            _dateString,
            instance
        ) => {
            if (!selectedDates.length) {
                return;
            }

            if (
                window.matchMedia(
                    '(max-width: 640px)'
                ).matches
            ) {
                requestAnimationFrame(
                    () => {
                        instance.close();
                    }
                );
            }
        },

        onOpen: (_dates, _str, instance) => {
            refreshFlatpickr(instance);
            openFlatpickrSheet(instance);
        },
        onClose: (_dates, _str, instance) => {
            closeFlatpickrSheet(instance);
        },
    };


    const dateInputs = scope.querySelectorAll(
        '.js-flatpickr-date, .js-flatpickr-date-min-today, .js-flatpickr-date-max-today, .js-flatpickr-date-range-from, .js-flatpickr-date-range-to'
    );

    dateInputs.forEach(el => {
        if (el._flatpickr) {
            return;
        }
        let options = { ...baseOptions };

        const parentPopup =
            el.closest(
                'dialog, .ui-modal, .modal-overlay'
            );

        const isMobileFlatpickr =
            window.matchMedia(
                '(max-width: 640px)'
            ).matches;

        const shouldPortalToBody =
            isMobileFlatpickr ||
            el.hasAttribute(
                'data-flatpickr-append-to-body'
            );

        options.appendTo =
            shouldPortalToBody
                ? document.body
                : (parentPopup || document.body);

        if (
            parentPopup &&
            !shouldPortalToBody
        ) {
            options.positionElement =
                el;
        }

        if (el.min) {
            options.minDate = el.min;
        }

        if (el.max) {
            options.maxDate = el.max;
        }

        const disabledDates = (() => {
            const rawValue = el.dataset.flatpickrDisabledDates;

            if (!rawValue) return [];

            try {
                const parsed = JSON.parse(rawValue);

                return Array.isArray(parsed)
                    ? parsed.map(value => String(value).trim()).filter(Boolean)
                    : [];
            } catch (_) {
                return rawValue
                    .split(',')
                    .map(value => value.trim())
                    .filter(Boolean);
            }
        })();

        if (disabledDates.length) {
            options.disable = [
                ...(Array.isArray(options.disable) ? options.disable : []),
                ...disabledDates,
            ];
        }

        if (el.classList.contains('js-flatpickr-date-min-today')) {
            options.minDate = "today";
        }

        if (
            el.classList.contains('js-flatpickr-date-max-today') ||
            el.classList.contains('js-flatpickr-date-range-from') ||
            el.classList.contains('js-flatpickr-date-range-to')
        ) {
            options.maxDate = "today";
        }

        window.flatpickr(el, options);
    });

    const timeInputs = scope.querySelectorAll('.js-flatpickr-time');

    timeInputs.forEach(el => {
        if (el._flatpickr) return;

        const initialTime = String(el.value || '').trim();
        const visibleInputClass = el.className
            .split(/\s+/)
            .filter(className => className && className !== 'js-flatpickr-time')
            .join(' ');

        const options = {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            altInput: true,
            altFormat: "h:i K",

            altInputClass: `${visibleInputClass} flatpickr-time-display form-control input`,
            time_24hr: false,
            minuteIncrement: 5,
            defaultDate: /^([01]\d|2[0-3]):[0-5]\d$/.test(initialTime)
                ? initialTime
                : undefined,
            allowInput: false,
            clickOpens: true,
            disableMobile: true,
            position: "auto center",
            appendTo: document.body,

            onReady: (_dates, _str, instance) => {
                if (/^([01]\d|2[0-3]):[0-5]\d$/.test(initialTime)) {
                    instance.setDate(initialTime, false, 'H:i');
                }
            },

            onOpen: (_dates, _str, instance) => {
                openFlatpickrSheet(instance);
            },

            onClose: (_dates, _str, instance) => {
                closeFlatpickrSheet(instance);
            },
        };

        window.flatpickr(el, options);
    });
}

function initMonthOnlyFlatpickr(root = document) {
    if (!window.flatpickr) return;

    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

    scope.querySelectorAll('[data-month-only-picker]').forEach(el => {
        if (el._flatpickr) return;

        const parentPopup = el.closest(
            'dialog, .ui-modal, .modal-overlay'
        );
        const rawDefault = el.value || el.dataset.defaultMonth || '';
        const defaultDate = /^\d{4}-\d{2}$/.test(rawDefault)
            ? `${rawDefault}-01`
            : rawDefault || new Date();

        const limitToToday =
            el.hasAttribute('data-month-max-today');

        const isMobileFlatpickr =
            window.matchMedia(
                '(max-width: 640px)'
            ).matches;

        window.flatpickr(el, {
            dateFormat: 'Y-m',
            altInput: true,
            altFormat: 'F Y',
            altInputClass: 'form-input-custom fp-month-input',
            defaultDate,
            maxDate: limitToToday ? 'today' : undefined,
            allowInput: false,
            clickOpens: true,
            disableMobile: true,
            position: 'auto center',
            appendTo:
                isMobileFlatpickr
                    ? document.body
                    : (parentPopup || document.body),

            positionElement:
                parentPopup &&
                    !isMobileFlatpickr
                    ? el
                    : undefined,

            onReady: (_dates, _str, instance) => {
                instance.calendarContainer.classList.add(
                    'flatpickr-month-only'
                );

                refreshFlatpickr(instance);

                updateMonthOnlyInput(
                    instance,
                    {
                        dispatch: false
                    }
                );

                const visibleInput =
                    instance.altInput ||
                    instance.input;

                let wrapper =
                    visibleInput.closest(
                        '.fp-date-input-wrap'
                    );

                if (!wrapper) {
                    wrapper =
                        document.createElement(
                            'div'
                        );

                    wrapper.className =
                        'fp-date-input-wrap';

                    visibleInput.parentNode.insertBefore(
                        wrapper,
                        visibleInput
                    );

                    wrapper.appendChild(
                        visibleInput
                    );
                }

                visibleInput.classList.add(
                    'fp-date-input'
                );

                if (
                    !wrapper.querySelector(
                        '.fp-date-icon'
                    )
                ) {
                    const icon =
                        document.createElement(
                            'i'
                        );

                    icon.className =
                        'fa-regular fa-calendar fp-date-icon';

                    icon.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                    wrapper.appendChild(
                        icon
                    );
                }
            },

            onOpen: (_dates, _str, instance) => {
                instance.calendarContainer.classList.add('flatpickr-month-only');
                refreshFlatpickr(instance);
                openFlatpickrSheet(instance);
            },

            onMonthChange: (_dates, _str, instance) => {
                instance.calendarContainer.classList.add('flatpickr-month-only');
                refreshFlatpickr(instance);
                updateMonthOnlyInput(instance);
            },

            onYearChange: (_dates, _str, instance) => {
                instance.calendarContainer.classList.add('flatpickr-month-only');
                refreshFlatpickr(instance);
                updateMonthOnlyInput(instance);
            },

            onClose: (_dates, _str, instance) => {
                closeFlatpickrSheet(instance);
            },
        });
    });
}

function setMonthOnlyPickerValue(inputOrSelector, value, dispatch = true) {
    const input = typeof inputOrSelector === 'string'
        ? document.querySelector(inputOrSelector)
        : inputOrSelector;

    if (!input || !value) return;

    const dateValue = /^\d{4}-\d{2}$/.test(value) ? `${value}-01` : value;

    if (input._flatpickr) {
        input._flatpickr.setDate(dateValue, false);
        refreshFlatpickr(input._flatpickr);
        updateMonthOnlyInput(input._flatpickr, { dispatch });
        return;
    }

    const normalizedValue =
        String(value).slice(0, 7);

    input.value =
        normalizedValue;

    input.dataset.defaultMonth =
        normalizedValue;

    if (dispatch) {
        input.dispatchEvent(
            new Event(
                'change',
                {
                    bubbles: true
                }
            )
        );
    }
}

let globalDatePickerBootPromise =
    null;

export async function initGlobalDatePickers(
    root = document
) {
    const scope =
        root &&
            typeof root.querySelectorAll ===
            'function'
            ? root
            : document;

    const hasFlatpickrFields =
        scope.matches?.(
            [
                '.js-flatpickr-date',
                '.js-flatpickr-date-min-today',
                '.js-flatpickr-date-max-today',
                '.js-flatpickr-date-range-from',
                '.js-flatpickr-date-range-to',
                '.js-flatpickr-time',
                '[data-month-only-picker]'
            ].join(',')
        ) ||
        scope.querySelector?.(
            [
                '.js-flatpickr-date',
                '.js-flatpickr-date-min-today',
                '.js-flatpickr-date-max-today',
                '.js-flatpickr-date-range-from',
                '.js-flatpickr-date-range-to',
                '.js-flatpickr-time',
                '[data-month-only-picker]'
            ].join(',')
        );

    if (!hasFlatpickrFields) {
        return;
    }

    ensureGlobalDateInputIcons(scope);

    if (!globalDatePickerBootPromise) {
        globalDatePickerBootPromise =
            loadFlatpickr()
                .catch(error => {
                    globalDatePickerBootPromise =
                        null;

                    throw error;
                });
    }

    await globalDatePickerBootPromise;

    initGlobalFlatpickr(
        scope
    );
    initMonthOnlyFlatpickr(
        scope
    );
}

function bootGlobalDatePickers() {
    initGlobalDatePickers(
        document
    )
        .catch(error => {
            console.error(
                'Unable to load Flatpickr.',
                error
            );
        });
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        bootGlobalDatePickers,
        {
            once: true
        }
    );
} else {
    bootGlobalDatePickers();
}

let activeFlatpickrInstance = null;

function ensureFlatpickrBackdrop() {
    let backdrop =
        document.querySelector(
            '.flatpickr-mobile-backdrop'
        );

    if (!backdrop) {
        backdrop =
            document.createElement(
                'div'
            );

        backdrop.className =
            'flatpickr-mobile-backdrop';

        document.body.appendChild(
            backdrop
        );

        backdrop.addEventListener(
            'pointerdown',
            event => {
                if (
                    backdrop.dataset.ready !==
                    'true'
                ) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const instance =
                    activeFlatpickrInstance;

                if (!instance) {
                    return;
                }

                activeFlatpickrInstance =
                    null;

                instance.close();
            }
        );
    }

    return backdrop;
}

function openFlatpickrSheet(instance) {
    activeFlatpickrInstance =
        instance;

    if (
        !window.matchMedia(
            '(max-width: 640px)'
        ).matches
    ) {
        return;
    }

    const backdrop =
        ensureFlatpickrBackdrop();

    backdrop.dataset.ready =
        'false';

    document.body.classList.add(
        'flatpickr-sheet-open'
    );

    backdrop.classList.add(
        'show'
    );

    instance.calendarContainer
        .classList.add(
            'flatpickr-mobile-sheet'
        );

    instance.calendarContainer.style.zIndex =
        '999999';

    requestAnimationFrame(() => {
        instance.calendarContainer
            .classList.add(
                'sheet-show'
            );

        window.setTimeout(() => {
            backdrop.dataset.ready =
                'true';
        }, 180);
    });
}

function closeFlatpickrSheet(instance) {
    if (
        !window.matchMedia(
            '(max-width: 640px)'
        ).matches
    ) {
        return;
    }

    const backdrop =
        document.querySelector(
            '.flatpickr-mobile-backdrop'
        );

    if (backdrop) {
        backdrop.dataset.ready =
            'false';

        backdrop.classList.remove(
            'show'
        );
    }

    document.body.classList.remove(
        'flatpickr-sheet-open'
    );

    instance.calendarContainer
        ?.classList.remove(
            'sheet-show'
        );

    window.setTimeout(() => {

        instance.calendarContainer
            ?.classList.remove(
                'flatpickr-mobile-sheet',
                'sheet-dragging'
            );

        if (
            instance.calendarContainer
        ) {
            instance.calendarContainer
                .style.removeProperty(
                    'transform'
                );

            instance.calendarContainer
                .style.removeProperty(
                    'z-index'
                );
        }

        if (activeFlatpickrInstance === instance) {
            activeFlatpickrInstance =
                null;
        }

    }, 220);
}

function initFlatpickrSwipeClose() {
    let startY = 0;
    let dragging = false;

    document.addEventListener('touchstart', (e) => {
        const calendar = e.target.closest('.flatpickr-mobile-sheet.open');
        if (!calendar) return;

        startY = e.touches[0].clientY;
        dragging = true;
        calendar.classList.add('sheet-dragging');
    }, { passive: true });

    document.addEventListener('touchmove', (e) => {
        if (!dragging) return;

        const calendar = document.querySelector('.flatpickr-mobile-sheet.open');
        if (!calendar) return;

        const diff = Math.max(0, e.touches[0].clientY - startY);
        calendar.style.transform = `translateY(${diff}px)`;
    }, { passive: true });

    document.addEventListener('touchend', () => {
        if (!dragging) return;

        const calendar = document.querySelector('.flatpickr-mobile-sheet.open');
        if (!calendar) return;

        const matrixY = calendar.style.transform.match(/translateY\((\d+)px\)/);
        const diff = matrixY ? Number(matrixY[1]) : 0;

        calendar.classList.remove('sheet-dragging');

        if (diff > 90 && activeFlatpickrInstance) {
            activeFlatpickrInstance.close();
        } else {
            calendar.style.transform = '';
        }

        dragging = false;
    });
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        initFlatpickrSwipeClose,
        {
            once: true
        }
    );
} else {
    initFlatpickrSwipeClose();
}

window.loadFlatpickr = loadFlatpickr;
window.createCalendarSource = createCalendarSource;

window.buildFlatpickrCalendarOptions =
    function (
        config = {},
        overrides = {}
    ) {
        return createCalendarSource(
            config
        ).buildFlatpickrOptions(
            overrides
        );
    };

window.initMonthOnlyFlatpickr = initMonthOnlyFlatpickr;
window.setMonthOnlyPickerValue = setMonthOnlyPickerValue;
window.initGlobalDatePickers = initGlobalDatePickers;