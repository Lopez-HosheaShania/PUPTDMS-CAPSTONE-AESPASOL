import './bootstrap';
import Chart from 'chart.js/auto';
import JSVoice from 'jsvoice';

window.Chart = Chart;
window.JSVoice = JSVoice;

import {
    swapSkeletonContent,
    renderWithStagger,
    runEnterpriseLoading,
    setDashboardLoadingStatus,
    finishDashboardLoading
} from './skeleton';

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

window.flatpickr = flatpickr;

function normalizeDateOnly(value) {
    if (!value) return null;

    const date = value instanceof Date ? new Date(value) : new Date(value);

    if (Number.isNaN(date.getTime())) return null;

    date.setHours(0, 0, 0, 0);
    return date;
}

function decorateFlatpickrDays(instance) {
    if (!instance?.calendarContainer) return;

    const minDate = normalizeDateOnly(instance.config?.minDate);
    const maxDate = normalizeDateOnly(instance.config?.maxDate);

    instance.calendarContainer.querySelectorAll('.flatpickr-day').forEach((dayElem) => {
        dayElem.classList.remove('flatpickr-has-tooltip');
        delete dayElem.dataset.tooltip;

        if (!dayElem.dateObj) return;

        const dayDate = normalizeDateOnly(dayElem.dateObj);
        if (!dayDate) return;

        if (minDate && dayDate < minDate) {
            dayElem.classList.add('flatpickr-has-tooltip');
            dayElem.dataset.tooltip = "You can't select previous date";
            return;
        }

        if (maxDate && dayDate > maxDate) {
            dayElem.classList.add('flatpickr-has-tooltip');
            dayElem.dataset.tooltip = "You can't select future date";
        }
    });
}

function buildFlatpickrHeader(instance) {
    if (!instance?.calendarContainer) return;

    const currentMonth = instance.calendarContainer.querySelector('.flatpickr-current-month');
    if (!currentMonth || currentMonth.querySelector('.custom-flatpickr-selects')) return;

    const monthSelect = document.createElement('select');
    monthSelect.className = 'custom-flatpickr-select custom-flatpickr-month';

    instance.l10n.months.longhand.forEach((month, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = month;
        if (index === instance.currentMonth) option.selected = true;
        monthSelect.appendChild(option);
    });

    const yearSelect = document.createElement('select');
    yearSelect.className = 'custom-flatpickr-select custom-flatpickr-year';

    const currentYear = instance.currentYear;
    for (let year = currentYear - 80; year <= currentYear + 5; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if (year === currentYear) option.selected = true;
        yearSelect.appendChild(option);
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'custom-flatpickr-selects';
    wrapper.appendChild(monthSelect);
    wrapper.appendChild(yearSelect);

    currentMonth.innerHTML = '';
    currentMonth.appendChild(wrapper);

    monthSelect.addEventListener('change', () => {
        instance.changeMonth(Number(monthSelect.value) - instance.currentMonth);
    });

    yearSelect.addEventListener('change', () => {
        instance.changeYear(Number(yearSelect.value));
    });
}

function refreshFlatpickr(instance) {
    buildFlatpickrHeader(instance);
    decorateFlatpickrDays(instance);
}

function initGlobalFlatpickr() {
    if (!window.flatpickr) return;

    const baseOptions = {
        dateFormat: "Y-m-d",
        allowInput: false,
        clickOpens: true,
        disableMobile: true,
        position: "auto center",

        onReady: (_dates, _str, instance) => refreshFlatpickr(instance),
        onMonthChange: (_dates, _str, instance) => refreshFlatpickr(instance),
        onYearChange: (_dates, _str, instance) => refreshFlatpickr(instance),

        onOpen: (_dates, _str, instance) => {
            refreshFlatpickr(instance);
            openFlatpickrSheet(instance);
        },
        onClose: (_dates, _str, instance) => {
            closeFlatpickrSheet(instance);
        },
    };

    const dateInputs = document.querySelectorAll(
        '.js-flatpickr-date, .js-flatpickr-date-min-today, .js-flatpickr-date-max-today, .js-flatpickr-date-range-from, .js-flatpickr-date-range-to'
    );

    dateInputs.forEach(el => {
        let options = { ...baseOptions };

        const parentPopup = el.closest('dialog, .ui-modal');

        options.appendTo = document.body;

        if (parentPopup) {
            options.positionElement = el;
        }

        if (el.min) {
            options.minDate = el.min;
        }

        if (el.max) {
            options.maxDate = el.max;
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

        flatpickr(el, options);
    });

    const timeInputs = document.querySelectorAll('.js-flatpickr-time');

    timeInputs.forEach(el => {
        if (el._flatpickr) return;

        const parentPopup = el.closest('dialog, .ui-modal');

        const options = {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            altInput: true,
            altFormat: "h:i K",
            time_24hr: false,
            minuteIncrement: 5,
            allowInput: false,
            clickOpens: true,
            disableMobile: true,
            position: "auto center",
            appendTo: document.body,

            onOpen: (_dates, _str, instance) => {
                openFlatpickrSheet(instance);
            },

            onClose: (_dates, _str, instance) => {
                closeFlatpickrSheet(instance);
            },
        };

        if (parentPopup) {
            options.positionElement = el;
        }

        flatpickr(el, options);
    });
}

document.addEventListener("DOMContentLoaded", initGlobalFlatpickr);
document.addEventListener('mousemove', (e) => {
    const day = e.target.closest('.flatpickr-day');

    let tooltip = document.querySelector('.flatpickr-floating-tooltip');

    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.className = 'flatpickr-floating-tooltip';
        document.body.appendChild(tooltip);
    }

    const message = day?.dataset?.tooltip || '';

    if (!message) {
        tooltip.classList.remove('show');
        return;
    }

    tooltip.textContent = message;
    tooltip.style.left = `${e.clientX}px`;
    tooltip.style.top = `${e.clientY - 12}px`;
    tooltip.classList.add('show');
});

document.addEventListener('mouseleave', () => {
    document.querySelector('.flatpickr-floating-tooltip')?.classList.remove('show');
});

let activeFlatpickrInstance = null;

function ensureFlatpickrBackdrop() {
    let backdrop = document.querySelector('.flatpickr-mobile-backdrop');

    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'flatpickr-mobile-backdrop';
        document.body.appendChild(backdrop);

        backdrop.addEventListener('click', () => {
            if (activeFlatpickrInstance) activeFlatpickrInstance.close();
        });
    }

    return backdrop;
}

