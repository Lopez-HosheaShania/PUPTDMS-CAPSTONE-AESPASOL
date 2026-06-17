@extends('layouts.admin')

@section('title', 'Document Templates | PUP Taguig Dental Clinic')

@section('content')
<main id="mainContent" class="admin-page-shell document-templates-page page-enter">
    <div class="w-full">
        <section class="page-banner document-template-banner">
            <div class="page-banner-inner">
                <div class="min-w-0">
                    <div class="page-greeting">
                        <i class="fa-solid fa-file-shield"></i>
                        Template Management
                    </div>
                    <h1 class="page-title">Document Templates</h1>
                    <p class="page-subtitle">Manage default printable templates used by the dental clinic.</p>
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

        <section class="stat-grid template-stat-grid" id="statCards" aria-label="Template summary">
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

        <section class="section-card template-toolbar-card" aria-label="Template filters">
            <div class="section-card-body">
                <div class="template-controls">
                    <div class="template-search-row">
                        <div class="search-wrap global-search template-search-wrap" data-search-wrapper>
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" id="templateSearch" class="search-input no-voice" data-search-input
                                placeholder="Search templates..." autocomplete="off">
                            <button type="button" id="templateSearchClear" class="search-clear" data-search-clear
                                aria-label="Clear search" title="Clear search">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="template-voice-toggle">
                            <button type="button" id="templateMicToggleBtn" class="voice-search-mic external"
                                aria-label="Toggle voice input" aria-pressed="false">
                                <i class="fa-solid fa-microphone"></i>
                            </button>
                            <span id="templateVoiceStatus" class="voice-status hidden" aria-live="polite"></span>
                        </div>
                    </div>

                    <div class="template-filter-groups">
                        <div class="tab-bar template-filter-tabs" role="group" aria-label="Filter by category">
                            <button type="button" class="tab-btn active" data-filter="" data-filter-type="category">
                                <i class="fa-solid fa-grip tab-icon"></i>
                                <span class="tab-label">All</span>
                            </button>
                            <button type="button" class="tab-btn" data-filter="clearance" data-filter-type="category">
                                <i class="fa-solid fa-file-circle-check tab-icon"></i>
                                <span class="tab-label">Clearance</span>
                            </button>
                            <button type="button" class="tab-btn" data-filter="record" data-filter-type="category">
                                <i class="fa-solid fa-folder-open tab-icon"></i>
                                <span class="tab-label">Record</span>
                            </button>
                            <button type="button" class="tab-btn" data-filter="report" data-filter-type="category">
                                <i class="fa-solid fa-chart-line tab-icon"></i>
                                <span class="tab-label">Report</span>
                            </button>
                            <button type="button" class="tab-btn" data-filter="inventory" data-filter-type="category">
                                <i class="fa-solid fa-boxes-stacked tab-icon"></i>
                                <span class="tab-label">Inventory</span>
                            </button>
                        </div>

                        <div class="tab-bar template-filter-tabs template-status-tabs" role="group"
                            aria-label="Filter by status">
                            <button type="button" class="tab-btn active" data-filter="" data-filter-type="status">
                                All Status
                            </button>
                            <button type="button" class="tab-btn" data-filter="active" data-filter-type="status">
                                <span class="template-status-dot is-active"></span>
                                Active
                            </button>
                            <button type="button" class="tab-btn" data-filter="archived" data-filter-type="status">
                                <span class="template-status-dot is-archived"></span>
                                Archived
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if (empty($templates) || $templates->isEmpty())
        <section class="section-card template-empty-card">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </div>
                <h2 class="empty-state-title">No templates available</h2>
                <p class="empty-state-sub">No default document templates are currently available.</p>
            </div>
        </section>
        @else
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
            $statusClass = $tpl->status === 'active' ? 'badge-active' : 'badge-archived';
            @endphp

            <article class="template-card status-{{ $tpl->status }}" data-id="{{ $tpl->id }}"
                data-name="{{ strtolower((string) $tpl->name) }}"
                data-type="{{ strtolower((string) $tpl->document_type) }}" data-category="{{ $category }}"
                data-status="{{ $tpl->status }}" data-template-name="{{ e($tpl->name) }}"
                data-archive-url="{{ route('admin.document-template.archive', $tpl->id) }}"
                data-activate-url="{{ route('admin.document-template.activate', $tpl->id) }}" tabindex="0" role="button"
                aria-label="Preview {{ $tpl->name }}" onclick="openTemplatePreview({{ $tpl->id }})">
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
                            <span class="status-badge template-default-badge" data-template-default-badge>
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
                            <button type="button" class="ui-icon-btn view template-action-btn" title="Preview"
                                aria-label="Preview template"
                                onclick="event.stopPropagation(); openTemplatePreview({{ $tpl->id }})">
                                <i class="fa-solid fa-eye"></i>
                            </button>

                            @if ($tpl->status === 'active')
                            <button type="button" class="ui-icon-btn template-action-btn template-archive-btn"
                                title="Archive" aria-label="Archive template" data-template-action="archive"
                                data-template-id="{{ $tpl->id }}"
                                onclick="event.stopPropagation(); window.handleTemplateActionClick(this)">
                                <i class="fa-solid fa-box-archive"></i>
                            </button>
                            @elseif($tpl->status === 'archived')
                            <button type="button" class="ui-icon-btn template-action-btn template-activate-btn"
                                title="Activate" aria-label="Activate template" data-template-action="activate"
                                data-template-id="{{ $tpl->id }}"
                                onclick="event.stopPropagation(); window.handleTemplateActionClick(this)">
                                <i class="fa-solid fa-circle-check"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
            @endforeach
        </section>

        <section id="templateClientEmpty" class="section-card template-empty-card template-client-empty" hidden>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h2 class="empty-state-title" id="templateEmptyTitle">No templates found</h2>
                <p class="empty-state-sub" id="templateEmptySub">No templates match your current search or
                    filters.</p>
                <button type="button" id="clearTemplateFiltersBtn" class="empty-state-btn">
                    <i class="fa-solid fa-rotate-left"></i>
                    Clear search
                </button>
            </div>
        </section>
        @endif
    </div>
