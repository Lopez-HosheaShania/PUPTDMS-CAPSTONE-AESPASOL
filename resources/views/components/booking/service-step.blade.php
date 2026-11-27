@props([
    'services',
    'selected' => '',
    'title' => 'Choose Your Dental Service',
    'subtitle' => 'Select the dental service for this appointment.',
])

<div class="step-content hidden">
    <div class="booking-step-shell">

        <div class="booking-step-header">
            <h2 class="booking-step-title">
                {{ $title }}
            </h2>

            <p class="booking-step-subtitle">
                {{ $subtitle }}
            </p>
        </div>

        <div class="booking-step-body">

            <div class="service-step-grid global-choice-group" role="radiogroup" aria-label="Service Type"
                data-global-field data-global-choice-group>
                @foreach ($services as $service)
                    @php
                        $serviceName = is_array($service) ? $service['name'] ?? '' : $service->name ?? '';

                        $serviceDescription = is_array($service) ? $service['desc'] ?? '' : $service->description ?? '';

                        $serviceImage = is_array($service) ? $service['img'] ?? null : null;

                        $isSelected = old('service_type', $selected) === $serviceName;
                    @endphp

                    <label class="service-option">

                        <input type="radio" name="service_type" value="{{ $serviceName }}"
                            class="service-option-input" data-required-message="Please select a dental service."
                            @checked($isSelected) required>

                        <div class="service-option-card">

                            <div class="service-option-main">

                                <div class="service-option-icon">

                                    @if ($serviceImage)
                                        <img src="{{ asset('images/' . $serviceImage . '.png') }}"
                                            class="service-option-image" alt="" aria-hidden="true">
                                    @else
                                        <i class="fa-solid fa-tooth" aria-hidden="true"></i>
                                    @endif

                                </div>

                                <div class="service-option-copy">

                                    <div class="service-option-topline">

                                        <p class="service-option-title">
                                            {{ $serviceName }}
                                        </p>

                                        <span class="service-option-badge">
                                            Available
                                        </span>

                                    </div>

                                    <p class="service-option-desc">
                                        {{ $serviceDescription ?: 'No description available.' }}
                                    </p>

                                </div>
                            </div>

                            <div class="service-option-arrow" aria-hidden="true">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>

                        </div>
                    </label>
                @endforeach
            </div>

        </div>
    </div>
</div>