function openFlatpickrSheet(instance) {
    activeFlatpickrInstance = instance;

    if (!window.matchMedia('(max-width: 640px)').matches) return;

    const backdrop = ensureFlatpickrBackdrop();

    document.body.classList.add('flatpickr-sheet-open');
    backdrop.classList.add('show');

    instance.calendarContainer.classList.add('flatpickr-mobile-sheet');

    requestAnimationFrame(() => {
        instance.calendarContainer.classList.add('sheet-show');
    });
}

function closeFlatpickrSheet(instance) {
    if (!window.matchMedia('(max-width: 640px)').matches) return;

    document.body.classList.remove('flatpickr-sheet-open');
    document.querySelector('.flatpickr-mobile-backdrop')?.classList.remove('show');

    instance.calendarContainer.classList.remove('sheet-show');

    setTimeout(() => {
        instance.calendarContainer.classList.remove('flatpickr-mobile-sheet', 'sheet-dragging');
        instance.calendarContainer.style.transform = '';
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

document.addEventListener('DOMContentLoaded', initFlatpickrSwipeClose);

window.swapSkeletonContent = swapSkeletonContent;
window.renderWithStagger = renderWithStagger;
window.runEnterpriseLoading = runEnterpriseLoading;
window.setDashboardLoadingStatus = setDashboardLoadingStatus;
window.finishDashboardLoading = finishDashboardLoading;

function initBackToTop() {
    if (document.querySelector('.back-to-top')) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'back-to-top floating-btn';
    button.setAttribute('aria-label', 'Back to top');
    button.setAttribute('title', 'Back to top');
    button.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';

    document.body.appendChild(button);

    const toggleButton = () => {
        button.classList.toggle('is-visible', window.scrollY > 350);
    };

    button.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    window.addEventListener('scroll', toggleButton, { passive: true });
    toggleButton();
}

document.addEventListener('DOMContentLoaded', () => {
    initBackToTop();
    initAiHelpPopover();
});

function initAiHelpPopover() {
    if (document.querySelector('.ai-help-popover')) return;

    const alreadyShown = sessionStorage.getItem('ai_help_shown');

    const popover = document.createElement('div');
    popover.className = 'ai-help-popover';
    popover.innerHTML = `
        <strong>Need help?</strong>
        <span>Get help with our AI assistant for appointments, records, and requests.</span>
    `;

    document.body.appendChild(popover);

    const chatbotBtn = document.querySelector('.chatbot-fab');
    if (!chatbotBtn) return;

    const showPopover = () => {
        if (document.querySelector('.chatbot-panel.show')) return;
        popover.classList.add('show');
    };

    const hidePopover = () => {
        popover.classList.remove('show');
    };

    if (!alreadyShown) {
        setTimeout(() => {
            showPopover();
            sessionStorage.setItem('ai_help_shown', 'true');
        }, 1200);
    }

    chatbotBtn.addEventListener('mouseenter', showPopover);
    chatbotBtn.addEventListener('mouseleave', () => {
        setTimeout(hidePopover, 600);
    });

    chatbotBtn.addEventListener('click', hidePopover);
}

function initAccessibilitySheetGesture() {
    let startY = 0;
    let currentY = 0;
    let dragging = false;

    document.addEventListener('touchstart', (e) => {
        const menu = document.querySelector('.asw-menu');
        if (!menu || !menu.contains(e.target)) return;

        startY = e.touches[0].clientY;
        currentY = startY;
        dragging = true;

        menu.classList.add('asw-dragging');
    }, { passive: true });

    document.addEventListener('touchmove', (e) => {
        if (!dragging) return;

        const menu = document.querySelector('.asw-menu');
        if (!menu) return;

        currentY = e.touches[0].clientY;
        const diff = Math.max(0, currentY - startY);

        menu.style.transform = `translateX(-50%) translateY(${diff}px)`;
    }, { passive: true });

    document.addEventListener('touchend', () => {
        if (!dragging) return;

        const menu = document.querySelector('.asw-menu');
        if (!menu) return;

        const diff = currentY - startY;

        menu.classList.remove('asw-dragging');

        if (diff > 90) {
            const closeBtn = menu.querySelector('.asw-menu-close');
            if (closeBtn) closeBtn.click();
        }

        menu.style.transform = 'translateX(-50%) translateY(0)';
        dragging = false;
    });
}

document.addEventListener('DOMContentLoaded', initAccessibilitySheetGesture);

function fixSiennaPosition() {
    const isMobile = window.matchMedia('(max-width: 640px)').matches;
    const isPatient = document.body.classList.contains('role-patient');

    const nav = document.querySelector(
        '.patient-mobile-nav, .mobile-bottom-nav, .bottom-nav, nav[class*="mobile"], #mobileBottomNav'
    );

    let navHeight = 0;

    if (isPatient && isMobile && nav) {
        navHeight = Math.ceil(nav.getBoundingClientRect().height);
    }

    if (isPatient && isMobile && navHeight < 70) {
        navHeight = 92;
    }

    const right = isMobile ? 18 : 22;
    const fabSize = isMobile ? 48 : 46;
    const gap = 14;

    const accessibilityBottom = isPatient && isMobile
        ? navHeight + 16
        : 24;

    const chatbotBottom = accessibilityBottom + fabSize + gap;
    const backTopBottom = chatbotBottom + fabSize + gap;

    document.documentElement.style.setProperty('--float-right', `${right}px`);
    document.documentElement.style.setProperty('--float-right-final', `${right}px`);
    document.documentElement.style.setProperty('--patient-nav-height', `${navHeight}px`);
    document.documentElement.style.setProperty('--fab-final-size', `${fabSize}px`);
    document.documentElement.style.setProperty('--accessibility-bottom', `${accessibilityBottom}px`);
    document.documentElement.style.setProperty('--accessibility-bottom-final', `${accessibilityBottom}px`);
    document.documentElement.style.setProperty('--chatbot-bottom-final', `${chatbotBottom}px`);
    document.documentElement.style.setProperty('--back-top-bottom', `${backTopBottom}px`);
    document.documentElement.style.setProperty('--back-top-bottom-final', `${backTopBottom}px`);

    document.querySelectorAll('.asw-widget, .asw-menu-btn').forEach((el) => {
        el.style.setProperty('--asw-off-x', `${right}px`);
        el.style.setProperty('--asw-off-y', `${accessibilityBottom}px`);
        el.style.setProperty('--asw-right', `${right}px`);
        el.style.setProperty('--asw-bottom', `${accessibilityBottom}px`);
        el.style.right = `${right}px`;
        el.style.bottom = `${accessibilityBottom}px`;
    });
}

document.addEventListener('DOMContentLoaded', fixSiennaPosition);
window.addEventListener('load', fixSiennaPosition);
window.addEventListener('resize', fixSiennaPosition);
window.addEventListener('orientationchange', fixSiennaPosition);

function clearSearchInput(input, options = {}) {
    if (!input) return;

    const shouldFocus = options.focus !== false;

    input.value = '';

    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));

    if (shouldFocus) {
        input.focus();
    }
}

function initSearchClearButtons() {
    document.querySelectorAll('[data-search-input]').forEach((input) => {
        if (input.dataset.searchClearInitialized === 'true') return;

        const wrapper = input.closest('[data-search-wrapper]');
        const clearButton = wrapper?.querySelector('[data-search-clear]');

        if (!wrapper || !clearButton) return;

        input.dataset.searchClearInitialized = 'true';

        const updateClearButton = () => {
            clearButton.classList.toggle('show', input.value.trim() !== '');
        };

        clearButton.addEventListener('click', () => {
            clearSearchInput(input);
            updateClearButton();
        });

        input.addEventListener('input', updateClearButton);
        input.addEventListener('change', updateClearButton);

        updateClearButton();
    });
}

document.addEventListener('click', function (event) {
    const clearButton = event.target.closest('[data-clear-search]');
    if (!clearButton) return;

    event.preventDefault();

    const targetSelector = clearButton.dataset.searchTarget || '[data-search-input]';
    const input = document.querySelector(targetSelector);

    clearSearchInput(input);
});

document.addEventListener('DOMContentLoaded', initSearchClearButtons);

window.clearSearchInput = clearSearchInput;
window.initSearchClearButtons = initSearchClearButtons;

function escapeToastHTML(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatToastMessage(message) {
    return escapeToastHTML(message)
        .replace(/&lt;strong&gt;(.*?)&lt;\/strong&gt;/g, '<strong>$1</strong>');
}

function normalizeToastArgs(first = 'success', second = '', third = undefined, fourth = undefined) {
    const validTypes = ['success', 'error', 'warning', 'info'];
    const defaultDuration = 5000;

    if (typeof first === 'object' && first !== null) {
        const type = validTypes.includes(String(first.type || '').toLowerCase())
            ? String(first.type).toLowerCase()
            : 'info';

        return {
            type,
            title: first.title || type.charAt(0).toUpperCase() + type.slice(1),
            message: first.message || '',
            duration: Number(first.duration) || defaultDuration,
        };
    }

    const firstLower = String(first || '').toLowerCase();
    const thirdLower = String(third || '').toLowerCase();

    if (validTypes.includes(firstLower)) {
        return {
            type: firstLower,
            title: firstLower.charAt(0).toUpperCase() + firstLower.slice(1),
            message: second || '',
            duration: Number(third) || defaultDuration,
        };
    }

    if (validTypes.includes(thirdLower)) {
        return {
            type: thirdLower,
            title: first || thirdLower.charAt(0).toUpperCase() + thirdLower.slice(1),
            message: second || '',
            duration: Number(fourth) || defaultDuration,
        };
    }

    return {
        type: 'info',
        title: first || 'Notification',
        message: second || '',
        duration: Number(third) || defaultDuration,
    };
}

const TOAST_MAX_VISIBLE = 3;
const activeToastRegistry = new Map();

function getToastKey(type, title, message) {
    return `${type}|${String(message || '').trim()}`;
}

function pruneToastStack(container) {
    if (!container) return;

    const visibleToasts = Array.from(
        container.querySelectorAll('.toast-item:not(.toast-exit)')
    );

    while (visibleToasts.length > TOAST_MAX_VISIBLE) {
        const oldestToast = visibleToasts.shift();

        if (oldestToast?.__closeToast) {
            oldestToast.__closeToast();
        } else {
            oldestToast?.remove();
        }
    }
}

function ensureToastContainer() {
    let container = document.getElementById('toastContainer');

    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
    }

    if (container.parentElement !== document.body) {
        document.body.appendChild(container);
    }

    return container;
}

function showToast(first = 'success', second = '', third = undefined, fourth = undefined) {
    const { type, title, message, duration } = normalizeToastArgs(first, second, third, fourth);

    const container = ensureToastContainer();

    const toastKey = getToastKey(type, title, message);
    const existingToast = activeToastRegistry.get(toastKey);

    if (
        existingToast &&
        document.body.contains(existingToast) &&
        !existingToast.classList.contains('toast-exit')
    ) {
        existingToast.__restartToast?.(duration);

        existingToast.classList.remove('toast-bumped');
        void existingToast.offsetWidth;
        existingToast.classList.add('toast-bumped');

        return existingToast;
    }

    const toast = document.createElement('div');
    toast.className = `toast-item toast-${type} ${type}`;
    toast.dataset.toastKey = toastKey;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

    const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info',
    };

    toast.innerHTML = `
        <div class="toast-icon-wrap">
            <i class="fa-solid ${icons[type] || icons.info}"></i>
        </div>

        <div class="toast-content">
            <div class="toast-title">${escapeToastHTML(title)}</div>
            <div class="toast-message">${formatToastMessage(message)}</div>
        </div>

        <button type="button" class="toast-close" aria-label="Close notification">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="toast-progress"></div>
    `;

    container.appendChild(toast);
    activeToastRegistry.set(toastKey, toast);

    const progress = toast.querySelector('.toast-progress');

    let remaining = duration;
    let startedAt = Date.now();
    let timeoutId = null;
    let closed = false;

    const resetProgress = (nextDuration = duration) => {
        if (!progress) return;

        progress.style.animation = 'none';
        void progress.offsetWidth;
        progress.style.animation = `toastProgress ${nextDuration}ms linear forwards`;
    };

    const closeToast = () => {
        if (closed) return;

        closed = true;
        clearTimeout(timeoutId);

        if (activeToastRegistry.get(toastKey) === toast) {
            activeToastRegistry.delete(toastKey);
        }

        toast.classList.remove('is-paused', 'toast-bumped');
        toast.classList.add('toast-exit');

        setTimeout(() => {
            toast.remove();
        }, 320);
    };

    const startTimer = () => {
        clearTimeout(timeoutId);
        startedAt = Date.now();
        timeoutId = setTimeout(closeToast, remaining);
    };

    const restartToast = (nextDuration = duration) => {
        if (closed) return;

        clearTimeout(timeoutId);

        remaining = Number(nextDuration) || duration;
        startedAt = Date.now();

        toast.classList.remove('is-paused');
        resetProgress(remaining);

        timeoutId = setTimeout(closeToast, remaining);
    };

    const pauseToast = () => {
        if (closed) return;

        clearTimeout(timeoutId);
        remaining -= Date.now() - startedAt;
        remaining = Math.max(remaining, 0);

        toast.classList.add('is-paused');

        if (progress) {
            progress.style.animationPlayState = 'paused';
        }
    };

    const resumeToast = () => {
        if (closed) return;

        toast.classList.remove('is-paused');

        if (progress) {
            progress.style.animationPlayState = 'running';
        }

        if (remaining <= 0) {
            closeToast();
            return;
        }

        startTimer();
    };

    toast.__closeToast = closeToast;
    toast.__restartToast = restartToast;

    toast.querySelector('.toast-close')?.addEventListener('click', closeToast);

    toast.addEventListener('mouseenter', pauseToast);
    toast.addEventListener('mouseleave', resumeToast);

    resetProgress(duration);
    startTimer();
    pruneToastStack(container);

    return toast;
}

