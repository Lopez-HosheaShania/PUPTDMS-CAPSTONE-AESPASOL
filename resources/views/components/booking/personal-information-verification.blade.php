<div id="personalInformationVerificationModal" class="ui-modal" role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="personalInformationVerificationTitle" aria-describedby="personalInformationVerificationMessage">
    <div class="ui-modal-card modal-md">

        <header class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon" aria-hidden="true">
                    <i class="fa-solid fa-user-pen"></i>
                </div>

                <div class="modal-copy">
                    <h2 id="personalInformationVerificationTitle" class="modal-title" tabindex="-1"
                        data-modal-initial-focus>
                        Unable to Verify Information
                    </h2>

                    <p class="modal-subtitle">
                        Appointment booking
                    </p>
                </div>
            </div>
        </header>

        <div class="modal-bd modal-form-grid">

            <div class="global-confirm-alert">
                <p id="personalInformationVerificationMessage">
                    We could not verify your Personal Information from GUISIS.
                </p>
            </div>

            <section class="modal-form-section" aria-labelledby="personalInformationVerificationActions">
                <div class="modal-section-heading">
                    <h3 id="personalInformationVerificationActions" class="text-sm font-bold">
                        What you can do
                    </h3>
                </div>

                <ol class="m-0 list-none space-y-3 p-0">

                    <li class="flex items-start gap-3">
                        <div class="modal-section-icon" aria-hidden="true">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </div>

                        <p id="personalInformationVerificationStep"
                            class="m-0 pt-2 text-sm font-medium leading-relaxed">
                            Please complete your personal information in GUISIS.
                        </p>
                    </li>

                    <li class="flex items-start gap-3">
                        <div class="modal-section-icon" aria-hidden="true">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>

                        <p id="personalInformationVerificationReturn"
                            class="m-0 pt-2 text-sm font-medium leading-relaxed">
                            Return here and try booking again.
                        </p>
                    </li>

                </ol>
            </section>

            <p id="personalInformationVerificationExternalNote" class="modal-helper-text">
                GUISIS opens in a new tab so you can keep this page open.
            </p>

        </div>

        <footer class="modal-ft">

            <button id="personalInformationVerificationClose" type="button" class="btn-close-modal"
                data-modal-close="personalInformationVerificationModal">
                Close
            </button>

            <a id="personalInformationVerificationGuisis" class="modal-btn-primary"
                href="https://www.guisis.dllbsit2027.com/" target="_blank" rel="noopener noreferrer"
                aria-describedby="personalInformationVerificationExternalNote">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>

                <span>Go to GUISIS</span>
            </a>

        </footer>

    </div>
</div>

<script>
    (() => {
        let verifying = false;

        const modalId = 'personalInformationVerificationModal';

        const verificationError = {
            title: 'Unable to Verify Information',
            message: 'We could not verify your Personal Information from GUISIS.'
        };

        function showFailure(failure = verificationError) {
            const incomplete =
                failure?.code === 'personal_information_incomplete';

            const title = document.getElementById(
                'personalInformationVerificationTitle'
            );

            const message = document.getElementById(
                'personalInformationVerificationMessage'
            );

            const step = document.getElementById(
                'personalInformationVerificationStep'
            );

            const returnStep = document.getElementById(
                'personalInformationVerificationReturn'
            );

            title.textContent = incomplete ?
                'Incomplete information' :
                (failure?.title || verificationError.title);

            message.textContent = incomplete ?
                "Your personal information in GUISIS is not yet complete, so we can't book your appointment. Complete your details in GUISIS, then come back to continue." :
                (failure?.message || verificationError.message);

            step.textContent =
                'Please complete your personal information in GUISIS.';

            returnStep.textContent = incomplete ?
                'Return here and book your appointment' :
                'Return here and try booking again.';

            window.openModal(modalId);
        }

        window.verifyPersonalInformationBeforeBooking = async (
            url,
            trigger = null
        ) => {
            if (verifying) return;

            verifying = true;
            trigger?.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(
                    @json(route('book.appointment.verify-personal-information')), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: AbortSignal.timeout(60000)
                    }
                );

                const result = await response.json();

                if (response.ok && result.verified === true) {
                    if (typeof url === 'function') {
                        url();
                    } else {
                        window.location.assign(url);
                    }

                    return;
                }

                showFailure(
                    [
                        'personal_information_incomplete',
                        'personal_information_unverified'
                    ].includes(result.code) ?
                    result :
                    verificationError
                );

            } catch (error) {
                showFailure(verificationError);

            } finally {
                verifying = false;
                trigger?.removeAttribute('aria-busy');
            }
        };

        document.addEventListener('click', (event) => {
            if (
                event.defaultPrevented ||
                event.button !== 0 ||
                event.ctrlKey ||
                event.metaKey ||
                event.shiftKey ||
                event.altKey
            ) return;

            const link = event.target.closest('a[href]');

            if (
                !link ||
                link.getAttribute('aria-disabled') === 'true' ||
                link.hasAttribute('download') ||
                (link.target && link.target !== '_self')
            ) return;

            const target = new URL(
                link.href,
                window.location.origin
            );

            if (
                target.origin !== window.location.origin ||
                !
                /^\/(?:book-appointment(?:\/reserved\/[^/]+)?|patient\/(?:book-appointment|appointment\/create))\/?$/
                .test(target.pathname)
            ) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            window.verifyPersonalInformationBeforeBooking(
                target.href,
                link
            );

        }, true);

        const failure = @json(session('booking_verification_error'));

        if (failure) {
            window.addEventListener(
                'load',
                () => showFailure(failure), {
                    once: true
                }
            );
        }
    })();
</script>
