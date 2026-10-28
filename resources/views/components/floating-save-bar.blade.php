@props(['id', 'title' => 'Unsaved changes', 'subtitle' => ''])

<div id="{{ $id }}"
    {{ $attributes->class(['floating-save-bar'])->merge([
        'data-floating-save-bar' => 'true',
        'aria-live' => 'polite',
        'aria-hidden' => 'true',
    ]) }}>
    <div class="fsb-text">
        <span class="fsb-title">
            {{ $title }}
        </span>

        <span class="fsb-sub">
            {{ $subtitle }}
        </span>
    </div>

    <div class="fsb-actions">
        {{ $actions }}
    </div>
</div>
