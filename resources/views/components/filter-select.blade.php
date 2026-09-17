@props([
    'id',
    'name' => null,

    'label' => 'Filter',
    'value' => '',
    'options' => [],

    'callback' => null,

    'icon' => 'fa-filter',
    'placeholder' => null,

    'class' => '',
    'menuAlign' => 'left',

    'searchable' => false,
    'multiple' => false,
    'searchPlaceholder' => 'Search options...',
])

@php
    $fieldName = $name ?: $id;

    $normalizedOptions = collect($options)
        ->map(function ($option, $key) {
            if (is_string($option)) {
                return [
                    'value' => (string) $key,
                    'label' => $option,
                    'icon' => null,
                    'tone' => null,
                    'count' => null,
                ];
            }

            return [
                'value' => (string) ($option['value'] ?? $key),

                'label' =>
                    $option['label'] ?? ucfirst(str_replace(['_', '-'], ' ', (string) ($option['value'] ?? $key))),

                'icon' => $option['icon'] ?? null,

                /*
                 * Pass the EXISTING global status class here.
                 *
                 * Examples:
                 * s-all
                 * s-upcoming
                 * s-rescheduled
                 * s-completed
                 * s-cancelled
                 * status-pending
                 * status-approved
                 * status-rejected
                 */
                'tone' => $option['tone'] ?? null,

                'count' => array_key_exists('count', $option) ? $option['count'] : null,
            ];
        })
        ->values();

    $selected =
        $normalizedOptions->first(fn($option) => (string) $option['value'] === (string) $value) ??
        $normalizedOptions->first();

    $selectedValue = (string) ($selected['value'] ?? ($value ?? ''));

    $selectedLabel = $selected['label'] ?? ($placeholder ?? 'Select');

    $selectedIcon = $selected['icon'] ?? $icon;

    $selectedTone = $selected['tone'] ?? '';

    $selectedCount = $selected['count'] ?? null;
@endphp

<div id="{{ $id }}" {{ $attributes->merge([
    'class' => 'global-filter-select ' . $class,
]) }}
    data-global-filter-select data-filter-select-callback="{{ $callback }}"
    data-filter-select-value="{{ $selectedValue }}" data-menu-align="{{ $menuAlign }}"
    data-filter-select-searchable="{{ $searchable ? 'true' : 'false' }}"
    data-filter-select-multiple="{{ $multiple ? 'true' : 'false' }}">
    <input type="hidden" id="{{ $id }}Input" name="{{ $fieldName }}" value="{{ $selectedValue }}"
        data-filter-select-input>

    <button type="button" class="global-filter-select-trigger" data-filter-select-trigger aria-haspopup="listbox"
        aria-expanded="false" aria-controls="{{ $id }}Menu">
        <span class="global-filter-select-main">

            <span class="global-filter-select-icon {{ $selectedTone }}" data-filter-select-icon
                data-tone="{{ $selectedTone }}">
                <i class="fa-solid {{ $selectedIcon }}" data-filter-select-icon-glyph></i>
            </span>

            <span class="global-filter-select-copy">
                <span class="global-filter-select-label">
                    {{ $label }}
                </span>

                <strong class="global-filter-select-value" data-filter-select-label>
                    {{ $selectedLabel }}
                </strong>
            </span>

        </span>

        <span class="global-filter-select-meta">

            <span
                class="global-filter-select-count
                       {{ $selectedCount === null ? 'hidden' : '' }}"
                data-filter-select-count>
                {{ $selectedCount }}
            </span>

            <i class="fa-solid fa-chevron-down
                       global-filter-select-chevron"></i>

        </span>
    </button>

    <div id="{{ $id }}Menu" class="global-filter-select-menu" data-filter-select-menu role="listbox"
        aria-label="{{ $label }}">

        @if ($searchable)
            <div class="global-filter-select-search">
                <i class="fa-solid fa-magnifying-glass"></i>

                <input type="text" class="global-filter-select-search-input" placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off" data-filter-select-search>

                <button type="button" class="search-clear global-filter-select-search-clear"
                    data-filter-select-search-clear aria-label="Clear search" title="Clear search" hidden>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        @endif

        @foreach ($normalizedOptions as $option)
            @php
                $isSelected = (string) $option['value'] === $selectedValue;
            @endphp

            <button type="button"
                class="
                    global-filter-select-option
                    {{ $option['tone'] ?? '' }}
                    {{ $isSelected ? 'is-active' : '' }}
                "
                role="option" aria-selected="{{ $isSelected ? 'true' : 'false' }}" data-filter-select-option
                data-value="{{ $option['value'] }}" data-label="{{ $option['label'] }}"
                data-icon="{{ $option['icon'] ?? $icon }}" data-tone="{{ $option['tone'] ?? '' }}"
                data-count="{{ $option['count'] ?? '' }}">
                <span class="global-filter-select-option-main">

                    <span class="global-filter-select-option-icon">
                        <i class="fa-solid
                                   {{ $option['icon'] ?? $icon }}"></i>
                    </span>

                    <span class="global-filter-select-option-label">
                        {{ $option['label'] }}
                    </span>

                </span>

                <span class="global-filter-select-option-meta">

                    @if ($multiple)
                        <span class="global-filter-select-checkbox" aria-hidden="true">
                            <i class="fa-solid fa-check"></i>
                        </span>
                    @else
                        <i class="fa-solid fa-check global-filter-select-check"></i>
                    @endif

                </span>
            </button>
        @endforeach
    </div>
</div>
