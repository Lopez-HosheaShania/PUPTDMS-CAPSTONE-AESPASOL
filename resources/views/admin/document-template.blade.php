@extends('layouts.app')

@section('layout-role', $layoutRole ?? 'admin')

@section('title', 'Document Templates')

@section('styles')
@vite('resources/css/pages/admin/document-templates.css')
@endsection

@section('content')

<main id="mainContent" class="app-page-shell document-templates-page page-enter">
    <div class="w-full">
        <section class="page-banner document-template-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Document Templates</h1>
                </div>

                <div class="admin-banner-actions">
                    <span class="admin-banner-pill">
                        <i class="fa-solid fa-lock"></i>
                        Default templates only
                    </span>
                </div>
            </div>
        </section>

        @php
        $stats = $stats ?? [];
        $totalTemplates = $stats['total'] ?? 0;
        $activeTemplates = $stats['active'] ?? 0;
        $archivedTemplates = $stats['archived'] ?? 0;
        @endphp

        <section class="stat-grid" id="statCards" aria-label="Template summary">
            <article class="stat-card s-all" data-template-stat="total">
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="stat-card-info">
                    <div class="stat-num" id="templateTotalStat">{{ $totalTemplates }}</div>
                    <div class="stat-label">Total Templates</div>
                </div>
            </article>

            <article class="stat-card s-active" data-template-stat="active">
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat-card-info">
                    <div class="stat-num" id="templateActiveStat">{{ $activeTemplates }}</div>
                    <div class="stat-label">Active</div>
                </div>
            </article>

            <article class="stat-card s-archived" data-template-stat="archived">
                <div class="stat-icon-wrapper">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
                <div class="stat-card-info">
                    <div class="stat-num" id="templateArchivedStat">{{ $archivedTemplates }}</div>
                    <div class="stat-label">Archived</div>
                </div>
            </article>
        </section>

        <section class="table-card" aria-label="Template filters">
            <div class="table-toolbar">

                <div class="table-toolbar-actions">

                    <div class="voice-search-row table-toolbar-search">

                        <x-search-bar id="templateSearch" placeholder="Search templates..."
                            clear-label="Clear template search" callback="handleTemplateSearch" :debounce="300" />

                        <x-voice-input target="#templateSearch" status-id="templateVoiceStatus"
                            label="Voice search document templates" title="Voice search" />

                    </div>

                    <button id="templateFilterBtn" type="button" class="global-filter-btn"
                        data-ui-click="action-4faee489e166" aria-label="Filter templates" data-tooltip="Filter"
                        data-tooltip-tone="neutral">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Filter</span>

                        <span id="templateFilterBadge" class="filter-badge"></span>
                    </button>

                    <button id="templateFilterResetBtn" type="button" class="global-filter-reset-btn hidden"
                        data-ui-click="action-9525fd19c356" aria-label="Clear template filters"
                        data-tooltip="Reset filters" data-tooltip-tone="neutral">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>

                </div>

            </div>

            <x-pagination-bar id="templatePaginationTopBar" info-id="templatePageInfoTop"
                pagination-id="templatePaginationTop" page-size-id="templatePageSize"
                page-size-callback="changeTemplatePageSize" position="top" :show-entries="true"
                :page-size-value="$templates->perPage()" label="templates"
                data-current-page="{{ $templates->currentPage() }}" data-last-page="{{ $templates->lastPage() }}"
                data-total="{{ $templates->total() }}" data-from="{{ $templates->firstItem() ?? 0 }}"
                data-to="{{ $templates->lastItem() ?? 0 }}" data-per-page="{{ $templates->perPage() }}" />

            <div id="templateResultsRegion">
                @if (!empty($templates) && $templates->isNotEmpty())
                <section class="templates-grid" id="templatesGrid" aria-label="Document template cards">
                    @foreach ($templates as $tpl)
                    @php
                    $category = strtolower(trim((string) ($tpl->category ?? '')));
                    $dt = strtolower((string) ($tpl->document_type ?? ''));

                    if ($category === '') {
                    if (str_contains($dt, 'clearance')) {
                    $category = 'clearance';
                    } elseif (str_contains($dt, 'record')) {
                    $category = 'record';
                    } elseif (str_contains($dt, 'report')) {
                    $category = 'report';
                    } elseif (str_contains($dt, 'inventory')) {
                    $category = 'inventory';
                    } else {
                    $category = 'other';
                    }
                    }

                    if (!in_array($category, ['clearance', 'record', 'report', 'inventory'], true)) {
                    $category = 'other';
                    }

                    $templateCode = $tpl->code ?? 'TPL-' . str_pad($tpl->id, 4, '0', STR_PAD_LEFT);
                    $statusClass = $tpl->status === 'active' ? 'status-active' : 'status-archived';
                    @endphp

                    <article class="template-card status-{{ $tpl->status }}" data-id="{{ $tpl->id }}"
                        data-name="{{ strtolower((string) $tpl->name) }}"
                        data-type="{{ strtolower((string) $tpl->document_type) }}" data-category="{{ $category }}"
                        data-status="{{ $tpl->status }}" data-template-name="{{ e($tpl->name) }}"
                        data-archive-url="{{ route(($routeNames['archive'] ?? 'admin.document-template.archive'), $tpl->id) }}"
                        data-activate-url="{{ route(($routeNames['activate'] ?? 'admin.document-template.activate'), $tpl->id) }}"
                        tabindex="0" role="button" aria-label="Preview {{ $tpl->name }}"
                        data-ui-click="action-27fedc7b9d1d" data-ui-click-arg-0="{{ json_encode($tpl->id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                        <div class="template-card-top">
                            <div class="template-top-row">
                                <div class="template-doc-icon">
                                    @if ($category === 'clearance')
                                    <i class="fa-solid fa-file-circle-check"></i>
                                    @elseif($category === 'record')
                                    <i class="fa-solid fa-folder-open"></i>
                                    @elseif($category === 'report')
                                    <i class="fa-solid fa-chart-line"></i>
                                    @elseif($category === 'inventory')
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                    @else
                                    <i class="fa-solid fa-file-lines"></i>
                                    @endif
                                </div>

                                <div class="template-badge-stack">
                                    <span class="status-badge {{ $statusClass }}" data-template-status-badge>
                                        {{ ucfirst($tpl->status) }}
                                    </span>

                                    @if ($tpl->is_default)
                                    <span class="status-badge status-default" data-template-default-badge>
                                        Default
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <div class="template-title-block">
                                <h2 class="template-name">{{ $tpl->name }}</h2>
                                <div class="template-code">{{ $templateCode }}</div>
                            </div>
                        </div>

                        <div class="template-card-body">
                            <p class="template-description">
                                {{ $tpl->description ?? ($tpl->notes ?? 'Default system template.') }}
                            </p>

                            <div class="template-meta-row">
                                <span class="template-meta-item">
                                    <i class="fa-solid fa-tag template-meta-icon"></i>
                                    <span>{{ ucwords(str_replace('_', ' ', $tpl->document_type)) }}</span>
                                </span>

                                <div class="template-actions" data-template-actions>
                                    <button type="button" class="ui-action-btn ui-action-view"
                                        data-tooltip="Preview template" data-tooltip-tone="view"
                                        aria-label="Preview template"
                                        data-ui-click="action-cc1085fad7b8" data-ui-click-arg-0="{{ json_encode($tpl->id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    @if ($tpl->status === 'active')
                                    <button type="button" class="ui-action-btn ui-action-warning"
                                        data-tooltip="Archive template" aria-label="Archive template"
                                        data-template-action="archive" data-template-id="{{ $tpl->id }}"
                                        data-ui-click="action-7286019d3d89">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </button>
                                    @elseif ($tpl->status === 'archived')
                                    <button type="button" class="ui-action-btn ui-action-success"
                                        data-tooltip="Activate template" aria-label="Activate template"
                                        data-template-action="activate" data-template-id="{{ $tpl->id }}"
                                        data-ui-click="action-7286019d3d89">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </section>
                @endif
                <div id="templateEmptyState" class="empty-state-host"></div>
            </div>

            <x-pagination-bar id="templatePaginationBottomBar" info-id="templatePageInfoBottom"
                pagination-id="templatePaginationBottom" position="bottom" label="templates"
                data-current-page="{{ $templates->currentPage() }}" data-last-page="{{ $templates->lastPage() }}"
                data-total="{{ $templates->total() }}" data-from="{{ $templates->firstItem() ?? 0 }}"
                data-to="{{ $templates->lastItem() ?? 0 }}" data-per-page="{{ $templates->perPage() }}" />
        </section>
    </div>
