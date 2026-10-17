@props([
    'id',
    'infoId',
    'paginationId',

    'position' => 'bottom',

    'showEntries' => false,
    'pageSizeId' => null,
    'pageSizeCallback' => null,
    'pageSizeValue' => 10,
    'pageSizeLabel' => 'entries',

    'label' => 'entries',
    'paginator' => null,

    'total' => null,
    'from' => null,
    'to' => null,
    'currentPage' => null,
    'lastPage' => null,
])

@php
    $hasPaginator =
        $paginator &&
        method_exists($paginator, 'total') &&
        method_exists($paginator, 'currentPage') &&
        method_exists($paginator, 'lastPage');

    if ($hasPaginator) {
        $total = $total ?? $paginator->total();
        $from = $from ?? ($paginator->firstItem() ?? 0);
        $to = $to ?? ($paginator->lastItem() ?? 0);
        $currentPage = $currentPage ?? $paginator->currentPage();
        $lastPage = $lastPage ?? $paginator->lastPage();

        if (method_exists($paginator, 'perPage')) {
            $pageSizeValue = $paginator->perPage();
        }
    }

    $total = max(0, (int) ($total ?? 0));

    $from = $from !== null ? (int) $from : ($total > 0 ? 1 : 0);

    $to = $to !== null ? (int) $to : ($total > 0 ? min((int) $pageSizeValue, $total) : 0);

    $currentPage = max(1, (int) ($currentPage ?? 1));

    $lastPage = max(1, (int) ($lastPage ?? ceil($total / max(1, (int) $pageSizeValue))));
@endphp

<div id="{{ $id }}" data-current-page="{{ $currentPage }}" data-last-page="{{ $lastPage }}"
    data-total="{{ $total }}" data-from="{{ $from }}" data-to="{{ $to }}"
    {{ $attributes->class([
        'global-pagebar',
        'global-pagebar-top' => $position === 'top',
        'global-pagebar-bottom' => $position === 'bottom',
    ]) }}>
    <div class="global-pagebar-left">

        <span id="{{ $infoId }}" class="global-pagebar-info">
            @if ($total !== null && (int) $total > 0)
                Showing
                <strong>{{ $from ?? 1 }}</strong>–<strong>{{ $to ?? min((int) $pageSizeValue, (int) $total) }}</strong>
                of
                <strong>{{ (int) $total }}</strong>
                {{ $label }}
            @else
                Showing <strong>0</strong> {{ $label }}
            @endif
        </span>

        @if ($showEntries && $pageSizeId && $pageSizeCallback)
            <div class="global-page-size-control">
                <label for="{{ $pageSizeId }}">
                    Show
                </label>

                <div class="global-page-size-select" data-global-page-size data-page-size-input="#{{ $pageSizeId }}"
                    data-page-size-callback="{{ $pageSizeCallback }}">
                    <select id="{{ $pageSizeId }}" class="global-page-size-native" tabindex="-1" aria-hidden="true">
                        @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) $pageSizeValue === $size)>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" class=" global-page-size-trigger" data-page-size-trigger
                        aria-haspopup="listbox" aria-expanded="false">
                        <span data-page-size-value>
                            {{ (int) $pageSizeValue }}
                        </span>
                        <i class=" fa-solid fa-chevron-down "></i>
                    </button>

                    <div class=" global-page-size-menu " role="listbox">
                        @foreach ([10, 20, 50, 100] as $size)
                            <button type="button"
                                class="global-page-size-option {{ (int) $pageSizeValue === $size ? 'is-selected' : '' }}"
                                data-page-size-option data-value="{{ $size }}" role="option"
                                aria-selected="{{ (int) $pageSizeValue === $size ? 'true' : 'false' }}">
                                <span>
                                    {{ $size }}
                                </span>

                                <i class="fa-solid fa-check"></i>
                            </button>
                        @endforeach
                    </div>
                </div>
                <span>{{ $pageSizeLabel }}</span>
            </div>
        @endif

    </div>

    <div class="global-pagination-wrap">
        <div id="{{ $paginationId }}" class="global-pagination" aria-label="Pagination"></div>
    </div>
</div>
