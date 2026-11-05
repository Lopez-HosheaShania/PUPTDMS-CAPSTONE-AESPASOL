<x-modal id="personalInformationVerificationModal" title="Unable to Verify Information"
    title-id="personalInformationVerificationTitle" description-id="personalInformationVerificationMessage"
    subtitle="Appointment booking" icon="fa-solid fa-user-pen" size="md" :show-close="true" :initial-focus-title="true"
    body-class="modal-form-grid">
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

                <p id="personalInformationVerificationStep" class="m-0 pt-2 text-xs font-medium leading-relaxed">
                    Please complete your personal information in GUISIS.
                </p>
            </li>

            <li class="flex items-start gap-3">
                <div class="modal-section-icon" aria-hidden="true">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>

                <p id="personalInformationVerificationReturn" class="m-0 pt-2 text-xs font-medium leading-relaxed">
                    Return here and try booking again.
                </p>
            </li>

        </ol>
    </section>

    <p id="personalInformationVerificationExternalNote" class="modal-helper-text">
        GUISIS opens in a new tab so you can keep this page open.
    </p>

    <x-slot:footer>

        <x-button id="personalInformationVerificationClose" type="button" variant="secondary"
            data-modal-close="personalInformationVerificationModal">
            Close
        </x-button>

        <x-button id="personalInformationVerificationContinue" type="button" variant="secondary" hidden>
            Continue Booking
        </x-button>

        <x-button id="personalInformationVerificationGuisis" href="https://www.guisis.dllbsit2027.com/"
            variant="primary" icon="fa-solid fa-arrow-up-right-from-square" target="_blank" rel="noopener noreferrer"
            aria-describedby="personalInformationVerificationExternalNote">
            Go to GUISIS
        </x-button>

        <x-button id="personalInformationVerificationFlss" href="https://flss.alquatrilixbsit2027.com/login"
            variant="primary" icon="fa-solid fa-arrow-up-right-from-square" target="_blank" rel="noopener noreferrer"
            aria-describedby="personalInformationVerificationExternalNote" hidden>
            Go to FLSS
        </x-button>

    </x-slot:footer>
</x-modal>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (() => {
        let verifying = false;
        let pendingBooking = null;

        const modalId = 'personalInformationVerificationModal';

        const verificationError = {
            title: 'Unable to Verify Information',
            message: 'We could not verify your Personal Information from GUISIS.'
        };

        function continueBooking(target) {
            if (typeof target === 'function') {
                target();
            } else {
                window.location.assign(target);
            }
        }

        document.getElementById('personalInformationVerificationContinue').addEventListener('click', () => {
            if (pendingBooking === null) return;
            const target = pendingBooking;
            pendingBooking = null;
            window.closeModal(modalId);
            continueBooking(target);
        });

        document.getElementById('personalInformationVerificationClose').addEventListener('click', () => {
            pendingBooking = null;
        });

        function showFailure(failure = verificationError, bookingTarget = null) {
            pendingBooking = null;
            if (failure?.code === 'booking_maintenance') {
                window.openModal('bookingMaintenanceModal');
                return;
            }

            const incomplete =
                failure?.code === 'personal_information_incomplete';
            const canContinue = failure?.can_continue === true && bookingTarget !== null;
            pendingBooking = canContinue ? bookingTarget : null;

            document.getElementById('personalInformationVerificationContinue').hidden = !canContinue;
            document.getElementById('personalInformationVerificationClose').hidden = canContinue;
            document.getElementById('personalInformationVerificationGuisis').hidden = canContinue;
            document.getElementById('personalInformationVerificationFlss').hidden = !canContinue;
            document.getElementById('personalInformationVerificationExternalNote').textContent = canContinue ?
                'FLSS opens in a new tab so you can keep this page open.' :
                'GUISIS opens in a new tab so you can keep this page open.';

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

            step.textContent = canContinue ?
                'Please complete your missing information in FLSS' :
                'Please complete your personal information in GUISIS.';

            returnStep.textContent = canContinue ?
                'Select Continue Booking to proceed with your appointment.' : incomplete ?
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
                    // The reminder belongs to booking entry. A verified final
                    // submission must continue even when the profile is incomplete.
                    if (result.warning?.can_continue === true && typeof url !== 'function') {
                        showFailure(result.warning, url);
                        return;
                    }
                    continueBooking(url);

                    return;
                }

                showFailure(
                    [
                        'booking_maintenance',
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