</main>

<div class="template-preview-backdrop" id="templatePreviewBackdrop" aria-hidden="true">
    <div class="template-preview-modal" role="dialog" aria-modal="true" aria-labelledby="templatePreviewTitle">
        <div class="template-preview-header">
            <div class="template-preview-heading">
                <div class="template-preview-title" id="templatePreviewTitle">Template Preview</div>
                <div class="template-preview-subtitle" id="templatePreviewSubtitle">Loading...</div>
            </div>
            <button type="button" class="template-preview-close" id="closeTemplatePreview" aria-label="Close preview">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="template-preview-meta" id="templatePreviewMeta"></div>

        <div class="template-preview-body">
            <iframe class="preview-frame" id="templatePreviewFrame"></iframe>
        </div>

        <div class="template-preview-footer" id="templatePreviewFooter"></div>
    </div>
</div>

<div class="template-action-modal" id="templateArchiveModal" aria-hidden="true" role="dialog" aria-modal="true"
    aria-labelledby="templateArchiveTitle">
    <div class="template-action-modal-backdrop" data-template-archive-close></div>
    <div class="template-action-modal-card template-archive-modal-card">
        <button type="button" class="template-action-modal-x" data-template-archive-close aria-label="Close modal">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="template-action-modal-hero">
            <div class="template-action-modal-icon">
                <i class="fa-solid fa-box-archive"></i>
            </div>
            <div>
                <p class="template-action-modal-eyebrow">Template action</p>
                <h2 class="template-action-modal-title" id="templateArchiveTitle">Archive this template?</h2>
                <p class="template-action-modal-subtitle">This template will be hidden from active template lists.</p>
            </div>
        </div>

        <div class="template-action-modal-body">
            <div class="template-action-summary-card">
                <div class="template-action-summary-icon">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="min-w-0">
                    <span>Selected template</span>
                    <strong id="templateArchiveName">Template</strong>
                </div>
            </div>

            <div class="template-action-warning">
                <i class="fa-solid fa-circle-info"></i>
                <p>You can activate this template again later from the archived templates filter.</p>
            </div>
        </div>

        <div class="template-action-modal-footer">
            <button type="button" class="ui-btn ui-btn-secondary template-modal-keep-btn" data-template-archive-close>
                <i class="fa-solid fa-arrow-left"></i>
                Keep
            </button>
            <button type="button" class="ui-btn template-modal-archive-btn" id="confirmTemplateArchiveBtn">
                <i class="fa-solid fa-box-archive"></i>
                Yes, Archive
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
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
        return d.content || '<p style="padding:1rem;color:#9ca3af;">No preview available.</p>';
    }

    function openPreviewModal() {
        const backdrop = document.getElementById('templatePreviewBackdrop');
        backdrop?.classList.add('show');
        backdrop?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closePreviewModal() {
        const backdrop = document.getElementById('templatePreviewBackdrop');
        backdrop?.classList.remove('show');
        backdrop?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        document.getElementById('templatePreviewFrame').srcdoc = '';
        currentPreviewTemplateId = null;
        currentPreviewPayload = null;
        document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
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
        return status === 'active' ? 'badge-active' : 'badge-archived';
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
        <button type="button" class="ui-icon-btn view template-action-btn" title="Preview"
            aria-label="Preview template" onclick="event.stopPropagation(); openTemplatePreview(${Number(id)})">
            <i class="fa-solid fa-eye"></i>
        </button>
        ${status === 'active' ? `
            <button type="button" class="ui-icon-btn template-action-btn template-archive-btn"
                title="Archive" aria-label="Archive template"
                data-template-action="archive" data-template-id="${id}"
                onclick="event.stopPropagation(); window.handleTemplateActionClick(this)">
                <i class="fa-solid fa-box-archive"></i>
            </button>
        ` : `
            <button type="button" class="ui-icon-btn template-action-btn template-activate-btn"
                title="Activate" aria-label="Activate template"
                data-template-action="activate" data-template-id="${id}"
                onclick="event.stopPropagation(); window.handleTemplateActionClick(this)">
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
        const metaEl = document.getElementById('templatePreviewMeta');
        if (!metaEl || !d) return;

        const status = d.status || 'active';
        const statusClass = getStatusBadgeClass(status);

        metaEl.innerHTML = `
            <span class="template-preview-chip status-badge">
                <i class="fa-solid fa-file-lines"></i>
                ${templateEscapeHtml(formatTitle(d.document_type))}
            </span>
            <span class="template-preview-chip status-badge">
                <i class="fa-solid fa-layer-group"></i>
                ${templateEscapeHtml(d.category || '—')}
            </span>
            <span class="template-preview-chip status-badge">
                <i class="fa-solid fa-print"></i>
                ${templateEscapeHtml(d.paper_size || '—')} • ${templateEscapeHtml(formatTitle(d.orientation || ''))}
            </span>
            <span class="template-preview-chip status-badge ${statusClass}">
                ${templateEscapeHtml(getStatusLabel(status))}
            </span>
            ${d.is_default ? `<span class="template-preview-chip status-badge template-default-badge">Default</span>` : ''}
        `;
    }

    function renderPreviewFooter(d) {
        const footerEl = document.getElementById('templatePreviewFooter');
        if (!footerEl || !d) return;

        footerEl.innerHTML = `
            ${d.status === 'active' ? `
                    <button type="button" class="ui-btn template-modal-archive-btn"
                        data-template-action="archive" data-template-id="${d.id}">
                        <i class="fa-solid fa-box-archive"></i>
                        Archive
                    </button>
                ` : `
                    <button type="button" class="ui-btn template-activate-btn template-preview-activate-btn"
                        data-template-action="activate" data-template-id="${d.id}">
                        <i class="fa-solid fa-circle-check"></i>
                        Activate
                    </button>
                `}
            <button type="button" class="ui-btn ui-btn-secondary" onclick="closePreviewModal()">
                <i class="fa-solid fa-xmark"></i>
                Close
            </button>
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
            '<p style="padding:2rem;text-align:center;color:#94a3b8;font-family:Arial,sans-serif;">Loading preview...</p>';

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
                '<p style="padding:2rem;text-align:center;color:#dc2626;font-family:Arial,sans-serif;">Failed to load template preview.</p>';
        }
    }

    function hasActiveTemplateFilters() {
        const q = (document.getElementById('templateSearch')?.value || '').trim();
        const activeCategory = document.querySelector('.tab-btn[data-filter-type="category"].active')?.dataset.filter ||
            '';
        const activeStatus = document.querySelector('.tab-btn[data-filter-type="status"].active')?.dataset.filter || '';
        return Boolean(q || activeCategory || activeStatus);
    }

    function filterTemplateCards() {
        const q = (document.getElementById('templateSearch')?.value || '').trim().toLowerCase();
        const activeCategory = document.querySelector('.tab-btn[data-filter-type="category"].active')?.dataset.filter ||
            '';
        const activeStatus = document.querySelector('.tab-btn[data-filter-type="status"].active')?.dataset.filter || '';
        const grid = document.getElementById('templatesGrid');
        const cards = document.querySelectorAll('.template-card');
        const empty = document.getElementById('templateClientEmpty');
        const title = document.getElementById('templateEmptyTitle');
        const sub = document.getElementById('templateEmptySub');
        const clearBtn = document.getElementById('clearTemplateFiltersBtn');

        let visible = 0;

        cards.forEach(card => {
            const haystack = `${card.dataset.name || ''} ${card.dataset.type || ''}`;
            const matchSearch = !q || haystack.includes(q);
            const matchCategory = !activeCategory || card.dataset.category === activeCategory;
            const matchStatus = !activeStatus || card.dataset.status === activeStatus;
            const show = matchSearch && matchCategory && matchStatus;

            card.hidden = !show;
            if (show) visible++;
        });

        if (grid) grid.hidden = visible === 0;
        if (empty) empty.hidden = visible !== 0;

        if (visible === 0 && title && sub && clearBtn) {
            if (q) {
                title.textContent = `No results found for “${document.getElementById('templateSearch').value.trim()}”`;
                sub.textContent = 'Clear your search or adjust the template filters.';
                clearBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Clear search';
            } else if (hasActiveTemplateFilters()) {
                title.textContent = 'No templates match your filter';
                sub.textContent = 'Clear the selected filter to show all available templates.';
                clearBtn.innerHTML = '<i class="fa-solid fa-rotate-left"></i> Clear filters';
            } else {
                title.textContent = 'No templates available';
                sub.textContent = 'No default document templates are currently available.';
                clearBtn.innerHTML = '<i class="fa-solid fa-rotate-left"></i> Refresh list';
            }
        }
    }

    function clearTemplateFilters() {
        const searchInput = document.getElementById('templateSearch');
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input', {
                bubbles: true
            }));
        }

        ['category', 'status'].forEach(type => {
            const tabs = document.querySelectorAll(`.tab-btn[data-filter-type="${type}"]`);
            tabs.forEach((tab, index) => tab.classList.toggle('active', index === 0));
        });

        filterTemplateCards();
        searchInput?.focus();
    }

    function openTemplateArchiveModal(actionData) {
        pendingTemplateAction = actionData;

        const modal = document.getElementById('templateArchiveModal');
        const nameEl = document.getElementById('templateArchiveName');

        if (nameEl) nameEl.textContent = actionData.name || 'Template';
        modal?.classList.add('open');
        modal?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-lock');
    }

    function closeTemplateArchiveModal() {
        const modal = document.getElementById('templateArchiveModal');
        modal?.classList.remove('open');
        modal?.setAttribute('aria-hidden', 'true');
        pendingTemplateAction = null;

        if (!document.querySelector('.template-preview-backdrop.show')) {
            document.body.classList.remove('modal-lock');
        }
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

            applyTemplateStatus(actionData.id, {
                ...payload,
                action: actionData.action
            });
            closeTemplateArchiveModal();

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

    document.addEventListener('DOMContentLoaded', () => {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const searchInput = document.getElementById('templateSearch');
        const clearBtn = document.getElementById('templateSearchClear');
        const micBtn = document.getElementById('templateMicToggleBtn');
        const status = document.getElementById('templateVoiceStatus');

        document.querySelectorAll('.tab-btn[data-filter-type]').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.filterType;
                document.querySelectorAll(`.tab-btn[data-filter-type="${type}"]`).forEach(b => b
                    .classList.remove('active'));
                btn.classList.add('active');
                filterTemplateCards();
            });
        });

        searchInput?.addEventListener('input', filterTemplateCards);
        clearBtn?.addEventListener('click', clearTemplateFilters);
        document.getElementById('clearTemplateFiltersBtn')?.addEventListener('click', clearTemplateFilters);

        document.querySelectorAll('[data-template-archive-close]').forEach(button => {
            button.addEventListener('click', closeTemplateArchiveModal);
        });

        document.getElementById('confirmTemplateArchiveBtn')?.addEventListener('click', () => {
            if (pendingTemplateAction) submitTemplateAction(pendingTemplateAction);
        });

        if (searchInput && clearBtn && micBtn && status && SpeechRecognition) {
            let listening = false;
            let manualStop = false;
            let recognition = null;

            const setStatus = (text, state) => {
                status.textContent = text;
                status.className = 'voice-status';
                if (state) status.classList.add(`is-${state}`);
                status.classList.remove('hidden');
            };

            const hideStatus = (delay = 0) => {
                window.setTimeout(() => status.classList.add('hidden'), delay);
            };

            const setMicState = (isActive) => {
                micBtn.classList.toggle('mic-active', isActive);
                micBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                micBtn.innerHTML = isActive ?
                    '<i class="fa-solid fa-stop"></i>' :
                    '<i class="fa-solid fa-microphone"></i>';
            };

            const stopListeningNow = () => {
                manualStop = true;
                listening = false;
                setMicState(false);
                setStatus('Voice input stopped.', 'success');
                hideStatus(1200);

                if (recognition) {
                    try {
                        recognition.abort();
                    } catch (error) {
                        try {
                            recognition.stop();
                        } catch (err) { }
                    }
                }
            };

            const createRecognition = () => {
                const r = new SpeechRecognition();
                r.lang = 'en-US';
                r.continuous = false;
                r.interimResults = true;
                r.maxAlternatives = 1;

                let sawSpeech = false;
                let timeoutId = null;
                const LISTEN_TIMEOUT = 6000;
                const clearTimeout_ = () => {
                    if (timeoutId) {
                        clearTimeout(timeoutId);
                        timeoutId = null;
                    }
                };

                r.onstart = () => {
                    timeoutId = window.setTimeout(() => {
                        if (listening && !sawSpeech) r.stop();
                    }, LISTEN_TIMEOUT);
                };

                r.onspeechend = () => {
                    clearTimeout_();
                    try {
                        r.stop();
                    } catch (error) { }
                };

                r.onresult = (event) => {
                    let transcript = '';

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const result = event.results[i];
                        const chunk = result?.[0]?.transcript?.trim() || '';
                        if (!chunk) continue;

                        sawSpeech = true;
                        transcript = result.isFinal ? `${transcript} ${chunk}`.trim() : (transcript ||
                            chunk);
                    }

                    transcript = transcript.trim();
                    if (transcript) {
                        clearTimeout_();
                        searchInput.value = transcript;
                        searchInput.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        setStatus('Listening...', 'listening');
                    }
                };

                r.onerror = () => {
                    clearTimeout_();
                    listening = false;
                    if (manualStop) {
                        manualStop = false;
                        return;
                    }
                    setMicState(false);
                    setStatus("Didn't catch that. Try again.", 'error');
                    hideStatus(2500);
                };

                r.onend = () => {
                    clearTimeout_();
                    if (manualStop) {
                        manualStop = false;
                        listening = false;
                        setMicState(false);
                        return;
                    }

                    const hadSpeech = sawSpeech || !!searchInput.value.trim();
                    listening = false;
                    setMicState(false);
                    setStatus(hadSpeech ? 'Voice captured.' : "Didn't catch that. Try again.",
                        hadSpeech ? 'success' : 'error');
                    hideStatus(hadSpeech ? 2200 : 2500);
                };

                return r;
            };

            micBtn.addEventListener('click', () => {
                if (listening && recognition) {
                    stopListeningNow();
                    return;
                }

                recognition = createRecognition();

                try {
                    recognition.start();
                } catch (error) {
                    setStatus('Unable to start voice input.', 'error');
                    hideStatus(2500);
                    setMicState(false);
                    listening = false;
                    return;
                }

                listening = true;
                setMicState(true);
                setStatus('Listening...', 'listening');
            });
        } else if (micBtn && !SpeechRecognition) {
            micBtn.disabled = true;
            micBtn.setAttribute('aria-disabled', 'true');
        }

        document.getElementById('closeTemplatePreview')?.addEventListener('click', closePreviewModal);
        document.getElementById('templatePreviewBackdrop')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('templatePreviewBackdrop')) closePreviewModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (document.getElementById('templateArchiveModal')?.classList.contains('open')) {
                    closeTemplateArchiveModal();
                    return;
                }
                closePreviewModal();
            }
        });

        filterTemplateCards();
    });
</script>
@endsection