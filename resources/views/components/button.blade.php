@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'disabled' => false,
    'shimmer' => false,
])

@php
    $variantClasses = [
        'primary' => 'ui-btn-primary',
        'secondary' => 'ui-btn-secondary',
        'danger' => 'ui-btn-danger',
        'delete' => 'ui-btn-delete',
        'edit' => 'ui-btn-edit',
        'success' => 'ui-btn-success',
        'warning' => 'ui-btn-warning',
        'reset-password' => 'ui-btn-reset-password',
        'ghost-light' => 'ui-btn-ghost-light',
    ];

    $sizeClasses = [
        'sm' => 'ui-btn-sm',
        'compact' => 'ui-btn-compact',
    ];

    $buttonClasses = [
        'ui-btn',
        $variant ? $variantClasses[$variant] ?? $variantClasses['primary'] : null,
        $size ? $sizeClasses[$size] ?? null : null,
        'ui-btn-shimmer' => $shimmer,
    ];
@endphp

@if ($href !== null)
    <a @if (!$disabled) href="{{ $href }}"
        @else
            aria-disabled="true"
            tabindex="-1" @endif
        {{ $attributes->class($buttonClasses) }}>
        @if ($icon && $iconPosition === 'left')
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif

        {{ $slot }}

        @if ($icon && $iconPosition === 'right')
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class($buttonClasses) }}>
        @if ($icon && $iconPosition === 'left')
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif

        {{ $slot }}

        @if ($icon && $iconPosition === 'right')
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
    </button>
@endif
