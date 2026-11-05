@props([
    'backUrl',
    'backLabel',
    'formTarget',
    'icon' => 'fa-regular fa-calendar-check',
    'title',
    'subtitle',
    'steps' => [],
    'totalSteps' => 5,
])

<div class="booking-page-header">

    <div class="booking-page-topbar">

        <x-button :href="$backUrl" variant="primary" size="sm" icon="fa-solid fa-arrow-left" data-discard-navigation
            data-discard-form-target="{{ $formTarget }}">
            {{ $backLabel }}
        </x-button>

        <span class="booking-step-counter">
            Step

            <span id="stepCounterText">
                1
            </span>

            of {{ $totalSteps }}
        </span>

    </div>

    <div class="booking-progress-track">

        <div id="headerProgressFill" class="booking-progress-fill">
            <span id="headerProgressPercent" class="booking-progress-percent">
                {{ intval(100 / max($totalSteps, 1)) }}%
            </span>
        </div>

    </div>

    <div class="booking-page-heading">

        <p class="booking-page-eyebrow">
            <i class="{{ $icon }}"></i>
            PUP TAGUIG DENTAL CLINIC
        </p>

        <h1 class="booking-page-title">
            {{ $title }}
        </h1>

        <p class="booking-page-subtitle">
            {{ $subtitle }}
        </p>

        @if (trim((string) $slot) !== '')
            <div class="mt-3">
                {{ $slot }}
            </div>
        @endif

    </div>

</div>


<div class="booking-stepper stepper-wrap-overflow">

    <div class="booking-stepper-row">

        @foreach ($steps as $index => $step)
            @php
                $number = $index + 1;
            @endphp

            <div class="booking-stepper-item">

                <div id="sc{{ $number }}" class="step-circle">
                    {{ $number }}
                </div>

                <span id="sl{{ $number }}" class="step-label">
                    {{ $step }}
                </span>

            </div>

            @if (!$loop->last)
                <div id="conn{{ $number }}" class="step-connector"></div>
            @endif
        @endforeach

    </div>

</div>
