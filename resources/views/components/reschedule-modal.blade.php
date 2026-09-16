<div id="rescheduleModal" class="ui-modal modal-theme-warning" aria-hidden="true" role="dialog" aria-modal="true"
    aria-labelledby="rescheduleModalTitle" onclick="handleRescheduleBackdropClick(event)">
    <div class="ui-modal-card modal-xl modal-split-card">

        <div class="modal-hd">

            <div class="modal-heading">

                <div class="modal-icon">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>

                <div class="modal-copy">

                    <h2 id="rescheduleModalTitle" class="modal-title">
                        Reschedule Appointment
                    </h2>

                    <p class="modal-subtitle">
                        Choose a new date and time, then save the changes.
                    </p>

                </div>

            </div>

            <button type="button" class="modal-x" data-discard-close="rescheduleModal"
                aria-label="Close reschedule modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="rescheduleForm" method="POST" class="modal-card-form" data-discard-form
            data-discard-title="Discard reschedule changes?"
            data-discard-subtitle="The selected schedule has not been saved."
            data-discard-message="Your selected date, time, and reason will be removed. Do you want to discard these changes?">
            @csrf
            @method('PUT')

            <input type="hidden" name="service_type" value="">
            <input type="hidden" id="new_appointment_date" name="new_appointment_date" required>
            <input type="hidden" id="new_appointment_time" name="new_appointment_time" required>

            <div class="modal-bd modal-scroll-body">
                <div class="modal-profile-card">
                    <div class="modal-profile-main">
                        <div class="modal-profile-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div class="modal-profile-main-copy">
                            <span class="modal-profile-eyebrow">
                                Appointment for
                            </span>

                            <strong id="resPatientName" class="modal-profile-name">
                                —
                            </strong>
                        </div>
                    </div>

                    <div class="modal-profile-details">
                        <div class="modal-profile-detail">
                            <span class="modal-profile-detail-icon">
                                <i class="fa-regular fa-clock"></i>
                            </span>

                            <div>
                                <span class="modal-profile-label">
                                    Current Schedule
                                </span>

                                <strong id="resCurrentSchedule" class="modal-profile-value">
                                    —
                                </strong>
                            </div>
                        </div>

                        <div class="modal-profile-detail">
                            <span class="modal-profile-detail-icon">
                                <i class="fa-solid fa-tooth"></i>
                            </span>

                            <div>
                                <span class="modal-profile-label">
                                    Service Type
                                </span>

                                <strong id="resServiceType" class="modal-profile-value">
                                    —
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-section-heading">
                    <span class="modal-section-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </span>

                    <div>
                        <h4>New Date &amp; Time</h4>
                    </div>
                </div>

                <div class="modal-form-grid-2 mb-2 sm:mb-3">

                    <div class="cal-wrap modal-calendar-panel calendar-shell-no-card" data-global-field
                        data-global-linked-input="#new_appointment_date" data-global-error-key="reschedule-date">
                        <div id="calGridWrapReschedule"></div>
                    </div>

                    <div class="slots-wrap time-panel appointment-time-panel" data-global-field
                        data-global-linked-input="#new_appointment_time" data-global-error-key="reschedule-time">

                        <div class="appointment-time-heading">
                            <span class="appointment-time-heading-icon">
                                <i class="fa-regular fa-clock"></i>
                            </span>

                            <div>
                                <h4>Pick a Time Slot</h4>

                                <p>
                                    Choose a new appointment time for the selected date.
                                </p>
                            </div>
                        </div>

                        <div id="dateBanner" class="hidden appointment-slot-date-banner">
                        </div>

                        <div id="slotPlaceholder" class="appointment-slot-placeholder">

                            <div class="empty-icon">
                                <i class="fa-regular fa-calendar"></i>
                            </div>

                            <p class="empty-title">
                                Choose a date
                            </p>

                            <p class="empty-subtitle">
                                Select an available day to see time slots.
                            </p>

                        </div>

                        <div id="slotContainer" class="hidden">

                            <div id="slotGrid" class="appointment-slot-grid">
                            </div>

                            <button type="button" id="clearSlotSelectionBtn"
                                class="
            ui-btn
            ui-btn-secondary
            ui-btn-sm
            hidden
            mt-4
            mb-2
            w-full
        "
                                onclick="clearRescheduleSlotSelection()">
                                Clear selection
                            </button>

                            <div id="selectedSlotDisplay" class="hidden appointment-selected-slot">

                                <i class="fa-solid fa-circle-check"></i>

                                Selected:

                                <span id="selectedSlotText" class="font-bold">
                                </span>

                            </div>

                        </div>
                    </div>
                </div>

                <div class="modal-section-heading mt-5 sm:mt-6">

                    <span class="modal-section-icon">
                        <i class="fa-regular fa-message"></i>
                    </span>

                    <div>
                        <h4>
                            Reason for Rescheduling
                        </h4>

                        <p>
                            Optional — add a short explanation for this schedule change.
                        </p>
                    </div>

                </div>

                <div class="global-form-group" data-global-field>

                    <textarea id="reschedule_reason" name="reschedule_reason" rows="3" maxlength="1000"
                        placeholder="e.g. Patient requested a later date…" class="form-input-custom global-form-textarea"
                        data-field-label="Reason for Rescheduling"></textarea>

                    <div id="rescheduleReasonError" class="global-field-error" data-error-for="reschedule_reason"
                        aria-hidden="true">
                    </div>

                </div>
            </div>

            <div class="modal-ft modal-sticky-footer">

                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="rescheduleModal">
                    <span>Cancel</span>
                </button>

                <button type="submit" id="confirmRescheduleBtn" class="ui-btn ui-btn-warning">
                    <span>Confirm Reschedule</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRescheduleModalFromDay(id, name, datetime, serviceType, updateUrl) {
        openRescheduleModal({
            id,
            name,
            datetime,
            serviceType,
            updateUrl
        });
    }

    let selectedRescheduleId = null;

    function getRescheduleCalendar() {
        const container =
            document.getElementById(
                'calGridWrapReschedule'
            );

        if (!container) {
            return null;
        }

        return window.__appointmentCalendars?.[
            container.id
        ] || null;
    }

    function clearRescheduleSlotSelection() {
        const timeInput = document.getElementById('new_appointment_time');
        const selectedSlotDisplay =
            document.getElementById(
                'selectedSlotDisplay'
            );

        const selectedSlotText =
            document.getElementById(
                'selectedSlotText'
            );
        const clearBtn = document.getElementById('clearSlotSelectionBtn');
        const slotGrid = document.getElementById('slotGrid');

        getRescheduleCalendar()?.setSelectedTime?.(null);

        if (timeInput) {
            timeInput.value = '';
            timeInput.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }

        if (selectedSlotText) {
            selectedSlotText.textContent = '';
        }

        selectedSlotDisplay?.classList.add(
            'hidden'
        );

        if (clearBtn) {
            clearBtn.classList.add('hidden');
            clearBtn.setAttribute('aria-hidden', 'true');
        }

        slotGrid
            ?.querySelectorAll('.slot-chip')
            .forEach(chip => {
                chip.classList.remove('selected');

                chip.setAttribute(
                    'aria-pressed',
                    'false'
                );
            });
        if (typeof markFormDirty === 'function') markFormDirty();
    }

    function resetRescheduleSlotUi() {
        const dateInput =
            document.getElementById(
                'new_appointment_date'
            );

        const timeInput =
            document.getElementById(
                'new_appointment_time'
            );

        const dateBanner =
            document.getElementById(
                'dateBanner'
            );

        const slotPlaceholder =
            document.getElementById(
                'slotPlaceholder'
            );

        const slotContainer =
            document.getElementById(
                'slotContainer'
            );

        const slotGrid =
            document.getElementById(
                'slotGrid'
            );

        const selectedSlotDisplay =
            document.getElementById(
                'selectedSlotDisplay'
            );

        const selectedSlotText =
            document.getElementById(
                'selectedSlotText'
            );

        const clearBtn =
            document.getElementById(
                'clearSlotSelectionBtn'
            );

        const calendar =
            getRescheduleCalendar();

        calendar
            ?.setSelectedDate?.(null);

        calendar
            ?.setSelectedTime?.(null);

        if (dateInput) {
            dateInput.value = '';
        }

        if (timeInput) {
            timeInput.value = '';
        }

        if (dateBanner) {
            dateBanner.replaceChildren();
            dateBanner.classList.add(
                'hidden'
            );
            dateBanner.style.removeProperty(
                'display'
            );
        }

        if (slotGrid) {
            slotGrid.replaceChildren();
            slotGrid.style.removeProperty(
                'display'
            );
        }

        slotContainer?.classList.add(
            'hidden'
        );

        slotContainer?.style.removeProperty(
            'display'
        );

        slotPlaceholder?.classList.remove(
            'hidden'
        );

        slotPlaceholder?.style.removeProperty(
            'display'
        );

        selectedSlotDisplay?.classList.add(
            'hidden'
        );

        selectedSlotDisplay
            ?.style.removeProperty(
                'display'
            );

        if (selectedSlotText) {
            selectedSlotText.textContent = '';
        }

        if (clearBtn) {
            clearBtn.classList.add(
                'hidden'
            );

            clearBtn.setAttribute(
                'aria-hidden',
                'true'
            );

            clearBtn.style.removeProperty(
                'display'
            );
        }
    }

    function openRescheduleModal(payload = {}) {
        selectedRescheduleId = payload.id || null;

        const modal = document.getElementById('rescheduleModal');
        if (!modal) return;

        const patientEl = document.getElementById('resPatientName');
        const scheduleEl = document.getElementById('resCurrentSchedule');
        const serviceEl = document.getElementById('resServiceType');

        if (patientEl) patientEl.textContent = payload.name || '—';
        if (scheduleEl) {
            scheduleEl.textContent = String(payload.datetime || '—')
                .replace(/\s*[•·]\s*/g, ' | ');
        }
        if (serviceEl) serviceEl.textContent = payload.serviceType || '—';

        const form = document.getElementById('rescheduleForm');
        if (form && payload.updateUrl) {
            form.action = payload.updateUrl;
        }

        const serviceInput = document.querySelector('#rescheduleForm input[name="service_type"]');
        if (serviceInput) {
            serviceInput.value = payload.serviceType || '';
        }

        const dateInput = document.getElementById('new_appointment_date');
        const timeInput = document.getElementById('new_appointment_time');
        const reasonInput = document.getElementById('reschedule_reason');

        if (dateInput) dateInput.value = '';
        if (timeInput) timeInput.value = '';
        if (reasonInput) reasonInput.value = '';

        const calendarGroup =
            document.querySelector(
                '#rescheduleModal .cal-wrap'
            );

        const timeSlotGroup =
            document.querySelector(
                '#rescheduleModal .slots-wrap'
            );

        window.clearGlobalGroupError?.(
            calendarGroup,
            'reschedule-date'
        );

        window.clearGlobalGroupError?.(
            timeSlotGroup,
            'reschedule-time'
        );

        resetRescheduleSlotUi();

        const submitBtn = document.getElementById('confirmRescheduleBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Confirm Reschedule';
        }

        window.openModal?.(
            'rescheduleModal'
        );

        const calendar = getRescheduleCalendar();

        calendar
            ?.renderLoading?.();

        setTimeout(() => {
            calendar
                ?.render?.();
        }, 0);

        if (dateInput) dateInput.value = '';
        if (timeInput) timeInput.value = '';
    }

    function forceCloseRescheduleModal() {
        const modal =
            document.getElementById(
                'rescheduleModal'
            );

        if (
            !modal ||
            !modal.classList.contains(
                'open'
            )
        ) {
            return;
        }

        resetRescheduleSlotUi();

        document
            .getElementById(
                'rescheduleForm'
            )
            ?.reset();

        window.closeModal?.(
            'rescheduleModal'
        );

        selectedRescheduleId = null;
    }

    function closeRescheduleModal(options = {}) {
        const modal = document.getElementById('rescheduleModal');

        if (!modal) return;

        if (options.force === true) {
            forceCloseRescheduleModal();
            return;
        }

        if (window.DiscardChanges) {
            window.DiscardChanges.confirmClose(
                modal,
                forceCloseRescheduleModal
            );

            return;
        }

        forceCloseRescheduleModal();
    }

    function handleRescheduleBackdropClick(e) {
        if (e.target === document.getElementById('rescheduleModal')) {
            closeRescheduleModal();
        }
    }

    document.getElementById("rescheduleForm")?.addEventListener("submit", async e => {
        e.preventDefault();

        let valid = true;

        const dateInput =
            document.getElementById(
                'new_appointment_date'
            );

        const timeInput =
            document.getElementById(
                'new_appointment_time'
            );

        const selectedDateValue =
            String(
                dateInput?.value || ''
            ).trim();

        const selectedTimeValue =
            String(
                timeInput?.value || ''
            ).trim();

        const calendarGroup =
            document.querySelector(
                '#rescheduleModal .cal-wrap'
            );

        const timeSlotGroup =
            document.querySelector(
                '#rescheduleModal .slots-wrap'
            );

        if (!selectedDateValue) {
            window.showGlobalGroupError?.(
                calendarGroup,
                'reschedule-date',
                'Please select a date.'
            );

            valid = false;
        } else {
            window.clearGlobalGroupError?.(
                calendarGroup,
                'reschedule-date'
            );
        }

        if (!selectedTimeValue) {
            window.showGlobalGroupError?.(
                timeSlotGroup,
                'reschedule-time',
                'Please select a time slot.'
            );

            valid = false;
        } else {
            window.clearGlobalGroupError?.(
                timeSlotGroup,
                'reschedule-time'
            );
        }

        if (!valid) {
            window.DiscardChanges?.markNotSubmitting(
                document.getElementById('rescheduleForm')
            );

            return;
        }

        const form = document.getElementById("rescheduleForm");
        if (!form || !form.action) {
            window.DiscardChanges?.markNotSubmitting(form);

            alert("Reschedule form action is missing.");
            return;
        }

        const formData = new FormData(form);

        const submitBtn = document.getElementById('confirmRescheduleBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
        }

        try {
            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                    "Accept": "application/json",
                },
                body: formData,
            });

            const data = await response.json().catch(() => null);

            if (response.ok && (data?.success ?? true)) {
                closeRescheduleModal({
                    force: true
                });

                if (typeof closeDayAppointmentsModal === 'function') {
                    closeDayAppointmentsModal();
                }

                sessionStorage.setItem('dentistToast', JSON.stringify({
                    title: 'Appointment rescheduled',
                    message: `${document.getElementById('resPatientName')?.textContent || 'Appointment'} was updated successfully.`,
                    tone: 'success',
                    duration: 3500
                }));

                window.location.reload();
            } else {
                window.DiscardChanges?.markNotSubmitting(form);

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Confirm Reschedule';
                }
                alert(data?.message ?? "Something went wrong. Please try again.");
            }
        } catch (err) {
            window.DiscardChanges?.markNotSubmitting(form);

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Reschedule';
            }
            alert("Network error. Please try again.");
        }
    });

    document.addEventListener('keydown', event => {
        const modal = document.getElementById('rescheduleModal');

        if (
            event.key !== 'Escape' ||
            !modal?.classList.contains('open')
        ) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        closeRescheduleModal();
    }, true);
</script>