function dismissToast(toast) {
    if (!toast) return;

    const targetToast = toast.closest ? toast.closest('.toast-item') : toast;

    if (!targetToast || targetToast.classList.contains('toast-exit')) return;

    targetToast.classList.remove('is-paused');
    targetToast.classList.add('toast-exit');

    setTimeout(() => {
        targetToast.remove();
    }, 320);
}

window.showToast = showToast;
window.dismissToast = dismissToast;

function getCurrentRole() {
    if (document.body.classList.contains('role-admin')) return 'admin';
    if (document.body.classList.contains('role-dentist')) return 'dentist';
    if (document.body.classList.contains('role-patient')) return 'patient';
    return 'global';
}

function getSidebarStorageKey() {
    const role = getCurrentRole();

    return {
        admin: 'adminSidebarCollapsed',
        dentist: 'dentistSidebarCollapsed',
        patient: 'patientSidebarCollapsed',
        global: 'sidebarCollapsed',
    }[role] || 'sidebarCollapsed';
}

function applyGlobalTheme(theme = 'light') {
    const nextTheme = theme === 'dark' ? 'dark' : 'light';

    document.documentElement.setAttribute('data-theme', nextTheme);
    document.documentElement.style.backgroundColor = nextTheme === 'dark' ? '#000D1A' : '#F4F4F4';

    localStorage.setItem('theme', nextTheme);

    document.querySelectorAll('.theme-option[data-theme]').forEach(option => {
        option.classList.toggle('active', option.dataset.theme === nextTheme);
    });

    document.querySelectorAll('.theme-indicator').forEach(indicator => {
        indicator.classList.toggle('dark-mode', nextTheme === 'dark');
    });

    document.querySelectorAll('#themeSwitchCheckbox').forEach(checkbox => {
        checkbox.checked = nextTheme === 'dark';
    });

    document.querySelectorAll('#themeIcon').forEach(icon => {
        icon.className = nextTheme === 'dark'
            ? 'fa-regular fa-moon text-gray-400 text-base'
            : 'fa-regular fa-sun text-gray-400 text-base';
    });

    window.dispatchEvent(new CustomEvent('global-theme-change', {
        detail: { theme: nextTheme }
    }));
}