</main>

<x-filter-drawer id="templateFilterDrawer" title="Template Filters" close-id="closeTemplateFilterDrawer"
    close-callback="action-cb61e864b11d" clear-id="clearTemplateFiltersDrawer" clear-label="Clear Filters"
    cancel-id="cancelTemplateFilterDrawer" cancel-callback="action-cb61e864b11d" cancel-label="Cancel"
    apply-id="applyTemplateFilters" apply-label="Show Results">

    <div id="templateActiveFilters" class="filter-active-section hidden">
        <div class="filter-active-header">

            <span class="filter-active-title">
                Active Filters
            </span>

            <button type="button" id="clearTemplateFilterChips"
                class="filter-clear-all ui-btn ui-btn-secondary ui-btn-sm">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Clear All</span>
            </button>

        </div>

        <div id="templateFilterChips" class="active-filters-container"></div>
    </div>


    <x-filter-group title="Category">

        <div class="filter-chip-row">

            <label class="choice-chip">
                <input type="radio" name="template_filter_category" value="" class="filter-input radio-red chip-radio"
                    checked>

                <span>
                    All Categories
                </span>
            </label>

            <label class="choice-chip">
                <input type="radio" name="template_filter_category" value="clearance"
                    class="filter-input radio-red chip-radio">

                <span>
                    Clearance
                </span>
            </label>

            <label class="choice-chip">
                <input type="radio" name="template_filter_category" value="record"
                    class="filter-input radio-red chip-radio">

                <span>
                    Record
                </span>
            </label>

            <label class="choice-chip">
                <input type="radio" name="template_filter_category" value="report"
                    class="filter-input radio-red chip-radio">

                <span>
                    Report
                </span>
            </label>

            <label class="choice-chip">
                <input type="radio" name="template_filter_category" value="inventory"
                    class="filter-input radio-red chip-radio">

                <span>
                    Inventory
                </span>
            </label>

        </div>

    </x-filter-group>


    <x-filter-group title="Status" class="filter-group-last">

        <div class="filter-chip-row">

            <label class="choice-chip">
                <input type="radio" name="template_filter_status" value="" class="filter-input radio-red chip-radio"
                    checked>

                <span>
                    All Status
                </span>
            </label>

            <label class="choice-chip">
                <input type="radio" name="template_filter_status" value="active"
                    class="filter-input radio-red chip-radio">

                <span>
                    Active
                </span>
            </label>

            <label class="choice-chip">
                <input type="radio" name="template_filter_status" value="archived"
                    class="filter-input radio-red chip-radio">

                <span>
                    Archived
                </span>
            </label>

        </div>

    </x-filter-group>

