@props([
    'label' => null,
    'for' => null,
    'required' => false,
    'optional' => false,
    'showError' => true,
    'errorFor' => null,
    'errorId' => null,
    'labelClass' => null,
])

@php
    $resolvedErrorFor = $errorFor ?: $for;
@endphp

<div data-global-field {{ $attributes->class(['global-form-group']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif
            class="{{ trim('global-form-label ' . ($labelClass ?? '')) }}">
            {{ $label }}

            @if ($required)
                <span class="required-mark">*</span>
            @elseif ($optional)
                <span class="field-optional">(Optional)</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @isset($help)
        {{ $help }}
    @endisset

    @if ($showError && $resolvedErrorFor)
        <div @if ($errorId) id="{{ $errorId }}" @endif class="global-field-error"
            data-error-for="{{ $resolvedErrorFor }}" aria-live="polite" aria-hidden="true"></div>
    @endif
</div>
