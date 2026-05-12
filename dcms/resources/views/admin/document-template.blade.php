@extends('layouts.admin')

@section('title', 'Document Templates | PUP Taguig Dental Clinic')

@section('styles')
<style>
    .document-templates-page {
        margin-left: var(--sidebar-w, 256px);
        padding: calc(var(--header-h, 70px) + 12px) 1.5rem 2rem;
        min-height: 100vh;
        background: #f6f7f9;
        overflow-x: hidden;
    }

    .page-banner {
        background: linear-gradient(135deg, #6b0000 0%, #8B0000 60%, #c0392b 100%);
        padding: 1.75rem 2rem 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(139, 0, 0, .25);
        border-radius: 16px;
        margin-bottom: 1.5rem;
    }

    .page-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .page-banner-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 900;
        color: #fff;
        line-height: 1.1;
        letter-spacing: -.02em;
    }

    .page-subtitle {
        font-size: .8rem;
        color: rgba(255, 255, 255, .7);
        margin-top: .35rem;
    }

    .banner-badge {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .7rem 1rem;
        border-radius: 10px;
        background: rgba(255, 255, 255, .12);
        color: #fff;
        font-size: .82rem;
        font-weight: 700;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 1.75rem;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        padding: 1.1rem 1.1rem 1rem;
        position: relative;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .07);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 14px 14px 0 0;
    }

    .stat-card.total::before      { background: #e5e7eb; }
    .stat-card.active::before     { background: linear-gradient(90deg, #10b981, #34d399); }
    .stat-card.archived::before   { background: linear-gradient(90deg, #9ca3af, #d1d5db); }

    .stat-icon {
        width: 34px; height: 34px;
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem;
        margin-bottom: .75rem;
    }

    .stat-card.total .stat-icon    { background: #f3f4f6; color: #6b7280; }
    .stat-card.active .stat-icon   { background: #d1fae5; color: #059669; }
    .stat-card.archived .stat-icon { background: #f3f4f6; color: #9ca3af; }

    .stat-val {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -.03em;
        margin-bottom: .25rem;
    }

    .stat-card.total .stat-val    { color: #111827; }
    .stat-card.active .stat-val   { color: #059669; }
    .stat-card.archived .stat-val { color: #9ca3af; }

    .stat-lbl {
        font-size: .67rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #9ca3af;
    }

    .toolbar {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .template-search-row {
        display: flex;
        align-items: center;
        gap: .65rem;
        width: 100%;
        max-width: 430px;
        margin-bottom: 0;
        flex: 0 0 auto;
    }

    .template-search-row .search-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #FAFAF9;
        border: 1.5px solid #E0DDD8;
        border-radius: 12px;
        padding: 0 14px;
        height: 38px;
        transition: border-color .2s, box-shadow .2s;
        min-width: 0;
        flex-shrink: 1;
        width: 260px;
        box-shadow: none;
    }

    .template-search-row .search-wrap:focus-within {
        border-color: var(--crimson);
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
    }

    .template-search-row .search-wrap .search-icon {
        color: var(--crimson);
        font-size: 13px;
        flex-shrink: 0;
    }

    .template-search-row .search-wrap input {
        border: none;
        background: none;
        outline: none;
        font-size: 13px;
        color: #333;
        width: 100%;
        padding: 0;
    }

    .template-search-row .search-wrap input::placeholder {
        color: #B0ABA6;
    }

    .template-search-clear-btn {
        border: none;
        background: transparent;
        color: #dc2626;
        font-size: .78rem;
        font-weight: 600;
        line-height: 1;
        padding: 0 .1rem;
        margin: 0;
        cursor: pointer;
        flex: 0 0 auto;
        transition: color .15s ease;
    }

    .template-search-clear-btn:hover {
        color: #991b1b;
    }

    .template-search-clear-btn.hidden {
        display: none;
    }

    .template-voice-toggle {
        position: relative;
        display: inline-flex;
        align-items: center;
        flex-shrink: 0;
    }

    .template-voice-status {
        position: absolute;
        right: 0;
        top: -1.15rem;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        font-size: .74rem;
        font-weight: 700;
        line-height: 1;
        padding: .18rem .48rem;
        border-radius: 999px;
        pointer-events: none;
        z-index: 6;
        background: #f0fdf4;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
    }

    .template-voice-status.hidden {
        display: none;
    }

    .template-voice-status.is-listening {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .template-voice-status.is-error {
        color: #b91c1c;
        border-color: #fecaca;
        background: #fef2f2;
    }

    .template-voice-status.is-success {
        color: #166534;
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .template-search-row .voice-search-mic.external {
        display: inline-flex !important;
        width: 40px;
        height: 40px;
        border-radius: 999px;
        align-items: center;
        justify-content: center;
        background: #4b5563;
        color: #ffffff;
        box-shadow: 0 6px 18px rgba(75, 85, 99, 0.12);
        border: none;
        margin-left: 0;
        flex-shrink: 0;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    }

    .template-search-row .voice-search-mic.external:hover {
        background: #374151;
    }

    .template-search-row .voice-search-mic.external.mic-active {
        background: #c0392b;
        transform: scale(1.1);
        animation: templateMicPulse 1.2s ease-in-out infinite;
    }

    .template-search-row .voice-search-mic.external i {
        font-size: 12px;
        line-height: 1;
    }

    @keyframes templateMicPulse {
        0% {
            box-shadow: 0 0 0 0 rgba(192, 57, 43, .35);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(192, 57, 43, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(192, 57, 43, 0);
        }
    }

    .search-wrap.voice-search-wrap {
        position: relative;
        padding-right: 42px;
    }

    .search-wrap.voice-search-wrap input.has-voice-padding {
        padding-right: 0 !important;
    }

    .search-wrap.voice-search-wrap .voice-search-mic {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        border: none;
        background: transparent;
        padding: 0;
        margin: 0;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #8B0000;
        cursor: pointer;
        z-index: 5;
    }

    .search-wrap.voice-search-wrap .voice-search-mic i {
        font-size: 13px;
        line-height: 1;
    }

    .search-wrap.voice-search-wrap [data-voice-status] {
        position: absolute;
        right: 0;
        top: -1.35rem;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        font-size: .74rem;
        font-weight: 700;
        line-height: 1;
        padding: .18rem .48rem;
        border-radius: 999px;
        pointer-events: none;
        z-index: 6;
        background: rgba(255, 255, 255, .92);
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
    }

    .search-wrap.voice-search-wrap [data-voice-status].hidden {
        display: none;
    }

    .search-wrap.voice-search-wrap [data-voice-status].is-listening {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .search-wrap.voice-search-wrap [data-voice-status].is-error {
        color: #b91c1c;
        border-color: #fecaca;
        background: #fef2f2;
    }

    .search-wrap.voice-search-wrap [data-voice-status].is-success {
        color: #166534;
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .btn-primary,
    .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: .15s ease;
        white-space: nowrap;
        border: none;
    }

    .btn-primary {
        padding: .52rem 1.1rem;
        background: #8B0000;
        color: #fff;
    }

    .btn-primary:hover { background: #6b0000; }

    .btn-secondary {
        padding: .5rem 1rem;
        background: #fff;
        color: #374151;
        border: 1.5px solid #e5e7eb;
    }

    .btn-secondary:hover { background: #f9fafb; }

    .templates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }

    .template-card {
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        cursor: pointer;
        position: relative;
    }

    .template-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        border-color: #fce8e8;
    }

    .template-card.selected {
        border-color: #8B0000;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .1);
    }

    .template-card-top {
        background: linear-gradient(135deg, #fef2f2, #fff8f8);
        border-bottom: 1px solid #fce8e8;
        padding: 1.25rem 1.25rem 1rem;
        position: relative;
        min-height: 90px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .template-card-top::after {
        content: '';
        position: absolute;
        bottom: -1px; left: 0; right: 0;
        height: 3px;
    }

    .template-card.status-active .template-card-top::after   { background: linear-gradient(90deg, #10b981, #34d399); }
    .template-card.status-archived .template-card-top::after { background: #e5e7eb; }

    .template-doc-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #8B0000, #6b0000);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .9rem;
        margin-bottom: .75rem;
        box-shadow: 0 4px 10px rgba(139, 0, 0, .25);
        flex-shrink: 0;
    }

    .template-top-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .5rem;
    }

    .template-name {
        font-size: .88rem;
        font-weight: 800;
        color: #111827;
        line-height: 1.25;
    }

    .template-code {
        font-size: .65rem;
        color: #9ca3af;
        font-weight: 600;
        font-family: monospace;
        margin-top: .2rem;
    }

    .status-badge {
        display: inline-block;
        padding: .18rem .55rem;
        border-radius: 999px;
        font-size: .63rem;
        font-weight: 700;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .status-badge.active   { background: #d1fae5; color: #065f46; }
    .status-badge.archived { background: #f3f4f6; color: #6b7280; }

    .template-card-body { padding: 1rem 1.25rem; }

    .template-description {
        font-size: .76rem;
        color: #6b7280;
        line-height: 1.5;
        margin-bottom: .85rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .template-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: .7rem;
        color: #9ca3af;
        padding-top: .75rem;
        border-top: 1px solid #f8f5f5;
    }

    .template-meta-item {
        display: flex;
        align-items: center;
        gap: .3rem;
        font-weight: 600;
    }

    .template-actions {
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    .act-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px; height: 28px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: .7rem;
        transition: .12s ease;
    }

    .act-btn.view    { background: #fef2f2; color: #8B0000; }
    .act-btn.view:hover { background: #8B0000; color: #fff; }
    .act-btn.copy    { background: #dbeafe; color: #2563eb; }
    .act-btn.copy:hover { background: #2563eb; color: #fff; }
    .act-btn.del     { background: transparent; color: #dc2626; }
    .act-btn.del:hover { background: #fef2f2; }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 14px;

        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .empty-state > i {
        display: block;
        margin: 0 auto 1rem;
        text-align: center;
        line-height: 1;
    }

    .tab-bar {
        display: flex;
        gap: 0;
        margin-bottom: 1.25rem;
        background: #fff;
        border: 1px solid #f0eaea;
        border-radius: 10px;
        padding: .25rem;
    }

    .tab-btn {
        flex: 1;
        padding: .45rem .75rem;
        border: none;
        background: transparent;
        font-size: .78rem;
        font-weight: 600;
        color: #9ca3af;
        border-radius: 7px;
        cursor: pointer;
        transition: .15s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .tab-btn.active {
        background: #8B0000;
        color: #fff;
        box-shadow: 0 2px 8px rgba(139, 0, 0, .2);
    }

    .tab-btn:not(.active):hover {
        background: #fef2f2;
        color: #8B0000;
    }

    .tab-icon {
        font-size: .65rem;
        flex-shrink: 0;
    }

    .template-preview-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .58);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 1rem;
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }

    .template-preview-backdrop.show {
        display: flex;
    }

    .template-preview-modal {
        width: min(1400px, 96vw);
        height: min(92vh, 900px);
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
        display: flex;
        flex-direction: column;
    }

    .template-preview-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #ececec;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        background: #fafafa;
    }

    .template-preview-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: #111827;
        margin-bottom: .2rem;
    }

    .template-preview-subtitle {
        font-size: .8rem;
        color: #9ca3af;
    }

    .template-preview-close {
        width: 38px;
        height: 38px;
        border: none;
        border-radius: 10px;
        background: #f3f4f6;
        color: #374151;
        cursor: pointer;
        font-size: .9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: .15s ease;
        flex-shrink: 0;
    }

    .template-preview-close:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    .template-preview-meta {
        padding: .85rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        background: #fff;
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .template-preview-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .34rem .7rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        background: #f3f4f6;
        color: #4b5563;
    }

    .template-preview-body {
        flex: 1;
        background: #f8fafc;
        padding: .75rem;
    }

    .preview-frame {
        width: 100%;
        height: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }

    .template-preview-footer {
        padding: .9rem 1.25rem;
        border-top: 1px solid #ececec;
        background: #fafafa;
        display: flex;
        justify-content: flex-end;
        gap: .65rem;
        flex-wrap: wrap;
    }

    @media (max-width: 1280px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 767px) {
        .document-templates-page {
            margin-left: 0;
            padding: calc(var(--header-h, 70px) + 12px) 1rem 2rem;
        }

        .search-wrap {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0;
            height: 50px;
            padding: 0 16px;
            border-radius: 14px;
            gap: 12px;
        }

        .search-wrap .search-icon {
            font-size: 15px;
        }

        .search-wrap input {
            font-size: 14px;
        }

        .toolbar { gap: .5rem; }
        .page-banner { border-radius: 14px; padding: 1.1rem 1.1rem 1.4rem; }
        .page-title { font-size: 1.45rem; }
        .page-banner-inner { flex-direction: column; gap: .6rem; }
        .templates-grid { grid-template-columns: 1fr; }
        .tab-btn { font-size: .72rem; padding: .4rem .55rem; }

        .template-preview-modal {
            width: 98vw;
            height: 94vh;
            border-radius: 14px;
        }
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .tab-btn .tab-label { display: none; }
    }

    @media (max-width: 640px) {
        .toolbar {
            flex-direction: column;
            align-items: stretch;
            gap: .6rem;
        }

        .toolbar > .search-wrap {
            flex: none !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;

            height: 52px;             
            padding: 0 18px;
            border-radius: 16px;

            display: flex;
            align-items: center;
            gap: 14px;
        }

        .toolbar > .search-wrap input {
            font-size: 15px;
        }

        .toolbar > .search-wrap .search-icon {
            font-size: 16px;
        }

        .toolbar > .search-wrap .voice-search-mic i {
            font-size: 15px;
        }

        .tab-bar {
            width: 100% !important;
            display: flex;
            gap: .25rem;
            padding: .3rem;
        }

        .tab-btn {
            flex: 1;
            justify-content: center;
            font-size: .7rem;
            padding: .45rem .3rem;
        }

        .toolbar .tab-bar + .tab-bar {
            margin-top: -.2rem;
        }
    }
</style>
@endsection

@section('content')
<div class="document-templates-page">
    <main class="w-full">
        <div class="max-w-[1280px] mx-auto">

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
                $totalTemplates    = $stats['total']    ?? 0;
                $activeTemplates   = $stats['active']   ?? 0;
                $archivedTemplates = $stats['archived'] ?? 0;
            @endphp

            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                    <div class="stat-val">{{ $totalTemplates }}</div>
                    <div class="stat-lbl">Total Templates</div>
                </div>
                <div class="stat-card active">
                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-val">{{ $activeTemplates }}</div>
                    <div class="stat-lbl">Active</div>
                </div>
                <div class="stat-card archived">
                    <div class="stat-icon"><i class="fa-solid fa-box-archive"></i></div>
                    <div class="stat-val">{{ $archivedTemplates }}</div>
                    <div class="stat-lbl">Archived</div>
                </div>
            </div>

            <div class="toolbar">
                <div class="template-search-row">
                    <div class="search-wrap">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" id="templateSearch" class="no-voice" placeholder="Search templates..." autocomplete="off">
                    </div>

                    <button type="button" id="templateSearchClear" class="template-search-clear-btn hidden" title="Clear">
                        Clear
                    </button>

                    <div class="template-voice-toggle">
                        <button type="button" id="templateMicToggleBtn" class="voice-search-mic external" aria-label="Toggle voice input" aria-pressed="false">
                            <i class="fa-solid fa-microphone"></i>
                        </button>
                        <span id="templateVoiceStatus" class="template-voice-status hidden" aria-live="polite"></span>
                    </div>
                </div>

                <div class="tab-bar" style="flex:none;width:auto;margin-bottom:0;">
                    <button type="button" class="tab-btn active" data-filter="" data-filter-type="category">
                        <i class="fa-solid fa-border-all tab-icon"></i>
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

                <div class="tab-bar" style="flex:none;width:auto;margin-bottom:0;">
                    <button type="button" class="tab-btn active" data-filter="" data-filter-type="status">
                        All
                    </button>
                    <button type="button" class="tab-btn" data-filter="active" data-filter-type="status">
                        <span style="width:7px;height:7px;border-radius:50%;background:#10b981;flex-shrink:0;display:inline-block;"></span>
                        Active
                    </button>
                    <button type="button" class="tab-btn" data-filter="archived" data-filter-type="status">
                        <span style="width:7px;height:7px;border-radius:50%;background:#9ca3af;flex-shrink:0;display:inline-block;"></span>
                        Archived
                    </button>
                </div>
            </div>

            @if(empty($templates) || $templates->isEmpty())
                <div class="empty-state">
                    <div style="width:64px;height:64px;border-radius:16px;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <i class="fa-solid fa-file-circle-xmark" style="font-size:1.6rem;color:#f9c1c1;"></i>
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

                            <div
                                class="template-card status-{{ $tpl->status }}"
                                data-id="{{ $tpl->id }}"
                                data-name="{{ strtolower($tpl->name) }}"
                                data-type="{{ strtolower($tpl->document_type) }}"
                                data-category="{{ $category }}"
                                data-status="{{ $tpl->status }}"
                                onclick="openTemplatePreview({{ $tpl->id }})"
                            >
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
                                        <div class="template-code">{{ $tpl->code ?? 'TPL-' . str_pad($tpl->id, 4, '0', STR_PAD_LEFT) }}</div>
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
                                            <button type="button" class="act-btn view" title="Preview" onclick="openTemplatePreview({{ $tpl->id }})">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            @if($tpl->status === 'active')
                                                <form action="{{ route('admin.document-template.archive', $tpl->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Archive this template?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="act-btn del" title="Archive">
                                                        <i class="fa-solid fa-box-archive"></i>
                                                    </button>
                                                </form>
                                            @elseif($tpl->status === 'archived')
                                                <form action="{{ route('admin.document-template.activate', $tpl->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Activate this template?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="act-btn copy" title="Activate">
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
                        <i class="fa-solid fa-magnifying-glass" style="font-size:2rem;color:#e5e7eb;display:block;margin-bottom:1rem;"></i>
                        <p class="font-semibold text-gray-500 mb-1">No templates match your filter</p>
                        <p class="text-sm">Try a different category or search term.</p>
                    </div>
                </div>
            @endif

        </div>
    </main>
</div>

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
            status.className = 'template-voice-status';
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
                    try { recognition.stop(); } catch (err) {}
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
                try { r.stop(); } catch (error) {}
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