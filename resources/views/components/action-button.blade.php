@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'neutral',
    'icon',
    'label' => null,
    'tooltip' => null,
    'disabled' => false,
])

@php
    $variantClasses = [
        'edit' => 'ui-action-edit',
        'delete' => 'ui-action-delete',
        'warning' => 'ui-action-warning',
        'success' => 'ui-action-success',
        'reset' => 'ui-action-reset',
        'view' => 'ui-action-view',
        'neutral' => 'ui-action-neutral',
    ];

    $variantClass = $variantClasses[$variant] ?? $variantClasses['neutral'];
    $resolvedLabel = $label ?: ucfirst($variant);
    $resolvedTooltip = $tooltip ?: $resolvedLabel;

    $actionAttributes = $attributes
        ->merge([
            'aria-label' => $resolvedLabel,
            'data-tooltip' => $resolvedTooltip,
        ])
        ->class(['ui-action-btn', $variantClass]);
@endphp

@if ($href !== null)
    <a @if (!$disabled) href="{{ $href }}"
        @else
            aria-disabled="true"
            tabindex="-1" @endif
        {{ $actionAttributes }}>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $actionAttributes }}>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </button>
@endif
