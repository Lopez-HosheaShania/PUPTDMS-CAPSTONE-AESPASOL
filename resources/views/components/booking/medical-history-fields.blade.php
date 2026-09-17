@props([
    'questions',
    'diseases',
    'mode' => 'standard',
    'answers' => [],
    'defaults' => [],
    'selectedDiseases' => [],
    'isFemale' => false,
    'dynamicFemale' => false,
])

@php

    $isNested = $mode === 'nested';

    $medicalQuestionMap = collect($questions)->flatten(1)->keyBy('code');

    $selectedDiseaseValues = collect(old('diseases', collect($selectedDiseases)->all()));

    $fieldName = function (string $code) use ($isNested) {
        return $isNested ? "medical_answers[{$code}]" : $code;
    };

    $fieldValue = function (string $code, string $fallback = '') use ($isNested, $answers, $defaults) {
        if ($isNested) {
            return old("medical_answers.{$code}", $answers[$code] ?? $fallback);
        }

        return old($code, $defaults[$code] ?? $fallback);
    };

    $allergyName = function (string $code) use ($isNested) {
        return $isNested ? "medical_answers[{$code}]" : $code;
    };

    $allergyValue = function (string $code, string $fallback = '') use ($isNested, $answers, $defaults) {
        if ($isNested) {
            return old("medical_answers.{$code}", $answers[$code] ?? $fallback);
        }

        return old($code, $defaults[$code] ?? $fallback);
    };
@endphp

