@extends('layouts.admin')

@section('title', 'Inventory | PUP Taguig Dental Clinic')

@section('content')
<main id="mainContent" class="admin-page-shell page-enter inventory-page">
    <div class="admin-page-container">

        <div class="page-banner inventory-admin-banner">
            <div class="page-banner-inner">
                <div class="min-w-0">
                    <h1 class="page-banner-title">Admin Inventory</h1>
                </div>

                <div class="page-banner-actions">
                    <span class="page-badge">
                        <i class="fa-solid fa-shield-heart"></i>
                        Admin Control
                    </span>
                </div>
            </div>
        </div>

        <div class="relative z-10 mt-4 px-4 sm:px-6 lg:px-7 pb-8">

        <div class="stat-grid inventory-stat-grid" id="statCards">
            <div class="stat-card s-total">
                <div class="stat-card-info">
                    <div class="stat-label">Total Items</div>
                    <div class="stat-value" id="statTotal">—</div>
                    <div class="stat-footer">all categories</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            </div>
            <div class="stat-card s-medicine">
                <div class="stat-card-info">
                    <div class="stat-label">Medicines</div>
                    <div class="stat-value" id="statMedicine">—</div>
                    <div class="stat-footer">pharmaceutical</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-pills"></i></div>
            </div>
            <div class="stat-card s-supplies">
                <div class="stat-card-info">
                    <div class="stat-label">Supplies</div>
                    <div class="stat-value" id="statSupplies">—</div>
                    <div class="stat-footer">consumables</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-syringe"></i></div>
            </div>
            <div class="stat-card s-low">
                <div class="stat-card-info">
                    <div class="stat-label">Low Stock</div>
                    <div class="stat-value" id="statLow">—</div>
                    <div class="stat-footer">need restocking</div>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
        </div>
        
        <div class="inventory-table-card bg-white rounded-xl shadow-sm border border-[#EDE9E4] overflow-hidden">

            <div class="px-4 sm:px-5 py-4 border-b border-[#EDE9E4] flex flex-col gap-4">
                <div class="inventory-toolbar-shell flex flex-col lg:flex-row lg:items-center justify-between gap-4">

                    <div
                        class="inventory-category-row flex items-center justify-between sm:justify-start gap-3 w-full lg:w-auto">
                        <div class="tab-group w-full sm:w-auto flex" role="tablist" aria-label="Inventory category">
                            <button type="button" data-tab="all" aria-selected="true"
                                class="tab-btn active flex-1 sm:flex-none" onclick="setTab('all',this)">All</button>

                            <button type="button" data-tab="medicine" aria-selected="false"
                                class="tab-btn flex-1 sm:flex-none" onclick="setTab('medicine',this)">Medicine</button>

                            <button type="button" data-tab="supplies" aria-selected="false"
                                class="tab-btn flex-1 sm:flex-none" onclick="setTab('supplies',this)">Supplies</button>
                        </div>

                        <span class="row-count row-count-desktop js-row-count" aria-live="polite"></span>
                    </div>

                    <div class="toolbar-actions w-full lg:w-auto">

                        <div class="inventory-search-row voice-search-row">
                            <div class="search-wrap global-search flex-1" data-search-wrapper>
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>

                                <input type="text" id="searchInput" placeholder="Search Stock No., Name…"
                                    data-search-input class="search-input" oninput="renderTable()" />

                                <button type="button" class="search-clear" data-search-clear aria-label="Clear search">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>

                            <div class="voice-input-toggle">
                                <button type="button" id="invMicToggleBtn" class="voice-search-mic external"
                                    data-voice-trigger data-voice-target="#searchInput"
                                    data-voice-status="#invVoiceStatus" aria-label="Voice search inventory">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="invVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>

                        <div class="inventory-mobile-actions">

                            <button id="filterBtn" type="button" onclick="openFilterPanel()" class="global-filter-btn">
                                <i class="fa-solid fa-sliders"></i>
                                <span>Filter</span>
                                <span id="filterBadge" class="filter-badge"></span>
                            </button>

                            <div class="view-toggle-container inventory-view-toggle" id="viewToggle">
                                <div class="view-slider"></div>

                                <button type="button" class="btn-view-mode active" data-view="list"
                                    onclick="setViewMode('list', this)" title="List View">
                                    <i class="fa-solid fa-list"></i>
                                </button>

                                <button type="button" class="btn-view-mode" data-view="grid"
                                    onclick="setViewMode('grid', this)" title="Grid View">
                                    <i class="fa-solid fa-grip"></i>
                                </button>
                            </div>

                            <button id="externalClearFilterBtn" type="button" onclick="clearFilterPanel()"
                                class="global-filter-reset-btn hidden" title="Reset filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>

                            <button type="button" onclick="openAddModal()"
                                class="btn-add inventory-add-btn justify-center">
                                <span class="add-icon"><i class="fa-solid fa-plus"></i></span>
                                <span>Add Item</span>
                            </button>

                            <span class="row-count row-count-mobile js-row-count" aria-live="polite"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tableWrapper" class="overflow-x-auto">
                <table class="d-inv-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Stock No.</th>
                            <th>Supply / Medicine</th>
                            <th>Unit</th>
                            <th>Qty</th>
                            <th>Used</th>
                            <th>Balance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>

            <div id="inventoryGrid" class="inventory-grid"></div>
            <div id="emptyState" class="empty-state-host"></div>

            <div class="table-footer-bar">
                <span class="text-xs text-gray-400" id="pageInfo"></span>
                <div></div>
            </div>
        </div>
    </div>
</main>