function initGlobalThemeControls(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    const savedTheme = localStorage.getItem('theme') || 'light';

    applyGlobalTheme(savedTheme);

    scope.querySelectorAll('.theme-option[data-theme]').forEach(option => {
        if (option.dataset.themeInitialized === 'true') return;

        option.dataset.themeInitialized = 'true';

        option.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            applyGlobalTheme(option.dataset.theme || 'light');
        });
    });

    scope.querySelectorAll('#themeSwitchCheckbox').forEach(checkbox => {
        if (checkbox.dataset.themeInitialized === 'true') return;

        checkbox.dataset.themeInitialized = 'true';
        checkbox.checked = savedTheme === 'dark';

        checkbox.addEventListener('click', event => {
            event.stopPropagation();
        });

        checkbox.addEventListener('change', () => {
            applyGlobalTheme(checkbox.checked ? 'dark' : 'light');
        });
    });

    scope.querySelectorAll('#darkModeToggleItem').forEach(item => {
        if (item.dataset.themeItemInitialized === 'true') return;

        item.dataset.themeItemInitialized = 'true';

        item.addEventListener('click', event => {
            const clickedSwitch = event.target.closest('#themeSwitchCheckbox, .modern-switch');

            if (clickedSwitch) return;

            event.preventDefault();
            event.stopPropagation();

            const checkbox = item.querySelector('#themeSwitchCheckbox');

            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
}

function initSidebarThemeDropdowns() {
    const dropdowns = document.querySelectorAll('[data-sidebar-theme-dropdown]');

    const syncThemeIcons = () => {
        const theme = localStorage.getItem('theme') || 'light';

        document.querySelectorAll('[data-sidebar-theme-icon]').forEach(icon => {
            icon.className = theme === 'dark'
                ? 'fa-regular fa-moon'
                : 'fa-solid fa-sun';
        });
    };

    dropdowns.forEach(dropdown => {
        if (dropdown.dataset.themeDropdownInitialized === 'true') return;

        dropdown.dataset.themeDropdownInitialized = 'true';

        const trigger = dropdown.querySelector('[data-sidebar-theme-trigger]');

        trigger?.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            dropdowns.forEach(item => {
                if (item !== dropdown) item.classList.remove('open');
            });

            dropdown.classList.toggle('open');
        });

        dropdown.querySelectorAll('[data-theme]').forEach(option => {
            option.addEventListener('click', () => {
                dropdown.classList.remove('open');
                setTimeout(syncThemeIcons, 0);
            });
        });
    });

    document.addEventListener('click', () => {
        dropdowns.forEach(dropdown => dropdown.classList.remove('open'));
    });

    window.addEventListener('global-theme-change', syncThemeIcons);
    syncThemeIcons();
}

function closeHeaderMenus() {
    document.getElementById('notifMenu')?.classList.remove('open', 'show');
    document.getElementById('userMenu')?.classList.remove('open', 'show');

    document.getElementById('notifBtn')?.classList.remove('active');
    document.getElementById('userBtn')?.classList.remove('active');
}

function initHeaderMenus() {
    const notifBtn = document.getElementById('notifBtn');
    const notifMenu = document.getElementById('notifMenu');
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');

    notifBtn?.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();

        const willOpen = !notifMenu?.classList.contains('show');

        closeHeaderMenus();

        if (willOpen) {
            notifMenu?.classList.add('open', 'show');
            notifBtn.classList.add('active');
        }
    });

    userBtn?.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();

        const willOpen = !userMenu?.classList.contains('show');

        closeHeaderMenus();

        if (willOpen) {
            userMenu?.classList.add('open', 'show');
            userBtn.classList.add('active');
        }
    });

    notifMenu?.addEventListener('click', event => event.stopPropagation());
    userMenu?.addEventListener('click', event => event.stopPropagation());
}

