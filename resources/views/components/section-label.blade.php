@props([
    'label' => null,
    'icon' => null,
])

<div {{ $attributes->class(['global-section-label']) }}>
    @if ($icon)
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif

    <span>
        {{ $label ?? $slot }}
    </span>
</div>