</x-filter-drawer>

<div id="templatePreviewBackdrop" class="ui-modal modal-theme-primary" aria-hidden="true">

    <div class="ui-modal-card modal-preview-card modal-card-form" role="dialog" aria-modal="true"
        aria-labelledby="templatePreviewTitle">

        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon">
                    <i class="fa-solid fa-file-lines"></i>
                </div>

                <div class="modal-copy">
                    <h2 id="templatePreviewTitle" class="modal-title">
                        Template Preview
                    </h2>

                    <p id="templatePreviewSubtitle" class="modal-subtitle">
                        Loading...
                    </p>
                </div>
            </div>

            <button type="button" class="modal-x" id="closeTemplatePreview" aria-label="Close preview">

                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-preview-body" data-global-preview-zoom data-preview-min="0.5" data-preview-max="2"
            data-preview-step="0.1">
            <div id="templatePreviewMeta" class="modal-preview-toolbar">
            </div>

            <div class="modal-preview-toolbar">
                <span class="modal-helper-text">
                    Use the controls to adjust the preview size.
                </span>

                <div class="modal-preview-toolbar-actions">
                    <button type="button" id="templateZoomOut" class="ui-action-btn ui-action-neutral"
                        data-preview-zoom-out data-tooltip="Zoom out" aria-label="Zoom out">

                        <i class="fa-solid fa-minus"></i>
                    </button>

                    <span id="templateZoomValue" class="modal-preview-zoom-value" data-preview-zoom-value>
                        100%
                    </span>

                    <button type="button" id="templateZoomIn" class="ui-action-btn ui-action-neutral"
                        data-preview-zoom-in data-tooltip="Zoom in" aria-label="Zoom in">

                        <i class="fa-solid fa-plus"></i>
                    </button>

                    <button type="button" id="templateZoomReset" class="ui-action-btn ui-action-reset"
                        data-preview-zoom-reset data-tooltip="Reset zoom" aria-label="Reset zoom">

                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <div class="modal-preview-stage" id="templatePreviewStage" data-preview-stage>

                <div class="modal-preview-canvas" id="templatePreviewCanvas" data-preview-canvas>

                    <iframe id="templatePreviewFrame" class="modal-preview-frame" data-preview-content
                        title="Document template preview" scrolling="no" sandbox="allow-same-origin">
                    </iframe>
                </div>
            </div>
        </div>

        <div class="modal-ft" id="templatePreviewFooter">
        </div>
    </div>
</div>

