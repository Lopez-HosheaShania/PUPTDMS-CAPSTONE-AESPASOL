@extends('layouts.admin')

@section('title', 'Document Templates | PUP Taguig Dental Clinic')

@section('content')
<main id="mainContent" class="admin-page-shell document-templates-page page-enter">
    <div class="admin-page-container">

        <div class="page-banner">
            <div class="page-banner-inner">
                <div>
                    <h1 class="page-title">Document Templates</h1>
                </div>
                <div class="banner-badge">
                    <i class="fa-solid fa-lock"></i>
                    Default templates only
                </div>
            </div>
        </div>

        @php
        $stats = $stats ?? [];
        $totalTemplates = $stats['total'] ?? 0;
        $activeTemplates = $stats['active'] ?? 0;
        $archivedTemplates = $stats['archived'] ?? 0;
        @endphp

        <div class="stat-grid">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div class="stat-value">{{ $totalTemplates }}</div>
                <div class="stat-lbl">Total Templates</div>
            </div>
            <div class="stat-card active">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-value">{{ $activeTemplates }}</div>
                <div class="stat-lbl">Active</div>
            </div>
            <div class="stat-card archived">
                <div class="stat-icon"><i class="fa-solid fa-box-archive"></i></div>
                <div class="stat-value">{{ $archivedTemplates }}</div>
                <div class="stat-lbl">Archived</div>
            </div>
        </div>

        <div class="toolbar">
            <div class="template-search-row">
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="templateSearch" class="no-voice" placeholder="Search templates..."
                        autocomplete="off">
                </div>

                <button type="button" id="templateSearchClear" class="template-search-clear-btn hidden" title="Clear">
                    Clear
                </button>

                <div class="template-voice-toggle">
                    <button type="button" id="templateMicToggleBtn" class="voice-search-mic external"
                        aria-label="Toggle voice input" aria-pressed="false">
                        <i class="fa-solid fa-microphone"></i>
                    </button>
                    <span id="templateVoiceStatus" class="voice-status hidden" aria-live="polite"></span>
                </div>
            </div>

            <div class="tab-bar template-filter-tabs">
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

            <div class="tab-bar template-filter-tabs">
                <button type="button" class="tab-btn active" data-filter="" data-filter-type="status">
                    All
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

        @if(empty($templates) || $templates->isEmpty())
        <div class="empty-state">
            <div class="template-empty-icon">
                <i class="fa-solid fa-file-circle-xmark"></i>
            </div>
            <p class="font-semibold text-gray-500 mb-1">No templates available</p>
            <p class="text-sm mb-5">No default document templates are currently available.</p>
        </div>
        @else
        <div>
            <div class="templates-grid" id="templatesGrid">
                @foreach($templates as $tpl)
                @php
                $category = strtolower(trim((string) ($tpl->category ?? '')));
                $dt = strtolower($tpl->document_type ?? '');

                if ($category === '') {
                if (str_contains($dt, 'clearance')) $category = 'clearance';
                elseif (str_contains($dt, 'record')) $category = 'record';
                elseif (str_contains($dt, 'report')) $category = 'report';
                elseif (str_contains($dt, 'inventory')) $category = 'inventory';
                else $category = 'other';
                }

                if (!in_array($category, ['clearance', 'record', 'report', 'inventory'], true)) {
                $category = 'other';
                }
                @endphp

                <div class="template-card status-{{ $tpl->status }}" data-id="{{ $tpl->id }}"
                    data-name="{{ strtolower($tpl->name) }}" data-type="{{ strtolower($tpl->document_type) }}"
                    data-category="{{ $category }}" data-status="{{ $tpl->status }}"
                    onclick="openTemplatePreview({{ $tpl->id }})">
                    <div class="template-card-top">
                        <div class="template-top-row">
                            <div class="template-doc-icon">
                                @if($category === 'clearance')
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

                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.35rem;">
                                <span class="status-badge {{ $tpl->status }}">
                                    {{ ucfirst($tpl->status) }}
                                </span>

                                @if($tpl->is_default)
                                <span class="status-badge" style="background:#dbeafe;color:#1d4ed8;">
                                    Default
                                </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="template-name">{{ $tpl->name }}</div>
                            <div class="template-code">{{ $tpl->code ?? 'TPL-' . str_pad($tpl->id, 4, '0',
                                STR_PAD_LEFT) }}</div>
                        </div>
                    </div>

                    <div class="template-card-body">
                        <p class="template-description">
                            {{ $tpl->description ?? $tpl->notes ?? 'Default system template.' }}
                        </p>

                        <div class="template-meta-row">
                            <div class="flex items-center gap-2">
                                <span class="template-meta-item">
                                    <i class="fa-solid fa-tag" style="font-size:.6rem;color:#8B0000;"></i>
                                    {{ ucwords(str_replace('_', ' ', $tpl->document_type)) }}
                                </span>
                            </div>

                            <div class="template-actions" onclick="event.stopPropagation()">
                                <button type="button" class="action-btn view" title="Preview"
                                    onclick="openTemplatePreview({{ $tpl->id }})">
                                    <i class="fa-solid fa-eye"></i>
                                </button>

                                @if($tpl->status === 'active')
                                <form action="{{ route('admin.document-template.archive', $tpl->id) }}" method="POST"
                                    style="display:inline;" onsubmit="return confirm('Archive this template?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="action-btn del" title="Archive">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </button>
                                </form>
                                @elseif($tpl->status === 'archived')
                                <form action="{{ route('admin.document-template.activate', $tpl->id) }}" method="POST"
                                    style="display:inline;" onsubmit="return confirm('Activate this template?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="action-btn copy" title="Activate">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div id="templateClientEmpty" class="empty-state" style="display:none;">
                <i class="fa-solid fa-magnifying-glass"
                    style="font-size:2rem;color:#e5e7eb;display:block;margin-bottom:1rem;"></i>
                <p class="font-semibold text-gray-500 mb-1">No templates match your filter</p>
                <p class="text-sm">Try a different category or search term.</p>
            </div>
        </div>
        @endif

    </div>
