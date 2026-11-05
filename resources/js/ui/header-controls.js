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

        dateIconEl.classList.remove(
            'date-icon-morning',
            'date-icon-afternoon',
            'date-icon-night'
        );

        if (hourInManila >= 5 && hourInManila < 12) {
            dateIconEl.className =
                'fa-solid fa-sun date-icon-morning';
        } else if (
            hourInManila >= 12 &&
            hourInManila < 18
        ) {
            dateIconEl.className =
                'fa-solid fa-sun date-icon-afternoon';
        } else {
            dateIconEl.className =
                'fa-solid fa-moon date-icon-night';
        }
    }

    updateDateTime();
    setInterval(updateDateTime, 60000);
}

function initPatientMobileFab() {
    const mobFab = document.getElementById('mobFab');
    const mobFabMenu = document.getElementById('mobFabMenu');

    if (!mobFab || !mobFabMenu) return;

    mobFab.addEventListener('click', event => {
        event.stopPropagation();

        window.closeHeaderMenus?.();

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

function initHeaderControls() {
    initGlobalDateTime();
    initPatientMobileFab();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initHeaderControls,
        { once: true }
    );
} else {
    initHeaderControls();
}

window.initGlobalDateTime = initGlobalDateTime;
window.initHeaderControls = initHeaderControls;