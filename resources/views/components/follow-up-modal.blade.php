@props([
    'id' => 'followUpModal',
    'patientName' => '—',
    'patientAvatarUrl' => '',
    'serviceType' => '—',
    'appointmentDate' => '—',
    'appointmentTime' => '—',
    'storeUrl' => '',
])

<x-modal :id="$id" title="Set Follow-Up Appointment" :title-id="$id . 'Title'"
    subtitle="Choose a date and time, then save the follow-up appointment." icon="fa-regular fa-calendar-check"
    size="xl" theme="warning" card-class="modal-split-card" body-class="modal-scroll-body"
    footer-class="modal-sticky-footer" close-label="Close follow-up modal" :close-attributes="[
        'data-discard-close' => $id,
    ]" form-id="followUpForm"
    form-class="modal-card-form" :form-attributes="[
        'method' => 'POST',
        'action' => $storeUrl,
        'data-discard-form' => '',
        'data-discard-title' => 'Discard follow-up schedule?',
        'data-discard-subtitle' => 'The selected schedule has not been saved.',
        'data-discard-message' =>
            'Your selected date, time, and reason will be removed. Do you want to discard these changes?',
        'novalidate' => 'novalidate',
    ]">
    @csrf

    <input type="hidden" id="followup_appointment_date" name="followup_appointment_date" required>

    <input type="hidden" id="followup_appointment_time" name="followup_appointment_time" required>

    <div class="modal-profile-card">

        <div class="modal-profile-main">

            <span class="patient-avatar patient-avatar-lg" data-patient-avatar data-patient-name="{{ $patientName }}"
                data-patient-url="{{ $patientAvatarUrl }}" aria-label="{{ $patientName }}"></span>

            <div class="modal-profile-main-copy">

                <span class="modal-profile-eyebrow">
                    Appointment for
                </span>

                <strong id="followUpPatientName" class="modal-profile-name" data-patient-name>
                    {{ $patientName }}
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

                    <strong id="followUpCurrentSchedule" class="modal-profile-value">
                        {{ $appointmentDate }} | {{ $appointmentTime }}
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

                    <strong id="followUpServiceType" class="modal-profile-value">
                        {{ $serviceType }}
                    </strong>
                </div>

            </div>

        </div>

    </div>

    <div class="modal-form-grid-2 mb-2 sm:mb-3">

        <div id="followUpCalendarWrap" class="cal-wrap modal-calendar-panel calendar-shell-no-card" data-global-field
            data-global-linked-input="#followup_appointment_date" data-global-error-key="followup_appointment_date"
            data-global-required="true" data-global-required-message="Please select a follow-up date.">
            <div id="followUpCalendarGrid"></div>

            <div id="followUpDateError" class="global-field-error" data-error-for="followup_appointment_date"
                aria-hidden="true"></div>
        </div>

        <div id="followUpTimeWrap" class="slots-wrap time-panel appointment-time-panel" data-global-field
            data-global-linked-input="#followup_appointment_time" data-global-error-key="followup_appointment_time"
            data-global-required="true" data-global-required-message="Please select a follow-up time slot.">

            <div class="appointment-time-heading">

                <span class="appointment-time-heading-icon">
                    <i class="fa-regular fa-clock"></i>
                </span>

                <div>
                    <h4>Pick a Time Slot</h4>

                    <p>
                        Choose a follow-up appointment time for the selected date.
                    </p>
                </div>

            </div>

            <div id="followUpDateBanner" class="hidden appointment-slot-date-banner"></div>

            <div id="followUpSlotPlaceholder" class="appointment-slot-placeholder">
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

            <div id="followUpSlotContainer" class="hidden appointment-slot-container">

                <div id="followUpSlotGrid" class="appointment-slot-grid"></div>

                <x-button id="followUpClearTimeBtn" type="button" variant="secondary" size="sm"
                    class="appointment-slot-clear hidden mt-4 mb-2 w-full" aria-hidden="true">
                    Clear selection
                </x-button>

                <div id="followUpSelectedSlotDisplay" class="hidden appointment-selected-slot">
                    <i class="fa-solid fa-circle-check"></i>

                    Selected:

                    <span id="followUpSelectedSlotText" class="font-bold"></span>
                </div>

            </div>

            <div id="followUpTimeError" class="global-field-error" data-error-for="followup_appointment_time"
                aria-hidden="true"></div>

        </div>

    </div>

    <div class="modal-section-heading mt-5 sm:mt-6">

        <span class="modal-section-icon">
            <i class="fa-regular fa-message"></i>
        </span>

        <div>
            <h4>
                Reason for Follow-Up
            </h4>

            <p>
                Optional — add a short explanation for this follow-up appointment.
            </p>
        </div>

    </div>

    <x-form-field for="followup_reason" error-id="followUpReasonError">
        <textarea id="followup_reason" name="followup_reason" rows="3" maxlength="1000"
            placeholder="e.g. Patient requires another evaluation…" class="form-input-custom global-form-textarea"
            data-field-label="Reason for Follow-Up"></textarea>
    </x-form-field>

    <x-slot:footer>

        <x-button type="button" variant="secondary" data-discard-close="{{ $id }}">
            Cancel
        </x-button>

        <x-button id="confirmFollowUpBtn" type="submit" variant="warning">
            Schedule Follow-Up
        </x-button>

    </x-slot:footer>
</x-modal>
