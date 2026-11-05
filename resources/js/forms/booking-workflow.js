(() => {
    if (window.BookingWorkflow) {
        return;
    }

    function resolveElement(value) {
        if (!value) {
            return null;
        }

        if (value instanceof Element) {
            return value;
        }

        if (typeof value === 'string') {
            return document.querySelector(value);
        }

        return null;
    }

    function resolvePanels(value) {
        if (!value) {
            return [];
        }

        if (typeof value === 'string') {
            return Array.from(
                document.querySelectorAll(value)
            );
        }

        if (
            value instanceof NodeList ||
            Array.isArray(value)
        ) {
            return Array.from(value);
        }

        return [];
    }

    function create(config = {}) {
        const panels =
            resolvePanels(
                config.panels ||
                '.step-content'
            );

        if (!panels.length) {
            return null;
        }

        const totalSteps =
            panels.length;

        const progressFill =
            resolveElement(
                config.progressFill ||
                '#headerProgressFill'
            );

        const progressPercent =
            resolveElement(
                config.progressPercent ||
                '#headerProgressPercent'
            );

        const counter =
            resolveElement(
                config.counter ||
                '#stepCounterText'
            );

        const navContainer =
            resolveElement(
                config.navContainer
            );

        const previousButton =
            resolveElement(
                config.previousButton
            );

        const nextButton =
            resolveElement(
                config.nextButton
            );

        const submitButton =
            resolveElement(
                config.submitButton
            );

        const defaultNextButtonHtml =
            nextButton?.innerHTML || '';

        const completedSteps =
            new Set();

        let currentStep =
            Math.max(
                0,
                Math.min(
                    Number(
                        config.initialStep ?? 0
                    ),
                    totalSteps - 1
                )
            );

        function getStepNumber(index) {
            return index + 1;
        }

        function syncStepper() {
            const progress =
                (
                    (
                        currentStep + 1
                    ) /
                    totalSteps
                ) * 100;

            if (progressFill) {
                progressFill.style.width =
                    `${progress}%`;

                progressFill.classList.toggle(
                    'is-complete',
                    currentStep === totalSteps - 1
                );
            }

            if (progressPercent) {
                progressPercent.textContent =
                    `${Math.round(progress)}%`;
            }

            if (counter) {
                counter.textContent =
                    String(
                        currentStep + 1
                    );
            }

            for (
                let index = 0;
                index < totalSteps;
                index++
            ) {
                const stepNumber =
                    getStepNumber(index);

                const circle =
                    document.getElementById(
                        `sc${stepNumber}`
                    );

                const label =
                    document.getElementById(
                        `sl${stepNumber}`
                    );

                const connector =
                    document.getElementById(
                        `conn${stepNumber}`
                    );

                const isActive =
                    index === currentStep;

                const isComplete =
                    index < currentStep &&
                    completedSteps.has(index);

                circle?.classList.toggle(
                    'is-active',
                    isActive
                );

                circle?.classList.toggle(
                    'is-complete',
                    isComplete
                );

                label?.classList.toggle(
                    'is-active',
                    isActive
                );

                label?.classList.toggle(
                    'is-complete',
                    isComplete
                );

                connector?.classList.toggle(
                    'is-complete',
                    index < currentStep &&
                    completedSteps.has(index)
                );

                if (circle) {
                    circle.innerHTML =
                        isComplete
                            ? '<i class="fa-solid fa-check" aria-hidden="true"></i>'
                            : String(stepNumber);
                }
            }
        }

        function syncPanels() {
            panels.forEach(
                (panel, index) => {
                    const isActive =
                        index ===
                        currentStep;

                    panel.classList.toggle(
                        'show',
                        isActive
                    );

                    panel.classList.toggle(
                        'hidden',
                        !isActive
                    );

                    panel.setAttribute(
                        'aria-hidden',
                        isActive
                            ? 'false'
                            : 'true'
                    );
                }
            );
        }

        function syncNavigation() {
            const isFirst =
                currentStep === 0;

            const isLast =
                currentStep ===
                totalSteps - 1;

            const hasLastStepAction =
                typeof config.onLastStepNext ===
                'function';

            if (previousButton) {
                previousButton.classList.toggle(
                    'hidden',
                    isFirst
                );

                previousButton.disabled =
                    false;

                previousButton.setAttribute(
                    'aria-disabled',
                    'false'
                );
            }

            if (nextButton) {
                nextButton.classList.toggle(
                    'hidden',
                    isLast &&
                    !hasLastStepAction
                );
            }

            if (submitButton) {
                submitButton.classList.toggle(
                    'hidden',
                    !isLast
                );
            }

            if (
                navContainer &&
                config.hideNavigationOnLast
            ) {
                navContainer.classList.toggle(
                    'hidden',
                    isLast &&
                    !hasLastStepAction
                );
            }
        }

        function setNextButton({
            label = 'Next',
            icon = 'fa-chevron-right',
            iconPosition = 'right',
        } = {}) {
            if (!nextButton) {
                return api;
            }

            const iconHtml =
                icon
                    ? `<i class="fa-solid ${icon}"></i>`
                    : '';

            nextButton.innerHTML =
                iconPosition === 'left'
                    ? `
                ${iconHtml}
                <span>${label}</span>
            `
                    : `
                <span>${label}</span>
                ${iconHtml}
            `;

            return api;
        }


        function resetNextButton() {
            if (
                nextButton &&
                defaultNextButtonHtml
            ) {
                nextButton.innerHTML =
                    defaultNextButtonHtml;
            }

            return api;
        }

        function runPageCallbacks() {
            if (
                currentStep ===
                totalSteps - 1
            ) {
                config.onLastStep?.(
                    api
                );
            }

            config.onStepChange?.(
                currentStep,
                api
            );
        }

        function scrollToWorkflowTop() {
            if (
                config.scrollToTop ===
                false
            ) {
                return;
            }

            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        }

        function render(options = {}) {
            syncPanels();
            syncNavigation();
            syncStepper();
            runPageCallbacks();

            if (
                options.scroll !==
                false
            ) {
                scrollToWorkflowTop();
            }

            return api;
        }

        function markComplete(index) {
            if (
                index >= 0 &&
                index < totalSteps
            ) {
                completedSteps.add(
                    index
                );
            }

            return api;
        }

        function markIncomplete(index) {
            completedSteps.delete(
                index
            );

            return api;
        }

        function goTo(index, options = {}) {
            const target =
                Math.max(
                    0,
                    Math.min(
                        Number(index),
                        totalSteps - 1
                    )
                );

            currentStep =
                target;

            return render(
                options
            );
        }

        function next() {
            const isLast =
                currentStep ===
                totalSteps - 1;

            if (
                isLast &&
                typeof config.onLastStepNext ===
                'function'
            ) {
                const result =
                    config.onLastStepNext(
                        api
                    );

                return result !== false;
            }

            const canContinue =
                config.beforeNext
                    ? config.beforeNext(
                        currentStep,
                        api
                    )
                    : true;

            if (
                canContinue ===
                false
            ) {
                return false;
            }

            markComplete(
                currentStep
            );

            goTo(
                currentStep + 1
            );

            return true;
        }

        function previous() {
            if (
                typeof config.beforePrevious ===
                'function'
            ) {
                const canGoBack =
                    config.beforePrevious(
                        currentStep,
                        api
                    );

                if (
                    canGoBack ===
                    false
                ) {
                    return false;
                }
            }

            if (currentStep <= 0) {
                return false;
            }

            goTo(
                currentStep - 1
            );

            return true;
        }

        function getCurrentStep() {
            return currentStep;
        }

        function getCompletedSteps() {
            return Array.from(
                completedSteps
            );
        }

        const api = {
            render,
            next,
            previous,
            goTo,
            setNextButton,
            resetNextButton,
            markComplete,
            markIncomplete,
            getCurrentStep,
            getCompletedSteps,

            getPanels: () =>
                panels,

            getTotalSteps: () =>
                totalSteps,
        };

        nextButton?.addEventListener(
            'click',
            next
        );

        previousButton?.addEventListener(
            'click',
            previous
        );

        render({
            scroll: false,
        });

        return api;
    }

    window.BookingWorkflow = {
        create,
    };
})();