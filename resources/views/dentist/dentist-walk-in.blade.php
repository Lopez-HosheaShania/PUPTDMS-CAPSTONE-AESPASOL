@extends('layouts.app')

@section('layout-role', 'dentist')

@section('hide-sidebar')
@endsection

@section('title', 'Walk-in Appointment')

@section('styles')
    @vite('resources/css/pages/dentist/dentist-walk-in.css')
@endsection

@section('content')

    <main id="mainContent" class="booking-page page-enter">
        <div class="booking-page-inner">
            <x-booking.workflow-header :back-url="route('dentist.dentist.appointments')" back-label="Back to Appointments" form-target="#appointmentForm"
                icon="fa-solid fa-person-walking-arrow-right" title="Walk-in Patient Intake"
                subtitle="Register or select the patient, complete the clinical history, and prepare the patient for treatment."
                :steps="['Patient', 'Service', 'Dental History', 'Medical History', 'Start Procedure']" />

            <div class="w-full">

                <div class="booking-workflow-card">

                    <form id="appointmentForm" action="{{ route('dentist.walk-in.start') }}" method="POST"
                        enctype="multipart/form-data" data-global-selects data-global-validation data-discard-form
                        data-discard-title="Discard walk-in intake?"
                        data-discard-subtitle="You have unsaved walk-in information."
                        data-discard-message="Leaving this page will remove the information you entered. Do you want to discard your changes?">

                        <input type="hidden" name="patient_id" id="selectedPatientId">
                        <input type="hidden" name="patient_mode" id="patientMode" value="existing">
                        @csrf

                        <div class="step-content hidden">
                            <div class="booking-step-shell walkin-step1-layout" id="patientAccountSection">
                                <div class="booking-step-header">
                                    <p class="booking-step-eyebrow">
                                        <i class="fa-solid fa-user-plus text-[11px] mr-1"></i>
                                        Step 1 of 5
                                    </p>

                                    <h2 class="booking-step-title">Patient account</h2>

                                    <p class="booking-step-subtitle">
                                        Search an existing student, faculty, or administrative patient, or create a
                                        guest account for walk-in onboarding.
                                    </p>
                                </div>

                                <div class="booking-step-body">
                                    <div class="booking-section-card" data-global-field data-walkin-patient-group>
                                        <p class="booking-section-card-title">
                                            <i class="fa-solid fa-user-magnifying-glass text-xs"></i>
                                            Select or create patient
                                            <span class="booking-section-card-title-line"></span>
                                        </p>

                                        <div class="account-tabs">
                                            <button type="button" class="account-tab active" data-tab="existing">
                                                <i class="fa-solid fa-database text-[13px]"></i>
                                                Existing / Unified DB
                                            </button>

                                            <button type="button" class="account-tab" data-tab="guest">
                                                <i class="fa-solid fa-user-plus text-[13px]"></i>
                                                Guest onboarding
                                            </button>
                                        </div>

                                        <div class="tab-panel active" id="existingPanel">

                                            <div class="patient-picker-toolbar">

                                                <x-search-bar id="patientSearch"
                                                    placeholder="Search by name, ID, or email..."
                                                    callback="handleWalkInPatientSearch" :debounce="350"
                                                    clear-label="Clear patient search" class="patient-picker-search" />

                                            </div>

                                            <x-pagination-bar id="patientPaginationTopBar" info-id="patientEntriesInfo"
                                                pagination-id="patientPaginationTop" position="top" label="patients"
                                                :show-entries="true" page-size-id="patientPageSize"
                                                page-size-callback="handleWalkInPatientPerPageChange" :page-size-value="10"
                                                hidden />

                                            <div id="patientResults" class="mt-5 mb-5" aria-live="polite"></div>

                                            <x-pagination-bar id="patientPaginationBar" info-id="patientEntriesInfoBottom"
                                                pagination-id="patientPagination" position="bottom" label="patients"
                                                hidden />
                                        </div>
                                        <div class="tab-panel" id="guestPanel">

                                            <div class="guest-fields-grid">

                                                <div class="walkin-two-col">

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestFirstName">
                                                            First Name
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <input type="text" id="guestFirstName" name="guest_first_name"
                                                            class="form-input-custom" placeholder="Enter first name"
                                                            autocomplete="given-name" data-validation-rule="guestName"
                                                            data-required-message="Please enter the guest's first name."
                                                            data-pattern-message="Only letters, spaces, apostrophe, period, and hyphen are allowed."
                                                            maxlength="50" disabled>
                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestMiddleName">
                                                            Middle Name
                                                            <span class="field-optional">(Optional)</span>
                                                        </label>

                                                        <input type="text" id="guestMiddleName" name="guest_middle_name"
                                                            class="form-input-custom" placeholder="Enter middle name"
                                                            autocomplete="additional-name" data-validation-rule="guestName"
                                                            data-pattern-message="Only letters, spaces, apostrophe, period, and hyphen are allowed."
                                                            maxlength="50" disabled>
                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestLastName">
                                                            Last Name
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <input type="text" id="guestLastName" name="guest_last_name"
                                                            class="form-input-custom" placeholder="Enter last name"
                                                            autocomplete="family-name" data-validation-rule="guestName"
                                                            data-required-message="Please enter the guest's last name."
                                                            data-pattern-message="Only letters, spaces, apostrophe, period, and hyphen are allowed."
                                                            maxlength="50" disabled>
                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestSuffix">
                                                            Suffix
                                                            <span class="field-optional">(Optional)</span>
                                                        </label>

                                                        <select id="guestSuffix" name="guest_suffix"
                                                            class="form-select-custom js-custom-select"
                                                            data-placeholder="Select suffix" disabled>
                                                            <option value="">Select suffix</option>
                                                            <option value="Jr.">Jr.</option>
                                                            <option value="Sr.">Sr.</option>
                                                            <option value="II">II</option>
                                                            <option value="III">III</option>
                                                            <option value="IV">IV</option>
                                                            <option value="V">V</option>
                                                        </select>
                                                    </div>

                                                </div>

                                                <div class="walkin-two-col">

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestEmail">
                                                            Email
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <input type="email" id="guestEmail" name="guest_email"
                                                            class="form-input-custom" placeholder="Enter email address"
                                                            autocomplete="email"
                                                            data-required-message="Please enter the guest's email address."
                                                            disabled>
                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestPhone">
                                                            Contact Number
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <input type="tel" id="guestPhone" name="guest_phone"
                                                            class="form-input-custom" placeholder="0000 000 0000"
                                                            autocomplete="tel" inputmode="numeric"
                                                            data-validation-rule="philippineMobile" maxlength="13"
                                                            data-required-message="Please enter the guest's contact number."
                                                            data-pattern-message="Contact number must start with 09 and contain exactly 11 digits."
                                                            disabled>
                                                    </div>

                                                </div>

                                                <div class="walkin-two-col">

                                                    <div class="space-y-4">

                                                        <div class="global-form-group" data-global-field>
                                                            <label class="global-form-label" for="guestPatientType">
                                                                Patient Type
                                                                <span class="required-mark">*</span>
                                                            </label>

                                                            <select id="guestPatientType" name="guest_patient_type"
                                                                class="form-select-custom js-custom-select" disabled
                                                                required
                                                                data-required-message="Please select the guest's patient type.">
                                                                <option value="">Select patient type</option>
                                                                <option value="student">Student</option>
                                                                <option value="faculty">Faculty</option>
                                                                <option value="alumni">Alumni</option>
                                                                <option value="dependent">Dependent</option>
                                                                <option value="administrative">Administrative Personnel
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <div class="global-form-group" data-global-field
                                                            data-guest-student-number-field hidden>
                                                            <label class="global-form-label" for="guestStudentNumber">
                                                                Student Number
                                                                <span class="required-mark">*</span>
                                                            </label>

                                                            <input type="text" id="guestStudentNumber"
                                                                name="guest_student_number" class="form-input-custom"
                                                                placeholder="0000-00000-TG-0" inputmode="text"
                                                                autocomplete="off" data-validation-rule="studentNumber"
                                                                data-required-message="Please enter the guest's student number."
                                                                maxlength="15" disabled>
                                                        </div>

                                                        <div class="global-form-group" data-global-field
                                                            data-guest-faculty-code-field hidden>
                                                            <label class="global-form-label" for="guestFacultyCode">
                                                                Faculty Code
                                                                <span class="required-mark">*</span>
                                                            </label>

                                                            <input type="text" id="guestFacultyCode"
                                                                name="guest_faculty_code" class="form-input-custom"
                                                                placeholder="FA0000TG0000" inputmode="text"
                                                                autocomplete="off" data-validation-rule="facultyCode"
                                                                data-required-message="Please enter the guest's faculty code."
                                                                maxlength="12" disabled>
                                                        </div>

                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestGender">
                                                            Gender
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <select id="guestGender" name="guest_gender"
                                                            class="form-select-custom js-custom-select" disabled
                                                            data-required-message="Please select the guest's gender.">
                                                            <option value="">Select gender</option>
                                                            <option value="Male">Male</option>
                                                            <option value="Female">Female</option>
                                                        </select>
                                                    </div>

                                                </div>

                                                <div class="walkin-two-col">

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestBirthdate">
                                                            Birthday
                                                            <span class="required-mark">*</span>
                                                        </label>

                                                        <input type="text" id="guestBirthdate" name="guest_birthdate"
                                                            class="form-input-custom js-flatpickr-date"
                                                            placeholder="Select birthday"
                                                            max="{{ now()->subDay()->format('Y-m-d') }}"
                                                            data-flatpickr-min-year="1900"
                                                            data-validation-rule="notFutureDate"
                                                            data-required-message="Please select the guest's birthday."
                                                            autocomplete="off" disabled>
                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestProgram">
                                                            Program
                                                            <span class="field-optional">(Optional)</span>
                                                        </label>

                                                        <input type="text" id="guestProgram" name="guest_program"
                                                            class="form-input-custom" placeholder="Enter program"
                                                            maxlength="100" disabled>
                                                    </div>

                                                </div>

                                                <div class="walkin-two-col">

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestYearLevel">
                                                            Year Level
                                                            <span class="field-optional">(Optional)</span>
                                                        </label>

                                                        <input type="text" id="guestYearLevel" name="guest_year_level"
                                                            class="form-input-custom" placeholder="e.g. 4"
                                                            data-validation-rule="yearLevel"
                                                            data-pattern-message="Please enter a valid year level using whole numbers only."
                                                            maxlength="2" disabled>
                                                    </div>

                                                    <div class="global-form-group" data-global-field>
                                                        <label class="global-form-label" for="guestSection">
                                                            Section
                                                            <span class="field-optional">(Optional)</span>
                                                        </label>

                                                        <input type="text" id="guestSection" name="guest_section"
                                                            class="form-input-custom" placeholder="e.g. BSIT 4-1"
                                                            data-validation-rule="sectionCode"
                                                            data-pattern-message="Section can only contain letters, numbers, spaces, periods, and hyphens."
                                                            maxlength="30" disabled>
                                                    </div>

                                                </div>

                                                <div class="global-form-group" data-global-field>
                                                    <label class="global-form-label">
                                                        Is the patient a Person with Disability (PWD)?
                                                        <span class="required-mark">*</span>
                                                    </label>

                                                    <div class="flex gap-4">
                                                        <label class="global-radio-option">
                                                            <input type="radio" name="guest_is_pwd" value="1"
                                                                class="global-radio-input"
                                                                data-required-message="Please select whether the guest patient is a PWD."
                                                                disabled>
                                                            <span>Yes</span>
                                                        </label>

                                                        <label class="global-radio-option">
                                                            <input type="radio" name="guest_is_pwd" value="0"
                                                                class="global-radio-input"
                                                                data-required-message="Please select whether the guest patient is a PWD."
                                                                disabled>
                                                            <span>No</span>
                                                        </label>
                                                    </div>
                                                </div>

                                            </div>

                                            <button type="button" id="createGuestBtn" class="ui-btn ui-btn-primary">
                                                <i class="fa-solid fa-user-plus"></i>
                                                Create guest account
                                            </button>

                                        </div>
                                    </div>


                                    <div class="selected-patient-box" id="selectedPatientBox" hidden>

                                        <span>
                                            <i class="fa-solid fa-circle-check text-[11px] mr-1"></i>
                                            Selected patient
                                        </span>

                                        <strong id="selectedPatientName" data-patient-name></strong>

                                        <small id="selectedPatientMeta"></small>

                                        <button type="button" id="clearSelectedPatientBtn"
                                            class="ui-btn ui-btn-secondary ui-btn-sm">

                                            <span>Clear Selection</span>

                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <x-booking.service-step :services="$serviceTypes" title="Choose Your Dental Service"
                            subtitle="Select the type of service for this walk-in appointment." />

                        <x-booking.dental-history :questions="$dentalQuestions" mode="flat" :defaults="[]"
                            subtitle="Record the patient's dental history before starting the procedure." />

                        <div class="step-content hidden">
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
                                    <x-booking.medical-history-fields :questions="$medicalQuestions" :diseases="$diseases" mode="standard"
                                        :defaults="[]" :selected-diseases="old('diseases', [])" :dynamic-female="true" />

                                    <x-booking.signature mode="draw-only" label="Patient's Signature"
                                        draw-title="Draw the patient's signature here"
                                        draw-help="Use the drawing tablet, mouse, touch, or stylus." />
                                </div>
                            </div>
                        </div>

                        <div class="step-content hidden">
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
                                            message="By starting the procedure, you confirm that the patient's dental and medical information has been reviewed and is accurate.">
                                            I have reviewed the patient's dental and medical
                                            information and confirm that it is accurate
                                            before beginning treatment.
                                        </x-booking.final-confirmation>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <x-booking.navigation />
                </div>
            </div>
        </div>
    </main>

    <div id="introBookingModal" class="ui-modal" aria-hidden="true">
        <div class="ui-modal-card modal-lg">
            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-person-walking-arrow-right"></i>
                    </div>

                    <div class="modal-copy">
                        <h2 class="modal-title">
                            Prepare the walk-in patient for treatment
                        </h2>

                        <p class="modal-subtitle">
                            Complete the patient intake, service selection,
                            clinical history, and signature before starting
                            the procedure.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-bd">
                <div class="booking-intro-steps">
                    <div class="booking-intro-step">
                        <strong>1</strong>
                        <span>Patient</span>
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
                        <span>Start</span>
                    </div>
                </div>

                <div class="booking-intro-checklist">

                    <div class="booking-intro-item">
                        <div class="global-icon-box global-icon-box-sm">
                            <i class="fa-solid fa-check"></i>
                        </div>

                        <p>
                            Select an existing patient or create a guest
                            record for today's walk-in visit.
                        </p>
                    </div>

                    <div class="booking-intro-item">
                        <div class="global-icon-box global-icon-box-sm">
                            <i class="fa-solid fa-tooth"></i>
                        </div>

                        <p>
                            Choose the requested dental service and complete
                            the patient's dental and medical history.
                        </p>
                    </div>

                    <div class="booking-intro-item">
                        <div class="global-icon-box global-icon-box-sm">
                            <i class="fa-solid fa-signature"></i>
                        </div>

                        <p>
                            Ask the patient to review the information and
                            provide a drawn signature.
                        </p>
                    </div>

                    <div class="booking-intro-item">
                        <div class="global-icon-box global-icon-box-sm">
                            <i class="fa-solid fa-list-check"></i>
                        </div>

                        <p>
                            Verify the intake summary before starting the
                            dental procedure.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <a href="{{ route('dentist.dentist.appointments') }}" class="ui-btn ui-btn-secondary ui-btn-sm"
                    data-discard-navigation data-discard-form-target="#appointmentForm">
                    Back to Appointments
                </a>

                <button type="button" id="introStartBtn" class="ui-btn ui-btn-primary ui-btn-sm">
                    Begin Walk-in Intake
                </button>
            </div>
        </div>
    </div>

    <x-booking.confirmed-modal id="confirmModal" eyebrow="Walk-in Appointment" title="Appointment Confirmed"
        subtitle="The walk-in intake has been saved successfully." header-icon="fa-check" section-icon="fa-stethoscope"
        section-eyebrow="Treatment Status" section-title="Ready to begin treatment"
        section-message="The patient intake is complete and the dental procedure can now be started."
        detail-label="Intake Status" result-title="Recorded" message-title="Procedure details"
        message-id="confirmMessage">
        The walk-in appointment was recorded successfully.

        <x-slot:footer>

            <button type="button" id="okBtn" class="ui-btn ui-btn-primary">
                <i class="fa-solid fa-play"></i>
                Start Procedure
            </button>

        </x-slot:footer>

    </x-booking.confirmed-modal>
