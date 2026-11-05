@props([
'id' => null,
'root' => '#mainContent',
'storageKey' => 'ViewToggleMode',
'listView' => null,
'gridView' => null,
'listLabel' => 'List',
'gridLabel' => 'Grid',
])

<div @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['view-toggle-container']) }} data-global-view-toggle
    data-view-root="{{ $root }}"
    data-storage-key="{{ $storageKey }}" @if ($listView) data-list-view="{{ $listView }}" @endif
    @if ($gridView) data-grid-view="{{ $gridView }}" @endif aria-label="View options">
    <span class="view-slider" aria-hidden="true"></span>

    <button type="button" class="btn-view-mode active" data-view-mode="list" title="List view" aria-label="List view"
        aria-pressed="true">
        <i class="fa-solid fa-list"></i>
        <span class="view-mode-label">
            {{ $listLabel }}
        </span>
    </button>

    <button type="button" class="btn-view-mode" data-view-mode="grid" title="Grid view" aria-label="Grid view"
        aria-pressed="false">
        <i class="fa-solid fa-table-cells-large"></i>
        <span class="view-mode-label">
            {{ $gridLabel }}
        </span>
    </button>
</div>