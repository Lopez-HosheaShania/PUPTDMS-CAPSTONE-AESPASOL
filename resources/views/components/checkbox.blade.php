@props([
    'id' => null,
    'name' => null,
    'value' => '1',
    'label' => null,
    'checked' => false,
    'disabled' => false,
    'required' => false,
    'uncheckedValue' => null,
    'wrapperClass' => null,
    'labelClass' => 'global-checkbox-label',
])

<label class="{{ trim('global-checkbox-row ' . ($wrapperClass ?? '')) }}"
    @if ($id) for="{{ $id }}" @endif>
    @if ($name && $uncheckedValue !== null)
        <input type="hidden" name="{{ $name }}" value="{{ $uncheckedValue }}" @disabled($disabled)>
    @endif

    <input type="checkbox" @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif value="{{ $value }}"
        @checked($checked) @disabled($disabled) @required($required)
        {{ $attributes->class(['global-checkbox-input']) }}>

    @if ($label || !$slot->isEmpty())
        <span class="{{ $labelClass }}">
            @if (!$slot->isEmpty())
                {{ $slot }}
            @else
                {{ $label }}
            @endif
        </span>
    @endif
</label>
