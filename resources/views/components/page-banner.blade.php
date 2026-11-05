@props([
    'title' => null,
    'subtitle' => null,
    'eyebrow' => null,
    'eyebrowIcon' => null,
    'titleId' => null,
    'innerClass' => null,
    'actionsClass' => 'page-banner-actions',
])

<div {{ $attributes->class(['page-banner']) }}>
    <div class="{{ trim('page-banner-inner ' . ($innerClass ?? '')) }}">
        @isset($content)
            {{ $content }}
        @else
            <div>
                @isset($eyebrowContent)
                    <div class="page-greeting">
                        {{ $eyebrowContent }}
                    </div>
                @else
                    @if ($eyebrow)
                        <div class="page-greeting">
                            @if ($eyebrowIcon)
                                <i class="{{ $eyebrowIcon }}" aria-hidden="true"></i>
                            @endif
                            <span>{{ $eyebrow }}</span>
                        </div>
                    @endif
                @endisset

                @if ($title)
                    <h1 @if ($titleId) id="{{ $titleId }}" @endif class="page-title">
                        {{ $title }}
                    </h1>
                @endif

                @if ($subtitle)
                    <p class="page-subtitle">{{ $subtitle }}</p>
                @endif

                {{ $slot }}
            </div>
        @endisset

        @isset($actions)
            <div class="{{ $actionsClass }}">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
