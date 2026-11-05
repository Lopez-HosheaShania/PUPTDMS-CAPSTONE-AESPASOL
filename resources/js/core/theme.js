function applyGlobalTheme(
    theme = 'light',
    options = {}
) {
    const html =
        document.documentElement;

    const animate =
        options.animate !== false;

    if (animate) {
        html.classList.add(
            'theme-transitioning'
        );

        window.clearTimeout(
            window.__themeTransitionTimer
        );

        window.__themeTransitionTimer =
            window.setTimeout(
                () => {
                    html.classList.remove(
                        'theme-transitioning'
                    );
                },
                320
            );
    } else {
        window.clearTimeout(
            window.__themeTransitionTimer
        );

        html.classList.remove(
            'theme-transitioning'
        );
    }

    const nextTheme =
        theme === 'dark'
            ? 'dark'
            : 'light';

    const isDark =
        nextTheme === 'dark';

    const root =
        document.documentElement;

    root.setAttribute(
        'data-theme',
        nextTheme
    );

    root.classList.toggle(
        'dark',
        isDark
    );

    root.style.colorScheme =
        isDark
            ? 'dark'
            : 'light';

    root.style.backgroundColor =
        isDark
            ? '#101111'
            : '#F4F4F4';

    localStorage.setItem(
        'theme',
        nextTheme
    );

    document
        .querySelectorAll(
            '.theme-option[data-theme-choice]'
        )
        .forEach(option => {
            option.classList.toggle(
                'active',
                option.dataset.themeChoice ===
                nextTheme
            );

            option.setAttribute(
                'aria-pressed',
                option.dataset.themeChoice ===
                    nextTheme
                    ? 'true'
                    : 'false'
            );
        });

    document
        .querySelectorAll(
            '.theme-indicator'
        )
        .forEach(indicator => {
            indicator.classList.toggle(
                'dark-mode',
                isDark
            );
        });

    document
        .querySelectorAll(
            '#themeSwitchCheckbox'
        )
        .forEach(checkbox => {
            checkbox.checked =
                isDark;
        });

    document
        .querySelectorAll(
            '#themeIcon, [data-global-theme-icon]'
        )
        .forEach(icon => {
            icon.className = isDark
                ? 'fa-solid fa-sun'
                : 'fa-solid fa-moon';
        });

    document
        .querySelectorAll('[data-global-theme-toggle]')
        .forEach(button => {
            button.setAttribute(
                'aria-label',
                isDark
                    ? 'Switch to light mode'
                    : 'Switch to dark mode'
            );

            button.setAttribute(
                'data-tooltip',
                isDark
                    ? 'Switch to light mode'
                    : 'Switch to dark mode'
            );

            button.setAttribute(
                'aria-pressed',
                isDark
                    ? 'true'
                    : 'false'
            );
        });

    document
        .querySelectorAll(
            '[data-sidebar-theme-icon]'
        )
        .forEach(icon => {
            icon.className =
                isDark
                    ? 'fa-regular fa-moon'
                    : 'fa-solid fa-sun';
        });

    window.dispatchEvent(
        new CustomEvent(
            'global-theme-change',
            {
                detail: {
                    theme: nextTheme,
                    isDark
                }
            }
        )
    );
}

function initGlobalThemeControls(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    const savedTheme = localStorage.getItem('theme') || 'light';

    applyGlobalTheme(savedTheme, { animate: false });

    scope.querySelectorAll('.theme-option[data-theme-choice]').forEach(option => {
        if (option.dataset.themeInitialized === 'true') return;

        option.dataset.themeInitialized = 'true';

        option.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            applyGlobalTheme(option.dataset.themeChoice || 'light');
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
            event.preventDefault();
            event.stopPropagation();

            const clickedCheckbox = event.target.closest('#themeSwitchCheckbox');

            if (clickedCheckbox) {
                return;
            }

            const checkbox = item.querySelector('#themeSwitchCheckbox');

            if (!checkbox) return;

            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        });
    });

    scope
        .querySelectorAll('[data-global-theme-toggle]')
        .forEach(button => {
            if (
                button.dataset.themeInitialized ===
                'true'
            ) {
                return;
            }

            button.dataset.themeInitialized =
                'true';

            button.addEventListener(
                'click',
                event => {
                    event.preventDefault();
                    event.stopPropagation();

                    const currentTheme =
                        document.documentElement
                            .getAttribute('data-theme') ||
                        'light';

                    applyGlobalTheme(
                        currentTheme === 'dark'
                            ? 'light'
                            : 'dark'
                    );
                }
            );
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

        dropdown.querySelectorAll('[data-theme-choice]').forEach(option => {
            option.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                applyGlobalTheme(option.dataset.themeChoice || 'light');
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

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initGlobalThemeControls();
        initSidebarThemeDropdowns();
    }
);

window.addEventListener('storage', event => {
    if (event.key === 'theme') {
        applyGlobalTheme(event.newValue || 'light');
    }
});

window.applyTheme = applyGlobalTheme;
window.initGlobalThemeControls = initGlobalThemeControls;
window.initSidebarThemeDropdowns = initSidebarThemeDropdowns;