<div id="templateArchiveModal" class="ui-modal modal-theme-warning" aria-hidden="true">

    <div class="ui-modal-card modal-md modal-card-form" role="dialog" aria-modal="true"
        aria-labelledby="templateArchiveTitle">

        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon">
                    <i class="fa-solid fa-box-archive"></i>
                </div>

                <div class="modal-copy">
                    <h2 id="templateArchiveTitle" class="modal-title">
                        Archive this template?
                    </h2>

                    <p class="modal-subtitle">
                        This template will be hidden from active template lists.
                    </p>
                </div>
            </div>

            <button type="button" class="modal-x" data-modal-close="templateArchiveModal"
                aria-label="Close archive modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-bd">
            <div class="global-confirm-alert">
                <i class="fa-solid fa-file-lines"></i>

                <div>
                    <p>
                        Selected template
                    </p>

                    <strong id="templateArchiveName">
                        Template
                    </strong>
                </div>
            </div>

            <div class="modal-helper-text">
                <i class="fa-solid fa-circle-info"></i>

                <span>
                    You can activate this template again later from the
                    archived templates filter.
                </span>
            </div>
        </div>

        <div class="modal-ft">
            <button type="button" class="ui-btn ui-btn-secondary" data-modal-close="templateArchiveModal">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Keep</span>
            </button>

            <button type="button" class="ui-btn ui-btn-warning" id="confirmTemplateArchiveBtn">
                <i class="fa-solid fa-box-archive"></i>
                <span>Archive</span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';
    let pendingTemplateAction = null;
    let currentPreviewTemplateId = null;
    let currentPreviewPayload = null;

    function templateEscapeHtml(value = '') {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatTitle(value) {
        if (!value) return '—';
        return String(value).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function buildPreviewDocument(d) {
        return window.prepareDocumentPreview(d.content || '<p>No preview available.</p>');
    }

    function openPreviewModal() {
        window.openModal?.(
            'templatePreviewBackdrop'
        );

        requestAnimationFrame(() => {
            window.resetGlobalPreviewZoom?.(
                document.getElementById(
                    'templatePreviewBackdrop'
                )
            );
        });
    }

    function closePreviewModal() {
        window.closeModal?.('templatePreviewBackdrop');

        const frame = document.getElementById('templatePreviewFrame');

        if (frame) {
            frame.srcdoc = '';
        }

        currentPreviewTemplateId = null;
        currentPreviewPayload = null;

        document
            .querySelectorAll('.template-card')
            .forEach(card => card.classList.remove('selected'));
    }

    function getTemplateCard(id) {
        return document.querySelector(`.template-card[data-id="${CSS.escape(String(id))}"]`);
    }

    function getTemplateActionUrl(id, action) {
        const card = getTemplateCard(id);
        if (card) {
            return action === 'archive' ? card.dataset.archiveUrl : card.dataset.activateUrl;
        }

        return `/admin/document-template/${id}/${action}`;
    }

    function getTemplateName(id) {
        const card = getTemplateCard(id);
        return card?.dataset.templateName || currentPreviewPayload?.name || 'Template';
    }

    function getStatusBadgeClass(status) {
        return status === 'active' ?
            'status-active' :
            'status-archived';
    }

    function getStatusLabel(status) {
        return status ? status.charAt(0).toUpperCase() + status.slice(1) : '—';
    }

    window.handleTemplateActionClick = function (button) {
        const action = button.dataset.templateAction;
        const id = button.dataset.templateId;
        const actionData = {
            action,
            id,
            name: getTemplateName(id),
            url: getTemplateActionUrl(id, action),
        };

        if (action === 'archive') {
            openTemplateArchiveModal(actionData);
            return;
        }

        submitTemplateAction(actionData);
    };

    function renderTemplateActionButtons(card, status) {
        const actions = card?.querySelector('[data-template-actions]');
        if (!actions || !card) return;

        const id = card.dataset.id;

        actions.innerHTML = `
        <button type="button"
            class="ui-action-btn ui-action-view"
            data-tooltip="Preview template"
            data-tooltip-tone="view"
            aria-label="Preview template"
            data-ui-click="action-cc1085fad7b8" data-ui-click-arg-0="${window.uiAttribute(id)}">
            <i class="fa-solid fa-eye"></i>
        </button>
        ${status === 'active' ? `
                    <button type="button"
            class="ui-action-btn ui-action-warning"
            data-tooltip="Archive template"
            data-tooltip-tone="reschedule"
            aria-label="Archive template"
                        data-template-action="archive" data-template-id="${id}"
                        data-ui-click="action-7286019d3d89">
                        <i class="fa-solid fa-box-archive"></i>
                    </button>
                ` : `
                    <button type="button"
            class="ui-action-btn ui-action-success"
            data-tooltip="Activate template" data-tooltip-tone="start"
            aria-label="Activate template" data-template-action="activate" data-template-id="${id}"
            data-ui-click="action-7286019d3d89">
                        <i class="fa-solid fa-circle-check"></i>
                    </button>
                `}
    `;
    }

    function updateStats(stats = {}) {
        if (stats.total !== undefined) document.getElementById('templateTotalStat').textContent = stats.total;
        if (stats.active !== undefined) document.getElementById('templateActiveStat').textContent = stats.active;
        if (stats.archived !== undefined) document.getElementById('templateArchivedStat').textContent = stats.archived;
    }

    function applyTemplateStatus(id, payload = {}) {
        const template = payload.template || payload;
        const status = template.status || (payload.action === 'archive' ? 'archived' : 'active');
        const card = getTemplateCard(id);

        if (card) {
            card.dataset.status = status;
            card.classList.remove('status-active', 'status-archived');
            card.classList.add(`status-${status}`);

            const badge = card.querySelector('[data-template-status-badge]');
            if (badge) {
                badge.className = `status-badge ${getStatusBadgeClass(status)}`;
                badge.textContent = getStatusLabel(status);
            }

            if (status === 'archived') {
                card.querySelector('[data-template-default-badge]')?.remove();
            }

            renderTemplateActionButtons(card, status);
        }

        if (payload.stats) updateStats(payload.stats);

        if (currentPreviewTemplateId && Number(currentPreviewTemplateId) === Number(id)) {
            currentPreviewPayload = {
                ...(currentPreviewPayload || {}),
                ...(template || {}),
                status,
                is_default: status === 'archived' ? false : (template.is_default ?? currentPreviewPayload
                    ?.is_default)
            };
            renderPreviewMeta(currentPreviewPayload);
            renderPreviewFooter(currentPreviewPayload);
        }

        filterTemplateCards();
    }

    function renderPreviewMeta(d) {
        const metaEl =
            document.getElementById('templatePreviewMeta');

        if (!metaEl || !d) return;

        const status = d.status || 'active';
        const statusClass =
            getStatusBadgeClass(status);

        metaEl.innerHTML = `
        <div class="ui-action-group">
            <span class="status-badge">
                <i class="fa-solid fa-file-lines"></i>
                ${templateEscapeHtml(
            formatTitle(d.document_type)
        )}
            </span>

            <span class="status-badge">
                <i class="fa-solid fa-layer-group"></i>
                ${templateEscapeHtml(
            d.category || '—'
        )}
            </span>

            <span class="status-badge">
                <i class="fa-solid fa-print"></i>
                ${templateEscapeHtml(
            d.paper_size || '—'
        )}
                •
                ${templateEscapeHtml(
            formatTitle(d.orientation || '')
        )}
            </span>

            <span class="status-badge ${statusClass}">
                ${templateEscapeHtml(
            getStatusLabel(status)
        )}
            </span>

            ${d.is_default
                ? `
                            <span class="status-badge">
                                Default
                            </span>
                        `
                : ''
            }
        </div>
    `;
    }

    function renderPreviewFooter(d) {
        const footerEl =
            document.getElementById('templatePreviewFooter');

        if (!footerEl || !d) return;

        footerEl.innerHTML = `
        <button type="button"
            class="ui-btn ui-btn-secondary"
            data-ui-click="action-0efe3e880025">

            <i class="fa-solid fa-xmark"></i>
            <span>Close</span>
        </button>

        ${d.status === 'active'
                ? `
                        <button type="button"
                            class="ui-btn ui-btn-warning"
                            data-template-action="archive"
                            data-template-id="${d.id}">

                            <i class="fa-solid fa-box-archive"></i>
                            <span>Archive</span>
                        </button>
                    `
                : `
                        <button type="button"
                            class="ui-btn ui-btn-success"
                            data-template-action="activate"
                            data-template-id="${d.id}">

                            <i class="fa-solid fa-circle-check"></i>
                            <span>Activate</span>
                        </button>
                    `
            }
    `;
    }

    async function openTemplatePreview(id) {
        document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
        getTemplateCard(id)?.classList.add('selected');

        openPreviewModal();
        currentPreviewTemplateId = id;

        const titleEl = document.getElementById('templatePreviewTitle');
        const subtitleEl = document.getElementById('templatePreviewSubtitle');
        const metaEl = document.getElementById('templatePreviewMeta');
        const frameEl = document.getElementById('templatePreviewFrame');
        const footerEl = document.getElementById('templatePreviewFooter');

        titleEl.textContent = 'Loading...';
        subtitleEl.textContent = 'Please wait';
        metaEl.innerHTML = '';
        footerEl.innerHTML = '';

        frameEl.srcdoc =
            '<p data-ui-presentation="presentation-c9730b18220d">Loading preview...</p>';

        try {
            const res = await fetch(`/admin/document-template/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!res.ok) throw new Error('Failed to fetch');

            const d = await res.json();
            currentPreviewPayload = d;

            titleEl.textContent = d.name || 'Template Preview';
            subtitleEl.textContent = formatTitle(d.document_type);
            renderPreviewMeta(d);
            frameEl.srcdoc = buildPreviewDocument(d);
            renderPreviewFooter(d);
        } catch (e) {
            titleEl.textContent = 'Template Preview';
            subtitleEl.textContent = 'Failed to load';
            frameEl.srcdoc =
                '<p data-ui-presentation="presentation-0b97713c0ae4">Failed to load template preview.</p>';
        }
    }

    let appliedTemplateCategory = '';
    let appliedTemplateStatus = '';
    let templatePageLoading = false;

    let templatePerPage =
        Number(
            document
                .getElementById(
                    'templatePaginationTopBar'
                )
                ?.dataset
                .perPage
        ) || 10;

    window.changeTemplatePageSize =
        function (value) {
            const nextSize =
                Number(value);

            templatePerPage =
                [10, 20, 50, 100]
                    .includes(nextSize)
                    ? nextSize
                    : 10;

            loadTemplatePage(1);
        };

    function getTemplateFilterCount() {
        return (
            (appliedTemplateCategory ? 1 : 0) +
            (appliedTemplateStatus ? 1 : 0)
        );
    }

    function getTemplatePreviewFilters() {
        const category =
            document.querySelector(
                'input[name="template_filter_category"]:checked'
            )?.value || '';

        const status =
            document.querySelector(
                'input[name="template_filter_status"]:checked'
            )?.value || '';

        return {
            category,
            status,
        };
    }

    function syncTemplateFilterRadios() {
        document
            .querySelectorAll(
                'input[name="template_filter_category"]'
            )
            .forEach(radio => {
                radio.checked =
                    radio.value ===
                    appliedTemplateCategory;
            });

        document
            .querySelectorAll(
                'input[name="template_filter_status"]'
            )
            .forEach(radio => {
                radio.checked =
                    radio.value ===
                    appliedTemplateStatus;
            });
    }

    function renderTemplateFilterChips() {
        const section =
            document.getElementById(
                'templateActiveFilters'
            );

        const host =
            document.getElementById(
                'templateFilterChips'
            );

        if (!section || !host) {
            return;
        }

        const preview =
            getTemplatePreviewFilters();

        host.replaceChildren();

        let hasChips =
            false;

        function addChip(
            label,
            onRemove
        ) {
            hasChips = true;

            const chip =
                document.createElement(
                    'div'
                );

            chip.className =
                'filter-chip';

            chip.innerHTML = `
            <span>
                ${templateEscapeHtml(label)}
            </span>

            <span
                class="filter-chip-remove"
                role="button"
                tabindex="0"
                aria-label="Remove ${templateEscapeHtml(label)} filter"
            >
                <i class="fa-solid fa-xmark"></i>
            </span>
        `;

            chip
                .querySelector(
                    '.filter-chip-remove'
                )
                ?.addEventListener(
                    'click',
                    () => {
                        onRemove();

                        renderTemplateFilterChips();
                    }
                );

            host.appendChild(
                chip
            );
        }

        if (preview.category) {
            const label =
                preview.category
                    .charAt(0)
                    .toUpperCase() +
                preview.category.slice(1);

            addChip(
                `Category: ${label}`,
                () => {
                    const allCategory =
                        document.querySelector(
                            'input[name="template_filter_category"][value=""]'
                        );

                    if (allCategory) {
                        allCategory.checked = true;

                        allCategory.dispatchEvent(
                            new Event(
                                'change',
                                {
                                    bubbles: true,
                                }
                            )
                        );
                    }
                }
            );
    }

    if (preview.status) {
        const label =
            preview.status
                .charAt(0)
                .toUpperCase() +
            preview.status.slice(1);

        addChip(
            `Status: ${label}`,
            () => {
                const allStatus =
                    document.querySelector(
                        'input[name="template_filter_status"][value=""]'
                    );

                if (allStatus) {
                    allStatus.checked =
                        true;
                }
            }
        );
    }

    section.classList.toggle(
        'hidden',
        !hasChips
    );
    }

    function updateTemplateFilterUi() {
        const count =
            getTemplateFilterCount();

        const badge =
            document.getElementById(
                'templateFilterBadge'
            );

        const filterButton =
            document.getElementById(
                'templateFilterBtn'
            );

        const resetButton =
            document.getElementById(
                'templateFilterResetBtn'
            );

        if (badge) {
            badge.textContent = count;

            badge.classList.toggle(
                'show',
                count > 0
            );
        }

        filterButton?.classList.toggle(
            'has-filters',
            count > 0
        );

        resetButton?.classList.toggle(
            'hidden',
            count === 0
        );

        renderTemplateFilterChips();
    }

    window.openTemplateFilterDrawer =
        function () {
            syncTemplateFilterRadios();
            renderTemplateFilterChips();

            window.openFilterDrawer?.(
                'templateFilterDrawer'
            );
        };

    window.closeTemplateFilterDrawer =
        function () {
            window.closeFilterDrawer?.(
                'templateFilterDrawer'
            );
        };

    function resetTemplateFilters() {
        appliedTemplateCategory = '';
        appliedTemplateStatus = '';

        syncTemplateFilterRadios();
        updateTemplateFilterUi();

        loadTemplatePage(1);
    }

    window.resetTemplateFilters =
        resetTemplateFilters;

    function openTemplateArchiveModal(actionData) {
        pendingTemplateAction = actionData;

        const nameEl =
            document.getElementById('templateArchiveName');

        if (nameEl) {
            nameEl.textContent =
                actionData.name || 'Template';
        }

        window.openModal?.('templateArchiveModal');
    }

    function closeTemplateArchiveModal() {
        window.closeModal?.('templateArchiveModal');
        pendingTemplateAction = null;
    }

    async function submitTemplateAction(actionData) {
        if (!actionData?.id || !actionData?.url || !actionData?.action) return;

        const triggerButtons = document.querySelectorAll(
            `[data-template-action="${actionData.action}"][data-template-id="${actionData.id}"]`);
        triggerButtons.forEach(button => {
            button.disabled = true;
            button.classList.add('is-loading');
        });

        const confirmBtn = document.getElementById('confirmTemplateArchiveBtn');
        const originalConfirmHTML = confirmBtn?.innerHTML;

        if (actionData.action === 'archive' && confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fa-solid fa-spinner spin"></i> Archiving...';
        }

        try {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('_method', 'PATCH');

            const response = await fetch(actionData.url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.success === false) {
                throw new Error(payload.message || 'Unable to update template.');
            }

            updateStats(
                payload.stats || {}
            );

            closeTemplateArchiveModal();

            const currentPage =
                Number(
                    document
                        .getElementById(
                            'templatePaginationTopBar'
                        )
                        ?.dataset
                        .currentPage
                ) || 1;

            await loadTemplatePage(
                currentPage
            );

            window.showToast?.({
                type: actionData.action === 'archive' ? 'warning' : 'success',
                title: actionData.action === 'archive' ? 'Template archived' : 'Template activated',
                message: payload.message ||
                    `Template ${actionData.action === 'archive' ? 'archived' : 'activated'} successfully.`,
            });
        } catch (error) {
            window.showToast?.({
                type: 'error',
                title: 'Update failed',
                message: error.message || 'Please try again.',
            });
        } finally {
            triggerButtons.forEach(button => {
                button.disabled = false;
                button.classList.remove('is-loading');
            });

            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalConfirmHTML ||
                    '<i class="fa-solid fa-box-archive"></i> Yes, Archive';
            }
        }
    }

    function handleTemplateActionClick(button) {
        const action = button.dataset.templateAction;
        const id = button.dataset.templateId;
        const actionData = {
            action,
            id,
            name: getTemplateName(id),
            url: getTemplateActionUrl(id, action),
        };

        if (action === 'archive') {
            openTemplateArchiveModal(actionData);
            return;
        }

        submitTemplateAction(actionData);
    }

    function renderTemplateEmptyState() {
        const host =
            document.getElementById(
                'templateEmptyState'
            );

        const grid =
            document.getElementById(
                'templatesGrid'
            );

        if (!host) {
            return;
        }

        const hasTemplates =
            Boolean(
                grid?.querySelector(
                    '.template-card'
                )
            );

        if (hasTemplates) {
            window.EmptyState?.hide(
                '#templateEmptyState'
            );

            return;
        }

        const searchInput =
            document.getElementById(
                'templateSearch'
            );

        const query =
            String(
                searchInput?.value || ''
            ).trim();

        if (query) {
            window.EmptyState?.renderSearch({
                host:
                    '#templateEmptyState',

                input:
                    '#templateSearch',

                query,

                message:
                    'Try a different template name or clear your search.',
            });

            return;
        }

        if (
            appliedTemplateCategory ||
            appliedTemplateStatus
        ) {
            window.EmptyState?.render({
                host:
                    '#templateEmptyState',

                icon:
                    'fa-sliders',

                title:
                    'No templates match your filters',

                message:
                    'Try another category or status.',

                actionHtml: `
                <button
                    type="button"
                    class="empty-state-btn"
                    data-ui-click="action-9525fd19c356"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Clear filters
                </button>
            `,
            });

            return;
        }

        window.EmptyState?.render({
            host:
                '#templateEmptyState',

            icon:
                'fa-file-circle-xmark',

            title:
                'No templates available',

            message:
                'No default document templates are currently available.',
        });
    }

    function renderTemplatePagination() {
        const topBar =
            document.getElementById(
                'templatePaginationTopBar'
            );

        const bottomBar =
            document.getElementById(
                'templatePaginationBottomBar'
            );

        if (!topBar) {
            return;
        }

        const currentPage =
            Number(
                topBar.dataset.currentPage
            ) || 1;

        const lastPage =
            Number(
                topBar.dataset.lastPage
            ) || 1;

        const total =
            Number(
                topBar.dataset.total
            ) || 0;

        const from =
            Number(
                topBar.dataset.from
            ) || 0;

        const to =
            Number(
                topBar.dataset.to
            ) || 0;

        window.renderGlobalPagination?.({
            currentPage,
            lastPage,
            total,
            from,
            to,

            containers: [
                document.getElementById(
                    'templatePaginationTop'
                ),
                document.getElementById(
                    'templatePaginationBottom'
                ),
            ],

            infoElements: [
                document.getElementById(
                    'templatePageInfoTop'
                ),
                document.getElementById(
                    'templatePageInfoBottom'
                ),
            ],

            bars: [
                topBar,
                bottomBar,
            ],

            itemLabel:
                total === 1
                    ? 'template'
                    : 'templates',

            onPageChange(page) {
                loadTemplatePage(page);
            },
        });

        const pageSize =
            document.getElementById(
                'templatePageSize'
            );

        if (pageSize) {
            pageSize.value =
                String(templatePerPage);

            window.syncGlobalPageSizeSelect?.(
                pageSize,
                templatePerPage
            );
        }
    }

    async function loadTemplatePage(
        page = 1
    ) {
        if (templatePageLoading) {
            return;
        }

        templatePageLoading = true;

        const searchInput =
            document.getElementById(
                'templateSearch'
            );

        const topBar =
            document.getElementById(
                'templatePaginationTopBar'
            );

        const bottomBar =
            document.getElementById(
                'templatePaginationBottomBar'
            );

        const pagebars =
            [
                topBar,
                bottomBar,
            ].filter(Boolean);

        const url =
            new URL(
                window.location.href
            );

        url.searchParams.set(
            'page',
            String(page)
        );

        url.searchParams.set(
            'per_page',
            String(templatePerPage)
        );

        const searchValue =
            String(
                searchInput?.value || ''
            ).trim();

        if (searchValue) {
            url.searchParams.set(
                'search',
                searchValue
            );
        } else {
            url.searchParams.delete(
                'search'
            );
        }

        if (appliedTemplateCategory) {
            url.searchParams.set(
                'category',
                appliedTemplateCategory
            );
        } else {
            url.searchParams.delete(
                'category'
            );
        }

        if (appliedTemplateStatus) {
            url.searchParams.set(
                'status',
                appliedTemplateStatus
            );
        } else {
            url.searchParams.delete(
                'status'
            );
        }

        try {
            pagebars.forEach(
                bar => {
                    bar.classList.add(
                        'is-loading'
                    );
                }
            );

            const response =
                await fetch(
                    url.toString(),
                    {
                        headers: {
                            Accept:
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',
                        },

                        credentials:
                            'same-origin',
                    }
                );

            if (!response.ok) {
                throw new Error(
                    'Unable to load document templates.'
                );
            }

            const payload =
                await response.json();

            if (
                !payload.success ||
                !payload.results_html
            ) {
                throw new Error(
                    'Invalid document template response.'
                );
            }

            const parsed =
                new DOMParser()
                    .parseFromString(
                        payload.results_html,
                        'text/html'
                    );

            const newRegion =
                parsed.getElementById(
                    'templateResultsRegion'
                );

            const currentRegion =
                document.getElementById(
                    'templateResultsRegion'
                );

            if (
                !newRegion ||
                !currentRegion
            ) {
                throw new Error(
                    'Unable to update document template results.'
                );
            }

            currentRegion.innerHTML =
                newRegion.innerHTML;

            const pagination =
                payload.pagination || {};

            pagebars.forEach(
                bar => {
                    bar.dataset.currentPage =
                        String(
                            pagination.current_page
                            ?? 1
                        );

                    bar.dataset.lastPage =
                        String(
                            pagination.last_page
                            ?? 1
                        );

                    bar.dataset.total =
                        String(
                            pagination.total
                            ?? 0
                        );

                    bar.dataset.from =
                        String(
                            pagination.from
                            ?? 0
                        );

                    bar.dataset.to =
                        String(
                            pagination.to
                            ?? 0
                        );

                    bar.dataset.perPage =
                        String(
                            pagination.per_page
                            ?? templatePerPage
                        );
                }
            );

            if (payload.stats) {
                updateStats(
                    payload.stats
                );
            }

            window.history.replaceState(
                {},
                '',
                url.toString()
            );

            renderTemplateEmptyState();
            renderTemplatePagination();

        } catch (error) {
            window.showToast?.({
                type: 'error',

                title:
                    'Unable to load templates',

                message:
                    error.message ||
                    'Please try again.',

                duration: 4500,
            });
        } finally {
            templatePageLoading =
                false;

            pagebars.forEach(
                bar => {
                    bar.classList.remove(
                        'is-loading'
                    );
                }
            );
        }
    }

    document.addEventListener('DOMContentLoaded', () => {

        window.initGlobalPageSizeSelects?.();

        document
            .querySelectorAll(
                [
                    'input[name="template_filter_category"]',
                    'input[name="template_filter_status"]',
                ].join(',')
            )
            .forEach(
                input => {
                    input.addEventListener(
                        'change',
                        () => {
                            renderTemplateFilterChips();
                        }
                    );
                }
            );

        document
            .getElementById(
                'clearTemplateFilterChips'
            )
            ?.addEventListener(
                'click',
                () => {
                    const allCategory =
                        document.querySelector(
                            'input[name="template_filter_category"][value=""]'
                        );

                    const allStatus =
                        document.querySelector(
                            'input[name="template_filter_status"][value=""]'
                        );

                    if (allCategory) {
                        allCategory.checked =
                            true;
                    }

                    if (allStatus) {
                        allStatus.checked =
                            true;
                    }

                    renderTemplateFilterChips();
                }
            );

        document.getElementById('closeTemplatePreview')?.addEventListener('click', closePreviewModal);

        document.getElementById('confirmTemplateArchiveBtn')?.addEventListener('click', () => {
            if (pendingTemplateAction) {
                submitTemplateAction(pendingTemplateAction);
            }
        });

        window.handleTemplateSearch =
            function () {
                loadTemplatePage(1);
            };

        window.handleTemplatePageSizeChange =
            function (value) {
                const parsed =
                    Number(value);

                templatePerPage =
                    [10, 20, 50, 100]
                        .includes(parsed)
                        ? parsed
                        : 10;

                loadTemplatePage(1);
            };

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;

            const archiveModal =
                document.getElementById('templateArchiveModal');

            const previewModal =
                document.getElementById('templatePreviewBackdrop');

            if (archiveModal?.classList.contains('open')) {
                closeTemplateArchiveModal();
                return;
            }

            if (previewModal?.classList.contains('open')) {
                closePreviewModal();
            }
        });

        document
            .getElementById('templatePreviewFooter')
            ?.addEventListener('click', function (event) {
                const actionButton =
                    event.target.closest('[data-template-action]');

                if (!actionButton) return;

                event.preventDefault();

                window.handleTemplateActionClick(actionButton);
            });

        document
            .getElementById(
                'applyTemplateFilters'
            )
            ?.addEventListener(
                'click',
                () => {
                    const preview =
                        getTemplatePreviewFilters();

                    appliedTemplateCategory =
                        preview.category;

                    appliedTemplateStatus =
                        preview.status;

                    updateTemplateFilterUi();

                    window
                        .closeTemplateFilterDrawer
                        ?.();

                    loadTemplatePage(1);
                }
            );

        document
            .getElementById(
                'clearTemplateFiltersDrawer'
            )
            ?.addEventListener(
                'click',
                () => {
                    resetTemplateFilters();
                    syncTemplateFilterRadios();
                }
            );

        renderTemplateEmptyState();
        updateTemplateFilterUi();
        renderTemplatePagination();
    });
</script>
@endsection
