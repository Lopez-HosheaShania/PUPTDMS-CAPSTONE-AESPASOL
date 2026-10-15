<div id="personalInformationVerificationModal" class="ui-modal" role="dialog"
    aria-modal="true" aria-hidden="true" aria-labelledby="personalInformationVerificationTitle"
    aria-describedby="personalInformationVerificationMessage">
    <div class="ui-modal-card modal-md">
        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon"><i class="fa-solid fa-user-pen" aria-hidden="true"></i></div>
                <div class="modal-copy">
                    <h2 id="personalInformationVerificationTitle" class="modal-title"></h2>
                    <p class="modal-subtitle">Appointment booking</p>
                </div>
            </div>
        </div>
        <div class="modal-bd modal-form-grid">
            <div class="global-confirm-alert">
                <p id="personalInformationVerificationMessage"></p>
            </div>
            <section class="modal-form-section" aria-labelledby="personalInformationVerificationActions">
                <div class="modal-section-heading">
                    <h4 id="personalInformationVerificationActions">What you can do</h4>
                </div>
                <ol>
                    <li class="modal-section-heading">
                        <div class="modal-section-icon"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></div>
                        <div><p id="personalInformationVerificationStep">Please complete your personal information in GUISIS.</p></div>
                    </li>
                    <li class="modal-section-heading">
                        <div class="modal-section-icon"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></div>
                        <div><p id="personalInformationVerificationReturn">Return here and book your appointment</p></div>
                    </li>
                </ol>
            </section>
            <p class="modal-helper-text">
                GUISIS opens in a new tab so you can keep this page open.
            </p>
        </div>
        <div class="modal-ft">
            <button id="personalInformationVerificationClose" type="button" class="btn-close-modal"
                data-modal-close="personalInformationVerificationModal">Close</button>
            <a id="personalInformationVerificationGuisis" class="modal-btn-primary"
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
            message: 'We could not verify your Personal Information from GUISIS.'
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
