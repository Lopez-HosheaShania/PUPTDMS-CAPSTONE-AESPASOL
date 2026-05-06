import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

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
        appendTo: document.body,

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

    flatpickr(".js-flatpickr-date", baseOptions);

    flatpickr(".js-flatpickr-date-max-today", {
        ...baseOptions,
        maxDate: "today",
    });

    flatpickr(".js-flatpickr-date-range-from", {
        ...baseOptions,
        maxDate: "today",
    });

    flatpickr(".js-flatpickr-date-range-to", {
        ...baseOptions,
        maxDate: "today",
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
    button.className = 'back-to-top grace-floating-btn';
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