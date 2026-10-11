<div id="personalInformationVerificationModal" class="ui-modal personal-information-modal" role="dialog"
    aria-modal="true" aria-hidden="true" aria-labelledby="personalInformationVerificationTitle"
    aria-describedby="personalInformationVerificationMessage">
    <div class="ui-modal-card modal-lg">
        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon"><i class="fa-solid fa-user-pen" aria-hidden="true"></i></div>
                <div class="modal-copy">
                    <span class="personal-information-eyebrow patient-doc-eyebrow">Appointment booking</span>
                    <h2 id="personalInformationVerificationTitle" class="modal-title patient-doc-title"></h2>
                </div>
            </div>
            <button type="button" class="modal-x" data-modal-close="personalInformationVerificationModal"
                aria-label="Close personal information notice">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-bd">
            <div class="personal-information-message">
                <p id="personalInformationVerificationMessage" class="patient-doc-help"></p>
            </div>
            <div class="personal-information-section-title patient-doc-label" role="heading" aria-level="3">What you can do</div>
            <ol class="personal-information-steps">
                <li>
                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                    <span id="personalInformationVerificationStep">Please complete your personal information in GUISIS.</span>
                </li>
                <li>
                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                    <span id="personalInformationVerificationReturn">Return here and book your appointment</span>
                </li>
            </ol>
            <p class="personal-information-note">
                GUISIS opens in a new tab so you can keep this page open.
            </p>
        </div>
        <div class="modal-ft">
            <button id="personalInformationVerificationClose" type="button" class="ui-btn ui-btn-secondary"
                data-modal-close="personalInformationVerificationModal">Close</button>
            <a id="personalInformationVerificationGuisis" class="ui-btn ui-btn-primary"
                href="https://www.guisis.dllbsit2027.com/" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Go to GUISIS
            </a>
        </div>
    </div>
</div>

<script>
    (() => {
        let verifying = false;
        const verificationError = {
            title: 'Unable to Verify Information',
            message: 'We could not verify your Personal Information from GUISIS. Please try again later.'
        };
        function showFailure(failure) {
            const incomplete = failure.code === 'personal_information_incomplete';
            document.getElementById('personalInformationVerificationTitle').textContent = incomplete
                ? 'Incomplete information' : failure.title;
            document.getElementById('personalInformationVerificationMessage').textContent = incomplete
                ? "Your personal information in GUISIS is not yet complete, so we can't book your appointment. Complete your details in GUISIS, then come back to continue."
                : failure.message;
            document.getElementById('personalInformationVerificationStep').textContent =
                'Please complete your personal information in GUISIS.';
            document.getElementById('personalInformationVerificationReturn').textContent = incomplete
                ? 'Return here and book your appointment' : 'Return here and try booking again';
            const closeButton = document.getElementById('personalInformationVerificationClose');
            closeButton.textContent = 'Close';
            window.openModal('personalInformationVerificationModal');
            closeButton.focus();
        }

        window.verifyPersonalInformationBeforeBooking = async (url, trigger = null) => {
            if (verifying) return;
            verifying = true;
            trigger?.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(@json(route('book.appointment.verify-personal-information')), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: AbortSignal.timeout(60000)
                });
                const result = await response.json();
                if (response.ok && result.verified === true) {
                    if (typeof url === 'function') {
                        url();
                    } else {
                        window.location.assign(url);
                    }
                    return;
                }
                showFailure(['personal_information_incomplete', 'personal_information_unverified'].includes(result.code)
                    ? result : verificationError);
            } catch (error) {
                showFailure(verificationError);
            } finally {
                verifying = false;
                trigger?.removeAttribute('aria-busy');
            }
        };

        // Cover booking links in the dashboard, calendar, mobile navigation, and chatbot.
        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');
            if (!link || link.getAttribute('aria-disabled') === 'true') return;
            const target = new URL(link.href, window.location.origin);
            if (target.origin !== window.location.origin ||
                !/^\/(?:book-appointment(?:\/reserved\/[^/]+)?|patient\/(?:book-appointment|appointment\/create))\/?$/.test(target.pathname)) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            window.verifyPersonalInformationBeforeBooking(target.href, link);
        }, true);

        const failure = @json(session('booking_verification_error'));
        if (failure) {
            window.addEventListener('load', () => showFailure(failure));
        }
    })();
</script>
