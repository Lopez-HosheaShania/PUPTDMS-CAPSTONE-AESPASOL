@extends('layouts.app')

@php
    $layoutRole = $layoutRole ?? 'admin';
    $isDentistView = $isDentistView ?? false;
    $canViewProcedureInventoryNotes =
        $isDentistView &&
        auth()
            ->user()
            ?->hasAnyRole(['dentist', 'dentist_role']) &&
        !session()->has('impersonated_role');

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
                        <div class="dentist-hero-content w-full justify-between">
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
                            @if ($canViewProcedureInventoryNotes)
                                <x-button type="button" variant="secondary" size="sm" class="shrink-0"
                                    data-open-modal="procedureInventoryNotesModal" aria-label="Procedure Inventory Notes"
                                    aria-haspopup="dialog" aria-controls="procedureInventoryNotesModal">
                                    <span>Procedure Inventory Notes</span>
                                    <span id="procedureInventoryNotesCount" class="filter-badge">
                                        0
                                    </span>
                                </x-button>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <x-page-banner title="Admin Inventory" class="inventory-admin-banner" />
            @endif

            <div class="relative z-10 mt-4 pb-8 inventory-page-content">

                <div class="stat-grid inventory-stat-grid" id="inventoryStats">
                    <x-stat-card label="Total Items" value="—" tone="crimson" icon="fa-solid fa-boxes-stacked"
                        footer="all categories" :value-attributes="['id' => 'statTotal']" />

                    <x-stat-card label="Medicines" value="—" tone="blue" icon="fa-solid fa-pills"
                        footer="pharmaceutical" :value-attributes="['id' => 'statMedicine']" />

                    <x-stat-card label="Supplies" value="—" tone="green" icon="fa-solid fa-syringe"
                        footer="consumables" :value-attributes="['id' => 'statSupplies']" />

                    <x-stat-card label="Low Stock" value="—" tone="amber" icon="fa-solid fa-triangle-exclamation"
                        footer="need restocking" :value-attributes="['id' => 'statLow']" />
                </div>

                <div class="table-card">

                    <div class="table-toolbar">

                        <div class="table-toolbar-title">
                            <div class="tab-group" role="tablist" aria-label="Inventory category">

                                <button type="button" data-tab="all" aria-selected="true" class="tab-btn active"
                                    data-ui-click="action-0f4a99c12b4d">
                                    All
                                </button>

                                <button type="button" data-tab="medicine" aria-selected="false" class="tab-btn"
                                    data-ui-click="action-6af6d8c34f27">
                                    Medicine
                                </button>

                                <button type="button" data-tab="supplies" aria-selected="false" class="tab-btn"
                                    data-ui-click="action-527a7d205eda">
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

                            <button id="filterBtn" type="button" data-ui-click="action-3b869f2f8687"
                                class="global-filter-btn">

                                <i class="fa-solid fa-sliders"></i>
                                <span>Filter</span>
                                <span id="filterBadge" class="filter-badge">
                                </span>
                            </button>

                            <x-view-toggle id="inventoryViewToggle" root="#mainContent" storage-key="inventoryViewMode" />

                            <button id="externalClearFilterBtn" type="button" data-ui-click="action-eb2a40caced3"
                                class="global-filter-reset-btn hidden" title="Reset filters">

                                <i class="fa-solid fa-rotate-left"></i>
                            </button>

                            <x-button type="button" variant="primary" size="sm" class="table-toolbar-primary-action"
                                icon="fa-solid fa-plus" data-ui-click="action-0d547826d7f9">
                                Add Item
                            </x-button>

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

    <x-filter-drawer id="filterModal" title="Filters" close-callback="action-945b15a06543"
        clear-id="clearFilterPanelBtn" clear-callback="action-109110749c54" clear-label="Clear Filters"
        cancel-callback="action-945b15a06543" cancel-label="Cancel" apply-id="saveFilterPanelBtn"
        apply-callback="action-362188adc405" apply-label="Show 0 results" results-id="showResultsText">

        <div id="activeFiltersSection" class="filter-active-section hidden">

            <div class="filter-active-header">

                <span class="filter-active-title">
                    Active Filters
                </span>

                <x-button id="clearAllChipsBtn" type="button" variant="secondary" size="sm"
                    icon="fa-solid fa-rotate-left" class="filter-clear-all">
                    Clear All
                </x-button>

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

                    <input id="fp_dateFrom" type="text" class="form-input-custom js-flatpickr-date-range-from"
                        placeholder="Start date" readonly autocomplete="off">

                    <i class="fa-regular fa-calendar"></i>

                </div>

                <div class="filter-date-input-wrap">

                    <input id="fp_dateTo" type="text" class="form-input-custom js-flatpickr-date-range-to"
                        placeholder="End date" readonly autocomplete="off">

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

        <form id="addInventoryForm" class="ui-modal-card modal-lg modal-card-form" data-global-validation
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
                            Enter the item details and starting stock.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="addModal"
                    aria-label="Close add inventory modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd space-y-5">
                <section class="modal-form-section">
                    <x-section-label label="Item" />

                    <div class="modal-form-grid-2">
                        <div class="inventory-field" data-global-field>
                            <label for="addCategory" class="inventory-field-label">
                                Category
                                <span class="required-mark">*</span>
                            </label>

                            <x-select id="addCategory" name="category" required data-placeholder="Select category"
                                data-field-label="Category" data-required-message="Please select a category.">
                                <option value="">Select category</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Supplies">Supplies</option>
                            </x-select>
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
                                        data-validation-rule="uniqueInventoryStock" data-ui-input="action-32e9868b0600"
                                        required>
                                </div>

                                <x-voice-input target="#addStock" status-id="addStockVoiceStatus"
                                    label="Voice input for stock number" title="Voice input" />
                            </div>
                        </div>

                        <div class="inventory-field inventory-field-full" data-global-field>

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
                                    label="Voice input for supply or medicine name" title="Voice input" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="modal-form-section">
                    <x-section-label label="Stock" />

                    <div id="addStockFieldsGrid" class="grid grid-cols-1 md:grid-cols-3 gap-[0.85rem]">

                        <div class="inventory-field" data-global-field>
                            <label for="addUnit" class="inventory-field-label">
                                Unit
                                <span class="required-mark">*</span>
                            </label>

                            <input id="addUnit" name="unit" type="text" class="form-input-custom"
                                placeholder="e.g. Bottle" maxlength="50" data-field-label="Unit"
                                data-required-message="Please enter a unit." required>
                        </div>

                        <div id="addBottleFields" hidden>
                            <div class="inventory-field" data-global-field>
                                <label for="addBottleCapacity" class="inventory-field-label">
                                    Volume per Bottle (mL)
                                    <span class="required-mark">*</span>
                                </label>

                                <x-number-stepper id="addBottleCapacity" name="volume_per_bottle_ml" min="0.01"
                                    max="99999999.99" step="1" inputmode="decimal" disabled
                                    decrease-label="Decrease volume per bottle"
                                    increase-label="Increase volume per bottle" placeholder="e.g. 100"
                                    data-field-label="Volume per Bottle"
                                    data-required-message="Please enter the volume per bottle." />
                            </div>
                        </div>

                        <div class="inventory-field" data-global-field>
                            <label for="addQty" class="inventory-field-label">
                                <span id="addQtyLabel">Quantity</span>
                                <span class="required-mark">*</span>
                            </label>

                            <x-number-stepper id="addQty" name="qty" value="1" min="1"
                                max="99999" step="1" inputmode="numeric" required
                                decrease-label="Decrease quantity" increase-label="Increase quantity"
                                data-field-label="Quantity" data-required-message="Please enter a quantity."
                                data-validation-rule="wholeNumber" data-ui-input="action-ea53196008ea" />
                        </div>

                        <div class="inventory-field" data-global-field>
                            <label for="addUsed" class="inventory-field-label">
                                <span id="addUsedLabel">Consumed</span>
                                <span class="field-optional">(Optional)</span>
                            </label>

                            <x-number-stepper id="addUsed" name="used" value="0" min="0"
                                max="99999" step="1" inputmode="numeric" required
                                decrease-label="Decrease consumed quantity" increase-label="Increase consumed quantity"
                                data-field-label="Consumed" data-required-message="Please enter the consumed quantity."
                                data-validation-rule="inventoryConsumed" data-ui-input="action-ea53196008ea" />
                        </div>
                    </div>

                    <input id="addBalance" type="hidden" value="0">

                    <div class="global-info-item global-info-item-compact justify-between mt-3">
                        <span class="global-info-subvalue m-0">
                            Total stock
                        </span>

                        <strong id="addBalanceSummary" class="global-info-value m-0 text-right" aria-live="polite">
                            1 item
                        </strong>
                    </div>
                </section>

                <section class="modal-form-section">
                    <x-section-label label="Dates" />

                    <div class="modal-form-grid-2">
                        <div class="inventory-field" data-global-field>
                            <label for="addDate" class="inventory-field-label">
                                Date Received
                                <span class="required-mark">*</span>
                            </label>

                            <div class="inventory-date-control">
                                <input id="addDate" name="date_received" type="text"
                                    class="field-input form-input-custom js-flatpickr-date-max-today"
                                    data-field-label="Date Received" data-required-message="Please select a date."
                                    data-validation-rule="notFutureDate" placeholder="Select date" required readonly>
                            </div>
                        </div>

                        <div class="inventory-field" data-global-field>
                            <label for="addExpirationDate" class="inventory-field-label">
                                Expiration Date <span class="field-optional">(optional)</span>
                            </label>

                            <div class="inventory-date-control">
                                <input id="addExpirationDate" name="expiration_date" type="text"
                                    class="field-input form-input-custom js-flatpickr-date"
                                    data-field-label="Expiration Date" placeholder="Select date" readonly>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-ft items-center">
                <x-button type="button" variant="secondary" data-discard-close="addModal">
                    Cancel
                </x-button>

                <x-button id="btnSaveAdd" type="submit" variant="primary">
                    Save Item
                </x-button>
            </div>
        </form>
    </div>

    <div id="editModal" class="ui-modal modal-theme-primary inventory-form-modal" aria-hidden="true">

        <form id="editInventoryForm" class="ui-modal-card modal-lg modal-card-form" data-global-validation
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

                        <p id="editInventorySubtitle" class="modal-subtitle">
                            Update the item details and stock usage.
                        </p>
                    </div>
                </div>

                <button type="button" class="modal-x" data-discard-close="editModal"
                    aria-label="Close edit inventory modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-bd space-y-5">
                <section class="modal-form-section">
                    <x-section-label label="Item" />

                    <div class="modal-form-grid-2">
                        <div class="inventory-field" data-global-field>
                            <label for="editCategory" class="inventory-field-label">
                                Category
                                <span class="required-mark">*</span>
                            </label>

                            <x-select id="editCategory" name="category" required data-placeholder="Select category"
                                data-field-label="Category" data-required-message="Please select a category.">
                                <option value="">Select category</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Supplies">Supplies</option>
                            </x-select>
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
                                        data-validation-rule="uniqueInventoryStockEdit"
                                        data-ui-input="action-32e9868b0600" required>
                                </div>

                                <x-voice-input target="#editStock" status-id="editStockVoiceStatus"
                                    label="Voice input for stock number" title="Voice input" />
                            </div>
                        </div>

                        <div class="inventory-field inventory-field-full" data-global-field>

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
                                    label="Voice input for supply or medicine name" title="Voice input" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="modal-form-section">
                    <x-section-label label="Stock" />

                    <div class="modal-form-grid-2">
                        <div class="inventory-field" data-global-field>
                            <label for="editQty" class="inventory-field-label">
                                <span id="editQtyLabel">Quantity</span>
                                <span class="required-mark">*</span>
                            </label>

                            <x-number-stepper id="editQty" name="qty" min="1" max="99999"
                                step="1" inputmode="numeric" required decrease-label="Decrease quantity"
                                increase-label="Increase quantity" data-field-label="Quantity"
                                data-required-message="Please enter a quantity." data-validation-rule="wholeNumber"
                                data-ui-input="action-8fba7d16f720" />
                        </div>

                        <div class="inventory-field" data-global-field>
                            <label for="editUsed" class="inventory-field-label">
                                <span id="editUsedLabel">Consumed</span>
                                <span class="field-optional">(Optional)</span>
                            </label>

                            <x-number-stepper id="editUsed" name="used" min="0" max="99999"
                                step="1" inputmode="numeric" required decrease-label="Decrease consumed quantity"
                                increase-label="Increase consumed quantity" data-field-label="Consumed"
                                data-required-message="Please enter the consumed quantity."
                                data-validation-rule="inventoryConsumed" data-ui-input="action-8fba7d16f720" />
                        </div>
                    </div>

                    <div class="global-info-group mt-3">
                        <span class="global-info-pill">
                            Unit <strong id="editUnitSummary">—</strong>
                            <span class="required-mark">*</span>
                        </span>

                        <div id="editBottleFields" class="global-info-group" hidden>
                            <span class="global-info-pill">
                                Volume per bottle <strong id="editBottleCapacitySummary">—</strong>
                            </span>

                            <span class="global-info-pill">
                                Current bottle <strong id="editBottleRemainingSummary">—</strong>
                            </span>
                        </div>
                    </div>

                    <p class="modal-helper-text mt-2 mb-0">
                        Unit and volume can't be changed after the item is added.
                    </p>

                    <div class="global-info-item global-info-item-compact justify-between mt-3">
                        <span class="global-info-subvalue m-0">
                            Balance after saving
                        </span>

                        <strong id="editBalanceSummary" class="global-info-value m-0 text-right" aria-live="polite">
                            —
                        </strong>
                    </div>

                    <div class="hidden">
                        <input id="editUnit" name="unit" type="text" class="form-input-custom" required>

                        <div id="editBottleCapacityField" class="hidden" hidden>
                            <input id="editBottleCapacity" name="volume_per_bottle_ml" type="number"
                                class="form-input-custom" min="0.01" max="99999999.99" step="0.01"
                                inputmode="decimal" disabled>
                        </div>

                        <input id="editBottleRemaining" type="text" readonly>
                        <input id="editBalance" type="hidden" value="0">
                    </div>
                </section>

                <section class="modal-form-section">
                    <x-section-label label="Dates" />

                    <div class="modal-form-grid-2">
                        <div class="inventory-field" data-global-field>
                            <label for="editDate" class="inventory-field-label">
                                Date Received
                                <span class="required-mark">*</span>
                            </label>

                            <div class="inventory-date-control">
                                <input id="editDate" name="date_received" type="text"
                                    class="field-input form-input-custom js-flatpickr-date"
                                    data-field-label="Date Received" data-required-message="Please select a date."
                                    data-validation-rule="notFutureDate" required readonly>
                            </div>
                        </div>

                        <div class="inventory-field" data-global-field>
                            <label for="editExpirationDate" class="inventory-field-label">
                                Expiration Date <span class="field-optional">(optional)</span>
                            </label>

                            <div class="inventory-date-control">
                                <input id="editExpirationDate" name="expiration_date" type="text"
                                    class="field-input form-input-custom js-flatpickr-date"
                                    data-field-label="Expiration Date" placeholder="Select date" readonly>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-ft items-center">
                <x-button type="button" variant="secondary" data-discard-close="editModal">
                    Cancel
                </x-button>

                <x-button type="submit" variant="primary">
                    Save Changes
                </x-button>
            </div>
        </form>
    </div>

    <x-delete-confirm-modal id="deleteModal" form-id="inventoryDeleteForm" name-id="inventoryDeleteName"
        title="Delete Inventory Item" helper="This inventory item will be permanently removed." />

    @if ($canViewProcedureInventoryNotes)
        <div id="procedureInventoryNotesModal" class="ui-modal" role="dialog" aria-modal="true" aria-hidden="true"
            aria-labelledby="procedureInventoryNotesTitle" aria-describedby="procedureInventoryNotesSubtitle">
            <div class="ui-modal-card modal-xl">
                <header class="modal-hd">
                    <div class="modal-heading">
                        <div class="modal-icon" aria-hidden="true"><i class="fa-regular fa-clipboard"></i></div>
                        <div class="modal-copy">
                            <h2 id="procedureInventoryNotesTitle" class="modal-title">Procedure Inventory Notes</h2>
                            <p id="procedureInventoryNotesSubtitle" class="modal-subtitle">
                                Items the dentist recorded during procedures.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="modal-x" data-modal-close="procedureInventoryNotesModal"
                        aria-label="Close inventory notes"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                </header>
                <div class="modal-bd modal-form-grid">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">

                        <x-search-bar id="procedureInventoryNotesSearch" placeholder="Search patient, procedure or item"
                            callback="handleProcedureInventoryNotesSearch" :debounce="200" class="flex-1" />

                        <x-filter-select id="procedureInventoryNotesFilter" name="procedure_inventory_notes_filter"
                            label="Date" value="all" callback="handleProcedureInventoryNotesFilter"
                            menu-align="right" :searchable="false" :options="[
                                [
                                    'value' => 'all',
                                    'label' => 'All',
                                    'icon' => 'fa-list',
                                    'tone' => 's-all',
                                ],
                                [
                                    'value' => 'today',
                                    'label' => 'Today',
                                    'icon' => 'fa-calendar-day',
                                    'tone' => 's-today',
                                ],
                                [
                                    'value' => 'week',
                                    'label' => 'This week',
                                    'icon' => 'fa-calendar-week',
                                    'tone' => 's-upcoming',
                                ],
                            ]" />

                    </div>

                    <p class="modal-helper-text">Reference notes only. Stock quantities are managed separately in
                        Inventory.</p>
                    <div id="procedureInventoryNotesLoading" class="table-list-view" role="status"
                        aria-label="Loading procedure inventory notes" hidden>
                        <div class="table-list-body space-y-2">
                            @for ($row = 0; $row < 3; $row++)
                                <div class="table-list-row">
                                    <div
                                        class="
                        grid
                        grid-cols-1
                        gap-3
                        p-3
                        sm:grid-cols-4
                        animate-pulse
                    ">
                                        @for ($column = 0; $column < 4; $column++)
                                            <div class="space-y-2">
                                                <div
                                                    class="
                                    h-3
                                    w-24
                                    rounded
                                    bg-[var(--border)]
                                ">
                                                </div>

                                                <div
                                                    class="
                                    h-2
                                    w-16
                                    rounded
                                    bg-[var(--surface)]
                                ">
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div id="procedureInventoryNotesEmptyState"
                        class="empty-state-host min-h-[16rem] sm:min-h-[22rem] flex items-center justify-center" hidden>
                    </div>

                    <div id="procedureInventoryNotesTable" class="table-list-view" hidden>
                        <div class="table-list-header hidden sm:grid sm:grid-cols-4 sm:gap-3">
                            <div>Date</div>
                            <div>Patient</div>
                            <div>Procedure</div>
                            <div>Items Used</div>
                        </div>

                        <div id="procedureInventoryNotesEntries" class="table-list-body"></div>
                    </div>

                    <p id="procedureInventoryNotesShowing" class="modal-helper-text" hidden></p>
                </div>
            </div>
        </div>
    @endif

