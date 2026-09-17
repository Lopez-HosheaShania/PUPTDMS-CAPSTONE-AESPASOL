@props(['questions', 'mode' => 'flat', 'answers' => [], 'defaults' => [], 'subtitle' => 'Share the patient\'s past dental records, treatments, and dental concerns for a better assessment.'])

@php
    $isNested = $mode === 'nested';

    $answerValue = function (string $code, string $fallback = '') use ($isNested, $answers, $defaults) {
        if ($isNested) {
            return old('dental_answers.' . $code, $answers[$code] ?? $fallback);
        }

        return old($code, $defaults[$code] ?? $fallback);
    };

    $questionName = function (string $code) use ($isNested) {
        return $isNested ? "dental_answers[{$code}]" : $code;
    };

    $lastDentalVisit = old('last_dental_visit', $defaults['last_dental_visit'] ?? '');

    $previousDentist = old('previous_dentist', $defaults['previous_dentist'] ?? '');

    $additionalConcerns = old('additional_concerns', $defaults['additional_concerns'] ?? '');
@endphp

<div class="step-content hidden">
    <div class="booking-step-shell">

        <div class="booking-step-header">
            <p class="booking-step-eyebrow">
                Step 3 of 5
            </p>

            <h2 class="booking-step-title">
                Dental History
            </h2>

            <p class="booking-step-subtitle">
                {{ $subtitle }}
            </p>
        </div>

        <div class="booking-step-body">

            <div class="booking-section-card">
                <p class="booking-section-card-title">
                    <i class="fa-regular fa-calendar-days text-xs"></i>
                    Basic Info

                    <span class="booking-section-card-title-line"></span>
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="global-form-group" data-global-field>
                        <label for="lastDentalVisit" class="global-form-label">
                            Last Dental Visit
                            <span class="field-optional">(Optional)</span>
                        </label>

                        <div class="global-control-wrap">
                            <input type="text" id="lastDentalVisit" name="last_dental_visit"
                                value="{{ $lastDentalVisit }}"
                                class="form-input-custom js-flatpickr-date-max-today"
                                placeholder="Select date" readonly>
                        </div>
                    </div>

                    <div class="global-form-group" data-global-field>
                        <label for="previous_dentist" class="global-form-label">
                            Previous Dentist
                            <span class="field-optional">(Optional)</span>
                        </label>

                        <input type="text" id="previous_dentist" name="previous_dentist" maxlength="50"
                            value="{{ $previousDentist }}" class="form-input-custom" placeholder="Dr. Name">
                    </div>

                </div>
            </div>

            <div class="booking-section-card">
                <p class="booking-section-card-title">
                    <i class="fa-solid fa-tooth text-xs"></i>
                    Dental Symptoms

                    <span class="booking-section-card-title-line"></span>
                </p>

                @foreach ($questions['symptoms'] as $question)
                    <x-booking-question :name="$questionName($question['code'])" :label="$question['label']" :checked-value="$answerValue($question['code'])" required />
                @endforeach
            </div>

            <div class="booking-section-card">
                <p class="booking-section-card-title">
                    <i class="fa-solid fa-circle-dot text-xs"></i>
                    Jaw &amp; Bite Symptoms

                    <span class="booking-section-card-title-line"></span>
                </p>

                @foreach ($questions['jaw_bite'] as $question)
                    <x-booking-question :name="$questionName($question['code'])" :label="$question['label']" :checked-value="$answerValue($question['code'])" required />
                @endforeach

                <p class="booking-section-note">
                    <i class="fa-solid fa-circle-info"></i>
                    If <strong>YES</strong>, please provide details
                    during the consultation.
                </p>
            </div>

            <div class="booking-section-card">
                <p class="booking-section-card-title">
                    <i class="fa-solid fa-notes-medical text-xs"></i>
                    Dental Procedures

                    <span class="booking-section-card-title-line"></span>
                </p>

                @foreach ($questions['procedures'] as $question)
                    <x-booking-question :name="$questionName($question['code'])" :label="$question['label']" :checked-value="$answerValue($question['code'])" required />

                    @if ($question['code'] === 'difficult_extraction')
                        <div id="extraction_date_box" class="booking-conditional-field hidden" data-booking-conditional
                            data-radio-name="{{ $questionName('difficult_extraction') }}" data-show-on="YES">
                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="extractionDate">
                                    Date of Extraction
                                    <span class="required-mark">*</span>
                                </label>

                                <div class="global-control-wrap">
                                    <input type="text" id="extractionDate" name="extraction_date"
                                        value="{{ old('extraction_date', $defaults['extraction_date'] ?? '') }}"
                                        class="form-input-custom js-flatpickr-date-max-today"
                                        placeholder="Select date"
                                        data-required-message="Please select the date of extraction."
                                        data-conditional-required readonly>
                                </div>
                            </div>
                        </div>
                    @endif


                    @if ($question['code'] === 'dentures')
                        <div id="dentures_date_box" class="booking-conditional-field hidden" data-booking-conditional
                            data-radio-name="{{ $questionName('dentures') }}" data-show-on="YES">
                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="denturesDate">
                                    Date of Placement
                                    <span class="required-mark">*</span>
                                </label>

                                <div class="global-control-wrap">
                                    <input type="text" id="denturesDate" name="dentures_date"
                                        value="{{ old('dentures_date', $defaults['dentures_date'] ?? '') }}"
                                        class="form-input-custom js-flatpickr-date-max-today"
                                        placeholder="Select date"
                                        data-required-message="Please select the dentures placement date."
                                        data-conditional-required readonly>
                                </div>
                            </div>
                        </div>
                    @endif


                    @if ($question['code'] === 'ortho_treatment')
                        <div id="ortho_date_box" class="booking-conditional-field hidden" data-booking-conditional
                            data-radio-name="{{ $questionName('ortho_treatment') }}" data-show-on="YES">
                            <div class="global-form-group" data-global-field>
                                <label class="global-form-label" for="orthoDate">
                                    Date of Completion
                                    <span class="required-mark">*</span>
                                </label>

                                <div class="global-control-wrap">
                                    <input type="text" id="orthoDate" name="ortho_date"
                                        value="{{ old('ortho_date', $defaults['ortho_date'] ?? '') }}"
                                        class="form-input-custom js-flatpickr-date-max-today"
                                        placeholder="Select date"
                                        data-required-message="Please select the orthodontic treatment completion date."
                                        data-conditional-required readonly>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="booking-section-card">
                <div class="global-form-group" data-global-field>
                    <label for="additional_concerns" class="global-form-label">
                        Additional Dental Concerns
                        <span class="field-optional">(Optional)</span>
                    </label>

                    <div class="global-form-textarea-wrap">
                        <textarea id="additional_concerns" name="additional_concerns" rows="4" maxlength="150"
                            class="form-input-custom global-form-textarea" placeholder="Write any additional concerns here...">{{ $additionalConcerns }}</textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@once
    <script>
        function initBookingDentalHistoryConditionals() {
            document
                .querySelectorAll(
                    '[data-booking-conditional]'
                )
                .forEach(box => {

                    if (
                        box.dataset
                        .bookingConditionalInitialized ===
                        'true'
                    ) {
                        return;
                    }

                    box.dataset
                        .bookingConditionalInitialized =
                        'true';

                    const radioName =
                        box.dataset.radioName;

                    const showOn =
                        box.dataset.showOn ||
                        'YES';

                    if (!radioName) {
                        return;
                    }

                    const form =
                        box.closest('form') ||
                        document;

                    const radios =
                        Array.from(
                            form.querySelectorAll(
                                `input[type="radio"][name="${CSS.escape(
                                radioName
                            )}"]`
                            )
                        );

                    if (!radios.length) {
                        return;
                    }

                    const conditionalFields =
                        Array.from(
                            box.querySelectorAll(
                                '[data-conditional-required]'
                            )
                        );

                    function clearConditionalField(
                        field
                    ) {
                        if (
                            field._flatpickr
                        ) {
                            field
                                ._flatpickr
                                .clear();
                        } else if (
                            field instanceof HTMLSelectElement
                        ) {
                            field.selectedIndex = 0;
                        } else if (
                            field.type !==
                            'checkbox' &&
                            field.type !==
                            'radio'
                        ) {
                            field.value = '';
                        }

                        field.classList.remove(
                            'is-invalid',
                            'is-valid'
                        );

                        window
                            .clearFormInputValidation?.(
                                field
                            );
                    }

                    function syncConditional() {
                        const selected =
                            radios.find(
                                radio =>
                                radio.checked
                            );

                        const shouldShow =
                            selected?.value ===
                            showOn;

                        box.classList.toggle(
                            'hidden',
                            !shouldShow
                        );

                        conditionalFields.forEach(
                            field => {
                                field.required =
                                    shouldShow;

                                if (
                                    !shouldShow
                                ) {
                                    clearConditionalField(
                                        field
                                    );
                                }
                            }
                        );
                    }

                    radios.forEach(radio => {
                        radio.addEventListener(
                            'change',
                            syncConditional
                        );
                    });

                    syncConditional();
                });
        }

        if (
            document.readyState ===
            'loading'
        ) {
            document.addEventListener(
                'DOMContentLoaded',
                initBookingDentalHistoryConditionals, {
                    once: true
                }
            );
        } else {
            initBookingDentalHistoryConditionals();
        }
    </script>
@endonce
