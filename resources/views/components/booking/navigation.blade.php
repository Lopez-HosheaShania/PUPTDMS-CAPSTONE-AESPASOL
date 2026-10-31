@props([
    'showSubmit' => false,
    'submitLabel' => 'Submit',
    'submitIcon' => 'fa-solid fa-arrow-right',
])

<div id="navBtns" class="booking-navigation">

    <x-button id="prevBtn" type="button" variant="secondary" size="sm" icon="fa-solid fa-chevron-left">
        Previous
    </x-button>

    <x-button id="nextBtn" type="button" variant="primary" size="sm" icon="fa-solid fa-chevron-right"
        icon-position="right">
        Next
    </x-button>

    @if ($showSubmit)
        <x-button id="submitBtn" type="submit" variant="primary" size="sm" :icon="$submitIcon" icon-position="right"
            class="hidden">
            {{ $submitLabel }}
        </x-button>
    @endif

</div>
