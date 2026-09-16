@extends('layouts.app')

@php
    $layoutRole = $layoutRole ?? 'admin';
    $isDentistView = $isDentistView ?? false;

    $pageShellClass = $pageShellClass ?? ($isDentistView ? 'app-page-shell' : 'app-page-shell');

    $inventoryRouteNames = $inventoryRouteNames ?? [
        'data' => 'admin.inventory.data',
        'store' => 'admin.inventory.store',
        'update' => 'admin.inventory.update',
        'destroy' => 'admin.inventory.destroy',
    ];
@endphp

@section('layout-role', $layoutRole)

@section('title', 'Inventory')

@section('styles')
    @vite('resources/css/pages/shared/inventory.css')
@endsection

@section('content')

    <main id="mainContent"
        class="{{ $pageShellClass }}
        {{ $isDentistView ? 'dentist-inventory-page' : 'admin-inventory-page' }}
        page-enter
        inventory-page
        mode-list">

        <div class="{{ $isDentistView ? 'w-full' : 'full' }} inventory-page-shell">

            @if ($isDentistView)
                <div class="inventory-header-wrap">
                    <div class="dentist-hero">
                        <div class="dentist-hero-content">
                            <div class="inventory-title-left">
                                <div class="dentist-hero-icon">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </div>

                                <div class="min-w-0">
                                    <div class="dentist-hero-eyebrow">
                                        <i class="fa-solid fa-tooth"></i>
                                        Inventory Management
                                    </div>

                                    <h2 class="dentist-hero-title">
                                        Inventory
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="page-banner inventory-admin-banner">
                    <div class="page-banner-inner">
                        <div class="min-w-0">
                            <h1 class="page-title">
                                Admin Inventory
                            </h1>
                        </div>
                    </div>
                </div>
            @endif

            <div class="relative z-10 mt-4 pb-8 inventory-page-content">

                <div class="stat-grid inventory-stat-grid" id="inventoryStats">
                    <div class="stat-card s-crimson">
                        <div class="stat-card-info">
                            <div class="stat-label">Total Items</div>
                            <div class="stat-value" id="statTotal">—</div>
                            <div class="stat-footer">all categories</div>
                        </div>
                        <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                    </div>
                    <div class="stat-card s-blue">
                        <div class="stat-card-info">
                            <div class="stat-label">Medicines</div>
                            <div class="stat-value" id="statMedicine">—</div>
                            <div class="stat-footer">pharmaceutical</div>
                        </div>
                        <div class="stat-icon"><i class="fa-solid fa-pills"></i></div>
                    </div>
                    <div class="stat-card s-green">
                        <div class="stat-card-info">
                            <div class="stat-label">Supplies</div>
                            <div class="stat-value" id="statSupplies">—</div>
                            <div class="stat-footer">consumables</div>
                        </div>
                        <div class="stat-icon"><i class="fa-solid fa-syringe"></i></div>
                    </div>
                    <div class="stat-card s-amber">
                        <div class="stat-card-info">
                            <div class="stat-label">Low Stock</div>
                            <div class="stat-value" id="statLow">—</div>
                            <div class="stat-footer">need restocking</div>
                        </div>
                        <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                </div>

                <div class="table-card">

                    <div class="table-toolbar">

                        <div class="table-toolbar-title">
                            <div class="tab-group" role="tablist" aria-label="Inventory category">

                                <button type="button" data-tab="all" aria-selected="true" class="tab-btn active"
                                    onclick="setTab('all', this)">
                                    All
                                </button>

                                <button type="button" data-tab="medicine" aria-selected="false" class="tab-btn"
                                    onclick="setTab('medicine', this)">
                                    Medicine
                                </button>

                                <button type="button" data-tab="supplies" aria-selected="false" class="tab-btn"
                                    onclick="setTab('supplies', this)">
                                    Supplies
                                </button>

                            </div>
                        </div>

                        <div class="table-toolbar-search">
                            <div class="voice-search-row">

                                <x-search-bar id="searchInput" placeholder="Search Stock No."
                                    callback="handleInventorySearch" :debounce="250" class="flex-1" />

                                <x-voice-input target="#searchInput" status-id="invVoiceStatus"
                                    label="Voice search inventory" title="Voice search" />

                            </div>
                        </div>

                        <div class="table-toolbar-actions">

                            <button id="filterBtn" type="button" onclick="openFilterDrawer('filterModal')"
                                class="global-filter-btn">

                                <i class="fa-solid fa-sliders"></i>
                                <span>Filter</span>
                                <span id="filterBadge" class="filter-badge">
                                </span>
                            </button>

                            <x-view-toggle id="inventoryViewToggle" root="#mainContent" storage-key="inventoryViewMode" />

                            <button id="externalClearFilterBtn" type="button" onclick="clearFilterPanel()"
                                class="global-filter-reset-btn hidden" title="Reset filters">

                                <i class="fa-solid fa-rotate-left"></i>
                            </button>

                            <button type="button" onclick="openAddModal()"
                                class="ui-btn ui-btn-primary ui-btn-sm table-toolbar-primary-action">

                                <i class="fa-solid fa-plus"></i>
                                <span>Add Item</span>
                            </button>

                        </div>

                    </div>

                    <x-pagination-bar id="inventoryPaginationTopBar" info-id="inventoryPageInfoTop"
                        pagination-id="inventoryPaginationTop" position="top" :show-entries="true"
                        page-size-id="inventoryPerPageSelect" page-size-callback="handleInventoryPerPageChange"
                        :page-size-value="10" page-size-label="per page" label="entries" hidden />

                    <div id="tableWrapper" class="table-list-view inventory-list-view">

                        <div class="table-list-header inventory-list-header">
                            <div>Item</div>
                            <div>Category</div>
                            <div>Stock level</div>
                            <div>Expiration</div>
                            <div>Usage</div>
                            <div>Received</div>
                            <div class="table-cell-center">Actions</div>
                        </div>

                        <div id="tableBody" class="inventory-list-body"></div>

                    </div>

                    <div id="inventoryGridView" class="table-grid-view inventory-grid-view" hidden>
                        <div id="inventoryGrid" class="table-record-grid"></div>
                    </div>

                    <div id="emptyState" class="empty-state-host"></div>

                    <x-pagination-bar id="inventoryPaginationBottomBar" info-id="inventoryPageInfoBottom"
                        pagination-id="inventoryPaginationBottom" position="bottom" label="entries" hidden />
                </div>
            </div>
        </div>
    </main>

    <x-filter-drawer id="filterModal" title="Filters" close-callback="closeFilterDrawer('filterModal')"
        clear-id="clearFilterPanelBtn" clear-callback="clearFilterPanelModal()" clear-label="Clear Filters"
        cancel-callback="closeFilterDrawer('filterModal')" cancel-label="Cancel" apply-id="saveFilterPanelBtn"
        apply-callback="saveFilterPanel()" apply-label="Show 0 results" results-id="showResultsText">

        <div id="activeFiltersSection" class="filter-active-section hidden">

            <div class="filter-active-header">

                <span class="filter-active-title">
                    Active Filters
                </span>

                <button id="clearAllChipsBtn" type="button"
                    class="
                    filter-clear-all
                    ui-btn
                    ui-btn-secondary
                    ui-btn-sm
                ">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Clear All</span>
                </button>

            </div>

            <div id="activeChipsContainer" class="active-filters-container"></div>

        </div>


        <x-filter-group title="Sort By">

            <div id="inventorySortGroup" class="filter-chip-row">

                <button type="button" class="ftag" data-group="sort" data-val="newest">
                    Newest First
                </button>

                <button type="button" class="ftag" data-group="sort" data-val="oldest">
                    Oldest First
                </button>

                <button type="button" class="ftag" data-group="sort" data-val="az">
                    Name A-Z
                </button>

                <button type="button" class="ftag" data-group="sort" data-val="za">
                    Name Z-A
                </button>

            </div>

        </x-filter-group>


        <x-filter-group title="Filter by Date Range">

            <div id="inventoryDatePresetGroup" class="filter-chip-row">

                <button type="button" class="quick-date-chip" data-range="7">
                    Last 7 Days
                </button>

                <button type="button" class="quick-date-chip" data-range="30">
                    Last 30 Days
                </button>

                <button type="button" class="quick-date-chip" data-range="90">
                    Last 3 Months
                </button>

                <button type="button" class="quick-date-chip" data-range="180">
                    Last 6 Months
                </button>

                <button type="button" class="quick-date-chip" data-range="365">
                    Last 12 Months
                </button>

            </div>

        </x-filter-group>


        <x-filter-group title="Custom Date Range">

            <div class="filter-date-grid">

                <div class="filter-date-input-wrap">

                    <input id="fp_dateFrom" type="text" class="js-flatpickr-date-range-from" placeholder="Start date"
                        readonly autocomplete="off">

                    <i class="fa-regular fa-calendar"></i>

                </div>

                <div class="filter-date-input-wrap">

                    <input id="fp_dateTo" type="text" class="js-flatpickr-date-range-to" placeholder="End date"
                        readonly autocomplete="off">

                    <i class="fa-regular fa-calendar"></i>

                </div>

            </div>

        </x-filter-group>


        <x-filter-group title="Stock Level" class="filter-group-last">

            <div id="inventoryStockGroup" class="filter-chip-row">

                <button type="button" class="ftag" data-group="stock" data-val="in-stock">
                    In Stock
                </button>

                <button type="button" class="ftag" data-group="stock" data-val="low-stock">
                    Low Stock
                </button>

                <button type="button" class="ftag" data-group="stock" data-val="out-stock">
                    Out of Stock
                </button>

                <button type="button" class="ftag" data-group="stock" data-val="low-high">
                    Lowest Stock
                </button>

                <button type="button" class="ftag" data-group="stock" data-val="high-low">
                    Highest Stock
                </button>

            </div>

        </x-filter-group>

    </x-filter-drawer>

    <div id="addModal" class="ui-modal modal-theme-primary inventory-form-modal" aria-hidden="true">

        <form id="addInventoryForm" class="ui-modal-card modal-xl modal-card-form" data-global-validation
            data-form-validation-rule="inventoryAdd" data-discard-form data-discard-title="Discard new inventory item?"
            data-discard-subtitle="You have unsaved item details."
            data-discard-message="Closing this modal will remove the inventory draft you entered. Do you want to discard your changes?"
            novalidate>

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-plus"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">
                            Add Inventory Item
                        </h3>

                        <p class="modal-subtitle">
                            A new row will be appended every time you save
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="addModal"
                    aria-label="Close add inventory modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="modal-form-grid-2">

                    <div class="inventory-field" data-global-field>
                        <label for="addCategory" class="inventory-field-label">
                            Category <span class="required-mark">*</span>
                        </label>
                        <select id="addCategory" name="category" class="js-custom-select"
                            data-placeholder="Select category" data-field-label="Category"
                            data-required-message="Please select a category." required>

                            <option value="">Select category</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Supplies">Supplies</option>
                        </select>
                    </div>

                    <div class="inventory-field" data-global-field>
                        <label for="addDate" class="inventory-field-label">
                            Date Received <span class="required-mark">*</span>
                        </label>

                        <div class="inventory-date-control">
                            <input id="addDate" name="date_received" type="text"
                                class="field-input form-input-custom js-flatpickr-date-max-today"
                                data-field-label="Date Received" data-required-message="Please select a date."
                                data-validation-rule="notFutureDate" data-flatpickr-append-to-body
                                placeholder="Select date" required readonly>

                            <i class="fa-regular fa-calendar inventory-date-icon"></i>
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>
                        <label for="addExpirationDate" class="inventory-field-label">
                            Expiration Date
                        </label>

                        <div class="inventory-date-control">
                            <input id="addExpirationDate" name="expiration_date" type="text"
                                class="field-input form-input-custom js-flatpickr-date" data-field-label="Expiration Date"
                                data-flatpickr-append-to-body placeholder="Optional" readonly>

                            <i class="fa-regular fa-calendar inventory-date-icon"></i>
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>
                        <label for="addStock" class="inventory-field-label">
                            Stock Number
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-voice-row" data-voice-field>
                            <div class="global-voice-control">
                                <input id="addStock" name="stock_no"
                                    class="field-input form-input-custom inventory-stock-input" placeholder="00-000"
                                    maxlength="6" pattern="\d{2}-\d{3}" data-field-label="Stock Number"
                                    data-required-message="Please enter a stock number."
                                    data-pattern-message="Stock number must use the format 00-000."
                                    oninput="formatStockNo(this)" required>
                            </div>

                            <x-voice-input target="#addStock" status-id="addStockVoiceStatus"
                                label="Voice input for stock number" title="Voice input" />
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>
                        <label for="addUnit" class="inventory-field-label">
                            Unit <span class="required-mark">*</span>
                        </label>

                        <input id="addUnit" name="unit" type="text" class="form-input-custom"
                            placeholder="e.g. Box, Piece, Bottle" maxlength="50" data-field-label="Unit"
                            data-required-message="Please enter a unit." required>
                    </div>

                    <div class="inventory-field" data-global-field>

                        <div class="global-label-row">
                            <label for="addName" class="inventory-field-label">

                                Supply / Medicine Name
                                <span class="required-mark">*</span>
                            </label>

                            <span class="char-counter" id="charCounter-addName">
                                0 / 100 characters
                            </span>
                        </div>

                        <div class="global-voice-row" data-voice-field>

                            <div class="global-voice-control">
                                <input id="addName" name="name" class="form-input-custom"
                                    placeholder="e.g. Nitrile Gloves Large" minlength="2" maxlength="100"
                                    data-field-label="Supply / Medicine Name"
                                    data-required-message="Please enter an item name." data-char-limit="100"
                                    data-char-counter="#charCounter-addName" required>
                            </div>

                            <x-voice-input target="#addName" status-id="addNameVoiceStatus"
                                label="Voice input for item name" title="Voice input" />
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>

                        <label for="addQty" class="inventory-field-label">

                            Quantity
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-voice-row" data-voice-field>

                            <div class="global-number-stepper" data-global-number-stepper>

                                <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                                    aria-label="Decrease quantity">

                                    <i class="fa-solid fa-minus"></i>
                                </button>

                                <input id="addQty" name="qty" type="number" class="global-number-stepper-input"
                                    value="1" min="1" max="99999" step="1" inputmode="numeric"
                                    data-number-stepper-input data-field-label="Quantity"
                                    data-required-message="Please enter a quantity." data-validation-rule="wholeNumber"
                                    oninput="computeAddBalance()" required>

                                <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                    aria-label="Increase quantity">

                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <x-voice-input target="#addQty" status-id="addQtyVoiceStatus"
                                label="Voice input for quantity" title="Voice input" />
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>

                        <label for="addUsed" class="inventory-field-label">

                            Consumed
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-voice-row" data-voice-field>

                            <div class="global-number-stepper" data-global-number-stepper>

                                <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                                    aria-label="Decrease consumed quantity">

                                    <i class="fa-solid fa-minus"></i>
                                </button>

                                <input id="addUsed" name="used" type="number" class="global-number-stepper-input"
                                    value="0" min="0" max="99999" step="1" inputmode="numeric"
                                    data-number-stepper-input data-field-label="Consumed"
                                    data-required-message="Please enter the consumed quantity."
                                    data-validation-rule="inventoryConsumed" oninput="computeAddBalance()" required>

                                <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                    aria-label="Increase consumed quantity">

                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <x-voice-input target="#addUsed" status-id="addUsedVoiceStatus"
                                label="Voice input for consumed quantity" title="Voice input" />
                        </div>
                    </div>

                    <div class="inventory-field inventory-field-full" data-global-field>
                        <label for="addBalance" class="inventory-field-label">
                            Balance (auto-calculated)
                        </label>
                        <input id="addBalance" class="form-input-custom" readonly placeholder="—">
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="addModal">
                    Cancel
                </button>

                <button type="submit" id="btnSaveAdd" class="ui-btn ui-btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Item</span>
                </button>
            </div>
        </form>
    </div>

    <div id="editModal" class="ui-modal modal-theme-edit inventory-form-modal" aria-hidden="true">

        <form id="editInventoryForm" class="ui-modal-card modal-xl modal-card-form" data-global-validation
            data-form-validation-rule="inventoryEdit" data-discard-form data-discard-title="Discard inventory changes?"
            data-discard-subtitle="You have unsaved edits for this item."
            data-discard-message="Closing this modal will remove the edits you entered. Do you want to discard your changes?"
            novalidate>

            <div class="modal-hd">
                <div class="modal-heading">
                    <div class="modal-icon">
                        <i class="fa-solid fa-pen"></i>
                    </div>

                    <div class="modal-copy">
                        <h3 class="modal-title">
                            Edit Inventory Item
                        </h3>

                        <p class="modal-subtitle">
                            Update the details for this item
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="editModal"
                    aria-label="Close edit inventory modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd">
                <div class="modal-form-grid-2">

                    <div class="inventory-field" data-global-field>
                        <label for="editCategory" class="inventory-field-label">
                            Category <span class="required-mark">*</span>
                        </label>

                        <select id="editCategory" name="category" class="js-custom-select"
                            data-placeholder="Select category" data-field-label="Category"
                            data-required-message="Please select a category." required>

                            <option value="">Select category</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Supplies">Supplies</option>
                        </select>
                    </div>

                    <div class="inventory-field" data-global-field>
                        <label for="editDate" class="inventory-field-label">
                            Date Received <span class="required-mark">*</span>
                        </label>

                        <div class="inventory-date-control">
                            <input id="editDate" name="date_received" type="text"
                                class="field-input form-input-custom js-flatpickr-date" data-field-label="Date Received"
                                data-required-message="Please select a date." data-validation-rule="notFutureDate"
                                required data-flatpickr-append-to-body readonly>

                            <i class="fa-regular fa-calendar inventory-date-icon"></i>
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>
                        <label for="editExpirationDate" class="inventory-field-label">
                            Expiration Date
                        </label>

                        <div class="inventory-date-control">
                            <input id="editExpirationDate" name="expiration_date" type="text"
                                class="field-input form-input-custom js-flatpickr-date" data-field-label="Expiration Date"
                                data-flatpickr-append-to-body placeholder="Optional" readonly>

                            <i class="fa-regular fa-calendar inventory-date-icon"></i>
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>

                        <label for="editStock" class="inventory-field-label">
                            Stock Number
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-voice-row" data-voice-field>

                            <div class="global-voice-control">
                                <input id="editStock" name="stock_no"
                                    class="field-input form-input-custom inventory-stock-input" placeholder="00-000"
                                    maxlength="6" pattern="\d{2}-\d{3}" data-field-label="Stock Number"
                                    data-required-message="Please enter a stock number."
                                    data-pattern-message="Stock number must use the format 00-000."
                                    oninput="formatStockNo(this)" required>
                            </div>

                            <x-voice-input target="#editStock" status-id="editStockVoiceStatus"
                                label="Voice input for stock number" title="Voice input" />
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>
                        <label for="editUnit" class="inventory-field-label">
                            Unit <span class="required-mark">*</span>
                        </label>

                        <input id="editUnit" name="unit" type="text" class="form-input-custom"
                            placeholder="e.g. Box, Piece, Bottle" maxlength="50" data-field-label="Unit"
                            data-required-message="Please enter a unit." required>
                    </div>

                    <div class="inventory-field" data-global-field>

                        <div class="global-label-row">
                            <label for="editName" class="inventory-field-label">

                                Supply / Medicine Name
                                <span class="required-mark">*</span>
                            </label>

                            <span class="char-counter" id="charCounter-editName">
                                0 / 100 characters
                            </span>
                        </div>

                        <div class="global-voice-row" data-voice-field>

                            <div class="global-voice-control">
                                <input id="editName" name="name" class="form-input-custom" minlength="2"
                                    maxlength="100" data-field-label="Supply / Medicine Name"
                                    data-required-message="Please enter an item name." data-char-limit="100"
                                    data-char-counter="#charCounter-editName" required>
                            </div>

                            <x-voice-input target="#editName" status-id="editNameVoiceStatus"
                                label="Voice input for item name" title="Voice input" />
                        </div>
                    </div>


                    <div class="inventory-field" data-global-field>

                        <label for="editQty" class="inventory-field-label">

                            Quantity
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-voice-row" data-voice-field>

                            <div class="global-number-stepper" data-global-number-stepper>

                                <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                                    aria-label="Decrease quantity">

                                    <i class="fa-solid fa-minus"></i>
                                </button>

                                <input id="editQty" name="qty" type="number" class="global-number-stepper-input"
                                    min="0" max="99999" step="1" inputmode="numeric"
                                    data-number-stepper-input data-field-label="Quantity"
                                    data-required-message="Please enter a quantity." data-validation-rule="wholeNumber"
                                    oninput="computeEditBalance()" required>

                                <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                    aria-label="Increase quantity">

                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <x-voice-input target="#editQty" status-id="editQtyVoiceStatus"
                                label="Voice input for quantity" title="Voice input" />
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>

                        <label for="editUsed" class="inventory-field-label">

                            Consumed
                            <span class="required-mark">*</span>
                        </label>

                        <div class="global-voice-row" data-voice-field>

                            <div class="global-number-stepper" data-global-number-stepper>

                                <button type="button" class="global-number-stepper-btn" data-number-step="-1"
                                    aria-label="Decrease consumed quantity">

                                    <i class="fa-solid fa-minus"></i>
                                </button>

                                <input id="editUsed" name="used" type="number" class="global-number-stepper-input"
                                    min="0" max="99999" step="1" inputmode="numeric"
                                    data-number-stepper-input data-field-label="Consumed"
                                    data-required-message="Please enter the consumed quantity."
                                    data-validation-rule="inventoryConsumed" oninput="computeEditBalance()" required>

                                <button type="button" class="global-number-stepper-btn" data-number-step="1"
                                    aria-label="Increase consumed quantity">

                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <x-voice-input target="#editUsed" status-id="editUsedVoiceStatus"
                                label="Voice input for consumed quantity" title="Voice input" />
                        </div>
                    </div>

                    <div class="inventory-field" data-global-field>
                        <label for="editBalance" class="inventory-field-label">
                            Balance (auto-calculated)
                        </label>
                        <input id="editBalance" class="form-input-custom" readonly>
                    </div>
                </div>
            </div>

            <div class="modal-ft">
                <button type="button" class="ui-btn ui-btn-secondary" data-discard-close="editModal">
                    Cancel
                </button>

                <button type="submit" class="ui-btn ui-btn-edit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>

    <x-delete-confirm-modal id="deleteModal" form-id="inventoryDeleteForm" name-id="inventoryDeleteName"
        title="Delete Inventory Item" helper="This inventory item will be permanently removed." />

