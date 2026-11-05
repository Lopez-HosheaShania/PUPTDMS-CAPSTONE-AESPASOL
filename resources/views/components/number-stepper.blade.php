@props([
    'id' => null,
    'name' => null,
    'value' => null,
    'min' => null,
    'max' => null,
    'step' => 1,
    'inputmode' => 'numeric',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'decreaseLabel' => 'Decrease value',
    'increaseLabel' => 'Increase value',
    'wrapperClass' => null,
])

<div class="{{ trim('global-number-stepper ' . ($wrapperClass ?? '')) }}" data-global-number-stepper>
    <button type="button" class="global-number-stepper-btn" data-number-step="-1" aria-label="{{ $decreaseLabel }}"
        @disabled($disabled || $readonly)>
        <i class="fa-solid fa-minus" aria-hidden="true"></i>
    </button>

    <input type="number" @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif value="{{ $value }}"
        @if ($min !== null) min="{{ $min }}" @endif
        @if ($max !== null) max="{{ $max }}" @endif step="{{ $step }}"
        inputmode="{{ $inputmode }}" @required($required) @disabled($disabled) @readonly($readonly)
        data-number-stepper-input {{ $attributes->class(['global-number-stepper-input']) }}>

    <button type="button" class="global-number-stepper-btn" data-number-step="1" aria-label="{{ $increaseLabel }}"
        @disabled($disabled || $readonly)>
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
    </button>
</div>