@endsection

@section('scripts')

    @if ($canViewProcedureInventoryNotes)
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            (() => {
                const endpoint = @json(route('dentist.inventory.procedure-notes'));
                const modal = document.getElementById('procedureInventoryNotesModal');
                const table = document.getElementById('procedureInventoryNotesTable');
                const entries = document.getElementById('procedureInventoryNotesEntries');
                const loading = document.getElementById('procedureInventoryNotesLoading');
                const emptyState = document.getElementById('procedureInventoryNotesEmptyState');
                const showing = document.getElementById('procedureInventoryNotesShowing');
                const search = document.getElementById('procedureInventoryNotesSearch');
                const countBadge = document.getElementById('procedureInventoryNotesCount');

                let requestNumber = 0;
                let activeRequest;
                let allNotes = [];
                let activeFilter = 'all';

                window.handleProcedureInventoryNotesSearch =
                    function() {
                        renderNotes();
                    };

                window.handleProcedureInventoryNotesFilter =
                    function(value) {
                        const allowedFilters = [
                            'all',
                            'today',
                            'week'
                        ];

                        activeFilter =
                            allowedFilters.includes(value) ?
                            value :
                            'all';

                        renderNotes();
                    };

                function addText(parent, tag, className, value) {
                    const element = document.createElement(tag);
                    element.className = className;
                    element.textContent = value;
                    parent.append(element);
                    return element;
                }

                function updateCount(total) {
                    if (countBadge) {
                        countBadge.textContent = String(total);
                    }
                }

                function getNoteDate(note) {
                    const rawDate = note.procedure_date || note.visit_date;
                    if (!rawDate) return null;

                    const parsed = new Date(rawDate);
                    return Number.isNaN(parsed.getTime()) ? null : parsed;
                }

                function isInActiveDateFilter(note) {
                    if (activeFilter === 'all') return true;

                    const noteDate = getNoteDate(note);
                    if (!noteDate) return false;

                    const now = new Date();
                    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const tomorrowStart = new Date(todayStart);
                    tomorrowStart.setDate(tomorrowStart.getDate() + 1);

                    if (activeFilter === 'today') {
                        return noteDate >= todayStart && noteDate < tomorrowStart;
                    }

                    const weekStart = new Date(todayStart);
                    const mondayOffset = (todayStart.getDay() + 6) % 7;
                    weekStart.setDate(weekStart.getDate() - mondayOffset);

                    const weekEnd = new Date(weekStart);
                    weekEnd.setDate(weekEnd.getDate() + 7);

                    return noteDate >= weekStart && noteDate < weekEnd;
                }

                function matchesSearch(note) {
                    const query = search.value.trim().toLowerCase();
                    if (!query) return true;

                    return [
                        note.patient_name,
                        note.procedure_name,
                        note.inventory_notes,
                        note.patient_id,
                        note.appointment_id,
                    ].some(value => String(value ?? '').toLowerCase().includes(query));
                }

                function formatNoteDateTime(note) {
                    const raw =
                        note.procedure_date ||
                        note.visit_date;

                    if (!raw) {
                        return {
                            date: 'Unavailable',
                            time: ''
                        };
                    }

                    const parsed =
                        new Date(raw);

                    if (
                        Number.isNaN(
                            parsed.getTime()
                        )
                    ) {
                        return {
                            date: raw,
                            time: ''
                        };
                    }

                    const hasTime =
                        /[T\s]\d{1,2}:\d{2}/.test(
                            String(raw)
                        );

                    return {
                        date: new Intl.DateTimeFormat(
                            'en-US', {
                                month: 'short',
                                day: '2-digit',
                                year: 'numeric'
                            }
                        ).format(parsed),

                        time: hasTime ?
                            new Intl.DateTimeFormat(
                                'en-US', {
                                    hour: 'numeric',
                                    minute: '2-digit',
                                    hour12: true
                                }
                            ).format(parsed) : ''
                    };
                }

                function renderNotes() {
                    const visibleNotes = allNotes.filter(note =>
                        matchesSearch(note) && isInActiveDateFilter(note)
                    );

                    entries.replaceChildren();

                    for (const note of visibleNotes) {
                        const row = addText(entries, 'article', 'table-list-row', '');
                        const layout = addText(row, 'div', 'grid grid-cols-1 gap-3 p-3 sm:grid-cols-4 sm:items-start', '');

                        const dateCell = addText(layout, 'div', 'min-w-0', '');
                        const noteDateTime = formatNoteDateTime(note);

                        addText(dateCell, 'strong', 'global-info-value m-0', noteDateTime.date);

                        if (noteDateTime.time) {
                            addText(
                                dateCell,
                                'p',
                                'modal-helper-text',
                                noteDateTime.time
                            );
                        }

                        const patientCell = addText(layout, 'div', 'min-w-0', '');
                        addText(patientCell, 'strong', 'global-info-value m-0', note.patient_name || 'Unavailable');
                        addText(patientCell, 'p', 'modal-helper-text', `Patient #${note.patient_id ?? 'Unavailable'}`);

                        const procedureCell = addText(layout, 'div', 'min-w-0', '');
                        addText(procedureCell, 'strong', 'global-info-value m-0', note.procedure_name || 'Unavailable');
                        addText(procedureCell, 'p', 'modal-helper-text', `Visit #${note.appointment_id ?? 'Unavailable'}`);

                        const itemsCell = addText(layout, 'div', 'min-w-0', '');
                        addText(itemsCell, 'span', 'status-pill status-pending whitespace-pre-wrap break-words', note
                            .inventory_notes || 'No items recorded');
                    }

                    const hasResults =
                        visibleNotes.length > 0;

                    const query =
                        search.value
                        .trim();

                    table.hidden = !hasResults;

                    window.EmptyState?.hide(
                        emptyState
                    );

                    emptyState.hidden =
                        hasResults;

                    if (!hasResults) {

                        emptyState.hidden = false;

                        if (!allNotes.length) {

                            window.EmptyState?.render({
                                host: emptyState,
                                icon: 'fa-clipboard',
                                title: 'No procedure notes yet',
                                message: 'Inventory notes recorded during procedures will appear here.',
                                className: 'empty-state-compact',
                            });

                        } else if (query) {

                            window.EmptyState?.renderSearch({
                                host: emptyState,
                                input: search,
                                query,
                                message: 'Try a different patient, procedure, or item.',
                                className: 'empty-state-compact',
                            });

                        } else if (activeFilter === 'today') {

                            window.EmptyState?.render({
                                host: emptyState,
                                icon: 'fa-calendar-day',
                                title: 'No notes for today',
                                message: 'No procedure inventory notes were recorded today.',
                                className: 'empty-state-compact',
                            });

                        } else if (activeFilter === 'week') {

                            window.EmptyState?.render({
                                host: emptyState,
                                icon: 'fa-calendar-week',
                                title: 'No notes this week',
                                message: 'No procedure inventory notes were recorded this week.',
                                className: 'empty-state-compact',
                            });
                        }
                    }

                    showing.hidden = !allNotes.length;

                    showing.textContent =
                        `Showing ${visibleNotes.length} of ${allNotes.length} ${
                            allNotes.length === 1
                                ? 'note'
                                : 'notes'
                        }`;

                }

                async function loadNotes({
                    render = true
                } = {}) {
                    const currentRequest = ++requestNumber;
                    activeRequest?.abort();
                    activeRequest = new AbortController();
                    const controller = activeRequest;
                    const timeout = setTimeout(() => controller.abort(), 20000);

                    if (render) {
                        entries.replaceChildren();

                        table.hidden = true;
                        loading.hidden = false;
                        showing.hidden = true;
                        emptyState.hidden = true;

                        window.EmptyState?.hide(
                            emptyState
                        );

                        modal.setAttribute(
                            'aria-busy',
                            'true'
                        );
                    }

                    try {
                        const url = new URL(endpoint, window.location.origin);
                        const response = await fetch(url, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            cache: 'no-store',
                            signal: controller.signal
                        });
                        const result = await response.json();
                        if (currentRequest !== requestNumber) return;
                        if (!response.ok || !Array.isArray(result.data)) throw new Error('Notes unavailable');

                        allNotes = result.data;
                        updateCount(allNotes.length);

                        if (render) {
                            renderNotes();
                        }
                    } catch (failure) {
                        if (
                            currentRequest !==
                            requestNumber
                        ) {
                            return;
                        }

                        if (
                            failure?.name ===
                            'AbortError'
                        ) {
                            return;
                        }

                        if (render) {
                            table.hidden = true;
                            showing.hidden = true;
                            emptyState.hidden = false;

                            window.EmptyState?.render({
                                host: emptyState,
                                icon: 'fa-triangle-exclamation',
                                title: 'Unable to load procedure notes',
                                message: 'Procedure inventory notes could not be loaded. Please try again.',
                                className: 'empty-state-compact',

                                actionHtml: `
                <button
                    type="button"
                    class="empty-state-btn"
                    data-procedure-notes-retry
                >
                    <i
                        class="fa-solid fa-rotate-right"
                        aria-hidden="true"
                    ></i>

                    Try again
                </button>
            `,
                            });

                            emptyState
                                .querySelector(
                                    '[data-procedure-notes-retry]'
                                )
                                ?.addEventListener(
                                    'click',
                                    () => {
                                        loadNotes();
                                    }, {
                                        once: true
                                    }
                                );
                        }
                    } finally {
                        clearTimeout(timeout);

                        if (render && currentRequest === requestNumber) {
                            loading.hidden = true;
                            modal.removeAttribute('aria-busy');
                        }
                    }
                }

                document.addEventListener('ui-modal:opened', event => {
                    if (event.detail.modal.id === modal.id) {
                        loadNotes();
                    }
                });

                loadNotes({
                    render: false
                });
            })();
        </script>
    @endif

    @php
        $inventoryDataUrl = route($inventoryRouteNames['data']);

        $inventoryStoreUrl = route($inventoryRouteNames['store']);

        $inventoryUpdateUrlTemplate = route($inventoryRouteNames['update'], ['inventory' => '__ID__']);

        $inventoryDestroyUrlTemplate = route($inventoryRouteNames['destroy'], ['inventory' => '__ID__']);
    @endphp

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
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

        function normalizeInventoryStockNo(value) {
            return String(value || '')
                .replace(/\D/g, '');
        }

        function findDuplicateInventoryStock(
            stockNo,
            ignoreId = null
        ) {
            const normalizedStock =
                normalizeInventoryStockNo(stockNo);

            if (normalizedStock.length !== 5) {
                return null;
            }

            return inventory.find(function(item) {
                const isCurrentItem =
                    ignoreId !== null &&
                    Number(item.id) === Number(ignoreId);

                if (isCurrentItem) {
                    return false;
                }

                return (
                    normalizeInventoryStockNo(
                        item.stock_no
                    ) === normalizedStock
                );
            }) || null;
        }

        function registerInventoryStockValidationRules() {
            if (
                typeof window.registerGlobalValidationRule !==
                'function'
            ) {
                return;
            }

            window.registerGlobalValidationRule(
                'uniqueInventoryStock',
                function(field) {
                    const duplicate =
                        findDuplicateInventoryStock(
                            field.value
                        );

                    return duplicate ?
                        'This stock number is already assigned to another inventory item.' :
                        '';
                }
            );

            window.registerGlobalValidationRule(
                'uniqueInventoryStockEdit',
                function(field) {
                    const duplicate =
                        findDuplicateInventoryStock(
                            field.value,
                            typeof editId !== 'undefined' ?
                            editId :
                            null
                        );

                    return duplicate ?
                        'This stock number is already assigned to another inventory item.' :
                        '';
                }
            );
        }

        if (
            typeof window.registerGlobalValidationRule ===
            'function'
        ) {
            registerInventoryStockValidationRules();
        } else {
            window.addEventListener(
                'global-validation-ready',
                registerInventoryStockValidationRules, {
                    once: true
                }
            );
        }

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
                anchorSelector: '#mainContent.inventory-page .table-card',
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
            return item?.is_volume_bottle ? Number(item.qty || 0) : Number(item?.qty || 0) - Number(item?.used || 0);
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
            window.closeFilterDrawer(
                'filterModal'
            );
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
            data-ui-click="action-640c3ab8383f" data-ui-click-arg-0="${window.uiAttribute(id)}">
            <i class="fa-solid fa-pen"></i>
        </button>

        <button type="button"
            class="ui-action-btn ui-action-delete"
            data-tooltip="Delete inventory item"