function openDrawer() {
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');

    if (!drawer || !overlay) return;

    overlay.classList.add('open');
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDrawer() {
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');

    if (!drawer || !overlay) return;

    drawer.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
}

function applySidebarState(isCollapsed) {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (!sidebar || !mainContent) return;

    if (window.innerWidth <= 767 && !document.body.classList.contains('role-patient')) {
        sidebar.classList.remove('collapsed');
        document.body.classList.remove('sidebar-collapsed');
        return;
    }

    sidebar.classList.toggle('collapsed', isCollapsed);
    document.body.classList.toggle('sidebar-collapsed', isCollapsed);

    document.querySelectorAll('#sidebarIcon, #sidebarToggleIcon').forEach(icon => {
        icon.className = isCollapsed ? 'fa-solid fa-bars' : 'fa-solid fa-xmark';
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    const nextState = !sidebar.classList.contains('collapsed');

    applySidebarState(nextState);
    localStorage.setItem(getSidebarStorageKey(), nextState ? '1' : '0');
}

function initGlobalSidebar() {
    const savedState = localStorage.getItem(getSidebarStorageKey()) === '1';

    applySidebarState(savedState);

    document.querySelectorAll('#sidebarToggleBtn, #desktopSidebarToggle, [data-sidebar-toggle]').forEach(button => {
        if (button.dataset.sidebarInitialized === 'true') return;

        button.dataset.sidebarInitialized = 'true';

        if (!button.getAttribute('onclick')) {
            button.addEventListener('click', event => {
                event.preventDefault();
                toggleSidebar();
            });
        }
    });

    document.documentElement.classList.remove('sidebar-preload', 'sidebar-collapsed-init');
}

function initAdminSidebarGroupClick() {
    const sidebar = document.querySelector('#sidebar.sidebar-admin');
    if (!sidebar) return;

    const groups = Array.from(sidebar.querySelectorAll('.nav-group'));

    const isCollapsed = () =>
        sidebar.classList.contains('collapsed') ||
        document.body.classList.contains('sidebar-collapsed');

    const closeGroups = () => {
        sidebar.classList.remove('has-flyout-open');

        groups.forEach(group => {
            group.classList.remove('is-flyout-open');
            group.querySelector('[data-admin-group-toggle]')?.setAttribute('aria-expanded', 'false');
        });
    };

    const openGroup = (targetGroup) => {
        sidebar.classList.add('has-flyout-open');

        groups.forEach(group => {
            const isOpen = group === targetGroup;

            group.classList.toggle('is-flyout-open', isOpen);
            group.querySelector('[data-admin-group-toggle]')?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    };

    groups.forEach(group => {
        const trigger = group.querySelector('[data-admin-group-toggle]');
        const panel = group.querySelector('.group-body');

        if (!trigger || trigger.dataset.groupClickInitialized === 'true') return;

        trigger.dataset.groupClickInitialized = 'true';

        trigger.addEventListener('click', event => {
            if (!isCollapsed()) return;

            event.preventDefault();
            event.stopPropagation();

            const alreadyOpen = group.classList.contains('is-flyout-open');

            closeGroups();

            if (!alreadyOpen) {
                openGroup(group);
            }
        });

        panel?.addEventListener('click', event => {
            event.stopPropagation();
        });
    });

    document.addEventListener('click', event => {
        if (!sidebar.contains(event.target)) {
            closeGroups();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeGroups();
        }
    });

    window.addEventListener('resize', () => {
        if (!isCollapsed()) {
            closeGroups();
        }
    });

    window.closeAdminSidebarGroups = closeGroups;
}

function initMobileDrawerControls() {
    const drawerButtons = document.querySelectorAll(
        '#mobileMenuBtn, [data-mobile-menu-toggle], [data-drawer-toggle]'
    );

    drawerButtons.forEach(button => {
        if (button.dataset.drawerToggleInitialized === 'true') return;

        button.dataset.drawerToggleInitialized = 'true';

        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            const drawer = document.getElementById('mobileDrawer');

            if (drawer?.classList.contains('open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
    });

    document.getElementById('mobileDrawerOverlay')?.addEventListener('click', closeDrawer);

    document.querySelectorAll('[data-drawer-close]').forEach(button => {
        if (button.dataset.drawerCloseInitialized === 'true') return;

        button.dataset.drawerCloseInitialized = 'true';

        button.addEventListener('click', event => {
            event.preventDefault();
            closeDrawer();
        });
    });
}

function initGlobalDateTime() {
    const dateEl = document.getElementById('currentDate');
    const dateIconEl = document.getElementById('currentDateIcon');

    if (!dateEl) return;

    function updateDateTime() {
        const now = new Date();

        const dateText = now.toLocaleDateString('en-US', {
            timeZone: 'Asia/Manila',
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const timeText = now.toLocaleTimeString('en-US', {
            timeZone: 'Asia/Manila',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        dateEl.textContent = `${dateText} | ${timeText}`;

        if (!dateIconEl) return;

        const hourInManila = Number(new Intl.DateTimeFormat('en-US', {
            timeZone: 'Asia/Manila',
            hour: 'numeric',
            hour12: false
        }).format(now));

        if (hourInManila >= 5 && hourInManila < 18) {
            dateIconEl.className = 'fa-solid fa-sun';
            dateIconEl.style.color = hourInManila < 12 ? '#fcd34d' : '#fb923c';
        } else {
            dateIconEl.className = 'fa-solid fa-moon';
            dateIconEl.style.color = '#c4b5fd';
        }
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);
}

function initPatientMobileFab() {
    const mobFab = document.getElementById('mobFab');
    const mobFabMenu = document.getElementById('mobFabMenu');

    if (!mobFab || !mobFabMenu) return;

    mobFab.addEventListener('click', event => {
        event.stopPropagation();

        closeHeaderMenus();

        mobFabMenu.classList.toggle('open');
        mobFab.classList.toggle('open');
    });

    mobFabMenu.addEventListener('click', event => {
        event.stopPropagation();
    });

    document.addEventListener('click', event => {
        const clickedInsideMenu = mobFabMenu.contains(event.target);
        const clickedFab = mobFab.contains(event.target);

        if (!clickedInsideMenu && !clickedFab) {
            mobFabMenu.classList.remove('open');
            mobFab.classList.remove('open');
        }
    });

    document.querySelectorAll('[data-quick-action]').forEach(button => {
        if (button.dataset.quickActionInitialized === 'true') return;

        button.dataset.quickActionInitialized = 'true';

        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            mobFabMenu.classList.remove('open');
            mobFab.classList.remove('open');

            if (typeof window.openQuickAction === 'function') {
                window.openQuickAction(button.dataset.quickAction);
            }
        });
    });
}

function initSessionStorageToasts() {
    const keys = ['dentistToast', 'adminToast', 'patientToast', 'globalToast'];

    keys.forEach(key => {
        const raw = sessionStorage.getItem(key);
        if (!raw) return;

        try {
            const toast = JSON.parse(raw);

            showToast({
                type: toast.type || (toast.tone === 'danger' ? 'error' : toast.tone) || 'success',
                title: toast.title || 'Notification',
                message: toast.message || '',
                duration: toast.duration || 4000,
            });
        } catch (_) {
            // Ignore invalid toast payloads
        }

        sessionStorage.removeItem(key);
    });
}

document.documentElement.classList.add('sidebar-preload');

try {
    const savedSidebarState = localStorage.getItem(getSidebarStorageKey());
    if (savedSidebarState === '1') {
        document.documentElement.classList.add('sidebar-collapsed-init');
    }
} catch (_) {
    // Ignore storage errors
}

document.addEventListener('DOMContentLoaded', () => {
    initGlobalThemeControls();
    initSidebarThemeDropdowns();
    initHeaderMenus();
    initMobileDrawerControls();
    initGlobalSidebar();
    initAdminSidebarGroupClick();
    initGlobalDateTime();
    initPatientMobileFab();
    initSessionStorageToasts();
});

document.addEventListener('click', closeHeaderMenus);

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        closeDrawer();
        closeHeaderMenus();
    }
});

window.addEventListener('storage', event => {
    if (event.key === 'theme') {
        applyGlobalTheme(event.newValue || 'light');
    }
});

window.applyTheme = applyGlobalTheme;
window.openDrawer = openDrawer;
window.closeDrawer = closeDrawer;
window.toggleSidebar = toggleSidebar;
window.applySidebarState = applySidebarState;

function readJsonPayload(id) {
    const el = document.getElementById(id);
    if (!el) return null;

    try {
        return JSON.parse(el.textContent || 'null');
    } catch (_) {
        return null;
    }
}

function initFlashToasts() {
    const payload = readJsonPayload('flashToastPayload');

    if (!Array.isArray(payload)) return;

    payload.forEach((toast) => {
        if (!toast || !toast.message) return;

        showToast({
            type: toast.type || 'info',
            title: toast.title || 'Notification',
            message: toast.message,
        });
    });
}

function acceptTerms() {
    const modal = document.getElementById('termsModal');

    if (modal?.open) {
        modal.close();
    }
}

function initTermsModal() {
    const modal = document.getElementById('termsModal');
    if (!modal) return;

    const checkbox = modal.querySelector('#termsCheckbox, [data-terms-checkbox]');
    const continueBtn = modal.querySelector('#termsContinueBtn, [data-terms-continue]');

    if (checkbox && continueBtn) {
        checkbox.checked = false;
        continueBtn.disabled = true;

        checkbox.addEventListener('change', () => {
            continueBtn.disabled = !checkbox.checked;
        });

        continueBtn.addEventListener('click', acceptTerms);
    }

    const shouldShow = modal.dataset.showTerms === 'true';

    if (shouldShow && typeof modal.showModal === 'function' && !modal.open) {
        requestAnimationFrame(() => {
            modal.showModal();
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initFlashToasts();
    initTermsModal();
});

window.acceptTerms = acceptTerms;
window.initTermsModal = initTermsModal;
window.initFlashToasts = initFlashToasts;

const modalTimers = {};

function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    if (modalTimers[id]) {
        clearTimeout(modalTimers[id]);
        modalTimers[id] = null;
    }

    modal.classList.remove('closing');
    modal.classList.add('open');
    document.body.classList.add('modal-lock');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal || (!modal.classList.contains('open') && !modal.classList.contains('closing'))) return;

    modal.classList.remove('open');
    modal.classList.add('closing');

    if (modalTimers[id]) clearTimeout(modalTimers[id]);

    modalTimers[id] = setTimeout(() => {
        modal.classList.remove('closing');
        modalTimers[id] = null;

        if (!document.querySelector('.ui-modal.open, .ui-modal.closing')) {
            document.body.classList.remove('modal-lock');
        }
    }, 180);
}

function closeModalOnBackdrop(event, id) {
    return false;
}

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;

    const openModalEl = document.querySelector('.ui-modal.open');
    if (openModalEl && openModalEl.id) {
        closeModal(openModalEl.id);
    }
});

window.openModal = openModal;
window.closeModal = closeModal;
window.closeModalOnBackdrop = closeModalOnBackdrop;

window.openInventoryModal = openModal;
window.closeInventoryModal = closeModal;
window.closeOnBackdrop = closeModalOnBackdrop;

function openFilterDrawer(panelId = 'filterPanel', overlayId = 'filterOverlay') {
    document.documentElement.classList.add('filter-lock');
    document.body.classList.add('filter-lock');
    document.getElementById(panelId)?.classList.add('open');
    document.getElementById(overlayId)?.classList.add('open');
}

function closeFilterDrawer(panelId = 'filterPanel', overlayId = 'filterOverlay') {
    document.documentElement.classList.remove('filter-lock');
    document.body.classList.remove('filter-lock');
    document.getElementById(panelId)?.classList.remove('open');
    document.getElementById(overlayId)?.classList.remove('open');
}

window.openFilterDrawer = openFilterDrawer;
window.closeFilterDrawer = closeFilterDrawer;

function openFilterPanel() {
    openFilterDrawer('filterPanel', 'filterOverlay');
}

function closeFilterPanel() {
    closeFilterDrawer('filterPanel', 'filterOverlay');
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

document.addEventListener('DOMContentLoaded', () => initCharLimitFields());

window.validateCharLimit = validateCharLimit;
window.initCharLimitFields = initCharLimitFields;

function formatStockNo(input) {
    if (!input) return;

    let digits = input.value.replace(/\D/g, '');
    if (digits.length > 5) digits = digits.slice(0, 5);

    input.value = digits.length <= 2 ? digits : `${digits.slice(0, 2)}-${digits.slice(2)}`;
}

window.setFieldState = setFieldState;
window.updateCharCounter = updateCharCounter;
window.formatStockNo = formatStockNo;

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function escapeHtml(value = '') {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function debounce(callback, wait = 250) {
    let timeout;

    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => callback.apply(this, args), wait);
    };
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            ...(options.headers || {})
        },
        ...options
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const error = new Error('Request failed');
        error.response = response;
        error.data = data;
        throw error;
    }

    return data;
}

window.getCsrfToken = getCsrfToken;
window.escapeHtml = escapeHtml;
window.debounce = debounce;
window.requestJson = requestJson;

window.formatDateForInput = function (date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
};

window.setQuickDateRange = function (days, fromId, toId) {
    const to = new Date();
    const from = new Date();

    from.setDate(to.getDate() - Number(days));

    const fromInput = document.getElementById(fromId);
    const toInput = document.getElementById(toId);

    if (fromInput) fromInput.value = window.formatDateForInput(from);
    if (toInput) toInput.value = window.formatDateForInput(to);
};

window.bindQuickDatePresets = function ({
    groupId = "datePresetGroup",
    fromId,
    toId,
    onChange
}) {
    const group = document.getElementById(groupId);
    if (!group) return;

    group.addEventListener("click", function (e) {
        const btn = e.target.closest(".quick-date-chip");
        if (!btn) return;

        group.querySelectorAll(".quick-date-chip").forEach(b => {
            b.classList.remove("active");
        });

        btn.classList.add("active");

        window.setQuickDateRange(btn.getAttribute("data-range"), fromId, toId);

        if (typeof onChange === "function") onChange();
    });

    [fromId, toId].forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;

        ["input", "change"].forEach(evt => {
            input.addEventListener(evt, function () {
                group.querySelectorAll(".quick-date-chip").forEach(b => {
                    b.classList.remove("active");
                });

                if (typeof onChange === "function") onChange();
            });
        });
    });
};

