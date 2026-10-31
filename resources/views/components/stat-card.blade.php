@props([
    'label' => null,
    'value' => null,
    'tone' => null,
    'icon' => null,
    'footer' => null,
    'footerIcon' => null,
    'valueClass' => 'stat-value',
    'valueAttributes' => [],
])

@php
    $toneClass = null;

    if ($tone) {
        $toneClass = str_starts_with($tone, 's-') ? $tone : 's-' . $tone;
    }

    $valueBag = new \Illuminate\View\ComponentAttributeBag($valueAttributes);
@endphp

<div {{ $attributes->class(['stat-card', $toneClass]) }}>
    <div class="stat-card-info">
        @if ($label)
            <div class="stat-label">{{ $label }}</div>
        @endif

        @isset($customValue)
            {{ $customValue }}
        @else
            @if ($value !== null)
                <div {{ $valueBag->class([$valueClass]) }}>{{ $value }}</div>
            @endif
        @endisset

        @isset($footerContent)
            {{ $footerContent }}
        @else
            @if ($footer)
                <div class="stat-footer">
                    @if ($footerIcon)
                        <i class="{{ $footerIcon }}" aria-hidden="true"></i>
                    @endif
                    {{ $footer }}
                </div>
            @endif
        @endisset

        {{ $slot }}
    </div>

    @if ($icon)
        <div class="stat-icon" aria-hidden="true">
            <i class="{{ $icon }}"></i>
        </div>
    @endif
</div>
