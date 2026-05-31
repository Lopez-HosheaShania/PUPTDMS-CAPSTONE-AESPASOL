@props([
    'id',
    'name' => '',
    'placeholder' => '',
    'value' => '',
    'class' => '',
    'type' => 'text',
    'isTextarea' => false,
    'rows' => 4,
    'maxlength' => '',
])

<div class="voice-input-wrap" data-voice-field>
    @if ($isTextarea)
        <textarea id="{{ $id }}" name="{{ $name }}" placeholder="{{ $placeholder }}" rows="{{ $rows }}"
            maxlength="{{ $maxlength }}" class="{{ $class }} voice-enabled-input">{{ $value }}</textarea>
    @else
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}"
            placeholder="{{ $placeholder }}" value="{{ $value }}" maxlength="{{ $maxlength }}"
            class="{{ $class }} voice-enabled-input">
    @endif

    <span id="status-{{ $id }}" class="voice-status hidden" data-voice-status></span>
</div>