window.syncFilterTagGroup = function (groupId, value) {
    const group = document.getElementById(groupId);
    if (!group) return;

    group.querySelectorAll(".ftag").forEach(btn => {
        btn.classList.toggle("ftag-active", btn.getAttribute("data-val") === value);
    });
};

window.bindFilterTagGroup = function ({
    groupId,
    onChange
}) {
    const group = document.getElementById(groupId);
    if (!group) return;

    group.addEventListener("click", function (e) {
        const btn = e.target.closest(".ftag");
        if (!btn) return;

        group.querySelectorAll(".ftag").forEach(b => {
            b.classList.remove("ftag-active");
        });

        btn.classList.add("ftag-active");

        if (typeof onChange === "function") onChange(btn.getAttribute("data-val"), btn);
    });
};

window.updateShowResultsText = function (count, targetId = "showResultsText") {
    const label = document.getElementById(targetId);
    if (!label) return;

    label.textContent = `Show ${count} ${count === 1 ? "result" : "results"}`;
};

window.APPOINTMENT_STATUS_META = {
    today: {
        label: 'Today',
        className: 'status-today',
        accentClass: 'accent-today',
        statClass: 's-today',
        icon: 'fa-calendar-day'
    },
    upcoming: {
        label: 'Upcoming',
        className: 'status-upcoming',
        accentClass: 'accent-upcoming',
        statClass: 's-upcoming',
        icon: 'fa-hourglass-half'
    },
    rescheduled: {
        label: 'Rescheduled',
        className: 'status-rescheduled',
        accentClass: 'accent-rescheduled',
        statClass: 's-rescheduled',
        icon: 'fa-rotate'
    },
    completed: {
        label: 'Completed',
        className: 'status-completed',
        accentClass: 'accent-completed',
        statClass: 's-completed',
        icon: 'fa-circle-check'
    },
    cancelled: {
        label: 'Cancelled',
        className: 'status-cancelled',
        accentClass: 'accent-cancelled',
        statClass: 's-cancelled',
        icon: 'fa-circle-xmark'
    },
    default: {
        label: 'Status',
        className: 'status-default',
        accentClass: 'accent-default',
        statClass: 's-default',
        icon: 'fa-circle'
    }
};

