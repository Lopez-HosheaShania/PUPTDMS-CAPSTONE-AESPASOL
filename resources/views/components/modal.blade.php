@props([
    'id',
    'title' => null,
    'subtitle' => null,
    'subtitleId' => null,
    'icon' => null,
    'size' => 'md',
    'theme' => null,

    'static' => false,
    'noInitialFocus' => false,
    'initialFocusTitle' => false,
    'showClose' => true,

    'titleId' => null,
    'descriptionId' => null,
    'closeLabel' => null,

    'cardClass' => null,
    'headerClass' => null,
    'bodyClass' => null,
    'footerClass' => null,

    'cardAttributes' => [],
    'closeAttributes' => [],

    'formId' => null,
    'formClass' => null,
    'formAttributes' => [],
])

@php
    $sizeClasses = [
        'sm' => 'modal-sm',
        'md' => 'modal-md',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
    ];

    $modalTitleId = $titleId ?: $id . 'Title';

    $rootClasses = array_filter(['ui-modal', $theme ? 'modal-theme-' . $theme : null]);

    $cardClasses = array_filter(['ui-modal-card', $sizeClasses[$size] ?? $sizeClasses['md'], $cardClass]);

    $headerClasses = array_filter(['modal-hd', $headerClass]);

    $bodyClasses = array_filter(['modal-bd', $bodyClass]);

    $footerClasses = array_filter(['modal-ft', $footerClass]);

    $cardAttributeBag = new \Illuminate\View\ComponentAttributeBag($cardAttributes);

    $resolvedCloseAttributes = !empty($closeAttributes) ? $closeAttributes : ['data-modal-close' => $id];

    $closeAttributeBag = new \Illuminate\View\ComponentAttributeBag($resolvedCloseAttributes);

    $formAttributeBag = new \Illuminate\View\ComponentAttributeBag($formAttributes);
@endphp

<div id="{{ $id }}" role="dialog" aria-modal="true" aria-hidden="true"
    @if ($title) aria-labelledby="{{ $modalTitleId }}" @endif
    @if ($descriptionId) aria-describedby="{{ $descriptionId }}" @endif
    @if ($static) data-modal-static @endif
    @if ($noInitialFocus) data-modal-no-initial-focus @endif {{ $attributes->class($rootClasses) }}>
    <div {{ $cardAttributeBag->class($cardClasses) }}>

        @if ($title || $subtitle !== null || isset($subtitleContent) || $icon || $showClose || isset($header))
            <div class="{{ implode(' ', $headerClasses) }}">

                @isset($header)
                    {{ $header }}
                @else
                    <div class="modal-heading">

                        @if ($icon)
                            <div class="modal-icon" aria-hidden="true">
                                <i class="{{ $icon }}"></i>
                            </div>
                        @endif

                        <div class="modal-copy">

                            @if ($title)
                                <h2 id="{{ $modalTitleId }}" class="modal-title"
                                    @if ($initialFocusTitle) tabindex="-1"
                                        data-modal-initial-focus @endif>
                                    {{ $title }}
                                </h2>
                            @endif

                            @if ($subtitle !== null || isset($subtitleContent))
                                <p @if ($subtitleId) id="{{ $subtitleId }}" @endif
                                    class="modal-subtitle">
                                    @isset($subtitleContent)
                                        {{ $subtitleContent }}
                                    @else
                                        {{ $subtitle }}
                                    @endisset
                                </p>
                            @endif

                        </div>
                    </div>

                    @if ($showClose)
                        <button type="button" {{ $closeAttributeBag->class(['modal-x']) }}
                            aria-label="{{ $closeLabel ?: 'Close ' . ($title ?: 'modal') }}">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    @endif
                @endisset

            </div>
        @endif


        @if ($formId)
            <form id="{{ $formId }}" {{ $formAttributeBag->class([$formClass]) }}>
                <div class="{{ implode(' ', $bodyClasses) }}">
                    {{ $slot }}
                </div>

                @if (isset($footerRaw))
                    {{ $footerRaw }}
                @elseif (isset($footer))
                    <div class="{{ implode(' ', $footerClasses) }}">
                        {{ $footer }}
                    </div>
                @endif
            </form>
        @else
            <div class="{{ implode(' ', $bodyClasses) }}">
                {{ $slot }}
            </div>

            @if (isset($footerRaw))
                {{ $footerRaw }}
            @elseif (isset($footer))
                <div class="{{ implode(' ', $footerClasses) }}">
                    {{ $footer }}
                </div>
            @endif
        @endif

    </div>
</div>