<div class="section-stack grid gap-4">
    <div class="booking-section-card">

        <p class="booking-section-card-title">
            <i class="fa-solid fa-heart-pulse text-xs"></i>
            General Health

            <span class="booking-section-card-title-line"></span>
        </p>

        <x-booking-question :name="$fieldName('good_health')" :label="$medicalQuestionMap->get('good_health')['label'] ?? 'Are you in good health?'" :checked-value="$fieldValue('good_health')" required />

        <div id="good_health_box"
            class="{{ $fieldValue('good_health') === 'NO' ? '' : 'hidden' }} booking-conditional-field">
            <div class="global-form-group" data-global-field>
                <label class="global-form-label" for="good_health_details">
                    {{ $medicalQuestionMap->get('good_health_details')['label'] ?? 'If NO, please specify:' }}
                </label>

                <input type="text" id="good_health_details" name="{{ $fieldName('good_health_details') }}"
                    value="{{ $fieldValue('good_health_details') }}" maxlength="150" class="form-input-custom"
                    placeholder="Input here" data-required-when-visible="true"
                    data-required-message="Please describe why you are not currently in good health.">

                <p class="field-help">
                    <span id="goodHealthCount">
                        {{ strlen($fieldValue('good_health_details')) }}/150
                    </span>
                </p>
            </div>
        </div>

        <x-booking-question :name="$fieldName('had_medical_exam')" :label="$medicalQuestionMap->get('had_medical_exam')['label'] ?? 'Have you had a medical examination?'" :checked-value="$fieldValue('had_medical_exam')" required />

        <div id="medical_exam_box"
            class="{{ $fieldValue('had_medical_exam') === 'YES' ? '' : 'hidden' }} booking-conditional-field">
            <div class="global-form-group" data-global-field>
                <label class="global-form-label" for="medicalExamDate">
                    {{ $medicalQuestionMap->get('medical_exam_date')['label'] ?? 'Date of last medical examination' }}
                </label>

                <div class="global-control-wrap">
                    <i class="fa-regular fa-calendar global-control-icon" aria-hidden="true"></i>

                    <input type="text" id="medicalExamDate" name="{{ $fieldName('medical_exam_date') }}"
                        value="{{ $fieldValue('medical_exam_date') }}"
                        class="form-input-custom global-form-icon js-flatpickr-date-max-today"
                        placeholder="Select date" data-required-when-visible="true"
                        data-required-message="Please select the date of your last medical examination." readonly>
                </div>
            </div>
        </div>

        <x-booking-question :name="$fieldName('under_treatment')" :label="$medicalQuestionMap->get('under_treatment')['label'] ??
            'Are you currently receiving treatment for any illness?'" :checked-value="$fieldValue('under_treatment')" required />

        <div id="treatment_box"
            class="{{ $fieldValue('under_treatment') === 'YES' ? '' : 'hidden' }} booking-conditional-field">
            <div class="global-form-group" data-global-field>
                <label class="global-form-label" for="treatment_details">
                    {{ $medicalQuestionMap->get('treatment_details')['label'] ?? 'If YES, please specify:' }}
                </label>

                <input type="text" id="treatment_details" name="{{ $fieldName('treatment_details') }}"
                    value="{{ $fieldValue('treatment_details') }}" maxlength="150" class="form-input-custom"
                    placeholder="Input here" data-required-when-visible="true"
                    data-required-message="Please specify the illness or treatment you are currently receiving.">

                <p class="field-help">
                    <span id="treatmentCount">
                        {{ strlen($fieldValue('treatment_details')) }}/150
                    </span>
                </p>
            </div>
        </div>

        <x-booking-question :name="$fieldName('hospitalized')" :label="$medicalQuestionMap->get('hospitalized')['label'] ?? 'Have you ever been hospitalized?'" :checked-value="$fieldValue('hospitalized')" required />

        <div id="hospital_box"
            class="{{ $fieldValue('hospitalized') === 'YES' ? '' : 'hidden' }} booking-conditional-field">
            <div class="global-form-group" data-global-field>
                <label class="global-form-label" for="hospital_details">
                    {{ $medicalQuestionMap->get('hospital_details')['label'] ?? 'If YES, please provide details:' }}
                </label>

                <input type="text" id="hospital_details" name="{{ $fieldName('hospital_details') }}"
                    value="{{ $fieldValue('hospital_details') }}" maxlength="150" class="form-input-custom"
                    placeholder="Input here" data-required-when-visible="true"
                    data-required-message="Please provide details about your hospitalization.">

                <p class="field-help">
                    <span id="hospitalCount">
                        {{ strlen($fieldValue('hospital_details')) }}/150
                    </span>
                </p>
            </div>
        </div>

    </div>

    <div class="booking-section-card mt-2">

        <p class="booking-section-card-title">
            <i class="fa-solid fa-triangle-exclamation text-xs"></i>
            Allergies

            <span class="booking-section-card-title-line"></span>
        </p>

        @foreach (collect($questions['allergies'] ?? [])->where('type', 'bool') as $question)
            <x-booking-question :name="$allergyName($question['code'])" :label="$question['label']" :checked-value="$allergyValue($question['code'])" required />
        @endforeach

        <div class="global-form-group booking-field-spacing" data-global-field>
            <label class="global-form-label">
                {{ $medicalQuestionMap->get('allergy_others')['label'] ?? 'Others (please specify):' }}
            </label>

            <input type="text" name="{{ $allergyName('allergy_others') }}"
                value="{{ $allergyValue('allergy_others') }}" class="form-input-custom" placeholder="Input here">
        </div>

    </div>

    <div class="booking-section-card">

        <p class="booking-section-card-title">
            <i class="fa-solid fa-pills text-xs"></i>
            Medications

            <span class="booking-section-card-title-line"></span>
        </p>

        <x-booking-question :name="$fieldName('medication')" :label="$medicalQuestionMap->get('medication')['label'] ??
            'Are you taking any prescription or non-prescription medication?'" :checked-value="$fieldValue('medication')" required />

        <div id="medication_box"
            class="{{ $fieldValue('medication') === 'YES' ? '' : 'hidden' }} booking-conditional-field">
            <div class="global-form-group" data-global-field>
                <label class="global-form-label" for="medication_details">
                    {{ $medicalQuestionMap->get('medication_details')['label'] ?? 'If YES, please specify:' }}
                </label>

                <input type="text" id="medication_details" name="{{ $fieldName('medication_details') }}"
                    value="{{ $fieldValue('medication_details') }}" maxlength="150" class="form-input-custom"
                    placeholder="Input here" data-required-when-visible="true"
                    data-required-message="Please specify the medication you are currently taking.">

                <p class="field-help">
                    <span id="medicationCount">
                        {{ strlen($fieldValue('medication_details')) }}/150
                    </span>
                </p>
            </div>
        </div>

    </div>

    @if ($dynamicFemale)

        <div class="booking-section-card hidden" id="forWomenSection">
            <p class="booking-section-card-title">
                <i class="fa-solid fa-venus text-xs"></i>
                For Women Only

                <span class="booking-section-card-title-line"></span>
            </p>

            @foreach ($questions['women'] ?? [] as $question)
                <x-booking-question :name="$fieldName($question['code'])" :label="$question['label']" :checked-value="$fieldValue($question['code'])" required />
            @endforeach
        </div>

        <div id="nonFemaleDefaults">
            @foreach ($questions['women'] ?? [] as $question)
                <input type="hidden" name="{{ $fieldName($question['code']) }}" value="NO">
            @endforeach
        </div>
    @elseif ($isFemale)
        <div class="booking-section-card" id="forWomenSection">
            <p class="booking-section-card-title">
                <i class="fa-solid fa-venus text-xs"></i>
                For Women Only

                <span class="booking-section-card-title-line"></span>
            </p>

            @foreach ($questions['women'] ?? [] as $question)
                <x-booking-question :name="$fieldName($question['code'])" :label="$question['label']" :checked-value="$fieldValue($question['code'])" required />
            @endforeach
        </div>
    @else
        @foreach ($questions['women'] ?? [] as $question)
            <input type="hidden" name="{{ $fieldName($question['code']) }}" value="NO">
        @endforeach

    @endif

    <div class="booking-section-card">

        <p class="booking-section-card-title">
            <i class="fa-solid fa-stethoscope text-xs"></i>
            Medical Conditions

            <span class="booking-section-card-title-line"></span>
        </p>

        <p class="booking-section-description">
            Please indicate below if you presently have or
            have ever had any of the following.
            Select only if applicable.
        </p>

        <div class="medical-condition-grid">

            @foreach ($diseases as $disease)
                @php
                    $checked = $selectedDiseaseValues->contains($disease->code);
                @endphp

                <label class="global-checkbox-row">

                    <input type="checkbox" name="diseases[]" value="{{ $disease->code }}"
                        class="global-checkbox-input" @checked($checked)>

                    <span class="global-checkbox-label">
                        {{ $disease->label }}
                    </span>

                </label>
            @endforeach

        </div>

    </div>

