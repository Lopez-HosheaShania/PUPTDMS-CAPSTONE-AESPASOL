@props([
    'id' => null,
    'name' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'floating' => false,
])

<select @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif @required($required) @disabled($disabled)
    @if ($multiple) multiple @endif
    @if ($floating) data-custom-select-floating @endif
    {{ $attributes->class(['form-select-custom', 'js-custom-select']) }}>
    {{ $slot }}
</select>