@endsection

@section('scripts')

    <script>
        const diseaseLabelByCode = @json($diseases->pluck('label', 'code'));

        let selectedWalkInPatient = null;
        let selectedPatientBookingInformation = null;
        let patientHasExistingBookingInformation = false;
        let patientHasAnyAutofillData = false;
        let patientHasReusableSignature = false;
        let patientExistingSignatureUrl = '';
        let formIsDirty = false;
        let formSubmitting = false;
        let pendingNavigation = null;

        const accountTabs = document.querySelectorAll(".account-tab");
        const existingPanel = document.getElementById("existingPanel");
        const guestPanel = document.getElementById("guestPanel");

        const patientSearch = document.getElementById("patientSearch");
        const patientResults = document.getElementById("patientResults");

        const selectedPatientId = document.getElementById("selectedPatientId");
        const patientModeInput = document.getElementById("patientMode");
        const selectedPatientBox = document.getElementById("selectedPatientBox");
        const selectedPatientName = document.getElementById("selectedPatientName");
        const selectedPatientMeta = document.getElementById("selectedPatientMeta");
        const clearSelectedPatientBtn = document.getElementById("clearSelectedPatientBtn");
        const createGuestBtn = document.getElementById("createGuestBtn");

        const guestFirstName =
            document.getElementById("guestFirstName");

        const guestMiddleName =
            document.getElementById("guestMiddleName");

        const guestLastName =
            document.getElementById("guestLastName");

        const guestSuffix =
            document.getElementById("guestSuffix");

        const guestEmail =
            document.getElementById("guestEmail");

        const guestPhone =
            document.getElementById("guestPhone");

        const guestPatientType =
            document.getElementById("guestPatientType");

        const guestGender =
            document.getElementById("guestGender");

        const guestBirthdate =
            document.getElementById("guestBirthdate");

        const guestProgram =
            document.getElementById("guestProgram");

        const guestStudentNumber =
            document.getElementById("guestStudentNumber");

        const guestFacultyCode =
            document.getElementById("guestFacultyCode");

        function formatGuestStudentNumber(value = '') {
            const raw =
                String(value)
                .toUpperCase()
                .replace(/[^0-9A-Z]/g, '');

            const digits =
                raw
                .replace(/TG/g, '')
                .replace(/\D/g, '')
                .slice(0, 10);

            let formatted =
                digits.slice(0, 4);

            if (digits.length > 4) {
                formatted +=
                    '-' +
                    digits.slice(4, 9);
            }

            if (digits.length > 9) {
                formatted +=
                    '-TG-' +
                    digits.slice(9, 10);
            } else if (
                digits.length === 9
            ) {
                formatted +=
                    '-TG-';
            }

            return formatted;
        }

        function formatGuestFacultyCode(value = '') {
            let raw =
                String(value)
                .toUpperCase()
                .replace(
                    /[^A-Z0-9]/g,
                    ''
                );

            if (
                !raw.startsWith('FA')
            ) {
                raw =
                    raw.replace(
                        /^FA/,
                        ''
                    );

                raw =
                    'FA' +
                    raw.replace(
                        /^[A-Z]+/,
                        ''
                    );
            }

            const digits =
                raw
                .slice(2)
                .replace(/TG/g, '')
                .replace(/\D/g, '')
                .slice(0, 8);

            let formatted =
                'FA' +
                digits.slice(0, 4);

            if (digits.length >= 4) {
                formatted +=
                    'TG' +
                    digits.slice(4, 8);
            }

            return formatted;
        }

        guestFacultyCode?.addEventListener('input', () => {
            const formatted =
                formatGuestFacultyCode(
                    guestFacultyCode.value
                );

            if (
                guestFacultyCode.value !==
                formatted
            ) {
                guestFacultyCode.value =
                    formatted;
            }
        });

        guestStudentNumber?.addEventListener('input', () => {
            const formatted =
                formatGuestStudentNumber(
                    guestStudentNumber.value
                );

            if (
                guestStudentNumber.value !==
                formatted
            ) {
                guestStudentNumber.value =
                    formatted;
            }
        });

        const guestYearLevel = document.getElementById("guestYearLevel");
        const guestSection = document.getElementById("guestSection");
        const guestStudentNumberField = document.querySelector("[data-guest-student-number-field]");

        const guestFacultyCodeField = document.querySelector("[data-guest-faculty-code-field]");
        const guestPwdRadios = document.querySelectorAll('input[name="guest_is_pwd"]');

        const walkInPatientGroup = document.querySelector('[data-walkin-patient-group]');
        const emergencyPersonField = document.getElementById("emergency_person");
        const emergencyNumberField = document.getElementById("emergency_number");
        const emergencyRelationField = document.getElementById("emergency_relation");

        function setEmergencyContactFieldLockState({
            lockPerson = false,
            lockNumber = false,
            lockRelation = false,
        } = {}) {
            if (emergencyPersonField) {
                emergencyPersonField.readOnly = lockPerson;
                emergencyPersonField.dataset.autofilled = lockPerson ? "1" : "0";
            }

            if (emergencyNumberField) {
                emergencyNumberField.readOnly = lockNumber;
                emergencyNumberField.dataset.autofilled = lockNumber ? "1" : "0";
            }

            if (emergencyRelationField) {
                emergencyRelationField.dataset.autofilled = lockRelation ? "1" : "0";

                const relationWrapper =
                    emergencyRelationField.closest(
                        ".custom-select"
                    );

                relationWrapper?.classList.toggle(
                    "is-autofilled",
                    lockRelation
                );

                window.syncCustomSelect?.(
                    relationWrapper
                );
            }
        }

        function setGuestFieldsEnabled(enabled) {
            const guestFields = [
                guestFirstName,
                guestMiddleName,
                guestLastName,
                guestSuffix,
                guestEmail,
                guestPhone,
                guestPatientType,
                guestGender,
                guestBirthdate,
                guestProgram,
                guestStudentNumber,
                guestFacultyCode,
                guestYearLevel,
                guestSection,
            ];

            guestFields.forEach(input => {
                if (!input) return;

                input.disabled = !enabled;

                if (!enabled) {
                    input.required = false;
                    input.value = "";

                    window.clearFormInputValidation?.(
                        input
                    );
                }
            });

            guestPwdRadios.forEach(radio => {
                radio.disabled = !enabled;

                if (!enabled) {
                    radio.required = false;
                    radio.checked = false;

                    window.clearFormInputValidation?.(
                        radio
                    );
                }
            });

            if (guestFirstName) {
                guestFirstName.required = enabled;
            }

            if (guestMiddleName) {
                guestMiddleName.required = false;
            }

            if (guestLastName) {
                guestLastName.required = enabled;
            }

            if (guestSuffix) {
                guestSuffix.required = false;
            }

            if (guestEmail) {
                guestEmail.required = enabled;
            }

            if (guestPhone) {
                guestPhone.required = enabled;
            }

            if (guestPatientType) {
                guestPatientType.required = enabled;
            }

            if (guestGender) {
                guestGender.required = enabled;
            }

            if (guestBirthdate) {
                guestBirthdate.required = enabled;
            }

            updateGuestIdentityFields();

            guestPwdRadios.forEach((radio, index) => {
                radio.required =
                    enabled &&
                    index === 0;
            });

            [
                guestSuffix,
                guestPatientType,
                guestGender,
            ].forEach(selectField => {
                if (!selectField) {
                    return;
                }

                window.initCustomSelects?.(
                    guestPanel
                );

                const selectWrapper =
                    selectField.closest(
                        ".custom-select"
                    );

                if (selectWrapper) {
                    window.syncCustomSelect?.(
                        selectWrapper
                    );
                }
            });

            if (
                guestBirthdate?._flatpickr
            ) {
                guestBirthdate._flatpickr.redraw();
            }
        }

        [
            guestFirstName,
            guestMiddleName,
            guestLastName,
            guestSuffix,
            guestEmail,
            guestPhone,
            guestPatientType,
            guestGender,
            guestBirthdate,
            guestProgram,
            guestStudentNumber,
            guestFacultyCode,
            guestYearLevel,
            guestSection,
        ].forEach(input => {
            if (!input) return;

            const eventName =
                input.tagName === "SELECT" ||
                input.type === "date" ?
                "change" :
                "input";

            input.addEventListener(eventName, () => {
                window.validateFormInputField?.(input);

                if (
                    patientModeInput?.value === "guest" &&
                    selectedPatientId?.value
                ) {
                    clearSelectedPatientUI({
                        clearSearch: false,
                        reloadPatients: false,
                    });
                }
            });
        });

        function updateGuestIdentityFields() {
            const selectedType =
                guestPatientType?.value || "";

            const isStudent =
                selectedType === "student";

            const isFaculty =
                selectedType === "faculty";

            if (guestStudentNumberField) {
                guestStudentNumberField.hidden = !isStudent;
            }

            if (guestFacultyCodeField) {
                guestFacultyCodeField.hidden = !isFaculty;
            }

            if (guestStudentNumber) {
                guestStudentNumber.disabled =
                    patientModeInput?.value !== "guest" ||
                    !isStudent;

                guestStudentNumber.required =
                    patientModeInput?.value === "guest" &&
                    isStudent;

                if (!isStudent) {
                    guestStudentNumber.value = "";
                    window.clearFormInputValidation?.(
                        guestStudentNumber
                    );
                }
            }

            if (guestFacultyCode) {
                guestFacultyCode.disabled =
                    patientModeInput?.value !== "guest" ||
                    !isFaculty;

                guestFacultyCode.required =
                    patientModeInput?.value === "guest" &&
                    isFaculty;

                if (!isFaculty) {
                    guestFacultyCode.value = "";
                    window.clearFormInputValidation?.(
                        guestFacultyCode
                    );
                }
            }
        }

        guestPwdRadios.forEach(radio => {
            radio.addEventListener("change", () => {
                if (
                    patientModeInput?.value === "guest" &&
                    selectedPatientId?.value
                ) {
                    clearSelectedPatientUI({
                        clearSearch: false,
                        reloadPatients: false,
                    });
                }
            });
        });

        guestPatientType?.addEventListener(
            "change",
            updateGuestIdentityFields
        );

        const forWomenSection =
            document.getElementById(
                "forWomenSection"
            );

        const nonFemaleDefaults =
            document.getElementById(
                "nonFemaleDefaults"
            );

        const womenFieldNames = [
            "pregnant",
            "nursing",
            "birth_control",
        ];

        function normalizePatientGender(
            gender
        ) {
            return String(gender || "")
                .trim()
                .toLowerCase();
        }

        function isFemaleGender(
            gender
        ) {
            const normalizedGender =
                normalizePatientGender(
                    gender
                );

            return [
                "female",
                "f",
                "woman",
            ].includes(
                normalizedGender
            );
        }

        function updateWomenSection(
            gender
        ) {
            const showWomenSection =
                isFemaleGender(
                    gender
                );

            forWomenSection
                ?.classList.toggle(
                    "hidden",
                    !showWomenSection
                );

            if (nonFemaleDefaults) {
                nonFemaleDefaults.hidden =
                    showWomenSection;

                nonFemaleDefaults
                    .querySelectorAll("input")
                    .forEach(input => {
                        input.disabled =
                            showWomenSection;
                    });
            }

            womenFieldNames.forEach(name => {
                const radios =
                    document.querySelectorAll(
                        `input[type="radio"][name="${name}"]`
                    );

                radios.forEach((radio, index) => {
                    radio.disabled = !showWomenSection;

                    radio.required =
                        showWomenSection &&
                        index === 0;

                    if (!showWomenSection) {
                        radio.checked = false;

                        window
                            .clearFormInputValidation?.(
                                radio
                            );
                    }
                });
            });
        }

        function clearSelectedPatientUI({
            clearSearch = true,
            reloadPatients = true,
            markDirty = true,
        } = {}) {

            selectedWalkInPatient = null;

            selectedPatientBookingInformation =
                null;

            patientHasExistingBookingInformation =
                false;

            patientHasAnyAutofillData =
                false;

            patientHasReusableSignature =
                false;

            patientExistingSignatureUrl = '';
            window.BookingSignature
                ?.get(document)
                ?.setExistingSignature({
                    reusable: false,
                    url: '',
                });
            setEmergencyContactFieldLockState();

            updateWomenSection(null);

            if (selectedPatientId) {
                selectedPatientId.value = "";
            }

            if (selectedPatientName) {
                selectedPatientName.textContent = "";
            }

            if (selectedPatientMeta) {
                selectedPatientMeta.textContent = "";
            }

            selectedPatientBox?.setAttribute(
                "hidden",
                "hidden"
            );

            document
                .querySelectorAll(
                    ".patient-record-card"
                )
                .forEach(card => {
                    card.classList.remove(
                        "is-selected"
                    );

                    const checkbox =
                        card.querySelector(
                            ".patient-card-checkbox"
                        );

                    if (checkbox) {
                        checkbox.checked = false;
                    }
                });

            if (clearSearch && patientSearch) {
                patientSearch.value = "";

                window.syncInputClearButton?.(
                    patientSearch
                );
            }

            window.clearGlobalGroupError?.(
                document.querySelector(
                    "[data-walkin-patient-group]"
                ),
                "walkin-patient"
            );

            if (reloadPatients) {
                patientCurrentPage = 1;

                loadPatients("", true);
            }

            if (markDirty) {
                markFormDirty();
            }
        }

        clearSelectedPatientBtn
            ?.addEventListener(
                "click",
                () => {
                    clearSelectedPatientUI({
                        clearSearch: false,
                        reloadPatients: false,
                    });

                    patientSearch?.focus();
                }
            );

        function setPatientMode(mode, shouldClearSelected = false) {
            const normalizedMode = mode === "guest" ? "guest" : "existing";
            const isGuest = normalizedMode === "guest";

            if (patientModeInput) {
                patientModeInput.value = normalizedMode;
            }

            accountTabs.forEach(item => {
                item.classList.toggle("active", item.dataset.tab === normalizedMode);
            });

            existingPanel?.classList.toggle("active", !isGuest);
            guestPanel?.classList.toggle("active", isGuest);

            setGuestFieldsEnabled(isGuest);
            setEmergencyContactFieldLockState();

            if (isGuest && shouldClearSelected) {
                clearSelectedPatientUI({
                    clearSearch: false,
                    reloadPatients: false,
                });
            }

            if (!isGuest) {
                [
                    guestFirstName,
                    guestMiddleName,
                    guestLastName,
                    guestSuffix,
                    guestEmail,
                    guestPhone,
                    guestPatientType,
                    guestGender,
                    guestBirthdate,
                    guestProgram,
                    guestStudentNumber,
                    guestFacultyCode,
                    guestYearLevel,
                    guestSection,
                ].forEach(input => {
                    if (input) {
                        input.value = "";
                    }
                });

                guestPwdRadios.forEach(radio => {
                    radio.checked = false;
                });

                [
                    guestSuffix,
                    guestPatientType,
                    guestGender,
                ].forEach(selectField => {
                    const selectWrapper =
                        selectField?.closest(
                            ".custom-select"
                        );

                    if (selectWrapper) {
                        window.syncCustomSelect?.(
                            selectWrapper
                        );
                    }
                });

                updateGuestIdentityFields();
            }
        }

        function setWalkInFormValue(name, value) {
            const fields =
                document.querySelectorAll(
                    `[name="${CSS.escape(name)}"]`
                );

            fields.forEach(field => {
                if (
                    field.type === "radio"
                ) {
                    field.checked =
                        String(field.value) ===
                        String(value);

                    return;
                }

                if (
                    field.type === "checkbox"
                ) {
                    field.checked =
                        Boolean(value);

                    return;
                }

                field.value =
                    value ?? "";

                window
                    .normalizeGlobalPhilippineMobile?.(
                        field
                    );

                if (
                    field._flatpickr &&
                    value
                ) {
                    field._flatpickr.setDate(
                        value,
                        false
                    );
                }

                if (
                    field.tagName === "SELECT"
                ) {
                    const wrapper =
                        field.closest(
                            ".custom-select"
                        );

                    if (wrapper) {
                        window.syncCustomSelect?.(
                            wrapper
                        );
                    }
                }
            });
        }

        function applyExistingPatientBookingInformation(data) {
            if (!data) {
                return;
            }

            if (data.last_service_type) {
                setWalkInFormValue(
                    'service_type',
                    data.last_service_type
                );
            }

            Object.entries(
                data.dental || {}
            ).forEach(
                ([name, value]) => {
                    setWalkInFormValue(
                        name,
                        value
                    );
                }
            );

            Object.entries(
                data.medical || {}
            ).forEach(
                ([name, value]) => {
                    setWalkInFormValue(
                        name,
                        value
                    );
                }
            );

            const selectedDiseaseCodes =
                new Set(
                    data.diseases || []
                );

            document
                .querySelectorAll(
                    'input[name="diseases[]"]'
                )
                .forEach(input => {
                    input.checked =
                        selectedDiseaseCodes.has(
                            input.value
                        );
                });

            syncMedicalExamBox();

            [{
                    name: "good_health",
                    boxId: "good_health_box",
                    showOn: "NO",
                },
                {
                    name: "under_treatment",
                    boxId: "treatment_box",
                    showOn: "YES",
                },
                {
                    name: "hospitalized",
                    boxId: "hospital_box",
                    showOn: "YES",
                },
                {
                    name: "medication",
                    boxId: "medication_box",
                    showOn: "YES",
                },
            ].forEach(({
                name,
                boxId,
                showOn,
            }) => {
                const selected =
                    document.querySelector(
                        `input[name="${name}"]:checked`
                    );

                const box =
                    document.getElementById(
                        boxId
                    );

                if (!box) {
                    return;
                }

                box.classList.toggle(
                    "hidden",
                    selected?.value !== showOn
                );
            });

            syncTobaccoDetails();

            updateWomenSection(
                selectedWalkInPatient?.gender
            );

            if (
                selectedWalkInPatient &&
                data.contact
            ) {
                selectedWalkInPatient.email =
                    data.contact.email ||
                    selectedWalkInPatient.email ||
                    "";

                selectedWalkInPatient.phone =
                    data.contact.phone ||
                    selectedWalkInPatient.phone ||
                    "";

                selectedWalkInPatient.address =
                    data.contact.address || "";
            }

            setEmergencyContactFieldLockState({
                lockPerson: Boolean(
                    patientModeInput?.value === "existing" &&
                    String(data?.medical?.emergency_person || "").trim()
                ),
                lockNumber: Boolean(
                    patientModeInput?.value === "existing" &&
                    String(data?.medical?.emergency_number || "").trim()
                ),
                lockRelation: Boolean(
                    patientModeInput?.value === "existing" &&
                    String(data?.medical?.emergency_relation || "").trim()
                ),
            });
        }

        async function loadPatientBookingInformation(
            patientId
        ) {
            if (!patientId) {
                return null;
            }

            try {
                const url =
                    `{{ url('/dentist/walk-in/patients') }}/${patientId}/booking-information`;

                const response =
                    await fetch(url, {
                        headers: {
                            Accept: "application/json",

                            "X-Requested-With": "XMLHttpRequest",
                        },
                    });

                const data =
                    await response
                    .json()
                    .catch(
                        () => ({})
                    );

                if (
                    !response.ok ||
                    !data?.success
                ) {
                    throw new Error(
                        data?.message ||
                        "Unable to load the patient's saved information."
                    );
                }

                selectedPatientBookingInformation =
                    data;

                patientHasExistingBookingInformation =
                    Boolean(
                        data.has_existing_booking_information
                    );

                patientHasAnyAutofillData =
                    Boolean(
                        data.has_autofill_data ??
                        data.has_existing_booking_information
                    );

                patientHasReusableSignature =
                    Boolean(
                        data.has_reusable_signature
                    );

                patientExistingSignatureUrl =
                    data.existing_signature_url || '';

                window.BookingSignature
                    ?.get(document)
                    ?.setExistingSignature({
                        reusable: patientHasReusableSignature,

                        url: patientExistingSignatureUrl,
                    });

                applyExistingPatientBookingInformation(
                    data
                );

                return data;
            } catch (error) {
                console.error(
                    "Walk-in history loading error:",
                    error
                );

                selectedPatientBookingInformation =
                    null;

                patientHasExistingBookingInformation =
                    false;

                patientHasAnyAutofillData =
                    false;

                patientHasReusableSignature =
                    false;

                window.showToast?.({
                    type: 'error',
                    title: 'Unable to Load Information',
                    message: error.message ||
                        'Unable to load saved patient information.',
                });

                return null;
            }
        }

        async function selectWalkInPatient(patient) {
            let resolvedPatient = patient;

            if (
                patient?.is_local === false ||
                String(patient?.id || '').startsWith('external:')
            ) {
                if (!patient?.selection_token) {
                    window.showToast?.({
                        type: 'error',
                        title: 'Unable to Select Patient',
                        message: 'The external patient could not be verified. Please search for the patient again.',
                    });

                    return;
                }

                try {
                    const response = await fetch(
                        `{{ url('/dentist/walk-in/resolve-external-patient') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': @json(csrf_token()),
                            },
                            body: JSON.stringify({
                                selection_token: patient.selection_token,
                            }),
                        }
                    );

                    const data = await response.json();

                    if (
                        !response.ok ||
                        !data.success ||
                        !data.patient
                    ) {
                        throw new Error(
                            data.message ||
                            'Unable to resolve the selected external patient.'
                        );
                    }

                    resolvedPatient = data.patient;
                } catch (error) {
                    console.error(
                        'Walk-in external patient resolution error:',
                        error
                    );

                    window.showToast?.({
                        type: 'error',
                        title: 'Unable to Load Information',
                        message: error.message ||
                            'Unable to prepare the selected external patient.',
                    });

                    return;
                }
            }

            patient = resolvedPatient;

            selectedWalkInPatient = {
                ...patient,
                mode: "existing",
            };

            updateWomenSection(
                patient.gender
            );

            setPatientMode("existing", false);

            if (selectedPatientId) {
                selectedPatientId.value = patient.id;
            }

            if (selectedPatientName) {
                selectedPatientName.textContent =
                    window.formatPatientName?.(
                        patient.name || "Unnamed Patient"
                    ) ||
                    patient.name ||
                    "Unnamed Patient";
            }

            if (selectedPatientMeta) {
                selectedPatientMeta.textContent =
                    `${patient.type || "Patient"}${patient.email ? " - " + patient.email : ""}`;
            }

            selectedPatientBox?.removeAttribute("hidden");
            [
                guestFirstName,
                guestMiddleName,
                guestLastName,
                guestSuffix,
                guestEmail,
                guestPhone,
                guestGender,
                guestBirthdate,
                guestProgram,
                guestStudentNumber,
                guestFacultyCode,
                guestYearLevel,
                guestSection,
            ].forEach(input => {
                if (input) {
                    input.value = "";
                }
            });

            guestPwdRadios.forEach(radio => {
                radio.checked = false;
            });

            markFormDirty();

            window.clearGlobalGroupError?.(
                document.querySelector(
                    "[data-walkin-patient-group]"
                ),
                "walkin-patient"
            );

            requestAnimationFrame(() => {
                selectedPatientBox?.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
            });

            await loadPatientBookingInformation(
                patient.id
            );

            if (
                patientHasAnyAutofillData
            ) {
                window.showToast?.({
                    type: 'success',
                    title: 'Patient Information Loaded',
                    message: patientHasExistingBookingInformation ?
                        'The patient\'s saved dental and medical history were reused in the walk-in form.' :
                        'Available patient information was loaded.',
                });
            }

        }

        function validateGuestOnboardingFields() {
            const guestValidationFields = [
                guestFirstName,
                guestMiddleName,
                guestLastName,
                guestEmail,
                guestPhone,
                guestPatientType,
                guestGender,
                guestBirthdate,
                guestProgram,
                guestYearLevel,
                guestSection,
            ].filter(Boolean);

            if (guestPatientType?.value === "student" && guestStudentNumber) {
                guestValidationFields.push(guestStudentNumber);
            }

            if (guestPatientType?.value === "faculty" && guestFacultyCode) {
                guestValidationFields.push(guestFacultyCode);
            }

            let firstInvalid = null;

            guestValidationFields.forEach(field => {
                const isValid =
                    window.validateFormInputField?.(field) ?? true;

                if (!isValid && !firstInvalid) {
                    firstInvalid = field;
                }
            });

            const firstPwdRadio =
                guestPwdRadios[0];

            if (firstPwdRadio) {
                const radioValid =
                    window.validateFormInputField?.(
                        firstPwdRadio
                    ) ?? true;

                if (!radioValid && !firstInvalid) {
                    firstInvalid = firstPwdRadio;
                }
            }

            if (firstInvalid) {
                window.focusGlobalInvalidField?.(
                    firstInvalid
                );

                return false;
            }

            return true;
        }

        function selectGuestPatient(shouldProceed = true) {
            const firstName =
                guestFirstName?.value?.trim() || "";

            const middleName =
                guestMiddleName?.value?.trim() || "";

            const lastName =
                guestLastName?.value?.trim() || "";

            const suffix =
                normalizeGuestSuffix(
                    guestSuffix?.value
                );

            if (guestSuffix) {
                guestSuffix.value = suffix;
            }

            const name = [
                    firstName,
                    middleName,
                    lastName,
                    suffix,
                ]
                .filter(Boolean)
                .join(" ");

            const email =
                guestEmail?.value?.trim() || "";

            const phone =
                guestPhone?.value?.trim() || "";

            const gender =
                guestGender?.value || "";

            const birthdate =
                guestBirthdate?.value || "";

            const program =
                guestProgram?.value?.trim() || "";

            const studentNumber =
                guestStudentNumber?.value?.trim() || "";

            const facultyCode =
                guestFacultyCode?.value?.trim() || "";

            const yearLevel =
                guestYearLevel?.value?.trim() || "";

            const section =
                guestSection?.value?.trim() || "";

            const pwdValue =
                document.querySelector(
                    'input[name="guest_is_pwd"]:checked'
                )?.value;

            if (!validateGuestOnboardingFields()) {
                return false;
            }

            const patientType =
                guestPatientType?.value || "";

            const patientTypeLabel =
                patientType === "student" ?
                "Student" :
                patientType === "faculty" ?
                "Faculty" :
                patientType === "alumni" ?
                "Alumni" :
                patientType === "dependent" ?
                "Dependent" :
                patientType === "administrative" ?
                "Administrative Personnel" :
                "Guest Patient";

            selectedWalkInPatient = {
                id: null,
                mode: "guest",

                name,
                suffix_name: suffix,
                email,
                phone,
                gender,
                birthdate,
                patient_type: patientType,

                program,
                student_number: studentNumber,
                faculty_code: facultyCode,
                year_level: yearLevel,
                section,

                is_pwd: pwdValue === "1",

                type: patientTypeLabel,
            };

            updateWomenSection(
                gender
            );

            if (selectedPatientId) {
                selectedPatientId.value = "";
            }

            if (patientModeInput) {
                patientModeInput.value = "guest";
            }

            if (selectedPatientName) {
                selectedPatientName.textContent =
                    name;
            }

            if (selectedPatientMeta) {
                const metaParts = [
                    patientTypeLabel,
                    gender,
                    program,
                    email,
                    phone,
                ].filter(Boolean);

                selectedPatientMeta.textContent =
                    metaParts.join(" - ");
            }

            selectedPatientBox
                ?.removeAttribute(
                    "hidden"
                );

            markFormDirty();

            return true;
        }

        function normalizeGuestSuffix(value) {
            const suffix = String(value || "")
                .trim();

            return /^(ii|iii|iv|v|vi|vii|viii|ix|x)\.?$/i.test(
                    suffix
                ) ?
                suffix.toUpperCase() :
                suffix;
        }

        function formatWalkInPatientName(value) {
            return String(value || "")
                .trim()
                .replace(
                    /\b(ii|iii|iv|v|vi|vii|viii|ix|x)\.?$/i,
                    suffix => suffix.toUpperCase()
                );
        }

        async function createGuestPatientOnServer() {
            if (!selectGuestPatient(false)) {
                return false;
            }

            const token = document.querySelector('input[name="_token"]')?.value || "";
            const payload = new FormData();
            payload.append(
                "guest_name",
                [
                    guestFirstName?.value?.trim(),
                    guestMiddleName?.value?.trim(),
                    guestLastName?.value?.trim(),
                    guestSuffix?.value?.trim(),
                ]
                .filter(Boolean)
                .join(" ")
            );
            payload.append(
                "guest_first_name",
                guestFirstName?.value?.trim() || ""
            );

            payload.append(
                "guest_middle_name",
                guestMiddleName?.value?.trim() || ""
            );

            payload.append(
                "guest_last_name",
                guestLastName?.value?.trim() || ""
            );
            payload.append(
                "guest_suffix",
                normalizeGuestSuffix(
                    guestSuffix?.value
                )
            );
            payload.append("guest_email", guestEmail?.value?.trim() || "");
            payload.append(
                "guest_phone",
                guestPhone?.value
                ?.replace(/\D/g, '')
                .trim() || ""
            );
            payload.append(
                "guest_patient_type",
                guestPatientType?.value || ""
            );
            payload.append(
                "guest_gender",
                guestGender?.value || ""
            );

            payload.append(
                "guest_birthdate",
                guestBirthdate?.value || ""
            );

            payload.append(
                "guest_program",
                guestProgram?.value?.trim() || ""
            );

            payload.append(
                "guest_student_number",
                guestStudentNumber?.value?.trim() || ""
            );

            payload.append(
                "guest_faculty_code",
                guestFacultyCode?.value?.trim() || ""
            );

            payload.append(
                "guest_year_level",
                guestYearLevel?.value?.trim() || ""
            );

            payload.append(
                "guest_section",
                guestSection?.value?.trim() || ""
            );

            payload.append(
                "guest_is_pwd",
                document.querySelector(
                    'input[name="guest_is_pwd"]:checked'
                )?.value ?? ""
            );

            if (createGuestBtn) {
                createGuestBtn.disabled = true;
                createGuestBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Creating guest account`;
            }

            try {
                const response = await fetch(`{{ route('dentist.walk-in.guest.store') }}`, {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": token,
                    },
                    body: payload,
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data?.success || !data?.patient?.id) {
                    throw new Error(data?.message || "Unable to create guest patient.");
                }

                selectedWalkInPatient = {
                    ...data.patient,
                    name: formatWalkInPatientName(
                        data.patient.name
                    ),
                    mode: "guest",
                    type: data.patient.type || "Guest",
                };

                updateWomenSection(
                    data.patient.gender
                );

                if (selectedPatientId) {
                    selectedPatientId.value = data.patient.id;
                }

                if (selectedPatientName) {
                    selectedPatientName.textContent =
                        selectedWalkInPatient.name || [
                            guestFirstName?.value?.trim(),
                            guestMiddleName?.value?.trim(),
                            guestLastName?.value?.trim(),
                            guestSuffix?.value?.trim(),
                        ]
                        .filter(Boolean)
                        .join(" ") ||
                        "Guest Patient";
                }

                if (selectedPatientMeta) {
                    const metaParts = [
                        data.patient.type || "Guest",
                        data.patient.email || guestEmail?.value?.trim(),
                        guestPhone?.value?.trim(),
                    ].filter(Boolean);
                    selectedPatientMeta.textContent = metaParts.join(" - ");
                }

                selectedPatientBox?.removeAttribute("hidden");
                window.showToast?.({
                    type: 'success',
                    title: 'Guest account created',
                    message: 'The guest patient is ready for walk-in intake.',
                });
                markFormDirty();
                return true;
            } catch (error) {
                window.showToast?.({
                    type: 'error',
                    title: 'Unable to create guest account',
                    message: error.message ||
                        'Please try again.',
                });
                return false;
            } finally {
                if (createGuestBtn) {
                    createGuestBtn.disabled = false;
                    createGuestBtn.innerHTML = `<i class="fa-solid fa-user-plus"></i> Create guest account`;
                }
            }
        }

        accountTabs.forEach(tab => {
            tab.addEventListener("click", function() {
                setPatientMode(this.dataset.tab, true);
                markFormDirty();
            });
        });

        createGuestBtn?.addEventListener("click", () => {
            setPatientMode("guest", false);
            createGuestPatientOnServer();
        });

        let patientCurrentPage = 1;
        let patientPageSize = 10;

        let patientPaginationMeta = {
            currentPage: 1,
            lastPage: 1,
            total: 0,
            from: null,
            to: null,
        };

        const patientEntriesInfo =
            document.getElementById(
                'patientEntriesInfo'
            );

        const patientEntriesInfoBottom =
            document.getElementById(
                'patientEntriesInfoBottom'
            );

        const patientPaginationTopBar =
            document.getElementById(
                'patientPaginationTopBar'
            );

        const patientPaginationTop =
            document.getElementById(
                'patientPaginationTop'
            );

        const patientPaginationBar =
            document.getElementById(
                'patientPaginationBar'
            );

        const patientPagination =
            document.getElementById(
                'patientPagination'
            );

        function buildWalkInPatientSkeletons(
            count = patientPageSize
        ) {
            const skeletonCount =
                Math.min(
                    Math.max(
                        Number(count) || 10,
                        4
                    ),
                    12
                );

            const cards =
                Array
                .from({
                        length: skeletonCount
                    },
                    () => `
                    <div
                        class="
                            table-record-card
                            skeleton-shell
                            p-4
                        "
                        aria-hidden="true"
                    >
                        <div
                            class="
                                flex
                                items-start
                                gap-3
                            "
                        >
                            <div
                                class="
                                    skeleton-circle
                                    w-[54px]
                                    h-[54px]
                                    flex-shrink-0
                                "
                            ></div>

                            <div
                                class="
                                    flex-1
                                    min-w-0
                                "
                            >
                                <div
                                    class="
                                        skeleton-line
                                        h-4
                                        w-3/5
                                        mb-3
                                    "
                                ></div>

                                <div
                                    class="
                                        skeleton-pill
                                        h-5
                                        w-20
                                        mb-4
                                    "
                                ></div>

                                <div
                                    class="
                                        skeleton-line
                                        h-3
                                        w-2/3
                                        mb-2
                                    "
                                ></div>

                                <div
                                    class="
                                        skeleton-line
                                        h-3
                                        w-4/5
                                    "
                                ></div>
                            </div>
                        </div>
                    </div>
                `
                )
                .join('');

            return `
    <div
        class="
            table-record-grid
            patient-record-grid
            gap-5
        "
    >
            ${cards}
        </div>
    `;
        }

        function renderWalkInPatientSkeletons() {
            if (!patientResults) {
                return;
            }

            window.EmptyState?.hide(
                patientResults
            );

            patientResults.innerHTML =
                buildWalkInPatientSkeletons();
        }

        function safePatientText(value) {
            return String(value ?? "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");
        }

        function renderPatientPagination() {
            window.renderGlobalPagination?.({
                ...patientPaginationMeta,

                containers: [
                    patientPaginationTop,
                    patientPagination,
                ],

                bars: [
                    patientPaginationTopBar,
                    patientPaginationBar,
                ],

                infoElements: [
                    patientEntriesInfo,
                    patientEntriesInfoBottom,
                ],

                itemLabel: 'patients',

                onPageChange(page) {
                    patientCurrentPage =
                        page;

                    const query =
                        patientSearch
                        ?.value
                        .trim() || '';

                    loadPatients(
                        query,
                        query === ''
                    );

                    patientResults
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start',
                        });
                },
            });
        }

        function createPatientCard(patient) {
            const card =
                document.createElement(
                    'div'
                );

            card.setAttribute(
                'role',
                'button'
            );

            card.setAttribute(
                'tabindex',
                '0'
            );

            card.setAttribute(
                'aria-pressed',
                'false'
            );

            card.className = [
                'card',
                'table-record-card',
                'patient-record-card',
            ].join(' ');

            card.dataset.patientId =
                patient.id;

            const rawPatientName =
                patient.name ||
                'Unnamed Patient';

            const patientName =
                window.formatPatientName?.(
                    rawPatientName
                ) ||
                rawPatientName;

            const patientType =
                patient.type ||
                'Patient';

            const roleClass =
                window.PatientUI
                ?.getRoleClass(
                    patientType
                ) ||
                'role-none';

            const patientEmail =
                patient.email || '';

            const studentNumber =
                patient.student_number || '';

            const program =
                patient.program || '';

            const avatarUrl =
                window.PatientUI
                ?.safeUrl(
                    patient.avatar_url
                ) || '';

            card.innerHTML = `
        <span class="patient-avatar patient-avatar-md">
    ${avatarUrl
                ? `
                                                                                                                                                                                                                                                                                                                    <img
                                                                                                                                                                                                                                                                                                                        src="${safePatientText(
                        avatarUrl
                    )}"
                                                                                                                                                                                                                                                                                                                        alt="${safePatientText(
                        patientName
                    )}"
                                                                                                                                                                                                                                                                                                                        loading="lazy"
                                                                                                                                                                                                                                                                                                                    >
                                                                                                                                                                                                                                                                                                                `
                : `
                                                                                                                                                                                                                                                                                                                    <span>
                                                                                                                                                                                                                                                                                                                        ${safePatientText(
                        window.PatientUI
                            ?.getInitials(
                                patientName
                            ) || 'P'
                    )}
                                                                                                                                                                                                                                                                                                                    </span>
                                                                                                                                                                                                                                                                                                                `
            }
</span>

        <span class="patient-card-content">
            <span class="patient-card-header">
                <strong class="patient-card-name" data-patient-name>
                    ${safePatientText(patientName)}
                </strong>

                <span class="badge-role ${roleClass}">
    ${safePatientText(patientType)}
</span>
            </span>

            <span class="patient-card-meta">
                ${studentNumber
                ? `
                                                                                                                                                                                                                                                                                                                                    <span>
                                                                                                                                                                                                                                                                                                                                        <i class="fa-solid fa-id-card"></i>
                                                                                                                                                                                                                                                                                                                                        ${safePatientText(
                        studentNumber
                    )}
                                                                                                                                                                                                                                                                                                                                    </span>
                                                                                                                                                                                                                                                                                                                                `
                : ''
            }

                ${program
                ? `
                                                                                                                                                                                                                                                                                                                                    <span>
                                                                                                                                                                                                                                                                                                                                        <i class="fa-solid fa-graduation-cap"></i>
                                                                                                                                                                                                                                                                                                                                        ${safePatientText(
                        program
                    )}
                                                                                                                                                                                                                                                                                                                                    </span>
                                                                                                                                                                                                                                                                                                                                `
                : ''
            }

                ${patientEmail
                ? `
                                                                                                                                                                                                                                                                                                                                    <span>
                                                                                                                                                                                                                                                                                                                                        <i class="fa-solid fa-envelope"></i>
                                                                                                                                                                                                                                                                                                                                        ${safePatientText(
                        patientEmail
                    )}
                                                                                                                                                                                                                                                                                                                                    </span>
                                                                                                                                                                                                                                                                                                                                `
                : ''
            }
            </span>

            <label
    class="patient-card-checkbox-wrap"
    aria-label="Select ${safePatientText(patientName)}"
>
    <input
        type="checkbox"
        class="patient-card-checkbox global-checkbox-input"
        aria-label="Select ${safePatientText(patientName)}"
    >
</label>
    `;

            const patientCheckbox =
                card.querySelector(
                    '.patient-card-checkbox'
                );

            if (
                String(selectedPatientId?.value) ===
                String(patient.id)
            ) {
                card.classList.add(
                    'is-selected'
                );

                if (patientCheckbox) {
                    patientCheckbox.checked = true;
                }
            }

            function isThisPatientSelected() {
                return (
                    String(
                        selectedPatientId?.value || ''
                    ) ===
                    String(
                        patient.id || ''
                    )
                );
            }

            function syncCardSelectionState(
                selected
            ) {
                card.classList.toggle(
                    'is-selected',
                    selected
                );

                card.setAttribute(
                    'aria-pressed',
                    selected ?
                    'true' :
                    'false'
                );

                if (patientCheckbox) {
                    patientCheckbox.checked =
                        selected;
                }
            }

            function clearOtherPatientCards() {
                document
                    .querySelectorAll(
                        '.patient-record-card'
                    )
                    .forEach(item => {
                        if (item === card) {
                            return;
                        }

                        item.classList.remove(
                            'is-selected'
                        );

                        item.setAttribute(
                            'aria-pressed',
                            'false'
                        );

                        const checkbox =
                            item.querySelector(
                                '.patient-card-checkbox'
                            );

                        if (checkbox) {
                            checkbox.checked = false;
                        }
                    });
            }

            async function selectThisPatient() {
                clearOtherPatientCards();

                syncCardSelectionState(
                    true
                );

                await selectWalkInPatient(
                    patient
                );
            }

            function unselectThisPatient() {
                clearSelectedPatientUI({
                    clearSearch: false,
                    reloadPatients: false,
                });

                syncCardSelectionState(
                    false
                );
            }

            function togglePatientSelection() {
                if (
                    isThisPatientSelected()
                ) {
                    unselectThisPatient();
                    return;
                }

                selectThisPatient();
            }

            syncCardSelectionState(
                isThisPatientSelected()
            );

            card.addEventListener('click', event => {
                if (event.target.closest('.patient-card-checkbox-wrap')) {
                    return;
                }
                togglePatientSelection();
            });

            card.addEventListener('keydown', event => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }
                event.preventDefault();
                togglePatientSelection();
            });

            patientCheckbox?.addEventListener(
                'click',
                event => {
                    event.stopPropagation();
                }
            );

            patientCheckbox?.addEventListener(
                'change',
                () => {
                    if (patientCheckbox.checked) {
                        selectThisPatient();
                    } else {
                        unselectThisPatient();
                    }
                }
            );
            return card;
        }

        function renderPatients(responseData) {
            const patients =
                Array.isArray(responseData) ?
                responseData :
                Array.isArray(responseData?.data) ?
                responseData.data : [];

            const isPaginatedResponse = !Array.isArray(responseData);

            patientPaginationMeta = {
                currentPage: isPaginatedResponse ?
                    Number(responseData?.current_page) || 1 : 1,

                lastPage: isPaginatedResponse ?
                    Number(responseData?.last_page) || 1 : 1,

                total: isPaginatedResponse ?
                    Number(responseData?.total) || patients.length : patients.length,

                from: isPaginatedResponse ?
                    responseData?.from ?? null : patients.length ?
                    1 : null,

                to: isPaginatedResponse ?
                    responseData?.to ?? null : patients.length,
            };

            patientCurrentPage =
                patientPaginationMeta.currentPage;

            if (!patients.length) {
                const query =
                    patientSearch
                    ?.value
                    .trim() || '';

                patientResults.innerHTML = '';

                if (query) {
                    window.EmptyState?.renderSearch({
                        host: patientResults,

                        input: patientSearch,

                        query,

                        title: 'No patient record found',

                        message: 'Try another name, ID, or email address.',
                    });
                } else {
                    window.EmptyState?.render({
                        host: patientResults,

                        icon: 'fa-user-slash',

                        title: 'No patient records found',

                        message: 'There are currently no patient records available.',
                    });
                }

                renderPatientPagination();

                return;
            }

            window.EmptyState?.hide(patientResults);

            const grid = document.createElement('div');
            grid.className = 'table-record-grid patient-record-grid';

            patients.forEach(patient => {
                grid.appendChild(createPatientCard(patient));
            });

            patientResults.replaceChildren(grid);
            renderPatientPagination();
        }

        window.handleWalkInPatientPerPageChange =
            function(value) {
                const allowed = [
                    10,
                    20,
                    50,
                    100,
                ];

                const requested =
                    Number(value);

                patientPageSize =
                    allowed.includes(
                        requested
                    ) ?
                    requested :
                    10;

                patientCurrentPage = 1;

                const query =
                    patientSearch
                    ?.value
                    .trim() || '';

                loadPatients(
                    query,
                    query === ''
                );
            };

        let patientSearchRequestId = 0;

        async function loadPatients(query = "", showAll = false) {
            if (!patientResults) return;

            const requestId = ++patientSearchRequestId;
            renderWalkInPatientSkeletons();

            try {
                const params = new URLSearchParams();

                if (query) {
                    params.set("q", query);
                }

                if (showAll) {
                    params.set("show_all", "1");
                }

                params.set(
                    'page',
                    String(patientCurrentPage)
                );

                params.set(
                    'per_page',
                    String(patientPageSize)
                );

                const response = await fetch(`{{ route('dentist.walk-in.search-patient') }}?${params.toString()}`, {
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                const responseData =
                    await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(
                        responseData?.debug ||
                        responseData?.message ||
                        `Patient search failed. Status: ${response.status}`
                    );
                }

                if (
                    requestId !==
                    patientSearchRequestId
                ) {
                    return;
                }

                renderPatients(responseData);

            } catch (error) {
                if (
                    requestId !==
                    patientSearchRequestId
                ) {
                    return;
                }

                console.error(
                    'Walk-in patient loading error:',
                    error
                );

                patientResults.innerHTML = '';

                window.EmptyState?.render({
                    host: patientResults,

                    icon: 'fa-triangle-exclamation',

                    title: 'Unable to load patient records',

                    message: error.message ||
                        'Check your connection, then try again.',
                });
            }
        }

        function loadInitialPatientRecords() {
            if (
                !patientResults ||
                patientResults.dataset
                .initialPatientsLoaded ===
                "true"
            ) {
                return;
            }

            patientResults.dataset
                .initialPatientsLoaded =
                "true";

            loadPatients("", true);
        }

        window.handleWalkInPatientSearch =
            function(value) {
                patientCurrentPage = 1;

                const query =
                    String(value || '')
                    .trim();

                loadPatients(
                    query,
                    query === ''
                );
            };

        let bookingWorkflow = null;

        let step5ConfirmationActive = false;

        let editingHistoryFromReview = null;

        const summarySection =
            document.getElementById(
                'summarySection'
            );

        const confirmationSection =
            document.getElementById(
                'confirmationSection'
            );

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
                    label: 'Review & Confirm',

                    icon: 'fa-arrow-right',

                    iconPosition: 'right',
                });

            summarySection
                ?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
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
                    label: 'Start Procedure',

                    icon: 'fa-play',

                    iconPosition: 'left',
                });

            confirmationSection
                ?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
        }


        function resetStep5View() {
            showStep5Review();
        }

        function scrollToInvalidTarget(target) {
            if (!target) return;

            const block =
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

            if (typeof target.focus === "function" && !target.hasAttribute("readonly")) {
                setTimeout(() => target.focus(), 250);
            }
        }

        function isStepComplete(s) {
            const stepEl =
                bookingWorkflow
                ?.getPanels()
                ?.[s];

            if (!stepEl) {
                return true;
            }

            if (s === 0) {
                const mode = patientModeInput?.value || "existing";
                const selectedPatientInput = document.getElementById("selectedPatientId");

                if (mode === "guest") {
                    if (!guestFirstName?.value?.trim()) {
                        window.validateFormInputField?.(
                            guestFirstName
                        );

                        window.focusGlobalInvalidField?.(
                            guestFirstName
                        );

                        return false;
                    }

                    if (!guestLastName?.value?.trim()) {
                        window.validateFormInputField?.(
                            guestLastName
                        );

                        window.focusGlobalInvalidField?.(
                            guestLastName
                        );

                        return false;
                    }

                    if (!selectedPatientInput?.value) {
                        window.showGlobalGroupError?.(
                            walkInPatientGroup,
                            'walkin-patient',
                            'Please create the guest account before proceeding.'
                        );

                        walkInPatientGroup
                            ?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                            });

                        return false;
                    }

                    window.clearGlobalGroupError?.(
                        walkInPatientGroup,
                        'walkin-patient'
                    );

                } else if (
                    !selectedPatientInput?.value
                ) {
                    window.showGlobalGroupError?.(
                        walkInPatientGroup,
                        'walkin-patient',
                        'Please select an existing patient or use Guest Onboarding.'
                    );

                    walkInPatientGroup
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });

                    return false;
                }

                window.clearGlobalGroupError?.(
                    walkInPatientGroup,
                    'walkin-patient'
                );
            }

            if (s === 1) {
                const serviceGroup =
                    stepEl.querySelector(
                        ".service-step-grid"
                    );

                const selectedService =
                    serviceGroup?.querySelector(
                        'input[name="service_type"]:checked'
                    );

                if (!selectedService) {
                    window.showGlobalGroupError?.(
                        serviceGroup,
                        "service_type",
                        "Please select a dental service."
                    );

                    serviceGroup?.scrollIntoView({
                        behavior: "smooth",
                        block: "center",
                    });

                    return false;
                }

                window.clearGlobalGroupError?.(
                    serviceGroup,
                    "service_type"
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

            if (
                s === 3 &&
                !patientHasReusableSignature
            ) {
                const signature =
                    window.BookingSignature
                    ?.get(
                        stepEl
                    );

                if (
                    signature &&
                    !signature.validate()
                ) {
                    stepEl
                        .querySelector(
                            '[data-booking-signature]'
                        )
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });

                    return false;
                }
            }

            return true;
        }

        function initWalkInWorkflow() {
            if (
                bookingWorkflow ||
                !window.BookingWorkflow
            ) {
                return;
            }

            bookingWorkflow =
                window.BookingWorkflow.create({
                    panels: '#appointmentForm .step-content',

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

                        if (
                            patientHasExistingBookingInformation &&
                            currentStep === 4 &&
                            !editingHistoryFromReview
                        ) {
                            const signature =
                                window.BookingSignature
                                    ?.get(document);

                            if (
                                !patientHasReusableSignature &&
                                signature?.isReady?.()
                            ) {
                                bookingWorkflow.goTo(3);

                                return false;
                            }

                            bookingWorkflow.goTo(1);

                            return false;
                        }

                        if (
                            editingHistoryFromReview ===
                            'dental' &&
                            currentStep === 2
                        ) {
                            editingHistoryFromReview =
                                null;

                            bookingWorkflow.goTo(4);

                            return false;
                        }

                        if (
                            editingHistoryFromReview ===
                            'medical' &&
                            currentStep === 3
                        ) {
                            editingHistoryFromReview =
                                null;

                            bookingWorkflow.goTo(4);

                            return false;
                        }

                        return true;
                    },

                    beforeNext: currentStep => {

                        if (
                            !isStepComplete(
                                currentStep
                            )
                        ) {
                            return false;
                        }

                        if (
                            patientHasExistingBookingInformation &&
                            currentStep === 1 &&
                            !editingHistoryFromReview
                        ) {
                            bookingWorkflow.markComplete(1);
                            bookingWorkflow.markComplete(2);

                            if (patientHasReusableSignature) {
                                bookingWorkflow.markComplete(3);
                                bookingWorkflow.goTo(4);
                            } else {
                                bookingWorkflow.goTo(3);
                            }

                            return false;
                        }

                        if (
                            editingHistoryFromReview ===
                            'dental' &&
                            currentStep === 2
                        ) {
                            bookingWorkflow.markComplete(2);

                            editingHistoryFromReview =
                                null;

                            bookingWorkflow.goTo(4);

                            return false;
                        }

                        if (
                            editingHistoryFromReview ===
                            'medical' &&
                            currentStep === 3
                        ) {
                            bookingWorkflow.markComplete(3);

                            editingHistoryFromReview =
                                null;

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
                            showStep5Confirmation();

                            return true;
                        }

                        if (
                            !finalConfirm ||
                            !finalConfirm.checked
                        ) {
                            if (finalConfirm) {
                                finalConfirm.required = true;

                                window.validateFormInputField?.(
                                    finalConfirm
                                );

                                window.focusGlobalInvalidField?.(
                                    finalConfirm
                                );
                            }

                            return false;
                        }

                        submitWalkInAppointment();

                        return true;
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

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    loadInitialPatientRecords();
                });
            });
        }

        async function bootWalkInWorkflow() {
            try {
                await window
                    .loadBookingWorkflowModule?.();

                initWalkInWorkflow();

            } catch (error) {
                console.error(
                    'Unable to initialize walk-in workflow.',
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
                bootWalkInWorkflow, {
                    once: true
                }
            );
        } else {
            bootWalkInWorkflow();
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

        function editDentalHistoryFromReview() {
            if (!bookingWorkflow) {
                return;
            }

            editingHistoryFromReview =
                'dental';

            resetStep5View();

            bookingWorkflow.goTo(2);
        }

        function editSignatureFromReview() {
            editingHistoryFromReview =
                'medical';

            resetStep5View();

            bookingWorkflow.goTo(3);

            setTimeout(() => {
                const signature =
                    window.BookingSignature
                    ?.get(document);

                signature?.editExisting();
                signature?.resize();

                signature?.root
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
            }, 120);
        }

        function editMedicalHistoryFromReview() {
            if (!bookingWorkflow) {
                return;
            }

            editingHistoryFromReview =
                'medical';

            resetStep5View();

            bookingWorkflow.goTo(3);

            setTimeout(() => {
                window.BookingSignature
                    ?.get(document)
                    ?.resize();
            }, 120);
        }

        function formatPatientDate(value) {
            if (!value) {
                return "N/A";
            }

            const rawValue =
                String(value).trim();

            let date;

            if (/^\d{4}-\d{2}-\d{2}$/.test(rawValue)) {
                const [
                    year,
                    month,
                    day
                ] = rawValue
                    .split("-")
                    .map(Number);

                date = new Date(
                    year,
                    month - 1,
                    day
                );
            } else {
                date = new Date(rawValue);
            }

            if (
                Number.isNaN(
                    date.getTime()
                )
            ) {
                return rawValue;
            }

            return new Intl.DateTimeFormat(
                "en-US", {
                    month: "long",
                    day: "numeric",
                    year: "numeric",
                }
            ).format(date);
        }

        function buildSummary() {
            const form = document.getElementById("appointmentForm");
            if (!form) return;

            const data = new FormData(form);
            const get = n => data.get(n) || "N/A";
            const getAll = n => data.getAll(n);

            const emergencyRelation = data.get("emergency_relation") || "N/A";

            const sigFile =
                data.get(
                    "patient_signature"
                );

            const signatureController =
                window.BookingSignature
                    ?.get(document);

            const canEditSignatureFromReview =
                patientHasReusableSignature ||
                Boolean(
                    signatureController
                        ?.isReady?.()
                );

            let sigHTML =
                patientHasReusableSignature ?
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

                ${patientExistingSignatureUrl
                    ? `
                                            <div class="signature-existing-preview">
                                                <img
                                                    src="${patientExistingSignatureUrl}"
                                                    alt="Existing signature"
                                                >
                                            </div>
                                        `
                    : ''
                }

                <p class="signature-existing-help">
                    The patient's previously verified signature will be reused.
                </p>

            </div>
        ` :
                `
            <span class="booking-summary-muted">
                Not provided
            </span>
        `;
            if (sigFile && sigFile.size > 0) {
                const url = URL.createObjectURL(sigFile);
                sigHTML = `
    <div class="booking-signature-summary">

        <div class="booking-signature-summary-file">
            <i class="fa-solid fa-file-image"></i>

            <span>
                ${sigFile.name}
            </span>
        </div>

        <div class="booking-signature-summary-status">
            <i class="fa-solid fa-circle-check"></i>

            <span>
                ${sigFile.name.startsWith(
                'walk-in-signature-'
            ) ||
                    sigFile.name.startsWith(
                        'drawn-signature-'
                    )
                    ? 'Drawn signature accepted'
                    : 'Signature uploaded'
                }
            </span>
        </div>

        <img
            src="${url}"
            alt="Patient signature"
            class="booking-signature-summary-preview"
        >

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

        <div
            class="
                booking-summary-card-header
                flex
                items-center
                justify-between
                gap-4
                w-full
            "
        >

            <div class="flex items-center gap-2 min-w-0">

                <i class="fa-solid ${icon}"></i>

                <span>
                    ${title}
                </span>

            </div>

            ${editAction
                ? `
                                                <button
                                                    type="button"
                                                    class="
                                                        ui-btn
                                                        ui-btn-secondary                                      "
                                                    onclick="${editAction}"
                                                >
                                                    <i class="fa-solid fa-pen"></i>
                                                    <span>Edit</span>
                                                </button>
                                            `
                : ''
            }

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

            const patientName =
                formatWalkInPatientName(
                    selectedWalkInPatient?.name ||
                    document.getElementById(
                        "selectedPatientName"
                    )?.textContent ||
                    "N/A"
                );

            const patientGender =
                selectedWalkInPatient?.gender ?
                String(
                    selectedWalkInPatient.gender
                )
                .trim()
                .replace(
                    /\b\w/g,
                    char =>
                    char.toUpperCase()
                ) :
                "N/A";

            const patientBirthday =
                formatPatientDate(
                    selectedWalkInPatient?.birthdate
                );

            const patientProgram =
                selectedWalkInPatient?.program ||
                "N/A";

            const patientFaculty =
                selectedWalkInPatient?.faculty_code ||
                "N/A";

            const patientYearLevel =
                selectedWalkInPatient?.year_level ||
                "N/A";

            const patientSection =
                selectedWalkInPatient?.section ||
                "N/A";

            const patientPwd =
                selectedWalkInPatient?.is_pwd === true ||
                selectedWalkInPatient?.is_pwd === 1 ||
                selectedWalkInPatient?.is_pwd === "1" ?
                "Yes" :
                (
                    selectedWalkInPatient?.is_pwd === false ||
                    selectedWalkInPatient?.is_pwd === 0 ||
                    selectedWalkInPatient?.is_pwd === "0" ?
                    "No" :
                    "N/A"
                );

            const patientEmail =
                selectedWalkInPatient?.email ||
                "N/A";

            const patientType =
                String(
                    selectedWalkInPatient?.type || "Patient"
                ).trim();

            const normalizedPatientType =
                patientType.toLowerCase();

            const patientDepartment =
                selectedWalkInPatient?.department ||
                selectedWalkInPatient?.program ||
                "N/A";

            const patientFacultyType =
                selectedWalkInPatient?.faculty_type ||
                "N/A";

            const patientPhone =
                selectedWalkInPatient?.phone ||
                "N/A";
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

    ${isFemaleGender(
            selectedWalkInPatient?.gender
        )
                ? subSection(
                    "For Women Only",
                    `
                                                                                                                                                                                                                                                                                                                    ${row(
                            "Pregnant",
                            get("pregnant")
                        )}

                                                                                                                                                                                                                                                                                                                    ${row(
                            "Nursing",
                            get("nursing")
                        )}

                                                                                                                                                                                                                                                                                                                    ${row(
                            "Birth Control Pills",
                            get("birth_control")
                        )}
                                                                                                                                                                                                                                                                                                                `
                )
                : ""}

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


            let patientInformationBody = "";

            if (normalizedPatientType.includes("student")) {
                patientInformationBody = `
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 sm-grid-1col">
            ${row("Name", patientName)}
            ${row("Gender", patientGender)}
            ${row("Birthday", patientBirthday)}
            ${row("PWD", patientPwd)}
            ${row("Program", patientProgram)}
            ${row("Year Level", patientYearLevel)}
            ${row("Section", patientSection)}
            ${row("Email", patientEmail)}
            ${row("Phone", patientPhone)}
        </div>
    `;
            } else if (normalizedPatientType.includes("faculty")) {
                patientInformationBody = `
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 sm-grid-1col">
            ${row("Name", patientName)}
            ${row("Gender", patientGender)}
            ${row("Birthday", patientBirthday)}
            ${row("Faculty Code", patientFaculty)}
            ${row("Department", patientDepartment)}
            ${row("Faculty Type", patientFacultyType)}
            ${row("Email", patientEmail)}
            ${row("Phone", patientPhone)}
        </div>
    `;
            } else if (normalizedPatientType.includes("guest")) {
                patientInformationBody = `
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 sm-grid-1col">
            ${row("Name", patientName)}
            ${row("Gender", patientGender)}
            ${row("Birthday", patientBirthday)}
            ${row("PWD", patientPwd)}
            ${row("Program", patientProgram)}
            ${row("Faculty", patientFaculty)}
            ${row("Year Level", patientYearLevel)}
            ${row("Section", patientSection)}
            ${row("Email", patientEmail)}
            ${row("Phone", patientPhone)}
        </div>
    `;
            } else if (
                normalizedPatientType.includes("administrative") ||
                normalizedPatientType.includes("admin")
            ) {
                patientInformationBody = `
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 sm-grid-1col">
            ${row("Name", patientName)}
            ${row("Gender", patientGender)}
            ${row("Office", patientProgram)}
            ${row("Email", patientEmail)}
            ${row("Phone", patientPhone)}
        </div>
    `;
            } else {
                patientInformationBody = `
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 sm-grid-1col">
            ${row("Name", patientName)}
            ${row("Gender", patientGender)}
            ${row("Birthday", patientBirthday)}
            ${row("Email", patientEmail)}
            ${row("Phone", patientPhone)}
        </div>
    `;
            }

            document.getElementById("summaryBox").innerHTML = `
${summaryCard(
            "Patient Information",
            "fa-user",
            patientInformationBody
        )}
     ${summaryCard("Walk-in Schedule", "fa-clock", `
                                                                                                                                                                                                                                                                                                                                    <div class="grid grid-cols-1 gap-y-1">
                                                                                                                                                                                                                                                                                                                                        <p class="booking-summary-row">

                                                                                                                                                                                                                                                                                <span class="booking-summary-row-label">
                                                                                                                                                                                                                                                                                    Date & Time:
                                                                                                                                                                                                                                                                                </span>

                                                                                                                                                                                                                                                                                <span class="booking-summary-auto-note">
                                                                                                                                                                                                                                                                                    Recorded automatically when Start Procedure is clicked.
                                                                                                                                                                                                                                                                                </span>

                                                                                                                                                                                                                                                                            </p>
                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                `)}

        ${summaryCard("Service", "fa-tooth", `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="grid grid-cols-1 gap-y-1">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ${row("Type", get("service_type"))}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                `)}
    </div>

    ${summaryCard(
            "Dental History",
            "fa-teeth",
            dentalHistoryBody,
            patientHasExistingBookingInformation
                ? "editDentalHistoryFromReview()"
                : null
        )}

    ${summaryCard(
            "Medical History",
            "fa-heart-pulse",
            medicalHistoryBody,
            patientHasExistingBookingInformation
                ? "editMedicalHistoryFromReview()"
                : null
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
            canEditSignatureFromReview
                ? "editSignatureFromReview()"
                : null
        )}
    </div>
`;

            document.querySelectorAll(".sm-grid-1col").forEach(el => {
                if (window.innerWidth < 640) el.style.gridTemplateColumns = "1fr";
            });
        }

        const confirmModal =
            document.getElementById(
                "confirmModal"
            );

        const confirmMessage =
            document.getElementById(
                "confirmMessage"
            );

        const okBtn =
            document.getElementById(
                "okBtn"
            );

        const finalConfirm =
            document.getElementById(
                "finalConfirm"
            );

        const appointmentForm =
            document.getElementById(
                "appointmentForm"
            );

        const appointmentsRedirectUrl =
            @json(route('dentist.dentist.appointments'));

        let appointmentSubmitRunning = false;

        const workflowNextBtn =
            document.getElementById(
                'nextBtn'
            );

        function setFinalSubmitLoading(
            loading
        ) {
            if (!workflowNextBtn) {
                return;
            }

            workflowNextBtn.disabled =
                loading;

            if (loading) {
                workflowNextBtn.innerHTML = `
            <i class="fa-solid fa-spinner fa-spin"></i>
            <span>Starting Procedure...</span>
        `;

                return;
            }

            bookingWorkflow
                ?.setNextButton({
                    label: 'Start Procedure',

                    icon: 'fa-play',

                    iconPosition: 'left',
                });
        }

        function applyWalkInServerValidationErrors(errors = {}) {
            let firstInvalid = null;

            Object.entries(errors).forEach(
                ([name, messages]) => {
                    const normalizedName =
                        name.replace(/\.\d+$/, '[]');

                    const field =
                        appointmentForm?.querySelector(
                            `[name="${CSS.escape(normalizedName)}"]`
                        );

                    if (!field) {
                        return;
                    }

                    const message =
                        Array.isArray(messages) ?
                        messages[0] :
                        String(messages || '');

                    window.showFormInputValidationMessage?.(
                        field,
                        message
                    );

                    firstInvalid ||= field;
                }
            );

            if (firstInvalid) {
                window.focusGlobalInvalidField?.(
                    firstInvalid
                );
            }

            return firstInvalid;
        }

        async function submitWalkInAppointment() {
            if (
                !appointmentForm ||
                appointmentSubmitRunning
            ) {
                return;
            }

            if (
                !finalConfirm ||
                !finalConfirm.checked
            ) {
                if (finalConfirm) {
                    finalConfirm.required = true;

                    window.validateFormInputField?.(
                        finalConfirm
                    );

                    window.focusGlobalInvalidField?.(
                        finalConfirm
                    );
                }

                return;
            }

            appointmentSubmitRunning = true;
            formSubmitting = true;

            setFinalSubmitLoading(true);

            const controller = new AbortController();

            const timeoutId = window.setTimeout(() => {
                controller.abort();
            }, 30000);

            try {
                const response = await fetch(
                    appointmentForm.action, {
                        method: appointmentForm.method ||
                            "POST",

                        headers: {
                            Accept: "application/json",

                            "X-Requested-With": "XMLHttpRequest",
                        },

                        body: new FormData(
                            appointmentForm
                        ),

                        signal: controller.signal,
                    }
                );

                window.clearTimeout(
                    timeoutId
                );

                const contentType =
                    response.headers.get(
                        'content-type'
                    ) || '';

                let responseData = {};

                if (
                    contentType.includes(
                        'application/json'
                    )
                ) {
                    responseData =
                        await response.json();
                } else {
                    const responseText =
                        await response.text();

                    responseData = {
                        message: responseText ||
                            'Unexpected server response.',
                    };
                }

                if (
                    response.status === 401 &&
                    responseData?.expired
                ) {
                    throw new Error(
                        responseData.message ||
                        'Your session has expired. Please sign in again.'
                    );
                }

                if (!response.ok) {
                    if (
                        response.status === 422 &&
                        responseData?.errors
                    ) {
                        const firstInvalid =
                            applyWalkInServerValidationErrors(
                                responseData.errors
                            );

                        if (firstInvalid) {
                            throw new Error(
                                "Please check the highlighted fields."
                            );
                        }

                        const firstMessage =
                            Object.values(
                                responseData.errors
                            )
                            .flat()
                            .find(Boolean);

                        throw new Error(
                            firstMessage ||
                            "Please check the submitted information."
                        );
                    }

                    throw new Error(
                        responseData?.message ||
                        "Unable to start the procedure."
                    );
                }

                formIsDirty = false;

                const patientName =
                    selectedWalkInPatient?.name ||
                    selectedPatientName
                    ?.textContent
                    ?.trim() ||
                    "the selected patient";

                const selectedService =
                    appointmentForm
                    ?.querySelector(
                        'input[name="service_type"]:checked'
                    );

                const serviceName =
                    selectedService?.value ||
                    'N/A';

                const appointmentDate =
                    new Intl.DateTimeFormat(
                        'en-US', {
                            month: 'long',
                            day: 'numeric',
                            year: 'numeric',
                        }
                    ).format(
                        new Date()
                    );

                const appointmentTime =
                    new Intl.DateTimeFormat(
                        'en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                        }
                    ).format(
                        new Date()
                    );

                if (confirmMessage) {
                    confirmMessage.innerHTML = `
        <div class="confirmed-modal-schedule-grid">

            <div class="confirmed-modal-schedule-item">
                <div class="confirmed-modal-schedule-icon">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>
                    <span class="confirmed-modal-schedule-label">
                        Patient
                    </span>

                    <strong class="confirmed-modal-schedule-value">
                        ${safePatientText(patientName)}
                    </strong>
                </div>
            </div>

            <div class="confirmed-modal-schedule-item">
                <div class="confirmed-modal-schedule-icon">
                    <i class="fa-solid fa-tooth"></i>
                </div>

                <div>
                    <span class="confirmed-modal-schedule-label">
                        Service
                    </span>

                    <strong class="confirmed-modal-schedule-value">
                        ${safePatientText(serviceName)}
                    </strong>
                </div>
            </div>

            <div class="confirmed-modal-schedule-item">
                <div class="confirmed-modal-schedule-icon">
                    <i class="fa-solid fa-person-walking-arrow-right"></i>
                </div>

                <div>
                    <span class="confirmed-modal-schedule-label">
                        Appointment Type
                    </span>

                    <strong class="confirmed-modal-schedule-value">
                        Walk-in
                    </strong>
                </div>
            </div>

            <div class="confirmed-modal-schedule-item">
                <div class="confirmed-modal-schedule-icon">
                    <i class="fa-regular fa-clock"></i>
                </div>

                <div>
                    <span class="confirmed-modal-schedule-label">
                        Recorded
                    </span>

                    <strong class="confirmed-modal-schedule-value">
                        ${appointmentDate}
                        ·
                        ${appointmentTime}
                    </strong>
                </div>
            </div>

        </div>

        <div class="confirmed-modal-schedule-note">
            <i class="fa-solid fa-circle-info"></i>

            <span>
                The walk-in intake has been saved.
                Start the procedure when you are ready.
            </span>
        </div>
    `;
                }

                okBtn.dataset.startUrl =
                    responseData?.start_url || '';

                appointmentSubmitRunning = false;
                formSubmitting = false;

                setFinalSubmitLoading(false);

                window.openModal?.(
                    'confirmModal'
                );

            } catch (error) {
                appointmentSubmitRunning = false;
                formSubmitting = false;

                setFinalSubmitLoading(false);

                if (error.name === "AbortError") {
                    window.showToast?.({
                        type: 'error',
                        title: 'Request timed out',
                        message: 'The request took too long to complete. Please try again.',
                    });

                    return;
                }

                window.showToast?.({
                    type: 'error',
                    title: 'Unable to start procedure',
                    message: error.message ||
                        'Please try again.',
                });
            }
        }

        appointmentForm?.addEventListener(
            "submit",
            function(event) {
                event.preventDefault();

                submitWalkInAppointment();
            }
        );

        function clearModalStateBeforeRedirect() {
            document.body.classList.remove(
                'modal-lock'
            );

            document.querySelectorAll(
                '.ui-modal.open, .ui-modal.closing, .modal-overlay.open, .modal-overlay.closing'
            ).forEach(
                modalEl => {
                    modalEl.classList.remove(
                        'open',
                        'closing'
                    );

                    modalEl.setAttribute(
                        'aria-hidden',
                        'true'
                    );
                }
            );
        }

        okBtn?.addEventListener(
            'click',
            () => {
                const startUrl =
                    okBtn.dataset.startUrl;

                if (!startUrl) {
                    return;
                }

                window.closeModal?.(
                    'confirmModal'
                );

                clearModalStateBeforeRedirect();

                window.location.assign(
                    startUrl
                );
            }
        );

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

        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('input', () => {
                formIsDirty = true;
            });
        });
        document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', () => {
                formIsDirty = true;
            });
        });

        function initIntroBookingModal() {
            const modal =
                document.getElementById(
                    "introBookingModal"
                );

            const startBtn =
                document.getElementById(
                    "introStartBtn"
                );

            if (!modal) {
                return;
            }

            startBtn?.addEventListener(
                "click",
                () => {
                    window.closeModal?.(
                        "introBookingModal"
                    );
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

        function refreshWalkInGlobalControls() {
            const page =
                document.getElementById(
                    'dentistWalkInPage'
                ) || document;

            window.initSearchClearButtons?.(page);
            window.initGlobalSearchBars?.(page);
            window.initCustomSelects?.(page);
            window.initGlobalPageSizeSelects?.(page);
            window.bindFormInputValidation?.(page);

            const relationSelect =
                document.getElementById(
                    'emergency_relation'
                );

            const relationWrapper =
                relationSelect?.closest(
                    '.custom-select'
                );

            if (relationWrapper) {
                window.syncCustomSelect?.(
                    relationWrapper
                );
            }
        }

        refreshWalkInGlobalControls();
        setPatientMode(
            "existing",
            false
        );
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