aria-label="Delete inventory item"
            data-ui-click="action-a48d004fa9c4" data-ui-click-arg-0="${window.uiAttribute(id)}">
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
                            '#mainContent.inventory-page .table-card'
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
                const balance = itemBalance(item);
                const activeBottleUsed = item.is_volume_bottle && quantity > 0 ?
                    Math.max(0, Number(item.volume_per_bottle_ml) - Number(item.current_bottle_remaining_ml)) : 0;
                const usagePercent = item.is_volume_bottle ?
                    (Number(item.volume_per_bottle_ml) > 0 ? activeBottleUsed / Number(item.volume_per_bottle_ml) *
                        100 : 0) :
                    (quantity > 0 ? Math.min(100, Math.max(0, used / quantity * 100)) : 0);

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
                            <strong>${item.is_volume_bottle ? formatMl(activeBottleUsed) : used}</strong>
                            <span>
                                / ${item.is_volume_bottle
                                    ? formatMl(item.volume_per_bottle_ml) + " mL (active bottle)"
                                    : quantity + " " + getInventoryUnitLabel(unit, quantity)}
                            </span>
                        </div>

                        <div class="inventory-usage-track" aria-hidden="true">
                            <span data-ui-style="width:${window.uiAttribute(usagePercent)}%"></span>
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
    ${getInventoryUnitLabel(unit, balance)} left
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
                                            ${item.is_volume_bottle
    ? `${formatMl(activeBottleUsed)} / ${formatMl(item.volume_per_bottle_ml)} mL (active bottle)`
    : `${used}/${quantity} ${getInventoryUnitLabel(unit, quantity)}`}
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

            document.getElementById('addBottleCapacity').value = '';
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
                        used: isBottleUnit(document.getElementById('addUnit').value) ? 0 : Number(document
                            .getElementById('addUsed').value || 0),
                        volume_per_bottle_ml: isBottleUnit(document.getElementById('addUnit').value) ?
                            document.getElementById('addBottleCapacity').value : null,
                        volume_consumed_ml: isBottleUnit(document.getElementById('addUnit').value) ? (
                            document.getElementById('addUsed').value || null) : null
                    })
                });
            btnSave.disabled = false;
            btnSave.innerHTML = '<span>Save Item</span>';
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
                        used: 'addUsed',
                        volume_per_bottle_ml: 'addBottleCapacity',
                        volume_consumed_ml: 'addUsed'
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

            editBottleOriginal = {
                ...item
            };
            document.getElementById('editBottleCapacity').value = item.volume_per_bottle_ml ?? '';

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

            const editInventorySubtitle =
                document.getElementById('editInventorySubtitle');

            if (editInventorySubtitle) {
                const subtitleParts = [item.name, item.stock_no]
                    .filter(Boolean);

                editInventorySubtitle.textContent =
                    subtitleParts.length ?
                    subtitleParts.join(' · ') :
                    'Update the item details and stock usage.';
            }

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
                editQty.value = isBottleUnit(item.unit) && !item.is_volume_bottle ? itemBalance(item) : (item.qty ?? 0);

                editQty.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );
            }

            if (editUsed) {
                editUsed.value = isBottleUnit(item.unit) ? '' : (item.used ?? 0);

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
        }

        async function saveEdit() {
            if (!editId || inventoryEditSaving) return;
            inventoryEditSaving = true;
            const saveButton = document.querySelector('#editInventoryForm button[type="submit"]');
            if (saveButton) saveButton.disabled = true;
            try {
                const res = await fetch(inventoryUrl('update', editId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
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
                        used: isBottleUnit(document.getElementById('editUnit').value) ? 0 : Number(
                            document.getElementById('editUsed').value || 0),
                        volume_per_bottle_ml: isBottleUnit(document.getElementById('editUnit').value) ?
                            document.getElementById('editBottleCapacity').value : null,
                        volume_consumed_ml: isBottleUnit(document.getElementById('editUnit').value) ? (
                            document.getElementById('editUsed').value || null) : null,
                        stock_revision: editBottleOriginal?.stock_revision || null
                    })
                });

                const result = await res.json().catch(function() {
                    return {};
                });

                if (!res.ok) {
                    console.error('Inventory edit failed:', res.status, result);
                    if (res.status === 409) await loadInventory();
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
            } catch (error) {
                showToast('error', 'Inventory could not be updated. Please try again.');
            } finally {
                inventoryEditSaving = false;
                if (saveButton) saveButton.disabled = false;
            }
        }

        var editBottleOriginal = null;
        var inventoryEditSaving = false;

        function isBottleUnit(unit) {
            return String(unit || '').trim().toLowerCase() === 'bottle';
        }

        function getInventoryUnitLabel(unit, amount) {
            const rawUnit =
                String(unit || 'item')
                .trim()
                .toLowerCase();

            const count = Number(amount);

            const singularOverrides = {
                pieces: 'piece',
                boxes: 'box'
            };

            let singular =
                singularOverrides[rawUnit] ||
                rawUnit;

            if (!singularOverrides[rawUnit]) {
                if (
                    singular.endsWith('ies') &&
                    singular.length > 3
                ) {
                    singular =
                        singular.slice(0, -3) +
                        'y';
                } else if (
                    /(ches|shes|xes|zes)$/.test(singular)
                ) {
                    singular =
                        singular.slice(0, -2);
                } else if (
                    singular.endsWith('s') &&
                    !singular.endsWith('ss')
                ) {
                    singular =
                        singular.slice(0, -1);
                }
            }

            if (count === 1) {
                return singular;
            }

            if (/[^aeiou]y$/.test(singular)) {
                return (
                    singular.slice(0, -1) +
                    'ies'
                );
            }

            if (/(s|x|z|ch|sh)$/.test(singular)) {
                return singular + 'es';
            }

            return singular + 's';
        }

        function formatMl(value) {
            const number = Number(value);
            return Number.isFinite(number) ? number.toLocaleString('en-US', {
                maximumFractionDigits: 2
            }) : '0';
        }

        function syncBottleFields(prefix) {
            const bottle = isBottleUnit(document.getElementById(prefix + 'Unit').value);

            if (prefix === 'add') {
                const stockGrid =
                    document.getElementById(
                        'addStockFieldsGrid'
                    );

                if (stockGrid) {
                    stockGrid.classList.toggle(
                        'md:grid-cols-2',
                        bottle
                    );

                    stockGrid.classList.toggle(
                        'md:grid-cols-3',
                        !bottle
                    );
                }
            }

            const wrapper = document.getElementById(prefix + 'BottleFields');
            const capacity = document.getElementById(prefix + 'BottleCapacity');
            const used = document.getElementById(prefix + 'Used');
            document.getElementById(prefix + 'QtyLabel').textContent = bottle ? 'Bottle Quantity' : 'Quantity';
            wrapper.hidden = !bottle;
            capacity.disabled = !bottle;
            capacity.required = bottle;
            used.disabled = false;
            used.required = !bottle;
            used.closest('.inventory-field').style.display = '';
            document.getElementById(prefix + 'UsedLabel').textContent = bottle ? 'Consumed (mL)' : 'Consumed';
            used.min = bottle ? '0.01' : '0';
            used.max = bottle ? '99999999.99' : '99999';
            used.step = bottle ? '0.01' : '1';
            used.inputMode = bottle ? 'decimal' : 'numeric';
            used.placeholder = bottle ? 'e.g. 0.5' : '';
            if (bottle) {
                used.removeAttribute('data-validation-rule');
                if (used.dataset.volumeMode === '0' || used.value === '0') used.value = '';
            } else {
                used.setAttribute('data-validation-rule', 'inventoryConsumed');
                if (used.dataset.volumeMode === '1') used.value = '0';
            }
            used.dataset.volumeMode = bottle ? '1' : '0';
            if (prefix === 'edit') {
                document.getElementById('editBottleCapacityField').hidden = true;
                document.getElementById('editQty').min = bottle ? '0' : '1';
                const fixedCapacity = editBottleOriginal?.is_volume_bottle && Number(editBottleOriginal.qty) > 0;
                capacity.readOnly = true;
                document.getElementById('editUnit').readOnly = true;
                const quantity = Number(document.getElementById('editQty').value || 0);
                const cap = Math.round(Number(capacity.value || 0) * 100);
                const current = fixedCapacity ? Math.round(Number(editBottleOriginal.current_bottle_remaining_ml) * 100) :
                    cap;
                const available = quantity > 0 ? current + (quantity - 1) * cap : 0;
                const consumed = Math.round(Number(document.getElementById('editUsed').value || 0) * 100);
                const remaining = available - consumed;
                const bottles = cap > 0 && remaining > 0 ? Math.ceil(remaining / cap) : 0;
                const currentBottleRemaining = remaining >= 0 && cap > 0 ?
                    formatMl((bottles > 0 ? remaining - (bottles - 1) * cap : 0) / 100) :
                    'Unavailable';

                const remainingQuantity =
                    Math.max(
                        0,
                        quantity -
                        Number(used.value || 0)
                    );

                const balanceValue =
                    bottle ?
                    (
                        remaining < 0 ?
                        'Consumption exceeds available volume' :
                        `${bottles} ${getInventoryUnitLabel(
                            'bottle',
                            bottles
                        )} · ${formatMl(remaining / 100)} mL`
                    ) :
                    `${remainingQuantity} ${getInventoryUnitLabel(
                        document.getElementById('editUnit').value || 'item',
                        remainingQuantity
                    )}`;

                document.getElementById('editBottleRemaining').value = currentBottleRemaining;
                document.getElementById('editBalance').value = balanceValue;

                const unitSummary = document.getElementById('editUnitSummary');
                const capacitySummary = document.getElementById('editBottleCapacitySummary');
                const remainingSummary = document.getElementById('editBottleRemainingSummary');
                const balanceSummary = document.getElementById('editBalanceSummary');

                if (unitSummary) unitSummary.textContent = document.getElementById('editUnit').value || '—';
                if (capacitySummary) capacitySummary.textContent = bottle && capacity.value ?
                    `${formatMl(capacity.value)} mL` :
                    '—';
                if (remainingSummary) remainingSummary.textContent = bottle ?
                    `${currentBottleRemaining} mL left` :
                    '—';
                if (balanceSummary) balanceSummary.textContent = balanceValue;
            } else {
                const quantity = Number(document.getElementById('addQty').value || 0);
                const cap = Math.round(Number(capacity.value || 0) * 100);
                const remaining = quantity * cap - Math.round(Number(used.value || 0) * 100);
                const bottles = remaining > 0 && cap > 0 ? Math.ceil(remaining / cap) : 0;
                const balanceValue = bottle ?
                    (remaining < 0 ? 'Consumption exceeds available volume' :
                        `${capacity.value ? bottles : quantity} bottles | ${formatMl(remaining / 100)} mL total`) :
                    quantity - Number(used.value || 0);

                document.getElementById('addBalance').value = balanceValue;

                const balanceSummary = document.getElementById('addBalanceSummary');

                if (balanceSummary) {
                    if (bottle) {
                        balanceSummary.textContent =
                            balanceValue;
                    } else {
                        const unit =
                            document.getElementById(
                                'addUnit'
                            ).value || 'item';

                        const consumed =
                            Number(
                                used.value || 0
                            );

                        const available =
                            Math.max(
                                0,
                                quantity - consumed
                            );

                        balanceSummary.textContent =
                            `${available} ${getInventoryUnitLabel(
                unit,
                available
            )}`;
                    }
                }
            }
        }

        function computeAddBalance() {
            syncBottleFields('add');
        }

        function computeEditBalance() {
            syncBottleFields('edit');
        }

        document.addEventListener('DOMContentLoaded', () => {
            for (const prefix of ['add', 'edit']) {
                for (const suffix of ['Unit', 'BottleCapacity']) {
                    document
                        .getElementById(prefix + suffix)
                        ?.addEventListener(
                            'input',
                            () => syncBottleFields(prefix)
                        );
                }
            }

            [
                ['addQty', computeAddBalance],
                ['addUsed', computeAddBalance],
                ['editQty', computeEditBalance],
                ['editUsed', computeEditBalance]
            ].forEach(function([
                fieldId,
                callback
            ]) {
                document
                    .getElementById(fieldId)
                    ?.addEventListener(
                        'input',
                        callback
                    );
            });
        });
    </script>
@endsection
