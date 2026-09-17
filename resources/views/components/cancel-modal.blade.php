<div id="cancelAppointmentModal" class="ui-modal modal-theme-danger" aria-hidden="true" role="dialog" aria-modal="true"
    aria-labelledby="cancelAppointmentModalTitle" onclick="handleCancelBackdropClick(event)">

    <div class="ui-modal-card modal-md">

        <div class="modal-hd">

            <div class="modal-heading">

                <div class="modal-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div class="modal-copy">

                    <h2 id="cancelAppointmentModalTitle" class="modal-title">
                        Cancel Appointment
                    </h2>

                    <p class="modal-subtitle">
                        This action cannot be undone.
                    </p>

                </div>

            </div>

            <button type="button" onclick="closeCancelAppointmentModal()" class="modal-x"
                aria-label="Close cancellation modal">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <form id="cancelAppointmentForm" data-global-validation data-discard-form
            data-discard-title="Discard cancellation?" data-discard-subtitle="A cancellation reason has been selected."
            data-discard-message="The selected cancellation reason will be cleared. Do you want to discard this change?"
            novalidate>

            <div class="modal-bd">

                <div class="global-info-profile">

                    <div class="global-info-profile-icon">
                        <i class="fa-regular fa-user"></i>
                    </div>

                    <div class="global-info-profile-copy">

                        <span class="global-info-label">
                            Cancelling appointment for
                        </span>

                        <strong id="cancelPatientName" class="global-info-profile-name">
                            —
                        </strong>

                    </div>

                </div>

                <div class="global-info-grid">

                    <div class="global-info-item">

                        <span
                            class="
                                global-info-icon
                                status-cancelled
                            ">
                            <i class="fa-regular fa-calendar-xmark"></i>
                        </span>

                        <div class="global-info-copy">

                            <span class="global-info-label">
                                Scheduled Date
                            </span>

                            <strong id="cancelAppointmentDate" class="global-info-value">
                                —
                            </strong>

                        </div>

                    </div>

                </div>

                <div class="modal-form-section global-form-group" data-global-field>

                    <div class="global-label-row">
                        <label class="global-form-label">
                            Cancellation Reason
                        </label>
                    </div>

                    <div id="cancelReasonChips" class="flex flex-wrap gap-2" role="radiogroup"
                        aria-label="Cancellation Reason">

                        <div class="reason-chip">
                            <input type="radio" name="cancelReason" id="r1" value="Patient no-show" required
                                data-required-message="Select the reason for cancelling this appointment.">

                            <label for="r1">
                                <i class="fa-regular fa-circle-xmark"></i>
                                Patient no-show
                            </label>
                        </div>

                        <div class="reason-chip">
                            <input type="radio" name="cancelReason" id="r2" value="Doctor unavailable"
                                required>

                            <label for="r2">
                                <i class="fa-solid fa-user-doctor"></i>
                                Doctor unavailable
                            </label>
                        </div>

                        <div class="reason-chip">
                            <input type="radio" name="cancelReason" id="r3" value="Patient request" required>

                            <label for="r3">
                                <i class="fa-regular fa-hand"></i>
                                Patient request
                            </label>
                        </div>

                        <div class="reason-chip">
                            <input type="radio" name="cancelReason" id="r4" value="Emergency" required>

                            <label for="r4">
                                <i class="fa-solid fa-bolt"></i>
                                Emergency
                            </label>
                        </div>

                        <div class="reason-chip">
                            <input type="radio" name="cancelReason" id="r5" value="Rescheduled" required>

                            <label for="r5">
                                <i class="fa-solid fa-rotate"></i>
                                Rescheduled
                            </label>
                        </div>

                    </div>

                    <div class="global-field-error" data-error-for="cancelReason" aria-hidden="true">
                    </div>

                </div>

            </div>

            <div class="modal-ft">

                <button type="button" onclick="closeCancelAppointmentModal()" class="ui-btn ui-btn-secondary">
                    <span>Keep Appointment</span>
                </button>

                <button type="button" id="confirmCancelBtn" onclick="confirmCancelAppointment()"
                    class="ui-btn ui-btn-danger">
                    <span>Cancel Appointment</span>
                </button>

            </div>

        </form>

    </div>

</div>