</div>

<div class="booking-section-card mt-4">

    <p class="booking-section-card-title">
        <i class="fa-solid fa-smoking text-xs"></i>
        Tobacco Use

        <span class="booking-section-card-title-line"></span>
    </p>

    <x-booking-question :name="$fieldName('tobacco_use')" :label="$medicalQuestionMap->get('tobacco_use')['label'] ?? 'Do you use tobacco products or any derivatives?'" :checked-value="$fieldValue('tobacco_use')" required />

    <div id="tobacco_details"
        class="{{ $fieldValue('tobacco_use') === 'YES' ? '' : 'hidden' }} booking-conditional-field">

        <div class="booking-two-field-stack">

            <div class="global-form-group" data-global-field>
                <label class="global-form-label">
                    {{ $medicalQuestionMap->get('tobacco_per_day')['label'] ?? 'How much per day:' }}
                </label>

                <div class="global-number-stepper" data-global-number-stepper>

                    <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                        aria-label="Decrease">

                        <i class="fa-solid fa-minus"></i>

                    </button>


                    <input type="number" name="{{ $fieldName('tobacco_per_day') }}"
                        value="{{ $fieldValue('tobacco_use') === 'YES' ? $fieldValue('tobacco_per_day', '1') : '' }}"
                        min="1" max="100" step="1" class="global-number-stepper-input"
                        data-number-stepper-input data-required-when-visible="true"
                        data-required-message="Please enter the tobacco amount per day.">

                    <button type="button" class="global-number-stepper-btn" data-number-step="1"
                        aria-label="Increase">
                        <i class="fa-solid fa-plus"></i>
                    </button>

                </div>
            </div>

            <div class="global-form-group" data-global-field>
                <label class="global-form-label">
                    {{ $medicalQuestionMap->get('tobacco_per_week')['label'] ?? 'Per week:' }}
                </label>

                <div class="global-number-stepper" data-global-number-stepper>

                    <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                        aria-label="Decrease">

                        <i class="fa-solid fa-minus"></i>
                    </button>

                    <input type="number" name="{{ $fieldName('tobacco_per_week') }}"
                        value="{{ $fieldValue('tobacco_use') === 'YES' ? $fieldValue('tobacco_per_week', '1') : '' }}"
                        min="1" max="7" step="1" class="global-number-stepper-input"
                        data-number-stepper-input data-required-when-visible="true"
                        data-required-message="Please enter the tobacco amount per week.">

                    <button type="button" class="global-number-stepper-btn" data-number-step="1"
                        aria-label="Increase">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>

        </div>

    </div>

