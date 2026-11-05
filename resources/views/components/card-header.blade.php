@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'titleId' => null,
    'actionsClass' => null,
])

<div {{ $attributes->class(['card-header']) }}>
    @isset($left)
        {{ $left }}
    @else
        <div class="card-header-left">
            @if ($icon)
                <div class="card-header-icon" aria-hidden="true">
                    <i class="{{ $icon }}"></i>
                </div>
            @endif

            @if ($title || $subtitle)
                <div>
                    @if ($title)
                        <h2 @if ($titleId) id="{{ $titleId }}" @endif class="card-title">
                            {{ $title }}
                        </h2>
                    @endif

                    @if ($subtitle)
                        <div class="card-subtitle">{{ $subtitle }}</div>
                    @endif
                </div>
            @endif

            {{ $slot }}
        </div>
    @endisset

    @isset($actions)
        <div class="{{ trim('card-header-right ' . ($actionsClass ?? '')) }}">
            {{ $actions }}
        </div>
    @endisset
</div>
