@props([
    'id' => null,
    'name' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'required' => false,
    'uncheckedValue' => null,
    'wrapperClass' => null,
])

<label class="{{ trim('global-switch ' . ($wrapperClass ?? '')) }}">
    @if ($name && $uncheckedValue !== null)
        <input type="hidden" name="{{ $name }}" value="{{ $uncheckedValue }}" @disabled($disabled)>
    @endif

    <input type="checkbox" @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif value="{{ $value }}"
        @checked($checked) @disabled($disabled) @required($required)
        {{ $attributes->class(['global-switch-input']) }}>

    <span class="global-switch-track"></span>
</label>