</main>

<div class="template-preview-backdrop" id="templatePreviewBackdrop">
    <div class="template-preview-modal" role="dialog" aria-modal="true" aria-labelledby="templatePreviewTitle">
        <div class="template-preview-header">
            <div>
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
@endsection

@section('scripts')
<script>
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    function formatTitle(value) {
        if (!value) return '—';
        return value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function buildPreviewDocument(d) {
        return d.content || '<p style="padding:1rem;color:#9ca3af;">No preview available.</p>';
    }

    function openPreviewModal() {
        const backdrop = document.getElementById('templatePreviewBackdrop');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closePreviewModal() {
        const backdrop = document.getElementById('templatePreviewBackdrop');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('templatePreviewFrame').srcdoc = '';
    }

    async function openTemplatePreview(id) {
        document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
        const card = document.querySelector(`.template-card[data-id="${id}"]`);
        if (card) card.classList.add('selected');

        openPreviewModal();

        const titleEl = document.getElementById('templatePreviewTitle');
        const subtitleEl = document.getElementById('templatePreviewSubtitle');
        const metaEl = document.getElementById('templatePreviewMeta');
        const frameEl = document.getElementById('templatePreviewFrame');
        const footerEl = document.getElementById('templatePreviewFooter');

        titleEl.textContent = 'Loading...';
        subtitleEl.textContent = 'Please wait';
        metaEl.innerHTML = '';
        footerEl.innerHTML = '';
        frameEl.srcdoc = '<p style="padding:2rem;text-align:center;color:#94a3b8;font-family:Arial,sans-serif;">Loading preview...</p>';

        try {
            const res = await fetch(`/admin/document-template/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!res.ok) throw new Error('Failed to fetch');

            const d = await res.json();

            titleEl.textContent = d.name || 'Template Preview';
            subtitleEl.textContent = formatTitle(d.document_type);

            metaEl.innerHTML = `
                <span class="template-preview-chip">
                    <i class="fa-solid fa-file-lines"></i>
                    ${formatTitle(d.document_type)}
                </span>
                <span class="template-preview-chip">
                    <i class="fa-solid fa-layer-group"></i>
                    ${d.category || '—'}
                </span>
                <span class="template-preview-chip">
                    <i class="fa-solid fa-print"></i>
                    ${d.paper_size || '—'} • ${formatTitle(d.orientation || '')}
                </span>
                <span class="template-preview-chip" style="background:${d.status === 'active' ? '#d1fae5' : '#f3f4f6'};color:${d.status === 'active' ? '#065f46' : '#6b7280'};">
                    ${d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '—'}
                </span>
                ${d.is_default ? `<span class="template-preview-chip" style="background:#dbeafe;color:#1d4ed8;">Default</span>` : ''}
            `;

            frameEl.srcdoc = buildPreviewDocument(d);

            footerEl.innerHTML = `
                ${d.status === 'active' ? `
                    <form action="/admin/document-template/${d.id}/archive" method="POST" onsubmit="return confirm('Archive this template?')" style="display:inline;">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="PATCH">
                        <button type="submit" class="btn-secondary">
                            <i class="fa-solid fa-box-archive"></i> Archive
                        </button>
                    </form>
                ` : `
                    <form action="/admin/document-template/${d.id}/activate" method="POST" onsubmit="return confirm('Activate this template?')" style="display:inline;">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="PATCH">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-circle-check"></i> Activate
                        </button>
                    </form>
                `}
            `;
        } catch (e) {
            titleEl.textContent = 'Template Preview';
            subtitleEl.textContent = 'Failed to load';
            frameEl.srcdoc = '<p style="padding:2rem;text-align:center;color:#dc2626;font-family:Arial,sans-serif;">Failed to load template preview.</p>';
        }
    }

    function filterTemplateCards() {
        const q = (document.getElementById('templateSearch')?.value || '').trim().toLowerCase();
        const activeCategory = document.querySelector('.tab-btn[data-filter-type="category"].active')?.dataset.filter || '';
        const activeStatus = document.querySelector('.tab-btn[data-filter-type="status"].active')?.dataset.filter || '';
        const cards = document.querySelectorAll('.template-card');
        const empty = document.getElementById('templateClientEmpty');

        let visible = 0;

        cards.forEach(card => {
            const matchSearch = !q || card.dataset.name.includes(q) || card.dataset.type.includes(q);
            const matchCategory = !activeCategory || card.dataset.category === activeCategory;
            const matchStatus = !activeStatus || card.dataset.status === activeStatus;
            const show = matchSearch && matchCategory && matchStatus;

            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const searchInput = document.getElementById('templateSearch');
        const clearBtn = document.getElementById('templateSearchClear');
        const micBtn = document.getElementById('templateMicToggleBtn');
        const status = document.getElementById('templateVoiceStatus');

        if (!searchInput || !clearBtn || !micBtn || !status) return;

        if (!SpeechRecognition) {
            micBtn.disabled = true;
            micBtn.setAttribute('aria-disabled', 'true');
            return;
        }

        let listening = false;
        let manualStop = false;
        let recognition = null;

        const toggleSearchClear = (input) => {
            if (!clearBtn) return;

            if ((input?.value || '').trim().length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        };

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
            micBtn.innerHTML = isActive
                ? '<i class="fa-solid fa-stop"></i>'
                : '<i class="fa-solid fa-microphone"></i>';
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
                    try { recognition.stop(); } catch (err) { }
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
                    if (listening && !sawSpeech) {
                        r.stop();
                    }
                }, LISTEN_TIMEOUT);
            };

            r.onspeechend = () => {
                clearTimeout_();
                try { r.stop(); } catch (error) { }
            };

            r.onresult = (event) => {
                let transcript = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const result = event.results[i];
                    const chunk = result?.[0]?.transcript?.trim() || '';
                    if (!chunk) continue;

                    sawSpeech = true;

                    if (result.isFinal) {
                        transcript = `${transcript} ${chunk}`.trim();
                    } else if (!transcript) {
                        transcript = chunk;
                    }
                }

                transcript = transcript.trim();

                if (transcript) {
                    clearTimeout_();
                    searchInput.value = transcript;
                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
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
                if (hadSpeech) {
                    setStatus('Voice captured.', 'success');
                    hideStatus(2200);
                } else {
                    setStatus("Didn't catch that. Try again.", 'error');
                    hideStatus(2500);
                }
            };

            return r;
        };

        const clearSearch = () => {
            searchInput.value = '';
            toggleSearchClear(searchInput);
            filterTemplateCards();
            searchInput.focus();
        };

        document.querySelectorAll('.tab-btn[data-filter-type]').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.filterType;
                document.querySelectorAll(`.tab-btn[data-filter-type="${type}"]`).forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterTemplateCards();
            });
        });

        searchInput.addEventListener('input', () => {
            toggleSearchClear(searchInput);
            filterTemplateCards();
        });

        clearBtn.addEventListener('click', () => {
            clearSearch();
        });

        toggleSearchClear(searchInput);

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

        document.getElementById('closeTemplatePreview')?.addEventListener('click', closePreviewModal);

        document.getElementById('templatePreviewBackdrop')?.addEventListener('click', (e) => {
            if (e.target === document.getElementById('templatePreviewBackdrop')) {
                closePreviewModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closePreviewModal();
            }
        });
    });
</script>
@endsection