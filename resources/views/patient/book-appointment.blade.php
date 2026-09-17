@extends('layouts.app')

@section('layout-role', 'patient')

@section('title', 'Book Appointment')

@section('hide-sidebar')
@endsection

@section('hide-mobile-nav')
@endsection

@section('hide-patient-modals')
@endsection

@section('hide-floating-actions')
@endsection

@section('styles')
    @vite('resources/css/pages/patient/book-appointment.css')
@endsection

@php
    $notifications = collect($notifications ?? []);
    $notifCount = $notifications->count();
    $isFemalePatient = strtolower($patient->gender ?? '') === 'female';
    $isReservedBooking = isset($reservedBookingPeriod) && $reservedBookingPeriod;
@endphp

@section('content')
    <main id="mainContent" class="app-page-shell booking-page page-enter">
        <div class="booking-page-inner">
            <x-booking.workflow-header :back-url="route('homepage')" back-label="Back to Home" form-target="#appointmentForm"
                icon="fa-regular fa-calendar-check" title="Book an Appointment"
                subtitle="Complete all five steps to schedule your dental visit." :steps="['Date & Time', 'Service', 'Dental History', 'Medical History', 'Confirm']" />

            <div class="w-full">

                <div class="booking-workflow-card">
                    <div>

                        <form id="appointmentForm" action="{{ route('book.appointment.store') }}" method="POST"
                            enctype="multipart/form-data" data-global-selects data-global-validation data-discard-form
                            data-discard-save-handler="saveAppointmentDraftBeforeLeave"
                            data-discard-handler="discardAppointmentDraftBeforeLeave"
                            data-discard-title="Discard appointment?"
                            data-discard-subtitle="You have unsaved appointment information."
                            data-discard-message="Leaving this page will remove the appointment details you entered. Do you want to discard your changes?">
                            @csrf
                            <input type="hidden" id="update_dental_history" name="update_dental_history" value="0">
                            <input type="hidden" id="update_medical_history" name="update_medical_history" value="0">

                            @if ($isReservedBooking)
                                <input type="hidden" name="reserved_booking_period_id"
                                    value="{{ $reservedBookingPeriod->id }}">
                                <input type="hidden" id="reserved_booking_period_slot_id"
                                    name="reserved_booking_period_slot_id" value="">
                            @endif

                            <input type="hidden" id="contact_email" name="contact_email"
                                value="{{ old('contact_email', $patient->email ?? '') }}">

                            <input type="hidden" id="contact_phone" name="contact_phone"
                                value="{{ old('contact_phone', $patient->phone ?? '') }}">

                            <input type="hidden" id="contact_address" name="contact_address"
                                value="{{ old('contact_address', $patient->address ?? '') }}">

                            <div class="step-content hidden">
                                <div class="booking-step-shell">
                                    <div class="booking-step-header">
                                        <p class="booking-step-eyebrow">Step 1 of 5</p>
                                        <h2 class="booking-step-title">Select Date &amp; Time</h2>
                                        <p class="booking-step-subtitle">
                                            Choose your preferred appointment date and available clinic time slot.
                                        </p>
                                    </div>

                                    <div class="booking-step-body">
                                        <input type="hidden" id="appointment_date" name="appointment_date" required
                                            value="{{ $isReservedBooking ? $reservedBookingPeriod->reserved_date->format('Y-m-d') : '' }}">
                                        <input type="hidden" id="appointment_time" name="appointment_time" required
                                            value="{{ $isReservedBooking && $reservedBookingPeriod->booking_mode === 'date_only' ? \Carbon\Carbon::parse($reservedBookingPeriod->start_time)->format('g:i A') : '' }}">

                                        @if ($isReservedBooking)
                                            <div class="booking-section-card space-y-5">
                                                <div class="flex flex-wrap items-start justify-between gap-4">
                                                    <div>
                                                        <span class="cal-pill cal-pill-maroon inline-flex mb-3">
                                                            <i class="fa-solid fa-calendar-check"></i>
                                                            Reserved appointment
                                                        </span>
                                                        <h3 class="text-xl font-bold text-[#303846]">
                                                            {{ $reservedBookingPeriod->title }}
                                                        </h3>
                                                        <p class="mt-1 text-sm text-[#697386]">
                                                            This schedule is exclusively available to
                                                            {{ $reservedBookingPeriod->target_label }}.
                                                        </p>
                                                    </div>

                                                    <div class="text-sm text-[#4b5563] sm:text-right">
                                                        <p class="font-bold">
                                                            {{ $reservedBookingPeriod->reserved_date->format('F j, Y') }}
                                                        </p>
                                                        <p>
                                                            {{ \Carbon\Carbon::parse($reservedBookingPeriod->start_time)->format('g:i
                                                                                                                A') }}
                                                            &ndash;
                                                            {{ \Carbon\Carbon::parse($reservedBookingPeriod->end_time)->format('g:i
                                                                                                                A') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                @if ($reservedBookingPeriod->booking_mode === 'timeslot')
                                                    <div class="time-panel appointment-time-panel" data-global-field
                                                        data-global-linked-input="#appointment_time"
                                                        data-global-error-key="appointment_time">
                                                        <div class="appointment-time-heading">
                                                            <span class="appointment-time-heading-icon">
                                                                <i class="fa-regular fa-clock"></i>
                                                            </span>
                                                            <div>
                                                                <h4>Pick a Reserved Timeslot</h4>
                                                                <p>Each timeslot can be selected by one patient only.</p>
                                                            </div>
                                                        </div>

                                                        <div id="slotGrid"
                                                            class="appointment-slot-grid slot-grid-ui reserved-slot-grid">
                                                            @foreach ($availableReservedSlots as $slot)
                                                                @php(
    $slotLabel = \Carbon\Carbon::parse($slot->slot_time)->format('g:i
                                                A')
)
                                                                <button type="button"
                                                                    class="slot-chip flex items-center gap-2.5 px-4 py-2.5 rounded-xl border font-semibold text-sm cursor-pointer"
                                                                    data-reserved-slot-id="{{ $slot->id }}"
                                                                    data-reserved-slot-time="{{ $slotLabel }}"
                                                                    aria-pressed="false">
                                                                    <i class="text-xs opacity-70 fa-regular fa-clock"></i>
                                                                    <span>{{ $slotLabel }}</span>
                                                                </button>
                                                            @endforeach
                                                        </div>

                                                        <button type="button" id="clearSlotSelectionBtn"
                                                            class="ui-btn ui-btn-secondary ui-btn-sm hidden mt-4 mb-2 w-full"
                                                            aria-hidden="true">
                                                            Clear selection
                                                        </button>

                                                        <div id="selectedSlotDisplay"
                                                            class="hidden appointment-selected-slot mt-4">
                                                            <i class="fa-solid fa-circle-check"></i>
                                                            Selected: <span id="selectedSlotText" class="font-bold"></span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="appointment-selected-slot">
                                                        <i class="fa-solid fa-list-ol"></i>
                                                        <span>
                                                            No individual timeslot is required. You may be attended in queue
                                                            order
                                                            anytime within the reserved period.
                                                        </span>
                                                    </div>
                                                @endif

                                                @if ($reservedBookingPeriod->notes)
                                                    <p class="text-sm text-[#697386]">
                                                        <strong>Clinic note:</strong> {{ $reservedBookingPeriod->notes }}
                                                    </p>
                                                @endif
                                            </div>
                                        @else
                                            <div class="cal-time-layout grid gap-5 lg:gap-6 mx-auto w-full">

                                                <div class="calendar-shell-no-card" data-global-field
                                                    data-global-linked-input="#appointment_date"
                                                    data-global-error-key="appointment_date">
                                                    <div id="calendarSkeletonContainer"></div>
                                                </div>

                                                <div class="time-panel appointment-time-panel is-empty" data-global-field
                                                    data-global-linked-input="#appointment_time"
                                                    data-global-error-key="appointment_time">

                                                    <div class="appointment-time-heading">

                                                        <span class="appointment-time-heading-icon">
                                                            <i class="fa-regular fa-clock"></i>
                                                        </span>

                                                        <div>
                                                            <h4>Pick a Time Slot</h4>

                                                            <p>
                                                                Choose your preferred schedule for the selected date.
                                                            </p>
                                                        </div>

                                                    </div>

                                                    <div id="dateBanner" class="hidden appointment-slot-date-banner">
                                                    </div>

                                                    <div id="workingHolidayNotice"
                                                        class="hidden cal-pill cal-pill-working-holiday mb-3"
                                                        aria-hidden="true">
                                                        <i class="fa-solid fa-briefcase"></i>

                                                        <span>
                                                            Working Holiday · Regular schedule applies
                                                        </span>
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
                                                            class="ui-btn ui-btn-secondary ui-btn-sm hidden mt-4 mb-2 w-full">
                                                            Clear selection
                                                        </button>

                                                        <div id="selectedSlotDisplay"
                                                            class="hidden appointment-selected-slot">

                                                            <i class="fa-solid fa-circle-check"></i>

                                                            Selected:

                                                            <span id="selectedSlotText" class="font-bold">
                                                            </span>

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <x-booking.service-step :services="$serviceTypes" title="Choose Your Dental Service"
                                subtitle="Select the type of service you want to book for your appointment." />

                            <x-booking.dental-history :questions="$dentalQuestions" mode="flat" :defaults="$dentalDefaults"
                                subtitle="Review your saved dental history and update it only if something has changed." />

                            <div class="step-content hidden" id="step4">
                                <div class="booking-step-shell">
                                    <div class="booking-step-header">
                                        <p class="booking-step-eyebrow">Step 4 of 5</p>
                                        <h2 class="booking-step-title">Medical History</h2>
                                        <p class="booking-step-subtitle">
                                            Provide important medical information so the clinic can prepare safe and
                                            proper
                                            dental care for you.
                                        </p>
                                    </div>

                                    <div class="booking-step-body">

                                        <x-booking.medical-history-fields :questions="$medicalQuestions" :diseases="$diseases"
                                            mode="standard" :defaults="$medicalDefaults" :selected-diseases="$selectedDiseases" :is-female="$isFemalePatient" />

                                        <x-booking.signature :has-existing-signature="$hasReusableSignature" :existing-signature-url="$patient->medicalHistory?->patient_signature
                                            ? asset('storage/' . $patient->medicalHistory->patient_signature)
                                            : null" />
                                    </div>
                                </div>
                            </div>

                            <div class="step-content hidden" id="step5">
                                <div class="booking-step-shell">
                                    <div id="summarySection">
                                        <div class="booking-step-header">
                                            <p class="booking-step-eyebrow">Step 5 of 5</p>
                                            <h2 class="booking-step-title">Review Your Information</h2>
                                            <p class="booking-step-subtitle">
                                                Please review all the information you provided before proceeding to
                                                final
                                                confirmation.
                                            </p>
                                        </div>

                                        <div id="summaryBox" class="space-y-4"></div>
                                    </div>

                                    <div id="confirmationSection" class="hidden">
                                        <div class="booking-step-header">
                                            <p class="booking-step-eyebrow">Step 5 of 5</p>
                                            <h2 class="booking-step-title">Final Confirmation</h2>
                                            <p class="booking-step-subtitle">
                                                Confirm that the information is accurate and that you accept the clinic
                                                terms
                                                and privacy policy.
                                            </p>
                                        </div>


                                        <div class="booking-step-body">

                                            <x-booking.final-confirmation
                                                message="By submitting, you confirm that all the information provided is accurate and complete.">
                                                I have reviewed my dental and medical information
                                                and agree to the

                                                <a href="https://www.pup.edu.ph/privacy/" target="_blank"
                                                    rel="noopener noreferrer" data-legal-link class="booking-legal-link">
                                                    Privacy Policy
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                </a>

                                                and

                                                <a href="https://www.pup.edu.ph/terms/" target="_blank"
                                                    rel="noopener noreferrer" data-legal-link class="booking-legal-link">
                                                    Terms and Conditions
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                </a>.
                                            </x-booking.final-confirmation>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <x-booking.navigation />

                </div>
            </div>
    </main>

    <div id="introBookingModal" class="ui-modal" aria-hidden="true">

        <div class="ui-modal-card modal-lg">

            <div class="modal-hd">
                <div class="modal-heading">

                    <div class="modal-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>

                    <div class="modal-copy">
                        <h2 class="modal-title">
                            Let's get your dental appointment ready
                        </h2>

                        <p class="modal-subtitle">
                            Complete the required booking information
                            before submitting your appointment request.
                        </p>
                    </div>

                </div>
            </div>

            <div class="modal-bd">

                <div class="booking-intro-steps">

                    <div class="booking-intro-step">
                        <strong>1</strong>
                        <span>Date</span>
                    </div>

                    <div class="booking-intro-step">
                        <strong>2</strong>
                        <span>Service</span>
                    </div>

                    <div class="booking-intro-step">
                        <strong>3</strong>
                        <span>Dental</span>
                    </div>

                    <div class="booking-intro-step">
                        <strong>4</strong>
                        <span>Medical</span>
                    </div>

                    <div class="booking-intro-step">
                        <strong>5</strong>
                        <span>Confirm</span>
                    </div>

                </div>

                <div class="booking-intro-checklist">

                    <div class="booking-intro-item">
                        <div class="global-icon-box global-icon-box-sm">
                            <i class="fa-solid fa-check"></i>
                        </div>

                        <p>
                            Fill out all required fields marked with
                            <strong>*</strong>.
                        </p>
                    </div>

                    <div class="booking-intro-item">
                        <div class="global-icon-box global-icon-box-sm">
                            <i class="fa-solid fa-phone-volume"></i>
                        </div>

                        <p>
                            Prepare your emergency contact information.
                        </p>
                    </div>

                    @if ($hasReusableSignature)
                        <div class="booking-intro-item">
                            <div class="global-icon-box global-icon-box-sm">
                                <i class="fa-solid fa-signature"></i>
                            </div>

                            <p>
                                Your existing verified signature
                                will be reused for this appointment.
                            </p>
                        </div>
                    @else
                        <div class="booking-intro-item">
                            <div class="global-icon-box global-icon-box-sm">
                                <i class="fa-solid fa-signature"></i>
                            </div>

                            <p>
                                Upload or draw your patient signature
                                before confirmation.
                            </p>
                        </div>
                    @endif

                    <div class="booking-intro-item">
                        <div class="global-icon-box global-icon-box-sm">
                            <i class="fa-solid fa-list-check"></i>
                        </div>

                        <p>
                            Review your appointment information
                            before submitting.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-ft">

                <a href="{{ route('homepage') }}" class="ui-btn ui-btn-primary ui-btn-sm" data-discard-navigation
                    data-discard-form-target="#appointmentForm">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Home
                </a>

                <button type="button" id="introContinueDraftBtn" class="ui-btn ui-btn-secondary ui-btn-sm"
                    style="display: none;">

                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Continue Draft
                </button>

                <button type="button" id="introStartBtn" class="ui-btn ui-btn-primary ui-btn-sm">

                    <i class="fa-solid fa-play"></i>
                    Begin Booking
                </button>
            </div>
        </div>
    </div>

    @if (!$isReservedBooking)
        <div id="holidaySchedulingNoticeModal" class="ui-modal modal-theme-primary" aria-hidden="true">
            <div class="ui-modal-card modal-md">

                <div class="modal-hd">
                    <div class="modal-heading w-full">

                        <div class="modal-icon">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>

                        <div class="modal-copy flex-1 min-w-0">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 class="modal-title">
                                    Holiday Scheduling Notice
                                </h2>

                                <span class="cal-pill cal-pill-maroon flex-shrink-0">
                                    <i class="fa-solid fa-bullhorn"></i>
                                    Announcement
                                </span>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="modal-bd">

                    <div class="global-confirm-alert mb-5">
                        <i class="fa-solid fa-circle-info"></i>

                        <p>
                            <strong>
                                Know how holidays affect appointment booking.
                            </strong>

                            <span>
                                A holiday does not always mean the clinic is closed.
                                Working holidays follow the regular clinic schedule,
                                while non-working holidays are unavailable for booking.
                            </span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4">
                            <span class="cal-pill cal-pill-working-holiday">
                                <i class="fa-solid fa-briefcase"></i>
                                Working Holiday
                            </span>

                            <p class="mt-3 text-sm font-bold text-[var(--text-1)]">
                                Regular schedule applies.
                            </p>

                            <p class="mt-1 text-xs leading-relaxed text-[var(--text-2)]">
                                You may book if appointment slots
                                are available.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4">
                            <span class="cal-pill cal-pill-yellow">
                                <i class="fa-solid fa-star"></i>
                                Non-Working Holiday
                            </span>

                            <p class="mt-3 text-sm font-bold text-[var(--text-1)]">
                                Clinic is closed.
                            </p>

                            <p class="mt-1 text-xs leading-relaxed text-[var(--text-2)]">
                                Appointment booking is unavailable
                                on this date.
                            </p>
                        </div>

                    </div>

                    <div class="mt-5 pt-4 border-t border-[var(--border)]">
                        <label class="global-checkbox-row" for="hideHolidayNoticeForSevenDays">
                            <input type="checkbox" id="hideHolidayNoticeForSevenDays" class="global-checkbox-input">

                            <span class="global-checkbox-label">
                                <span class="ui-muted-text">
                                    Don't show this notice again for 7 days.
                                </span>
                            </span>
                        </label>
                    </div>

                </div>

                <div class="modal-ft modal-ft--centered">

                    <button type="button" id="holidaySchedulingNoticeDoneBtn" class="ui-btn ui-btn-primary ui-btn-sm">
                        <i class="fa-solid fa-check"></i>
                        Got it
                    </button>

                </div>

            </div>
        </div>
    @endif

    @if (!$isReservedBooking)
        @include('components.appointment-calendar-script', [
            'mode' => 'booking',
            'renderStyle' => 'patient',
            'calendarContainerId' => 'calendarSkeletonContainer',
            'dateInputId' => 'appointment_date',
            'timeInputId' => 'appointment_time',
            'dateBannerId' => 'dateBanner',
            'workingHolidayNoticeId' => 'workingHolidayNotice',
            'slotPlaceholderId' => 'slotPlaceholder',
            'slotContainerId' => 'slotContainer',
            'slotGridId' => 'slotGrid',
            'selectedSlotDisplayId' => 'selectedSlotDisplay',
            'selectedSlotTextId' => 'selectedSlotText',
            'slotEndpoint' => route('book.appointment.slots'),
            'scheduleRules' => $schedules ?? [],
            'blockedDates' => $blockedDates ?? [],
            'appointmentCountsPerDay' => $appointmentCountsPerDay ?? [],
            'philippineHolidays' => $philippineHolidays ?? [],
            'useDynamicScheduleRules' => true,
            'disallowToday' => true,
            'allowToggleOffDate' => true,
        ])
    @endif

@endsection

@section('scripts')

    <script>
        const diseaseLabelByCode = @json($diseases->pluck('label', 'code'));
        const isFemalePatient = @json($isFemalePatient);
        const isReservedBooking = @json((bool) $isReservedBooking);
        const reservedBookingPeriodId = @json($isReservedBooking ? $reservedBookingPeriod->id : null);
        const reservedBookingDate = @json($isReservedBooking ? $reservedBookingPeriod->reserved_date->format('Y-m-d') : null);
        const reservedDateOnlyTime = @json(
            $isReservedBooking && $reservedBookingPeriod->booking_mode === 'date_only'
                ? \Carbon\Carbon::parse($reservedBookingPeriod->start_time)->format('g:i A')
                : null);
        const DRAFT_KEY = isReservedBooking ?
            `appointmentDraft:reserved:${reservedBookingPeriodId}` :
            "appointmentDraft:v1";

        const HOLIDAY_NOTICE_STORAGE_KEY = 'holidaySchedulingNoticeHiddenUntil:v1';
        const HOLIDAY_NOTICE_HIDE_DURATION = 7 * 24 * 60 * 60 * 1000;

        function enforceReservedSchedule() {
            if (!isReservedBooking) return;

            const dateInput = document.getElementById('appointment_date');
            const timeInput = document.getElementById('appointment_time');

            if (dateInput) dateInput.value = reservedBookingDate || '';
            if (reservedDateOnlyTime && timeInput) timeInput.value = reservedDateOnlyTime;
        }

        function initializeReservedSlotPicker() {
            if (!isReservedBooking) return;

            enforceReservedSchedule();

            document.querySelectorAll('[data-reserved-slot-id]').forEach(chip => {
                if (chip.dataset.reservedPickerReady === '1') return;
                chip.dataset.reservedPickerReady = '1';

                chip.addEventListener('click', () => {
                    const slotIdInput = document.getElementById('reserved_booking_period_slot_id');
                    const timeInput = document.getElementById('appointment_time');
                    const selectedDisplay = document.getElementById('selectedSlotDisplay');
                    const selectedText = document.getElementById('selectedSlotText');
                    const clearSlotButton = document.getElementById('clearSlotSelectionBtn');

                    document.querySelectorAll('[data-reserved-slot-id]').forEach(option => {
                        option.classList.remove('selected');
                        option.setAttribute('aria-pressed', 'false');
                    });

                    chip.classList.add('selected');
                    chip.setAttribute('aria-pressed', 'true');

                    if (slotIdInput) slotIdInput.value = chip.dataset.reservedSlotId || '';
                    if (timeInput) {
                        timeInput.value = chip.dataset.reservedSlotTime || '';
                        timeInput.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                    if (selectedText) selectedText.textContent = chip.dataset.reservedSlotTime || '';
                    selectedDisplay?.classList.remove('hidden');
                    clearSlotButton?.classList.remove('hidden');
                    clearSlotButton?.removeAttribute('aria-hidden');
                });
            });
        }

        const DRAFT_SHOW_URL = @json(route('book.appointment.draft.show'));
        const DRAFT_SAVE_URL = @json(route('book.appointment.draft.save'));
        const DRAFT_DELETE_URL = @json(route('book.appointment.draft.delete'));

        let formIsDirty = false;
        let formSubmitting = false;
        let draftRestoring = false;

        let draftAutosaveTimer = null;

        const appointmentCountsPerDay =
            @json($appointmentCountsPerDay ?? []);

        const MAX_SLOTS_PER_DAY = 5;

        function getDraftCsrfToken() {
            return (
                document.querySelector(
                    '#appointmentForm input[name="_token"]'
                )?.value ||
                document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content ||
                ''
            );
        }

        function hasMeaningfulDraftData(payload) {
            if (
                !payload ||
                typeof payload !== "object"
            ) {
                return false;
            }

            const progressKeys = [
                "appointment_date",
                "appointment_time",
                "service_type",
                "last_dental_visit",
                "previous_dentist",
                "additional_concerns",
                "emergency_person",
                "emergency_number",
                "emergency_relation",
                "good_health",
                "had_medical_exam",
                "under_treatment",
                "hospitalized",
                "allergy_medicine",
                "allergy_food",
                "medication",
                "tobacco_use",
                "headaches",
                "earaches",
                "neck_aches",
                "diseases[]",
            ];

            return progressKeys.some(key => {
                const value = payload[key];

                if (Array.isArray(value)) {
                    return value.some(
                        item =>
                        String(
                            item ?? ""
                        ).trim() !== ""
                    );
                }

                return (
                    value !== null &&
                    value !== undefined &&
                    String(value).trim() !== ""
                );
            });
        }

        function clearDraft() {
            localStorage.removeItem(
                DRAFT_KEY
            );
        }

        function getLocalDraft() {
            const raw =
                localStorage.getItem(
                    DRAFT_KEY
                );

            if (!raw) {
                return null;
            }

            try {
                const parsed =
                    JSON.parse(raw);

                if (
                    !hasMeaningfulDraftData(
                        parsed
                    )
                ) {
                    clearDraft();
                    return null;
                }

                return {
                    payload: parsed,
                    savedAt: parsed.__meta?.savedAt ??
                        null,
                    source: "local",
                };
            } catch {
                clearDraft();
                return null;
            }
        }

        async function getServerDraft() {
            try {
                const response =
                    await fetch(
                        DRAFT_SHOW_URL, {
                            method: "GET",
                            headers: {
                                "Accept": "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            credentials: "same-origin",
                            cache: "no-store",
                        }
                    );

                if (!response.ok) {
                    return null;
                }

                const data =
                    await response.json();

                if (
                    !data?.has_draft ||
                    !data?.draft?.payload ||
                    !hasMeaningfulDraftData(
                        data.draft.payload
                    )
                ) {
                    return null;
                }

                return {
                    payload: data.draft.payload,

                    savedAt: data.draft.last_saved_at ??
                        data.draft.payload
                        ?.__meta
                        ?.savedAt ??
                        null,

                    currentStep: data.draft.current_step ??
                        0,

                    source: "server",
                };
            } catch (error) {
                console.warn(
                    "Unable to load server draft.",
                    error
                );

                return null;
            }
        }

        function getDraftTimestamp(draft) {
            if (!draft?.savedAt) {
                return 0;
            }

            const timestamp =
                new Date(
                    draft.savedAt
                ).getTime();

            return Number.isFinite(timestamp) ?
                timestamp :
                0;
        }

        function chooseNewestDraft(
            localDraft,
            serverDraft
        ) {
            if (
                localDraft &&
                !serverDraft
            ) {
                return localDraft;
            }

            if (
                serverDraft &&
                !localDraft
            ) {
                return serverDraft;
            }

            if (
                !localDraft &&
                !serverDraft
            ) {
                return null;
            }

            return (
                    getDraftTimestamp(
                        serverDraft
                    ) >
                    getDraftTimestamp(
                        localDraft
                    )
                ) ?
                serverDraft :
                localDraft;
        }

        async function resolveAvailableDraft() {
            const localDraft =
                getLocalDraft();

            const serverDraft =
                await getServerDraft();

            const newest =
                chooseNewestDraft(
                    localDraft,
                    serverDraft
                );

            if (!newest) {
                return null;
            }

            localStorage.setItem(
                DRAFT_KEY,
                JSON.stringify(
                    newest.payload
                )
            );

            return newest;
        }

        function saveDraftData({
            syncServer = true
        } = {}) {
            const form =
                document.getElementById(
                    "appointmentForm"
                );

            if (!form) {
                return null;
            }

            const data =
                new FormData(form);

            const obj = {};

            for (const [key, value] of data.entries()) {
                if (
                    key === "_token" ||
                    key === "patient_signature"
                ) {
                    continue;
                }

                if (obj[key] === undefined) {
                    obj[key] = value;
                } else if (Array.isArray(obj[key])) {
                    obj[key].push(value);
                } else {
                    obj[key] = [
                        obj[key],
                        value
                    ];
                }
            }

            if (
                !hasMeaningfulDraftData(
                    obj
                )
            ) {
                clearDraft();

                return null;
            }

            obj.__meta = {
                step: bookingWorkflow
                    ?.getCurrentStep?.() ??
                    0,

                savedAt: new Date()
                    .toISOString(),

                fieldCount: Object.keys(obj).length,
            };

            localStorage.setItem(
                DRAFT_KEY,
                JSON.stringify(obj)
            );

            if (syncServer) {
                syncDraftToServer(obj);
            }

            return obj;
        }

        async function syncDraftToServer(draft) {
            if (
                !draft ||
                formSubmitting ||
                draftRestoring
            ) {
                return false;
            }

            try {
                const response =
                    await fetch(
                        DRAFT_SAVE_URL, {
                            method: "PUT",

                            headers: {
                                "Content-Type": "application/json",

                                "Accept": "application/json",

                                "X-Requested-With": "XMLHttpRequest",

                                "X-CSRF-TOKEN": getDraftCsrfToken(),
                            },

                            credentials: "same-origin",

                            body: JSON.stringify({
                                payload: draft,

                                current_step: draft.__meta?.step ??
                                    0,
                            }),
                        }
                    );

                if (!response.ok) {
                    throw new Error(
                        `Draft save failed: ${response.status}`
                    );
                }

                return true;

            } catch (error) {
                console.warn(
                    "Server draft autosave unavailable.",
                    error
                );

                return false;
            }
        }

        window.saveAppointmentDraftBeforeLeave =
            async function() {
                clearTimeout(
                    draftAutosaveTimer
                );

                const draft =
                    saveDraftData({
                        syncServer: false
                    });

                if (!draft) {
                    formSubmitting = true;

                    try {
                        const response =
                            await fetch(
                                DRAFT_DELETE_URL, {
                                    method: "DELETE",

                                    headers: {
                                        "Accept": "application/json",

                                        "X-Requested-With": "XMLHttpRequest",

                                        "X-CSRF-TOKEN": getDraftCsrfToken(),
                                    },

                                    credentials: "same-origin",
                                }
                            );

                        if (!response.ok) {
                            throw new Error(
                                `Draft delete failed: ${response.status}`
                            );
                        }

                        clearDraft();

                        return true;

                    } catch (error) {
                        formSubmitting =
                            false;

                        console.warn(
                            "Unable to remove empty server draft.",
                            error
                        );

                        window.showToast?.({
                            type: "error",
                            title: "Draft not saved",
                            message: "Unable to clear the previous saved draft. Please try again.",
                            duration: 4000,
                        });

                        return false;
                    }
                }

                const saved =
                    await syncDraftToServer(
                        draft
                    );

                if (!saved) {
                    window.showToast?.({
                        type: "error",
                        title: "Draft not saved",
                        message: "Unable to save your draft right now. Please try again.",
                        duration: 4000,
                    });

                    return false;
                }

                formSubmitting = true;

                sessionStorage.setItem(
                    'appointmentDraftSavedToast',
                    '1'
                );

                return true;
            };

        window.discardAppointmentDraftBeforeLeave =
            async function() {
                clearTimeout(
                    draftAutosaveTimer
                );

                formSubmitting = true;

                try {
                    const response =
                        await fetch(
                            DRAFT_DELETE_URL, {
                                method: "DELETE",

                                headers: {
                                    "Accept": "application/json",

                                    "X-Requested-With": "XMLHttpRequest",

                                    "X-CSRF-TOKEN": getDraftCsrfToken(),
                                },

                                credentials: "same-origin",
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            `Draft delete failed: ${response.status}`
                        );
                    }

                    clearDraft();

                    return true;

                } catch (error) {
                    formSubmitting = false;

                    console.warn(
                        "Unable to discard appointment draft.",
                        error
                    );

                    window.showToast?.({
                        type: "error",
                        title: "Draft not discarded",
                        message: "Unable to remove your saved draft. Please try again.",
                        duration: 4000,
                    });

                    return false;
                }
            };

        function scheduleDraftAutosave() {
            if (
                formSubmitting ||
                draftRestoring ||
                !formIsDirty
            ) {
                return;
            }

            clearTimeout(
                draftAutosaveTimer
            );

            draftAutosaveTimer =
                setTimeout(() => {
                    saveDraftData();
                }, 700);
        }

        function saveDraftImmediately() {
            if (
                formSubmitting ||
                draftRestoring ||
                !formIsDirty
            ) {
                return;
            }

            clearTimeout(
                draftAutosaveTimer
            );

            saveDraftData();
        }

        async function restoreDraft() {
            const raw =
                localStorage.getItem(
                    DRAFT_KEY
                );

            if (!raw) {
                return false;
            }

            let obj;

            try {
                obj =
                    JSON.parse(raw);
            } catch {
                clearDraft();

                return false;
            }

            if (
                !hasMeaningfulDraftData(
                    obj
                )
            ) {
                clearDraft();

                return false;
            }

            const form =
                document.getElementById(
                    "appointmentForm"
                );

            if (!form) {
                return false;
            }

            draftRestoring = true;

            try {
                Object.keys(obj)
                    .forEach(name => {
                        if (
                            name === "__meta"
                        ) {
                            return;
                        }

                        const value =
                            obj[name];

                        if (
                            Array.isArray(value)
                        ) {
                            form
                                .querySelectorAll(
                                    `[name="${CSS.escape(name)}"]`
                                )
                                .forEach(el => {
                                    if (
                                        el.type ===
                                        "checkbox"
                                    ) {
                                        el.checked =
                                            value.includes(
                                                el.value
                                            );
                                    }
                                });

                            return;
                        }

                        form
                            .querySelectorAll(
                                `[name="${CSS.escape(name)}"]`
                            )
                            .forEach(el => {
                                const shouldPreserveEmergencyValue = [
                                        "emergency_person",
                                        "emergency_number",
                                        "emergency_relation",
                                    ].includes(name) &&
                                    String(value ?? "").trim() === "" &&
                                    String(
                                        el.type === "checkbox" || el.type === "radio" ?
                                        (el.checked ? el.value : "") :
                                        el.value ?? ""
                                    ).trim() !== "";

                                if (shouldPreserveEmergencyValue) {
                                    return;
                                }

                                if (
                                    el.type ===
                                    "radio"
                                ) {
                                    el.checked =
                                        el.value ===
                                        value;

                                } else if (
                                    el.type ===
                                    "checkbox"
                                ) {
                                    el.checked =
                                        value === true ||
                                        value === "on" ||
                                        value ===
                                        el.value;

                                } else {
                                    el.value =
                                        value;
                                }
                            });
                    });

                form
                    .querySelectorAll(
                        '[data-booking-conditional]'
                    )
                    .forEach(box => {
                        const radioName =
                            box.dataset.radioName;

                        if (!radioName) {
                            return;
                        }

                        const selectedRadio =
                            form.querySelector(
                                `input[type="radio"][name="${CSS.escape(
                    radioName
                )}"]:checked`
                            );

                        selectedRadio?.dispatchEvent(
                            new Event(
                                'change', {
                                    bubbles: true
                                }
                            )
                        );
                    });
                form
                    .querySelectorAll(
                        "select"
                    )
                    .forEach(select => {
                        const wrapper =
                            select.closest(
                                ".custom-select"
                            );

                        if (
                            wrapper &&
                            typeof window
                            .syncCustomSelect ===
                            "function"
                        ) {
                            window
                                .syncCustomSelect(
                                    wrapper
                                );
                        }
                    });

                const restoredDate =
                    document
                    .getElementById(
                        "appointment_date"
                    )
                    ?.value;

                const restoredTime =
                    document
                    .getElementById(
                        "appointment_time"
                    )
                    ?.value;

                if (isReservedBooking) {
                    enforceReservedSchedule();
                    initializeReservedSlotPicker();

                    const restoredSlotId = String(obj.reserved_booking_period_slot_id || '');
                    const restoredSlotChip = Array.from(
                        document.querySelectorAll('[data-reserved-slot-id]')
                    ).find(chip => chip.dataset.reservedSlotId === restoredSlotId);

                    restoredSlotChip?.click();
                } else if (restoredDate) {
                    const appointmentCount =
                        appointmentCountsPerDay[
                            restoredDate
                        ] || 0;

                    if (
                        appointmentCount >=
                        MAX_SLOTS_PER_DAY
                    ) {
                        showSlotFullWarning(
                            restoredDate
                        );

                    } else {
                        await selectDate(
                            restoredDate
                        );

                        if (restoredTime) {
                            const restoredTimeChip =
                                Array.from(
                                    document
                                    .querySelectorAll(
                                        ".slot-chip"
                                    )
                                )
                                .find(
                                    chip =>
                                    chip.dataset
                                    .time ===
                                    restoredTime &&
                                    !chip.classList
                                    .contains(
                                        "disabled"
                                    )
                                );

                            if (
                                restoredTimeChip
                            ) {
                                restoredTimeChip
                                    .click();
                            } else {
                                const timeInput =
                                    document
                                    .getElementById(
                                        "appointment_time"
                                    );

                                if (timeInput) {
                                    timeInput.value =
                                        "";
                                }
                            }
                        }
                    }
                }

                const restoredStep =
                    Math.max(
                        0,
                        Math.min(
                            Number(
                                obj.__meta
                                ?.step ??
                                0
                            ),
                            4
                        )
                    );

                if (bookingWorkflow) {
                    for (
                        let index = 0; index < restoredStep; index++
                    ) {
                        bookingWorkflow
                            .markComplete(
                                index
                            );
                    }

                    bookingWorkflow
                        .goTo(
                            restoredStep, {
                                scroll: false
                            }
                        );
                }

                formIsDirty = true;

                return true;

            } catch (error) {
                console.warn(
                    "Unable to restore appointment draft.",
                    error
                );

                return false;

            } finally {
                draftRestoring = false;
            }
        }

        const summarySection =
            document.getElementById(
                'summarySection'
            );

        const confirmationSection =
            document.getElementById(
                'confirmationSection'
            );

        let bookingWorkflow = null;

        let step5ConfirmationActive = false;

        const hasExistingDentalHistory =
            @json($hasExistingDentalHistory ?? false);

        const hasExistingMedicalHistory =
            @json($hasExistingMedicalHistory ?? false);

        const hasReusableSignature =
            @json($hasReusableSignature ?? false);

        let editingHistoryFromReview = null;

        function showStep5Review() {
            step5ConfirmationActive =
                false;

            summarySection
                ?.classList.remove(
                    'hidden'
                );

            confirmationSection
                ?.classList.add(
                    'hidden'
                );

            bookingWorkflow
                ?.setNextButton({
                    label: 'Confirm Appointment',

                    icon: 'fa-chevron-right',
                });
        }

        function showStep5Confirmation() {
            step5ConfirmationActive =
                true;

            summarySection
                ?.classList.add(
                    'hidden'
                );

            confirmationSection
                ?.classList.remove(
                    'hidden'
                );

            bookingWorkflow
                ?.setNextButton({
                    label: 'Save Changes',

                    icon: 'fa-floppy-disk',

                    iconPosition: 'left',
                });
        }

        function resetStep5View() {
            showStep5Review();
        }

        function scrollToInvalidTarget(target) {
            if (!target) return;

            const block =
                target.closest('.custom-select') ||
                target.closest('.grid') ||
                target.closest('.ml-6') ||
                target.closest('.booking-section-card') ||
                target.closest('.voice-input-wrap') ||
                target.closest('.date-input-wrap') ||
                target;

            block.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            const focusTarget =
                target.closest('.custom-select')
                ?.querySelector('.custom-select-button') ||
                target;

            if (
                typeof focusTarget.focus === 'function' &&
                !target.hasAttribute('readonly')
            ) {
                setTimeout(() => {
                    focusTarget.focus({
                        preventScroll: true
                    });
                }, 250);
            }
        }

        function isStepComplete(s) {
            const stepEl =
                bookingWorkflow
                ?.getPanels?.()
                ?.[s];

            if (!stepEl) {
                return true;
            }

            if (s === 0) {
                const dateInput =
                    document.getElementById(
                        'appointment_date'
                    );

                const timeInput =
                    document.getElementById(
                        'appointment_time'
                    );

                const calendarGroup =
                    stepEl.querySelector(
                        '.calendar-shell-no-card'
                    );

                const timeGroup =
                    stepEl.querySelector(
                        '.time-panel'
                    );

                if (!dateInput?.value) {
                    window.showGlobalGroupError?.(
                        calendarGroup,
                        'appointment_date',
                        'Please select an appointment date.'
                    );

                    calendarGroup
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });

                    return false;
                }

                window.clearGlobalGroupError?.(
                    calendarGroup,
                    'appointment_date'
                );

                if (!timeInput?.value) {
                    window.showGlobalGroupError?.(
                        timeGroup,
                        'appointment_time',
                        'Please select an available time slot.'
                    );

                    timeGroup
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });

                    return false;
                }

                window.clearGlobalGroupError?.(
                    timeGroup,
                    'appointment_time'
                );
            }

            if (s === 1) {
                const serviceGroup =
                    stepEl.querySelector(
                        '.service-step-grid'
                    );

                const selectedService =
                    serviceGroup?.querySelector(
                        'input[name="service_type"]:checked'
                    );

                if (!selectedService) {
                    window.showGlobalGroupError?.(
                        serviceGroup,
                        'service_type',
                        'Please select a dental service.'
                    );

                    serviceGroup
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });

                    return false;
                }

                window.clearGlobalGroupError?.(
                    serviceGroup,
                    'service_type'
                );
            }

            const validation =
                window.validateGlobalSection?.(
                    stepEl
                );

            if (
                validation &&
                !validation.valid
            ) {
                return false;
            }

            if (s === 3) {
                const signature =
                    window.BookingSignature?.get(
                        stepEl
                    );

                if (
                    signature &&
                    !signature.validate()
                ) {
                    const signatureGroup =
                        stepEl.querySelector(
                            '[data-booking-signature]'
                        );

                    signatureGroup
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });

                    return false;
                }
            }

            return true;
        }

        function initBookingWorkflow() {
            if (
                bookingWorkflow ||
                !window.BookingWorkflow
            ) {
                return;
            }

            bookingWorkflow =
                window.BookingWorkflow.create({
                    panels: '#appointmentForm > .step-content',

                    progressFill: '#headerProgressFill',

                    counter: '#stepCounterText',

                    navContainer: '#navBtns',

                    previousButton: '#prevBtn',

                    nextButton: '#nextBtn',

                    hideNavigationOnLast: false,

                    beforePrevious: currentStep => {
                        if (
                            currentStep === 4 &&
                            step5ConfirmationActive
                        ) {
                            showStep5Review();

                            return false;
                        }

                        if (currentStep === 4 && !editingHistoryFromReview) {
                            if (!hasExistingMedicalHistory) {
                                return true;
                            }

                            bookingWorkflow.goTo(hasExistingDentalHistory ? 1 : 2);

                            return false;
                        }

                        if (
                            currentStep === 3 &&
                            hasExistingDentalHistory &&
                            !editingHistoryFromReview
                        ) {
                            bookingWorkflow.goTo(1);

                            return false;
                        }

                        return true;
                    },

                    beforeNext: currentStep => {
                        if (!isStepComplete(currentStep)) {
                            return false;
                        }

                        if (currentStep === 1 && !editingHistoryFromReview && hasExistingDentalHistory) {
                            bookingWorkflow.markComplete(1);
                            bookingWorkflow.markComplete(2);

                            if (hasExistingMedicalHistory) {
                                bookingWorkflow.markComplete(3);
                                bookingWorkflow.goTo(4);
                            } else {
                                bookingWorkflow.goTo(3);
                            }

                            return false;
                        }

                        if (
                            currentStep === 2 &&
                            hasExistingMedicalHistory &&
                            !editingHistoryFromReview
                        ) {
                            bookingWorkflow.markComplete(2);
                            bookingWorkflow.markComplete(3);
                            bookingWorkflow.goTo(4);

                            return false;
                        }

                        if (
                            editingHistoryFromReview === 'dental' &&
                            currentStep === 2
                        ) {
                            bookingWorkflow.markComplete(2);

                            editingHistoryFromReview = null;

                            bookingWorkflow.goTo(4);

                            return false;
                        }

                        if (
                            editingHistoryFromReview === 'medical' &&
                            currentStep === 3
                        ) {
                            bookingWorkflow.markComplete(3);

                            editingHistoryFromReview = null;

                            bookingWorkflow.goTo(4);

                            return false;
                        }

                        return true;
                    },

                    onLastStep: () => {
                        buildSummary();
                        showStep5Review();
                    },

                    onLastStepNext: () => {
                        if (
                            !step5ConfirmationActive
                        ) {
                            if (!ensureGlobalContactInformation()) {
                                return false;
                            }

                            showStep5Confirmation();

                            return true;
                        }

                        return confirmFinalAppointment();
                    },

                    onStepChange: currentStep => {
                        if (
                            currentStep !== 3
                        ) {
                            return;
                        }

                        setTimeout(
                            () => {
                                window
                                    .BookingSignature
                                    ?.get(
                                        document
                                    )
                                    ?.resize();
                            },
                            120
                        );
                    },
                });
        }

        async function bootBookingWorkflow() {
            try {
                await window
                    .loadBookingWorkflowModule?.();

                initBookingWorkflow();

            } catch (error) {
                console.error(
                    'Unable to initialize booking workflow.',
                    error
                );
            }
        }

        if (
            document.readyState ===
            'loading'
        ) {
            document.addEventListener(
                'DOMContentLoaded',
                bootBookingWorkflow, {
                    once: true
                }
            );
        } else {
            bootBookingWorkflow();
        }

        function setupCharLimit(inputId, counterId, max = 150, warningId = null) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            const warning = warningId ? document.getElementById(warningId) : null;

            if (!input || !counter) return;

            function updateUI() {
                let length = input.value.length;

                if (length > max) {
                    input.value = input.value.slice(0, max);
                    length = max;
                }

                counter.textContent = `${length}/${max}`;

                counter.classList.remove("text-red-600", "text-yellow-500");
                input.classList.remove("border-red-500", "ring-1", "ring-red-400");

                if (warning) warning.classList.add("hidden");

                if (length >= max) {
                    counter.classList.add("text-red-600");
                    input.classList.add("border-red-500", "ring-1", "ring-red-400");
                    if (warning) warning.classList.remove("hidden");
                } else if (length >= max - 10) {
                    counter.classList.add("text-yellow-500");
                }
            }

            input.addEventListener("input", updateUI);

            updateUI();
        }

        function markFormDirty() {
            formIsDirty = true;
        }

        setupCharLimit("additional_concerns", "concernCount", 150);

        function editContactInformationFromReview() {
            const view =
                document.getElementById(
                    'contactSummaryView'
                );

            const editor =
                document.getElementById(
                    'contactSummaryEditor'
                );

            view?.classList.add('hidden');
            editor?.classList.remove('hidden');

            setTimeout(() => {
                document
                    .getElementById('contactEditEmail')
                    ?.focus();
            }, 80);
        }

        function cancelContactInformationEdit() {
            const view =
                document.getElementById(
                    'contactSummaryView'
                );

            const editor =
                document.getElementById(
                    'contactSummaryEditor'
                );

            const email =
                document.getElementById(
                    'contact_email'
                )?.value || '';

            const phone =
                document.getElementById(
                    'contact_phone'
                )?.value || '';

            const address =
                document.getElementById(
                    'contact_address'
                )?.value || '';

            const emailEditor =
                document.getElementById(
                    'contactEditEmail'
                );

            const phoneEditor =
                document.getElementById(
                    'contactEditPhone'
                );

            const addressEditor =
                document.getElementById(
                    'contactEditAddress'
                );

            if (emailEditor) {
                emailEditor.value = email;
            }

            if (phoneEditor) {
                phoneEditor.value = phone;
            }

            if (addressEditor) {
                addressEditor.value = address;
            }

            editor?.classList.add('hidden');
            view?.classList.remove('hidden');
        }

        function validateGlobalContactFields() {
            const emailEditor =
                document.getElementById(
                    'contactEditEmail'
                );

            const phoneEditor =
                document.getElementById(
                    'contactEditPhone'
                );

            const addressEditor =
                document.getElementById(
                    'contactEditAddress'
                );

            const fields = [
                emailEditor,
                phoneEditor,
                addressEditor,
            ].filter(Boolean);

            let firstInvalid = null;

            fields.forEach(field => {
                const valid =
                    window.validateFormInputField?.(field) ??
                    field.checkValidity();

                if (!valid && !firstInvalid) {
                    firstInvalid = field;
                }
            });

            return {
                valid: !firstInvalid,
                firstInvalid,
            };
        }

        function ensureGlobalContactInformation() {
            const validation =
                validateGlobalContactFields();

            if (validation.valid) {
                return true;
            }

            editContactInformationFromReview();

            window.setTimeout(() => {
                window.focusGlobalInvalidField?.(
                    validation.firstInvalid
                );
            }, 100);

            return false;
        }

        function saveGlobalContactInformation() {
            const emailEditor =
                document.getElementById(
                    'contactEditEmail'
                );

            const phoneEditor =
                document.getElementById(
                    'contactEditPhone'
                );

            const addressEditor =
                document.getElementById(
                    'contactEditAddress'
                );

            const validation =
                validateGlobalContactFields();

            if (!validation.valid) {
                window.focusGlobalInvalidField?.(
                    validation.firstInvalid
                );

                return;
            }

            const email =
                emailEditor?.value.trim() || '';

            const phone =
                phoneEditor?.value
                .replace(/\D/g, '')
                .trim() || '';

            const address =
                addressEditor?.value.trim() || '';

            const emailInput =
                document.getElementById(
                    'contact_email'
                );

            const phoneInput =
                document.getElementById(
                    'contact_phone'
                );

            const addressInput =
                document.getElementById(
                    'contact_address'
                );

            if (emailInput) {
                emailInput.value = email;
            }

            if (phoneInput) {
                phoneInput.value = phone;
            }

            if (addressInput) {
                addressInput.value = address;
            }

            markFormDirty();

            buildSummary();
        }

        function editDentalHistoryFromReview() {
            const updateDentalInput = document.getElementById('update_dental_history');
            if (updateDentalInput) updateDentalInput.value = '1';

            editingHistoryFromReview = 'dental';

            resetStep5View();

            bookingWorkflow?.goTo(2);
        }

        function openMedicalHistoryEditorFromReview({
            focusSignature = false
        } = {}) {

            const updateMedicalInput = document.getElementById('update_medical_history');
            if (updateMedicalInput) updateMedicalInput.value = '1';

            editingHistoryFromReview = 'medical';

            resetStep5View();

            bookingWorkflow?.goTo(3);

            setTimeout(() => {

                const signature =
                    window.BookingSignature
                    ?.get(document);

                signature?.resize();


                if (focusSignature) {

                    const signatureCard =
                        document.querySelector(
                            '[data-booking-signature]'
                        );

                    signatureCard?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

            }, 200);
        }

        function editSignatureFromReview() {

            const reuseInput =
                document.getElementById(
                    "reuse_existing_signature"
                );

            if (reuseInput) {
                reuseInput.value = "0";
            }


            const existingCard =
                document.querySelector(
                    ".signature-existing-card"
                );

            const editorWrapper =
                document.getElementById(
                    "signatureEditorWrapper"
                );


            existingCard?.classList.add("hidden");

            editorWrapper?.classList.remove("hidden");


            openMedicalHistoryEditorFromReview({
                focusSignature: true
            });


            setTimeout(() => {
                buildSummary();
            }, 250);

        }

        function editMedicalHistoryFromReview() {

            openMedicalHistoryEditorFromReview();

        }

        function buildSummary() {
            const form = document.getElementById("appointmentForm");
            if (!form) return;

            const data = new FormData(form);
            const get = n => data.get(n) || "N/A";
            const getAll = n => data.getAll(n);

            const emergencyRelation = data.get("emergency_relation") || "N/A";

            const sigFile = data.get("patient_signature");

            let sigHTML = hasReusableSignature ?
                `
        <div class="signature-existing-card">

            <div class="signature-existing-header">
                <i class="fa-solid fa-circle-check"></i>

                <div>
                    <p class="signature-existing-title">
                        Existing signature on file
                    </p>
                </div>
            </div>

            @if ($patient->medicalHistory?->patient_signature)
                <div class="signature-existing-preview">
                    <img
                        src="{{ asset('storage/' . $patient->medicalHistory->patient_signature) }}"
                        alt="Existing signature"
                    >
                </div>
            @endif

            <p class="signature-existing-help">
                Your previously verified signature will be reused.
            </p>

        </div>
    ` :
                `
        <span class="booking-summary-muted">
            Not uploaded
        </span>
    `;

            if (sigFile && sigFile.size > 0) {
                const url = URL.createObjectURL(sigFile);
                sigHTML = `
        <div class="space-y-2">
            <p><b class="text-[#5c5550] font-semibold">File:</b> ${sigFile.name}</p>
            <p class="text-emerald-700 font-semibold">
                <i class="fa-solid fa-circle-check mr-1"></i> Signature uploaded
            </p>
            <img src="${url}" alt="Signature" class="max-w-[220px] max-h-[130px] rounded-lg border border-[#e8e2dd]">
        </div>
    `;
            }

            const row = (
                label,
                value
            ) => {
                const resolvedValue =
                    value &&
                    String(value).trim() !== '' ?
                    value :
                    `
                <span class="booking-summary-muted">
                    N/A
                </span>
            `;

                return `
        <p class="booking-summary-row">
            <span class="booking-summary-row-label">
                ${label}:
            </span>

            ${resolvedValue}
        </p>
    `;
            };

            const optionalRow = (
                label,
                value
            ) => {
                if (
                    !value ||
                    String(value).trim() === '' ||
                    value === 'N/A'
                ) {
                    return '';
                }

                return `
        <p class="booking-summary-row">
            <span class="booking-summary-row-label">
                ${label}:
            </span>

            ${value}
        </p>
    `;
            };

            const summaryCard = (
                title,
                icon,
                body,
                editAction = null
            ) => `
<section class="booking-summary-card">

<div class="booking-summary-card-header flex items-center">
    <div class="flex items-center gap-2">
        <i class="fa-solid ${icon}"></i>

        <span>
            ${title}
        </span>
    </div>

    ${editAction ? `
                            <button
                                type="button"
                                class="ui-btn ui-btn-secondary ui-btn-sm ml-auto"
                                onclick="${editAction}"
                            >
                                <i class="fa-solid fa-pen"></i>
                                Edit
                            </button>
                        ` : ''}
</div>

    <div class="booking-summary-card-body">
        ${body}
    </div>

</section>
`;

            const subSection = (
                title,
                body
            ) => `
    <section class="booking-summary-section">

        <div class="booking-summary-section-title">
            ${title}
        </div>

        <div class="booking-summary-section-body">

            <div
                class="
                    grid
                    grid-cols-2
                    gap-x-8
                    gap-y-1
                    sm-grid-1col
                "
            >
                ${body}
            </div>

        </div>

    </section>
`;

            const fullWidthSection = (
                title,
                body
            ) => `
    <section class="booking-summary-section">

        <div class="booking-summary-section-title">
            ${title}
        </div>

        <div class="booking-summary-section-body">
            ${body}
        </div>

    </section>
`;

            const diseases =
                getAll(
                    'diseases[]'
                );


            const diseaseLabels =
                diseases.map(
                    code =>
                    diseaseLabelByCode?.[code] ??
                    code
                );


            const diseaseTags =
                diseaseLabels.length ?
                `
            <div class="booking-summary-tag-list">
                ${diseaseLabels
                    .map(
                        label => `
                                                                                                                            <span class="booking-summary-tag">
                                                                                                                                ${label}
                                                                                                                            </span>
                                                                                                                        `
                    )
                    .join('')}
            </div>
        ` :
                `
            <span class="booking-summary-muted">
                None selected
            </span>
        `;

            const patientName = @json($patient->name ?? 'N/A');
            const patientGender = @json($patient->gender ?? 'N/A');
            const contactEmail =
                document.getElementById('contact_email')?.value || 'N/A';

            const contactPhone =
                document.getElementById('contact_phone')?.value || 'N/A';

            const contactAddress =
                document.getElementById('contact_address')?.value || 'N/A';
            const dentalHistoryBody = `
    ${subSection("Basic Info", `
                                                                                                                ${row("Last Dental Visit", get("last_dental_visit"))}
                                                                                                                ${row("Previous Dentist", get("previous_dentist"))}
                                                                                                            `)}

    ${subSection("Dental Symptoms", `
                                                                                                                ${row("Bleeding Gums", get("bleeding_gums"))}
                                                                                                                ${row("Sensitive (Hot/Cold)", get("sensitive_temp"))}
                                                                                                                ${row("Sensitive (Sweets/Sour)", get("sensitive_taste"))}
                                                                                                                ${row("Tooth Pain", get("tooth_pain"))}
                                                                                                                ${row("Sores/Lumps", get("sores"))}
                                                                                                                ${row("Jaw Injuries", get("injuries"))}
                                                                                                            `)}

    ${subSection("Jaw & Bite Symptoms", `
                                                                                                                ${row("Clicking", get("clicking"))}
                                                                                                                ${row("Joint Pain", get("joint_pain"))}
                                                                                                                ${row("Difficulty Moving", get("difficulty_moving"))}
                                                                                                                ${row("Difficulty Chewing", get("difficulty_chewing"))}
                                                                                                                ${row("Frequent Headaches", get("jaw_headaches"))}
                                                                                                                ${row("Grinding/Clenching", get("clench_grind"))}
                                                                                                                ${row("Lips/Cheek Biting", get("biting"))}
                                                                                                                ${row("Teeth Loosening", get("teeth_loosening"))}
                                                                                                                ${row("Food Caught Between Teeth", get("food_teeth"))}
                                                                                                                ${row("Medicine Reaction", get("med_reaction"))}
                                                                                                            `)}

    ${subSection("Dental Procedures", `
                                                                                                                ${row("Periodontal Treatment", get("periodontal"))}
                                                                                                                ${row("Difficult Extraction", get("difficult_extraction"))}
                                                                                                                ${get("difficult_extraction") === "YES" ? row("Extraction Date", get("extraction_date")) : ""}

                                                                                                                ${row("Prolonged Bleeding", get("prolonged_bleeding"))}
                                                                                                                ${row("Dentures", get("dentures"))}
                                                                                                                ${get("dentures") === "YES" ? row("Dentures Placement Date", get("dentures_date")) : ""}

                                                                                                                ${row("Orthodontic Treatment", get("ortho_treatment"))}
                                                                                                                ${get("ortho_treatment") === "YES" ? row("Orthodontic Completion Date", get("ortho_date")) : ""}
                                                                                                            `)}

    ${fullWidthSection("Additional Concerns", `
                                                                                                                ${get("additional_concerns") !== "N/A" && String(get("additional_concerns")).trim() !== ""
                    ? get("additional_concerns")
                    : '<span class="text-[#9e9690] italic">No additional concerns provided.</span>'}
                                                                                                            `)}
    `;

            const medicalHistoryBody = `
    ${subSection("General Health", `
                                                                                                                ${row("Good Health", get("good_health"))}
                                                                                                                ${get("good_health") === "NO" ? row("Health Details", get("good_health_details")) : ""}

                                                                                                                ${row("Had Medical Exam", get("had_medical_exam"))}
                                                                                                                ${get("had_medical_exam") === "YES" ? row("Medical Exam Date", get("medical_exam_date")) : ""}

                                                                                                                ${row("Under Treatment", get("under_treatment"))}
                                                                                                                ${get("under_treatment") === "YES" ? row("Treatment Details", get("treatment_details")) : ""}

                                                                                                                ${row("Hospitalized", get("hospitalized"))}
                                                                                                                ${get("hospitalized") === "YES" ? row("Hospital Details", get("hospital_details")) : ""}
                                                                                                            `)}

    ${subSection("Allergies", `
                                                                                                                ${row("Allergy (Medicine)", get("allergy_medicine"))}
                                                                                                                ${row("Allergy (Food)", get("allergy_food"))}
                                                                                                                ${optionalRow("Allergy (Others)", get("allergy_others"))}
                                                                                                            `)}

    ${subSection("Medications", `
                                                                                                                ${row("Medication", get("medication"))}
                                                                                                                ${get("medication") === "YES" ? row("Medication Details", get("medication_details")) : ""}
                                                                                                            `)}

    ${isFemalePatient ? subSection("For Women Only", `
                                                                                                                ${row("Pregnant", get("pregnant"))}
                                                                                                                ${row("Nursing", get("nursing"))}
                                                                                                                ${row("Birth Control Pills", get("birth_control"))}
                                                                                                            `) : ""}

   ${fullWidthSection(
            "Medical Conditions",
            diseaseTags
        )}

    ${subSection("Tobacco Use", `
                                                                                                                ${row("Tobacco Use", get("tobacco_use"))}
                                                                                                                ${get("tobacco_use") === "YES" ? row("Amount Per Day", get("tobacco_per_day")) : ""}
                                                                                                                ${get("tobacco_use") === "YES" ? row("Amount Per Week", get("tobacco_per_week")) : ""}
                                                                                                            `)}

    ${subSection("Do You Suffer From", `
                                                                                                                ${row("Headaches", get("headaches"))}
                                                                                                                ${row("Earaches", get("earaches"))}
                                                                                                                ${row("Neck Aches", get("neck_aches"))}
                                                                                                            `)}
    `;

            document.getElementById("summaryBox").innerHTML = `
    ${summaryCard("Patient Information", "fa-user", `
                                                                                                                <div class="grid grid-cols-1 gap-y-1">
                                                                                                                    ${row("Name", patientName)}
                                                                                                                    ${row("Gender", patientGender)}
                                                                                                                </div>
                                                                                                            `)}
                                                                    ${summaryCard(
            "Contact Information",
            "fa-address-card",
            `
                                                <div id="contactSummaryView">
                                                    <div class="grid grid-cols-1 gap-y-1">
                                                        ${row("Email", contactEmail)}
                                                        ${row("Phone", contactPhone)}
                                                        ${row("Address", contactAddress)}
                                                    </div>
                                                </div>

                                                <div
                                                    id="contactSummaryEditor"
                                                    class="hidden space-y-4"
                                                >
                                                    <div class="global-form-group" data-global-field>
                                                        <label
                                                            for="contactEditEmail"
                                                            class="global-form-label"
                                                        >
                                                            Email
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <input
                                                            type="email"
                                                            id="contactEditEmail"
                                                            name="contact_edit_email"
                                                            class="form-input-custom"
                                                            required
                                                            data-required-message="Please enter your email address."
                                                            value="${contactEmail === 'N/A' ? '' : contactEmail}"
                                                        >
                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label
                                                            for="contactEditPhone"
                                                            class="global-form-label"
                                                        >
                                                            Phone
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <input
                                                            type="tel"
                                                            id="contactEditPhone"
                                                            name="contact_edit_phone"
                                                            class="form-input-custom"
                                                            required
                                                            data-validation-rule="philippineMobile"
                                                            data-required-message="Please enter your contact number."
                                                            data-pattern-message="Contact number must start with 09 and contain exactly 11 digits."
                                                            maxlength="13"
                                                            inputmode="numeric"
                                                            autocomplete="tel"
                                                            placeholder="0000 000 0000"
                                                            value="${contactPhone === 'N/A' ? '' : contactPhone}"
                                                        >
                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label
                                                            for="contactEditAddress"
                                                            class="global-form-label"
                                                        >
                                                            Address
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <textarea
                                                            id="contactEditAddress"
                                                            name="contact_edit_address"
                                                            class="form-input-custom global-form-textarea"
                                                            required
                                                            data-required-message="Please enter your address."
                                                            rows="3"
                                                        >${contactAddress === 'N/A' ? '' : contactAddress}</textarea>
                                                    </div>

                                                    <div class="flex flex-wrap gap-2">
                                                        <button
                                                            type="button"
                                                            class="ui-btn ui-btn-primary ui-btn-sm"
                                                            onclick="saveGlobalContactInformation()"
                                                        >
                                                            <i class="fa-solid fa-check"></i>
                                                            Save Changes
                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="ui-btn ui-btn-secondary ui-btn-sm"
                                                            onclick="cancelContactInformationEdit()"
                                                        >
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>
                                            `,
            "editContactInformationFromReview()"
        )}

    <div class="grid grid-cols-2 gap-4 sm-grid-1col">
        ${summaryCard("Appointment Details", "fa-calendar-check", `
                                                                                                                <div class="grid grid-cols-1 gap-y-1">
                                                                                                                    ${row("Date", get("appointment_date"))}
                                                                                                                    ${row("Time", get("appointment_time"))}
                                                                                                                </div>
                                                                                                            `)}

        ${summaryCard("Service", "fa-tooth", `
                                                                                                                    <div class="grid grid-cols-1 gap-y-1">
                                                                                                                        ${row("Type", get("service_type"))}
                                                                                                                    </div>
                                                                                                                `)}
    </div>

        ${summaryCard("Dental History", "fa-teeth",
            dentalHistoryBody,
            "editDentalHistoryFromReview()"
        )}

         ${summaryCard("Medical History", "fa-heart-pulse",
            medicalHistoryBody,
            "editMedicalHistoryFromReview()"
        )}

    <div class="grid grid-cols-2 gap-4 sm-grid-1col">
        ${summaryCard("Emergency Contact", "fa-phone", `
                                                                                                                    <div class="grid grid-cols-1 gap-y-1">
                                                                                                                        ${row("Name", get("emergency_person"))}
                                                                                                                        ${row("Number", get("emergency_number"))}
                                                                                                                        ${row("Relation", emergencyRelation)}
                                                                                                                    </div>
                                                                                                                `)}

        ${summaryCard(
            "Signature",
            "fa-signature",
            sigHTML,
            "editSignatureFromReview()"
        )}
    </div>
`;

            window
                .normalizeGlobalPhilippineMobile?.(
                    document.getElementById(
                        'contactEditPhone'
                    )
                );

            document.querySelectorAll(".sm-grid-1col").forEach(el => {
                if (window.innerWidth < 640) el.style.gridTemplateColumns = "1fr";
            });
        }

        function showSlotFullWarning(date) {
            const slotFullModal = document.getElementById("slotFullModal");
            if (!slotFullModal) return;

            const dateDisplay = new Date(date + "T00:00:00").toLocaleDateString("en-US", {
                weekday: "short",
                year: "numeric",
                month: "short",
                day: "numeric"
            });

            const msg = document.getElementById("slotFullMessage");
            if (msg) {
                msg.innerHTML =
                    `<strong>The appointment slots for ${dateDisplay} are fully booked.</strong><br>Please select a different date to continue with your booking.`;
            }

            slotFullModal.showModal();
        }

        const finalConfirm =
            document.getElementById(
                'finalConfirm'
            );

        var html = document.documentElement;
        var themeToggleContainer = document.getElementById("themeToggle");

        function confirmFinalAppointment() {
            if (
                !finalConfirm ||
                !finalConfirm.checked
            ) {
                window
                    .validateFormInputField?.(
                        finalConfirm
                    );

                window
                    .focusGlobalInvalidField?.(
                        finalConfirm
                    );

                return false;
            }

            if (!ensureGlobalContactInformation()) {
                return false;
            }

            const form =
                document.getElementById(
                    'appointmentForm'
                );

            if (!form) {
                return false;
            }

            formSubmitting = true;

            clearTimeout(
                draftAutosaveTimer
            );

            form.submit();

            return true;
        }

        function syncMedicalExamBox() {
            const sel = document.querySelector('input[name="had_medical_exam"]:checked');
            const box = document.getElementById("medical_exam_box");
            const inp = document.getElementById("medicalExamDate");
            if (!sel || !box || !inp) return;
            if (sel.value === "YES") {
                box.classList.remove("hidden");
                inp.required = true;
            } else {
                box.classList.add("hidden");
                inp.required = false;
                inp.value = "";
            }
        }
        document.querySelectorAll('input[name="had_medical_exam"]').forEach(r => r.addEventListener("change",
            syncMedicalExamBox));
        syncMedicalExamBox();

        [{
            name: "good_health",
            boxId: "good_health_box",
            showOn: "NO"
        }, {
            name: "under_treatment",
            boxId: "treatment_box",
            showOn: "YES"
        }, {
            name: "hospitalized",
            boxId: "hospital_box",
            showOn: "YES"
        }, {
            name: "medication",
            boxId: "medication_box",
            showOn: "YES"
        }].forEach(({
            name,
            boxId,
            showOn
        }) => {
            const radios = document.getElementsByName(name);
            const box = document.getElementById(boxId);
            if (!box || !radios.length) return;
            radios.forEach(r => r.addEventListener("change", () => {
                const sel = [...radios].find(x => x.checked);
                const inputs = box.querySelectorAll("input");
                if (sel?.value === showOn) {
                    box.classList.remove("hidden");
                    inputs.forEach(i => i.required = true);
                } else {
                    box.classList.add("hidden");
                    inputs.forEach(i => {
                        i.required = false;
                        i.value = "";
                    });
                }
            }));
        });

        function syncTobaccoDetails() {
            const selected =
                document.querySelector(
                    'input[name="tobacco_use"]:checked'
                );

            const box =
                document.getElementById(
                    'tobacco_details'
                );

            if (!box) {
                return;
            }

            const shouldShow =
                selected?.value === 'YES';

            box.classList.toggle(
                'hidden',
                !shouldShow
            );

            box.querySelectorAll(
                '[data-number-stepper-input]'
            ).forEach(input => {
                if (shouldShow) {
                    if (!input.value) {
                        input.value = '1';
                    }

                    return;
                }

                input.value = '';

                window
                    .clearFormInputValidation?.(
                        input
                    );
            });
        }

        document
            .querySelectorAll(
                'input[name="tobacco_use"]'
            )
            .forEach(radio => {
                radio.addEventListener(
                    'change',
                    syncTobaccoDetails
                );
            });

        syncTobaccoDetails();

        const appointmentForm = document.getElementById("appointmentForm");

        appointmentForm?.addEventListener("input", event => {
            if (
                !event.target.matches(
                    "input, textarea, select"
                )
            ) {
                return;
            }

            formIsDirty = true;

            scheduleDraftAutosave();
        });

        appointmentForm?.addEventListener("change", event => {
            if (
                !event.target.matches(
                    "input, textarea, select"
                )
            ) {
                return;
            }

            formIsDirty = true;

            scheduleDraftAutosave();
        });

        document.addEventListener(
            "visibilitychange",
            () => {
                if (
                    document.visibilityState ===
                    "hidden"
                ) {
                    saveDraftImmediately();
                }
            }
        );

        window.addEventListener(
            "pagehide",
            () => {
                saveDraftImmediately();
            }
        );

        document.getElementById('slotFullOkBtn')?.addEventListener('click', () => {
            const modal = document.getElementById('slotFullModal');
            if (modal) modal.close();
        });

        const clearSlotSelectionBtn = document.getElementById("clearSlotSelectionBtn");

        function clearTimeSelectionOnly() {
            if (
                typeof selectedTime !==
                "undefined"
            ) {
                selectedTime = null;
            }

            const timeInput =
                document.getElementById("appointment_time");

            const selectedSlotDisplay =
                document.getElementById("selectedSlotDisplay");

            const selectedSlotText =
                document.getElementById("selectedSlotText");

            const reservedSlotIdInput =
                document.getElementById("reserved_booking_period_slot_id");

            if (timeInput) {
                timeInput.value = "";

                timeInput.dispatchEvent(
                    new Event("change", {
                        bubbles: true
                    })
                );
            }

            if (selectedSlotText) {
                selectedSlotText.textContent = "";
            }

            if (reservedSlotIdInput) {
                reservedSlotIdInput.value = "";
            }

            selectedSlotDisplay?.classList.add("hidden");
            selectedSlotDisplay?.style.removeProperty("display");
            clearSlotSelectionBtn?.classList.add("hidden");
            clearSlotSelectionBtn?.setAttribute("aria-hidden", "true");
            clearSlotSelectionBtn?.style.removeProperty("display");

            document
                .querySelectorAll(
                    "#slotGrid .slot-chip"
                )
                .forEach(chip => {
                    chip.classList.remove(
                        "selected"
                    );

                    chip.setAttribute(
                        "aria-pressed",
                        "false"
                    );
                });

            markFormDirty();
        }

        function clearDateAndTimeSelection() {
            const dateInput = document.getElementById("appointment_date");
            const dateBanner = document.getElementById("dateBanner");
            const slotContainer = document.getElementById("slotContainer");
            const slotPlaceholder = document.getElementById("slotPlaceholder");
            const slotGrid = document.getElementById("slotGrid");
            const timePanel = document.querySelector(".time-panel");

            if (dateInput) dateInput.value = "";

            clearTimeSelectionOnly();

            if (slotGrid) slotGrid.innerHTML = "";

            dateBanner?.classList.add("hidden");

            slotContainer?.classList.add("hidden");
            slotContainer?.classList.remove("slot-fade-in", "slot-fade-out");

            slotPlaceholder?.classList.remove("hidden", "slot-fade-in", "slot-fade-out");
            slotPlaceholder?.style.removeProperty("display");
            slotPlaceholder?.style.removeProperty("transform");
            slotPlaceholder?.style.removeProperty("opacity");

            timePanel?.classList.add("is-empty");
        }

        clearSlotSelectionBtn?.addEventListener("click", clearTimeSelectionOnly);

        document.getElementById("appointment_date")?.addEventListener("change", function() {
            const slotPlaceholder = document.getElementById("slotPlaceholder");
            const slotContainer = document.getElementById("slotContainer");
            const timePanel = document.querySelector(".time-panel");

            if (!this.value) {
                clearDateAndTimeSelection();
                slotPlaceholder?.classList.remove("hidden");
                slotContainer?.classList.add("hidden");
                timePanel?.classList.add("is-empty");
            } else {
                slotPlaceholder?.classList.add("hidden");
                slotContainer?.classList.remove("hidden");
                timePanel?.classList.remove("is-empty");
                animateSlotsIn();
            }
        });

        const slotContainer = document.getElementById("slotContainer");
        const slotPlaceholder = document.getElementById("slotPlaceholder");
        const timePanel = document.querySelector(".time-panel");

        let slotAnimationLock = false;

        function animateSlotsIn() {
            if (!slotContainer || slotAnimationLock) return;

            slotAnimationLock = true;

            timePanel?.classList.remove("is-empty");

            slotPlaceholder?.classList.add("hidden");
            slotPlaceholder?.style.setProperty("display", "none", "important");

            slotContainer.classList.remove("hidden", "slot-fade-out");
            slotContainer.classList.add("slot-fade-in");

            setTimeout(() => {
                slotContainer.classList.remove("slot-fade-in");
                slotAnimationLock = false;
            }, 320);
        }

        function isHolidaySchedulingNoticeHidden() {
            try {
                const storedValue =
                    Number(
                        localStorage.getItem(
                            HOLIDAY_NOTICE_STORAGE_KEY
                        )
                    );

                if (
                    !Number.isFinite(storedValue) ||
                    storedValue <= Date.now()
                ) {
                    localStorage.removeItem(
                        HOLIDAY_NOTICE_STORAGE_KEY
                    );

                    return false;
                }

                return true;

            } catch {
                return false;
            }
        }

        function openHolidaySchedulingNotice() {
            if (
                isReservedBooking ||
                window.__SESSION_EXPIRED__ ||
                isHolidaySchedulingNoticeHidden()
            ) {
                return;
            }

            const modal =
                document.getElementById(
                    'holidaySchedulingNoticeModal'
                );

            if (
                !modal ||
                modal.classList.contains('open')
            ) {
                return;
            }

            window.openModal?.(
                'holidaySchedulingNoticeModal'
            );
        }

        function closeHolidaySchedulingNotice() {
            const checkbox =
                document.getElementById(
                    'hideHolidayNoticeForSevenDays'
                );

            if (checkbox?.checked) {
                try {
                    localStorage.setItem(
                        HOLIDAY_NOTICE_STORAGE_KEY,
                        String(
                            Date.now() +
                            HOLIDAY_NOTICE_HIDE_DURATION
                        )
                    );
                } catch {
                    /*
                     * Storage failure should never prevent
                     * the modal from closing.
                     */
                }
            }

            window.closeModal?.(
                'holidaySchedulingNoticeModal'
            );
        }

        document.getElementById('holidaySchedulingNoticeDoneBtn')
            ?.addEventListener('click', closeHolidaySchedulingNotice);

        async function initIntroBookingModal() {
            const modal =
                document.getElementById(
                    "introBookingModal"
                );

            const startBtn =
                document.getElementById(
                    "introStartBtn"
                );

            const continueDraftBtn =
                document.getElementById(
                    "introContinueDraftBtn"
                );

            if (!modal) {
                return;
            }

            const serverDraft =
                await getServerDraft();

            const hasDraft =
                Boolean(
                    serverDraft &&
                    hasMeaningfulDraftData(
                        serverDraft.payload
                    )
                );

            if (!hasDraft) {
                clearDraft();
            }

            if (continueDraftBtn) {
                if (hasDraft) {
                    continueDraftBtn
                        .style
                        .removeProperty(
                            "display"
                        );
                } else {
                    continueDraftBtn
                        .style
                        .setProperty(
                            "display",
                            "none"
                        );
                }
            }

            startBtn?.addEventListener("click", () => {
                window.closeModal?.(
                    "introBookingModal"
                );

                window.setTimeout(
                    openHolidaySchedulingNotice,
                    220
                );
            });

            continueDraftBtn
                ?.addEventListener(
                    "click",
                    async () => {
                        const availableDraft =
                            await resolveAvailableDraft();

                        if (!availableDraft) {
                            continueDraftBtn
                                ?.style
                                .setProperty(
                                    "display",
                                    "none"
                                );

                            window.showToast?.({
                                type: "info",
                                title: "No draft found",
                                message: "There is no saved appointment draft to restore.",
                                duration: 3500,
                            });

                            return;
                        }

                        window.closeModal?.(
                            "introBookingModal"
                        );

                        const restored = await restoreDraft();

                        if (restored) {
                            window.showToast?.({
                                type: "success",
                                title: "Draft restored",
                                message: "Your saved appointment information has been restored.",
                                duration: 4000,
                            });
                        }

                        window.setTimeout(openHolidaySchedulingNotice, 220);
                    }
                );

            setTimeout(() => {
                if (
                    window.__SESSION_EXPIRED__ ||
                    modal.classList.contains(
                        "open"
                    )
                ) {
                    return;
                }

                window.openModal?.(
                    "introBookingModal"
                );
            }, 350);
        }

        initIntroBookingModal();
        initializeReservedSlotPicker();

        window.addEventListener("resize", () => {
            document.querySelectorAll(".sm-grid-1col").forEach(el => {
                el.style.gridTemplateColumns = window.innerWidth < 640 ? "1fr" : "1fr 1fr";
            });
        });

        setupCharLimit("additional_concerns", "concernCount", 150, "concernWarning");
        setupCharLimit(
            "good_health_details", "goodHealthCount", 150);
        setupCharLimit("treatment_details", "treatmentCount",
            150);
        setupCharLimit("hospital_details", "hospitalCount", 150);
        setupCharLimit("medication_details",
            "medicationCount", 150);

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".global-question-text").forEach(q => {
                const row = q.closest(".global-question-row");
                const hasRequiredRadio = row?.querySelector("input[required]");

                if (hasRequiredRadio && !q.querySelector(".required-mark")) {
                    const star = document.createElement("span");
                    star.className = "required-mark";
                    star.textContent = " *";
                    q.appendChild(star);
                }
            });

            document.querySelectorAll(
                "input[required], select[required], textarea[required]"
            ).forEach(input => {
                if (
                    input.tagName === "INPUT" &&
                    (
                        input.type === "hidden" ||
                        input.type === "radio" ||
                        input.type === "checkbox"
                    )
                ) {
                    return;
                }

                let label = null;

                if (input.id) {
                    label = document.querySelector(`label[for="${input.id}"]`);
                }

                if (!label) {
                    label = input.closest("label");
                }

                if (!label) {
                    const fieldContainer = input.closest(
                        ".space-y-4 > div, .grid > div, .ml-6, .mt-3, .mt-2, .date-input-wrap, .voice-input-wrap"
                    );
                    label = fieldContainer?.parentElement?.querySelector(":scope > label") || null;
                }

                if (!label) {
                    const previous = input.previousElementSibling;
                    if (previous && previous.tagName === "LABEL") {
                        label = previous;
                    }
                }

                if (label && !label.querySelector(".required-mark")) {
                    const star = document.createElement("span");
                    star.className = "required-mark";
                    star.textContent = " *";
                    label.appendChild(star);
                }


            });
        });
    </script>
@endsection