</div>

<div class="booking-section-card">

    <p class="booking-section-card-title">
        <i class="fa-solid fa-head-side-mask text-xs"></i>
        Do You Suffer From

        <span class="booking-section-card-title-line"></span>
    </p>

    @foreach ($questions['symptoms'] ?? [] as $question)
        <x-booking-question :name="$fieldName($question['code'])" :label="$question['label']" :checked-value="$fieldValue($question['code'])" required />
    @endforeach

</div>

<div class="booking-section-card">

    <p class="booking-section-card-title">
        <i class="fa-solid fa-phone-volume text-xs"></i>
        Emergency Contact

        <span class="booking-section-card-title-line"></span>
    </p>

    <div class="emergency-fields-stack">

        <div class="global-form-group" data-global-field>
            <label for="emergency_person" class="global-form-label">
                Person to contact in case of emergency
                <span class="required-mark">*</span>
            </label>

            <input type="text" id="emergency_person" name="emergency_person" maxlength="50"
                value="{{ old('emergency_person', $defaults['emergency_person'] ?? '') }}" class="form-input-custom"
                placeholder="Full name" data-validation-rule="personName"
                data-required-message="Please enter the emergency contact person's name." required>
        </div>


        <div class="global-form-group" data-global-field>
            <label for="emergency_number" class="global-form-label">
                Contact Number

                <span class="required-mark">*</span>
            </label>

            <input type="tel" id="emergency_number" name="emergency_number"
                value="{{ old('emergency_number', $defaults['emergency_number'] ?? '') }}" inputmode="numeric"
                autocomplete="tel" maxlength="13" pattern="09[0-9]{2}\s?[0-9]{3}\s?[0-9]{4}"
                class="form-input-custom" placeholder="0000 000 0000" data-validation-rule="philippineMobile"
                data-required-message="Please enter an emergency contact number."
                data-pattern-message="Contact number must start with 09 and contain exactly 11 digits." required>
        </div>


        <div class="global-form-group" data-global-field>
            <label for="emergency_relation" class="global-form-label">
                Relation to Patient

                <span class="required-mark">*</span>
            </label>

            @php
                $selectedRelation = old('emergency_relation', $defaults['emergency_relation'] ?? '');
            @endphp

            <select id="emergency_relation" name="emergency_relation" class="js-custom-select"
                data-placeholder="Select relation"
                data-required-message="Please select the emergency contact's relationship to the patient." required>
                <option value="" disabled @selected($selectedRelation === '')>
                    Select relation
                </option>

                @foreach (['Father', 'Mother', 'Uncle', 'Auntie', 'Brother', 'Sister', 'Grandmother', 'Grandfather', 'Cousin', 'Legal Guardian', 'Friend', 'Other Relative'] as $relation)
                    <option value="{{ $relation }}" @selected($selectedRelation === $relation)>
                        {{ $relation }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