<div id="filterModal" class="filter-drawer-wrapper">
    <div class="filter-drawer-overlay" onclick="closeFilterDrawer('filterModal')"></div>
    <div class="filter-drawer-panel">
        <div class="filter-drawer-header px-6 py-5 flex items-center justify-between border-b border-gray-100">
            <div class="filter-drawer-title flex items-center gap-2">
                <i class="fa-solid fa-sliders text-xl"></i>
                <h2 class="text-xl font-extrabold">Filters</h2>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700 transition-colors"
                onclick="closeFilterDrawer('filterModal')" aria-label="Close filters">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="filter-drawer-body px-6 py-5 flex flex-col gap-6">
            <div id="activeFiltersSection" class="hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[13px] font-bold text-gray-800">Active Filters</span>
                    <button id="clearAllChipsBtn" type="button"
                        class="text-xs font-bold text-[#8B0000] hover:underline">Clear All</button>
                </div>
                <div id="activeChipsContainer"
                    class="active-filters-container flex flex-wrap gap-2 pb-4 border-b border-gray-100"></div>
            </div>

            <div>
                <h3 class="filter-section-title">Sort By</h3>
                <div class="filter-chip-row" id="inventorySortGroup">
                    <button type="button" class="ftag" data-group="sort" data-val="newest">Newest First</button>
                    <button type="button" class="ftag" data-group="sort" data-val="oldest">Oldest First</button>
                    <button type="button" class="ftag" data-group="sort" data-val="az">Name A-Z</button>
                    <button type="button" class="ftag" data-group="sort" data-val="za">Name Z-A</button>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Filter by Date Range</h3>
                <div class="filter-chip-row" id="inventoryDatePresetGroup">
                    <button type="button" class="quick-date-chip" data-range="7">Last 7 Days</button>
                    <button type="button" class="quick-date-chip" data-range="30">Last 30 Days</button>
                    <button type="button" class="quick-date-chip" data-range="90">Last 3 Months</button>
                    <button type="button" class="quick-date-chip" data-range="180">Last 6 Months</button>
                    <button type="button" class="quick-date-chip" data-range="365">Last 12 Months</button>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Custom Date Range</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="filter-date-input-wrap">
                        <input id="fp_dateFrom" type="text" class="js-flatpickr-date-range-from"
                            placeholder="Start date" readonly autocomplete="off">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <div class="filter-date-input-wrap">
                        <input id="fp_dateTo" type="text" class="js-flatpickr-date-range-to" placeholder="End date"
                            readonly autocomplete="off">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title">Stock Level</h3>
                <div class="filter-chip-row" id="inventoryStockGroup">
                    <button type="button" class="ftag" data-group="stock" data-val="in-stock">In Stock</button>
                    <button type="button" class="ftag" data-group="stock" data-val="low-stock">Low Stock</button>
                    <button type="button" class="ftag" data-group="stock" data-val="out-stock">Out of Stock</button>
                    <button type="button" class="ftag" data-group="stock" data-val="low-high">Lowest Stock</button>
                    <button type="button" class="ftag" data-group="stock" data-val="high-low">Highest Stock</button>
                </div>
            </div>
        </div>

        <div
            class="filter-drawer-footer px-6 py-5 flex flex-col sm:flex-row items-center justify-between border-t border-gray-100 gap-4">
            <button id="clearFilterPanelBtn" type="button" onclick="clearFilterPanelModal()"
                class="filter-clear-btn flex items-center gap-2 transition-colors w-full sm:w-auto justify-center sm:justify-start">
                <i class="fa-regular fa-trash-can text-lg"></i>
                <span class="text-[13px] font-bold leading-none whitespace-nowrap">Clear Filters</span>
            </button>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button type="button" onclick="closeFilterDrawer('filterModal')"
                    class="filter-cancel-btn flex-1 sm:flex-none px-5 py-2.5 text-sm font-bold rounded-lg transition-colors">
                    Cancel
                </button>
                <button id="saveFilterPanelBtn" type="button" onclick="saveFilterPanel()"
                    class="filter-show-results-btn filter-apply-btn flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold rounded-lg transition-colors shadow-sm">
                    <i class="fa-solid fa-check"></i>
                    <span id="showResultsText">Show 0 results</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="addModal" class="ui-modal inventory-form-modal" onclick="closeModalOnBackdrop(event, 'addModal')">
    <div class="ui-modal-card modal-box-custom">
        <form id="addInventoryForm" class="modal-box-split" data-discard-form
            data-discard-title="Discard new inventory item?" data-discard-subtitle="You have unsaved item details."
            data-discard-message="Closing this modal will remove the inventory draft you entered. Do you want to discard your changes?"
            onsubmit="return false;">
            <div class="modal-header-custom modal-sticky-header">
                <div class="inventory-modal-head-left">
                    <div class="modal-icon-custom"><i class="fa-solid fa-plus"></i></div>
                    <div>
                        <div class="modal-title-custom">Add Inventory Item</div>
                        <div class="modal-sub-custom">A new row will be appended every time you save</div>
                    </div>
                </div>

                <button type="button" class="inventory-modal-x" data-discard-close="addModal"
                    aria-label="Close add inventory modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-scroll-body">
                <div class="form-grid-2">
                    <div class="form-group-custom">
                        <div class="form-label-custom">Category <span style="color:#C0392B">*</span></div>
                        <select id="addCategory" class="inv-native-hidden-select"
                            onchange="validateAddField('addCategory')">
                            <option selected value="">Select...</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Supplies">Supplies</option>
                        </select>

                        <div class="inv-custom-select" data-inv-select data-target="#addCategory">
                            <button type="button" class="inv-custom-select-btn">
                                <span data-inv-select-label>Select...</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>

                            <div class="inv-custom-select-menu">
                                <button type="button" class="inv-custom-select-option active" data-value="">
                                    <span>Select...</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>

                                <button type="button" class="inv-custom-select-option" data-value="Medicine">
                                    <span>Medicine</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>

                                <button type="button" class="inv-custom-select-option" data-value="Supplies">
                                    <span>Supplies</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </div>
                        </div>
                        <div class="field-error" id="err-addCategory"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Date Received <span style="color:#C0392B">*</span></div>
                        <div class="fp-date-input-wrap">
                            <input id="addDate" type="text" class="form-input-custom js-flatpickr-date"
                                placeholder="Select date" onchange="validateAddField('addDate')" readonly>
                            <i class="fa-regular fa-calendar fp-date-icon"></i>
                        </div>
                        <div class="field-error" id="err-addDate"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Stock Number <span style="color:#C0392B">*</span></div>
                        <div class="st-voice-row" data-voice-field>
                            <input id="addStock" class="form-input-custom" placeholder="00-000" maxlength="6"
                                oninput="formatStockNo(this); validateAddField('addStock')"
                                style="letter-spacing:0.15em">
                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#addStock" data-voice-status="#addStockVoiceStatus"
                                    aria-label="Voice input for stock number">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addStockVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>
                        <div class="field-error" id="err-addStock"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Unit <span style="color:#C0392B">*</span></div>
                        <div class="st-voice-row inv-combo" data-voice-field data-inv-combo data-target="#addUnit">
                            <div class="voice-input-wrap inv-combo-input-wrap">
                                <input id="addUnit" class="form-input-custom" placeholder="Type or select unit"
                                    maxlength="50" autocomplete="off" oninput="validateAddField('addUnit')">

                                <button type="button" class="inv-combo-toggle" aria-label="Show unit options">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>

                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#addUnit" data-voice-status="#addUnitVoiceStatus"
                                    aria-label="Voice input for unit">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addUnitVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>

                            <div class="inv-combo-menu">
                                <button type="button" class="inv-combo-option" data-value="Box">Box</button>
                                <button type="button" class="inv-combo-option" data-value="Pack">Pack</button>
                                <button type="button" class="inv-combo-option" data-value="Bottle">Bottle</button>
                                <button type="button" class="inv-combo-option" data-value="Piece">Piece</button>
                                <button type="button" class="inv-combo-option" data-value="Set">Set</button>
                                <button type="button" class="inv-combo-option" data-value="Tube">Tube</button>
                                <button type="button" class="inv-combo-option" data-value="Vial">Vial</button>
                                <button type="button" class="inv-combo-option" data-value="Roll">Roll</button>
                            </div>
                        </div>
                        <div class="field-error" id="err-addUnit"></div>
                    </div>

                    <div class="form-group-custom full">
                        <div class="flex justify-between items-center">
                            <div class="form-label-custom">Supply / Medicine Name <span style="color:#C0392B">*</span>
                            </div>
                            <div class="char-counter" id="charCounter-addName">0 / 100</div>
                        </div>
                        <div class="st-voice-row" data-voice-field>
                            <input id="addName" class="form-input-custom" placeholder="e.g. Nitrile Gloves Large"
                                maxlength="100" oninput="updateCharCounter('addName',100); validateAddField('addName')">
                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#addName" data-voice-status="#addNameVoiceStatus"
                                    aria-label="Voice input for item name">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addNameVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>
                        <div class="field-error" id="err-addName"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Quantity <span style="color:#C0392B">*</span></div>
                        <div class="st-voice-row" data-voice-field>
                            <input id="addQty" type="number" class="form-input-custom" placeholder="0" min="0"
                                max="99999"
                                oninput="computeAddBalance(); validateAddField('addQty'); validateAddField('addUsed')">
                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#addQty" data-voice-status="#addQtyVoiceStatus"
                                    aria-label="Voice input for quantity">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addQtyVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>
                        <div class="field-error" id="err-addQty"></div>
                    </div>

                    <div class="form-group-custom">
                        <div class="form-label-custom">Consumed</div>
                        <div class="st-voice-row" data-voice-field>
                            <input id="addUsed" type="number" class="form-input-custom" placeholder="0" min="0"
                                max="99999" oninput="computeAddBalance(); validateAddField('addUsed')">
                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#addUsed" data-voice-status="#addUsedVoiceStatus"
                                    aria-label="Voice input for consumed quantity">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="addUsedVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>
                        <div class="field-error" id="err-addUsed"></div>
                    </div>

                    <div class="form-group-custom full">
                        <div class="form-label-custom">Balance (auto-calculated)</div>
                        <input id="addBalance" class="form-input-custom" readonly placeholder="—">
                    </div>
                </div>
            </div>

            <div class="modal-footer-custom modal-sticky-footer">
                <button type="button" class="ui-btn ui-btn-secondary btn-modal-cancel"
                    data-discard-close="addModal">Cancel</button>
                <button type="button" id="btnSaveAdd" class="ui-btn ui-btn-primary btn-modal-save" onclick="addItem()">
                    <i class="fa-solid fa-floppy-disk"></i> Save Item
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="ui-modal inventory-form-modal" onclick="closeModalOnBackdrop(event, 'editModal')">
    <div class="ui-modal-card modal-box-custom">
        <form id="editInventoryForm" class="modal-box-split" data-discard-form
            data-discard-title="Discard inventory changes?"
            data-discard-subtitle="You have unsaved edits for this item."
            data-discard-message="Closing this modal will remove the edits you entered. Do you want to discard your changes?"
            onsubmit="return false;">
            <div class="modal-header-custom modal-sticky-header">
                <div class="inventory-modal-head-left">
                    <div class="modal-icon-custom modal-icon-edit">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <div>
                        <div class="modal-title-custom">Edit Inventory Item</div>
                        <div class="modal-sub-custom">Update the details for this item</div>
                    </div>
                </div>

                <button type="button" class="inventory-modal-x" data-discard-close="editModal"
                    aria-label="Close edit inventory modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-scroll-body">
                <div class="form-grid-2">
                    <div class="form-group-custom">
                        <div class="form-label-custom">Category</div>
                        <select id="editCategory" class="inv-native-hidden-select">
                            <option value="Medicine">Medicine</option>
                            <option value="Supplies">Supplies</option>
                        </select>

                        <div class="inv-custom-select" data-inv-select data-target="#editCategory">
                            <button type="button" class="inv-custom-select-btn">
                                <span data-inv-select-label>Medicine</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>

                            <div class="inv-custom-select-menu">
                                <button type="button" class="inv-custom-select-option active" data-value="Medicine">
                                    <span>Medicine</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>

                                <button type="button" class="inv-custom-select-option" data-value="Supplies">
                                    <span>Supplies</span>
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-custom">
                        <div class="form-label-custom">Date Received</div>
                        <div class="fp-date-input-wrap">
                            <input id="editDate" type="text" class="form-input-custom js-flatpickr-date"
                                placeholder="Select date" readonly>
                            <i class="fa-regular fa-calendar fp-date-icon"></i>
                        </div>
                    </div>
                    <div class="form-group-custom">
                        <div class="form-label-custom">Stock Number</div>
                        <div class="st-voice-row" data-voice-field>
                            <input id="editStock" class="form-input-custom" maxlength="6" oninput="formatStockNo(this)">
                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#editStock" data-voice-status="#editStockVoiceStatus"
                                    aria-label="Voice input for stock number">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editStockVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-custom">
                        <div class="form-label-custom">Unit</div>
                        <div class="st-voice-row inv-combo" data-voice-field data-inv-combo data-target="#editUnit">
                            <div class="voice-input-wrap inv-combo-input-wrap">
                                <input id="editUnit" class="form-input-custom" placeholder="Type or select unit"
                                    maxlength="50" autocomplete="off">

                                <button type="button" class="inv-combo-toggle" aria-label="Show unit options">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>

                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#editUnit" data-voice-status="#editUnitVoiceStatus"
                                    aria-label="Voice input for unit">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editUnitVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>

                            <div class="inv-combo-menu">
                                <button type="button" class="inv-combo-option" data-value="Box">Box</button>
                                <button type="button" class="inv-combo-option" data-value="Pack">Pack</button>
                                <button type="button" class="inv-combo-option" data-value="Bottle">Bottle</button>
                                <button type="button" class="inv-combo-option" data-value="Piece">Piece</button>
                                <button type="button" class="inv-combo-option" data-value="Set">Set</button>
                                <button type="button" class="inv-combo-option" data-value="Tube">Tube</button>
                                <button type="button" class="inv-combo-option" data-value="Vial">Vial</button>
                                <button type="button" class="inv-combo-option" data-value="Roll">Roll</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-custom full">
                        <div class="form-label-custom">Supply / Medicine Name</div>
                        <div class="st-voice-row" data-voice-field>
                            <input id="editName" class="form-input-custom">
                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#editName" data-voice-status="#editNameVoiceStatus"
                                    aria-label="Voice input for item name">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editNameVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-custom">
                        <div class="form-label-custom">Quantity</div>
                        <div class="st-voice-row" data-voice-field>
                            <input id="editQty" type="number" class="form-input-custom" oninput="computeEditBalance()">
                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#editQty" data-voice-status="#editQtyVoiceStatus"
                                    aria-label="Voice input for quantity">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editQtyVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-custom">
                        <div class="form-label-custom">Consumed</div>
                        <div class="st-voice-row" data-voice-field>
                            <input id="editUsed" type="number" class="form-input-custom" oninput="computeEditBalance()">
                            <div class="voice-input-toggle">
                                <button type="button" class="voice-search-mic external" data-voice-trigger
                                    data-voice-target="#editUsed" data-voice-status="#editUsedVoiceStatus"
                                    aria-label="Voice input for consumed quantity">
                                    <i class="fa-solid fa-microphone"></i>
                                </button>
                                <span id="editUsedVoiceStatus" class="voice-status hidden" data-voice-status
                                    aria-live="polite"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group-custom full">
                        <div class="form-label-custom">Balance (auto-calculated)</div>
                        <input id="editBalance" class="form-input-custom" readonly>
                    </div>
                </div>
            </div>

            <div class="modal-footer-custom modal-sticky-footer">
                <button type="button" class="ui-btn ui-btn-secondary btn-modal-cancel"
                    data-discard-close="editModal">Cancel</button>
                <button type="button" class="ui-btn ui-btn-primary btn-modal-save" onclick="saveEdit()">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="ui-modal modal-overlay inv-delete-modal" aria-hidden="true">
    <div class="modal-box-inner inv-delete-modal-card" onclick="event.stopPropagation()" role="dialog" aria-modal="true"
        aria-labelledby="inventoryDeleteTitle">

        <div class="inv-delete-head">
            <div class="inv-delete-head-left">
                <div class="inv-delete-icon">
                    <i class="fa-solid fa-trash"></i>
                </div>

                <div>
                    <h3 id="inventoryDeleteTitle">Delete Inventory Item</h3>
                    <p>This action requires confirmation</p>
                </div>
            </div>

            <button type="button" class="inv-delete-x" onclick="forceCloseModal('deleteModal')"
                aria-label="Close delete modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="inv-delete-body">
            <div class="inv-delete-alert">
                <i class="fa-solid fa-triangle-exclamation"></i>

                <div>
                    <p>
                        Are you sure you want to delete
                        <strong id="inventoryDeleteName"></strong>?
                    </p>
                    <span>This inventory item will be permanently removed.</span>
                </div>
            </div>

            <div class="inv-delete-actions">
                <button type="button" class="modal-btn-ghost" onclick="forceCloseModal('deleteModal')">
                    Cancel
                </button>

                <button type="button" id="confirmDeleteBtn" class="inv-delete-confirm">
                    <i class="fa-solid fa-trash"></i>
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    var currentViewMode = window.innerWidth <= 767 ? 'grid' : (localStorage.getItem('adminInventoryView') || 'list');

    function setViewMode(mode, btn) {
        if (window.innerWidth <= 767) {
            currentViewMode = 'grid';
        } else {
            currentViewMode = mode;
        }

        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.classList.toggle('mode-list', currentViewMode === 'list');
            mainContent.classList.toggle('mode-grid', currentViewMode === 'grid');
        }

        document.querySelectorAll('.btn-view-mode').forEach(function (b) {
            b.classList.remove('active');
        });

        var activeBtn = document.querySelector('.btn-view-mode[data-view="' + currentViewMode + '"]');
        if (activeBtn) activeBtn.classList.add('active');

        if (window.innerWidth > 767) localStorage.setItem('adminInventoryView', currentViewMode);
        renderTable();
    }

    var inventory = [];
    var activeTab = 'all';

    const inventoryUrlTemplates = {
        update: @json(url('/admin/inventory/__ID__')),
        destroy: @json(url('/admin/inventory/__ID__')),
    };

    function inventoryUrl(type, id) {
        return inventoryUrlTemplates[type].replace('__ID__', encodeURIComponent(id));
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.innerWidth <= 767) {
            currentViewMode = 'grid';
        }

        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.classList.toggle('mode-list', currentViewMode === 'list');
            mainContent.classList.toggle('mode-grid', currentViewMode === 'grid');
        }

        document.querySelectorAll('.btn-view-mode').forEach(function (b) {
            b.classList.remove('active');
        });

        var defaultBtn = document.querySelector('.btn-view-mode[data-view="' + currentViewMode + '"]');
        if (defaultBtn) defaultBtn.classList.add('active');

        applyDashboardStockFilterFromQuery();
        syncInventoryFilterGroups();
        updateFilterButtonState();
        initInventoryChoiceControls();
        if (window.initGlobalVoiceInputs) window.initGlobalVoiceInputs(document);
    });

    async function loadInventory() {
        var res = await fetch('{{ route('admin.inventory.data') }}', {
            cache: 'no-store'
        });
        var ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            console.error('Inventory data is not JSON.');
            return;
        }
        inventory = await res.json();
        renderTable();
    }
    loadInventory();

    window.addEventListener('resize', function () {
        if (window.innerWidth <= 767) {
            currentViewMode = 'grid';
        }

        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.classList.toggle('mode-list', currentViewMode === 'list');
            mainContent.classList.toggle('mode-grid', currentViewMode === 'grid');
        }

        document.querySelectorAll('.btn-view-mode').forEach(function (b) {
            b.classList.remove('active');
        });

        var activeBtn = document.querySelector('.btn-view-mode[data-view="' + currentViewMode + '"]');
        if (activeBtn) activeBtn.classList.add('active');

        renderTable();
    });

    function updateStats() {
        document.getElementById('statTotal').textContent = inventory.length;
        document.getElementById('statMedicine').textContent = inventory.filter(function (i) {
            return i.category === 'Medicine';
        }).length;
        document.getElementById('statSupplies').textContent = inventory.filter(function (i) {
            return i.category === 'Supplies';
        }).length;
        document.getElementById('statLow').textContent = inventory.filter(function (i) {
            return (Number(i.qty) - Number(i.used)) <= 5;
        }).length;
    }

    function setTab(tab, btn) {
        activeTab = tab;

        document.querySelectorAll('.tab-group .tab-btn').forEach(function (button) {
            const isActive = button.getAttribute('data-tab') === tab || button === btn;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        document.querySelectorAll('#statCards .stat-card').forEach(function (card) {
            card.classList.remove('active', 'stat-active');
            card.setAttribute('aria-pressed', 'false');
        });

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
            return list.filter(function (item) { return itemBalance(item) > 5; });
        }

        if (mode === 'low-stock') {
            return list.filter(function (item) {
                var balance = itemBalance(item);
                return balance >= 1 && balance <= 5;
            });
        }

        if (mode === 'out-stock') {
            return list.filter(function (item) { return itemBalance(item) <= 0; });
        }

        if (mode === 'low-high') {
            return list.sort(function (a, b) { return itemBalance(a) - itemBalance(b); });
        }

        if (mode === 'high-low') {
            return list.sort(function (a, b) { return itemBalance(b) - itemBalance(a); });
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

    function captureDiscardForModal(id) {
        window.setTimeout(function () {
            var modal = document.getElementById(id);
            if (window.DiscardChanges && modal) {
                window.DiscardChanges.captureModal(modal);
            }
        }, 0);
    }

    var baseInventoryCloseModal = window.closeModal ? window.closeModal.bind(window) : null;

    function forceCloseModal(id) {
        if (baseInventoryCloseModal) {
            baseInventoryCloseModal(id);
            return;
        }

        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('open', 'closing');
        document.body.classList.remove('modal-lock');
    }

    window.forceCloseModal = forceCloseModal;

    function requestCloseInventoryModal(id) {
        var modal = document.getElementById(id);

        if (window.DiscardChanges && modal) {
            window.DiscardChanges.confirmClose(modal, function () {
                forceCloseModal(id);
            });
            return;
        }

        forceCloseModal(id);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        var openInventoryModal = document.querySelector('#addModal.open, #editModal.open');
        if (!openInventoryModal) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        requestCloseInventoryModal(openInventoryModal.id);
    }, true);

    function closeInventoryChoiceMenus(except) {
        document.querySelectorAll('.inv-custom-select.is-open, .inv-combo.is-open').forEach(function (wrapper) {
            if (wrapper !== except) {
                wrapper.classList.remove('is-open');
            }
        });
    }

    function setInventoryCustomSelectValue(selectId, value, shouldValidate = true) {
        const select = document.getElementById(selectId);
        const wrapper = document.querySelector('[data-inv-select][data-target="#' + selectId + '"]');

        if (!select || !wrapper) return;

        select.value = value || '';

        const options = wrapper.querySelectorAll('.inv-custom-select-option');
        const label = wrapper.querySelector('[data-inv-select-label]');

        let selectedText = 'Select...';

        options.forEach(function (option) {
            const isActive = String(option.dataset.value || '') === String(select.value || '');
            option.classList.toggle('active', isActive);

            if (isActive) {
                selectedText = option.querySelector('span')?.textContent.trim() || selectedText;
            }
        });

        if (label) label.textContent = selectedText;

        if (shouldValidate) {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function initInventoryChoiceControls() {
        document.querySelectorAll('[data-inv-select]').forEach(function (wrapper) {
            const targetSelector = wrapper.dataset.target;
            const target = document.querySelector(targetSelector);
            const trigger = wrapper.querySelector('.inv-custom-select-btn');

            if (!target || !trigger || wrapper.dataset.bound === 'true') return;
            wrapper.dataset.bound = 'true';

            trigger.addEventListener('click', function () {
                const willOpen = !wrapper.classList.contains('is-open');
                closeInventoryChoiceMenus(wrapper);
                wrapper.classList.toggle('is-open', willOpen);
            });

            wrapper.querySelectorAll('.inv-custom-select-option').forEach(function (option) {
                option.addEventListener('click', function () {
                    setInventoryCustomSelectValue(target.id, option.dataset.value || '');
                    closeInventoryChoiceMenus();
                });
            });
        });

        document.querySelectorAll('[data-inv-combo]').forEach(function (wrapper) {
            const target = document.querySelector(wrapper.dataset.target);
            const toggle = wrapper.querySelector('.inv-combo-toggle');

            if (!target || !toggle || wrapper.dataset.bound === 'true') return;
            wrapper.dataset.bound = 'true';

            toggle.addEventListener('click', function () {
                const willOpen = !wrapper.classList.contains('is-open');
                closeInventoryChoiceMenus(wrapper);
                wrapper.classList.toggle('is-open', willOpen);
            });

            target.addEventListener('focus', function () {
                closeInventoryChoiceMenus(wrapper);
                wrapper.classList.add('is-open');
            });

            target.addEventListener('input', function () {
                const query = target.value.trim().toLowerCase();

                wrapper.querySelectorAll('.inv-combo-option').forEach(function (option) {
                    const match = option.dataset.value.toLowerCase().includes(query);
                    option.hidden = query && !match;
                });
            });

            wrapper.querySelectorAll('.inv-combo-option').forEach(function (option) {
                option.addEventListener('click', function () {
                    target.value = option.dataset.value || '';
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                    closeInventoryChoiceMenus();
                });
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.inv-custom-select') && !event.target.closest('.inv-combo')) {
                closeInventoryChoiceMenus();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeInventoryChoiceMenus();
            }
        });
    }

    function openAddModal() {
        resetAddForm();
        openModal('addModal');
        if (window.initGlobalVoiceInputs) window.initGlobalVoiceInputs(document.getElementById('addModal'));
        document.dispatchEvent(new CustomEvent('voice:refresh', { detail: { root: document.getElementById('addModal') } }));
        captureDiscardForModal('addModal');
    }

    function openFilterPanel() {
        filterDraft = Object.assign({}, activeFilters);

        const dateFrom = document.getElementById('fp_dateFrom');
        const dateTo = document.getElementById('fp_dateTo');

        if (dateFrom) dateFrom.value = filterDraft.dateFrom || '';
        if (dateTo) dateTo.value = filterDraft.dateTo || '';

        inventoryActiveDatePreset = '';

        document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function (btn) {
            var range = btn.getAttribute('data-range');
            var isActive = false;

            if (filterDraft.dateFrom && filterDraft.dateTo) {
                var today = new Date();
                var from = new Date();
                from.setDate(today.getDate() - Number(range));

                var expectedFrom = window.formatDateForInput
                    ? window.formatDateForInput(from)
                    : from.toISOString().slice(0, 10);

                var expectedTo = window.formatDateForInput
                    ? window.formatDateForInput(today)
                    : today.toISOString().slice(0, 10);

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

        group.querySelectorAll('.ftag').forEach(function (btn) {
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

        document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function (btn) {
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
        renderTable();
    }

    function countInventoryDraftResults() {
        let data = inventory.slice();

        const q = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();

        if (q) {
            data = data.filter(function (item) {
                return String(item.stock_no || '').toLowerCase().includes(q) ||
                    String(item.name || '').toLowerCase().includes(q) ||
                    String(item.category || '').toLowerCase().includes(q);
            });
        }

        if (activeTab !== 'all') {
            data = data.filter(function (item) {
                return String(item.category || '').toLowerCase() === activeTab;
            });
        }

        if (filterDraft.dateFrom || filterDraft.dateTo) {
            data = data.filter(function (item) {
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

            chip.querySelector(".filter-chip-remove").onclick = function () {
                callback();
                syncInventoryFilterGroups();
                renderFilterChips();
                updateShowResultsButton();
            };

            container.appendChild(chip);
        }

        if (filterDraft.sort === 'az') {
            addChip('Name: A → Z', function () {
                filterDraft.sort = '';
            });
        }

        if (filterDraft.sort === 'za') {
            addChip('Name: Z → A', function () {
                filterDraft.sort = '';
            });
        }

        if (filterDraft.sort === 'newest') {
            addChip('Sort: Newest First', function () {
                filterDraft.sort = '';
            });
        }

        if (filterDraft.sort === 'oldest') {
            addChip('Sort: Oldest First', function () {
                filterDraft.sort = '';
            });
        }

        if (filterDraft.stock === 'in-stock') {
            addChip('Stock: In Stock', function () {
                filterDraft.stock = '';
            });
        }

        if (filterDraft.stock === 'low-stock') {
            addChip('Stock: Low Stock', function () {
                filterDraft.stock = '';
            });
        }

        if (filterDraft.stock === 'out-stock') {
            addChip('Stock: Out of Stock', function () {
                filterDraft.stock = '';
            });
        }

        if (filterDraft.stock === 'low-high') {
            addChip('Stock: Lowest → Highest', function () {
                filterDraft.stock = '';
            });
        }

        if (filterDraft.stock === 'high-low') {
            addChip('Stock: Highest → Lowest', function () {
                filterDraft.stock = '';
            });
        }

        var dateFrom = document.getElementById('fp_dateFrom')?.value || filterDraft.dateFrom || '';
        var dateTo = document.getElementById('fp_dateTo')?.value || filterDraft.dateTo || '';

        filterDraft.dateFrom = dateFrom;
        filterDraft.dateTo = dateTo;

        if (dateFrom || dateTo) {
            var activePresetBtn = document.querySelector('#inventoryDatePresetGroup .quick-date-chip.active');

            var label = activePresetBtn
                ? activePresetBtn.textContent.trim()
                : dateFrom && dateTo
                    ? dateFrom + ' to ' + dateTo
                    : dateFrom
                        ? 'From ' + dateFrom
                        : 'Until ' + dateTo;

            addChip('Date: ' + label, function () {
                filterDraft.dateFrom = '';
                filterDraft.dateTo = '';
                inventoryActiveDatePreset = '';

                const fromInput = document.getElementById('fp_dateFrom');
                const toInput = document.getElementById('fp_dateTo');

                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';

                document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function (btn) {
                    btn.classList.remove('active');
                });
            });
        }

        if (hasChips) {
            section.classList.remove("hidden");

            var clearAllBtn = document.getElementById("clearAllChipsBtn");
            if (clearAllBtn) {
                clearAllBtn.onclick = function () {
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

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#inventorySortGroup .ftag, #inventoryStockGroup .ftag')
            .forEach(function (btn) {
                btn.addEventListener('click', function () {
                    setInventoryDraftValue(btn.getAttribute('data-group'), btn.getAttribute('data-val'));
                });
            });

        if (window.bindQuickDatePresets) {
            window.bindQuickDatePresets({
                groupId: 'inventoryDatePresetGroup',
                fromId: 'fp_dateFrom',
                toId: 'fp_dateTo',
                onChange: function () {
                    var activePresetBtn = document.querySelector('#inventoryDatePresetGroup .quick-date-chip.active');

                    inventoryActiveDatePreset = activePresetBtn
                        ? activePresetBtn.getAttribute('data-range')
                        : '';

                    filterDraft.dateFrom = document.getElementById('fp_dateFrom')?.value || '';
                    filterDraft.dateTo = document.getElementById('fp_dateTo')?.value || '';

                    renderFilterChips();
                    updateShowResultsButton();
                }
            });
        } else {
            document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var range = Number(btn.getAttribute('data-range'));
                    var today = new Date();
                    var from = new Date();

                    from.setDate(today.getDate() - range);

                    var fromVal = window.formatDateForInput
                        ? window.formatDateForInput(from)
                        : from.toISOString().slice(0, 10);

                    var toVal = window.formatDateForInput
                        ? window.formatDateForInput(today)
                        : today.toISOString().slice(0, 10);

                    var alreadyActive = btn.classList.contains('active');

                    document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function (b) {
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

        ['fp_dateFrom', 'fp_dateTo'].forEach(function (id) {
            var input = document.getElementById(id);
            if (!input) return;

            input.addEventListener('change', function () {
                inventoryActiveDatePreset = '';

                document.querySelectorAll('#inventoryDatePresetGroup .quick-date-chip').forEach(function (btn) {
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
            class="action-btn btn-edit"
            title="Edit"
            aria-label="Edit item"
            onclick="openEdit(${id})">
            <i class="fa-solid fa-pen"></i>
        </button>

        <button type="button"
            class="action-btn btn-delete"
            title="Delete"
            aria-label="Delete item"
            onclick="deleteItem(${id})">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    }

    function renderTable() {
        var tbody = document.getElementById('tableBody');
        var grid = document.getElementById('inventoryGrid');
        var tableWrapper = document.getElementById('tableWrapper');
        var emptyState = document.getElementById('emptyState');

        tbody.innerHTML = '';
        if (grid) grid.innerHTML = '';

        var data = inventory.slice();

        if (activeTab === 'medicine') {
            data = data.filter(function (i) {
                return i.category === 'Medicine';
            });
        }
        if (activeTab === 'supplies') {
            data = data.filter(function (i) {
                return i.category === 'Supplies';
            });
        }

        var q = (document.getElementById('searchInput').value || '').toLowerCase();
        if (q) {
            data = data.filter(function (i) {
                return String(i.stock_no || '').toLowerCase().includes(q) ||
                    String(i.name || '').toLowerCase().includes(q);
            });
        }

        var from = activeFilters.dateFrom ? new Date(activeFilters.dateFrom) : null;
        var to = activeFilters.dateTo ? new Date(activeFilters.dateTo) : null;

        if (from) {
            from.setHours(0, 0, 0, 0);
            data = data.filter(function (i) {
                return i.date_received && new Date(i.date_received) >= from;
            });
        }

        if (to) {
            to.setHours(23, 59, 59, 999);
            data = data.filter(function (i) {
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
            data.sort(function (a, b) {
                return String(a.name || '').localeCompare(String(b.name || ''));
            });
        } else if (activeFilters.sort === 'za') {
            data.sort(function (a, b) {
                return String(b.name || '').localeCompare(String(a.name || ''));
            });
        } else if (activeFilters.sort === 'oldest') {
            data.sort(function (a, b) {
                return dt(a.date_received) - dt(b.date_received);
            });
        } else if (activeFilters.sort === 'newest') {
            data.sort(function (a, b) {
                return dt(b.date_received) - dt(a.date_received);
            });
        }

        updateStats();
        const totalInventoryItems = inventory.length;
        const countText = totalInventoryItems + ' item' + (totalInventoryItems !== 1 ? 's' : '');

        document.querySelectorAll('.js-row-count').forEach(function (el) {
            el.textContent = countText;
        });
        document.getElementById('pageInfo').textContent = 'Showing ' + data.length + ' of ' + inventory.length +
            ' items';

        if (!data.length) {
            if (tableWrapper) tableWrapper.style.display = 'none';
            if (grid) grid.style.display = 'none';

            var isSearching = q.length > 0;
            var hasFilters = Object.values(activeFilters).some(Boolean);
            var icon, title, sub, extraHtml = '';

            if (isSearching) {
                icon = 'fa-magnifying-glass';
                title = 'No results for "' + q + '"';
                sub = 'Try a different stock number or supply name.';
                extraHtml =
                    '<button type="button" data-clear-search data-search-target="#searchInput" class="empty-state-btn"><i class="fa-solid fa-xmark"></i> Clear search</button>';
            } else if (hasFilters) {
                icon = 'fa-sliders';
                title = 'No matches for your filters';
                sub = 'Try removing or adjusting your filter criteria.';
                extraHtml =
                    '<button type="button" onclick="clearFilterPanel()" class="empty-state-btn"><i class="fa-solid fa-xmark"></i> Reset</button>';
            } else {
                var msgs = {
                    all: {
                        icon: 'fa-box-open',
                        title: 'No items in the inventory',
                        sub: 'Add your first item using the "Add Item" button above.'
                    },
                    medicine: {
                        icon: 'fa-pills',
                        title: 'No medicines in the inventory',
                        sub: 'Add a medicine item above.'
                    },
                    supplies: {
                        icon: 'fa-syringe',
                        title: 'No dental supplies in the inventory',
                        sub: 'Add a supply item above.'
                    }
                };

                var msg = msgs[activeTab] || msgs.all;
                icon = msg.icon;
                title = msg.title;
                sub = msg.sub;
            }

            emptyState.innerHTML = buildEmptyStateHtml({
                icon: icon,
                title: title,
                sub: sub,
                actionHtml: extraHtml
            });

            emptyState.classList.add('show');
            return;
        }

        emptyState.classList.remove('show');
        emptyState.innerHTML = '';

        if (currentViewMode === 'grid') {
            if (tableWrapper) tableWrapper.style.display = 'none';
            if (grid) grid.style.display = 'grid';
        } else {
            if (tableWrapper) tableWrapper.style.display = 'block';
            if (grid) grid.style.display = 'none';
        }

        data.forEach(function (item) {
            var balance = n(item.qty) - n(item.used);

            var balClass = balance <= 0 ? 'critical' : balance <= 5 ? 'low' : 'ok';
            var balLabel = balance <= 0 ? 'Out of stock' : balance <= 5 ? 'Low stock' : 'In stock';
            var cardStockClass = balance <= 0 ? 'out-stock' : (balance <= 5 ? 'low-stock' : '');

            var catClass = item.category === 'Medicine' ? 'medicine' : 'supplies';

            if (currentViewMode === 'grid') {
                grid.innerHTML += `
    <div class="inventory-card ${cardStockClass} ${catClass}">
        <div class="inventory-card-head">
            <div class="min-w-0">
                <div class="inventory-card-name">${item.name || ''}</div>

                <div class="inventory-card-tags">
                    <span class="stock-no">${item.stock_no || ''}</span>
                    <span class="supply-cat ${catClass}">${item.category || ''}</span>
                </div>
            </div>

            <span class="bal-chip ${balClass}">
                ${balance}
                <span>${balLabel}</span>
            </span>
        </div>

        <div class="inventory-card-meta">
            <div>
                <div class="inventory-card-label">Date</div>
                <div class="inventory-card-value">${item.formatted_date || ''}</div>
            </div>
            <div>
                <div class="inventory-card-label">Unit</div>
                <div class="inventory-card-value">${item.unit || ''}</div>
            </div>
            <div>
                <div class="inventory-card-label">Qty</div>
                <div class="inventory-card-value">${item.qty || 0}</div>
            </div>
            <div>
                <div class="inventory-card-label">Used</div>
                <div class="inventory-card-value">${item.used || 0}</div>
            </div>
        </div>

        <div class="inventory-card-actions">
    ${inventoryActionButtons(item.id)}
</div>
    </div>
`;
            } else {
                tbody.innerHTML +=
                    '<tr>' +
                    '<td style="color:#9A9490;font-size:12px;white-space:nowrap;">' + (item.formatted_date ||
                        '') + '</td>' +
                    '<td><span class="stock-no">' + (item.stock_no || '') + '</span></td>' +
                    '<td><div class="supply-name">' + (item.name || '') + '</div><span class="supply-cat ' +
                    catClass + '">' + (item.category || '') + '</span></td>' +
                    '<td style="color:#9A9490;">' + (item.unit || '') + '</td>' +
                    '<td style="font-weight:700;">' + (item.qty || 0) + '</td>' +
                    '<td style="color:#9A9490;">' + (item.used || 0) + '</td>' +
                    '<td><span class="bal-chip ' + balClass + '">' + balance +
                    ' <span style="font-weight:400;font-size:10px;">' + balLabel + '</span></span></td>' +
                    '<td><div class="inventory-row-actions">' + inventoryActionButtons(item.id) + '</div></td>' +
                    '</tr>';
            }
        });
    }

    function buildEmptyStateHtml({ icon, title, sub, actionHtml = '' }) {
        return `
        <div class="empty-state">
            <div class="empty-state-icon inventory-empty-icon">
                <i class="fa-solid ${icon}"></i>
            </div>

            <p class="empty-state-title">${title}</p>
            <p class="empty-state-sub">${sub}</p>

            ${actionHtml}
        </div>
    `;
    }

    function validateAddField(id) {
        var el = document.getElementById(id);
        if (!el) return true;
        var val = el.value.trim();
        var today = new Date();
        today.setHours(23, 59, 59, 999);
        switch (id) {
            case 'addCategory':
                if (!val) {
                    setFieldState(id, 'Please select a category');
                    return false;
                }
                break;
            case 'addDate': {
                if (!val) {
                    setFieldState(id, 'Date is required');
                    return false;
                }
                var picked = new Date(val);
                if (isNaN(picked.getTime())) {
                    setFieldState(id, 'Invalid date');
                    return false;
                }
                if (picked > today) {
                    setFieldState(id, 'Date cannot be in the future');
                    return false;
                }
                break;
            }
            case 'addStock':
                if (!val) {
                    setFieldState(id, 'Stock number is required');
                    return false;
                }
                if (!/^\d{2}-\d{3}$/.test(val)) {
                    setFieldState(id, 'Must be in format 00-000');
                    return false;
                }
                break;
            case 'addUnit':
                if (!val) {
                    setFieldState(id, 'Unit is required');
                    return false;
                }
                break;
            case 'addName':
                if (!val) {
                    setFieldState(id, 'Name is required');
                    return false;
                }
                if (val.length < 2) {
                    setFieldState(id, 'Minimum 2 characters');
                    return false;
                }
                break;
            case 'addQty': {
                var raw = el.value;
                if (raw === '' || raw === null) {
                    setFieldState(id, 'Quantity is required');
                    return false;
                }
                var qn = Number(raw);
                if (!Number.isInteger(qn) || qn < 0) {
                    setFieldState(id, 'Must be a whole number ≥ 0');
                    return false;
                }
                if (qn > 99999) {
                    setFieldState(id, 'Maximum quantity is 99,999');
                    return false;
                }
                break;
            }
            case 'addUsed': {
                var rawU = el.value;
                var un = Number(rawU || 0);
                var qnU = Number(document.getElementById('addQty').value || 0);
                if (rawU !== '' && (!Number.isInteger(un) || un < 0)) {
                    setFieldState(id, 'Must be a whole number ≥ 0');
                    return false;
                }
                if (un > qnU) {
                    setFieldState(id, 'Consumed cannot exceed quantity');
                    return false;
                }
                break;
            }
        }
        setFieldState(id, '');
        return true;
    }

    function validateAllAddFields() {
        return ['addCategory', 'addDate', 'addStock', 'addUnit', 'addName', 'addQty', 'addUsed'].map(function (id) {
            return validateAddField(id);
        }).every(Boolean);
    }

    function resetAddForm() {
        setInventoryCustomSelectValue('addCategory', '', false);
        closeInventoryChoiceMenus();
        ['addDate', 'addStock', 'addName', 'addUnit', 'addQty', 'addUsed', 'addBalance'].forEach(function (id) {
            document.getElementById(id).value = '';
        });
        ['addCategory', 'addDate', 'addStock', 'addUnit', 'addName', 'addQty', 'addUsed'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('is-invalid', 'is-valid');
            var err = document.getElementById('err-' + id);
            if (err) err.innerHTML = '';
        });
        var counter = document.getElementById('charCounter-addName');
        if (counter) {
            counter.textContent = '0 / 100';
            counter.className = 'char-counter';
        }

        document.querySelectorAll('#addModal [data-voice-status]').forEach(function (status) {
            status.classList.add('hidden');
            status.textContent = '';
        });
    }

    async function addItem() {
        if (!validateAllAddFields()) {
            var fi = document.querySelector('#addModal .is-invalid');
            if (fi) fi.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            return;
        }
        var btnSave = document.getElementById('btnSaveAdd');
        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
        var res = await fetch('{{ route('admin.inventory.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                category: document.getElementById('addCategory').value,
                date_received: document.getElementById('addDate').value,
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
            var err = await res.json().catch(function () {
                return {};
            });
            if (err.errors) {
                var map = {
                    category: 'addCategory',
                    date_received: 'addDate',
                    stock_no: 'addStock',
                    name: 'addName',
                    unit: 'addUnit',
                    qty: 'addQty',
                    used: 'addUsed'
                };
                Object.entries(err.errors).forEach(function ([k, v]) {
                    if (map[k]) setFieldState(map[k], Array.isArray(v) ? v[0] : v);
                });
            } else {
                showToast('error', 'Could not save item. Please try again.');
            }
            return;
        }
        forceCloseModal('addModal');
        resetAddForm();
        await loadInventory();
        showToast('success', 'Item added successfully!');
    }

    var deleteId = null;

    function deleteItem(id) {
        deleteId = id;

        const item = inventory.find(function (row) {
            return Number(row.id) === Number(id);
        });

        const nameTarget = document.getElementById('inventoryDeleteName');
        if (nameTarget) {
            nameTarget.textContent = item?.name || item?.stock_no || 'this item';
        }

        openModal('deleteModal');
    }

    document.getElementById('confirmDeleteBtn').onclick = async function () {
        if (!deleteId) return;

        const res = await fetch(inventoryUrl('destroy', deleteId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const result = await res.json().catch(function () {
            return {};
        });

        if (!res.ok) {
            console.error('Inventory delete failed:', res.status, result);
            showToast('error', result.message || 'Delete failed — please try again.');
            return;
        }

        if (window.closeModal) {
            closeModal('deleteModal');
        } else {
            document.getElementById('deleteModal')?.close();
        }

        deleteId = null;
        await loadInventory();
        showToast('success', 'Item deleted.');
    };

    var editId = null;

    function openEdit(id) {
        editId = id;
        var i = inventory.find(function (item) {
            return item.id === id;
        });
        if (!i) return;
        setInventoryCustomSelectValue('editCategory', i.category, false);
        closeInventoryChoiceMenus();
        document.getElementById('editStock').value = i.stock_no;
        document.getElementById('editName').value = i.name;
        document.getElementById('editUnit').value = i.unit;
        document.getElementById('editQty').value = i.qty;
        document.getElementById('editUsed').value = i.used;
        document.getElementById('editDate').value = i.date_received ? i.date_received.slice(0, 10) : '';
        computeEditBalance();
        openModal('editModal');
        if (window.initGlobalVoiceInputs) window.initGlobalVoiceInputs(document.getElementById('editModal'));
        document.dispatchEvent(new CustomEvent('voice:refresh', { detail: { root: document.getElementById('editModal') } }));
        captureDiscardForModal('editModal');
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
                stock_no: document.getElementById('editStock').value.trim(),
                name: document.getElementById('editName').value.trim(),
                unit: document.getElementById('editUnit').value.trim(),
                qty: Number(document.getElementById('editQty').value),
                used: Number(document.getElementById('editUsed').value || 0)
            })
        });

        const result = await res.json().catch(function () {
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