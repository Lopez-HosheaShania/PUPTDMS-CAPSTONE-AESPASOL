@props([
    'id' => 'filterModal',
    'title' => 'Filters',

    'closeId' => null,
    'closeCallback' => null,

    'clearId' => null,
    'clearCallback' => null,
    'clearLabel' => 'Clear Filters',

    'cancelId' => null,
    'cancelCallback' => null,
    'cancelLabel' => 'Cancel',

    'applyId' => null,
    'applyCallback' => null,
    'applyLabel' => 'Apply Filters',

    'resultsId' => null,
])

<div id="{{ $id }}" {{ $attributes->class(['filter-drawer-wrapper']) }} aria-hidden="true">
    <div class="filter-drawer-overlay" aria-hidden="true"
        @if ($closeCallback) onclick="{{ $closeCallback }}" @endif></div>

    <div class="filter-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}Title">

        <div class="filter-drawer-header">

            <div class="filter-drawer-title">
                <i class="fa-solid fa-sliders"></i>

                <h2 id="{{ $id }}Title">
                    {{ $title }}
                </h2>
            </div>

            <button @if ($closeId) id="{{ $closeId }}" @endif type="button" class="modal-x"
                @if ($closeCallback) onclick="{{ $closeCallback }}" @endif aria-label="Close filters"
                data-tooltip="Close" data-tooltip-tone="neutral">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div class="filter-drawer-body">
            {{ $slot }}
        </div>

        <div class="filter-drawer-footer">

            <button @if ($clearId) id="{{ $clearId }}" @endif type="button"
                class="ui-btn ui-btn-secondary"
                @if ($clearCallback) onclick="{{ $clearCallback }}" @endif>
                <i class="fa-regular fa-trash-can"></i>
                <span>{{ $clearLabel }}</span>
            </button>

            <div class="filter-footer-actions">

                <button @if ($cancelId) id="{{ $cancelId }}" @endif type="button"
                    class="ui-btn ui-btn-secondary"
                    @if ($cancelCallback) onclick="{{ $cancelCallback }}" @endif>
                    <span>{{ $cancelLabel }}</span>
                </button>

                <button @if ($applyId) id="{{ $applyId }}" @endif type="button"
                    class="ui-btn ui-btn-primary"
                    @if ($applyCallback) onclick="{{ $applyCallback }}" @endif>

                    @if ($resultsId)
                        <span id="{{ $resultsId }}" class="filter-results-text">
                            {{ $applyLabel }}
                        </span>
                    @else
                        <span>{{ $applyLabel }}</span>
                    @endif
                </button>

            </div>

        </div>

    </div>
</div>
