@props([
    'label' => 'items',
    'variant' => 'list',
    'target' => null,
    'expanded' => false,
])

@if ($variant === 'disclosure')
    <div class="global-show-more-controls global-show-more-controls-inline" data-show-more-controls>
        <button type="button" class="global-show-more-btn global-show-more-btn-inline" data-show-more-button
            data-mode="{{ $expanded ? 'less' : 'more' }}" aria-expanded="{{ $expanded ? 'true' : 'false' }}"
            @if ($target) aria-controls="{{ $target }}" @endif>
            <span data-show-more-text>
                {{ $expanded ? 'Show less' : 'Show more' }}
            </span>

            <i class="fa-solid fa-chevron-down global-accordion-chevron" aria-hidden="true"></i> </button>
    </div>
@else
    <div class="global-show-more-controls" data-show-more-controls hidden>
        <button type="button" class="ui-btn ui-btn-secondary global-show-more-btn" data-show-more-button
            data-show-more-label="{{ $label }}" data-mode="more">
            <span data-show-more-text>
                Show more
            </span>

            <i class="fa-solid fa-chevron-down"></i>
        </button>
    </div>
@endif