window.getAppointmentStatusMeta = function (status) {
    const key = String(status || '').toLowerCase().trim();
    return window.APPOINTMENT_STATUS_META[key] || window.APPOINTMENT_STATUS_META.default;
};

window.setGlobalFilterButtonState = function ({
    buttonId = 'filterBtn',
    badgeId = 'filterBadge',
    resetId = 'externalClearFilterBtn',
    count = 0
} = {}) {
    const btn = document.getElementById(buttonId);
    const badge = document.getElementById(badgeId);
    const reset = document.getElementById(resetId);

    const has = Number(count) > 0;

    if (btn) {
        btn.classList.toggle('has-filters', has);
        btn.setAttribute('aria-pressed', has ? 'true' : 'false');
    }

    if (badge) {
        badge.classList.toggle('show', has);
        badge.textContent = has ? String(count) : '';
    }

    if (reset) {
        reset.classList.toggle('hidden', !has);
        reset.classList.toggle('show', has);
    }
};

function initGlobalViewToggles(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    const toggles = scope.querySelectorAll('[data-global-view-toggle]');

    toggles.forEach((toggle) => {
        if (toggle.dataset.globalViewInitialized === 'true') return;

        toggle.dataset.globalViewInitialized = 'true';

        const rootSelector = toggle.dataset.viewRoot || '#mainContent';
        const listSelector = toggle.dataset.listView;
        const gridSelector = toggle.dataset.gridView;
        const storageKey = toggle.dataset.storageKey || `${toggle.id || 'global'}_view_mode`;

        const pageRoot = document.querySelector(rootSelector);
        const listView = listSelector ? document.querySelector(listSelector) : null;
        const gridView = gridSelector ? document.querySelector(gridSelector) : null;
        const buttons = Array.from(toggle.querySelectorAll('[data-view-mode]'));

        if (!buttons.length) return;

        const setMode = (mode, options = {}) => {
            const nextMode = mode === 'grid' ? 'grid' : 'list';
            const isGrid = nextMode === 'grid';

            if (listView) listView.hidden = isGrid;
            if (gridView) gridView.hidden = !isGrid;

            pageRoot?.classList.toggle('mode-grid', isGrid);
            pageRoot?.classList.toggle('mode-list', !isGrid);

            buttons.forEach((button) => {
                const active = button.dataset.viewMode === nextMode;

                button.classList.toggle('active', active);
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            toggle.dataset.currentView = nextMode;

            if (options.persist !== false) {
                localStorage.setItem(storageKey, nextMode);
            }

            toggle.dispatchEvent(new CustomEvent('global-view-change', {
                bubbles: true,
                detail: { mode: nextMode }
            }));
        };

        toggle.__setGlobalViewMode = setMode;
        toggle.__getGlobalViewMode = () => toggle.dataset.currentView || localStorage.getItem(storageKey) || 'list';

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                setMode(button.dataset.viewMode);
            });
        });

        const mobile = window.matchMedia('(max-width: 767px)').matches;
        const savedMode = localStorage.getItem(storageKey);

        setMode(mobile ? 'grid' : savedMode || 'list', { persist: false });
    });
}

function setGlobalViewMode(toggleOrId, mode, options = {}) {
    const toggle = typeof toggleOrId === 'string'
        ? document.getElementById(toggleOrId) || document.querySelector(toggleOrId)
        : toggleOrId;

    if (!toggle) return;

    if (typeof toggle.__setGlobalViewMode !== 'function') {
        initGlobalViewToggles(document);
    }

    toggle.__setGlobalViewMode?.(mode, options);
}

