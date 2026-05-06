export function swapSkeletonContent(targetId, html, options = {}) {
    const el = document.getElementById(targetId);
    if (!el) return;

    const leaveDuration = options.leaveDuration ?? 140;
    const revealClass = options.revealClass ?? 'content-reveal';

    el.classList.add('skeleton-fade-leave');
    el.style.pointerEvents = 'none';

    setTimeout(() => {
        el.innerHTML = html;
        el.classList.remove('skeleton-fade-leave');

        void el.offsetWidth;

        el.classList.add(revealClass);
        el.style.pointerEvents = '';

        setTimeout(() => {
            el.classList.remove(revealClass);
        }, 460);
    }, leaveDuration);
}

export function setDashboardLoadingStatus(label, percent) {
    const text = document.getElementById('dashboardLoadingText');
    const pct = document.getElementById('dashboardLoadingPercent');
    const bar = document.getElementById('dashboardLoadingBar');

    if (text && label) {
        text.innerHTML =
            '<span class="w-2 h-2 rounded-full bg-[#8B0000] dashboard-loading-dot"></span>' +
            label;
    }

    if (pct && typeof percent !== 'undefined') {
        pct.textContent = percent + '%';
    }

    if (bar && typeof percent !== 'undefined') {
        bar.style.width = percent + '%';
    }
}

export function finishDashboardLoading() {
    setDashboardLoadingStatus('Dashboard ready', 100);

    setTimeout(() => {
        const status = document.getElementById('dashboardLoadingStatus');
        if (status) status.classList.add('is-done');

        setTimeout(() => {
            if (status) status.remove();
        }, 380);
    }, 420);
}

export function renderWithStagger(tasks, initialDelay = 500, step = 120) {
    setTimeout(() => {
        tasks.forEach((task, index) => {
            setTimeout(() => {
                if (typeof task === 'function') task();
            }, index * step);
        });
    }, initialDelay);
}

export function runEnterpriseLoading(phases = [], options = {}) {
    const initialDelay = options.initialDelay ?? 450;
    const phaseGap = options.phaseGap ?? 260;
    const taskGap = options.taskGap ?? 120;

    let cursor = initialDelay;

    phases.forEach((phase) => {
        setTimeout(() => {
            if (phase.label) {
                const updater = window.setDashboardLoadingStatus || setDashboardLoadingStatus;
                if (typeof updater === 'function') {
                    updater(phase.label);
                }
            }

            (phase.tasks || []).forEach((task, index) => {
                setTimeout(() => {
                    if (typeof task === 'function') task();
                }, index * taskGap);
            });
        }, cursor);

        cursor += ((phase.tasks || []).length * taskGap) + phaseGap;
    });

    setTimeout(() => {
        const finisher = window.finishDashboardLoading || finishDashboardLoading;
        if (typeof finisher === 'function') {
            finisher();
        }
    }, cursor + 260);
}