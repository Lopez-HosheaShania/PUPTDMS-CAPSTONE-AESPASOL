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

function decorateFlatpickrDays(instance) {
    if (!instance?.calendarContainer) return;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    instance.calendarContainer.querySelectorAll('.flatpickr-day').forEach((dayElem) => {
        if (!dayElem.dateObj) return;

        const dayDate = new Date(dayElem.dateObj);
        dayDate.setHours(0, 0, 0, 0);

        if (dayDate > today) {
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

    const dateInputs = document.querySelectorAll('.js-flatpickr-date, .js-flatpickr-date-max-today, .js-flatpickr-date-range-from, .js-flatpickr-date-range-to');

    dateInputs.forEach(el => {
        let options = { ...baseOptions };

        const parentDialog = el.closest('dialog');
        if (parentDialog) {
            options.appendTo = parentDialog;
        } else {
            options.appendTo = document.body;
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

    if (!day || !day.dateObj) {
        tooltip.classList.remove('show');
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const dayDate = new Date(day.dateObj);
    dayDate.setHours(0, 0, 0, 0);

    if (dayDate <= today) {
        tooltip.classList.remove('show');
        return;
    }

    tooltip.textContent = "You can't select future date";
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
    const rootStyle = getComputedStyle(document.documentElement);
    const bodyStyle = getComputedStyle(document.body);

    const isMobile = window.matchMedia('(max-width: 480px)').matches;
    const isPatient = document.body.classList.contains('role-patient');

    const nav = document.querySelector(
        '.patient-mobile-nav, .mobile-bottom-nav, .bottom-nav, nav[class*="mobile"]'
    );

    let navHeight = 0;

    if (isPatient && isMobile && nav) {
        navHeight = Math.ceil(nav.getBoundingClientRect().height);
    }

    if (isPatient && isMobile && navHeight < 70) {
        navHeight = 92;
    }

    const right = isMobile ? 18 : 22;
    const accessibilityBottom = isPatient && isMobile ? navHeight + 16 : 24;
    const backTopBottom = accessibilityBottom + 56 + 14;

    document.documentElement.style.setProperty('--float-right', `${right}px`);
    document.documentElement.style.setProperty('--patient-nav-height', `${navHeight}px`);
    document.documentElement.style.setProperty('--accessibility-bottom', `${accessibilityBottom}px`);
    document.documentElement.style.setProperty('--back-top-bottom', `${backTopBottom}px`);

    document.querySelectorAll('.asw-widget, .asw-menu-btn').forEach((el) => {
        el.style.setProperty('--asw-off-x', `${right}px`);
        el.style.setProperty('--asw-off-y', `${accessibilityBottom}px`);
        el.style.setProperty('--asw-right', `${right}px`);
        el.style.setProperty('--asw-bottom', `${accessibilityBottom}px`);
        el.style.right = `${right}px`;
        el.style.bottom = `${accessibilityBottom}px`;
    });
}

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

function showToast(optionsOrType = 'success', messageStr = '', durationNum = 4000) {
    let type = 'success', message = '', title = '', duration = durationNum;

    if (typeof optionsOrType === 'object') {
        type = optionsOrType.type || 'success';
        message = optionsOrType.message || '';
        title = optionsOrType.title || (type.charAt(0).toUpperCase() + type.slice(1));
        duration = optionsOrType.duration || 4000;
    } else {
        type = optionsOrType;
        message = messageStr;
        title = type.charAt(0).toUpperCase() + type.slice(1);
    }

    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-item toast-${type}`;

    const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-exclamation',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };

    toast.innerHTML = `
        <div class="toast-icon-wrap"><i class="fa-solid ${icons[type] || icons.info}"></i></div>
        <div>
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="dismissToast(this.parentElement)"><i class="fa-solid fa-xmark"></i></button>
        <div class="toast-progress" style="animation-duration: ${duration}ms;"></div>
    `;

    container.appendChild(toast);
    setTimeout(() => dismissToast(toast), duration);
}

function dismissToast(toast) {
    if (!toast || toast.classList.contains('toast-exit')) return;
    toast.classList.add('toast-exit');
    setTimeout(() => toast.remove(), 350);
}

window.showToast = showToast;

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
    if (event.target && event.target.id === id) {
        closeModal(id);
    }
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

/* Temporary compatibility for Inventory */
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

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

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