<script>
    let selectedCancelUrl = null;
    let selectedCancelAppointmentId = null;

    function extractAppointmentIdFromCancelUrl(url) {
        const match = String(url || '').match(/appointments\/(\d+)/);
        return match ? match[1] : null;
    }

    function cancelAppointmentFromModal(url, patientName = 'this patient', appointmentDate = '—') {
        selectedCancelUrl = url;
        selectedCancelAppointmentId = extractAppointmentIdFromCancelUrl(url);
        document.getElementById('cancelPatientName').textContent = patientName;
        document.getElementById('cancelAppointmentDate').textContent = appointmentDate;
        document.querySelectorAll('input[name="cancelReason"]').forEach(r => r.checked = false);
        const confirmBtn = document.getElementById('confirmCancelBtn');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = `<span>Cancel Appointment</span>`;
        const modal = document.getElementById('cancelAppointmentModal');

        if (!modal) return;

        modal.classList.remove('closing');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');

        document.documentElement.classList.add('modal-lock');
        document.body.classList.add('modal-lock');

        requestAnimationFrame(() => {
            window.DiscardChanges?.captureModal(modal);
        });
    }

    function forceCloseCancelAppointmentModal() {
        const modal =
            document.getElementById('cancelAppointmentModal');

        if (!modal || !modal.classList.contains('open')) {
            return;
        }

        modal.classList.remove('open');
        modal.classList.add('closing');
        modal.setAttribute('aria-hidden', 'true');

        window.setTimeout(() => {
            modal.classList.remove('closing');

            document.documentElement.classList.remove('modal-lock');

            if (!document.querySelector('.ui-modal.open')) {
                document.body.classList.remove('modal-lock');
            }
        }, 170);

        selectedCancelUrl = null;
        selectedCancelAppointmentId = null;
    }

    function closeCancelAppointmentModal(options = {}) {
        const modal =
            document.getElementById('cancelAppointmentModal');

        if (!modal) return;

        if (options.force === true) {
            forceCloseCancelAppointmentModal();
            return;
        }

        if (window.DiscardChanges) {
            window.DiscardChanges.confirmClose(
                modal,
                forceCloseCancelAppointmentModal
            );

            return;
        }

        forceCloseCancelAppointmentModal();
    }

    function handleCancelBackdropClick(e) {
        if (e.target === document.getElementById('cancelAppointmentModal')) {
            closeCancelAppointmentModal();
        }
    }

    async function confirmCancelAppointment() {
        const form = document.getElementById('cancelAppointmentForm');

        const validation =
            window.validateGlobalForm?.(form);

        if (
            validation &&
            !validation.valid
        ) {
            window.focusGlobalInvalidField?.(
                validation.firstInvalid
            );

            return;
        }

        const selectedReason =
            document.querySelector(
                'input[name="cancelReason"]:checked'
            )?.value || '';

        if (!selectedCancelUrl) {
            return;
        }

        const btn = document.getElementById('confirmCancelBtn');
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i><span>Cancelling…</span>`;

        try {
            const response = await fetch(selectedCancelUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                        'content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reason: selectedReason
                })
            });

            const data = await response.json().catch(() => null);

            if (response.ok && data?.success) {
                closeCancelAppointmentModal({
                    force: true
                });

                if (typeof closeDayAppointmentsModal === 'function') {
                    closeDayAppointmentsModal();
                }

                sessionStorage.setItem('dentistToast', JSON.stringify({
                    title: 'Appointment cancelled',
                    message: `${document.getElementById('cancelPatientName')?.textContent || 'Appointment'} was cancelled successfully.`,
                    tone: 'danger',
                    duration: 3500
                }));

                window.location.reload();
            } else {
                btn.disabled = false;
                btn.innerHTML = `
        <span>Cancel Appointment</span>
    `;

                alert(data?.message ?? 'Unable to cancel the appointment. Please try again.');
            }
        } catch (error) {
            console.error('Cancel appointment failed:', error);

            btn.disabled = false;

            btn.innerHTML = `
        <span>Cancel Appointment</span>
    `;

            alert('Server error while cancelling the appointment. Please try again.');
        }
    }

    document.addEventListener('keydown', event => {
        const modal =
            document.getElementById('cancelAppointmentModal');

        if (
            event.key !== 'Escape' ||
            !modal?.classList.contains('open')
        ) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        closeCancelAppointmentModal();
    }, true);
</script>