@endsection

@section('scripts')

    @php
        $inventoryDataUrl = route($inventoryRouteNames['data']);

        $inventoryStoreUrl = route($inventoryRouteNames['store']);

        $inventoryUpdateUrlTemplate = route($inventoryRouteNames['update'], ['inventory' => '__ID__']);

        $inventoryDestroyUrlTemplate = route($inventoryRouteNames['destroy'], ['inventory' => '__ID__']);
    @endphp

    <script>
        var currentViewMode = 'list';

        function bindInventoryGlobalViewToggle() {
            const toggle =
                document.getElementById('inventoryViewToggle');

            if (!toggle) return;

            window.initGlobalViewToggles?.(document);

            currentViewMode =
                window.getGlobalViewMode?.(toggle) || 'list';

            toggle.addEventListener(
                'global-view-change',
                function(event) {
                    currentViewMode =
                        event.detail?.mode === 'grid' ?
                        'grid' :
                        'list';

                    renderTable();
                }
            );
        }

        var inventory = [];
        var activeTab = 'all';

        var inventoryCurrentPage = 1;
        var inventoryEntriesPerPage = 10;

        window.handleInventorySearch =
            function() {
                inventoryCurrentPage = 1;
                renderTable();
            };

        function handleInventoryPerPageChange(value) {
            const nextPerPage =
                Math.max(
                    1,
                    Number(value) || 10
                );

            if (
                inventoryEntriesPerPage ===
                nextPerPage
            ) {
                return;
            }

            inventoryEntriesPerPage =
                nextPerPage;

            inventoryCurrentPage = 1;

            renderTable();
        }

        window.handleInventoryPerPageChange =
            handleInventoryPerPageChange;

        let inventoryRefreshWatcher = null;

        const INVENTORY_DATA_URL = @json($inventoryDataUrl);

        function applyInventorySnapshot(payload) {
            inventory = Array.isArray(payload) ? payload : [];
            renderTable();
        }

        function initInventoryRefreshWatcher() {
            if (!window.initGlobalRefreshWatcher) return;

            inventoryRefreshWatcher = window.initGlobalRefreshWatcher({
                key: @json($inventoryWatcherKey),
                url: INVENTORY_DATA_URL,
                initialItems: inventory,
                anchorSelector: '#mainContent.inventory-page .inventory-table-card',
                itemLabel: 'inventory item',
                getItems: (payload) => Array.isArray(payload) ? payload : [],
                getItemId: (item) => item?.id,
                title: (count) => `${count} new inventory item${count === 1 ? '' : 's'} available`,
                subtitle: (count) => `Refresh to see the latest inventory update${count === 1 ? '' : 's'}.`,
                onRefresh: applyInventorySnapshot,
                toast: {
                    type: 'info',
                    title: 'Inventory updated',
                    message: 'Latest inventory records are now shown.'
                }
            });
        }

        const inventoryUrlTemplates = {
            update: @json($inventoryUpdateUrlTemplate),
            destroy: @json($inventoryDestroyUrlTemplate),
        };

        function inventoryUrl(type, id) {
            return inventoryUrlTemplates[type].replace('__ID__', encodeURIComponent(id));
        }

        document.addEventListener(
            'DOMContentLoaded',
            async function() {
                bindInventoryGlobalViewToggle();

                applyDashboardStockFilterFromQuery();
                syncInventoryFilterGroups();
                updateFilterButtonState();

                window.initCustomSelects?.(
                    document.getElementById('addModal')
                );

                window.initCustomSelects?.(
                    document.getElementById('editModal')
                );

                const inventoryPerPageSelect =
                    document.getElementById(
                        'inventoryPerPageSelect'
                    );

                if (inventoryPerPageSelect) {
                    inventoryPerPageSelect.value =
                        String(inventoryEntriesPerPage);

                    window.syncGlobalPageSizeSelect?.(
                        inventoryPerPageSelect,
                        inventoryEntriesPerPage
                    );

                    window.initGlobalPageSizeSelects?.();
                }

                await loadInventory();
            }
        );

        async function loadInventory() {
            var res = await fetch(INVENTORY_DATA_URL, {
                cache: 'no-store'
            });

            var ct = res.headers.get('content-type') || '';

            if (!ct.includes('application/json')) {
                console.error('Inventory data is not JSON.');
                return;
            }

            inventory = await res.json();
            renderTable();

            if (inventoryRefreshWatcher) {
                inventoryRefreshWatcher.sync(inventory);
            } else {
                initInventoryRefreshWatcher();
            }
        }

        function updateStats() {
            document.getElementById('statTotal').textContent = inventory.length;
            document.getElementById('statMedicine').textContent = inventory.filter(function(i) {
                return i.category === 'Medicine';
            }).length;
            document.getElementById('statSupplies').textContent = inventory.filter(function(i) {
                return i.category === 'Supplies';
            }).length;
            document.getElementById('statLow').textContent = inventory.filter(function(i) {
                return (Number(i.qty) - Number(i.used)) <= 5;
            }).length;
        }

        function setTab(tab, btn) {
            const normalizedTab =
                String(tab || 'all')
                .trim()
                .toLowerCase();

            activeTab = ['all', 'medicine', 'supplies']
                .includes(normalizedTab) ?
                normalizedTab :
                'all';

            document
                .querySelectorAll(
                    '#mainContent.inventory-page .tab-group .tab-btn'
                )
                .forEach(function(button) {
                    const buttonTab =
                        String(
                            button.dataset.tab || ''
                        )
                        .trim()
                        .toLowerCase();

                    const isActive =
                        buttonTab === activeTab;

                    button.classList.toggle(
                        'active',
                        isActive
                    );

                    button.setAttribute(
                        'aria-selected',
                        isActive ? 'true' : 'false'
                    );
                });

            document
                .querySelectorAll(
                    '#inventoryStats .stat-card'
                )
                .forEach(function(card) {
                    card.classList.remove(
                        'active',
                        'stat-active'
                    );

                    card.setAttribute(
                        'aria-pressed',
                        'false'
                    );
                });

            inventoryCurrentPage = 1;

            renderTable();
        }

        var activeFilters = {
            sort: '',
            dateFrom: '',
            dateTo: '',
            stock: ''
        };

        var filterDraft = {
            sort: '',
            dateFrom: '',
            dateTo: '',
            stock: ''
        };

        var inventoryActiveDatePreset = '';

        function itemBalance(item) {
            var qty = Number(item?.qty || 0);
            var used = Number(item?.used || 0);
            return qty - used;
        }

        function applyStockMode(data, mode) {
            var list = data.slice();

            if (mode === 'in-stock') {
                return list.filter(function(item) {
                    return itemBalance(item) > 5;
                });
            }

            if (mode === 'low-stock') {
                return list.filter(function(item) {
                    var balance = itemBalance(item);
                    return balance >= 1 && balance <= 5;
                });
            }

            if (mode === 'out-stock') {
                return list.filter(function(item) {
                    return itemBalance(item) <= 0;
                });
            }

            if (mode === 'low-high') {
                return list.sort(function(a, b) {
                    return itemBalance(a) - itemBalance(b);
                });
            }

            if (mode === 'high-low') {
                return list.sort(function(a, b) {
                    return itemBalance(b) - itemBalance(a);
                });
            }

            return list;
        }

        function applyDashboardStockFilterFromQuery() {
            var params = new URLSearchParams(window.location.search);
            var stockFilter = (params.get('stock_filter') || '').toLowerCase();
            var allowed = ['in-stock', 'low-stock', 'out-stock'];

            if (allowed.includes(stockFilter)) {
                activeFilters.stock = stockFilter;
                filterDraft.stock = stockFilter;
            }
        }

        function openAddModal() {
            resetAddForm();

            const modal =
                document.getElementById(
                    'addModal'
                );

            window.initCustomSelects?.(
                modal
            );

            modal
                ?.querySelectorAll(
                    '.custom-select'
                )
                .forEach(function(wrapper) {
                    wrapper.classList.remove(
                        'is-invalid',
                        'is-valid'
                    );

                    window.syncCustomSelect?.(
                        wrapper
                    );
                });

            window.openModal?.(
                'addModal'
            );

            window.initGlobalVoiceInputs?.(
                modal
            );

            document.dispatchEvent(
                new CustomEvent(
                    'voice:refresh', {
                        detail: {
                            root: modal
                        }
                    }
                )
            );
        }

        function openFilterPanel() {
            filterDraft = Object.assign({}, activeFilters);

            const dateFrom = document.getElementById('fp_dateFrom');
            const dateTo = document.getElementById('fp_dateTo');

            if (dateFrom) dateFrom.value = filterDraft.dateFrom || '';
            if (dateTo) dateTo.value = filterDraft.dateTo || '';

            inventoryActiveDatePreset = '';

            document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function(btn) {
                var range = btn.getAttribute('data-range');
                var isActive = false;

                if (filterDraft.dateFrom && filterDraft.dateTo) {
                    var today = new Date();
                    var from = new Date();
                    from.setDate(today.getDate() - Number(range));

                    var expectedFrom = window.formatDateForInput ?
                        window.formatDateForInput(from) :
                        from.toISOString().slice(0, 10);

                    var expectedTo = window.formatDateForInput ?
                        window.formatDateForInput(today) :
                        today.toISOString().slice(0, 10);

                    isActive = filterDraft.dateFrom === expectedFrom && filterDraft.dateTo === expectedTo;
                }

                btn.classList.toggle('active', isActive);
                if (isActive) inventoryActiveDatePreset = range;
            });

            syncInventoryFilterGroups();
            renderFilterChips();
            updateShowResultsButton();

            window.openFilterDrawer('filterModal');
        }

        function closeFilterPanel() {
            if (window.closeFilterDrawer) {
                window.closeFilterDrawer('filterModal');
                return;
            }

            var modal = document.getElementById('filterModal');
            if (modal) modal.classList.remove('open', 'closing');
            document.documentElement.classList.remove('filter-lock');
            document.body.classList.remove('filter-lock');
        }

        function syncInventoryFilterGroups() {
            syncInventoryGroup('inventorySortGroup', filterDraft.sort);
            syncInventoryGroup('inventoryStockGroup', filterDraft.stock);
        }

        function syncInventoryGroup(groupId, activeValue) {
            const group = document.getElementById(groupId);
            if (!group) return;

            group.querySelectorAll('.ftag').forEach(function(btn) {
                btn.classList.toggle('ftag-active', btn.getAttribute('data-val') === activeValue);
            });
        }

        function setInventoryDraftValue(group, value) {
            filterDraft[group] = filterDraft[group] === value ? '' : value;
            syncInventoryFilterGroups();
            renderFilterChips();
            updateShowResultsButton();
        }

        function clearFormState() {
            filterDraft = {
                sort: '',
                dateFrom: '',
                dateTo: '',
                stock: ''
            };

            const dateFrom = document.getElementById('fp_dateFrom');
            const dateTo = document.getElementById('fp_dateTo');

            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';

            inventoryActiveDatePreset = '';

            document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function(btn) {
                btn.classList.remove('active');
            });

            syncInventoryFilterGroups();
        }

        function clearFilterPanel() {
            clearFormState();

            activeFilters = Object.assign({}, filterDraft);

            updateFilterButtonState();
            renderFilterChips();
            updateShowResultsButton();
            inventoryCurrentPage = 1;
            renderTable();
            closeFilterPanel();
        }

        function clearFilterPanelModal() {
            clearFormState();
            renderFilterChips();
            updateShowResultsButton();
        }

        function saveFilterPanel() {
            filterDraft.dateFrom = document.getElementById('fp_dateFrom')?.value || '';
            filterDraft.dateTo = document.getElementById('fp_dateTo')?.value || '';

            var activePresetBtn = document.querySelector('#inventoryDatePresetGroup .quick-date-chip.active');
            inventoryActiveDatePreset = activePresetBtn ? activePresetBtn.getAttribute('data-range') : '';

            activeFilters = Object.assign({}, filterDraft);

            updateFilterButtonState();
            closeFilterPanel();
            inventoryCurrentPage = 1;
            renderTable();
        }

        function countInventoryDraftResults() {
            let data = inventory.slice();

            const q = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();

            if (q) {
                data = data.filter(function(item) {
                    return String(item.stock_no || '').toLowerCase().includes(q) ||
                        String(item.name || '').toLowerCase().includes(q) ||
                        String(item.category || '').toLowerCase().includes(q);
                });
            }

            if (activeTab !== 'all') {
                data = data.filter(function(item) {
                    return String(item.category || '').toLowerCase() === activeTab;
                });
            }

            if (filterDraft.dateFrom || filterDraft.dateTo) {
                data = data.filter(function(item) {
                    const d = new Date(item.date_received);
                    if (isNaN(d.getTime())) return false;

                    if (filterDraft.dateFrom && d < new Date(filterDraft.dateFrom)) return false;
                    if (filterDraft.dateTo && d > new Date(filterDraft.dateTo)) return false;

                    return true;
                });
            }

            data = applyStockMode(data, filterDraft.stock);

            return data.length;
        }

        function updateShowResultsButton() {
            window.updateShowResultsText(countInventoryDraftResults());
        }

        function renderFilterChips() {
            var container = document.getElementById("activeChipsContainer");
            var section = document.getElementById("activeFiltersSection");
            if (!container || !section) return;

            container.innerHTML = "";
            var hasChips = false;

            function addChip(label, callback) {
                hasChips = true;

                var chip = document.createElement("div");
                chip.className = "filter-chip";
                chip.innerHTML =
                    "<span>" + label + "</span>" +
                    "<span class='filter-chip-remove'><i class='fa-solid fa-xmark'></i></span>";

                chip.querySelector(".filter-chip-remove").onclick = function() {
                    callback();
                    syncInventoryFilterGroups();
                    renderFilterChips();
                    updateShowResultsButton();
                };

                container.appendChild(chip);
            }

            if (filterDraft.sort === 'az') {
                addChip('Name: A → Z', function() {
                    filterDraft.sort = '';
                });
            }

            if (filterDraft.sort === 'za') {
                addChip('Name: Z → A', function() {
                    filterDraft.sort = '';
                });
            }

            if (filterDraft.sort === 'newest') {
                addChip('Sort: Newest First', function() {
                    filterDraft.sort = '';
                });
            }

            if (filterDraft.sort === 'oldest') {
                addChip('Sort: Oldest First', function() {
                    filterDraft.sort = '';
                });
            }

            if (filterDraft.stock === 'in-stock') {
                addChip('Stock: In Stock', function() {
                    filterDraft.stock = '';
                });
            }

            if (filterDraft.stock === 'low-stock') {
                addChip('Stock: Low Stock', function() {
                    filterDraft.stock = '';
                });
            }

            if (filterDraft.stock === 'out-stock') {
                addChip('Stock: Out of Stock', function() {
                    filterDraft.stock = '';
                });
            }

            if (filterDraft.stock === 'low-high') {
                addChip('Stock: Lowest → Highest', function() {
                    filterDraft.stock = '';
                });
            }

            if (filterDraft.stock === 'high-low') {
                addChip('Stock: Highest → Lowest', function() {
                    filterDraft.stock = '';
                });
            }

            var dateFrom = document.getElementById('fp_dateFrom')?.value || filterDraft.dateFrom || '';
            var dateTo = document.getElementById('fp_dateTo')?.value || filterDraft.dateTo || '';

            filterDraft.dateFrom = dateFrom;
            filterDraft.dateTo = dateTo;

            if (dateFrom || dateTo) {
                var activePresetBtn = document.querySelector('#inventoryDatePresetGroup .quick-date-chip.active');

                var label = activePresetBtn ?
                    activePresetBtn.textContent.trim() :
                    dateFrom && dateTo ?
                    dateFrom + ' to ' + dateTo :
                    dateFrom ?
                    'From ' + dateFrom :
                    'Until ' + dateTo;

                addChip('Date: ' + label, function() {
                    filterDraft.dateFrom = '';
                    filterDraft.dateTo = '';
                    inventoryActiveDatePreset = '';

                    const fromInput = document.getElementById('fp_dateFrom');
                    const toInput = document.getElementById('fp_dateTo');

                    if (fromInput) fromInput.value = '';
                    if (toInput) toInput.value = '';

                    document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function(btn) {
                        btn.classList.remove('active');
                    });
                });
            }

            if (hasChips) {
                section.classList.remove("hidden");

                var clearAllBtn = document.getElementById("clearAllChipsBtn");
                if (clearAllBtn) {
                    clearAllBtn.onclick = function() {
                        clearFormState();
                        renderFilterChips();
                        updateShowResultsButton();
                    };
                }
            } else {
                section.classList.add("hidden");
            }

            updateShowResultsButton();
        }

        document.addEventListener('DOMContentLoaded', function() {
            [
                ['addQty', 'addUsed'],
                ['editQty', 'editUsed']
            ].forEach(function([qtyId, usedId]) {
                const qtyField =
                    document.getElementById(qtyId);

                const usedField =
                    document.getElementById(usedId);

                qtyField?.addEventListener(
                    'input',
                    function() {
                        if (usedField?.value) {
                            window.validateFormInputField?.(
                                usedField
                            );
                        }
                    }
                );
            });

            const editInventoryForm =
                document.getElementById('editInventoryForm');

            editInventoryForm?.addEventListener(
                'submit',
                function(event) {
                    event.preventDefault();

                    const result = window.validateGlobalForm?.(
                        editInventoryForm
                    );

                    if (result && !result.valid) {
                        return;
                    }

                    saveEdit();
                }
            );

            const addInventoryForm =
                document.getElementById('addInventoryForm');

            addInventoryForm?.addEventListener('submit', function(event) {
                event.preventDefault();

                const result = window.validateGlobalForm?.(
                    addInventoryForm
                );

                if (result && !result.valid) {
                    return;
                }

                addItem();
            });

            document.querySelectorAll('#inventorySortGroup .ftag, #inventoryStockGroup .ftag')
                .forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        setInventoryDraftValue(btn.getAttribute('data-group'), btn.getAttribute(
                            'data-val'));
                    });
                });

            if (window.bindQuickDatePresets) {
                window.bindQuickDatePresets({
                    groupId: 'inventoryDatePresetGroup',
                    fromId: 'fp_dateFrom',
                    toId: 'fp_dateTo',
                    onChange: function() {
                        var activePresetBtn = document.querySelector(
                            '#inventoryDatePresetGroup .quick-date-chip.active');

                        inventoryActiveDatePreset = activePresetBtn ?
                            activePresetBtn.getAttribute('data-range') :
                            '';

                        filterDraft.dateFrom = document.getElementById('fp_dateFrom')?.value || '';
                        filterDraft.dateTo = document.getElementById('fp_dateTo')?.value || '';

                        renderFilterChips();
                        updateShowResultsButton();
                    }
                });
            } else {
                document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var range = Number(btn.getAttribute('data-range'));
                        var today = new Date();
                        var from = new Date();

                        from.setDate(today.getDate() - range);

                        var fromVal = window.formatDateForInput ?
                            window.formatDateForInput(from) :
                            from.toISOString().slice(0, 10);

                        var toVal = window.formatDateForInput ?
                            window.formatDateForInput(today) :
                            today.toISOString().slice(0, 10);

                        var alreadyActive = btn.classList.contains('active');

                        document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip')
                            .forEach(function(b) {
                                b.classList.remove('active');
                            });

                        if (alreadyActive) {
                            inventoryActiveDatePreset = '';
                            document.getElementById('fp_dateFrom').value = '';
                            document.getElementById('fp_dateTo').value = '';
                        } else {
                            inventoryActiveDatePreset = String(range);
                            btn.classList.add('active');
                            document.getElementById('fp_dateFrom').value = fromVal;
                            document.getElementById('fp_dateTo').value = toVal;
                        }

                        filterDraft.dateFrom = document.getElementById('fp_dateFrom')?.value || '';
                        filterDraft.dateTo = document.getElementById('fp_dateTo')?.value || '';

                        renderFilterChips();
                        updateShowResultsButton();
                    });
                });
            }

            ['fp_dateFrom', 'fp_dateTo'].forEach(function(id) {
                var input = document.getElementById(id);
                if (!input) return;

                input.addEventListener('change', function() {
                    inventoryActiveDatePreset = '';

                    document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(
                        function(btn) {
                            btn.classList.remove('active');
                        });

                    filterDraft.dateFrom = document.getElementById('fp_dateFrom')?.value || '';
                    filterDraft.dateTo = document.getElementById('fp_dateTo')?.value || '';

                    renderFilterChips();
                    updateShowResultsButton();
                });
            });
        });

        function updateFilterButtonState() {
            var count = 0;
            if (activeFilters.sort) count++;
            if (activeFilters.stock) count++;
            if (activeFilters.dateFrom || activeFilters.dateTo) count++;

            var has = count > 0;
            var filterPill = document.getElementById("filterBtn");
            var filterDot = document.getElementById("filterBadge");
            var externalClearFilterBtn = document.getElementById("externalClearFilterBtn");

            if (filterPill) {
                filterPill.classList.toggle("has-filters", has);
                filterPill.setAttribute("aria-pressed", has ? "true" : "false");
            }

            if (filterDot) {
                filterDot.classList.toggle("show", has);
                filterDot.textContent = has ? count : "";
            }

            if (externalClearFilterBtn) {
                externalClearFilterBtn.classList.toggle("hidden", !has);
                externalClearFilterBtn.classList.toggle("show", has);
            }
        }

        function inventoryActionButtons(id) {
            return `
        <button type="button"
            class="ui-action-btn ui-action-edit"
            data-tooltip="Edit inventory item"
            aria-label="Edit inventory item"
            onclick="openEdit(${id})">
            <i class="fa-solid fa-pen"></i>
        </button>

        <button type="button"
            class="ui-action-btn ui-action-delete"
            data-tooltip="Delete inventory item"
aria-label="Delete inventory item"
            onclick="deleteItem(${id})">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
        }

        function formatInventoryDisplayDate(value, fallback = '—') {
            const raw = String(value || '').trim();

            if (!raw) {
                return fallback;
            }

            const isoDate = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
            let date;

            if (isoDate) {
                date = new Date(
                    Number(isoDate[1]),
                    Number(isoDate[2]) - 1,
                    Number(isoDate[3])
                );
            } else {
                date = new Date(raw);
            }

            if (Number.isNaN(date.getTime())) {
                return fallback;
            }

            return new Intl.DateTimeFormat('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            }).format(date);
        }

        function getInventoryExpirationDisplay(status, days) {
            if (status === 'none' || days === null || days === undefined) {
                return {
                    label: 'No expiry',
                    tone: 'is-neutral'
                };
            }

            const numericDays = Number(days);

            if (status === 'expired' || numericDays < 0) {
                return {
                    label: 'Expired',
                    tone: 'is-danger'
                };
            }

            if (numericDays === 0) {
                return {
                    label: 'Today',
                    tone: 'is-danger'
                };
            }

            if (numericDays <= 14) {
                return {
                    label: `In ${numericDays}d`,
                    tone: 'is-danger'
                };
            }

            if (numericDays <= 45) {
                return {
                    label: `In ${numericDays}d`,
                    tone: 'is-warning'
                };
            }

            return {
                label: `In ${Math.max(1, Math.round(numericDays / 30))}mo`,
                tone: 'is-neutral'
            };
        }

        function renderTable() {
            const tbody =
                document.getElementById(
                    'tableBody'
                );

            const gridView =
                document.getElementById(
                    'inventoryGridView'
                );

            const grid =
                document.getElementById(
                    'inventoryGrid'
                );

            const tableWrapper =
                document.getElementById(
                    'tableWrapper'
                );

            const emptyState =
                document.getElementById(
                    'emptyState'
                );

            if (!tbody) {
                return;
            }

            tbody.replaceChildren();

            if (grid) {
                grid.replaceChildren();
            }

            if (tableWrapper) {
                tableWrapper.hidden = false;
                tableWrapper.style.removeProperty(
                    'display'
                );
                tableWrapper.style.removeProperty(
                    'visibility'
                );
            }

            window.EmptyState?.hide(
                '#emptyState'
            );

            let data =
                Array.isArray(inventory) ?
                inventory.slice() : [];

            if (activeTab !== 'all') {
                data = data.filter(function(item) {
                    const itemCategory =
                        String(item.category || '')
                        .trim()
                        .toLowerCase();

                    return itemCategory === activeTab;
                });
            }

            var q = (document.getElementById('searchInput').value || '').toLowerCase();
            if (q) {
                data = data.filter(function(i) {
                    return String(i.stock_no || '').toLowerCase().includes(q) ||
                        String(i.name || '').toLowerCase().includes(q);
                });
            }

            var from = activeFilters.dateFrom ? new Date(activeFilters.dateFrom) : null;
            var to = activeFilters.dateTo ? new Date(activeFilters.dateTo) : null;

            if (from) {
                from.setHours(0, 0, 0, 0);
                data = data.filter(function(i) {
                    return i.date_received && new Date(i.date_received) >= from;
                });
            }

            if (to) {
                to.setHours(23, 59, 59, 999);
                data = data.filter(function(i) {
                    return i.date_received && new Date(i.date_received) <= to;
                });
            }

            function n(v) {
                var x = Number(v);
                return isFinite(x) ? x : 0;
            }

            function dt(v) {
                if (!v) return 0;
                var t = new Date(v).getTime();
                return isFinite(t) ? t : 0;
            }

            if (activeFilters.stock) {
                data = applyStockMode(data, activeFilters.stock);
            }

            if (activeFilters.sort === 'az') {
                data.sort(function(a, b) {
                    return String(a.name || '').localeCompare(String(b.name || ''));
                });
            } else if (activeFilters.sort === 'za') {
                data.sort(function(a, b) {
                    return String(b.name || '').localeCompare(String(a.name || ''));
                });
            } else if (activeFilters.sort === 'oldest') {
                data.sort(function(a, b) {
                    return dt(a.date_received) - dt(b.date_received);
                });
            } else if (activeFilters.sort === 'newest') {
                data.sort(function(a, b) {
                    return dt(b.date_received) - dt(a.date_received);
                });
            }

            updateStats();

            const filteredInventoryCount =
                data.length;

            const totalInventoryCount =
                Array.isArray(inventory) ?
                inventory.length :
                0;

            const totalInventoryLabel =
                totalInventoryCount === 1 ?
                'item' :
                'items';

            document
                .querySelectorAll(
                    '.js-row-count'
                )
                .forEach(function(element) {
                    element.textContent =
                        `${totalInventoryCount} ${totalInventoryLabel}`;
                });

            const totalPages =
                Math.max(
                    1,
                    Math.ceil(
                        filteredInventoryCount /
                        inventoryEntriesPerPage
                    )
                );

            if (inventoryCurrentPage > totalPages) {
                inventoryCurrentPage = totalPages;
            }

            const pageStart =
                (
                    inventoryCurrentPage - 1
                ) * inventoryEntriesPerPage;

            const pageEnd =
                Math.min(
                    pageStart +
                    inventoryEntriesPerPage,
                    filteredInventoryCount
                );

            const paginatedData =
                data.slice(
                    pageStart,
                    pageEnd
                );

            const entryLabel =
                filteredInventoryCount === 1 ?
                'entry' :
                'entries';

            const pageInformation =
                filteredInventoryCount > 0 ?
                `
            Showing
            <strong>
                ${pageStart + 1}–${pageEnd}
            </strong>
            of
            <strong>
                ${filteredInventoryCount}
            </strong>
            ${entryLabel}
        ` :
                `
            Showing
            <strong>0</strong>
            entries
        `;

            [
                document.getElementById(
                    'inventoryPageInfoTop'
                ),
                document.getElementById(
                    'inventoryPageInfoBottom'
                )
            ]
            .filter(Boolean)
                .forEach(function(element) {
                    element.innerHTML =
                        pageInformation;
                });

            window.renderGlobalPagination?.({
                currentPage: inventoryCurrentPage,

                lastPage: totalPages,

                total: filteredInventoryCount,

                from: filteredInventoryCount > 0 ?
                    pageStart + 1 : null,

                to: filteredInventoryCount > 0 ?
                    pageEnd : null,

                containers: [
                    document.getElementById(
                        'inventoryPaginationTop'
                    ),
                    document.getElementById(
                        'inventoryPaginationBottom'
                    ),
                ],

                bars: [
                    document.getElementById(
                        'inventoryPaginationTopBar'
                    ),
                    document.getElementById(
                        'inventoryPaginationBottomBar'
                    ),
                ],

                infoElements: [
                    document.getElementById(
                        'inventoryPageInfoTop'
                    ),
                    document.getElementById(
                        'inventoryPageInfoBottom'
                    ),
                ],

                itemLabel: filteredInventoryCount === 1 ?
                    'entry' : 'entries',

                onPageChange(page) {
                    inventoryCurrentPage =
                        page;

                    renderTable();

                    document
                        .querySelector(
                            '.inventory-table-card'
                        )
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start',
                        });
                },
            });

            if (!data.length) {
                if (!window.EmptyState) {
                    requestAnimationFrame(() => {
                        renderTable();
                    });

                    return;
                }

                if (tableWrapper) {
                    tableWrapper.hidden = true;
                    tableWrapper.style.setProperty(
                        'display',
                        'none',
                        'important'
                    );
                }

                if (gridView) {
                    gridView.hidden = true;
                }

                var isSearching =
                    q.length > 0;

                var hasFilters =
                    Object
                    .values(
                        activeFilters
                    )
                    .some(Boolean);

                if (isSearching) {
                    window.EmptyState?.renderSearch({
                        host: '#emptyState',

                        input: '#searchInput',

                        query: q,

                        message: 'Try a different stock number or supply name.',
                    });

                    return;
                }

                if (hasFilters) {
                    window.EmptyState?.render({
                        host: '#emptyState',

                        icon: 'fa-sliders',

                        title: 'No matches for your filters',

                        message: 'Try removing or adjusting your filter criteria.',

                        actionHtml: `
            <button
                type="button"
                class="empty-state-btn"
                data-inventory-clear-filters
            >
                <i class="fa-solid fa-xmark"></i>
                Reset
            </button>
        `,
                    });

                    document
                        .querySelector(
                            '#emptyState [data-inventory-clear-filters]'
                        )
                        ?.addEventListener(
                            'click',
                            clearFilterPanel
                        );

                    return;
                }

                var messages = {
                    all: {
                        icon: 'fa-box-open',

                        title: 'No items in the inventory',

                        message: 'Add your first item using the "Add Item" button above.',
                    },

                    medicine: {
                        icon: 'fa-pills',

                        title: 'No medicines in the inventory',

                        message: 'Add a medicine item above.',
                    },

                    supplies: {
                        icon: 'fa-syringe',

                        title: 'No dental supplies in the inventory',

                        message: 'Add a supply item above.',
                    },
                };

                window.EmptyState?.render({
                    host: '#emptyState',

                    ...(
                        messages[
                            activeTab
                        ] ||
                        messages.all
                    ),
                });

                return;
            }

            window.EmptyState?.hide(
                '#emptyState'
            );

            if (currentViewMode === 'grid') {

                if (tableWrapper) {
                    tableWrapper.hidden = true;
                }

                if (gridView) {
                    gridView.hidden = false;
                }

            } else {

                if (gridView) {
                    gridView.hidden = true;
                }

                if (tableWrapper) {
                    tableWrapper.hidden = false;
                }
            }

            paginatedData.forEach(function(item) {
                const quantity = n(item.qty);
                const used = n(item.used);
                const balance = quantity - used;
                const usagePercent = quantity > 0 ?
                    Math.min(100, Math.max(0, (used / quantity) * 100)) :
                    0;

                const balLabel =
                    balance <= 0 ?
                    'Out of stock' :
                    balance <= 5 ?
                    'Low stock' :
                    balance <= 10 ?
                    'Running low' :
                    'In Stock';

                const balStatusClass =
                    balance <= 0 ?
                    'status-out-of-stock' :
                    balance <= 5 ?
                    'status-low-stock' :
                    balance <= 10 ?
                    'status-pending' :
                    'status-in-stock';

                const catStatusClass =
                    item.category === 'Medicine' ?
                    'status-medicine' :
                    'status-supplies';

                const expirationStatus =
                    item.expiration_status ||
                    'none';

                const expirationDays =
                    item.expiration_days_remaining;

                const expirationDisplay =
                    getInventoryExpirationDisplay(
                        expirationStatus,
                        expirationDays
                    );

                const expirationDate =
                    formatInventoryDisplayDate(
                        item.expiration_date,
                        item.formatted_expiration_date || '—'
                    );

                const receivedDate =
                    formatInventoryDisplayDate(
                        item.date_received,
                        item.formatted_date || '—'
                    );

                const unit =
                    String(item.unit || '').trim() ||
                    'item';

                const categoryMarkup = `
                    <span class="status-pill ${catStatusClass}">
                        <span class="status-dot"></span>
                        ${item.category || '—'}
                    </span>
                `;

                const stockStatusMarkup = `
                    <span class="status-pill ${balStatusClass}">
                        <span class="status-dot"></span>
                        ${balLabel}
                    </span>
                `;

                const expirationMarkup = `
                    <span class="inventory-expiration-lead ${expirationDisplay.tone}">
                        ${expirationDisplay.label}
                    </span>

                    <span class="inventory-expiration-date-text">
                        ${expirationDate}
                    </span>
                `;

                const usageMarkup = `
                    <div class="inventory-usage-block">
                        <div class="inventory-usage-label">
                            <strong>${used}</strong>
                            <span>/ ${quantity} ${unit}</span>
                        </div>

                        <div class="inventory-usage-track" aria-hidden="true">
                            <span style="width:${usagePercent}%"></span>
                        </div>
                    </div>
                `;

                if (currentViewMode === 'grid') {
                    grid.innerHTML += `
                        <article class="table-record-card inventory-grid-card">
                            <div class="inventory-grid-card-content">
                                <div class="inventory-grid-card-heading">
                                    <h3 class="table-record-title inventory-grid-item-name">
                                        ${item.name || '—'}
                                    </h3>

                                    <span class="inventory-stock-code">
                                        ${item.stock_no || '—'}
                                    </span>
                                </div>

                                <div class="global-info-group inventory-grid-statuses">
                                    ${categoryMarkup}
                                    ${stockStatusMarkup}
                                </div>

                                <div class="inventory-grid-level-box">
                                    <strong class="inventory-grid-balance-value">
                                        ${balance}
                                    </strong>

                                    <span class="inventory-grid-balance-unit">
                                        ${unit} left
                                    </span>
                                </div>

                                <div class="inventory-grid-details">
                                    <div class="inventory-grid-detail-row">
                                        <span class="inventory-grid-detail-key">
                                            Expiration
                                        </span>

                                        <span class="inventory-grid-detail-value inventory-grid-expiration-value">
                                            <span class="inventory-expiration-lead ${expirationDisplay.tone}">
                                                ${expirationDisplay.label}
                                            </span>
                                            <span class="inventory-grid-detail-separator">·</span>
                                            <span>${expirationDate}</span>
                                        </span>
                                    </div>

                                    <div class="inventory-grid-detail-row">
                                        <span class="inventory-grid-detail-key">
                                            Received
                                        </span>

                                        <span class="inventory-grid-detail-value">
                                            ${receivedDate}
                                        </span>
                                    </div>

                                    <div class="inventory-grid-detail-row">
                                        <span class="inventory-grid-detail-key">
                                            Usage
                                        </span>

                                        <span class="inventory-grid-detail-value">
                                            ${used}/${quantity} ${unit}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="inventory-grid-actions ui-action-group">
                                ${inventoryActionButtons(item.id)}
                            </div>
                        </article>
                    `;

                    return;
                }

                tbody.innerHTML += `
                    <div class="table-list-row">
                        <div class="inventory-list-layout">
                            <div class="inventory-item-cell inventory-col-item">
                                <div class="table-record-title inventory-list-item-name">
                                    ${item.name || '—'}
                                </div>

                                <div class="inventory-stock-code">
                                    ${item.stock_no || '—'}
                                </div>
                            </div>

                            <div class="inventory-col-category" data-label="Category">
                                ${categoryMarkup}
                            </div>

                            <div class="inventory-stock-level" data-label="Stock level">
                                <strong class="inventory-balance-number">
                                    ${balance}
                                </strong>

                                ${stockStatusMarkup}
                            </div>

                            <div class="inventory-col-expiration" data-label="Expiration">
                                ${expirationMarkup}
                            </div>

                            <div class="inventory-col-usage" data-label="Usage">
                                ${usageMarkup}
                            </div>

                            <div class="inventory-col-received" data-label="Received">
                                <span class="inventory-received-date">
                                    ${receivedDate}
                                </span>
                            </div>

                            <div class="table-action-cell inventory-list-actions" data-label="Actions">
                                <div class="ui-action-group">
                                    ${inventoryActionButtons(item.id)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        function resetAddForm() {
            const addCategory =
                document.getElementById(
                    'addCategory'
                );

            if (addCategory) {
                addCategory.value = '';

                addCategory.classList.remove(
                    'is-invalid',
                    'is-valid'
                );

                addCategory.removeAttribute(
                    'aria-invalid'
                );

                addCategory.removeAttribute(
                    'aria-describedby'
                );

                addCategory.setCustomValidity('');

                const categoryWrapper =
                    addCategory.closest(
                        '.custom-select'
                    );

                categoryWrapper?.classList.remove(
                    'is-invalid',
                    'is-valid'
                );

                window.syncCustomSelect?.(
                    categoryWrapper
                );
            }

            [
                'addDate',
                'addExpirationDate',
                'addStock',
                'addName',
                'addUnit'
            ].forEach(function(id) {
                const field =
                    document.getElementById(id);

                if (!field) {
                    return;
                }

                if (id === 'addExpirationDate') {
                    clearInventoryDate(id);
                } else {
                    field.value = '';
                }

                field.classList.remove(
                    'is-invalid',
                    'is-valid'
                );

                field.removeAttribute(
                    'aria-invalid'
                );

                field.removeAttribute(
                    'aria-describedby'
                );

                field.setCustomValidity('');
            });

            const addQty =
                document.getElementById(
                    'addQty'
                );

            const addUsed =
                document.getElementById(
                    'addUsed'
                );

            if (addQty) {
                addQty.value = '1';
            }

            if (addUsed) {
                addUsed.value = '0';
            }

            computeAddBalance();

            const form =
                document.getElementById(
                    'addInventoryForm'
                );

            form
                ?.querySelectorAll(
                    'input, select, textarea'
                )
                .forEach(function(field) {
                    field.classList.remove(
                        'is-invalid',
                        'is-valid'
                    );

                    field.removeAttribute(
                        'aria-invalid'
                    );

                    field.removeAttribute(
                        'aria-describedby'
                    );

                    field.setCustomValidity('');
                });

            form
                ?.querySelectorAll(
                    '.custom-select'
                )
                .forEach(function(wrapper) {
                    wrapper.classList.remove(
                        'is-invalid',
                        'is-valid'
                    );

                    window.syncCustomSelect?.(
                        wrapper
                    );
                });

            form
                ?.querySelectorAll(
                    '.global-field-error'
                )
                .forEach(function(error) {
                    error.classList.remove(
                        'show'
                    );

                    error.textContent = '';

                    error.setAttribute(
                        'aria-hidden',
                        'true'
                    );
                });

            document
                .querySelectorAll(
                    '#addModal [data-voice-status]'
                )
                .forEach(function(status) {
                    status.classList.add(
                        'hidden'
                    );

                    status.textContent = '';
                });
        }

        async function addItem() {

            var btnSave = document.getElementById('btnSaveAdd');
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
            var res = await fetch(
                @json($inventoryStoreUrl), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        category: document.getElementById('addCategory').value,
                        date_received: document.getElementById('addDate').value,
                        expiration_date: document.getElementById('addExpirationDate').value || null,
                        stock_no: document.getElementById('addStock').value.trim(),
                        name: document.getElementById('addName').value.trim(),
                        unit: document.getElementById('addUnit').value.trim(),
                        qty: Number(document.getElementById('addQty').value),
                        used: Number(document.getElementById('addUsed').value || 0)
                    })
                });
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Item';
            if (!res.ok) {
                const errorPayload = await res.json().catch(function() {
                    return {};
                });

                if (errorPayload.errors) {
                    const fieldMap = {
                        category: 'addCategory',
                        date_received: 'addDate',
                        expiration_date: 'addExpirationDate',
                        stock_no: 'addStock',
                        name: 'addName',
                        unit: 'addUnit',
                        qty: 'addQty',
                        used: 'addUsed'
                    };

                    Object.entries(errorPayload.errors).forEach(function([key, messages]) {
                        const fieldId = fieldMap[key];

                        if (!fieldId) return;

                        const field = document.getElementById(fieldId);
                        const message = Array.isArray(messages) ?
                            messages[0] :
                            messages;

                        window.showFormInputValidationMessage?.(
                            field,
                            message
                        );
                    });
                } else {
                    showToast(
                        'error',
                        errorPayload.message ||
                        'Could not save item. Please try again.'
                    );
                }

                return;
            }

            window.closeModal?.('addModal');
            resetAddForm();
            await loadInventory();
            showToast('success', 'Item added successfully!');
        }

        function deleteItem(id) {
            const item = inventory.find(row =>
                Number(row.id) === Number(id)
            );

            window.openDeleteConfirmModal({
                modalId: 'deleteModal',
                formId: 'inventoryDeleteForm',
                nameId: 'inventoryDeleteName',
                action: inventoryUrl('destroy', id),
                itemName: item?.name || item?.stock_no || 'this item',
                recordId: id,
            });
        }

        document
            .getElementById('inventoryDeleteForm')
            ?.addEventListener('submit', async function(event) {
                event.preventDefault();

                const id = this.dataset.recordId;

                if (!id) return;

                const response = await fetch(
                    inventoryUrl('destroy', id), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .content,
                            Accept: 'application/json',
                        },
                    }
                );

                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    showToast(
                        'error',
                        result.message || 'Delete failed.'
                    );
                    return;
                }

                window.closeModal('deleteModal');

                await loadInventory();

                showToast('success', 'Item deleted.');
            });

        var editId = null;

        function clearInventoryDate(id) {
            const input = document.getElementById(id);

            if (!input) return;

            if (input._flatpickr) {
                input._flatpickr.clear();
            } else {
                input.value = '';
                input.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        }

        function openEdit(id) {
            editId = id;

            const item = inventory.find(function(entry) {
                return Number(entry.id) === Number(id);
            });

            if (!item) return;

            const editCategory =
                document.getElementById('editCategory');

            if (editCategory) {
                editCategory.value = item.category || '';

                editCategory.dispatchEvent(
                    new Event('change', {
                        bubbles: true
                    })
                );
            }

            const editUnit =
                document.getElementById('editUnit');

            if (editUnit) {
                editUnit.value =
                    item.unit || '';
            }

            document.getElementById('editStock').value =
                item.stock_no || '';

            const editName =
                document.getElementById('editName');

            if (editName) {
                editName.value =
                    item.name || '';

                window.initCharLimitFields?.(
                    document.getElementById('editModal')
                );

                editName.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );
            }

            const editQty =
                document.getElementById('editQty');

            const editUsed =
                document.getElementById('editUsed');

            if (editQty) {
                editQty.value = item.qty ?? 0;

                editQty.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );
            }

            if (editUsed) {
                editUsed.value = item.used ?? 0;

                editUsed.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );
            }

            document.getElementById('editDate').value =
                item.date_received ?
                String(item.date_received).slice(0, 10) :
                '';

            const editExpirationDate = document.getElementById('editExpirationDate');
            const expirationDateValue = item.expiration_date ?
                String(item.expiration_date).slice(0, 10) :
                '';

            if (editExpirationDate?._flatpickr) {
                editExpirationDate._flatpickr.setDate(expirationDateValue, false);
            } else if (editExpirationDate) {
                editExpirationDate.value = expirationDateValue;
            }

            document.querySelectorAll(
                '#editModal .custom-select'
            ).forEach(function(wrapper) {
                window.syncCustomSelect?.(wrapper);
            });

            computeEditBalance();
            window.openModal?.('editModal');

            const editModal =
                document.getElementById(
                    'editModal'
                );

            window.initGlobalVoiceInputs?.(
                editModal
            );

            document.dispatchEvent(
                new CustomEvent(
                    'voice:refresh', {
                        detail: {
                            root: editModal
                        }
                    }
                )
            );
        }

        async function saveEdit() {
            if (!editId) return;

            const res = await fetch(inventoryUrl('update', editId), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    category: document.getElementById('editCategory').value,
                    date_received: document.getElementById('editDate').value,
                    expiration_date: document.getElementById('editExpirationDate').value || null,
                    stock_no: document.getElementById('editStock').value.trim(),
                    name: document.getElementById('editName').value.trim(),
                    unit: document.getElementById('editUnit').value.trim(),
                    qty: Number(document.getElementById('editQty').value),
                    used: Number(document.getElementById('editUsed').value || 0)
                })
            });

            const result = await res.json().catch(function() {
                return {};
            });

            if (!res.ok) {
                console.error('Inventory edit failed:', res.status, result);
                showToast('error', result.message || 'Edit failed — please try again.');
                return;
            }

            if (window.closeModal) {
                closeModal('editModal');
            } else {
                document.getElementById('editModal')?.close();
            }

            editId = null;
            await loadInventory();
            showToast('success', 'Item updated successfully!');
        }

        function computeAddBalance() {
            document.getElementById('addBalance').value = Number(document.getElementById('addQty').value || 0) - Number(
                document.getElementById('addUsed').value || 0);
        }

        function computeEditBalance() {
            document.getElementById('editBalance').value = Number(document.getElementById('editQty').value || 0) - Number(
                document.getElementById('editUsed').value || 0);
        }
    </script>
@endsection