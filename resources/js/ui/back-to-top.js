function initBackToTop() {
    let button = document.querySelector('.back-to-top');

    if (!button) {
        button = document.createElement('button');
        button.type = 'button';
        button.className = 'back-to-top floating-btn';
        button.setAttribute('aria-label', 'Back to top');
        button.setAttribute('data-tooltip', 'Back to top');
        button.setAttribute('data-tooltip-tone', 'view');
        button.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';

        document.body.appendChild(button);
    }

    button.removeAttribute('title');
    button.setAttribute('data-tooltip', 'Back to top');
    button.setAttribute('data-tooltip-tone', 'view');

    if (button.dataset.backToTopInitialized === 'true') return;
    button.dataset.backToTopInitialized = 'true';

    const getScrollTop = () => {
        return window.scrollY ||
            document.documentElement.scrollTop ||
            document.body.scrollTop ||
            document.getElementById('mainContent')?.scrollTop ||
            0;
    };

    const scrollPageToTop = () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });

        document.documentElement.scrollTo?.({
            top: 0,
            behavior: 'smooth'
        });

        document.body.scrollTo?.({
            top: 0,
            behavior: 'smooth'
        });

        const mainContent = document.getElementById('mainContent');

        if (mainContent) {
            mainContent.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    };

    const toggleButton = () => {
        button.classList.toggle('is-visible', getScrollTop() > 350);
    };

    button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        scrollPageToTop();
    });

    window.addEventListener('scroll', toggleButton, { passive: true });
    document.getElementById('mainContent')?.addEventListener('scroll', toggleButton, { passive: true });

    toggleButton();
}

document.addEventListener('DOMContentLoaded', initBackToTop);