function getGlobalViewMode(toggleOrId) {
    const toggle = typeof toggleOrId === 'string'
        ? document.getElementById(toggleOrId) || document.querySelector(toggleOrId)
        : toggleOrId;

    if (!toggle) return 'list';

    return toggle.__getGlobalViewMode?.() || toggle.dataset.currentView || 'list';
}

document.addEventListener('DOMContentLoaded', () => {
    initGlobalViewToggles();
});

window.initGlobalViewToggles = initGlobalViewToggles;
window.setGlobalViewMode = setGlobalViewMode;
window.getGlobalViewMode = getGlobalViewMode;

function initDashboardLogsViewToggle() {
    const root = document.getElementById('mainContent');
    const toggle = document.getElementById('dashboardLogsViewToggle');
    const listView = document.getElementById('dashboardLogsListView');
    const gridView = document.getElementById('dashboardLogsGridView');
    const buttons = document.querySelectorAll('[data-dashboard-logs-view]');

    if (!toggle || !listView || !gridView || !buttons.length) return;

    const setMode = (mode) => {
        const nextMode = mode === 'grid' ? 'grid' : 'list';
        const isGrid = nextMode === 'grid';

        listView.hidden = isGrid;
        gridView.hidden = !isGrid;

        root?.classList.toggle('mode-grid', isGrid);
        root?.classList.toggle('mode-list', !isGrid);

        buttons.forEach((button) => {
            const active = button.dataset.dashboardLogsView === nextMode;

            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        localStorage.setItem('admin_dashboard_logs_view', nextMode);
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            setMode(button.dataset.dashboardLogsView);
        });
    });

    const mobile = window.matchMedia('(max-width: 767px)').matches;
    const savedMode = localStorage.getItem('admin_dashboard_logs_view');

    setMode(mobile ? 'grid' : savedMode || 'list');
}

const activeVoiceControllers = new Map();

function getSpeechRecognitionConstructor() {
    return window.SpeechRecognition || window.webkitSpeechRecognition || null;
}

function setVoiceStatus(statusEl, message = '', state = 'default') {
    if (!statusEl) return;

    statusEl.textContent = message;
    statusEl.classList.remove('hidden', 'is-listening', 'is-success', 'is-error', 'is-default');

    if (!message) {
        statusEl.classList.add('hidden');
        return;
    }

    statusEl.classList.add(`is-${state}`);
}

function resolveVoiceTarget(button) {
    const targetSelector = button.dataset.voiceTarget;

    if (targetSelector) {
        return document.querySelector(targetSelector);
    }

    const field = button.closest('[data-voice-field], .voice-search-row, .st-voice-row');

    return field?.querySelector('input:not([type="hidden"]), textarea') || null;
}

function resolveVoiceStatus(button) {
    const statusSelector = button.dataset.voiceStatus;

    if (statusSelector) {
        return document.querySelector(statusSelector);
    }

    const field = button.closest('[data-voice-field], .voice-search-row, .st-voice-row');

    return field?.querySelector('[data-voice-status]') || null;
}

function stopActiveVoiceExcept(currentButton = null) {
    activeVoiceControllers.forEach((controller, button) => {
        if (button === currentButton) return;

        try {
            controller.recognition.stop();
        } catch (_) { }

        button.classList.remove('mic-active');
        setVoiceStatus(controller.statusEl, '', 'default');
        activeVoiceControllers.delete(button);
    });
}

function initGlobalVoiceInputs(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

    const SpeechRecognition = getSpeechRecognitionConstructor();

    const buttons = scope.querySelectorAll(
        '.voice-search-mic.external[data-voice-trigger], [data-global-voice-trigger]'
    );

    buttons.forEach((button) => {
        if (button.dataset.voiceInitialized === 'true') return;

        button.dataset.voiceInitialized = 'true';

        button.addEventListener('click', () => {
            const input = resolveVoiceTarget(button);
            const statusEl = resolveVoiceStatus(button);

            if (!input) {
                setVoiceStatus(statusEl, 'No input found', 'error');
                return;
            }

            if (!SpeechRecognition) {
                setVoiceStatus(statusEl, 'Voice not supported', 'error');
                return;
            }

            if (activeVoiceControllers.has(button)) {
                const active = activeVoiceControllers.get(button);

                try {
                    active.recognition.stop();
                } catch (_) { }

                button.classList.remove('mic-active');
                setVoiceStatus(statusEl, '', 'default');
                activeVoiceControllers.delete(button);
                return;
            }

            stopActiveVoiceExcept(button);

            const recognition = new SpeechRecognition();

            recognition.lang = input.dataset.voiceLang || button.dataset.voiceLang || 'en-US';
            recognition.continuous = false;
            recognition.interimResults = true;
            recognition.maxAlternatives = 1;

            let finalTranscript = '';

            recognition.onstart = () => {
                button.classList.add('mic-active');
                setVoiceStatus(statusEl, 'Listening...', 'listening');
            };

            recognition.onresult = (event) => {
                let interimTranscript = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0]?.transcript?.trim() || '';

                    if (!transcript) continue;

                    if (event.results[i].isFinal) {
                        finalTranscript += ` ${transcript}`;
                    } else {
                        interimTranscript += ` ${transcript}`;
                    }
                }

                const spokenText = (finalTranscript || interimTranscript).trim();

                if (!spokenText) return;

                if (input.tagName.toLowerCase() === 'textarea' && input.value.trim()) {
                    input.value = `${input.value.trim()} ${spokenText}`.trim();
                } else {
                    input.value = spokenText;
                }

                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            recognition.onerror = (event) => {
                button.classList.remove('mic-active');

                const error = event?.error || 'unknown';
                const message = error === 'not-allowed'
                    ? 'Microphone blocked'
                    : 'Voice input failed';

                setVoiceStatus(statusEl, message, 'error');
                activeVoiceControllers.delete(button);

                setTimeout(() => {
                    setVoiceStatus(statusEl, '', 'default');
                }, 1800);
            };

            recognition.onend = () => {
                button.classList.remove('mic-active');
                activeVoiceControllers.delete(button);

                if (input.value.trim()) {
                    setVoiceStatus(statusEl, 'Captured', 'success');

                    setTimeout(() => {
                        setVoiceStatus(statusEl, '', 'default');
                    }, 1200);
                } else {
                    setVoiceStatus(statusEl, '', 'default');
                }
            };

            activeVoiceControllers.set(button, {
                recognition,
                input,
                statusEl,
            });

            recognition.start();
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initGlobalVoiceInputs();
});

window.initGlobalVoiceInputs = initGlobalVoiceInputs;

document.addEventListener('DOMContentLoaded', initDashboardLogsViewToggle);
window.initDashboardLogsViewToggle = initDashboardLogsViewToggle;